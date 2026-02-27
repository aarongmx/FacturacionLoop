<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomUnit;
use App\Models\TariffClassification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TariffClassification>
 */
final class TariffClassificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Z]{3}'),
            'name' => fake()->word(),
            'custom_unit_code' => CustomUnit::factory(),
        ];
    }
}
