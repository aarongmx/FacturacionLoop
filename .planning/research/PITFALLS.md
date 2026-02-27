# Pitfalls Research

**Domain:** CFDI 4.0 Electronic Invoicing (SAT Mexico) — Laravel/PHP with eclipxe/cfdiutils and Finkok PAC
**Researched:** 2026-02-27
**Confidence:** HIGH (SAT Anexo 20 specs + cfdiutils source + Finkok API docs + PAC integration patterns)

---

## Critical Pitfalls

### Pitfall 1: Wrong RFC Validation for CFDI 4.0 — Strict Issuer/Receiver Match

**What goes wrong:**
CFDI 4.0 introduced strict validation: the `Nombre` (fiscal name) and `DomicilioFiscalReceptor` (ZIP code) of the receiver must **exactly match** what is registered in SAT's Registro Federal de Contribuyentes. If there is any mismatch — including extra spaces, abbreviations, or different capitalization — the PAC rejects the CFDI with error `CFDI40144` or similar catalog-match errors. This is the #1 cause of timbrado failures in CFDI 4.0 migrations.

**Why it happens:**
Developers carry over CFDI 3.3 logic where the receiver's name was optional or free-text. In CFDI 4.0, `NombreReceptor` and `DomicilioFiscalReceptor` (CP) are mandatory and validated against SAT's catalog. Developers store customer names from a CRM or input form that doesn't match the exact SAT fiscal name.

**How to avoid:**
- Require users to enter the receiver's **fiscal name exactly as it appears on their constancia de situación fiscal** — add a UI note in Filament.
- Store `domicilio_fiscal` (5-digit CP) as a mandatory field in the receptor model, validated against the State/Municipality SAT catalog.
- Pre-validate RFC format with a regex: issuers must be 12 chars (moral) or 13 chars (física). The RFC "XAXX010101000" (público en general) and "XEXX010101000" (foreign) are the only valid generic RFCs.
- Add a Pest test that stamps a CFDI in the Finkok sandbox with valid SAT test credentials to catch this before production.

**Warning signs:**
- Finkok returns error code referencing `NombreReceptor` or `DomicilioFiscalReceptor` during timbrado.
- Users report "mi cliente no acepta la factura" after delivery — the SAT's portal (`verificacfdi.sat.gob.mx`) shows the CFDI as invalid.
- Sandbox stamps succeed but production fails (sandbox does not enforce the fiscal name catalog).

**Phase to address:**
Receptor/Emisor data management phase — before any timbrado implementation. Build receptor model with `nombre_fiscal` and `domicilio_fiscal_cp` as required fields, not optional ones.

---

### Pitfall 2: CSD Private Key Password Stored in Plaintext

**What goes wrong:**
The `.key` file (private key of the CSD) is encrypted with the user's password. Many implementations store this password in the database in plaintext or with insufficient encryption. A database dump or SQL injection exposes all CSD passwords, allowing an attacker to stamp fraudulent CFDIs in the company's name — a tax fraud offense (delito fiscal).

**Why it happens:**
Developers focus on making the timbrado flow work first, then "plan to encrypt later." The CSD password is needed at seal time, making it tempting to store it ready-to-use. The `.key` file itself looks protected because it's encrypted, but that protection is defeated if the password is also compromised.

**How to avoid:**
- **Never store the CSD password in plaintext.** Use Laravel's `Crypt::encryptString($password)` before persisting. Decrypt only in-memory at seal time.
- Store the `.key` file in `storage/app/private` (not public), with filesystem permissions `0600`.
- Consider an alternative: derive a session-only seal flow where the user enters the CSD password on each use and it is only kept in an encrypted session for the duration of the seal operation — never persisted.
- Add an audit log every time the CSD is used to stamp a CFDI (who, when, which invoice UUID).
- Use Laravel's `APP_KEY`-backed encryption (AES-256-CBC via `Crypt`) — not a custom algorithm.

