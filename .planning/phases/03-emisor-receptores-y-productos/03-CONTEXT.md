# Phase 3: Emisor, Receptores y Productos - Context

**Gathered:** 2026-02-27
**Status:** Ready for planning

<domain>
## Phase Boundary

Configure the single issuer's fiscal data (Emisor), build a customer catalog (Receptores), and build a product/service catalog (Productos) — the three entity types required to create CFDI forms in Phase 4. This phase delivers CRUD management and search/select readiness; actual invoice creation is Phase 4.

</domain>

<decisions>
## Implementation Decisions

### Emisor setup
- Filament settings page (not a CRUD resource) — single form for the one emisor record
- Supports multiple fiscal regimes (regímenes fiscales) — stored as a relation, selectable per invoice in Phase 4
- Stores fiscal data: RFC, RazonSocial, DomicilioFiscalCP, plus optional logo upload (image field for future PDF generation in Phase 5)
- Top-level navigation item (not nested under a "Configuración" group)

### Receptor RFC & validation
- RFC validation: 12-char persona moral, 13-char persona física, plus generic RFCs
- Generic RFCs supported: XAXX010101000 (público en general) and XEXX010101000 (extranjero)
- Auto-fill when XAXX010101000 entered: nombre → "PÚBLICO EN GENERAL", régimen → 616, uso CFDI → S01
- Duplicate RFCs allowed — same RFC can have multiple receptor records
- Soft delete — archived receptors hidden from search but preserved for invoice history, restorable

### Product tax config
- Taxes configured as repeater/table of tax lines per product — each line: Impuesto, TipoFactor, TasaOCuota
- Preset tax templates available on product creation: "Solo IVA 16%", "IVA 16% + ISR retenido", "Exento", etc. — user picks a template then can customize
- Quantity is per-invoice only — product catalog stores unit price and tax config, not default quantity
- Soft delete — consistent with receptors

### Search & select UX (for Phase 4 consumption)
- Type-ahead search (Filament Select with search) for both receptors and products
- Receptor results display: Nombre + RFC + Régimen fiscal
- Product results display: ClaveProdServ + description + unit price
- Quick-create supported: "Crear nuevo" option in search dropdown opens inline create modal

### Claude's Discretion
- Exact Filament component choices for settings page implementation
- Database schema design for emisor-regimen relationship
- Tax template preset definitions and how they populate the repeater
- Search query optimization and debounce behavior
- Form layout and field grouping within receptor/product forms

</decisions>

<specifics>
## Specific Ideas

- Auto-fill behavior for público en general (XAXX) should match SAT required values exactly: nombre "PÚBLICO EN GENERAL", régimen 616, uso CFDI S01
- Tax templates should cover the most common Mexican business scenarios (IVA 16% traslado, IVA 16% + ISR 10% retención, Exento, IVA 0%)
- Product search results should show price to help distinguish variants of the same product

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 03-emisor-receptores-y-productos*
*Context gathered: 2026-02-27*
