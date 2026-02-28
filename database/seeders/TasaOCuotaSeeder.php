<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class TasaOCuotaSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/c_TasaOCuota.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn(sprintf('CSV not found: %s. Skipping.', $csvPath));

            return;
        }

        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            $this->command->error('Cannot open: '.$csvPath);

            return;
        }

        fgetcsv($handle, escape: '\\'); // skip header row

        $chunk = [];
        $now = now();

        // Composite unique key: ['impuesto', 'factor', 'valor_minimo', 'valor_maximo', 'traslado', 'retencion']
        $uniqueBy = ['impuesto', 'factor', 'valor_minimo', 'valor_maximo', 'traslado', 'retencion'];

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $impuesto = mb_trim($row[3] ?? '');

            if ($impuesto === '') {
                continue;
            }

            $chunk[] = [
                'tipo' => ($row[0] ?? '') !== '' ? mb_trim($row[0]) : null,
                'valor_minimo' => mb_trim($row[1] ?? ''),
                'valor_maximo' => mb_trim($row[2] ?? ''),
                'impuesto' => $impuesto,
                'factor' => mb_trim($row[4] ?? ''),
                'traslado' => ($row[5] ?? '') === 'true',
                'retencion' => ($row[6] ?? '') === 'true',
                'vigencia_inicio' => ($row[7] ?? '') !== '' ? $row[7] : null,
                'vigencia_fin' => ($row[8] ?? '') !== '' ? $row[8] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('tasas_o_cuotas')->upsert(
                    $chunk,
                    uniqueBy: $uniqueBy,
                    update: ['tipo', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
                );
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('tasas_o_cuotas')->upsert(
                $chunk,
                uniqueBy: $uniqueBy,
                update: ['tipo', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
            );
        }

        fclose($handle);
    }
}
