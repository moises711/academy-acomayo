<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComunicacionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_publish_and_update_announcements_and_materials(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();
        $course = Curso::query()->firstOrFail();

        $this->actingAs($secretary)->from('/comunicacion')->post('/comunicacion', [
            'tipo_registro' => 'anuncio',
            'titulo' => 'Cambio de horario',
            'contenido' => 'La clase inicia a las 8:00 a. m.',
        ])->assertRedirect('/comunicacion');

        $this->assertDatabaseHas('anuncios', [
            'id_usuario' => $secretary->id_usuario,
            'titulo' => 'Cambio de horario',
            'contenido' => 'La clase inicia a las 8:00 a. m.',
        ]);
        $announcementId = (int) \DB::table('anuncios')->where('titulo', 'Cambio de horario')->value('id_anuncio');

        $this->actingAs($secretary)->put("/comunicacion/anuncio/{$announcementId}", [
            'tipo_registro' => 'anuncio',
            'titulo' => 'Horario actualizado',
            'contenido' => 'La clase inicia a las 8:30 a. m.',
        ])->assertRedirect('/comunicacion');

        $this->actingAs($secretary)->post('/comunicacion', [
            'tipo_registro' => 'material',
            'titulo' => 'Guía de álgebra',
            'id_curso' => $course->id_curso,
            'tipo' => 'PDF',
            'url' => 'https://academia.example.test/algebra.pdf',
        ])->assertRedirect('/comunicacion');

        $this->assertDatabaseHas('materiales', [
            'id_usuario' => $secretary->id_usuario,
            'id_curso' => $course->id_curso,
            'titulo' => 'Guía de álgebra',
            'tipo' => 'PDF',
        ]);
    }

    public function test_user_without_communication_permission_cannot_read_or_publish(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cashier = User::query()->where('email', 'caja@academia.test')->firstOrFail();
        $this->actingAs($cashier)->get('/comunicacion')->assertForbidden();
        $this->actingAs($cashier)->post('/comunicacion', [
            'tipo_registro' => 'anuncio', 'titulo' => 'No autorizado', 'contenido' => 'Contenido',
        ])->assertForbidden();
        $this->assertDatabaseMissing('anuncios', ['titulo' => 'No autorizado']);
    }

    public function test_material_requires_a_valid_url_and_course(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();

        $this->actingAs($secretary)->from('/comunicacion')->post('/comunicacion', [
            'tipo_registro' => 'material', 'titulo' => 'Material sin enlace', 'tipo' => 'PDF', 'url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors(['id_curso', 'url']);
        $this->assertDatabaseCount('materiales', 0);
    }
}
