# Codebase Structure

**Analysis Date:** 2026-02-27

## Directory Layout

```
FacturacionLoop/
├── app/                     # Application code
│   ├── Http/               # HTTP layer
│   │   └── Controllers/    # Route handlers (minimal usage currently)
│   ├── Models/             # Eloquent models
│   └── Providers/          # Service providers
│       └── Filament/       # Filament admin panel provider
├── bootstrap/              # Application bootstrap
│   ├── app.php            # Main application configuration
│   ├── cache/             # Generated cache files
│   └── providers.php      # Service provider registry
├── config/                 # Configuration files
├── database/               # Database layer
│   ├── factories/         # Model factories for testing/seeding
│   ├── migrations/        # Schema definitions
│   └── seeders/           # Database seeders
├── public/                 # Web server public directory
│   ├── css/               # Compiled CSS assets
│   ├── js/                # Compiled JS assets
│   ├── fonts/             # Web fonts
│   └── index.php          # Application entry point
├── resources/              # Frontend assets
│   ├── css/               # Source CSS (Tailwind)
│   ├── js/                # Source JavaScript
│   └── views/             # Blade templates
├── routes/                 # Route definitions
├── storage/                # Runtime storage
│   ├── app/               # Application storage
│   ├── framework/         # Framework cache/views
│   └── logs/              # Application logs
├── tests/                  # Test suite
│   ├── Feature/           # Feature tests
│   ├── Unit/              # Unit tests
│   └── Pest.php           # Pest configuration
├── vendor/                 # Composer dependencies
├── .planning/              # GSD planning documents
│   └── codebase/          # Generated codebase analysis
├── .claude/                # Claude code execution context
├── composer.json           # PHP dependencies
├── package.json            # JavaScript dependencies
├── vite.config.js         # Vite build configuration
└── bootstrap/app.php       # Main bootstrap file
```

## Directory Purposes

**`app/`:**
- Purpose: Core application code and business logic
- Contains: Models, controllers, providers, Filament resources (future)
- Key files: Service providers, model classes

**`app/Http/Controllers/`:**
- Purpose: HTTP request handlers and controller logic
- Contains: Base Controller class, future feature controllers
- Key files: `app/Http/Controllers/Controller.php` (base class)

**`app/Models/`:**
- Purpose: Eloquent model definitions
- Contains: Database entity representations with relationships
- Key files: `User.php`, `Currency.php`, `Country.php`, `CustomUnit.php`, `TariffClassification.php`, `State.php`, `Incoterm.php`

**`app/Providers/`:**
- Purpose: Service provider registration and bootstrapping
- Contains: `AppServiceProvider` (general), `AdminPanelProvider` (Filament config)
- Key files: `app/Providers/AppServiceProvider.php`, `app/Providers/Filament/AdminPanelProvider.php`

**`bootstrap/`:**
- Purpose: Application bootstrap and configuration
- Contains: Main `app.php` (routing, middleware, exception handling), provider registry, cache
- Key files: `bootstrap/app.php`, `bootstrap/providers.php`

**`config/`:**
- Purpose: Centralized configuration by domain
- Contains: Database, mail, cache, session, logging, authentication configs
- Key files: `config/essentials.php` (Laravel Essentials), `config/database.php`

**`database/`:**
- Purpose: Database schema and seeding
- Contains: Migrations (schema versions), factories (test data), seeders (reference data)
- Key files: Migrations for users, currencies, countries, states, custom units, tariff classifications

**`database/migrations/`:**
- Purpose: Version-controlled schema changes
- Contains: Timestamped migration files in ascending order
- Key files: `0001_01_01_000000_create_users_table.php`, `2026_02_27_*` domain entity tables

**`database/factories/`:**
- Purpose: Generate fake data for testing and seeding
- Contains: Factory classes for each model
- Key files: `UserFactory.php`, `CurrencyFactory.php`, `CountryFactory.php`, etc.

**`database/seeders/`:**
- Purpose: Populate reference data into database
- Contains: Seeder classes triggered during `php artisan db:seed`
- Key files: `DatabaseSeeder.php` (main entry), `CurrencySeeder.php`, `CountrySeeder.php`, etc.

**`public/`:**
- Purpose: Web server document root
- Contains: Compiled assets, favicon, robots.txt, .htaccess
- Key files: `public/index.php` (Laravel entry point)

**`resources/`:**
- Purpose: Frontend source assets
- Contains: CSS source, JavaScript source, Blade templates
- Key files: `resources/views/welcome.blade.php` (landing page)

**`resources/css/`:**
- Purpose: Source stylesheets (Tailwind CSS)
- Contains: `app.css` loaded and compiled by Vite
- Key files: `resources/css/app.css`

**`resources/js/`:**
- Purpose: Source JavaScript modules
- Contains: `app.js` (entry point), `bootstrap.js` (utilities)
- Key files: `resources/js/app.js`, `resources/js/bootstrap.js`

**`resources/views/`:**
- Purpose: Blade template files for HTML generation
- Contains: Layout templates, page components
- Key files: `resources/views/welcome.blade.php` (main landing page)

**`routes/`:**
- Purpose: Route definitions for web and console
- Contains: HTTP route definitions, CLI command registration
- Key files: `routes/web.php` (HTTP routes), `routes/console.php` (CLI commands)

**`storage/`:**
- Purpose: Runtime generated files and logs
- Contains: Application cache, framework cache, views, logs
- Key files: `.gitignored` except logs directory

**`tests/`:**
- Purpose: Test suite (Pest PHP)
- Contains: Feature tests, unit tests, shared test configuration
- Key files: `tests/Pest.php` (configuration), `tests/TestCase.php` (base class)

