<?php

namespace Tests\Feature\Auth;

use App\Models\Estudiante;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'identifier' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'identifier' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_student_login_always_redirects_to_the_student_portal(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $studentRole = Role::query()->where('nombre', 'estudiante')->firstOrFail();
        $student = User::factory()->create(['id_rol' => $studentRole->id_rol]);

        $this->get('/dashboard');
        $this->post('/login', [
            'identifier' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('mi.index', absolute: false));
    }

    public function test_student_can_sign_in_with_their_dni_and_six_digit_pin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $studentProfile = Estudiante::query()->create([
            'dni' => '70123456', 'nombre' => 'Ana', 'apellido' => 'Torres', 'estado' => 'activo',
        ]);
        $student = User::factory()->create([
            'email' => 'ana@example.test',
            'id_rol' => Role::query()->where('nombre', 'estudiante')->valueOrFail('id_rol'),
            'id_estudiante' => $studentProfile->id_estudiante,
            'password' => '012345',
        ]);

        $this->post('/login', ['identifier' => '70123456', 'password' => '012345'])
            ->assertRedirect(route('mi.index', absolute: false));

        $this->assertAuthenticatedAs($student);
    }

    public function test_student_dni_login_rejects_non_six_digit_pins(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $studentProfile = Estudiante::query()->create([
            'dni' => '71234567', 'nombre' => 'Luis', 'apellido' => 'Rojas', 'estado' => 'activo',
        ]);
        User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'estudiante')->valueOrFail('id_rol'),
            'id_estudiante' => $studentProfile->id_estudiante,
            'password' => '123456',
        ]);

        $this->from('/login')->post('/login', ['identifier' => '71234567', 'password' => '1234567'])
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
