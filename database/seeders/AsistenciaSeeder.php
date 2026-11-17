<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Auditoria;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\RegistroIngreso;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AsistenciaSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::query()->where('email', 'secretaria@academia.test')->first();
        if (! $usuario) {
            return;
        }

        $horarios = Horario::query()->orderBy('hora_inicio')->get()->groupBy('id_grupo');
        $matriculas = Matricula::query()->with('ciclo')->where('estado_matricula', 'inscrito')->get();

        DB::transaction(function () use ($usuario, $horarios, $matriculas): void {
            foreach ($matriculas as $matricula) {
                for ($dias = 29; $dias >= 0; $dias--) {
                    $fecha = today()->subDays($dias);
                    if (! $matricula->ciclo || $fecha->lt($matricula->fecha_matricula)
                        || $fecha->toDateString() < $matricula->ciclo->fecha_inicio->toDateString()
                        || $fecha->toDateString() > $matricula->ciclo->fecha_fin->toDateString()
                        || ($matricula->fecha_retiro && $fecha->gt($matricula->fecha_retiro))) {
                        continue;
                    }

                    $clases = ($horarios->get($matricula->id_grupo) ?? collect())
                        ->filter(fn (Horario $horario) => (int) $horario->dia_semana === $fecha->dayOfWeekIso
                            && $fecha->copy()->setTimeFromTimeString($horario->hora_inicio)->lte(now()));
                    if ($clases->isEmpty()) {
                        continue;
                    }

                    $caso = ($matricula->id_matricula + $fecha->day) % 10;
                    $estado = match ($caso) {
                        0 => 'Falta',
                        1 => 'Justificada',
                        2, 3 => 'Tarde',
                        default => 'Presente',
                    };
                    $ingreso = null;
                    if (in_array($estado, ['Presente', 'Tarde'], true)) {
                        $hora = $fecha->copy()->setTimeFromTimeString($clases->first()->hora_inicio)
                            ->addMinutes($estado === 'Tarde' ? 20 : -10);
                        $ingreso = RegistroIngreso::query()->where('id_matricula', $matricula->id_matricula)
                            ->whereDate('fecha_hora', $fecha->toDateString())->first();
                        $ingreso ??= RegistroIngreso::query()->firstOrCreate([
                            'id_matricula' => $matricula->id_matricula,
                            'tipo' => 'ingreso',
                            'fecha_hora' => $hora,
                        ], [
                            'id_usuario' => $usuario->id_usuario,
                            'metodo' => 'qr',
                            'observacion' => 'Ingreso de demostración',
                        ]);
                    }

                    foreach ($clases as $clase) {
                        $asistencia = Asistencia::query()->firstOrCreate([
                            'id_matricula' => $matricula->id_matricula,
                            'id_horario' => $clase->id_horario,
                            'fecha' => $fecha->toDateString(),
                        ], [
                            'estado' => $estado,
                            'origen' => $ingreso ? 'qr' : 'manual',
                            'id_usuario_registro' => $usuario->id_usuario,
                            'id_registro_ingreso' => $ingreso?->id_registro,
                            'hora_registro' => $ingreso?->fecha_hora->format('H:i:s'),
                        ]);
                        if ($asistencia->wasRecentlyCreated) {
                            Auditoria::query()->create([
                                'id_usuario' => $usuario->id_usuario,
                                'accion' => 'crear',
                                'tabla' => 'asistencias',
                                'id_registro' => (string) $asistencia->id_asistencia,
                                'datos_despues' => $asistencia->only(['id_matricula', 'id_horario', 'fecha', 'estado', 'origen']),
                            ]);
                        }
                    }
                }
            }
        });
    }
}
