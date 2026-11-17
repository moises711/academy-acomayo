<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfesorManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_register_professors(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->firstOrFail()->id_rol,
        ]);

        $this->actingAs($admin)->get('/docentes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Docentes/Index')
                ->where('stats.total', 0));

        $this->actingAs($admin)->from('/docentes')->post('/docentes', [
            'dni' => '70123456',
            'nombre' => 'Rosa',
            'apellido' => 'Quispe',
            'email' => 'rosa.quispe@example.test',
            'telefono' => '987654321',
            'estado' => 'activo',
        ])->assertRedirect('/docentes');

        $this->assertDatabaseHas('profesores', [
            'dni' => '70123456',
            'nombre' => 'Rosa',
            'apellido' => 'Quispe',
            'estado' => 'activo',
        ]);
    }

    public function test_user_without_teacher_management_permission_is_forbidden(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $secretary = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'secretaria')->firstOrFail()->id_rol,
        ]);

        $this->actingAs($secretary)->get('/docentes')->assertForbidden();
        $this->actingAs($secretary)->post('/docentes', [
            'dni' => '70987654', 'nombre' => 'Luis', 'apellido' => 'Rojas', 'estado' => 'activo',
        ])->assertForbidden();

        $this->assertDatabaseMissing('profesores', ['dni' => '70987654']);
    }
}
