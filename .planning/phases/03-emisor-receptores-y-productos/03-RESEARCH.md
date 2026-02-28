# Phase 3: Emisor, Receptores y Productos - Research

**Researched:** 2026-02-27
**Domain:** Fiscal Entity Management — Filament Settings Page, CRUD Resources, RFC Validation, Soft Deletes, Repeater Tax Config
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

#### Emisor setup
- Filament settings page (not a CRUD resource) — single form for the one emisor record
- Supports multiple fiscal regimes (regímenes fiscales) — stored as a relation, selectable per invoice in Phase 4
- Stores fiscal data: RFC, RazonSocial, DomicilioFiscalCP, plus optional logo upload (image field for future PDF generation in Phase 5)
- Top-level navigation item (not nested under a "Configuración" group)

#### Receptor RFC & validation
- RFC validation: 12-char persona moral, 13-char persona física, plus generic RFCs
- Generic RFCs supported: XAXX010101000 (público en general) and XEXX010101000 (extranjero)
- Auto-fill when XAXX010101000 entered: nombre → "PÚBLICO EN GENERAL", régimen → 616, uso CFDI → S01
- Duplicate RFCs allowed — same RFC can have multiple receptor records
- Soft delete — archived receptors hidden from search but preserved for invoice history, restorable

#### Product tax config
- Taxes configured as repeater/table of tax lines per product — each line: Impuesto, TipoFactor, TasaOCuota
- Preset tax templates available on product creation: "Solo IVA 16%", "IVA 16% + ISR retenido", "Exento", etc. — user picks a template then can customize
- Quantity is per-invoice only — product catalog stores unit price and tax config, not default quantity
- Soft delete — consistent with receptors

#### Search & select UX (for Phase 4 consumption)
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

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope

</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| ENT-01 | Usuario puede configurar datos del emisor (RFC, nombre, régimen fiscal, domicilio fiscal) | Filament settings page via `HasForms` — single record pattern with `firstOrCreate` on boot |
| ENT-02 | Usuario puede crear y gestionar catálogo de receptores (clientes) | Standard Filament resource with CreateRecord, ListRecords, EditRecord pages, SoftDeletes |
| ENT-03 | Receptor almacena RFC, nombre fiscal, domicilio fiscal CP, régimen fiscal y uso CFDI predeterminado | Receptor model columns + FK relations to regimenes_fiscales and usos_cfdi catalogs |
| ENT-04 | Sistema valida formato de RFC al registrar receptor (12 chars persona moral, 13 persona física) | Custom validation rule class + Filament form `->rules([])` — regex: `/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/` |
| ENT-05 | Usuario puede buscar y seleccionar receptor existente al crear factura | Filament `Select::make()->searchable()->relationship()` on Phase 4 invoice form — data contract established here |
| PROD-01 | Usuario puede crear catálogo de productos/servicios con ClaveProdServ, ClaveUnidad, descripción y precio unitario | Standard Filament resource; `precio_unitario` stored as `decimal(12,6)` for CFDI precision |
| PROD-02 | Usuario puede buscar y seleccionar producto existente al agregar concepto a factura | Same `Select::make()->searchable()` pattern as ENT-05 — data contract established here |
| PROD-03 | Producto almacena configuración de impuestos (IVA, ISR, IEPS) y ObjetoImp | `producto_impuestos` pivot table OR JSON column; Filament Repeater for tax lines |

</phase_requirements>

---

## Summary

Phase 3 introduces three new entity types — Emisor (single-record settings), Receptor (customer catalog), and Producto (product/service catalog). The technical work spans three distinct patterns: a Filament settings page for the singleton Emisor, two standard CRUD resources for Receptores and Productos, and a tax-line repeater with template presets for Productos.

