# Pull Request Summary

## Fix: Invalid CSRF Storage Error in CLI/Bakery Context

### Issue Description
UserFrosting 6.0 applications crash when running Bakery (CLI) commands with the following fatal error:

```
Fatal error: Uncaught RuntimeException: Invalid CSRF storage. 
Use session_start() before instantiating the Guard middleware or provide array storage.
```

**Root Cause:** The upstream `CsrfGuard` class in `userfrosting/sprinkle-core` passes `null` as the storage parameter to Slim CSRF Guard. While this worked in earlier versions, the Slim CSRF Guard now validates this parameter and throws an exception when `null` is passed without an active session. In CLI context, sessions cannot be started properly, causing the error.

### Solution Overview
This PR implements a **local workaround** that:

1. **Creates a custom CsrfGuard** (`app/src/Csrf/CsrfGuard.php`) that extends `Slim\Csrf\Guard`
2. **Detects CLI context** automatically using `php_sapi_name() === 'cli' || defined('STDIN')`
3. **Uses array storage in CLI** - satisfies the Guard's validation requirements
4. **Uses session storage in HTTP** - maintains normal web application behavior
5. **Preserves all functionality** - includes CSRF blacklist support from the base class
6. **Overrides the service** in `MyServices.php` to use the custom implementation

### Files Modified

| File | Changes | Description |
|------|---------|-------------|
| `app/src/Csrf/CsrfGuard.php` | +106 lines (new) | Custom CSRF Guard with CLI detection |
| `app/src/MyServices.php` | +6/-1 lines | Service override configuration |
| `CSRF_FIX.md` | +67 lines (new) | Comprehensive documentation |
| **Total** | **+179/-1 lines** | **3 files changed** |

### Technical Details

#### CLI Detection Logic
```php
$isCli = php_sapi_name() === 'cli' || defined('STDIN');
```

#### Storage Selection
```php
if ($isCli) {
    $storage = [];  // Array storage for CLI
} else {
    $session->start();
    $storage = null;  // Session storage for HTTP
}
```

#### Service Override
```php
return [
    CoreCsrfGuard::class => \DI\autowire(CsrfGuard::class),
];
```

### Security Analysis
✅ **No security vulnerabilities introduced**
- Maintains the same security model as upstream
- Does not bypass or weaken CSRF protection
- Only changes storage mechanism based on context
- All configuration values sourced from Config service
- Proper input validation and normalization maintained

### Testing & Validation
✅ All validations passed:
- CLI detection logic works correctly
- Storage selection operates as expected  
- PHP syntax valid for all modified files
- Service override properly configured
- Documentation complete and accurate
- No regressions in existing functionality

### Impact
- **Before Fix:** Bakery commands fail with RuntimeException
- **After Fix:** Bakery commands execute successfully
- **HTTP Context:** No changes, works exactly as before
- **CLI Context:** Now works with array-based CSRF storage

### Compatibility
- **UserFrosting:** 6.0.0-beta.7 and later
- **PHP:** 8.1+
- **Contexts:** Both CLI (Bakery) and HTTP (web)

### Future Considerations
This is a **local workaround** for the skeleton application. The proper fix should be implemented in the upstream `userfrosting/sprinkle-core` package. Once the upstream fix is released, this local override can be removed by simply reverting the changes to `MyServices.php`.

### How to Use
1. Pull this branch
2. Run Bakery commands as normal
3. Everything should work without the CSRF storage error

### References
- Original error stack trace: See issue description
- Upstream package: `userfrosting/sprinkle-core`
- Slim CSRF Guard: `slim/csrf` ^1.3

### Commits
1. Add custom CsrfGuard to fix CLI context issue
2. Improve CsrfGuard implementation with complete process method
3. Add documentation for CSRF fix
