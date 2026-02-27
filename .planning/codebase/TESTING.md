# Testing Patterns

**Analysis Date:** 2026-02-27

## Test Framework

**Runner:**
- Pest v4.4.1
- PHPUnit v12 (underlying framework)
- Pest Laravel plugin v4.1
- Config: `phpunit.xml`

**Assertion Library:**
- Built-in Pest expectations (replaces traditional PHPUnit assertions)
- Custom expectations can be added via `expect()->extend()`

**Run Commands:**
```bash
php artisan test                    # Run all tests
php artisan test --compact          # Run tests with compact output
php artisan test --filter=testName  # Run specific test
npm run build                       # Build assets if UI tests fail
npm run dev                         # Watch assets during development
```

## Test File Organization

**Location:**
- Feature tests: `tests/Feature/` (co-located tests - may be organized by domain)
- Unit tests: `tests/Unit/`

**Naming:**
- Test files: `*Test.php` suffix (e.g., `ExampleTest.php`, `CustomUnitTest.php`)
- Actual files should extend Pest nomenclature when needed

**Structure:**
```
tests/
├── Feature/                    # Integration/feature tests
│   ├── ExampleTest.php
│   └── Models/                 # Tests organized by domain
│       ├── CustomUnitTest.php
│       └── TariffClassificationTest.php
├── Unit/                       # Unit tests
│   └── ExampleTest.php
├── TestCase.php                # Base test class
└── Pest.php                    # Pest configuration
```

## Test Structure

**Suite Organization:**
Pest uses closure-based test syntax instead of traditional PHPUnit test methods:

```php
<?php

declare(strict_types=1);

test('descriptive test name', function () {
    // Arrange
    $model = Model::factory()->create();

    // Act & Assert
    expect($model)->toBeInstanceOf(Model::class);
});
```

**Patterns:**

1. **Basic Feature Test:**
```php
// From tests/Feature/ExampleTest.php
test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
```

2. **Model Factory Test:**
```php
// From tests/Feature/Models/CustomUnitTest.php
test('se puede crear una fraccion', function () {
    TariffClassification::factory()->create();

    assertDatabaseCount('tariff_classifications', 1);
});
```

3. **Relationship Verification Test:**
```php
// From tests/Feature/Models/TariffClassificationTest.php
test('se puede crear una fraccion y se muestra su unidad medida', function () {
    TariffClassification::factory()->create();

    $fraccion = TariffClassification::query()->with('customUnit')->first();

    expect($fraccion->customUnit)->toBeInstanceOf(CustomUnit::class);
});
```

**Pest Configuration (tests/Pest.php):**
- Feature tests automatically extend `Tests\TestCase` class
- Feature tests automatically use `Illuminate\Foundation\Testing\RefreshDatabase` trait
- Custom expectations can be added: `expect()->extend('toBeOne', function () { return $this->toBe(1); });`
- Global test helper functions available via `tests/Pest.php`

## Mocking

**Framework:** Mockery v1.6.12

**Patterns:**
Not yet explicitly demonstrated in current tests. When needed:
- Use Mockery for dependency mocking
- Laravel's `Http::fake()` for HTTP request mocking
- Factory states for test data variations

**What to Mock:**
- External API calls
- Third-party service dependencies
- Complex service classes in unit tests

**What NOT to Mock:**
- Database models (use factories instead)
- Eloquent relationships when testing with real data
- Configuration values accessible via `config()`

## Fixtures and Factories

**Test Data Pattern:**

All models have corresponding factories in `database/factories/`:
- `UserFactory.php`
- `CurrencyFactory.php`
- `CountryFactory.php`
- `StateFactory.php`
- `IncotermFactory.php`
- `CustomUnitFactory.php`
- `TariffClassificationFactory.php`

**Factory Locations:**
- `database/factories/*.php`

**Factory Usage in Tests:**
```php
// Create single instance
$unit = CustomUnit::factory()->create();

// Create multiple instances
$units = CustomUnit::factory(5)->create();

// Use state methods
$user = User::factory()->unverified()->create();

// With specific attributes
$user = User::factory()->create(['email' => 'test@example.com']);
```

**Factory Pattern Example (CustomUnitFactory):**
```php
/**
 * @extends Factory<CustomUnit>
 */
final class CustomUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Z]{3}'),
            'name' => fake()->word(),
        ];
    }
}
```

