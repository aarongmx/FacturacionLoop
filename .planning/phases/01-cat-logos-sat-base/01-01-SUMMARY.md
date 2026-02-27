---
phase: 01-cat-logos-sat-base
plan: 01
subsystem: database
tags: [laravel, eloquent, migrations, factories, sat, cfdi, catalogs, postgresql]

# Dependency graph
requires: []
provides:
  - 12 SAT catalog Eloquent models with string PKs (clave) following existing project conventions
  - 12 migrations creating regimenes_fiscales, usos_cfdi, formas_pago, metodos_pago, tipos_comprobante, impuestos, tipos_factor, objetos_imp, tipos_relacion, claves_prod_serv, claves_unidad, tasas_o_cuotas tables
  - 12 factories producing valid test data for all SAT catalog models
  - Search indexes on claves_prod_serv.descripcion and claves_unidad.nombre
  - TasaOCuota with composite unique constraint and belongsTo(Impuesto) relationship
  - Impuesto with hasMany(TasaOCuota) inverse relationship
affects: [02-cat-logos-sat-base, filament-resources, cfdi-form, invoice-creation]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "String PK pattern: final class, HasFactory, $incrementing=false, $primaryKey='clave', $keyType='string', #[Override] attributes, casts() method"
    - "TasaOCuota exception: auto-increment id with composite unique index (no natural single-column PK)"
    - "Migrations use declare(strict_types=1) and typed closures for Blueprint"

key-files:
  created:
    - app/Models/RegimenFiscal.php
    - app/Models/UsoCfdi.php
    - app/Models/FormaPago.php
    - app/Models/MetodoPago.php
    - app/Models/TipoDeComprobante.php
    - app/Models/Impuesto.php
    - app/Models/TipoFactor.php
    - app/Models/ObjetoImp.php
    - app/Models/TipoRelacion.php
    - app/Models/ClaveProdServ.php
    - app/Models/ClaveUnidad.php
    - app/Models/TasaOCuota.php
    - database/migrations/2026_02_27_200000_create_regimenes_fiscales_table.php
    - database/migrations/2026_02_27_200001_create_usos_cfdi_table.php
    - database/migrations/2026_02_27_200002_create_formas_pago_table.php
    - database/migrations/2026_02_27_200003_create_metodos_pago_table.php
    - database/migrations/2026_02_27_200004_create_tipos_comprobante_table.php
    - database/migrations/2026_02_27_200005_create_impuestos_table.php
    - database/migrations/2026_02_27_200006_create_tipos_factor_table.php
    - database/migrations/2026_02_27_200007_create_objetos_imp_table.php
    - database/migrations/2026_02_27_200008_create_tipos_relacion_table.php
    - database/migrations/2026_02_27_200009_create_claves_prod_serv_table.php
    - database/migrations/2026_02_27_200010_create_claves_unidad_table.php
    - database/migrations/2026_02_27_200011_create_tasas_o_cuotas_table.php
    - database/factories/RegimenFiscalFactory.php
    - database/factories/UsoCfdiFactory.php
    - database/factories/FormaPagoFactory.php
    - database/factories/MetodoPagoFactory.php
    - database/factories/TipoDeComprobanteFactory.php
    - database/factories/ImpuestoFactory.php
    - database/factories/TipoFactorFactory.php
    - database/factories/ObjetoImpFactory.php
    - database/factories/TipoRelacionFactory.php
    - database/factories/ClaveProdServFactory.php
    - database/factories/ClaveUnidadFactory.php
    - database/factories/TasaOCuotaFactory.php
  modified: []

key-decisions:
  - "TasaOCuota uses auto-increment id (not string clave) because c_TasaOCuota has no natural single-column PK — composite unique index preserves data integrity"
  - "TipoDeComprobante explicitly sets $table='tipos_comprobante' since model name does not follow Laravel's default snake_case plural convention"
  - "Impuesto model pre-emptively declares hasMany(TasaOCuota) relationship for forward-compatibility with Phase 2 Filament resources"
  - "Migrations use PHP 8.5 via Laravel Sail Docker container (system PHP is 8.4.1, below project requirement)"

patterns-established:
  - "String PK catalog pattern: final class, HasFactory<Factory>, $incrementing=false, $primaryKey='clave', $keyType='string', all with #[Override] attribute"
  - "casts() method (not $casts property) for boolean and date casts per Laravel 12 convention"
  - "Factory definition(): unique() faker for PKs to prevent test collision; null defaults for optional date fields"

