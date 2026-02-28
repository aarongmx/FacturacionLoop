---
phase: 03-emisor-receptores-y-productos
plan: "03"
subsystem: ui
tags: [filament, filament5, producto, cfdi, impuestos, repeater, soft-delete]

# Dependency graph
requires:
  - phase: 03-01
    provides: Producto model with impuestos() HasMany to ProductoImpuesto, TasaOCuota SAT catalog model
  - phase: 01-03
    provides: Filament 5 resource pattern (Schema API, BackedEnum navigationIcon types)
provides:
  - Filament CRUD resource for Producto/Servicio management
  - Tax line Repeater with dynamic dependent selects (impuesto -> tasa/cuota filtering)
  - 4 tax template presets (Solo IVA 16%, IVA 16% + ISR 10%, Exento, IVA 0%)
  - Soft-delete actions and TrashedFilter on ProductoResource table
affects:
  - 04-generacion-de-cfdi (Producto CRUD must exist before Phase 4 invoice line items)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Filament Repeater with ->relationship() for HasMany child records
    - Dynamic dependent Select in Repeater using Get $get and ->live()
    - Filament Action on hintAction() for template preset modal
    - TasaOCuota IDs resolved at runtime via DB query (not hardcoded) to avoid seeder order pitfall

key-files:
  created:
    - app/Filament/Resources/ProductoResource.php
    - app/Filament/Resources/ProductoResource/Pages/ListProductos.php
    - app/Filament/Resources/ProductoResource/Pages/CreateProducto.php
    - app/Filament/Resources/ProductoResource/Pages/EditProducto.php
  modified:
    - app/Filament/Pages/EmisorSettings.php (auto-fix: navigationIcon type)

key-decisions:
  - "ClaveProdServ and ClaveUnidad selects use getSearchResultsUsing() with 50-result limit — avoids loading 53K/2K rows as options"
  - "TasaOCuota select is dynamically filtered by impuesto_clave and tipo_factor via Get $get in Repeater"
  - "Tax template presets query TasaOCuota at runtime by (impuesto, factor, valor_maximo, traslado/retencion) — no hardcoded IDs"
  - "Repeater uses ->relationship('impuestos') — Filament handles ProductoImpuesto create/update/delete automatically"
  - "getOptionLabelUsing() uses plain string interpolation (not ->pipe()) for label display on edit"

patterns-established:
  - "Repeater.relationship: Filament Repeater with ->relationship('name') creates/updates/deletes child records via HasMany"
  - "DynamicSelect.Repeater: Use ->live() on parent selects and Get $get in child select options() closure"
  - "TemplatePreset: hintAction() on Repeater opens a modal select that calls Set to populate repeater state"
  - "LargeTable.SearchableSelect: getSearchResultsUsing() + getOptionLabelUsing() pattern for 50+ row tables"

requirements-completed: [PROD-01, PROD-02, PROD-03]

# Metrics
duration: 4min
completed: 2026-02-28
---

# Phase 3 Plan 03: Producto Resource Summary

**Filament CRUD resource for Producto/Servicio with tax Repeater, dynamic dependent selects, and 4 tax template presets resolving TasaOCuota IDs at runtime**

## Performance

- **Duration:** 4 min
- **Started:** 2026-02-28T06:19:16Z
- **Completed:** 2026-02-28T06:23:34Z
- **Tasks:** 2
- **Files modified:** 5 (4 created, 1 auto-fixed)

## Accomplishments

- ProductoResource with searchable ClaveProdServ (53K rows) and ClaveUnidad selects limited to 50 results
- Tax Repeater with ->relationship('impuestos') persisting to producto_impuestos via ProductoImpuesto HasMany
- Dynamic TasaOCuota select filtered by impuesto_clave and tipo_factor using Filament's Get $get
- 4 tax template presets (Solo IVA 16%, IVA 16% + ISR 10%, Exento, IVA 0%) querying TasaOCuota IDs at runtime
- Soft delete actions (Archivar, Restaurar, Eliminar permanentemente) and TrashedFilter
- All 3 page classes (List, Create, Edit) following Filament 5 conventions

