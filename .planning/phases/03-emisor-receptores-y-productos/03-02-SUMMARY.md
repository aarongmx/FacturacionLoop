---
phase: 03-emisor-receptores-y-productos
plan: 02
subsystem: filament-ui
tags: [filament, emisor, receptor, settings, crud, soft-deletes, rfc-validation]
dependency_graph:
  requires: [03-01]
  provides: [emisor-settings-page, receptor-resource]
  affects: [03-03, phase-04]
tech_stack:
  added: []
  patterns:
    - Filament Page with InteractsWithForms for singleton settings
    - Manual BelongsToMany sync for string-PK pivot tables
    - Live(onBlur) afterStateUpdated for RFC auto-fill
    - getEloquentQuery() + withoutGlobalScopes for TrashedFilter
key_files:
  created:
    - app/Filament/Pages/EmisorSettings.php
    - resources/views/filament/pages/emisor-settings.blade.php
    - app/Filament/Resources/ReceptorResource.php
    - app/Filament/Resources/ReceptorResource/Pages/ListReceptores.php
    - app/Filament/Resources/ReceptorResource/Pages/CreateReceptor.php
    - app/Filament/Resources/ReceptorResource/Pages/EditReceptor.php
  modified: []
decisions:
  - "EmisorSettings uses protected string \$view (non-static) — Filament base Page declares \$view as instance property not static"
  - "EmisorSettings regimenes select uses manual options()+sync() not relationship() — BelongsToMany with string FK clave not auto-wireable"
  - "ReceptorResource uses getEloquentQuery() removing SoftDeletingScope — required for TrashedFilter to show all states"
  - "navigationGroup is 'Entidades' for ReceptorResource to group with ProductoResource in Phase 03-03"
metrics:
  duration: 4 min
  completed: "2026-02-28"
  tasks: 2
  files: 6
requirements: [ENT-01, ENT-02, ENT-05]
---

# Phase 03 Plan 02: Emisor Settings and Receptor CRUD Summary

**One-liner:** Filament 5 settings page for Emisor singleton with multi-regime BelongsToMany sync, and full Receptor CRUD resource with ValidaRfc validation, XAXX auto-fill, and soft-delete TrashedFilter.

## What Was Built

### Task 1: EmisorSettings page with Blade template

Created the singleton settings page for Emisor fiscal data:

- `app/Filament/Pages/EmisorSettings.php` — Filament Page implementing HasForms + InteractsWithForms
- `resources/views/filament/pages/emisor-settings.blade.php` — Blade template with save button

Key implementation:
- `firstOrCreate(['id' => 1])` singleton pattern — no duplicate emisor records
- `mount()` loads current emisor data and regimenes into component state
- Multi-select for regimenesFiscales uses manual `->options()` + `->sync()` because Filament's `->relationship()` does not work correctly with string-PK pivot tables
- RFC uses `->extraInputAttributes(['style' => 'text-transform: uppercase'])` + `->dehydrateStateUsing()` with `mb_strtoupper`
- No `$navigationGroup` — top-level nav item per locked decision
- FileUpload for optional logo stored in `emisor/` directory

### Task 2: ReceptorResource with CRUD pages

Created full CRUD resource for Receptor management:

- `app/Filament/Resources/ReceptorResource.php` — Resource with form, table, getPages, getEloquentQuery
- `app/Filament/Resources/ReceptorResource/Pages/ListReceptores.php` — List page with CreateAction
- `app/Filament/Resources/ReceptorResource/Pages/CreateReceptor.php` — Create page
- `app/Filament/Resources/ReceptorResource/Pages/EditReceptor.php` — Edit page with Archivar action

Key implementation:
- RFC field: `ValidaRfc` rule + CSS auto-uppercase + `dehydrateStateUsing` + `->live(onBlur: true)`
- `afterStateUpdated` auto-fills XAXX010101000 → nombre_fiscal='PUBLICO EN GENERAL', regimen_fiscal_clave='616', uso_cfdi_clave='S01'
- SAT catalog selects use `->options()` (not `->relationship()`) for string clave PKs
- Soft delete: `DeleteAction` labeled 'Archivar', `RestoreAction`, `ForceDeleteAction` labeled 'Eliminar permanentemente'
- `getEloquentQuery()` removes `SoftDeletingScope` so TrashedFilter operates correctly
- `->searchable()` on rfc and nombre_fiscal for Phase 4 search/select consumption
- `navigationGroup = 'Entidades'` groups Receptor with Producto

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed EmisorSettings $view static declaration**
- **Found during:** Task 2 (when running artisan make command)
- **Issue:** The plan specified `protected static string $view` but Filament's base `Page` class declares `$view` as a non-static instance property (`protected string $view`). PHP throws a fatal error: "Cannot redeclare non static Filament\Pages\Page::$view as static"
- **Fix:** Changed to `protected string $view` (removed `static`), removed `#[Override]` attribute from it, added `BackedEnum` import and corrected `$navigationIcon` type to `string|BackedEnum|null`
- **Files modified:** `app/Filament/Pages/EmisorSettings.php`
- **Commit:** afb7610

**2. [Rule 3 - Blocking] Artisan generated different file structure**
- **Found during:** Task 2 (make:filament-resource)
- **Issue:** `php artisan make:filament-resource Receptor` generated files under `Receptors/` namespace with extra `Schemas/` and `Tables/` subdirectories (Filament 5 new structure), different from the plan's expected `ReceptorResource/Pages/` convention used by existing CsdResource
- **Fix:** Removed generated `Receptors/` directory, manually created files following the `ReceptorResource/Pages/` convention established by Phase 2
- **Files modified:** N/A (generated files deleted, correct files created manually)
- **Commit:** f2befca

## Test Results

All 87 tests pass (172 assertions) — no regressions from this plan.

Note: One test (`ImpuestoTest` unique constraint violation) appeared during one run but passed on re-run in isolation and on full suite re-run — pre-existing DB isolation flakiness unrelated to this plan.

## Self-Check: PASSED

Files verified:
- FOUND: app/Filament/Pages/EmisorSettings.php
- FOUND: resources/views/filament/pages/emisor-settings.blade.php
- FOUND: app/Filament/Resources/ReceptorResource.php
- FOUND: app/Filament/Resources/ReceptorResource/Pages/ListReceptores.php
- FOUND: app/Filament/Resources/ReceptorResource/Pages/CreateReceptor.php
- FOUND: app/Filament/Resources/ReceptorResource/Pages/EditReceptor.php

Commits verified:
- 63ed71a: feat(03-02): create EmisorSettings page with singleton pattern and multi-regime select
- f2befca: feat(03-02): create ReceptorResource with CRUD pages and soft deletes
- afb7610: fix(03-02): fix EmisorSettings view property and navigationIcon type
