# Codebase Concerns

**Analysis Date:** 2026-02-27

## Tech Debt

**Incomplete Test Suite:**
- Issue: Multiple test files exist but are largely empty with no actual test implementations
- Files: `tests/Feature/Models/CustomUnitTest.php`, `tests/Unit/ExampleTest.php` contain only placeholder code
- Impact: No actual validation of business logic for models (Country, Currency, CustomUnit, Incoterm, State, TariffClassification)
- Fix approach: Implement comprehensive unit tests for all models, especially those with relationships; add feature tests for Filament resources when they are created

**Empty Service Providers:**
- Issue: `AppServiceProvider` contains only empty register and boot methods
- Files: `app/Providers/AppServiceProvider.php`
- Impact: No service registrations, configurations, or bootstrapping logic in place for the application
- Fix approach: As features are added (e.g., API endpoints, custom services), register them here following Laravel conventions

**Minimal Bootstrap Configuration:**
- Issue: `bootstrap/app.php` has empty middleware and exception handler closures
- Files: `bootstrap/app.php`
- Impact: No middleware registration, exception handling, or custom configurations defined; using all defaults
- Fix approach: Add middleware declarations as needed and implement proper exception handling for API responses and error pages

## Missing Critical Features

**No API Resources or Versioning:**
- Problem: No API endpoints exist; only a basic welcome route is defined
- Blocks: Cannot expose invoice, customer, or domain model data via API; no support for external integrations
- Files: `routes/web.php` contains only a welcome route
- Impact: Application is UI-only (Filament admin); cannot serve as a backend for mobile or external systems

**No Filament Resources:**
- Problem: No Filament resources have been created for the domain models despite having a Filament AdminPanelProvider configured
- Files: `app/Providers/Filament/AdminPanelProvider.php` is configured but `app/Filament/Resources/` directory is empty
- Impact: Models (Country, Currency, CustomUnit, Incoterm, State, TariffClassification) cannot be managed via Filament admin panel
- Priority: High - These are foundational CRUD operations needed for the invoicing domain

**No Business Logic or Service Classes:**
- Problem: Only database models exist; no service classes, repositories, or business logic layers
- Files: `app/` directory only contains Models and Providers
- Impact: Business operations (invoice creation, calculations, validations) will need to be implemented; risk of logic being scattered across controllers
- Fix approach: Create service classes in `app/Services/` (e.g., InvoiceService, PaymentService) and repositories in `app/Repositories/` once features are defined

## Database Schema Concerns

**Missing Foreign Key Constraints on Delete:**
- Issue: TariffClassification has a foreign key to CustomUnit with only `onUpdate('cascade')`, missing `onDelete` clause
- Files: `database/migrations/2026_02_27_083029_create_tariff_classifications_table.php`
- Impact: Deleting a CustomUnit will fail or leave orphaned TariffClassification records depending on database behavior
- Fix approach: Add `->onDelete('cascade')` or `->onDelete('restrict')` to the foreign key definition based on business requirements

**Missing Database Indexes:**
- Issue: Most lookup fields lack indexes (e.g., Country name, Currency code, State name)
- Files: `database/migrations/2026_02_27_073049_create_currencies_table.php`, `database/migrations/2026_02_27_073552_create_countries_table.php`, `database/migrations/2026_02_27_083423_create_states_table.php`
- Impact: Queries on these fields will become slower as data grows; potential N+1 query problems with eager loading
- Fix approach: Add indexes on commonly queried fields (code, name) in each lookup table migration

**No Soft Deletes:**
- Issue: None of the domain models use soft deletes for audit/recovery purposes
- Files: All model classes lack `SoftDeletes` trait
- Impact: Deleting records is permanent; cannot maintain audit history or recover accidentally deleted data
- Fix approach: Consider adding soft deletes to key models (Country, Currency, CustomUnit, Incoterm, State, TariffClassification) if business requires historical tracking

**Missing Model Relationships Reverse Side:**
- Issue: CustomUnit has a hasMany relationship to TariffClassification, but reverse relationships are incomplete
- Files: `app/Models/CustomUnit.php`, `app/Models/TariffClassification.php`, and other models
- Impact: Cannot easily navigate relationships bidirectionally; increases coupling and makes queries more complex
- Fix approach: As domain expands (invoices, customers, line items), ensure all relationships are defined on both sides with proper return types

## Model Architecture Concerns

**Generic Model Names:**
- Issue: Models like Country, State, Incoterm are very generic and lack domain context
- Files: `app/Models/Country.php`, `app/Models/State.php`, `app/Models/Incoterm.php`
- Impact: As the application grows, these generic names may conflict with other concepts (e.g., Order State vs. Geographic State)
- Fix approach: Consider using more specific names in future (e.g., ShippingCountry, TaxState) or use namespaces if context becomes ambiguous

**Minimal Model Documentation:**
- Issue: Models use only type hints in PHPDoc, no comments explaining business purpose or constraints
- Files: All model classes in `app/Models/`
- Impact: New developers cannot understand business intent without reading migrations or code
- Fix approach: Add PHPDoc comments to models explaining their role (e.g., "Represents a country for customs/tax purposes")

## Test Coverage Gaps

