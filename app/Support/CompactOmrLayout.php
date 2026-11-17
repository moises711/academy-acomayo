<?php

namespace App\Support;

use Illuminate\Support\Collection;

class CompactOmrLayout
{
    public function build(Collection $keys, int $options): array
    {
        $questions = $keys->values()->map(function ($key, $index) use ($options): array {
            $left = 380 + intdiv($index, 20) * 200;
            $y = 200 + ($index % 20) * 32;

            return ['numero_pregunta' => (int) $key->numero_pregunta, 'id_curso' => (int) $key->id_curso, 'label_x' => $left + 12, 'y' => $y,
                'opciones' => collect(range(0, $options - 1))->map(fn ($option) => ['label' => chr(65 + $option), 'x' => $left + 55 + ($option * ($options === 4 ? 38 : 30)), 'y' => $y])->all()];
        })->all();

        return ['version' => 2, 'diseno' => 'compacto', 'columnas' => 4, 'page' => ['width' => 1200, 'height' => 900],
            'alignment_marks' => [[6, 6], [1182, 6], [6, 882], [1182, 882]], 'alignment_size' => 12, 'bubble_radius' => 12,
            'dni' => ['columnas' => 8, 'digitos' => range(0, 9), 'x' => 41.25, 'y' => 210, 'paso_x' => 42.5, 'paso_y' => 43, 'radio' => 12],
            'grupo' => ['x' => 140, 'y' => 804, 'paso_x' => 50, 'radio' => 15],
            'questions' => $questions, 'sections' => [], 'qr' => null];
    }
}
