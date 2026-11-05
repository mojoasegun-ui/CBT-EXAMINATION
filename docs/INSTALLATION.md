# Installation

## Requirements

- PHP 8.1 or later; enable `PDO`, `pdo_mysql`, `json`, and `session`
- MySQL 8+ or compatible MariaDB
- Apache 2.4 with `mod_rewrite` and `AllowOverride` enabled for `.htaccess`
- Composer 2 for development and optional optimized autoload generation
- HTTPS for any environment that carries real identities or credentials

## Local Setup

1. Copy `.env.example` to `.env`; set a local database name, user, and password. Do not commit `.env`.
2. Run `composer install` and `composer dump-autoload` from the project root.
3. Make `storage/logs`, `storage/cache`, and `storage/sessions` writable by the local PHP process.
4. Start the local server from the project root with `php -S 127.0.0.1:8000 -t public public/router.php`.
5. Visit `/`, `/health`, and `/api/health`. The health endpoints report only a generic status and do not test database connectivity.
6. Run `php cbt migrate`, then `composer test` (integration tests use a separate `*_test` database).

## Environment Configuration

Use cPanel's environment-variable facility where available, or a protected `.env` file outside version control. The supplied root `.htaccess` denies dotfiles and private application directories. Configure at least `APP_ENV`, `APP_DEBUG=false`, `APP_BASE_URL`, `APP_TIMEZONE`, `DB_*`, `DB_PREFIX=cbt_`, `SESSION_*`, and `INTEGRATION_MODE`. `APP_KEY` is reserved for later encryption features; generate a cryptographically random secret before those features are implemented. It is not currently consumed.

An empty `APP_BASE_URL` permits request-path detection for route processing. Set the full public URL, including its subdirectory, for generated page asset links, for example `https://example.edu/examcore-cbt`.

## Database Setup

Create a database and least-privilege database user using the hosting control panel, then set the credentials through environment configuration. `Database` connects lazily; no database setup is performed when the app boots. All ExamCore tables use the configured prefix (default `cbt_`); host-school tables are never modified.

**Migrations are the source of truth** (`database/migrations/`, applied in filename order and recorded in `cbt_migrations`):

```bash
php cbt db:backup        # always back up first; MySQL DDL cannot be rolled back
php cbt migrate          # apply pending migrations
php cbt migrate:status   # see what has run
```

Hosts without shell access (InfinityFree, some cPanel plans): import `database/schema.sql` in phpMyAdmin instead. It is generated from the migrations and safe to import repeatedly. Then optionally import `database/seed.sql` for demo data.

## Operations: scheduler, queue and email

Three things must run in the background: auto-submitting exams whose time ran out (closed tab, dead battery), sending queued result emails, and housekeeping.

| Host | Setup |
| --- | --- |
| VPS / cPanel with cron (e.g. WhoGoHost) | Add a cron job every minute: `* * * * * php /home/USER/examcore/cbt schedule:run >/dev/null 2>&1`, then set `SCHEDULER_WEB_TRIGGER=false`. |
| No cron (e.g. InfinityFree) | Keep `SCHEDULER_WEB_TRIGGER=true`. Tasks run after normal page responses, at most once a minute, with small time budgets. The session lock is released first, so students are never slowed down. |

Other commands: `php cbt queue:status`, `php cbt queue:work`, `php cbt attempts:expire`, `php cbt schedule:run --force`.

Email is sent from the queue, never during a student's request. Configure SMTP (`MAIL_*` in `.env.example`; Brevo, Mailgun, Zoho or the host's SMTP all work) and set `MAIL_ENABLED=true`. Failed sends retry after 1, 4 and 9 minutes, then are marked failed and logged.

## Troubleshooting

Every response carries an `X-Request-Id` header, and error pages show it as a "Reference". Search `storage/logs/application.log` for that id to find the full error: exception, message, file, line and stack frames. Secrets are redacted and function arguments are never logged.

## cPanel Deployment

1. Upload the project into a dedicated directory. Prefer configuring the site's document root to the project's `public/` directory. If this is not available, retain the root `.htaccess` rewrite and protection rules and test them on the host's Apache version.
2. Select PHP 8.1+ and enable `pdo_mysql`, `session`, and `json` in MultiPHP Manager/INI Editor or ask the host to enable them.
3. Set production environment variables and `APP_DEBUG=false`; configure the canonical HTTPS URL as `APP_BASE_URL`.
4. Create the database/user in cPanel and grant only the needed privileges. Run `php cbt migrate` (or import `database/schema.sql` in phpMyAdmin where there is no shell).
5. Ensure `storage/logs`, `storage/cache`, and `storage/sessions` are writable by PHP, typically with owner-only or group-restricted permissions appropriate to the host. Do not make storage world-writable.
6. Force HTTPS at the hosting layer. Confirm the session cookie has `Secure`, `HttpOnly`, and the configured `SameSite` value.
7. Check that requests for `.env`, `/app/`, `/config/`, `/storage/`, `/vendor/`, and non-front-controller PHP files are denied. Some hosts disable `.htaccess`; do not deploy until equivalent protections are configured.
8. Test the homepage, health endpoints, 404/405 handling, storage writes, database connection from an authorized maintenance path, and logs. Health status does not establish database readiness.

## Installation Process Policy

There is no public installer. A future installer should be an authorized CLI or authenticated, one-time operation with explicit confirmation, preflight checks, backups, and auditable migration output. Never expose an unrestricted web endpoint that creates users, changes database configuration, or runs arbitrary SQL.