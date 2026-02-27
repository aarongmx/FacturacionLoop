<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TasaOCuota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TasaOCuota>
 */
final class TasaOCuotaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo' => null,
            'valor_minimo' => '0.160000',
            'valor_maximo' => '0.160000',
            'impuesto' => fake()->randomElement(['001', '002', '003']),
            'factor' => fake()->randomElement(['Tasa', 'Cuota']),
            'traslado' => fake()->boolean(),
            'retencion' => fake()->boolean(),
            'vigencia_inicio' => null,
            'vigencia_fin' => null,
        ];
    }
}
