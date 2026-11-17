<?php

namespace Tests\Feature;

use App\Models\Ciclo;
use App\Models\ClaveRespuesta;
use App\Models\ConvocatoriaBeca;
use App\Models\Curso;
use App\Models\Estudiante;
use App\Models\HojaRespuesta;
use App\Models\Matricula;
use App\Models\PlantillaHoja;
use App\Models\PostulanteBeca;
use App\Models\Role;
use App\Models\Simulacro;
use App\Models\User;
use App\Support\CompactOmrLayout;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BecaRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(): ConvocatoriaBeca
    {
        $cycle = Ciclo::query()->create(['nombre_ciclo' => 'Ciclo Becas 2026', 'tipo_ciclo' => 'Anual', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31', 'modalidad' => 'Presencial', 'estado' => 'En curso']);
        $course = Curso::query()->create(['nombre_curso' => 'Razonamiento', 'categoria' => 'Ciencias']);
        $ids = [];
        foreach (['A–B' => 'A', 'C–D' => 'C'] as $pair => $answer) {
            $exam = Simulacro::query()->create(['titulo' => 'Becas '.$pair, 'fecha_aplicacion' => today('America/Lima'), 'id_ciclo' => $cycle->id_ciclo, 'puntaje_maximo' => '2.00']);
            foreach ([1, 2] as $number) {
                ClaveRespuesta::query()->create(['id_simulacro' => $exam->id_simulacro, 'numero_pregunta' => $number, 'id_curso' => $course->id_curso, 'respuesta_correcta' => $answer, 'puntos_correcta' => '1.00', 'puntos_incorrecta' => '-0.25']);
            }
            $ids[] = $exam->id_simulacro;
        }

        return ConvocatoriaBeca::factory()->create(['estado' => 'Abierta', 'puntaje_minimo' => '1.00', 'id_simulacro_ab' => $ids[0], 'id_simulacro_cd' => $ids[1]]);
    }

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        return User::factory()->create(['id_rol' => Role::query()->where('nombre', 'admin')->value('id_rol')]);
    }

    private function sheet(PostulanteBeca $applicant): HojaRespuesta
    {
        $template = PlantillaHoja::query()->create(['nombre' => 'Cartilla becas', 'num_preguntas' => 2, 'opciones_por_pregunta' => 5, 'config' => ['questions' => [['numero_pregunta' => 1], ['numero_pregunta' => 2]]], 'activa' => true]);

        return HojaRespuesta::query()->create(['id_simulacro' => $applicant->examenId(), 'id_plantilla' => $template->id_plantilla, 'codigo_hoja' => 'BE-'.Str::uuid(), 'estado' => 'revision']);
    }

    private function revision(string $dni, string $answer, string $state = 'valida'): array
    {
        return ['dni_postulante' => $dni, 'motivo' => 'Verificación manual de DNI y respuestas', 'respuestas' => [
            ['numero_pregunta' => 1, 'marcada' => $state === 'valida' ? $answer : null, 'estado' => $state],
            ['numero_pregunta' => 2, 'marcada' => null, 'estado' => 'blanco'],
        ]];
    }

    public function test_public_registration_saves_dni_phone_and_all_groups_without_exposing_applicants(): void
    {
        $campaign = $this->campaign();
        foreach (['A', 'B', 'C', 'D'] as $index => $group) {
            $dni = '7000000'.($index + 1);
            $this->from('/becas/inscripcion')->post(route('becas.inscripcion.store'), ['id_convocatoria' => $campaign->id_convocatoria, 'dni' => $dni, 'telefono' => '987654321', 'grupo' => $group])->assertRedirect('/becas/inscripcion')->assertSessionHasNoErrors();
            $this->assertDatabaseHas('postulantes_becas', ['dni' => $dni, 'telefono' => '987654321', 'grupo' => $group, 'estado' => 'Inscrito']);
        }
        $this->get('/becas/inscripcion')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Becas/Inscripcion')->has('convocatorias', 1)->missing('postulantes'));
        $this->post(route('becas.inscripcion.store'), ['id_convocatoria' => $campaign->id_convocatoria, 'dni' => '70000001', 'telefono' => '999999999', 'grupo' => 'D'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('postulantes_becas', 4);
        $this->assertDatabaseHas('postulantes_becas', ['dni' => '70000001', 'telefono' => '987654321', 'grupo' => 'A']);
    }

    public function test_invalid_and_closed_registrations_are_refused(): void
    {
        $campaign = $this->campaign();
        $this->post(route('becas.inscripcion.store'), ['id_convocatoria' => $campaign->id_convocatoria, 'dni' => 'abc', 'telefono' => '123', 'grupo' => 'E'])->assertSessionHasErrors(['dni', 'telefono', 'grupo']);
        $campaign->update(['fecha_fin' => today('America/Lima')->subDay()]);
        $this->post(route('becas.inscripcion.store'), ['id_convocatoria' => $campaign->id_convocatoria, 'dni' => '70000001', 'telefono' => '987654321', 'grupo' => 'B'])->assertSessionHasErrors('id_convocatoria');
        $this->assertDatabaseCount('postulantes_becas', 0);
    }

    public function test_admin_can_publish_exam_schedule_and_public_landing_hides_it_after_the_end(): void
    {
        $admin = $this->admin();
        $campaign = $this->campaign();
        $examDate = today('America/Lima')->addDay()->toDateString();
        $payload = ['nombre' => $campaign->nombre, 'descripcion' => $campaign->descripcion, 'fecha_inicio' => today('America/Lima')->toDateString(), 'fecha_fin' => today('America/Lima')->addWeek()->toDateString(), 'examen_becas_activo' => true, 'fecha_examen' => $examDate, 'hora_inicio_examen' => '09:00', 'hora_fin_examen' => '11:30', 'puntaje_minimo' => '1.00', 'estado' => 'Abierta', 'id_simulacro_ab' => $campaign->id_simulacro_ab, 'id_simulacro_cd' => $campaign->id_simulacro_cd];

        $this->actingAs($admin)->put(route('becas.update', $campaign), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('convocatorias_becas', ['id_convocatoria' => $campaign->id_convocatoria, 'examen_becas_activo' => true, 'fecha_examen' => $examDate, 'hora_inicio_examen' => '09:00:00', 'hora_fin_examen' => '11:30:00']);
        $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Welcome')->where('examenBecas.fecha_examen', $examDate)->where('examenBecas.hora_inicio_examen', '09:00')->where('examenBecas.hora_fin_examen', '11:30'));
        $this->get(route('becas.inscripcion'))->assertInertia(fn (Assert $page) => $page->component('Becas/Inscripcion')->where('examenBecas.fecha_examen', $examDate)->where('examenBecas.hora_inicio_examen', '09:00'));

        $campaign->update(['fecha_examen' => today('America/Lima')->subDay()]);
        $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Welcome')->where('examenBecas', null));
        $this->get(route('becas.inscripcion'))->assertInertia(fn (Assert $page) => $page->component('Becas/Inscripcion')->where('examenBecas', null));
    }

    public function test_exam_schedule_requires_valid_times_when_publication_is_enabled(): void
    {
        $admin = $this->admin();
        $campaign = $this->campaign();
        $payload = ['nombre' => $campaign->nombre, 'descripcion' => $campaign->descripcion, 'fecha_inicio' => today('America/Lima')->toDateString(), 'fecha_fin' => today('America/Lima')->addWeek()->toDateString(), 'examen_becas_activo' => true, 'fecha_examen' => today('America/Lima')->addDay()->toDateString(), 'hora_inicio_examen' => '11:00', 'hora_fin_examen' => '09:00', 'puntaje_minimo' => '1.00', 'estado' => 'Abierta', 'id_simulacro_ab' => $campaign->id_simulacro_ab, 'id_simulacro_cd' => $campaign->id_simulacro_cd];

        $this->actingAs($admin)->put(route('becas.update', $campaign), $payload)->assertSessionHasErrors('hora_fin_examen');
    }

    public function test_administrator_permissions_protect_configuration_list_and_closure(): void
    {
        $admin = $this->admin();
        $campaign = $this->campaign();
        $cashier = User::factory()->create(['id_rol' => Role::query()->where('nombre', 'caja')->value('id_rol')]);
        $this->assertTrue(Gate::forUser($admin)->allows('manage', ConvocatoriaBeca::class));
        $this->assertFalse(Gate::forUser($cashier)->allows('manage', ConvocatoriaBeca::class));
        $this->get('/becas')->assertRedirect('/login');
        $this->actingAs($admin)->get('/becas')->assertOk();
        $this->actingAs($cashier)->get('/becas')->assertForbidden();
        $this->post('/becas', [])->assertForbidden();
        $this->post(route('becas.close', $campaign), [])->assertForbidden();
        $this->actingAs($admin)->post(route('becas.close', $campaign), [])->assertSessionHasErrors(['confirmacion', 'eliminar_no_becarios']);
    }

    public function test_campaign_configuration_prevents_sharing_an_exam_or_overwriting_registered_group_rules(): void
    {
        $admin = $this->admin();
        $campaign = $this->campaign();
        $payload = ['nombre' => 'Becas nuevas', 'descripcion' => 'Registro público', 'fecha_inicio' => today('America/Lima')->toDateString(), 'fecha_fin' => today('America/Lima')->addWeek()->toDateString(), 'puntaje_minimo' => '1.00', 'estado' => 'Abierta', 'id_simulacro_ab' => $campaign->id_simulacro_ab, 'id_simulacro_cd' => $campaign->id_simulacro_cd];
        $this->actingAs($admin)->post('/becas', $payload)->assertSessionHasErrors('id_simulacro_ab');
        $this->put(route('becas.update', $campaign), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('convocatorias_becas', ['id_convocatoria' => $campaign->id_convocatoria, 'nombre' => 'Becas nuevas']);
        PostulanteBeca::factory()->create(['id_convocatoria' => $campaign->id_convocatoria]);
        $payload['puntaje_minimo'] = '2.00';
        $this->put(route('becas.update', $campaign), $payload)->assertSessionHasErrors('nombre');
        $this->assertDatabaseHas('convocatorias_becas', ['id_convocatoria' => $campaign->id_convocatoria, 'puntaje_minimo' => '1.00']);
        $campaign->update(['id_simulacro_ab' => null, 'id_simulacro_cd' => null]);
        $payload['nombre'] = 'Nueva convocatoria independiente';
        $this->post('/becas', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('convocatorias_becas', ['nombre' => 'Nueva convocatoria independiente', 'id_simulacro_ab' => $payload['id_simulacro_ab'], 'id_simulacro_cd' => $payload['id_simulacro_cd']]);
    }

    public function test_omr_identifies_dni_uses_each_group_key_and_rejects_wrong_group_and_duplicate_sheet(): void
    {
        $admin = $this->admin();
        $campaign = $this->campaign();
        $ab = PostulanteBeca::factory()->create(['id_convocatoria' => $campaign->id_convocatoria, 'grupo' => 'B']);
        $cd = PostulanteBeca::factory()->create(['id_convocatoria' => $campaign->id_convocatoria, 'grupo' => 'D']);
        $sheetAb = $this->sheet($ab);
        $sheetCd = $this->sheet($cd);
        $this->actingAs($admin)->patch(route('omr.hojas.update', $sheetAb), $this->revision($cd->dni, 'C'))->assertSessionHasErrors('dni_postulante');
        $this->patch(route('omr.hojas.update', $sheetAb), $this->revision($ab->dni, 'A'))->assertSessionHasNoErrors();
        $this->patch(route('omr.hojas.update', $sheetCd), $this->revision($cd->dni, 'A'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('postulantes_becas', ['id_postulante' => $ab->id_postulante, 'nota' => '1.00', 'estado' => 'Becario']);
        $this->assertDatabaseHas('postulantes_becas', ['id_postulante' => $cd->id_postulante, 'nota' => '-0.25', 'estado' => 'Calificado']);
        $this->assertDatabaseHas('hojas_respuesta', ['id_hoja' => $sheetAb->id_hoja, 'id_postulante' => $ab->id_postulante, 'id_matricula' => null]);
        $another = $this->sheet($ab);
        $this->patch(route('omr.hojas.update', $another), $this->revision($ab->dni, 'A'))->assertSessionHasErrors('dni_postulante');
        $this->assertDatabaseCount('resultados_simulacros', 0);
    }

    public function test_doubtful_answers_block_closure_and_confirmed_cleanup_preserves_only_winners(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $campaign = $this->campaign();
        $winner = PostulanteBeca::factory()->create(['id_convocatoria' => $campaign->id_convocatoria, 'grupo' => 'A']);
        $loser = PostulanteBeca::factory()->create(['id_convocatoria' => $campaign->id_convocatoria, 'grupo' => 'C']);
        $sheet = $this->sheet($winner);
        $lostSheet = $this->sheet($loser);
        Storage::disk('local')->put('omr/uploads/descartado.png', 'demo');
        $lostSheet->update(['archivo_path' => 'omr/uploads/descartado.png']);
        $this->actingAs($admin)->patch(route('omr.hojas.update', $sheet), $this->revision($winner->dni, 'A', 'dudosa'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('postulantes_becas', ['id_postulante' => $winner->id_postulante, 'nota' => null, 'estado' => 'Revision']);
        $payload = ['confirmacion' => $campaign->nombre, 'eliminar_no_becarios' => true];
        $this->post(route('becas.close', $campaign), $payload)->assertSessionHasErrors('confirmacion');
        $this->patch(route('omr.hojas.update', $sheet), $this->revision($winner->dni, 'A'))->assertSessionHasNoErrors();
        $this->patch(route('omr.hojas.update', $lostSheet), $this->revision($loser->dni, 'B'))->assertSessionHasNoErrors();
        $this->post(route('becas.close', $campaign), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('postulantes_becas', ['id_postulante' => $winner->id_postulante, 'dni' => $winner->dni, 'telefono' => $winner->telefono, 'estado' => 'Becario']);
        $this->assertDatabaseMissing('postulantes_becas', ['id_postulante' => $loser->id_postulante]);
        $this->assertDatabaseMissing('hojas_respuesta', ['id_hoja' => $lostSheet->id_hoja]);
        $this->assertDatabaseMissing('respuestas_hoja', ['id_hoja' => $lostSheet->id_hoja]);
        Storage::disk('local')->assertMissing('omr/uploads/descartado.png');
        $this->assertDatabaseHas('convocatorias_becas', ['id_convocatoria' => $campaign->id_convocatoria, 'estado' => 'Cerrada']);
        $this->patch(route('omr.hojas.update', $sheet), $this->revision($winner->dni, 'B'))->assertSessionHasErrors('dni_postulante');
    }

    private function cameraPayload(ConvocatoriaBeca $campaign, string $dni, string $group = 'B', string $state = 'valida'): array
    {
        $keys = ClaveRespuesta::query()->where('id_simulacro', $campaign->id_simulacro_ab)->orderBy('numero_pregunta')->get();
        $config = app(CompactOmrLayout::class)->build($keys, 4);
        $config['simulacro_id'] = $campaign->id_simulacro_ab;
        $template = PlantillaHoja::query()->create(['nombre' => 'Cartilla cámara', 'num_preguntas' => 2, 'opciones_por_pregunta' => 4, 'config' => $config, 'activa' => true]);

        return ['id_simulacro' => (string) $campaign->id_simulacro_ab, 'id_plantilla' => (string) $template->id_plantilla,
            'archivos' => [UploadedFile::fake()->image('camara.png')],
            'lecturas' => [['dni' => $dni, 'grupo' => $group, 'respuestas' => [
                ['numero_pregunta' => 1, 'marcada' => $state === 'valida' ? 'A' : null, 'estado' => $state],
                ['numero_pregunta' => 2, 'marcada' => 'A', 'estado' => 'valida'],
            ]]]];
    }

    public function test_camera_identifies_applicant_and_calculates_grade_without_accepting_client_score(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $campaign = $this->campaign();
        $applicant = PostulanteBeca::factory()->create(['id_convocatoria' => $campaign->id_convocatoria, 'dni' => '12345678', 'grupo' => 'B']);
        $payload = $this->cameraPayload($campaign, $applicant->dni);
        $payload['lecturas'][0]['nota'] = '999.00';
        $this->actingAs($admin)->post(route('omr.hojas.subir'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('2.00', $applicant->fresh()->nota);
        $this->assertSame('Becario', $applicant->fresh()->estado);
        $this->assertDatabaseHas('hojas_respuesta', ['id_postulante' => $applicant->id_postulante, 'estado' => 'procesada']);
        $this->actingAs($admin)->post(route('omr.hojas.subir'), $this->cameraPayload($campaign, $applicant->dni))->assertSessionHasNoErrors();
        $this->assertSame(1, HojaRespuesta::query()->where('id_postulante', $applicant->id_postulante)->count());
        $this->assertDatabaseHas('hojas_respuesta', ['id_postulante' => null, 'estado' => 'revision']);
    }

    public function test_camera_does_not_grade_wrong_group_or_multiple_marks(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $campaign = $this->campaign();
        $applicant = PostulanteBeca::factory()->create(['id_convocatoria' => $campaign->id_convocatoria, 'dni' => '12345678', 'grupo' => 'B']);
        $this->actingAs($admin)->post(route('omr.hojas.subir'), $this->cameraPayload($campaign, $applicant->dni, 'A'))->assertSessionHasNoErrors();
        $this->assertNull($applicant->fresh()->nota);
        $this->assertDatabaseHas('hojas_respuesta', ['id_postulante' => null, 'estado' => 'revision']);
        $this->actingAs($admin)->post(route('omr.hojas.subir'), $this->cameraPayload($campaign, $applicant->dni, 'B', 'multiple'))->assertSessionHasNoErrors();
        $this->assertNull($applicant->fresh()->nota);
        $this->assertSame('Revision', $applicant->fresh()->estado);
    }

    public function test_camera_rejects_invalid_answers_and_denies_user_without_permission(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $campaign = $this->campaign();
        $payload = $this->cameraPayload($campaign, '12345678');
        $cashier = User::factory()->create(['id_rol' => Role::query()->where('nombre', 'caja')->value('id_rol')]);
        $this->actingAs($cashier)->post(route('omr.hojas.subir'), $payload)->assertForbidden();
        $payload['lecturas'][0]['respuestas'][0]['marcada'] = 'E';
        $this->actingAs($admin)->post(route('omr.hojas.subir'), $payload)->assertSessionHasErrors('lecturas');
        $this->assertDatabaseCount('hojas_respuesta', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_compact_template_has_eighty_questions_dni_group_and_guards_its_limits(): void
    {
        $admin = $this->admin();
        $campaign = $this->campaign();
        $first = ClaveRespuesta::query()->where('id_simulacro', $campaign->id_simulacro_ab)->firstOrFail();
        foreach (range(3, 80) as $number) {
            ClaveRespuesta::query()->create(['id_simulacro' => $campaign->id_simulacro_ab, 'numero_pregunta' => $number, 'id_curso' => $first->id_curso, 'respuesta_correcta' => 'A', 'puntos_correcta' => '1.00', 'puntos_incorrecta' => '-0.25']);
        }
        $payload = ['nombre' => 'Cartilla 80', 'diseno' => 'compacto', 'opciones_por_pregunta' => 4];
        $exam = Simulacro::query()->findOrFail($campaign->id_simulacro_ab);
        $this->actingAs($admin)->post(route('omr.plantillas.store', $exam), $payload)->assertSessionHasNoErrors();
        $template = PlantillaHoja::query()->firstOrFail();
        $this->assertSame(80, $template->num_preguntas);
        $this->assertCount(4, $template->config['alignment_marks']);
        $this->assertSame(8, $template->config['dni']['columnas']);
        $this->assertSame(804, $template->config['grupo']['y']);
        $this->assertSame(80, $template->config['questions'][79]['numero_pregunta']);
        $this->assertCount(4, $template->config['questions'][79]['opciones']);
        $first->update(['respuesta_correcta' => 'E']);
        $this->actingAs($admin)->post(route('omr.plantillas.store', $exam), $payload)->assertSessionHasErrors('opciones_por_pregunta');
        $first->update(['respuesta_correcta' => 'A']);
        ClaveRespuesta::query()->create(['id_simulacro' => $exam->id_simulacro, 'numero_pregunta' => 81, 'id_curso' => $first->id_curso, 'respuesta_correcta' => 'A', 'puntos_correcta' => '1.00', 'puntos_incorrecta' => '-0.25']);
        $this->actingAs($admin)->post(route('omr.plantillas.store', $exam), $payload)->assertSessionHasErrors('diseno');
    }

    public function test_camera_identifies_enrolled_student_by_dni_and_displays_the_server_grade(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $campaign = $this->campaign();
        $payload = $this->cameraPayload($campaign, '76543210');
        $exam = Simulacro::query()->findOrFail($campaign->id_simulacro_ab);
        $campaign->update(['id_simulacro_ab' => null]);
        $student = Estudiante::query()->create(['dni' => '76543210', 'nombre' => 'Lucía', 'apellido' => 'Quispe', 'estado' => 'activo']);
        $enrollment = Matricula::query()->create(['id_estudiante' => $student->id_estudiante, 'id_ciclo' => $exam->id_ciclo, 'fecha_matricula' => today('America/Lima'), 'estado_matricula' => 'inscrito']);
        $this->actingAs($admin)->post(route('omr.hojas.subir'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('resultados_simulacros', ['id_simulacro' => $exam->id_simulacro, 'id_matricula' => $enrollment->id_matricula, 'puntaje_obtenido' => '2.00']);
        $this->actingAs($admin)->get(route('omr.index', ['id_simulacro' => $exam->id_simulacro]))->assertInertia(fn (Assert $page) => $page->where('hojas.data.0.resumen_lectura.correctas', 2)->where('hojas.data.0.nota_omr', '2.00')->where('hojas.data.0.matricula.estudiante.dni', '76543210'));
    }

    public function test_unified_key_is_explicit_and_grades_all_four_registered_groups(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $campaign = $this->campaign();
        $payload = ['nombre' => $campaign->nombre, 'descripcion' => '', 'fecha_inicio' => $campaign->fecha_inicio->format('Y-m-d'), 'fecha_fin' => $campaign->fecha_fin->format('Y-m-d'), 'puntaje_minimo' => '1.00', 'estado' => 'Abierta', 'id_simulacro_ab' => (string) $campaign->id_simulacro_ab, 'id_simulacro_cd' => null, 'modo_clave' => 'unificada'];
        $cashier = User::factory()->create(['id_rol' => Role::query()->where('nombre', 'caja')->value('id_rol')]);
        $this->actingAs($cashier)->put(route('becas.update', $campaign), $payload)->assertForbidden();
        $this->actingAs($admin)->put(route('becas.update', $campaign), $payload)->assertSessionHasNoErrors();
        $campaign->refresh();
        $this->assertSame($campaign->id_simulacro_ab, $campaign->id_simulacro_cd);
        foreach (['A', 'B', 'C', 'D'] as $index => $group) {
            $applicant = PostulanteBeca::factory()->create(['id_convocatoria' => $campaign->id_convocatoria, 'dni' => '8000000'.($index + 1), 'grupo' => $group]);
            $this->assertSame($campaign->id_simulacro_ab, $applicant->examenId());
            $this->actingAs($admin)->post(route('omr.hojas.subir'), $this->cameraPayload($campaign, $applicant->dni, $group))->assertSessionHasNoErrors();
            $this->assertSame('2.00', $applicant->fresh()->nota);
        }
        $payload['modo_clave'] = 'separada';
        $payload['id_simulacro_cd'] = $campaign->id_simulacro_ab;
        $this->actingAs($admin)->put(route('becas.update', $campaign), $payload)->assertSessionHasErrors('id_simulacro_ab');
    }
}
