<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\Horario;
use App\Models\Matricula;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('academia:cerrar-asistencias-diarias')]
#[Description('Marca falta a matrículas inscritas sin asistencia en las clases del día.')]
class CerrarAsistenciasDiarias extends Command
{
    public function handle(): int
    {
        $fecha = today();
        $diaSemana = (int) $fecha->dayOfWeekIso;
        $marcadas = 0;

        Horario::query()->where('dia_semana', $diaSemana)->each(function (Horario $horario) use ($fecha, &$marcadas): void {
            Matricula::query()
                ->where('id_grupo', $horario->id_grupo)
                ->where('estado_matricula', 'inscrito')
                ->whereHas('ciclo', fn ($query) => $query
                    ->whereDate('fecha_inicio', '<=', $fecha->toDateString())
                    ->whereDate('fecha_fin', '>=', $fecha->toDateString()))
                ->each(function (Matricula $matricula) use ($horario, $fecha, &$marcadas): void {
                    $attendance = Asistencia::query()->firstOrCreate(
                        [
                            'id_matricula' => $matricula->id_matricula,
                            'id_horario' => $horario->id_horario,
                            'fecha' => $fecha->toDateString(),
                        ],
                        ['estado' => 'Falta', 'origen' => 'manual'],
                    );

                    if ($attendance->wasRecentlyCreated) {
                        $marcadas++;
                    }
                });
        });

        $this->info("Se registraron {$marcadas} faltas.");

        return self::SUCCESS;
    }
}
