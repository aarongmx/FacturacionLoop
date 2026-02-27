<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClaveUnidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaveUnidad>
 */
final class ClaveUnidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave' => fake()->unique()->regexify('[A-Z][A-Z0-9]{2}'),
            'nombre' => fake()->word(),
            'descripcion' => null,
            'nota' => null,
            'vigencia_inicio' => null,
            'vigencia_fin' => null,
            'simbolo' => null,
        ];
    }
}
