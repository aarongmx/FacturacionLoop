# Project Research Summary

**Project:** FacturacionLoop
**Domain:** CFDI 4.0 Electronic Invoicing (Facturacion Electronica Mexico) — Laravel 12 + Filament v5
**Researched:** 2026-02-27
**Confidence:** HIGH

## Executive Summary

FacturacionLoop is a single-company, SAT-compliant electronic invoicing system built on an existing Laravel 12 / Filament v5 / PostgreSQL 18 stack. The project is not a SaaS product competing on features — it is a legal compliance system where the primary constraint is regulatory, not competitive. The dominant PHP library ecosystem for CFDI in Mexico is the `phpcfdi` GitHub organization (`eclipxe/cfdiutils` already decided; `phpcfdi/credentials`, `phpcfdi/finkok`, `phpcfdi/xml-cancelacion`, `phpcfdi/cfdi-expresiones` are the remaining additions). There is no credible competing ecosystem. The PAC for v1 is Finkok via SOAP, abstracted behind a `PacServiceInterface` from day one to satisfy the multi-PAC project requirement.

The recommended architecture is a layered service approach: a `CfdiBuilderService` composes the CFDI XML using `eclipxe/cfdiutils`, a `CsdManagerService` handles encrypted CSD credential loading, a `PacServiceInterface` / `FinkokPacDriver` handles stamping, and isolated `ComplementBuilderInterface` implementations handle Pagos 2.0, Carta Porte 3.1, and Comercio Exterior 2.0. All PAC calls and PDF/email operations must be queued — synchronous HTTP handling of Finkok SOAP calls is a primary failure mode. The strict dependency chain is: `SAT Catalogs → CSD → Issuer/Receiver → CFDI Base → Post-Stamp Pipeline → Complements`. Building out of this order wastes effort.

The three critical risks are: (1) CSD private key and password stored in plaintext — a tax fraud liability that must be encrypted from day one using Laravel `Crypt`; (2) CFDI 4.0's strict receiver fiscal data matching (RFC + legal name + postal code must match SAT's registry exactly) — the #1 cause of timbrado rejections in CFDI 4.0 migrations; and (3) floating-point arithmetic in Pagos 2.0 balance calculations — SAT enforces exact centavo precision and PHP native floats will cause production failures. All three require proactive design decisions, not retrofits.

---

## Key Findings

### Recommended Stack

The stack is largely locked by existing production choices. What remains to be added is the `phpcfdi` ecosystem for CFDI operations: `phpcfdi/credentials` for CSD signing, `phpcfdi/finkok` for PAC SOAP integration, `phpcfdi/xml-cancelacion` for cancellation XML, and `phpcfdi/cfdi-expresiones` for QR string generation. PDF generation should use `barryvdh/laravel-dompdf` (Blade-based, Laravel-native, no extra dependency complexity). CSD encryption uses Laravel's built-in `Crypt` facade — no third-party encryption package is needed. The one infrastructure risk is `ext-soap`, which is frequently disabled in minimal Docker images and must be explicitly enabled in the Sail Docker configuration before any Finkok integration work begins.

**Core technologies:**
- `eclipxe/cfdiutils ^4.0`: CFDI 4.0 XML generation and validation — already decided; de-facto PHP standard for SAT-compliant XML
- `phpcfdi/credentials ^1.3`: CSD .cer/.key loading and XML signing — purpose-built for SAT certificate handling
- `phpcfdi/finkok ^0.5`: Finkok PAC SOAP client — official phpcfdi client for timbrado and cancellation via SOAP
- `phpcfdi/xml-cancelacion ^0.5`: SAT cancellation XML structure — required for proper CFDI cancellation
- `phpcfdi/cfdi-expresiones ^1.2`: QR string generation for CFDI PDFs — SAT-spec verification URL
- `barryvdh/laravel-dompdf ^3.0`: PDF generation from Blade templates — Laravel-native, no additional complexity
- Laravel `Crypt` facade: CSD password and .key file encryption at rest — built-in, no additional dependency
- Laravel Queue (database driver, existing): async timbrado, PDF, email pipeline — already configured

### Expected Features

The feature scope is defined by SAT legal requirements first. All three complements (Pagos 2.0, Carta Porte 3.1, Comercio Exterior 2.0) are in-scope for v1 because they are legally required for the company's actual operations — not optional enhancements.

