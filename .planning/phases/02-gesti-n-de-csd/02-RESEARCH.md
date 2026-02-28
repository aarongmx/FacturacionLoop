# Phase 2: Gestión de CSD - Research

**Researched:** 2026-02-27
**Domain:** Digital Certificate Management — CSD Upload, Encryption, Parsing, Filament UI
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- All domain logic in Action classes: `UploadCsdAction`, `ValidateCsdExpiryAction`, `ActivateCsdAction`, `DeactivateCsdAction`
- DTOs with Spatie Data: `UploadCsdData`, `CsdData`
- `CsdStatus` backed enum (Active, ExpiringSoon, Expired, Inactive)
- Custom query builder `CsdBuilder` with `whereActive()`, `whereExpiring()` — no scopes
- Form Request for upload validation (file types, required fields)
- Thin model: persistence, relationships, casts only
- Actions are invokable, single-responsibility, transactional
- `declare(strict_types=1)` in every file

#### Upload flow
- Single Filament form with three fields: .cer file, .key file, passphrase
- On submit, the `UploadCsdAction` validates the .cer/.key pair (openssl verification that the key matches the certificate)
- Passphrase is verified by decrypting the .key during pair validation — if it fails, form shows error, nothing is saved
- On success, redirect to CSD detail view showing extracted data with a confirmation toast
- .key file stored encrypted in private storage, passphrase encrypted with Laravel Crypt
- NoCertificado, RFC, and validity dates extracted from .cer on upload

#### Certificate display
- List table columns: NoCertificado, RFC, validity dates (inicio/fin), status badge, upload date
- Status badge color-coded: Active (green), Expiring soon (yellow), Expired (red), Inactive (gray)
- Detail view shows extracted fields only — no raw certificate data (subject, issuer, algorithm)
- CSD records are immutable once uploaded — no edit/replace functionality
- No file download capability — the system is not a file manager
- Navigation group: "Configuración" (separate from "Catálogos SAT")

#### Multi-CSD handling
- Multiple CSDs can be stored simultaneously (useful during certificate renewal)
- Only one CSD can be active at a time for signing
- Explicit "Set as active" action button with confirmation — auto-deactivates the previous active CSD
- Soft delete for old/expired CSDs (audit trail — past invoices reference the signing CSD)
- Deactivating the only active CSD shows a warning ("No podrás timbrar facturas") but is allowed

#### Expiry & validation UX
- 90-day expiry warning: yellow status badge on CSD list/detail + dismissible banner on Filament dashboard with days remaining
- Hard block before PAC when CSD is expired: "El CSD está expirado. Suba un nuevo certificado antes de timbrar."
- Hard block before PAC when no active CSD exists: "No hay CSD activo. Configure un certificado antes de timbrar."
- Both validations happen in the stamping pipeline (Phase 4 will consume `ValidateCsdExpiryAction`)

#### Localization
- All user-facing messages in Spanish — this is a Mexican tax invoicing system

### Claude's Discretion

- Exact Filament form component choices (FileUpload config, TextInput for passphrase)
- Dashboard banner implementation (Filament widget vs custom component)
- Database column types and sizes for encrypted fields
- Exact openssl PHP functions for certificate parsing and pair validation
- Migration structure and naming

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| CSD-01 | Usuario puede subir archivo .cer desde Filament | Filament FileUpload with `acceptedFileTypes` + `mimeTypeMap` for .cer extension; `disk('local')` with private storage |
| CSD-02 | Usuario puede subir archivo .key desde Filament | Same FileUpload config for .key extension; both in single form; `dehydrated(false)` on passphrase field |
| CSD-03 | Sistema almacena contraseña del .key encriptada con Laravel Crypt | `Crypt::encryptString($passphrase)` + `'encrypted'` cast on model column → stored in `TEXT` column |
| CSD-04 | Sistema almacena archivo .key encriptado (nunca en storage público) | `Storage::disk('local')` (private) + file contents encrypted with `Crypt::encryptString(file_get_contents(...))` before storage; path in DB |
| CSD-05 | Sistema extrae NoCertificado y fechas de vigencia del .cer al subir | `phpcfdi/credentials` `Certificate::openFile($cerPath)` → `serialNumber()->bytes()` (NoCertificado), `validFrom()` / `validTo()` (dates as strings) |
| CSD-06 | Sistema valida que el CSD no esté expirado antes de cada firmado | `ValidateCsdExpiryAction` checks `$csd->fecha_fin < now()` → throw or return error; `CsdBuilder::whereActive()` |
| CSD-07 | Sistema muestra alerta cuando el CSD está próximo a expirar (3 meses) | `CsdBuilder::whereExpiring()` — filters where `fecha_fin` between now and +90 days; Filament custom widget on dashboard |
</phase_requirements>

---

## Summary

Phase 2 centers on three technical domains: (1) certificate file parsing using `phpcfdi/credentials`, (2) encrypted storage of sensitive data using Laravel Crypt, and (3) a Filament CRUD resource that handles file uploads to private storage and displays parsed certificate data.

The `phpcfdi/credentials` library (v1.3.0, requires ext-openssl + ext-mbstring) is the established ecosystem tool for this domain. It reads SAT-issued `.cer` and `.key` files in their native DER format without conversion, validates that the private key belongs to the certificate during `Credential::openFiles()` instantiation (throws `UnexpectedValueException` if mismatch), and exposes clean methods for `serialNumber()->bytes()` (NoCertificado), `certificate()->validFrom()` / `validTo()` (validity dates), `certificate()->rfc()`, and `isCsd()` type check. This library is NOT yet installed — it must be added as a production dependency.

