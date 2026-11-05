# Security Foundation

This document describes implemented controls and remaining work. The presence of these classes is not a security certification; threat modeling, deployment review, and testing remain required before production use.

## Sessions and Authentication

Sessions use strict mode, cookie-only transport, HttpOnly, SameSite, HTTPS-aware Secure cookies, a configurable idle timeout, and a private storage directory. `Auth::login()` regenerates the session ID before recording a server-created identity, and `logout()` removes the identity. Password helper methods use `password_hash()` and `password_verify()`. There is no login controller, credential store, lockout flow, recovery flow, or MFA in this phase. Never call login with client-supplied identity data.

## Authorization and Integration

Admin/student middleware checks for a server-side identity and its role. The default routes reject unauthenticated requests. Permission mappings are configurable in `config/permissions.php`; callers should use `Auth::can()` for action-specific authorization. The website integration must return a `StudentIdentity` from trusted server-side host state. URL/query/form student identifiers are not used to establish identity. The default website provider returns no identity until explicitly wired.

## CSRF, Input, and Output

Global middleware requires a session CSRF token for POST, PUT, PATCH, and DELETE requests and rotates it after successful processing. Tokens may arrive in the form field `_csrf_token`, JSON field `_csrf_token`, or `X-CSRF-Token` header. The validator provides basic scalar rules; each feature must define allowlists, limits, and domain validation. `e()` escapes HTML text/attribute output. Templates must escape untrusted values and must not emit raw user content. JSON encoding is centralized in `Response::json()`.

## Database

PDO is configured for exception mode and native prepared statements. Bind all values. Identifiers require allowlist validation because PDO cannot bind table/column names. Connection exceptions are replaced with a generic message. No production table schema exists yet.

## Headers and Errors

Responses set `nosniff`, same-origin framing, referrer policy, a restrictive baseline Content Security Policy, a permissions policy, and `no-store`. Dispatch failures return a generic message and log only the exception class. Production mode suppresses detailed exception output. Review CSP and headers when adding external assets, forms, or embedded content.

## Logging and Rate Limiting

Logs are JSON lines stored under private `storage/logs`, with common secret-like context keys redacted and control characters removed. Do not pass raw request bodies, passwords, cookies, or tokens as log context. Audit service calls are a foundation, not a durable tamper-evident audit store. The rate limiter stores locked counters in `storage/cache`, keyed by remote address. It is suitable only as a basic shared-hosting control; proxy configuration, cleanup, availability, and distributed behavior need further work. Do not trust forwarded IP headers unless a known proxy is explicitly configured.

## Files and Deployment

Root rules deny direct private-directory and dotfile access; storage has its own deny rule and PHP execution restrictions; `public/` permits only the front controller as PHP. Prefer `public/` as document root. Confirm `.htaccess` is honored by the actual cPanel host. Keep backups outside the web root. No upload feature exists; any future upload directory must be outside public execution paths, validate file content and size server-side, and deny script execution at the web server.

## Required Before Production

- Add and review a complete authentication lifecycle and authorization policy.
- Integrate a host-specific identity provider and verify its trust boundary.
- Perform application-specific CSRF/XSS/SQLi testing and session behavior testing over HTTPS.
- Review reverse-proxy/IP handling, rate-limit persistence/cleanup, log retention, backup access, and Apache/PHP-FPM configuration.
- Establish dependency update, vulnerability scanning, and secret rotation procedures.
- Run tests under the exact supported PHP versions and database engine used in production.