<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCarnetTemplateRequest;
use App\Support\PlantillaCarnetStore;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CarnetTemplateController extends Controller
{
    public function index(PlantillaCarnetStore $templates): Response
    {
        return Inertia::render('Carnets/Diseno', [
            'plantillas' => $templates->all(),
            'predeterminada' => $templates->defaultId(),
        ]);
    }

    public function store(SaveCarnetTemplateRequest $request, PlantillaCarnetStore $templates): RedirectResponse
    {
        $id = $templates->save($request->validated());
        if ($templates->defaultId() === null) {
            $templates->setDefault($id);
        }

        return back()->with('success', 'Plantilla guardada correctamente.');
    }

    public function update(SaveCarnetTemplateRequest $request, string $id, PlantillaCarnetStore $templates): RedirectResponse
    {
        abort_unless($templates->all()->contains(fn (array $template): bool => $template['id'] === $id), 404);
        $templates->save($request->validated(), $id);

        return back()->with('success', 'Plantilla actualizada correctamente.');
    }

    public function destroy(string $id, PlantillaCarnetStore $templates): RedirectResponse
    {
        abort_unless($templates->all()->contains(fn (array $template): bool => $template['id'] === $id), 404);
        $templates->delete($id);

        return back()->with('success', 'Plantilla eliminada.');
    }

    public function setDefault(string $id, PlantillaCarnetStore $templates): RedirectResponse
    {
        $templates->setDefault($id);

        return back()->with('success', 'Plantilla predeterminada actualizada.');
    }
}
