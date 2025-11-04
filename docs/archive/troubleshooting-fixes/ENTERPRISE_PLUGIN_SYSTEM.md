# Enterprise-Level Plugin Update System

## Version 1.0.1 - What's New

This release implements a professional, enterprise-grade plugin update and management system.

---

## New Features in v1.0.1

### 1. Automatic Version Detection & Upgrades

The plugin now automatically detects when a new version is installed and runs upgrade routines.

**How it works:**
- On every page load (`plugins_loaded` hook), the plugin checks its version against the database
- If current version > installed version → automatic upgrade runs
- Upgrade routine runs migrations, re-syncs data if needed, clears caches

**Code location:** `ma-deal-room.php` lines 268-336

```php
add_action('plugins_loaded', function() {
    $installed_version = get_option('ma_deal_room_version', '0.0.0');
    $current_version = MA_DEAL_VERSION;

    if (version_compare($installed_version, $current_version, '<')) {
        ma_deal_room_upgrade($installed_version, $current_version);
        update_option('ma_deal_room_version', $current_version);
    }
});
```

### 2. Proper Deactivation Hook

Clean deactivation without data loss.

**What happens on deactivation:**
- ✅ Flushes rewrite rules
- ✅ Clears scheduled cron jobs
- ✅ Logs deactivation timestamp
- ❌ **Does NOT delete data** (use uninstall for that)

**Code location:** `ma-deal-room.php` lines 243-263

### 3. Complete Uninstall Support

Full data cleanup when plugin is deleted (not just deactivated).

**What gets deleted:**
- All database tables (23 tables)
- All plugin options
- Custom pages (agent dashboard)
- Custom user capabilities
- Uploaded files in `/uploads/ma-deal-room/`
- Scheduled cron jobs

**How to use:**
1. Deactivate plugin
2. Click "Delete" in WordPress plugins page
3. WordPress runs `uninstall.php` automatically

**Code location:** `uninstall.php`

### 4. Version-Specific Upgrade Routines

Upgrade logic based on version number.

**Example:** Upgrading from 1.0.0 → 1.0.1:
```php
if (version_compare($from_version, '1.0.1', '<')) {
    // Check if task definitions need re-syncing
    $task_def_count = get_option('ma_deal_room_task_definitions_count', 0);
    if ($task_def_count < 100) {
        ma_deal_room_sync_task_definitions();
    }
}
```

**Add your own upgrade routines:**
```php
// In ma_deal_room_upgrade() function
if (version_compare($from_version, '1.1.0', '<')) {
    // Your 1.1.0 upgrade code here
}
```

### 5. Comprehensive Logging

Debug logging for all major operations.

**Logged events:**
- Plugin activation
- Plugin deactivation
- Version upgrades
- Migration results
- Task definition sync results
- Errors and warnings

**View logs:**
```bash
# WordPress debug log
tail -f wp-content/debug.log | grep "MA Deal Room"
```

**Enable logging in wp-config.php:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 6. WordPress Options Tracking

New options added for tracking:

| Option Name | Description |
|-------------|-------------|
| `ma_deal_room_version` | Current installed version |
| `ma_deal_room_previous_version` | Previous version before upgrade |
| `ma_deal_room_last_upgraded` | Timestamp of last upgrade |
| `ma_deal_room_last_deactivated` | Timestamp of last deactivation |
| `ma_deal_room_uninstalled` | Timestamp if plugin was uninstalled |

**Query these:**
```sql
SELECT option_name, option_value
FROM wp_options
WHERE option_name LIKE 'ma_deal_room%'
ORDER BY option_name;
```

---

## Deployment Process (Enterprise-Level)

### Step 1: Backup

**Always backup before upgrading!**

```bash
# Database backup
wp db export backup-$(date +%Y%m%d-%H%M%S).sql

# File backup
tar -czf plugin-backup-$(date +%Y%m%d-%H%M%S).tar.gz wp-content/plugins/ma-deal-room/
```

