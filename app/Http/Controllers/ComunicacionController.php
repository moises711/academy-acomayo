<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComunicacionRequest;
use App\Http\Requests\UpdateComunicacionRequest;
use App\Models\Anuncio;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Material;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComunicacionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'tipo_registro' => ['nullable', Rule::in(['anuncio', 'material'])],
        ]);
        $search = $filters['search'] ?? null;
        $announcements = Anuncio::query()->with(['ciclo:id_ciclo,nombre_ciclo', 'grupo:id_grupo,nombre', 'user:id_usuario,name'])
            ->when($search, fn ($query, string $term) => $query->where(function ($nested) use ($term): void {
                $nested->where('titulo', 'like', "%{$term}%")->orWhere('contenido', 'like', "%{$term}%");
            }))->latest('publicado_en')->paginate(8, ['*'], 'anuncios_pagina')->withQueryString();
        $materials = Material::query()->with(['curso:id_curso,nombre_curso', 'grupo:id_grupo,nombre', 'user:id_usuario,name'])
            ->when($search, fn ($query, string $term) => $query->where('titulo', 'like', "%{$term}%"))
            ->latest('created_at')->paginate(8, ['*'], 'materiales_pagina')->withQueryString();

        return Inertia::render('Comunicacion/Index', [
            'anuncios' => ($filters['tipo_registro'] ?? null) === 'material' ? null : InertiaPaginator::from($announcements),
            'materiales' => ($filters['tipo_registro'] ?? null) === 'anuncio' ? null : InertiaPaginator::from($materials),
            'filters' => $filters,
            'ciclos' => Ciclo::query()->orderByDesc('fecha_inicio')->get(['id_ciclo', 'nombre_ciclo']),
            'grupos' => Grupo::query()->with('ciclo:id_ciclo,nombre_ciclo')->orderBy('nombre')->get(['id_grupo', 'id_ciclo', 'nombre']),
            'cursos' => Curso::query()->orderBy('nombre_curso')->get(['id_curso', 'nombre_curso']),
        ]);
    }

    public function store(StoreComunicacionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $request): void {
            if ($data['tipo_registro'] === 'anuncio') {
                Anuncio::query()->create([
                    'id_usuario' => $request->user()->id_usuario,
                    'id_ciclo' => $data['id_ciclo'] ?? null,
                    'id_grupo' => $data['id_grupo'] ?? null,
                    'titulo' => $data['titulo'],
                    'contenido' => $data['contenido'],
                    'vence_en' => $data['vence_en'] ?? null,
                ]);
            } else {
                Material::query()->create([
                    'id_usuario' => $request->user()->id_usuario,
                    'id_curso' => $data['id_curso'],
                    'id_grupo' => $data['id_grupo'] ?? null,
                    'titulo' => $data['titulo'],
                    'tipo' => $data['tipo'],
                    'url' => $data['url'],
                ]);
            }
        });

        return back()->with('success', 'Publicación creada correctamente.');
    }

    public function update(UpdateComunicacionRequest $request, string $tipo, int $id): RedirectResponse
    {
        $data = $request->validated();
        abort_unless($tipo === $data['tipo_registro'], 404);
        DB::transaction(function () use ($data, $tipo, $id): void {
            if ($tipo === 'anuncio') {
                $announcement = Anuncio::query()->findOrFail($id);
                $announcement->update([
                    'id_ciclo' => $data['id_ciclo'] ?? null,
                    'id_grupo' => $data['id_grupo'] ?? null,
                    'titulo' => $data['titulo'],
                    'contenido' => $data['contenido'],
                    'vence_en' => $data['vence_en'] ?? null,
                ]);

                return;
            }

            Material::query()->findOrFail($id)->update([
                'id_curso' => $data['id_curso'],
                'id_grupo' => $data['id_grupo'] ?? null,
                'titulo' => $data['titulo'],
                'tipo' => $data['tipo'],
                'url' => $data['url'],
            ]);
        });

        return back()->with('success', 'Publicación actualizada.');
    }

    public function destroy(string $tipo, int $id): RedirectResponse
    {
        abort_unless(in_array($tipo, ['anuncio', 'material'], true), 404);
        $model = $tipo === 'anuncio' ? Anuncio::query()->findOrFail($id) : Material::query()->findOrFail($id);
        $model->delete();

        return back()->with('success', 'Publicación eliminada.');
    }
}