The project already has all necessary SAT catalog models in place (RegimenFiscal, UsoCfdi, ClaveProdServ, ClaveUnidad, Impuesto, TipoFactor, TasaOCuota, ObjetoImp) from Phase 1. This phase wires those catalogs to new business entity models. The established patterns — `final class`, `declare(strict_types=1)`, `#[Override]`, `casts()` method, `HasFactory`, Filament 5 Schema API — must be followed exactly.

The most architecturally interesting decision is how to store Emisor's multi-regime capability. Since the Emisor is a singleton with multiple fiscal regimes, a separate `emisor_regimenes_fiscales` pivot table is the clean approach (not a JSON array). For product taxes, a `producto_impuestos` child table (not JSON) is strongly preferred for queryability in Phase 4 when building CFDI tax nodes.

**Primary recommendation:** Use Filament's `HasForms` settings page pattern for Emisor (single `emisores` table row), standard CRUD resources with soft deletes for Receptor and Producto, a pivot table `emisor_regimen_fiscal` for multi-regime storage, a child table `producto_impuestos` for tax lines (repeater-backed), and a custom `ValidaRfc` rule class for RFC format enforcement.

---

## Standard Stack

### Core (Already in Project — No New Dependencies Needed)

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| filament/filament | ^5.2.4 | Settings page, CRUD resources, Repeater, Select with search | Already installed; all patterns used in Phases 1 & 2 |
| laravel/framework | ^12.53 | Eloquent models, migrations, validation rules | Core framework |
| spatie/laravel-data | ^4.20 | DTOs for search result contracts | Already installed from Phase 2 |
| SoftDeletes | Laravel built-in | Archive receptores and productos without destroying invoice history | Used on Csd model already |

### Filament 5 Components Used in This Phase

| Component | Import Namespace | Purpose |
|-----------|-----------------|---------|
| `Pages\SettingsPage` (or `HasForms` on a custom Page) | `Filament\Pages\` | Emisor singleton form |
| `TextInput`, `Select`, `FileUpload`, `Repeater` | `Filament\Forms\Components\` | Form fields |
| `Select::make()->searchable()->relationship()` | Filament Forms | Type-ahead receptor/product select |
| `CreateRecord`, `ListRecords`, `EditRecord` | `Filament\Resources\Pages\` | Receptor and Producto CRUD pages |
| `SoftDeleteAction`, `RestoreAction`, `ForceDeleteAction` | `Filament\Tables\Actions\` | Soft-delete table actions |
| `TrashedFilter` | `Filament\Tables\Filters\` | Show/hide archived records |

**No new Composer packages are required for this phase.**

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `producto_impuestos` child table | JSON column on `productos` | JSON is not queryable by Phase 4 tax aggregation; child table allows proper joins |
| `emisor_regimen_fiscal` pivot table | JSON array on `emisores` | Pivot enables future per-regime validation and cleaner Phase 4 selection logic |
| Custom `ValidaRfc` rule class | Inline `->regex()` rule | Custom rule class is reusable across Receptor and any Phase 4 invoice form; better error messages |
| Filament `HasForms` settings page | Singleton Eloquent CRUD resource | Settings page has no index/list — correct for single emisor; CRUD resource implies multiple records |

---

## Architecture Patterns

### Recommended Directory / File Structure

```
app/
├── Models/
│   ├── Emisor.php                    # Singleton model (id=1 enforced)
│   ├── Receptor.php                  # Customer catalog, SoftDeletes
│   └── Producto.php                  # Product catalog, SoftDeletes
├── Rules/
│   └── ValidaRfc.php                 # Reusable RFC format validation rule
├── Filament/
│   ├── Pages/
│   │   └── EmisorSettings.php        # Settings page (not a Resource)
│   └── Resources/
│       ├── ReceptorResource.php
│       ├── ReceptorResource/
│       │   └── Pages/
│       │       ├── ListReceptores.php
│       │       ├── CreateReceptor.php
│       │       └── EditReceptor.php
│       ├── ProductoResource.php
│       └── ProductoResource/
│           └── Pages/
│               ├── ListProductos.php
│               ├── CreateProducto.php
│               └── EditProducto.php
database/
├── migrations/
│   ├── ..._create_emisores_table.php
│   ├── ..._create_emisor_regimen_fiscal_table.php   # pivot
│   ├── ..._create_receptores_table.php
│   ├── ..._create_productos_table.php
│   └── ..._create_producto_impuestos_table.php      # tax lines
└── factories/
    ├── EmisorFactory.php
    ├── ReceptorFactory.php
    └── ProductoFactory.php