### Step 2: Upload New Version

**WordPress Admin:**
1. Go to Plugins > Add New > Upload Plugin
2. Select `ma-deal-room-v1.0.1.zip`
3. Click "Replace current with uploaded"

**FTP/SFTP:**
1. Extract ZIP locally
2. Upload to `wp-content/plugins/ma-deal-room/`
3. Overwrite all files

### Step 3: Let WordPress Detect Upgrade

**DO NOT deactivate/reactivate unless told to!**

The plugin will automatically detect the version change on next page load and run the upgrade routine.

**Watch for upgrade in debug log:**
```
[03-Nov-2025 04:00:00 UTC] MA Deal Room: Upgrading from 1.0.0 to 1.0.1
[03-Nov-2025 04:00:01 UTC] MA Deal Room upgrade migrations: Array(...)
[03-Nov-2025 04:00:02 UTC] MA Deal Room 1.0.1 upgrade: Re-synced task definitions (created: 0, updated: 276)
```

### Step 4: Verify Upgrade

**Check version:**
```php
// Via PHP
echo get_option('ma_deal_room_version');
// Should output: 1.0.1
```

**Check upgrade log:**
```sql
SELECT option_value FROM wp_options WHERE option_name = 'ma_deal_room_last_upgraded';
```

**Verify functionality:**
- Dashboard loads
- Analytics shows data
- MLS configuration works
- No errors in console

---

## Troubleshooting Live Site Issues

### Issue: Plugin uploaded but still getting errors

**Cause:** Files didn't upload correctly OR PHP cache not cleared

**Solution:**

1. **Verify files exist on server:**
   ```bash
   # Check if new files exist
   ls -la wp-content/plugins/ma-deal-room/src/Models/MLSConfig.php
   ls -la wp-content/plugins/ma-deal-room/src/Repositories/MLSConfigRepository.php
   ```

2. **Check file modification dates:**
   ```bash
   ls -lh wp-content/plugins/ma-deal-room/ma-deal-room.php
   # Should show recent modification date
   ```

3. **Clear PHP cache:**
   ```bash
   # If using OPcache
   sudo service php8.1-fpm restart
   # OR
   sudo service php-fpm restart
   ```

4. **Clear WordPress cache:**
   ```php
   wp cache flush
   ```

### Issue: Version still shows 1.0.0

**Cause:** Plugin file didn't update OR cache issue

**Solution:**

1. **Check plugin header:**
   ```bash
   head -n 20 wp-content/plugins/ma-deal-room/ma-deal-room.php | grep Version
   ```
   Should show: `* Version: 1.0.1`

2. **Force clear cache:**
   ```bash
   # Delete plugin cache
   rm -rf wp-content/cache/

   # Restart web server
   sudo service apache2 restart
   # OR
   sudo service nginx restart
   ```

### Issue: 500 Error when saving MLS config

**Cause:** MLSConfigRepository not loaded OR autoload issue

**Debug steps:**

1. **Upload diagnostic script:**
   Upload `check-live-site-errors.php` to site root

2. **Access via browser:**
   ```
   https://your-site.com/check-live-site-errors.php
   ```

3. **Check output for:**
   - MLSConfig model: EXISTS?
   - MLSConfigRepository: EXISTS?
   - MLS repository registered: YES/NO?

4. **If files exist but not loading:**
   ```bash
   # Regenerate Composer autoload
   cd wp-content/plugins/ma-deal-room
   composer dump-autoload -o
   ```

5. **Check error log:**
   ```bash
   tail -100 wp-content/debug.log
   ```

### Issue: Task definitions not syncing

**Cause:** Upgrade routine didn't run

**Solution:**

1. **Manually trigger sync:**
   ```php
   // Via wp-cli
   wp eval 'ma_deal_room_sync_task_definitions();'

   // Or deactivate/reactivate plugin
   ```

2. **Check if upgrade ran:**
   ```sql
   SELECT option_value FROM wp_options
   WHERE option_name = 'ma_deal_room_last_upgraded';
   ```

