# Architecture Research

**Domain:** CFDI 4.0 Electronic Invoicing — Laravel 12 + Filament v5
**Researched:** 2026-02-27
**Confidence:** HIGH (CFDI 4.0 spec is stable; eclipxe/cfdiutils is the dominant PHP library; Finkok API patterns are well-established)

---

## Standard Architecture

### System Overview

```
┌──────────────────────────────────────────────────────────────────────┐
│                     PRESENTATION LAYER (Filament v5)                  │
│  ┌─────────────────┐  ┌──────────────────┐  ┌──────────────────────┐  │
│  │  CFDI Resources  │  │  CSD Management  │  │  Invoice List/Search │  │
│  │ (create/edit UI) │  │  (upload .cer/   │  │  (status, download,  │  │
│  │                  │  │   .key/pass)      │  │   cancel, email)     │  │
│  └────────┬─────────┘  └────────┬─────────┘  └──────────┬───────────┘  │
└───────────┼─────────────────────┼────────────────────────┼─────────────┘
            │                     │                        │
┌───────────▼─────────────────────▼────────────────────────▼─────────────┐
│                     SERVICE LAYER (app/Services/)                        │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐  │
│  │ CfdiBuilder │  │ CsdManager   │  │  PacService  │  │ InvoicePdf  │  │
│  │  Service    │  │  Service     │  │ (interface)  │  │   Service   │  │
│  └──────┬──────┘  └──────┬───────┘  └──────┬───────┘  └──────┬──────┘  │
│         │                │                 │                  │         │
│  ┌──────▼──────┐         │         ┌───────▼────────┐         │         │
│  │ Complement  │         │         │  FinkokPacDriver│         │         │
│  │  Builders   │         │         │  (+ future PACs)│         │         │
│  └─────────────┘         │         └────────────────┘         │         │
└──────────────────────────┼──────────────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────────────────┐
│                     DOMAIN / MODEL LAYER (app/Models/)                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │  Invoice  │  │  Issuer  │  │ Receiver │  │  CsdKey  │  │  Compl.  │  │
│  │  (CFDI)   │  │          │  │          │  │          │  │  Models  │  │
│  └────┬──────┘  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
│       │                                                                   │
│  ┌────▼─────────────────────────────────────────────────────────────────┐ │
│  │  SAT Catalog Models (existing): Currency, Country, State, Incoterm,  │ │
│  │  CustomUnit, TariffClassification + new: FiscalRegime, CfdiUse,      │ │
│  │  PaymentForm, PaymentMethod, CfdiType, ProductServiceKey, SatUnit    │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────┘
            │
┌───────────▼──────────────────────────────────────────────────────────────┐
│                     INFRASTRUCTURE LAYER                                  │
│  ┌───────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐  │
│  │  PostgreSQL   │  │  Filesystem  │  │  Finkok API  │  │  Mail/Queue │  │
│  │  (models,     │  │  (XML files, │  │  (SOAP/REST) │  │             │  │
│  │   catalogs)   │  │   PDFs, CSD) │  │              │  │             │  │
│  └───────────────┘  └──────────────┘  └──────────────┘  └─────────────┘  │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Component Responsibilities

| Component | Responsibility | Typical Implementation |
|-----------|----------------|------------------------|
| Filament CFDI Resource | Form to capture all CFDI 4.0 fields; trigger stamp action | `app/Filament/Resources/InvoiceResource.php` |
| Filament CSD Resource | Upload/manage .cer, .key, password; mark active cert | `app/Filament/Resources/CsdResource.php` |
| CfdiBuilderService | Compose CFDI data structure using eclipxe/cfdiutils; apply complements | `app/Services/CfdiBuilderService.php` |
| CsdManagerService | Load CSD from storage, decrypt .key, create signing credential | `app/Services/CsdManagerService.php` |
| PacServiceInterface | Contract for timbrado/cancelation operations | `app/Contracts/PacServiceInterface.php` |
| FinkokPacDriver | Finkok-specific SOAP client; implements PacServiceInterface | `app/Services/Pac/FinkokPacDriver.php` |
| ComplementBuilder contracts | Interface per complement type (Pagos, CartaPorte, ComercioExterior) | `app/Contracts/ComplementBuilderInterface.php` |
| InvoicePdfService | Generate PDF from timbrado XML using Blade or external renderer | `app/Services/InvoicePdfService.php` |
| InvoiceMailer | Mailable with attached XML + PDF; dispatched as queued job | `app/Mail/InvoiceMail.php` + job |
| Invoice model | Persists CFDI metadata, status (draft/stamped/cancelled), UUID, XML path | `app/Models/Invoice.php` |
| CsdKey model | Stores certificate serial, issuer RFC, encrypted .key + password | `app/Models/CsdKey.php` |
| Issuer / Receiver | Fiscal entity data (RFC, name, fiscal regime, zip code) | `app/Models/Issuer.php`, `app/Models/Receiver.php` |

---

## Recommended Project Structure

```
app/
├── Contracts/
│   ├── PacServiceInterface.php        # stamp(string $xml): StampResult
│   ├── ComplementBuilderInterface.php # build(Invoice $invoice): string (XML node)
│   └── CsdCredentialInterface.php     # getSignedXml(string $xml): string
│
├── Services/
│   ├── CfdiBuilderService.php         # Composes full CFDI XML via eclipxe/cfdiutils
│   ├── CsdManagerService.php          # Loads, decrypts, validates CSD
│   ├── InvoicePdfService.php          # Renders PDF (Blade+DomPDF or external)
│   ├── InvoiceSigningService.php      # Applies sello digital to XML
│   └── Pac/
│       ├── FinkokPacDriver.php        # Finkok SOAP integration
│       └── PacDriverFactory.php       # Resolves driver by name from config
│
├── Services/Complements/
│   ├── PagosComplementBuilder.php     # Recepción de Pagos 2.0
│   ├── CartaPorteComplementBuilder.php # Carta Porte 3.1
│   └── ComercioExteriorComplementBuilder.php # Comercio Exterior 2.0
│
├── Jobs/
│   ├── StampInvoiceJob.php            # Async: send to PAC, save result
│   ├── GenerateInvoicePdfJob.php      # Async: render PDF, store
│   └── SendInvoiceEmailJob.php        # Async: mail XML + PDF
│
├── Mail/
│   └── InvoiceMail.php                # Mailable with XML + PDF attachments
│
├── Models/
│   ├── Invoice.php                    # Central CFDI record
│   ├── InvoiceItem.php                # Line items (conceptos)
│   ├── Issuer.php                     # Emisor fiscal data
│   ├── Receiver.php                   # Receptor fiscal data
│   ├── CsdKey.php                     # Certificado de sello digital
│   ├── PaymentComplement.php          # Pagos 2.0 data
│   ├── CartaPorteComplement.php       # Carta Porte 3.1 data
│   └── ComercioExteriorComplement.php # Comercio Exterior 2.0 data
│   # SAT Catalogs (new):
│   ├── FiscalRegime.php
│   ├── CfdiUse.php
│   ├── PaymentForm.php
│   ├── PaymentMethod.php
│   ├── CfdiType.php
│   ├── ProductServiceKey.php
│   └── SatUnit.php
│
├── Filament/
│   ├── Resources/
│   │   ├── InvoiceResource.php
│   │   ├── IssuerResource.php
│   │   ├── ReceiverResource.php
│   │   ├── CsdKeyResource.php
│   │   └── [catalog resources]/
│   └── Actions/
│       ├── StampInvoiceAction.php     # Filament table/page action
│       ├── CancelInvoiceAction.php
│       └── SendInvoiceEmailAction.php
│
├── Enums/
│   ├── InvoiceStatus.php              # Draft, Stamped, Cancelled, Error
│   └── PacDriver.php                  # Finkok, etc.
│
└── Exceptions/
    ├── CfdiValidationException.php
    ├── CsdException.php
    └── PacException.php