tests/
└── Feature/
    ├── Models/
    │   ├── EmisorTest.php
    │   ├── ReceptorTest.php
    │   └── ProductoTest.php
    └── Rules/
        └── ValidaRfcTest.php
```

### Pattern 1: Filament Settings Page (Emisor)

The Emisor is a singleton — there is always exactly one row. Filament 5 does not ship a `SettingsPage` base class out of the box; the pattern is to extend `Filament\Pages\Page` and use the `InteractsWithForms` concern, fetching or creating the singleton record in `mount()`.

```php
// app/Filament/Pages/EmisorSettings.php
declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Emisor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

final class EmisorSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    // No $navigationGroup — top-level nav item per locked decision

    public ?array $data = [];

    public function mount(): void
    {
        $emisor = Emisor::firstOrCreate(['id' => 1]);
        $this->form->fill($emisor->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // RFC, RazonSocial, DomicilioFiscalCP, logo, regimenes relation
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $emisor = Emisor::firstOrCreate(['id' => 1]);
        $emisor->update($this->form->getState());
        // sync regimenes relation
    }
}
```

**Key insight:** The page `view` blade template must also be created (`resources/views/filament/pages/emisor-settings.blade.php`) with a simple form and save button.

### Pattern 2: Standard CRUD Resource with Soft Deletes (Receptor, Producto)

Follows the exact same pattern as `CsdResource` for the resource file and `RegimenFiscalResource` for navigation group decisions. The critical Filament 5 differences to follow (already established in this project):

- `protected static string|BackedEnum|null $navigationIcon` (not `?string`)
- `protected static string|UnitEnum|null $navigationGroup` (not `?string`)
- `public static function form(Schema $schema): Schema` (not `Form $form`)
- `->recordActions([...])` on Table (not on ListRecords page)

```php
// Soft delete actions in table definition (Filament 5 pattern established in CsdResource):
->recordActions([
    EditAction::make(),
    DeleteAction::make()->label('Archivar'),
    RestoreAction::make(),
    ForceDeleteAction::make()->label('Eliminar permanentemente'),
])
->filters([
    TrashedFilter::make(),
])
```

### Pattern 3: RFC Validation Rule

RFC format per SAT rules:
- Persona física: 13 chars — `[A-Z&Ñ]{4}[0-9]{6}[A-Z0-9]{3}` (4-letter surname fragment + 6-digit birth date + 3-char homoclave)
- Persona moral: 12 chars — `[A-Z&Ñ]{3}[0-9]{6}[A-Z0-9]{3}` (3-letter company name fragment + 6-digit date + 3-char homoclave)
- Generic: `XAXX010101000` (público en general) and `XEXX010101000` (extranjero) — both 13 chars

```php
// app/Rules/ValidaRfc.php
declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidaRfc implements ValidationRule
{
    // Matches SAT RFC format: moral (12 chars) or física (13 chars)
    private const REGEX_MORAL   = '/^[A-Z&Ñ]{3}[0-9]{6}[A-Z0-9]{3}$/';
    private const REGEX_FISICA  = '/^[A-Z&Ñ]{4}[0-9]{6}[A-Z0-9]{3}$/';
    private const GENERICOS     = ['XAXX010101000', 'XEXX010101000'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $rfc = strtoupper(trim((string) $value));

        if (in_array($rfc, self::GENERICOS, strict: true)) {
            return; // Always valid
        }

        if (! preg_match(self::REGEX_MORAL, $rfc) && ! preg_match(self::REGEX_FISICA, $rfc)) {
            $fail('El RFC no tiene el formato válido (12 chars persona moral, 13 chars persona física).');
        }
    }
}
```

### Pattern 4: Tax Repeater with Template Presets (Producto)

The Filament `Repeater` component manages the `producto_impuestos` child rows. Template presets are implemented as Filament `Action` buttons that call `$set()` to populate the repeater state, NOT as a separate database table.

```php
// Inside ProductoResource::form() — tax section:
Repeater::make('impuestos')
    ->relationship('impuestos')  // -> producto_impuestos table
    ->schema([
        Select::make('impuesto_clave')
            ->label('Impuesto')
            ->options(Impuesto::query()->pluck('descripcion', 'clave'))
            ->required(),
        Select::make('tipo_factor')
            ->label('Tipo Factor')
            ->options(TipoFactor::query()->pluck('clave', 'clave'))
            ->required(),
        Select::make('tasa_o_cuota_id')
            ->label('Tasa o Cuota')
            ->options(fn (Get $get) => TasaOCuota::query()
                ->where('impuesto', $get('impuesto_clave'))
                ->pluck('valor_maximo', 'id'))
            ->required(),
    ])
    ->defaultItems(0)
    ->addActionLabel('Agregar impuesto')
    ->hintAction(
        Action::make('plantilla')
            ->label('Aplicar plantilla')
            ->form([
                Select::make('template')
                    ->options([
                        'iva16' => 'Solo IVA 16%',
                        'iva16_isr10' => 'IVA 16% + ISR 10% retención',
                        'exento' => 'Exento',
                        'iva0' => 'IVA 0%',
                    ])
            ])
            ->action(function (array $data, Set $set): void {
                $set('impuestos', self::getTaxTemplate($data['template']));
            })
    )
