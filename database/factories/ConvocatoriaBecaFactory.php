<?php

namespace Database\Factories;

use App\Models\ConvocatoriaBeca;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConvocatoriaBecaFactory extends Factory
{
    protected $model = ConvocatoriaBeca::class;

    public function definition(): array
    {
        return ['nombre' => 'Examen de becas '.fake()->unique()->numerify('###'), 'descripcion' => 'Inscripción de postulantes por grupos.', 'fecha_inicio' => today('America/Lima')->subDay(), 'fecha_fin' => today('America/Lima')->addWeek(), 'examen_becas_activo' => false, 'fecha_examen' => null, 'hora_inicio_examen' => null, 'hora_fin_examen' => null, 'puntaje_minimo' => '50.00', 'estado' => 'Borrador'];
    }
}
