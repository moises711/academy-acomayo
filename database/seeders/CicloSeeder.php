<?php

namespace Database\Seeders;

use App\Models\Ciclo;
use Illuminate\Database\Seeder;

class CicloSeeder extends Seeder
{
    public function run(): void
    {
        Ciclo::query()->updateOrCreate(['nombre_ciclo' => 'Ciclo Anual San Marcos 2026'], [
            'tipo_ciclo' => 'Anual',
            'fecha_inicio' => '2026-03-01',
            'fecha_fin' => '2026-12-15',
            'modalidad' => 'Presencial',
            'estado' => 'En curso',
        ]);

        Ciclo::query()->updateOrCreate(['nombre_ciclo' => 'Ciclo Repaso Intensivo'], [
            'tipo_ciclo' => 'Repaso',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-09-15',
            'modalidad' => 'Híbrido',
            'estado' => 'Cerrado',
        ]);
    }
}
