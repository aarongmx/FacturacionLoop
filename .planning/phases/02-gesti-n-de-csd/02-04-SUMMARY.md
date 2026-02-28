---
phase: 02-gesti-n-de-csd
plan: "04"
subsystem: testing
tags: [pest, phpcfdi, credentials, csd, encrypted-cast, soft-delete, eloquent-builder, feature-tests, openssl]

# Dependency graph
requires:
  - phase: 02-gesti-n-de-csd
    plan: "01"
    provides: Csd model, CsdStatus enum, CsdBuilder (whereActive/whereExpiring/whereNotExpired), CsdFactory (active/expiringSoon/expired states)
  - phase: 02-gesti-n-de-csd
    plan: "02"
    provides: UploadCsdAction, ActivateCsdAction, DeactivateCsdAction, ValidateCsdExpiryAction
provides:
  - Feature tests proving CSD model encrypted cast round-trips correctly (CSD-03)
  - Feature tests proving .key stored encrypted in private disk (CSD-04)
  - Feature tests proving NoCertificado/RFC/dates extracted from .cer (CSD-05)
  - Feature tests proving RuntimeException when no active CSD or expired (CSD-06)
  - Feature tests proving CsdBuilder::whereActive() and whereExpiring() filter correctly (CSD-07)
  - Self-signed CSD test certificate fixture (EKU9003173C9) for real certificate parsing tests
  - Bug fix for UploadCsdAction: serialNumber().decimal() instead of .bytes()
affects:
  - phase-04 (stamping pipeline integration tests can reuse CsdFactory states and action tests as regression baseline)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Test fixture pattern: self-signed OpenSSL certificate with x500UniqueIdentifier OID and OU field for isCsd() detection
    - Action test pattern: Storage::fake('local') + temp file copy + app(Action::class)($data)
    - Encrypted cast test pattern: raw DB value via DB::table()->value() vs plaintext vs Crypt::decryptString()

key-files:
  created:
    - tests/Feature/Models/CsdTest.php
    - tests/Feature/Actions/UploadCsdActionTest.php
    - tests/Feature/Actions/ActivateCsdActionTest.php
    - tests/Feature/Actions/ValidateCsdExpiryActionTest.php
    - tests/fixtures/csd/EKU9003173C9.cer
    - tests/fixtures/csd/EKU9003173C9.key
  modified:
    - app/Actions/UploadCsdAction.php

key-decisions:
  - "UploadCsdAction fixed to use serialNumber().decimal() — bytes() returns raw binary (hex2bin) which is not UTF-8 safe for PostgreSQL varchar columns"
  - "Test certificate generated via OpenSSL with x500UniqueIdentifier OID (2.5.4.45) and OU field — required for phpcfdi isCsd() detection to work correctly"
  - "Test certificate uses -set_serial 0x00000000001234567890 to produce 11-digit decimal serial number fitting within the 40-char no_certificado column"
  - "Test key is PKCS8 DER encrypted with passphrase '12345678a' — matches SAT format for real certificate files"

patterns-established:
  - "CSD feature test pattern: real certificate fixtures in tests/fixtures/csd/ with markTestSkipped() guard"
  - "Encrypted field test: compare raw DB::table()->value() to plaintext and Crypt::decryptString() round-trip"

requirements-completed: [CSD-01, CSD-02, CSD-03, CSD-04, CSD-05, CSD-06, CSD-07]

# Metrics
duration: 5min
completed: 2026-02-28
---

# Phase 2 Plan 04: CSD Feature Tests Summary

**29 Pest feature tests across 4 files proving all CSD requirements (CSD-01 through CSD-07): model encrypted cast, .key dual-layer encryption, certificate metadata extraction via real OpenSSL-generated fixtures, activation single-active enforcement, and expiry validation**

## Performance

- **Duration:** 5 min
- **Started:** 2026-02-28T00:46:26Z
- **Completed:** 2026-02-28T00:52:23Z
- **Tasks:** 2
- **Files modified:** 7 (4 test files, 2 fixture files, 1 bug fix in UploadCsdAction)

## Accomplishments

- Created 4 Pest feature test files with 29 tests and 61 assertions — all passing, zero regressions in full suite (87/87)
- Generated proper CSD-type test certificate using OpenSSL with x500UniqueIdentifier OID and OU field, enabling real phpcfdi/credentials integration tests
- Fixed a latent bug in UploadCsdAction where `serialNumber().bytes()` was storing raw binary in a PostgreSQL varchar column — corrected to `serialNumber().decimal()`

## Task Commits

Each task was committed atomically:

1. **Task 1: Create CsdTest — model, casts, soft delete, and builder tests** - `0cf808a` (test)
2. **Task 2: Create action tests — UploadCsdAction, ActivateCsdAction, ValidateCsdExpiryAction** - `476f477` (test)

**Plan metadata:** (docs commit below)

