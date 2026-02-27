# External Integrations

**Analysis Date:** 2026-02-27

## APIs & External Services

**Email Services (configurable):**
- Postmark - Configured, optional email service
  - SDK/Client: Laravel mail driver
  - Auth: `POSTMARK_API_KEY` env var
- Resend - Configured, optional email service
  - SDK/Client: Laravel mail driver
  - Auth: `RESEND_API_KEY` env var
- AWS SES - Configured, optional email service
  - SDK/Client: Laravel mail driver
  - Auth: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`
- Slack Notifications - Configured, optional notification channel
  - SDK/Client: Laravel notification driver
  - Auth: `SLACK_BOT_USER_OAUTH_TOKEN` env var
  - Channel: `SLACK_BOT_USER_DEFAULT_CHANNEL` env var

**Search & Indexing:**
- Typesense v27.1 - Full-text search service
  - Running in Docker container (port 8108)
  - API Key: `TYPESENSE_API_KEY` env var (default: 'xyz')
  - CORS enabled: `TYPESENSE_ENABLE_CORS` env var
  - Data directory: `TYPESENSE_DATA_DIR` env var

## Data Storage

**Databases:**
- PostgreSQL 18 (primary)
  - Connection: `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_USERNAME=root`, `DB_PASSWORD` via env
  - Database: `facturacionloop`
  - Client: PDO (PHP Data Objects) via Laravel Eloquent ORM
  - Fallback: SQLite for development, supports MySQL/MariaDB/SQL Server

**File Storage:**
- Local filesystem - Default storage disk
  - Path: `storage/app/private` (local disk)
  - Path: `storage/app/public` (public disk, publicly accessible)
  - Served from: `/storage` web path
- AWS S3 - Optional cloud storage
  - Configuration: `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` env vars
  - Credentials: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`

**Caching:**
- Database cache (default) - Cache stored in `cache` table
  - Fallback drivers: Redis, Memcached, File, Array (for testing)
- Redis - Optional high-performance cache
  - Host: `REDIS_HOST=127.0.0.1`, Port: `6redis PORT=6379`
  - Client: PHPRedis
  - Databases: 0 (default), 1 (cache), configurable via `REDIS_DB`, `REDIS_CACHE_DB`

## Authentication & Identity

**Auth Provider:**
- Custom Laravel Authentication - No external auth provider detected
  - Implementation: Laravel's built-in authentication using database sessions
  - Session driver: Database (via `SESSION_DRIVER=database`)
  - Encryption: Bcrypt password hashing (`BCRYPT_ROUNDS=12`)
  - Session lifetime: 120 minutes (configurable)

## Monitoring & Observability

**Error Tracking:**
- None configured - Application reliance on Laravel error handling

**Logs:**
- Logging approach: Laravel's built-in logging stack
  - Log channel: `stack` (multiple handlers)
  - Log stack: `single` (write to single log file)
  - Log level: `debug` in local environment
  - Log deprecations: `null` (not logged separately)
  - Log handler: File-based logging by default

## CI/CD & Deployment

**Hosting:**
- Docker Compose (local/staging via Sail)
- Deployment-ready: Sail includes production-capable Docker image for PHP 8.5

**CI Pipeline:**
- None detected - No GitHub Actions/GitLab CI configuration found
- Test suite available via `php artisan test` command

## Environment Configuration

**Required env vars (critical):**
- `APP_KEY` - Application encryption key (generated via `php artisan key:generate`)
- `APP_NAME` - Application name (default: Laravel)
- `APP_ENV` - Environment (local/testing/production)
- `APP_DEBUG` - Debug mode (true for local, false for production)
- `APP_URL` - Application base URL
- `DB_CONNECTION` - Database driver (pgsql/sqlite/mysql/mariadb/sqlsrv)
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` - Database credentials
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` - Email sender identity
- `SESSION_DRIVER` - Session storage driver (database/file/redis)
- `QUEUE_CONNECTION` - Queue driver (database/redis/sync/sqs)
- `CACHE_STORE` - Cache driver (database/redis/file)

**Optional service vars:**
- `POSTMARK_API_KEY` - Postmark email service
- `RESEND_API_KEY` - Resend email service
- `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` - AWS (SES, S3, SQS, DynamoDB)
- `SLACK_BOT_USER_OAUTH_TOKEN`, `SLACK_BOT_USER_DEFAULT_CHANNEL` - Slack notifications
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`, `REDIS_CLIENT` - Redis connection
- `TYPESENSE_API_KEY`, `TYPESENSE_ENABLE_CORS` - Typesense search
- `MEMCACHED_HOST`, `MEMCACHED_PORT` - Memcached (optional cache backend)

**Secrets location:**
- `.env` file (git-ignored, loaded at runtime)
- Configuration files access `.env()` for secret values

## Webhooks & Callbacks

**Incoming:**
- Not detected - No webhook endpoints configured or documented

**Outgoing:**
- None currently configured
- Infrastructure available via: Mail notifications, Slack notifications, Queue jobs, Custom events

## Queue System

**Default Driver:** Database
- Jobs stored in `jobs` table
- Configuration: `QUEUE_CONNECTION=database`
- Retry policy: 90 seconds retry_after
- Failed jobs stored in `failed_jobs` table with UUID tracking

**Optional Drivers:**
- Redis - High-performance queue
- AWS SQS - Managed queue service (credentials via `AWS_*` vars)
- Sync - Synchronous execution (testing)
- Beanstalkd - Legacy queue service

## Broadcasting

**Default Driver:** Log
- WebSocket broadcasting: Not configured
- Broadcasting driver: `BROADCAST_CONNECTION=log`

## Session Management

**Driver:** Database (primary)
- Session table: `sessions`
- Lifetime: 120 minutes (via `SESSION_LIFETIME`)
- Encryption: Not enabled by default (`SESSION_ENCRYPT=false`)
- Path: `/` (domain-wide)

## Maintenance Mode

**Driver:** File (default)
- Alternative: `APP_MAINTENANCE_STORE=database` for distributed systems

---

*Integration audit: 2026-02-27*