**Warning signs:**
- The `csd_password` column in the database has type `string` without any indication of encryption in the model's `$casts` (should be `'encrypted'`).
- The `.cer`/`.key` files are stored in `storage/app/public` (publicly accessible).
- The CSD password appears in application logs.

**Phase to address:**
CSD management phase — this is the first feature to build and must be built correctly from day one. Retrofitting encryption onto an existing plaintext store requires a migration that risks data loss and downtime.

---

### Pitfall 3: Wrong Cadena Original / Sello Calculation

**What goes wrong:**
The XML `Sello` attribute is computed by: (1) generating the cadena original via XSLT transformation, (2) signing with SHA-256 + RSA using the CSD private key, (3) base64-encoding the result. If any step uses the wrong encoding, wrong XSLT, or wrong key format, the PAC rejects with `CFDI33106` / `CFDI40106` ("El sello del comprobante es inválido"). This is opaque because the error says the sello is invalid, not why.

**Why it happens:**
Developers try to implement the seal manually instead of using `eclipxe/cfdiutils`'s `Credentials` class and `CfdiCreator40`. The XSLT must be the exact SAT-published version for CFDI 4.0 (`cadenaoriginal_TFD_1_1.xslt` and `cadenaoriginal_4_0_0.xslt`). Using cached or stale XSLTs, or wrong string encoding (UTF-8 vs ISO-8859-1), breaks the sello.

**How to avoid:**
- Use `eclipxe/cfdiutils` exclusively for XML building and sealing. Do not reimplement cadena original or sello manually.
- Specifically use `PhpCfdi\CfdiUtils\CfdiCreator40` and let it handle the XSLT transformation internally.
- Ensure XSLTs are sourced from the official SAT location or the ones bundled with cfdiutils — never edit them.
- Always validate the generated XML against SAT's `CfdiValidator` before sending to the PAC.
- Test the seal against Finkok's sandbox and verify the UUID comes back — that proves the sello is valid.

**Warning signs:**
- Finkok returns error code containing "sello" or "cadena original."
- The XML `Sello` attribute is empty or contains obvious ASCII garbage.
- The XSLT file path in your code points to a custom or vendor-copied file.

**Phase to address:**
XML generation and sealing phase — the very first timbrado pipeline implementation. Write a Pest integration test that builds a minimal CFDI, seals it with test CSD, and validates the sello before attempting PAC submission.

---

### Pitfall 4: Incorrect Timezone Handling — All CFDI Dates Must Be Mexico City Local Time

**What goes wrong:**
The `Fecha` attribute in CFDI must be in the **local time of the issuer's fiscal domicile**, formatted as `YYYY-MM-DDTHH:MM:SS` (ISO 8601 without timezone offset). SAT validates that the `Fecha` is not more than 72 hours before the stamp date, and not in the future. If the server runs in UTC (the Laravel/PHP default) and dates are stored or formatted without timezone conversion, CFDIs get rejected for "fecha fuera de rango" or worse, silently accepted with a wrong timestamp that causes problems during SAT audits.

**Why it happens:**
Laravel's default timezone is UTC. The developer uses `now()` or `Carbon::now()` without specifying the Mexican timezone. The XML gets `2026-02-27T06:00:00` instead of `2026-02-27T00:00:00` (for UTC-6 CST).

**How to avoid:**
- Set `config('app.timezone')` to `'America/Mexico_City'` in `config/app.php`. This makes `now()` return Mexico City local time by default.
- For Carta Porte and other complements requiring UTC timestamps, use `Carbon::now('UTC')` explicitly.
- Store all CFDI-related dates with timezone awareness. Use `timestampTz` in PostgreSQL migrations, not `timestamp`.
- Add a Pest unit test that verifies the generated CFDI `Fecha` attribute matches `America/Mexico_City` time.

**Warning signs:**
- CFDI `Fecha` values are exactly 5 or 6 hours off from the actual creation time.
- SAT validation returns "fecha no válida" or "fecha fuera del rango permitido."
- `config('app.timezone')` returns `'UTC'` in production.

