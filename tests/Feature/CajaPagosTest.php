<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\Cuota;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Pago;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaPagosTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_collect_a_partial_payment_and_get_a_unique_receipt(): void
    {
        $fixtures = $this->createCashierFixtures();

        $this->actingAs($fixtures['cashier'])
            ->from('/pagos')
            ->post('/pagos', [
                'id_matricula' => $fixtures['enrollment']->id_matricula,
                'monto' => '40.00',
                'metodo_pago' => 'Yape/Plin',
                'tipo_comprobante' => 'Recibo',
                'aplicaciones' => [['id_cuota' => $fixtures['firstFee']->id_cuota, 'monto_aplicado' => '40.00']],
            ])
            ->assertRedirect('/pagos')
            ->assertSessionHas('success');

        $payment = Pago::query()->firstOrFail();
        $this->assertSame('40.00', $payment->monto);
        $this->assertNotEmpty($payment->nro_comprobante);
        $this->assertDatabaseHas('pagos_cuotas', [
            'id_pago' => $payment->id_pago,
            'id_cuota' => $fixtures['firstFee']->id_cuota,
            'monto_aplicado' => '40.00',
        ]);
        $this->assertDatabaseHas('cuotas', ['id_cuota' => $fixtures['firstFee']->id_cuota, 'estado_pago' => 'Parcial']);
        $this->assertDatabaseHas('auditoria', ['tabla' => 'pagos', 'id_registro' => (string) $payment->id_pago]);
        $this->actingAs($fixtures['cashier'])->get("/pagos/{$payment->id_pago}/comprobante")->assertOk();
    }

    public function test_cashier_can_create_a_fee_with_discount_and_audit_it(): void
    {
        $fixtures = $this->createCashierFixtures();

        $this->actingAs($fixtures['cashier'])
            ->from('/pagos')
            ->post('/cuotas', [
                'id_matricula' => $fixtures['enrollment']->id_matricula,
                'concepto' => 'Materiales',
                'numero_cuota' => '3',
                'monto_original' => '120.00',
                'monto_descuento' => '20.00',
                'monto_final' => '100.00',
                'fecha_vencimiento' => today()->addMonth()->toDateString(),
            ])
            ->assertRedirect('/pagos')
            ->assertSessionHas('success');

        $fee = Cuota::query()->where('concepto', 'Materiales')->firstOrFail();
        $this->assertSame('100.00', $fee->monto_final);
        $this->assertSame('Pendiente', $fee->estado_pago);
        $this->assertDatabaseHas('auditoria', ['tabla' => 'cuotas', 'id_registro' => (string) $fee->id_cuota]);

        $this->actingAs($fixtures['cashier'])->from('/pagos')->post('/cuotas', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'concepto' => 'Importe incoherente',
            'monto_original' => '100.00',
            'monto_descuento' => '10.00',
            'monto_final' => '95.00',
            'fecha_vencimiento' => today()->addMonth()->toDateString(),
        ])->assertSessionHasErrors('monto_final');
    }

    public function test_payment_can_cover_multiple_fees_and_cannot_exceed_the_balance(): void
    {
        $fixtures = $this->createCashierFixtures();
        $this->actingAs($fixtures['cashier'])->post('/pagos', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'monto' => '130.00',
            'metodo_pago' => 'Efectivo',
            'aplicaciones' => [
                ['id_cuota' => $fixtures['firstFee']->id_cuota, 'monto_aplicado' => '80.00'],
                ['id_cuota' => $fixtures['secondFee']->id_cuota, 'monto_aplicado' => '50.00'],
            ],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('cuotas', ['id_cuota' => $fixtures['firstFee']->id_cuota, 'estado_pago' => 'Pagado']);
        $this->assertDatabaseHas('cuotas', ['id_cuota' => $fixtures['secondFee']->id_cuota, 'estado_pago' => 'Pagado']);

        $this->actingAs($fixtures['cashier'])->post('/pagos', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'monto' => '1.00',
            'metodo_pago' => 'Efectivo',
            'aplicaciones' => [['id_cuota' => $fixtures['firstFee']->id_cuota, 'monto_aplicado' => '1.00']],
        ])->assertSessionHasErrors('aplicaciones');

        $this->assertSame(1, Pago::query()->count());
    }

    public function test_annulling_payment_requires_a_reason_and_reopens_the_fee(): void
    {
        $fixtures = $this->createCashierFixtures();
        $this->actingAs($fixtures['cashier'])->post('/pagos', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'monto' => '80.00',
            'metodo_pago' => 'Transferencia',
            'aplicaciones' => [['id_cuota' => $fixtures['firstFee']->id_cuota, 'monto_aplicado' => '80.00']],
        ]);
        $payment = Pago::query()->firstOrFail();

        $this->actingAs($fixtures['cashier'])
            ->from('/pagos')
            ->post("/pagos/{$payment->id_pago}/anular", ['motivo_anulacion' => 'Transferencia devuelta'])
            ->assertRedirect('/pagos')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pagos', [
            'id_pago' => $payment->id_pago,
            'estado' => 'anulado',
            'motivo_anulacion' => 'Transferencia devuelta',
            'anulado_por' => $fixtures['cashier']->id_usuario,
        ]);
        $this->assertDatabaseHas('cuotas', ['id_cuota' => $fixtures['firstFee']->id_cuota, 'estado_pago' => 'Pendiente']);
        $this->assertDatabaseHas('auditoria', ['accion' => 'anular', 'tabla' => 'pagos', 'id_registro' => (string) $payment->id_pago]);
        $this->assertSame(1, $payment->fresh()->cuotas()->count());
    }

    public function test_non_cashier_cannot_create_a_payment(): void
    {
        $fixtures = $this->createCashierFixtures();
        $secretaryRole = Role::query()->where('nombre', 'secretaria')->firstOrFail();
        $secretary = User::factory()->create(['id_rol' => $secretaryRole->id_rol]);

        $this->actingAs($secretary)->post('/pagos', [
            'id_matricula' => $fixtures['enrollment']->id_matricula,
            'monto' => '20.00',
            'metodo_pago' => 'Efectivo',
            'aplicaciones' => [['id_cuota' => $fixtures['firstFee']->id_cuota, 'monto_aplicado' => '20.00']],
        ])->assertForbidden();

        $this->assertSame(0, Pago::query()->count());
    }

    /**
     * @return array{cashier: User, enrollment: Matricula, firstFee: Cuota, secondFee: Cuota}
     */
    private function createCashierFixtures(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $cashierRole = Role::query()->where('nombre', 'caja')->firstOrFail();
        $cashier = User::factory()->create(['id_rol' => $cashierRole->id_rol]);
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo Caja',
            'tipo_ciclo' => 'Anual',
            'fecha_inicio' => today()->subDay()->toDateString(),
            'fecha_fin' => today()->addYear()->toDateString(),
            'modalidad' => 'Presencial',
            'estado' => 'En curso',
        ]);
        $student = Estudiante::query()->create([
            'dni' => fake()->unique()->numerify('########'),
            'nombre' => 'Luis',
            'apellido' => 'García',
            'estado' => 'activo',
        ]);
        $group = Grupo::query()->create([
            'id_ciclo' => $cycle->id_ciclo,
            'nombre' => 'Caja A',
            'turno' => 'Mañana',
        ]);
        $enrollment = Matricula::query()->create([
            'id_estudiante' => $student->id_estudiante,
            'id_ciclo' => $cycle->id_ciclo,
            'id_grupo' => $group->id_grupo,
            'fecha_matricula' => today()->toDateString(),
            'estado_matricula' => 'inscrito',
        ]);
        $firstFee = $this->createFee($enrollment, 'Matrícula', 80);
        $secondFee = $this->createFee($enrollment, 'Mensualidad', 50);

        return compact('cashier', 'enrollment', 'firstFee', 'secondFee');
    }

    private function createFee(Matricula $enrollment, string $concept, int $amount): Cuota
    {
        return Cuota::query()->create([
            'id_matricula' => $enrollment->id_matricula,
            'concepto' => $concept,
            'monto_original' => $amount,
            'monto_descuento' => 0,
            'monto_final' => $amount,
            'fecha_vencimiento' => today()->addWeek()->toDateString(),
            'estado_pago' => 'Pendiente',
        ]);
    }
}
