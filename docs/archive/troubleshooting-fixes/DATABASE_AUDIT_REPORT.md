# MA Deal Room Plugin - Database Audit Report

**Date**: 2025-11-03
**Plugin Version**: 1.0.7
**Audited by**: Data Expert (AI Agent)
**Location**: `/home/snova/projects/dealroom/ma-deal-room/`

---

## Executive Summary

This comprehensive audit analyzed all database migrations, table schemas, foreign key relationships, and model-schema alignment for the MA Deal Room WordPress plugin. The audit identified **CRITICAL ISSUES** that must be resolved before the plugin can function correctly on a fresh WordPress installation.

### Overall Assessment: **CRITICAL ISSUES FOUND**

**Status**: Plugin will encounter errors on activation due to migration numbering conflicts and missing table references.

**Recommendation**: **DO NOT DEPLOY** to production until critical issues are resolved.

---

## Critical Issues Found

### 1. DUPLICATE MIGRATION NUMBERS (CRITICAL)

**Severity**: CRITICAL - Will cause migration execution failures

The migration system has **duplicate migration numbers** which will cause unpredictable behavior:

| Migration # | Count | Files |
|------------|-------|-------|
| **024** | **2** | `024_add_crm_sync_fields.sql`<br>`024_create_docusign_config_table.sql` |
| **025** | **2** | `025_add_crm_deal_sync_fields.sql`<br>`025_create_docusign_envelopes_table.sql` |
| **026** | **2** | `026_create_contacts_table.sql`<br>`026_create_docusign_webhook_log_table.sql` |

**Impact**:
- The `Migrator.php` class (lines 56-82) executes migrations in numeric order
- Duplicate numbers mean only ONE of each pair will execute
- The other will be permanently skipped
- This creates a **non-deterministic** deployment where tables may or may not exist

**Root Cause**:
Migration files were created in parallel without coordinating migration numbers.

---

### 2. MISSING MIGRATION GAP (MEDIUM)

**Issue**: Migration sequence jumps from `003` to `006`, skipping migrations `004` and `005`.

**Files**:
```
001_initial_schema.sql          ✓
002_create_documents_table.sql  ✓
003_create_notifications_table.sql ✓
004_*.sql                       ✗ MISSING
005_*.sql                       ✗ MISSING
006_create_modular_task_system.sql ✓
```

**Impact**:
- While not technically breaking, this creates confusion
- May indicate deleted migrations that contained important schema changes
- Breaks sequential migration numbering convention

---

### 3. PREFIX PLACEHOLDER INCONSISTENCY (MEDIUM)

**Issue**: Migration files use TWO different prefix placeholder patterns:

| Pattern | Files Using It |
|---------|----------------|
| `{prefix}` | 021, 023, 024 (DocuSign), 025 (DocuSign), 026 (DocuSign & Contacts) |
| `wp_` (hardcoded) | 001, 002, 003, 011, 014, 015, 017 |

**Example - Migration 021**:
```sql
CREATE TABLE IF NOT EXISTS {prefix}ma_deal_mls_config (
```

**Example - Migration 001**:
```sql
CREATE TABLE IF NOT EXISTS `wp_ma_deal_accounts` (
```

**Impact**:
- The `Migrator.php` class (line 180) replaces `{prefix}` with actual prefix
- Hardcoded `wp_` tables will NOT use custom prefixes if configured
- This breaks multi-site and custom prefix installations

---

## Table Inventory

### Tables Created Successfully

After resolving migration conflicts, these **29 tables** should be created:

