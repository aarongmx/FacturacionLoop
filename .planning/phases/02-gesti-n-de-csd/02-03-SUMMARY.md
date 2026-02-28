---
phase: 02-gesti-n-de-csd
plan: "03"
subsystem: ui
tags: [filament, livewire, csd, widget, blade, upload, filament5]

# Dependency graph
requires:
  - phase: 02-gesti-n-de-csd
    plan: "01"
    provides: Csd model, CsdStatus enum, CsdBuilder, UploadCsdData DTO
  - phase: 02-gesti-n-de-csd
    plan: "02"
    provides: UploadCsdAction, ActivateCsdAction, DeactivateCsdAction domain actions
provides:
  - CsdResource Filament resource under Configuración navigation group
  - ListCsds page with activate/deactivate/delete record actions
  - CreateCsd page with FileUpload (.cer/.key) and passphrase — calls UploadCsdAction
  - ViewCsd read-only infolist page (no edit — immutable CSD records)
  - CsdExpiryWarningWidget: full-width dashboard banner with days remaining
  - Blade view with Spanish warning text and conditional rendering
affects:
  - 02-04 (Filament resource tests depend on CsdResource pages and widget)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Filament 5 resource pattern with recordActions() in Table (not actions())
    - Filament 5 Actions from Filament\Actions\* namespace (not Filament\Tables\Actions\*)
    - Filament 5 $view as non-static instance property on Widget (not protected static)
    - Filament 5 infolist() on ViewRecord returns Schema $schema (not Infolist $infolist)
    - CreateRecord::handleRecordCreation() override with halt() for error rollback
    - Widget canView() for conditional dashboard widget visibility

key-files:
  created:
    - app/Filament/Resources/CsdResource.php
    - app/Filament/Resources/CsdResource/Pages/ListCsds.php
    - app/Filament/Resources/CsdResource/Pages/CreateCsd.php
    - app/Filament/Resources/CsdResource/Pages/ViewCsd.php
    - app/Filament/Widgets/CsdExpiryWarningWidget.php
    - resources/views/filament/widgets/csd-expiry-warning.blade.php

key-decisions:
  - "Filament 5 uses recordActions() not actions() on Table — row actions belong on Table definition in resource, not on ListRecords page"
  - "Filament 5 actions imported from Filament\\Actions\\* — not Filament\\Tables\\Actions\\* as in v3/v4"
  - "Widget $view property is non-static (instance property) — protected static $view causes fatal error in Filament 5"
  - "ViewRecord::infolist() takes Schema $schema (not Infolist $infolist) and returns Schema — Filament 5 unified Schema API"

patterns-established:
  - "Filament 5 resource pattern: table() defines recordActions(), imports from Filament\\Actions\\*"
  - "Custom widget pattern: Widget class with mount() + canView() + non-static $view + $columnSpan='full'"
  - "CreateRecord override pattern: handleRecordCreation() + halt() for domain action error handling"

requirements-completed: [CSD-01, CSD-02, CSD-07]

# Metrics
duration: 5min
completed: 2026-02-28
---

# Phase 2 Plan 03: CSD Filament Resource and Dashboard Widget Summary

**Filament 5 CsdResource under Configuración with FileUpload create page (UploadCsdAction), activate/deactivate record actions (Filament\Actions\*), read-only infolist view, and CsdExpiryWarningWidget full-width dashboard banner using CsdBuilder::whereExpiring()**

## Performance

- **Duration:** 5 min
- **Started:** 2026-02-28T00:37:41Z
- **Completed:** 2026-02-28T00:43:00Z
- **Tasks:** 2
- **Files modified:** 6

## Accomplishments

- CsdResource with 3 pages (list, create, view) under Configuración navigation group with all Spanish labels
- Upload form on CreateCsd page handles .cer/.key file uploads and passphrase — delegates to UploadCsdAction with error notification on failure
- Dashboard warning banner (CsdExpiryWarningWidget) shows days remaining for any expiring CSD, using CsdBuilder::whereExpiring()
- Discovered and auto-fixed Filament 5 API differences: recordActions(), Filament\Actions\* namespace, non-static $view property

## Task Commits

Each task was committed atomically:

1. **Task 1: Create CsdResource with list table, create page, and view page** - `434a8af` (feat)
2. **Task 2: Create CsdExpiryWarningWidget and Blade view** - `fb336b6` (feat)

**Plan metadata:** (docs commit below)

## Files Created/Modified

