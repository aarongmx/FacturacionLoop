# Coding Conventions

**Analysis Date:** 2026-02-27

## Naming Patterns

**Files:**
- PHP classes: PascalCase - e.g., `User.php`, `CustomUnit.php`, `TariffClassification.php`
- PHP namespaces: PascalCase folder structure matching class names
- Configuration files: lowercase with dots - e.g., `phpunit.xml`, `pint.json`

**Classes:**
- Model classes: PascalCase - e.g., `User`, `Currency`, `CustomUnit`
- Final classes preferred - all models use `final class Model` pattern (e.g., `final class User extends Authenticatable`)
- Factory classes: ModelName + `Factory` - e.g., `UserFactory`, `CustomUnitFactory`
- Service Provider classes: PascalCase + `Provider` - e.g., `AppServiceProvider`, `AdminPanelProvider`

**Functions and Methods:**
- camelCase for all methods and functions
- Public methods: descriptive names like `tariffClassifications()`, `customUnit()`
- Relationship methods return type hints explicitly typed (e.g., `HasMany<TariffClassification, $this>`)
- Factory definition method: always `definition(): array`
- Factory state method: descriptive verb like `unverified(): static`

**Variables:**
- camelCase for all variables
- For Eloquent relationships: descriptive plural/singular names matching the related model (e.g., `$customUnit`, `$tariffClassifications`)

**Types:**
- Enum keys: TitleCase - Laravel convention (though none currently in use)
- Database column names: snake_case - e.g., `custom_unit_code`, `email_verified_at`

## Code Style

**Formatting:**
- Tool: Laravel Pint v1.27.1
- Preset: `laravel` (with custom overrides)
- File: `pint.json`
- Must run `vendor/bin/pint --dirty --format agent` before finalizing changes

**Key Style Rules (from pint.json):**
- `declare(strict_types=1);` required at top of every PHP file (enforced by Pint)
- Final classes strongly preferred (`final_class` rule enabled)
- Array push syntax preferred over `[]` assignment
- Strict comparison operators required (`===`, `!==`)
- Fully qualified strict types enforced (`fully_qualified_strict_types`)
- No backticks for shell execution
- DateTime immutability preferred
- Lowercase keywords and static references
- Global namespace imports for classes, constants, functions enabled

**Class Element Ordering (enforced by Pint):**
Order elements in this sequence:
1. Traits (`use_trait`)
2. Enum cases
3. Constants (public, protected, private)
4. Properties (public, protected, private)
5. Constructor
6. Destructors
7. Magic methods
8. PHPUnit methods
9. Casts method
10. Abstract methods
11. Public static methods
12. Public instance methods
13. Protected static methods
14. Protected instance methods
15. Private static methods
16. Private instance methods

**Linting:**
- Tool: Larastan v3.9 (PHPStan for Laravel)
- Level: `max` (level 10 - strictest)
- Config: `phpstan.neon`
- Includes: Larastan extension + Carbon extension
- Scans: `app/` directory only

## Import Organization

**Order:**
1. Built-in PHP/Laravel classes (`Illuminate\*`, `App\Models\*`)
2. Database factories (`Database\Factories\*`)
3. Traits and mixins
4. Third-party classes (vendor packages)
5. Local application classes

**Path Aliases:**
- No path aliases configured - use full PSR-4 namespaces
- Autoloading: PSR-4 with namespace mapping in `composer.json`
  - `App\*` → `app/`
  - `Database\Factories\*` → `database/factories/`
  - `Database\Seeders\*` → `database/seeders/`
  - `Tests\*` → `tests/`

## Error Handling

**Strategy:** Bootstrap-level configuration with global exception handler

**Implementation:**
- Exception handling configured in `bootstrap/app.php` using `->withExceptions(function (Exceptions $exceptions): void { ... })`
- Middleware configured in `bootstrap/app.php` using `->withMiddleware(function (Middleware $middleware): void { ... })`
- Custom exception handling not yet implemented (empty handler body)

