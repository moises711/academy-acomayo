<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Estudiante;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_student_account_linked_to_the_student_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '70123456', 'nombre' => 'Ana', 'apellido' => 'Torres', 'estado' => 'activo',
        ]);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);

        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@example.test',
            'password' => '012345',
            'password_confirmation' => '012345',
            'role' => 'estudiante',
            'id_estudiante' => $student->id_estudiante,
        ])->assertRedirect();

        $user = User::query()->where('email', 'ana.torres@example.test')->firstOrFail();
        $this->assertSame($student->id_estudiante, $user->id_estudiante);
        $this->assertSame('estudiante', $user->role);
        $this->assertTrue(Hash::check('012345', $user->password));
        $this->actingAs($user)->get('/mi')->assertOk();
    }

    public function test_student_role_requires_a_profile_and_profile_cannot_be_linked_twice(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '71234567', 'nombre' => 'Luis', 'apellido' => 'Rojas', 'estado' => 'activo',
        ]);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);
        $this->actingAs($admin);

        $this->post(route('usuarios.store'), [
            'name' => 'Sin perfil', 'email' => 'sin-perfil@example.test',
            'password' => '123456', 'password_confirmation' => '123456',
            'role' => 'estudiante',
        ])->assertSessionHasErrors('id_estudiante');

        User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'estudiante')->valueOrFail('id_rol'),
            'id_estudiante' => $student->id_estudiante,
        ]);

        $this->post(route('usuarios.store'), [
            'name' => 'Duplicado', 'email' => 'duplicado@example.test',
            'password' => '123456', 'password_confirmation' => '123456',
            'role' => 'estudiante', 'id_estudiante' => $student->id_estudiante,
        ])->assertSessionHasErrors('id_estudiante');
    }

    public function test_changing_account_to_staff_role_clears_student_profile_link(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '72345678', 'nombre' => 'María', 'apellido' => 'Pérez', 'estado' => 'activo',
        ]);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);
        $user = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'estudiante')->valueOrFail('id_rol'),
            'id_estudiante' => $student->id_estudiante,
        ]);

        $this->actingAs($admin)->put(route('usuarios.update', $user->id_usuario), [
            'role' => 'secretaria',
        ])->assertRedirect();

        $user->refresh();
        $this->assertSame('secretaria', $user->role);
        $this->assertNull($user->id_estudiante);
    }

    public function test_admin_can_delete_an_unused_account_and_audit_the_action(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '74567890', 'nombre' => 'Elena', 'apellido' => 'Vega', 'estado' => 'activo',
        ]);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);
        $account = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'estudiante')->valueOrFail('id_rol'),
            'id_estudiante' => $student->id_estudiante,
        ]);

        $this->actingAs($admin)->delete(route('usuarios.destroy', $account->id_usuario))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($account);
        $this->assertDatabaseHas('estudiantes', ['id_estudiante' => $student->id_estudiante]);
        $this->assertDatabaseHas('auditoria', [
            'id_usuario' => $admin->id_usuario,
            'tabla' => 'users',
            'id_registro' => (string) $account->id_usuario,
            'accion' => 'eliminar',
        ]);
    }

    public function test_admin_deactivates_accounts_with_history_and_releases_profile_links(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $student = Estudiante::query()->create([
            'dni' => '73456789', 'nombre' => 'Rosa', 'apellido' => 'Díaz', 'estado' => 'activo',
        ]);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);
        $account = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'estudiante')->valueOrFail('id_rol'),
            'id_estudiante' => $student->id_estudiante,
        ]);
        Auditoria::query()->create([
            'id_usuario' => $account->id_usuario, 'accion' => 'crear', 'tabla' => 'users',
            'id_registro' => (string) $account->id_usuario, 'datos_despues' => ['name' => $account->name],
        ]);

        $this->actingAs($admin)->delete(route('usuarios.destroy', $account->id_usuario))
            ->assertSessionHasNoErrors();

        $account->refresh();
        $this->assertFalse($account->activo);
        $this->assertNull($account->id_estudiante);
        $this->assertDatabaseHas('users', ['id_usuario' => $account->id_usuario, 'activo' => false]);
    }

    public function test_admin_cannot_delete_their_own_account_or_the_last_active_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->valueOrFail('id_rol'),
        ]);
        $this->actingAs($admin)->delete(route('usuarios.destroy', $admin->id_usuario))
            ->assertSessionHasErrors('usuario');
        $this->put(route('usuarios.update', $admin->id_usuario), [
            'role' => 'secretaria',
        ])->assertSessionHasErrors('role');
    }
}