requirements-completed: [CAT-01, CAT-02, CAT-03, CAT-04, CAT-05, CAT-06, CAT-07, CAT-08, CAT-09, CAT-10, CAT-11, CAT-12]

# Metrics
duration: 4min
completed: 2026-02-27
---

# Phase 1 Plan 1: SAT Catalog Migrations, Models, and Factories Summary

**12 SAT CFDI 4.0 catalog tables with Eloquent models (11 string PKs + TasaOCuota auto-increment) and test factories, all indexed and ready for Filament resource building**

## Performance

- **Duration:** 4 min
- **Started:** 2026-02-27T19:33:05Z
- **Completed:** 2026-02-27T19:37:14Z
- **Tasks:** 2 of 2
- **Files modified:** 36 (12 migrations + 12 models + 12 factories)

## Accomplishments
- Created 12 database tables matching SAT c_* catalog schemas for CFDI 4.0
- All 11 small/medium catalogs use string PK `clave` with `$incrementing = false` following existing CustomUnit convention
- TasaOCuota uses auto-increment id with composite unique constraint (no natural single-column PK in SAT data)
- Search indexes on `claves_prod_serv.descripcion` and `claves_unidad.nombre` for Filament select search performance (~53K and ~2K rows respectively)
- Bidirectional Impuesto/TasaOCuota relationship (hasMany/belongsTo) established

## Task Commits

Each task was committed atomically:

1. **Task 1: Create migrations, models, and factories for 9 small SAT catalogs** - `3b25848` (feat)
2. **Task 2: Create migrations, models, and factories for 3 large/special SAT catalogs** - `2f7e7ab` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `app/Models/RegimenFiscal.php` - c_RegimenFiscal catalog model (string PK, aplica_fisica/moral booleans)
- `app/Models/UsoCfdi.php` - c_UsoCFDI catalog model (string PK, aplica_fisica/moral booleans)
- `app/Models/FormaPago.php` - c_FormaPago catalog model (string PK, bancarizado boolean)
- `app/Models/MetodoPago.php` - c_MetodoPago catalog model (string PK)
- `app/Models/TipoDeComprobante.php` - c_TipoDeComprobante model (string PK, explicit table name)
- `app/Models/Impuesto.php` - c_Impuesto model (string PK, hasMany TasaOCuota)
- `app/Models/TipoFactor.php` - c_TipoFactor catalog model (string PK, values: Tasa/Cuota/Exento)
- `app/Models/ObjetoImp.php` - c_ObjetoImp catalog model (string PK)
- `app/Models/TipoRelacion.php` - c_TipoRelacion catalog model (string PK)
- `app/Models/ClaveProdServ.php` - c_ClaveProdServ model (~53K rows, string PK, descripcion indexed)
- `app/Models/ClaveUnidad.php` - c_ClaveUnidad model (~2K rows, string PK, nombre indexed)
- `app/Models/TasaOCuota.php` - c_TasaOCuota model (auto-increment id, composite unique, belongsTo Impuesto)
- 12 migrations in `database/migrations/` (timestamps 200000-200011)
- 12 factories in `database/factories/` with unique() faker PKs and null date defaults

## Decisions Made
- TasaOCuota uses auto-increment id — the SAT c_TasaOCuota catalog has no natural single-column PK (data is a combinatorial matrix of impuesto + factor + valor ranges)
- TipoDeComprobante explicitly sets `$table = 'tipos_comprobante'` since the class name would otherwise resolve to `tipo_de_comprobantes` (incorrect table name)
- Impuesto model pre-emptively declares `hasMany(TasaOCuota)` for forward-compatibility with Phase 2 resources
- Sail (PHP 8.5 Docker) used for migration/test execution because system PHP is 8.4.1 (below project requirement of ^8.5)

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
- System PHP is 8.4.1 but project requires ^8.5. Used Laravel Sail Docker container (`facturacionloop-laravel.test-1`) for all `php artisan` commands. This is expected behavior for this project setup — Pint ran fine via the system PHP path since it uses the Herd Lite binary.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- All 12 SAT catalog tables are available in the database
- All models follow the established string-PK pattern that Filament resources will build on
- Factories enable test-driven development for all subsequent phases
- Ready for Phase 1 Plan 2: SAT catalog data seeders

---
*Phase: 01-cat-logos-sat-base*
*Completed: 2026-02-27*
