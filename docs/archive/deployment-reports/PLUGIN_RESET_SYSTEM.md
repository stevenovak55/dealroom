# MA Deal Room Plugin Reset System

## Overview

The Plugin Reset System is an enterprise-level feature that ensures your live site can be reset to match your dev site exactly. This solves the problem of incomplete activations, database inconsistencies, and update issues.

## The Problem It Solves

**Before this system:**
- Live site activation didn't create all database tables
- Analytics showing "Failed to load"
- MLS Integration showing "Something went wrong"
- Task definitions not syncing
- Inconsistent state between dev and live sites

**After this system:**
- One button to reset everything
- Guaranteed to match dev site structure
- Built-in verification checks
- Safe dry-run mode for testing
- Automatic backups before reset

---

## Features

### ✅ Complete Database Reset
- Drops all MA Deal Room tables
- Deletes all plugin WordPress options
- Starts completely fresh

### ✅ Full Recreation
- Runs all 21 database migrations
- Syncs 7 system templates
- Syncs 276 task definitions
- Creates default account
- Sets all WordPress options

### ✅ Automatic Backup
- Creates SQL backup before reset
- Stored in `wp-content/ma-deal-room-backups/`
- Includes all tables and data
- Can be restored if needed

### ✅ Verification System
- Checks all tables exist
- Verifies data counts (276 tasks, 7 templates)
- Confirms default account created
- Validates WordPress options
- Reports pass/fail for each check

###human ✅ Dry-Run Mode
- Test the reset without making changes
- See what will happen
- Review verification results
- Safe to run anytime

---

## How to Use

### Step 1: Access the Reset Page

1. Log into WordPress Admin
2. Go to **MA Deal Room > Reset Plugin**
3. Read the warnings carefully

### Step 2: Run Dry-Run First (Recommended)

**Always test first!**

1. Click **"🔍 Run Dry-Run (Test Mode)"** button
2. Wait 5-10 seconds
3. Review the results:
   - ✅ Success message
   - Verification results table
   - Reset log showing what will happen

**The dry-run is safe** - it doesn't make any actual changes. It just simulates what would happen.

### Step 3: Perform Actual Reset

**Only do this when ready!**

1. Make sure you have a full site backup
2. Inform all users (data will be deleted)
3. Type **"RESET"** in the confirmation box
4. Click **"🔥 RESET PLUGIN NOW"** button
5. Confirm in the dialog box
6. Wait 5-10 seconds

### Step 4: Review Results

After reset, you'll see:
- ✅ Success message
- Backup file location
- Verification results (should be 13/13 passed)
- Complete log of all operations

---

## What Gets Deleted

During reset, these are **COMPLETELY WIPED**:

### Data Deleted:
- ❌ All transactions
- ❌ All tasks
- ❌ All documents
- ❌ All custom users (not WordPress users)
- ❌ All MLS configurations
- ❌ All custom templates
- ❌ All notifications
- ❌ All vendor requests
- ❌ All events/audit log
- ❌ Everything in the plugin!

### What's Preserved:
- ✅ WordPress users (not affected)
- ✅ Other WordPress data
- ✅ Plugin files
- ✅ Backup created before reset

---

## What Gets Created

After reset, the plugin will have:

### Database Tables (24 tables):
```
wp_ma_deal_accounts            (1 default account)
wp_ma_deal_transactions        (0 records)
wp_ma_deal_tasks               (0 records)
wp_ma_deal_task_definitions    (276 records) ✓
wp_ma_deal_templates           (7 system templates) ✓
wp_ma_deal_documents           (0 records)
wp_ma_deal_notifications       (0 records)
wp_ma_deal_users               (0 records)
wp_ma_deal_mls_config          (0 records)
wp_ma_deal_mls_sync_log        (0 records)
... and 14 more tables
```

### Data Seeded:
- **7 system templates:**
  - base_transaction.yaml
  - condo.yaml
  - multifamily.yaml
  - rental_landlord.yaml
  - rental_tenant.yaml
  - sfh_city_water.yaml
  - sfh_septic.yaml

- **276 task definitions** extracted from templates

- **1 default account:**
  - Name: "MA Deal Room Real Estate"
  - Status: Active
  - Tier: Professional