```

### Pattern 5: Emisor → RegimenFiscal Pivot Relationship

```
emisores table: id, rfc, razon_social, domicilio_fiscal_cp, logo_path, timestamps
emisor_regimen_fiscal pivot: emisor_id, regimen_fiscal_clave
```

In the Emisor model:
```php
public function regimenesFiscales(): BelongsToMany
{
    return $this->belongsToMany(
        RegimenFiscal::class,
        'emisor_regimen_fiscal',
        'emisor_id',
        'regimen_fiscal_clave',
        'id',
        'clave'
    );
}
```

In the settings page form, use:
```php
Select::make('regimenesFiscales')
    ->relationship('regimenesFiscales', 'descripcion')
    ->multiple()
    ->required()
```

### Anti-Patterns to Avoid

- **JSON for tax lines:** Do NOT store `producto_impuestos` as a JSON array on the `productos` table. Phase 4 needs to join and aggregate these tax lines when generating CFDI nodes. A proper child table is required.
- **JSON for regimes:** Do NOT store Emisor's fiscal regimes as a JSON array. A pivot table allows the multi-select Filament component and proper Eloquent `sync()` on save.
- **Scopes instead of Builder:** This project established `app/Builders/` pattern in Phase 2. If query methods are needed on Receptor or Producto (e.g., `whereActive()` for soft-delete exclusion), prefer a custom Builder. However, soft-delete queries are handled by Laravel's `SoftDeletes` trait automatically and do NOT require a custom builder.
- **`?string` on Filament properties:** The project has already hit this — use `string|UnitEnum|null` and `string|BackedEnum|null` (never `?string`) for `$navigationGroup` and `$navigationIcon`.
- **Empty `__construct()`:** Per project rules, no empty zero-parameter constructors. Use PHP 8 constructor property promotion where constructors are needed.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| RFC format validation | Custom string parsing logic | `ValidaRfc` implements `ValidationRule` with two regex patterns + generic RFC whitelist | SAT format has subtleties (Ñ, &, length differences); centralize once |
| Soft delete UI actions | Custom Livewire components | Filament `DeleteAction`, `RestoreAction`, `ForceDeleteAction` + `TrashedFilter` | Built-in; handles confirmation modals, bulk operations, filter state |
| Singleton record enforcement | Guard clauses in every controller | `firstOrCreate(['id' => 1])` in settings page `mount()` + database `unique` constraint | Simple and proven; no custom middleware or service needed |
| Tax line management | Custom array manipulation JS | Filament `Repeater` with `->relationship()` | Handles add/remove/reorder UI and automatically syncs child records on save |
| Type-ahead search UI | Custom Livewire search component | Filament `Select::make()->searchable()` with `->getSearchResultsUsing()` | Built-in; handles debounce, keyboard navigation, "create new" modal |

**Key insight:** Every custom UI component considered here has a direct Filament 5 equivalent. Building custom Livewire components would duplicate Filament's battle-tested implementation.

---

## Common Pitfalls

### Pitfall 1: Filament Repeater + `->relationship()` requires Eloquent relation on model

**What goes wrong:** Defining a `Repeater::make('impuestos')->relationship('impuestos')` but forgetting to define `impuestos()` as a `HasMany` relation on the `Producto` model — Filament silently fails or throws a confusing error.

**Why it happens:** The Repeater's `relationship()` call looks up the Eloquent relation by name at runtime.

**How to avoid:** Define `public function impuestos(): HasMany` on `Producto` pointing to `producto_impuestos` table BEFORE testing the Filament form.

**Warning signs:** `Call to undefined method` or `RelationNotFoundException` in Filament form rendering.

### Pitfall 2: RFC case sensitivity

**What goes wrong:** User enters `xaxx010101000` (lowercase) — fails regex validation even though it's a valid generic RFC.

**Why it happens:** SAT RFCs are uppercase but input may not be.

**How to avoid:** Normalize with `strtoupper(trim($value))` at the start of `ValidaRfc::validate()`. Also add Filament `->afterStateUpdated(fn ($set, $state) => $set('rfc', strtoupper($state)))` to auto-uppercase the input field.

### Pitfall 3: TasaOCuota ID vs. CFDI XML value

**What goes wrong:** Storing `tasa_o_cuota_id` (auto-increment integer) in `producto_impuestos` and trying to write it directly into CFDI XML — the XML requires the actual decimal rate value (e.g., `0.160000`).

**Why it happens:** `TasaOCuota` has an auto-increment PK because SAT data doesn't have a single-column natural key (Phase 1 decision, documented in STATE.md).

**How to avoid:** Store the FK to `tasas_o_cuotas.id` in `producto_impuestos`, but when building CFDI in Phase 4, always eager-load the relation and use `$tasaOCuota->valor_maximo` for the XML value.

### Pitfall 4: Filament settings page requires a Blade view

**What goes wrong:** Creating `EmisorSettings extends Page` without a corresponding Blade template — results in a "View not found" exception.

**Why it happens:** Unlike Resource pages (which inherit default Blade templates), custom `Page` classes look for `resources/views/filament/pages/{kebab-case-name}.blade.php`.

**How to avoid:** Always create the Blade view alongside the Page class. Minimal template:
```blade
<x-filament-panels::page>
    <x-filament::section>
        {{ $this->form }}
        <x-filament::button wire:click="save">Guardar</x-filament::button>
    </x-filament::section>
