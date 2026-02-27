# Phase 1: Catálogos SAT Base - Research

**Researched:** 2026-02-27
**Domain:** SAT CFDI 4.0 Catalog Data — Laravel Migrations, Seeders, Filament Resources
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- Load catalog data from official SAT CSV files
- CSVs stored in `database/data/` directory, versioned in git
- User provides the CSV files downloaded from the SAT portal — system does not auto-download
- Seeders read from CSV files using Laravel's filesystem or PHP's fgetcsv
- When SAT publishes updated catalogs, use incremental migration strategy
- Detect differences between old CSV and new CSV, apply only changes (inserts, updates, soft-deletes)
- Do not truncate and re-seed — existing records may be referenced by invoices

### Claude's Discretion

- Filament resource configuration for each catalog (read-only vs editable, columns displayed, search behavior)
- Model structure pattern (shared base class or standalone models)
- Handling large catalogs in Filament (c_ClaveProdServ ~53K rows, c_ClaveUnidad ~2K rows) — search/autocomplete strategy
- Migration and seeder naming conventions following existing project patterns
- Table naming conventions (follow existing catalog models like Currency, Country, etc.)

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| CAT-01 | Sistema tiene catálogo c_RegimenFiscal con ~20 regímenes fiscales del SAT | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, fisica, moral, vigencia_inicio, vigencia_fin |
| CAT-02 | Sistema tiene catálogo c_UsoCFDI con ~30 usos de CFDI válidos | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, aplica_fisica, aplica_moral, vigencia_inicio, vigencia_fin |
| CAT-03 | Sistema tiene catálogo c_FormaPago con ~30 formas de pago del SAT | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, bancarizado, vigencia_inicio, vigencia_fin |
| CAT-04 | Sistema tiene catálogo c_MetodoPago con PUE y PPD | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, vigencia_inicio, vigencia_fin |
| CAT-05 | Sistema tiene catálogo c_TipoDeComprobante con los 6 tipos (I, E, T, N, P, nomina) | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, vigencia_inicio, vigencia_fin |
| CAT-06 | Sistema tiene catálogo c_ClaveProdServ con ~53,000 claves de producto/servicio | XLS sheet in catCFDI_V_4_20260212.xls (large); columns: clave, descripcion, incluye_iva, incluye_ieps, complemento, vigencia_inicio, vigencia_fin, estimulo_franja, palabras_similares |
| CAT-07 | Sistema tiene catálogo c_ClaveUnidad con ~2,000 unidades SAT | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, nombre, descripcion, nota, vigencia_inicio, vigencia_fin, simbolo |
| CAT-08 | Sistema tiene catálogo c_Impuesto con IVA, ISR e IEPS | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, vigencia_inicio, vigencia_fin |
| CAT-09 | Sistema tiene catálogo c_TipoFactor con Tasa, Cuota y Exento | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, vigencia_inicio, vigencia_fin |
| CAT-10 | Sistema tiene catálogo c_TasaOCuota con las tasas de IVA válidas | XLS sheet in catCFDI_V_4_20260212.xls; columns: tipo (fijo/rango), clave, valor_minimo, valor_maximo, impuesto, factor, traslado, retencion, vigencia_inicio, vigencia_fin |
| CAT-11 | Sistema tiene catálogo c_ObjetoImp con los 3 valores (01, 02, 03) | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, vigencia_inicio, vigencia_fin |
| CAT-12 | Sistema tiene catálogo c_TipoRelacion con ~10 tipos de relación entre CFDIs | XLS sheet in catCFDI_V_4_20260212.xls; columns: clave, descripcion, vigencia_inicio, vigencia_fin |
</phase_requirements>

---

## Summary

Phase 1 is a pure data infrastructure phase: create 12 Eloquent models with migrations, seed them from the official SAT catalog files already present in `database/data/catCFDI_V_4_20260212.xls`, and expose them in Filament as read-only (or mostly read-only) resources for browsing.

The existing project already has a working pattern for this: `CustomUnit`, `TariffClassification`, `Currency`, `Country`, `Incoterm`, and `State` models are already created with the same approach (string primary keys, `$incrementing = false`, `$fillable` arrays, PHPDoc relationship hints). The SAT catalog models should follow this exact pattern. All seeders in the codebase are currently empty stubs — the project has the scaffolding but no seeder implementation yet. Phase 1 will be the first time an actual seeder is implemented.

