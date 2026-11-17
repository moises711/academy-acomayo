<?php

namespace App\Http\Controllers;

use App\Models\Profesor;
use App\Support\InertiaPaginator;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfesorController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ]);
        $query = Profesor::query()
            ->withCount(['horarios', 'cursos'])
            ->when($filters['search'] ?? null, fn ($builder, string $search) => $builder->where(function ($person) use ($search): void {
                $person->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($filters['estado'] ?? null, fn ($builder, string $estado) => $builder->where('estado', $estado));

        $docentes = $query->orderBy('apellido')->orderBy('nombre')->paginate(15)->withQueryString();

        return Inertia::render('Docentes/Index', [
            'docentes' => InertiaPaginator::from($docentes),
            'filters' => $filters,
            'stats' => [
                'total' => Profesor::query()->count(),
                'activos' => Profesor::query()->where('estado', 'activo')->count(),
                'inactivos' => Profesor::query()->where('estado', 'inactivo')->count(),
            ],
            'canManagePayments' => $request->user()->hasPermissionTo('pagos_docentes.gestionar'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Profesor::query()->create($this->validated($request));

        return back()->with('success', 'Docente registrado.');
    }

    public function update(Request $request, Profesor $docente): RedirectResponse
    {
        $docente->update($this->validated($request, $docente));

        return back()->with('success', 'Datos del docente actualizados.');
    }

    public function destroy(Profesor $docente): RedirectResponse
    {
        if ($docente->horarios()->exists() || $docente->cursos()->exists() || $docente->user()->exists()) {
            return back()->with('error', 'No se puede eliminar: el docente está vinculado a cursos, horarios o una cuenta. Puedes cambiar su estado a inactivo.');
        }

        try {
            DB::transaction(fn () => $docente->delete());
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar este docente porque tiene registros vinculados.');
        }

        return back()->with('success', 'Docente eliminado.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Profesor $docente = null): array
    {
        return $request->validate([
            'dni' => ['required', 'string', 'min:8', 'max:15', Rule::unique('profesores', 'dni')->ignore($docente?->id_profesor, 'id_profesor')],
            'nombre' => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ]);
    }
}
