<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CursoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Cursos/Index', [
            'cursos' => Curso::query()->withCount('horarios')->latest()->get(),
            'stats' => [
                'total' => Curso::query()->count(),
                'aptitud' => Curso::query()->where('categoria', 'Aptitud')->count(),
                'ciencias' => Curso::query()->where('categoria', 'Ciencias')->count(),
                'letras' => Curso::query()->where('categoria', 'Letras')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Curso::query()->create($this->validated($request));

        return back()->with('success', 'Curso creado con éxito.');
    }

    public function update(Request $request, Curso $curso): RedirectResponse
    {
        $curso->update($this->validated($request));

        return back()->with('success', 'Curso actualizado.');
    }

    public function destroy(Curso $curso): RedirectResponse
    {
        $curso->delete();

        return back()->with('success', 'Curso eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre_curso' => ['required', 'string', 'max:255'],
            'categoria' => ['required', Rule::in(['Aptitud', 'Ciencias', 'Letras'])],
        ]);
    }
}
