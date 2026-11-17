<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstudianteRequest;
use App\Http\Requests\UpdateEstudianteRequest;
use App\Models\Asistencia;
use App\Models\Auditoria;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Nota;
use App\Support\PlantillaCarnetStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EstudianteController extends Controller
{
    public function index(Request $request): Response
    {
        $paginator = Estudiante::query()
            ->withCount('matriculas')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('dni', 'like', "%{$search}%")
                        ->orWhere('nombre', 'like', "%{$search}%")
                        ->orWhere('apellido', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->string('estado')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Estudiantes/Index', [
            'estudiantes' => [
                'data' => collect($paginator->items())->map(fn (Estudiante $estudiante): array => [
                    ...$estudiante->toArray(),
                    'fotoUrl' => $estudiante->foto_path ? route('estudiantes.foto', $estudiante) : null,
                    ...($request->user()?->hasPermissionTo('carnets.generar') ? ['codigo_qr' => $estudiante->codigo_qr] : []),
                ])->all(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'from' => $paginator->firstItem(),
                    'last_page' => $paginator->lastPage(),
                    'path' => $paginator->path(),
                    'per_page' => $paginator->perPage(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                    'links' => $paginator->linkCollection()->toArray(),
                ],
            ],
            'filters' => $request->only(['search', 'estado']),
            'stats' => [
                'total' => Estudiante::query()->count(),
                'activos' => Estudiante::query()->where('estado', 'activo')->count(),
                'inactivos' => Estudiante::query()->where('estado', 'inactivo')->count(),
                'egresados' => Estudiante::query()->where('estado', 'egresado')->count(),
            ],
            'canGenerateCarnets' => $request->user()?->hasPermissionTo('carnets.generar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Estudiantes/Create');
    }

    public function store(StoreEstudianteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['foto']);
        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('estudiantes/fotos', 'local');
        }

        $estudiante = DB::transaction(function () use ($request, $data): Estudiante {
            $student = Estudiante::query()->create($data);
            $this->auditStudentChange($request, $student, 'crear', null, $this->studentSnapshot($student));

            return $student;
        });

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante registrado exitosamente.');
    }

    public function show(Estudiante $estudiante): Response
    {
        $estudiante->load([
            'apoderados', 'matriculas.ciclo', 'matriculas.grupo', 'matriculas.carrera',
            'matriculas.cuotas', 'matriculas.pagos',
        ]);
        $matriculaIds = $estudiante->matriculas->pluck('id_matricula');

        return Inertia::render('Estudiantes/Show', [
            'estudiante' => $estudiante,
            'canGenerateCarnets' => request()->user()?->hasPermissionTo('carnets.generar') ?? false,
            'asistencias' => Asistencia::query()->with(['horario.curso', 'matricula.grupo'])
                ->whereIn('id_matricula', $matriculaIds)->latest('fecha')->limit(100)->get(),
            'notas' => Nota::query()->with(['evaluacion.curso', 'matricula.grupo'])
                ->whereIn('id_matricula', $matriculaIds)->latest()->limit(100)->get(),
            'resultados' => $estudiante->resultadosSimulacros()->with(['simulacro', 'matricula.carrera'])
                ->latest('fecha_rendicion')->limit(50)->get(),
        ]);
    }

    public function carnet(Estudiante $estudiante, PlantillaCarnetStore $templates): Response
    {
        $estudiante->load(['matriculas' => fn ($query) => $query
            ->with(['grupo', 'ciclo'])
            ->where('estado_matricula', 'inscrito')
            ->latest('fecha_matricula')]);
        $matricula = $estudiante->matriculas->first();

        return Inertia::render('Estudiantes/Carnet', [
            'carnets' => [[
                'id_estudiante' => $estudiante->id_estudiante,
                'nombre' => trim($estudiante->nombre.' '.$estudiante->apellido),
                'codigo' => 'EST-'.str_pad((string) $estudiante->id_estudiante, 5, '0', STR_PAD_LEFT),
                'vigencia' => $matricula?->ciclo?->fecha_fin?->toDateString(),
                'fotoUrl' => $estudiante->foto_path ? route('estudiantes.foto', $estudiante) : null,
                'grupo' => $matricula?->grupo?->nombre,
                'ciclo' => $matricula?->ciclo?->nombre_ciclo,
                'qrValue' => $estudiante->codigo_qr,
            ]],
            'regenerarUrl' => route('estudiantes.carnet.regenerar', $estudiante),
            'canRegenerate' => request()->user()?->hasPermissionTo('estudiantes.gestionar') ?? false,
            'plantilla' => $templates->defaultConfig(),
        ]);
    }

    public function carnets(Request $request, PlantillaCarnetStore $templates): Response
    {
        $validated = $request->validate(['id_grupo' => ['nullable', 'integer', 'exists:grupos,id_grupo']]);
        if (! isset($validated['id_grupo'])) {
            return Inertia::render('Estudiantes/CarnetsSelect', [
                'grupos' => Grupo::query()->with('ciclo:id_ciclo,nombre_ciclo,fecha_fin')->withCount('matriculas')->orderBy('nombre')->get(),
            ]);
        }

        $group = Grupo::query()->with('ciclo:id_ciclo,nombre_ciclo,fecha_fin')->findOrFail($validated['id_grupo']);
        $students = Estudiante::query()
            ->where('estado', 'activo')
            ->whereHas('matriculas', fn ($query) => $query->where('id_grupo', $group->id_grupo)->where('estado_matricula', 'inscrito'))
            ->orderBy('apellido')->orderBy('nombre')->get();

        return Inertia::render('Estudiantes/Carnet', [
            'carnets' => $students->map(fn (Estudiante $student): array => [
                'id_estudiante' => $student->id_estudiante,
                'nombre' => trim($student->nombre.' '.$student->apellido),
                'codigo' => 'EST-'.str_pad((string) $student->id_estudiante, 5, '0', STR_PAD_LEFT),
                'vigencia' => $group->ciclo?->fecha_fin?->toDateString(),
                'fotoUrl' => $student->foto_path ? route('estudiantes.foto', $student) : null,
                'grupo' => $group->nombre,
                'ciclo' => $group->ciclo?->nombre_ciclo,
                'qrValue' => $student->codigo_qr,
            ])->values(),
            'regenerarUrl' => null,
            'canRegenerate' => false,
            'plantilla' => $templates->defaultConfig(),
        ]);
    }

    public function codigosQrGrupo(Request $request): Response
    {
        $validated = $request->validate([
            'id_grupo' => ['required', 'integer', 'exists:grupos,id_grupo'],
        ]);

        $group = Grupo::query()->findOrFail($validated['id_grupo']);
        $students = Estudiante::query()
            ->where('estado', 'activo')
            ->whereHas('matriculas', fn ($query) => $query
                ->where('id_grupo', $group->id_grupo)
                ->where('estado_matricula', 'inscrito'))
            ->orderBy('apellido')->orderBy('nombre')->get();

        return Inertia::render('Estudiantes/QRsGrupo', [
            'grupo' => ['nombre' => $group->nombre, 'ciclo' => $group->ciclo?->nombre_ciclo],
            'estudiantes' => $students->map(fn (Estudiante $student): array => [
                'id' => $student->id_estudiante,
                'nombre' => trim($student->nombre.' '.$student->apellido),
                'codigo' => 'EST-'.str_pad((string) $student->id_estudiante, 5, '0', STR_PAD_LEFT),
                'qrValue' => $student->codigo_qr,
            ])->values(),
        ]);
    }

    public function foto(Estudiante $estudiante): mixed
    {
        abort_unless($estudiante->foto_path && Storage::disk('local')->exists($estudiante->foto_path), 404);

        return response()->file(Storage::disk('local')->path($estudiante->foto_path), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function regenerarQr(Request $request, Estudiante $estudiante): RedirectResponse
    {
        DB::transaction(function () use ($request, $estudiante): void {
            $estudiante->forceFill(['codigo_qr' => (string) Str::uuid(), 'qr_regenerado_at' => now()])->save();
            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => 'editar',
                'tabla' => 'estudiantes',
                'id_registro' => (string) $estudiante->id_estudiante,
                'datos_despues' => ['evento' => 'qr_regenerado', 'qr_regenerado_at' => now()->toIso8601String()],
                'ip' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Se generó un nuevo código QR para el carnet.');
    }

    public function edit(Estudiante $estudiante): Response
    {
        return Inertia::render('Estudiantes/Edit', [
            'estudiante' => $estudiante,
            'fotoUrl' => $estudiante->foto_path ? route('estudiantes.foto', $estudiante) : null,
        ]);
    }

    public function update(UpdateEstudianteRequest $request, Estudiante $estudiante): RedirectResponse
    {
        $data = $request->validated();
        unset($data['foto']);
        $before = $this->studentSnapshot($estudiante);
        $oldPhoto = $estudiante->foto_path;
        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('estudiantes/fotos', 'local');
        }
        DB::transaction(function () use ($request, $estudiante, $data, $before): void {
            $estudiante->update($data);
            $this->auditStudentChange($request, $estudiante, 'editar', $before, $this->studentSnapshot($estudiante));
        });
        if ($request->hasFile('foto') && $oldPhoto) {
            Storage::disk('local')->delete($oldPhoto);
        }

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante actualizado exitosamente.');
    }

    public function destroy(Request $request, Estudiante $estudiante): RedirectResponse
    {
        if ($estudiante->matriculas()->exists()) {
            throw ValidationException::withMessages([
                'estudiante' => 'No se puede eliminar porque tiene matrículas registradas. Cambie su estado a inactivo.',
            ]);
        }

        $before = $this->studentSnapshot($estudiante);
        $photoPath = $estudiante->foto_path;
        DB::transaction(function () use ($request, $estudiante, $before): void {
            $estudiante->delete();
            $this->auditStudentChange($request, $estudiante, 'eliminar', $before, null);
        });
        if ($photoPath) {
            Storage::disk('local')->delete($photoPath);
        }

        return redirect()->route('estudiantes.index')->with('success', 'Estudiante eliminado exitosamente.');
    }

    /** @return array<string, mixed> */
    private function studentSnapshot(Estudiante $estudiante): array
    {
        return [...$estudiante->only(['dni', 'nombre', 'apellido', 'email', 'telefono', 'fecha_nacimiento', 'direccion', 'colegio_procedencia', 'anio_egreso', 'estado']), 'tiene_foto' => (bool) $estudiante->foto_path];
    }

    /** @param array<string, mixed>|null $before
     * @param  array<string, mixed>|null  $after
     */
    private function auditStudentChange(Request $request, Estudiante $estudiante, string $action, ?array $before, ?array $after): void
    {
        Auditoria::query()->create([
            'id_usuario' => $request->user()->id_usuario,
            'accion' => $action,
            'tabla' => 'estudiantes',
            'id_registro' => (string) $estudiante->id_estudiante,
            'datos_antes' => $before,
            'datos_despues' => $after,
            'ip' => $request->ip(),
        ]);
    }
}
