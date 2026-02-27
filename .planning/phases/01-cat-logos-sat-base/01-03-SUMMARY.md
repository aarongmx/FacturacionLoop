---
phase: 01-cat-logos-sat-base
plan: 03
subsystem: ui
tags: [filament, filament-5, resources, catalogos-sat, admin-panel]

# Dependency graph
requires:
  - phase: 01-cat-logos-sat-base/01-01
    provides: 12 Eloquent models (RegimenFiscal, UsoCfdi, FormaPago, MetodoPago, TipoDeComprobante, Impuesto, TipoFactor, ObjetoImp, TipoRelacion, ClaveProdServ, ClaveUnidad, TasaOCuota)
provides:
  - 12 Filament read-only resources auto-discovered under 'Catálogos SAT' navigation group
  - ClaveProdServResource with deferLoading() for 53K row performance
  - ClaveUnidadResource with deferLoading() for ~2K rows
  - All resources list-only (no create/edit/delete) with searchable, sortable columns
affects:
  - 01-cat-logos-sat-base/01-04 (seeders will populate the tables these resources browse)
  - Phase 2+ any phase that adds Filament resources follows same pattern

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Filament 5 resource pattern with Schema API (not Form API from older versions)
    - Union types for navigationIcon (string|BackedEnum|null) and navigationGroup (string|UnitEnum|null)
    - deferLoading() on large-table resources to prevent browser freeze on page load
    - List-only resources via single 'index' key in getPages() with empty getHeaderActions()

key-files:
  created:
    - app/Filament/Resources/RegimenFiscalResource.php
    - app/Filament/Resources/RegimenFiscalResource/Pages/ListRegimenFiscals.php
    - app/Filament/Resources/UsoCfdiResource.php
    - app/Filament/Resources/UsoCfdiResource/Pages/ListUsoCfdis.php
    - app/Filament/Resources/FormaPagoResource.php
    - app/Filament/Resources/FormaPagoResource/Pages/ListFormaPagos.php
    - app/Filament/Resources/MetodoPagoResource.php
    - app/Filament/Resources/MetodoPagoResource/Pages/ListMetodoPagos.php
    - app/Filament/Resources/TipoDeComprobanteResource.php
    - app/Filament/Resources/TipoDeComprobanteResource/Pages/ListTipoDeComprobantes.php
    - app/Filament/Resources/ImpuestoResource.php
    - app/Filament/Resources/ImpuestoResource/Pages/ListImpuestos.php
    - app/Filament/Resources/TipoFactorResource.php
    - app/Filament/Resources/TipoFactorResource/Pages/ListTipoFactors.php
    - app/Filament/Resources/ObjetoImpResource.php
    - app/Filament/Resources/ObjetoImpResource/Pages/ListObjetoImps.php
    - app/Filament/Resources/TipoRelacionResource.php
    - app/Filament/Resources/TipoRelacionResource/Pages/ListTipoRelacions.php
    - app/Filament/Resources/ClaveProdServResource.php
    - app/Filament/Resources/ClaveProdServResource/Pages/ListClaveProdServs.php
    - app/Filament/Resources/ClaveUnidadResource.php
    - app/Filament/Resources/ClaveUnidadResource/Pages/ListClaveUnidads.php
    - app/Filament/Resources/TasaOCuotaResource.php
    - app/Filament/Resources/TasaOCuotaResource/Pages/ListTasaOCuotas.php
  modified: []

key-decisions:
  - "Filament 5 uses Schema API (not Form $form) — form() method signature is public static function form(Schema $schema): Schema"
  - "Filament 5 property types: $navigationGroup requires string|UnitEnum|null, $navigationIcon requires string|BackedEnum|null — ?string is incompatible"
  - "deferLoading() applied to both ClaveProdServResource (53K rows) and ClaveUnidadResource (~2K rows) for performance safety"
  - "TasaOCuota has auto-increment id PK — its resource sorts by 'impuesto' column (not 'clave') matching its data model"

patterns-established:
  - "Filament 5 Resource Pattern: extends Resource, uses Schema API, union types for nav properties, list-only via single getPages() entry"
  - "Read-only resource: getHeaderActions() returns [], no Create/Edit/Delete in getPages(), empty form(Schema) returning schema unchanged"
  - "Large table performance: deferLoading() prevents loading all records on page load — use for tables with >1K rows"

requirements-completed: [CAT-01, CAT-02, CAT-03, CAT-04, CAT-05, CAT-06, CAT-07, CAT-08, CAT-09, CAT-10, CAT-11, CAT-12]

# Metrics
duration: 20min
completed: 2026-02-27
---

# Phase 01 Plan 03: Filament SAT Catalog Resources Summary

**12 read-only Filament 5 admin resources for all SAT catalogs grouped under 'Catálogos SAT', with deferLoading() on large tables (ClaveProdServ 53K rows, ClaveUnidad ~2K rows)**