## Files Created/Modified

- `tests/Feature/Models/CsdTest.php` - 13 tests: factory creation, auto-increment PK, encrypted cast round-trip, CsdStatus enum cast, date casts, soft delete, factory states (active/expiringSoon/expired), CsdBuilder::whereActive/whereExpiring/whereNotExpired
- `tests/Feature/Actions/UploadCsdActionTest.php` - 8 tests: Csd record creation, RFC extraction, .key encrypted storage, .cer storage, initial status determination, temp file cleanup, invalid contents error, wrong passphrase error
- `tests/Feature/Actions/ActivateCsdActionTest.php` - 4 tests: activation, previous CSD deactivation, single-active enforcement, expired CSD rejection
- `tests/Feature/Actions/ValidateCsdExpiryActionTest.php` - 4 tests: no CSDs, inactive-only, expired active, success case
- `tests/fixtures/csd/EKU9003173C9.cer` - Self-signed X.509 DER certificate with RFC EKU9003173C9, OU=Sucursal 1 (isCsd=true), serial 78187493520 (11 digits)
- `tests/fixtures/csd/EKU9003173C9.key` - PKCS8 DER encrypted private key, passphrase '12345678a'
- `app/Actions/UploadCsdAction.php` - Bug fix: `.bytes()` → `.decimal()` for no_certificado

## Decisions Made

- Used self-signed OpenSSL certificate as CSD test fixture: phpcfdi/credentials package is installed without dev dependencies, so no pre-built SAT test certificates were available. The phpcfdi `isCsd()` check only requires `OU` (branchName) in the subject; the `x500UniqueIdentifier` field is needed for RFC extraction. Both were achieved with OpenSSL using the `x500UniqueIdentifier` short name and `-set_serial` for a short decimal serial number.
- Used `markTestSkipped()` guard pattern in UploadCsdActionTest as insurance — ensures tests are skippable if fixtures are missing in a clean checkout, but since fixtures are committed, all 8 tests run fully.
- Fixed `serialNumber().bytes()` bug proactively discovered during testing — corrected to `.decimal()` which is the SAT standard representation and is valid UTF-8 text.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed UploadCsdAction: serialNumber().bytes() stores raw binary in PostgreSQL varchar**
- **Found during:** Task 2 (UploadCsdActionTest)
- **Issue:** `$certificate->serialNumber()->bytes()` calls `hex2bin()` which returns raw binary bytes. PostgreSQL's varchar column requires valid UTF-8, causing `SQLSTATE[22021]: Character not in repertoire: 7 ERROR: invalid byte sequence for encoding "UTF8"`. Real SAT `no_certificado` values are decimal digit strings, not raw bytes.
- **Fix:** Changed to `$certificate->serialNumber()->decimal()` which returns the SAT-standard decimal string representation
- **Files modified:** `app/Actions/UploadCsdAction.php`
- **Verification:** All 8 UploadCsdActionTest tests pass with real certificate fixture producing decimal `no_certificado`
- **Committed in:** `476f477` (Task 2 commit)

---

**Total deviations:** 1 auto-fixed (Rule 1 bug fix)
**Impact on plan:** Critical correctness fix — production uploads of real SAT CSD certificates would have failed with this bug. No scope creep.

## Issues Encountered

- phpcfdi/credentials package has no test fixtures in the installed vendor directory (dev dependencies not installed). Resolved by generating a proper CSD-type self-signed certificate using OpenSSL with the correct `x500UniqueIdentifier` OID (2.5.4.45) for RFC extraction and `OU` subject field for `isCsd()` detection.
- Generated certificate's default RSA serial number (48 decimal digits) exceeded the 40-char `no_certificado` column. Resolved by using `-set_serial 0x00000000001234567890` to produce an 11-digit decimal serial.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- All CSD requirements (CSD-01 through CSD-07) are now proven via feature tests
- The bug fix in UploadCsdAction ensures real SAT certificate uploads will work correctly in production
- Phase 4 (CFDI stamping pipeline) can use CsdFactory states and ValidateCsdExpiryAction as test infrastructure
- Test fixtures at `tests/fixtures/csd/` can be reused in Phase 4 integration tests

## Self-Check: PASSED

| Check | Status |
|-------|--------|
| tests/Feature/Models/CsdTest.php | FOUND |
| tests/Feature/Actions/UploadCsdActionTest.php | FOUND |
| tests/Feature/Actions/ActivateCsdActionTest.php | FOUND |
| tests/Feature/Actions/ValidateCsdExpiryActionTest.php | FOUND |
| tests/fixtures/csd/EKU9003173C9.cer | FOUND |
| tests/fixtures/csd/EKU9003173C9.key | FOUND |
| Commit 0cf808a | FOUND |
| Commit 476f477 | FOUND |

---
*Phase: 02-gesti-n-de-csd*
*Completed: 2026-02-28*
