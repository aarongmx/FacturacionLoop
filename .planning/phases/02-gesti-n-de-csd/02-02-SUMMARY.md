---
phase: 02-gesti-n-de-csd
plan: "02"
subsystem: domain
tags: [phpcfdi, credentials, actions, invokable, csd, encryption, laravel-crypt, storage]

# Dependency graph
requires:
  - phase: 02-gesti-n-de-csd
    plan: "01"
    provides: Csd model, CsdStatus enum, CsdBuilder (whereActive), UploadCsdData DTO, CsdFactory
provides:
  - UploadCsdAction: validates .cer/.key pair, extracts metadata, encrypts .key, stores .cer, creates Csd record, cleans temp files
  - ActivateCsdAction: deactivates current active CSD atomically, activates new CSD, refuses expired CSDs
  - DeactivateCsdAction: sets CSD status to Inactive
  - ValidateCsdExpiryAction: returns active CSD or throws RuntimeException with Spanish message
  - app/Actions/ directory established as invokable action pattern directory
affects:
  - 02-03 (Filament resource will call ActivateCsdAction and DeactivateCsdAction from table actions)
  - 02-04 (tests will unit-test all four actions)
  - phase-04 (CFDI stamping pipeline will call ValidateCsdExpiryAction before PAC submission)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Invokable action pattern: final class with __invoke() in app/Actions/
    - DB::transaction() wrapping multi-row updates for atomicity (ActivateCsdAction)
    - Crypt::encryptString() for .key file content encryption before Storage::disk('local')->put()
    - Carbon::instance(DateTimeImmutable) for conversion from phpcfdi date objects

key-files:
  created:
    - app/Actions/UploadCsdAction.php
    - app/Actions/ActivateCsdAction.php
    - app/Actions/DeactivateCsdAction.php
    - app/Actions/ValidateCsdExpiryAction.php
  modified: []

key-decisions:
  - "UploadCsdAction stores encrypted .key file contents in storage AND uses 'encrypted' Eloquent cast for passphrase — defense-in-depth for key material at rest"
  - ".cer file stored unencrypted in local disk because Phase 4 XML signing requires raw .cer bytes — encrypting would require decrypt-then-sign on every invoice"
  - "Initial CSD status is never Active after upload — always Inactive/ExpiringSoon/Expired — avoids implicit activation without explicit user intent"
  - "ActivateCsdAction refuses expired CSDs at the action level — not just in UI — enforcing the business rule server-side"
  - "All RuntimeException messages in Spanish per locked localization decision"

patterns-established:
  - "Action pattern: app/Actions/XxxAction.php — final class, declare(strict_types=1), single __invoke(), no constructor dependencies"
  - "Transaction pattern: DB::transaction(function() use ($model): void { ... }) for multi-row atomic updates"

requirements-completed: [CSD-01, CSD-02, CSD-04, CSD-05, CSD-06]

# Metrics
duration: 2min
completed: 2026-02-28
---

# Phase 2 Plan 02: CSD Domain Actions Summary

**Four invokable action classes implementing CSD business logic: upload/validate certificates via phpcfdi/credentials, encrypt .key with Crypt::encryptString(), manage active/inactive status atomically, and enforce expiry before CFDI stamping**

## Performance

- **Duration:** 2 min
- **Started:** 2026-02-28T00:37:08Z
- **Completed:** 2026-02-28T00:38:49Z
- **Tasks:** 2
- **Files modified:** 4

## Accomplishments

- Created app/Actions/ directory establishing invokable action pattern for the entire project
- UploadCsdAction orchestrates full certificate validation pipeline: phpcfdi pair validation, CSD type check, metadata extraction, dual-layer key encryption (Crypt::encryptString + encrypted cast), Storage persistence, temp cleanup
- ActivateCsdAction, DeactivateCsdAction, and ValidateCsdExpiryAction cover all status management and expiry enforcement needed by Phase 4 stamping

## Task Commits

Each task was committed atomically:

1. **Task 1: Create UploadCsdAction** - `5f56d0c` (feat)
2. **Task 2: Create ActivateCsdAction, DeactivateCsdAction, and ValidateCsdExpiryAction** - `723d330` (feat)

**Plan metadata:** (docs commit below)

## Files Created/Modified

- `app/Actions/UploadCsdAction.php` - Validates .cer/.key pair, verifies CSD type, extracts metadata, encrypts .key, stores .cer unencrypted, creates Csd with auto-determined status, cleans temp files
- `app/Actions/ActivateCsdAction.php` - Refuses expired CSDs, deactivates current active CSD in DB transaction, activates new CSD
- `app/Actions/DeactivateCsdAction.php` - Sets CSD status to Inactive via simple update
- `app/Actions/ValidateCsdExpiryAction.php` - Returns active CSD or throws RuntimeException with Spanish message when no active or expired

## Decisions Made

- UploadCsdAction uses dual-layer encryption for .key material: `Crypt::encryptString($keyContents)` encrypts file bytes before writing to Storage, AND the `passphrase_encrypted` field uses Laravel's `'encrypted'` Eloquent cast — defense-in-depth for the most sensitive credential material
- .cer stored unencrypted because Phase 4 XML signing requires raw .cer bytes on every invoice — the encryption overhead would be applied on the hot path
- Upload never auto-activates a CSD: status is always Inactive (or ExpiringSoon/Expired if applicable) — explicit user activation via ActivateCsdAction is required
- ActivateCsdAction validates expiry server-side, not just in Filament UI — enforcing the business rule at the domain layer

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None — all four actions created cleanly with no syntax errors. Pint confirmed clean formatting on all action files. 58 existing tests / 111 assertions still passing with zero regressions.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Plan 02-03 (Filament CSD resource) can now call ActivateCsdAction and DeactivateCsdAction from table row actions
- Plan 02-04 (tests) can now unit/feature test all four actions using CsdFactory states
- Phase 4 (CFDI stamping) can call ValidateCsdExpiryAction before PAC submission to enforce no-expired-CSD rule

## Self-Check: PASSED

| Check | Status |
|-------|--------|
| app/Actions/UploadCsdAction.php | FOUND |
| app/Actions/ActivateCsdAction.php | FOUND |
| app/Actions/DeactivateCsdAction.php | FOUND |
| app/Actions/ValidateCsdExpiryAction.php | FOUND |
| Commit 5f56d0c | FOUND |
| Commit 723d330 | FOUND |

---
*Phase: 02-gesti-n-de-csd*
*Completed: 2026-02-28*
