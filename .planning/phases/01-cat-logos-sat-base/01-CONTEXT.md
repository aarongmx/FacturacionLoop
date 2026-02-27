# Phase 1: Catálogos SAT Base - Context

**Gathered:** 2026-02-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Seed all 12 missing SAT catalog tables (c_RegimenFiscal, c_UsoCFDI, c_FormaPago, c_MetodoPago, c_TipoDeComprobante, c_ClaveProdServ, c_ClaveUnidad, c_Impuesto, c_TipoFactor, c_TasaOCuota, c_ObjetoImp, c_TipoRelacion) as Eloquent models with migrations, seeders, and Filament resources. These catalogs are required before any CFDI form can be built.

</domain>

<decisions>
## Implementation Decisions

### Data Sourcing
- Load catalog data from official SAT CSV files
- CSVs stored in `database/data/` directory, versioned in git
- User provides the CSV files downloaded from the SAT portal — system does not auto-download
- Seeders read from CSV files using Laravel's filesystem or PHP's fgetcsv

### Catalog Updates
- When SAT publishes updated catalogs, use incremental migration strategy
- Detect differences between old CSV and new CSV, apply only changes (inserts, updates, soft-deletes)
- Do not truncate and re-seed — existing records may be referenced by invoices

### Claude's Discretion
- Filament resource configuration for each catalog (read-only vs editable, columns displayed, search behavior)
- Model structure pattern (shared base class or standalone models)
- Handling large catalogs in Filament (c_ClaveProdServ ~53K rows, c_ClaveUnidad ~2K rows) — search/autocomplete strategy
- Migration and seeder naming conventions following existing project patterns
- Table naming conventions (follow existing catalog models like Currency, Country, etc.)

</decisions>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches. Follow existing catalog model patterns already in the project (Currency, Country, CustomUnit, TariffClassification, State, Incoterm).

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 01-cat-logos-sat-base*
*Context gathered: 2026-02-27*
