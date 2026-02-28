---
phase: 03-emisor-receptores-y-productos
plan: 04
subsystem: testing
tags: [pest, models, validation, rfc, emisor, receptor, producto, soft-delete, belongs-to-many]

# Dependency graph
requires:
  - phase: 03-01
    provides: Emisor, Receptor, Producto, ProductoImpuesto models + ValidaRfc rule + all factories
  - phase: 03-02
    provides: EmisorSettings + ReceptorResource Filament UI
  - phase: 03-03
    provides: ProductoResource Filament UI with tax repeater

provides:
  - Pest feature tests for Emisor model (factory, fillable, BelongsToMany regimenesFiscales)
  - Pest feature tests for Receptor model (factory, fillable, SoftDeletes, BelongsTo relationships, factory states)
  - Pest feature tests for Producto model (factory, fillable, decimal cast, SoftDeletes, all BelongsTo + HasMany)
  - Pest feature tests for ValidaRfc rule (valid/invalid formats, Unicode Ñ, lowercase, whitespace, generics)

affects: [04-generacion-de-cfdi, all future phases consuming Phase 3 models]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "beforeEach SAT catalog seeding pattern: ProductoFactory hardcodes SAT claves, tests must seed ClaveProdServ/ClaveUnidad/ObjetoImp before factory runs"
    - "publicoEnGeneral FK seeding: factory state hardcodes regimen_fiscal_clave=616/uso_cfdi_clave=S01, test creates those records inline"
    - "Non-implicit rule behavior: ValidaRfc skips empty strings (not ImplicitRule), use required+ValidaRfc together for presence enforcement"

key-files:
  created:
    - tests/Feature/Models/EmisorTest.php
    - tests/Feature/Models/ReceptorTest.php
    - tests/Feature/Models/ProductoTest.php
    - tests/Feature/Rules/ValidaRfcTest.php
  modified:
    - app/Rules/ValidaRfc.php

key-decisions:
  - "ValidaRfc REGEX_MORAL/REGEX_FISICA require /u Unicode flag — without it, Ñ (2-byte UTF-8) causes character count mismatch, silently rejecting valid Mexican RFCs with Ñ"
  - "publicoEnGeneral factory state creates Receptor with FK references (616/S01) — tests must seed those catalog rows or get PostgreSQL FK violation"
  - "Empty string passes ValidaRfc without required rule — ValidaRfc is not ImplicitRule, so Laravel skips it for absent values; requires required+ValidaRfc combination for presence enforcement"
  - "ProductoFactory hardcodes SAT claves (01010101/E48/02) — all Producto tests use beforeEach to seed those catalog records"

patterns-established:
  - "SAT catalog FK seeding: any test using ProductoFactory must first seed ClaveProdServ(01010101), ClaveUnidad(E48), ObjetoImp(02) via beforeEach"
  - "Factory state FK dependencies: factory states that hardcode FK values require those referenced records to exist — seed them inline in the test"

requirements-completed: [ENT-01, ENT-02, ENT-03, ENT-04, ENT-05, PROD-01, PROD-02, PROD-03]

# Metrics
duration: 5min
completed: 2026-02-28
---

# Phase 3 Plan 04: Phase 3 Model and ValidaRfc Tests Summary

**Pest feature tests for Emisor, Receptor, Producto models and ValidaRfc rule — 50 tests / 80 assertions, all passing; auto-fixed Unicode Ñ bug in ValidaRfc regex**

## Performance

- **Duration:** 5 min
- **Started:** 2026-02-28T06:26:45Z
- **Completed:** 2026-02-28T06:32:00Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments
- EmisorTest: 6 tests covering factory creation, fillable attributes, nullable logo_path, regimenesFiscales BelongsToMany attach/sync/count, and table name
- ReceptorTest: 12 tests covering factory, fillable, soft delete/restore, regimenFiscal/usoCfdi BelongsTo relationships, nullable FKs, duplicate RFC tolerance, all 3 factory states (publicoEnGeneral/personaMoral/extranjero), and table name
- ProductoTest: 12 tests covering factory, fillable, precio_unitario decimal:6 cast, soft delete/restore, all 4 relationships (claveProdServ/claveUnidad/objetoImp/impuestos), cascade forceDelete, multiple tax lines, and table name
- ValidaRfcTest: 20 tests covering valid persona física (13 chars including Ñ/&), valid persona moral (12 chars including Ñ), generic RFCs (XAXX/XEXX), lowercase normalization, whitespace trimming, invalid formats, empty string implicit-rule behavior, and Spanish error message
- Auto-fixed Unicode bug in ValidaRfc: added /u flag to REGEX_MORAL and REGEX_FISICA so Ñ is handled as a single character not 2 bytes

## Task Commits

Each task was committed atomically:

1. **Task 1: Create EmisorTest and ReceptorTest** - `5494a23` (feat)
2. **Task 2: Create ProductoTest and ValidaRfcTest** - `921ff55` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `tests/Feature/Models/EmisorTest.php` - 6 tests for Emisor model: factory, BelongsToMany regimenesFiscales
- `tests/Feature/Models/ReceptorTest.php` - 12 tests for Receptor model: SoftDeletes, BelongsTo relationships, factory states
- `tests/Feature/Models/ProductoTest.php` - 12 tests for Producto model: decimal cast, SoftDeletes, all relationships, cascade delete
- `tests/Feature/Rules/ValidaRfcTest.php` - 20 tests for ValidaRfc rule: valid/invalid formats, Unicode, whitespace, generics
- `app/Rules/ValidaRfc.php` - Added /u Unicode flag to REGEX_MORAL and REGEX_FISICA

