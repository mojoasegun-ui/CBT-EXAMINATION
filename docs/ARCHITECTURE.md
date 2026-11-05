# Architecture

## Request Flow

`public/index.php` loads `bootstrap/autoload.php`, obtains the booted application from `bootstrap/app.php`, constructs a `Request`, and sends the resulting `Response`. Apache rewrites application URLs to this front controller. For local development, `public/router.php` sends existing public assets to PHP's built-in static server and dispatches other URLs to the front controller.

Bootstrap loads environment values and the PHP configuration arrays, sets the timezone, creates the dependency container, registers error handling, binds shared infrastructure, and loads route registration closures. The application adds security headers to responses. Exceptions in dispatch become generic 500 responses and are logged without exception messages or paths.

## Directories

- `app/Core`: application lifecycle, HTTP primitives, router, container, database, view rendering, validation, session/authentication, CSRF, logging, and error handling
- `app/Contracts`: authentication and integration contracts
- `app/Controllers`: request coordination and response rendering
- `app/Middleware`: authorization, CSRF, and request-rate controls
- `app/Integrations`: standalone and trusted website identity adapters
- `app/Services`: cross-cutting application operations such as audit logging and settings access
- `app/Helpers`: small framework-independent helpers, including HTML output escaping
- `bootstrap`: Composer-optional autoload fallback and application assembly
- `config`: environment-backed configuration arrays
- `database`: schema starting point, seed placeholder, and migration policy
- `public`: web entry point and static assets; this is the preferred document root
- `resources/views`: PHP templates and layouts
- `routes`: route registration by surface
- `storage`: private runtime logs, cache, sessions, and backups
- `tests`: PHPUnit unit and integration tests
- `docs`: operation, architecture, security, and integration documentation

## Dependency Flow

The container stores shared instances and lazily-created services. Controllers receive the container and view renderer through constructor injection; models receive `Database`; services receive only their required collaborators. Core classes do not query host website data. The `AuthAdapterInterface`/`IntegrationInterface` is the identity boundary for future business services.

## Autoloading and Configuration

Composer maps `ExamCore\\` to `app/` using PSR-4. `bootstrap/autoload.php` first loads Composer when present and registers a namespaced fallback for installations where Composer is not available. The same file loads the escaping helper. Configuration files return arrays; `.env` loading never overwrites an already exported environment variable.

## Routing

The router supports GET, POST, PUT, PATCH, DELETE, named routes, `{parameter}` segments, nested route groups, global/route middleware, and 404/405 responses. The request factory strips a detected application subdirectory from the incoming path. Set `APP_BASE_URL` when generating links under a fixed URL; route URL generation uses its path component.

Handlers return `Response` objects. Middleware wraps the handler as a pipeline and may short-circuit with a response. Avoid putting business rules or SQL in route closures; delegate to controllers/services.

## Database

`Database` lazily creates one PDO connection per application instance. It uses exception mode, associative fetches, native prepares, utf8mb4, validated DSN identifiers, and generic connection failures. Transactions roll back on thrown exceptions. `tablePrefix()` defaults to `cbt_` and validates identifiers. Models should bind all data values; prepared statements cannot bind SQL identifiers, so only validated internal table/column names may be interpolated.

No connection is made during startup or health checks. No schema or migration executes automatically.