**Must have — SAT rejects without these:**
- CFDI 4.0 Ingreso / Egreso / Traslado with all required fields (Emisor, Receptor with CFDI 4.0 strictness, Conceptos, taxes)
- SAT catalogs not yet seeded: c_RegimenFiscal, c_UsoCFDI, c_FormaPago, c_ClaveProdServ (~53k rows), c_ClaveUnidad (~2k rows), c_ObjetoImp — without these no form can be populated
- CSD upload and secure storage (.cer, .key, password encrypted) — hard blocker for any signing
- Finkok PAC stamping (timbrado) — creates the legal UUID; no UUID = no legal CFDI
- Cancellation with Motivo code — legally required capability
- Complemento Pagos 2.0 — required for all PPD (deferred payment) invoices
- Complemento Carta Porte 3.1 — required for transport operations (enforcement: Oct 2024)
- Complemento Comercio Exterior 2.0 — required for export operations
- Stamped XML and PDF storage + email delivery — legal retention and commercial practice

**Should have — operational advantage:**
- Customer (Receptor) catalog — avoid re-entering fiscal data per invoice
- Product/Service catalog — avoid re-entering line items per invoice
- Invoice list with status filter (draft / stamped / cancelled) — operational necessity in Filament
- CSD expiry indicator and alert — prevents silent production failure
- Related document linking (CfdiRelacionados) — required for Egreso against an Ingreso

**Defer to v1.x:**
- Cancellation acceptance polling (async SAT confirmation)
- RFC lookup via Finkok LCO API
- Bulk XML download for audits

**Defer to v2+:**
- ERP API integration endpoints
- PDF template customization (branding)
- Global invoice (Factura Global) for retail
- Multiple PAC simultaneous routing (interface contract built in v1; switch manually)

### Architecture Approach

The system uses a four-layer architecture: Filament v5 presentation layer (forms, actions, resources), a service layer (`CfdiBuilderService`, `CsdManagerService`, `PacServiceInterface`, `InvoicePdfService`, complement builders), a domain/model layer (Invoice, InvoiceItem, Issuer, Receiver, CsdKey, complement models, SAT catalog models), and infrastructure (PostgreSQL, private filesystem for XML/PDF/CSD, Finkok SOAP, Laravel queue). Filament actions must only dispatch queued jobs — never call PAC or PDF services synchronously. Complement builders use a tagged collection pattern with `supports()` / `attachTo()` so adding a new complement does not touch the core builder.

**Major components:**
1. `CfdiBuilderService` — composes full CFDI 4.0 XML via eclipxe/cfdiutils, iterates complement builders
2. `CsdManagerService` — loads and decrypts CSD from private storage; provides signing credential
3. `PacServiceInterface` / `FinkokPacDriver` — stamps and cancels CFDIs; swappable driver via config
4. Complement builders (`PagosComplementBuilder`, `CartaPorteComplementBuilder`, `ComercioExteriorComplementBuilder`) — isolated per complement type
5. Queued job pipeline (`StampInvoiceJob` → `GenerateInvoicePdfJob` → `SendInvoiceEmailJob`) — all PAC and post-stamp operations async
6. `InvoiceStatus` enum state machine — enforces valid transitions: Draft → Stamped → CancelRequested → Cancelled
7. SAT Catalog models (FiscalRegime, CfdiUse, PaymentForm, ProductServiceKey, SatUnit, etc.) — prerequisite for all form dropdowns

### Critical Pitfalls

1. **CFDI 4.0 strict receiver data matching** — RFC + fiscal legal name + ZIP code must exactly match SAT's registry. Store `nombre_fiscal` and `domicilio_fiscal_cp` as required fields in the Receptor model. Add a Filament UI note instructing users to copy the name exactly from their constancia de situacion fiscal. Test in Finkok sandbox (note: sandbox does NOT enforce name catalog; production does).

2. **CSD password and .key stored in plaintext** — a tax fraud liability. Use Laravel `Crypt::encryptString()` before persisting the password. Store the .key file content encrypted in `storage/app/private`. Use Laravel's `'encrypted'` model cast. Never log the CSD password. Implement this at the CSD upload feature from day one — retrofitting encryption is high cost and risk.

