# Release Process

This plugin uses Composer patches to maintain modifications to the `andreskrey/readability.php` library. The release process is automated via GitHub Actions.

## How It Works

### Development
- The `vendor/` directory is **not** committed to git (in `.gitignore`)
- Modifications to vendor libraries are stored as patch files in `patches/`
- Run `composer install` to install dependencies and apply patches

### Creating a Release

1. **Update version number** in the main plugin file if needed

2. **Create and push a tag:**
   ```bash
   git tag 1.2.3
   git push origin 1.2.3
   ```

3. **GitHub Action automatically:**
   - Runs `composer install --no-dev` (installs production dependencies with patches)
   - Creates a clean ZIP file excluding development files
   - Creates a GitHub Release with the ZIP attached
   - Generates release notes from commits

### What Gets Included in the Release ZIP

**Included:**
- All plugin PHP files
- `vendor/` directory (with patches applied)
- `patches/` directory
- `composer.json`
- Templates, languages, and site-configs

**Excluded:**
- `.git` directory
- `.github` workflows
- `tests/` directory
- Development files (`phpunit.xml`, `phpcs.xml`, etc.)
- `.gitignore`, `.vscode`, `.DS_Store`
- `composer.lock` and `patches.lock.json`
- `vendor.backup/`

## Manual Release (if needed)

If you need to create a release manually:

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Create a clean copy
mkdir -p release
rsync -av --exclude='.git' --exclude='.github' --exclude='tests' \
         --exclude='.gitignore' --exclude='vendor.backup' \
         ./ release/friends-post-collection/

# Create ZIP
cd release
zip -r friends-post-collection-1.2.3.zip friends-post-collection/
```

## Patches

Current patches applied to `andreskrey/readability.php`:

1. **Handle missing body element** (`patches/readability-missing-body.patch`)
   - Fallback to `<html>` element if `<body>` tag is missing

2. **PHP 8+ union types** (`patches/readability-php8-union-types.patch`)
   - Adds modern PHP 8 union type hints to DOMNodeList

To view what the patches do:
```bash
cat patches/*.patch
```
