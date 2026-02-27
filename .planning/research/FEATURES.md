# Feature Research

**Domain:** CFDI 4.0 Invoicing System (Sistema de Facturación Electrónica)
**Researched:** 2026-02-27
**Confidence:** HIGH (CFDI 4.0 spec is SAT-published and stable; complement specs are versioned and not expected to change in v1 scope)

---

## Context Note

This is NOT a SaaS product competing on features. It is a **single-company fiscal compliance system**. "Differentiators" here mean operational advantages over manual or spreadsheet-based invoicing—not market differentiation. "Table stakes" means SAT will reject the CFDI if the feature is missing. The primary constraint is legal, not competitive.

---

## Feature Landscape

### Table Stakes (SAT Will Reject Without These)

These are technical and legal requirements from SAT Anexo 20 v4.0, PAC Finkok, and SAT validation rules. Missing any of these means the CFDI will not be stamped or will be invalid.

#### Core CFDI 4.0 Structure

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Versión 4.0 attribute | SAT rejects any non-4.0 CFDI after January 2024 enforcement | LOW | Must be exactly "4.0" in XML |
| Serie + Folio (internal) | Required for internal control; SAT requires unique UUID but Folio is issuer-controlled | LOW | Alphanumeric, optional per spec but expected in practice |
| Fecha (RFC 3339 format) | Must be within 72 hours of stamping moment | LOW | Timezone must be issuer's local time; Finkok validates this |
| TipoDeComprobante (I/E/T/N/P) | SAT validates correct type per operation | LOW | I=Ingreso, E=Egreso, T=Traslado, N=Nómina, P=Pago; N and P require complements |
| FormaPago (SAT catalog c_FormaPago) | Required for type I (Ingreso) and E (Egreso) unless PUE/PPD split | MEDIUM | 99=Por Definir if MetodoPago=PPD; catalog has ~30 entries |
| MetodoPago (PUE or PPD) | PUE=single payment, PPD=multiple payments; PPD requires Pago complement | LOW | Determines if Complement Pagos 2.0 is needed |
| Moneda + TipoCambio | Required; TipoCambio required when Moneda != MXN | MEDIUM | TipoCambio must use Banxico rate of the day; SAT validates range |
| LugarExpedicion (postal code) | Must be valid 5-digit MX postal code from SAT catalog | LOW | Issuer's fiscal postal code |
| SubTotal | Arithmetic must match sum of Conceptos | MEDIUM | SAT runs roundoff validation; decimals per currency |
| Descuento | Optional but if present must equal sum of Concepto discounts | LOW | — |
| Total | Must equal SubTotal - Descuento + Impuestos trasladados - Impuestos retenidos | HIGH | Rounding rules are strict; 1-centavo error = rejection |
| Sello digital (CSD issuer) | XML must be signed with issuer's CSD (.cer + .key) | HIGH | eclipxe/cfdiutils handles this; CSD must be valid and not expired |
| NoCertificado | 20-digit certificate number from .cer | LOW | Extracted from the .cer file |
| Certificado (base64 of .cer) | Full certificate embedded in XML | LOW | Extracted from the .cer file |

#### Emisor (Issuer) Required Fields

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Emisor RFC | Must be valid MX RFC format (12 chars company, 13 person) | LOW | SAT validates format and existence in their registry |
| Emisor Nombre | Legal company name as registered at SAT | LOW | Must match CSD certificate name |
| Emisor RegimenFiscal (c_RegimenFiscal) | SAT catalog; must match RFC's registered regime | LOW | ~20 options; company typically has one |
| Emisor FacAtrAdquirente | Only for Adquirente de bienes raíces; not common | LOW | Leave null unless specific use case |

