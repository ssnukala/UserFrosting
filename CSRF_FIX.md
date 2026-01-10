# CSRF Storage Fix for CLI Context

## Problem

When running UserFrosting 6.0 with Bakery commands (CLI context), the application would crash with the following error:

```
Fatal error: Uncaught RuntimeException: Invalid CSRF storage. Use session_start() 
before instantiating the Guard middleware or provide array storage.
```

This error occurred because the `CsrfGuard` class in `userfrosting/sprinkle-core` was passing `null` as the storage parameter to the Slim CSRF Guard constructor. While this worked in previous versions, the Slim CSRF Guard now validates that the storage parameter is not null when sessions are not started.

## Root Cause

The issue is in the upstream `userfrosting/sprinkle-core` package's `CsrfGuard` class:

1. It calls `$session->start()` 
2. Sets `$sessionStorage = null`
3. Passes `null` to the parent `Guard` constructor

In CLI/Bakery context:
- Sessions don't start properly (no HTTP context)
- Passing `null` to the Guard constructor triggers the validation error
- The application crashes during dependency injection when loading Bakery commands

## Solution

This repository provides a local workaround by overriding the `CsrfGuard` service with a custom implementation that:

1. **Detects CLI context** using `php_sapi_name() === 'cli' || defined('STDIN')`
2. **Uses array storage in CLI** instead of null, which satisfies the Guard's validation
3. **Uses session storage in HTTP context** (normal behavior)
4. **Preserves all functionality** including the CSRF blacklist feature

## Files Changed

### `app/src/Csrf/CsrfGuard.php` (NEW)
Custom CSRF Guard implementation that extends `Slim\Csrf\Guard` and handles both CLI and HTTP contexts properly.

### `app/src/MyServices.php` (MODIFIED)
Service provider configuration that overrides the Core CsrfGuard with our custom implementation.

## Testing

The fix has been validated with:
- CLI detection tests
- Storage selection tests
- Syntax validation
- Integration with the service container

## Future Considerations

This is a **local workaround** for the skeleton application. The proper fix should be implemented in the upstream `userfrosting/sprinkle-core` package. Once the upstream fix is released, this local override can be removed.

### Recommended Upstream Fix

The upstream package should:
1. Detect CLI context in the same way
2. Use array storage when not in HTTP context
3. Maintain backward compatibility

## Compatibility

- **UserFrosting**: 6.0.0-beta.7+
- **PHP**: 8.1+
- **Context**: Works in both CLI (Bakery) and HTTP (web) contexts
