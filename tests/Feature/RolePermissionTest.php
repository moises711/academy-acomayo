<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_register_attendance_but_cannot_manage_payments(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $secretaryRole = Role::query()->where('nombre', 'secretaria')->firstOrFail();
        $secretary = User::factory()->create(['id_rol' => $secretaryRole->id_rol]);

        $this->assertSame(true, $secretary->hasPermissionTo('asistencia.registrar_qr'));
        $this->assertSame(false, $secretary->hasPermissionTo('pagos.gestionar'));
    }
}