3. **Floating-point arithmetic in Pagos 2.0** — SAT enforces exact centavo precision. `ImpSaldoAnt - ImpPagado = ImpSaldoInsoluto` must be arithmetically exact. PHP native floats fail this. Use `bcmath` extension for all monetary calculations in the Pagos 2.0 complement. Never use native float arithmetic for invoice amounts. Establish a `MoneyCalculator` service before implementing Pagos 2.0.

4. **Synchronous PAC calls in HTTP request** — Finkok SOAP calls take 200ms–30s depending on PAC load. Running them synchronously blocks the Filament UI and causes timeouts. Always dispatch `StampInvoiceJob` as a queued job. Show `InvoiceStatus::Processing` in the UI and poll for completion. Finkok returns an "already stamped" fault code (307) with the UUID in the response body — parse it correctly or invoices get stuck in a broken state.

5. **CSD certificate expiry not monitored** — CSDs expire after 2-4 years. Parse `valid_to` from the .cer file via `openssl_x509_parse()` when the CSD is uploaded. Store the expiry date in the database. Schedule a daily job to alert before expiry. Display the expiry with a color-coded badge in Filament. Check expiry before every stamp operation.

6. **Complement namespace and XSD errors** — Pagos 2.0, Carta Porte 3.1, and Comercio Exterior 2.0 require precise `xmlns` namespace declarations matching the exact complement version. Mixing version namespaces silently generates invalid XML. Use `eclipxe/cfdiutils` complement builders exclusively — never build complement XML by string concatenation. Run `CfdiValidator::validate()` on the final XML before every PAC submission.

---

## Implications for Roadmap

Based on the strict dependency chain identified in ARCHITECTURE.md and feature blockers in FEATURES.md, the phase structure must follow infrastructure-to-feature order. Complements must not be built until the core CFDI pipeline is solid.

### Phase 1: SAT Catalogs and Infrastructure Foundation

**Rationale:** Every Filament form select requires catalog data. This is the root dependency. Without seeded catalogs, no CFDI form can render valid options. This phase has no external API calls and no encryption complexity — it is the safest starting point.

**Delivers:** All missing SAT catalog tables seeded and browsable in Filament (c_RegimenFiscal, c_UsoCFDI, c_FormaPago, c_ClaveProdServ, c_ClaveUnidad, c_ObjetoImp, c_TipoRelacion, c_Municipio). Application timezone set to `America/Mexico_City`. `ext-soap` verified in Sail Docker image. Base Invoice and InvoiceItem models and migrations.

**Addresses:** SAT catalog blockers from FEATURES.md; Timezone pitfall from PITFALLS.md.

**Avoids:** Starting CFDI form before dropdowns have valid options; timezone mismatches in CFDI Fecha.

### Phase 2: CSD Management

**Rationale:** CSD upload and encrypted storage is a hard blocker for all signing. It is also the highest-risk security surface — it must be built correctly before any timbrado feature work begins. Building this second (before CFDI creation UI) allows sealing logic to be tested in isolation.

**Delivers:** CsdKey model with encrypted .key and password storage; `valid_to` expiry parsed and stored from .cer; `CsdManagerService` with load/decrypt/validate methods; Filament CsdKeyResource with upload UI and expiry badge; daily expiry alert job; audit log for CSD usage.

**Addresses:** CSD table stakes from FEATURES.md.

**Avoids:** CSD password plaintext pitfall; CSD expiry silent failure pitfall; .key file in public storage pitfall.

### Phase 3: Issuer and Receiver Configuration

**Rationale:** Issuer (Emisor) and Receiver (Receptor) fiscal data must be stored before any CFDI can reference them. This phase establishes the Receptor model with the strict CFDI 4.0 fields (`nombre_fiscal`, `domicilio_fiscal_cp`, `regimen_fiscal_receptor`, `uso_cfdi`) and the Issuer model with its active CSD relationship.

**Delivers:** Issuer model and Filament resource (company-level config, single record); Receiver/customer catalog model with required CFDI 4.0 fiscal fields; Filament ReceiverResource with CFDI 4.0 field UI notes; RFC format validation; SAT catalog foreign keys resolved.

**Addresses:** Customer catalog differentiator from FEATURES.md.

**Avoids:** CFDI 4.0 strict receiver data matching pitfall — the model enforces required fields before any invoice can use the receptor.

### Phase 4: CFDI Base — Ingreso/Egreso and Core Timbrado Pipeline

**Rationale:** This is the central deliverable. It integrates all previous phases. The PAC interface, XML builder, CSD sealing, and Finkok stamping all come together here. This phase is the highest complexity phase and should not begin until phases 1-3 are stable.

