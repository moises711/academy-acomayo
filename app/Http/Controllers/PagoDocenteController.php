<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnularPagoDocenteRequest;
use App\Http\Requests\StorePagoDocenteRequest;
use App\Models\Auditoria;
use App\Models\Ciclo;
use App\Models\PagoDocente;
use App\Models\Profesor;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PagoDocenteController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', PagoDocente::class);
        $professorId = $request->route('docente') ?? $request->input('id_profesor');
        $filters = validator(['id_profesor' => $professorId], [
            'id_profesor' => ['nullable', 'integer', 'exists:profesores,id_profesor'],
        ])->validate();
        $profesorSeleccionado = isset($filters['id_profesor'])
            ? Profesor::query()->findOrFail($filters['id_profesor'])
            : null;
        $payments = PagoDocente::query()
            ->with(['ciclo:id_ciclo,nombre_ciclo', 'profesor:id_profesor,nombre,apellido,dni', 'usuario:id_usuario,name', 'anulador:id_usuario,name'])
            ->when($profesorSeleccionado, fn ($query) => $query->where('id_profesor', $profesorSeleccionado->id_profesor))
            ->latest('fecha_pago')->latest('id_pago_docente')->paginate(15)->withQueryString();

        return Inertia::render('PagosDocentes/Index', [
            'ciclos' => Ciclo::query()->orderByDesc('fecha_inicio')->get(['id_ciclo', 'nombre_ciclo']),
            'docentes' => Profesor::query()->orderBy('apellido')->orderBy('nombre')->get(['id_profesor', 'nombre', 'apellido', 'dni']),
            'profesorSeleccionado' => $profesorSeleccionado ? [
                'id_profesor' => $profesorSeleccionado->id_profesor,
                'nombre' => trim($profesorSeleccionado->nombre.' '.$profesorSeleccionado->apellido),
            ] : null,
            'pagos' => InertiaPaginator::from($payments),
            'filters' => $filters,
            'fechaHoy' => today()->toDateString(),
        ]);
    }

    public function store(StorePagoDocenteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payment = DB::transaction(function () use ($request, $validated): PagoDocente {
            Ciclo::whereKey($validated['id_ciclo'])->lockForUpdate()->firstOrFail();
            $payment = PagoDocente::query()->create([
                ...$validated,
                'id_usuario' => $request->user()->id_usuario,
                'estado' => 'valido',
            ]);
            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => 'crear',
                'tabla' => 'pagos_docentes',
                'id_registro' => (string) $payment->id_pago_docente,
                'datos_despues' => $payment->only(['id_ciclo', 'id_profesor', 'monto', 'fecha_pago', 'periodo', 'metodo_pago', 'nro_comprobante', 'estado']),
                'ip' => $request->ip(),
            ]);

            return $payment;
        });

        return redirect()->route('pagos-docentes.index', ['id_profesor' => $payment->id_profesor])
            ->with('success', 'Pago al docente registrado correctamente.');
    }

    public function anular(AnularPagoDocenteRequest $request, PagoDocente $pagoDocente): RedirectResponse
    {
        $before = $pagoDocente->only(['estado', 'motivo_anulacion', 'anulado_por', 'anulado_en']);

        DB::transaction(function () use ($request, $pagoDocente, $before): void {
            $payment = PagoDocente::query()->whereKey($pagoDocente->id_pago_docente)->lockForUpdate()->firstOrFail();
            if ($payment->estado !== 'valido') {
                throw ValidationException::withMessages(['motivo_anulacion' => 'Este pago ya está anulado.']);
            }

            $payment->forceFill([
                'estado' => 'anulado',
                'motivo_anulacion' => $request->validated('motivo_anulacion'),
                'anulado_por' => $request->user()->id_usuario,
                'anulado_en' => now(),
            ])->save();
            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => 'anular',
                'tabla' => 'pagos_docentes',
                'id_registro' => (string) $payment->id_pago_docente,
                'datos_antes' => $before,
                'datos_despues' => $payment->only(['estado', 'motivo_anulacion', 'anulado_por', 'anulado_en']),
                'ip' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Pago anulado y registrado en auditoría.');
    }
}