**Phase to address:**
Application bootstrap / environment configuration — fix the timezone before building any CFDI date logic. Add a test that asserts the application timezone.

---

### Pitfall 5: Complement Namespace / XSD Schema Errors Breaking XML Validation

**What goes wrong:**
CFDI 4.0 complements (Pagos 2.0, Carta Porte 3.1, Comercio Exterior 2.0) each require precise `xmlns` namespace declarations and `xsi:schemaLocation` entries. Incorrect or missing namespace attributes cause SAT's XSD validation to fail with schema errors. Complement namespaces are version-specific: mixing Pagos 1.0 attributes with Pagos 2.0 namespace declarations is a common source of silent errors.

**Why it happens:**
Developers copy XML structure examples from online tutorials that use outdated complement versions (Pagos 1.0, Carta Porte 2.0) because those tutorials are more numerous. When eclipxe/cfdiutils is used correctly, it handles namespaces — but if developers bypass cfdiutils and build XML strings manually (tempting for complements), namespaces get wrong.

**How to avoid:**
- Use `eclipxe/cfdiutils` complement builders exclusively: `\PhpCfdi\CfdiUtils\Cfdi\Complements\` classes for Pagos, `\PhpCfdi\SatCfdi\` for CartaPorte (if using the sat-cfdi extension).
- Never use string concatenation or manual XML building for complement nodes.
- Always run `CfdiValidator::validate()` on the final XML before PAC submission. Log all validation errors with the full XML for debugging.
- Cross-reference the SAT's published XSD schemas to verify namespace URIs:
  - Pagos 2.0: `http://www.sat.gob.mx/Pagos20`
  - Carta Porte 3.1: `http://www.sat.gob.mx/CartaPorte31`
  - Comercio Exterior 2.0: `http://www.sat.gob.mx/ComercioExterior20`

**Warning signs:**
- PAC returns errors referencing "esquema" or "namespace."
- The `CfdiValidator` throws exceptions about unresolvable XSD schema locations.
- The generated XML has correct-looking content but the complement node is missing or misplaced.

**Phase to address:**
Each complement implementation phase — Pagos 2.0, Carta Porte 3.1, Comercio Exterior 2.0 should each have a dedicated validation test before integration tests against the PAC sandbox.

---

### Pitfall 6: Pagos 2.0 — DoctoRelacionado Importe/Equivalencia Rounding Errors

**What goes wrong:**
In Pagos 2.0, the `DoctoRelacionado` requires precise tax breakdown: `ImpSaldoAnt`, `ImpPagado`, `ImpSaldoInsoluto`, and `EquivalenciaDR`. SAT enforces that `ImpPagado + ImpSaldoInsoluto = ImpSaldoAnt` to the cent. PHP floating-point arithmetic produces values like `10000.000000000001` when doing tax calculations, which SAT rejects. This is the most common Pagos 2.0 failure in production.

**Why it happens:**
Monetary amounts in PHP are computed with `float` or `double`. Division for exchange rates (EquivalenciaDR) produces irrational repeating decimals. SAT requires exact 2-decimal precision on MXN amounts.

**How to avoid:**
- Use `bcmath` or a money library (e.g., `brick/money`) for all monetary calculations in Pagos 2.0. Never use native PHP `float` arithmetic for invoice amounts.
- Cast all currency amounts as `numeric(20,6)` in PostgreSQL and round to 2 decimal places only at the XML generation step, not at the DB storage step.
- The `EquivalenciaDR` for same-currency payments must be exactly `1` — not `1.000000` — the SAT schema validates the value string.
- Add Pest dataset tests with known MXN amounts and verify the Pagos 2.0 XML output matches expected totals to the cent.