</x-filament-panels::page>
```

### Pitfall 5: `BelongsToMany` pivot with non-standard FK to string PK

**What goes wrong:** Laravel assumes integer FKs on pivots. When the related model (`RegimenFiscal`) has `clave` (string) as PK, the `BelongsToMany` call must explicitly name all four key parameters.

**Why it happens:** Laravel's `BelongsToMany` defaults to `{related_model}_id` which would look for an integer column.

**How to avoid:** Always specify all four key parameters in the relationship:
```php
$this->belongsToMany(RegimenFiscal::class, 'emisor_regimen_fiscal', 'emisor_id', 'regimen_fiscal_clave', 'id', 'clave')
```

### Pitfall 6: Filament `Select->relationship()` for BelongsToMany with non-standard PK

**What goes wrong:** `Select::make('regimenesFiscales')->relationship('regimenesFiscales', 'descripcion')` fails or selects wrong values when the related model uses a string PK.

**Why it happens:** Filament's `relationship()` option assumes integer PKs for the option values.

**How to avoid:** May need to use `->options()` + `->afterStateHydrated()` + `->dehydrated()` pattern instead of `->relationship()`. Test this early in implementation.

---

## Code Examples

Verified patterns from this codebase (confirmed from source files):

### Established Model Pattern (follow exactly)

```php
// Source: app/Models/Csd.php and app/Models/RegimenFiscal.php
declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReceptorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;

