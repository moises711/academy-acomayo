<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHorarioRequest;
use App\Models\Aula;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Profesor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AulasHorarioController extends Controller
{
    public function index(Request $request): Response
    {
        $paginator = Horario::query()
            ->with(['curso', 'grupo.ciclo', 'profesor', 'aula'])
            ->when($request->filled('dia'), fn ($query) => $query->where('dia_semana', $request->integer('dia')))
            ->when($request->filled('id_grupo'), fn ($query) => $query->where('id_grupo', $request->integer('id_grupo')))
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Horarios/Index', [
            'horarios' => [
                'data' => $paginator->items(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'path' => $paginator->path(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'links' => $paginator->linkCollection()->toArray(),
                ],
            ],
            'filters' => $request->only(['dia', 'id_grupo']),
            'cursos' => Curso::query()->orderBy('nombre_curso')->get(['id_curso', 'nombre_curso', 'categoria']),
            'grupos' => Grupo::query()->with('ciclo')->orderBy('nombre')->get(),
            'profesores' => Profesor::query()->where('estado', 'activo')->orderBy('apellido')->get(['id_profesor', 'nombre', 'apellido']),
            'aulas' => Aula::query()->orderBy('nombre')->get(['id_aula', 'nombre', 'tipo']),
            'stats' => [
                'total' => Horario::query()->count(),
                'aulas' => Horario::query()->distinct('id_aula')->count('id_aula'),
                'cursos' => Curso::query()->count(),
            ],
        ]);
    }

    public function store(StoreHorarioRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $hasConflict = Horario::query()
            ->where('dia_semana', $validated['dia_semana'])
            ->where(function ($query) use ($validated): void {
                $query->where('id_profesor', $validated['id_profesor'])
                    ->orWhere('id_aula', $validated['id_aula']);
            })
            ->where('hora_inicio', '<', $validated['hora_fin'])
            ->where('hora_fin', '>', $validated['hora_inicio'])
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'hora_inicio' => 'El docente o el aula ya tienen una clase en ese horario.',
            ]);
        }

        Horario::query()->create($validated);

        return back()->with('success', 'Horario asignado correctamente.');
    }

    public function destroy(Horario $horario): RedirectResponse
    {
        $horario->delete();

        return back()->with('success', 'Horario eliminado.');
    }
}
