<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\PagoDocente;
use App\Models\Profesor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PagoDocenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_teacher_payments(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);

        $this->actingAs($admin)->get(route('pagos-docentes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PagosDocentes/Index')
                ->has('docentes', 0)
                ->has('pagos.data', 0));
    }

    public function test_cashier_can_record_and_annul_a_teacher_payment_with_audit(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $professor = Profesor::factory()->create();
        $cashier = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'caja')->valueOrFail('id_rol'),
        ]);

        $this->actingAs($cashier)->post(route('pagos-docentes.store'), [
            'id_ciclo' => Ciclo::create(['nombre_ciclo' => 'Ciclo Anual 2026', 'tipo_ciclo' => 'Anual', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31', 'modalidad' => 'Presencial', 'estado' => 'En curso'])->id_ciclo,
            'id_profesor' => $professor->id_profesor,
            'monto' => '875.50',
            'fecha_pago' => '2026-10-08',
            'periodo' => '2026-10',
            'metodo_pago' => 'Transferencia',
            'nro_comprobante' => 'HON-2026-123',
        ])->assertRedirect(route('pagos-docentes.index', ['id_profesor' => $professor->id_profesor]));

        $payment = PagoDocente::query()->firstOrFail();
        $this->assertSame('875.50', $payment->monto);
        $this->assertSame('valido', $payment->estado);
        $this->assertSame($cashier->id_usuario, $payment->id_usuario);
        $this->assertDatabaseHas('auditoria', [
            'id_usuario' => $cashier->id_usuario,
            'tabla' => 'pagos_docentes',
            'id_registro' => (string) $payment->id_pago_docente,
            'accion' => 'crear',
        ]);

        $this->post(route('pagos-docentes.anular', $payment), ['motivo_anulacion' => 'Comprobante duplicado'])
            ->assertSessionHasNoErrors();

        $this->assertSame('anulado', $payment->fresh()->estado);
        $this->assertSame('Comprobante duplicado', $payment->fresh()->motivo_anulacion);
        $this->assertSame($cashier->id_usuario, $payment->fresh()->anulado_por);
        $this->assertDatabaseHas('auditoria', [
            'id_usuario' => $cashier->id_usuario,
            'tabla' => 'pagos_docentes',
            'id_registro' => (string) $payment->id_pago_docente,
            'accion' => 'anular',
        ]);
    }

    public function test_user_without_teacher_payment_permission_cannot_view_or_create_payments(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $professor = Profesor::factory()->create();
        $secretary = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'secretaria')->valueOrFail('id_rol'),
        ]);

        $this->actingAs($secretary)->get(route('pagos-docentes.index'))->assertForbidden();
        $this->post(route('pagos-docentes.store'), [
            'id_ciclo' => Ciclo::create(['nombre_ciclo' => 'Ciclo Anual 2026', 'tipo_ciclo' => 'Anual', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31', 'modalidad' => 'Presencial', 'estado' => 'En curso'])->id_ciclo,
            'id_profesor' => $professor->id_profesor,
            'monto' => '100.00',
            'fecha_pago' => '2026-10-08',
            'periodo' => '2026-10',
            'metodo_pago' => 'Efectivo',
        ])->assertForbidden();

        $this->assertDatabaseCount('pagos_docentes', 0);
    }
}
