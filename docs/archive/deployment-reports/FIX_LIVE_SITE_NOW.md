# Fix Live Site - Quick Action Plan

## The Problem

Your live site is showing a **500 Internal Server Error** when trying to save MLS configuration. This is likely because:

1. The new plugin files (MLSConfigRepository, MLSConfig model) didn't upload correctly
2. PHP cache (opcache) hasn't been cleared
3. Files uploaded but WordPress can't find them due to cache

---

## Quick Fix Steps

### Step 1: Verify Files on Live Server

SSH into your live server and run:

```bash
# Check if critical files exist
ls -la /path/to/wordpress/wp-content/plugins/ma-deal-room/src/Models/MLSConfig.php
ls -la /path/to/wordpress/wp-content/plugins/ma-deal-room/src/Repositories/MLSConfigRepository.php

# Check main plugin file modification date
ls -lh /path/to/wordpress/wp-content/plugins/ma-deal-room/ma-deal-room.php

# Check version in file
head -20 /path/to/wordpress/wp-content/plugins/ma-deal-room/ma-deal-room.php | grep Version
```

**Expected:**
- MLSConfig.php and MLSConfigRepository.php should **exist**
- ma-deal-room.php should show recent modification date (today)
- Version should be **1.0.1** (or 1.0.0 with recent date)

### Step 2: Clear PHP Cache

```bash
# Clear OPcache (choose the command that works on your server)
sudo service php8.1-fpm restart
# OR
sudo service php-fpm restart
# OR
sudo service php8.0-fpm restart
# OR (for Apache with mod_php)
sudo service apache2 restart
```

### Step 3: Upload Diagnostic Script

1. **Download from server:**
   ```bash
   scp snova@server:/home/snova/projects/dealroom/check-live-site-errors.php ~/Downloads/
   ```

2. **Upload to live site root:**
   Upload `check-live-site-errors.php` to your WordPress root directory

3. **Access via browser:**
   ```
   https://novak.realtor/check-live-site-errors.php
   ```

4. **Check output for:**
   ```
   MLSConfig Model: EXISTS
   MLSConfigRepository: EXISTS
   MLS Config Repository: REGISTERED ✓
   ```

### Step 4: If Files Missing, Re-upload Plugin

**Option A: Fresh Upload (Recommended)**

1. **Download latest version:**
   ```bash
   scp snova@server:/home/snova/projects/dealroom/ma-deal-room-v1.0.1.zip ~/Downloads/
   ```

2. **Delete old plugin via FTP:**
   - Connect to live site via FTP/SFTP
   - Navigate to `wp-content/plugins/`
   - Rename `ma-deal-room` to `ma-deal-room-old-backup`

3. **Upload via WordPress Admin:**
   - Go to Plugins > Add New > Upload Plugin
   - Select `ma-deal-room-v1.0.1.zip`
   - Install
   - Activate

**Option B: Manual File Upload via FTP**

If the ZIP is too large:

1. Extract `ma-deal-room-v1.0.1.zip` on your computer

2. Upload these specific files via FTP:
   ```
   wp-content/plugins/ma-deal-room/src/Models/MLSConfig.php
   wp-content/plugins/ma-deal-room/src/Repositories/MLSConfigRepository.php
   wp-content/plugins/ma-deal-room/src/Core/Plugin.php
   wp-content/plugins/ma-deal-room/ma-deal-room.php
   ```

3. Set permissions:
   ```bash
   chmod 644 wp-content/plugins/ma-deal-room/src/Models/MLSConfig.php
   chmod 644 wp-content/plugins/ma-deal-room/src/Repositories/MLSConfigRepository.php
   chmod 644 wp-content/plugins/ma-deal-room/src/Core/Plugin.php
   chmod 644 wp-content/plugins/ma-deal-room/ma-deal-room.php
   ```

### Step 5: Regenerate Autoload (If Using Composer)

```bash
cd /path/to/wordpress/wp-content/plugins/ma-deal-room
composer dump-autoload -o
```

### Step 6: Clear WordPress Cache

```bash
# Via WP-CLI
wp cache flush

# Or delete cache directory
rm -rf /path/to/wordpress/wp-content/cache/*
```

### Step 7: Test MLS Configuration

1. Go to **MA Deal Room > MLS Configuration**
2. Click **Add MLS Configuration**
3. Fill in details and click **Save**
4. Should work now! ✅

---

## If Still Not Working

### Check Error Log

```bash
# WordPress debug log
tail -50 /path/to/wordpress/wp-content/debug.log

# Or PHP error log (location varies)
tail -50 /var/log/php/error.log
tail -50 /var/log/apache2/error.log
tail -50 /var/log/nginx/error.log
```

**Look for:**
```
Service 'mls_config_repository' not found in container
Class 'MADealRoom\Models\MLSConfig' not found
Class 'MADealRoom\Repositories\MLSConfigRepository' not found
```

### Force Plugin Reactivation

```bash
# Via WP-CLI
wp plugin deactivate ma-deal-room
wp plugin activate ma-deal-room

# Or via WordPress Admin
# Plugins > Deactivate > Activate
```

### Check if Plugin is Actually Updated

```bash
# Check what version WordPress sees
wp plugin list --name=ma-deal-room --fields=name,version
```

### Nuclear Option: Delete and Reinstall

**Only if nothing else works!**

```bash
# 1. Backup database first!
wp db export backup-before-reinstall.sql

# 2. Deactivate plugin
wp plugin deactivate ma-deal-room

# 3. Delete plugin files
rm -rf /path/to/wordpress/wp-content/plugins/ma-deal-room

# 4. Upload fresh copy via WordPress Admin
# Plugins > Add New > Upload > ma-deal-room-v1.0.1.zip

# 5. Activate
wp plugin activate ma-deal-room
```

---

## Quick Checklist

After uploading, verify these:

- [ ] MLSConfig.php exists on server
- [ ] MLSConfigRepository.php exists on server
- [ ] ma-deal-room.php shows Version 1.0.1
- [ ] PHP-FPM restarted
- [ ] WordPress cache cleared
- [ ] Plugin reactivated
- [ ] Diagnostic script shows "MLS repository: REGISTERED"
- [ ] Can save MLS configuration without 500 error

---

## Emergency Contact

If you're still stuck, share:

1. Output of diagnostic script (`check-live-site-errors.php`)
2. Last 50 lines of error log
3. Screenshot of the 500 error from browser console (Network tab)

**The diagnostic script will tell us exactly what's wrong!**

---

## Most Likely Cause

Based on your error, the most likely issue is:

**PHP cache hasn't been cleared after upload**

Try this first:
```bash
sudo service php-fpm restart
# Then test MLS configuration again
```

90% of the time, this fixes it!