The locked decisions reference `spatie/laravel-data` for DTOs. This package is also NOT installed. It must be added. The combination of `phpcfdi/credentials` + Laravel Crypt + Filament FileUpload + `spatie/laravel-data` + custom query builder gives a clean, testable architecture: the `UploadCsdAction` becomes a thin orchestrator that (a) validates the pair, (b) extracts metadata, (c) encrypts and stores the .key, (d) persists the `Csd` model.

**Primary recommendation:** Install `phpcfdi/credentials` and `spatie/laravel-data`, use `Credential::openFiles()` for pair validation and metadata extraction, store .key contents encrypted with `Crypt::encryptString(file_get_contents($path))` in private storage, use `'encrypted'` cast on the passphrase column, and use `CsdStatus` enum with `HasColor` interface for Filament badge rendering.

---

## Standard Stack

### Core (New Dependencies Required)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `phpcfdi/credentials` | ^1.3 | CSD/FIEL certificate parsing, pair validation, metadata extraction | Official phpcfdi ecosystem; used by all serious CFDI libraries; handles DER format natively without OpenSSL CLI |
| `spatie/laravel-data` | ^4.0 | DTOs (`UploadCsdData`, `CsdData`) with validation attributes | Locked decision; provides typed DTOs, validation, and transformation in one package |

### Core (Already in Project)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Crypt (`Illuminate\Support\Facades\Crypt`) | 12.x | Encrypt passphrase string and .key file contents | Built-in; AES-256-CBC + MAC; `'encrypted'` Eloquent cast handles passphrase transparently |
| Laravel Storage (`Illuminate\Support\Facades\Storage`) | 12.x | Store .key file in private `local` disk | Built-in; `local` disk maps to `storage/app/private` — inaccessible from web by default |
| Filament 5 | ^5.2 | Upload form, resource table, action buttons, dashboard widget | Already installed |
| `Illuminate\Database\Eloquent\SoftDeletes` | 12.x | Soft delete for audit trail | Built-in Laravel trait |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| PHP `ext-openssl` | built-in | Required by `phpcfdi/credentials` | Verify it is available in the environment (it is, since Filament uses it too) |
| PHP `ext-mbstring` | built-in | Required by `phpcfdi/credentials` | Also required by Laravel itself — already present |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `phpcfdi/credentials` | Raw PHP `openssl_*` functions | `openssl_*` requires manual DER-to-PEM conversion, hex serial number parsing, and date string parsing — high bug surface; `phpcfdi/credentials` does all this correctly |
| `spatie/laravel-data` | Plain PHP readonly classes | Locked decision; Data objects also handle form validation and resource transformation |
| `Crypt::encryptString()` | Symmetric key from `.env` + `openssl_encrypt()` | Laravel Crypt is already tested, MAC-verified, and tied to `APP_KEY`; no custom implementation needed |
| Store raw .key file | Store encrypted file contents | Raw storage in `private` disk is safe but encrypted contents provide defense-in-depth; decision is locked to encrypt |

**Installation:**
```bash
composer require phpcfdi/credentials spatie/laravel-data
```

---

## Architecture Patterns

### Recommended Project Structure

```
app/
├── Models/
│   └── Csd.php                              # Thin model: casts, softDeletes, fillable
├── Enums/
│   └── CsdStatus.php                        # BackedEnum: Active, ExpiringSoon, Expired, Inactive
├── Actions/
│   ├── UploadCsdAction.php                  # Invokable: validate pair, extract metadata, encrypt, persist
│   ├── ActivateCsdAction.php                # Invokable: deactivate current, set new as active
│   ├── DeactivateCsdAction.php              # Invokable: set status to Inactive, warn if only active
│   └── ValidateCsdExpiryAction.php          # Invokable: throws if expired or no active CSD
├── Data/
│   ├── UploadCsdData.php                    # Input DTO: cerFile, keyFile, passphrase
│   └── CsdData.php                          # Output DTO: NoCertificado, RFC, dates, status
├── Builders/
│   └── CsdBuilder.php                       # Custom query builder: whereActive(), whereExpiring()
├── Filament/
│   ├── Resources/
│   │   ├── CsdResource.php                  # navigationGroup: 'Configuración'
│   │   └── CsdResource/
│   │       └── Pages/
│   │           ├── ListCsds.php
│   │           ├── CreateCsd.php            # Upload form
│   │           └── ViewCsd.php              # Read-only detail view
│   └── Widgets/
│       └── CsdExpiryWarningWidget.php       # Dashboard banner: shows when CSD expires in < 90 days
database/
└── migrations/
    └── 2026_*_create_csds_table.php
tests/
└── Feature/
    ├── Actions/
    │   ├── UploadCsdActionTest.php
    │   ├── ActivateCsdActionTest.php
    │   └── ValidateCsdExpiryActionTest.php
    └── Models/
        └── CsdTest.php
```

### Pattern 1: Csd Model with Encrypted Casts and SoftDeletes

