---
phase: 03-emisor-receptores-y-productos
plan: 01
subsystem: database
tags: [eloquent, migrations, factories, validation, softdeletes, belongstomany, pivot]

# Dependency graph
requires:
  - phase: 01-cat-logos-sat-base
    provides: SAT catalog tables (regimenes_fiscales, usos_cfdi, claves_prod_serv, claves_unidad, objetos_imp, impuestos, tasas_o_cuotas) with string clave PKs
  - phase: 02-gesti-n-de-csd
    provides: Established model/factory/builder patterns used in this phase

provides:
  - emisores table with BelongsToMany to regimenes_fiscales via pivot
  - receptores table with SoftDeletes and nullable string FK to SAT catalogs
  - productos table with SoftDeletes, decimal(12,6) precio_unitario, string FK to SAT catalogs
  - producto_impuestos child table cascading delete from productos
  - Emisor/Receptor/Producto/ProductoImpuesto Eloquent models with full relationship graph
  - ValidaRfc validation rule (12-char moral, 13-char fisica, XAXX/XEXX generics)
  - 4 factories with useful states (personaMoral, publicoEnGeneral, extranjero, iva16, isrRetencion)

affects:
  - 03-02-PLAN (Emisor Filament resource UI)
  - 03-03-PLAN (Receptor/Producto Filament resource UI)
  - 03-04-PLAN (Model/rule tests using these factories)
  - All invoice generation phases (Emisor and Receptor are required for CFDI 4.0)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - BelongsToMany with 4 explicit key parameters for string PK pivot relationships
    - BelongsTo with explicit FK/owner key for string clave PK catalogs
    - SoftDeletes on customer-facing models (receptores, productos) per locked decision
    - decimal:6 cast for CFDI 4.0 ValorUnitario precision requirement
    - Factory states for domain-specific RFC variants (publicoEnGeneral, extranjero)

key-files:
  created:
    - database/migrations/2026_02_28_200000_create_emisores_table.php
    - database/migrations/2026_02_28_200001_create_emisor_regimen_fiscal_table.php
    - database/migrations/2026_02_28_200002_create_receptores_table.php
    - database/migrations/2026_02_28_200003_create_productos_table.php
    - database/migrations/2026_02_28_200004_create_producto_impuestos_table.php
    - app/Models/Emisor.php
    - app/Models/Receptor.php
    - app/Models/Producto.php
    - app/Models/ProductoImpuesto.php
    - app/Rules/ValidaRfc.php
    - database/factories/EmisorFactory.php
    - database/factories/ReceptorFactory.php
    - database/factories/ProductoFactory.php
    - database/factories/ProductoImpuestoFactory.php
  modified: []

key-decisions:
  - "Emisor is a singleton model (no SoftDeletes) — you never delete the issuer"
  - "Emisor BelongsToMany regimenesFiscales uses all 4 explicit key params because RegimenFiscal uses string clave PK not auto-increment"
  - "regimen_fiscal_clave and uso_cfdi_clave on receptores are nullable — new receptors can be saved without selecting catalog values initially"
  - "precio_unitario uses decimal(12,6) — CFDI 4.0 ValorUnitario requires 6 decimal places"
  - "ProductoFactory uses hardcoded SAT claves (01010101, E48, 02) — tests must seed SAT catalogs before using this factory"
  - "Pint replaced trim() with mb_trim() in ValidaRfc — mb_str_functions rule for multibyte RFC character safety"

patterns-established:
  - "BelongsToMany with 4-param explicit keys: belongsToMany(Model::class, table, local_fk, related_fk, parent_pk, related_pk)"
  - "BelongsTo with string catalog FK: belongsTo(Model::class, 'clave_column', 'clave')"
  - "Factory states for RFC variants: personaMoral(), publicoEnGeneral(), extranjero()"

requirements-completed: [ENT-01, ENT-03, ENT-04, PROD-01, PROD-03]

# Metrics
duration: 3min
completed: 2026-02-28
---

# Phase 3 Plan 01: Emisor/Receptor/Producto Data Layer Summary

**5 migrations + 4 Eloquent models + ValidaRfc rule + 4 factories establishing the CFDI 4.0 issuer, customer, and product data layer with correct string FK relationships to SAT catalogs**

## Performance

- **Duration:** 3 min
- **Started:** 2026-02-28T06:13:33Z
- **Completed:** 2026-02-28T06:16:30Z
- **Tasks:** 2
- **Files modified:** 14 created

