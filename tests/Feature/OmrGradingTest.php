<?php

namespace Tests\Feature;

use App\Models\AreaAcademica;
use App\Models\Carrera;
use App\Models\Ciclo;
use App\Models\ClaveRespuesta;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\HojaRespuesta;
use App\Models\Matricula;
use App\Models\PlantillaHoja;
use App\Models\Simulacro;
use App\Models\Universidad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OmrGradingTest extends TestCase
{
    use RefreshDatabase;

    public function test_omr_answers_create_course_scores_and_general_and_career_rankings(): void
    {
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo de preparación', 'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31',
            'modalidad' => 'Presencial', 'estado' => 'En curso',
        ]);
        $course = Curso::query()->create(['nombre_curso' => 'Aritmética', 'categoria' => 'Ciencias']);
        $university = Universidad::query()->create(['nombre' => 'Universidad Demo', 'siglas' => 'UD', 'tipo' => 'Pública']);
        $area = AreaAcademica::query()->create(['nombre' => 'Ingeniería']);
        $career = Carrera::query()->create([
            'id_universidad' => $university->id_universidad, 'id_area' => $area->id_area,
            'nombre' => 'Ingeniería Civil', 'puntaje_minimo' => 1200,
        ]);
        $exam = Simulacro::query()->create([
            'titulo' => 'Simulacro de clasificación', 'fecha_aplicacion' => '2026-10-06',
            'id_ciclo' => $cycle->id_ciclo, 'puntaje_maximo' => 3,
        ]);
        $template = PlantillaHoja::query()->create([
            'nombre' => 'Plantilla de prueba', 'num_preguntas' => 3, 'opciones_por_pregunta' => 5,
            'config' => ['questions' => []], 'activa' => true,
        ]);

        foreach ([['A', 1], ['B', 2], ['C', 3]] as [$answer, $question]) {
            ClaveRespuesta::query()->create([
                'id_simulacro' => $exam->id_simulacro, 'id_curso' => $course->id_curso,
                'numero_pregunta' => $question, 'respuesta_correcta' => $answer,
                'puntos_correcta' => 1, 'puntos_incorrecta' => '-0.25',
            ]);
        }

        $results = [];
        foreach ([['A', 'B', 'C'], ['A', 'B', 'A'], ['A', 'B', 'A']] as $index => $answers) {
            $student = Estudiante::query()->create([
                'dni' => '78'.str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
                'nombre' => 'Estudiante', 'apellido' => 'Demo '.$index, 'estado' => 'activo',
            ]);
            $enrollment = Matricula::query()->create([
                'id_estudiante' => $student->id_estudiante, 'id_ciclo' => $cycle->id_ciclo,
                'id_carrera_objetivo' => $career->id_carrera,
                'fecha_matricula' => '2026-01-02', 'estado_matricula' => 'inscrito',
            ]);
            $sheet = HojaRespuesta::query()->create([
                'id_simulacro' => $exam->id_simulacro, 'id_matricula' => $enrollment->id_matricula,
                'id_plantilla' => $template->id_plantilla, 'codigo_hoja' => 'OMR-GRADE-'.$index,
                'estado' => 'procesada',
            ]);
            foreach ($answers as $questionIndex => $marked) {
                DB::table('respuestas_hoja')->insert([
                    'id_hoja' => $sheet->id_hoja,
                    'numero_pregunta' => $questionIndex + 1,
                    'marcada' => $marked,
                    'estado' => 'valida',
                ]);
            }

            $results[] = $exam->calificarHoja($sheet);
        }

        $this->assertSame('3.00', $results[0]->puntaje_obtenido);
        $this->assertSame(1, $results[0]->puesto_general);
        $this->assertSame(1, $results[0]->puesto_carrera);
        $this->assertSame('1.75', $results[1]->puntaje_obtenido);
        $this->assertSame(2, $results[1]->puesto_general);
        $this->assertSame(2, $results[1]->puesto_carrera);
        $this->assertSame(2, $results[2]->puesto_general);
        $this->assertSame(2, $results[2]->puesto_carrera);
        $this->assertDatabaseHas('resultados_curso', [
            'id_resultado' => $results[1]->id_resultado,
            'id_curso' => $course->id_curso,
            'correctas' => 2,
            'incorrectas' => 1,
            'en_blanco' => 0,
            'puntaje' => '1.75',
        ]);
        $this->assertDatabaseHas('resultados_simulacros', [
            'id_resultado' => $results[0]->id_resultado,
            'fecha_rendicion' => '2026-10-06',
            'estado_ranking' => 'procesado',
        ]);
    }
}
