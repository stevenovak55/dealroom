# Live Site Quick Fix Guide

## Symptoms You're Seeing

1. **Analytics page:** All cards say "Failed to load"
2. **MLS Integration page:** Shows "Something went wrong" error

## Most Likely Causes

Based on these symptoms, the issue is likely:

1. ✅ **Plugin not fully activated** (most common)
2. ✅ **Database tables missing or empty**
3. ✅ **REST API routes not registered**
4. ✅ **JavaScript build not deployed**

---

## STEP 1: Upload Diagnostic Script (DO THIS FIRST)

This will tell us exactly what's wrong.

### Upload the diagnostic script:

1. **Download from server:**
   ```bash
   scp snova@your-server:/home/snova/projects/dealroom/diagnose-live-site.php ~/Downloads/
   ```

2. **Upload to live site:**
   - Via FTP/SFTP: Upload to WordPress root directory (same folder as `wp-config.php`)
   - Via WordPress Admin: Not applicable (PHP files can't be uploaded this way)

3. **Access via browser:**
   ```
   https://novak.realtor/diagnose-live-site.php
   ```

4. **Copy ALL the output** and send it to me

### What this script checks:

- ✅ Plugin activation status
- ✅ Plugin version
- ✅ Critical files existence
- ✅ Database tables and record counts
- ✅ REST API routes registration
- ✅ Service container status
- ✅ API endpoint responses
- ✅ Recent PHP errors

---

## STEP 2: Try These Quick Fixes

### Fix #1: Deactivate and Reactivate Plugin

**This fixes 90% of issues!**

1. Log into WordPress Admin
2. Go to **Plugins > Installed Plugins**
3. Find **MA Deal Room**
4. Click **Deactivate**
5. Wait 2 seconds
6. Click **Activate**

**Why this works:**
- Runs activation hooks
- Creates missing database tables
- Syncs task definitions (276 tasks)
- Registers REST API routes
- Initializes services

### Fix #2: Clear All Caches

**Do this after reactivating:**

1. **Clear WordPress cache:**
   - If using a caching plugin (WP Super Cache, W3 Total Cache, etc.):
     - Go to plugin settings
     - Click "Clear All Cache" or "Purge Cache"

2. **Clear browser cache:**
   - Press **Ctrl+Shift+Del** (Windows) or **Cmd+Shift+Del** (Mac)
   - Select "Cached images and files"
   - Click "Clear data"

3. **Hard refresh the page:**
   - Press **Ctrl+Shift+R** (Windows) or **Cmd+Shift+R** (Mac)

### Fix #3: Check Browser Console

**This tells us which API call is failing:**

1. Open the problematic page (Analytics or MLS Integration)
2. Press **F12** to open Developer Tools
3. Click the **Console** tab
4. Look for red error messages
5. Click the **Network** tab
6. Reload the page
7. Look for failed requests (shown in red)
8. Click on failed request to see details

**Common errors you might see:**
- `403 Forbidden` = Permission issue
- `404 Not Found` = Route not registered
- `500 Internal Server Error` = PHP error
- `Failed to fetch` = Network/CORS issue

---

## STEP 3: Most Common Issues & Solutions

### Issue: "Failed to load" on Analytics cards

**Cause:** REST API endpoints not returning data

**Diagnostic checks:**
```
1. Are database tables created? (should have data)
2. Are REST API routes registered? (should see /ma-deal-room/v1/analytics/*)
3. Are there any PHP errors in error log?
```

**Solutions:**
1. Deactivate and reactivate plugin
2. Check if you're logged in as an admin
3. Check browser console for 403 errors

### Issue: "Something went wrong" on MLS Integration

**Cause:** JavaScript error in React application

**Diagnostic checks:**
```
1. Open browser console (F12)
2. Look for red JavaScript errors
3. Check if /ma-deal-room/v1/mls routes are registered
```

**Solutions:**
1. Clear browser cache and hard refresh
2. Deactivate and reactivate plugin
3. Check if plugin files fully uploaded (JavaScript bundle missing?)

### Issue: Empty database tables

**Symptom:** Tables exist but have 0 records

**Check:**
```sql
SELECT COUNT(*) FROM wp_ma_deal_task_definitions;
-- Should return: 276

SELECT COUNT(*) FROM wp_ma_deal_templates;
-- Should return: 7
```

**Solution:**
Deactivate and reactivate plugin (runs activation hook which syncs data)

### Issue: REST API routes not registered

**Symptom:** Diagnostic script shows "MA Deal Room namespace: NO"

**Cause:** Plugin activation hook didn't run, or plugin didn't load

**Solution:**
1. Check if plugin is active: `Plugins > Installed Plugins`
2. If active, deactivate and reactivate
3. If not active, activate it
4. Check for fatal errors in error log

---

## STEP 4: If Quick Fixes Don't Work

### Check WordPress Error Log

**Location:** `wp-content/debug.log` (if debug mode enabled)

**Enable debug mode** (add to `wp-config.php`):
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Look for:**
- Fatal errors
- Missing class errors
- Database errors
- Permission errors

### Check File Permissions

**Via SSH:**
```bash
# Check plugin directory permissions
ls -la wp-content/plugins/ma-deal-room/

# Set correct permissions if needed
find wp-content/plugins/ma-deal-room -type d -exec chmod 755 {} \;
find wp-content/plugins/ma-deal-room -type f -exec chmod 644 {} \;
```

### Verify Plugin Files Uploaded

**Critical files that must exist:**

```
wp-content/plugins/ma-deal-room/
  ├── ma-deal-room.php  (main plugin file)
  ├── assets/
  │   └── admin/
  │       └── dist/  (JavaScript bundle - IMPORTANT!)
  │           ├── main.js
  │           └── style.css
  ├── src/
  │   ├── Core/Plugin.php
  │   ├── REST/Controllers/AnalyticsController.php
  │   └── REST/Controllers/MLSController.php
  └── vendor/  (Composer dependencies)
```

**Most common missing file:** `assets/admin/dist/main.js`
- This is the React application
- If missing, frontend won't work
- Check if it uploaded (51 MB ZIP might have timed out)

---

## STEP 5: Nuclear Option (Last Resort)

### Complete Plugin Reinstall

**Only if nothing else works!**

1. **Backup first:**
   - Export database: `Tools > Export`
   - Or via phpMyAdmin

2. **Deactivate plugin:**
   - `Plugins > Deactivate MA Deal Room`

3. **Delete plugin:**
   - `Plugins > Delete`
   - **NOTE:** This will NOT delete your data (transactions, tasks, etc.)
   - Data is safe in database

4. **Re-upload fresh copy:**
   - `Plugins > Add New > Upload Plugin`
   - Select `ma-deal-room-v1.0.1.zip`
   - Install and activate

5. **Verify:**
   - Check analytics page
   - Check MLS integration page
   - Run diagnostic script

---

## Expected Results After Fixing

### Analytics Page Should Show:

- ✅ Transaction overview card (with numbers)
- ✅ Agent performance leaderboard (with data)
- ✅ Task completion chart (with graph)
- ✅ Transaction trends (with timeline)
- ✅ Vendor activity widget (with stats)

### MLS Integration Page Should Show:

- ✅ MLS provider selection
- ✅ Configuration form
- ✅ Test connection button
- ✅ Save configuration button

### Diagnostic Script Should Show:

```
Plugin active: YES ✓
Plugin version: 1.0.1
Critical files: All ✓
Database tables: All exist with data ✓
REST API routes: ma-deal-room/v1 registered ✓
Service container: All services registered ✓
API endpoint tests: All return 200 OK ✓
```

---

## Common Mistakes

### ❌ Mistake #1: Not reactivating after upload

**Wrong:**
- Upload new plugin ZIP
- Plugin shows as active
- Don't touch it

**Right:**
- Upload new plugin ZIP
- Deactivate plugin
- Activate plugin
- (This runs activation hooks!)

### ❌ Mistake #2: Not clearing browser cache

**Wrong:**
- Upload new files
- Refresh page
- Still seeing old version

**Right:**
- Upload new files
- Clear browser cache (Ctrl+Shift+Del)
- Hard refresh (Ctrl+Shift+R)

### ❌ Mistake #3: Partial file upload

**Wrong:**
- Upload times out
- Assume it worked
- Some files missing

**Right:**
- Check file modification dates in FTP
- Verify critical files exist
- Re-upload if needed

---

## What to Send Me

If the quick fixes don't work, please send:

1. **Full output from diagnostic script** (`diagnose-live-site.php`)
2. **Browser console errors** (screenshot or copy/paste)
3. **Network tab showing failed requests** (screenshot)
4. **Last 50 lines from error log:**
   ```bash
   tail -50 wp-content/debug.log
   ```

With this information, I can pinpoint the exact issue!

---

## Quick Command Reference

### Via SSH:

```bash
# Deactivate plugin
wp plugin deactivate ma-deal-room

# Activate plugin
wp plugin activate ma-deal-room

# Check plugin status
wp plugin list --name=ma-deal-room

# Check database tables
wp db query "SHOW TABLES LIKE 'wp_ma_deal%';"

# Check task definitions count
wp db query "SELECT COUNT(*) FROM wp_ma_deal_task_definitions;"

# View recent errors
tail -50 wp-content/debug.log | grep -i "ma.deal"
```

### Via WordPress Admin:

1. **Activate/Deactivate:** `Plugins > Installed Plugins`
2. **Check errors:** `Tools > Site Health > Info > Server`
3. **Export database:** `Tools > Export`

---

## Success Checklist

After fixing, verify these:

- [ ] Plugin shows as "Active" in Plugins menu
- [ ] Plugin version shows "1.0.1"
- [ ] Diagnostic script shows all green checkmarks ✓
- [ ] Analytics page loads with data (not "Failed to load")
- [ ] MLS Integration page shows configuration form (not error message)
- [ ] Browser console shows no red errors
- [ ] Network tab shows all API calls return 200 OK
- [ ] No PHP errors in error log

---

**Start with STEP 1 (diagnostic script) and let me know what it shows!**

The diagnostic output will tell us exactly what's wrong and we can fix it from there.
