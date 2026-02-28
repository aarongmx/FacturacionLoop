---
phase: 02-gesti-n-de-csd
verified: 2026-02-27T00:00:00Z
status: passed
score: 29/29 must-haves verified
re_verification: false
---

# Phase 2: Gestión de CSD Verification Report

**Phase Goal:** Gestión de CSD — Upload, validate, store encrypted, and manage CSD lifecycle (activate/deactivate/expiry)
**Verified:** 2026-02-27
**Status:** PASSED
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | phpcfdi/credentials and spatie/laravel-data are installed and autoloadable | VERIFIED | `composer.json` requires `phpcfdi/credentials: ^1.3` and `spatie/laravel-data: ^4.20`; both present in `vendor/` |
| 2 | csds table exists with correct schema including encrypted passphrase (TEXT), softDeletes, and indexes | VERIFIED | `2026_02_28_100000_create_csds_table.php`: `id()`, `no_certificado(40)->unique()`, `rfc(13)`, `fecha_inicio`, `fecha_fin`, `status(20)->default('inactive')`, `key_path(500)`, `passphrase_encrypted` as `text()`, `cer_path(500)`, `timestamps()`, `softDeletes()`, `index('status')`, `index('fecha_fin')` |
| 3 | Csd model uses encrypted cast on passphrase_encrypted, CsdStatus enum cast on status, date casts, SoftDeletes, and #[UseEloquentBuilder] attribute | VERIFIED | `app/Models/Csd.php`: `#[UseEloquentBuilder(CsdBuilder::class)]`, `use SoftDeletes`, casts method returns `passphrase_encrypted => encrypted`, `status => CsdStatus::class`, `fecha_inicio/fecha_fin => date` |
| 4 | CsdStatus backed enum implements HasColor and HasLabel with Spanish labels | VERIFIED | `app/Enums/CsdStatus.php`: `enum CsdStatus: string implements HasColor, HasLabel`, 4 cases (Active/ExpiringSoon/Expired/Inactive), Spanish labels (Activo/Por vencer/Expirado/Inactivo) |
| 5 | CsdBuilder has whereActive(), whereExpiring(int $withinDays = 90), and whereNotExpired() methods | VERIFIED | `app/Builders/CsdBuilder.php`: all three methods present and substantive |
| 6 | CsdFactory produces valid Csd records for testing | VERIFIED | `database/factories/CsdFactory.php`: `definition()` + `active()`, `expiringSoon()`, `expired()` states |
| 7 | UploadCsdData and CsdData are spatie/laravel-data DTOs | VERIFIED | Both extend `Spatie\LaravelData\Data`, constructor property promotion, final classes |
| 8 | UploadCsdAction validates .cer/.key pair using Credential::openFiles(), verifies isCsd(), extracts metadata, encrypts .key with Crypt::encryptString(), stores files, creates Csd, and cleans temp files | VERIFIED | `app/Actions/UploadCsdAction.php`: `Credential::openFiles()` in try/catch, `isCsd()` guard, `serialNumber()->decimal()`, `Crypt::encryptString($keyContents)`, `Storage::disk('local')->put()`, `DB::transaction()`, `Csd::create()`, `@unlink()` cleanup |
| 9 | UploadCsdAction throws RuntimeException with Spanish message when pair validation fails or certificate is not a CSD | VERIFIED | Catches `UnexpectedValueException`, rethrows as `RuntimeException` with Spanish message; separate check for `!$credential->isCsd()` with Spanish message |
| 10 | UploadCsdAction deletes temp upload files after processing | VERIFIED | `file_exists()` guards + `@unlink()` for both `$data->cerFilePath` and `$data->keyFilePath` |
| 11 | ActivateCsdAction deactivates the current active CSD (if any) and sets the given CSD as active — only one active CSD at a time | VERIFIED | `app/Actions/ActivateCsdAction.php`: `DB::transaction()`, `Csd::query()->whereActive()->where('id', '!=', $csd->id)->update([status => Inactive])`, then `$csd->update([status => Active])` |
| 12 | DeactivateCsdAction sets the given CSD status to Inactive | VERIFIED | `app/Actions/DeactivateCsdAction.php`: `$csd->update(['status' => CsdStatus::Inactive])` + `refresh()` |
| 13 | ValidateCsdExpiryAction throws RuntimeException when the active CSD is expired or when no active CSD exists | VERIFIED | `app/Actions/ValidateCsdExpiryAction.php`: `whereActive()->first()` null check throws Spanish message; `fecha_fin->isPast()` check throws Spanish message |
| 14 | CsdResource uses navigationGroup 'Configuración' (not 'Catálogos SAT') | VERIFIED | `app/Filament/Resources/CsdResource.php`: `protected static string\|UnitEnum\|null $navigationGroup = 'Configuración'` |
| 15 | CSD list table shows NoCertificado, RFC, fecha_inicio, fecha_fin, status badge, and created_at | VERIFIED | `CsdResource::table()`: 6 `TextColumn` definitions including `->badge()` on status |
| 16 | Upload form in CreateCsd page collects .cer file, .key file, and passphrase — calls UploadCsdAction on submit | VERIFIED | `app/Filament/Resources/CsdResource/Pages/CreateCsd.php`: `FileUpload::make('cer_file')`, `FileUpload::make('key_file')`, `TextInput::make('passphrase')`, `handleRecordCreation()` calls `app(UploadCsdAction::class)(...)` |
| 17 | ViewCsd page shows read-only CSD details (immutable records — no edit page) | VERIFIED | `app/Filament/Resources/CsdResource/Pages/ViewCsd.php`: extends `ViewRecord`, `infolist(Schema $schema)` returns 6 `TextEntry` fields; `getPages()` has no 'edit' route |
| 18 | List table has Activar action (visible when not active) and Desactivar action (visible when active) with confirmation modals | VERIFIED | `CsdResource::table()`: `->recordActions([])` with `Action::make('activar')` and `Action::make('desactivar')`, both `->requiresConfirmation()`, `->modalHeading()`, `->visible()` closures checking `CsdStatus::Active` |
| 19 | CsdExpiryWarningWidget shows a banner on the Filament dashboard when any CSD expires within 90 days | VERIFIED | `app/Filament/Widgets/CsdExpiryWarningWidget.php`: `canView()` calls `whereExpiring()->exists()`, `mount()` fetches `whereExpiring()->first()`, `$daysRemaining` calculated |
| 20 | CSD records support soft delete from the list table | VERIFIED | `DeleteAction::make()` in `recordActions()` array; `Csd` model uses `SoftDeletes`; migration has `softDeletes()` |
| 21 | Blade view renders days remaining with Spanish text | VERIFIED | `resources/views/filament/widgets/csd-expiry-warning.blade.php`: conditional `@if($this->expiringCsd)`, shows `$this->daysRemaining`, `'día' : 'días'` Spanish pluralization |
| 22 | CsdTest verifies model behavior, encrypted cast, date casts, CsdStatus enum cast, soft delete, and CsdBuilder query methods | VERIFIED | `tests/Feature/Models/CsdTest.php`: 13 tests using `expect()` assertions — factory creation, auto-increment PK, encrypted cast round-trip, enum cast, date casts, soft delete, factory states, `whereActive`, `whereExpiring`, `whereNotExpired` |
| 23 | UploadCsdActionTest verifies successful upload, metadata extraction, encrypted .key storage, .cer storage, temp file cleanup, and error handling | VERIFIED | `tests/Feature/Actions/UploadCsdActionTest.php`: 8 tests using real fixtures at `tests/fixtures/csd/EKU9003173C9.{cer,key}` with `markTestSkipped()` guard; tests cover CSD-01/02/04/05 |
| 24 | ActivateCsdActionTest verifies single-active enforcement, expired CSD rejection, and transactional behavior | VERIFIED | `tests/Feature/Actions/ActivateCsdActionTest.php`: 4 tests — activation, previous deactivation, single-active enforcement, expired rejection with Spanish message assertion |
| 25 | ValidateCsdExpiryActionTest verifies RuntimeException when no active CSD exists and when active CSD is expired | VERIFIED | `tests/Feature/Actions/ValidateCsdExpiryActionTest.php`: 4 tests — no CSDs, inactive-only, expired-active, success case |
| 26 | All tests use Pest syntax with expect() assertions | VERIFIED | All 4 test files use `it()` functions, `expect()` fluent chains, `->and()` chaining, `->throws()` closures |
| 27 | UploadCsdAction bug fix: serialNumber().decimal() not .bytes() | VERIFIED | `app/Actions/UploadCsdAction.php` line 51: `$certificate->serialNumber()->decimal()` — binary bytes would break PostgreSQL UTF-8 varchar |
| 28 | Test fixtures exist for real certificate parsing tests | VERIFIED | `tests/fixtures/csd/EKU9003173C9.cer` and `tests/fixtures/csd/EKU9003173C9.key` both present |
| 29 | Filament 5 API patterns used correctly (recordActions, Filament\Actions\*, non-static $view, Schema $schema) | VERIFIED | `CsdResource.php` uses `->recordActions([])`, imports from `Filament\Actions\{Action, ViewAction, DeleteAction}`; `ViewCsd.php` uses `Schema $schema`; `CsdExpiryWarningWidget.php` uses `protected string $view` (non-static) |

