<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseConvocatoriaBecaRequest;
use App\Http\Requests\SaveConvocatoriaBecaRequest;
use App\Models\Auditoria;
use App\Models\ConvocatoriaBeca;
use App\Models\HojaRespuesta;
use App\Models\PostulanteBeca;
use App\Models\Simulacro;
use App\Support\BecaGrading;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConvocatoriaBecaController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('manage', ConvocatoriaBeca::class);
        Gate::authorize('viewAny', PostulanteBeca::class);
        $filters = $request->validate(['id_convocatoria' => ['nullable', 'integer', 'exists:convocatorias_becas,id_convocatoria'], 'buscar' => ['nullable', 'string', 'max:30'], 'grupo' => ['nullable', 'in:A,B,C,D'], 'orden' => ['nullable', 'in:recientes,nota']]);
        $campaigns = ConvocatoriaBeca::query()->withCount('postulantes')->latest('id_convocatoria')->get();
        $campaigns->each(fn (ConvocatoriaBeca $campaign) => $campaign->setAttribute('aviso_examen_visible', $campaign->examenVisibleEnWeb()));
        $selected = isset($filters['id_convocatoria']) ? $campaigns->firstWhere('id_convocatoria', (int) $filters['id_convocatoria']) : $campaigns->first();
        $query = PostulanteBeca::query()->where('id_convocatoria', $selected?->id_convocatoria ?? 0)
            ->when($filters['buscar'] ?? null, fn ($q, $search) => $q->where('dni', 'like', '%'.$search.'%'))
            ->when($filters['grupo'] ?? null, fn ($q, $group) => $q->where('grupo', $group));
        ($filters['orden'] ?? '') === 'nota' ? $query->orderByDesc('nota')->orderBy('id_postulante') : $query->latest('id_postulante');

        return Inertia::render('Becas/Index', ['convocatorias' => $campaigns, 'seleccionada' => $selected,
            'simulacros' => Simulacro::query()->with('ciclo:id_ciclo,nombre_ciclo')->latest('fecha_aplicacion')->get(['id_simulacro', 'id_ciclo', 'titulo']),
            'postulantes' => InertiaPaginator::from($query->paginate(20)->withQueryString()), 'filters' => $filters, 'hoy' => today('America/Lima')->toDateString(),
        ]);
    }

    public function store(SaveConvocatoriaBecaRequest $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function update(SaveConvocatoriaBecaRequest $request, ConvocatoriaBeca $convocatoria): RedirectResponse
    {
        return $this->save($request, $convocatoria);
    }

    private function save(SaveConvocatoriaBecaRequest $request, ?ConvocatoriaBeca $campaign = null): RedirectResponse
    {
        $data = $request->validated();
        if (($data['modo_clave'] ?? 'separada') === 'unificada') {
            $data['id_simulacro_cd'] = $data['id_simulacro_ab'];
        } elseif ((int) $data['id_simulacro_ab'] === (int) $data['id_simulacro_cd']) {
            throw ValidationException::withMessages(['id_simulacro_ab' => 'Selecciona exámenes distintos para las claves A–B y C–D.']);
        }
        unset($data['modo_clave']);
        $saved = DB::transaction(function () use ($data, $campaign, $request): ConvocatoriaBeca {
            $exams = Simulacro::query()->whereIn('id_simulacro', [$data['id_simulacro_ab'], $data['id_simulacro_cd']])->orderBy('id_simulacro')->lockForUpdate()->get();
            if ($exams->count() !== count(array_unique([$data['id_simulacro_ab'], $data['id_simulacro_cd']])) || $exams->pluck('id_ciclo')->unique()->count() !== 1) {
                throw ValidationException::withMessages(['id_simulacro_cd' => 'Los dos exámenes deben pertenecer al mismo ciclo.']);
            }
            if (ConvocatoriaBeca::query()->when($campaign, fn ($q) => $q->where('id_convocatoria', '!=', $campaign->id_convocatoria))
                ->where(fn ($q) => $q->whereIn('id_simulacro_ab', $exams->modelKeys())->orWhereIn('id_simulacro_cd', $exams->modelKeys()))->exists()) {
                throw ValidationException::withMessages(['id_simulacro_ab' => 'Alguno de estos exámenes ya está vinculado a otra convocatoria.']);
            }
            if ($campaign) {
                $campaign = ConvocatoriaBeca::query()->lockForUpdate()->findOrFail($campaign->id_convocatoria);
                if ($campaign->estado === 'Cerrada' || ($campaign->postulantes()->exists() && ((int) $data['id_simulacro_ab'] !== $campaign->id_simulacro_ab || (int) $data['id_simulacro_cd'] !== $campaign->id_simulacro_cd || BecaGrading::cents($data['puntaje_minimo']) !== BecaGrading::cents($campaign->puntaje_minimo)))) {
                    throw ValidationException::withMessages(['nombre' => 'No se puede cambiar una convocatoria cerrada, ni sus exámenes o puntaje mínimo si ya tiene inscritos.']);
                }
            }
            $before = $campaign?->toArray();
            $campaign = $campaign ? tap($campaign)->update($data) : ConvocatoriaBeca::query()->create($data);
            Auditoria::query()->create(['id_usuario' => $request->user()->id_usuario, 'accion' => $before ? 'editar' : 'crear', 'tabla' => 'convocatorias_becas', 'id_registro' => (string) $campaign->id_convocatoria, 'datos_antes' => $before, 'datos_despues' => $campaign->toArray(), 'ip' => $request->ip()]);

            return $campaign;
        });

        return redirect()->route('becas.index', ['id_convocatoria' => $saved->id_convocatoria])->with('success', 'Convocatoria guardada. Configura las claves A–B y C–D en Lectura OMR.');
    }

    public function close(CloseConvocatoriaBecaRequest $request, ConvocatoriaBeca $convocatoria): RedirectResponse
    {
        $paths = DB::transaction(function () use ($convocatoria, $request): array {
            $campaign = ConvocatoriaBeca::query()->lockForUpdate()->findOrFail($convocatoria->id_convocatoria);
            if ($campaign->estado === 'Cerrada') {
                throw ValidationException::withMessages(['confirmacion' => 'La convocatoria ya está cerrada.']);
            }
            $applicants = $campaign->postulantes()->orderBy('id_postulante')->lockForUpdate()->get();
            if ($applicants->isEmpty() || $applicants->contains(fn ($p) => $p->nota === null || in_array($p->estado, ['Inscrito', 'Revision'], true))) {
                throw ValidationException::withMessages(['confirmacion' => 'Califica y revisa a todos los postulantes antes de cerrar. Los no presentados también requieren una resolución manual.']);
            }
            $discarded = $applicants->filter(fn ($p) => BecaGrading::cents($p->nota) < BecaGrading::cents($campaign->puntaje_minimo));
            $sheets = HojaRespuesta::query()->whereIn('id_postulante', $discarded->modelKeys())->lockForUpdate()->get();
            $paths = $sheets->pluck('archivo_path')->filter()->all();
            DB::table('respuestas_hoja')->whereIn('id_hoja', $sheets->modelKeys())->delete();
            HojaRespuesta::query()->whereIn('id_hoja', $sheets->modelKeys())->delete();
            PostulanteBeca::query()->whereIn('id_postulante', $discarded->modelKeys())->delete();
            $campaign->postulantes()->update(['estado' => 'Becario']);
            $campaign->update(['estado' => 'Cerrada', 'depurada_en' => now()]);
            Auditoria::query()->create(['id_usuario' => $request->user()->id_usuario, 'accion' => 'eliminar', 'tabla' => 'postulantes_becas', 'id_registro' => (string) $campaign->id_convocatoria,
                'datos_antes' => ['total' => $applicants->count()], 'datos_despues' => ['eliminados' => $discarded->count(), 'becarios' => $campaign->postulantes()->count(), 'motivo' => 'Cierre y depuración confirmados'], 'ip' => $request->ip()]);
            // No dejar DNI ni teléfono de los descartados en auditorías de revisiones OMR.
            foreach ($sheets as $sheet) {
                Auditoria::query()->where('tabla', 'respuestas_hoja')->where('id_registro', (string) $sheet->id_hoja)->update(['datos_antes' => ['depurado' => true], 'datos_despues' => ['depurado' => true]]);
            }

            return $paths;
        });
        foreach ($paths as $path) {
            if (str_starts_with($path, 'omr/uploads/') && ! str_contains($path, '..') && ! HojaRespuesta::query()->where('archivo_path', $path)->exists()) {
                Storage::disk('local')->delete($path);
            }
        }

        return back()->with('success', 'Convocatoria cerrada. Se conservaron los becarios y se eliminaron los demás registros.');
    }
}
