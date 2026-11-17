<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_open_the_student_record_with_all_academic_sections(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();
        $student = Estudiante::query()->firstOrFail();

        $this->actingAs($secretary)->get(route('estudiantes.show', $student))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Estudiantes/Show')
                ->has('estudiante.apoderados')
                ->has('estudiante.matriculas')
                ->has('asistencias')
                ->has('notas')
                ->has('resultados')
                ->missing('estudiante.codigo_qr'));
    }
}
