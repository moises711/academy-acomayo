<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignPagoDocenteCicloRequest;
use App\Http\Requests\DeleteCicloHistoryRequest;
use App\Models\Auditoria;
use App\Models\Ciclo;
use App\Models\ConvocatoriaBeca;
use App\Models\PagoDocente;
use App\Models\Simulacro;
use App\Support\CicloArchive;
use App\Support\InertiaPaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CicloHistoryController extends Controller
{
    public function index(Request $request, CicloArchive $archive): Response
    {
        Gate::authorize('manageHistory', Ciclo::class);
        $filters = $request->validate([
            'id_ciclo' => ['nullable', 'integer', 'exists:ciclos,id_ciclo'],
            'buscar' => ['nullable', 'string', 'max:100'],
        ]);
        $ciclo = isset($filters['id_ciclo']) ? Ciclo::findOrFail($filters['id_ciclo']) : null;
        $summary = null;
        $error = null;
        if ($ciclo) {
            try {
                $rows = $archive->snapshot($archive->queries($ciclo));
                $summary = array_map('count', $rows);
            } catch (ValidationException $exception) {
                $error = $exception->getMessage();
            }
        }
        $pending = PagoDocente::query()->with('profesor:id_profesor,nombre,apellido')
            ->whereNull('id_ciclo')
            ->when(! empty($filters['buscar']), fn ($query) => $query->whereHas('profesor', fn ($teacher) => $teacher
                ->where(fn ($name) => $name->where('nombre', 'like', '%'.$filters['buscar'].'%')->orWhere('apellido', 'like', '%'.$filters['buscar'].'%'))))
            ->orderBy('periodo')->orderBy('id_pago_docente')->paginate(10)->withQueryString();

        return Inertia::render('Ciclos/Historial', [
            'ciclos' => Ciclo::orderByDesc('fecha_fin')->orderByDesc('id_ciclo')->get(['id_ciclo', 'nombre_ciclo', 'fecha_inicio', 'fecha_fin', 'estado']),
            'seleccionado' => $ciclo ? $ciclo->only(['id_ciclo', 'nombre_ciclo', 'fecha_inicio', 'fecha_fin', 'estado']) : null,
            'resumen' => $summary,
            'errorResumen' => $error,
            'puedeEliminar' => $ciclo && $this->isPast($ciclo),
            'pagosSinCiclo' => InertiaPaginator::from($pending),
            'totalSinCiclo' => PagoDocente::whereNull('id_ciclo')->count(),
            'filters' => $filters,
        ]);
    }

    public function export(Request $request, Ciclo $ciclo, CicloArchive $archive): BinaryFileResponse
    {
        Gate::authorize('manageHistory', $ciclo);
        $this->assertPast($ciclo);
        $this->assertClassified();
        [$rows, $files] = DB::transaction(function () use ($archive, $ciclo): array {
            $rows = $archive->snapshot($archive->queries($ciclo));

            return [$rows, $archive->files($rows)];
        });
        $path = $archive->zip($ciclo, $rows, $files);
        if ($files !== $archive->files($rows)) {
            unlink($path);
            throw ValidationException::withMessages(['respaldo' => 'Un archivo cambió durante la descarga. Genera nuevamente el respaldo.']);
        }
        $token = (string) Str::uuid();
        $request->session()->put('ciclo_respaldo.'.$ciclo->id_ciclo, [
            'token' => $token,
            'user' => $request->user()->id_usuario,
            'fingerprint' => $archive->fingerprint($rows, $files),
            'expires_at' => now()->addHour()->timestamp,
        ]);
        Auditoria::create([
            'id_usuario' => $request->user()->id_usuario, 'accion' => 'exportar', 'tabla' => 'ciclos',
            'id_registro' => (string) $ciclo->id_ciclo,
            'datos_despues' => ['registros' => array_map('count', $rows), 'huella' => $archive->fingerprint($rows, $files)], 'ip' => $request->ip(),
        ]);

        return response()->download($path, 'ciclo-'.$ciclo->id_ciclo.'-'.Str::slug($ciclo->nombre_ciclo).'.zip', [
            'Content-Type' => 'application/zip', 'X-Respaldo-Token' => $token,
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }

    public function destroy(DeleteCicloHistoryRequest $request, Ciclo $ciclo, CicloArchive $archive): RedirectResponse
    {
        $grant = $request->session()->get('ciclo_respaldo.'.$ciclo->id_ciclo);
        if (! $grant || $grant['user'] !== $request->user()->id_usuario || $grant['expires_at'] < now()->timestamp || ! hash_equals($grant['token'], $request->validated('token_respaldo'))) {
            throw ValidationException::withMessages(['respaldo' => 'Descarga un respaldo nuevo desde esta sesión antes de eliminar.']);
        }
        try {
            $files = DB::transaction(function () use ($request, $ciclo, $archive, $grant): array {
                $lockedCycle = Ciclo::whereKey($ciclo->id_ciclo)->lockForUpdate()->firstOrFail();
                $this->assertPast($lockedCycle);
                $this->assertClassified();
                if ($request->validated('nombre_ciclo') !== $lockedCycle->nombre_ciclo) {
                    throw ValidationException::withMessages(['nombre_ciclo' => 'El nombre del ciclo cambió. Actualiza la pantalla.']);
                }
                $rows = $archive->snapshot($archive->queries($lockedCycle), true);
                $files = $archive->files($rows);
                if (! hash_equals($grant['fingerprint'], $archive->fingerprint($rows, $files))) {
                    throw ValidationException::withMessages(['respaldo' => 'Los registros cambiaron después de la descarga. Descarga nuevamente antes de eliminar.']);
                }
                $archive->deleteRows($rows, $request->user(), $request->ip());

                return array_keys($files);
            }, 3);
        } catch (QueryException $exception) {
            report($exception);
            throw ValidationException::withMessages(['ciclo' => 'No se pudo eliminar: hay relaciones pendientes. No se eliminó ningún registro; revisa las relaciones y vuelve a intentarlo.']);
        }
        $request->session()->forget('ciclo_respaldo.'.$ciclo->id_ciclo);
        $failed = false;
        foreach ($files as $file) {
            // Un archivo compartido con otra hoja debe conservarse.
            if (! DB::table('hojas_respuesta')->where('archivo_path', $file)->exists() && ! Storage::disk('local')->delete($file)) {
                $failed = true;
            }
        }
        $response = redirect()->route('historial-ciclos.index')->with('success', 'Ciclo y registros asociados eliminados. Las fichas compartidas y la auditoría se conservaron.');

        return $failed ? $response->with('warning', 'Los registros se eliminaron, pero no se pudo borrar algún archivo de hoja de respuestas del almacenamiento.') : $response;
    }

    public function assign(AssignPagoDocenteCicloRequest $request, PagoDocente $pagoDocente): RedirectResponse
    {
        DB::transaction(function () use ($request, $pagoDocente): void {
            $cycle = Ciclo::whereKey($request->validated('id_ciclo'))->lockForUpdate()->firstOrFail();
            $payment = PagoDocente::whereKey($pagoDocente->id_pago_docente)->lockForUpdate()->firstOrFail();
            if ($payment->id_ciclo !== null) {
                throw ValidationException::withMessages(['id_ciclo' => 'El pago ya tiene un ciclo asignado. Actualiza la pantalla.']);
            }
            $before = $payment->only(['id_ciclo', 'id_profesor', 'monto', 'periodo']);
            $payment->update(['id_ciclo' => $cycle->id_ciclo]);
            Auditoria::create([
                'id_usuario' => $request->user()->id_usuario, 'accion' => 'editar', 'tabla' => 'pagos_docentes',
                'id_registro' => (string) $payment->id_pago_docente,
                'datos_antes' => $before, 'datos_despues' => $payment->only(['id_ciclo', 'id_profesor', 'monto', 'periodo']), 'ip' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Pago docente asignado al ciclo correctamente.');
    }

    private function isPast(Ciclo $ciclo): bool
    {
        return $ciclo->estado === 'Cerrado' && $ciclo->fecha_fin->lt(today());
    }

    private function assertPast(Ciclo $ciclo): void
    {
        $examIds = Simulacro::query()->where('id_ciclo', $ciclo->id_ciclo)->pluck('id_simulacro');
        if (ConvocatoriaBeca::query()->where('estado', '!=', 'Cerrada')->where(fn ($q) => $q->whereIn('id_simulacro_ab', $examIds)->orWhereIn('id_simulacro_cd', $examIds))->exists()) {
            throw ValidationException::withMessages(['ciclo' => 'Cierra y depura las convocatorias de becas de este ciclo antes de eliminarlo.']);
        }
        if (! $this->isPast($ciclo)) {
            throw ValidationException::withMessages(['ciclo' => 'Solo se pueden descargar para eliminación los ciclos cerrados cuya fecha de fin ya pasó.']);
        }
    }

    private function assertClassified(): void
    {
        if (PagoDocente::whereNull('id_ciclo')->exists()) {
            throw ValidationException::withMessages(['pagos_docentes' => 'Asigna un ciclo a los pagos docentes pendientes antes de descargar o eliminar.']);
        }
    }
}
