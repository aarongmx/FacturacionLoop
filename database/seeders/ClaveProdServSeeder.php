<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ClaveProdServSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/c_ClaveProdServ.csv');

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
                'incluye_iva' => ($row[2] ?? '') !== '' ? mb_trim($row[2]) : null,
                'incluye_ieps' => ($row[3] ?? '') !== '' ? mb_trim($row[3]) : null,
                'complemento' => ($row[4] ?? '') !== '' ? mb_trim($row[4]) : null,
                'vigencia_inicio' => ($row[5] ?? '') !== '' ? $row[5] : null,
                'vigencia_fin' => ($row[6] ?? '') !== '' ? $row[6] : null,
                'estimulo_franja' => ($row[7] ?? '') === 'true',
                'palabras_similares' => ($row[8] ?? '') !== '' ? mb_trim($row[8]) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('claves_prod_serv')->upsert(
                    $chunk,
                    uniqueBy: ['clave'],
                    update: ['descripcion', 'incluye_iva', 'incluye_ieps', 'complemento', 'vigencia_inicio', 'vigencia_fin', 'estimulo_franja', 'palabras_similares', 'updated_at']
                );
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('claves_prod_serv')->upsert(
                $chunk,
                uniqueBy: ['clave'],
                update: ['descripcion', 'incluye_iva', 'incluye_ieps', 'complemento', 'vigencia_inicio', 'vigencia_fin', 'estimulo_franja', 'palabras_similares', 'updated_at']
            );
        }

        fclose($handle);
    }
}
