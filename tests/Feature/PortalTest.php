<?php

namespace Tests\Feature;

use App\Models\Apoderado;
use App\Models\Ciclo;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_own_dashboard_and_cannot_select_another_student(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '71234567', 'nombre' => 'Ana', 'apellido' => 'Torres', 'estado' => 'activo',
        ]);
        $otherStudent = Estudiante::query()->create([
            'dni' => '72345678', 'nombre' => 'Luis', 'apellido' => 'Rojas', 'estado' => 'activo',
        ]);
        $user = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'estudiante')->firstOrFail()->id_rol,
            'id_estudiante' => $student->id_estudiante,
        ]);

        $this->actingAs($user)->get('/mi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Mi/Dashboard')
                ->where('estudiante.id_estudiante', $student->id_estudiante)
                ->where('estudiante.codigo_qr', $student->codigo_qr));

        $this->actingAs($user)->get('/mi?id_estudiante='.$otherStudent->id_estudiante)->assertNotFound();
    }

    public function test_guardian_can_select_children_linked_by_their_email_only(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $firstStudent = Estudiante::query()->create([
            'dni' => '73456789', 'nombre' => 'María', 'apellido' => 'Pérez', 'estado' => 'activo',
        ]);
        $secondStudent = Estudiante::query()->create([
            'dni' => '74567890', 'nombre' => 'José', 'apellido' => 'Pérez', 'estado' => 'activo',
        ]);
        $unrelatedStudent = Estudiante::query()->create([
            'dni' => '75678901', 'nombre' => 'Carmen', 'apellido' => 'Díaz', 'estado' => 'activo',
        ]);
        $guardian = Apoderado::query()->create([
            'id_estudiante' => $firstStudent->id_estudiante,
            'nombre' => 'Rosa', 'apellido' => 'Pérez', 'parentesco' => 'Madre',
            'email' => 'rosa@example.test', 'telefono' => '987654321',
        ]);
        Apoderado::query()->create([
            'id_estudiante' => $secondStudent->id_estudiante,
            'nombre' => 'Rosa', 'apellido' => 'Pérez', 'parentesco' => 'Madre',
            'email' => 'rosa@example.test', 'telefono' => '987654321',
        ]);
        $user = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'apoderado')->firstOrFail()->id_rol,
            'id_apoderado' => $guardian->id_apoderado,
        ]);

        $this->actingAs($user)->get('/mi?id_estudiante='.$secondStudent->id_estudiante)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('esApoderado', true)
                ->where('estudiante.id_estudiante', $secondStudent->id_estudiante)
                ->has('estudiantes', 2));

        $this->actingAs($user)->get('/mi?id_estudiante='.$unrelatedStudent->id_estudiante)->assertNotFound();
    }

    public function test_student_can_open_only_their_own_valid_payment_receipt(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '76789012', 'nombre' => 'Elena', 'apellido' => 'Vargas', 'estado' => 'activo',
        ]);
        $otherStudent = Estudiante::query()->create([
            'dni' => '77890123', 'nombre' => 'Bruno', 'apellido' => 'Castillo', 'estado' => 'activo',
        ]);
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo 2026', 'tipo_ciclo' => 'Anual', 'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31', 'modalidad' => 'Presencial', 'estado' => 'En curso',
        ]);
        $enrollment = Matricula::query()->create([
            'id_estudiante' => $student->id_estudiante, 'id_ciclo' => $cycle->id_ciclo,
            'fecha_matricula' => '2026-01-02', 'estado_matricula' => 'inscrito',
        ]);
        $otherEnrollment = Matricula::query()->create([
            'id_estudiante' => $otherStudent->id_estudiante, 'id_ciclo' => $cycle->id_ciclo,
            'fecha_matricula' => '2026-01-02', 'estado_matricula' => 'inscrito',
        ]);
        $cashier = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->firstOrFail()->id_rol,
        ]);
        $ownPayment = Pago::query()->create([
            'id_matricula' => $enrollment->id_matricula, 'id_usuario' => $cashier->id_usuario,
            'fecha_pago' => now(), 'monto' => 100, 'metodo_pago' => 'Efectivo',
            'tipo_comprobante' => 'Recibo', 'nro_comprobante' => 'REC-OWN-01', 'estado' => 'valido',
        ]);
        $otherPayment = Pago::query()->create([
            'id_matricula' => $otherEnrollment->id_matricula, 'id_usuario' => $cashier->id_usuario,
            'fecha_pago' => now(), 'monto' => 100, 'metodo_pago' => 'Efectivo',
            'tipo_comprobante' => 'Recibo', 'nro_comprobante' => 'REC-OTHER-01', 'estado' => 'valido',
        ]);
        $user = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'estudiante')->firstOrFail()->id_rol,
            'id_estudiante' => $student->id_estudiante,
        ]);

        $this->actingAs($user)->get(route('mi.pagos.comprobante', $ownPayment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pagos/Comprobante')
                ->where('portal', true)
                ->where('pago.id_pago', $ownPayment->id_pago));

        $this->actingAs($user)->get(route('mi.pagos.comprobante', $otherPayment))->assertNotFound();
    }
}