```php
// Source: Laravel 12 encrypted cast + SoftDeletes
declare(strict_types=1);

namespace App\Models;

use App\Builders\CsdBuilder;
use App\Enums\CsdStatus;
use Database\Factories\CsdFactory;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

#[UseEloquentBuilder(CsdBuilder::class)]
final class Csd extends Model
{
    /** @use HasFactory<CsdFactory> */
    use HasFactory;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'no_certificado',
        'rfc',
        'fecha_inicio',
        'fecha_fin',
        'status',
        'key_path',
        'passphrase_encrypted',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin'    => 'date',
            'status'       => CsdStatus::class,
            'passphrase_encrypted' => 'encrypted',  // Laravel auto-decrypts on access
        ];
    }
}
```

**Important:** The `passphrase_encrypted` column must be `TEXT` (not `string(255)`) because encrypted ciphertext is longer than plaintext. The `'encrypted'` cast encrypts on save and decrypts on read automatically.

### Pattern 2: CsdStatus Enum with Filament HasColor

```php
// Source: Filament 5 enum badge pattern via HasColor interface
declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CsdStatus: string implements HasColor, HasLabel
{
    case Active       = 'active';
    case ExpiringSoon = 'expiring_soon';
    case Expired      = 'expired';
    case Inactive     = 'inactive';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active       => 'success',   // green
            self::ExpiringSoon => 'warning',   // yellow
            self::Expired      => 'danger',    // red
            self::Inactive     => 'gray',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Active       => 'Activo',
            self::ExpiringSoon => 'Por vencer',
            self::Expired      => 'Expirado',
            self::Inactive     => 'Inactivo',
        };
    }
}
```

When `TextColumn::make('status')->badge()` is used in the Filament table, it automatically picks up `getColor()` and `getLabel()` from the enum.

### Pattern 3: CsdBuilder with Custom Query Methods

```php
// Source: Laravel 12 UseEloquentBuilder attribute pattern (Laravel 12.19+)
declare(strict_types=1);

namespace App\Builders;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\Csd
 * @extends Builder<TModelClass>
 */
final class CsdBuilder extends Builder
{
    public function whereActive(): static
    {
        return $this->where('status', \App\Enums\CsdStatus::Active);
    }

    public function whereExpiring(int $withinDays = 90): static
    {
        return $this->where('status', \App\Enums\CsdStatus::ExpiringSoon)
            ->orWhere(function (self $query) use ($withinDays): void {
                $query->where('status', \App\Enums\CsdStatus::Active)
                    ->whereBetween('fecha_fin', [now(), now()->addDays($withinDays)]);
            });
    }

    public function whereNotExpired(): static
    {
        return $this->where('fecha_fin', '>', now());
    }
}
```

The `#[UseEloquentBuilder(CsdBuilder::class)]` attribute on `Csd` model (Laravel 12.19+) wires this automatically. No `newEloquentBuilder()` override needed.

### Pattern 4: UploadCsdAction — Certificate Parsing and Encrypted Storage

```php
// Source: phpcfdi/credentials README + Laravel Crypt + Laravel Storage
declare(strict_types=1);

namespace App\Actions;

use App\Data\UploadCsdData;
use App\Enums\CsdStatus;
use App\Models\Csd;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use PhpCfdi\Credentials\Credential;
use UnexpectedValueException;

final class UploadCsdAction
{
    /**
     * @throws \RuntimeException When .cer/.key pair is invalid or passphrase is wrong
     */
    public function __invoke(UploadCsdData $data): Csd
    {
        // This throws UnexpectedValueException if key does not match cert,
        // or if passphrase is wrong (cannot decrypt the .key)
        try {
            $credential = Credential::openFiles(
                $data->cerFilePath,
                $data->keyFilePath,
                $data->passphrase,
            );
        } catch (UnexpectedValueException $e) {
            throw new \RuntimeException(
                'El certificado y la llave privada no coinciden, o la contraseña es incorrecta.',
                previous: $e
            );
        }

        if (! $credential->isCsd()) {
            throw new \RuntimeException('El archivo .cer no es un Certificado de Sello Digital (CSD).');
        }

        $certificate = $credential->certificate();

        // Extract metadata
        $noCertificado = $certificate->serialNumber()->bytes();
        $rfc           = $certificate->rfc();
        $fechaInicio   = Carbon::createFromFormat('YmdHise', $certificate->validFrom());
        $fechaFin      = Carbon::createFromFormat('YmdHise', $certificate->validTo());

        // Encrypt .key file contents before storage
        $keyContents  = file_get_contents($data->keyFilePath);
        $encryptedKey = Crypt::encryptString($keyContents);
        $keyStorePath = 'csd/' . $noCertificado . '.key.enc';
        Storage::disk('local')->put($keyStorePath, $encryptedKey);

        // Determine initial status
        $status = $fechaFin->lt(now())
            ? CsdStatus::Expired
            : ($fechaFin->lt(now()->addDays(90)) ? CsdStatus::ExpiringSoon : CsdStatus::Inactive);

        return Csd::create([
            'no_certificado'        => $noCertificado,
            'rfc'                   => $rfc,
            'fecha_inicio'          => $fechaInicio,
            'fecha_fin'             => $fechaFin,
            'status'                => $status,
            'key_path'              => $keyStorePath,
            'passphrase_encrypted'  => $data->passphrase, // 'encrypted' cast handles encryption
        ]);
    }
}
```

