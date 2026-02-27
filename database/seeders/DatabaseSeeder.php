<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            RegimenFiscalSeeder::class,
            UsoCfdiSeeder::class,
            FormaPagoSeeder::class,
            MetodoPagoSeeder::class,
            TipoDeComprobanteSeeder::class,
            ImpuestoSeeder::class,      // Must run before TasaOCuotaSeeder (impuesto FK reference)
            TipoFactorSeeder::class,
            TasaOCuotaSeeder::class,    // References c_Impuesto claves in impuesto column
            ObjetoImpSeeder::class,
            TipoRelacionSeeder::class,
            ClaveProdServSeeder::class,
            ClaveUnidadSeeder::class,
        ]);
    }
}
