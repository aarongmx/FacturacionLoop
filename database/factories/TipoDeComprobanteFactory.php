<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TipoDeComprobante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoDeComprobante>
 */
final class TipoDeComprobanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clave' => fake()->unique()->regexify('[A-Z]'),
            'descripcion' => fake()->sentence(3),
            'vigencia_inicio' => null,
            'vigencia_fin' => null,
        ];
    }
}