**Note on `validFrom()` / `validTo()` format:** The `phpcfdi/credentials` `Certificate::validFrom()` and `validTo()` return strings in the format returned by PHP's `openssl_x509_parse()` — which is typically `"YYYYMMDDHHMMSSZ"` (UTC). Verify the exact format from a real SAT .cer file and use the correct Carbon parsing format. An alternative is `validFromDateTime()` / `validToDateTime()` which return `DateTimeImmutable` directly.

### Pattern 5: Filament FileUpload for .cer/.key (Private Storage)

```php
// Source: Filament 5.x forms/file-upload documentation
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;

FileUpload::make('cer_file')
    ->label('Archivo .cer')
    ->disk('local')
    ->directory('csd/temp')
    ->visibility('private')
    ->acceptedFileTypes(['application/x-x509-ca-cert'])
    ->mimeTypeMap(['cer' => 'application/x-x509-ca-cert'])
    ->maxSize(512)  // KB
    ->required(),

FileUpload::make('key_file')
    ->label('Archivo .key')
    ->disk('local')
    ->directory('csd/temp')
    ->visibility('private')
    ->acceptedFileTypes(['application/pkcs8', 'application/octet-stream'])
    ->mimeTypeMap(['key' => 'application/pkcs8'])
    ->maxSize(512)
    ->required(),

TextInput::make('passphrase')
    ->label('Contraseña del .key')
    ->password()
    ->revealable()
    ->required()
    ->dehydrated(false),  // Never sent to model; handled manually in afterCreate()
```

**Important:** Filament FileUpload stores the uploaded file path (relative to the disk) in the model column. The form field name (`cer_file`, `key_file`) must be matched in `afterCreate()` to get the actual stored paths for the action.

**Passphrase dehydration:** Use `->dehydrated(false)` on the passphrase TextInput so Filament does not attempt to save it to the `Csd` model directly. The `UploadCsdAction` will receive it from `$this->form->getState()['passphrase']` in the CreateRecord page's `afterCreate()` or a custom form handler.

**Alternative approach:** Instead of a standard Filament CreateRecord, use a custom `Action` in a Filament page that captures all three values, calls `UploadCsdAction`, and handles error display. This avoids the lifecycle hook complexity.

### Pattern 6: Filament Table with Status Badge

```php
// Source: Filament 5 tables + HasColor enum pattern
public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('no_certificado')
                ->label('No. Certificado')
                ->searchable()
                ->sortable(),
            TextColumn::make('rfc')
                ->label('RFC')
                ->searchable(),
            TextColumn::make('fecha_inicio')
                ->label('Vigencia desde')
                ->date('d/m/Y')
                ->sortable(),
            TextColumn::make('fecha_fin')
                ->label('Vigencia hasta')
                ->date('d/m/Y')
                ->sortable(),
            TextColumn::make('status')
                ->label('Estado')
                ->badge(),   // HasColor + HasLabel on CsdStatus enum provides color/label automatically
            TextColumn::make('created_at')
                ->label('Subido')
                ->date('d/m/Y')
                ->sortable(),
        ])
        ->actions([
            Tables\Actions\Action::make('activar')
                ->label('Activar')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->modalHeading('¿Activar este CSD?')
                ->modalDescription('Se desactivará el CSD activo actual.')
                ->action(fn (Csd $record) => app(ActivateCsdAction::class)($record))
                ->visible(fn (Csd $record) => $record->status !== CsdStatus::Active),
        ]);
}
```

### Pattern 7: CsdExpiryWarningWidget (Dashboard Banner)

```php
// Source: Filament 5 custom widget pattern (extends Filament\Widgets\Widget)
declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Csd;
use Filament\Widgets\Widget;

final class CsdExpiryWarningWidget extends Widget
{
    protected static string $view = 'filament.widgets.csd-expiry-warning';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $expiring = Csd::query()->whereExpiring()->first();

        return [
            'expiring' => $expiring,
            'daysRemaining' => $expiring
                ? (int) now()->diffInDays($expiring->fecha_fin, false)
                : null,
        ];
    }
}
```

The widget is auto-discovered from `app/Filament/Widgets/` since `AdminPanelProvider` has `->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')`. If only showing when there is an expiring CSD, use `protected static bool $isLazy = true` and conditionally render the view.

### Anti-Patterns to Avoid

