<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TipoFactor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoFactor>
 */
final class TipoFactorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave' => fake()->unique()->randomElement(['Tasa', 'Cuota', 'Exento']),
            'descripcion' => fake()->sentence(3),
            'vigencia_inicio' => null,
            'vigencia_fin' => null,
        ];
    }
}
