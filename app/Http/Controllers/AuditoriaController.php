<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Support\InertiaPaginator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AuditoriaController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'tabla' => ['nullable', 'string', 'max:60'],
            'accion' => ['nullable', Rule::in(['crear', 'editar', 'eliminar', 'anular', 'exportar'])],
        ]);

        $query = Auditoria::query()->with('user:id_usuario,name,email')
            ->when($filters['tabla'] ?? null, fn ($builder, string $table) => $builder->where('tabla', $table))
            ->when($filters['accion'] ?? null, fn ($builder, string $action) => $builder->where('accion', $action))
            ->latest('created_at');

        $registros = $query->paginate(25)->withQueryString();

        return Inertia::render('Auditoria/Index', [
            'registros' => InertiaPaginator::from($registros),
            'filters' => $filters,
            'tablas' => Auditoria::query()->select('tabla')->distinct()->orderBy('tabla')->pluck('tabla'),
        ]);
    }
}