**Warning signs:**
- Finkok returns errors referencing `ImpSaldoInsoluto` or `EquivalenciaDR`.
- Calculated amounts in logs show values like `999.9999999999`.
- PHP `var_dump(100.00 + 200.00 === 300.00)` returns `true` in your environment but not in edge cases.

**Phase to address:**
Pagos 2.0 complement phase — before any monetary calculation is implemented, establish a `MoneyCalculator` service using bcmath. This is not retrofix-friendly.

---

### Pitfall 7: Finkok API — Not Distinguishing "Already Stamped" from "Stamp Error"

**What goes wrong:**
Finkok's stamp API (stampcfdi) can return a specific fault code when the CFDI UUID already exists in their system (a duplicate submission). If your application treats this as a fatal error and does not extract the UUID from the response body, the invoice is marked as failed when it was actually successfully stamped. The inverse is also dangerous: treating a timbrado timeout as a success before confirming the UUID.

**Why it happens:**
Finkok's SOAP fault structure varies. Some error codes embed the already-stamped XML in the response. Developers who implement basic try/catch on the SOAP call miss the embedded UUID in the error response body, leaving the invoice in a broken state (not stamped in the app, but stamped at SAT).

**How to avoid:**
- Implement idempotent timbrado: before sending to Finkok, check if this CFDI's UUID already exists in your local `cfdis` table. If yes, skip the Finkok call.
- Parse Finkok responses for fault code `307` ("Este comprobante ya fue procesado") and extract the stamped XML from the response — it is returned even in this error case.
- Wrap all PAC calls in a database transaction: mark the CFDI as "pending" before the call, then update to "stamped" with UUID or "failed" with error details. Never leave it in "pending" indefinitely.
- Implement a reconciliation job that queries Finkok for CFDIs marked as "pending" older than 5 minutes and resolves their status.

**Warning signs:**
- Duplicate CFDIs appear in the database: same series/folio combination stamped twice.
- Invoices stuck in "pendiente de timbrado" status after a timeout.
- Finkok error logs show code `307` but the app marked the invoice as failed.

**Phase to address:**
Finkok integration phase — specifically in the timbrado queue job design. The job must be idempotent with proper state machine handling.

---

### Pitfall 8: CSD Certificate Expiry Not Monitored — Silently Breaks All Timbrado

**What goes wrong:**
CSD certificates are issued by SAT with an expiry of 2–4 years. When a CSD expires, every timbrado attempt fails with an error like "certificado vencido." Because expiry is predictable but silent, an expired CSD can block all invoicing for days while the user scrambles to renew. In CFDI 4.0, the PAC verifies the certificate chain against SAT's CRL (Certificate Revocation List) — a revoked CSD (due to an RFC update or certificate renewal) also causes this failure.

**Why it happens:**
The CSD is uploaded once, works for years, and nobody sets up an expiry alert. The application stores the `.cer` file but never reads the validity period from it.

**How to avoid:**
- When storing a CSD, parse the certificate's validity period using PHP's `openssl_x509_parse()`. Store `valid_from` and `valid_to` as database columns.
- Schedule a daily job (Artisan command) that checks all active CSDs for expiry within 30 days and sends an alert email.
- Display the CSD expiry date prominently in the Filament CSD management resource, with a color-coded status (green/yellow/red).
- Add a `isCsdExpired(): bool` method on the CSD model and check it before every timbrado attempt, failing fast with a clear user-facing error message.

**Warning signs:**
- CSD `valid_to` date is not stored in the database — only the raw `.cer` file.
- No scheduled job exists for CSD expiry monitoring.
- Users discover expired CSDs only when invoicing fails.

**Phase to address:**
CSD management phase — implement expiry tracking when the CSD upload feature is built.

---

### Pitfall 9: Carta Porte 3.1 — AutoTransporte vs TransporteAereo vs TransporteFerroviario Node Selection

