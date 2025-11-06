# Plugin Build Checklist

This document defines the **mandatory checklist** that must be completed **AFTER making any plugin changes** and **BEFORE pushing to GitHub**.

**Purpose**: Prevent common dependency errors, build failures, and deployment issues. Ensure user has access to installable plugin zip.

---

## ⚠️ CRITICAL: When to Use This Checklist

**MANDATORY** after making changes to:
- ✅ **Frontend code** (React components, TypeScript, CSS)
- ✅ **Backend code** (PHP controllers, models, services)
- ✅ **Database migrations**
- ✅ **Dependencies** (composer.json, package.json)
- ✅ **Plugin configuration** (ma-deal-room.php)

**The Process:**
1. Make your code changes
2. **Run this entire checklist**
3. **Build the plugin zip**
4. **Commit zip to GitHub**

**WHY**: The user does NOT have access to the local directory. They can ONLY download from GitHub. If you don't create and commit the zip, they cannot test your changes.

---

## Overview

Many issues during plugin testing arise from:

1. **Missing vendor dependencies** (Composer packages not installed)
2. **Missing frontend build** (React admin dashboard not compiled)
3. **Syntax errors** that weren't caught during development
4. **Migration issues** that break fresh installations
5. **Incorrect file permissions** or paths

This checklist ensures the plugin is **production-ready** before zipping.

---

## Mandatory Pre-Build Checklist

Complete these steps **IN ORDER** before running any plugin build script:

### Step 1: Verify Working Directory

```bash
# Ensure you're in the project root
pwd
# Should output: /home/user/dealroom (or similar)

# Check that ma-deal-room directory exists
ls -la ma-deal-room/
```

**Expected**: ma-deal-room directory exists with plugin files

---

### Step 2: Check Composer Dependencies

**⚠️ CRITICAL**: The plugin **requires** vendor dependencies to function.

```bash
# Navigate to plugin directory
cd ma-deal-room

# Check if vendor directory exists
if [ -d "vendor" ]; then
  echo "✅ Vendor directory exists"
  ls -la vendor/ | head -10
else
  echo "❌ Vendor directory missing - MUST install dependencies"
fi

# Check composer.lock exists
if [ -f "composer.lock" ]; then
  echo "✅ Composer lock file exists"
else
  echo "⚠️ No composer.lock - dependencies may not match"
fi
```

**If vendor directory is missing**, run:

```bash
# Install Composer dependencies (production mode)
composer install --no-dev --optimize-autoloader

# Verify installation
ls -la vendor/
composer show | head -20
```

**Expected Dependencies** (minimum):

- firebase/php-jwt
- guzzlehttp/guzzle
- monolog/monolog
- vlucas/phpdotenv (if used)

**Verification**:

```bash
# Check that autoloader exists
ls -la vendor/autoload.php

# Check critical packages
ls vendor/firebase/
ls vendor/guzzlehttp/
ls vendor/monolog/
```

---

### Step 3: Check Frontend Build

The React admin dashboard must be built before packaging.

```bash
# Check if build directory exists
if [ -d "assets/admin/build" ]; then
  echo "✅ Frontend build exists"
  ls -lh assets/admin/build/
else
  echo "❌ Frontend build missing - MUST build React app"
fi

# Check for critical build files
required_files=(
  "assets/admin/build/index.html"
  "assets/admin/build/static/js/main.*.js"
  "assets/admin/build/static/css/main.*.css"
)

for file in "${required_files[@]}"; do
  if ls $file 1> /dev/null 2>&1; then
    echo "✅ Found: $file"
  else
    echo "❌ Missing: $file"
  fi
done
```

**If frontend build is missing**, run:

```bash
# Navigate to frontend directory
cd assets/admin

# Install npm dependencies
npm install

# Build for production
npm run build

# Verify build output
ls -lh build/
ls -lh build/static/js/
ls -lh build/static/css/

# Return to plugin root
cd ../..
```

**Expected Output**:

- `build/index.html` (entry point)
- `build/static/js/main.[hash].js` (JavaScript bundle)
- `build/static/css/main.[hash].css` (CSS bundle)
- `build/asset-manifest.json` (asset mapping)

---

### Step 4: Validate PHP Syntax

Check for PHP syntax errors that could break the plugin.

```bash
# From plugin directory (ma-deal-room/)
# Check all PHP files for syntax errors
find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \; | grep -v "No syntax errors"

# If no output, all files are valid
# If errors appear, fix them before proceeding
```

**Expected**: No syntax errors found

**If errors found**:

1. Note the file and line number
2. Fix the syntax error
3. Re-run the check
4. Commit the fix before building

---

### Step 5: Verify Database Migrations

Ensure migrations are properly numbered and functional.

```bash
# List all migrations in order
ls -la database/migrations/

# Check that migrations are sequentially numbered
# Expected format: 000_initial_schema.php, 001_add_users.php, etc.

# Verify latest migration number
latest=$(ls database/migrations/ | tail -1 | grep -oP '^\d+')
echo "Latest migration: $latest"
```

**Common Issues**:

- Duplicate migration numbers
- Missing migrations in sequence
- Syntax errors in migration files

**Validation Script** (optional):

```bash
# Quick migration syntax check
for file in database/migrations/*.php; do
  echo "Checking: $file"
  php -l "$file"
done
```

---

### Step 6: Check Plugin Version

Ensure the plugin version is updated and matches VERSION_HISTORY.md.

```bash
# Check plugin version in main file
grep "Version:" ma-deal-room.php

# Check version constant
grep "MA_DEAL_ROOM_VERSION" ma-deal-room.php

# Compare with VERSION_HISTORY.md
head -20 ../VERSION_HISTORY.md | grep "Version"
```

**Expected**: Versions should match (e.g., 2.0.0)

**If versions don't match**:

1. Update `ma-deal-room.php` version header
2. Update `MA_DEAL_ROOM_VERSION` constant
3. Update `VERSION_HISTORY.md` with changes
4. Commit version bump

---

### Step 7: Verify File Permissions

Check that files have correct permissions for WordPress.

```bash
# Directories should be 755
find . -type d -not -path "./vendor/*" -not -path "./node_modules/*" | head -10 | xargs ls -ld

# PHP files should be 644 or 755
find . -name "*.php" -not -path "./vendor/*" | head -10 | xargs ls -l
```

**Expected**:

- Directories: `drwxr-xr-x` (755)
- Files: `-rw-r--r--` (644) or `-rwxr-xr-x` (755)

---

### Step 8: Run Pre-Build Tests (Optional but Recommended)

If you have automated tests, run them before building.

```bash
# PHP Unit tests (if available)
vendor/bin/phpunit tests/

# Frontend tests (if available)
cd assets/admin
npm test
cd ../..
```

---

## Build Process

Once all checklist items pass, create the plugin zip:

### Option 1: Using Python Build Script

```bash
# Return to project root
cd /home/user/dealroom

# Run build script
python3 build-plugin-zip.py

# Or
python3 create-plugin-zip.py
```

### Option 2: Manual Build

```bash
# From project root
cd /home/user/dealroom

# Create zip excluding development files
zip -r ma-deal-room-v[VERSION].zip ma-deal-room \
  -x "ma-deal-room/node_modules/*" \
  -x "ma-deal-room/assets/admin/node_modules/*" \
  -x "ma-deal-room/assets/admin/src/*" \
  -x "ma-deal-room/.git/*" \
  -x "ma-deal-room/.env" \
  -x "ma-deal-room/tests/*" \
  -x "*.log" \
  -x ".DS_Store"

# Verify zip contents
unzip -l ma-deal-room-v[VERSION].zip | head -50
```

**Critical Files to Include**:

- ✅ `vendor/` directory (Composer dependencies)
- ✅ `assets/admin/build/` directory (React build)
- ✅ `database/migrations/` (all migrations)
- ✅ `src/` directory (plugin source code)
- ✅ `ma-deal-room.php` (main plugin file)

