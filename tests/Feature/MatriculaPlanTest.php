<?php

namespace Tests\Feature;

use App\Models\Beca;
use App\Models\Ciclo;
use App\Models\Cuota;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MatriculaPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_index_provides_pagination_metadata_expected_by_the_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);

        $this->actingAs($admin)
            ->get(route('matriculas.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Matriculas/Index')
                ->has('matriculas.data')
                ->has('matriculas.meta.links'));
    }

    public function test_enrollment_creates_monthly_fees_and_distributes_discount_and_remainder(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '74123456', 'nombre' => 'Rosa', 'apellido' => 'Díaz', 'estado' => 'activo',
        ]);
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo Verano', 'tipo_ciclo' => 'Semestral',
            'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2027-04-30',
            'modalidad' => 'Presencial', 'estado' => 'Programado',
        ]);
        $scholarship = Beca::query()->create([
            'nombre' => 'Beca parcial', 'tipo' => 'Beca', 'porcentaje_descuento' => 25, 'activa' => true,
        ]);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);

        $this->actingAs($admin)->post(route('matriculas.store'), [
            'id_estudiante' => $student->id_estudiante,
            'id_ciclo' => $cycle->id_ciclo,
            'id_beca' => $scholarship->id_beca,
            'fecha_matricula' => '2026-10-06',
            'plan' => [
                'monto_total' => '100.01',
                'numero_cuotas' => 3,
                'fecha_primera_cuota' => '2026-11-30',
            ],
        ])->assertRedirect(route('matriculas.index'));

        $fees = Cuota::query()->orderBy('numero_cuota')->get();
        $this->assertCount(3, $fees);
        $this->assertSame(['33.34', '33.34', '33.33'], $fees->pluck('monto_original')->all());
        $this->assertSame(['8.34', '8.33', '8.33'], $fees->pluck('monto_descuento')->all());
        $this->assertSame(['25.00', '25.01', '25.00'], $fees->pluck('monto_final')->all());
        $this->assertSame(['2026-11-30', '2026-12-30', '2027-01-30'], $fees->map(fn (Cuota $fee): string => $fee->fecha_vencimiento->toDateString())->all());
    }

    public function test_enrollment_rejects_a_group_that_belongs_to_another_cycle(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '75234567', 'nombre' => 'Carlos', 'apellido' => 'Vega', 'estado' => 'activo',
        ]);
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo A', 'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31',
            'modalidad' => 'Presencial', 'estado' => 'En curso',
        ]);
        $otherCycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo B', 'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31',
            'modalidad' => 'Presencial', 'estado' => 'En curso',
        ]);
        $group = Grupo::query()->create([
            'id_ciclo' => $otherCycle->id_ciclo, 'nombre' => 'Grupo B', 'turno' => 'Mañana',
        ]);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);

        $this->actingAs($admin)->post(route('matriculas.store'), [
            'id_estudiante' => $student->id_estudiante,
            'id_ciclo' => $cycle->id_ciclo,
            'id_grupo' => $group->id_grupo,
            'fecha_matricula' => '2026-10-06',
            'plan' => ['monto_total' => '100', 'numero_cuotas' => 1, 'fecha_primera_cuota' => '2026-11-01'],
        ])->assertSessionHasErrors('id_grupo');
    }
}