| # | Table Name | Migration | Purpose |
|---|------------|-----------|---------|
| 1 | `wp_ma_deal_migrations` | 001 | Migration tracking |
| 2 | `wp_ma_deal_accounts` | 001 | Multi-tenant accounts |
| 3 | `wp_ma_deal_transactions` | 001 | Property transactions |
| 4 | `wp_ma_deal_parties` | 001 | Transaction parties/contacts |
| 5 | `wp_ma_deal_templates` | 001 | Task templates (YAML) |
| 6 | `wp_ma_deal_tasks` | 001 | Transaction tasks |
| 7 | `wp_ma_deal_events` | 001 | Audit log |
| 8 | `wp_ma_deal_reminders` | 001 | Email/SMS reminders |
| 9 | `wp_ma_deal_vendor_requests` | 001 | Vendor portal requests |
| 10 | `wp_ma_deal_documents` | 002 | Document metadata |
| 11 | `wp_ma_deal_notifications` | 003 | In-app notifications |
| 12 | `wp_ma_deal_task_definitions` | 006 | Reusable task definitions |
| 13 | `wp_ma_deal_template_tasks` | 006 | Template→Task mappings |
| 14 | `wp_ma_deal_transaction_custom_tasks` | 006 | Ad-hoc tasks |
| 15 | `wp_ma_deal_task_categories` | 006 | Task category lookup |
| 16 | `wp_ma_deal_custom_users` | 011 | Client user accounts |
| 17 | `wp_ma_deal_user_roles` | 011 | User role assignments |
| 18 | `wp_ma_deal_user_sessions` | 011 | JWT sessions |
| 19 | `wp_ma_deal_password_resets` | 011 | Password reset tokens |
| 20 | `wp_ma_deal_email_verifications` | 011 | Email verification |
| 21 | `wp_ma_deal_2fa_secrets` | 011 | 2FA TOTP secrets |
| 22 | `wp_ma_deal_user_invitations` | 011 | User invitations |
| 23 | `wp_ma_deal_notification_queue` | 012 | Notification batching queue |
| 24 | `wp_ma_deal_rate_limits` | 014 | API rate limiting |
| 25 | `wp_ma_deal_mls_config` | 021 | MLS integration config |
| 26 | `wp_ma_deal_crm_config` | 023 | CRM integration config |
| 27 | `wp_ma_deal_docusign_config` | 024 | DocuSign API config |
| 28 | `wp_ma_deal_docusign_envelopes` | 025 | DocuSign envelope tracking |
| 29 | `wp_ma_deal_contacts` | 026 | Global contacts (CRM sync) |
| 30 | `wp_ma_deal_docusign_webhook_log` | 026 | DocuSign webhook audit log |

**Note**: Due to duplicate migration numbers, only **24-27 tables** will actually be created depending on which migrations execute first.

---

## Foreign Key Analysis

### Properly Defined Foreign Keys

All core foreign keys are correctly defined with appropriate CASCADE/SET NULL actions:

| Table | FK Column | References | On Delete | Status |
|-------|-----------|------------|-----------|--------|
| transactions | account_id | accounts(id) | CASCADE | ✓ |
| parties | transaction_id | transactions(id) | CASCADE | ✓ |
| parties | custom_user_id | custom_users(id) | SET NULL | ✓ |
| tasks | transaction_id | transactions(id) | CASCADE | ✓ |
| tasks | template_id | templates(id) | SET NULL | ✓ |
| reminders | task_id | tasks(id) | CASCADE | ✓ |
| vendor_requests | task_id | tasks(id) | CASCADE | ✓ |
| user_roles | account_id | accounts(id) | CASCADE | ✓ |
| contacts | account_id | accounts(id) | CASCADE | ✓ |

### Missing Foreign Key Constraint (LOW PRIORITY)

**Issue**: `wp_ma_deal_mls_config` table (migration 021) does not define a foreign key to `accounts` table.

```sql
-- Migration 021 - MISSING FK
CREATE TABLE IF NOT EXISTS {prefix}ma_deal_mls_config (
    account_id BIGINT(20) UNSIGNED NOT NULL,
    -- ... fields ...
    KEY idx_account_provider (account_id, provider_type),
    -- MISSING: CONSTRAINT fk_mls_config_account FOREIGN KEY (account_id) REFERENCES accounts(id)
```

**Impact**:
- Orphaned MLS config records possible if account deleted
- No referential integrity enforcement
- Not CRITICAL but violates database normalization

---

## Migration Execution Order Issues

### Expected Order vs Actual Order

The `Migrator.php` sorts migrations numerically (line 128-133). With duplicates, execution order is **undefined**:

**Scenario 1**: If alphabetical sorting used as tiebreaker:
```
024_add_crm_sync_fields.sql          ← Executes (adds columns to transactions)
024_create_docusign_config_table.sql ← SKIPPED (table never created!) ✗
```

**Scenario 2**: If filesystem order used:
```
024_create_docusign_config_table.sql ← Executes (creates table)
024_add_crm_sync_fields.sql          ← SKIPPED (columns never added!) ✗
```

**Result**: **Unpredictable schema state** depending on filesystem implementation.

---

## Data Type Analysis

### Appropriate Data Types Used

All tables use appropriate MySQL data types:

| Field Type | Data Type Used | Status |
|------------|----------------|--------|
| Primary Keys | `BIGINT(20) UNSIGNED AUTO_INCREMENT` | ✓ Optimal |
| Strings | `VARCHAR` with appropriate lengths | ✓ Good |
| Text | `TEXT` / `LONGTEXT` for YAML/JSON | ✓ Appropriate |
| JSON | `JSON` data type (MySQL 5.7+) | ✓ Modern |
| Dates | `DATETIME` with DEFAULT CURRENT_TIMESTAMP | ✓ Correct |
| Booleans | `BOOLEAN` / `TINYINT(1)` | ✓ Standard |
| Money | `DECIMAL(12,2)` for sale_price | ✓ Appropriate |

