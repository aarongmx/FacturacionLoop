---
phase: 01-cat-logos-sat-base
plan: 02
subsystem: database
tags: [laravel, seeders, csv, phpspreadsheet, sat, cfdi, postgresql, upsert]

# Dependency graph
requires:
  - phase: 01-01
    provides: 12 SAT catalog migrations and Eloquent models
provides:
  - Artisan command sat:export-csv converting catCFDI XLS to 12 normalized CSV files
  - 12 seeder classes using fgetcsv + DB::table()->upsert() — idempotent, no truncate
  - All 12 SAT catalog tables fully seeded from real SAT CFDI 4.0 data
  - phpoffice/phpspreadsheet installed as dev dependency for XLS reading
affects: [filament-resources, cfdi-form, invoice-creation, cfdi-xml-generation]

# Tech tracking
tech-stack:
  added:
    - phpoffice/phpspreadsheet ^5.4 (dev dependency, used only by sat:export-csv command)
  patterns:
    - "Seeder pattern: declare(strict_types=1), final class, fgetcsv loop, 500-row chunks, DB::table()->upsert(), guard with file_exists(), skip empty rows"
    - "Boolean CSV normalization: 'Sí'/'Si'/1 → 'true', else → 'false' in export; seeder reads ($row[N] === 'true') for booleans"
    - "Date CSV normalization: Excel serial/M/D/YYYY → Y-m-d in export; seeder reads date string directly, empty → null"
    - "sat:export-csv: reusable command for SAT catalog updates — re-run when SAT publishes new XLS, commit CSVs, seed"

key-files:
  created:
    - app/Console/Commands/SatExportCsv.php
    - database/data/c_RegimenFiscal.csv
    - database/data/c_UsoCFDI.csv
    - database/data/c_FormaPago.csv
    - database/data/c_MetodoPago.csv
    - database/data/c_TipoDeComprobante.csv
    - database/data/c_Impuesto.csv
    - database/data/c_TipoFactor.csv
    - database/data/c_ObjetoImp.csv
    - database/data/c_TipoRelacion.csv
    - database/data/c_ClaveProdServ.csv
    - database/data/c_ClaveUnidad.csv
    - database/data/c_TasaOCuota.csv
    - database/seeders/RegimenFiscalSeeder.php
    - database/seeders/UsoCfdiSeeder.php
    - database/seeders/FormaPagoSeeder.php
    - database/seeders/MetodoPagoSeeder.php
    - database/seeders/TipoDeComprobanteSeeder.php
    - database/seeders/ImpuestoSeeder.php
    - database/seeders/TipoFactorSeeder.php
    - database/seeders/ObjetoImpSeeder.php
    - database/seeders/TipoRelacionSeeder.php
    - database/seeders/ClaveProdServSeeder.php
    - database/seeders/ClaveUnidadSeeder.php
    - database/seeders/TasaOCuotaSeeder.php
  modified:
    - database/seeders/DatabaseSeeder.php
    - database/migrations/2026_02_27_200010_create_claves_unidad_table.php
    - composer.json
    - composer.lock

key-decisions:
  - "Task 1 checkpoint (manual XLS export) replaced by sat:export-csv Artisan command per updated CONTEXT.md — automatic, repeatable, version-controllable"
  - "TipoFactor CSV has no descripcion column; seeder uses clave as descripcion (Tasa/Cuota/Exento are self-describing values)"
  - "TasaOCuota impuesto XLS column contains text names (IVA/IEPS/ISR); sat:export-csv maps these to numeric c_Impuesto claves (002/003/001) at export time"
  - "claves_unidad.descripcion changed from string(255) to text() — SAT descriptions up to 565 chars in XLS"
  - "claves_unidad.simbolo changed from string(20) to string(50) — some SAT unit symbols like BtuIT·ft/(h·ft²·°F) are 24 chars"

patterns-established:
  - "XLS-to-CSV export pattern: phpspreadsheet reads XLS, SatExportCsv normalizes booleans/dates/text, writes UTF-8 BOM CSVs — seeders never touch XLS directly"
  - "Catalog update workflow: replace XLS → php artisan sat:export-csv → git commit CSVs → php artisan db:seed (upsert, safe)"