#### Receptor (Recipient) Required Fields — CFDI 4.0 New Requirements

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Receptor RFC | CFDI 4.0 REQUIRES real RFC; XAXX010101000 (publico general) only allowed for ≤$2,000 | MEDIUM | SAT validates RFC format; LCO validation recommended |
| Receptor Nombre | Legal name; must match RFC at SAT registry | MEDIUM | CFDI 4.0 strictly requires matching name |
| Receptor DomicilioFiscalReceptor | 5-digit postal code of fiscal address | MEDIUM | New in 4.0; SAT validates postal code exists |
| Receptor RegimenFiscalReceptor | Fiscal regime of the recipient | MEDIUM | New in 4.0; must match RFC's regime |
| Receptor UsoCFDI (c_UsoCFDI) | How the recipient uses the CFDI; must be compatible with their regime | MEDIUM | CFDI 4.0 catalog is stricter; validation vs regime required |

#### Concepto (Line Items) Required Fields

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Concepto ClaveProdServ (c_ClaveProdServ) | SAT product/service key catalog; ~53,000 entries | MEDIUM | Required; no default; customer must select correct key |
| Concepto Cantidad | Positive decimal | LOW | — |
| Concepto ClaveUnidad (c_ClaveUnidad) | SAT unit key catalog; different from custom units | LOW | E.g., H87=piece, KGM=kilogram; ~50 common codes |
| Concepto Descripcion | Text description of product/service | LOW | — |
| Concepto ValorUnitario | Unit price before taxes | LOW | — |
| Concepto Importe | Cantidad × ValorUnitario; arithmetic must match | LOW | SAT validates exact arithmetic |
| Concepto ObjetoImp | Tax object flag (01=no tax, 02=yes, 03=third-party) | LOW | New in 4.0; required on every Concepto |
| Concepto Impuestos > Traslados | IVA and/or IEPS taxes per line item | MEDIUM | TasaOCuota must match SAT catalog values |
| Concepto Impuestos > Retenciones | ISR/IVA retentions per line item when applicable | MEDIUM | Required for professional services, rent, etc. |

#### Document-Level Taxes

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Impuestos > Traslados totals | Must equal sum of all Concepto traslados per tax/rate | HIGH | Rounding aggregation is a common rejection cause |
| Impuestos > Retenciones totals | Must equal sum of all Concepto retenciones | MEDIUM | Same aggregation rules |

#### CSD Management

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Upload .cer file | Certificate file for the company | MEDIUM | Must extract NoCertificado, Certificado, and validity dates |
| Upload .key file | Private key for signing | HIGH | Must be stored encrypted; never exposed |
| CSD password handling | Password to decrypt the .key file | HIGH | Store encrypted; used only during signing |
| CSD validity check | SAT invalidates expired or revoked CSDs | MEDIUM | Check expiry on upload and before each signing |

#### PAC Stamping (Timbrado)

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Finkok stamping API integration | Returns TimbreFiscalDigital complement with UUID | HIGH | SOAP/REST API; UUID is the legal fiscal identifier |
| TimbreFiscalDigital complement added to XML | SAT validates this complement is present | LOW | Finkok adds it; must be stored correctly |
| UUID storage | Required for cancellation and queries | LOW | UUID v4 from Finkok response |
| NoCertificadoSAT | SAT's certificate number in the stamp | LOW | From Finkok response |
| FechaTimbrado | Timestamp from Finkok; must be within 72h of Fecha | LOW | From Finkok response |

#### Cancellation (Cancelación)

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Cancel via PAC (Finkok) | SAT requires PAC-mediated cancellation via CSD signature | HIGH | Must sign cancellation request with CSD |
| CancellationReason (motivo) | SAT catalog with 4 reasons (01, 02, 03, 04) | MEDIUM | 01=error, 02=correccion, 03=operacion no realizada, 04=factura global |
| FolioSustitucion | UUID of replacement CFDI; required for motivo 01 | MEDIUM | Only when motivo=01 (error with correction) |
| Cancellation status tracking | SAT sends async acceptance (some require receptor confirmation) | HIGH | Receptor must accept cancellation for >$1,000 CFDIs; polling required |

#### XML & Document Storage

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Signed+stamped XML storage | Legal document; must be kept 5 years per CFF | MEDIUM | Store in filesystem/S3; link to invoice record |
| PDF generation from XML | Required by clients; legal equivalent with addenda | MEDIUM | Must follow SAT-standard layout (no formal spec, but industry standard) |
| Email delivery (XML + PDF) | Standard commercial practice; some clients require it | MEDIUM | Both files attached; XML is the legal document |

