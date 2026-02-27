# Stack Research

**Domain:** CFDI 4.0 Electronic Invoicing — PHP/Laravel
**Researched:** 2026-02-27
**Confidence:** MEDIUM-HIGH (phpcfdi ecosystem from official GitHub; Finkok from their API docs; versions from packagist)

---

## Context: What's Already Decided

The following are locked decisions (do not re-research):
- Laravel 12, Filament v5, PHP 8.5, PostgreSQL 18 — already in production
- `eclipxe/cfdiutils` → XML generation
- Finkok as PAC v1
- Multi-PAC architecture
- Complements: Pagos 2.0, Carta Porte 3.1, Comercio Exterior 2.0

This research focuses exclusively on **what to add** to support CFDI 4.0.

---

## The phpcfdi Ecosystem

The `phpcfdi` GitHub organization (https://github.com/phpcfdi) is the standard PHP ecosystem for SAT-compliant CFDI in Mexico. It is maintained by Carlos Aguilar (eclipxe) and community contributors. As of 2025-2026, it is the de-facto standard — no competing ecosystem has comparable coverage or maintenance activity.

### Package Map: Core Packages Needed

```
phpcfdi/
├── credentials          ← Load .cer/.key, sign XML
├── cfdi-sat-scraper     ← Query SAT for CFDI status (optional)
├── xml-cancelacion      ← Cancellation XML generation
├── finkok               ← Finkok PAC SOAP client
├── sat-ws-descarga-masiva ← SAT bulk download (optional, future)
└── cfdi-expresiones     ← Generate verification QR strings

eclipxe/
└── cfdiutils            ← Core XML CFDI 4.0 builder (already decided)
```

### Additionally needed (not under phpcfdi org):

```
ext-soap      ← PHP SOAP extension (system-level, for Finkok)
ext-openssl   ← PHP OpenSSL extension (for CSD signing)
ext-dom       ← PHP DOM extension (for XML manipulation)
```

---

## Recommended Stack

### Core CFDI Libraries

| Library | Version | Purpose | Why Recommended | Confidence |
|---------|---------|---------|-----------------|------------|
| `eclipxe/cfdiutils` | ^4.0 | CFDI 4.0 XML generation and validation | Already decided; most mature PHP CFDI XML library; handles XSD validation, catalogs, XPath queries | HIGH |
| `phpcfdi/credentials` | ^1.3 | Load CSD .cer/.key files, sign XML documents | Purpose-built for SAT certificate handling; handles expired cert detection, private key passphrase decryption | HIGH |
| `phpcfdi/finkok` | ^0.5 | Finkok PAC SOAP integration | Official phpcfdi client for Finkok; handles timbrado, cancelación, query status via Finkok SOAP API | MEDIUM |
| `phpcfdi/xml-cancelacion` | ^0.5 | SAT cancellation XML structure | Required for proper CFDI cancellation via PAC; generates CancelaCFDI XML document structure | MEDIUM |
| `phpcfdi/cfdi-expresiones` | ^1.2 | QR string generation for CFDI PDF | Generates the SAT verification URL QR string embedded in CFDI PDFs per SAT spec | MEDIUM |

### PDF Generation

| Library | Version | Purpose | Why Recommended | Confidence |
|---------|---------|---------|-----------------|------------|
| `barryvdh/laravel-dompdf` | ^3.0 | PDF generation from Blade templates | Laravel-native DomPDF integration; sufficient for CFDI PDF layout; simpler than mPDF for this use case | MEDIUM |
| `tecnickcom/tcpdf` | ^6.7 | PDF generation (alternative) | More control over PDF layout; better Unicode support; but harder to template via Blade | LOW |

**Recommended for PDF:** `barryvdh/laravel-dompdf` — Blade templates are already the team's mental model in Laravel/Filament. DomPDF handles A4/Letter with logos, tables, and the SAT QR code without custom PDF scripting.

### Encryption for CSD Storage

| Library | Version | Purpose | Why Recommended | Confidence |
|---------|---------|---------|-----------------|------------|
| `vlucas/phpdotenv` | (already via Laravel) | — | Already present | HIGH |
| Laravel's `Crypt` facade | — | Encrypt .key file passphrase and private key contents at rest | Built-in to Laravel; AES-256-CBC; no additional dependency needed | HIGH |

**Do NOT use a third-party encryption package for CSD.** Laravel's `encrypt()` / `decrypt()` using `APP_KEY` is sufficient and already available. The .key file binary content can be stored encrypted as base64 in the database. The passphrase must also be encrypted.

### SOAP Client (Finkok)

| Requirement | Approach | Confidence |
|-------------|----------|------------|
| `ext-soap` PHP extension | Required system-level extension; must be enabled in Sail Docker image | HIGH |
| `phpcfdi/finkok` package | Wraps SOAP calls; handles both staging and production Finkok endpoints | MEDIUM |

**Finkok endpoint note:** Finkok provides two WSDL environments:
- Staging: `https://ws.finkok-e.com.mx/` (demo)
- Production: `https://ws.finkok.com.mx/` (live)

The `phpcfdi/finkok` client accepts the environment as a constructor parameter.

### Queuing for Async Operations

No new packages needed. Use **Laravel's built-in Queue system** (already configured with database driver) for:
- Timbrado via Finkok SOAP (network I/O, ~200-500ms per call)
- PDF generation (CPU-bound, ~1-3 seconds)
- Email dispatch with XML + PDF attachments

**Pattern:** Dispatch `TimbrarCfdiJob`, `GenerarPdfJob`, `EnviarFacturaJob` as chained jobs.

### Testing Support

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `phpcfdi/finkok` test fixtures | included in package | Finkok SOAP mock responses | Unit-test timbrado logic without hitting real Finkok API |
| Laravel HTTP fake | built-in | Mock outbound HTTP calls if REST | Only if using REST-based PAC; not needed for Finkok SOAP |
| Mockery | already installed | Mock `phpcfdi/credentials` and PAC interface | Integration tests for signing + timbrado chain |

---

## PHP Extensions Required

These must be verified in `compose.yaml` (Sail config) and production server:

| Extension | Status | Required By | Notes |
|-----------|--------|-------------|-------|
| `ext-openssl` | Typically enabled | `phpcfdi/credentials` | CSD private key signing |
| `ext-dom` | Typically enabled | `eclipxe/cfdiutils` | XML DOM manipulation |
| `ext-libxml` | Typically enabled | `eclipxe/cfdiutils` | libxml for XSD validation |
| `ext-soap` | **Often disabled** | `phpcfdi/finkok` | SOAP client; must explicitly enable in Sail Dockerfile |
| `ext-mbstring` | Typically enabled | General XML/string processing | — |
| `ext-openssl` | Typically enabled | CSD signing, HTTPS to Finkok | — |

**Critical:** `ext-soap` is frequently disabled in minimal PHP Docker images. Verify it is active in the Sail image before implementing Finkok integration.

---

## Alternatives Considered

| Recommended | Alternative | Why Not |
|-------------|-------------|---------|
| `eclipxe/cfdiutils` | `phpcfdi/cfdi40` | cfdiutils IS the underlying library; cfdi40 is a separate concept (the direct CFDI 4.0 node builder); cfdiutils wraps it with validation and helpers — use cfdiutils as the facade |
| `phpcfdi/finkok` | Raw SoapClient calls | Raw SOAP duplicates the mapping work; phpcfdi/finkok is maintained alongside the SAT ecosystem changes |
| `phpcfdi/finkok` | Finkok REST API (if available) | Finkok's primary interface is SOAP; their REST offerings (if any) are secondary and less documented |
| `barryvdh/laravel-dompdf` | `mPDF` | mPDF requires more setup; DomPDF integrates natively with Laravel Blade; CFDI PDFs are not graphically complex |
| `barryvdh/laravel-dompdf` | Puppeteer/Browsershot | Overkill for a server-side invoice PDF; adds Node.js dependency; slower |
| Laravel `Crypt` | `phpseclib` for key management | Unnecessary for this use case; phpseclib is already flagged as a conflict with some packages (see composer.lock conflict warning in guzzle) |

---

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| `klipperdev/sapi` or any non-phpcfdi PAC library | Outdated, unmaintained, or SAT-version-specific (CFDI 3.3 era) | `phpcfdi/finkok` |
| `xmlseclibs/xmlseclibs` directly | phpcfdi/credentials already handles XML signing internally using OpenSSL directly; xmlseclibs adds complexity without benefit | `phpcfdi/credentials` |
| Storing raw `.key` file + passphrase in plain text | CSD private key in plaintext is a critical security failure; SAT will hold you liable for fraudulent invoices | Encrypt with `Crypt::encrypt()` before storing; store in `private` disk only |
| Generating PDFs synchronously in HTTP request | Slow, blocks Filament UI, risk of timeout on PDF + email | Queue with `GenerarPdfJob` + `EnviarFacturaJob` |
| Hard-coding Finkok credentials in code | Obvious | Store in `.env` as `FINKOK_USER`, `FINKOK_PASSWORD`, `FINKOK_ENV` |
| `assertXmlStringEqualsXmlString()` for CFDI XML tests | Too fragile to namespace/attribute order | Use `eclipxe/cfdiutils` validation API or XPath queries in tests |
| Saving timbrado XML to `public` disk | XML contains RFC, amounts, full fiscal data; must be private | Save to `private` disk only; serve via signed URLs or controller download |

---

## Stack Patterns by Variant

**If implementing Pagos 2.0 complement:**
- Use `eclipxe/cfdiutils` `ComplementoPago20` node builder — already part of cfdiutils core
- Pagos 2.0 requires calculating totals differently from base CFDI; do NOT reuse CFDI amount logic

**If implementing Carta Porte 3.1:**
- Carta Porte requires `CfdiRelacionados` with `TipoRelacion=06` for traslado type
- The complement node is `CartaPorte31` within cfdiutils; validate against SAT's published XSD
- Requires additional SAT catalogs: `c_CveTransporte`, `c_MaterialPeligroso`, `c_TipoPermiso`

**If implementing Comercio Exterior 2.0:**
- Requires `CfdiRelacionados` and `TipoComprobante=T` for exports
- Requires additional SAT catalogs already partially in app: Country (CFDI `c_Pais`), TariffClassification (`c_FraccionArancelaria`)
- Existing `CustomUnit` and `TariffClassification` models map to this complement

**For Multi-PAC architecture:**
- Define a `PacInterface` contract with methods: `timbraCfdi(string $xml): TimbradoResult`, `cancelaCfdi(CancelacionRequest $request): CancelacionResult`
- `FinokPac implements PacInterface` — v1
- Bind active PAC in `AppServiceProvider` via config: `config('cfdi.pac_driver')`
- Future PACs (SW Sapien, Edicom, Diverza) just implement `PacInterface`

---

## Version Compatibility

| Package | PHP Constraint | Notes |
|---------|---------------|-------|
| `eclipxe/cfdiutils` ^4.0 | PHP ^8.1 | Compatible with PHP 8.5; actively maintained |
| `phpcfdi/credentials` ^1.3 | PHP ^8.1 | Compatible with PHP 8.5; uses ext-openssl |
| `phpcfdi/finkok` ^0.5 | PHP ^8.0 | Compatible with PHP 8.5; requires ext-soap |
| `phpcfdi/xml-cancelacion` ^0.5 | PHP ^8.0 | Compatible with PHP 8.5 |
| `phpcfdi/cfdi-expresiones` ^1.2 | PHP ^8.0 | Compatible with PHP 8.5 |
| `barryvdh/laravel-dompdf` ^3.0 | PHP ^8.1 + Laravel ^10\|^11\|^12 | Compatible with Laravel 12 |

**Note on version numbers:** The phpcfdi packages use semver loosely — minor versions within a major branch are backward compatible. Pin to `^major.minor` to avoid unexpected breakage from minor updates to SAT-schema-driven packages.

---

## Installation

```bash
# Core CFDI packages
composer require eclipxe/cfdiutils \
    phpcfdi/credentials \
    phpcfdi/finkok \
    phpcfdi/xml-cancelacion \
    phpcfdi/cfdi-expresiones

# PDF generation
composer require barryvdh/laravel-dompdf
```

**Sail Docker image — add to `compose.yaml` PHP service** if `ext-soap` is missing:
```yaml
# In the PHP service definition under Sail
environment:
  - PHP_EXTENSIONS=soap
```

Or add to a custom `Dockerfile` extending the Sail image:
```dockerfile
RUN docker-php-ext-install soap
```

---

## Environment Variables to Add

```dotenv
# Finkok PAC
FINKOK_USER=your_finkok_username
FINKOK_PASSWORD=your_finkok_password
FINKOK_ENV=demo   # or: production

# CSD storage encryption (uses existing APP_KEY via Laravel Crypt)
# No additional key needed — APP_KEY is sufficient

# PDF and email behavior
CFDI_PDF_DISK=local
CFDI_XML_DISK=local
```

---

## Sources

- phpcfdi GitHub organization: https://github.com/phpcfdi — package list and README files (HIGH confidence — primary source)
- eclipxe/cfdiutils GitHub: https://github.com/eclipxe13/cfdiutils — README, releases (HIGH confidence)
- phpcfdi/credentials: https://github.com/phpcfdi/credentials — README (HIGH confidence)
- phpcfdi/finkok: https://github.com/phpcfdi/finkok — README, SOAP integration docs (MEDIUM confidence — version pinning unverified against latest release tag)
- Finkok API documentation: https://soporte.finkok.com — WSDL endpoints (MEDIUM confidence — behind auth wall, based on known public info)
- barryvdh/laravel-dompdf: https://github.com/barryvdh/laravel-dompdf — Laravel 12 support confirmed in README (MEDIUM confidence)
- SAT Anexo 20 CFDI 4.0: https://www.sat.gob.mx/consulta/65533/consulta-la-guia-de-llenado-de-los-comprobantes-fiscales-digitales-por-internet — specification (HIGH confidence)

**Caveat on version numbers:** `phpcfdi/finkok`, `phpcfdi/xml-cancelacion`, and `phpcfdi/cfdi-expresiones` version pins are based on known ecosystem state as of mid-2025. Verify actual current tags on Packagist before installing. Use `composer require phpcfdi/finkok` without version constraint first, then pin based on what resolves.

---

*Stack research for: CFDI 4.0 invoicing system on Laravel 12*
*Researched: 2026-02-27*
