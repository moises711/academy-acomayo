<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCatalogoRequest;
use App\Models\AreaAcademica;
use App\Models\Aula;
use App\Models\Beca;
use App\Models\Carrera;
use App\Models\Ciclo;
use App\Models\Grupo;
use App\Models\Universidad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CatalogoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Catalogos/Index', [
            'universidades' => Universidad::query()->withCount('carreras')->orderBy('nombre')->get(),
            'areas' => AreaAcademica::query()->withCount(['carreras', 'grupos'])->orderBy('nombre')->get(),
            'carreras' => Carrera::query()->with(['universidad', 'areaacademica'])->withCount('matriculas')->orderBy('nombre')->get(),
            'becas' => Beca::query()->withCount('matriculas')->orderBy('nombre')->get(),
            'aulas' => Aula::query()->withCount('horarios')->orderBy('nombre')->get(),
            'grupos' => Grupo::query()->with(['ciclo', 'areaacademica'])->withCount(['matriculas', 'horarios'])->orderBy('nombre')->get(),
            'ciclos' => Ciclo::query()->orderByDesc('fecha_inicio')->get(['id_ciclo', 'nombre_ciclo']),
        ]);
    }

    public function store(SaveCatalogoRequest $request, string $tipo): RedirectResponse
    {
        $model = $this->modelFor($tipo);
        $model::query()->create($request->validated());

        return back()->with('success', 'Catálogo registrado.');
    }

    public function update(SaveCatalogoRequest $request, string $tipo, int $id): RedirectResponse
    {
        $this->modelFor($tipo)::query()->findOrFail($id)->update($request->validated());

        return back()->with('success', 'Catálogo actualizado.');
    }

    public function destroy(string $tipo, int $id): RedirectResponse
    {
        $model = $this->modelFor($tipo)::query()->findOrFail($id);
        if ($this->hasDependencies($tipo, $model)) {
            return back()->with('error', 'No se puede eliminar porque tiene registros vinculados. Actualiza el dato o retíralo de uso.');
        }

        try {
            DB::transaction(fn () => $model->delete());
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar porque otros datos dependen de este catálogo.');
        }

        return back()->with('success', 'Catálogo eliminado.');
    }

    /** @return class-string<Model> */
    private function modelFor(string $type): string
    {
        return match ($type) {
            'universidades' => Universidad::class,
            'areas' => AreaAcademica::class,
            'carreras' => Carrera::class,
            'becas' => Beca::class,
            'aulas' => Aula::class,
            'grupos' => Grupo::class,
            default => abort(404),
        };
    }

    private function hasDependencies(string $type, Model $model): bool
    {
        return match ($type) {
            'universidades' => Carrera::query()->where('id_universidad', $model->getKey())->exists(),
            'areas' => Carrera::query()->where('id_area', $model->getKey())->exists()
                || Grupo::query()->where('id_area', $model->getKey())->exists(),
            'carreras' => DB::table('matriculas')->where('id_carrera_objetivo', $model->getKey())->exists(),
            'becas' => DB::table('matriculas')->where('id_beca', $model->getKey())->exists(),
            'aulas' => DB::table('horarios')->where('id_aula', $model->getKey())->exists(),
            'grupos' => DB::table('matriculas')->where('id_grupo', $model->getKey())->exists()
                || DB::table('horarios')->where('id_grupo', $model->getKey())->exists()
                || DB::table('evaluaciones')->where('id_grupo', $model->getKey())->exists(),
            default => true,
        };
    }
}
