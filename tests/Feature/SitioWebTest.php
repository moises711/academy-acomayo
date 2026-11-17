<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\Profesor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SitioWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_website_content_editor(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@academia.test')->firstOrFail();

        $this->actingAs($admin)->get(route('sitio-web.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('SitioWeb/Index')
                ->has('docentes')
                ->has('contenido'));
    }

    public function test_user_without_administration_permission_cannot_edit_website_content(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();

        $this->actingAs($secretary)->get(route('sitio-web.index'))->assertForbidden();
    }

    public function test_admin_can_publish_content_and_private_teacher_photos(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        $admin = User::query()->where('email', 'admin@academia.test')->firstOrFail();
        $teacher = Profesor::query()->where('estado', 'activo')->firstOrFail();

        $this->actingAs($admin)->put(route('sitio-web.update'), [
            'contenido' => $this->content(),
            'docentes' => [['id_profesor' => $teacher->id_profesor, 'bio' => 'Docente de preparación universitaria.']],
            'fotos_docentes' => [$teacher->id_profesor => UploadedFile::fake()->image('docente.jpg')],
            'foto_portada' => UploadedFile::fake()->image('clase.jpg'),
        ])->assertRedirect();

        $published = json_decode(Configuracion::query()->whereKey('sitio.contenido')->value('valor'), true);
        $this->assertSame('Inscríbete hoy', $published['hero']['title']);
        $teacherConfig = json_decode(Configuracion::query()->whereKey('sitio.docentes')->value('valor'), true);
        $this->assertStringStartsWith('sitio/docentes/', $teacherConfig[0]['foto_path']);
        Storage::disk('local')->assertExists($teacherConfig[0]['foto_path']);
        $cover = Configuracion::query()->whereKey('sitio.foto_portada')->value('valor');
        Storage::disk('local')->assertExists($cover);
        $this->assertDatabaseHas('auditoria', [
            'id_usuario' => $admin->id_usuario,
            'tabla' => 'configuraciones',
            'id_registro' => 'sitio.contenido',
            'accion' => 'editar',
        ]);
    }

    /** @return array<string, mixed> */
    private function content(): array
    {
        return [
            'brand' => 'Academia Horizonte',
            'hero' => [
                'eyebrow' => 'Convocatoria abierta', 'title' => 'Inscríbete hoy', 'description' => 'Preparación preuniversitaria.',
                'primary' => 'Ver programas', 'secondary' => 'Consultar', 'visualLabel' => 'Nuestras clases', 'caption' => 'Estudia con nosotros.',
                'stats' => [['value' => '90%', 'label' => 'avance'], ['value' => '10+', 'label' => 'años'], ['value' => '6', 'label' => 'cursos']],
            ],
            'admisiones' => ['fecha_apertura' => '2026-01-01', 'fecha_cierre' => '2026-02-01', 'mensaje' => 'Vacantes disponibles.'],
            'programs' => ['eyebrow' => 'Ciclos', 'title' => 'Programas', 'description' => 'Elige tu ciclo.', 'inquire' => 'Consultar', 'fallback' => array_fill(0, 3, ['name' => 'Ciclo', 'type' => 'Anual', 'modality' => 'Presencial', 'dates' => 'Por confirmar'])],
            'contact' => ['title' => 'Contacto', 'description' => 'Escríbenos.', 'address' => 'Lima', 'hours' => 'Lunes a sábado', 'whatsapp' => 'WhatsApp', 'whatsappUrl' => 'https://wa.me/51900000000', 'phone' => 'Teléfono', 'email' => 'Correo', 'phoneLabel' => 'Tu teléfono', 'emailLabel' => 'Tu correo', 'publicPhone' => '900000000', 'publicEmail' => 'info@academia.test', 'addressLabel' => 'Dirección', 'hoursLabel' => 'Atención', 'mapPlaceholder' => 'Mapa', 'name' => 'Nombre', 'interest' => 'Interés', 'interestPlaceholder' => 'Selecciona', 'interests' => ['Ciclo anual'], 'message' => 'Consulta', 'submit' => 'Enviar', 'sending' => 'Enviando', 'success' => 'Recibido'],
            'methodology' => ['eyebrow' => 'Método', 'title' => 'Aprender', 'description' => 'Acompañamiento semanal.', 'features' => []],
            'faculty' => ['eyebrow' => 'Docentes', 'title' => 'Equipo docente', 'description' => 'Docentes cercanos.', 'placeholder' => 'Foto', 'people' => []],
            'schedule' => ['eyebrow' => 'Horarios', 'title' => 'Estudia', 'description' => 'Turnos disponibles.', 'options' => [['title' => 'Mañana', 'time' => '8:00'], ['title' => 'Tarde', 'time' => '14:00'], ['title' => 'Noche', 'time' => '19:00']]],
            'testimonials' => ['eyebrow' => 'Familias', 'title' => 'Experiencias', 'items' => [['quote' => 'Buen acompañamiento.', 'name' => 'Ana', 'detail' => 'Estudiante'], ['quote' => 'Muy buen equipo.', 'name' => 'Luis', 'detail' => 'Apoderado']]],
            'faq' => ['eyebrow' => 'Dudas', 'title' => 'Preguntas', 'items' => array_fill(0, 4, ['question' => '¿Cómo me inscribo?', 'answer' => 'Contacta a secretaría.'])],
            'results' => ['eyebrow' => 'Resultados', 'title' => 'Avances', 'description' => 'Resultados de ejemplo.', 'metrics' => [['value' => '90%', 'label' => 'avance'], ['value' => '10+', 'label' => 'años'], ['value' => '6', 'label' => 'cursos']], 'note' => 'Resultados informativos.'],
            'footer' => ['description' => 'Academia preuniversitaria.', 'linksTitle' => 'Enlaces', 'contactTitle' => 'Contacto', 'copyright' => '© 2026 Academia'],
        ];
    }
}