---

### Complement: Pagos 2.0 (Recepción de Pagos)

**Required when:** MetodoPago = PPD (Pago en Parcialidades o Diferido). Every payment received against a PPD invoice requires a Complemento de Pagos CFDI.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Pago > FechaPago | Actual payment date | LOW | — |
| Pago > FormaDePagoP (c_FormaPago) | Payment method code | LOW | Cannot be 99 (Por Definir) |
| Pago > MonedaP | Payment currency | LOW | — |
| Pago > TipoCambioP | Exchange rate when MonedaP != MXN | MEDIUM | — |
| Pago > Monto | Amount paid | MEDIUM | Must match DoctoRelacionado sum accounting for exchange rate |
| DoctoRelacionado > IdDocumento | UUID of the related PPD CFDI | MEDIUM | — |
| DoctoRelacionado > MonedaDR | Currency of the original invoice | LOW | — |
| DoctoRelacionado > EquivalenciaDR | Exchange rate between MonedaP and MonedaDR | MEDIUM | Complex when currencies differ |
| DoctoRelacionado > NumParcialidad | Payment installment number | LOW | Sequential starting from 1 |
| DoctoRelacionado > ImpSaldoAnt | Previous outstanding balance | MEDIUM | Must match SAT ledger; error if wrong |
| DoctoRelacionado > ImpPagado | Amount applied to this document from this payment | MEDIUM | Sum of ImpPagado across parcialidades must equal Monto |
| DoctoRelacionado > ImpSaldoInsoluto | Remaining balance after this payment | MEDIUM | ImpSaldoAnt - ImpPagado |
| Pago > ImpuestoP node | Tax breakdown for the payment | HIGH | Pagos 2.0 added tax breakdown; must recalculate taxes proportionally |
| Pago > ImpuestoP > TrasladosP | IVA/IEPS on the paid portion | HIGH | BaseP, ImpuestoP, TipoFactorP, TasaOCuotaP, ImporteP required |
| Pago > ImpuestoP > RetencionesP | Retenciones on the paid portion | HIGH | Same complexity as TrasladosP |
| Totales node (Pagos 2.0) | Aggregate tax totals across all Pago nodes | HIGH | Required in Pagos 2.0; was optional in 1.0 |

**SAT validation rules for Pagos:**
- A payment CFDI can have multiple Pago nodes (one per payment date/form)
- Each Pago can reference multiple DoctoRelacionado
- The related invoice must have been previously stamped (UUID must exist)
- Running balance (ImpSaldoAnt) must precisely match SAT's ledger

---

### Complement: Carta Porte 3.1

