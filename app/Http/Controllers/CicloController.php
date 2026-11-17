<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CicloController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Ciclos/Index', [
            'ciclos' => Ciclo::query()
                ->withCount(['matriculas', 'simulacros', 'grupos'])
                ->latest()
                ->get(),
            'stats' => [
                'total' => Ciclo::query()->count(),
                'presenciales' => Ciclo::query()->where('modalidad', 'Presencial')->count(),
                'virtuales' => Ciclo::query()->where('modalidad', 'Virtual')->count(),
                'hibridos' => Ciclo::query()->where('modalidad', 'Híbrido')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Ciclo::query()->create($this->validated($request));

        return back()->with('success', 'Ciclo creado correctamente.');
    }

    public function update(Request $request, Ciclo $ciclo): RedirectResponse
    {
        $ciclo->update($this->validated($request));

        return back()->with('success', 'Ciclo actualizado correctamente.');
    }

    public function destroy(Ciclo $ciclo): RedirectResponse
    {
        $ciclo->delete();

        return back()->with('success', 'Ciclo eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre_ciclo' => ['required', 'string', 'max:255'],
            'tipo_ciclo' => ['required', Rule::in(['Anual', 'Semestral', 'Repaso'])],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'modalidad' => ['required', Rule::in(['Presencial', 'Virtual', 'Híbrido'])],
            'estado' => ['required', Rule::in(['Programado', 'En curso', 'Cerrado'])],
        ]);
    }
}
