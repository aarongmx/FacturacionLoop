# Phase 2: Gestión de CSD - Context

**Gathered:** 2026-02-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Upload, encrypt, and manage Certificados de Sello Digital (.cer/.key) in Filament, with encrypted storage and expiry monitoring. Users can store multiple CSDs, activate one for signing, and receive warnings when certificates approach expiration. The system blocks stamping with expired or missing CSDs before contacting the PAC.

</domain>

<decisions>
## Implementation Decisions

### Architecture (Fixed by laravel-architecture skill)
- All domain logic in Action classes: `UploadCsdAction`, `ValidateCsdExpiryAction`, `ActivateCsdAction`, `DeactivateCsdAction`
- DTOs with Spatie Data: `UploadCsdData`, `CsdData`
- `CsdStatus` backed enum (Active, ExpiringSoon, Expired, Inactive)
- Custom query builder `CsdBuilder` with `whereActive()`, `whereExpiring()` — no scopes
- Form Request for upload validation (file types, required fields)
- Thin model: persistence, relationships, casts only
- Actions are invokable, single-responsibility, transactional
- `declare(strict_types=1)` in every file

### Upload flow
- Single Filament form with three fields: .cer file, .key file, passphrase
- On submit, the `UploadCsdAction` validates the .cer/.key pair (openssl verification that the key matches the certificate)
- Passphrase is verified by decrypting the .key during pair validation — if it fails, form shows error, nothing is saved
- On success, redirect to CSD detail view showing extracted data with a confirmation toast
- .key file stored encrypted in private storage, passphrase encrypted with Laravel Crypt
- NoCertificado, RFC, and validity dates extracted from .cer on upload

### Certificate display
- List table columns: NoCertificado, RFC, validity dates (inicio/fin), status badge, upload date
- Status badge color-coded: Active (green), Expiring soon (yellow), Expired (red), Inactive (gray)
- Detail view shows extracted fields only — no raw certificate data (subject, issuer, algorithm)
- CSD records are immutable once uploaded — no edit/replace functionality
- No file download capability — the system is not a file manager
- Navigation group: "Configuración" (separate from "Catálogos SAT")

### Multi-CSD handling
- Multiple CSDs can be stored simultaneously (useful during certificate renewal)
- Only one CSD can be active at a time for signing
- Explicit "Set as active" action button with confirmation — auto-deactivates the previous active CSD
- Soft delete for old/expired CSDs (audit trail — past invoices reference the signing CSD)
- Deactivating the only active CSD shows a warning ("No podrás timbrar facturas") but is allowed

### Expiry & validation UX
- 90-day expiry warning: yellow status badge on CSD list/detail + dismissible banner on Filament dashboard with days remaining
- Hard block before PAC when CSD is expired: "El CSD está expirado. Suba un nuevo certificado antes de timbrar."
- Hard block before PAC when no active CSD exists: "No hay CSD activo. Configure un certificado antes de timbrar."
- Both validations happen in the stamping pipeline (Phase 4 will consume `ValidateCsdExpiryAction`)

### Localization
- All user-facing messages in Spanish — this is a Mexican tax invoicing system

### Claude's Discretion
- Exact Filament form component choices (FileUpload config, TextInput for passphrase)
- Dashboard banner implementation (Filament widget vs custom component)
- Database column types and sizes for encrypted fields
- Exact openssl PHP functions for certificate parsing and pair validation
- Migration structure and naming

</decisions>

<specifics>
## Specific Ideas

- CSD immutability: once uploaded, the record cannot be edited — upload a new CSD and activate it instead
- Soft delete preserves audit trail for invoices signed with old CSDs
- The "Configuración" navigation group will also host the Emisor resource in Phase 3

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 02-gesti-n-de-csd*
*Context gathered: 2026-02-27*