The main technical challenge is the data source: the SAT provides `.xls` (Excel 97-2003 binary) files, not CSVs. The files in `database/data/` are confirmed `.xls` format (MIME: `application/vnd.ms-excel`). Seeders cannot use `fgetcsv` directly on XLS files — they require either a PHP XLS reader library (e.g., `PhpSpreadsheet`) or the XLS files must be pre-converted to CSV. Given the locked decision to use `fgetcsv`, the plan must include an XLS-to-CSV conversion step, or the decision should be clarified. The phpcfdi ecosystem provides a separate tool (`phpcfdi/sat-catalogos-populate`) that converts these XLS files to SQLite via CSVs.

**Primary recommendation:** Convert the XLS files to CSV format (manual or scripted), store the CSVs in `database/data/`, and use `fgetcsv` in seeders to read them. Use `DB::table()->upsert()` for incremental seeding (not truncate-and-insert). For Filament, use `->searchable()` columns with `->deferLoading()` on list pages, and database indexes on the `clave`/`code` and `descripcion` columns for the large catalogs.

---

## Standard Stack

### Core (Already in Project — No New Dependencies)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Eloquent | 12.x | Model/migration/seeder pattern | Already in use; existing catalog models follow this pattern exactly |
| Filament | 5.x | Admin panel resources for browsing catalogs | Already installed; AdminPanelProvider already configured with auto-discovery |
| PHP `fgetcsv` | built-in | Read CSV rows in seeders | Native PHP; no library needed once XLS is converted to CSV |
| `DB::table()->upsert()` | Laravel 12.x | Incremental seeding without truncation | Built-in Laravel; handles INSERT ON CONFLICT DO UPDATE; prevents breaking FK references |

### Supporting (Needed for XLS → CSV Conversion)

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `phpoffice/phpspreadsheet` | ^2.0 | Read XLS/XLSX files in PHP | If converting XLS to CSV programmatically within a seeder or Artisan command |
| Manual Excel export | — | Export each XLS sheet to CSV manually before seeding | Simpler if done once; CSV files committed to `database/data/` |

**Recommendation:** The locked decision says "fgetcsv" — the simplest path is to manually export each relevant sheet from the XLS to a CSV file using LibreOffice or Excel, commit those CSVs to `database/data/`, and then write seeders that read them with `fgetcsv`. Do NOT add `phpoffice/phpspreadsheet` as a production dependency for a one-time data conversion task.

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Manual CSV + fgetcsv | `phpoffice/phpspreadsheet` in seeder | phpspreadsheet adds a dev-only dependency and is slower for large files; manual CSV is simpler and faster at runtime |
| `DB::table()->upsert()` | Truncate + re-insert | Truncate breaks FK constraints from invoices referencing catalog keys |
| Standalone Filament resources | Custom Livewire pages | Filament resources are faster to build and auto-register via AdminPanelProvider |

---

## Architecture Patterns

### Recommended Project Structure

```
app/
└── Models/
    ├── SatRegimenFiscal.php      # CAT-01
    ├── SatUsoCfdi.php             # CAT-02
    ├── SatFormaPago.php           # CAT-03
    ├── SatMetodoPago.php          # CAT-04
    ├── SatTipoDeComprobante.php   # CAT-05
    ├── SatClaveProdServ.php       # CAT-06
    ├── SatClaveUnidad.php         # CAT-07
    ├── SatImpuesto.php            # CAT-08
    ├── SatTipoFactor.php          # CAT-09
    ├── SatTasaOCuota.php          # CAT-10
    ├── SatObjetoImp.php           # CAT-11
    └── SatTipoRelacion.php        # CAT-12

app/Filament/Resources/
    ├── SatRegimenFiscalResource.php
    ├── SatUsoCfdiResource.php
    ├── SatFormaPagoResource.php
    ├── SatMetodoPagoResource.php
    ├── SatTipoDeComprobanteResource.php
    ├── SatClaveProdServResource.php
    ├── SatClaveUnidadResource.php
    ├── SatImpuestoResource.php
    ├── SatTipoFactorResource.php
    ├── SatTasaOCuotaResource.php
    ├── SatObjetoImpResource.php
    └── SatTipoRelacionResource.php

database/migrations/
    └── 2026_*_create_sat_*_table.php (one per catalog)

database/seeders/
    ├── SatRegimenFiscalSeeder.php
    ├── SatUsoCfdiSeeder.php
    ├── SatFormaPagoSeeder.php
    ├── SatMetodoPagoSeeder.php
    ├── SatTipoDeComprobanteSeeder.php
    ├── SatClaveProdServSeeder.php
    ├── SatClaveUnidadSeeder.php
    ├── SatImpuestoSeeder.php
    ├── SatTipoFactorSeeder.php
    ├── SatTasaOCuotaSeeder.php
    ├── SatObjetoImpSeeder.php
    └── SatTipoRelacionSeeder.php

database/data/
    ├── catCFDI_V_4_20260212.xls   # Already present (source of truth)
    ├── c_RegimenFiscal.csv        # To be generated from XLS sheet
    ├── c_UsoCFDI.csv
    ├── c_FormaPago.csv
    ├── c_MetodoPago.csv
    ├── c_TipoDeComprobante.csv
    ├── c_ClaveProdServ.csv
    ├── c_ClaveUnidad.csv
    ├── c_Impuesto.csv
    ├── c_TipoFactor.csv
    ├── c_TasaOCuota.csv
    ├── c_ObjetoImp.csv
    └── c_TipoRelacion.csv

tests/Feature/Models/
    ├── SatRegimenFiscalTest.php
    ├── SatClaveProdServTest.php    # Covers large-catalog search behavior
    └── SatClaveUnidadTest.php
```

