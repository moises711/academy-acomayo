<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportesPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_reportes_page(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $adminRole = Role::query()->where('nombre', 'admin')->firstOrFail();
        $admin = User::factory()->create(['id_rol' => $adminRole->id_rol]);

        $this->actingAs($admin)->get('/reportes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reportes/Index')
                ->has('resumen.estudiantes_por_estado')
                ->has('ingresosPorMes', 6));
    }

    public function test_professor_without_report_permission_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $teacherRole = Role::query()->where('nombre', 'profesor')->firstOrFail();
        $teacher = User::factory()->create(['id_rol' => $teacherRole->id_rol]);

        $this->actingAs($teacher)->get('/reportes')->assertForbidden();
    }
}