config/
└── pac.php                            # driver: 'finkok', credentials per driver

storage/
└── app/
    └── private/
        └── cfdi/
            ├── xml/                   # Stamped XMLs (UUID-named)
            ├── pdf/                   # Generated PDFs
            └── csd/                  # Encrypted CSD key files
```

---

## Architectural Patterns

### Pattern 1: PAC Service Interface (Strategy Pattern)

**What:** Define a `PacServiceInterface` contract that every PAC driver implements. The application only knows the interface; concrete drivers are resolved by `PacDriverFactory` from `config/pac.php`.

**When to use:** Immediately — this is the multi-PAC architecture requirement. Finkok is the first driver.

**Trade-offs:** Slight indirection, but enables swapping PACs without touching invoice logic. Worth it given explicit project requirement.

**Example:**
```php
// app/Contracts/PacServiceInterface.php
interface PacServiceInterface
{
    public function stamp(string $unsignedXml): StampResult;
    public function cancel(string $uuid, string $rfcEmisor, string $rfcReceptor, string $total): CancelResult;
    public function query(string $uuid): QueryResult;
}

// app/Services/Pac/FinkokPacDriver.php
final class FinkokPacDriver implements PacServiceInterface
{
    public function __construct(
        private readonly string $username,
        private readonly string $password,
        private readonly bool $sandbox = false,
    ) {}

