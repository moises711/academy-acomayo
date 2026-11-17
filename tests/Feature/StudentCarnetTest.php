<?php

namespace Tests\Feature;

use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentCarnetTest extends TestCase
{
    use RefreshDatabase;

    public function test_secretary_can_open_a_printable_carnet_without_exposing_the_qr_value(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();
        $student = Estudiante::query()->firstOrFail();

        $this->actingAs($secretary)->get(route('estudiantes.carnet', $student))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Estudiantes/Carnet')
                ->where('carnets.0.nombre', trim($student->nombre.' '.$student->apellido))
                ->where('carnets.0.codigo', 'EST-'.str_pad((string) $student->id_estudiante, 5, '0', STR_PAD_LEFT))
                ->missing('carnets.0.dni')
                ->missing('carnets.0.codigo_qr'));
    }

    public function test_admin_can_regenerate_student_qr_and_the_change_is_audited(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@academia.test')->firstOrFail();
        $student = Estudiante::query()->firstOrFail();
        $originalCode = $student->codigo_qr;

        $this->actingAs($admin)->post(route('estudiantes.carnet.regenerar', $student))->assertRedirect();

        $student->refresh();
        $this->assertNotSame($originalCode, $student->codigo_qr);
        $this->assertTrue(Str::isUuid($student->codigo_qr));
        $this->assertDatabaseHas('auditoria', [
            'id_usuario' => $admin->id_usuario,
            'tabla' => 'estudiantes',
            'id_registro' => (string) $student->id_estudiante,
        ]);
    }

    public function test_role_without_student_view_permission_cannot_open_printable_carnet(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = Estudiante::query()->firstOrFail();
        $portalUser = User::query()->where('email', 'estudiante@academia.test')->firstOrFail();

        $this->actingAs($portalUser)->get(route('estudiantes.carnet', $student))->assertForbidden();
    }

    public function test_role_with_student_read_access_but_without_carnet_permission_cannot_generate_a_carnet(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cashier = User::query()->where('email', 'caja@academia.test')->firstOrFail();
        $student = Estudiante::query()->firstOrFail();

        $this->actingAs($cashier)->get(route('estudiantes.carnet', $student))->assertForbidden();
    }

    public function test_role_without_carnet_permission_cannot_open_group_qr_sheet(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cashier = User::query()->where('email', 'caja@academia.test')->firstOrFail();
        $group = Grupo::query()->firstOrFail();

        $this->actingAs($cashier)->get(route('estudiantes.carnets.qr', ['id_grupo' => $group->id_grupo]))
            ->assertForbidden();
    }

    public function test_secretary_can_open_printable_group_qr_sheet(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();
        $group = Grupo::query()->firstOrFail();

        $this->actingAs($secretary)->get(route('estudiantes.carnets.qr', ['id_grupo' => $group->id_grupo]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Estudiantes/QRsGrupo')
                ->has('estudiantes', fn (Assert $students) => $students
                    ->each(fn (Assert $student) => $student
                        ->where('qrValue', fn ($value) => Str::isUuid($value))
                        ->where('codigo', fn ($value) => str_starts_with($value, 'EST-')))));
    }

    public function test_secretary_can_upload_a_private_student_photo_and_view_it_without_exposing_its_path(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('local');
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();

        $this->actingAs($secretary)->post(route('estudiantes.store'), [
            'dni' => '78945612',
            'nombre' => 'Lucía',
            'apellido' => 'Quispe',
            'estado' => 'activo',
            'foto' => UploadedFile::fake()->image('rostro.png', 400, 500),
        ])->assertRedirect(route('estudiantes.index'));

        $student = Estudiante::query()->where('dni', '78945612')->firstOrFail();
        $this->assertStringStartsWith('estudiantes/fotos/', $student->foto_path);
        Storage::disk('local')->assertExists($student->foto_path);

        $this->actingAs($secretary)->get(route('estudiantes.show', $student))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missing('estudiante.foto_path'));
        $this->actingAs($secretary)->get(route('estudiantes.foto', $student))->assertOk();
    }
}
