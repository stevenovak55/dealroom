# MLS Configuration Fix - Deployment Guide

## Summary

Fixed MLS configuration functionality to properly work with Bridge Interactive API. The dev site is now fully functional, and this guide provides instructions to deploy the fixes to the live site.

---

## Files Modified

### Backend PHP Files

1. **`/ma-deal-room/src/Services/Integration/MLS/BridgeClient.php`**
   - **Line 772-782**: Fixed array-to-string conversion error in error handling
   - **Line 205-241**: Updated `testConnection()` method to use simple `/Property` endpoint without `$top` parameter
   - **Added**: Detailed error logging for debugging

2. **`/ma-deal-room/src/REST/Controllers/MLSController.php`**
   - **Line 697-730**: Updated `get_providers()` method to return proper array format expected by frontend
   - **Line 175**: Removed invalid `nonce_callback` parameter from test-connection route

3. **`/ma-deal-room/src/Services/Integration/MLS/MLSClientFactory.php`**
   - **Line 234-262**: Added `server_token` field to Bridge provider configuration
   - **Line 240-242**: Updated placeholder text to show full OData URL format
   - **Note**: Line 346 already maps `server_token` to `access_token` correctly

### Frontend TypeScript Files

4. **`/ma-deal-room/assets/admin/src/components/MLSConfigurationForm.tsx`**
   - **Line 38-47**: Added defensive programming to ensure providers array is always valid

### Database Migrations

These migrations already exist in the repository and will be automatically run when the plugin is activated:

- **`021_create_mls_config_table.sql`** - Creates MLS configuration table
- **`022_add_mls_fields_to_transactions.sql`** - Adds MLS fields to transactions
- **`023_create_mls_sync_log_table.sql`** - Creates MLS sync log table

---

## Deployment Options

### Option 1: Git Pull (Recommended if using Git on live site)

```bash
# SSH into live site
cd /path/to/wordpress/wp-content/plugins/ma-deal-room

# Pull latest changes
git pull origin main

# If frontend assets were rebuilt, copy them
# (Check if assets/admin/build or assets/admin/dist exists with recent dates)

# Deactivate and reactivate plugin to run migrations
wp plugin deactivate ma-deal-room
wp plugin activate ma-deal-room
```

### Option 2: Manual File Upload via FTP/SFTP

Upload the following files to your live site:

**PHP Files:**
- `wp-content/plugins/ma-deal-room/src/Services/Integration/MLS/BridgeClient.php`
- `wp-content/plugins/ma-deal-room/src/REST/Controllers/MLSController.php`
- `wp-content/plugins/ma-deal-room/src/Services/Integration/MLS/MLSClientFactory.php`

**Database Migrations (if not already present):**
- `wp-content/plugins/ma-deal-room/database/migrations/021_create_mls_config_table.sql`
- `wp-content/plugins/ma-deal-room/database/migrations/022_add_mls_fields_to_transactions.sql`
- `wp-content/plugins/ma-deal-room/database/migrations/023_create_mls_sync_log_table.sql`
- `wp-content/plugins/ma-deal-room/database/migrations/rollback_021.sql`
- `wp-content/plugins/ma-deal-room/database/migrations/rollback_022.sql`
- `wp-content/plugins/ma-deal-room/database/migrations/rollback_023.sql`

**Frontend Assets (if rebuilt):**
- `wp-content/plugins/ma-deal-room/assets/admin/src/components/MLSConfigurationForm.tsx`

**Then via WordPress Admin:**
1. Go to Plugins
2. Deactivate "MA Deal Room" plugin
3. Activate "MA Deal Room" plugin
4. This will automatically run all pending migrations

### Option 3: Manual Migration via Docker (For Dev Site Pattern)

If you need to manually run migrations on the live site (similar to what we did on dev):

```bash
# Access the WordPress container (adjust container name as needed)
docker exec -it <wordpress-container-name> bash

# Inside the container, create a migration runner script
cat > /tmp/run-mls-migrations.php << 'EOF'
<?php
require_once('/var/www/html/wp-load.php');

if (class_exists('MADealRoom\Database\Migrator')) {
    $migrator = new MADealRoom\Database\Migrator();
    $results = $migrator->run();

    echo "Migration Results:\n";
    print_r($results);
} else {
    echo "Error: Migrator class not found. Make sure plugin is active.\n";
}
EOF

# Run the migration script
php /tmp/run-mls-migrations.php

# Clean up
rm /tmp/run-mls-migrations.php
```

---

## Verification Steps

After deployment, verify the fix works:

### 1. Test MLS Configuration Form