**Required when:** Transporting goods by road within Mexico AND the goods are merchandise (not services). Required for both the carrier and the shipper. TipoDeComprobante must be T (Traslado) for the carrier or I/E (Ingreso/Egreso) with the complement for the shipper.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| CartaPorte > Version | Must be "3.1" | LOW | — |
| CartaPorte > TranspInternac | YN flag for international transport | LOW | — |
| CartaPorte > EntradaSalidaMerc | Entry/exit flag when TranspInternac=Sí | LOW | — |
| CartaPorte > PaisOrigenDestino | Country when TranspInternac=Sí | LOW | c_Pais catalog |
| CartaPorte > ViaEntradaSalida | Transport mode for international (c_CveTransporte) | LOW | — |
| Ubicaciones > Origen | Origin location with departure date/time | MEDIUM | RFCRemitenteDestinatario required |
| Ubicaciones > Destino | Destination location with arrival date/time | MEDIUM | DistanciaRecorrida required |
| Ubicacion > Domicilio | Full address: calle, colonia, municipio, estado, pais, CP | MEDIUM | c_Estado and c_Municipio catalogs required |
| Mercancias > Mercancia | Each product being transported | HIGH | ClaveProdSTCC, Descripcion, Cantidad, ClaveUnidad, PesoEnKg |
| Mercancia > ClaveProdSTCC | SAT transport goods catalog key | HIGH | Separate catalog from ClaveProdServ; ~17,000 entries |
| Mercancia > PesoEnKg | Weight per item in kg | LOW | — |
| Mercancia > MaterialPeligroso | Hazmat flag and CVE | MEDIUM | If true, requires MaterialPeligroso node with UN number |
| Mercancias > NumTotalMercancias | Total number of goods types | LOW | — |
| Mercancias > PesoBrutoTotal | Total gross weight | LOW | — |
| Mercancias > UnidadPeso | Weight unit (c_ClaveUnidad) | LOW | — |
| Autotransporte node | Vehicle and operator info | MEDIUM | Required for road transport |
| Autotransporte > PermSCT | SCT permit number | MEDIUM | c_TipoPermiso catalog |
| Autotransporte > NumPermisoSCT | Actual permit number string | LOW | — |
| Autotransporte > IdentificacionVehicular | Vehicle plate and year | MEDIUM | c_SubTipoRem for trailer |
| Autotransporte > Seguros | Insurance policy numbers | MEDIUM | Responsabilidad civil + Carga required |
| Operadores > Operador | Driver RFC and license | MEDIUM | RFC must be valid; LicTipoLic from c_TipoLicencia |
| FiguraTransporte node | New in 3.0; replaces/complements Operadores | HIGH | RFCFigura, TipoFigura (01=Operador, 02=Propietario, 03=Arrendador...) |

**Catalog dependencies for Carta Porte 3.1:**
- c_ClaveProdServCP (transport goods — distinct from c_ClaveProdServ)
- c_Municipio (linked to c_Estado)
- c_ColoniaCP (linked to c_Municipio) — optional but recommended
- c_SubTipoRem (trailer subtype)
- c_TipoPermiso (SCT permits)
- c_TipoLicencia (driver license types)
- c_TipoFigura (transport actor types)

---

### Complement: Comercio Exterior 2.0

**Required when:** Exporting goods from Mexico. TipoDeComprobante = I (Ingreso) or T (Traslado) for export operations. ClavePedimento is the customs document reference.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| ComercioExterior > Version | Must be "2.0" | LOW | — |
| ComercioExterior > MotivoTraslado | Reason when TipoDeComprobante=T | LOW | c_MotivoTraslado catalog |
| ComercioExterior > TipoOperacion | Export (1) or Import (2) | LOW | — |
| ComercioExterior > ClaveDePedimento | Customs pedimento key (c_ClavePedimento) | MEDIUM | — |
| ComercioExterior > CertificadoLeyendas | Certificate indicator | LOW | — |
| ComercioExterior > NumCertificadoOrigen | Certificate of origin number | LOW | Optional but common |
| ComercioExterior > NumeroExportadorConfiable | Trusted exporter number (EU) | LOW | Optional |
| ComercioExterior > Incoterm | c_Incoterm catalog (already seeded in app) | LOW | Already have Incoterm model |
| ComercioExterior > Subdivision | Additional subdivision indicator | LOW | — |
| ComercioExterior > Observaciones | Free text field | LOW | — |
| ComercioExterior > TipoCambioUSD | MXN/USD exchange rate | MEDIUM | Required; must match Banxico rate |
| ComercioExterior > TotalUSD | Total invoice value in USD | MEDIUM | Must match arithmetic |
| Emisor node (ComExt) | Company name and fiscal address | MEDIUM | Curp if natural person, Calle/Colonia/Municipio/Estado |
| Receptor node (ComExt) | Foreign buyer name and address | MEDIUM | NumRegIdTrib (foreign tax ID), Calle, Estado, Pais, CP |
| Destinatario node | Where goods go if different from receptor | MEDIUM | Optional; NumRegIdTrib, address |
| Mercancias > Mercancia | Each exported product with customs info | HIGH | FraccionArancelaria (already seeded in app), CantidadAduana, UnidadAduana (already seeded), ValorUnitarioAduana, ValorDolares |
| Mercancia > DescripcionesEspecificas | Serial numbers or specific descriptions | MEDIUM | Required for some HS codes |

