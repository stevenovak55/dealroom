# Deploy Updated Plugin to Live Site

## Current Situation

✅ **Dev Site:** Working perfectly with all fixes
- Task definitions syncing automatically
- MLS configuration saving correctly
- All REST API endpoints functional

❌ **Live Site:** Still has old code
- Missing MLSConfigRepository
- Missing MLSConfig model
- MLS configuration fails with "Failed to create configuration"

## What's in the Updated Plugin

The plugin ZIP at `/home/snova/projects/dealroom/ma-deal-room-v1.0.0.zip` contains **ALL** fixes:

### Fix #1: MLS Configuration Issues
- ✅ BridgeClient.php - Fixed error handling
- ✅ MLSController.php - Fixed provider data format
- ✅ MLSClientFactory.php - Added server_token field
- ✅ MLS migrations (021, 022, 023)

### Fix #2: REST API 403 Errors
- ✅ BaseController.php - Added admin_permission_callback
- ✅ AnalyticsController.php - Removed conflicting override
- ✅ MLSController.php - Removed conflicting override

### Fix #3: Rate Limiter Crashes
- ✅ BaseController.php - Added null checks

### Fix #4: Task Definitions Auto-Sync
- ✅ ma-deal-room.php - Automatic task definitions sync on activation
- ✅ 276 task definitions from YAML templates

### Fix #5: MLS Repository (NEW - This is what's failing on live)
- ✅ MLSConfig.php - Model for MLS configurations
- ✅ MLSConfigRepository.php - Repository for database operations
- ✅ Plugin.php - Registered in service container

---

## Deployment Steps for Live Site

### Step 1: Download Plugin ZIP

From your local machine:

```bash
scp snova@your-server:/home/snova/projects/dealroom/ma-deal-room-v1.0.0.zip ~/Downloads/
```

**Verify download:**
- File size should be approximately **51.45 MB**
- File should be `ma-deal-room-v1.0.0.zip`

### Step 2: Backup Live Site (IMPORTANT!)

Before deploying, create a backup:

**Option A: Via WordPress Plugin (Recommended)**
1. Use your backup plugin (UpdraftPlus, BackWPup, etc.)
2. Create full backup (database + files)
3. Download backup to local machine

**Option B: Via Hosting Control Panel**
1. Log into cPanel / Plesk / hosting panel
2. Create full site backup
3. Download backup file

**Option C: Via SSH**
```bash
# Backup database
wp db export live-backup-$(date +%Y%m%d).sql

# Backup plugin files
tar -czf live-plugin-backup-$(date +%Y%m%d).tar.gz wp-content/plugins/ma-deal-room/
```

### Step 3: Upload Plugin to Live Site

#### Method A: Via WordPress Admin (Easiest)

1. **Log into WordPress Admin** on live site
2. Go to **Plugins > Add New**
3. Click **Upload Plugin** button (top of page)
4. Click **Choose File**
5. Select `ma-deal-room-v1.0.0.zip` from your Downloads folder
6. Click **Install Now**
7. **IMPORTANT:** When prompted, click **Replace current with uploaded**
   - This replaces the old plugin files with the new ones

#### Method B: Via FTP/SFTP (If upload limit too small)

1. **Extract ZIP** on your local computer
2. **Connect via FTP/SFTP** to live site
3. **Navigate to:** `wp-content/plugins/`
4. **Rename existing folder:** `ma-deal-room` → `ma-deal-room-old-backup`
5. **Upload new folder:** Upload the extracted `ma-deal-room` folder
6. **Set permissions:**
   - Folders: 755
   - Files: 644

### Step 4: Activate the Plugin

**CRITICAL:** You MUST deactivate and reactivate to run the activation hook.

1. Go to **Plugins > Installed Plugins**
2. Find **MA Deal Room**
3. Click **Deactivate**
4. Click **Activate**

