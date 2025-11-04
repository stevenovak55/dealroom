# WP-Plugin-Deployment-Agent

## Agent Purpose

This specialized Claude Code Agent is responsible for ensuring the **MA Deal Room WordPress plugin** works correctly across all deployment scenarios:

- ✅ Fresh installation on new WordPress sites
- ✅ Plugin activation after deactivation
- ✅ Plugin updates with database migrations
- ✅ Complete uninstallation (data cleanup)
- ✅ Reactivation after deletion

## Core Responsibilities

### 1. Verify Plugin Activation
- Ensure all database migrations run in sequence
- Verify all 17+ plugin tables are created correctly
- Check that default account is created for admin users
- Confirm system templates are synced from YAML files
- Validate WordPress capabilities are registered

### 2. Test Database Migrations
- Verify migration tracking table exists: `wp_ma_deal_migrations`
- Ensure migrations run in sequential order (001 → 002 → 003... → 008)
- Check that SQL comments are properly parsed (critical bug fixed in session)
- Validate foreign key relationships are created correctly

### 3. Monitor Uninstallation
- Verify all 17+ plugin tables are dropped
- Check that plugin options are removed from `wp_options`
- Ensure plugin pages (agent-dashboard) are deleted
- Validate foreign key constraints don't block table drops

### 4. Test Upgrade Paths
- Verify new migrations run when plugin is updated
- Ensure existing data is preserved during upgrades
- Check that schema changes don't break existing functionality

## Critical Systems

### Plugin Activation Hook
**File**: `ma-deal-room/ma-deal-room.php:130-173`

**What It Does**:
1. Runs database migrations via `Migrator::run()`
2. Syncs system templates from YAML files
3. Registers custom WordPress capabilities
4. Creates default account for first admin user
5. Creates agent dashboard page
6. Flushes rewrite rules for REST API

**Key Function**: `ma_deal_room_activate()`

### Database Migration System
**File**: `ma-deal-room/src/Database/Migrator.php`

**Critical Fix Applied**: SQL comment parsing
- **Bug**: Comments (`--`) were being attached to CREATE TABLE statements
- **Fix**: Strip comment lines BEFORE splitting into statements (lines 182-192)
- **Result**: Migrations now parse correctly (9 statements instead of 2)

**Migration Files**: `ma-deal-room/database/migrations/*.sql`

Current migrations:
- 001: Initial schema (accounts, templates, transactions, tasks, parties)
- 002: Documents table
- 003: Notifications system
- 004: Property types and task categories
- 005: Custom tasks and events
- 006: Modular task system (TaskDefinitions, TemplateTask junction)
- 007: Fix task due calculations (32 TaskDefinition records updated)
- 008: Add loan_commitment_date column to transactions

### Default Account Creation
**File**: `ma-deal-room/ma-deal-room.php:360-420`

**Function**: `ma_deal_room_ensure_default_account()`

**Why Critical**: Users MUST have an associated account to create transactions. For fresh WordPress installs, this function automatically creates an account for the first admin user.

**Account Structure**:
```php
[
    'name' => get_bloginfo('name') . ' Real Estate',
    'owner_user_id' => $admin_user->ID,
    'status' => 'active',
    'subscription_tier' => 'professional',
    'subscription_expires_at' => '+10 years',
    'max_transactions' => 1000,
    'max_users' => 50,
]
```

### Template Sync System
**Function**: `ma_deal_room_sync_system_templates()`

**Template Files**:
- `templates/sfh_septic.yaml` - Single Family Home with Septic
- `templates/sfh_city_water.yaml` - Single Family Home with City Water
- `templates/condo.yaml` - Condominium
- `templates/multifamily.yaml` - Multi-family Property
- `templates/base_transaction.yaml` - Base template (modular system)

**What It Does**: Reads YAML files and syncs them to `wp_ma_deal_templates` table with `is_system = 1`

### Uninstall System
**File**: `ma-deal-room/uninstall.php`