**What goes wrong:**
Carta Porte 3.1 has mutually exclusive transport type nodes. Developers include both `AutoTransporte` and `TransporteAereo` nodes (or include the wrong one based on hardcoded defaults), causing an XSD validation error. The `TipoFigura` codes and `NumeroEconomico` format also differ by transport type. CFDI with wrong transport type passes syntactic validation but fails SAT's semantic validation.

**Why it happens:**
Carta Porte is the most structurally complex CFDI complement. With 40+ fields and 5 transport types, developers default to AutoTransporte (the most common) without building conditional logic for other types. The business may later require air or rail transport and the codebase has no branching.

**How to avoid:**
- Model the Carta Porte as a proper domain object with a `TransportType` enum (`AutoTransporte`, `TransporteAereo`, `TransporteFerroviario`, `TransporteMaritimo`, `TransportePipducto`).
- Use a strategy pattern or factory for complement node construction: each transport type has its own builder class.
- Build and test each transport type independently in Pest before combining them.
- Validate against Carta Porte's XSD locally (cfdiutils provides XSD validation tooling) before PAC submission.

**Warning signs:**
- The Carta Porte model has a single `transport_type` field but no conditional logic in the XML builder.
- The XML builder has `if ($type === 'auto')` inline logic — not a strategy pattern.
- Only AutoTransporte is tested; other types have no tests.

**Phase to address:**
Carta Porte 3.1 implementation phase — design the transport type abstraction before writing any XML generation code.

---

### Pitfall 10: Comercio Exterior 2.0 — Fraccion Arancelaria + ClavePedimento Mismatch with SAT Catalog

**What goes wrong:**
In Comercio Exterior 2.0, `FraccionArancelaria` values must match the active entries in SAT's catalog at the time of CFDI emission. Tariff classifications are updated annually by SAT (via DOF). Using a deprecated `FraccionArancelaria` causes PAC rejection. Additionally, `ClavePedimento` format (`AAAA/AAAAAA/AAAAAAA`) is specific and validated by format — a wrong hyphen placement fails the regex.

**Why it happens:**
The project already has a `TariffClassification` seeder from the initial setup. If this seeder was populated from an outdated source or if SAT updates its tariff catalog, the application's catalog diverges from SAT's current catalog. There is no update strategy.

**How to avoid:**
- Source the `FraccionArancelaria` catalog from SAT's official published catalog (available at `omawww.sat.gob.mx` as a CSV/Excel file), not from third-party sources.
- Add a `valid_from` / `valid_to` on the `TariffClassification` model to support catalog versioning.
- Implement an annual review process (Artisan command) to diff the stored catalog against SAT's published catalog and report discrepancies.
- Validate `ClavePedimento` format with a regex before generating the XML: `^[0-9]{4}\/[0-9]{1,2}\/[0-9]{7}$`.

**Warning signs:**
- `TariffClassification` seeder was populated from a GitHub repo or unofficial source.
- The `TariffClassification` table has no `valid_from`/`valid_to` columns.
- Comercio Exterior tests only use a hardcoded `FraccionArancelaria` that was never verified against the current SAT catalog.

**Phase to address:**
Comercio Exterior 2.0 phase — audit the existing `TariffClassification` seeder source and add catalog versioning before building the complement.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Store CSD password encrypted but in same DB as business data | Simpler architecture | Single DB breach exposes all CSDs | Never — use at minimum separate encryption key per CSD or session-only password |
| Skip pre-PAC XML validation (send directly to Finkok) | Fewer development steps | PAC calls cost money and time; errors are opaque without local validation | Never — always validate locally first |
| Use PHP `float` for invoice amounts | Simpler code | Rounding errors in Pagos 2.0 cause SAT rejections | Never for monetary calculations |
| Hardcode the SAT XSLT files in the project | No network dependency at runtime | XSLTs become stale when SAT updates schemas | Acceptable if a process exists to check for XSLT version updates |
| Single `cfdi` table for all CFDI types | Simpler schema | Carta Porte and Comercio Exterior have 40+ unique fields; schema becomes unmanageable | Acceptable only for a base CFDI table with complement-specific subtables |
| One PAC integration (Finkok only) without abstraction | Faster to build | Cannot add backup PAC without rewriting timbrado logic | Acceptable in v1 only if the PacInterface contract is in place from day one |
| Skip CSD expiry monitoring | One less feature | Silent production failure when CSD expires | Never — the cost of implementation is 2 hours; the cost of not having it is days of downtime |