**Score:** 29/29 truths verified

---

### Required Artifacts

| Artifact | Provides | Status | Details |
|----------|----------|--------|---------|
| `app/Models/Csd.php` | Eloquent model with encrypted casts and SoftDeletes | VERIFIED | `#[UseEloquentBuilder(CsdBuilder::class)]`, final class, SoftDeletes, encrypted/enum/date casts |
| `app/Enums/CsdStatus.php` | Backed enum with 4 cases, Filament HasColor + HasLabel | VERIFIED | `implements HasColor, HasLabel`, Spanish labels, 4 color-coded cases |
| `app/Builders/CsdBuilder.php` | Custom query builder with 3 CSD-specific methods | VERIFIED | `whereActive()`, `whereExpiring(int)`, `whereNotExpired()` — all substantive |
| `app/Data/UploadCsdData.php` | Input DTO for upload action | VERIFIED | Extends `Spatie\LaravelData\Data`, 3 constructor properties |
| `app/Data/CsdData.php` | Output DTO for CSD display | VERIFIED | Extends `Spatie\LaravelData\Data`, 6 constructor properties including `CsdStatus` |
| `database/factories/CsdFactory.php` | Factory with 3 states for testing | VERIFIED | `definition()` + `active()`, `expiringSoon()`, `expired()` |
| `database/migrations/2026_02_28_100000_create_csds_table.php` | csds table schema | VERIFIED | Full schema: auto-increment PK, unique no_certificado, text passphrase, softDeletes, 2 indexes |
| `app/Actions/UploadCsdAction.php` | Upload/validate/encrypt/persist action | VERIFIED | `Credential::openFiles`, `isCsd()`, `decimal()`, `Crypt::encryptString`, `Storage::disk`, `DB::transaction`, `Csd::create`, temp cleanup |
| `app/Actions/ActivateCsdAction.php` | Activate CSD (single-active enforcement) | VERIFIED | Expires check, `DB::transaction`, `whereActive()` bulk deactivate, activate target |
| `app/Actions/DeactivateCsdAction.php` | Deactivate CSD | VERIFIED | `update(['status' => CsdStatus::Inactive])` + `refresh()` |
| `app/Actions/ValidateCsdExpiryAction.php` | Validate active CSD for stamping | VERIFIED | `whereActive()->first()`, null check, `isPast()` check, Spanish RuntimeException messages |
| `app/Filament/Resources/CsdResource.php` | Filament resource under Configuración | VERIFIED | Correct group, 6 columns, `recordActions()` with Activar/Desactivar/Eliminar |
| `app/Filament/Resources/CsdResource/Pages/ListCsds.php` | List page with Subir CSD header action | VERIFIED | Extends `ListRecords`, `CreateAction::make()->label('Subir CSD')` |
| `app/Filament/Resources/CsdResource/Pages/CreateCsd.php` | Upload form calling UploadCsdAction | VERIFIED | `FileUpload` + `TextInput::passphrase`, `handleRecordCreation()` invokes `UploadCsdAction`, `halt()` on error |
| `app/Filament/Resources/CsdResource/Pages/ViewCsd.php` | Read-only infolist view | VERIFIED | Extends `ViewRecord`, `infolist(Schema $schema)` with 6 `TextEntry` fields |
| `app/Filament/Widgets/CsdExpiryWarningWidget.php` | Dashboard expiry warning widget | VERIFIED | `canView()` uses `whereExpiring()`, `mount()` fetches expiring CSD, `$daysRemaining` computed |
| `resources/views/filament/widgets/csd-expiry-warning.blade.php` | Blade view for expiry banner | VERIFIED | Conditional rendering, certificate number, days remaining with Spanish pluralization |
| `tests/Feature/Models/CsdTest.php` | Model/casts/builder tests (13 tests) | VERIFIED | Covers encrypted cast round-trip, enum cast, date casts, soft delete, all 3 builder methods |
| `tests/Feature/Actions/UploadCsdActionTest.php` | Upload action integration tests (8 tests) | VERIFIED | Real fixture files used; covers CSD creation, metadata extraction, encrypted storage, temp cleanup, error cases |
| `tests/Feature/Actions/ActivateCsdActionTest.php` | Activation action tests (4 tests) | VERIFIED | Single-active enforcement, expired rejection with message assertion |
| `tests/Feature/Actions/ValidateCsdExpiryActionTest.php` | Expiry validation tests (4 tests) | VERIFIED | No-active, inactive-only, expired-active, success case |
| `tests/fixtures/csd/EKU9003173C9.cer` | Self-signed CSD test certificate | VERIFIED | File exists; OpenSSL-generated with x500UniqueIdentifier OID for `isCsd()` detection |
| `tests/fixtures/csd/EKU9003173C9.key` | Encrypted PKCS8 test private key | VERIFIED | File exists; PKCS8 DER encrypted with passphrase '12345678a' |

