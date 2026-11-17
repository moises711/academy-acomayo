<?php

namespace Database\Seeders;

use App\Models\Ciclo;
use App\Models\ClaveRespuesta;
use App\Models\ConvocatoriaBeca;
use App\Models\Curso;
use App\Models\PostulanteBeca;
use App\Models\Simulacro;
use Illuminate\Database\Seeder;

class ConvocatoriaBecaSeeder extends Seeder
{
    public function run(): void
    {
        $cycle = Ciclo::query()->where('estado', 'En curso')->first();
        $course = Curso::query()->first();
        if (! $cycle || ! $course) {
            return;
        }
        $ids = [];
        foreach (['A–B' => 'A', 'C–D' => 'C'] as $pair => $answer) {
            $exam = Simulacro::query()->firstOrCreate(['titulo' => 'Examen de becas demo · '.$pair, 'id_ciclo' => $cycle->id_ciclo], ['fecha_aplicacion' => today('America/Lima')->addDays(10), 'puntaje_maximo' => '80.00']);
            foreach (range(1, 80) as $number) {
                ClaveRespuesta::query()->firstOrCreate(['id_simulacro' => $exam->id_simulacro, 'numero_pregunta' => $number], ['id_curso' => $course->id_curso, 'respuesta_correcta' => $answer, 'puntos_correcta' => '1.00', 'puntos_incorrecta' => '-0.25']);
            }
            $ids[] = $exam->id_simulacro;
        }
        $campaign = ConvocatoriaBeca::query()->firstOrCreate(['nombre' => 'Examen de becas de demostración'], ['descripcion' => 'Datos de demostración. Revisa fechas, claves y puntaje antes de publicar.', 'fecha_inicio' => today('America/Lima'), 'fecha_fin' => today('America/Lima')->addWeek(), 'examen_becas_activo' => false, 'fecha_examen' => null, 'hora_inicio_examen' => null, 'hora_fin_examen' => null, 'puntaje_minimo' => '60.00', 'estado' => 'Borrador', 'id_simulacro_ab' => $ids[0], 'id_simulacro_cd' => $ids[1]]);
        foreach (['A', 'B', 'C', 'D'] as $index => $group) {
            PostulanteBeca::query()->firstOrCreate(['id_convocatoria' => $campaign->id_convocatoria, 'dni' => '8000000'.($index + 1)], ['telefono' => '99000000'.($index + 1), 'grupo' => $group, 'estado' => 'Inscrito']);
        }
    }
}
