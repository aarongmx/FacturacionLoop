<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UsoCfdi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UsoCfdi>
 */
final class UsoCfdiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave' => fake()->unique()->regexify('[0-9]{3}'),
            'descripcion' => fake()->sentence(3),
            'aplica_fisica' => fake()->boolean(),
            'aplica_moral' => fake()->boolean(),
            'vigencia_inicio' => null,
            'vigencia_fin' => null,
        ];
    }
}
