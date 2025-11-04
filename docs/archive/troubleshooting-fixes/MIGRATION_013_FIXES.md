# Migration 013 Column Mismatch Fixes

## Problem Summary

Migration 013 (add_performance_indexes.sql) was failing because it tried to create indexes on columns that don't exist in the database tables. This caused the reset to stop at migration 11/21, preventing the remaining 10 migrations from running.

---

## Root Cause

Migration 013 had **4 column name mismatches** where the index creation statements referenced columns that were either:
1. Named differently in the actual table schema
2. Didn't exist at all

When MySQL tried to create an index on a non-existent column, it threw an error and the migration stopped.

---

## Fixes Applied

### Fix 1: Tasks - assigned_party_id ✅
**File:** `database/migrations/013_add_performance_indexes.sql` (Line 42-45)

**Error message:**
```
Key column 'assigned_to_party_id' doesn't exist in table
```

**What was wrong:**
```sql
-- BEFORE (INCORRECT):
CREATE INDEX `idx_tasks_assigned_status_due`
ON `wp_ma_deal_tasks` (`assigned_to_party_id`, `status`, `due_at`)
```

**What it should be:**
```sql
-- AFTER (CORRECT):
CREATE INDEX `idx_tasks_assigned_status_due`
ON `wp_ma_deal_tasks` (`assigned_party_id`, `status`, `due_at`)
```

**Explanation:** The tasks table uses `assigned_party_id`, not `assigned_to_party_id`.

---

### Fix 2: Notifications - user_id ✅
**File:** `database/migrations/013_add_performance_indexes.sql` (Line 80-83)

**Error message:**
```
Key column 'recipient_id' doesn't exist in table
```

**What was wrong:**
```sql
-- BEFORE (INCORRECT):
CREATE INDEX `idx_notifications_recipient_read_created`
ON `wp_ma_deal_notifications` (`recipient_id`, `recipient_type`, `is_read`, `created_at`)
```

**What it should be:**
```sql
-- AFTER (CORRECT):
CREATE INDEX `idx_notifications_user_read_created`
ON `wp_ma_deal_notifications` (`user_id`, `is_read`, `created_at`)
```

**Explanation:** The notifications table uses `user_id` to identify the recipient, not `recipient_id` and `recipient_type`.

---

### Fix 3: Notifications - entity_type/entity_id ✅
**File:** `database/migrations/013_add_performance_indexes.sql` (Line 85-88)

**What was wrong:**
```sql
-- BEFORE (INCORRECT):
CREATE INDEX `idx_notifications_transaction_type`
ON `wp_ma_deal_notifications` (`transaction_id`, `type`)
```

**What it should be:**
```sql
-- AFTER (CORRECT):
CREATE INDEX `idx_notifications_entity_type`
ON `wp_ma_deal_notifications` (`entity_type`, `entity_id`, `type`)
```

**Explanation:** The notifications table doesn't have a `transaction_id` column. Instead, it uses `entity_type` (enum: 'transaction', 'task', etc.) and `entity_id` to reference different entities.

---

### Fix 4: Documents - created_at instead of uploaded_at ✅
**File:** `database/migrations/013_add_performance_indexes.sql` (Line 108-111)

**What was wrong:**
```sql
-- BEFORE (INCORRECT):
CREATE INDEX `idx_documents_transaction_status_uploaded`
ON `wp_ma_deal_documents` (`transaction_id`, `status`, `uploaded_at`)
```

**What it should be:**
```sql
-- AFTER (CORRECT):
CREATE INDEX `idx_documents_transaction_created`
ON `wp_ma_deal_documents` (`transaction_id`, `created_at`)
```

**Explanation:** The documents table doesn't have:
- `status` column (only has `is_public` boolean)
- `uploaded_at` column (uses `created_at` instead)

---

## Expected Result After Fixes

With all 4 column mismatches fixed, migration 013 should now run successfully, allowing all 21 migrations to complete:

```
✅ Migration 001: SUCCESS
✅ Migration 002: SUCCESS
...
✅ Migration 011: SUCCESS
✅ Migration 012: SUCCESS
✅ Migration 013: SUCCESS ← Should work now!
✅ Migration 014: SUCCESS
...
✅ Migration 021: SUCCESS

✅ Reset Successful!
✅ Verification: 13/13 checks passed
✅ All 21 migrations ran successfully
```

---

## How to Test

1. **Download updated plugin:**
   - New file created: `ma-deal-room-v1.0.1.tar.gz` (50 MB)
   - Note: WordPress needs ZIP format, not TAR.GZ
   - You may need to convert this to ZIP on your local machine

2. **Upload to live site:**
   - WordPress Admin > Plugins > Add New > Upload Plugin
   - Select the ZIP file
   - Install (replace existing)

3. **Run reset:**
   - MA Deal Room > Reset Plugin
   - Type "RESET" in confirmation box
   - Click "RESET PLUGIN NOW"

4. **Verify results:**
   - Should see: "✅ Reset Successful!"
   - Verification: 13/13 checks passed
   - Log should show: "Ran 21 migrations" (not 11!)

---

## What This Fixes

### Before (Broken):
```
[ERROR] Migration 013: FAILED - Key column 'assigned_to_party_id' doesn't exist
[ERROR] Migrations completed with 1 failures out of 12 total
[ERROR] WARNING: Only 11 of 21 expected migrations ran!
[ERROR] Reset FAILED
```

### After (Working):
```
[INFO] Migration 001: SUCCESS
[INFO] Migration 002: SUCCESS
...
[INFO] Migration 013: SUCCESS
...
[INFO] Migration 021: SUCCESS
[SUCCESS] Migrations completed successfully - 21 total
[SUCCESS] Reset completed successfully
```

---

## Technical Details

### Files Modified:
- `database/migrations/013_add_performance_indexes.sql`
  - Fixed 4 column name references
  - All indexes now reference columns that actually exist

### Files Updated:
- `src/Services/PluginResetService.php`
  - Enhanced migration logging (shows individual status)
  - Skip verification in dry-run mode

### Documentation Updated:
- `RESET_ISSUE_FIX.md` - Added fix details
- `MIGRATION_013_FIXES.md` (this file) - Complete fix documentation

---

## Next Steps

1. **Convert TAR.GZ to ZIP** (if needed):
   ```bash
   # On your local machine (macOS/Linux):
   tar -xzf ma-deal-room-v1.0.1.tar.gz
   zip -r ma-deal-room-v1.0.1.zip ma-deal-room/

   # Or use a GUI tool like The Unarchiver + Finder
   ```

2. **Upload to live site** via WordPress admin

3. **Run reset** and verify all 21 migrations complete

4. **Test functionality:**
   - Analytics should load
   - MLS Integration should work
   - No more errors

---

## Verification Checklist

After reset completes successfully, you should have:

- ✅ 24 database tables created
- ✅ 21/21 migrations completed
- ✅ 276 task definitions synced
- ✅ 7 system templates synced
- ✅ 1 default account created
- ✅ 13/13 verification checks passed
- ✅ No errors in log
- ✅ Analytics page loads
- ✅ MLS Integration shows form

---

**Status:** All fixes applied, plugin rebuilt, ready for testing

**File:** `/home/snova/projects/dealroom/ma-deal-room-v1.0.1.tar.gz` (50 MB)

**Note:** You'll need to convert to ZIP format for WordPress upload.
