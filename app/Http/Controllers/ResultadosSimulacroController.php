<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\ResultadosSimulacro;
use App\Models\Simulacro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ResultadosSimulacroController extends Controller
{
    public function index(Simulacro $simulacro): Response
    {
        $simulacro->load('ciclo');

        $resultados = ResultadosSimulacro::query()
            ->where('id_simulacro', $simulacro->id_simulacro)
            ->with(['matricula.estudiante', 'matricula.grupo', 'matricula.carrera'])
            ->orderByDesc('puntaje_obtenido')
            ->get();

        return Inertia::render('Simulacros/Resultados', [
            'simulacro' => $simulacro,
            'resultados' => $resultados,
            'matriculas' => Matricula::query()
                ->with('estudiante:id_estudiante,nombre,apellido,dni')
                ->where('id_ciclo', $simulacro->id_ciclo)
                ->whereIn('estado_matricula', ['inscrito', 'finalizado'])
                ->orderBy('id_matricula')
                ->get(['id_matricula', 'id_estudiante', 'id_ciclo', 'id_grupo']),
            'stats' => [
                'promedio' => round((float) $resultados->avg('puntaje_obtenido'), 2),
                'maximo' => (float) $resultados->max('puntaje_obtenido'),
                'rezagados' => $resultados->where('es_rezagado', true)->count(),
                'total' => $resultados->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $simulacroData = $request->validate([
            'id_simulacro' => ['required', 'integer', 'exists:simulacros,id_simulacro'],
        ]);
        $simulacro = Simulacro::query()->findOrFail($simulacroData['id_simulacro']);

        $validated = $request->validate([
            'id_matricula' => [
                'required',
                'integer',
                Rule::exists('matriculas', 'id_matricula')->where(fn ($query) => $query
                    ->where('id_ciclo', $simulacro->id_ciclo)
                    ->whereIn('estado_matricula', ['inscrito', 'finalizado'])),
            ],
            'puntaje_obtenido' => ['required', 'numeric', 'min:0', 'max:'.$simulacro->puntaje_maximo],
            'es_rezagado' => ['boolean'],
        ]);

        $validated['es_rezagado'] = $request->boolean('es_rezagado');
        $validated['estado_ranking'] = 'pendiente_actualizar';
        $validated['id_simulacro'] = $simulacro->id_simulacro;
        $validated['fecha_rendicion'] = $simulacro->fecha_aplicacion;

        ResultadosSimulacro::query()->updateOrCreate([
            'id_simulacro' => $validated['id_simulacro'],
            'id_matricula' => $validated['id_matricula'],
        ], $validated);
        $simulacro->recalcularPuestos();

        return back()->with('success', 'Resultado guardado correctamente.');
    }
}