**No data type issues found.**

---

## Index Analysis

### Well-Designed Indexes

The schema includes comprehensive indexing:

**Performance Indexes** (Migration 013):
- Composite indexes on frequently queried columns
- Covering indexes for common WHERE clauses
- Status + date range indexes

**Examples**:
```sql
KEY `idx_transaction_status_due` (transaction_id, status, due_at)
KEY `idx_status_scheduled` (status, scheduled_at)
KEY `idx_account_provider` (account_id, provider_type)
```

**Assessment**: Index strategy is **excellent** - no recommendations needed.

---

## Model-Schema Alignment

### Repository Classes vs Database Tables

Cross-referenced all Repository classes against migration-created tables:

| Repository | Table | Schema Aligned | Status |
|------------|-------|----------------|--------|
| TransactionRepository | wp_ma_deal_transactions | ✓ | OK |
| TaskRepository | wp_ma_deal_tasks | ✓ | OK |
| TemplateRepository | wp_ma_deal_templates | ✓ | OK |
| TaskDefinitionRepository | wp_ma_deal_task_definitions | ✓ | OK |
| DocumentRepository | wp_ma_deal_documents | ✓ | OK |
| CustomUserRepository | wp_ma_deal_custom_users | ✓ | OK |
| UserSessionRepository | wp_ma_deal_user_sessions | ✓ | OK |
| MLSConfigRepository | wp_ma_deal_mls_config | ✓ | OK |
| CRMConfigRepository | wp_ma_deal_crm_config | ✓ | OK |
| DocuSignConfigRepository | wp_ma_deal_docusign_config | ⚠️ | DEPENDS ON MIGRATION ORDER |

**Conditional**: DocuSign repositories will fail if migration 024 (DocuSign config) is skipped in favor of 024 (CRM sync).

---

## Migration Transaction Safety

### Atomicity Issues

**Issue**: Migrations are NOT wrapped in transactions.

**Current Implementation** (Migrator.php lines 203-211):
```php
foreach ($statements as $statement) {
    $result = $this->wpdb->query($statement);

    if ($result === false) {
        throw new \Exception(
            "Migration failed: {$file}\nError: {$this->wpdb->last_error}"
        );
    }
}
```

**Problem**:
- If migration fails mid-execution, partial changes remain
- No automatic rollback on failure
- Database left in inconsistent state

**Example**: Migration 011 creates 7 tables. If table #5 fails:
- Tables 1-4 are created
- Tables 5-7 are NOT created
- Migration marked as failed
- Re-running creates "table already exists" errors for 1-4

**Impact**: Medium - Rollback scripts exist but must be run manually.

---

## Plugin Activation Flow Analysis

### Activation Hook (ma-deal-room.php lines 185-259)

**Execution Order**:
1. Runs migrations via `Migrator::run()` (line 193)
2. Syncs YAML templates to database (line 208)
3. Syncs task definitions (line 223)
4. Registers capabilities (line 241)
5. Creates default account (line 244)

### Potential Activation Failures

**Scenario 1**: Migration fails due to duplicate numbers
```
Result: Templates sync fails (line 410-417) - tables don't exist
Error: "Database tables not initialized yet"
```

**Scenario 2**: Default account creation fails
```php
// Line 811: Check if accounts table exists
$account_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ma_deal_accounts");
// If migration 001 failed, this query errors
```

**Protection**: Code includes safety checks (lines 410-417, 803-808) but still logs errors.

---

## Missing Column Analysis

### Columns Added by ALTER TABLE

Several migrations add columns to existing tables. If parent migration is skipped, these fail:

| Migration | Operation | Depends On | Risk |
|-----------|-----------|------------|------|
| 011 | ALTER parties ADD custom_user_id | 001 (parties) | LOW - 001 always runs |
| 011 | ALTER notifications ADD user_type | 003 (notifications) | LOW - 003 runs before 011 |
| 015 | ALTER documents ADD checksum | 002 (documents) | LOW - Sequential |
| 017 | ALTER user_sessions ADD session_id | 011 (user_sessions) | LOW - Sequential |
| 022 | ALTER transactions ADD mls_number | 001 (transactions) | LOW - Already in 001! |
| 026 | ALTER parties ADD contact_id | 001 (parties) | **MEDIUM** - If 026 skipped |