1. Log into WordPress Admin on live site
2. Navigate to **MA Deal Room > Settings > MLS Configuration**
3. Click **Add MLS Configuration**
4. Select **Bridge Interactive** as provider type
5. Fill in the credentials:
   - **Configuration Name**: MLS PIN Massachusetts
   - **API Base URL**: `https://api.bridgedataoutput.com/api/v2/OData/shared_mlspin_41854c5`
   - **Server Token**: `1c69fed3083478d187d4ce8deb8788ed`
6. Click **Test Connection**
7. Should see: ✅ **Connection Successful**
8. Click **Create**
9. Should see configuration saved successfully

### 2. Test Template Sync

1. In WordPress Admin, go to **MA Deal Room > Settings**
2. Scroll to **System Templates** section
3. Click **Sync System Templates** button
4. Should see: ✅ **Sync Successful**
5. Check that no 404 or 500 errors appear in browser console

### 3. Verify Database Tables

Run this in your database to confirm tables exist:

```sql
SHOW TABLES LIKE 'wp_ma_deal_mls_%';
```

Should return:
- `wp_ma_deal_mls_config`
- `wp_ma_deal_mls_sync_log`

---

## Expected Behavior

### MLS Configuration Form
- **Provider Selection**: Shows "Bridge Interactive" with proper description
- **Fields Shown**: API Base URL, Server Token, Client ID, Client Secret
- **Test Connection**: Successfully connects to Bridge API and returns property count
- **Save**: Creates configuration in `wp_ma_deal_mls_config` table with encrypted credentials

### Template Sync
- **GET /wp-json/ma-deal-room/v1/settings**: Returns 200 with account settings
- **POST /wp-json/ma-deal-room/v1/templates/sync-system**: Returns 200 with sync results
- **Console**: No 404 or 500 errors

---

## Troubleshooting

### Issue: "Connection Failed. Request failed with status code 400"

**Causes:**
1. Old version of BridgeClient.php with array-to-string error
2. Old version of MLSClientFactory.php without server_token field

**Solution:**
- Verify you've uploaded the updated BridgeClient.php and MLSClientFactory.php files
- Clear any PHP opcode cache (if applicable): `wp cache flush`

### Issue: "Failed to create configuration"

**Cause:** Database table `wp_ma_deal_mls_config` doesn't exist

**Solution:**
1. Check if migration 021 was run:
   ```sql
   SELECT * FROM wp_ma_deal_migrations WHERE migration_number = '021';
   ```
2. If not found, deactivate and reactivate the plugin
3. Or manually run migrations using Option 3 above

### Issue: Template Sync Shows 404 or 500 Errors

**Causes:**
1. Plugin not activated properly
2. REST API routes not registered
3. Missing database tables

**Solution:**
1. Deactivate and reactivate the plugin
2. Check WordPress error logs
3. Verify all controllers are registered in Plugin.php (they should be by default)

### Issue: "Array to string conversion" in Logs

**Cause:** Old version of BridgeClient.php

**Solution:**
- Verify lines 772-782 of BridgeClient.php contain the type checking code:
  ```php
  if (is_array($error_message)) {
      $error_message = json_encode($error_message);
  }
  ```

---

## Migration Details

### Migration 021: Create MLS Config Table
Creates `wp_ma_deal_mls_config` table to store MLS provider configurations with encrypted credentials.

### Migration 022: Add MLS Fields to Transactions
Adds MLS-related fields to the transactions table for linking to MLS listings.

### Migration 023: Create MLS Sync Log Table
Creates `wp_ma_deal_mls_sync_log` table to track MLS data synchronization history.

---

## Files Changed Summary

| File | Lines Changed | Description |
|------|---------------|-------------|
| BridgeClient.php | 772-782, 205-241 | Fixed error handling and test connection |
| MLSController.php | 697-730, 175 | Fixed provider data format |
| MLSClientFactory.php | 234-262 | Added server_token field |
| MLSConfigurationForm.tsx | 38-47 | Added defensive checks |

---

## Success Criteria

✅ MLS configuration form loads without errors
✅ Bridge Interactive provider shows server_token field
✅ Test connection succeeds with user's credentials
✅ Configuration saves to database successfully
✅ Template sync works without 404/500 errors
✅ All three MLS migrations (021-023) are applied

---

## Support

If you encounter issues after deployment:

1. Check WordPress debug log: `wp-content/debug.log`
2. Check browser console for JavaScript errors
3. Verify all migrations ran:
   ```sql
   SELECT * FROM wp_ma_deal_migrations ORDER BY id DESC LIMIT 10;
   ```
4. Check plugin activation status:
   ```bash
   wp plugin list | grep ma-deal-room
   ```

---

**Deployment Date**: 2025-11-02
**Plugin Version**: 1.0.0
**Tested On**: Dev site with Bridge Interactive API credentials
**Status**: ✅ Ready for production deployment
