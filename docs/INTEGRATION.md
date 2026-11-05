# Website Integration Contract

ExamCore supports standalone mode and existing-website mode without assuming a host database schema or session variable. Integration is replaceable through `IntegrationInterface` and `AuthAdapterInterface`; business logic should consume a normalized `StudentIdentity`, not host-specific globals.

## Identity

`StudentIdentity` contains an external host ID, an ExamCore internal ID, display name, and server-established roles. External IDs should be stored/mapped as opaque strings so host-specific formats are preserved. The host integration is responsible for a stable mapping to the internal CBT identity; this phase does not create mapping tables or duplicate-account handling. A future mapping store should use unique constraints on the configured host/source and external ID.

## Standalone Mode

Set `INTEGRATION_MODE=standalone`. `StandaloneAdapter` delegates to ExamCore's session-backed `Auth`. A standalone authentication controller and persistence model are intentionally not implemented, so users cannot log in through this phase's interface.

## Existing Website Mode

Set `INTEGRATION_MODE=website` and register a trusted server-side provider in `config/integration.php`, for example:

```php
return [
    'mode' => 'website',
    'identity_provider' => static function (): ?\ExamCore\Integrations\StudentIdentity {
        // Verify the host application's server-side authentication state here.
        // Map its trusted identity to stable external and CBT internal IDs.
        return null;
    },
];
```

The callback must validate the host's authenticated session using its own documented server-side API and return a `StudentIdentity` or `null`. Do not read an ID from a URL, hidden field, request header, or arbitrary cookie and treat it as authenticated. Do not assume a host session key. Do not expose the callback to the browser. The app currently constructs the website adapter with `identity_provider` when supplied; without it, identity is always absent and protected routes remain denied.

## Host Responsibilities and Verification

The host website remains responsible for login, logout, credential verification, and its own session security. The integration must define logout synchronization, role mapping, account deactivation, identifier stability, and the effects of host session expiration. Validate that the application runs in the intended subdirectory and that cookie path/domain behavior is compatible. Integration tests should cover authenticated, expired, disabled, unmapped, and wrong-role identities. Do not connect to or alter a real school website database until its owner approves a specific mapping design.