    public function stamp(string $unsignedXml): StampResult
    {
        // Finkok SOAP call: stamp -> returns timbrado XML with UUID
    }
}

// app/Services/Pac/PacDriverFactory.php
final class PacDriverFactory
{
    public static function make(): PacServiceInterface
    {
        return match(config('pac.driver')) {
            'finkok' => new FinkokPacDriver(
                username: config('pac.finkok.username'),
                password: config('pac.finkok.password'),
                sandbox: config('pac.finkok.sandbox', true),
            ),
            default => throw new \InvalidArgumentException('Unknown PAC driver'),
        };
    }
}
```

**Binding in AppServiceProvider:**
```php
$this->app->bind(PacServiceInterface::class, fn () => PacDriverFactory::make());
```

---

### Pattern 2: CFDI Builder via eclipxe/cfdiutils

**What:** `CfdiBuilderService` wraps eclipxe/cfdiutils to produce a valid, sealed CFDI 4.0 XML string. It takes an `Invoice` model (with relationships eagerly loaded), composes the node tree, signs it with the CSD, and returns the XML ready for timbrado.

**When to use:** Any time an Invoice needs to be sent to a PAC.

**Trade-offs:** eclipxe/cfdiutils handles XSD validation and cadena original generation — do not reimplement those. The builder just composes the data.

**Key eclipxe/cfdiutils classes:**
- `CfdiUtils\Cfdi40\Cfdi40Creator` — creates a CFDI 4.0 document
- `CfdiUtils\CadenaOrigen\XsltBuilderInterface` — generates cadena original
- `CfdiUtils\Certificado\Certificado` — loads .cer file
- `CfdiUtils\PemPrivateKey\PemPrivateKey` — loads .key as PEM
- `CfdiUtils\Validate\Cfdi40Validator` — validates before sending

**Example skeleton:**
```php
final class CfdiBuilderService
{
    public function __construct(private readonly CsdManagerService $csdManager) {}

    public function buildXml(Invoice $invoice): string
    {
        $creator = new Cfdi40Creator([
            'Version' => '4.0',
            'Serie' => $invoice->serie,
            'Folio' => $invoice->folio,
            'Fecha' => $invoice->issued_at->format('Y-m-d\TH:i:s'),
            'FormaPago' => $invoice->paymentForm->code,
            'SubTotal' => number_format($invoice->subtotal, 2, '.', ''),
            'Moneda' => $invoice->currency->code,
            'Total' => number_format($invoice->total, 2, '.', ''),
            'TipoDeComprobante' => $invoice->cfdiType->code,
            'MetodoPago' => $invoice->paymentMethod->code,
            'LugarExpedicion' => $invoice->issuer->zip_code,
        ]);

        $creator->addEmisor(/* ... */);
        $creator->addReceptor(/* ... */);
        foreach ($invoice->items as $item) {
            $creator->addConcepto(/* ... */);
        }

        $this->csdManager->seal($creator, $invoice->issuer->activeCsd);

        return $creator->asXml();
    }
}
```

---

### Pattern 3: Complement Builder Interface

**What:** Each complement type (Pagos, CartaPorte, ComercioExterior) implements `ComplementBuilderInterface`. `CfdiBuilderService` checks the invoice type and calls the appropriate builder to attach the complement node.

**When to use:** When the invoice has a complement (type P for Pagos, or custom flags for Carta Porte / Comercio Exterior).

**Trade-offs:** Keeps complement logic isolated and testable. Each complement has its own builder, model, and migration.

**Example:**
```php
interface ComplementBuilderInterface
{
    public function supports(Invoice $invoice): bool;
    public function attachTo(Cfdi40Creator $creator, Invoice $invoice): void;
}