- **Hand-rolling certificate parsing with raw `openssl_*`:** DER-to-PEM conversion, serial number hex-to-bytes parsing, and date format handling are all edge-case-heavy. `phpcfdi/credentials` handles all of this correctly for SAT certificates.
- **Storing the .key file in the `public` disk:** Even with a random filename, files in `public` disk are accessible via URL. Always use `local` disk (maps to `storage/app/private`).
- **Using `string(255)` column for `passphrase_encrypted`:** Encrypted text from `Crypt::encryptString()` is 300–500 characters for a typical passphrase. Use `TEXT` column type.
- **Storing .key file without encryption:** The locked decision requires encrypting the .key contents. Storing the raw binary in private storage is not sufficient — encrypt with `Crypt::encryptString(file_get_contents($path))`.
- **Querying on `passphrase_encrypted` column:** Encrypted columns cannot be used in `WHERE` clauses. Do not add an index on this column.
- **Using `getNavigationBadge()` on the resource for the expiry warning:** A navigation badge is small and easy to miss. Use a full-width dashboard widget as decided. Both can coexist if desired.
- **Leaving temp upload files:** Filament FileUpload stores files in a temp directory on the disk before form submission. After processing in `UploadCsdAction`, delete the temp `.cer` file (the `.key` is re-stored as encrypted — delete the unencrypted temp copy).

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| CSD/.cer parsing (serial number, validity dates, RFC) | Custom `openssl_x509_parse()` wrapper | `phpcfdi/credentials` `Certificate::openFile()` | SAT uses non-standard serial number encoding (bytes-as-ASCII); library handles this correctly |
| Key-certificate pair validation | Custom `openssl_pkey_*` comparison | `Credential::openFiles()` — throws `UnexpectedValueException` if mismatch | Internal `$privateKey->belongsTo($certificate)` check is cryptographically correct |
| Passphrase-protected key validation | Try/catch on `openssl_pkey_get_private()` | `Credential::openFiles()` with the passphrase — throws if wrong | Library decrypts the PKCS#8 DER key; failure means wrong passphrase |
| Encrypted storage | Custom XOR / AES wrapper | `Crypt::encryptString()` + `'encrypted'` model cast | Laravel Crypt uses AES-256-CBC with MAC; already integrated with APP_KEY |
| Status badge colors | Inline `->color(fn() => ...)` closures | `CsdStatus` enum implementing `HasColor` | Enum-based colors are testable and centralized |
| Query scopes | Model `scopeWhereActive()` | `CsdBuilder::whereActive()` | Locked decision; custom builder also provides stronger IDE type inference |
| Expiry date calculation | `strtotime()` / raw timestamp math | Carbon `diffInDays()` via `fecha_fin` date cast | Carbon handles timezone, DST, and format edge cases |