**Note on naming:** Existing models do NOT use a prefix (e.g., `Currency` not `SatCurrency`). However, these 12 SAT-specific catalogs are distinguishable by having "SAT" context. Using the `Sat` prefix makes the domain explicit and avoids future naming collisions. Alternatively, follow the exact naming pattern and use the SAT catalog name as the model name: `RegimenFiscal`, `UsoCfdi`, etc. **Recommendation: no prefix, use the SAT name directly** — consistent with existing models (`CustomUnit`, `TariffClassification`, etc.).

### Pattern 1: String Primary Key Catalog Model

All SAT catalogs use a string code (clave) as the natural identifier. Follow the existing `CustomUnit` and `TariffClassification` model pattern exactly.

```php
// Source: Existing app/Models/CustomUnit.php pattern
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RegimenFiscalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Override;

final class RegimenFiscal extends Model
{
    /** @use HasFactory<RegimenFiscalFactory> */
    use HasFactory;

    #[Override]
    public $incrementing = false;

    #[Override]
    protected $primaryKey = 'clave';

    #[Override]
    protected $keyType = 'string';

    #[Override]
    protected $fillable = [
        'clave',
        'descripcion',
        'aplica_fisica',
        'aplica_moral',
        'vigencia_inicio',
        'vigencia_fin',
    ];

    protected function casts(): array
    {
        return [
            'aplica_fisica' => 'boolean',
            'aplica_moral' => 'boolean',
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }
}
```

### Pattern 2: Seeder with fgetcsv + Upsert

Use PHP's built-in `fgetcsv` to stream CSV rows. Use `DB::table()->upsert()` to handle incremental updates without truncating.

```php
// Locked decision: fgetcsv + upsert strategy
final class RegimenFiscalSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/c_RegimenFiscal.csv');
        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle); // skip header row

        $chunk = [];
        while (($row = fgetcsv($handle)) !== false) {
            $chunk[] = [
                'clave'          => $row[0],
                'descripcion'    => $row[1],
                'aplica_fisica'  => $row[2] === 'Sí',
                'aplica_moral'   => $row[3] === 'Sí',
                'vigencia_inicio' => $row[4] ?: null,
                'vigencia_fin'   => $row[5] ?: null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            if (count($chunk) >= 500) {
                DB::table('regimenes_fiscales')->upsert(
                    $chunk,
                    ['clave'],                    // conflict key
                    ['descripcion', 'aplica_fisica', 'aplica_moral', 'vigencia_inicio', 'vigencia_fin', 'updated_at']
                );
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            DB::table('regimenes_fiscales')->upsert($chunk, ['clave'], ['descripcion', 'aplica_fisica', 'aplica_moral', 'vigencia_inicio', 'vigencia_fin', 'updated_at']);
        }

        fclose($handle);
    }
}
```

### Pattern 3: Filament Resource for Catalog Browse (Read-Only)

Small catalogs (< 1,000 rows): simple resource with searchable columns.
Large catalogs (c_ClaveProdServ ~53K, c_ClaveUnidad ~2K): same approach, but require DB indexes on searchable columns and `->deferLoading()`.

