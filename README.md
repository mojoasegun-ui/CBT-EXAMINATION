# ExamCore CBT

ExamCore CBT is a reusable PHP computer-based testing platform. This repository contains **Phase 1: foundation and core infrastructure only**. It does not contain an examination engine, question bank, admin dashboard, student dashboard, results system, or authentication screens.

The foundation is designed to run independently or to accept an authenticated identity from a trusted PHP host website through a replaceable adapter. Host database schemas are not inspected or changed.

## Technology

- PHP 8.1+, PDO, MySQL/MariaDB
- Composer-compatible PSR-4 autoloading with a small fallback loader
- Apache `.htaccess` and a PHP built-in-server router for local development
- HTML, CSS, and JavaScript; no Node.js, Redis, Docker, worker, or runtime package installation required
- PHPUnit 10 as a development-only test dependency

## Architecture

`public/index.php` is a small front controller. Bootstrap loads the optional `.env`, configuration, dependency container, error handler, security middleware, and route tables. `app/Core` contains reusable HTTP, routing, database, session, authentication, validation, logging, and rendering components. Controllers coordinate presentation, models are the database access base, and services hold reusable application operations. Integrations implement a trusted identity contract. More details are in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Requirements

- PHP 8.1 or later with `PDO`, `pdo_mysql`, `json`, and `session` enabled
- MySQL 8+ or a compatible MariaDB server for database use
- Composer 2 for development dependencies and optimized autoloading; Composer is not required on production hosting if the project is deployed with its `vendor/` directory or uses the fallback loader
- Apache 2.4 with `mod_rewrite` and `.htaccess` overrides enabled for the provided rewrite/protection rules

## Local Development

1. Copy `.env.example` to `.env` and set local values. Keep `.env` out of source control.
2. Install development dependencies and build Composer autoload metadata:

   ```sh
   composer install
   composer dump-autoload
   ```

3. Ensure `storage/logs`, `storage/cache`, and `storage/sessions` are writable by the PHP process.
4. Start the PHP development server from the repository root:

   ```sh
   php -S 127.0.0.1:8000 -t public public/router.php
   ```

5. Open `http://127.0.0.1:8000/`. The public health URLs are `/health` and `/api/health`.
6. Run the tests with `composer test` or `vendor/bin/phpunit`. A dependency-free core smoke/lint check is also available as `php tests/smoke.php`.

The health routes do not open a database connection. Database credentials must be configured before a future feature requests `Database::connection()`; failure messages are generic. No database tables are created in Phase 1.

## Configuration

Environment variables take precedence over defaults. The small built-in `.env` reader is used only when a process environment variable is not already defined. `config/app.php`, `config/database.php`, `config/security.php`, `config/integration.php`, and `config/permissions.php` return the central configuration arrays. Never place production secrets in committed files. `APP_KEY` is a placeholder for later cryptographic features and is not currently used to encrypt application data.

Database table names must be created with the configured prefix, defaulting to `cbt_`. The base `Model` only validates and qualifies table identifiers; values must still be passed as prepared-statement parameters. See [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Routing

Routes are registered in `routes/web.php` and `routes/api.php`. Handlers receive a `Request` and a named-parameter array and must return a `Response`:

```php
$router->get('/students/{studentId}', static function (Request $request, array $parameters): Response {
    return Response::json(['id' => $parameters['studentId']]);
}, name: 'students.show');
```

Use `Router::group(['prefix' => '/api/v1', 'middleware' => [$middleware]], $register)` to group routes. `Router::url('students.show', ['studentId' => 42])` includes the configured application base path. See the architecture guide for the dispatch and middleware flow.

## Security Status

Implemented foundations include strict cookie-only sessions, HTTP-only/SameSite cookies, HTTPS-aware Secure cookies, session ID regeneration on login, idle timeout, CSRF token validation, output escaping helper, PDO exception/prepared-statement settings, role/permission checks, default-deny protected routes, security headers, file-based rate limiting, private storage denial, and generic production errors. These are building blocks, **not a production security certification**. See [docs/SECURITY.md](docs/SECURITY.md) for what still requires review and deployment testing.

There is deliberately no built-in login route. `Auth::login()` is a server-side operation and must only be called after verified credentials or a trusted identity provider. Protected `/admin` and `/student` placeholders return 401 until a valid identity exists; they do not grant access by default.

## Add a Controller, Model, Service, or Route

- Controller: add a namespaced class under `app/Controllers`, extend `Core\Controller`, and register it in the container in `Application::boot()`.
- Model: add a namespaced class under `app/Models`, extend `Core\Model`, use `connection()` and prepared statements, and use `table()` only for validated identifiers.
- Service: add a focused class under `app/Services`, inject its dependencies, and register it in the container.
- Route: add a route to the relevant route file, return a `Response`, and attach authorization/CSRF middleware as appropriate. Global CSRF and rate limiting are already applied to the registered web and API routes.

Do not trust role, user, or student identifiers submitted by a browser. Use `Auth::identity()` or the configured integration contract.

## cPanel Deployment

Upload the application, configure PHP 8.1+ and `pdo_mysql`, set environment values, create the database/user through cPanel, and grant the minimum required database privileges. Prefer a document root pointing at `public/`; otherwise use the provided root rewrite and ensure `.htaccess` is enabled. Set a real HTTPS `APP_BASE_URL`, enable HTTPS, and make only the required `storage` directories writable by PHP. Do not expose storage or `.env`; keep backups outside the web root where possible. Deployment details and checks are in [docs/INSTALLATION.md](docs/INSTALLATION.md).

## Development Status and Limitations

Phase 1 establishes the architecture only. There are no migrations, application tables, user persistence, password login workflow, host-specific adapter, external identity mapping table, or installation wizard. The default website adapter returns no identity until a developer supplies a trusted server-side provider. File-based rate limiting is a basic shared-hosting foundation and is not a substitute for edge/WAF controls or a distributed limiter. No tests can establish the security of an eventual deployment without environment-specific review.