final class PagosComplementBuilder implements ComplementBuilderInterface
{
    public function supports(Invoice $invoice): bool
    {
        return $invoice->cfdiType->code === 'P';
    }

    public function attachTo(Cfdi40Creator $creator, Invoice $invoice): void
    {
        // Attach Pagos 2.0 complement node to $creator
    }
}
```

Register all builders in `AppServiceProvider`:
```php
$this->app->tag([
    PagosComplementBuilder::class,
    CartaPorteComplementBuilder::class,
    ComercioExteriorComplementBuilder::class,
], 'cfdi-complement-builders');
```

`CfdiBuilderService` receives tagged builders via constructor injection and iterates `supports()`.

---

### Pattern 4: Invoice Lifecycle State Machine

**What:** The `Invoice` model tracks status via an `InvoiceStatus` enum. Status transitions happen in service methods, not in controllers or Filament actions directly.

**Valid transitions:**
```
Draft → Stamped (via PAC)
Draft → Error (PAC rejection)
Stamped → CancelRequested (user triggers cancel)
CancelRequested → Cancelled (PAC confirms)
CancelRequested → Stamped (PAC rejects cancel)
Error → Draft (user corrects data)
```

**Example enum:**
```php
enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Stamped = 'stamped';
    case CancelRequested = 'cancel_requested';
    case Cancelled = 'cancelled';
    case Error = 'error';
}
```

---

### Pattern 5: Async Post-Stamp Pipeline (Queue Jobs)

**What:** After successful timbrado, a chain of queued jobs handles: saving the timbrado XML to storage, generating the PDF, sending the email.

**When to use:** Always — these operations can take seconds and must not block the HTTP request or Filament form submission.

**Example chain:**
```php
// In StampInvoiceJob::handle()
Bus::chain([
    new GenerateInvoicePdfJob($invoice),
    new SendInvoiceEmailJob($invoice),
])->dispatch();
```

---

## Data Flow

### Primary Flow: Filament Form → XML → PAC → PDF → Email

```
[User fills Filament form]
        │
        ▼
[Invoice::create() + InvoiceItem::create() × N]
        │
        ▼ (Stamp action clicked)
[StampInvoiceAction → dispatch StampInvoiceJob]
        │
        ▼ (queue worker)
[StampInvoiceJob::handle()]
        │
        ├─▶ CsdManagerService::loadCredential(issuer.activeCsd)
        │         └─ decrypt .key with password → PEM key
        │
        ├─▶ CfdiBuilderService::buildXml(invoice)
        │         ├─ Cfdi40Creator: compose all CFDI nodes
        │         ├─ ComplementBuilderInterface::attachTo() (if applicable)
        │         └─ Seal XML with CSD (sello digital + certificado)
        │
        ├─▶ PacServiceInterface::stamp(sealedXml)
        │         └─ FinkokPacDriver: SOAP call → timbrado XML with UUID + NoCertificadoSAT + SelloSAT
        │
        ├─▶ invoice->update([status=>'stamped', uuid=>..., xml_path=>..., stamped_at=>...])
        │
        └─▶ Bus::chain([GeneratePdfJob, SendEmailJob])->dispatch()
                    │
                    ├─ InvoicePdfService::generate(invoice) → storage/app/private/cfdi/pdf/{uuid}.pdf
                    └─ InvoiceMail: attach XML + PDF → send via configured mail driver
```

### CSD Storage Flow

```
[User uploads .cer + .key + password in Filament]
        │
        ▼
[CsdKeyResource form]
        │
        ▼