**Catalog dependencies already covered:**
- TariffClassification (FraccionArancelaria) — already seeded
- CustomUnit (UnidadAduana) — already seeded
- Country (Pais) — already seeded
- Incoterm — already seeded
- State (c_Estado) — already seeded

**Missing catalog dependencies:**
- c_ClaveUnidad SAT (different from CustomUnit) — needs catalog for Mercancias
- c_TipoRelacion (document relation types for CFDI relationships)

---

### SAT Catalogs Required (Not Yet in System)

These are catalog tables the system needs beyond what's already seeded:

| Catalog | SAT Code | Estimated Rows | Used In |
|---------|----------|---------------|---------|
| Régimen Fiscal | c_RegimenFiscal | ~20 | Emisor, Receptor (CFDI 4.0) |
| Uso CFDI | c_UsoCFDI | ~30 | Receptor |
| Forma de Pago | c_FormaPago | ~30 | CFDI, Pagos |
| Método de Pago | c_MetodoPago | 2 (PUE/PPD) | CFDI |
| Tipo de Comprobante | c_TipoDeComprobante | 6 | CFDI type |
| Clave Producto/Servicio | c_ClaveProdServ | ~53,000 | Concepto |
| Unidad SAT | c_ClaveUnidad | ~2,000 | Concepto, Mercancias |
| Impuesto | c_Impuesto | 3 (IVA/ISR/IEPS) | Concepto taxes |
| Tipo Factor | c_TipoFactor | 3 (Tasa/Cuota/Exento) | Concepto taxes |
| Tasa/Cuota IVA | c_TasaOCuota | ~10 | IVA rates (0%, 8%, 16%) |
| Objeto de Impuesto | c_ObjetoImp | 3 | Concepto (CFDI 4.0) |
| Tipo Relación | c_TipoRelacion | ~10 | CfdiRelacionados |
| Clave Prod/Serv CP | c_ClaveProdServCP | ~17,000 | Carta Porte Mercancias |
| Tipo Permiso SCT | c_TipoPermiso | ~20 | Carta Porte Autotransporte |
| Subtipo Remolque | c_SubTipoRem | ~20 | Carta Porte trailer |
| Tipo Licencia | c_TipoLicencia | ~10 | Carta Porte Operadores |
| Tipo Figura | c_TipoFigura | ~10 | Carta Porte 3.1 |
| Municipio | c_Municipio | ~2,500 | Carta Porte, ComExt addresses |
| Colonia | c_ColoniaCP | ~145,000 | Carta Porte addresses |
| Clave Pedimento | c_ClavePedimento | ~50 | Comercio Exterior |
| Motivo Traslado | c_MotivoTraslado | ~5 | Comercio Exterior |

---

### Differentiators (Operational Advantage, Not SAT Required)

Features that are not required by SAT but improve daily operations for the single company.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Customer/Receptor catalog | Avoid re-entering RFC, Nombre, DomicilioFiscal every time | LOW | Store validated receptor data; autocomplete in invoice form |
| Product/Service catalog | Avoid re-entering ClaveProdServ, Descripcion, ValorUnitario per product | LOW | Most companies have a finite set of products |
| Invoice series management | Separate sequences for different invoice types or departments | LOW | e.g., "A" for exports, "B" for domestic |
| PDF template customization | Company branding on the invoice PDF | MEDIUM | SAT does not mandate format; company logo/colors common |
| Invoice list with status | At-a-glance view of stamped, cancelled, draft invoices | LOW | Filament table with filters; very high value |
| Bulk XML download | Download all XMLs for a period | MEDIUM | Required for SAT annual audit; typically manual |
| Cancellation status polling | Auto-poll SAT for async cancellation acceptance | MEDIUM | Avoids manual status checking; needed when receptor must confirm |
| CSD expiry notification | Alert before CSD expires (3-month warning) | LOW | CSD renewal is easy to forget; expires after 4 years |
| RFC lookup/validation (LCO) | Validate that receptor RFC exists in SAT's LCO before stamping | MEDIUM | Reduces rejection rate; Finkok provides this |
| Draft invoices | Save incomplete invoices before stamping | LOW | Prevents data loss; natural Filament behavior |
| Related document linking | CfdiRelacionados node for credit notes, substitutions | MEDIUM | Required for Egreso (credit note) against an Ingreso |
| Global invoice (Factura Global) | CFDI for retail sales without receptor RFC | MEDIUM | Special rules for publico general; daily, weekly, or monthly |
| Addenda support | Additional XML data after the CFDI for ERP/EDI integration | HIGH | Retailer-specific addendas (Walmart, FEMSA) are complex |

