<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultaRequest;
use App\Http\Requests\UpdateConsultaRequest;
use App\Models\Consulta;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ConsultaController extends Controller
{
    public function index(): Response
    {
        $consultas = Consulta::query()->latest('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Consultas/Index', [
            'consultas' => InertiaPaginator::from($consultas),
            'stats' => [
                'nuevas' => Consulta::query()->where('estado', 'Nueva')->count(),
                'contactadas' => Consulta::query()->where('estado', 'Contactada')->count(),
                'descartadas' => Consulta::query()->where('estado', 'Descartada')->count(),
            ],
        ]);
    }

    public function store(StoreConsultaRequest $request): RedirectResponse
    {
        Consulta::query()->create($request->validated());

        return back()->with('success', 'Gracias por escribirnos. La academia se comunicará contigo pronto.');
    }

    public function update(UpdateConsultaRequest $request, Consulta $consulta): RedirectResponse
    {
        $consulta->update($request->validated());

        return back()->with('success', 'Estado de la consulta actualizado.');
    }
}
