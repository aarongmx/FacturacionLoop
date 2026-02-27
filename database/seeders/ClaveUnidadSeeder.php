<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ClaveUnidadSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/c_ClaveUnidad.csv');

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
                'nombre' => mb_trim($row[1] ?? ''),
                'descripcion' => ($row[2] ?? '') !== '' ? mb_trim($row[2]) : null,
                'nota' => ($row[3] ?? '') !== '' ? mb_trim($row[3]) : null,
                'vigencia_inicio' => ($row[4] ?? '') !== '' ? $row[4] : null,
                'vigencia_fin' => ($row[5] ?? '') !== '' ? $row[5] : null,
                'simbolo' => ($row[6] ?? '') !== '' ? mb_trim($row[6]) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('claves_unidad')->upsert(
                    $chunk,
                    uniqueBy: ['clave'],
                    update: ['nombre', 'descripcion', 'nota', 'vigencia_inicio', 'vigencia_fin', 'simbolo', 'updated_at']
                );
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('claves_unidad')->upsert(
                $chunk,
                uniqueBy: ['clave'],
                update: ['nombre', 'descripcion', 'nota', 'vigencia_inicio', 'vigencia_fin', 'simbolo', 'updated_at']
            );
        }

        fclose($handle);
    }
}