## Decisions Made
- Added `/u` Unicode flag to ValidaRfc regex constants — essential for Ñ support (multi-byte UTF-8 character treated as 1 char not 2 bytes)
- productFactory tests use beforeEach to seed SAT catalog FKs — ProductoFactory hardcodes clave_prod_serv=01010101, clave_unidad=E48, objeto_imp_clave=02
- Empty string excluded from "invalid format" dataset in ValidaRfcTest — ValidaRfc is not ImplicitRule so Laravel skips validation for empty strings; documented with a dedicated test explaining the behavior

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed ValidaRfc Unicode bug: missing /u flag causes Ñ to fail validation**
- **Found during:** Task 2 (ValidaRfcTest with Ñ datasets)
- **Issue:** REGEX_MORAL and REGEX_FISICA lacked `/u` Unicode flag. `Ñ` is 2 bytes in UTF-8, so without `/u`, PCRE counts it as 2 bytes, not 1 character. A 12-char moral RFC starting with Ñ becomes 13 bytes, failing the 3-char prefix match.
- **Fix:** Added `/u` flag to both regex constants in `app/Rules/ValidaRfc.php`
- **Files modified:** `app/Rules/ValidaRfc.php`
- **Verification:** All Ñ test cases pass; `ÑABC010101AB1` (13-char física) and `ÑAB010101AB1` (12-char moral) both accepted
- **Committed in:** `921ff55` (Task 2 commit)

**2. [Rule 1 - Bug] Fixed ReceptorTest publicoEnGeneral: missing FK seed causes PostgreSQL constraint violation**
- **Found during:** Task 1 (ReceptorTest publicoEnGeneral state)
- **Issue:** `publicoEnGeneral()` factory state sets `regimen_fiscal_clave='616'` and `uso_cfdi_clave='S01'` but no '616' record exists in regimenes_fiscales nor 'S01' in usos_cfdi — FK constraint violation
- **Fix:** Added inline `RegimenFiscal::factory()->create(['clave' => '616'])` and `UsoCfdi::factory()->create(['clave' => 'S01'])` before the factory call
- **Files modified:** `tests/Feature/Models/ReceptorTest.php`
- **Verification:** Test passes; receptor is created with the expected FK values
- **Committed in:** `5494a23` (Task 1 commit)

**3. [Rule 1 - Bug] Fixed ValidaRfcTest invalid dataset: 'AB1010101123' is not a valid moral RFC**
- **Found during:** Task 2 (ValidaRfcTest persona moral dataset)
- **Issue:** `AB1010101123` was listed as a valid moral RFC but REGEX_MORAL requires 3 letters first — `AB1` has a digit in position 3, so it correctly fails. The test expectation was wrong.
- **Fix:** Replaced with `ABC010101123` which correctly has 3 letters + 6 digits + 3 alphanumeric chars
- **Files modified:** `tests/Feature/Rules/ValidaRfcTest.php`
- **Verification:** All 3 moral RFC datasets pass
- **Committed in:** `921ff55` (Task 2 commit)

**4. [Rule 1 - Bug] Fixed ValidaRfcTest: empty string incorrectly listed as invalid RFC format**
- **Found during:** Task 2 (ValidaRfcTest invalid formats dataset)
- **Issue:** Empty string `''` was in the "rejects invalid" dataset but Laravel skips non-ImplicitRule validation for absent values — `$validator->fails()` returns `false` for empty string with only `ValidaRfc` rule
- **Fix:** Removed empty string from the with() dataset; added a dedicated explanatory test (`it does not fail on empty string because Laravel skips non-implicit rules`) that demonstrates correct behavior (using `required` + `ValidaRfc` together)
- **Files modified:** `tests/Feature/Rules/ValidaRfcTest.php`
- **Verification:** All ValidaRfcTest tests pass including the new empty-string behavioral test
- **Committed in:** `921ff55` (Task 2 commit)

---

**Total deviations:** 4 auto-fixed (4 Rule 1 bugs)
**Impact on plan:** All fixes were necessary for test correctness — bugs in test data, missing FK seeds, and a real validation bug in production code. No scope creep.

## Issues Encountered
- ImpuestoTest has a pre-existing failure (UniqueConstraintViolationException on tasas_o_cuotas composite unique) that is unrelated to this plan. Logged to deferred items.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All Phase 3 models are now programmatically verified: Emisor, Receptor, Producto, ProductoImpuesto, ValidaRfc
- Phase 4 (CFDI generation) can confidently consume these models knowing their relationships, casts, and constraints are correct
- The beforeEach SAT catalog seeding pattern is established for any future tests that use ProductoFactory

---
*Phase: 03-emisor-receptores-y-productos*
*Completed: 2026-02-28*

## Self-Check: PASSED

- FOUND: tests/Feature/Models/EmisorTest.php
- FOUND: tests/Feature/Models/ReceptorTest.php
- FOUND: tests/Feature/Models/ProductoTest.php
- FOUND: tests/Feature/Rules/ValidaRfcTest.php
- FOUND: .planning/phases/03-emisor-receptores-y-productos/03-04-SUMMARY.md
- FOUND: commit 5494a23
- FOUND: commit 921ff55
