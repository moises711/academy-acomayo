<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\Role;
use App\Models\Simulacro;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SimulacroManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_and_update_a_simulacro(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo de preparación',
            'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'modalidad' => 'Presencial',
            'estado' => 'Programado',
        ]);
        $simulacro = Simulacro::query()->create([
            'titulo' => 'Simulacro inicial',
            'fecha_aplicacion' => '2026-10-10',
            'id_ciclo' => $cycle->id_ciclo,
            'puntaje_maximo' => '100.00',
        ]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('simulacros.edit', $simulacro->id_simulacro))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Simulacros/Edit')
                ->where('simulacro.id_simulacro', $simulacro->id_simulacro));

        $this->actingAs($admin)
            ->put(route('simulacros.update', $simulacro->id_simulacro), [
                'titulo' => 'Simulacro actualizado',
                'fecha_aplicacion' => '2026-10-15',
                'id_ciclo' => $cycle->id_ciclo,
                'puntaje_maximo' => '120.00',
            ])
            ->assertRedirect(route('simulacros.index'));

        $this->assertDatabaseHas('simulacros', [
            'id_simulacro' => $simulacro->id_simulacro,
            'titulo' => 'Simulacro actualizado',
            'puntaje_maximo' => '120.00',
        ]);
    }

    public function test_user_without_evaluation_permission_cannot_open_simulacro_editor(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $studentRole = Role::query()->where('nombre', 'estudiante')->firstOrFail();
        $student = User::factory()->create(['id_rol' => $studentRole->id_rol]);
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo de preparación',
            'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'modalidad' => 'Presencial',
            'estado' => 'Programado',
        ]);
        $simulacro = Simulacro::query()->create([
            'titulo' => 'Simulacro inicial',
            'fecha_aplicacion' => '2026-10-10',
            'id_ciclo' => $cycle->id_ciclo,
            'puntaje_maximo' => '100.00',
        ]);

        $this->actingAs($student)
            ->get(route('simulacros.edit', $simulacro->id_simulacro))
            ->assertForbidden();
    }

    public function test_simulacro_detail_and_results_navigation_resolves_to_existing_pages(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo de preparación',
            'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'modalidad' => 'Presencial',
            'estado' => 'Programado',
        ]);
        $simulacro = Simulacro::query()->create([
            'titulo' => 'Simulacro inicial',
            'fecha_aplicacion' => '2026-10-10',
            'id_ciclo' => $cycle->id_ciclo,
            'puntaje_maximo' => '100.00',
        ]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('simulacros.show', $simulacro->id_simulacro))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Simulacros/Show'));

        $this->actingAs($admin)
            ->get(route('simulacros.resultados', $simulacro->id_simulacro))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Simulacros/Resultados')
                ->has('matriculas')
                ->has('resultados'));
    }
}
