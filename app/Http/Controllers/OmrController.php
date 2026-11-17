<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClavesRespuestaRequest;
use App\Http\Requests\StorePlantillaHojaRequest;
use App\Http\Requests\UpdateHojaRespuestaRequest;
use App\Http\Requests\UploadHojasRespuestaRequest;
use App\Models\Auditoria;
use App\Models\ClaveRespuesta;
use App\Models\ConvocatoriaBeca;
use App\Models\Curso;
use App\Models\HojaRespuesta;
use App\Models\Matricula;
use App\Models\PlantillaHoja;
use App\Models\PostulanteBeca;
use App\Models\ResultadosSimulacro;
use App\Models\Simulacro;
use App\Support\AutoOmrImport;
use App\Support\BecaGrading;
use App\Support\CompactOmrLayout;
use App\Support\InertiaPaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OmrController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'id_simulacro' => ['nullable', 'integer', 'exists:simulacros,id_simulacro'],
            'estado' => ['nullable', 'in:pendiente,procesada,revision,error'],
        ]);
        $simulacros = Simulacro::query()->with('ciclo')->orderByDesc('fecha_aplicacion')->get();
        $simulacroId = $validated['id_simulacro'] ?? $simulacros->first()?->id_simulacro;
        $simulacro = $simulacros->firstWhere('id_simulacro', (int) $simulacroId);
        $baseHojasQuery = HojaRespuesta::query()
            ->when($simulacroId, fn ($query) => $query->where('id_simulacro', $simulacroId));
        $hojasQuery = (clone $baseHojasQuery)
            ->with(['matricula.estudiante', 'matricula.grupo', 'postulante:id_postulante,id_convocatoria,dni,grupo,nota,estado', 'plantillahoja', 'respuestas'])
            ->when(isset($validated['estado']), fn ($query) => $query->where('estado', $validated['estado']))
            ->latest('created_at');
        $stats = [
            'total' => (clone $baseHojasQuery)->count(),
            'pendientes' => (clone $baseHojasQuery)->where('estado', 'pendiente')->count(),
            'revision' => (clone $baseHojasQuery)->where('estado', 'revision')->count(),
            'errores' => (clone $baseHojasQuery)->where('estado', 'error')->count(),
            'procesadas' => (clone $baseHojasQuery)->where('estado', 'procesada')->count(),
        ];

        $scores = ResultadosSimulacro::query()->where('id_simulacro', $simulacroId)->pluck('puntaje_obtenido', 'id_matricula');
        $keyMap = ClaveRespuesta::query()->with('curso')->where('id_simulacro', $simulacroId)->orderBy('numero_pregunta')->get()->keyBy('numero_pregunta');
        $page = $hojasQuery->paginate(12)->withQueryString()->through(function (HojaRespuesta $sheet) use ($scores, $keyMap) {
            $summary = ['correctas' => 0, 'incorrectas' => 0, 'en_blanco' => 0, 'por_revisar' => 0];
            foreach ($sheet->respuestas as $answer) {
                $key = $keyMap->get($answer->numero_pregunta);
                $category = ! $key || $answer->estado === 'dudosa' ? 'por_revisar' : ($answer->estado === 'blanco' ? 'en_blanco' : ($answer->estado === 'valida' && $answer->marcada === $key->respuesta_correcta ? 'correctas' : 'incorrectas'));
                $summary[$category]++;
            }

            return $sheet->setAttribute('nota_omr', $sheet->id_matricula ? ($scores[$sheet->id_matricula] ?? null) : null)->setAttribute('resumen_lectura', $summary);
        });

        return Inertia::render('Omr/Index', [
            'simulacros' => $simulacros,
            'simulacroSeleccionado' => $simulacro,
            'convocatoriaBeca' => $simulacro ? ConvocatoriaBeca::query()->where(fn ($q) => $q->where('id_simulacro_ab', $simulacro->id_simulacro)->orWhere('id_simulacro_cd', $simulacro->id_simulacro))->first(['id_convocatoria', 'nombre', 'estado', 'id_simulacro_ab', 'id_simulacro_cd']) : null,
            'claves' => $keyMap->values(),
            'cursos' => Curso::query()->orderBy('nombre_curso')->get(['id_curso', 'nombre_curso']),
            'plantillas' => PlantillaHoja::query()->where('activa', true)
                ->whereJsonContains('config->simulacro_id', $simulacro?->id_simulacro ?? -1)
                ->orderBy('nombre')->get(),
            'matriculas' => $simulacro
                ? Matricula::query()->with(['estudiante', 'grupo'])
                    ->where('id_ciclo', $simulacro->id_ciclo)->where('estado_matricula', 'inscrito')
                    ->orderBy('id_matricula')->limit(300)->get()
                : [],
            'hojas' => InertiaPaginator::from($page),
            'filters' => $validated,
            'omr' => ['available' => true, 'message' => 'Abre el escáner en vivo para leer DNI, grupo y respuestas de una cartilla compacta. Las marcas ambiguas requieren revisión.'],
            'stats' => $stats,
        ]);
    }

    public function storeClaves(StoreClavesRespuestaRequest $request, Simulacro $simulacro): RedirectResponse
    {
        if (ConvocatoriaBeca::query()->where('estado', 'Cerrada')->where(fn ($q) => $q->where('id_simulacro_ab', $simulacro->id_simulacro)->orWhere('id_simulacro_cd', $simulacro->id_simulacro))->exists()) {
            throw ValidationException::withMessages(['claves' => 'No se puede cambiar la clave de una convocatoria cerrada.']);
        }
        $claves = collect($request->validated('claves'));
        $rows = $claves->map(fn (array $clave): array => [
            'id_simulacro' => $simulacro->id_simulacro,
            'id_curso' => $clave['id_curso'],
            'numero_pregunta' => $clave['numero_pregunta'],
            'respuesta_correcta' => $clave['respuesta_correcta'],
            'puntos_correcta' => $clave['puntos_correcta'],
            'puntos_incorrecta' => $clave['puntos_incorrecta'],
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        DB::transaction(function () use ($rows, $claves, $simulacro): void {
            $campaign = ConvocatoriaBeca::query()->where(fn ($q) => $q->where('id_simulacro_ab', $simulacro->id_simulacro)->orWhere('id_simulacro_cd', $simulacro->id_simulacro))->lockForUpdate()->first();
            if ($campaign?->estado === 'Cerrada') {
                throw ValidationException::withMessages(['claves' => 'La convocatoria está cerrada.']);
            }
            DB::table('claves_respuesta')->upsert(
                $rows,
                ['id_simulacro', 'numero_pregunta'],
                ['id_curso', 'respuesta_correcta', 'puntos_correcta', 'puntos_incorrecta', 'updated_at'],
            );

            $includedNumbers = $claves->pluck('numero_pregunta')->all();
            ClaveRespuesta::query()
                ->where('id_simulacro', $simulacro->id_simulacro)
                ->whereNotIn('numero_pregunta', $includedNumbers)
                ->delete();

            $simulacro->hojasRespuesta()
                ->with('respuestas')
                ->where(fn ($q) => $q->whereNotNull('id_matricula')->orWhereNotNull('id_postulante'))
                ->whereIn('estado', ['procesada', 'revision'])
                ->get()
                ->each(function (HojaRespuesta $hoja) use ($simulacro): void {
                    if ($hoja->respuestas->contains('estado', 'dudosa') || ($hoja->estado === 'revision' && $hoja->respuestas->contains('estado', 'multiple'))) {
                        return;
                    }
                    if ($hoja->id_postulante) {
                        app(BecaGrading::class)->grade($hoja);
                    } else {
                        $simulacro->calificarHoja($hoja);
                    }
                });
        });

        return back()->with('success', 'Clave guardada y resultados recalculados.');
    }

    public function storePlantilla(StorePlantillaHojaRequest $request, Simulacro $simulacro): RedirectResponse
    {
        $keys = ClaveRespuesta::query()
            ->with('curso:id_curso,nombre_curso')
            ->where('id_simulacro', $simulacro->id_simulacro)
            ->orderBy('numero_pregunta')
            ->get();

        if ($keys->isEmpty()) {
            throw ValidationException::withMessages(['plantilla' => 'Primero registra la clave de respuestas del simulacro.']);
        }
        if ($keys->count() > 120) {
            throw ValidationException::withMessages(['plantilla' => 'Una plantilla admite hasta 120 preguntas. Divide el examen en más de una hoja.']);
        }

        $compact = $request->validated('diseno') === 'compacto';
        $options = (int) ($request->validated('opciones_por_pregunta') ?? 5);
        if ($compact && $keys->count() > 80) {
            throw ValidationException::withMessages(['diseno' => 'El diseño compacto admite hasta 80 preguntas.']);
        }
        if ($options === 4 && $keys->contains('respuesta_correcta', 'E')) {
            throw ValidationException::withMessages(['opciones_por_pregunta' => 'La clave contiene respuestas E. Usa cinco alternativas o corrige la clave antes de crear la cartilla.']);
        }
        $columns = (int) ($request->validated('columnas') ?? 2);
        if (! $compact && $columns === 1 && $keys->count() > 60) {
            throw ValidationException::withMessages(['columnas' => 'Para más de 60 preguntas utiliza dos columnas.']);
        }
        $questions = [];
        $sections = [];
        $lastCourse = null;
        foreach ($keys as $index => $key) {
            $column = intdiv($index, 60);
            $row = $index % 60;
            $labelX = $column === 0 ? 160 : 850;
            $y = 400 + ($row * 30);
            $courseId = (int) $key->id_curso;
            if ($lastCourse !== $courseId) {
                $sections[] = ['nombre' => $key->curso->nombre_curso, 'x' => $labelX, 'y' => $y - 12];
                $lastCourse = $courseId;
            }

            $questions[] = [
                'numero_pregunta' => (int) $key->numero_pregunta,
                'id_curso' => $courseId,
                'label_x' => $labelX,
                'y' => $y,
                'opciones' => collect(array_slice(['A', 'B', 'C', 'D', 'E'], 0, $options))->values()->map(fn (string $label, int $option): array => [
                    'label' => $label,
                    'x' => ($column === 0 ? 260 : 950) + ($option * 42),
                    'y' => $y,
                ])->all(),
            ];
        }

        $template = PlantillaHoja::query()->create([
            'nombre' => $request->validated('nombre'),
            'num_preguntas' => $keys->count(),
            'opciones_por_pregunta' => $options,
            'activa' => true,
            'config' => array_replace([
                'version' => 1,
                'titulo' => $request->validated('titulo') ?: $simulacro->titulo,
                'instrucciones' => $request->validated('instrucciones') ?: 'Rellena una sola alternativa por pregunta. Utiliza lápiz y evita tachaduras.',
                'columnas' => $columns,
                'simulacro_id' => $simulacro->id_simulacro,
                'page' => ['width' => 1654, 'height' => 2339],
                'alignment_marks' => [[50, 50], [1572, 50], [50, 2257], [1572, 2257]],
                'alignment_size' => 32,
                'qr' => ['x' => 1350, 'y' => 80, 'size' => 200],
                'bubble_radius' => 10,
                'thresholds' => ['marcada' => 0.34, 'tenue' => 0.11],
                'sections' => $sections,
                'questions' => $questions,
            ], $compact ? app(CompactOmrLayout::class)->build($keys, $options) : []),
        ]);

        return back()->with('success', "Plantilla {$template->nombre} creada para {$template->num_preguntas} preguntas.");
    }

    public function subir(UploadHojasRespuestaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $simulacro = Simulacro::query()->findOrFail($data['id_simulacro']);
        $template = PlantillaHoja::query()->findOrFail($data['id_plantilla']);
        if ((int) ($template->config['simulacro_id'] ?? 0) !== (int) $simulacro->id_simulacro) {
            throw ValidationException::withMessages(['id_plantilla' => 'La plantilla pertenece a otro simulacro.']);
        }
        $matriculaId = $data['id_matricula'] ?? null;
        if ($matriculaId && ! Matricula::query()->where('id_ciclo', $simulacro->id_ciclo)->whereKey($matriculaId)->exists()) {
            throw ValidationException::withMessages(['id_matricula' => 'La matrícula seleccionada no pertenece al ciclo del simulacro.']);
        }

        $paths = [];
        try {
            $processed = DB::transaction(function () use ($request, $data, $matriculaId, &$paths): int {
                $campaign = ConvocatoriaBeca::query()->where(fn ($q) => $q->where('id_simulacro_ab', $data['id_simulacro'])->orWhere('id_simulacro_cd', $data['id_simulacro']))->lockForUpdate()->first();
                if ($campaign?->estado === 'Cerrada') {
                    throw ValidationException::withMessages(['archivos' => 'Esta convocatoria ya está cerrada.']);
                }
                $processed = 0;
                foreach ($request->file('archivos') as $index => $file) {
                    $paths[] = $path = $file->store('omr/uploads', 'local');
                    $reading = $data['lecturas'][$index] ?? null;
                    $hoja = HojaRespuesta::query()->create([
                        'id_simulacro' => $data['id_simulacro'], 'id_matricula' => $reading ? null : $matriculaId,
                        'id_plantilla' => $data['id_plantilla'], 'codigo_hoja' => 'P-'.Str::uuid(),
                        'archivo_path' => $path, 'estado' => 'revision', 'id_usuario' => $request->user()->id_usuario,
                    ]);
                    if ($reading && app(AutoOmrImport::class)->apply($hoja, $reading)) {
                        $processed++;
                    }
                }

                return $processed;
            });
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($paths);
            throw $error;
        }
        $total = count($paths);

        return back()->with('success', "Se cargaron {$total} hojas: {$processed} calificadas y ".($total - $processed).' para revisión.');
    }

    public function actualizar(UpdateHojaRespuestaRequest $request, HojaRespuesta $hoja): RedirectResponse
    {
        $data = $request->validated();
        $postulante = $hoja->postulante;
        if (! empty($data['dni_postulante'])) {
            Gate::authorize('grade', PostulanteBeca::class);
            $postulante = PostulanteBeca::query()->with('convocatoria')->where('dni', $data['dni_postulante'])
                ->whereHas('convocatoria', fn ($q) => $q->where(fn ($q) => $q->where('id_simulacro_ab', $hoja->id_simulacro)->orWhere('id_simulacro_cd', $hoja->id_simulacro)))->first();
            if (! $postulante || $postulante->examenId() !== $hoja->id_simulacro) {
                throw ValidationException::withMessages(['dni_postulante' => 'No existe un postulante con ese DNI para este examen y agrupación.']);
            }
            if ($hoja->id_postulante && $hoja->id_postulante !== $postulante->id_postulante) {
                throw ValidationException::withMessages(['dni_postulante' => 'La hoja ya está vinculada a otro postulante.']);
            }
        }
        if ($postulante && ! empty($data['id_matricula'])) {
            throw ValidationException::withMessages(['id_matricula' => 'Una hoja no puede pertenecer a un estudiante y a un postulante simultáneamente.']);
        }
        $matriculaId = $postulante ? null : ($data['id_matricula'] ?? $hoja->id_matricula);
        if ($matriculaId && ! Matricula::query()->where('id_ciclo', $hoja->simulacro->id_ciclo)->where('estado_matricula', 'inscrito')->whereKey($matriculaId)->exists()) {
            throw ValidationException::withMessages(['id_matricula' => 'La matrícula no pertenece al ciclo del simulacro.']);
        }

        $questionNumbers = collect($hoja->plantillahoja->config['questions'])->pluck('numero_pregunta')->all();
        $corrections = collect($data['respuestas'] ?? []);
        $allowedOptions = array_slice(['A', 'B', 'C', 'D', 'E'], 0, $hoja->plantillahoja->opciones_por_pregunta);
        if ($corrections->contains(fn (array $answer): bool => ! in_array((int) $answer['numero_pregunta'], $questionNumbers, true)
            || ($answer['estado'] === 'valida' && (empty($answer['marcada']) || ! in_array($answer['marcada'], $allowedOptions, true)))
            || ($answer['estado'] === 'blanco' && ! empty($answer['marcada']))
            || (in_array($answer['estado'], ['multiple', 'dudosa'], true) && ! empty($answer['marcada'])))) {
            throw ValidationException::withMessages(['respuestas' => 'Cada pregunta debe usar un estado y una alternativa coherentes con la plantilla.']);
        }

        DB::transaction(function () use ($hoja, $matriculaId, $postulante, $corrections, $data, $request): void {
            if ($postulante) {
                $campaign = ConvocatoriaBeca::query()->lockForUpdate()->findOrFail($postulante->id_convocatoria);
                if ($campaign->estado === 'Cerrada') {
                    throw ValidationException::withMessages(['dni_postulante' => 'Esta convocatoria está cerrada.']);
                }
                $postulante = PostulanteBeca::query()->lockForUpdate()->findOrFail($postulante->id_postulante);
                if (HojaRespuesta::query()->where('id_postulante', $postulante->id_postulante)->where('id_hoja', '!=', $hoja->id_hoja)->exists()) {
                    throw ValidationException::withMessages(['dni_postulante' => 'Este postulante ya tiene otra hoja vinculada.']);
                }
            }
            $hoja = HojaRespuesta::query()->lockForUpdate()->findOrFail($hoja->id_hoja);
            $before = [
                'id_postulante' => $hoja->id_postulante,
                'id_matricula' => $hoja->id_matricula,
                'respuestas' => DB::table('respuestas_hoja')->where('id_hoja', $hoja->id_hoja)->orderBy('numero_pregunta')->get()->toArray(),
            ];
            $hoja->update([
                'id_postulante' => $postulante?->id_postulante,
                'id_matricula' => $matriculaId,
                'estado' => $corrections->contains('estado', 'dudosa') ? 'revision' : 'procesada',
            ]);

            foreach ($corrections as $answer) {
                DB::table('respuestas_hoja')->updateOrInsert(
                    ['id_hoja' => $hoja->id_hoja, 'numero_pregunta' => $answer['numero_pregunta']],
                    ['marcada' => $answer['marcada'] ?? null, 'estado' => $answer['estado']],
                );
            }

            $allAnswers = DB::table('respuestas_hoja')->where('id_hoja', $hoja->id_hoja)->get();
            $requiresReview = (! $matriculaId && ! $postulante)
                || $allAnswers->count() !== $hoja->plantillahoja->num_preguntas
                || $allAnswers->contains('estado', 'dudosa');
            $hoja->update(['estado' => $requiresReview ? 'revision' : 'procesada']);

            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => 'editar',
                'tabla' => 'respuestas_hoja',
                'id_registro' => (string) $hoja->id_hoja,
                'datos_antes' => $before,
                'datos_despues' => [
                    'id_postulante' => $postulante?->id_postulante,
                    'id_matricula' => $matriculaId,
                    'respuestas' => $corrections->all(),
                    'motivo' => $data['motivo'],
                ],
                'ip' => $request->ip(),
            ]);

            if ($postulante) {
                app(BecaGrading::class)->grade($hoja);
            } elseif (! $requiresReview) {
                $hoja->simulacro->calificarHoja($hoja);
            }
        });

        return back()->with('success', 'La hoja fue actualizada y su corrección quedó auditada.');
    }

    public function descargar(HojaRespuesta $hoja): mixed
    {
        if (! $hoja->archivo_path || ! Storage::disk('local')->exists($hoja->archivo_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($hoja->archivo_path, basename($hoja->archivo_path));
    }

    public function previsualizar(HojaRespuesta $hoja): BinaryFileResponse
    {
        abort_unless($hoja->archivo_path && Storage::disk('local')->exists($hoja->archivo_path), 404);
        $mime = Storage::disk('local')->mimeType($hoja->archivo_path);
        abort_unless(in_array($mime, ['image/jpeg', 'image/png'], true), 404);

        return response()->file(Storage::disk('local')->path($hoja->archivo_path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => 'inline; filename="revision-omr"',
        ]);
    }
}
