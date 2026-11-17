<?php

namespace Database\Factories;

use App\Models\Profesor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profesor>
 */
class ProfesorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dni' => fake()->unique()->numerify('########'),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => fake()->numerify('9########'),
            'estado' => 'activo',
        ];
    }
}