**`tests/Feature/`:**
- Purpose: Feature and integration tests
- Contains: Tests for HTTP endpoints, models, business logic
- Key files: `Feature/ExampleTest.php`, `Feature/Models/CustomUnitTest.php`, `Feature/Models/TariffClassificationTest.php`

**`tests/Unit/`:**
- Purpose: Unit tests for isolated functionality
- Contains: Single responsibility tests
- Key files: `Unit/ExampleTest.php`

## Key File Locations

**Entry Points:**
- `public/index.php`: Web application entry point - loads bootstrap and handles HTTP request
- `bootstrap/app.php`: Application configuration and bootstrap - defines routing, middleware, exception handling
- `routes/web.php`: HTTP route definitions - maps URLs to handlers

**Configuration:**
- `config/app.php`: Application name, timezone, provider list (legacy)
- `config/database.php`: Database connection configuration
- `config/essentials.php`: Laravel Essentials feature flags (strict mode, eager loading, etc.)
- `bootstrap/providers.php`: Service provider registry for Laravel 12

**Core Logic:**
- `app/Models/*.php`: Eloquent models representing domain entities
- `app/Providers/Filament/AdminPanelProvider.php`: Filament admin panel configuration

**Testing:**
- `tests/Pest.php`: Pest configuration and shared test helpers
- `tests/TestCase.php`: Base test class with utilities
- `tests/Feature/*.php`: Feature tests using Pest syntax

**Database:**
- `database/migrations/`: All schema changes - timestamped files executed in order
- `database/factories/`: Test data generation using Faker
- `database/seeders/DatabaseSeeder.php`: Main seeder entry point

**Frontend:**
- `resources/views/welcome.blade.php`: Primary landing page template
- `resources/css/app.css`: Source CSS with Tailwind directives
- `resources/js/app.js`: JavaScript entry point
- `vite.config.js`: Vite build configuration (CSS, JS compilation)

## Naming Conventions

**Files:**
- Models: `PascalCase` singular noun (e.g., `User.php`, `Currency.php`, `CustomUnit.php`)
- Controllers: `PascalCase` with `Controller` suffix (e.g., `UserController.php`)
- Migrations: Timestamp prefix + snake_case description (e.g., `2026_02_27_073049_create_currencies_table.php`)
- Factories: `PascalCase` model name + `Factory` suffix (e.g., `UserFactory.php`)
- Seeders: `PascalCase` model name + `Seeder` suffix (e.g., `CurrencySeeder.php`)
- Tests: `PascalCase` + `Test` suffix (e.g., `CustomUnitTest.php`)

**Directories:**
- Model folders: singular lowercase (e.g., `Models/`, not `Models/User/`)
- Feature folders: Feature type (e.g., `Feature/Models/`)
- Namespaces: Mirror directory structure in PascalCase (e.g., `App\Models`, `Database\Factories`)

## Where to Add New Code

**New Feature:**
- Primary code: `app/Models/FeatureName.php` for model, `app/Http/Controllers/FeatureController.php` for logic
- Tests: `tests/Feature/FeatureNameTest.php` (or `tests/Feature/Models/FeatureNameTest.php` for model tests)
- Database: `database/migrations/YYYY_MM_DD_HHMMSS_create_feature_names_table.php` for schema
- Seeding: `database/seeders/FeatureNameSeeder.php` and register in `DatabaseSeeder.php`

**New Model:**
- Model: `app/Models/ModelName.php` as final class with explicit type hints
- Factory: `database/factories/ModelNameFactory.php` with `definition()` method
- Migration: `database/migrations/` with `php artisan make:model ModelName --migration`
- Seeder: `database/seeders/ModelNameSeeder.php` if reference data needed

**New Filament Resource:**
- Location: `app/Filament/Resources/ModelNameResource.php` (auto-discovered by `AdminPanelProvider`)
- Command: `php artisan make:filament-resource ModelName --resource`
- Auto-registered via `discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')` in `AdminPanelProvider`

**Utilities/Helpers:**
- Shared helpers: `app/Support/ClassName.php` or similar domain-specific namespace
- Reusable traits: `app/Concerns/TraitName.php`
- Value objects: `app/Values/ValueName.php`

**Controllers:**
- Location: `app/Http/Controllers/` with resource controllers for RESTful endpoints
- Pattern: Extend base `Controller` class, use form requests for validation
- Registration: Automatic via route definitions in `routes/web.php`

## Special Directories

**`.planning/codebase/`:**
- Purpose: Generated codebase analysis documents
- Generated: Yes (created by GSD mapping)
- Committed: Yes (tracked in git)
- Contents: ARCHITECTURE.md, STRUCTURE.md, CONVENTIONS.md, TESTING.md, CONCERNS.md

**`storage/logs/`:**
- Purpose: Application error and activity logs
- Generated: Yes (at runtime)
- Committed: No (.gitignored)
- Format: Laravel log files (laravel.log, etc.)

**`bootstrap/cache/`:**
- Purpose: Framework caches (routes, config, services)
- Generated: Yes (by commands like `php artisan config:cache`)
- Committed: No (.gitignored)
- Contents: Pre-computed configurations for performance

**`.env`:**
- Purpose: Environment variables for this installation
- Generated: No (copy from `.env.example`)
- Committed: No (.gitignored)
- Contains: Database credentials, API keys, app settings

**`vendor/`:**
- Purpose: Composer dependencies
- Generated: Yes (`composer install`)
- Committed: No (.gitignored)
- Size: ~1GB typical

**`node_modules/`:**
- Purpose: NPM JavaScript dependencies
- Generated: Yes (`npm install`)
- Committed: No (.gitignored)
- Contains: Vite, Tailwind CSS, build tools

---

*Structure analysis: 2026-02-27*
