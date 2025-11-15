# Lazy Loading Implementation for UserFrosting 6.0

This folder contains the modified composable files that implement lazy loading for YAML schemas.

## Problem Being Solved

When loading any UserFrosting 6.0 frontend page, ALL schema YAML files were being eagerly loaded via static imports, even when not needed:

- `login.yaml` and `register.yaml` - loaded even when user was already logged in
- `profile-settings.yaml`, `account-settings.yaml`, `account-email.yaml` - loaded on every page
- `group.yaml`, `role.yaml`, `create.yaml` - loaded even for non-admin users

## Solution

All 8 composables have been modified to use **dynamic imports** instead of static imports.

## Modified Files

### Account Composables (5 files)
Located in: `node_modules/@userfrosting/sprinkle-account/app/assets/composables/`

1. **useLoginApi.ts** - Lazy loads `login.yaml`
2. **useRegisterApi.ts** - Lazy loads `register.yaml`
3. **useUserProfileEditApi.ts** - Lazy loads `profile-settings.yaml`
4. **useUserPasswordEditApi.ts** - Lazy loads `account-settings.yaml`
5. **useUserEmailEditApi.ts** - Lazy loads `account-email.yaml`

### Admin Composables (3 files)
Located in: `node_modules/@userfrosting/sprinkle-admin/app/assets/composables/`

1. **useGroupApi.ts** - Lazy loads `group.yaml`
2. **useRoleApi.ts** - Lazy loads `role.yaml`
3. **useUserApi.ts** - Lazy loads `user/create.yaml`

## Key Changes

### Before (Static Import - Eager Loading)
```typescript
import schemaFile from '../../schema/requests/login.yaml'
const { r$ } = useRegle(formData, useRuleSchemaAdapter().adapt(schemaFile))
```

### After (Dynamic Import - Lazy Loading)
```typescript
// Lazy load schema with memoization
let schemaPromise: Promise<any> | null = null
function loadSchema() {
    if (!schemaPromise) {
        schemaPromise = import('../../schema/requests/login.yaml')
    }
    return schemaPromise
}

// Reactive schema state
const schemaLoaded = ref<boolean>(false)
const schemaData = ref<any>({})

// Load schema asynchronously
loadSchema().then((module) => {
    schemaData.value = module.default
    schemaLoaded.value = true
})

// Computed validation rules that activate when schema loads
const { r$ } = useRegle(formData, computed(() => 
    schemaLoaded.value ? useRuleSchemaAdapter().adapt(schemaData.value) : {}
))
```

## Implementation Method

Since these files are in npm packages (`@userfrosting/sprinkle-account` and `@userfrosting/sprinkle-admin`), we use **patch-package** to maintain the changes:

1. Modify the files in `node_modules/`
2. Run `npx patch-package @userfrosting/sprinkle-account`
3. Run `npx patch-package @userfrosting/sprinkle-admin`
4. This creates patch files in `patches/` directory
5. Add a postinstall script to `package.json` to auto-apply patches

## package.json Changes

Add this to your `package.json`:

```json
{
  "scripts": {
    "postinstall": "patch-package"
  },
  "devDependencies": {
    "patch-package": "^8.0.1",
    "postinstall-postinstall": "^2.1.0"
  }
}
```

## Benefits

1. **Faster initial page load** - Only schemas needed for the current page are loaded
2. **Better performance for logged-in users** - login/register schemas never load
3. **Conditional loading** - Admin schemas only load when admin pages are accessed
4. **Code splitting** - Vite automatically creates separate chunks:
   - `login.yaml` → ~0.37 kB chunk
   - `register.yaml` → ~1.37 kB chunk
   - `profile-settings.yaml` → ~0.44 kB chunk
   - `account-settings.yaml` → ~0.40 kB chunk
   - `account-email.yaml` → ~0.29 kB chunk
   - `group.yaml` → ~0.48 kB chunk
   - `role.yaml` → ~0.38 kB chunk
   - `user/create.yaml` → ~1.17 kB chunk

## Files in This Folder

- `composables/account/` - 5 modified account composables
- `composables/admin/` - 3 modified admin composables
- `README.md` - This file
- `package.json.changes` - The changes needed for package.json

## Next Steps

1. Install patch-package: `npm install --save-dev patch-package postinstall-postinstall`
2. Copy these modified files to the appropriate locations in `node_modules/`
3. Run `npx patch-package @userfrosting/sprinkle-account`
4. Run `npx patch-package @userfrosting/sprinkle-admin`
5. Add the postinstall script to your `package.json`
6. Commit the patch files in `patches/` directory

The patches will automatically apply whenever someone runs `npm install`.