final class Receptor extends Model
{
    /** @use HasFactory<ReceptorFactory> */
    use HasFactory;
    use SoftDeletes;

    #[Override]
    protected $fillable = [
        'rfc',
        'nombre_fiscal',
        'domicilio_fiscal_cp',
        'regimen_fiscal_clave',
        'uso_cfdi_clave',
    ];

    protected function casts(): array
    {
        return [
            // string columns don't need explicit casts
        ];
    }

    /** @return BelongsTo<RegimenFiscal, $this> */
    public function regimenFiscal(): BelongsTo
    {
        return $this->belongsTo(RegimenFiscal::class, 'regimen_fiscal_clave', 'clave');
    }

    /** @return BelongsTo<UsoCfdi, $this> */
    public function usoCfdi(): BelongsTo
    {
        return $this->belongsTo(UsoCfdi::class, 'uso_cfdi_clave', 'clave');
    }
}
```

### Established Filament Resource Pattern (follow exactly)

```php
// Source: app/Filament/Resources/CsdResource.php
declare(strict_types=1);

namespace App\Filament\Resources;

use Override;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class ReceptorResource extends Resource
{
    #[Override]
    protected static ?string $model = Receptor::class;

    #[Override]
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    #[Override]
    protected static string|UnitEnum|null $navigationGroup = 'Entidades';

    #[Override]
    protected static ?string $modelLabel = 'Receptor';

    #[Override]
    protected static ?string $pluralModelLabel = 'Receptores';

    public static function form(Schema $schema): Schema { ... }
    public static function table(Table $table): Table { ... }
}
```

### Established Test Pattern (Pest, RefreshDatabase)

```php
// Source: tests/Feature/Models/CsdTest.php
declare(strict_types=1);

use App\Models\Receptor;

it('can be created via factory', function (): void {
    $receptor = Receptor::factory()->create();
    expect($receptor)->toBeInstanceOf(Receptor::class)
        ->and($receptor->id)->toBeGreaterThan(0);
});

it('supports soft delete', function (): void {
    $receptor = Receptor::factory()->create();
    $id = $receptor->id;
    $receptor->delete();
    expect(Receptor::find($id))->toBeNull()
        ->and(Receptor::withTrashed()->find($id))->not->toBeNull();
});
```

### Database Schema (Recommended)

```php
// emisores table — singleton, id is always 1
Schema::create('emisores', function (Blueprint $table): void {
    $table->id();
    $table->string('rfc', 13);
    $table->string('razon_social', 300);
    $table->string('domicilio_fiscal_cp', 5);
    $table->string('logo_path', 500)->nullable();
    $table->timestamps();
});

// emisor_regimen_fiscal pivot
Schema::create('emisor_regimen_fiscal', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('emisor_id')->constrained('emisores')->cascadeOnDelete();
    $table->string('regimen_fiscal_clave', 10);
    $table->foreign('regimen_fiscal_clave')->references('clave')->on('regimenes_fiscales');
    $table->unique(['emisor_id', 'regimen_fiscal_clave']);
});