**Patterns:**
- Laravel's default exception handling is used
- HTTP status assertions in tests (e.g., `$response->assertStatus(200)`)
- Database state verification in tests (e.g., `assertDatabaseCount('table_name', expected_count)`)

## Logging

**Framework:** Not explicitly configured - uses Laravel default (likely stack driver via config)

**Patterns:**
- No custom logging observed in application code
- Logging configured via `config/logging.php`

## Comments

**When to Comment:**
- Prefer self-documenting code over comments
- PHPDoc blocks required for all classes and public methods

**PHPDoc Blocks:**
- Used on all class definitions
- Used on all public methods with proper return type declarations
- Include generic type hints for relationships: e.g., `@return HasMany<TariffClassification, $this>`
- Include array shape type definitions: e.g., `@return array<string, mixed>`
- Factory classes: include `@extends Factory<ModelClass>` docblock

Example from `app/Models/CustomUnit.php`:
```php
/**
 * @return HasMany<TariffClassification, $this>
 */
public function tariffClassifications(): HasMany
{
    return $this->hasMany(TariffClassification::class, 'custom_unit_code', 'code');
}
```

Example from `database/factories/UserFactory.php`:
```php
/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
```

## Constructor Property Promotion

**Pattern:** Always use PHP 8 constructor property promotion

Example from `app/Providers/AppServiceProvider.php`:
```php
public function register(): void
{
    //
}
```

When providers/services accept dependencies, use promotion:
```php
public function __construct(public SomeService $service) { }
```

Note: Empty constructors should not be created unless necessary (private constructors may be exceptions).

## Type Declarations

**Return Types:**
- All methods and functions must have explicit return type declarations
- Use appropriate type hints: `void`, `array`, `bool`, `string`, `int`, `mixed`, etc.
- Use union types when applicable: `string|int`
- Use nullable types: `?string` instead of `string|null`

**Method Parameters:**
- Type hint all parameters where possible
- Use nullable syntax when appropriate
- Optional parameters require defaults

Example from `app/Models/TariffClassification.php`:
```php
/**
 * @return BelongsTo<CustomUnit, $this>
 */
public function customUnit(): BelongsTo
{
    return $this->belongsTo(CustomUnit::class, 'custom_unit_code', 'code');
}
```

## Models & Eloquent

**Model Declaration:**
- All models are `final class` extending `Model` or `Authenticatable`
- Declare explicit property overrides with `#[Override]` attribute

**Model Properties:**
- `$fillable` property for mass-assignable attributes
- Override `$incrementing`, `$primaryKey`, `$keyType` when using non-standard primary keys (e.g., custom unit codes)
- Use `casts()` method instead of `$casts` property (as per Laravel 12 convention)

**Relationships:**
- Define return type hints explicitly: `public function relationship(): RelationType`
- Use proper relationship methods: `hasMany()`, `belongsTo()`, etc.
- Include generic type hints in PHPDoc

Example from `app/Models/User.php`:
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

## Filament Integration

**Provider Pattern:**
- Filament configuration in `app/Providers/Filament/AdminPanelProvider.php`
- Extends `PanelProvider` and implements `panel(Panel $panel): Panel` method
- Auto-discovery for resources, pages, and widgets

Resources, pages, and widgets auto-discovered from:
- `app/Filament/Resources/` → Resources
- `app/Filament/Pages/` → Pages
- `app/Filament/Widgets/` → Widgets

## Frontend Conventions

**JavaScript/CSS:**
- Vite build tool configured (v7.0.7)
- Tailwind CSS v4.0 for styling
- Entry points: `resources/css/app.css`, `resources/js/app.js`
- Build scripts: `npm run build` (production), `npm run dev` (watch mode)
- No TypeScript or ESLint configuration detected

**Frontend Build Watch:**
- Ignored directory: `storage/framework/views/` (don't trigger rebuilds)

---

*Convention analysis: 2026-02-27*
