<?php

namespace Database\Factories;

use App\Models\ConvocatoriaBeca;
use App\Models\PostulanteBeca;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostulanteBecaFactory extends Factory
{
    protected $model = PostulanteBeca::class;

    public function definition(): array
    {
        return ['id_convocatoria' => ConvocatoriaBeca::factory(), 'dni' => fake()->unique()->numerify('########'), 'telefono' => fake()->numerify('9########'), 'grupo' => fake()->randomElement(['A', 'B', 'C', 'D']), 'estado' => 'Inscrito'];
    }
}
