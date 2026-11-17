<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Simulacro extends Model
{
    protected $table = 'simulacros';

    protected $primaryKey = 'id_simulacro';

    protected $fillable = [
        'titulo',
        'fecha_aplicacion',
        'id_ciclo',
        'puntaje_maximo',
    ];

    public function getRouteKeyName(): string
    {
        return 'id_simulacro';
    }

    protected function casts(): array
    {
        return [
            'fecha_aplicacion' => 'date',
            'puntaje_maximo' => 'decimal:2',
        ];
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class, 'id_ciclo');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(ResultadosSimulacro::class, 'id_simulacro');
    }

    public function clavesRespuesta(): HasMany
    {
        return $this->hasMany(ClaveRespuesta::class, 'id_simulacro', 'id_simulacro');
    }

    public function hojasRespuesta(): HasMany
    {
        return $this->hasMany(HojaRespuesta::class, 'id_simulacro', 'id_simulacro');
    }

    public function calificarHoja(HojaRespuesta $hoja): ?ResultadosSimulacro
    {
        if (! $hoja->id_matricula) {
            return null;
        }

        $answers = DB::table('respuestas_hoja')->where('id_hoja', $hoja->id_hoja)->get()->keyBy('numero_pregunta');
        if ($answers->contains(fn (object $answer): bool => $answer->estado === 'dudosa')) {
            return null;
        }

        $keys = ClaveRespuesta::query()
            ->where('id_simulacro', $this->id_simulacro)
            ->with('curso:id_curso,nombre_curso')
            ->orderBy('numero_pregunta')
            ->get();
        if ($keys->isEmpty()) {
            return null;
        }

        $courseScores = [];
        $totalCents = 0;
        foreach ($keys as $key) {
            $answer = $answers->get($key->numero_pregunta);
            if (! $answer) {
                return null;
            }

            $courseId = (int) $key->id_curso;
            $courseScores[$courseId] ??= [
                'correctas' => 0,
                'incorrectas' => 0,
                'en_blanco' => 0,
                'puntaje' => 0,
            ];

            if ($answer->estado === 'blanco') {
                $courseScores[$courseId]['en_blanco']++;

                continue;
            }

            if ($answer->estado === 'valida' && $answer->marcada === $key->respuesta_correcta) {
                $courseScores[$courseId]['correctas']++;
                $points = $this->decimalToCents((string) $key->puntos_correcta);
            } else {
                $courseScores[$courseId]['incorrectas']++;
                $points = $this->decimalToCents((string) $key->puntos_incorrecta);
            }

            $courseScores[$courseId]['puntaje'] += $points;
            $totalCents += $points;
        }

        $result = ResultadosSimulacro::query()->updateOrCreate([
            'id_simulacro' => $this->id_simulacro,
            'id_matricula' => $hoja->id_matricula,
        ], [
            'puntaje_obtenido' => $this->centsToDecimal($totalCents),
            'fecha_rendicion' => $this->fecha_aplicacion->toDateString(),
            'estado_ranking' => 'pendiente_actualizar',
        ]);

        ResultadoCurso::query()->where('id_resultado', $result->id_resultado)->delete();
        foreach ($courseScores as $courseId => $score) {
            ResultadoCurso::query()->updateOrCreate([
                'id_resultado' => $result->id_resultado,
                'id_curso' => $courseId,
            ], [
                'correctas' => $score['correctas'],
                'incorrectas' => $score['incorrectas'],
                'en_blanco' => $score['en_blanco'],
                'puntaje' => $this->centsToDecimal($score['puntaje']),
            ]);
        }

        $this->recalcularPuestos();

        return $result->fresh();
    }

    public function recalcularPuestos(): void
    {
        $results = ResultadosSimulacro::query()
            ->with('matricula:id_matricula,id_carrera_objetivo')
            ->where('id_simulacro', $this->id_simulacro)
            ->orderByDesc('puntaje_obtenido')
            ->orderBy('id_resultado')
            ->get();
        $lastScore = null;
        $rank = 0;

        foreach ($results as $index => $result) {
            $score = $this->decimalToCents((string) $result->puntaje_obtenido);
            if ($lastScore !== $score) {
                $rank = $index + 1;
            }
            $result->puesto_general = $rank;
            $lastScore = $score;
        }

        foreach ($results->groupBy(fn (ResultadosSimulacro $result): string => (string) ($result->matricula?->id_carrera_objetivo ?? 'sin-carrera')) as $careerResults) {
            $lastScore = null;
            $rank = 0;
            foreach ($careerResults->values() as $index => $result) {
                $score = $this->decimalToCents((string) $result->puntaje_obtenido);
                if ($lastScore !== $score) {
                    $rank = $index + 1;
                }
                $result->puesto_carrera = $rank;
                $result->estado_ranking = 'procesado';
                $result->save();
                $lastScore = $score;
            }
        }
    }

    private function decimalToCents(string $amount): int
    {
        $negative = str_starts_with($amount, '-');
        $amount = ltrim($amount, '-');
        [$units, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');
        $cents = ((int) $units * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $negative ? -$cents : $cents;
    }

    private function centsToDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
