<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class RegimenFiscalSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/c_RegimenFiscal.csv');

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

        while (($row = fgetcsv($handle, escape: '\\')) !== false) {
            $clave = mb_trim($row[0] ?? '');

            if ($clave === '') {
                continue;
            }

            $chunk[] = [
                'clave' => $clave,
                'descripcion' => mb_trim($row[1] ?? ''),
                'aplica_fisica' => ($row[2] ?? '') === 'true',
                'aplica_moral' => ($row[3] ?? '') === 'true',
                'vigencia_inicio' => ($row[4] ?? '') !== '' ? $row[4] : null,
                'vigencia_fin' => ($row[5] ?? '') !== '' ? $row[5] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('regimenes_fiscales')->upsert(
                    $chunk,
                    uniqueBy: ['clave'],
                    update: ['descripcion', 'aplica_fisica', 'aplica_moral', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
                );
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('regimenes_fiscales')->upsert(
                $chunk,
                uniqueBy: ['clave'],
                update: ['descripcion', 'aplica_fisica', 'aplica_moral', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
            );
        }

        fclose($handle);
    }
}