---

## Version Numbering System

We use [Semantic Versioning](https://semver.org/):

**Format:** MAJOR.MINOR.PATCH

### When to bump version:

| Change Type | Version Bump | Example |
|-------------|--------------|---------|
| Breaking changes, major features | MAJOR | 1.0.0 → 2.0.0 |
| New features, backwards compatible | MINOR | 1.0.0 → 1.1.0 |
| Bug fixes, small improvements | PATCH | 1.0.0 → 1.0.1 |

### Current Versions:

- **v1.0.0:** Initial release with all core features
- **v1.0.1:** Added MLSConfigRepository, enterprise update system

### Future Versions (planned):

- **v1.0.2:** Additional bug fixes if needed
- **v1.1.0:** New features (reporting enhancements, etc.)
- **v2.0.0:** Major architectural changes (if any)

---

## How to Release a New Version

### 1. Update Version Number

**File:** `ma-deal-room.php`

```php
/**
 * Version: 1.0.2  ← Update this
 */

// Plugin version
define('MA_DEAL_VERSION', '1.0.2');  ← Update this
```

### 2. Add Upgrade Routine (if needed)

**File:** `ma-deal-room.php` in `ma_deal_room_upgrade()` function

```php
// Version-specific upgrades
if (version_compare($from_version, '1.0.2', '<')) {
    // Your 1.0.2 upgrade code here
    // Example: run new migration, update settings, etc.
}
```

### 3. Document Changes

Create a changelog file or update README:

```markdown
## Version 1.0.2 (2025-11-04)

### Added
- New feature X

### Fixed
- Bug Y

### Changed
- Improved Z
```

### 4. Build ZIP

```bash
./build-plugin-zip.sh 1.0.2
```

### 5. Test Upgrade

1. Install on test site with v1.0.1
2. Upload v1.0.2
3. Verify automatic upgrade runs
4. Check debug log for upgrade messages
5. Test all functionality

### 6. Deploy to Production

Follow deployment process above.

---

## Diagnostic Script

Upload `check-live-site-errors.php` to diagnose issues:

**What it checks:**
- ✅ Plugin activation status
- ✅ Plugin version
- ✅ Critical files exist
- ✅ Service container loaded
- ✅ MLS repository registered
- ✅ Database tables exist
- ✅ WordPress options set
- ✅ Recent PHP errors

**Usage:**
```bash
# Upload via FTP/SFTP
scp check-live-site-errors.php user@server:/path/to/wordpress/

# Access via browser
https://your-site.com/check-live-site-errors.php

# Or via command line
curl https://your-site.com/check-live-site-errors.php
```

---

## Best Practices

### DO:
- ✅ Always backup before upgrading
- ✅ Test upgrades on staging first
- ✅ Monitor debug logs during upgrade
- ✅ Verify version changed after upload
- ✅ Clear PHP cache after upload
- ✅ Use semantic versioning
- ✅ Document all changes
- ✅ Add version-specific upgrade routines

### DON'T:
- ❌ Skip backups
- ❌ Deploy directly to production without testing
- ❌ Forget to bump version number
- ❌ Deactivate/reactivate unnecessarily (upgrade runs automatically)
- ❌ Delete plugin data on deactivation (use uninstall instead)
- ❌ Ignore error logs
- ❌ Upload files without verifying they copied correctly

---

## Summary

**Version 1.0.1 adds enterprise-grade features:**

1. ✅ Automatic version detection and upgrades
2. ✅ Proper deactivation hooks
3. ✅ Complete uninstall support
4. ✅ Version-specific upgrade routines
5. ✅ Comprehensive logging
6. ✅ WordPress options tracking
7. ✅ Diagnostic tools
8. ✅ Professional deployment process

**Plugin ZIP:** `ma-deal-room-v1.0.1.zip` (51.45 MB)

**Next step for live site:** Upload v1.0.1, verify files copied correctly, clear PHP cache, test!
