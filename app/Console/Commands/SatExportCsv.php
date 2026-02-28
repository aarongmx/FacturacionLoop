<?php

declare(strict_types=1);

namespace App\Console\Commands;

use DateTimeImmutable;
use Exception;
use Illuminate\Console\Command;
use Override;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exports SAT CFDI catalog sheets from the XLS source file to individual CSV files.
 *
 * Normalizations applied during export:
 * - Booleans: 'Sí'/'Si' (with or without accent, case-insensitive) → 'true', '1' → 'true', else → 'false'
 * - Dates: Excel serial numbers or M/D/YYYY strings → 'Y-m-d' format, empty → empty string
 * - TasaOCuota impuesto: text names ('IVA', 'IEPS', 'ISR') → numeric c_Impuesto claves ('001', '002', '003')
 *
 * Usage: php artisan sat:export-csv
 * Re-run whenever SAT publishes updated catalog XLS files.
 */
final class SatExportCsv extends Command
{
    /** @var string */
    #[Override]
    protected $signature = 'sat:export-csv
                            {--source= : Path to the XLS file (default: database/data/catCFDI_V_4_20260212.xls)}
                            {--output= : Output directory for CSV files (default: database/data)}';

    /** @var string */
    #[Override]
    protected $description = 'Export SAT CFDI catalog sheets from XLS to normalized CSV files in database/data/';