**Files to Exclude**:

- ❌ `node_modules/` (npm packages)
- ❌ `assets/admin/src/` (React source - already built)
- ❌ `.env` files (sensitive data)
- ❌ `tests/` directory
- ❌ `.git/` directory

---

## Post-Build Verification

After creating the zip file, verify its integrity:

### Step 1: Check Zip Size

```bash
# Check file size
ls -lh ma-deal-room-v*.zip

# Expected size: 4-6 MB (with vendor and build)
# If < 1 MB: Missing dependencies
# If > 10 MB: Likely includes node_modules
```

### Step 2: Verify Zip Contents

```bash
# List zip contents
unzip -l ma-deal-room-v[VERSION].zip > zip_contents.txt

# Check for vendor directory
grep "vendor/" zip_contents.txt | head -10

# Check for frontend build
grep "assets/admin/build/" zip_contents.txt | head -10

# Check that node_modules is NOT included
grep "node_modules/" zip_contents.txt
# Should return empty

# Check that source files are NOT included
grep "assets/admin/src/" zip_contents.txt
# Should return empty (only build directory)
```

### Step 3: Test Zip Extraction

```bash
# Create test directory
mkdir -p /tmp/plugin-test

# Extract zip
unzip ma-deal-room-v[VERSION].zip -d /tmp/plugin-test/

# Verify structure
ls -la /tmp/plugin-test/ma-deal-room/

# Check vendor exists
ls -la /tmp/plugin-test/ma-deal-room/vendor/

# Check build exists
ls -la /tmp/plugin-test/ma-deal-room/assets/admin/build/

# Clean up
rm -rf /tmp/plugin-test
```

---

## Common Build Errors and Solutions

### Error: "Composer dependencies not found"

**Cause**: vendor/ directory missing from zip

**Solution**:
```bash
cd ma-deal-room
composer install --no-dev --optimize-autoloader
cd ..
# Re-run build script
```

### Error: "Frontend not loading" or "Blank admin screen"

**Cause**: React build missing from zip

**Solution**:
```bash
cd ma-deal-room/assets/admin
npm install
npm run build
cd ../../..
# Re-run build script
```

### Error: "Plugin won't activate"

**Cause**: Syntax errors in PHP files

**Solution**:
```bash
# Check for syntax errors
find ma-deal-room -name "*.php" -not -path "*/vendor/*" -exec php -l {} \;
# Fix any errors found
```

### Error: "Database errors on activation"

**Cause**: Migration files have errors or wrong sequence

**Solution**:
```bash
# Verify migrations
ls ma-deal-room/database/migrations/
# Check for duplicate numbers or missing files
# Fix migration sequence
```

### Error: "Zip file too large (>10MB)"

**Cause**: node_modules or other dev files included

**Solution**:
```bash
# Check what's in the zip
unzip -l ma-deal-room-v[VERSION].zip | grep -E "node_modules|\.git|src/"
# Update build script to exclude these directories
```

### Error: "Zip file too small (<1MB)"

**Cause**: vendor/ or build/ directories not included

**Solution**:
```bash
# Verify vendor exists before building
ls -la ma-deal-room/vendor/
# Verify build exists before building
ls -la ma-deal-room/assets/admin/build/
# Re-run checklist steps 2 and 3
```

---

## Automated Checklist Script

You can create a script to automate this checklist:

