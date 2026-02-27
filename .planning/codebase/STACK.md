# Technology Stack

**Analysis Date:** 2026-02-27

## Languages

**Primary:**
- PHP 8.5+ - Backend application logic, API endpoints, models, controllers

**Secondary:**
- JavaScript/TypeScript (ES Module) - Frontend asset bundling with Vite
- SQL - Database queries via Eloquent ORM and raw queries

## Runtime

**Environment:**
- PHP 8.5.3 (Local), PHP 8.4.1 (Host system)
- Docker container support via Laravel Sail (PHP 8.5)

**Package Managers:**
- Composer - PHP dependency management
  - Lockfile: `composer.lock` (present, deterministic builds)
- npm - Node.js dependency management
  - Lockfile: `package-lock.json` (implied, not explicitly shown)

## Frameworks

**Core:**
- laravel/framework v12.53+ - Web application framework
- filament/filament v5.2.3+ - Admin panel and UI components

**Testing:**
- pestphp/pest v4.4.1+ - PHP testing framework
- pestphp/pest-plugin-laravel v4.1+ - Laravel integration for Pest
- phpunit/phpunit v12+ - Unit/feature test infrastructure (via Pest)
- mockery/mockery v1.6.12+ - Mocking library for tests

**Build/Dev:**
- vite v7.0.7+ - Frontend asset bundler
- laravel-vite-plugin v2.0.0+ - Laravel + Vite integration
- tailwindcss v4.0.0+ - Utility-first CSS framework
- @tailwindcss/vite v4.0.0+ - Tailwind CSS Vite plugin

**Development Tools:**
- laravel/tinker v2.11.1+ - Interactive REPL for debugging
- laravel/pint v1.27.1+ - PHP code formatter/style fixer
- laravel/pail v1.2.6+ - Real-time log viewer
- laravel/sail v1.53+ - Docker development environment
- laravel/boost v2.2.1+ - Performance optimization utilities
- larastan/larastan v3.9+ - PHPStan integration for Laravel (type analysis)
- rector/rector v2.3+ - Automated code refactoring
- driftingly/rector-laravel v2.1+ - Laravel-specific Rector rules

**Utilities:**
- nunomaduro/essentials v1.2+ - Laravel development enhancements
- nunomaduro/collision v8.9.1+ - Beautiful error display
- fakerphp/faker v1.24.1+ - Fake data generation for testing/factories

## Key Dependencies

**Critical:**
- laravel/framework v12+ - Core framework providing routing, ORM, middleware, validation, etc.
- filament/filament v5+ - Admin panel builder with form, table, and page components
- larastan/larastan v3.9+ - Static type analysis to catch errors early

**Infrastructure:**
- Laravel Session Management (database-backed)
- Laravel Queue (database-backed by default, with Redis/SQS options)
- Laravel Cache (database-backed, with Redis/Memcached options)
- PostgreSQL Support (via native PDO driver)

**HTTP & Requests:**
- axios v1.11.0+ - HTTP client for frontend AJAX requests
- Laravel HTTP client (built-in) - Backend HTTP requests

## Configuration

**Environment:**
- `.env` file (not in repository, created from `.env.example`)
- Environment variables control all service connections, app settings, debug mode
- Required env vars: `APP_KEY`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `MAIL_FROM_ADDRESS`

**Build:**
- `vite.config.js` - Vite bundler configuration (inferred from package.json)
- `tailwind.config.js` - Tailwind CSS configuration (inferred from package.json)
- `pint.json` - Code style configuration at `/home/aarongmx/Proyectos/FacturacionLoop/pint.json`
- `phpstan.neon` - Static analysis configuration at `/home/aarongmx/Proyectos/FacturacionLoop/phpstan.neon`
- `phpunit.xml` - Test configuration at `/home/aarongmx/Proyectos/FacturacionLoop/phpunit.xml`

**Application Boot:**
- `bootstrap/app.php` - Entry point configuring routing, middleware, exception handling
- `bootstrap/providers.php` - Service provider registration

## Platform Requirements

**Development:**
- Docker (via Docker Compose for local environment)
- Laravel Sail orchestrates containers for: PHP 8.5 app, PostgreSQL 18, Redis, Typesense 27.1
- Xdebug available in Docker for debugging (`SAIL_XDEBUG_MODE` env var)
- Node.js (for npm, Vite, Tailwind CSS building)

**Production:**
- PHP 8.5+
- PostgreSQL 18+ (recommended, but MySQL/MariaDB/SQLServer supported)
- Redis (optional, for caching/sessions/queues)
- Web server (Apache/Nginx) with Laravel Sail image available

**Database:**
- Primary: PostgreSQL 18 (via `DB_CONNECTION=pgsql` in `.env.example`)
- Fallback support: MySQL 8.0+, MariaDB 10.3+, SQLite (dev-only), SQL Server
- ORM: Eloquent (Laravel's built-in ORM)

---

*Stack analysis: 2026-02-27*
