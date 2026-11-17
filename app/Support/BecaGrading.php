<?php

namespace App\Support;

use App\Models\ClaveRespuesta;
use App\Models\HojaRespuesta;
use App\Models\PostulanteBeca;
use Illuminate\Validation\ValidationException;

class BecaGrading
{
    public static function cents(string $value): int
    {
        $negative = str_starts_with($value, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-')), 2, '');
        $amount = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $negative ? -$amount : $amount;
    }

    public function grade(HojaRespuesta $sheet): void
    {
        $applicant = PostulanteBeca::query()->with('convocatoria')->lockForUpdate()->findOrFail($sheet->id_postulante);
        if ($applicant->convocatoria->estado === 'Cerrada' || $applicant->examenId() !== (int) $sheet->id_simulacro) {
            throw ValidationException::withMessages(['dni_postulante' => 'La convocatoria está cerrada o el examen no corresponde al grupo.']);
        }
        $answers = $sheet->respuestas()->get()->keyBy('numero_pregunta');
        $keys = ClaveRespuesta::query()->where('id_simulacro', $sheet->id_simulacro)->get()->keyBy('numero_pregunta');
        $numbers = collect($sheet->plantillahoja->config['questions'])->pluck('numero_pregunta');
        if ($keys->isEmpty() || $numbers->count() !== $keys->count() || $numbers->contains(fn ($number) => ! $keys->has($number))) {
            throw ValidationException::withMessages(['respuestas' => 'La clave y la plantilla deben contener las mismas preguntas para calificar la beca.']);
        }
        if ($answers->count() !== $numbers->count() || $numbers->contains(fn ($number) => ! $answers->has($number)) || $answers->contains('estado', 'dudosa')) {
            $applicant->update(['nota' => null, 'estado' => 'Revision']);

            return;
        }
        $score = 0;
        foreach ($keys as $number => $key) {
            $answer = $answers[$number];
            if ($answer->estado === 'blanco') {
                continue;
            }
            $score += self::cents((string) ($answer->estado === 'valida' && $answer->marcada === $key->respuesta_correcta ? $key->puntos_correcta : $key->puntos_incorrecta));
        }
        $formatted = ($score < 0 ? '-' : '').intdiv(abs($score), 100).'.'.str_pad((string) (abs($score) % 100), 2, '0', STR_PAD_LEFT);
        $applicant->update(['nota' => $formatted, 'estado' => $score >= self::cents($applicant->convocatoria->puntaje_minimo) ? 'Becario' : 'Calificado']);
    }
}