**Delivers:** `CfdiBuilderService` using `eclipxe/cfdiutils`; `PacServiceInterface` + `FinkokPacDriver`; `StampInvoiceJob` with idempotency and Finkok fault-code-307 handling; `InvoiceStatus` state machine; Filament InvoiceResource with Stamp action dispatching the job; CFDI Ingreso and Egreso (credit note) support; pre-PAC `CfdiValidator` validation with human-readable error mapping.

**Addresses:** Core CFDI base table stakes; Finkok integration; PAC multi-driver abstraction from FEATURES.md.

**Avoids:** Synchronous PAC calls pitfall; wrong sello calculation pitfall; Finkok duplicate stamp pitfall; monolithic InvoiceService anti-pattern; business logic in Filament actions anti-pattern.

### Phase 5: Post-Stamp Pipeline — PDF, Storage, Email

**Rationale:** PDF and email delivery are legally expected (client expectation) and operationally required. These depend on having a valid stamped UUID from Phase 4. Keeping them in a separate phase prevents Phase 4 from becoming too large and ensures the PDF is only generated from a valid timbrado XML (never from a draft).

**Delivers:** `InvoicePdfService` with Blade template and DomPDF; stamped XML storage in `storage/app/private/cfdi/xml/`; PDF storage in `storage/app/private/cfdi/pdf/`; `InvoiceMail` mailable with XML + PDF attachments; `SendInvoiceEmailJob`; Filament download and email actions on the invoice list.

**Addresses:** XML and PDF storage, email delivery table stakes from FEATURES.md.

**Avoids:** PDF from draft (no UUID) pitfall; synchronous PDF generation blocking HTTP; incorrect email attachment MIME types.

### Phase 6: Cancellation

**Rationale:** Cancellation is a separate legal operation from stamping. It requires the UUID from Phase 4 and CSD signing from Phase 2. Implemented separately to keep the cancellation state machine isolated. CFDI 4.0 cancellation is more complex than 3.3 — Motivo codes and FolioSustitucion are required.

**Delivers:** `CancelInvoiceAction` and `CancelInvoiceJob`; `PacServiceInterface::cancel()` with Motivo + FolioSustitucion; CancelRequested → Cancelled status transition; Finkok cancellation response parsing and status update; user-facing error messages for Motivo validation failures.

**Addresses:** Cancellation table stakes from FEATURES.md.

**Avoids:** Missing Motivo code causing PAC rejection; missing FolioSustitucion for Motivo=01; leaving CFDI in CancelRequested indefinitely.

### Phase 7: Complemento Pagos 2.0

**Rationale:** Pagos 2.0 is required for all PPD (deferred payment) invoices — a primary business operation. It depends on the CFDI Base from Phase 4 (must reference stamped PPD invoice UUID). Pagos 2.0 has the highest arithmetic complexity and requires the `bcmath`-based `MoneyCalculator` established before any monetary calculation code is written.

**Delivers:** `PaymentComplement` model; `PagosComplementBuilder` implementing `ComplementBuilderInterface`; `MoneyCalculator` service using bcmath for exact decimal arithmetic; ImpSaldoAnt / ImpPagado / ImpSaldoInsoluto calculation from related CFDI DB record (not user input); Totales node aggregation; Filament complement sub-form on Invoice create/edit.

**Addresses:** Pagos 2.0 table stakes from FEATURES.md.

**Avoids:** Floating-point rounding errors; user-input ImpSaldoAnt (must come from DB); incorrect EquivalenciaDR for same-currency payments (must be exactly `1`).

### Phase 8: Complemento Carta Porte 3.1

**Rationale:** Legally required for transport operations; depends on Phase 4 CFDI Base being solid. Carta Porte is the most structurally complex complement (40+ fields, 5 mutually exclusive transport types, large additional catalogs). The transport type strategy pattern must be designed before any XML generation code is written.

**Delivers:** `CartaPorteComplement` model; transport type strategy pattern (`AutoTransporte`, `TransporteAereo`, etc.); `CartaPorteComplementBuilder`; additional SAT catalog seeding (c_ClaveProdServCP ~17k rows, c_TipoPermiso, c_SubTipoRem, c_TipoLicencia, c_TipoFigura); Ubicaciones with Origen/Destino validation; Operadores (drivers with RFC and TipoFigura=01); FiguraTransporte node; Filament complement sub-form.

