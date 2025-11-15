# Changes Needed to package.json

Add "postinstall": "patch-package" to the scripts section.

The key changes are:
1. Line 2: Add "postinstall": "patch-package",
2. Lines 25-26: Add patch-package and postinstall-postinstall to devDependencies

See package.json.example for the complete file with these changes.
