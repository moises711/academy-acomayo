<?php

namespace Database\Seeders;

use App\Models\Ciclo;
use App\Models\PagoDocente;
use App\Models\Profesor;
use App\Models\User;
use Illuminate\Database\Seeder;

class PagoDocenteSeeder extends Seeder
{
    public function run(): void
    {
        $profesor = Profesor::query()->orderBy('id_profesor')->first();
        $cashier = User::query()->where('email', 'caja@academia.test')->first();

        $ciclo = Ciclo::query()->orderBy('id_ciclo')->first();

        if (! $ciclo || ! $profesor || ! $cashier) {
            return;
        }

        PagoDocente::query()->updateOrCreate(
            ['nro_comprobante' => 'HON-2026-001'],
            [
                'id_ciclo' => $ciclo->id_ciclo,
                'id_profesor' => $profesor->id_profesor,
                'id_usuario' => $cashier->id_usuario,
                'monto' => '1200.00',
                'fecha_pago' => now()->startOfMonth()->toDateString(),
                'periodo' => now()->format('Y-m'),
                'metodo_pago' => 'Transferencia',
                'estado' => 'valido',
            ],
        );
    }
}