## Task Commits

Each task was committed atomically:

1. **Task 1: Create ProductoResource with form, tax repeater, and template presets** - `3ee84db` (feat)
2. **Task 2: Create ProductoResource page classes** - `a39636e` (feat)

**Plan metadata:** TBD (docs: complete plan)

## Files Created/Modified

- `app/Filament/Resources/ProductoResource.php` - Full CRUD resource with form, tax repeater, template presets, and soft delete table
- `app/Filament/Resources/ProductoResource/Pages/ListProductos.php` - List page with CreateAction header
- `app/Filament/Resources/ProductoResource/Pages/CreateProducto.php` - Create page extending CreateRecord
- `app/Filament/Resources/ProductoResource/Pages/EditProducto.php` - Edit page with Archivar delete action
- `app/Filament/Pages/EmisorSettings.php` - Auto-fixed: corrected navigationIcon type from ?string to string|BackedEnum|null

## Decisions Made

- Used `getSearchResultsUsing()` + `getOptionLabelUsing()` for ClaveProdServ and ClaveUnidad selects — necessary for large SAT catalog tables (53K/2K rows) to avoid loading all options
- Tax template presets use runtime DB queries (`TasaOCuota::query()->where(...)->value('id')`) to find correct IDs — this avoids the seeder order pitfall documented in plan research
- `->live()` applied to impuesto_clave and tipo_factor selects in Repeater — triggers TasaOCuota options re-evaluation on change
- `getOptionLabelUsing()` implemented with plain string interpolation using null checks — avoids non-existent `->pipe()` on plain string values from `->value()`

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed EmisorSettings navigationIcon type declaration**
- **Found during:** Task 1 (when running the test suite — PHP fatal error crashed artisan)
- **Issue:** `EmisorSettings::$navigationIcon` declared as `?string` but Filament 5 requires `string|BackedEnum|null` — caused PHP fatal error loading the panel
- **Fix:** Changed `protected static ?string $navigationIcon` to `protected static string|BackedEnum|null $navigationIcon` and added `use BackedEnum;` import
- **Files modified:** `app/Filament/Pages/EmisorSettings.php`
- **Verification:** Tests pass (87/87), PHP syntax clean, pint pass
- **Committed in:** Already in HEAD commit `afb7610` (was committed in prior session, restored from stash during this session)

**2. [Rule 3 - Blocking] Removed incorrectly generated Filament v5 resource structure**
- **Found during:** Task 1 (post-Artisan generation)
- **Issue:** `make:filament-resource` generated a nested `Productos/` subdirectory with `Schemas/ProductoForm.php` and `Tables/ProductosTable.php` — incompatible with existing project resource pattern
- **Fix:** Removed generated `app/Filament/Resources/Productos/` directory; manually created resource following existing single-file pattern at `app/Filament/Resources/ProductoResource.php`
- **Files modified:** Removed `Productos/` directory (not committed); created `ProductoResource.php` following sibling resource conventions
- **Verification:** PHP syntax clean, pint pass, tests pass
- **Committed in:** `3ee84db`

---

**Total deviations:** 2 auto-fixed (1 pre-existing bug, 1 blocking generator mismatch)
**Impact on plan:** Both auto-fixes essential. No scope creep.

## Issues Encountered

- Filament 5's `make:filament-resource` command generates a different structure (with separate Schema/Table class files in subdirectory) than the project's established pattern. The generated files were removed and the resource was written manually following existing sibling resources as reference.
- The `?->pipe()` pattern in the plan's getOptionLabelUsing() example would fail on plain string returns from `->value()`. Implemented with explicit null check and string interpolation.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- ProductoResource is fully operational with CRUD, tax repeater, and template presets
- Phase 4 can consume `Producto::query()` for invoice line item selects (searchable by clave and descripcion)
- Products with pre-configured tax lines are ready for CFDI 4.0 invoice generation in Phase 4

---
*Phase: 03-emisor-receptores-y-productos*
*Completed: 2026-02-28*