**Addresses:** Carta Porte 3.1 table stakes from FEATURES.md.

**Avoids:** Transport type node collision pitfall; missing Operadores with RFC; c_ClaveProdServCP vs c_ClaveProdServ confusion; AutoTransporte-only implementation without abstraction for other types.

### Phase 9: Complemento Comercio Exterior 2.0

**Rationale:** Legally required for export operations; depends on Phase 4. Comercio Exterior has an existing TariffClassification seeder whose source must be audited before this phase begins. The catalog source and versioning strategy must be established first.

**Delivers:** `ComercioExteriorComplement` model; `ComercioExteriorComplementBuilder`; TariffClassification seeder source audit and `valid_from`/`valid_to` columns; c_ClavePedimento format validation regex; foreign receptor address fields (NumRegIdTrib, international address); TotalUSD calculation; Filament complement sub-form.

**Addresses:** Comercio Exterior 2.0 table stakes from FEATURES.md.

**Avoids:** Stale TariffClassification catalog causing PAC rejection; ClavePedimento format errors; missing catalog versioning strategy.

### Phase Ordering Rationale

- **Catalogs before everything:** No CFDI form dropdown can work without seeded catalog data. This is a root dependency with zero external risk.
- **CSD before signing:** CSD management must be built and security-hardened before any code attempts to sign XML. Security architecture is always easier to get right first than to retrofit.
- **Issuer/Receiver before CFDI creation:** The Receptor model must enforce CFDI 4.0 strict fields before any invoice can reference it — enforcing at the model layer prevents rejection-causing data from entering the system.
- **CFDI Base before complements:** Complements are nodes attached to a base CFDI. Without a working timbrado pipeline, complement development has no integration target.
- **Post-stamp and cancellation after base:** These are downstream operations that require a stamped UUID.
- **Complements in their own phases:** Each complement has independent model, builder, and catalog dependencies. Isolating them allows parallel testing and prevents one complement's complexity from blocking another.

### Research Flags

Phases likely needing deeper research during planning:

- **Phase 4 (CFDI Base + Finkok):** Finkok SOAP endpoint parameters, fault code catalog, and sandbox test credentials should be verified against the Finkok developer portal before implementation. The exact `phpcfdi/finkok` API surface should be confirmed against the current Packagist release.
- **Phase 7 (Pagos 2.0):** The `ImpuestoP` tax breakdown calculation for the paid portion is algorithmically complex. The SAT's published Pagos 2.0 guide should be consulted for the exact proportional tax calculation formula before implementing `PagosComplementBuilder`.
- **Phase 8 (Carta Porte 3.1):** The `FiguraTransporte` node structure changed from Carta Porte 2.0 to 3.1. The SAT's current v3.1 XSD must be the reference — not online tutorials which commonly reference older versions.
- **Phase 9 (Comercio Exterior 2.0):** The existing `TariffClassification` seeder source must be identified and compared against the current SAT-published catalog before any Comercio Exterior work begins.

Phases with standard, well-documented patterns (can skip research-phase):

- **Phase 1 (SAT Catalogs):** Catalog data comes from SAT's official published CSVs/Excel files. Seeding is standard Laravel. No novel integration.
- **Phase 2 (CSD Management):** `phpcfdi/credentials` and Laravel `Crypt` are well-documented. Pattern is straightforward file upload + encryption.
- **Phase 3 (Issuer/Receiver):** Standard Filament CRUD resources with model validation. No external integration.
- **Phase 5 (PDF/Email):** `barryvdh/laravel-dompdf` and Laravel Mailable are well-documented. Standard Blade template work.
- **Phase 6 (Cancellation):** Follows the same PAC interface pattern as stamping; Motivo/FolioSustitucion field requirements are documented in SAT spec.

