# Roadmap: FacturacionLoop

## Overview

This roadmap delivers a SAT-compliant CFDI 4.0 electronic invoicing system for a single company, built on an existing Laravel 12 / Filament v5 stack. The delivery order follows the strict dependency chain required by the SAT specification: catalogs unlock all form dropdowns, CSD management enables signing, issuer/receiver data enables CFDI creation, the base CFDI pipeline delivers the core legal value (timbrado), and the three complements (Pagos, Carta Porte, Comercio Exterior) extend it for specialized legal operations. Nine phases derive naturally from nine independent capability boundaries in the domain.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Catálogos SAT Base** - Seed all missing SAT catalog tables required for CFDI base forms
- [ ] **Phase 2: Gestión de CSD** - Upload, encrypt, and manage Certificados de Sello Digital in Filament
- [ ] **Phase 3: Emisor, Receptores y Productos** - Configure issuer, build customer catalog, and build product/service catalog
- [ ] **Phase 4: CFDI Base y Timbrado** - Create Ingreso/Egreso CFDIs, generate and seal XML, stamp with Finkok
- [ ] **Phase 5: Post-Timbrado y Consulta** - Store XML/PDF, send email, enable invoice list and downloads
- [ ] **Phase 6: Cancelación** - Cancel stamped CFDIs before the SAT via Finkok with Motivo codes
- [ ] **Phase 7: Complemento Pagos 2.0** - Create payment receipt CFDIs for PPD invoices with exact decimal arithmetic
- [ ] **Phase 8: Complemento Carta Porte 3.1** - Add transport complement with Ubicaciones, Mercancias, and FiguraTransporte
- [ ] **Phase 9: Complemento Comercio Exterior 2.0** - Add export complement with fractional tariff and customs data

## Phase Details

### Phase 1: Catálogos SAT Base
**Goal**: All SAT catalog tables required for CFDI base form dropdowns are seeded and browsable in Filament
**Depends on**: Nothing (first phase)
**Requirements**: CAT-01, CAT-02, CAT-03, CAT-04, CAT-05, CAT-06, CAT-07, CAT-08, CAT-09, CAT-10, CAT-11, CAT-12
**Success Criteria** (what must be TRUE):
  1. A Filament user can navigate to catalog admin sections and see seeded records for c_RegimenFiscal, c_UsoCFDI, c_FormaPago, c_MetodoPago, c_TipoDeComprobante, c_Impuesto, c_TipoFactor, c_TasaOCuota, c_ObjetoImp, and c_TipoRelacion
  2. A Filament user can search and filter the c_ClaveProdServ catalog (approx 53,000 rows) and retrieve a specific clave
  3. A Filament user can search and filter the c_ClaveUnidad catalog (approx 2,000 rows) and retrieve a specific unit
  4. All catalog models have Eloquent relationships usable by the CFDI form in the next phase
**Plans**: 4 plans
  - [ ] 01-01-PLAN.md — Migrations, models, and factories for all 12 SAT catalogs
  - [ ] 01-02-PLAN.md — XLS-to-CSV conversion + seeders for all 12 catalogs + DatabaseSeeder registration
  - [ ] 01-03-PLAN.md — Filament read-only resources for all 12 catalogs
  - [ ] 01-04-PLAN.md — Feature tests for all 12 catalog models

### Phase 2: Gestión de CSD
**Goal**: Users can securely upload and manage their Certificado de Sello Digital from Filament, with encrypted storage and expiry monitoring
**Depends on**: Phase 1
**Requirements**: CSD-01, CSD-02, CSD-03, CSD-04, CSD-05, CSD-06, CSD-07
**Success Criteria** (what must be TRUE):
  1. User can upload a .cer file and a .key file through a Filament form and see the certificate number (NoCertificado) and expiry date extracted and displayed
  2. The .key file and its passphrase are never stored in plaintext — the database contains only encrypted ciphertext and the file lives in private storage
  3. A badge in Filament shows a warning when the CSD expiry date is within 90 days of today
  4. Any attempt to stamp a CFDI with an expired CSD produces a clear user-facing error before contacting the PAC
**Plans**: TBD