---

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `app/Models/Csd.php` | `app/Builders/CsdBuilder.php` | `#[UseEloquentBuilder]` attribute | WIRED | Line 16: `#[UseEloquentBuilder(CsdBuilder::class)]` present |
| `app/Models/Csd.php` | `app/Enums/CsdStatus.php` | Eloquent cast | WIRED | Line 44 in casts(): `'status' => CsdStatus::class` |
| `app/Models/Csd.php` | migration | Eloquent table convention `csds` | WIRED | Migration creates `csds` table; model uses default convention |
| `app/Actions/UploadCsdAction.php` | `app/Data/UploadCsdData.php` | Method parameter type | WIRED | Line 23: `public function __invoke(UploadCsdData $data): Csd` |
| `app/Actions/UploadCsdAction.php` | `app/Models/Csd.php` | Creates Csd records | WIRED | Line 75: `Csd::create([...])` inside `DB::transaction()` |
| `app/Actions/ValidateCsdExpiryAction.php` | `app/Builders/CsdBuilder.php` | Uses custom builder methods | WIRED | Line 19: `Csd::query()->whereActive()->first()` |
| `app/Filament/Resources/CsdResource/Pages/CreateCsd.php` | `app/Actions/UploadCsdAction.php` | Action invocation in handleRecordCreation | WIRED | Lines 7, 67: imported and invoked via `app(UploadCsdAction::class)(...)` |
| `app/Filament/Resources/CsdResource.php` | `app/Actions/ActivateCsdAction.php` | Table row action | WIRED | Lines 7, 75: imported and invoked via `app(ActivateCsdAction::class)($record)` |
| `app/Filament/Resources/CsdResource.php` | `app/Actions/DeactivateCsdAction.php` | Table row action | WIRED | Lines 8, 84: imported and invoked via `app(DeactivateCsdAction::class)($record)` |
| `app/Filament/Widgets/CsdExpiryWarningWidget.php` | `app/Builders/CsdBuilder.php` | Query builder method | WIRED | Lines 26, 31: `Csd::query()->whereExpiring()->exists()` and `->first()` |
| `tests/Feature/Actions/UploadCsdActionTest.php` | `app/Actions/UploadCsdAction.php` | Tests action invocation | WIRED | `app(UploadCsdAction::class)($data)` called in 6 tests |
| `tests/Feature/Models/CsdTest.php` | `app/Models/Csd.php` | Tests model behavior | WIRED | `Csd::factory()->create()` and `Csd::query()` called in all 13 tests |