    /**
     * Sheet extraction configuration.
     *
     * startRow: first row that contains actual data (not headers or metadata).
     * filename: output CSV filename.
     * columns: XLS column letters to extract, in CSV output order.
     * headers: CSV column header names matching the DB column names.
     * booleanCols: columns to normalize to 'true'/'false'.
     * dateCols: columns to normalize to 'Y-m-d'.
     *
     * @var array<string, array{startRow: int, filename: string, columns: list<string>, headers: list<string>, booleanCols: list<string>, dateCols: list<string>}>
     */
    private array $sheetConfig = [
        'c_RegimenFiscal' => [
            'startRow' => 7,
            'filename' => 'c_RegimenFiscal.csv',
            'columns' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'headers' => ['clave', 'descripcion', 'aplica_fisica', 'aplica_moral', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => ['C', 'D'],
            'dateCols' => ['E', 'F'],
        ],
        'c_UsoCFDI' => [
            'startRow' => 7,
            'filename' => 'c_UsoCFDI.csv',
            'columns' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'headers' => ['clave', 'descripcion', 'aplica_fisica', 'aplica_moral', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => ['C', 'D'],
            'dateCols' => ['E', 'F'],
        ],
        'c_FormaPago' => [
            // A=clave, B=descripcion, C=bancarizado, D..L=extra fields (skip), M=vigencia_inicio, N=vigencia_fin
            'startRow' => 7,
            'filename' => 'c_FormaPago.csv',
            'columns' => ['A', 'B', 'C', 'M', 'N'],
            'headers' => ['clave', 'descripcion', 'bancarizado', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => ['C'],
            'dateCols' => ['M', 'N'],
        ],
        'c_MetodoPago' => [
            'startRow' => 7,
            'filename' => 'c_MetodoPago.csv',
            'columns' => ['A', 'B', 'C', 'D'],
            'headers' => ['clave', 'descripcion', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => [],
            'dateCols' => ['C', 'D'],
        ],
        'c_TipoDeComprobante' => [
            // A=clave, B=descripcion, C=valor_maximo (skip), D=empty (skip), E=vigencia_inicio, F=vigencia_fin
            'startRow' => 6,
            'filename' => 'c_TipoDeComprobante.csv',
            'columns' => ['A', 'B', 'E', 'F'],
            'headers' => ['clave', 'descripcion', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => [],
            'dateCols' => ['E', 'F'],
        ],
        'c_Impuesto' => [
            // A=clave, B=descripcion, C=retencion, D=traslado, E=local_federal, F=vigencia_inicio, G=vigencia_fin
            'startRow' => 6,
            'filename' => 'c_Impuesto.csv',
            'columns' => ['A', 'B', 'F', 'G'],
            'headers' => ['clave', 'descripcion', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => [],
            'dateCols' => ['F', 'G'],
        ],
        'c_TipoFactor' => [
            // A=clave (e.g. 'Tasa'), B=vigencia_inicio, C=vigencia_fin — NO descripcion in XLS
            'startRow' => 6,
            'filename' => 'c_TipoFactor.csv',
            'columns' => ['A', 'B', 'C'],
            'headers' => ['clave', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => [],
            'dateCols' => ['B', 'C'],
        ],
        'c_ObjetoImp' => [
            'startRow' => 6,
            'filename' => 'c_ObjetoImp.csv',
            'columns' => ['A', 'B', 'C', 'D'],
            'headers' => ['clave', 'descripcion', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => [],
            'dateCols' => ['C', 'D'],
        ],
        'c_TipoRelacion' => [
            'startRow' => 6,
            'filename' => 'c_TipoRelacion.csv',
            'columns' => ['A', 'B', 'C', 'D'],
            'headers' => ['clave', 'descripcion', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => [],
            'dateCols' => ['C', 'D'],
        ],
        'c_ClaveProdServ' => [
            // A=clave, B=descripcion, C=incluye_iva (string), D=incluye_ieps (string),
            // E=complemento, F=vigencia_inicio, G=vigencia_fin, H=estimulo_franja (0/1), I=palabras_similares
            'startRow' => 6,
            'filename' => 'c_ClaveProdServ.csv',
            'columns' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'],
            'headers' => ['clave', 'descripcion', 'incluye_iva', 'incluye_ieps', 'complemento', 'vigencia_inicio', 'vigencia_fin', 'estimulo_franja', 'palabras_similares'],
            'booleanCols' => ['H'],
            'dateCols' => ['F', 'G'],
        ],
        'c_ClaveUnidad' => [
            'startRow' => 7,
            'filename' => 'c_ClaveUnidad.csv',
            'columns' => ['A', 'B', 'C', 'D', 'E', 'F', 'G'],
            'headers' => ['clave', 'nombre', 'descripcion', 'nota', 'vigencia_inicio', 'vigencia_fin', 'simbolo'],
            'booleanCols' => [],
            'dateCols' => ['E', 'F'],
        ],
        'c_TasaOCuota' => [
            // A=tipo, B=valor_minimo, C=valor_maximo, D=impuesto (text→mapped to code),
            // E=factor, F=traslado, G=retencion, H=vigencia_inicio, I=vigencia_fin
            'startRow' => 7,
            'filename' => 'c_TasaOCuota.csv',
            'columns' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'],
            'headers' => ['tipo', 'valor_minimo', 'valor_maximo', 'impuesto', 'factor', 'traslado', 'retencion', 'vigencia_inicio', 'vigencia_fin'],
            'booleanCols' => ['F', 'G'],
            'dateCols' => ['H', 'I'],
        ],
    ];

    /**
     * Maps descriptive impuesto names in c_TasaOCuota to numeric c_Impuesto claves.
     *
     * @var array<string, string>
     */
    private array $impuestoNameToCode = [
        'IVA' => '002',
        'IVA Crédito aplicado del 50%' => '002',
        'IEPS' => '003',
        'ISR' => '001',
    ];

    public function handle(): int
    {
        $sourcePath = $this->option('source') ?? database_path('data/catCFDI_V_4_20260212.xls');
        $outputDir = $this->option('output') ?? database_path('data');

        if (! file_exists($sourcePath)) {
            $this->error('XLS source file not found: '.$sourcePath);

            return self::FAILURE;
        }

        $this->info('Loading XLS from: '.$sourcePath);
        $this->info('This may take a moment for large sheets (c_ClaveProdServ ~52K rows)...');

        $reader = IOFactory::createReaderForFile($sourcePath);
        $reader->setReadDataOnly(false);
        $reader->setLoadSheetsOnly(array_keys($this->sheetConfig));

        $spreadsheet = $reader->load($sourcePath);

        $exported = 0;
        $failed = 0;

        foreach ($this->sheetConfig as $sheetName => $config) {
            $sheet = $spreadsheet->getSheetByName($sheetName);

            if (! $sheet instanceof Worksheet) {
                $this->warn(sprintf('Sheet not found: %s — skipping.', $sheetName));
                $failed++;

                continue;
            }

            $outputPath = mb_rtrim($outputDir, '/').'/'.$config['filename'];
            $rowsWritten = $this->exportSheet($sheet, $config, $outputPath, $sheetName === 'c_TasaOCuota');

            $this->line(sprintf('  Exported %s → %s (%d rows)', $sheetName, $config['filename'], $rowsWritten));
            $exported++;
        }

        $this->newLine();
        $this->info(sprintf('Done: %d sheets exported, %d skipped.', $exported, $failed));

        if ($failed > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array{startRow: int, filename: string, columns: list<string>, headers: list<string>, booleanCols: list<string>, dateCols: list<string>}  $config
     */
    private function exportSheet(Worksheet $sheet, array $config, string $outputPath, bool $isTasaOCuota = false): int
    {
        $handle = fopen($outputPath, 'w');

        if ($handle === false) {
            $this->error('Cannot write to: '.$outputPath);

            return 0;
        }

        // Write UTF-8 BOM so that Excel opens the file correctly; seeders strip it via fgetcsv
        fwrite($handle, "\xEF\xBB\xBF");

        // Write header row
        fputcsv($handle, $config['headers'], escape: '\\');

        $highestRow = $sheet->getHighestRow();
        $rowsWritten = 0;
        $boolSet = array_flip($config['booleanCols']);
        $dateSet = array_flip($config['dateCols']);

        for ($row = $config['startRow']; $row <= $highestRow; $row++) {
            $outputRow = [];

            foreach ($config['columns'] as $col) {
                $cell = $sheet->getCell($col.$row);
                $outputRow[] = $this->getCellValue($cell, isset($boolSet[$col]), isset($dateSet[$col]));
            }

            // Skip completely empty rows
            $nonEmpty = array_filter($outputRow, fn (string $v): bool => $v !== '');

            if ($nonEmpty === []) {
                continue;
            }

            // Skip rows where the primary key (first column) is empty
            if (mb_trim($outputRow[0]) === '') {
                continue;
            }

            // For TasaOCuota, map impuesto text name to numeric c_Impuesto clave
            if ($isTasaOCuota) {
                $outputRow = $this->normalizeTasaOCuotaRow($outputRow);
            }

            fputcsv($handle, $outputRow, escape: '\\');
            $rowsWritten++;
        }

        fclose($handle);

        return $rowsWritten;
    }

    private function getCellValue(Cell $cell, bool $isBoolean, bool $isDate): string
    {
        if ($isDate) {
            return $this->normalizeDateCell($cell);
        }

        if ($isBoolean) {
            return $this->normalizeBooleanCell($cell);
        }

        return mb_trim($cell->getFormattedValue());
    }

    private function normalizeBooleanCell(Cell $cell): string
    {
        $raw = mb_trim($cell->getFormattedValue());
        $lower = mb_strtolower($raw);

        // Accept 'Sí'/'si' (with or without accent), and numeric 1
        if ($lower === 'sí' || $lower === 'si' || $raw === '1') {
            return 'true';
        }

        return 'false';
    }

    private function normalizeDateCell(Cell $cell): string
    {
        $value = $cell->getValue();

        if (in_array($value, [null, '', 0], true)) {
            return '';
        }

        // PhpSpreadsheet stores dates as floats (Excel serial numbers)
        if (is_numeric($value) && ExcelDate::isDateTime($cell)) {
            try {
                $dateTime = ExcelDate::excelToDateTimeObject((float) $value);

                return $dateTime->format('Y-m-d');
            } catch (Exception) {
                return '';
            }
        }

        // Handle string date formats like M/D/YYYY from getFormattedValue()
        $formatted = mb_trim($cell->getFormattedValue());

        if ($formatted === '' || $formatted === '0') {
            return '';
        }

        foreach (['m/d/Y', 'n/j/Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $formatted);

            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        return $formatted;
    }

    /**
     * Maps the impuesto column in c_TasaOCuota from descriptive text to numeric c_Impuesto clave.
     *
     * @param  list<string>  $row
     * @return list<string>
     */
    private function normalizeTasaOCuotaRow(array $row): array
    {
        // Column index 3 is impuesto (0=tipo, 1=valor_minimo, 2=valor_maximo, 3=impuesto)
        $impuestoText = mb_trim($row[3]);

        if (isset($this->impuestoNameToCode[$impuestoText])) {
            $row[3] = $this->impuestoNameToCode[$impuestoText];
        }

        return $row;
    }
}