**Issue Found**: Migration 022 claims to add `mls_number` column, but it's already in migration 001 (line 75)!

**Impact**: Migration 022 will fail with "Duplicate column" error if 001 ran first. The column definition in 001 should be removed, or 022 should be marked as completed.

---

## Charset and Collation

**All tables use**:
- `ENGINE=InnoDB` ✓
- `DEFAULT CHARSET=utf8mb4` ✓
- `COLLATE=utf8mb4_unicode_ci` ✓

**Assessment**: Proper UTF-8 support for international characters. No issues.

---

## Recommendations

### CRITICAL - Must Fix Before Deployment

1. **Renumber Duplicate Migrations**

   Rename files to ensure unique sequential numbering:

   ```
   OLD                                    NEW
   024_add_crm_sync_fields.sql       →   024_add_crm_sync_fields.sql (keep)
   024_create_docusign_config_table.sql → 027_create_docusign_config_table.sql
   025_add_crm_deal_sync_fields.sql  →   025_add_crm_deal_sync_fields.sql (keep)
   025_create_docusign_envelopes_table.sql → 028_create_docusign_envelopes_table.sql
   026_create_contacts_table.sql     →   026_create_contacts_table.sql (keep)
   026_create_docusign_webhook_log_table.sql → 029_create_docusign_webhook_log_table.sql
   ```

2. **Standardize Prefix Placeholders**

   Convert all hardcoded `wp_` prefixes to `{prefix}` placeholder:

   ```sql
   -- WRONG (in migrations 001-017)
   CREATE TABLE IF NOT EXISTS `wp_ma_deal_accounts` (

   -- CORRECT (in migrations 021+)
   CREATE TABLE IF NOT EXISTS {prefix}ma_deal_accounts (
   ```

3. **Remove Duplicate Column Definition**

   Remove `mls_number` from migration 001 OR make migration 022 use `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`:

   ```sql
   -- Migration 022 - Make idempotent
   ALTER TABLE {prefix}ma_deal_transactions
     ADD COLUMN IF NOT EXISTS `mls_number` VARCHAR(50) DEFAULT NULL;
   ```

### HIGH PRIORITY

4. **Add Missing Foreign Key to MLS Config**

   Migration 021:
   ```sql
   ALTER TABLE {prefix}ma_deal_mls_config
     ADD CONSTRAINT fk_mls_config_account
     FOREIGN KEY (account_id)
     REFERENCES {prefix}ma_deal_accounts(id)
     ON DELETE CASCADE;
   ```

5. **Add Transaction Wrapper to Migrator**

   Modify `Migrator.php` to wrap migrations in transactions:
   ```php
   $this->wpdb->query('START TRANSACTION');
   try {
       // Execute statements
       $this->wpdb->query('COMMIT');
   } catch (\Exception $e) {
       $this->wpdb->query('ROLLBACK');
       throw $e;
   }
   ```

### MEDIUM PRIORITY

6. **Fill Migration Number Gaps**

   Create placeholder migrations 004 and 005 (even if empty) or update documentation explaining the gap.

7. **Add Migration Dependency Checker**

   Enhance `Migrator.php` to verify:
   - No duplicate migration numbers
   - No gaps in sequence
   - Required tables exist before ALTER TABLE

### LOW PRIORITY

8. **Update Migration Comments**

   Ensure all migrations have header comments with:
   - Description
   - Date created
   - Dependencies
   - Rollback availability

---

## Testing Recommendations

Before deploying to production:

### 1. Fresh Installation Test

```bash
# On a clean WordPress install:
1. Install plugin
2. Activate plugin
3. Check for PHP errors in debug.log
4. Verify all 29+ tables created:
   mysql> SHOW TABLES LIKE 'wp_ma_deal_%';
5. Check migration status:
   SELECT * FROM wp_ma_deal_migrations ORDER BY id;
```

### 2. Migration Rollback Test

```bash
# Test rollback scripts exist and work:
1. Run all migrations
2. Execute rollback for last migration
3. Verify table/columns removed
4. Re-run migration
5. Verify idempotency
```

### 3. Duplicate Number Test

```bash
# Reproduce the duplicate number issue:
1. Clean database
2. List migration files: ls -l database/migrations/*.sql
3. Note which 024/025/026 file is alphabetically first
4. Activate plugin
5. Check which table is missing
```

### 4. Prefix Test

```bash
# Test custom prefix handling:
1. Set custom table prefix in wp-config.php: $table_prefix = 'custom_';
2. Activate plugin
3. Verify tables use 'custom_ma_deal_' prefix
4. Check for any 'wp_ma_deal_' tables (should be none)
```

---