---

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | phpcfdi ecosystem is the de-facto standard; no competing library has comparable coverage. Version constraints compatible with PHP 8.5 and Laravel 12 verified. One caveat: `phpcfdi/finkok`, `phpcfdi/xml-cancelacion`, `phpcfdi/cfdi-expresiones` version pins should be verified on Packagist before installation. |
| Features | HIGH | Feature set is defined by SAT Anexo 20 v4.0 (stable since 2022, mandatory since Jan 2024) and complement specs (Pagos 2.0, Carta Porte 3.1, Comercio Exterior 2.0 — all stable). Finkok API parameters need implementation-time verification. |
| Architecture | HIGH | Layer separation, PAC interface contract, complement builder pattern, and async job pipeline are well-established patterns for this domain. eclipxe/cfdiutils API surface (Cfdi40Creator, CfdiValidator) is stable. |
| Pitfalls | HIGH | SAT Anexo 20 pitfalls (strict Receptor data, arithmetic precision, timezone requirements) are documented in the spec. Finkok SOAP fault-code-307 handling is a known integration pattern. CSD encryption is a security requirement. All pitfalls have concrete, implementable prevention strategies. |

**Overall confidence:** HIGH

### Gaps to Address

- **Finkok SOAP exact method signatures:** The exact WSDL method names and response structure for `stampcfdi`, `cancel`, and `query` operations should be verified against the current Finkok developer portal during Phase 4 planning. The `phpcfdi/finkok` package wraps these, but knowing the underlying response structure helps debug edge cases.

- **SAT catalog data sources:** The exact download URLs for SAT catalog CSVs (c_ClaveProdServ, c_ClaveUnidad, c_Municipio, c_ClaveProdServCP) should be confirmed from `omawww.sat.gob.mx` before writing seeders. SAT occasionally reorganizes their download portal.

- **TariffClassification seeder source audit:** The existing seeder's data source must be identified. If it came from an unofficial source or is from before the 2024 tariff update, all Comercio Exterior tests will use stale data. This should be resolved before Phase 9 begins.

- **Finkok sandbox test CSD:** Finkok provides test CSD credentials for their demo environment. These must be obtained and stored in `.env.testing` before any integration tests against the PAC sandbox can run.

- **`phpcfdi/finkok` current release tag:** Packagist should be checked for the current release before Phase 4 to pin the exact version and review the CHANGELOG for breaking changes since the researched `^0.5` constraint.

---

## Sources

### Primary (HIGH confidence)

- SAT Anexo 20 CFDI 4.0 Technical Specification — field requirements, validation rules, XSD schemas
  `https://omawww.sat.gob.mx/tramitesyservicios/Paginas/documentos/Anexo_20_Guia_de_llenado_CFDI.pdf`
- SAT Complemento Pagos 2.0 Technical Guide — Pagos 2.0 field requirements and tax breakdown rules
  `https://omawww.sat.gob.mx/tramitesyservicios/Paginas/complemento_pagos.htm`
- SAT Complemento Carta Porte 3.1 Technical Guide — transport complement v3.1 requirements
  `https://omawww.sat.gob.mx/tramitesyservicios/Paginas/carta_porte.htm`
- SAT Complemento Comercio Exterior 2.0 Technical Guide — export complement requirements
  `https://omawww.sat.gob.mx/tramitesyservicios/Paginas/comercio_exterior.htm`
- eclipxe/CfdiUtils GitHub — PHP CFDI 4.0 XML generation library, creator API, validator API
  `https://github.com/eclipxe13/CfdiUtils`
- phpcfdi GitHub organization — credentials, finkok, xml-cancelacion, cfdi-expresiones packages
  `https://github.com/phpcfdi`
- Laravel Encryption documentation — Crypt facade, encrypted model cast
  `https://laravel.com/docs/12.x/encryption`
- PHP bcmath documentation — arbitrary precision arithmetic
  `https://www.php.net/manual/en/book.bc.php`

### Secondary (MEDIUM confidence)

- Finkok API Documentation (SOAP endpoints, fault codes, stampcfdi/cancel response structure)
  `https://wiki.finkok.com/` — some parameters require Finkok portal login to verify; verified where publicly documented
- phpcfdi/credentials package README — CSD loading and signing API
  `https://github.com/phpcfdi/credentials`
- barryvdh/laravel-dompdf GitHub — Laravel 12 compatibility confirmed in README
  `https://github.com/barryvdh/laravel-dompdf`

### Tertiary (MEDIUM confidence, validate at implementation)

- phpcfdi/finkok, phpcfdi/xml-cancelacion, phpcfdi/cfdi-expresiones version tags — pin constraints researched as of mid-2025; verify current Packagist release before installation
- PHP CFDI integration community forums and eclipxe/cfdiutils GitHub issues — known production pitfalls and workarounds

---

*Research completed: 2026-02-27*
*Ready for roadmap: yes*