**What happens during activation:**
- ✅ Runs database migrations (MLS tables)
- ✅ Syncs 7 templates from YAML files
- ✅ **Syncs 276 task definitions from YAML files**
- ✅ Registers capabilities
- ✅ Creates default account

### Step 5: Verify Deployment

#### Check 1: No Fatal Errors

After activation, check that:
- WordPress admin dashboard loads without errors
- MA Deal Room menu appears in sidebar
- No PHP fatal errors displayed

#### Check 2: Database Tables

Run this SQL query in phpMyAdmin or database tool:

```sql
-- Check MLS tables exist
SHOW TABLES LIKE 'wp_ma_deal_mls_%';
```

**Expected output:**
```
wp_ma_deal_mls_config
wp_ma_deal_mls_sync_log
```

#### Check 3: Task Definitions Populated

```sql
-- Check task definitions count
SELECT COUNT(*) FROM wp_ma_deal_task_definitions;
```

**Expected:** 276 records

#### Check 4: WordPress Options Set

```sql
-- Check activation options
SELECT option_name, option_value
FROM wp_options
WHERE option_name LIKE '%ma_deal_room%'
ORDER BY option_name;
```

**Should include:**
```
ma_deal_room_activated = 1
ma_deal_room_task_definitions_synced = 1
ma_deal_room_task_definitions_count = 276
ma_deal_room_templates_synced = 1
ma_deal_room_templates_sync_count = 7
```

#### Check 5: REST API Working

Test in browser console or via curl:

```bash
curl -u admin:password https://your-live-site.com/wp-json/ma-deal-room/v1/
```

**Should return:** JSON with registered routes (not 403 or 500 error)

#### Check 6: Analytics Page

1. Go to **MA Deal Room > Analytics**
2. Should see charts and data (not blank)
3. Check browser console - no 403 errors

#### Check 7: Task Library

1. Go to **MA Deal Room > Task Library**
2. Should show **276 task definitions**
3. Should be able to filter by category

#### Check 8: MLS Configuration (THE MAIN FIX)

1. Go to **MA Deal Room > Settings > MLS Configuration** (or wherever MLS config is)
2. Click **Add MLS Configuration**
3. Select **Bridge Interactive**
4. Enter test credentials:
   - **Configuration Name:** Test Config
   - **API Base URL:** `https://api.bridgedataoutput.com/api/v2/OData/shared_mlspin_41854c5`
   - **Server Token:** `1c69fed3083478d187d4ce8deb8788ed`
5. Click **Test Connection**
   - ✅ Should see: **Connection Successful**
6. Click **Create** or **Save**
   - ✅ Should save successfully
   - ❌ **NO MORE "Failed to create configuration" error**

---

## Troubleshooting

### Issue: "Upload failed - File size exceeds limit"

**Cause:** PHP upload limit too small (default often 2MB-8MB)

**Solution A: Increase limits temporarily in wp-config.php**

Add this to `wp-config.php` (before the "That's all" line):

```php
@ini_set('upload_max_filesize', '64M');
@ini_set('post_max_size', '64M');
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '300');
```

**Solution B: Use FTP/SFTP method instead**

### Issue: Still getting "Failed to create configuration"

**Possible causes:**

1. **Plugin not actually updated**
   - Check file modification dates in FTP
   - Verify `wp-content/plugins/ma-deal-room/src/Models/MLSConfig.php` exists

2. **Plugin not activated after upload**
   - Must deactivate and reactivate for activation hook to run

3. **Caching issues**
   - Clear WordPress object cache
   - Clear PHP opcache (restart PHP-FPM if available)
   - Hard refresh browser (Ctrl+Shift+R)

4. **Old plugin still in memory**
   - Restart PHP-FPM: `sudo service php-fpm restart` or `sudo service php8.1-fpm restart`

**Debug:**

Check if repository exists:

```php
// Add to wp-config.php temporarily
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Then try to save MLS config and check wp-content/debug.log
```

