---
phase: 01-cat-logos-sat-base
plan: 04
subsystem: testing
tags: [pest, laravel, eloquent, sat, cfdi, models, feature-tests, postgresql]

# Dependency graph
requires:
  - phase: 01-01
    provides: 12 SAT catalog Eloquent models with string PKs and factories
  - phase: 01-02
    provides: SAT catalog seeders and CSV data
provides:
  - 12 Pest feature test files covering all SAT catalog models
  - Factory creation, string PK lookup, no auto-increment, boolean cast, date cast tests
  - ClaveProdServ search by descripcion test proving Eloquent query returns correct results
  - ClaveUnidad search by nombre test proving Eloquent query returns correct results
  - TasaOCuota belongsTo Impuesto relationship test
  - TasaOCuota composite unique constraint enforcement test
affects: [filament-resources, cfdi-form, invoice-creation]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Pest feature test pattern: declare(strict_types=1), no uses() needed (Pest.php configures RefreshDatabase for all Feature tests)"
    - "CarbonInterface (not Carbon/CarbonImmutable) for date cast assertions — Laravel 12 'date' cast returns CarbonImmutable"
    - "TasaOCuota relationship access: use impuesto()->first() not $model->impuesto — column name matches relation name, column takes precedence"

key-files:
  created:
    - tests/Feature/Models/RegimenFiscalTest.php
    - tests/Feature/Models/UsoCfdiTest.php
    - tests/Feature/Models/FormaPagoTest.php
    - tests/Feature/Models/MetodoPagoTest.php
    - tests/Feature/Models/TipoDeComprobanteTest.php
    - tests/Feature/Models/ImpuestoTest.php
    - tests/Feature/Models/TipoFactorTest.php
    - tests/Feature/Models/ObjetoImpTest.php
    - tests/Feature/Models/TipoRelacionTest.php
    - tests/Feature/Models/ClaveProdServTest.php
    - tests/Feature/Models/ClaveUnidadTest.php
    - tests/Feature/Models/TasaOCuotaTest.php
  modified:
    - app/Models/RegimenFiscal.php
    - app/Models/UsoCfdi.php
    - app/Models/FormaPago.php
    - app/Models/MetodoPago.php
    - app/Models/Impuesto.php
    - app/Models/TipoFactor.php
    - app/Models/ObjetoImp.php
    - app/Models/TipoRelacion.php
    - app/Models/ClaveProdServ.php
    - app/Models/ClaveUnidad.php

key-decisions:
  - "10 models needed explicit \$table declarations — Laravel's auto-pluralization of Spanish compound names does not produce correct SAT table names (e.g. RegimenFiscal → regimen_fiscals vs regimenes_fiscales)"
  - "CarbonInterface used for date cast assertions instead of Carbon::class — Laravel 12 'date' cast returns CarbonImmutable, not Illuminate\\Support\\Carbon"
  - "TasaOCuota belongsTo test uses impuesto()->first() instead of \$model->impuesto — column name 'impuesto' and relation name 'impuesto' collide, column wins"

patterns-established:
  - "Date cast assertions: use CarbonInterface::class, not Carbon::class or CarbonImmutable::class — works for both mutable and immutable Carbon instances"
  - "String PK test pattern: factory()->create(['clave' => 'known-value']) then Model::find('known-value') to verify lookup"
  - "Unique constraint test: create once, attempt duplicate with same attrs, expect QueryException"

requirements-completed: [CAT-01, CAT-02, CAT-03, CAT-04, CAT-05, CAT-06, CAT-07, CAT-08, CAT-09, CAT-10, CAT-11, CAT-12]

# Metrics
duration: 8min
completed: 2026-02-27
---

# Phase 1 Plan 4: SAT Catalog Model Feature Tests Summary

**12 Pest feature tests (54 tests / 111 assertions) covering all SAT CFDI 4.0 catalog models — factory creation, string PK lookup, boolean/date casts, Eloquent search, and TasaOCuota composite unique constraint**

## Performance

- **Duration:** 8 min
- **Started:** 2026-02-27T20:24:38Z
- **Completed:** 2026-02-27T20:32:00Z
- **Tasks:** 2 of 2
- **Files modified:** 22 (12 test files + 10 model files with $table fix)

## Accomplishments
- Created 12 Pest feature test files covering all SAT catalog models with 58 total tests passing
- ClaveProdServ search test proves `where('descripcion', 'like', '%keyword%')` returns correct record from factory-created data
- ClaveUnidad search test proves `where('nombre', 'like', '%keyword%')` returns correct record
- TasaOCuota relationship test proves `belongsTo(Impuesto)` query works correctly via `impuesto()->first()`
- TasaOCuota composite unique constraint test proves duplicate `(impuesto, factor, valor_minimo, valor_maximo, traslado, retencion)` throws QueryException
- Auto-fixed 10 models with missing `$table` declarations — critical bug discovered only by running tests

## Task Commits

Each task was committed atomically:

