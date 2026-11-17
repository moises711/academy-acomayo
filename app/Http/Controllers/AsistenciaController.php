<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsistenciaPeriodoRequest;
use App\Http\Requests\StoreAsistenciaRequest;
use App\Http\Requests\UpdateAsistenciaRequest;
use App\Models\Asistencia;
use App\Models\Auditoria;
use App\Models\Configuracion;
use App\Models\Cuota;
use App\Models\Estudiante;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\RegistroIngreso;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AsistenciaController extends Controller
{
    public function index(AsistenciaPeriodoRequest $request): Response
    {
        [$desde, $hasta] = $request->rango();
        if (! $request->filled('desde') && ! $request->filled('hasta')) {
            $desde = today()->startOfMonth()->toDateString();
        }
        $inicio = now()->parse($desde)->startOfDay();
        $fin = now()->parse($hasta)->endOfDay();

        return Inertia::render('Asistencias/Index', [
            'horarios' => Horario::query()
                ->with(['curso', 'grupo.ciclo', 'aula'])
                ->where('dia_semana', $inicio->dayOfWeekIso)
                ->orderBy('hora_inicio')
                ->get(),
            'padron' => Matricula::query()
                ->select(['id_matricula', 'id_estudiante', 'id_grupo', 'fecha_matricula', 'fecha_retiro'])
                ->with(['estudiante:id_estudiante,nombre,apellido,dni', 'grupo:id_grupo,nombre'])
                ->whereNotNull('id_grupo')
                ->whereDate('fecha_matricula', '<=', $hasta)
                ->where(fn ($query) => $query->whereNull('fecha_retiro')->orWhereDate('fecha_retiro', '>=', $desde))
                ->whereHas('ciclo', fn ($query) => $query
                    ->whereDate('fecha_inicio', '<=', $hasta)
                    ->whereDate('fecha_fin', '>=', $desde))
                ->get(),
            'ingresosPorDia' => RegistroIngreso::query()
                ->select(['id_matricula', 'fecha_hora'])
                ->where('tipo', 'ingreso')
                ->whereBetween('fecha_hora', [$inicio, $fin])
                ->get()
                ->map(fn (RegistroIngreso $ingreso) => [
                    'id_matricula' => $ingreso->id_matricula,
                    'fecha' => $ingreso->fecha_hora->toDateString(),
                ])
                ->unique(fn (array $ingreso) => $ingreso['id_matricula'].'|'.$ingreso['fecha'])
                ->values(),
            'asistencias' => Asistencia::query()
                ->with(['matricula.estudiante', 'horario.curso', 'usuario'])
                ->whereBetween('fecha', [$desde, $hasta])
                ->orderBy('hora_registro')
                ->get(),
            'desde' => $desde,
            'hasta' => $hasta,
            'canScan' => $request->user()->hasPermissionTo('asistencia.registrar_qr')
                && $desde <= today()->toDateString() && $hasta >= today()->toDateString(),
            'canCorrect' => $request->user()->hasPermissionTo('asistencia.registrar_manual'),
            'stats' => [
                'ingresos' => RegistroIngreso::query()->whereBetween('fecha_hora', [$inicio, $fin])->count(),
                'asistencias' => Asistencia::query()->whereBetween('fecha', [$desde, $hasta])->count(),
            ],
        ]);
    }

    public function exportar(AsistenciaPeriodoRequest $request): StreamedResponse
    {
        [$desde, $hasta] = $request->rango();
        $filename = "asistencias-{$desde}-{$hasta}.csv";

        return response()->streamDownload(function () use ($desde, $hasta): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Fecha', 'Hora', 'DNI', 'Estudiante', 'Grupo', 'Curso', 'Estado', 'Origen', 'Registrado por'], ';', '"', '\\');

            Asistencia::query()
                ->with(['matricula.estudiante', 'matricula.grupo', 'horario.curso', 'usuario'])
                ->whereBetween('fecha', [$desde, $hasta])
                ->lazyById(500, 'id_asistencia')
                ->each(function (Asistencia $asistencia) use ($output): void {
                    $student = $asistencia->matricula?->estudiante;
                    fputcsv($output, [
                        $asistencia->fecha?->format('d/m/Y'),
                        $asistencia->hora_registro,
                        $student?->dni,
                        trim(($student?->nombre ?? '').' '.($student?->apellido ?? '')),
                        $asistencia->matricula?->grupo?->nombre,
                        $asistencia->horario?->curso?->nombre_curso,
                        $asistencia->estado,
                        $asistencia->origen,
                        $asistencia->usuario?->name ?? 'Sistema',
                    ], ';', '"', '\\');
                });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function store(StoreAsistenciaRequest $request): RedirectResponse
    {
        $result = DB::transaction(function () use ($request): array {
            $estudiante = Estudiante::query()
                ->where('codigo_qr', $request->validated('codigo_qr'))
                ->lockForUpdate()
                ->first();

            if (! $estudiante || $estudiante->estado !== 'activo') {
                throw ValidationException::withMessages([
                    'codigo_qr' => 'El código QR no es válido o el estudiante está inactivo.',
                ]);
            }

            $matricula = Matricula::query()
                ->where('id_estudiante', $estudiante->id_estudiante)
                ->where('estado_matricula', 'inscrito')
                ->whereNotNull('id_grupo')
                ->whereHas('ciclo', fn ($query) => $query
                    ->whereDate('fecha_inicio', '<=', today()->toDateString())
                    ->whereDate('fecha_fin', '>=', today()->toDateString()))
                ->with('grupo')
                ->lockForUpdate()
                ->first();

            if (! $matricula) {
                throw ValidationException::withMessages([
                    'codigo_qr' => 'El estudiante no tiene una matrícula activa para el ciclo actual.',
                ]);
            }

            $alreadyRegistered = RegistroIngreso::query()
                ->where('id_matricula', $matricula->id_matricula)
                ->whereDate('fecha_hora', today()->toDateString())
                ->exists();

            if ($alreadyRegistered) {
                return [
                    'status' => 'error',
                    'message' => "{$estudiante->nombre} {$estudiante->apellido} ya registró su ingreso hoy.",
                ];
            }

            $cuotasVencidas = Cuota::query()
                ->where('id_matricula', $matricula->id_matricula)
                ->whereDate('fecha_vencimiento', '<', today()->toDateString())
                ->whereNotIn('estado_pago', ['Pagado', 'Anulado'])
                ->exists();
            $bloquearPorDeuda = Configuracion::query()
                ->whereKey('asistencia.bloquear_si_deuda')
                ->value('valor') === '1';

            if ($cuotasVencidas && $bloquearPorDeuda) {
                return [
                    'status' => 'error',
                    'message' => "Ingreso bloqueado: {$estudiante->nombre} {$estudiante->apellido} tiene cuotas vencidas.",
                ];
            }

            $ingreso = RegistroIngreso::query()->create([
                'id_matricula' => $matricula->id_matricula,
                'id_usuario' => $request->user()->id_usuario,
                'tipo' => 'ingreso',
                'metodo' => 'qr',
                'fecha_hora' => now(),
            ]);

            $now = now();
            $dayOfWeek = (int) $now->dayOfWeekIso;
            $tolerancia = (int) (Configuracion::query()
                ->whereKey('asistencia.tolerancia_minutos')
                ->value('valor') ?? 10);
            $horarios = Horario::query()
                ->where('id_grupo', $matricula->id_grupo)
                ->where('dia_semana', $dayOfWeek)
                ->get();

            foreach ($horarios as $horario) {
                $horaInicio = $now->copy()->setTimeFromTimeString($horario->hora_inicio);
                if ($horaInicio->greaterThan($now->copy()->addMinutes($tolerancia))) {
                    continue;
                }

                $estado = $now->greaterThan($horaInicio->addMinutes($tolerancia)) ? 'Tarde' : 'Presente';

                $attendance = Asistencia::query()->firstOrCreate(
                    [
                        'id_matricula' => $matricula->id_matricula,
                        'id_horario' => $horario->id_horario,
                        'fecha' => $now->toDateString(),
                    ],
                    [
                        'estado' => $estado,
                        'origen' => 'qr',
                        'id_usuario_registro' => $request->user()->id_usuario,
                        'id_registro_ingreso' => $ingreso->id_registro,
                        'hora_registro' => $now->toTimeString(),
                    ],
                );

                if ($attendance->wasRecentlyCreated) {
                    Auditoria::query()->create([
                        'id_usuario' => $request->user()->id_usuario,
                        'accion' => 'crear',
                        'tabla' => 'asistencias',
                        'id_registro' => (string) $attendance->id_asistencia,
                        'datos_despues' => $attendance->only([
                            'id_matricula', 'id_horario', 'fecha', 'estado', 'origen', 'id_usuario_registro',
                        ]),
                        'ip' => $request->ip(),
                    ]);
                }
            }

            return [
                'status' => $cuotasVencidas ? 'warning' : 'success',
                'message' => $cuotasVencidas
                    ? "Ingreso registrado para {$estudiante->nombre} {$estudiante->apellido}. Tiene cuotas vencidas pendientes."
                    : "Ingreso registrado para {$estudiante->nombre} {$estudiante->apellido}.",
            ];
        });

        return back()->with($result['status'], $result['message']);
    }

    public function update(UpdateAsistenciaRequest $request, Asistencia $asistencia): RedirectResponse
    {
        $validated = $request->validated();
        $before = $asistencia->only(['estado', 'origen', 'id_usuario_registro', 'id_registro_ingreso', 'hora_registro']);

        DB::transaction(function () use ($asistencia, $before, $request, $validated): void {
            $asistencia->update([
                'estado' => $validated['estado'],
                'origen' => 'manual',
                'id_usuario_registro' => $request->user()->id_usuario,
                'id_registro_ingreso' => null,
                'hora_registro' => now()->toTimeString(),
            ]);

            Auditoria::query()->create([
                'id_usuario' => $request->user()->id_usuario,
                'accion' => 'editar',
                'tabla' => 'asistencias',
                'id_registro' => (string) $asistencia->id_asistencia,
                'datos_antes' => $before,
                'datos_despues' => [
                    ...$asistencia->only(['estado', 'origen', 'id_usuario_registro', 'id_registro_ingreso', 'hora_registro']),
                    'motivo' => $validated['motivo'],
                ],
                'ip' => $request->ip(),
            ]);
        });

        return back()->with('success', 'Asistencia corregida y registrada en auditoría.');
    }
}