## Accomplishments

- 5 migrations creating emisores, emisor_regimen_fiscal (pivot), receptores, productos, producto_impuestos with all FK constraints and correct ordering
- 4 Eloquent models with complete relationship graph: Emisor singleton with 4-param BelongsToMany, Receptor with SoftDeletes and nullable string FKs, Producto with SoftDeletes and HasMany impuestos, ProductoImpuesto with BelongsTo chain
- ValidaRfc rule accepting SAT-compliant 12-char persona moral, 13-char persona fisica, and generic XAXX/XEXX RFCs
- 4 factories with 5 domain-specific states covering common billing scenarios (publicoEnGeneral, personaMoral, extranjero, iva16, isrRetencion)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create migrations** - `81cda14` (feat)
2. **Task 2: Create models, ValidaRfc, and factories** - `0ff52c5` (feat)

## Files Created/Modified

- `database/migrations/2026_02_28_200000_create_emisores_table.php` - Emisor table: id, rfc, razon_social, domicilio_fiscal_cp, logo_path (nullable)
- `database/migrations/2026_02_28_200001_create_emisor_regimen_fiscal_table.php` - Pivot table: emisor_id FK + regimen_fiscal_clave string FK + unique constraint
- `database/migrations/2026_02_28_200002_create_receptores_table.php` - Receptores: rfc (indexed), nombre_fiscal, nullable string FKs to SAT catalogs, softDeletes
- `database/migrations/2026_02_28_200003_create_productos_table.php` - Productos: string FKs to SAT catalogs, decimal(12,6) precio_unitario, softDeletes
- `database/migrations/2026_02_28_200004_create_producto_impuestos_table.php` - Child table: producto_id (cascade), impuesto_clave string FK, tasa_o_cuota_id FK, es_retencion
- `app/Models/Emisor.php` - Singleton model with 4-param BelongsToMany regimenesFiscales
- `app/Models/Receptor.php` - SoftDeletes, BelongsTo regimenFiscal/usoCfdi with string FK
- `app/Models/Producto.php` - SoftDeletes, BelongsTo catalog models, HasMany impuestos
- `app/Models/ProductoImpuesto.php` - BelongsTo producto/impuesto/tasaOCuota
- `app/Rules/ValidaRfc.php` - ValidationRule for SAT RFC format (12/13 chars + generics)
- `database/factories/EmisorFactory.php` - Basic definition with regexify RFC
- `database/factories/ReceptorFactory.php` - Definition + personaMoral/publicoEnGeneral/extranjero states
- `database/factories/ProductoFactory.php` - Definition with hardcoded SAT catalog claves
- `database/factories/ProductoImpuestoFactory.php` - Definition + iva16/isrRetencion states

## Decisions Made

- Emisor has no SoftDeletes — it is a singleton and you never delete the issuer
- BelongsToMany for Emisor->RegimenesFiscales uses all 4 explicit key parameters because RegimenFiscal uses string `clave` as PK (not auto-increment)
- `regimen_fiscal_clave` and `uso_cfdi_clave` on receptores are nullable so new receptors can be created without requiring SAT catalog selection upfront
- `precio_unitario` is `decimal(12,6)` per CFDI 4.0 spec which requires 6 decimal places for ValorUnitario
- ProductoFactory uses hardcoded SAT claves (01010101, E48, 02) — tests that use this factory must seed the SAT catalog tables first

## Deviations from Plan

None — plan executed exactly as written. Pint automatically applied `mb_trim()` instead of `trim()` in ValidaRfc per the mb_str_functions coding standard (expected project behavior, not a deviation).

## Issues Encountered

Pre-existing test failure in `ImpuestoTest` (line 39) — `TasaOCuota::factory()->count(2)->create()` violates composite unique constraint when both records get identical generated values. This failure existed before this plan and is unrelated to the new models/migrations. Documented as out-of-scope per deviation rules.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- All 5 tables exist with correct FK constraints, indexes, and softDeletes
- All 4 models have complete relationship graphs ready for Filament UI (Plans 02 and 03)
- ValidaRfc rule ready for use in Filament form validation
- Factories with domain states ready for test plan (Plan 04)
- Plans 02-02 and 02-03 can now execute in parallel against stable model contracts

---
*Phase: 03-emisor-receptores-y-productos*
*Completed: 2026-02-28*
