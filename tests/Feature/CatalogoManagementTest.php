<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_manage_a_classroom_catalog_entry(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();

        $this->actingAs($secretary)->get('/catalogos')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Catalogos/Index')->has('universidades')->has('grupos'));

        $this->actingAs($secretary)->from('/catalogos')->post('/catalogos/aulas', [
            'nombre' => 'Aula Virtual 07',
            'tipo' => 'Virtual',
            'capacidad' => 40,
            'enlace_virtual' => 'https://meet.example.test/aula-07',
        ])->assertRedirect('/catalogos');

        $this->assertDatabaseHas('aulas', ['nombre' => 'Aula Virtual 07', 'tipo' => 'Virtual', 'capacidad' => 40]);
    }

    public function test_catalogs_require_academic_management_permission(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cashier = User::query()->where('email', 'caja@academia.test')->firstOrFail();

        $this->actingAs($cashier)->get('/catalogos')->assertForbidden();
        $this->actingAs($cashier)->post('/catalogos/areas', ['nombre' => 'Área no autorizada'])->assertForbidden();
        $this->assertDatabaseMissing('areas_academicas', ['nombre' => 'Área no autorizada']);
    }

    public function test_classrooms_with_scheduled_classes_cannot_be_deleted(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@academia.test')->firstOrFail();
        $classroom = Aula::query()->whereHas('horarios')->firstOrFail();

        $this->actingAs($admin)->from('/catalogos')->delete(route('catalogos.destroy', ['aulas', $classroom->id_aula]))
            ->assertRedirect('/catalogos')->assertSessionHas('error');

        $this->assertDatabaseHas('aulas', ['id_aula' => $classroom->id_aula]);
    }
}