---

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Finkok — stampcfdi SOAP | Using REST-style error handling on SOAP responses | Parse `SoapFault->faultcode` and `faultstring`; Finkok embeds the stamped XML in certain error responses |
| Finkok — cancel | Sending cancellation without `Motivo` (reason code) | CFDI 4.0 requires `Motivo` code: `01` (comprobante emitido con errores con relación), `02` (comprobante emitido con errores sin relación), `03` (no se llevó a cabo la operación), `04` (operación nominativa relacionada en una factura global) — wrong code causes rejection |
| Finkok — cancel | Cancelling without `FolioSustitucion` when Motivo=01 | When using Motivo `01`, the UUID of the replacement CFDI must be provided — this is required in CFDI 4.0 |
| eclipxe/cfdiutils | Passing the raw `.key` file bytes directly | Must use `Credentials::create($cerContent, $keyContent, $password)` — the library handles PEM conversion internally |
| eclipxe/cfdiutils | Building the complement node after calling `addSello()` | The sello must be the last operation. Build the full XML including complements, then call `addSello()` |
| SAT XSD validation | Downloading XSD schemas at runtime from SAT servers | SAT servers are frequently slow or unavailable; cache XSDs locally and update on a schedule |
| PostgreSQL + CFDI XML | Storing XML as `text` without encoding check | PostgreSQL requires valid UTF-8; if the XML contains non-UTF-8 characters from a user input, the insert fails — always enforce UTF-8 at the PHP level before storing |

---

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Synchronous timbrado in HTTP request | User waits 5–30s for timbrado; timeouts on slow PAC days | Move timbrado to a queued job; return "en proceso" status and poll/notify via email | From day one — Finkok SOAP calls are inherently slow |
| Loading all SAT catalog entries in a single Filament Select | Browser freezes when selecting ClaveProdServ (50k+ entries) | Use searchable selects with server-side filtering; lazy-load catalog entries | At 1,000+ entries in the select |
| Generating PDF + sending email synchronously during timbrado | Compound HTTP timeout (timbrado + PDF + email) | Queue PDF generation and email separately from timbrado | Always — especially if PDF uses a remote rendering service |
| No index on CFDI UUID column | UUID lookup (for verification/cancellation) does full table scan | Add `unique` index on `uuid` column in `cfdis` migration | At ~10,000 CFDIs |
| Validating CFDI XML against XSD on every request | Each validation call downloads or reads large XSD files | Cache the parsed XSD schema in memory (cfdiutils handles this, but ensure not reinitializing per request) | At ~100 validations/hour |

---

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| Storing `.key` file in public storage | Any web user can download the private key | Always use `storage/app/private` (never `storage/app/public`) for CSD files; validate the storage path in the upload handler |
| Logging CSD password to Laravel logs during debugging | Password appears in log files accessible to ops/hosting | Never log the CSD password; log only a `[REDACTED]` placeholder; add a test that asserts the password does not appear in logs after a stamp operation |
| No audit trail for CFDI stamping | Cannot detect fraudulent invoice generation | Log all stamp operations: user ID, timestamp, CFDI UUID, emisor RFC — store in an immutable audit table |
| Exposing Finkok API credentials in version control | Attacker can stamp CFDIs under your PAC account | Store Finkok credentials in `.env` only; add `.env` to `.gitignore`; add a pre-commit hook that blocks commits containing `FINKOK_` env values |
| No rate limiting on CFDI creation endpoint | A bug or malicious user can generate thousands of CFDIs in seconds (SAT liability) | Apply queue throttling: max N CFDI submissions per minute per RFC; add Filament form-level CSRF protection |
| Allowing direct download of `.cer`/`.key` files via storage URL | File permissions bypass allows direct file download | Never expose CSD files via any public URL; serve them programmatically through a controller that enforces authorization |