**Critical Elements**:

1. **Foreign Key Handling**:
```php
$wpdb->query('SET FOREIGN_KEY_CHECKS=0');
// Drop tables...
$wpdb->query('SET FOREIGN_KEY_CHECKS=1');
```

2. **Table Drop Order** (child tables first):
```
ma_deal_events
ma_deal_vendor_requests
ma_deal_reminders
ma_deal_documents
ma_deal_notifications
ma_deal_template_tasks
ma_deal_transaction_custom_tasks
ma_deal_property_attributes
ma_deal_security_deposits
ma_deal_tasks
ma_deal_parties
ma_deal_transactions
ma_deal_templates
ma_deal_task_definitions
ma_deal_accounts
ma_deal_task_categories
ma_deal_transaction_types
ma_deal_migrations
```

3. **Options Cleanup**:
```php
DELETE FROM wp_options WHERE option_name LIKE 'ma_deal_room_%'
DELETE FROM wp_options WHERE option_name LIKE 'ma_deal_%'
```

## Known Issues & Solutions

### Issue #1: SQL Comment Parsing Bug
**Symptom**: Migrations only find 2 statements instead of 9
**Root Cause**: SQL comments (`--`) attached to multi-line CREATE TABLE statements
**Solution**: Strip comment lines BEFORE splitting by semicolon
**File**: `Migrator.php:182-192`
**Status**: ✅ FIXED

### Issue #2: Foreign Key Constraints During Uninstall
**Symptom**: Cannot drop tables - "referenced by a foreign key constraint"
**Root Cause**: Some child tables reference parent tables
**Solution**:
1. Add `SET FOREIGN_KEY_CHECKS=0` before dropping
2. Order tables correctly (child before parent)
3. Include ALL tables (ma_deal_events, ma_deal_vendor_requests were missing)
**File**: `uninstall.php:63-68`
**Status**: ✅ FIXED

### Issue #3: Missing loan_commitment_date Column
**Symptom**: 500 error - "Unknown column 'loan_commitment_date' in 'field list'"
**Root Cause**: Frontend expects this column for 6-milestone workflow, but initial schema didn't include it
**Solution**: Created migration 008 to add the column
**File**: `database/migrations/008_add_loan_commitment_date.sql`
**Status**: ✅ FIXED

### Issue #4: Admins Need Accounts
**Symptom**: 403 error "You must have an account to create transactions"
**Root Cause**: TransactionController requires users to have associated account records
**User Feedback**: "but I am logged in as an Administrator. We don't yet have user types such as agent, buyer, seller, etc, to create accounts"
**Solution**: Added automatic account creation in activation hook
**Function**: `ma_deal_room_ensure_default_account()`
**Status**: ✅ FIXED

### Issue #5: Task Creation Failing
**Symptom**: 0 tasks created even though template returned 92 task definitions
**Root Cause**: Test script tried to add 'account_id' to task_data, but tasks table doesn't have that column
**Solution**: Remove account_id from task creation data
**Status**: ✅ FIXED

## Testing Procedures

### Comprehensive Lifecycle Test
**Test Script**: `/tmp/comprehensive-lifecycle-test.php`

**Test Steps**:
1. ✅ Deactivate plugin
2. ✅ Run uninstall (simulate deletion)
3. ✅ Verify all tables dropped (0 tables remaining)
4. ✅ Verify all options removed
5. ✅ Reactivate plugin (fresh install)
6. ✅ Verify migrations applied (6 migrations)
7. ✅ Verify default account created
8. ✅ Verify templates synced (5 templates)
9. ✅ Create transaction via API
10. ✅ Verify transaction data saved correctly
11. ✅ Verify tasks generated (92 tasks)
12. ✅ Verify date columns saved correctly

**Expected Results**:
- All plugin tables removed during uninstall
- Fresh activation creates all tables
- Default account auto-created for admin
- 5 system templates synced
- Transaction creation succeeds
- 92 tasks generated with 81 having calculated due dates
- All 4 date columns saved correctly (offer_accepted_date, ps_agreement_date, loan_commitment_date, closing_date)