---

### Anti-Features (Do Not Build in v1)

| Anti-Feature | Why Requested | Why Problematic | Alternative |
|--------------|---------------|-----------------|-------------|
| Multi-tenant / multiple issuers | Seems like future-proofing | Changes all data isolation logic; adds massive complexity to CSD management and PAC billing; out of stated scope | Hard-code single company; if multi-company needed later, it's a redesign |
| Custom tax logic / special regimes | REPECOS, RIF, simplified regime have different rules | These regimes have their own SAT specs; mixing adds exponential validation complexity | Implement only standard 601/626 regime first; add special regimes in v2 |
| CFDI Nómina (payroll) complement | Logical extension of invoicing | Nómina is a completely different legal domain (IMSS, SAT nómina rules); requires HR data model | Explicitly out of scope per PROJECT.md |
| Contabilidad Electrónica (COE) | SAT also requires COE | COE is accounting system-level (chart of accounts, trial balance); requires a full accounting module | Out of scope per PROJECT.md |
| DIOT (Declaración Informativa) | VAT declaration automation | Requires aggregation across all invoices; separate SAT filing format | Out of scope per PROJECT.md |
| Real-time SAT LCO validation on keystroke | Good UX idea | SAT's LCO service has rate limits; can cause latency in form | Validate on form submit or invoice preview only |
| Automatic Banxico exchange rate fetch | Avoid manual entry | Banxico API exists but rate must be official and date-specific; automation can fetch wrong rate | Provide a lookup helper but require user confirmation |
| Offline/local XML generation without PAC | Independence from PAC | SAT requires PAC-stamped CFDIs; unsigned XMLs have no legal standing | Always stamp via Finkok; provide XML export after stamping only |
| Multiple PAC simultaneous stamping | Redundancy | Causes duplicate UUID risk; PAC billing complications | Implement PAC interface contract now; switch PAC manually when needed |
| ERP integration via API | Automation from other systems | Requires full API design, authentication, versioning; high complexity for single-company | Build core invoicing first; add API endpoints in v2 if needed |
| Colonia catalog (c_ColoniaCP) | Address autocomplete | 145,000+ records; rarely queried by code; better as a type-ahead from external API | Free-text colonia field; validate only Estado/Municipio |

---

## Feature Dependencies

```
[SAT Catalogs (missing ones)]
    └──required by──> [CFDI Base Creation]
                          └──required by──> [XML Generation with eclipxe/cfdiutils]
                                                └──required by──> [XML Signing with CSD]
                                                                      └──required by──> [Finkok Stamping]
                                                                                            └──required by──> [UUID Storage]
                                                                                                                  ├──required by──> [PDF Generation]
                                                                                                                  ├──required by──> [Email Delivery]
                                                                                                                  └──required by──> [Cancellation]

[CSD Upload + Storage]
    └──required by──> [XML Signing with CSD]
    └──required by──> [Cancellation] (signing cancel request)

[Customer/Receptor Catalog] ──enhances──> [CFDI Base Creation]
[Product/Service Catalog] ──enhances──> [CFDI Base Creation]

[CFDI Base (PPD type)]
    └──required by──> [Complemento Pagos 2.0]

[CFDI Base (Ingreso/Traslado with export)]
    └──required by──> [Complemento Comercio Exterior 2.0]

[CFDI Base (Traslado/Ingreso with transport)]
    └──required by──> [Complemento Carta Porte 3.1]

[Carta Porte 3.1]
    └──requires──> [c_ClaveProdServCP catalog]
    └──requires──> [c_Municipio catalog]
    └──requires──> [c_TipoPermiso catalog]
    └──requires──> [c_TipoLicencia catalog]
    └──requires──> [c_TipoFigura catalog]

[Comercio Exterior 2.0]
    └──requires──> [TariffClassification catalog] (already seeded)
    └──requires──> [CustomUnit catalog] (already seeded)
    └──requires──> [Country catalog] (already seeded)
    └──requires──> [Incoterm catalog] (already seeded)
    └──requires──> [c_Municipio catalog] (for Mexican addresses)
```