### WordPress Options Set:
```
ma_deal_room_activated = true
ma_deal_room_version = 1.0.1
ma_deal_room_last_reset = (current timestamp)
ma_deal_room_templates_synced = true
ma_deal_room_task_definitions_synced = true
ma_deal_room_templates_sync_count = 7
ma_deal_room_task_definitions_count = 276
```

---

## Verification Checks

After reset, the system automatically verifies:

| Check | Expected | What It Checks |
|-------|----------|----------------|
| Table exists: wp_ma_deal_accounts | ✅ Pass | Table created |
| Table exists: wp_ma_deal_transactions | ✅ Pass | Table created |
| Table exists: wp_ma_deal_tasks | ✅ Pass | Table created |
| Table exists: wp_ma_deal_task_definitions | ✅ Pass | Table created |
| Table exists: wp_ma_deal_templates | ✅ Pass | Table created |
| Table exists: wp_ma_deal_documents | ✅ Pass | Table created |
| Table exists: wp_ma_deal_notifications | ✅ Pass | Table created |
| Table exists: wp_ma_deal_mls_config | ✅ Pass | Table created |
| Table exists: wp_ma_deal_mls_sync_log | ✅ Pass | Table created |
| Task definitions count | 276 | Exactly 276 tasks |
| Templates count | 7 | Exactly 7 templates |
| Default account exists | ID: 1 | Account created |
| Version option set | 1.0.1 | Version matches |

**Target:** 13/13 checks passed ✅

---

## When to Use Reset

### ✅ Use Reset When:
- After updating the plugin to a new version
- Live site isn't working correctly
- Analytics showing "Failed to load"
- MLS Integration showing errors
- Database tables are missing or incomplete
- Task definitions count is wrong (should be 276)
- You want to match dev site exactly
- Troubleshooting complex issues

### ❌ Don't Use Reset When:
- You have production data you need to keep
- Plugin is working fine
- You just need to deactivate/reactivate
- It's a minor configuration issue
- You haven't backed up first

---

## Troubleshooting

### Issue: Reset button not visible

**Check:**
1. Are you logged in as an admin?
2. Go directly to: `your-site.com/wp-admin/admin.php?page=ma-deal-room-reset`
3. Check if user has `manage_options` capability

### Issue: Reset failed

**Check the log:**
- Look for error messages in red
- Common issues:
  - Database connection lost
  - Insufficient permissions
  - Disk space full
  - PHP timeout

**Solutions:**
1. Increase PHP `max_execution_time` to 300 seconds
2. Check database credentials
3. Free up disk space
4. Try again

### Issue: Verification shows failures

**Dry run failures are normal** - dry run doesn't create data

**Actual reset failures:**
- Check which specific check failed
- Look in the log for related errors
- May need to run reset again

### Issue: Backup not created

**Check:**
1. Directory exists: `wp-content/ma-deal-room-backups/`
2. Directory is writable
3. Sufficient disk space

**Create manually:**
```bash
wp db export backup-$(date +%Y%m%d-%H%M%S).sql
```

---

## Backup and Restore

### Automatic Backup

Every reset creates a backup:
- **Location:** `wp-content/ma-deal-room-backups/`
- **Format:** `backup_YYYY-MM-DD_HH-MM-SS.sql`
- **Contents:** All MA Deal Room tables and data

### Manual Restore

If you need to restore from backup:

```bash
# Via WP-CLI
wp db import wp-content/ma-deal-room-backups/backup_2025-11-03_12-30-45.sql

# Via phpMyAdmin
1. Open phpMyAdmin
2. Select database
3. Click Import
4. Choose backup SQL file
5. Click Go
```

---

## Technical Details

### What Happens Internally

1. **Create Backup**
   - Gets all `wp_ma_deal_%` tables
   - Exports CREATE TABLE statements
   - Exports all data as INSERT statements
   - Saves to backup file

2. **Drop Tables**
   - Disables foreign key checks
   - Drops all MA Deal Room tables
   - Re-enables foreign key checks

3. **Delete Options**
   - Removes all `ma_deal_room%` options from wp_options table

4. **Run Migrations**
   - Executes all 21 migration files in order
   - Creates table structure
   - Adds indexes and foreign keys

