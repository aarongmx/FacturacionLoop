<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CsdStatus;
use App\Models\Csd;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Csd> */
final class CsdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'no_certificado' => fake()->unique()->numerify('####################'),
            'rfc' => fake()->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'),
            'fecha_inicio' => now()->subYear(),
            'fecha_fin' => now()->addYears(3),
            'status' => CsdStatus::Inactive,
            'key_path' => 'csd/'.fake()->uuid().'.key.enc',
            'passphrase_encrypted' => fake()->password(8, 20),
            'cer_path' => 'csd/'.fake()->uuid().'.cer',
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CsdStatus::Active,
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CsdStatus::ExpiringSoon,
            'fecha_fin' => now()->addDays(30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CsdStatus::Expired,
            'fecha_fin' => now()->subDay(),
        ]);
    }
}