### Dependency Notes

- **SAT Catalogs require loading before CFDI creation:** c_RegimenFiscal, c_UsoCFDI, c_FormaPago, c_ClaveProdServ, c_ClaveUnidad, c_ObjetoImp must all be seeded. Without these, the Filament form cannot offer valid dropdown options.
- **CSD must precede signing:** The .cer/.key upload flow must be completed and CSD stored before any CFDI can be signed. This is a hard blocker.
- **Finkok credentials required before stamping:** FINKOK_USERNAME, FINKOK_PASSWORD, FINKOK_WSDL env vars must be configured. Development uses Finkok's test environment.
- **Pagos 2.0 requires prior PPD CFDI:** Cannot create a payment receipt without an existing stamped PPD invoice UUID.
- **Cancellation requires UUID:** Cannot cancel a CFDI that hasn't been stamped yet; must store UUID from stamping step.
- **Carta Porte transport catalogs are large:** c_ClaveProdServCP (~17,000 rows) and c_Municipio (~2,500 rows) must be seeded; these are distinct from general CFDI catalogs.

---

## MVP Definition

### Launch With (v1) — All Required by SAT or Operationally Critical

- [x] Missing SAT catalogs (c_RegimenFiscal, c_UsoCFDI, c_FormaPago, c_ClaveProdServ, c_ClaveUnidad, c_ObjetoImp) — without these, forms cannot be populated
- [x] CSD upload and secure storage (.cer, .key, password encrypted) — hard blocker for signing
- [x] Issuer (Emisor) configuration — company data entered once
- [x] Customer catalog (Receptor) — store RFC + fiscal data for repeat clients
- [x] Product/Service catalog — store line items for repeat invoicing
- [x] CFDI 4.0 Ingreso creation with full field validation — core invoice type
- [x] XML generation with eclipxe/cfdiutils — SAT-compliant XML
- [x] XML signing with CSD — required before stamping
- [x] Finkok stamping integration — creates legal UUID
- [x] Stamped XML storage — legal retention requirement
- [x] PDF generation — client-facing document
- [x] Email delivery (XML + PDF) — standard commercial practice
- [x] Invoice list/search in Filament — operational necessity
- [x] Cancellation with motivo — legally required capability
- [x] CFDI 4.0 Egreso (credit note/nota de crédito) — common follow-on to Ingreso
- [x] Complemento Pagos 2.0 — required for all PPD (deferred payment) invoices
- [x] Complemento Carta Porte 3.1 — required for transport operations
- [x] Complemento Comercio Exterior 2.0 — required for export operations

### Add After Validation (v1.x)

- [ ] Cancellation acceptance polling — auto-check async SAT cancellation status
- [ ] CSD expiry notification — operational alert; not blocking
- [ ] Bulk XML download — useful for audits; not day-to-day
- [ ] RFC lookup via Finkok LCO API — reduces rejections; not blocking if users enter correct data
- [ ] Global invoice (Factura Global) — needed if there are retail sales to public; assess after v1 launch

### Future Consideration (v2+)

