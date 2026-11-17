<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_open_user_management(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $studentRole = Role::query()->where('nombre', 'estudiante')->firstOrFail();
        $student = User::factory()->create(['id_rol' => $studentRole->id_rol]);

        $this->actingAs($student)->get('/usuarios')->assertForbidden();
    }
}
