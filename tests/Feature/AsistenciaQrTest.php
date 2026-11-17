<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Aula;
use App\Models\Ciclo;
use App\Models\Configuracion;
use App\Models\Cuota;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Matricula;
use App\Models\Profesor;
use App\Models\RegistroIngreso;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AsistenciaQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_scan_registers_entry_and_today_classes(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();

        $this->actingAs($fixtures['user'])
            ->from('/asistencias')
            ->post('/asistencias', ['codigo_qr' => $fixtures['student']->codigo_qr])
            ->assertRedirect('/asistencias')
            ->assertSessionHas('success', 'Ingreso registrado para Ana Torres.');

        $this->assertDatabaseHas('registros_ingreso', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'id_usuario' => $fixtures['user']->id_usuario,
            'metodo' => 'qr',
        ]);
        $this->assertDatabaseHas('asistencias', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'id_horario' => $fixtures['schedule']->id_horario,
            'estado' => 'Presente',
            'origen' => 'qr',
        ]);
        $this->assertDatabaseHas('auditoria', [
            'accion' => 'crear',
            'tabla' => 'asistencias',
            'id_registro' => (string) Asistencia::query()->firstOrFail()->id_asistencia,
        ]);
        $this->assertDatabaseMissing('asistencias', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'id_horario' => $fixtures['futureSchedule']->id_horario,
            'fecha' => today()->toDateString(),
        ]);
    }

    public function test_second_scan_today_reports_duplicate_without_creating_another_entry(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();
        $url = '/asistencias';

        $this->actingAs($fixtures['user'])->from($url)->post($url, ['codigo_qr' => $fixtures['student']->codigo_qr]);
        $this->actingAs($fixtures['user'])
            ->from($url)
            ->post($url, ['codigo_qr' => $fixtures['student']->codigo_qr])
            ->assertRedirect($url)
            ->assertSessionHas('error', 'Ana Torres ya registró su ingreso hoy.');

        $this->assertSame(1, RegistroIngreso::query()->count());
    }

    public function test_invalid_qr_and_inactive_enrollment_are_rejected_and_student_cannot_scan(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();

        $this->actingAs($fixtures['user'])
            ->from('/asistencias')
            ->post('/asistencias', ['codigo_qr' => '00000000-0000-4000-8000-000000000000'])
            ->assertSessionHasErrors('codigo_qr');

        $fixtures['enrollment']->update(['estado_matricula' => 'congelado']);
        $this->actingAs($fixtures['user'])
            ->from('/asistencias')
            ->post('/asistencias', ['codigo_qr' => $fixtures['student']->codigo_qr])
            ->assertSessionHasErrors('codigo_qr');

        $studentRole = Role::query()->where('nombre', 'estudiante')->firstOrFail();
        $studentUser = User::factory()->create(['id_rol' => $studentRole->id_rol]);
        $this->actingAs($studentUser)->get('/asistencias')->assertForbidden();
        $attendance = Asistencia::query()->create([
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'id_horario' => $fixtures['schedule']->id_horario,
            'fecha' => today()->toDateString(),
            'estado' => 'Presente',
            'origen' => 'manual',
        ]);
        $this->actingAs($studentUser)
            ->patch("/asistencias/{$attendance->id_asistencia}", ['estado' => 'Falta', 'motivo' => 'Sin autorización'])
            ->assertForbidden();
    }

    public function test_daily_closing_marks_missing_students_once(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();

        $this->artisan('academia:cerrar-asistencias-diarias')
            ->expectsOutput('Se registraron 2 faltas.')
            ->assertSuccessful();

        $this->assertDatabaseHas('asistencias', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'id_horario' => $fixtures['schedule']->id_horario,
            'estado' => 'Falta',
        ]);

        $this->artisan('academia:cerrar-asistencias-diarias')
            ->expectsOutput('Se registraron 0 faltas.')
            ->assertSuccessful();

        $this->assertSame(2, Asistencia::query()->count());
    }

    public function test_manual_correction_requires_reason_and_is_audited(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();
        $this->actingAs($fixtures['user'])->post('/asistencias', ['codigo_qr' => $fixtures['student']->codigo_qr]);
        $attendance = Asistencia::query()->firstOrFail();

        $this->actingAs($fixtures['user'])
            ->from('/asistencias')
            ->patch("/asistencias/{$attendance->id_asistencia}", ['estado' => 'Justificada', 'motivo' => 'Presentó constancia médica'])
            ->assertRedirect('/asistencias')
            ->assertSessionHas('success', 'Asistencia corregida y registrada en auditoría.');

        $this->assertDatabaseHas('asistencias', [
            'id_asistencia' => $attendance->id_asistencia,
            'estado' => 'Justificada',
            'origen' => 'manual',
            'id_usuario_registro' => $fixtures['user']->id_usuario,
            'id_registro_ingreso' => null,
        ]);
        $this->assertDatabaseHas('auditoria', [
            'id_usuario' => $fixtures['user']->id_usuario,
            'accion' => 'editar',
            'tabla' => 'asistencias',
            'id_registro' => (string) $attendance->id_asistencia,
        ]);
    }

    public function test_overdue_fee_warns_when_ingress_is_not_configured_to_block(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();
        Cuota::query()->create([
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'concepto' => 'Cuota vencida',
            'monto_original' => 80,
            'monto_descuento' => 0,
            'monto_final' => 80,
            'fecha_vencimiento' => today()->subDay()->toDateString(),
            'estado_pago' => 'Vencido',
        ]);
        Configuracion::query()->create([
            'clave' => 'asistencia.bloquear_si_deuda', 'valor' => '0', 'descripcion' => 'Registra y muestra aviso',
        ]);

        $this->actingAs($fixtures['user'])
            ->from('/asistencias')
            ->post('/asistencias', ['codigo_qr' => $fixtures['student']->codigo_qr])
            ->assertSessionHas('warning');
        $this->assertSame(1, RegistroIngreso::query()->count());
    }

    public function test_overdue_fee_blocks_ingress_when_configured(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();
        Cuota::query()->create([
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'concepto' => 'Cuota vencida',
            'monto_original' => 80,
            'monto_descuento' => 0,
            'monto_final' => 80,
            'fecha_vencimiento' => today()->subDay()->toDateString(),
            'estado_pago' => 'Vencido',
        ]);
        Configuracion::query()->create([
            'clave' => 'asistencia.bloquear_si_deuda', 'valor' => '1', 'descripcion' => 'Bloquea el ingreso',
        ]);

        $this->actingAs($fixtures['user'])
            ->from('/asistencias')
            ->post('/asistencias', ['codigo_qr' => $fixtures['student']->codigo_qr])
            ->assertSessionHas('error', 'Ingreso bloqueado: Ana Torres tiene cuotas vencidas.');

        $this->assertSame(0, RegistroIngreso::query()->count());
    }

    public function test_arriving_after_tolerance_marks_class_as_late(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();
        Carbon::setTestNow('2026-10-06 10:20:00');

        $this->actingAs($fixtures['user'])->post('/asistencias', ['codigo_qr' => $fixtures['student']->codigo_qr]);

        $this->assertDatabaseHas('asistencias', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'id_horario' => $fixtures['schedule']->id_horario,
            'estado' => 'Tarde',
        ]);
    }

    public function test_attendance_sheet_includes_enrolled_students_without_marks_and_hides_qr(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();

        $this->actingAs($fixtures['user'])->get('/asistencias')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('desde', '2026-10-01')
                ->where('hasta', '2026-10-06')
                ->where('canScan', true)
                ->has('padron', 1)
                ->where('padron.0.id_matricula', $fixtures['enrollment']->id_matricula)
                ->where('padron.0.estudiante.nombre', 'Ana')
                ->missing('padron.0.estudiante.codigo_qr')
                ->has('asistencias', 0)
                ->has('ingresosPorDia', 0));
    }

    public function test_daily_entries_include_more_than_the_old_hundred_record_limit(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $fixtures = $this->createAttendanceFixtures();
        for ($minute = 0; $minute <= 100; $minute++) {
            RegistroIngreso::query()->create([
                'id_matricula' => $fixtures['enrollment']->id_matricula,
                'id_usuario' => $fixtures['user']->id_usuario,
                'tipo' => 'ingreso',
                'metodo' => 'qr',
                'fecha_hora' => now()->subDay()->addMinutes($minute),
            ]);
        }
        RegistroIngreso::query()->create([
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'id_usuario' => $fixtures['user']->id_usuario,
            'tipo' => 'ingreso',
            'metodo' => 'qr',
            'fecha_hora' => now()->subDays(2),
        ]);

        $this->actingAs($fixtures['user'])->get('/asistencias?desde=2026-10-04&hasta=2026-10-05')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('ingresosPorDia', 2)
                ->where('canScan', false));
    }

    /**
     * @return array{user: User, student: Estudiante, enrollment: Matricula, schedule: Horario, futureSchedule: Horario}
     */
    private function createAttendanceFixtures(): array
    {
        Carbon::setTestNow('2026-10-06 10:00:00');
        $secretaryRole = Role::query()->where('nombre', 'secretaria')->firstOrFail();
        $secretary = User::factory()->create(['id_rol' => $secretaryRole->id_rol]);
        $today = today()->toDateString();
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo de prueba',
            'tipo_ciclo' => 'Anual',
            'fecha_inicio' => today()->subWeek()->toDateString(),
            'fecha_fin' => today()->addMonth()->toDateString(),
            'modalidad' => 'Presencial',
            'estado' => 'En curso',
        ]);
        $student = Estudiante::query()->create([
            'dni' => '12345678',
            'nombre' => 'Ana',
            'apellido' => 'Torres',
            'estado' => 'activo',
        ]);
        $group = Grupo::query()->create([
            'id_ciclo' => $cycle->id_ciclo,
            'nombre' => 'Grupo A',
            'turno' => 'Mañana',
        ]);
        $enrollment = Matricula::query()->create([
            'id_estudiante' => $student->id_estudiante,
            'id_ciclo' => $cycle->id_ciclo,
            'id_grupo' => $group->id_grupo,
            'fecha_matricula' => $today,
            'estado_matricula' => 'inscrito',
        ]);
        $course = Curso::query()->create(['nombre_curso' => 'Matemática', 'categoria' => 'Ciencias']);
        $teacher = Profesor::query()->create([
            'dni' => '87654321', 'nombre' => 'Luis', 'apellido' => 'Rojas', 'estado' => 'activo',
        ]);
        $classroom = Aula::query()->create(['nombre' => 'Aula 1', 'tipo' => 'Presencial', 'capacidad' => 30]);
        $schedule = Horario::query()->create([
            'id_grupo' => $group->id_grupo,
            'id_curso' => $course->id_curso,
            'id_profesor' => $teacher->id_profesor,
            'id_aula' => $classroom->id_aula,
            'dia_semana' => (int) today()->dayOfWeekIso,
            'hora_inicio' => '09:55',
            'hora_fin' => '11:00',
        ]);
        $futureSchedule = Horario::query()->create([
            'id_grupo' => $group->id_grupo,
            'id_curso' => $course->id_curso,
            'id_profesor' => $teacher->id_profesor,
            'id_aula' => $classroom->id_aula,
            'dia_semana' => (int) today()->dayOfWeekIso,
            'hora_inicio' => '12:00',
            'hora_fin' => '13:00',
        ]);

        return [
            'user' => $secretary,
            'student' => $student,
            'enrollment' => $enrollment,
            'schedule' => $schedule,
            'futureSchedule' => $futureSchedule,
        ];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