```php
// Source: Filament v5 Tables documentation
final class RegimenFiscalResource extends Resource
{
    protected static ?string $model = RegimenFiscal::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Catálogos SAT';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clave')->searchable()->sortable(),
                TextColumn::make('descripcion')->searchable(),
                IconColumn::make('aplica_fisica')->boolean(),
                IconColumn::make('aplica_moral')->boolean(),
            ])
            ->defaultSort('clave')
            ->paginated([25, 50, 100])
            ->deferLoading();  // Important for large catalogs
    }

    // No form() method — read-only resource
    // No create/edit pages
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegimenFiscals::route('/'),
        ];
    }
}
```

### Pattern 4: Migration for SAT Catalog Tables

Follow existing migration conventions (timestamped, `declare(strict_types=1)`, Blueprint closures with type hints).

```php
// Source: Existing migration pattern in database/migrations/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regimenes_fiscales', function (Blueprint $table): void {
            $table->string('clave', 10)->primary();
            $table->string('descripcion');
            $table->boolean('aplica_fisica')->default(false);
            $table->boolean('aplica_moral')->default(false);
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimenes_fiscales');
    }
};
```

For large catalogs, add a search index:
```php
// For clave_prod_servs (~53K rows) — index descripcion for Filament search
$table->index(['descripcion']); // or use fulltext in PostgreSQL
```

### Anti-Patterns to Avoid

- **Truncate-and-reseed:** `DB::table('...')->truncate()` before seeding breaks FK references to catalog keys from CFDI records. Use `upsert()` always.
- **Auto-increment primary key on catalog tables:** SAT catalog keys (e.g., `601`, `G01`, `H`) are the canonical identifiers used in CFDI XML. Using a surrogate integer PK would require a join everywhere. Use the natural string key.
- **Eager loading all 53K rows in Filament:** Do not use `->get()` in a resource query without pagination. Filament paginates by default — do not disable it for large catalogs.
- **Storing all catalog data in one polymorphic table:** Each catalog has different columns (c_TasaOCuota has min/max values, c_RegimenFiscal has física/moral flags). Separate tables per catalog.

---

## Catalog Schema Reference

Based on phpcfdi/sat-catalogos-populate CSV test files and SAT Anexo 20 CFDI 4.0 specification:

### Small Catalogs (< 100 rows — simple table, no performance concerns)

| Catalog | Table Name | Key Columns | Row Count |
|---------|-----------|-------------|-----------|
| c_RegimenFiscal | `regimenes_fiscales` | `clave` (PK), `descripcion`, `aplica_fisica`, `aplica_moral` | ~22 |
| c_UsoCFDI | `usos_cfdi` | `clave` (PK), `descripcion`, `aplica_fisica`, `aplica_moral` | ~30 |
| c_FormaPago | `formas_pago` | `clave` (PK), `descripcion`, `bancarizado` | ~30 |
| c_MetodoPago | `metodos_pago` | `clave` (PK), `descripcion` | 2 (PUE, PPD) |
| c_TipoDeComprobante | `tipos_comprobante` | `clave` (PK), `descripcion` | 6 |
| c_Impuesto | `impuestos` | `clave` (PK), `descripcion` | 3 (IVA, ISR, IEPS) |
| c_TipoFactor | `tipos_factor` | `clave` (PK), `descripcion` | 3 (Tasa, Cuota, Exento) |
| c_TasaOCuota | `tasas_o_cuotas` | composite, `impuesto`, `factor`, `valor_minimo`, `valor_maximo`, `traslado`, `retencion` | ~20 |
| c_ObjetoImp | `objetos_imp` | `clave` (PK), `descripcion` | 3 (01, 02, 03) |
| c_TipoRelacion | `tipos_relacion` | `clave` (PK), `descripcion` | ~10 |

### Large Catalogs (performance considerations apply)

| Catalog | Table Name | Key Columns | Row Count | Special Handling |
|---------|-----------|-------------|-----------|-----------------|
| c_ClaveProdServ | `claves_prod_serv` | `clave` (PK), `descripcion`, `incluye_iva`, `incluye_ieps`, `palabras_similares` | ~53,000 | DB index on `descripcion`; `->deferLoading()` in Filament; search is mandatory |
| c_ClaveUnidad | `claves_unidad` | `clave` (PK), `nombre`, `descripcion`, `simbolo` | ~2,000 | DB index on `nombre`; searchable in Filament |

**Note on c_TasaOCuota:** This catalog is a "rules table" — it does not have a single natural primary key. Each row represents a valid combination of impuesto + factor + rango. Use a composite unique index or a surrogate auto-increment PK.

