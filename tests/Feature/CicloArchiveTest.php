<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\Estudiante;
use App\Models\Matricula;
use App\Models\Role;
use App\Models\User;
use App\Support\CicloArchive;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use ZipArchive;

class CicloArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_selects_only_the_cycle_and_keeps_student_profiles(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create(['id_rol' => Role::where('nombre', 'admin')->value('id_rol')]);
        $past = $this->cycle('Ciclo anterior');
        $current = $this->cycle('Ciclo actual');
        $student = Estudiante::create(['dni' => '12345678', 'nombre' => 'María', 'apellido' => 'Quispe', 'estado' => 'activo']);
        $pastEnrollment = Matricula::create(['id_estudiante' => $student->id_estudiante, 'id_ciclo' => $past->id_ciclo, 'fecha_matricula' => '2025-01-01', 'estado_matricula' => 'inscrito']);
        $currentEnrollment = Matricula::create(['id_estudiante' => $student->id_estudiante, 'id_ciclo' => $current->id_ciclo, 'fecha_matricula' => '2025-01-01', 'estado_matricula' => 'inscrito']);
        $archive = new CicloArchive;
        $queries = $archive->queries($past);

        DB::transaction(function () use ($archive, $queries, $admin): void {
            $archive->deleteRows($archive->snapshot($queries, true), $admin, '127.0.0.1');
        });

        $this->assertModelMissing($past);
        $this->assertModelMissing($pastEnrollment);
        $this->assertModelExists($student);
        $this->assertModelExists($current);
        $this->assertModelExists($currentEnrollment);
        $this->assertDatabaseHas('auditoria', ['accion' => 'eliminar', 'tabla' => 'matriculas', 'id_registro' => (string) $pastEnrollment->id_matricula]);
    }

    public function test_download_contains_csv_and_json_without_student_qr(): void
    {
        $cycle = $this->cycle('Ciclo de prueba');
        $student = Estudiante::create(['dni' => '87654321', 'nombre' => '=CMD()', 'apellido' => 'Rojas', 'estado' => 'activo']);
        Matricula::create(['id_estudiante' => $student->id_estudiante, 'id_ciclo' => $cycle->id_ciclo, 'fecha_matricula' => '2025-01-01', 'estado_matricula' => 'inscrito']);
        $archive = new CicloArchive;
        $rows = $archive->snapshot($archive->queries($cycle));

        $path = $archive->zip($cycle, $rows, []);
        $zip = new ZipArchive;
        try {
            $this->assertTrue($zip->open($path));
            $students = json_decode($zip->getFromName('registros/estudiantes.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('=CMD()', $students[0]['nombre']);
            $this->assertArrayNotHasKey('codigo_qr', $students[0]);
            $this->assertStringContainsString("'=CMD()", $zip->getFromName('csv/estudiantes.csv'));
            $manifest = json_decode($zip->getFromName('manifiesto.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(1, $manifest['registros_a_eliminar']['matriculas']);
        } finally {
            $zip->close();
            unlink($path);
        }
    }

    public function test_archive_refuses_enrollments_attached_to_another_cycles_group(): void
    {
        $past = $this->cycle('Ciclo anterior');
        $current = $this->cycle('Ciclo actual');
        $group = DB::table('grupos')->insertGetId(['id_ciclo' => $past->id_ciclo, 'nombre' => 'Grupo A', 'turno' => 'Mañana', 'capacidad' => 30], 'id_grupo');
        $student = Estudiante::create(['dni' => '11223344', 'nombre' => 'José', 'apellido' => 'Torres', 'estado' => 'activo']);
        Matricula::create(['id_estudiante' => $student->id_estudiante, 'id_ciclo' => $current->id_ciclo, 'id_grupo' => $group, 'fecha_matricula' => '2025-01-01', 'estado_matricula' => 'inscrito']);
        $archive = new CicloArchive;

        $this->expectException(ValidationException::class);
        $archive->snapshot($archive->queries($past));
    }

    private function cycle(string $name): Ciclo
    {
        return Ciclo::create(['nombre_ciclo' => $name, 'tipo_ciclo' => 'Semestral', 'fecha_inicio' => '2025-01-01', 'fecha_fin' => '2025-06-30', 'modalidad' => 'Presencial', 'estado' => 'Cerrado']);
    }
}