### Issue: Task definitions not populated (count = 0)

**Cause:** Activation hook didn't run

**Solution:**

1. Deactivate plugin
2. Activate plugin (this runs activation hook)

**Or manually trigger:**

```php
// Via wp-admin or SSH
wp eval 'ma_deal_room_sync_task_definitions();'
```

### Issue: 403 errors still appearing

**Cause:** Browser cache or old code still loaded

**Solution:**

1. Hard refresh browser (Ctrl+Shift+R or Cmd+Shift+R)
2. Clear browser cache
3. Log out and log back in to WordPress
4. Restart PHP-FPM if available

### Issue: "Class not found" errors

**Cause:** PHP autoloader cache or opcache

**Solution:**

```bash
# If using Composer autoloader
cd wp-content/plugins/ma-deal-room
composer dump-autoload

# Restart PHP-FPM
sudo service php-fpm restart
# OR
sudo service php8.1-fpm restart
```

---

## Rollback Plan (If Something Goes Wrong)

If the update causes issues:

### Quick Rollback

1. Go to **Plugins > Installed Plugins**
2. **Deactivate** MA Deal Room
3. Via FTP/SFTP:
   - Delete `wp-content/plugins/ma-deal-room/`
   - Rename `wp-content/plugins/ma-deal-room-old-backup/` to `ma-deal-room`
4. **Activate** the old plugin

### Full Restore

Restore from your backup created in Step 2.

---

## Post-Deployment Checklist

After deployment, verify these on live site:

- [ ] WordPress plugin activated successfully
- [ ] No fatal errors in WordPress debug log
- [ ] MLS tables exist in database
- [ ] 276 task definitions in database
- [ ] Dashboard loads and displays data
- [ ] Transactions page shows list
- [ ] Analytics page displays charts
- [ ] Task library shows 276 definitions
- [ ] **MLS configuration form opens**
- [ ] **MLS test connection succeeds**
- [ ] **MLS configuration saves successfully** ← MAIN FIX
- [ ] No 403 errors in browser console
- [ ] No null reference errors in logs

---

## What Changes on Live Site

### Files Added:
- `src/Models/MLSConfig.php` (new model)
- `src/Repositories/MLSConfigRepository.php` (new repository)

### Files Modified:
- `src/Core/Plugin.php` (registered MLS repository)
- `ma-deal-room.php` (task definitions sync in activation)
- `src/REST/Controllers/BaseController.php` (admin callback, null checks)
- `src/REST/Controllers/AnalyticsController.php` (removed override)
- `src/REST/Controllers/MLSController.php` (removed override, fixed providers)
- `src/Services/Integration/MLS/BridgeClient.php` (fixed errors)
- `src/Services/Integration/MLS/MLSClientFactory.php` (server_token field)

### Database Changes:
- MLS tables created (migrations 021-023)
- 276 task definitions inserted
- 7 templates synced
- WordPress options updated

---

## Support

If you encounter any issues during deployment:

1. Check WordPress debug log: `wp-content/debug.log`
2. Check PHP error log (location varies by hosting)
3. Check browser console for JavaScript errors
4. Verify plugin files uploaded correctly via FTP

**Common file to verify exists:**
`wp-content/plugins/ma-deal-room/src/Repositories/MLSConfigRepository.php`

If this file doesn't exist, the upload didn't complete correctly.

---

## Summary

**Before Deployment:**
- ❌ MLS configuration fails on live site
- ❌ Missing MLSConfigRepository

**After Deployment:**
- ✅ MLS configuration saves successfully
- ✅ All 5 fixes applied
- ✅ 276 task definitions auto-synced
- ✅ Production-ready plugin

**Plugin Version:** 1.0.0
**ZIP File:** `ma-deal-room-v1.0.0.zip` (51.45 MB)
**Total Files:** 4,757
**Status:** ✅ Ready for live deployment
