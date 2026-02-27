# Phase 1: Catálogos SAT Base - Context

**Gathered:** 2026-02-27
**Updated:** 2026-02-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Seed all 12 missing SAT catalog tables (c_RegimenFiscal, c_UsoCFDI, c_FormaPago, c_MetodoPago, c_TipoDeComprobante, c_ClaveProdServ, c_ClaveUnidad, c_Impuesto, c_TipoFactor, c_TasaOCuota, c_ObjetoImp, c_TipoRelacion) as Eloquent models with migrations, seeders, and Filament resources. These catalogs are required before any CFDI form can be built.

</domain>

<decisions>
## Implementation Decisions

### XLS-to-CSV Conversion
- Install `phpoffice/phpspreadsheet` as a dev dependency
- Create an Artisan command (`php artisan sat:export-csv`) that reads `database/data/catCFDI_V_4_20260212.xls` and exports all 12 sheets to CSV automatically
- Single command exports all sheets at once — no per-sheet selection needed
- Normalize booleans during export: 'Sí' → true, anything else → false — seeders read clean values
- Normalize dates during export to 'Y-m-d' format — seeders don't need to parse SAT date quirks

### Data Sourcing
- Exported CSVs are committed to git in `database/data/` — ready-to-seed snapshot
- The XLS file remains the canonical source of truth
- Seeders read from CSV files using PHP's fgetcsv

### Catalog Updates
- When SAT publishes updated catalogs, replace XLS, re-run `php artisan sat:export-csv`, commit new CSVs
- Seeders use `upsert()` — never `truncate()`. Existing records may be referenced by invoices

### Claude's Discretion
- Filament resource configuration for each catalog (read-only vs editable, columns displayed, search behavior)
- Model structure pattern (shared base class or standalone models)
- Handling large catalogs in Filament (c_ClaveProdServ ~53K rows, c_ClaveUnidad ~2K rows) — search/autocomplete strategy
- Migration and seeder naming conventions following existing project patterns
- Table naming conventions (follow existing catalog models like Currency, Country, etc.)
- Date format detection and edge case handling in the export command
- CSV encoding (UTF-8 output regardless of XLS source encoding)

</decisions>

<specifics>
## Specific Ideas

- Follow existing catalog model patterns in the project (Currency, Country, CustomUnit, TariffClassification, State, Incoterm)
- The Artisan export command should be reusable when SAT publishes catalog updates — run once, commit, seed

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 01-cat-logos-sat-base*
*Context gathered: 2026-02-27*
