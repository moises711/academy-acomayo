<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_dashboard_does_not_receive_financial_records(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $secretary = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'secretaria')->firstOrFail()->id_rol,
        ]);

        $this->actingAs($secretary)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('role', 'secretaria')
                ->has('consultasNuevas')
                ->missing('pagosRecientes'));
    }

    public function test_teacher_dashboard_contains_only_their_schedule_data(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $teacher = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'profesor')->firstOrFail()->id_rol,
        ]);

        $this->actingAs($teacher)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('role', 'profesor')
                ->has('clasesHoy', 0)
                ->missing('pagosRecientes')
                ->missing('matriculasRecientes'));
    }
}