## Performance

- **Duration:** 20 min
- **Started:** 2026-02-27T19:26:00Z
- **Completed:** 2026-02-27T19:46:08Z
- **Tasks:** 2
- **Files modified:** 24

## Accomplishments

- 12 Filament 5 resources auto-discovered by AdminPanelProvider under 'Catálogos SAT' navigation group
- ClaveProdServResource uses deferLoading() preventing browser freeze on 53K row catalog
- All resources are strictly read-only — list page only, no Create/Edit/Delete actions
- Correct Filament 5 API used: Schema (not Form), union types for nav properties

## Task Commits

Each task was committed atomically:

1. **Task 1: Filament resources for 9 small SAT catalogs** - `d91f44d` (feat)
2. **Task 2: Filament resources for 3 large/special SAT catalogs** - `5e49411` (feat)

**Plan metadata:** (docs commit — see state_updates)

## Files Created/Modified

- `app/Filament/Resources/RegimenFiscalResource.php` - Régimen Fiscal list resource with aplica_fisica/aplica_moral boolean columns
- `app/Filament/Resources/UsoCfdiResource.php` - Uso CFDI list resource with persona física/moral columns
- `app/Filament/Resources/FormaPagoResource.php` - Forma de Pago list resource with bancarizado boolean
- `app/Filament/Resources/MetodoPagoResource.php` - Método de Pago list resource
- `app/Filament/Resources/TipoDeComprobanteResource.php` - Tipo de Comprobante list resource
- `app/Filament/Resources/ImpuestoResource.php` - Impuesto list resource
- `app/Filament/Resources/TipoFactorResource.php` - Tipo de Factor list resource
- `app/Filament/Resources/ObjetoImpResource.php` - Objeto de Impuesto list resource
- `app/Filament/Resources/TipoRelacionResource.php` - Tipo de Relación list resource
- `app/Filament/Resources/ClaveProdServResource.php` - 53K row catalog with deferLoading()
- `app/Filament/Resources/ClaveUnidadResource.php` - Unit keys with nombre/simbolo columns and deferLoading()
- `app/Filament/Resources/TasaOCuotaResource.php` - Tax rates with impuesto/factor/traslado/retencion columns
- Plus 12 corresponding Pages/List*.php files

## Decisions Made

- Filament 5 uses `Schema` API for `form()` — the PLAN.md used the older `Form $form` signature which would cause a type mismatch. Auto-fixed to use `Filament\Schemas\Schema`.
- Filament 5 base Resource class declares `$navigationGroup` as `string|UnitEnum|null` and `$navigationIcon` as `string|BackedEnum|null`. PHP forbids narrowing these to `?string` in subclasses. Used correct union types (auto-fixed before first artisan call).
- TasaOCuota uses auto-increment id (not `clave`) — resource sorts by `impuesto` column as planned.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Filament 5 Schema API instead of Form API**
- **Found during:** Task 1 (RegimenFiscalResource creation)
- **Issue:** PLAN.md interface example used `Form $form` / `Filament\Forms\Form` which is the Filament 4 API. Filament 5 changed form() to accept `Schema $schema` / `Filament\Schemas\Schema`.
- **Fix:** All resource `form()` methods use `Schema $schema` signature and `Filament\Schemas\Schema` import
- **Files modified:** All 12 resource files
- **Verification:** `php artisan route:list --path=admin` loads all 12 resources without error; tests pass
- **Committed in:** d91f44d, 5e49411

**2. [Rule 1 - Bug] Correct union types for Filament 5 navigation properties**
- **Found during:** Task 1 (first test run)
- **Issue:** PHP fatal error — `?string` type for `$navigationGroup` and `$navigationIcon` narrows the `string|UnitEnum|null` / `string|BackedEnum|null` type declared in the Filament 5 base Resource class
- **Fix:** Changed property declarations to use `string|UnitEnum|null` and `string|BackedEnum|null` union types with proper use imports
- **Files modified:** All 12 resource files
- **Verification:** Fatal PHP error resolved; all routes load; `vendor/bin/pint --format agent` passes
- **Committed in:** d91f44d, 5e49411

---

**Total deviations:** 2 auto-fixed (both Rule 1 - Bug: Filament 5 API differences from PLAN's Filament 4 examples)
**Impact on plan:** All auto-fixes required for correctness. Same functionality as specified, correct API usage for installed Filament version.

## Issues Encountered

The PLAN.md interface examples used Filament 4 API. Filament 5 has breaking API changes: `form(Schema)` instead of `form(Form)`, and stricter property type declarations. Both were identified and resolved before the first commit.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- 12 catalog resources ready for browsing once data is seeded (Plan 04)
- All resources auto-discovered — no manual registration needed
- Navigation group 'Catálogos SAT' will appear in admin sidebar after seeding

---
*Phase: 01-cat-logos-sat-base*
*Completed: 2026-02-27*
