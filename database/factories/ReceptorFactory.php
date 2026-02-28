<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Receptor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Receptor> */
final class ReceptorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rfc' => fake()->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'nombre_fiscal' => fake()->company(),
            'domicilio_fiscal_cp' => fake()->numerify('#####'),
            'regimen_fiscal_clave' => null,
            'uso_cfdi_clave' => null,
        ];
    }

    /** Factory state for persona moral (12-char RFC). */
    public function personaMoral(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rfc' => fake()->regexify('[A-Z]{3}[0-9]{6}[A-Z0-9]{3}'),
        ]);
    }

    /** Factory state for público en general. */
    public function publicoEnGeneral(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rfc' => 'XAXX010101000',
            'nombre_fiscal' => 'PUBLICO EN GENERAL',
            'regimen_fiscal_clave' => '616',
            'uso_cfdi_clave' => 'S01',
        ]);
    }

    /** Factory state for extranjero. */
    public function extranjero(): static
    {
        return $this->state(fn (array $attributes): array => [
            'rfc' => 'XEXX010101000',
        ]);
    }
}