### Phase 3: Emisor, Receptores y Productos
**Goal**: Users can configure the issuer's fiscal data and maintain a catalog of customers and products/services used when building CFDI forms
**Depends on**: Phase 1
**Requirements**: ENT-01, ENT-02, ENT-03, ENT-04, ENT-05, PROD-01, PROD-02, PROD-03
**Success Criteria** (what must be TRUE):
  1. User can configure the single issuer (Emisor) record with RFC, legal name, fiscal regime, and domicilio fiscal postal code
  2. User can create a customer (Receptor) record with RFC, nombre fiscal, domicilio fiscal CP, fiscal regime, and default UsoCFDI, and the system rejects an RFC that does not match the 12-char (moral) or 13-char (fisica) format
  3. User can create a product/service (Producto) record with ClaveProdServ, ClaveUnidad, description, unit price, and tax configuration (IVA/ISR/IEPS rates and ObjetoImp)
  4. When starting a new invoice, the user can search for and select an existing receptor from the catalog without re-entering fiscal data
  5. When adding a concept to an invoice, the user can search for and select an existing product, auto-populating all fields including tax configuration
**Plans**: TBD

### Phase 4: CFDI Base y Timbrado
**Goal**: Users can create a valid CFDI 4.0 Ingreso or Egreso in Filament, stamp it with Finkok asynchronously, and receive a timbrado XML with a valid UUID
**Depends on**: Phase 2, Phase 3
**Requirements**: CFDI-01, CFDI-02, CFDI-03, CFDI-04, CFDI-05, CFDI-06, CFDI-07, CFDI-08, CFDI-09, CFDI-10, CFDI-11, CFDI-12, PAC-01, PAC-02, PAC-03, PAC-04, PAC-05
**Success Criteria** (what must be TRUE):
  1. User can create and save a CFDI Ingreso draft in Filament with all required CFDI 4.0 fields (Emisor, Receptor, multiple Conceptos with taxes, FormaPago, MetodoPago, Moneda, LugarExpedicion) and the system auto-calculates totals
  2. User can create a CFDI Egreso that references a previously stamped CFDI Ingreso via CfdiRelacionados
  3. User can configure multiple invoice series (A, B, etc.) and each series auto-increments its folio independently
  4. User dispatches the Stamp action in Filament and the invoice status transitions to Processing; within seconds to minutes the status changes to Stamped with the UUID, NoCertificadoSAT, FechaTimbrado, and SelloSAT visible
  5. If Finkok returns fault code 307 (already stamped), the system recovers the existing UUID instead of creating a duplicate or stuck invoice
  6. The codebase has a PacServiceInterface so a second PAC can be configured without modifying the stamping pipeline
**Plans**: TBD

### Phase 5: Post-Timbrado y Consulta
**Goal**: Stamped invoices are stored, PDF-generated, emailed to the receiver, and fully browsable in Filament
**Depends on**: Phase 4
**Requirements**: POST-01, POST-02, POST-03, POST-04, POST-05, LIST-01, LIST-02, LIST-03
**Success Criteria** (what must be TRUE):
  1. After stamping, the timbrado XML is automatically stored in private storage (not accessible via public URL) and the PDF is generated and stored alongside it
  2. The receiver receives an email with the XML and PDF attached, formatted with fiscal data including the SAT QR code
  3. A user viewing an invoice in Filament can download the XML and the PDF individually via action buttons
  4. A user can bulk-download XMLs for a date range from the invoice list
  5. The invoice list shows all invoices with status column (Borrador, Timbrada, Cancelada) and the user can filter by date range, type, series, receptor RFC, and status, and search by UUID, folio, or RFC
**Plans**: TBD

### Phase 6: Cancelación
**Goal**: Users can cancel a stamped CFDI before the SAT via Finkok, selecting the required Motivo code and providing a substitution UUID when applicable
**Depends on**: Phase 4
**Requirements**: CANC-01, CANC-02, CANC-03, CANC-04, CANC-05
**Success Criteria** (what must be TRUE):
  1. User can trigger a Cancel action on a stamped invoice in Filament and must select a Motivo (01, 02, 03, or 04) from a dropdown before confirming
  2. When Motivo 01 is selected, the form requires a FolioSustitucion UUID and does not submit without it
  3. The cancellation request is signed with the issuer's CSD before being sent to Finkok
  4. After submitting cancellation, the invoice status updates to reflect the SAT's response (accepted, in process, or rejected) with a human-readable message