### Quick Activation Test
```bash
docker exec ma-dealroom-wp php /tmp/test-account-creation.php
```

Verifies:
- Account creation works
- Admin user detection works
- Account repository functions correctly

### Migration Test
```bash
docker exec ma-dealroom-wp php /var/www/html/wp-content/plugins/ma-deal-room/src/Database/Migrator.php
```

Verifies:
- All migrations run in sequence
- SQL parsing works correctly
- No duplicate migrations applied

## Docker Environment

**Container**: `ma-dealroom-wp`
**WordPress Root**: `/var/www/html`
**Plugin Path**: `/var/www/html/wp-content/plugins/ma-deal-room`

**Common Commands**:
```bash
# Run test script
docker exec ma-dealroom-wp php /tmp/test-script.php

# Check database tables
docker exec ma-dealroom-wp wp db query "SHOW TABLES LIKE 'wp_ma_deal_%'"

# Check migrations
docker exec ma-dealroom-wp wp db query "SELECT * FROM wp_ma_deal_migrations"

# Copy migration to container
docker cp migration.sql ma-dealroom-wp:/tmp/

# Run PHP syntax check
docker exec ma-dealroom-wp php -l /var/www/html/wp-content/plugins/ma-deal-room/ma-deal-room.php
```

## Key Database Tables

### Core Tables
- `wp_ma_deal_accounts` - Account/organization records
- `wp_ma_deal_transactions` - Real estate transactions
- `wp_ma_deal_tasks` - Task instances for transactions
- `wp_ma_deal_templates` - Transaction templates (from YAML)
- `wp_ma_deal_parties` - Buyers, sellers, agents, attorneys
- `wp_ma_deal_documents` - File attachments

### Modular Task System
- `wp_ma_deal_task_definitions` - Reusable task templates (92 tasks)
- `wp_ma_deal_template_tasks` - Junction table linking templates to task definitions
- `wp_ma_deal_task_categories` - Task categorization (financing, inspection, closing, etc.)

### Supporting Tables
- `wp_ma_deal_notifications` - Email/SMS notifications
- `wp_ma_deal_reminders` - Scheduled reminders
- `wp_ma_deal_events` - Audit log
- `wp_ma_deal_property_attributes` - Property-specific data
- `wp_ma_deal_migrations` - Migration tracking

## Date Anchor System

### 6 Milestone Workflow
The plugin uses 6 key dates to calculate task due dates:

1. **Offer** - `offer_accepted_date` (when offer is accepted)
2. **PS** - `ps_agreement_date` (Purchase & Sale agreement signed)
3. **LoanCommitment** - `loan_commitment_date` (mortgage commitment received)
4. **Closing** - `closing_date` (final closing date)
5. **FirstMeeting** - For initial client meeting tasks
6. **Listing** - `listing_date` (property listed - for listing agent tasks)

### Due Calculation Format
Tasks use relative date calculations:
- `Offer+2d` - 2 days after offer accepted
- `PS+7d` - 7 days after P&S signed
- `Closing-21d` - 21 days before closing
- `LoanCommitment+1d` - 1 day after loan commitment

### Example Task Definitions
```
collect-earnest-money-deposit: Offer+2d
submit-formal-mortgage-application: PS+3d
order-title-insurance: Closing-21d
conduct-final-walkthrough: Closing-1d
```

## Critical Files Reference

### Plugin Core
- `ma-deal-room/ma-deal-room.php` - Main plugin file, activation hooks
- `ma-deal-room/uninstall.php` - Cleanup on deletion

### Database Layer
- `ma-deal-room/src/Database/Migrator.php` - Migration runner
- `ma-deal-room/database/migrations/*.sql` - All migration files

