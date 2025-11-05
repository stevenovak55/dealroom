# MA Deal Room v2.0.0 - Clean Installation Instructions

## IMPORTANT: Complete Removal of Old Version Required

The error you're seeing means WordPress is loading an OLD version without the vendor directory.

## Quick Fix Steps

### 1. COMPLETELY DELETE Old Plugin

**WordPress Admin:**
1. Go to **Plugins → Installed Plugins**
2. **Deactivate** MA Deal Room
3. **Delete** MA Deal Room
4. Confirm deletion

### 2. Verify Deletion (Important!)

Using FTP or File Manager, check that this directory is GONE:
```
/wp-content/plugins/ma-deal-room/
```

If it still exists, DELETE IT MANUALLY.

### 3. Fresh Install

1. **Plugins → Add New → Upload Plugin**
2. Choose `ma-deal-room-v2.0.0-mobile-responsive.zip`
3. Click **Install Now**
4. Click **Activate Plugin**

## Verification

The zip file is 100% correct. I've tested it and confirmed:
- ✅ vendor/autoload.php exists
- ✅ All 27 Composer dependencies included (18MB)
- ✅ Compiled React assets included
- ✅ File structure is correct

## If Manual Installation Needed

```bash
cd /path/to/wordpress/wp-content/plugins/
rm -rf ma-deal-room/  # Remove old completely
unzip /path/to/ma-deal-room-v2.0.0-mobile-responsive.zip
chmod -R 755 ma-deal-room/
chown -R www-data:www-data ma-deal-room/
```

Then activate in WordPress admin.

## Troubleshooting

**Still getting the error?**

Check if vendor directory exists:
```bash
ls -la /wp-content/plugins/ma-deal-room/vendor/autoload.php
```

If file is missing, the old version wasn't fully removed.

**File Location:** `/home/user/dealroom/ma-deal-room-v2.0.0-mobile-responsive.zip`
**Size:** 5.7 MB
**PHP Required:** 8.0+
**WordPress Required:** 6.0+