**Plans**: TBD

### Phase 7: Complemento Pagos 2.0
**Goal**: Users can create CFDI-P payment receipt documents for PPD invoices, with exact centavo-precision arithmetic for all tax balance calculations
**Depends on**: Phase 4
**Requirements**: PAG-01, PAG-02, PAG-03, PAG-04, PAG-05, PAG-06
**Success Criteria** (what must be TRUE):
  1. User can create a CFDI Tipo P (Pago) in Filament that references one or more existing stamped PPD invoices by UUID
  2. The payment form includes FechaPago, FormaDePagoP, MonedaP, TipoCambioP, and Monto fields
  3. The system automatically calculates ImpSaldoAnt from the database record of the referenced invoice — the user does not enter it manually — and derives ImpSaldoInsoluto using bcmath with exact decimal precision
  4. A single CFDI de Pago can record multiple Pagos, each referencing multiple related documents
  5. The generated XML includes a valid Totales node with correctly aggregated ImpuestosTrasladados and ImpuestosRetenidos across all payments
**Plans**: TBD

### Phase 8: Complemento Carta Porte 3.1
**Goal**: Users can create a CFDI with Complemento Carta Porte 3.1 for transport operations, including Ubicaciones, Mercancias, Autotransporte, and FiguraTransporte nodes
**Depends on**: Phase 4
**Requirements**: CAT-13, CAT-14, CAT-15, CAT-16, CAT-17, CRP-01, CRP-02, CRP-03, CRP-04, CRP-05, CRP-06, CRP-07
**Success Criteria** (what must be TRUE):
  1. User can create a CFDI with Carta Porte complement in Filament and select cargo product keys from the c_ClaveProdServCP catalog (approx 17,000 rows) separately from the regular c_ClaveProdServ
  2. User can add Origen and Destino locations with RFC, full address, departure/arrival dates, and total distance
  3. User can configure Autotransporte with PermSCT (from c_TipoPermiso), vehicle plate, year, and insurance data; and add Remolques using c_SubTipoRem keys
  4. User can add one or more FiguraTransporte operators with RFC, TipoFigura, TipoLicencia, and license number
  5. User can mark the shipment as international transport (TranspInternac = Sí) and provide PaisOrigenDestino and ViaEntradaSalida
  6. PesoBrutoTotal and NumTotalMercancias are calculated automatically from the Mercancias list without user input
**Plans**: TBD

### Phase 9: Complemento Comercio Exterior 2.0
**Goal**: Users can create a CFDI with Complemento Comercio Exterior 2.0 for export operations, referencing tariff classifications, customs keys, and USD valuation
**Depends on**: Phase 4
**Requirements**: CAT-18, CAT-19, CAT-20, CEX-01, CEX-02, CEX-03, CEX-04, CEX-05
**Success Criteria** (what must be TRUE):
  1. User can create a CFDI with Comercio Exterior 2.0 complement in Filament and select TipoOperacion, ClaveDePedimento (from c_ClavePedimento), and Incoterm from their existing catalog tables
  2. User can specify TipoCambioUSD and TotalUSD for the export operation
  3. The complement includes the full issuer and receiver addresses required by Comercio Exterior 2.0 (different from CFDI base address fields)
  4. User can add export merchandise lines with FraccionArancelaria (from TariffClassification), CantidadAduana, UnidadAduana (from CustomUnit), ValorUnitarioAduana, and ValorDolares
  5. The system uses the existing TariffClassification, CustomUnit, Country, Incoterm, and State catalog models that already exist in the project
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Catálogos SAT Base | 2/4 | In Progress|  |
| 2. Gestión de CSD | 0/TBD | Not started | - |
| 3. Emisor, Receptores y Productos | 0/TBD | Not started | - |
| 4. CFDI Base y Timbrado | 0/TBD | Not started | - |
| 5. Post-Timbrado y Consulta | 0/TBD | Not started | - |
| 6. Cancelación | 0/TBD | Not started | - |
| 7. Complemento Pagos 2.0 | 0/TBD | Not started | - |
| 8. Complemento Carta Porte 3.1 | 0/TBD | Not started | - |
| 9. Complemento Comercio Exterior 2.0 | 0/TBD | Not started | - |