[CsdManagerService::store()]
        ├─ Parse .cer → extract RFC, serial, validity, issuer name
        ├─ Validate RFC matches Issuer RFC
        ├─ Encrypt .key bytes with app key (Laravel encryption)
        ├─ Hash password, store encrypted
        └─ CsdKey::create([...]) → storage/app/private/cfdi/csd/{serial}.key.enc
```

### Cancellation Flow

```
[User clicks Cancel action in Filament]
        │
        ▼
[CancelInvoiceAction → dispatch CancelInvoiceJob]
        │
        ▼ (queue worker)
[PacServiceInterface::cancel(uuid, rfcEmisor, rfcReceptor, total)]
        │
        ├─ Success → invoice->update([status=>'cancel_requested'])
        │            (SAT confirms within minutes/hours via Finkok webhook or polling)
        └─ Error → invoice->update([status=>'error', last_pac_error=>...])
```

---

## Build Order (Phase Dependencies)

```
Phase 1: SAT Catalogs (prerequisite for everything)
    → FiscalRegime, CfdiUse, PaymentForm, PaymentMethod, CfdiType
    → ProductServiceKey, SatUnit
    → Filament resources for catalog browsing

Phase 2: CSD Management (prerequisite for signing)
    → CsdKey model + migration + factory
    → CsdManagerService (load, decrypt, validate)
    → Filament CsdKeyResource (upload UI)

Phase 3: Issuer + Receiver (prerequisite for CFDI entity data)
    → Issuer model (RFC, name, fiscal regime, zip, activeCsd relationship)
    → Receiver model (RFC, name, fiscal regime, zip, cfdi_use)
    → Filament resources for both

Phase 4: CFDI Base (Ingreso, Egreso, Traslado) — core deliverable
    → Invoice + InvoiceItem models
    → CfdiBuilderService (uses eclipxe/cfdiutils)
    → InvoiceSigningService (sello digital)
    → PacServiceInterface + FinkokPacDriver
    → StampInvoiceJob
    → Filament InvoiceResource with Stamp action

Phase 5: Post-Stamp Pipeline
    → InvoicePdfService (Blade template or DomPDF)
    → InvoiceMail + SendInvoiceEmailJob
    → Storage of XML + PDF
    → Filament download/email actions

Phase 6: Cancellation
    → CancelInvoiceAction
    → CancelInvoiceJob
    → PAC cancellation polling or webhook

Phase 7: Complements
    → Pagos 2.0 (separate model + ComplementBuilder)
    → Carta Porte 3.1 (separate model + ComplementBuilder)
    → Comercio Exterior 2.0 (separate model + ComplementBuilder)
    (each complement is independent; can be built sequentially)