**Key insight:** The certificate domain has several hidden complexity points (SAT's non-standard serial number format, PKCS#8 DER key format, openssl version differences). `phpcfdi/credentials` encapsulates all of this. Do not attempt to replicate it.

---

## Common Pitfalls

### Pitfall 1: validFrom() / validTo() Return Value Format

**What goes wrong:** `Certificate::validFrom()` returns a string. The exact format depends on how PHP's `openssl_x509_parse()` returns dates on the server — typically `"YYYYMMDDHHMMSSZ"` (UTC). Passing this to `Carbon::parse()` may fail or parse incorrectly on some environments.

**Why it happens:** Different PHP/OpenSSL versions may format the certificate validity dates differently.

**How to avoid:** Use `$certificate->validFromDateTime()` and `$certificate->validToDateTime()` which return `DateTimeImmutable` objects — then pass to Carbon: `Carbon::instance($certificate->validFromDateTime())`. These are timezone-safe.

**Warning signs:** `fecha_inicio` or `fecha_fin` stored as `NULL` or wrong year.

### Pitfall 2: MIME Type Detection Fails for .cer/.key Files

**What goes wrong:** Filament's `acceptedFileTypes()` uses the browser's MIME type detection. `.cer` and `.key` files may be detected as `application/octet-stream` by some browsers, not as `application/x-x509-ca-cert`.

**Why it happens:** MIME type for certificate files varies by browser and OS.

**How to avoid:** Use `->mimeTypeMap(['cer' => 'application/x-x509-ca-cert', 'key' => 'application/pkcs8'])` combined with `->acceptedFileTypes(['application/x-x509-ca-cert', 'application/pkcs8', 'application/octet-stream'])`. Include `application/octet-stream` as a fallback. The real validation happens in `UploadCsdAction` using `phpcfdi/credentials` — if the file is not a valid certificate, it will throw.

**Warning signs:** Upload is rejected with "file type not accepted" even for valid .cer files.

### Pitfall 3: Passphrase Leaked in Form State

**What goes wrong:** If `->dehydrated(false)` is not set on the passphrase TextInput, Filament will attempt to set `$csd->passphrase = '...'` on the model, which may leak the plaintext passphrase to logs or exceptions.

**Why it happens:** Filament's default behavior maps form fields to model attributes by name.

**How to avoid:** Always use `->dehydrated(false)` on the passphrase field. Retrieve the passphrase value in the action handler via `$this->form->getState()['passphrase']` before calling `UploadCsdAction`.

**Warning signs:** Model save fails with "unknown column passphrase" or plaintext passphrase appears in model attributes.

### Pitfall 4: Unencrypted .key File Left in Temp Storage

**What goes wrong:** Filament FileUpload stores uploaded files in a temp directory. After the form submits and `UploadCsdAction` runs, the original unencrypted .key file remains in `storage/app/private/csd/temp/`.

**Why it happens:** Filament's file handling copies (or optionally moves) the temp file to the permanent directory, but the original temp copy is managed by Livewire's temporary upload mechanism — it may not be immediately cleaned up.

**How to avoid:** After successfully encrypting and storing the .key file contents, explicitly delete the temp .key file: `Storage::disk('local')->delete($data->keyFilePath)`. Also delete the temp .cer file (not sensitive, but good practice). Livewire cleans temp uploads after a configurable TTL, but proactive deletion is safer.

**Warning signs:** `storage/app/private/csd/temp/` grows over time with unencrypted .key files.

### Pitfall 5: Single-Column PK Conflict — Csd Uses Auto-Increment

**What goes wrong:** Developers apply the existing `string PK` pattern from catalog models to `Csd`. The `no_certificado` is NOT the primary key — it's a unique data field. The `Csd` table needs a standard auto-increment `id`.

**Why it happens:** Phase 1 models all use string PKs (`clave`); developers continue the pattern.

**How to avoid:** `Csd` uses standard auto-increment `id` (Laravel default). `no_certificado` gets a unique index. Multiple CSDs per user scenario (future multi-tenancy) also works better with surrogate PK.

**Warning signs:** Migration sets `$table->string('no_certificado')->primary()` — this is wrong.

### Pitfall 6: Encrypting Empty Passphrase

**What goes wrong:** If passphrase validation is not enforced at the form level, an empty string passphrase may be encrypted and stored. On decryption, `Credential::openFiles()` with an empty passphrase may succeed if the .key was exported without a passphrase, creating a false validation result.

**Why it happens:** Empty string `Crypt::encryptString('')` works without error.

**How to avoid:** Add `->required()` to the passphrase TextInput AND validate in `UploadCsdData` that the passphrase is non-empty. The Form Request (locked decision) should include `'passphrase' => ['required', 'string', 'min:1']`.

**Warning signs:** CSD records with empty passphrase in the database after decryption.

---

## Code Examples

Verified patterns from official sources:

### Certificate Parsing with phpcfdi/credentials

```php
// Source: phpcfdi/credentials README (https://github.com/phpcfdi/credentials)
use PhpCfdi\Credentials\Credential;

try {
    $credential = Credential::openFiles(
        cerFile: '/tmp/cert.cer',
        keyFile: '/tmp/cert.key',
        passPhrase: 'my-passphrase',
    );
} catch (\UnexpectedValueException $e) {
    // Pair mismatch OR wrong passphrase — both throw UnexpectedValueException
    throw new \RuntimeException('Certificado o contraseña incorrectos.', previous: $e);
}

$cert = $credential->certificate();

$noCertificado = $cert->serialNumber()->bytes();    // e.g. "30001000000300023708"
$rfc            = $cert->rfc();                      // e.g. "XAXX010101000"
$validFrom      = $cert->validFromDateTime();        // DateTimeImmutable
$validTo        = $cert->validToDateTime();          // DateTimeImmutable
$isCsd          = $credential->isCsd();              // bool: true for CSD, false for FIEL
```

### Laravel Encrypted Cast

```php
// Source: Laravel 12 docs — https://laravel.com/docs/12.x/eloquent-mutators#encrypted-casting
protected function casts(): array
{
    return [
        'passphrase_encrypted' => 'encrypted',   // Auto-encrypt on save, auto-decrypt on read
    ];
}
```

**Migration must use TEXT:**
```php
$table->text('passphrase_encrypted');  // NOT string(255) — encrypted text is ~300-500 chars
```

### Encrypting .key File Contents for Storage

```php
// Source: Laravel Crypt facade (https://laravel.com/docs/12.x/encryption)
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

// Encrypt binary file contents as a string
$keyBinary   = file_get_contents($tempKeyPath);
$keyEncrypted = Crypt::encryptString($keyBinary);

// Store encrypted text in private disk
$storagePath = 'csd/' . $noCertificado . '.key.enc';
Storage::disk('local')->put($storagePath, $keyEncrypted);

// Later: decrypt when needed for signing
$encryptedContent = Storage::disk('local')->get($storagePath);
$keyBinary        = Crypt::decryptString($encryptedContent);
```

### Filament CreateRecord with Manual Action Invocation

```php
// Source: Filament 5 CreateRecord lifecycle — afterCreate() has access to $this->record
// Source: Filament 5 form state — $this->form->getState() returns all field values

final class CreateCsd extends CreateRecord
{
    protected static string $resource = CsdResource::class;

    protected function afterCreate(): void
    {
        // afterCreate() is called AFTER Filament saves the record (but we use dehydrated(false)
        // for passphrase, so it was not saved). We need a different approach.
    }
}
```

**Recommended alternative — custom Action form instead of CreateRecord:**

```php
// Use a Filament Page with a form Action that calls UploadCsdAction directly,
// bypassing Filament's automatic model-from-form-state save.
// This avoids the lifecycle hook complexity and gives full control over the save process.

// In ListCsds.php:
protected function getHeaderActions(): array
{
    return [
        Action::make('upload')
            ->label('Subir CSD')
            ->form([
                FileUpload::make('cer_file')->...,
                FileUpload::make('key_file')->...,
                TextInput::make('passphrase')->password()->dehydrated(false)->...,
            ])
            ->action(function (array $data, UploadCsdAction $action): void {
                // $data['cer_file'] = relative path on disk
                // $data['key_file'] = relative path on disk
                // $data['passphrase'] = plaintext (not dehydrated(false) in this context)
                $action(UploadCsdData::from([
                    'cerFilePath' => Storage::disk('local')->path($data['cer_file']),
                    'keyFilePath' => Storage::disk('local')->path($data['key_file']),
                    'passphrase'  => $data['passphrase'],
                ]));
            }),
    ];
}
```

This is the cleaner approach: the action form's `->action()` callback receives `$data` directly including passphrase (Filament does NOT save action form data to a model — only CreateRecord/EditRecord pages do that). No lifecycle hook gymnastics needed.

### UseEloquentBuilder Attribute

```php
// Source: Laravel News — https://laravel-news.com/defining-a-dedicated-query-builder-in-laravel-12-with-php-attributes
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;

#[UseEloquentBuilder(CsdBuilder::class)]
final class Csd extends Model { ... }
```

Available since Laravel 12.19. Check current project version satisfies this (project uses `^12.53` — confirmed available).

### Migration for csds Table

```php
Schema::create('csds', function (Blueprint $table): void {
    $table->id();
    $table->string('no_certificado', 40)->unique();
    $table->string('rfc', 13);
    $table->date('fecha_inicio');
    $table->date('fecha_fin');
    $table->string('status', 20)->default('inactive');
    $table->string('key_path', 500);
    $table->text('passphrase_encrypted');   // TEXT — encrypted ciphertext
    $table->timestamps();
    $table->softDeletes();
    $table->index('status');
    $table->index('fecha_fin');             // For whereExpiring() queries
});
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Manual `openssl_x509_parse()` + DER-to-PEM conversion | `phpcfdi/credentials` `Certificate::openFile()` | Library stable since 2019; v1.3 April 2025 | Zero-boilerplate certificate parsing; handles SAT's non-standard serial number |
| Model `scope*()` methods | Custom query builder with `#[UseEloquentBuilder]` | Laravel 12.19 (attribute approach is new) | Locked decision; type-safe builder methods; IDE completion works |
| `$casts = []` property array | `casts(): array` method | Laravel 11+ (method is preferred) | Existing project uses method — follow this pattern |
| Filament `form(Form $form)` | `form(Schema $schema)` | Filament 5 | Already confirmed from Phase 1 — use `Schema` not `Form` |
| `newEloquentBuilder()` override | `#[UseEloquentBuilder(Builder::class)]` attribute | Laravel 12.19 | Locked decision; declarative, no boilerplate |

**Deprecated/outdated:**
- `Form $form` parameter in Filament resource `form()` method: Replaced by `Schema $schema` in Filament 5. (Confirmed from Phase 1 implementation.)
- `protected static ?string $navigationGroup`: Must be typed as `string|UnitEnum|null`, not `?string`. (Confirmed from Phase 1.)

---

## Open Questions

1. **Exact format of `validFrom()` / `validTo()` strings from phpcfdi/credentials**
   - What we know: `Certificate::validFromDateTime()` returns `DateTimeImmutable` — this is safer to use than the string variant
   - What's unclear: Whether `validFrom()` string format matches what Carbon's `parse()` handles directly
   - Recommendation: Use `validFromDateTime()` / `validToDateTime()` (`DateTimeImmutable`) and convert with `Carbon::instance()`; do NOT use the string methods for date storage

2. **Action Form vs CreateRecord Page for Upload Form**
   - What we know: Standard `CreateRecord` requires `afterCreate()` lifecycle hook for post-save logic; passphrase `dehydrated(false)` requires retrieving from form state separately
   - What's unclear: In Filament 5, does `dehydrated(false)` on a TextInput inside an `Action` form still pass the value to `$data` in the `->action()` callback?
   - Recommendation: Based on community knowledge, `dehydrated(false)` prevents saving to model but the `$data` array in action callbacks DOES include the field. Verify this in Wave 0 by building the form first. If it doesn't work, read passphrase from `$this->form->getState()` in `afterCreate()`.

3. **spatie/laravel-data version compatibility with Laravel 12 / PHP 8.5**
   - What we know: spatie/laravel-data v4 is the current stable release; Laravel 12 compatible
   - What's unclear: PHP 8.5 compatibility (PHP 8.5.3 is the project's PHP version — this is a bleeding-edge PHP version)
   - Recommendation: Run `composer require spatie/laravel-data` and let Composer resolve the version; if version conflict, use plain PHP readonly classes as a fallback (violates locked decision, so confirm with user if needed)

