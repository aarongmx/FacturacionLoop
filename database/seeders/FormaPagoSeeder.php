<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class FormaPagoSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/c_FormaPago.csv');

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
                'descripcion' => mb_trim($row[1] ?? ''),
                'bancarizado' => ($row[2] ?? '') === 'true',
                'vigencia_inicio' => ($row[3] ?? '') !== '' ? $row[3] : null,
                'vigencia_fin' => ($row[4] ?? '') !== '' ? $row[4] : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('formas_pago')->upsert(
                    $chunk,
                    uniqueBy: ['clave'],
                    update: ['descripcion', 'bancarizado', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
                );
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('formas_pago')->upsert(
                $chunk,
                uniqueBy: ['clave'],
                update: ['descripcion', 'bancarizado', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
            );
        }

        fclose($handle);
    }
}