---

### Requirements Coverage

| Requirement | Description | Source Plans | Status | Evidence |
|-------------|-------------|-------------|--------|----------|
| CSD-01 | Usuario puede subir archivo .cer desde Filament | 02-02, 02-03, 02-04 | SATISFIED | `FileUpload::make('cer_file')` in `CreateCsd.php`; stored via `UploadCsdAction`; 2 tests prove it |
| CSD-02 | Usuario puede subir archivo .key desde Filament | 02-02, 02-03, 02-04 | SATISFIED | `FileUpload::make('key_file')` in `CreateCsd.php`; encrypted and stored via `UploadCsdAction`; tests prove it |
| CSD-03 | Sistema almacena contraseña del .key encriptada con Laravel Crypt | 02-01, 02-04 | SATISFIED | `passphrase_encrypted => 'encrypted'` cast in `Csd::casts()`; `CsdTest.php` proves round-trip: raw DB value != plaintext, `Crypt::decryptString()` returns plaintext |
| CSD-04 | Sistema almacena archivo .key encriptado (nunca en storage público) | 02-01, 02-02, 02-04 | SATISFIED | `Crypt::encryptString($keyContents)` before `Storage::disk('local')->put()`; `UploadCsdActionTest.php` proves encrypted storage and decrypt round-trip |
| CSD-05 | Sistema extrae NoCertificado y fechas de vigencia del .cer al subir | 02-02, 02-04 | SATISFIED | `$certificate->serialNumber()->decimal()`, `rfc()`, `validFromDateTime()/validToDateTime()` in `UploadCsdAction`; test verifies RFC='EKU9003173C9', non-null dates, and `fecha_fin > fecha_inicio` |
| CSD-06 | Sistema valida que el CSD no esté expirado antes de cada firmado | 02-02, 02-04 | SATISFIED | `ValidateCsdExpiryAction::__invoke()` throws `RuntimeException` when no active CSD or `fecha_fin->isPast()`; 3 tests prove all failure paths and success case |
| CSD-07 | Sistema muestra alerta cuando el CSD está próximo a expirar (3 meses) | 02-01, 02-03, 02-04 | SATISFIED | `CsdBuilder::whereExpiring(90)` queries CSDs expiring within 90 days; `CsdExpiryWarningWidget::canView()` and `mount()` use it; Blade renders days remaining |

