<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\ClaveRespuesta;
use App\Models\Curso;
use App\Models\PlantillaHoja;
use App\Models\Role;
use App\Models\Simulacro;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OmrTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_omr_workspace(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->firstOrFail()->id_rol,
        ]);

        $this->actingAs($admin)->get('/omr')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Omr/Index')
                ->has('omr.available')
                ->where('stats.total', 0));
    }

    public function test_user_without_omr_permission_cannot_open_workspace(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $cashier = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'caja')->firstOrFail()->id_rol,
        ]);

        $this->actingAs($cashier)->get('/omr')->assertForbidden();
    }

    public function test_admin_can_save_answer_key_and_create_its_template(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create([
            'id_rol' => Role::query()->where('nombre', 'admin')->firstOrFail()->id_rol,
        ]);
        $cycle = Ciclo::query()->create([
            'nombre_ciclo' => 'Ciclo 2026',
            'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'modalidad' => 'Presencial',
            'estado' => 'En curso',
        ]);
        $course = Curso::query()->create(['nombre_curso' => 'Matemática', 'categoria' => 'Ciencias']);
        $exam = Simulacro::query()->create([
            'titulo' => 'Simulacro de admisión',
            'fecha_aplicacion' => '2026-10-06',
            'id_ciclo' => $cycle->id_ciclo,
            'puntaje_maximo' => 20,
        ]);

        $this->actingAs($admin)->from('/omr')->post(route('omr.claves.store', $exam), [
            'claves' => [[
                'numero_pregunta' => 1,
                'id_curso' => $course->id_curso,
                'respuesta_correcta' => 'C',
                'puntos_correcta' => '1.00',
                'puntos_incorrecta' => '-0.25',
            ]],
        ])->assertRedirect('/omr');

        $this->assertDatabaseHas('claves_respuesta', [
            'id_simulacro' => $exam->id_simulacro,
            'numero_pregunta' => 1,
            'respuesta_correcta' => 'C',
            'puntos_incorrecta' => '-0.25',
        ]);

        $this->actingAs($admin)->from('/omr')->post(route('omr.plantillas.store', $exam), [
            'nombre' => 'Plantilla de prueba',
            'titulo' => 'Examen de becas',
            'instrucciones' => 'Marca una alternativa.',
            'columnas' => 1,
        ])->assertRedirect('/omr');

        $template = PlantillaHoja::query()->where('nombre', 'Plantilla de prueba')->firstOrFail();
        $this->assertSame('Examen de becas', $template->config['titulo']);
        $this->assertSame('Marca una alternativa.', $template->config['instrucciones']);
        $this->assertSame(1, $template->config['columnas']);
        $this->assertCount(5, $template->config['questions'][0]['opciones']);
        $this->assertArrayNotHasKey('respuesta_correcta', $template->config['questions'][0]);

        $this->actingAs($admin)->from('/omr')->post(route('omr.plantillas.store', $exam), [
            'nombre' => 'Diseño inválido', 'columnas' => 3,
        ])->assertSessionHasErrors('columnas');

        $cashier = User::factory()->create(['id_rol' => Role::query()->where('nombre', 'caja')->firstOrFail()->id_rol]);
        $this->actingAs($cashier)->post(route('omr.plantillas.store', $exam), ['nombre' => 'No autorizado'])->assertForbidden();

        foreach (range(2, 61) as $number) {
            ClaveRespuesta::query()->create([
                'id_simulacro' => $exam->id_simulacro, 'id_curso' => $course->id_curso,
                'numero_pregunta' => $number, 'respuesta_correcta' => 'B',
                'puntos_correcta' => '2.00', 'puntos_incorrecta' => '-0.50',
            ]);
        }
        $this->actingAs($admin)->from('/omr')->post(route('omr.plantillas.store', $exam), [
            'nombre' => 'No cabe', 'columnas' => 1,
        ])->assertSessionHasErrors('columnas');
        $this->assertDatabaseMissing('plantillas_hoja', ['nombre' => 'No cabe']);
        $this->actingAs($admin)->from('/omr')->post(route('omr.plantillas.store', $exam), [
            'nombre' => 'Dos columnas', 'columnas' => 2,
        ])->assertRedirect('/omr');
        $largeTemplate = PlantillaHoja::query()->where('nombre', 'Dos columnas')->firstOrFail();
        $this->assertSame(850, $largeTemplate->config['questions'][60]['label_x']);
        $this->assertSame(61, $largeTemplate->num_preguntas);

        $this->assertDatabaseHas('plantillas_hoja', [
            'nombre' => 'Plantilla de prueba',
            'num_preguntas' => 1,
        ]);
    }
}