4. **'local' disk for Filament FileUpload temp files**
   - What we know: The `local` disk maps to `storage/app/private` and is not web-accessible
   - What's unclear: Whether Filament FileUpload requires the Livewire temp upload disk to be the same as the target disk; Livewire uses its own temp disk (`livewire-tmp` by default)
   - Recommendation: Allow Filament to upload to its default temp disk (Livewire-managed), then in the action handler, read the temp file via `Storage::disk('livewire-tmp')->path($filename)` or the path Filament provides, process it, and store the encrypted version to `local` disk. Then delete the temp file.

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest 4.4.1 |
| Config file | `phpunit.xml` |
| Quick run command | `php artisan test --compact --filter=Feature/Actions` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| CSD-01 | FileUpload .cer accepted on form; path stored in state | Feature | `php artisan test --compact --filter=UploadCsdActionTest` | ❌ Wave 0 |
| CSD-02 | FileUpload .key accepted on form; passphrase dehydrated(false) | Feature | `php artisan test --compact --filter=UploadCsdActionTest` | ❌ Wave 0 |
| CSD-03 | `passphrase_encrypted` column decrypts to original value; Csd model `'encrypted'` cast | Feature | `php artisan test --compact --filter=CsdTest` | ❌ Wave 0 |
| CSD-04 | .key file stored in private disk (not public); contents are encrypted (not raw binary) | Feature | `php artisan test --compact --filter=UploadCsdActionTest` | ❌ Wave 0 |
| CSD-05 | UploadCsdAction extracts correct NoCertificado, RFC, fecha_inicio, fecha_fin from test .cer | Feature | `php artisan test --compact --filter=UploadCsdActionTest` | ❌ Wave 0 |
| CSD-06 | ValidateCsdExpiryAction throws RuntimeException when active CSD is expired | Feature | `php artisan test --compact --filter=ValidateCsdExpiryActionTest` | ❌ Wave 0 |
| CSD-07 | CsdBuilder::whereExpiring() returns CSDs with fecha_fin within 90 days | Feature | `php artisan test --compact --filter=CsdBuilderTest` | ❌ Wave 0 |

