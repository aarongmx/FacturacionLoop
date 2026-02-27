<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ClaveProdServ;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaveProdServ>
 */
final class ClaveProdServFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave' => fake()->unique()->numerify('########'),
            'descripcion' => fake()->sentence(4),
            'incluye_iva' => null,
            'incluye_ieps' => null,
            'complemento' => null,
            'vigencia_inicio' => null,
            'vigencia_fin' => null,
            'estimulo_franja' => false,
            'palabras_similares' => null,
        ];
    }
}