requirements-completed: [CAT-01, CAT-02, CAT-03, CAT-04, CAT-05, CAT-06, CAT-07, CAT-08, CAT-09, CAT-10, CAT-11, CAT-12]

# Metrics
duration: 16min
completed: 2026-02-27
---

# Phase 1 Plan 2: SAT Catalog CSV Export and Seeders Summary

**Artisan sat:export-csv command converts SAT CFDI 4.0 XLS to 12 normalized CSVs; 12 fgetcsv+upsert seeders populate all catalog tables (52,513 claves_prod_serv, 2,418 claves_unidad) with idempotent upsert**

## Performance

- **Duration:** 16 min
- **Started:** 2026-02-27T20:04:33Z
- **Completed:** 2026-02-27T20:20:45Z
- **Tasks:** 3 of 3 (Task 0 added as deviation + Tasks 2 and 3 from plan)
- **Files modified:** 29 files (1 command + 12 CSVs + 12 seeders + DatabaseSeeder + 1 migration + composer.json + composer.lock)

## Accomplishments
- Created `sat:export-csv` Artisan command that automatically converts all 12 SAT catalog sheets from XLS to UTF-8 normalized CSVs (replaces original manual export checkpoint)
- All 12 CSV files committed to git with booleans as `true`/`false`, dates as `Y-m-d`, TasaOCuota impuesto text names mapped to numeric codes
- 12 seeder classes implemented with fgetcsv + 500-row chunked upsert — idempotent, no truncate, file_exists guard
- `php artisan migrate:fresh --seed` runs successfully in ~7 seconds for small catalogs + 6 seconds for ClaveProdServ (52,513 rows)

## Task Commits

Task 0 (deviation - XLS export command per CONTEXT.md):
1. **Deviation 0a: sat:export-csv command + phpspreadsheet** - `dcacd62` (feat)
2. **Deviation 0b: commit 12 generated CSV files** - `7a1a171` (chore)

Planned tasks:
3. **Task 2: Create 12 SAT catalog seeders** - `4cecf23` (feat)
4. **Task 3: Register seeders in DatabaseSeeder + run migrate:fresh --seed** - `b870327` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `app/Console/Commands/SatExportCsv.php` - Artisan command for XLS→CSV export with boolean/date/impuesto normalization
- `database/data/c_RegimenFiscal.csv` - 19 rows; regimenes_fiscales seed data
- `database/data/c_UsoCFDI.csv` - 24 rows; usos_cfdi seed data
- `database/data/c_FormaPago.csv` - 22 rows; formas_pago seed data (M/N date columns, not D/E as originally expected)
- `database/data/c_MetodoPago.csv` - 2 rows; metodos_pago seed data
- `database/data/c_TipoDeComprobante.csv` - 5 rows; tipos_comprobante seed data
- `database/data/c_Impuesto.csv` - 3 rows; impuestos seed data
- `database/data/c_TipoFactor.csv` - 3 rows; tipos_factor seed data (no descripcion in XLS)
- `database/data/c_ObjetoImp.csv` - 8 rows; objetos_imp seed data
- `database/data/c_TipoRelacion.csv` - 7 rows; tipos_relacion seed data
- `database/data/c_ClaveProdServ.csv` - 52,513 rows; claves_prod_serv seed data
- `database/data/c_ClaveUnidad.csv` - 2,418 rows; claves_unidad seed data
- `database/data/c_TasaOCuota.csv` - 19 rows; tasas_o_cuotas seed data (impuesto mapped to numeric codes)
- `database/seeders/RegimenFiscalSeeder.php` through `TasaOCuotaSeeder.php` - 12 seeders (fgetcsv + upsert)
- `database/seeders/DatabaseSeeder.php` - Calls all 12 catalog seeders in dependency-safe order
- `database/migrations/2026_02_27_200010_create_claves_unidad_table.php` - Fixed column types (text/string50)