```

**Critical dependency chain:**
`SAT Catalogs → CSD → Issuer/Receiver → CFDI Base → Post-Stamp → Complements`

Complements depend on CFDI Base being solid. Do not build complements in parallel with Phase 4.

---

## Anti-Patterns

### Anti-Pattern 1: Business Logic in Filament Actions

**What people do:** Put the SOAP call, XML generation, and PDF creation directly inside a Filament action's `action()` closure.

**Why it's wrong:** Blocks the HTTP request (can take 3-10 seconds), cannot be retried on failure, untestable without spinning up Filament, couples UI to infrastructure.

**Do this instead:** Filament action dispatches a queued job. The job contains all the logic. The Filament UI shows status via the `InvoiceStatus` enum field.

---

### Anti-Pattern 2: One Monolithic InvoiceService

**What people do:** Create a single `InvoiceService` that handles XML building, CSD management, PAC calls, PDF generation, and email sending.

**Why it's wrong:** Cannot test individual concerns. Cannot swap PAC without touching XML logic. Violates single responsibility. Becomes unmaintainable for 3 complement types.

**Do this instead:** Each concern is its own service class with a clear contract. Compose them in jobs.

---

### Anti-Pattern 3: Storing CSD Password in Plaintext

**What people do:** Store the .key password directly in the `csd_keys` database table as a string.

**Why it's wrong:** Key compromise exposes all historical CFDIs to forgery (the private key + password = signing capability).

**Do this instead:** Encrypt the password using Laravel's `Crypt::encryptString()` before persisting. Also encrypt the .key file bytes. The decrypted credential should only exist in memory during the stamp operation.

---

### Anti-Pattern 4: Skipping eclipxe/cfdiutils Validation

**What people do:** Build the XML manually or skip `Cfdi40Validator` before sending to PAC.

**Why it's wrong:** Finkok will reject structurally invalid XML with opaque error codes. Debugging PAC rejections is expensive. Local validation catches ~90% of issues before any PAC call.

**Do this instead:** Always run `Cfdi40Validator` (or the equivalent creator validation) before calling `PacServiceInterface::stamp()`. Throw a `CfdiValidationException` with the validation errors so the UI can display them.

---

### Anti-Pattern 5: Hardcoding Finkok as the Only PAC

**What people do:** Call Finkok SOAP methods directly in `StampInvoiceJob` or `InvoiceService`.

**Why it's wrong:** Explicit project requirement states multi-PAC architecture. Even if only Finkok is used in v1, hardcoding makes the switch painful.

**Do this instead:** Implement `PacServiceInterface` from day one. Bind via `AppServiceProvider`. Finkok is just the first concrete implementation.

---

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Finkok (PAC) | SOAP via PHP SoapClient or guzzle-based SOAP wrapper; credentials in `config/pac.php` | Use sandbox=true until production CSD is activated. Finkok requires pre-registration of RFC. |
| SAT WSDL (indirect) | Via Finkok — SAT communication happens PAC-side | Direct SAT integration not needed; PAC handles it. |
| Mail (invoice delivery) | Laravel Mailable + configured mail driver (Postmark/Resend/SES) | Already configured in the project. |
| Storage (XML/PDF/CSD) | Laravel filesystem, `private` disk, `cfdi/` directory | Do NOT use public disk for these files. |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| Filament → Services | Direct method call (actions call service methods or dispatch jobs) | Filament actions should only dispatch jobs, not call services directly for long operations. |
| Services → PacServiceInterface | Interface injection via IoC container | Never instantiate FinkokPacDriver directly outside factory/container. |
| CfdiBuilderService → CsdManagerService | Constructor injection | CsdManager provides signing capability; Builder composes structure. |
| Jobs → Services | Constructor injection via Laravel's job dependency injection | Jobs receive resolved service instances automatically. |
| ComplementBuilders → CfdiBuilderService | Tagged service collection + interface iteration | Builder iterates all tagged complement builders, calls `supports()` then `attachTo()`. |

---

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| Single company, low volume (<500 invoices/month) | Current Laravel queue with database driver is sufficient. SQLite not acceptable — already using PostgreSQL. |
| Single company, medium volume (500-5000/month) | Switch queue to Redis for better performance. Add `GenerateInvoicePdfJob` to a separate `pdf` queue. |
| Multi-company or high volume (5000+/month) | Out of scope per PROJECT.md. Would require queue partitioning, PAC account limits, and possibly microservice extraction for XML/PDF generation. |

### Scaling Priorities

1. **First bottleneck:** PAC API rate limits — Finkok imposes per-account limits. Throttle `StampInvoiceJob` with job middleware if needed.
2. **Second bottleneck:** PDF generation — CPU-intensive if using DomPDF. Move to a dedicated `pdf` queue with higher timeout.

---

## Sources

- CFDI 4.0 Anexo 20 SAT specification (stable since Jan 2022): establishes mandatory fields, complement schemas, XSD structure
- eclipxe/cfdiutils GitHub (eclipxe13/CfdiUtils): PHP 8.1+ compatible, covers CFDI 4.0, includes creator, validator, cadena original builder, CSD handling — HIGH confidence from training data + well-known library in Mexican PHP ecosystem
- Finkok API documentation: SOAP-based, requires pre-registered RFC, provides stamp/cancel/query endpoints — MEDIUM confidence (training data; verify exact SOAP endpoint URLs from Finkok portal)
- Laravel service container / interface binding patterns: HIGH confidence (official Laravel docs)
- Laravel queue job chaining (Bus::chain): HIGH confidence (Laravel 12 docs)
- Laravel Crypt facade for CSD encryption: HIGH confidence (official Laravel docs)

---

*Architecture research for: CFDI 4.0 invoicing system on Laravel 12 + Filament v5*
*Researched: 2026-02-27*