### c_ClaveProdServ Full Column Set

From phpcfdi/sat-catalogos-populate test CSV header:
```
c_ClaveProdServ, Descripción, Incluir IVA trasladado, Incluir IEPS trasladado,
Complemento que debe incluir, FechaInicioVigencia, FechaFinVigencia,
Estímulo Franja Fronteriza, Palabras similares
```

Store in Laravel migration as:
```php
$table->string('clave')->primary();          // e.g., 01010101
$table->string('descripcion');
$table->string('incluye_iva')->nullable();   // 'Sí', 'No', 'Opcional'
$table->string('incluye_ieps')->nullable();
$table->string('complemento')->nullable();
$table->date('vigencia_inicio')->nullable();
$table->date('vigencia_fin')->nullable();
$table->boolean('estimulo_franja')->default(false);
$table->text('palabras_similares')->nullable();
$table->timestamps();
$table->index('descripcion');
```

### c_ClaveUnidad Full Column Set

From phpcfdi/sat-catalogos-populate test CSV header:
```
c_ClaveUnidad, Nombre, Descripción, Nota, Fecha de inicio de vigencia, Fecha de fin de vigencia, Símbolo
```

Store in Laravel migration as:
```php
$table->string('clave')->primary();    // e.g., KGM, LTR, H87
$table->string('nombre');
$table->string('descripcion')->nullable();
$table->text('nota')->nullable();
$table->date('vigencia_inicio')->nullable();
$table->date('vigencia_fin')->nullable();
$table->string('simbolo', 20)->nullable();
$table->timestamps();
$table->index('nombre');
```

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| XLS file reading | Custom binary XLS parser | Manual CSV export from Excel/LibreOffice (one-time) | XLS binary format is complex; a manual export takes 2 minutes and produces a clean CSV |
| CSV chunked insert | Custom batch processor | `DB::table()->upsert()` in chunks of 500 | Laravel handles the SQL; upsert handles conflicts natively in PostgreSQL |
| Search on large table | Custom AJAX endpoint | Filament `->searchable()` with `->deferLoading()` and a DB index | Filament's built-in search with `LIKE` queries on an indexed column is sufficient for 53K rows |
| Catalog update strategy | Diff algorithm in PHP | `upsert()` with conflict-update strategy | Postgres `ON CONFLICT DO UPDATE` handles the diff at DB level |
| Filament navigation groups | Custom panel configuration | `protected static ?string $navigationGroup = 'Catálogos SAT'` | Built-in Filament grouping |

**Key insight:** This phase is fundamentally data infrastructure. Do not build custom UI, custom search APIs, or custom data parsers. The entire phase should be built using Laravel's standard migration/seeder/model/Filament resource stack with zero new packages.

---

## Common Pitfalls

### Pitfall 1: XLS Files Cannot Be Read with fgetcsv

**What goes wrong:** The locked decision says "use fgetcsv" but the actual files in `database/data/` are `.xls` (binary Excel 97-2003 format). Calling `fgetcsv` on an XLS file will not produce valid CSV rows — it reads garbage bytes.

**Why it happens:** The SAT publishes catalog data in `.xls` format. The CONTEXT.md decision assumed CSV; the actual files are XLS.

**How to avoid:** Convert each relevant sheet from `catCFDI_V_4_20260212.xls` to individual CSV files. This is a one-time manual step (open in LibreOffice/Excel, save sheet as CSV). The resulting CSV files are committed to `database/data/` and are the source for `fgetcsv` in seeders.

**Warning signs:** Seeder outputs garbage data or throws errors when trying to read the XLS file.

### Pitfall 2: Truncate-and-Reseed Breaks Future Invoice References

**What goes wrong:** After the first CFDI invoice is created in Phase 4, it will reference catalog keys (e.g., `regimen_fiscal_clave = '601'`). If the catalog seeder truncates and re-inserts, existing FK references would break (or be orphaned if not FK-constrained).

**Why it happens:** The simplest seeder pattern is `DB::table()->truncate()` then insert. It's taught in most Laravel tutorials.

**How to avoid:** Use `DB::table()->upsert($rows, ['clave'], ['descripcion', ...])` always. This handles new rows, updated descriptions, and ignores unchanged rows.

**Warning signs:** Seeder has `$table->truncate()` anywhere in its body.

### Pitfall 3: c_ClaveProdServ Without DB Index Kills Filament Search