## Decisions Made
- Replaced manual XLS export checkpoint with `sat:export-csv` Artisan command (CONTEXT.md decision)
- TipoFactor XLS has no descripcion column; seeder uses clave value as descripcion (self-describing)
- TasaOCuota XLS stores impuesto as text (IVA/IEPS/ISR) — export command maps to numeric c_Impuesto claves
- FormaPago has 14 columns in XLS (not 5); date columns are M and N, not D and E

## Deviations from Plan

### Planned Task Replaced

**Deviation: Task 1 checkpoint superseded by Artisan command**
- **Original:** `type="checkpoint:human-action"` — user manually exports XLS sheets to CSV in LibreOffice
- **Replacement:** `sat:export-csv` Artisan command automatically reads XLS and exports all 12 sheets with normalization
- **Reason:** Updated CONTEXT.md explicitly specified this approach before plan execution
- **Impact:** Zero scope creep — cleaner, repeatable, version-controlled

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed claves_unidad column sizes too small for SAT data**
- **Found during:** Task 3 (migrate:fresh --seed)
- **Issue:** `descripcion` varchar(255) — SAT data has descriptions up to 565 chars. `simbolo` varchar(20) — some SAT unit symbols like `BtuIT·ft/(h·ft²·°F)` are 24 chars
- **Fix:** Changed `descripcion` to `text()`, `simbolo` to `string(50)` in the create migration
- **Files modified:** `database/migrations/2026_02_27_200010_create_claves_unidad_table.php`
- **Verification:** `migrate:fresh --seed` completed with all 2,418 claves_unidad rows inserted
- **Committed in:** b870327 (Task 3 commit)

**2. [Rule 3 - Blocking] Fixed PhpSpreadsheet API — isDateTimeValue() → isDateTime()**
- **Found during:** Deviation Task 0 (running sat:export-csv)
- **Issue:** `Date::isDateTimeValue($cell)` doesn't exist in phpspreadsheet v5; method is `Date::isDateTime($cell)`
- **Fix:** Updated method call in SatExportCsv.php
- **Files modified:** `app/Console/Commands/SatExportCsv.php`
- **Verification:** Command ran successfully exporting all 12 sheets
- **Committed in:** dcacd62

**3. [Rule 1 - Bug] Fixed FormaPago column mapping — date columns are M/N not D/E**
- **Found during:** Deviation Task 0 (verifying CSV output)
- **Issue:** FormaPago XLS has 14 columns (A-N): A=clave, B=descripcion, C=bancarizado, D-L=extra payment fields, M=vigencia_inicio, N=vigencia_fin — original config had D and E as dates
- **Fix:** Updated SatExportCsv sheetConfig for c_FormaPago to use columns M and N for dates
- **Files modified:** `app/Console/Commands/SatExportCsv.php`
- **Verification:** c_FormaPago.csv shows valid dates in columns 3 and 4
- **Committed in:** dcacd62

---

**Total deviations:** 1 planned task replaced + 3 auto-fixed (2 bugs, 1 blocking)
**Impact on plan:** All fixes necessary for correctness. The column size fix was a data-discovery issue from actual SAT data. No scope creep.

## Issues Encountered
- `storage/logs/laravel.log` owned by root after previous Sail run — fixed ownership via `docker exec -u root` before running Artisan commands
- PhpSpreadsheet v5 API change: `Date::isDateTimeValue()` renamed to `Date::isDateTime()` — caught immediately from error output

## User Setup Required

None - no external service configuration required. Run `php artisan sat:export-csv` to regenerate CSVs when SAT publishes updated catalogs.

## Next Phase Readiness
- All 12 SAT CFDI 4.0 catalog tables populated with real SAT data
- Filament resources (plan 03) can now display actual catalog data instead of empty tables
- When SAT publishes updated catalogs: replace XLS → `php artisan sat:export-csv` → commit CSVs → `php artisan db:seed`
- TipoFactor descripcion = clave (Tasa/Cuota/Exento) — future phase may want to enhance this if needed

---
*Phase: 01-cat-logos-sat-base*
*Completed: 2026-02-27*
