# Reset Issue Fix

## Problem

When running the actual reset (not dry-run), only **11 of 21 migrations** are running, causing verification to fail.

## Root Cause

The Migrator class stops processing migrations when it encounters an error (line 80: `break; // Stop on first error`). This means migration #12 is likely failing, which prevents migrations 13-21 from running.

## Fix Applied

I've updated the PluginResetService to:
1. ✅ Log each individual migration with its status (SUCCESS/FAILED/SKIPPED)
2. ✅ Show detailed error messages for failed migrations
3. ✅ List which migrations didn't run
4. ✅ Provide much better debugging information

## Migration 013 Column Mismatches Fixed

I've identified and fixed 4 column name mismatches in migration 013_add_performance_indexes.sql:

### Fix 1: Tasks assigned_party_id (Line 42-45)
**Error:** `Key column 'assigned_to_party_id' doesn't exist in table`
**Cause:** Column name was `assigned_to_party_id` instead of `assigned_party_id`
**Fix:** Changed to correct column name `assigned_party_id`

### Fix 2: Notifications user_id (Line 80-83)
**Error:** `Key column 'recipient_id' doesn't exist in table`
**Cause:** Tried to create index on `recipient_id` and `recipient_type` which don't exist
**Fix:** Changed to use actual column `user_id`

### Fix 3: Notifications entity columns (Line 85-88)
**Error:** Tried to index `transaction_id` which doesn't exist
**Cause:** Notifications table uses `entity_type` and `entity_id`, not `transaction_id`
**Fix:** Changed to use `entity_type`, `entity_id`, `type`

### Fix 4: Documents created_at (Line 108-111)
**Error:** Tried to index `status` and `uploaded_at` which don't exist
**Cause:** Documents table doesn't have `status` column and uses `created_at` not `uploaded_at`
**Fix:** Changed to use `transaction_id`, `created_at`

## Next Steps

### Option 1: Re-upload Plugin and Try Again (Recommended)

1. **Download updated plugin:**
   ```bash
   scp snova@server:/home/snova/projects/dealroom/ma-deal-room-v1.0.1.zip ~/Downloads/
   ```

2. **Upload to live site:**
   - WordPress Admin > Plugins > Add New > Upload Plugin
   - Select: ma-deal-room-v1.0.1.zip
   - Click: "Replace current with uploaded"

3. **Run reset again:**
   - MA Deal Room > Reset Plugin
   - Type: RESET
   - Click: RESET PLUGIN NOW

4. **Check the detailed log:**
   - This time you'll see exactly which migration failed
   - The log will show: "Migration XXX: FAILED - [error message]"

### Option 2: Manual Migration Check (If you want to investigate first)

Upload this diagnostic script to your live site to see which migrations are already applied:

```php
<?php
// check-migrations.php
require_once(__DIR__ . '/wp-load.php');

header('Content-Type: text/plain');

global $wpdb;

echo "=== Migration Status ===\n\n";

// Check if migrations table exists
$migrations_table = $wpdb->prefix . 'ma_deal_migrations';
$exists = $wpdb->get_var("SHOW TABLES LIKE '{$migrations_table}'");

if (!$exists) {
    echo "Migrations table doesn't exist yet.\n";
    exit;
}

// Get applied migrations
$applied = $wpdb->get_results(
    "SELECT migration_number, migration_name, applied_at FROM {$migrations_table} ORDER BY migration_number",
    ARRAY_A
);

echo "Applied migrations: " . count($applied) . "\n\n";

foreach ($applied as $migration) {
    echo "[{$migration['migration_number']}] {$migration['migration_name']}\n";
    echo "  Applied: {$migration['applied_at']}\n";
}

// List expected migrations
echo "\n=== Expected Migrations ===\n\n";

$migration_files = glob(dirname(__FILE__) . '/wp-content/plugins/ma-deal-room/database/migrations/*.sql');
$migration_files = array_filter($migration_files, function($file) {
    return strpos($file, 'rollback') === false;
});

sort($migration_files);

echo "Expected: " . count($migration_files) . "\n\n";

foreach ($migration_files as $file) {
    echo basename($file) . "\n";
}
```

## What Will Happen

With the updated plugin, when you run reset again, you'll get detailed output like:

```
[INFO] Starting plugin reset
[INFO] Dry run mode: NO
[SUCCESS] Created backup
[SUCCESS] Dropped 23 tables
[SUCCESS] Deleted 7 WordPress options
[INFO] Migration 001: SUCCESS
[INFO] Migration 002: SUCCESS
...
[INFO] Migration 011: SUCCESS
[ERROR] Migration 012: FAILED - Table 'wp_ma_deal_something' already exists
[ERROR] Migrations completed with 1 failures out of 12 total
[ERROR] WARNING: Only 11 of 21 expected migrations ran!
[ERROR] Missing migrations: 013_..., 014_..., 015_..., etc.
```

This will tell us exactly which migration is failing and why.

## Common Migration Failures

Based on the log showing "Ran 11 migrations", the 12th migration is likely failing. Here are the migration files in order:

1. 001_initial_schema.sql
2. 002_create_documents_table.sql
3. 003_create_notifications_table.sql
4. 006_create_modular_task_system.sql
5. 007_fix_task_due_calculations.sql
6. 008_add_loan_commitment_date.sql
7. 009_add_template_transaction_side.sql
8. 010_add_property_details_fields.sql
9. 011_create_user_system.sql
10. 012_create_notification_queue_table.sql  ← **Probably failing here**
11. 013_add_performance_indexes.sql
12. 014_create_rate_limits_table.sql
... and so on

**Most likely culprits:**
- Migration 012 (notification_queue_table) - might have foreign key issues
- Database permissions
- Disk space
- MySQL/MariaDB version compatibility

## Temporary Workaround

If the reset continues to fail, you can:

1. **Manually run problematic migrations:**
   - Find the failing migration file
   - Run it directly in phpMyAdmin
   - Then run reset again (it will skip already-applied migrations)

2. **Use WP-CLI to run migrations:**
   ```bash
   wp ma-deal migrate
   ```

3. **Restore from backup and try again:**
   - The reset created a backup before it started
   - Restore if needed: `wp db import wp-content/ma-deal-room-backups/backup_2025-11-03_17-42-23.sql`

## Expected Output After Fix

After uploading the updated plugin and running reset, you should see:

```
✅ Reset Successful!
✅ Verification: 13/13 checks passed
✅ All 21 migrations ran successfully
✅ Task definitions: 276
✅ Templates: 7
```

---

**Action Required:** Upload the updated plugin ZIP and run reset again to get detailed error information.