**All 7 requirements (CSD-01 through CSD-07) are SATISFIED.**

No orphaned requirements found — all CSD-01 through CSD-07 appear in at least one plan's `requirements` field and have corresponding implementation evidence.

---

### Anti-Patterns Found

None detected across all 23 source files. No TODO/FIXME/placeholder comments. No empty implementations. No stub API routes.

**Notable code quality observations:**

| File | Line | Pattern | Severity | Notes |
|------|------|---------|----------|-------|
| `app/Actions/UploadCsdAction.php` | 89-94 | `@unlink()` with error suppression | Info | Intentional — cleanup is best-effort; failure should not abort the action |
| `app/Filament/Resources/CsdResource/Pages/CreateCsd.php` | 82 | `$this->halt()` after exception | Info | Correct Filament pattern — halts form submission without redirect on error |

---

### Human Verification Required

The following items cannot be verified programmatically and require manual testing in a browser:

**1. Upload Form — File Acceptance in Browser**
**Test:** Navigate to the "Subir CSD" page and attempt to upload real .cer and .key files.
**Expected:** Files are accepted, UploadCsdAction runs, success notification appears, redirects to View page.
**Why human:** MIME type matching for .cer and .key files is browser-dependent; Livewire file upload behavior requires browser interaction.

**2. Activate/Deactivate Confirmation Modals**
**Test:** In the CSD list, click "Activar" on an inactive CSD.
**Expected:** Confirmation modal appears with Spanish text; confirming changes status to Active; previously active CSD becomes Inactive.
**Why human:** Livewire modal rendering and record refresh behavior requires browser interaction.

**3. Dashboard Widget Visibility**
**Test:** With a CSD expiring within 90 days in the database, navigate to the Filament dashboard.
**Expected:** Full-width yellow warning banner appears at the top showing the certificate number and days remaining.
**Why human:** Widget auto-registration and Livewire lazy loading require a running browser session.

**4. Error Notification Display**
**Test:** Upload an invalid .cer file (e.g., a text file renamed to .cer).
**Expected:** A red persistent danger notification appears with the Spanish error message from UploadCsdAction.
**Why human:** Filament notification rendering requires a browser session.

---

## Summary

Phase 2 goal achievement is **COMPLETE**. All four plans executed successfully:

- **Plan 01 (Data Layer):** 7 artifacts — Csd model, CsdStatus enum, CsdBuilder, CsdFactory, 2 DTOs, migration — all substantive and correctly wired.
- **Plan 02 (Domain Actions):** 4 invokable action classes implement the full CSD business logic including encryption, validation, and expiry enforcement.
- **Plan 03 (Filament UI):** CsdResource with 3 pages and CsdExpiryWarningWidget correctly use Filament 5 API patterns (`recordActions()`, `Filament\Actions\*`, non-static `$view`, `Schema $schema`).
- **Plan 04 (Tests):** 29 Pest feature tests across 4 files prove all 7 requirements with real certificate fixtures.

One latent bug was discovered and fixed during Plan 04: `serialNumber().bytes()` was changed to `serialNumber().decimal()` in `UploadCsdAction.php` to prevent UTF-8 encoding errors in PostgreSQL when storing real SAT certificate serial numbers.

All 7 CSD requirements are satisfied by actual code, not claims.

---

_Verified: 2026-02-27_
_Verifier: Claude (gsd-verifier)_