1. **Task 1: Create feature tests for 9 small SAT catalog models** - `6f3ddcb` (feat)
2. **Task 2: Create feature tests for 3 large/special SAT catalog models** - `3ddc407` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `tests/Feature/Models/RegimenFiscalTest.php` - 5 tests: factory, string PK, no auto-increment, boolean casts (aplica_fisica/moral), date cast
- `tests/Feature/Models/UsoCfdiTest.php` - 5 tests: factory, string PK, no auto-increment, boolean casts (aplica_fisica/moral), date cast
- `tests/Feature/Models/FormaPagoTest.php` - 5 tests: factory, string PK, no auto-increment, boolean cast (bancarizado), date cast
- `tests/Feature/Models/MetodoPagoTest.php` - 4 tests: factory, string PK, no auto-increment, date cast
- `tests/Feature/Models/TipoDeComprobanteTest.php` - 4 tests: factory, string PK, no auto-increment, date cast
- `tests/Feature/Models/ImpuestoTest.php` - 5 tests: factory, string PK, no auto-increment, date cast, hasMany TasaOCuota
- `tests/Feature/Models/TipoFactorTest.php` - 4 tests: factory, string PK, no auto-increment, date cast
- `tests/Feature/Models/ObjetoImpTest.php` - 4 tests: factory, string PK, no auto-increment, date cast
- `tests/Feature/Models/TipoRelacionTest.php` - 4 tests: factory, string PK, no auto-increment, date cast
- `tests/Feature/Models/ClaveProdServTest.php` - 5 tests: factory, string PK, no auto-increment, search by descripcion, estimulo_franja cast
- `tests/Feature/Models/ClaveUnidadTest.php` - 4 tests: factory, string PK, no auto-increment, search by nombre
- `tests/Feature/Models/TasaOCuotaTest.php` - 5 tests: factory, auto-increment PK, belongsTo Impuesto, composite unique, boolean casts
- 10 model files: added explicit `$table` declarations (RegimenFiscal, UsoCfdi, FormaPago, MetodoPago, Impuesto, TipoFactor, ObjetoImp, TipoRelacion, ClaveProdServ, ClaveUnidad)

## Decisions Made
- `$table` declarations added to 10 models — all SAT catalog table names use Spanish pluralization conventions that Laravel's auto-pluralizer doesn't produce correctly
- `CarbonInterface::class` used for date assertions instead of `Carbon::class` — Laravel 12 returns `CarbonImmutable` from `date` casts and `CarbonImmutable` is not a subclass of `Illuminate\Support\Carbon`
- TasaOCuota relationship test uses `$model->impuesto()->first()` instead of `$model->impuesto` — the `impuesto` column name takes precedence over the `impuesto()` relationship when accessed as a property

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed 10 models missing $table declarations**
- **Found during:** Task 1 (running tests after creating test files)
- **Issue:** Laravel's auto-pluralization produces incorrect table names for Spanish compound words: `RegimenFiscal` → `regimen_fiscals` (should be `regimenes_fiscales`), `UsoCfdi` → `uso_cfdis` (should be `usos_cfdi`), etc. Tests failed with `SQLSTATE[42P01]: Undefined table` on all but TipoDeComprobante and TasaOCuota (which already had explicit `$table`)
- **Fix:** Added `#[Override] protected $table = 'correct_name';` to RegimenFiscal, UsoCfdi, FormaPago, MetodoPago, Impuesto, TipoFactor, ObjetoImp, TipoRelacion, ClaveProdServ, ClaveUnidad
- **Files modified:** 10 model files in `app/Models/`
- **Verification:** All 40 Task 1 tests pass after fix
- **Committed in:** 6f3ddcb (Task 1 commit)

**2. [Rule 1 - Bug] Fixed date cast assertions using wrong Carbon class**
- **Found during:** Task 1 (first test run after table name fix)
- **Issue:** Tests used `toBeInstanceOf(Carbon::class)` (i.e. `Illuminate\Support\Carbon`) but Laravel 12 `date` cast returns `Carbon\CarbonImmutable` which is not a subclass of `Illuminate\Support\Carbon`
- **Fix:** Changed all date assertions to use `CarbonInterface::class` which `CarbonImmutable` does implement
- **Files modified:** 9 test files
- **Verification:** All date cast tests pass with `CarbonInterface`
- **Committed in:** 6f3ddcb (Task 1 commit)

**3. [Rule 1 - Bug] Fixed TasaOCuota relationship test accessing column instead of relationship**
- **Found during:** Task 2 (running TasaOCuota tests)
- **Issue:** `$tasaOCuota->impuesto` returned the string column value `'002'` instead of the related `Impuesto` model because the column name `impuesto` conflicts with the relationship method name `impuesto`
- **Fix:** Changed test to use `$tasaOCuota->impuesto()->first()` to explicitly invoke the relationship query
- **Files modified:** `tests/Feature/Models/TasaOCuotaTest.php`
- **Verification:** TasaOCuota relationship test passes
- **Committed in:** 3ddc407 (Task 2 commit)

---

**Total deviations:** 3 auto-fixed (3 bugs)
**Impact on plan:** All fixes necessary for correctness. The missing `$table` declarations are a latent bug in the models from Plan 01 — discovered only when tests actually tried to insert records. No scope creep.

## Issues Encountered
- TipoFactor factory uses `fake()->unique()->randomElement(['Tasa', 'Cuota', 'Exento'])` with only 3 possible values — intermittent unique constraint failures when multiple TipoFactor records exist across tests. Tests are written to create only 1 TipoFactor per test, so this is not an issue in practice but could be flaky if tests shared state. Tracked for future consideration.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- All 12 SAT catalog models verified working with factory, PK, casts, relationships
- Phase 1 complete: migrations, models, factories, seeders, Filament resources, and tests all done
- The `$table` fix also means Filament resources (Plan 03) now correctly map to database tables — the resources were also missing `$table` declarations on their underlying models

## Self-Check: PASSED

- All 12 test files exist in `tests/Feature/Models/`
- Commits 6f3ddcb and 3ddc407 verified in git log
- `php artisan test --compact` → 58 passed (111 assertions)

---
*Phase: 01-cat-logos-sat-base*
*Completed: 2026-02-27*