**No Model Tests:**
- What's not tested: Model creation, factories, relationships (CustomUnit -> TariffClassification), casts
- Files: `tests/Feature/Models/CustomUnitTest.php` (empty), `tests/Feature/Models/TariffClassificationTest.php` (empty)
- Risk: Business-critical relationships may silently break during refactoring
- Priority: High - Test model relationships before adding Filament resources

**No Feature Tests for Future APIs:**
- What's not tested: Once API endpoints are created, there will be no test structure in place
- Files: `tests/Feature/` directory
- Risk: API regressions will not be caught; integration issues with models will go undetected
- Priority: Medium - Create test structure when APIs are added

**No Authentication/Authorization Tests:**
- What's not tested: Filament authentication, role-based access (when added)
- Files: `tests/Feature/` directory
- Risk: Security vulnerabilities in admin access control
- Priority: High - Add before allowing multiple users in production

## Dependencies at Risk

**No Clear Version Pinning:**
- Risk: Filament v5 is very new; potential breaking changes in minor updates
- Impact: `composer update` could introduce unexpected changes
- Migration plan: Use `composer update --dry-run` before updating; pin major versions in composer.json and test thoroughly before production

**Rector Auto-Upgrade:**
- Risk: Rector is configured to auto-modernize code but may not understand domain-specific patterns
- Files: `composer.json` includes `rector/rector` in dev-requires; `pint.json` includes rector rules
- Impact: Automatic refactoring could change business logic unintentionally
- Recommendation: Use `--dry-run` by default; manually review and test all rector changes before committing

## Scaling Limits

**SQLite Default Database:**
- Current capacity: SQLite is suitable for development and small deployments only
- Limit: Breaks down at high concurrent writes (typical for invoice/transaction systems)
- Files: `config/database.php` defaults to sqlite
- Scaling path: Ensure migration to MySQL/PostgreSQL is tested before production; document this transition

**No Caching Strategy:**
- Current: No Redis/memcached configuration in use (only defaults)
- Limit: Lookup tables (Country, Currency, State) will hit database on every Filament page load
- Scaling path: Implement caching for lookup tables in `AppServiceProvider::boot()` once queries are profiled

**No Queue Configuration:**
- Current: Queue is configured but no jobs are being used
- Limit: Any long-running operations (invoice generation, email notifications) will block HTTP requests
- Scaling path: Move report generation, PDF creation, and notification sending to queues as feature set grows

## Security Considerations

**Authentication Not Configured:**
- Risk: Filament admin panel requires authentication but no custom guards or policies are defined
- Files: `app/Providers/Filament/AdminPanelProvider.php` delegates to Filament's defaults
- Current mitigation: Filament provides basic auth; no custom business logic required yet
- Recommendations: Implement custom Gate/Policy classes before allowing role-based access; add audit logging for admin actions

**No Input Validation Strategy:**
- Risk: Form requests do not exist yet; validation will be ad-hoc when controllers are created
- Files: No `app/Http/Requests/` directory exists
- Mitigation: Create and enforce Form Request pattern for all API endpoints
- Recommendations: Use Filament's built-in validation for admin forms; create Form Requests for any custom APIs

**No Rate Limiting:**
- Risk: Once APIs are exposed, endpoints have no rate limiting
- Files: `bootstrap/app.php` contains no rate limiting configuration
- Impact: Vulnerable to brute force and DoS attacks
- Recommendations: Configure throttle middleware for all API routes; use different limits for authenticated vs. public endpoints

**Hardcoded Database Connection Type:**
- Risk: `.env` file stores database connection details; no encryption or key rotation strategy
- Files: `.env` file present (not readable in this analysis per guidelines)
- Current mitigation: Development only; SQLite is used by default
- Recommendations: Before production, ensure database credentials are managed via environment variables with secrets management (AWS Secrets Manager, HashiCorp Vault, etc.)

## Performance Bottlenecks

**No Query Optimization:**
- Problem: Models lack eager loading hints; N+1 queries likely when displaying related data
- Files: All models in `app/Models/`
- Cause: Generic relationships without specific load strategies
- Improvement path: Profile Filament page loads with Laravel Debugbar; add `load()` or `with()` to relationship-heavy queries

**No Indexing on Foreign Keys:**
- Problem: Foreign key columns lack indexes (e.g., `custom_unit_code` in tariff_classifications)
- Files: `database/migrations/2026_02_27_083029_create_tariff_classifications_table.php`
- Cause: Migrations only define constraints, not indexes
- Improvement path: Add explicit indexes on foreign key columns; profile join performance

## Fragile Areas

**Models with String Primary Keys:**
- Files: `app/Models/CustomUnit.php`, `app/Models/TariffClassification.php`
- Why fragile: String primary keys (e.g., 'code') are harder to track, update, or delete; require special handling in routes
- Safe modification: Always use route model binding with explicit key specification; test polymorphic relationships carefully if added later
- Test coverage: Test factory generation of unique code values; test relationship loads with string keys

**Filament Panel Configuration:**
- Files: `app/Providers/Filament/AdminPanelProvider.php`
- Why fragile: Discovered resources are auto-loaded from directory; typos in namespace or file structure will silently fail
- Safe modification: After creating resources, verify them with `php artisan filament:show-resources`; test admin panel loads in browser
- Test coverage: None exists; manual testing required before each deployment

---

*Concerns audit: 2026-02-27*