**What goes wrong:** Filament's `->searchable()` generates a `LIKE '%query%'` SQL query. On a 53,000-row table with no index on `descripcion`, this causes a full table scan (~200-500ms per keystroke in the Filament search field).

**Why it happens:** Migrations create tables without thinking about search patterns.

**How to avoid:** Add `$table->index('descripcion')` to the `claves_prod_serv` migration. For PostgreSQL, consider a GIN full-text index for better LIKE performance: `DB::statement("CREATE INDEX claves_prod_serv_descripcion_gin ON claves_prod_serv USING gin(to_tsvector('spanish', descripcion))")`. Start with a simple B-tree index — it is enough for the Phase 1 success criterion.

**Warning signs:** Filament search on c_ClaveProdServ takes > 1 second per keystroke in development.

### Pitfall 4: c_TasaOCuota Has No Natural Single-Column Primary Key

**What goes wrong:** c_TasaOCuota rows are defined by the combination of impuesto + factor + valor. There is no single-column `clave` field. Using a string PK will fail at the migration level.

**Why it happens:** All other catalogs follow the same string PK pattern; developers apply it blindly to c_TasaOCuota.

**How to avoid:** Use a standard auto-increment `id` PK for this table, with a composite unique index on `(impuesto, factor, valor_minimo, valor_maximo, traslado, retencion)`. The upsert key for the seeder uses this composite unique index.

**Warning signs:** Migration for `tasas_o_cuotas` tries to set `$table->string('clave')->primary()` but the CSV has no single-column identifier.

### Pitfall 5: Filament Lists Without Pagination Freeze on Large Catalogs

**What goes wrong:** If `->paginated(false)` is set on the Filament resource (or `'all'` is added to page options), loading 53,000 records at once freezes the browser.

**Why it happens:** Developers disable pagination for "simplicity" or set "all" as the default page size.

**How to avoid:** Keep default Filament pagination (25 records). Add `->deferLoading()` to the table configuration. Do NOT add `'all'` to paginated options for c_ClaveProdServ.

**Warning signs:** Resource list page for c_ClaveProdServ is slow or browser tab crashes.

---

## Code Examples

### Migration: Small Catalog (c_RegimenFiscal)

```php
// Source: Existing migration pattern in database/migrations/2026_02_27_*.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regimenes_fiscales', function (Blueprint $table): void {
            $table->string('clave', 10)->primary();
            $table->string('descripcion');
            $table->boolean('aplica_fisica')->default(false);
            $table->boolean('aplica_moral')->default(false);
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimenes_fiscales');
    }
};
```

### Migration: Large Catalog with Index (c_ClaveProdServ)

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claves_prod_serv', function (Blueprint $table): void {
            $table->string('clave', 20)->primary();
            $table->string('descripcion');
            $table->string('incluye_iva', 20)->nullable();
            $table->string('incluye_ieps', 20)->nullable();
            $table->string('complemento', 100)->nullable();
            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fin')->nullable();
            $table->boolean('estimulo_franja')->default(false);
            $table->text('palabras_similares')->nullable();
            $table->timestamps();
            $table->index('descripcion');  // Required for Filament search performance
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claves_prod_serv');
    }
};
```

### Seeder: Chunked fgetcsv + Upsert

```php
// Source: Laravel 12 DB::table()->upsert() + PHP fgetcsv pattern
final class RegimenFiscalSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('data/c_RegimenFiscal.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("CSV not found: {$csvPath}. Skipping.");
            return;
        }

        $handle = fopen($csvPath, 'r');
        fgetcsv($handle); // skip header

        $chunk = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0])) {
                continue; // skip empty rows
            }

            $chunk[] = [
                'clave'           => trim($row[0]),
                'descripcion'     => trim($row[1]),
                'aplica_fisica'   => trim($row[2]) === 'Sí',
                'aplica_moral'    => trim($row[3]) === 'Sí',
                'vigencia_inicio' => $row[4] !== '' ? $row[4] : null,
                'vigencia_fin'    => isset($row[5]) && $row[5] !== '' ? $row[5] : null,
                'created_at'      => now(),
                'updated_at'      => now(),
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
```

### Filament Resource: Read-Only with Search

```php
// Source: Filament v5 tables documentation
final class RegimenFiscalResource extends Resource
{
    protected static ?string $model = RegimenFiscal::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Catálogos SAT';
    protected static ?string $modelLabel = 'Régimen Fiscal';
    protected static ?string $pluralModelLabel = 'Regímenes Fiscales';

    public static function form(Form $form): Form
    {
        // Read-only: no form fields needed
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('clave')->searchable()->sortable()->label('Clave'),
                TextColumn::make('descripcion')->searchable()->label('Descripción'),
                IconColumn::make('aplica_fisica')->boolean()->label('Persona Física'),
                IconColumn::make('aplica_moral')->boolean()->label('Persona Moral'),
            ])
            ->defaultSort('clave')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegimenFiscals::route('/'),
        ];
    }
}
```

### Artisan Command to Create Resources (Filament v5)

```bash
# Simple resource (list-only, no create/edit pages)
php artisan make:filament-resource RegimenFiscal --no-interaction

