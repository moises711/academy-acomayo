<?php

namespace Tests\Feature;

use App\Models\Evaluacion;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotasManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_create_evaluation_for_assigned_group_and_save_grades(): void
    {
        $this->seed(DatabaseSeeder::class);
        $teacher = User::query()->where('email', 'profesor@academia.test')->firstOrFail();
        $schedule = Horario::query()->where('id_profesor', $teacher->id_profesor)->firstOrFail();
        $group = Matricula::query()->where('id_grupo', $schedule->id_grupo)->where('estado_matricula', 'inscrito')->firstOrFail();

        $this->actingAs($teacher)->post(route('evaluaciones.store'), [
            'id_grupo' => $schedule->id_grupo,
            'id_curso' => $schedule->id_curso,
            'titulo' => 'Control de álgebra',
            'tipo' => 'Práctica',
            'fecha' => now()->toDateString(),
            'nota_maxima' => 20,
            'peso' => 1,
        ])->assertRedirect();

        $evaluation = Evaluacion::query()->where('titulo', 'Control de álgebra')->firstOrFail();
        $this->actingAs($teacher)->put(route('evaluaciones.notas.store', $evaluation), [
            'notas' => [[
                'id_matricula' => $group->id_matricula,
                'nota' => 17.5,
                'observacion' => 'Buen avance',
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('notas', [
            'id_evaluacion' => $evaluation->id_evaluacion,
            'id_matricula' => $group->id_matricula,
            'nota' => '17.50',
            'id_usuario' => $teacher->id_usuario,
        ]);
        $this->assertDatabaseHas('auditoria', [
            'tabla' => 'notas',
            'id_usuario' => $teacher->id_usuario,
            'id_registro' => (string) \DB::table('notas')->where('id_evaluacion', $evaluation->id_evaluacion)->value('id_nota'),
        ]);
    }

    public function test_teacher_cannot_create_evaluation_for_an_unassigned_course_group_pair(): void
    {
        $this->seed(DatabaseSeeder::class);
        $teacher = User::query()->where('email', 'profesor@academia.test')->firstOrFail();
        $unassignedSchedule = Horario::query()->where('id_profesor', '<>', $teacher->id_profesor)->firstOrFail();

        $this->actingAs($teacher)->from('/notas')->post(route('evaluaciones.store'), [
            'id_grupo' => $unassignedSchedule->id_grupo,
            'id_curso' => $unassignedSchedule->id_curso,
            'titulo' => 'Evaluación ajena',
            'tipo' => 'Examen',
            'fecha' => now()->toDateString(),
            'nota_maxima' => 20,
            'peso' => 1,
        ])->assertSessionHasErrors('id_curso');

        $this->assertDatabaseMissing('evaluaciones', ['titulo' => 'Evaluación ajena']);
    }

    public function test_teacher_cannot_save_grades_for_another_teachers_group(): void
    {
        $this->seed(DatabaseSeeder::class);
        $teacher = User::query()->where('email', 'profesor@academia.test')->firstOrFail();
        $otherSchedule = Horario::query()->where('id_profesor', '<>', $teacher->id_profesor)->firstOrFail();
        $otherEnrollment = Matricula::query()->where('id_grupo', $otherSchedule->id_grupo)
            ->where('estado_matricula', 'inscrito')->firstOrFail();
        $evaluation = Evaluacion::query()->create([
            'id_grupo' => $otherSchedule->id_grupo,
            'id_curso' => $otherSchedule->id_curso,
            'titulo' => 'Evaluación de otro grupo',
            'tipo' => 'Examen',
            'fecha' => now()->toDateString(),
            'nota_maxima' => 20,
            'peso' => 1,
        ]);

        $this->actingAs($teacher)->put(route('evaluaciones.notas.store', $evaluation), [
            'notas' => [['id_matricula' => $otherEnrollment->id_matricula, 'nota' => 18]],
        ])->assertSessionHasErrors('id_curso');

        $this->assertDatabaseMissing('notas', [
            'id_evaluacion' => $evaluation->id_evaluacion,
            'id_matricula' => $otherEnrollment->id_matricula,
        ]);
    }

    public function test_cashier_cannot_access_grade_management(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cashier = User::query()->where('email', 'caja@academia.test')->firstOrFail();
        $this->actingAs($cashier)->get('/notas')->assertForbidden();
    }
}
