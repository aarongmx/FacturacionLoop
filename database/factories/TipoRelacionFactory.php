<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TipoRelacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoRelacion>
 */
final class TipoRelacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave' => fake()->unique()->regexify('[0-9]{2}'),
            'descripcion' => fake()->sentence(3),
            'vigencia_inicio' => null,
            'vigencia_fin' => null,
        ];
    }
}
