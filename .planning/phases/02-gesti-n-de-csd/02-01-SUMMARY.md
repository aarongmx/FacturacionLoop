---
phase: 02-gesti-n-de-csd
plan: "01"
subsystem: database
tags: [phpcfdi, spatie-laravel-data, eloquent, enum, builder, dto, migration, csd]

# Dependency graph
requires:
  - phase: 01-cat-logos-sat-base
    provides: Project conventions, model patterns, factory patterns, Filament resource patterns
provides:
  - phpcfdi/credentials package installed for CSD certificate parsing
  - spatie/laravel-data package installed for DTOs
  - csds table migration with encrypted passphrase storage, softDeletes, and indexes
  - Csd Eloquent model with encrypted cast, CsdStatus enum cast, date casts, UseEloquentBuilder
  - CsdStatus backed enum with 4 cases, Spanish labels, and Filament colors
  - CsdBuilder with whereActive/whereExpiring/whereNotExpired query methods
  - CsdFactory with definition and active/expiringSoon/expired states
  - UploadCsdData and CsdData as spatie/laravel-data DTOs
affects:
  - 02-02 (upload action depends on UploadCsdData DTO and Csd model)
  - 02-03 (Filament resource depends on Csd model and CsdStatus enum)
  - 02-04 (tests depend on CsdFactory and all data layer artifacts)

# Tech tracking
tech-stack:
  added:
    - phpcfdi/credentials v1.3 — CSD/eFirma certificate parsing library
    - spatie/laravel-data v4.20 — DTOs and data transfer objects
  patterns:
    - Custom Eloquent builder via #[UseEloquentBuilder] attribute
    - Backed enum implementing Filament HasColor + HasLabel contracts
    - Encrypted field using Laravel's built-in 'encrypted' cast
    - SoftDeletes for CSD audit trail
    - spatie/laravel-data DTOs with constructor property promotion

key-files:
  created:
    - database/migrations/2026_02_28_100000_create_csds_table.php
    - app/Models/Csd.php
    - app/Enums/CsdStatus.php
    - app/Builders/CsdBuilder.php
    - database/factories/CsdFactory.php
    - app/Data/UploadCsdData.php
    - app/Data/CsdData.php
  modified:
    - composer.json
    - composer.lock

key-decisions:
  - "spatie/laravel-data requires --with-all-dependencies flag on PHP 8.5 due to phpdocumentor/reflection ^6.0 transitive dependency — installed v4.20 with downgraded phpdocumentor packages"
  - "CsdBuilder uses self type hint in closure parameters for proper IDE support with custom builder"
  - "app/Builders/ and app/Data/ are new directories consistent with standard Laravel project structure"

patterns-established:
  - "Custom builder pattern: app/Builders/XxxBuilder.php + #[UseEloquentBuilder(XxxBuilder::class)] on model"
  - "Enum pattern: backed string enum in app/Enums/ implementing Filament HasColor + HasLabel with Spanish labels"
  - "DTO pattern: spatie/laravel-data final class in app/Data/ with constructor property promotion"

requirements-completed: [CSD-03, CSD-04]

# Metrics
duration: 3min
completed: 2026-02-28
---

# Phase 2 Plan 01: CSD Data Layer Foundation Summary

**Eloquent Csd model with encrypted passphrase cast, CsdStatus enum (Filament colors + Spanish labels), CsdBuilder (3 query methods), CsdFactory (3 states), and spatie/laravel-data DTOs — all backed by csds migration with softDeletes and indexes**

## Performance

- **Duration:** 3 min
- **Started:** 2026-02-28T00:31:21Z
- **Completed:** 2026-02-28T00:34:22Z
- **Tasks:** 2
- **Files modified:** 9

## Accomplishments

- Installed phpcfdi/credentials (v1.3) and spatie/laravel-data (v4.20) as production dependencies
- Created csds table with auto-increment id, unique no_certificado, encrypted passphrase (TEXT), softDeletes, and indexes on status and fecha_fin
- Established complete CSD data layer: model, enum, builder, factory, and 2 DTOs — zero test regressions (58 tests / 111 assertions still passing)

## Task Commits

Each task was committed atomically:

1. **Task 1: Install dependencies and create migration** - `aab2544` (chore)
2. **Task 2: Create Csd model, CsdStatus enum, CsdBuilder, CsdFactory, and DTOs** - `dcc2b83` (feat)

**Plan metadata:** (docs commit below)

## Files Created/Modified

- `composer.json` + `composer.lock` - Added phpcfdi/credentials ^1.3 and spatie/laravel-data ^4.20
- `database/migrations/2026_02_28_100000_create_csds_table.php` - csds table schema with encrypted passphrase, softDeletes, dual indexes
- `app/Models/Csd.php` - Final Eloquent model: UseEloquentBuilder, SoftDeletes, encrypted+enum+date casts
- `app/Enums/CsdStatus.php` - Backed string enum: Active/ExpiringSoon/Expired/Inactive with Filament HasColor + HasLabel
- `app/Builders/CsdBuilder.php` - Custom builder: whereActive(), whereExpiring(int), whereNotExpired()
- `database/factories/CsdFactory.php` - Factory with definition + active/expiringSoon/expired states
- `app/Data/UploadCsdData.php` - Input DTO for CSD upload action
- `app/Data/CsdData.php` - Output DTO for CSD display with CarbonInterface dates

## Decisions Made

- spatie/laravel-data required `--with-all-dependencies` flag on PHP 8.5: phpdocumentor/reflection 6.4.4 supports PHP 8.5 but needed downgrading phpdocumentor/reflection-docblock and type-resolver
- CsdBuilder closure parameters use `self` type hint for proper custom builder chaining inside `whereExpiring()`
- New directories `app/Builders/` and `app/Data/` created following standard Laravel conventions

## Deviations from Plan

None - plan executed exactly as written (except using `--with-all-dependencies` for spatie install, which was anticipated in the plan's fallback instructions).

## Issues Encountered

- `spatie/laravel-data` initial install failed with PHP 8.5 constraint on `phpdocumentor/reflection`. Resolved with `--with-all-dependencies` flag which downgraded two phpdocumentor sub-packages to versions supporting PHP 8.5.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Plans 02-02 (upload action) and 02-03 (Filament resource) can execute in parallel — both depend on this data layer
- Plan 02-04 (tests) can begin after 02-02 and 02-03 complete
- CsdFactory states (active, expiringSoon, expired) ready for feature and integration tests

## Self-Check: PASSED

All files verified to exist on disk. All task commits verified in git history.

| Check | Status |
|-------|--------|
| app/Models/Csd.php | FOUND |
| app/Enums/CsdStatus.php | FOUND |
| app/Builders/CsdBuilder.php | FOUND |
| database/factories/CsdFactory.php | FOUND |
| app/Data/UploadCsdData.php | FOUND |
| app/Data/CsdData.php | FOUND |
| database/migrations/2026_02_28_100000_create_csds_table.php | FOUND |
| .planning/phases/02-gesti-n-de-csd/02-01-SUMMARY.md | FOUND |
| Commit aab2544 | FOUND |
| Commit dcc2b83 | FOUND |

---
*Phase: 02-gesti-n-de-csd*
*Completed: 2026-02-28*
