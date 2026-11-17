<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\Matricula;
use App\Models\PagoDocente;
use App\Models\Profesor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

class CicloHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_history_permission_allows_admin_history_and_all_routes_reject_cashier(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->user('admin');
        $cashier = $this->user('caja');
        $cycle = $this->cycle('Anterior');
        $payment = PagoDocente::factory()->create(['id_ciclo' => null]);

        $this->assertTrue(Gate::forUser($admin)->allows('manageHistory', Ciclo::class));
        $this->assertFalse(Gate::forUser($cashier)->allows('manageHistory', Ciclo::class));
        $this->actingAs($admin)->get(route('historial-ciclos.index'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Ciclos/Historial')->where('totalSinCiclo', 1));
        $this->actingAs($cashier)->get(route('historial-ciclos.index'))->assertForbidden();
        $this->post(route('historial-ciclos.export', $cycle))->assertForbidden();
        $this->delete(route('historial-ciclos.destroy', $cycle))->assertForbidden();
        $this->patch(route('historial-ciclos.assign', $payment), ['id_ciclo' => $cycle->id_ciclo])->assertForbidden();
        $this->assertModelExists($cycle);
        $this->assertNull($payment->fresh()->id_ciclo);
    }

    public function test_admin_can_assign_a_legacy_teacher_payment_and_assignment_is_audited(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->user('admin');
        $cycle = $this->cycle('Ciclo anterior');
        $payment = PagoDocente::factory()->create(['id_ciclo' => null]);
        $this->actingAs($admin)->postJson(route('historial-ciclos.export', $cycle))->assertUnprocessable()->assertJsonValidationErrors('pagos_docentes');

        $this->patch(route('historial-ciclos.assign', $payment), ['id_ciclo' => $cycle->id_ciclo])->assertSessionHasNoErrors();

        $this->assertSame($cycle->id_ciclo, $payment->fresh()->id_ciclo);
        $this->assertDatabaseHas('auditoria', ['accion' => 'editar', 'tabla' => 'pagos_docentes', 'id_registro' => (string) $payment->id_pago_docente]);
        $this->patch(route('historial-ciclos.assign', $payment), ['id_ciclo' => $cycle->id_ciclo])->assertSessionHasErrors('id_ciclo');
    }

    public function test_closed_cycle_export_and_deletion_include_teacher_payments_and_preserve_other_cycle(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 9));
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@academia.test')->firstOrFail();
        $past = Ciclo::where('estado', 'Cerrado')->firstOrFail();
        $current = Ciclo::where('estado', 'En curso')->firstOrFail();
        $teacher = Profesor::firstOrFail();
        $pastTeacherPayment = PagoDocente::factory()->create(['id_ciclo' => $past->id_ciclo, 'id_profesor' => $teacher->id_profesor, 'id_usuario' => $admin->id_usuario]);
        $otherTeacherPayment = PagoDocente::where('id_ciclo', $current->id_ciclo)->firstOrFail();
        $pastEnrollment = Matricula::where('id_ciclo', $past->id_ciclo)->firstOrFail();
        $student = $pastEnrollment->estudiante;
        $otherEnrollment = Matricula::where('id_ciclo', $current->id_ciclo)->firstOrFail();
        $simulacro = DB::table('simulacros')->insertGetId(['id_ciclo' => $past->id_ciclo, 'titulo' => 'Simulacro anterior', 'fecha_aplicacion' => '2026-09-01', 'puntaje_maximo' => '100.00'], 'id_simulacro');
        $template = DB::table('plantillas_hoja')->insertGetId(['nombre' => 'Hoja del ciclo anterior', 'num_preguntas' => 1, 'opciones_por_pregunta' => 5, 'config' => json_encode(['simulacro_id' => $simulacro]), 'activa' => true], 'id_plantilla');
        Storage::disk('local')->put('omr/uploads/prueba.png', 'imagen de prueba');
        $sheet = DB::table('hojas_respuesta')->insertGetId(['id_simulacro' => $simulacro, 'id_matricula' => $pastEnrollment->id_matricula, 'id_plantilla' => $template, 'codigo_hoja' => 'HOJA-PRUEBA', 'archivo_path' => 'omr/uploads/prueba.png', 'estado' => 'revision'], 'id_hoja');
        DB::table('respuestas_hoja')->insert(['id_hoja' => $sheet, 'numero_pregunta' => 1, 'marcada' => 'A', 'estado' => 'valida']);
        $this->actingAs($admin);

        $token = $this->download($past, function (ZipArchive $zip) use ($pastTeacherPayment, $otherTeacherPayment): void {
            $payments = json_decode($zip->getFromName('registros/pagos_docentes.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame([$pastTeacherPayment->id_pago_docente], array_column($payments, 'id_pago_docente'));
            $this->assertNotContains($otherTeacherPayment->id_pago_docente, array_column($payments, 'id_pago_docente'));
            $this->assertSame('imagen de prueba', $zip->getFromName('archivos/omr/uploads/prueba.png'));
        });
        $this->delete(route('historial-ciclos.destroy', $past), $this->confirmation($past, $token))->assertRedirect(route('historial-ciclos.index'))->assertSessionHasNoErrors();

        $this->assertModelMissing($past);
        $this->assertModelMissing($pastEnrollment);
        $this->assertModelMissing($pastTeacherPayment);
        $this->assertModelExists($current);
        $this->assertModelExists($otherTeacherPayment);
        $this->assertModelExists($otherEnrollment);
        $this->assertModelExists($student);
        $this->assertModelExists($teacher);
        $this->assertModelExists($admin);
        $this->assertDatabaseMissing('plantillas_hoja', ['id_plantilla' => $template]);
        Storage::disk('local')->assertMissing('omr/uploads/prueba.png');
        $this->assertDatabaseHas('auditoria', ['accion' => 'eliminar', 'tabla' => 'pagos_docentes', 'id_registro' => (string) $pastTeacherPayment->id_pago_docente]);
        $this->assertDatabaseHas('auditoria', ['accion' => 'exportar', 'tabla' => 'ciclos', 'id_registro' => (string) $past->id_ciclo]);
    }

    public function test_deletion_requires_a_download_and_exact_name_and_verified_backup(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cycle = $this->cycle('Ciclo anterior');
        $this->actingAs($this->user('admin'));
        $this->delete(route('historial-ciclos.destroy', $cycle), $this->confirmation($cycle, (string) Str::uuid()))->assertSessionHasErrors('respaldo');
        $token = $this->download($cycle);

        $this->delete(route('historial-ciclos.destroy', $cycle), [...$this->confirmation($cycle, $token), 'nombre_ciclo' => 'Otro ciclo', 'respaldo_verificado' => false])->assertSessionHasErrors(['nombre_ciclo', 'respaldo_verificado']);

        $this->assertModelExists($cycle);
    }

    public function test_changed_cycle_records_expired_backup_and_active_cycle_cannot_be_deleted(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cycle = $this->cycle('Ciclo anterior');
        $this->actingAs($this->user('admin'));
        $token = $this->download($cycle);
        $cycle->update(['nombre_ciclo' => 'Ciclo anterior editado']);
        $this->delete(route('historial-ciclos.destroy', $cycle), $this->confirmation($cycle, $token))->assertSessionHasErrors('respaldo');
        $token = $this->download($cycle);
        $this->travel(61)->minutes();
        $this->delete(route('historial-ciclos.destroy', $cycle), $this->confirmation($cycle, $token))->assertSessionHasErrors('respaldo');
        $this->travelBack();
        $cycle->update(['estado' => 'En curso']);

        $this->postJson(route('historial-ciclos.export', $cycle))->assertUnprocessable()->assertJsonValidationErrors('ciclo');
        $this->assertModelExists($cycle);
    }

    public function test_missing_omr_file_blocks_the_backup_without_deleting_records(): void
    {
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);
        $cycle = $this->cycle('Ciclo anterior');
        $simulacro = DB::table('simulacros')->insertGetId(['id_ciclo' => $cycle->id_ciclo, 'titulo' => 'Simulacro', 'fecha_aplicacion' => '2025-06-01', 'puntaje_maximo' => '100.00'], 'id_simulacro');
        $template = DB::table('plantillas_hoja')->insertGetId(['nombre' => 'Hoja', 'num_preguntas' => 1, 'config' => '{}', 'activa' => true], 'id_plantilla');
        DB::table('hojas_respuesta')->insert(['id_simulacro' => $simulacro, 'id_plantilla' => $template, 'codigo_hoja' => 'FALTANTE', 'archivo_path' => 'omr/uploads/faltante.png', 'estado' => 'revision']);

        $this->actingAs($this->user('admin'))->postJson(route('historial-ciclos.export', $cycle))->assertUnprocessable()->assertJsonValidationErrors('respaldo');

        $this->assertModelExists($cycle);
        $this->assertDatabaseCount('hojas_respuesta', 1);
    }

    private function confirmation(Ciclo $cycle, string $token): array
    {
        return ['nombre_ciclo' => $cycle->nombre_ciclo, 'respaldo_verificado' => true, 'token_respaldo' => $token];
    }

    private function download(Ciclo $cycle, ?callable $inspect = null): string
    {
        $response = $this->post(route('historial-ciclos.export', $cycle));
        $response->assertOk()->assertHeader('Content-Type', 'application/zip');
        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        try {
            $this->assertTrue($zip->open($path));
            if ($inspect) {
                $inspect($zip);
            }
        } finally {
            $zip->close();
            unlink($path);
        }

        return $response->headers->get('X-Respaldo-Token');
    }

    private function user(string $role): User
    {
        return User::factory()->create(['id_rol' => Role::where('nombre', $role)->value('id_rol')]);
    }

    private function cycle(string $name): Ciclo
    {
        return Ciclo::create(['nombre_ciclo' => $name, 'tipo_ciclo' => 'Semestral', 'fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-06-30', 'modalidad' => 'Presencial', 'estado' => 'Cerrado']);
    }
}
