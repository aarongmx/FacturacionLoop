<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class TipoFactorSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/c_TipoFactor.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("CSV not found: {$csvPath}. Skipping.");

            return;
        }

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            $this->command->error("Cannot open: {$csvPath}");

            return;
        }

        fgetcsv($handle); // skip header row

        $chunk = [];
        $now = now();

        while (($row = fgetcsv($handle)) !== false) {
            $clave = mb_trim($row[0] ?? '');

            if ($clave === '') {
                continue;
            }

            $chunk[] = [
                'clave' => $clave,
                // c_TipoFactor has no descripcion column in the SAT XLS; the clave is self-describing (Tasa/Cuota/Exento)
                'descripcion' => $clave,
                'vigencia_inicio' => ($row[1] ?? '') !== '' ? $row[1] : null,
                'vigencia_fin' => ($row[2] ?? '') !== '' ? $row[2] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('tipos_factor')->upsert(
                    $chunk,
                    uniqueBy: ['clave'],
                    update: ['descripcion', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
                );
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('tipos_factor')->upsert(
                $chunk,
                uniqueBy: ['clave'],
                update: ['descripcion', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
            );
        }

        fclose($handle);
    }
}