# For large catalogs: add --simple for modal-based CRUD (not needed for read-only)
# For read-only: just don't define create/edit pages in getPages()
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Truncate + insert in seeders | `upsert()` with conflict key | Laravel 8+ (always available in L12) | Catalogs can be safely re-seeded without breaking FK references |
| Integer PK for all tables | Natural string PK for catalog tables | Existing project convention | Direct mapping to SAT catalog keys; no surrogate ID join |
| Pagination disabled for "simplicity" | `->deferLoading()` + default pagination | Filament v3+ | Prevents browser freeze on large catalogs |
| Inline seeder data arrays | CSV files in `database/data/` | Project decision | Data source is auditable and versionable; SAT updates visible as diff |

---

## Open Questions

1. **XLS-to-CSV Conversion Responsibility**
   - What we know: The files in `database/data/` are `.xls` (binary), not CSV. The locked decision says `fgetcsv`.
   - What's unclear: Who converts the XLS sheets to CSV? Is it a manual step documented in README? Is there an Artisan command? Or should the seeder use PhpSpreadsheet directly?
   - Recommendation: Add a manual step — the user exports each relevant sheet from `catCFDI_V_4_20260212.xls` to CSV, names it by catalog (e.g., `c_RegimenFiscal.csv`), and places it in `database/data/`. Document this in the DatabaseSeeder comments. This keeps no new production dependencies.

2. **Table Naming Convention — Plural of SAT Catalog Names**
   - What we know: Existing tables use English plurals (`currencies`, `countries`, `custom_units`). SAT catalog names are Spanish.
   - What's unclear: Should SAT catalog tables be English (`tax_regimes`) or Spanish (`regimenes_fiscales`) or abbreviated SAT names (`c_regimen_fiscal`)?
   - Recommendation: Use descriptive Spanish plurals that match the SAT catalog meaning (`regimenes_fiscales`, `usos_cfdi`, `formas_pago`) — this is unambiguous and consistent with the domain vocabulary. Avoid the `c_` prefix for table names.

3. **Model Naming — Prefix or Not?**
   - What we know: Existing models use clean names without prefix (`Currency`, `CustomUnit`).
   - What's unclear: Should the 12 SAT models get a `Sat` prefix to distinguish them from future generic models?
   - Recommendation: No prefix. Use the catalog name directly: `RegimenFiscal`, `UsoCfdi`, `FormaPago`, `MetodoPago`, `TipoDeComprobante`, `ClaveProdServ`, `ClaveUnidad`, `Impuesto`, `TipoFactor`, `TasaOCuota`, `ObjetoImp`, `TipoRelacion`. If a naming collision ever occurs (unlikely), the domain context is sufficient.