```bash
#!/bin/bash
# File: pre-build-check.sh

echo "🔍 MA Deal Room - Pre-Build Checklist"
echo "======================================"

# Change to plugin directory
cd ma-deal-room || exit 1

# Check 1: Vendor directory
if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
  echo "✅ Composer dependencies installed"
else
  echo "❌ FAILED: Composer dependencies missing"
  exit 1
fi

# Check 2: Frontend build
if [ -d "assets/admin/build" ] && [ -f "assets/admin/build/index.html" ]; then
  echo "✅ Frontend build exists"
else
  echo "❌ FAILED: Frontend build missing"
  exit 1
fi

# Check 3: PHP syntax
echo "🔍 Checking PHP syntax..."
syntax_errors=$(find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \; 2>&1 | grep -v "No syntax errors")
if [ -z "$syntax_errors" ]; then
  echo "✅ No PHP syntax errors"
else
  echo "❌ FAILED: PHP syntax errors found:"
  echo "$syntax_errors"
  exit 1
fi

# Check 4: Migrations exist
migration_count=$(ls database/migrations/*.php 2>/dev/null | wc -l)
if [ "$migration_count" -gt 0 ]; then
  echo "✅ Found $migration_count migrations"
else
  echo "❌ FAILED: No migrations found"
  exit 1
fi

# Check 5: Version check
version=$(grep "Version:" ma-deal-room.php | head -1 | awk '{print $2}')
echo "✅ Plugin version: $version"

cd ..

echo ""
echo "✅ All pre-build checks passed!"
echo "Ready to build plugin zip for version $version"
```

**Usage**:

```bash
chmod +x pre-build-check.sh
./pre-build-check.sh
```

---

## Quick Reference Checklist

Use this quick checklist before every build:

- [ ] Composer dependencies installed (`ls ma-deal-room/vendor/`)
- [ ] Frontend built (`ls ma-deal-room/assets/admin/build/`)
- [ ] No PHP syntax errors (`find ma-deal-room -name "*.php" -exec php -l {} \;`)
- [ ] Migrations verified (`ls ma-deal-room/database/migrations/`)
- [ ] Version updated in ma-deal-room.php
- [ ] Version matches VERSION_HISTORY.md
- [ ] Ready to build plugin zip

---

## ⚠️ MANDATORY: Commit Zip to GitHub

**CRITICAL FINAL STEP**: After creating and verifying the zip, you **MUST** commit it to GitHub.

### Why This Matters

The user **DOES NOT** have access to your local working directory. They can **ONLY** download files from GitHub. If you don't commit the zip, they have no way to test your changes.

### Steps to Commit

```bash
# 1. Add the zip file to git
git add ma-deal-room-v*.zip

# 2. Check what's being committed
git status

# 3. Commit with descriptive message
git commit -m "build: create plugin v[VERSION] with [brief description of changes]

- Built React frontend (npm run build)
- Installed Composer dependencies
- Created installable WordPress plugin zip

[List key features or fixes included]

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"

# 4. Push to GitHub
git push origin [your-branch-name]
```

### Verification

After pushing, verify the zip is on GitHub:
1. Go to the GitHub repository
2. Check that `ma-deal-room-v[VERSION].zip` exists in the file listing
3. Try downloading it to confirm it uploaded successfully

**Do NOT consider your work complete until the zip is on GitHub.**

---

## Integration with Other Protocols

This checklist works together with:

- **SESSION_STARTUP_PROTOCOL.md** - Complete before any session work
- **WP_PLUGIN_DEPLOYMENT_AGENT.md** - Follow after building plugin
- **DEPLOYMENT_CHECKLIST.md** - Use for production deployments
- **VERSION_HISTORY.md** - Update with each build

---

## Notes for AI Assistants

**CRITICAL**: After making ANY changes to the plugin code, you MUST:

1. **Run this entire checklist**
2. **Fix** any issues found (don't skip them)
3. **Verify** all checks pass
4. **Build the plugin zip**
5. **Verify** the zip file integrity
6. **Commit the zip to GitHub**
7. **Push to remote repository**

**NEVER tell the user to run these steps themselves** - they don't have access to the local directory. YOU must complete all steps and push the zip to GitHub.

**Do not skip these steps** - They prevent the common "dependency not found" errors and ensure the user can actually test your changes.

---

## Version

**Checklist Version**: 1.0
**Last Updated**: November 6, 2025
**Maintained By**: MA Deal Room Development Team

---

## Feedback

If you encounter build issues not covered here, document them in:
- This file (add to Common Build Errors section)
- GitHub Issues
- Session notes in `docs/archive/session-notes/`
