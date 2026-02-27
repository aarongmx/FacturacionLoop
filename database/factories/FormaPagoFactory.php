<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FormaPago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormaPago>
 */
final class FormaPagoFactory extends Factory
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
            'bancarizado' => fake()->boolean(),
            'vigencia_inicio' => null,
            'vigencia_fin' => null,
        ];
    }
}
