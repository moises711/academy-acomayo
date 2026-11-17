<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConfiguracionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_read_and_update_attendance_configuration(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@academia.test')->firstOrFail();

        $this->actingAs($admin)->get('/configuracion')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Configuracion/Index')
                ->where('configuracion.tolerancia_minutos', 10)
                ->where('configuracion.bloquear_si_deuda', false));

        $this->actingAs($admin)->put('/configuracion', [
            'tolerancia_minutos' => 15,
            'bloquear_si_deuda' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('configuraciones', ['clave' => 'asistencia.tolerancia_minutos', 'valor' => '15']);
        $this->assertDatabaseHas('configuraciones', ['clave' => 'asistencia.bloquear_si_deuda', 'valor' => '1']);
    }

    public function test_non_admin_cannot_change_configuration(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();

        $this->actingAs($secretary)->get('/configuracion')->assertForbidden();
        $this->actingAs($secretary)->put('/configuracion', [
            'tolerancia_minutos' => 0,
            'bloquear_si_deuda' => true,
        ])->assertForbidden();
    }
}