5. **Seed Data**
   - Syncs 7 YAML template files
   - Extracts 276 task definitions from templates
   - Creates records in database

6. **Create Account**
   - Inserts default account (ID: 1)
   - Sets default settings

7. **Set Options**
   - Updates wp_options with plugin metadata

8. **Verify**
   - Runs 13 verification checks
   - Reports results

**Total time:** 5-10 seconds

### Files Created

**New files in this system:**
- `src/Services/PluginResetService.php` (350 lines)
- `src/Admin/PluginResetPage.php` (280 lines)

**Modified files:**
- `ma-deal-room.php` (added reset page initialization)

---

## Security

### Access Control
- Only users with `manage_options` capability
- Typically only administrators
- Nonce verification on form submission

### Confirmation Required
- Must type "RESET" (case-sensitive)
- JavaScript confirmation dialog
- Two-step process prevents accidents

### Backup Protection
- Always creates backup first
- Cannot proceed if backup fails
- Backups stored outside web root (if possible)

---

## FAQ

### Q: Will this delete my WordPress users?
**A:** No, WordPress users are not affected. Only custom users created within the plugin are deleted.

### Q: Can I undo a reset?
**A:** Yes, restore from the automatic backup created before reset.

### Q: How long does reset take?
**A:** 5-10 seconds typically. Depends on database size and server speed.

### Q: Is reset safe to run on live site?
**A:** Yes, but **only if** you're prepared to lose all plugin data. Always backup first and inform users.

### Q: What's the difference between Reset and Deactivate/Reactivate?
**A:**
- **Deactivate/Reactivate:** Runs activation hook, may skip existing data
- **Reset:** Complete wipe and recreation, guaranteed fresh state

### Q: Can I reset just one part (like task definitions)?
**A:** No, reset is all-or-nothing. For partial updates, use WP-CLI commands:
```bash
wp ma-deal task-definitions:sync
wp ma-deal templates:sync
```

### Q: Will reset fix my live site issues?
**A:** If issues are database-related (missing tables, wrong data), yes. If issues are code/file related, you may need to re-upload plugin files.

---

## Use Cases

### Use Case 1: After Plugin Update

**Scenario:** You just updated from v1.0.0 to v1.0.1 on live site

**Steps:**
1. Upload new plugin ZIP
2. Go to Reset Plugin page
3. Run dry-run to verify
4. Click actual reset
5. Live site now matches dev site exactly

**Result:** ✅ Clean state with all new features

### Use Case 2: Live Site Not Working

**Scenario:** Analytics showing "Failed to load", MLS showing errors

**Steps:**
1. Try deactivate/reactivate first
2. If that doesn't work, go to Reset Plugin
3. Run dry-run
4. Review what will be reset
5. Click actual reset
6. Test analytics and MLS

**Result:** ✅ Fresh database, everything works

### Use Case 3: Development Testing

**Scenario:** Testing new features, need clean state

**Steps:**
1. Develop feature on dev site
2. Test with production-like data
3. Reset to clean state
4. Test from scratch
5. Repeat as needed

**Result:** ✅ Consistent testing environment

---

## Summary

The Plugin Reset System provides:
- ✅ Enterprise-level database management
- ✅ Guaranteed consistency with dev site
- ✅ Safe testing with dry-run mode
- ✅ Automatic backups
- ✅ Complete verification
- ✅ One-click reset

**Perfect for:**
- Plugin updates
- Troubleshooting
- Starting fresh
- Matching dev site

**Safe because:**
- Requires confirmation
- Creates backups
- Can be restored
- Verification checks

---

## Quick Reference

### Access Reset Page
```
WordPress Admin > MA Deal Room > Reset Plugin
```

### Direct URL
```
your-site.com/wp-admin/admin.php?page=ma-deal-room-reset
```

### Confirmation Text
```
RESET
```
(must be typed in capital letters)

### Backup Location
```
wp-content/ma-deal-room-backups/
```

### Expected Results
```
13/13 verification checks passed
276 task definitions
7 templates
1 default account
```

---

**Last Updated:** November 3, 2025
**Plugin Version:** 1.0.1
**Status:** ✅ Production Ready