**CSD-01 / CSD-02 Filament UI tests:** Full Filament form upload testing requires Dusk (not installed). Unit-test `UploadCsdAction` directly by creating a temp .cer/.key fixture and calling the action. Filament form acceptance is verified manually.

**Test fixture requirement:** Phase 2 tests need real (but SAT test-valid) `.cer` and `.key` file fixtures, OR mocked `Credential::openFiles()` calls. SAT provides test CSD credentials in the `phpcfdi/credentials` test suite — use those fixture files for `tests/fixtures/`.

### Sampling Rate

- **Per task commit:** `php artisan test --compact --filter=Csd`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `tests/Feature/Actions/UploadCsdActionTest.php` — covers CSD-01, CSD-02, CSD-04, CSD-05
- [ ] `tests/Feature/Actions/ActivateCsdActionTest.php` — covers activation/deactivation flow
- [ ] `tests/Feature/Actions/ValidateCsdExpiryActionTest.php` — covers CSD-06
- [ ] `tests/Feature/Models/CsdTest.php` — covers CSD-03 (encrypted cast), CsdBuilder
- [ ] `tests/fixtures/` — test .cer and .key files (copy from `phpcfdi/credentials` test suite)
- [ ] `phpcfdi/credentials` install: `composer require phpcfdi/credentials`
- [ ] `spatie/laravel-data` install: `composer require spatie/laravel-data`

---

## Sources

### Primary (HIGH confidence)

- `phpcfdi/credentials` GitHub README (`https://github.com/phpcfdi/credentials`) — `Credential::openFiles()`, `Certificate` methods, `SerialNumber::bytes()`, `validFromDateTime()`, exception behavior
- `phpcfdi/credentials` `src/Certificate.php` GitHub (`https://github.com/phpcfdi/credentials/blob/main/src/Certificate.php`) — full public method list verified
- `phpcfdi/credentials` `src/SerialNumber.php` GitHub — `bytes()`, `decimal()`, `hexadecimal()` methods
- Laravel 12 docs — Encrypted Cast (`https://laravel.com/docs/12.x/eloquent-mutators#encrypted-casting`) — TEXT column requirement, no-search caveat
- Laravel 12 docs — Encryption (`https://laravel.com/docs/12.x/encryption`) — `Crypt::encryptString()` / `decryptString()`
- Filament 5 docs — File Upload (`https://filamentphp.com/docs/5.x/forms/file-upload`) — disk/directory/visibility/mimeTypeMap/dehydrated
- Filament 5 docs — Text Input (`https://filamentphp.com/docs/5.x/forms/text-input`) — `->password()->revealable()->dehydrated(false)`
- Laravel News — `UseEloquentBuilder` attribute (`https://laravel-news.com/defining-a-dedicated-query-builder-in-laravel-12-with-php-attributes`) — PHP attribute syntax, Laravel 12.19+
- Existing `app/Providers/Filament/AdminPanelProvider.php` — widget auto-discovery config confirmed
- Existing `app/Models/*.php` — established model patterns (`final`, `declare(strict_types=1)`, `casts()` method, `#[Override]`)

### Secondary (MEDIUM confidence)

- Filament community (GitHub discussions) — `->dehydrated(false)` behavior in action forms vs CreateRecord pages — MEDIUM confidence; needs Wave 0 verification
- Filament 3.x/4.x docs — `requiresConfirmation()` table action — pattern is stable across Filament versions; verify in Filament 5
- WebSearch — `CsdStatus` backed enum with `HasColor` + `HasLabel` interfaces for Filament badge — confirmed pattern; implementation straightforward

### Tertiary (LOW confidence)

- `phpcfdi/credentials` SerialNumber `bytes()` = NoCertificado: The README says "SAT interprets serial number data in bytes notation, for example in Comprobante@NoCertificado". This is MEDIUM confidence from the search result; verification against a real SAT .cer file is recommended before finalizing.
- Spatie laravel-data v4 PHP 8.5 compatibility: Not explicitly verified — project uses PHP 8.5.3 which is newer than the package's tested versions. LOW confidence on this specific point.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — `phpcfdi/credentials` is the definitive SAT credential library; Laravel Crypt and Filament FileUpload are well-documented
- Architecture: HIGH — patterns follow existing project conventions; locked decisions are clear
- Certificate parsing specifics (validFrom format, NoCertificado exact value): MEDIUM — library behavior verified from source code, but real SAT .cer file testing recommended in Wave 0
- Filament action form `dehydrated(false)` in `$data` callback: MEDIUM — community-confirmed but Filament 5 specific behavior needs Wave 0 check
- spatie/laravel-data PHP 8.5 compat: LOW — verify on install

**Research date:** 2026-02-27
**Valid until:** 2026-05-27 (phpcfdi/credentials and Filament are stable; Laravel 12 encryption API is stable)
