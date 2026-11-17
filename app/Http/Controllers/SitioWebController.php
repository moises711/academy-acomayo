<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSitioWebRequest;
use App\Models\Auditoria;
use App\Models\Configuracion;
use App\Models\Profesor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SitioWebController extends Controller
{
    private const CONTENT_KEY = 'sitio.contenido';

    private const TEACHERS_KEY = 'sitio.docentes';

    public function index(): Response
    {
        $content = $this->decode(Configuracion::query()->whereKey(self::CONTENT_KEY)->value('valor'));
        $teachers = $this->decode(Configuracion::query()->whereKey(self::TEACHERS_KEY)->value('valor'));
        $teachersById = collect($teachers)->keyBy('id_profesor');

        return Inertia::render('SitioWeb/Index', [
            'contenido' => $content,
            'docentesSeleccionados' => collect($teachers)->map(fn (array $teacher): array => [
                'id_profesor' => $teacher['id_profesor'],
                'bio' => $teacher['bio'] ?? '',
            ])->values(),
            'docentes' => Profesor::query()->with('cursos:id_curso,nombre_curso')
                ->where('estado', 'activo')->orderBy('apellido')->orderBy('nombre')->get()
                ->map(fn (Profesor $teacher): array => [
                    'id_profesor' => $teacher->id_profesor,
                    'nombre' => trim($teacher->nombre.' '.$teacher->apellido),
                    'cursos' => $teacher->cursos->pluck('nombre_curso')->all(),
                    'fotoUrl' => isset($teachersById[$teacher->id_profesor]['foto_path'])
                        ? route('sitio-web.docentes.foto', $teacher->id_profesor)
                        : null,
                ]),
            'fotoPortadaUrl' => Configuracion::query()->whereKey('sitio.foto_portada')->exists()
                ? route('sitio-web.portada.foto')
                : null,
        ]);
    }

    /** @return array<int, array{id_profesor: int, nombre: string, bio: string, cursos: array<int, string>, fotoUrl: string|null}> */
    public function docentesPublicos(): array
    {
        $profiles = collect($this->decode(Configuracion::query()->whereKey(self::TEACHERS_KEY)->value('valor')));
        if ($profiles->isEmpty()) {
            return [];
        }

        $byId = $profiles->keyBy('id_profesor');

        return Profesor::query()->with('cursos:id_curso,nombre_curso')
            ->where('estado', 'activo')->whereIn('id_profesor', $byId->keys())
            ->orderBy('apellido')->orderBy('nombre')->get()
            ->map(fn (Profesor $teacher): array => [
                'id_profesor' => $teacher->id_profesor,
                'nombre' => trim($teacher->nombre.' '.$teacher->apellido),
                'bio' => $byId[$teacher->id_profesor]['bio'] ?? '',
                'cursos' => $teacher->cursos->pluck('nombre_curso')->all(),
                'fotoUrl' => ! empty($byId[$teacher->id_profesor]['foto_path'])
                    ? route('sitio-web.docentes.foto', $teacher->id_profesor)
                    : null,
            ])->values()->all();
    }

    public function update(UpdateSitioWebRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $previousContent = $this->decode(Configuracion::query()->whereKey(self::CONTENT_KEY)->value('valor'));
        $previousTeachers = $this->decode(Configuracion::query()->whereKey(self::TEACHERS_KEY)->value('valor'));
        $teachersById = collect($previousTeachers)->keyBy('id_profesor');
        $uploadedPaths = [];
        $teachers = collect($data['docentes'])->map(function (array $profile) use ($teachersById, $request, &$uploadedPaths): array {
            $id = (int) $profile['id_profesor'];
            $existing = $teachersById->get($id, []);
            $photo = $request->file("fotos_docentes.{$id}");
            $photoPath = $photo?->store('sitio/docentes', 'local') ?? ($existing['foto_path'] ?? null);
            if ($photoPath && $photo) {
                $uploadedPaths[] = $photoPath;
            }

            return [
                'id_profesor' => $id,
                'bio' => $profile['bio'] ?? '',
                'foto_path' => $photoPath,
            ];
        })->values()->all();

        $oldPaths = collect($previousTeachers)->pluck('foto_path')->filter()->all();
        $oldCover = Configuracion::query()->whereKey('sitio.foto_portada')->value('valor');
        $coverPath = $request->file('foto_portada')?->store('sitio/portada', 'local') ?? $oldCover;
        if ($request->hasFile('foto_portada') && $coverPath) {
            $uploadedPaths[] = $coverPath;
        }

        $newPaths = collect($teachers)->pluck('foto_path')->filter()->push($coverPath)->filter()->all();
        try {
            DB::transaction(function () use ($data, $teachers, $coverPath, $oldCover, $previousContent, $previousTeachers, $request): void {
                Configuracion::query()->updateOrCreate(
                    ['clave' => self::CONTENT_KEY],
                    ['valor' => json_encode($data['contenido'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'descripcion' => 'Contenido editable de la página pública'],
                );
                Configuracion::query()->updateOrCreate(
                    ['clave' => self::TEACHERS_KEY],
                    ['valor' => json_encode($teachers, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'descripcion' => 'Docentes publicados en la página pública'],
                );
                if ($coverPath) {
                    Configuracion::query()->updateOrCreate(
                        ['clave' => 'sitio.foto_portada'],
                        ['valor' => $coverPath, 'descripcion' => 'Fotografía principal de la página pública'],
                    );
                }

                Auditoria::query()->create([
                    'id_usuario' => $request->user()->id_usuario,
                    'accion' => 'editar',
                    'tabla' => 'configuraciones',
                    'id_registro' => self::CONTENT_KEY,
                    'datos_antes' => ['contenido' => $previousContent, 'docentes' => $previousTeachers, 'foto_portada_path' => $oldCover],
                    'datos_despues' => ['contenido' => $data['contenido'], 'docentes' => $teachers, 'foto_portada_path' => $coverPath],
                    'ip' => $request->ip(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($uploadedPaths);
            throw $exception;
        }

        Storage::disk('local')->delete(array_values(array_diff($oldPaths, $newPaths)));
        if ($oldCover && $oldCover !== $coverPath) {
            Storage::disk('local')->delete($oldCover);
        }

        return back()->with('success', 'La información pública del sitio web se guardó correctamente.');
    }

    public function portadaFoto(): BinaryFileResponse
    {
        $path = Configuracion::query()->whereKey('sitio.foto_portada')->value('valor');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'public, max-age=0, must-revalidate', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function docenteFoto(Profesor $docente): BinaryFileResponse
    {
        abort_unless($docente->estado === 'activo', 404);
        $teachers = collect($this->decode(Configuracion::query()->whereKey(self::TEACHERS_KEY)->value('valor')));
        $profile = $teachers->firstWhere('id_profesor', $docente->id_profesor);
        $path = $profile['foto_path'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), ['Cache-Control' => 'public, max-age=0, must-revalidate', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** @return array<string, mixed> */
    private function decode(?string $value): array
    {
        if (! $value) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