- `app/Filament/Resources/CsdResource.php` - Main resource: Configuración group, table columns, recordActions (Activar/Desactivar/Eliminar with confirmations)
- `app/Filament/Resources/CsdResource/Pages/ListCsds.php` - List page with "Subir CSD" header action
- `app/Filament/Resources/CsdResource/Pages/CreateCsd.php` - Upload form page: FileUpload .cer/.key + passphrase — calls UploadCsdAction via handleRecordCreation override
- `app/Filament/Resources/CsdResource/Pages/ViewCsd.php` - Read-only infolist: no_certificado, RFC, dates, status badge, upload date
- `app/Filament/Widgets/CsdExpiryWarningWidget.php` - Dashboard widget: full-width, sort=-1, canView() hides when no expiring CSDs
- `resources/views/filament/widgets/csd-expiry-warning.blade.php` - Warning banner with certificate number, days remaining, expiry date in Spanish

## Decisions Made

- **recordActions() vs actions():** Filament 5 uses `->recordActions([...])` in the Table definition — the plan's approach of returning `getTableActions()` from ListRecords does not exist in Filament 5.
- **Filament\Actions\* namespace:** Row actions come from `Filament\Actions\{ViewAction, DeleteAction, Action}` not `Filament\Tables\Actions\*` as in Filament 3/4.
- **Widget $view non-static:** The parent `Widget::$view` is a non-static instance property. Declaring it as `protected static string $view` causes a fatal PHP error at runtime. Changed to `protected string $view`.
- **Infolist API unified in Filament 5:** ViewRecord::infolist() takes `Schema $schema` and returns `Schema` — the Filament 3/4 `Infolist $infolist` type no longer exists.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed Filament 5 API for table record actions**
- **Found during:** Task 1 (CsdResource creation)
- **Issue:** Plan specified `getTableActions()` method on ListRecords page — this method does not exist in Filament 5. Also used wrong action namespace `Filament\Tables\Actions\*`.
- **Fix:** Moved record actions to `CsdResource::table()` using `->recordActions([...])`. Imported from `Filament\Actions\{ViewAction, DeleteAction, Action}`.
- **Files modified:** app/Filament/Resources/CsdResource.php, app/Filament/Resources/CsdResource/Pages/ListCsds.php
- **Verification:** PHP syntax passes, tests pass (58/111)
- **Committed in:** 434a8af (Task 1 commit)

**2. [Rule 1 - Bug] Fixed Filament 5 Widget $view property (non-static)**
- **Found during:** Task 2 (CsdExpiryWarningWidget creation)
- **Issue:** Plan specified `protected static string $view` — this conflicts with parent Widget class which declares `protected string $view` (non-static). Fatal PHP error on class load.
- **Fix:** Changed to `protected string $view = 'filament.widgets.csd-expiry-warning'`
- **Files modified:** app/Filament/Widgets/CsdExpiryWarningWidget.php
- **Verification:** Tests pass (58/111), fatal error resolved
- **Committed in:** fb336b6 (Task 2 commit)

**3. [Rule 1 - Bug] Fixed Filament 5 infolist API (Schema $schema instead of Infolist $infolist)**
- **Found during:** Task 1 (ViewCsd creation)
- **Issue:** Plan specified `public function infolist(Infolist $infolist): Infolist` — Filament 5 uses unified Schema API. ViewRecord::infolist() signature is `Schema $schema` returning `Schema`.
- **Fix:** Used `Schema $schema` parameter and return type, with `->components([...])` to add TextEntry items.
- **Files modified:** app/Filament/Resources/CsdResource/Pages/ViewCsd.php
- **Verification:** PHP syntax passes
- **Committed in:** 434a8af (Task 1 commit)

---

**Total deviations:** 3 auto-fixed (all Rule 1 bugs — Filament 5 API differences from plan's Filament 3/4 assumptions)
**Impact on plan:** All auto-fixes required for correct Filament 5 compatibility. No scope creep.

## Issues Encountered

- Filament artisan `make:filament-resource` placed the resource in a `Csds/` subdirectory with a different namespace. Deleted the scaffold and created files manually in `App\Filament\Resources\CsdResource` per plan specification.
- `make:filament-widget` requires interactive input even with `--no-interaction`. Created widget class manually.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Plan 02-04 (tests) can now run: CsdResource pages and CsdExpiryWarningWidget are ready for Filament testing
- CsdResource action chain is complete: Upload → Activate → Deactivate all wired to Filament UI
- Dashboard widget auto-registers via Filament's auto-discovery

## Self-Check: PASSED

| Check | Status |
|-------|--------|
| app/Filament/Resources/CsdResource.php | FOUND |
| app/Filament/Resources/CsdResource/Pages/ListCsds.php | FOUND |
| app/Filament/Resources/CsdResource/Pages/CreateCsd.php | FOUND |
| app/Filament/Resources/CsdResource/Pages/ViewCsd.php | FOUND |
| app/Filament/Widgets/CsdExpiryWarningWidget.php | FOUND |
| resources/views/filament/widgets/csd-expiry-warning.blade.php | FOUND |
| Commit 434a8af | FOUND |
| Commit fb336b6 | FOUND |

---
*Phase: 02-gesti-n-de-csd*
*Completed: 2026-02-28*