---

## UX Pitfalls

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Showing Finkok's raw SOAP error to the user | User sees "CFDI33106: El sello del comprobante es inválido" — meaningless | Map PAC error codes to human-readable messages in Spanish; log the technical error for developers separately |
| No timbrado status feedback for async jobs | User submits invoice and sees nothing; refreshes repeatedly; submits again (creating duplicates) | Use Filament notifications + Livewire polling to show "timbrado en proceso" status; prevent re-submission while pending |
| No CSD expiry indicator | User discovers expired CSD when a customer complains about missing invoice | Show expiry date with color-coded badge in the Filament CSD resource; display warning in the invoice creation form |
| Requiring re-entry of CSD password on every invoice | Friction for high-volume users | Offer session-cached password option (store encrypted in session for X hours, not in DB) with explicit user consent |
| PDF shows "CFDI en proceso" indefinitely if timbrado fails | User prints/sends a PDF that has no valid QR code or UUID | Never generate the final PDF until the CFDI has a valid UUID; generate a "borrador" preview only before timbrado |

---

## "Looks Done But Isn't" Checklist

- [ ] **CSD Upload:** File stored, but `valid_to` parsed and saved? — verify `openssl_x509_parse()` is called and the expiry date is in the DB
- [ ] **XML Generation:** cfdiutils `CfdiCreator40` produces XML, but is `CfdiValidator::validate()` also called? — verify validation errors are caught and logged before PAC submission
- [ ] **Timbrado Flow:** Finkok returns a UUID, but is the stamped XML from Finkok's response (not the pre-stamp XML) what gets stored? — verify the response XML (with `TimbreFiscalDigital`) is what's persisted
- [ ] **Cancelation:** SAT accepts the cancellation request, but does the app update the CFDI status to `cancelled`? — verify the status update and the cancellation UUID are persisted from the Finkok response
- [ ] **Pagos 2.0:** Complement node is present in the XML, but are `ImpSaldoAnt`, `ImpPagado`, `ImpSaldoInsoluto` calculated from the related CFDI's original amounts, not user-input? — verify the related CFDI UUID is resolved and its balances are fetched from DB
- [ ] **Carta Porte:** Transport data is present, but are the `Operadores` (drivers with RFC) also included? — `Operadores` with valid RFC and `TipoFigura=01` is mandatory for AutoTransporte
- [ ] **Email Send:** Email with XML + PDF is sent, but are both attachments correct file types? — verify `Content-Type: application/xml` for XML and `application/pdf` for PDF (not `text/plain`)
- [ ] **Multi-PAC Abstraction:** PacInterface is defined, but does switching the PAC in config actually route to a different implementation? — verify with a Pest test that both implementations satisfy the interface contract

