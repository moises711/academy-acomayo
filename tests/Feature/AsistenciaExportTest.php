<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Aula;
use App\Models\Ciclo;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Profesor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AsistenciaExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_filter_attendance_by_date_range_and_download_csv(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $secretary = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'secretaria')->valueOrFail('id_rol'),
        ]);
        [$student, $schedule, $enrollment] = $this->createAttendanceContext();
        Asistencia::query()->create([
            'id_matricula' => $enrollment->id_matricula,
            'id_horario' => $schedule->id_horario,
            'fecha' => '2026-10-06',
            'estado' => 'Presente',
            'origen' => 'qr',
            'id_usuario_registro' => $secretary->id_usuario,
            'hora_registro' => '08:05:00',
        ]);
        Asistencia::query()->create([
            'id_matricula' => $enrollment->id_matricula,
            'id_horario' => $schedule->id_horario,
            'fecha' => '2026-10-09',
            'estado' => 'Falta',
            'origen' => 'manual',
            'id_usuario_registro' => $secretary->id_usuario,
        ]);

        $this->actingAs($secretary)->get(route('asistencias.index', ['desde' => '2026-10-05', 'hasta' => '2026-10-07']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('desde', '2026-10-05')
                ->where('hasta', '2026-10-07')
                ->where('stats.asistencias', 1)
                ->has('asistencias', 1));

        $response = $this->get(route('asistencias.exportar', ['desde' => '2026-10-05', 'hasta' => '2026-10-07']));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString($student->dni, $csv);
        $this->assertStringContainsString('Presente', $csv);
        $this->assertStringNotContainsString('Falta', $csv);
    }

    public function test_attendance_export_rejects_a_reversed_date_range(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $secretary = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'secretaria')->valueOrFail('id_rol'),
        ]);

        $this->actingAs($secretary)->from('/asistencias')->get(route('asistencias.exportar', [
            'desde' => '2026-10-08', 'hasta' => '2026-10-07',
        ]))->assertSessionHasErrors('hasta');
    }

    /** @return array{Estudiante, Horario, Matricula} */
    private function createAttendanceContext(): array
    {
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo de prueba', 'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31',
            'modalidad' => 'Presencial', 'estado' => 'En curso',
        ]);
        $group = Grupo::query()->create([
            'id_ciclo' => $cycle->id_ciclo, 'nombre' => 'Grupo A', 'turno' => 'Mañana',
        ]);
        $student = Estudiante::query()->create([
            'dni' => '70123456', 'nombre' => 'Ana', 'apellido' => 'Torres', 'estado' => 'activo',
        ]);
        $professor = Profesor::factory()->create();
        $course = Curso::query()->create(['nombre_curso' => 'Álgebra', 'categoria' => 'Ciencias']);
        $room = Aula::query()->create(['nombre' => 'Aula 01', 'tipo' => 'Presencial', 'capacidad' => 30]);
        $schedule = Horario::query()->create([
            'id_grupo' => $group->id_grupo, 'id_curso' => $course->id_curso,
            'id_profesor' => $professor->id_profesor, 'id_aula' => $room->id_aula,
            'dia_semana' => 2, 'hora_inicio' => '08:00:00', 'hora_fin' => '09:00:00',
        ]);
        $enrollment = Matricula::query()->create([
            'id_estudiante' => $student->id_estudiante, 'id_ciclo' => $cycle->id_ciclo,
            'id_grupo' => $group->id_grupo, 'fecha_matricula' => '2026-01-02',
            'estado_matricula' => 'inscrito',
        ]);

        return [$student, $schedule, $enrollment];
    }
}
