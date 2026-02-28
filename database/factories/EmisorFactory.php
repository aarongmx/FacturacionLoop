<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Emisor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Emisor> */
final class EmisorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rfc' => fake()->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'razon_social' => fake()->company(),
            'domicilio_fiscal_cp' => fake()->numerify('#####'),
            'logo_path' => null,
        ];
    }
}