## Deployment Checklist

- [ ] Rename duplicate migration files (027, 028, 029)
- [ ] Update all migrations to use `{prefix}` placeholder
- [ ] Remove duplicate `mls_number` column from 001 or make 022 conditional
- [ ] Add foreign key constraint to `mls_config` table
- [ ] Create migrations 004 and 005 (or document why skipped)
- [ ] Add transaction wrapper to Migrator class
- [ ] Test fresh installation on staging
- [ ] Test rollback scripts
- [ ] Verify all 29+ tables created
- [ ] Check Repository classes work with schema
- [ ] Review error logs for migration failures
- [ ] Document breaking changes in changelog

---

## Conclusion

The MA Deal Room plugin database schema is **well-designed** with appropriate data types, comprehensive indexing, and proper foreign key relationships. However, **critical migration numbering conflicts** will prevent successful deployment.

**Current Risk Level**: **HIGH** 🔴

**After Fixes Applied**: **LOW** 🟢

**Estimated Fix Time**: 2-3 hours for critical issues

**Blocker for Production**: YES - Must resolve duplicate migration numbers

---

## Appendix A: Complete Table List

All tables that should exist after successful migration:

```
wp_ma_deal_migrations              (Migration tracking)
wp_ma_deal_accounts                (Multi-tenant accounts)
wp_ma_deal_transactions            (Property transactions)
wp_ma_deal_parties                 (Transaction participants)
wp_ma_deal_templates               (Task templates - YAML)
wp_ma_deal_tasks                   (Transaction tasks)
wp_ma_deal_events                  (Audit log)
wp_ma_deal_reminders               (Email/SMS reminder queue)
wp_ma_deal_vendor_requests         (Vendor portal)
wp_ma_deal_documents               (Document metadata)
wp_ma_deal_notifications           (In-app notifications)
wp_ma_deal_task_definitions        (Reusable task library)
wp_ma_deal_template_tasks          (Template→Task junction)
wp_ma_deal_transaction_custom_tasks (Ad-hoc tasks)
wp_ma_deal_task_categories         (Task categories)
wp_ma_deal_custom_users            (Client user accounts)
wp_ma_deal_user_roles              (Role assignments)
wp_ma_deal_user_sessions           (JWT refresh tokens)
wp_ma_deal_password_resets         (Password reset tokens)
wp_ma_deal_email_verifications     (Email verification)
wp_ma_deal_2fa_secrets             (2FA TOTP secrets)
wp_ma_deal_user_invitations        (User invitations)
wp_ma_deal_notification_queue      (Batch notification queue)
wp_ma_deal_rate_limits             (API rate limiting)
wp_ma_deal_mls_config              (MLS integration)
wp_ma_deal_crm_config              (CRM integration)
wp_ma_deal_docusign_config         (DocuSign API config)
wp_ma_deal_docusign_envelopes      (DocuSign envelope tracking)
wp_ma_deal_docusign_webhook_log    (DocuSign webhooks)
wp_ma_deal_contacts                (Global contacts with CRM sync)
```

**Total**: 30 tables

---

## Appendix B: Migration Dependencies Graph

```
001 (Initial Schema)
  ├─→ 002 (Documents)
  ├─→ 003 (Notifications)
  │     └─→ 011 (ALTER notifications)
  ├─→ 006 (Modular Tasks)
  ├─→ 011 (User System)
  │     ├─→ ALTER parties
  │     ├─→ ALTER notifications
  │     ├─→ ALTER events
  │     └─→ 017 (Enhance user_sessions)
  └─→ 022 (ADD mls_number) ⚠️ DUPLICATE

002 (Documents)
  └─→ 015 (ADD file security)

006 (Modular Tasks)
  └─→ 007 (Fix task calculations)

011 (User System)
  ├─→ 014 (ALTER 2fa for rate limiting)
  └─→ 017 (Enhance sessions)

021 (MLS Config) [standalone]

023 (CRM Config) [standalone]
  ├─→ 024 (ADD crm_sync to transactions) ⚠️ DUPLICATE #
  └─→ 025 (ADD crm_deal_sync) ⚠️ DUPLICATE #

024 (DocuSign Config) ⚠️ DUPLICATE #
  ├─→ 025 (DocuSign Envelopes) ⚠️ DUPLICATE #
  └─→ 026 (DocuSign Webhook) ⚠️ DUPLICATE #

026 (Contacts) ⚠️ DUPLICATE #
  └─→ ALTER parties (ADD contact_id)
```

---

**END OF REPORT**

*Generated by Data Expert AI Agent*
*Report Version: 1.0*
*Date: 2025-11-03*
