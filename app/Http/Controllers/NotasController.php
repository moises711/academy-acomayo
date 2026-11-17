<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvaluacionRequest;
use App\Http\Requests\StoreNotasRequest;
use App\Models\Auditoria;
use App\Models\Evaluacion;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Nota;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class NotasController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $validated = $request->validate(['id_evaluacion' => ['nullable', 'integer', 'exists:evaluaciones,id_evaluacion']]);
        $evaluacionesQuery = Evaluacion::query()->with(['grupo.ciclo', 'curso'])->withCount('notas');
        if ($user->role === 'profesor') {
            $evaluacionesQuery->whereExists(fn ($query) => $query->selectRaw('1')->from('horarios')
                ->whereColumn('horarios.id_grupo', 'evaluaciones.id_grupo')
                ->whereColumn('horarios.id_curso', 'evaluaciones.id_curso')
                ->where('horarios.id_profesor', $user->id_profesor));
        }
        $evaluaciones = $evaluacionesQuery->latest('fecha')->limit(100)->get();
        $evaluacion = isset($validated['id_evaluacion'])
            ? $evaluaciones->firstWhere('id_evaluacion', (int) $validated['id_evaluacion'])
            : $evaluaciones->first();
        abort_if(isset($validated['id_evaluacion']) && ! $evaluacion, 404);

        $roster = $evaluacion ? Matricula::query()
            ->with('estudiante:id_estudiante,nombre,apellido,dni')
            ->where('id_grupo', $evaluacion->id_grupo)
            ->where('estado_matricula', 'inscrito')
            ->whereHas('ciclo', fn ($query) => $query
                ->whereDate('fecha_inicio', '<=', $evaluacion->fecha)
                ->whereDate('fecha_fin', '>=', $evaluacion->fecha))
            ->orderBy('id_matricula')->get() : collect();
        $notes = $evaluacion ? Nota::query()->where('id_evaluacion', $evaluacion->id_evaluacion)->get()->keyBy('id_matricula') : collect();

        return Inertia::render('Notas/Index', [
            'evaluaciones' => $evaluaciones,
            'evaluacionActual' => $evaluacion,
            'estudiantes' => $roster->map(fn (Matricula $matricula): array => [
                'id_matricula' => $matricula->id_matricula,
                'estudiante' => $matricula->estudiante,
                'nota' => $notes->get($matricula->id_matricula)?->nota,
                'observacion' => $notes->get($matricula->id_matricula)?->observacion,
            ])->values(),
            'grupos' => $this->availableGroupCourses($user),
        ]);
    }

    public function storeEvaluation(StoreEvaluacionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureCourseGroupIsAvailable($request->user(), (int) $data['id_grupo'], (int) $data['id_curso']);
        $evaluation = Evaluacion::query()->create($data);

        return redirect()->route('notas.index', ['id_evaluacion' => $evaluation->id_evaluacion])
            ->with('success', 'Evaluación creada. Ya puedes registrar las notas del grupo.');
    }

    public function storeGrades(StoreNotasRequest $request, Evaluacion $evaluacion): RedirectResponse
    {
        $user = $request->user();
        $this->ensureCourseGroupIsAvailable($user, $evaluacion->id_grupo, $evaluacion->id_curso);
        $rows = $request->validated('notas');
        $matriculaIds = collect($rows)->pluck('id_matricula')->map(fn ($id): int => (int) $id);
        $validMatriculaIds = Matricula::query()
            ->whereIn('id_matricula', $matriculaIds)
            ->where('id_grupo', $evaluacion->id_grupo)
            ->where('estado_matricula', 'inscrito')
            ->pluck('id_matricula')->map(fn ($id): int => (int) $id);

        if ($validMatriculaIds->count() !== $matriculaIds->count()) {
            throw ValidationException::withMessages(['notas' => 'Solo puedes registrar notas para estudiantes inscritos en el grupo de esta evaluación.']);
        }

        foreach ($rows as $row) {
            if (($row['nota'] ?? null) !== null && ($row['nota'] ?? '') !== '' && (float) $row['nota'] > (float) $evaluacion->nota_maxima) {
                throw ValidationException::withMessages([
                    'notas' => "Una nota supera el máximo de {$evaluacion->nota_maxima}.",
                ]);
            }
        }

        DB::transaction(function () use ($rows, $evaluacion, $user, $request): void {
            foreach ($rows as $row) {
                if (($row['nota'] ?? null) === null || ($row['nota'] ?? '') === '') {
                    $existingNote = Nota::query()->where('id_evaluacion', $evaluacion->id_evaluacion)
                        ->where('id_matricula', $row['id_matricula'])->first();
                    if (! $existingNote) {
                        continue;
                    }

                    $before = $existingNote->only(['id_evaluacion', 'id_matricula', 'nota', 'observacion', 'id_usuario']);
                    $noteId = $existingNote->id_nota;
                    $existingNote->delete();
                    Auditoria::query()->create([
                        'id_usuario' => $user->id_usuario,
                        'accion' => 'eliminar',
                        'tabla' => 'notas',
                        'id_registro' => (string) $noteId,
                        'datos_antes' => $before,
                        'ip' => $request->ip(),
                    ]);

                    continue;
                }
                $nota = Nota::query()->firstOrNew([
                    'id_evaluacion' => $evaluacion->id_evaluacion,
                    'id_matricula' => $row['id_matricula'],
                ]);
                $before = $nota->exists ? $nota->only(['nota', 'observacion', 'id_usuario']) : null;
                $nota->fill([
                    'nota' => $row['nota'],
                    'observacion' => $row['observacion'] ?? null,
                    'id_usuario' => $user->id_usuario,
                ]);
                $nota->save();

                Auditoria::query()->create([
                    'id_usuario' => $user->id_usuario,
                    'accion' => $before ? 'editar' : 'crear',
                    'tabla' => 'notas',
                    'id_registro' => (string) $nota->id_nota,
                    'datos_antes' => $before,
                    'datos_despues' => $nota->only(['id_evaluacion', 'id_matricula', 'nota', 'observacion', 'id_usuario']),
                    'ip' => $request->ip(),
                ]);
            }
        });

        return back()->with('success', 'Notas guardadas y cambios registrados en auditoría.');
    }

    /** @return Collection<int, array<string, mixed>> */
    private function availableGroupCourses(User $user): Collection
    {
        $query = Horario::query()->with(['grupo.ciclo:id_ciclo,nombre_ciclo', 'curso:id_curso,nombre_curso']);
        if ($user->role === 'profesor') {
            $query->where('id_profesor', $user->id_profesor);
        }

        return $query->get()->unique(fn (Horario $horario): string => $horario->id_grupo.'-'.$horario->id_curso)
            ->map(fn (Horario $horario): array => [
                'id_grupo' => $horario->id_grupo,
                'grupo' => $horario->grupo?->nombre,
                'ciclo' => $horario->grupo?->ciclo?->nombre_ciclo,
                'id_curso' => $horario->id_curso,
                'curso' => $horario->curso?->nombre_curso,
            ])->values();
    }

    private function ensureCourseGroupIsAvailable(User $user, int $groupId, int $courseId): void
    {
        $scheduleQuery = Horario::query()->where('id_grupo', $groupId)->where('id_curso', $courseId);
        if ($user->role === 'profesor') {
            $scheduleQuery->where('id_profesor', $user->id_profesor);
        }

        if (! $scheduleQuery->exists()) {
            throw ValidationException::withMessages(['id_curso' => 'No tienes asignado este curso y grupo.']);
        }
    }
}