### Models
- `ma-deal-room/src/Models/Transaction.php` - Transaction model
- `ma-deal-room/src/Models/Task.php` - Task model
- `ma-deal-room/src/Models/TaskDefinition.php` - Task definition model
- `ma-deal-room/src/Models/Template.php` - Template model

### Repositories
- `ma-deal-room/src/Repositories/AccountRepository.php`
- `ma-deal-room/src/Repositories/TransactionRepository.php`
- `ma-deal-room/src/Repositories/TaskRepository.php`
- `ma-deal-room/src/Repositories/TemplateRepository.php`

### Services
- `ma-deal-room/src/Services/TemplateEngine.php` - Task instantiation
- `ma-deal-room/src/Services/TaskScheduler.php` - Due date calculation

### REST API Controllers
- `ma-deal-room/src/REST/Controllers/TransactionController.php`
- `ma-deal-room/src/REST/Controllers/TaskController.php`
- `ma-deal-room/src/REST/Controllers/TemplateController.php`

## Success Criteria

When testing plugin deployment, verify:

✅ **Fresh Install**
- All migrations run (currently 8)
- All tables created (17+ tables)
- Default account created for admin
- 5 system templates synced
- Agent dashboard page created

✅ **Transaction Creation**
- Transaction can be created via API
- All date columns save correctly (offer_accepted_date, ps_agreement_date, loan_commitment_date, closing_date)
- Tasks are generated from template (92 tasks)
- Task due dates are calculated correctly (81+ tasks with due dates)

✅ **Uninstall**
- All plugin tables dropped (0 remaining)
- All plugin options removed (0 remaining)
- Plugin pages deleted
- No foreign key constraint errors

✅ **Reactivation**
- Plugin can be reactivated after deactivation
- All systems work as on fresh install
- No duplicate data created

## Agent Workflow

When working on deployment issues:

1. **Understand the Issue**
   - Read error messages carefully
   - Check WordPress debug.log
   - Review browser console for frontend errors

2. **Locate the Problem**
   - Identify which system is failing (activation, migration, uninstall)
   - Check relevant files (see Critical Files Reference)
   - Review database state

3. **Test the Fix**
   - Create test script if needed
   - Run in Docker container
   - Verify fix doesn't break other systems

4. **Document Changes**
   - Update this agent guide if new issues discovered
   - Add migration if schema changes needed
   - Update version number in `ma-deal-room.php`

5. **Verify End-to-End**
   - Run comprehensive lifecycle test
   - Confirm all tests pass
   - Check for any new warnings/errors

## Current Status

**Version**: 1.0.0 (as of session completion)
**Migrations**: 008 (current)
**Templates**: 5 system templates
**Task Definitions**: 92 reusable tasks
**Test Status**: ✅ ALL TESTS PASSING

**Last Comprehensive Test**: 2025-10-31
- Plugin deactivation: ✅
- Uninstall (all tables dropped): ✅
- Fresh activation (8 migrations): ✅
- Default account created: ✅
- Templates synced (5 templates): ✅
- Transaction created successfully: ✅
- All date columns saved correctly: ✅
- Tasks generated (92 tasks): ✅

**Production Readiness**: ✅ READY FOR DEPLOYMENT

## Future Considerations

### Upgrade Path Testing
When new migrations are added:
1. Test upgrade from version N to N+1
2. Verify existing data is preserved
3. Check that new columns have sensible defaults
4. Test rollback procedure if needed

### Multisite Support
If WordPress multisite support is added:
- Ensure migrations run per-site
- Account creation per-site
- Template sync per-site
- Uninstall cleanup per-site

### Data Migration Tools
Consider creating:
- Export/import functionality for transactions
- Backup before migration
- Rollback capability for failed migrations

## Contact & Support

For deployment issues:
- Check WordPress debug.log
- Review browser console
- Run comprehensive lifecycle test
- Consult this guide for known issues

---

**Last Updated**: 2025-10-31
**Agent Version**: 1.0
**Plugin Version**: 1.0.0
