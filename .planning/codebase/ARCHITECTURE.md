# Architecture

**Analysis Date:** 2026-02-27

## Pattern Overview

**Overall:** Layered MVC architecture with Filament admin panel as primary UI.

**Key Characteristics:**
- Follows Laravel 12 streamlined application structure
- Bootstrap-driven configuration in `bootstrap/app.php` and `bootstrap/providers.php`
- Filament v5 admin panel for data management
- Eloquent ORM for data access layer
- Strict mode enabled via Laravel Essentials for safety
- Strong type declarations throughout (PHP 8.5+)

## Layers

**Presentation Layer (Filament):**
- Purpose: Admin panel UI for managing application data
- Location: `app/Providers/Filament/AdminPanelProvider.php` configures resources, pages, and widgets
- Contains: Resource configurations, custom pages, widgets (future)
- Depends on: Models, authentication middleware
- Used by: Authenticated users accessing admin panel at `/admin`

**HTTP Layer:**
- Purpose: Handle web requests and routing
- Location: `routes/web.php` contains route definitions
- Contains: Route handlers, middleware stacks
- Depends on: Controllers, models
- Used by: All HTTP requests

**Model/Domain Layer:**
- Purpose: Represent domain entities and business logic
- Location: `app/Models/`
- Contains: Eloquent models with relationships, factories, seeders
- Depends on: Laravel database layer
- Used by: Controllers, Filament resources, business operations

**Data Access Layer:**
- Purpose: Persist and retrieve data via Eloquent ORM
- Location: `database/migrations/`, `database/factories/`, `database/seeders/`
- Contains: Schema definitions, test data generation, seeding logic
- Depends on: Database engine
- Used by: Models, queries

**Service Provider Layer:**
- Purpose: Application bootstrap and configuration
- Location: `bootstrap/providers.php`, `app/Providers/`
- Contains: `AppServiceProvider`, `AdminPanelProvider`, service registration
- Depends on: Laravel foundation
- Used by: Application kernel during startup

**Configuration Layer:**
- Purpose: Centralize configuration and environment variables
- Location: `config/`, `bootstrap/app.php`
- Contains: Route registration, middleware registration, exception handling
- Depends on: Environment variables
- Used by: All layers during bootstrap

## Data Flow

**Request Handling (Web):**

1. Public HTTP request arrives at `public/index.php`
2. Bootstrap loads application via `bootstrap/app.php`
3. Routes defined in `routes/web.php` match request
4. Request dispatched to route handler (currently only welcome view)
5. Response returned to client

**Admin Panel Access:**

1. Authenticated user navigates to `/admin`
2. Filament middleware (`AdminPanelProvider`) authenticates user
3. Filament discovers resources in `app/Filament/Resources/` (future location)
4. Resource classes handle CRUD operations via models
5. Models query database through Eloquent
6. Response rendered in Filament UI

**Data Persistence:**

1. Model methods or service layer code creates/updates/deletes entities
2. Eloquent translates model changes to database queries
3. Migrations in `database/migrations/` define schema structure
4. Factories in `database/factories/` generate test data
5. Seeders in `database/seeders/` populate reference data

**State Management:**

- Models carry domain state via Eloquent attributes
- Relationships eagerly loaded by default (`AutomaticallyEagerLoadRelationships` enabled in config/essentials.php)
- Database is single source of truth for persistent state
- No client-side state management currently configured (Filament handles frontend state)

## Key Abstractions

**Eloquent Models:**
- Purpose: Represent database tables and define relationships
- Examples: `app/Models/User.php`, `app/Models/Currency.php`, `app/Models/Country.php`, `app/Models/CustomUnit.php`, `app/Models/TariffClassification.php`, `app/Models/State.php`, `app/Models/Incoterm.php`
- Pattern: Final classes with strict type hints, factory support via `HasFactory` trait, relationship methods with return types

**Model Relationships:**
- `CustomUnit` → hasMany → `TariffClassification` (custom_unit_code foreign key)
- `TariffClassification` → belongsTo → `CustomUnit`
- All other models currently standalone (no defined relationships)

**Filament Admin Panel:**
- Purpose: Auto-generated CRUD interface for models
- Pattern: `PanelProvider` discovers resources from `app/Filament/Resources/` directory
- Features: Authentication required, dashboard at `/admin`, discovers pages and widgets

**Factories:**
- Purpose: Generate test data for database seeding and testing
- Examples: `database/factories/UserFactory.php`, `database/factories/CurrencyFactory.php`
- Pattern: Define `definition()` method returning attribute array, optional state methods

**Seeders:**
- Purpose: Populate reference data into database
- Examples: `database/seeders/DatabaseSeeder.php`, `database/seeders/CurrencySeeder.php`
- Pattern: Implement `run()` method, called during database seeding

## Entry Points

**Web Application:**
- Location: `public/index.php`
- Triggers: HTTP requests to application domain
- Responsibilities: Bootstrap Laravel application, capture request, handle via routing, return response

**Artisan CLI:**
- Location: `bootstrap/app.php` with console routing in `routes/console.php`
- Triggers: `php artisan` commands from CLI
- Responsibilities: Run migrations, seeders, tinker, custom commands

**Admin Panel:**
- Location: Filament `AdminPanelProvider` at `app/Providers/Filament/AdminPanelProvider.php`
- Triggers: User navigation to `/admin` route
- Responsibilities: Authentication, resource discovery, CRUD UI rendering

## Error Handling

**Strategy:** Exception-based error handling with framework defaults.

**Patterns:**
- Validation errors: Form Request classes validate input and throw `ValidationException`
- Authorization: Policies and gates determine access (future - not yet implemented)
- Database errors: Eloquent throws relevant exceptions (model not found, constraint violations)
- Framework errors: Laravel exception handler in `bootstrap/app.php` formats responses
- Production: Framework handles graceful error pages via `Illuminate\Foundation\Configuration\Exceptions`

## Cross-Cutting Concerns

**Logging:**
- Configured via `config/logging.php`
- Default channel: stack (combines multiple outputs)
- Available levels: debug, info, notice, warning, error, critical, alert, emergency

**Validation:**
- Form Request classes (future) for controller validation
- Eloquent fillable attributes prevent mass assignment vulnerabilities
- Database schema enforces nullable/required constraints

**Authentication:**
- Laravel's built-in authentication via `Illuminate\Foundation\Auth\User`
- User model at `app/Models/User.php`
- Filament middleware ensures admin panel access requires authentication
- Sessions managed via `config/session.php`

**Database Transactions:**
- Enabled by default for seeding via `WithoutModelEvents` trait
- Available via `DB::transaction()` for multi-step operations
- Jobs queue configured for async work via `database/migrations/*_create_jobs_table.php`

---

*Architecture analysis: 2026-02-27*