4. **DatabaseSeeder Registration**
   - What we know: `DatabaseSeeder.php` currently only creates the test user.
   - What's unclear: Should catalog seeders be called from `DatabaseSeeder::run()` or run independently with `php artisan db:seed --class=RegimenFiscalSeeder`?
   - Recommendation: Register all 12 catalog seeders in `DatabaseSeeder::run()` via `$this->call([...])`. This ensures a fresh environment (new deployment, CI) is fully seeded in one command.

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest 4.4.1 |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --compact --filter=Models/Sat` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| CAT-01 | RegimenFiscal model can be created and has clave PK | Feature | `php artisan test --compact --filter=RegimenFiscalTest` | ❌ Wave 0 |
| CAT-02 | UsoCfdi model can be created | Feature | `php artisan test --compact --filter=UsoCfdiTest` | ❌ Wave 0 |
| CAT-03 | FormaPago model can be created | Feature | `php artisan test --compact --filter=FormaPagoTest` | ❌ Wave 0 |
| CAT-04 | MetodoPago model can be created | Feature | `php artisan test --compact --filter=MetodoPagoTest` | ❌ Wave 0 |
| CAT-05 | TipoDeComprobante model can be created | Feature | `php artisan test --compact --filter=TipoDeComprobanteTest` | ❌ Wave 0 |
| CAT-06 | ClaveProdServ is searchable by descripcion; search returns matching clave | Feature | `php artisan test --compact --filter=ClaveProdServTest` | ❌ Wave 0 |
| CAT-07 | ClaveUnidad is searchable by nombre; search returns matching clave | Feature | `php artisan test --compact --filter=ClaveUnidadTest` | ❌ Wave 0 |
| CAT-08 | Impuesto model can be created | Feature | `php artisan test --compact --filter=ImpuestoTest` | ❌ Wave 0 |
| CAT-09 | TipoFactor model can be created | Feature | `php artisan test --compact --filter=TipoFactorTest` | ❌ Wave 0 |
| CAT-10 | TasaOCuota model can be created | Feature | `php artisan test --compact --filter=TasaOCuotaTest` | ❌ Wave 0 |
| CAT-11 | ObjetoImp model can be created | Feature | `php artisan test --compact --filter=ObjetoImpTest` | ❌ Wave 0 |
| CAT-12 | TipoRelacion model can be created | Feature | `php artisan test --compact --filter=TipoRelacionTest` | ❌ Wave 0 |

**Filament resource browsing (success criteria 1–3) is manual-only:** Filament UI tests require browser testing infrastructure (Dusk) not installed in this project. The success criterion "A Filament user can navigate..." is verified manually.

### Sampling Rate

- **Per task commit:** `php artisan test --compact --filter=Models/Sat`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps

All test files need to be created. Minimum required for Phase 1:

- [ ] `tests/Feature/Models/RegimenFiscalTest.php` — covers CAT-01: model creation, string PK, factory
- [ ] `tests/Feature/Models/ClaveProdServTest.php` — covers CAT-06: model creation, search query returns results
- [ ] `tests/Feature/Models/ClaveUnidadTest.php` — covers CAT-07: model creation, search query
- [ ] Individual model tests for CAT-02 through CAT-05, CAT-08 through CAT-12 (lightweight, factory-based)

---

## Sources

### Primary (HIGH confidence)

- Existing project models `app/Models/CustomUnit.php`, `app/Models/TariffClassification.php` — string PK pattern
- Existing migrations `database/migrations/2026_02_27_*.php` — migration structure pattern
- Existing factories `database/factories/CustomUnitFactory.php` — factory pattern
- Existing `app/Providers/Filament/AdminPanelProvider.php` — Filament auto-discovery config
- `phpcfdi/sat-catalogos-populate` test CSVs — confirmed column structures for c_ClaveProdServ, c_ClaveUnidad, c_RegimenFiscal, c_UsoCFDI, c_TasaOCuota
- Filament v5 Tables documentation at `https://filamentphp.com/docs/5.x/tables/overview` — search, pagination, deferLoading

### Secondary (MEDIUM confidence)

- WebSearch: Filament large dataset performance — confirmed deferLoading + pagination + DB indexes as the recommended approach
- WebSearch: Laravel fgetcsv + upsert pattern for large CSV seeding — confirmed chunked approach with 500-row batches
- WebSearch: SAT catálogos CFDI 4.0 structure — confirmed catalog names, approximate row counts, and that catCFDI_V_4 contains all 12 catalogs for Phase 1

### Tertiary (LOW confidence)

- c_TasaOCuota exact column count in CFDI 4.0 version — test CSV showed CFDI 3.3 format; CFDI 4.0 version may have additional columns. Verify from actual XLS sheet before writing migration.
- c_FormaPago exact columns — not directly fetched from CSV; assumed similar to other small catalogs (clave, descripcion, bancarizado). Verify from XLS.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — no new dependencies; uses existing Laravel/Filament patterns already in project
- Architecture: HIGH — follows exact existing model/migration/seeder/resource patterns from the codebase
- Pitfalls: HIGH — XLS-vs-CSV is a verified fact (file exists, confirmed binary format); upsert vs truncate is a locked decision; performance pitfalls confirmed by Filament community
- Catalog schemas: MEDIUM — derived from phpcfdi test CSVs (CFDI 3.3 base); CFDI 4.0 variants may add columns. Verify from actual XLS before writing migrations.

**Research date:** 2026-02-27
**Valid until:** 2026-05-27 (catalog structure is stable; SAT catalog updates are content-only, not schema changes)