// receptores table
Schema::create('receptores', function (Blueprint $table): void {
    $table->id();
    $table->string('rfc', 13)->index();
    $table->string('nombre_fiscal', 300);
    $table->string('domicilio_fiscal_cp', 5);
    $table->string('regimen_fiscal_clave', 10)->nullable();
    $table->string('uso_cfdi_clave', 10)->nullable();
    $table->foreign('regimen_fiscal_clave')->references('clave')->on('regimenes_fiscales');
    $table->foreign('uso_cfdi_clave')->references('clave')->on('usos_cfdi');
    $table->timestamps();
    $table->softDeletes();
});

// productos table
Schema::create('productos', function (Blueprint $table): void {
    $table->id();
    $table->string('clave_prod_serv', 10);
    $table->string('clave_unidad', 10);
    $table->string('descripcion', 1000);
    $table->decimal('precio_unitario', 12, 6);
    $table->string('objeto_imp_clave', 2);
    $table->foreign('clave_prod_serv')->references('clave')->on('claves_prod_serv');
    $table->foreign('clave_unidad')->references('clave')->on('claves_unidad');
    $table->foreign('objeto_imp_clave')->references('clave')->on('objetos_imp');
    $table->timestamps();
    $table->softDeletes();
});

// producto_impuestos child table (tax lines)
Schema::create('producto_impuestos', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
    $table->string('impuesto_clave', 3); // '001'=ISR, '002'=IVA, '003'=IEPS
    $table->string('tipo_factor', 10);   // 'Tasa', 'Cuota', 'Exento'
    $table->foreignId('tasa_o_cuota_id')->constrained('tasas_o_cuotas');
    $table->boolean('es_retencion')->default(false);
    $table->timestamps();
    $table->foreign('impuesto_clave')->references('clave')->on('impuestos');
});
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `protected static ?string $navigationGroup` | `protected static string\|UnitEnum\|null $navigationGroup` | Filament 5 | Already enforced in this codebase — see STATE.md [01-03] |
| `form(Form $form)` | `form(Schema $schema): Schema` | Filament 5 | Already enforced — see CsdResource and RegimenFiscalResource |
| Actions on ListRecords page class | `->recordActions([])` on Table definition | Filament 5 | Already enforced — see STATE.md [Phase 02] |
| `Model::$casts` property | `Model::casts()` method | Laravel 12 | Already enforced — see all Phase 1 and 2 models |

---

## Open Questions

1. **Filament `Select->relationship()` with BelongsToMany + string FK**
   - What we know: `RegimenFiscal` uses `clave` (string) as PK; standard `->relationship()` may not handle this correctly.
   - What's unclear: Whether Filament 5's `Select->relationship()` for multi-select BelongsToMany correctly handles non-integer PKs without custom configuration.
   - Recommendation: Implement the Emisor regime select using `->options(RegimenFiscal::query()->pluck('descripcion', 'clave'))` with `->afterStateHydrated()` and `->dehydrated()` as a fallback if `->relationship()` fails. Test this in Wave 1 of implementation.

2. **Producto price precision**
   - What we know: CFDI 4.0 requires 6 decimal places for `ValorUnitario`; `decimal(12,6)` in MySQL/PostgreSQL handles this.
   - What's unclear: Whether Filament's `TextInput::make()->numeric()` preserves 6 decimal places in the UI.
   - Recommendation: Use `->numeric()->step(0.000001)` on the price input and store as `decimal(12,6)`.

