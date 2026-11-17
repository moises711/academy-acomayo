<?php

namespace Database\Factories;

use App\Models\Ciclo;
use App\Models\PagoDocente;
use App\Models\Profesor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagoDocente>
 */
class PagoDocenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_ciclo' => fn () => Ciclo::query()->firstOrCreate(['nombre_ciclo' => 'Ciclo de honorarios 2026'], ['tipo_ciclo' => 'Anual', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-12-31', 'modalidad' => 'Presencial', 'estado' => 'En curso'])->id_ciclo,
            'id_profesor' => Profesor::factory(),
            'id_usuario' => User::factory(),
            'monto' => '850.00',
            'fecha_pago' => now()->toDateString(),
            'periodo' => now()->format('Y-m'),
            'metodo_pago' => 'Transferencia',
            'nro_comprobante' => fake()->unique()->bothify('PAG-########'),
            'estado' => 'valido',
            'motivo_anulacion' => null,
            'anulado_por' => null,
            'anulado_en' => null,
        ];
    }
}
