<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarnetTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_and_select_a_carnet_template_using_configurations_table(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@academia.test')->firstOrFail();

        $this->actingAs($admin)->post(route('carnets.plantillas.store'), $this->templateData())
            ->assertRedirect();

        $setting = Configuracion::query()->where('clave', 'like', 'carnet.plantilla.%')
            ->where('clave', '!=', 'carnet.plantilla.default')->firstOrFail();
        $this->assertDatabaseHas('configuraciones', ['clave' => 'carnet.plantilla.default', 'valor' => str($setting->clave)->after('carnet.plantilla.')->toString()]);
        $this->assertSame('Carnet demo', json_decode($setting->valor, true, flags: JSON_THROW_ON_ERROR)['nombre']);
    }

    public function test_only_users_with_design_permission_can_manage_carnet_templates(): void
    {
        $this->seed(DatabaseSeeder::class);
        $secretary = User::query()->where('email', 'secretaria@academia.test')->firstOrFail();

        $this->actingAs($secretary)->get(route('carnets.plantillas.index'))->assertForbidden();
        $this->actingAs($secretary)->post(route('carnets.plantillas.store'), $this->templateData())->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function templateData(): array
    {
        return [
            'nombre' => 'Carnet demo', 'ancho_mm' => 85.6, 'alto_mm' => 54, 'orientacion' => 'horizontal',
            'campos' => ['nombre' => true, 'dni' => true, 'codigo' => false, 'grupo' => true, 'ciclo' => true, 'vigencia' => false, 'foto' => true, 'logo' => true, 'nombre_academia' => true, 'texto_libre' => false],
            'qr_mm' => 18, 'qr_posicion' => 'derecha', 'color_fondo' => '#ffffff', 'color_acento' => '#173b66',
            'color_texto' => '#172033', 'tamano_fuente' => 10, 'franja_superior' => true, 'reverso' => false, 'texto_reverso' => '',
        ];
    }
}