3. **Auto-fill for XAXX010101000**
   - What we know: When this RFC is entered, three fields should auto-populate.
   - What's unclear: Whether Filament's `->afterStateUpdated()` with `$set()` triggers reliably for Select fields (vs TextInput).
   - Recommendation: Use `TextInput::make('rfc')->afterStateUpdated(fn ($state, Set $set) => ...)` with `->live()` enabled; test thoroughly.

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest 4.4.1 with pestphp/pest-plugin-laravel |
| Config file | `tests/Pest.php` — RefreshDatabase applied globally to Feature tests |
| Quick run command | `php artisan test --compact --filter=ReceptorTest` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| ENT-01 | Emisor record can be created/updated via settings page | Feature | `php artisan test --compact --filter=EmisorTest` | No — Wave 0 |
| ENT-02 | Receptor CRUD — create, list, edit, soft delete | Feature | `php artisan test --compact --filter=ReceptorTest` | No — Wave 0 |
| ENT-03 | Receptor stores all required fiscal fields | Feature | `php artisan test --compact --filter=ReceptorTest` | No — Wave 0 |
| ENT-04 | RFC validation rejects wrong format, accepts valid formats | Feature | `php artisan test --compact --filter=ValidaRfcTest` | No — Wave 0 |
| ENT-05 | Receptor search/select contract (data exists and is queryable) | Feature | `php artisan test --compact --filter=ReceptorTest` | No — Wave 0 |
| PROD-01 | Producto CRUD — create, list, edit, soft delete | Feature | `php artisan test --compact --filter=ProductoTest` | No — Wave 0 |
| PROD-02 | Producto search/select contract (data exists and is queryable) | Feature | `php artisan test --compact --filter=ProductoTest` | No — Wave 0 |
| PROD-03 | Producto tax lines stored and retrievable via impuestos() relation | Feature | `php artisan test --compact --filter=ProductoTest` | No — Wave 0 |

### Sampling Rate

- **Per task commit:** `php artisan test --compact --filter={relevant test file}`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `tests/Feature/Models/EmisorTest.php` — covers ENT-01
- [ ] `tests/Feature/Models/ReceptorTest.php` — covers ENT-02, ENT-03, ENT-05
- [ ] `tests/Feature/Rules/ValidaRfcTest.php` — covers ENT-04
- [ ] `tests/Feature/Models/ProductoTest.php` — covers PROD-01, PROD-02, PROD-03
- [ ] Factories: `EmisorFactory`, `ReceptorFactory`, `ProductoFactory`

---

## Sources

### Primary (HIGH confidence)

- Codebase — `app/Filament/Resources/CsdResource.php` — Filament 5 resource pattern with `string|BackedEnum|null`, `Schema`, `recordActions()`
- Codebase — `app/Models/Csd.php`, `app/Models/RegimenFiscal.php` — established model conventions (`final`, `declare(strict_types=1)`, `#[Override]`, `casts()` method)
- Codebase — `tests/Feature/Models/CsdTest.php` — Pest test pattern with RefreshDatabase and soft deletes
- Codebase — `database/migrations/2026_02_28_100000_create_csds_table.php` — migration style with explicit indexes
- Codebase — `app/Builders/CsdBuilder.php` — custom Eloquent builder pattern with `#[UseEloquentBuilder]`
- Codebase — `.planning/STATE.md` — all project-level decisions affecting Phase 3 implementation
- Codebase — `composer.json` — confirmed no new dependencies needed; all packages already installed

### Secondary (MEDIUM confidence)

- SAT CFDI 4.0 RFC format rules — 12-char moral / 13-char física / XAXX / XEXX pattern is well-documented in Mexican tax regulations
- Filament 5 Repeater `->relationship()` behavior with HasMany — based on established pattern from PhaseS 1-2 and Filament docs structure

### Tertiary (LOW confidence)

- Filament 5 `Select->relationship()` handling of `BelongsToMany` with string foreign key — needs verification during implementation (flagged as Open Question #1)

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all packages confirmed in `composer.json`; no new dependencies needed
- Architecture: HIGH — patterns directly verified from existing codebase (CsdResource, Csd model, CsdTest)
- Database schema: HIGH — follows established conventions; FK patterns match Phase 1 catalog tables
- Pitfalls: HIGH — items 1-5 are directly derived from decisions documented in STATE.md; item 6 is MEDIUM (needs testing)

**Research date:** 2026-02-27
**Valid until:** 2026-03-27 (stable stack; Filament 5 and Laravel 12 APIs are stable)
