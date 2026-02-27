# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-02-27)

**Core value:** El usuario puede crear una factura CFDI 4.0 en Filament, timbrarla con Finkok, y obtener el XML timbrado con UUID válido ante el SAT.
**Current focus:** Phase 1 — Catálogos SAT Base

## Current Position

Phase: 1 of 9 (Catálogos SAT Base)
Plan: 4 of 4 in current phase
Status: Executing — Plans 01, 02, 03 complete
Last activity: 2026-02-27 — Plan 01-03 complete: 12 Filament read-only resources for SAT catalogs created

## Session Handoff

**Stopped at:** Completed 01-03-PLAN.md (12 Filament resources for SAT catalogs)
**Resume with:** `/gsd:execute-phase 01-cat-logos-sat-base` (plan 04)
**Resume file:** `.planning/phases/01-cat-logos-sat-base/01-04-PLAN.md`

Progress: [██░░░░░░░░] 8%

## Performance Metrics

**Velocity:**
- Total plans completed: 3
- Average duration: 14 min
- Total execution time: 0.6 hours

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-cat-logos-sat-base | 3/4 | 44 min | 14 min |

**Recent Trend:**
- Last 5 plans: 4 min, 20 min, 20 min
- Trend: Stable

*Updated after each plan completion*

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- [Roadmap]: eclipxe/cfdiutils for XML generation; phpcfdi ecosystem for credentials, finkok, xml-cancelacion
- [Roadmap]: All PAC calls must be queued jobs — never synchronous from Filament actions
- [Roadmap]: CSD .key and passphrase must be encrypted at rest via Laravel Crypt from day one
- [Roadmap]: bcmath required for all monetary calculations in Pagos 2.0 complement
- [Roadmap]: CAT-13 through CAT-17 (Carta Porte catalogs) seeded in Phase 8, not Phase 1
- [01-01]: TasaOCuota uses auto-increment id (no natural single-column PK in c_TasaOCuota)
- [01-01]: TipoDeComprobante explicitly sets $table='tipos_comprobante' (non-standard plural)
- [01-01]: Sail Docker (PHP 8.5) used for migrations — system PHP is 8.4.1, below project requirement
- [01-03]: Filament 5 uses Schema API (not Form $form) — form() is public static function form(Schema $schema): Schema
- [01-03]: Filament 5 property types: $navigationGroup requires string|UnitEnum|null, $navigationIcon requires string|BackedEnum|null — ?string narrows type illegally
- [01-03]: deferLoading() applied to ClaveProdServResource (53K rows) and ClaveUnidadResource (~2K rows) for performance

### Pending Todos

None yet.

### Blockers/Concerns

- [Pre-Phase 4]: Finkok SOAP WSDL exact method signatures need verification at implementation time
- [Pre-Phase 4]: Finkok sandbox test CSD credentials must be obtained before integration tests can run
- [Pre-Phase 8]: SAT catalog data source URLs (c_ClaveProdServCP, c_Municipio) must be confirmed from omawww.sat.gob.mx before writing seeders
- [Pre-Phase 9]: Existing TariffClassification seeder source must be audited before Comercio Exterior work begins

## Session Continuity

Last session: 2026-02-27
Stopped at: Completed 01-03-PLAN.md — 12 Filament resources for SAT catalogs committed (d91f44d, 5e49411)
Resume file: .planning/phases/01-cat-logos-sat-base/01-04-PLAN.md