- [ ] API endpoints for ERP/external system integration — only if integration requirement emerges
- [ ] Multi-PAC support (other PACs beyond Finkok) — infrastructure contract built in v1; switch manually
- [ ] PDF template customization — cosmetic; default template sufficient for legal purposes
- [ ] CFDI Traslado (standalone) — only for pure transport companies; assess need
- [ ] Nómina complement — different domain; out of scope

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| SAT Catalogs (missing) | HIGH | MEDIUM | P1 |
| CSD Upload + Storage | HIGH | MEDIUM | P1 |
| CFDI Base Ingreso | HIGH | HIGH | P1 |
| Finkok Stamping | HIGH | HIGH | P1 |
| XML + PDF + Email | HIGH | MEDIUM | P1 |
| Cancellation | HIGH | HIGH | P1 |
| Complemento Pagos 2.0 | HIGH | HIGH | P1 |
| Complemento Carta Porte 3.1 | HIGH | HIGH | P1 |
| Complemento Comercio Exterior 2.0 | HIGH | HIGH | P1 |
| Customer/Product Catalogs | MEDIUM | LOW | P1 |
| CFDI Egreso (credit note) | MEDIUM | MEDIUM | P1 |
| RFC LCO Validation | MEDIUM | LOW | P2 |
| CSD Expiry Notification | MEDIUM | LOW | P2 |
| Bulk XML Download | LOW | MEDIUM | P2 |
| PDF Template Customization | LOW | HIGH | P3 |
| Global Invoice (Factura Global) | LOW | HIGH | P3 |
| ERP API Integration | LOW | HIGH | P3 |

---

## Competitor Feature Analysis

Context: For a single-company system, "competitors" are commercial SaaS invoicing platforms. We compare to understand what the company's users will expect.

| Feature | Aspel SAE/COI | Facturapi SaaS | Contpaqi | Our Approach |
|---------|--------------|----------------|----------|--------------|
| CFDI 4.0 Ingreso/Egreso/Traslado | Yes | Yes | Yes | Yes (v1) |
| Complemento Pagos 2.0 | Yes | Yes | Yes | Yes (v1) |
| Complemento Carta Porte 3.1 | Yes | Yes | Yes | Yes (v1) |
| Complemento Comercio Exterior 2.0 | Yes | Yes | Yes | Yes (v1) |
| CSD management | In software | API only | In software | In Filament UI |
| Multi-RFC / multi-company | Yes (paid tiers) | Yes (API tenants) | Yes | No (single company by design) |
| Accounting integration | Yes (core product) | No | Yes (core product) | No (out of scope) |
| PDF template customization | Yes | Limited | Yes | Deferred (v2) |
| SAT LCO RFC validation | Yes | Yes | Yes | Deferred (v1.x via Finkok) |
| Factura Global (retail) | Yes | Yes | Yes | Deferred (v1.x) |
| Nómina CFDI | Yes | No | Yes | Out of scope |

**Our advantage:** Custom-built for this specific company on their existing infrastructure; no per-invoice or per-user SaaS fees; integrated into their Filament admin panel workflow; all complements in v1.

---

## Sources

- SAT Anexo 20 v4.0 — official CFDI 4.0 technical specification (PUBLIC: sat.gob.mx) [HIGH confidence — stable spec, enforcement started Jan 2022 v4.0 was mandatory from Jan 2024]
- Complemento Pagos v2.0 technical specification — SAT [HIGH confidence — Pagos 2.0 was mandated from Jan 2023]
- Complemento Carta Porte v3.1 — SAT [HIGH confidence — v3.1 enforcement started Oct 2024]
- Complemento Comercio Exterior v2.0 — SAT [HIGH confidence — stable spec]
- Finkok API documentation — SOAP/REST endpoints for timbrado and cancelación [MEDIUM confidence — based on knowledge of Finkok; actual endpoint parameters require Finkok docs during implementation]
- eclipxe/cfdiutils GitHub — PHP library for CFDI XML generation, signing, and validation [HIGH confidence — library is the de-facto standard for PHP CFDI generation; used extensively in production]
- PROJECT.md — project constraints and scope confirmed above [HIGH confidence — primary source of truth for this project]

---

*Feature research for: CFDI 4.0 Invoicing System (FacturacionLoop)*
*Researched: 2026-02-27*
