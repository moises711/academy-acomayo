<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuditoriaPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_audit_records(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->firstOrFail()->id_rol,
        ]);

        $this->actingAs($admin)->get('/auditoria')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auditoria/Index')
                ->has('registros.data')
                ->has('tablas'));
    }

    public function test_secretary_cannot_review_audit_records(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $secretary = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'secretaria')->firstOrFail()->id_rol,
        ]);

        $this->actingAs($secretary)->get('/auditoria')->assertForbidden();
    }
}