---

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| CSD password stored in plaintext | HIGH | 1. Migrate plaintext passwords through `Crypt::encryptString()` in a migration with a transaction. 2. Update all reads to `Crypt::decryptString()`. 3. Rotate the APP_KEY and re-encrypt all values. 4. Audit logs for any exposure window. |
| Wrong RFC/NombreFiscal for receivers | MEDIUM | 1. Identify all CFDIs stamped with wrong receiver data via UUID lookup at SAT. 2. Cancel and reissue each one (may require receiver's consent for substitution). 3. Fix the receptor model validation. |
| Duplicate CFDIs from retry without idempotency | HIGH | 1. Query Finkok for all UUIDs for your RFC. 2. Match against DB records. 3. Void duplicates with Motivo=03 (operación no realizada). 4. Add idempotency key to timbrado job. |
| Expired CSD discovered in production | MEDIUM | 1. Renew CSD at SAT immediately (CIEC login required). 2. Upload new `.cer`/`.key` via Filament. 3. Re-queue any failed timbrado jobs from the expiry window. |
| PAC credentials in Git history | HIGH | 1. Rotate Finkok credentials immediately. 2. Use `git filter-repo` to scrub the credentials from history. 3. Force-push and notify all collaborators. 4. Audit Finkok account for unauthorized stamp activity. |
| Stale XSD/XSLT causing silent validation failures | LOW | 1. Update cfdiutils to latest version (XSLTs are bundled). 2. Re-validate all recently generated CFDIs. 3. Resend any that failed PAC validation. |

---

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Wrong RFC / Nombre Receptor | Receptor/Emisor data management | Pest test: create receptor with missing `domicilio_fiscal_cp` and assert validation fails |
| CSD password in plaintext | CSD management (first feature) | Pest test: assert `csd_password` column uses Laravel encrypted cast; assert plaintext never appears in logs |
| Wrong Cadena Original / Sello | XML generation & sealing | Pest integration test: seal a CFDI with SAT test CSD and assert sello is non-empty and Finkok sandbox accepts it |
| Timezone errors in CFDI Fecha | App bootstrap / environment | Pest test: assert `config('app.timezone')` === `'America/Mexico_City'`; assert generated CFDI `Fecha` matches Mexico City time |
| Complement namespace / XSD errors | Each complement phase | Pest test per complement: validate generated XML against SAT XSD before PAC submission |
| Pagos 2.0 rounding errors | Pagos 2.0 phase | Pest dataset test: verify bcmath calculation of `ImpSaldoInsoluto` for 20 known MXN amounts |
| Finkok duplicate stamp handling | Finkok integration phase | Pest test: mock Finkok fault code 307 and assert the app extracts the UUID from the response |
| CSD expiry not monitored | CSD management phase | Pest test: assert CSD model stores `valid_to`; assert scheduled job sends alert when expiry < 30 days |
| Carta Porte transport type selection | Carta Porte 3.1 phase | Pest tests for all 5 transport types: verify correct XSD node is generated per type |
| Comercio Exterior stale catalog | Comercio Exterior 2.0 phase | Pest test: assert `TariffClassification` seeder source is documented and `valid_from` is present |

---

## Sources

- SAT Anexo 20 — CFDI 4.0 Technical Specification (confidence: HIGH — primary SAT documentation)
  `https://omawww.sat.gob.mx/tramitesyservicios/Paginas/documentos/Anexo_20_Guia_de_llenado_CFDI.pdf`
- eclipxe/CfdiUtils GitHub (confidence: HIGH — primary library source)
  `https://github.com/eclipxe13/CfdiUtils`
- Finkok API Documentation (confidence: HIGH — official PAC documentation)
  `https://wiki.finkok.com/`
- SAT Pagos 2.0 Technical Guide (confidence: HIGH — SAT official)
  `https://omawww.sat.gob.mx/tramitesyservicios/Paginas/complemento_pagos.htm`
- SAT Carta Porte 3.1 Technical Guide (confidence: HIGH — SAT official)
  `https://omawww.sat.gob.mx/tramitesyservicios/Paginas/carta_porte.htm`
- SAT Comercio Exterior 2.0 Technical Guide (confidence: HIGH — SAT official)
  `https://omawww.sat.gob.mx/tramitesyservicios/Paginas/comercio_exterior.htm`
- PHP bcmath documentation (confidence: HIGH — official PHP docs)
  `https://www.php.net/manual/en/book.bc.php`
- Laravel Encryption documentation (confidence: HIGH — official Laravel docs)
  `https://laravel.com/docs/12.x/encryption`
- Known community pitfalls from PHP CFDI integration forums and GitHub issues on eclipxe/cfdiutils (confidence: MEDIUM — multiple sources)

---
*Pitfalls research for: CFDI 4.0 electronic invoicing — FacturacionLoop*
*Researched: 2026-02-27*
