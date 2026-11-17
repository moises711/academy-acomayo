<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_student_can_change_their_pin_from_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $studentProfile = Estudiante::query()->create([
            'dni' => '73456789', 'nombre' => 'Rosa', 'apellido' => 'Díaz', 'estado' => 'activo',
        ]);
        $student = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'estudiante')->valueOrFail('id_rol'),
            'id_estudiante' => $studentProfile->id_estudiante,
            'password' => '123456',
        ]);

        $this->actingAs($student)->get('/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('isStudent', true));
        $this->actingAs($student)->put(route('password.update'), [
            'current_password' => '123456',
            'password' => '654321',
            'password_confirmation' => '654321',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('654321', $student->fresh()->password));
        $this->post('/logout');
        $this->post('/login', ['identifier' => $studentProfile->dni, 'password' => '654321'])
            ->assertRedirect(route('mi.index', absolute: false));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