**Related Model Creation (TariffClassificationFactory):**
```php
public function definition(): array
{
    return [
        'code' => fake()->regexify('[A-Z]{3}'),
        'name' => fake()->word(),
        'custom_unit_code' => CustomUnit::factory(),  // Auto-creates related model
    ];
}
```

**State Methods (UserFactory):**
```php
public function unverified(): static
{
    return $this->state(fn (array $attributes): array => [
        'email_verified_at' => null,
    ]);
}
```

**Faker Methods Used:**
- `fake()->name()` - Random name
- `fake()->unique()->safeEmail()` - Unique safe email
- `fake()->word()` - Random word
- `fake()->regexify('[A-Z]{3}')` - Regex-based generation
- `fake()->randomDigit()` - Random digit

## Coverage

**Requirements:**
- Coverage source directory: `app/`
- No minimum coverage enforced (not set in `phpunit.xml`)

**View Coverage:**
```bash
php artisan test --coverage
```

Coverage settings in `phpunit.xml`:
```xml
<source>
    <include>
        <directory>app</directory>
    </include>
</source>
```

## Test Types

**Feature Tests (Integration Tests):**
- Location: `tests/Feature/`
- Scope: Test application flows, database interactions, HTTP responses
- Base class: Extends `Tests\TestCase` (auto-configured via Pest)
- Database: `RefreshDatabase` trait auto-applied (rolls back changes after each test)
- Assertions: Use Pest expectations or Laravel assertions
- Examples: `ExampleTest.php`, `Models/CustomUnitTest.php`, `Models/TariffClassificationTest.php`

Example:
```php
test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
```

**Unit Tests:**
- Location: `tests/Unit/`
- Scope: Test individual methods, helpers, pure functions
- Currently minimal usage (only `ExampleTest.php` placeholder)
- Pattern: Standard Pest syntax without HTTP/database context

## Database Testing

**Database Configuration (phpunit.xml):**
```xml
<env name="DB_DATABASE" value="testing"/>
```

**Available Assertions:**
- `assertDatabaseCount('table_name', expected_count)` - Verify record count
- `assertDatabaseHas('table_name', attributes)` - Verify record exists with attributes
- `assertDatabaseMissing('table_name', attributes)` - Verify record does not exist

**Example:**
```php
test('se puede crear una fraccion', function () {
    TariffClassification::factory()->create();

    assertDatabaseCount('tariff_classifications', 1);
});
```

**Automatic Rollback:**
- `RefreshDatabase` trait used in Feature tests
- Automatically rolls back database after each test
- Ensures test isolation without manual cleanup

## Environment for Tests

**Testing Environment (phpunit.xml):**
```xml
<env name="APP_ENV" value="testing"/>
<env name="APP_MAINTENANCE_DRIVER" value="file"/>
<env name="BCRYPT_ROUNDS" value="4"/>
<env name="BROADCAST_CONNECTION" value="null"/>
<env name="CACHE_STORE" value="array"/>
<env name="DB_DATABASE" value="testing"/>
<env name="MAIL_MAILER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="PULSE_ENABLED" value="false"/>
<env name="TELESCOPE_ENABLED" value="false"/>
<env name="NIGHTWATCH_ENABLED" value="false"/>
```

**Note:** BCRYPT_ROUNDS reduced to 4 for faster test execution

## Pest Helper Functions

**Available Global Functions (configured in tests/Pest.php):**
- `test('name', function () { ... })` - Define a test
- `expect($value)` - Create expectation for assertions
- `fake()` - Get Faker instance
- `now()` - Current datetime
- `app_path()`, `database_path()` - Path helpers
- `$this->get()`, `$this->post()` - HTTP request helpers (in Feature tests)

**Custom Extensions:**
```php
// From tests/Pest.php
expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
```

## Test Names and Organization

**Naming Convention:**
- Test names are descriptive strings in Spanish/English describing behavior
- Examples:
  - `'the application returns a successful response'`
  - `'se puede crear una fraccion'` (can create a tariff classification)
  - `'se puede crear una fraccion y se muestra su unidad medida'` (creates and shows unit)

## Running All Tests

**Full Test Suite:**
```bash
composer test
```

This runs in order:
1. Config clear
2. Type checking (`phpstan analyse`)
3. Lint check (`pint --test`)
4. Rector analysis (`rector --dry-run`)
5. PHPUnit tests (`php artisan test`)

---

*Testing analysis: 2026-02-27*
