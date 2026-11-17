<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\ResultadosSimulacro;
use App\Models\Simulacro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SimulacroController extends Controller
{
    public function index(Request $request): Response
    {
        $paginator = Simulacro::query()
            ->with('ciclo')
            ->withCount('resultados')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where('titulo', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Simulacros/Index', [
            'simulacros' => [
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
            'filters' => $request->only(['search']),
            'ciclos' => Ciclo::query()->orderByDesc('fecha_inicio')->get(['id_ciclo', 'nombre_ciclo']),
            'stats' => [
                'total' => Simulacro::query()->count(),
                'totalResultados' => ResultadosSimulacro::query()->count(), // <-- Usando ResultadosSimulacro
                'promedioPuntaje' => round(ResultadosSimulacro::query()->avg('puntaje_obtenido') ?? 0, 2), // <-- Usando ResultadosSimulacro
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'fecha_aplicacion' => ['required', 'date'],
            'id_ciclo' => ['required', 'exists:ciclos,id_ciclo'],
            'puntaje_maximo' => ['required', 'numeric', 'min:0'],
        ]);

        Simulacro::query()->create($validated);

        return back()->with('success', 'Simulacro programado correctamente.');
    }

    public function create(): Response
    {
        return Inertia::render('Simulacros/Create', [
            'ciclos' => Ciclo::query()->orderByDesc('fecha_inicio')->get(['id_ciclo', 'nombre_ciclo']),
        ]);
    }

    public function show(Simulacro $simulacro): Response
    {
        $simulacro->load('ciclo')->loadCount('resultados');

        return Inertia::render('Simulacros/Show', [
            'simulacro' => $simulacro,
        ]);
    }

    public function edit(Simulacro $simulacro): Response
    {
        return Inertia::render('Simulacros/Edit', [
            'simulacro' => $simulacro,
            'ciclos' => Ciclo::query()->orderByDesc('fecha_inicio')->get(['id_ciclo', 'nombre_ciclo']),
        ]);
    }

    public function update(Request $request, Simulacro $simulacro): RedirectResponse
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'fecha_aplicacion' => ['required', 'date'],
            'id_ciclo' => ['required', 'exists:ciclos,id_ciclo'],
            'puntaje_maximo' => ['required', 'numeric', 'min:0'],
        ]);

        $simulacro->update($validated);

        return redirect()->route('simulacros.index')->with('success', 'Simulacro actualizado correctamente.');
    }

    public function destroy(Simulacro $simulacro): RedirectResponse
    {
        $simulacro->delete();

        return back()->with('success', 'Simulacro eliminado.');
    }
}
