# CRM Integration - Quick Fix Guide

This guide provides exact code changes needed to fix the 5 critical deployment issues.

---

## Fix #1: Migration 024 - Update Table References (30 minutes)

**File:** `/home/snova/projects/dealroom/ma-deal-room/database/migrations/024_add_crm_sync_fields.sql`

### Current Code (BROKEN):
```sql
-- Add CRM sync fields to contacts table
ALTER TABLE `{prefix}ma_contacts`
    ADD COLUMN `crm_id` varchar(100) DEFAULT NULL COMMENT 'CRM record ID (Salesforce/HubSpot)',
    ADD COLUMN `crm_provider` varchar(50) DEFAULT NULL COMMENT 'CRM provider type (salesforce/hubspot)',
    ADD COLUMN `crm_last_sync` datetime DEFAULT NULL COMMENT 'Last successful sync timestamp',
    ADD COLUMN `crm_sync_status` varchar(20) DEFAULT NULL COMMENT 'Sync status (synced/pending/error)',
    ADD INDEX `idx_crm_id` (`crm_id`),
    ADD INDEX `idx_crm_provider` (`crm_provider`),
    ADD INDEX `idx_crm_sync_status` (`crm_sync_status`),
    ADD INDEX `idx_crm_last_sync` (`crm_last_sync`),
    ADD UNIQUE INDEX `idx_crm_unique` (`crm_provider`, `crm_id`);

-- Add CRM sync fields to users table
ALTER TABLE `{prefix}ma_users`
    ADD COLUMN `crm_contact_id` varchar(100) DEFAULT NULL COMMENT 'CRM contact record ID',
    ADD COLUMN `crm_provider` varchar(50) DEFAULT NULL COMMENT 'CRM provider type',
    ADD INDEX `idx_user_crm_contact` (`crm_contact_id`),
    ADD INDEX `idx_user_crm_provider` (`crm_provider`);
```

### Fixed Code:
```sql
-- Migration: 024 - Add CRM Sync Fields to Parties and Users
-- Description: Add tracking fields for CRM synchronization to parties and custom users tables
-- Author: AI Agent (Claude Code)
-- Date: 2025-11-03

-- Add CRM sync fields to parties table (transaction contacts)
ALTER TABLE `{prefix}ma_deal_parties`
    ADD COLUMN `crm_id` varchar(100) DEFAULT NULL COMMENT 'CRM record ID (Salesforce/HubSpot)',
    ADD COLUMN `crm_provider` varchar(50) DEFAULT NULL COMMENT 'CRM provider type (salesforce/hubspot)',
    ADD COLUMN `crm_last_sync` datetime DEFAULT NULL COMMENT 'Last successful sync timestamp',
    ADD COLUMN `crm_sync_status` varchar(20) DEFAULT NULL COMMENT 'Sync status (synced/pending/error)',
    ADD INDEX `idx_party_crm_id` (`crm_id`),
    ADD INDEX `idx_party_crm_provider` (`crm_provider`),
    ADD INDEX `idx_party_crm_sync_status` (`crm_sync_status`),
    ADD INDEX `idx_party_crm_last_sync` (`crm_last_sync`),
    ADD UNIQUE INDEX `idx_party_crm_unique` (`crm_provider`, `crm_id`);

-- Add CRM sync fields to custom users table (portal users)
ALTER TABLE `{prefix}ma_deal_custom_users`
    ADD COLUMN `crm_contact_id` varchar(100) DEFAULT NULL COMMENT 'CRM contact record ID',
    ADD COLUMN `crm_provider` varchar(50) DEFAULT NULL COMMENT 'CRM provider type',
    ADD COLUMN `crm_last_sync` datetime DEFAULT NULL COMMENT 'Last successful sync timestamp',
    ADD INDEX `idx_user_crm_contact` (`crm_contact_id`),
    ADD INDEX `idx_user_crm_provider` (`crm_provider`),
    ADD INDEX `idx_user_crm_last_sync` (`crm_last_sync`);
```

**Testing:**
```bash
# Test SQL syntax
wp db query < /path/to/024_add_crm_sync_fields.sql

# Verify columns added
wp db query "DESCRIBE wp_ma_deal_parties"
wp db query "DESCRIBE wp_ma_deal_custom_users"
```

---

## Fix #2: ContactSyncService - Update Table Reference (2 hours)

**File:** `/home/snova/projects/dealroom/ma-deal-room/src/Services/Integration/CRM/ContactSyncService.php`

### Change #1: Line 18-28 (Constructor)

**Current Code (BROKEN):**
```php
class ContactSyncService {
    private $wpdb;
    private $contacts_table;  // ✗
    private $config_repository;
    private $factory;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->contacts_table = $wpdb->prefix . 'ma_contacts';  // ✗ BROKEN
        $this->config_repository = new CRMConfigRepository();
        $this->factory = new CRMClientFactory();
    }
```

**Fixed Code:**
```php
class ContactSyncService {
    private $wpdb;
    private $parties_table;  // ✓ Changed
    private $config_repository;
    private $factory;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->parties_table = $wpdb->prefix . 'ma_deal_parties';  // ✓ Fixed
        $this->config_repository = new CRMConfigRepository();
        $this->factory = new CRMClientFactory();
    }
```

### Change #2: Update All Method References

**Search and Replace:**
```
Find:    $this->contacts_table
Replace: $this->parties_table
```

This affects these methods:
- `syncFromCRM()` - line ~38
- `syncToCRM()` - line ~104
- `findContactByCrmId()` - line ~200
- `findContactByEmail()` - line ~220
- `createContact()` - line ~240
- `updateContact()` - line ~260

### Change #3: Add Transaction ID Handling

**Important:** The `ma_deal_parties` table requires a `transaction_id`. You need to add logic to handle this.

**Option A: Require transaction_id in sync methods**
```php
public function syncContactToParty(
    int $account_id,
    int $transaction_id,  // ✓ Add this parameter
    array $contact_data,
    string $provider_type
): array {
    // Sync CRM contact to party for specific transaction
    $contact_data['transaction_id'] = $transaction_id;
    // ... rest of sync logic
}
```

**Option B: Create placeholder transaction for orphaned contacts**
```php
private function getOrCreatePlaceholderTransaction(int $account_id): int {
    // Create a "CRM Contacts" placeholder transaction
    // to hold synced contacts not yet assigned to real transactions
    // ...
}
```

**Recommendation:** Use Option B for MVP, refactor to Option A later.

---

## Fix #3: Register CRMSyncController (15 minutes)

**File:** `/home/snova/projects/dealroom/ma-deal-room/src/Core/Plugin.php`

### Change #1: Add Import (after line 67)

**Current Code:**
```php
use MADealRoom\REST\Controllers\MLSController;

/**
 * Main plugin singleton class
 */
class Plugin {
```

**Fixed Code:**
```php
use MADealRoom\REST\Controllers\MLSController;
use MADealRoom\REST\Controllers\CRMSyncController;  // ✓ Add this

/**
 * Main plugin singleton class
 */
class Plugin {
```

### Change #2: Register Service (in register_services method, around line 300)

Find this section:
```php
// MLS Integration
$this->container->register('mls_controller', function() {
    return new MLSController();
});
```

Add after it:
```php
// CRM Integration
$this->container->register('crm_config_repository', function() {
    return new \MADealRoom\Repositories\CRMConfigRepository();
});

$this->container->register('crm_sync_controller', function() {
    return new CRMSyncController();
});
```

### Change #3: Register Routes (in register_hooks method, around line 500)

Find this section:
```php
// MLS Controller
add_action('rest_api_init', function() {
    $controller = $this->container->get('mls_controller');
    $controller->register_routes();
});
```

Add after it:
```php
// CRM Sync Controller
add_action('rest_api_init', function() {
    $controller = $this->container->get('crm_sync_controller');
    $controller->register_routes();
});
```

**Testing:**
```bash
# Verify routes registered
wp rest-route list | grep crm

# Expected output:
# /ma-deal/v1/crm/sync/contacts
# /ma-deal/v1/crm/sync/deals
# /ma-deal/v1/crm/sync/status
# ... etc
```

---

## Fix #4: Update Uninstall.php (5 minutes)

**File:** `/home/snova/projects/dealroom/ma-deal-room/uninstall.php`

### Current Code (line 27-50):
```php
$tables = [
    // Child tables first (tables that reference other tables)
    $prefix . 'ma_deal_events',
    $prefix . 'ma_deal_vendor_requests',
    // ... more tables ...
    $prefix . 'ma_deal_custom_users',

    // Parent tables (referenced by foreign keys)
    $prefix . 'ma_deal_transactions',
```

### Fixed Code:
```php
$tables = [
    // Child tables first (tables that reference other tables)
    $prefix . 'ma_deal_events',
    $prefix . 'ma_deal_vendor_requests',
    // ... existing tables ...
    $prefix . 'ma_deal_custom_users',

    // Integration tables (add these after line 49)
    $prefix . 'ma_deal_crm_config',     // ✓ Add this
    $prefix . 'ma_deal_mls_config',     // ✓ Add this

    // Parent tables (referenced by foreign keys)
    $prefix . 'ma_deal_transactions',
```

**Testing:**
```bash
# Test uninstall (BE CAREFUL - this deletes all data!)
# Only run on development/test site
wp plugin delete ma-deal-room --yes

# Verify no orphaned tables
wp db query "SHOW TABLES LIKE 'wp_ma_deal_%'"

# Expected: No results
```

---

## Fix #5: Standardize Namespaces (1 hour)

**Files to Update:**
- `src/Services/Integration/CRM/CRMClientInterface.php`
- `src/Services/Integration/CRM/SalesforceClient.php`
- `src/Services/Integration/CRM/HubSpotClient.php`
- `src/Services/Integration/CRM/CRMClientFactory.php`
- `src/Services/Integration/CRM/ContactSyncService.php`
- `src/Services/Integration/CRM/DealSyncService.php`
- `src/Services/Integration/CRM/ActivitySyncService.php`
- `src/Services/Integration/CRM/CRMActivityLogger.php`
- `src/REST/Controllers/CRMSyncController.php`
- `src/Repositories/CRMConfigRepository.php`

### Search and Replace in Each File:

**Find:**
```php
namespace MA_Deal_Room\Services\Integration\CRM;
namespace MA_Deal_Room\REST\Controllers;
namespace MA_Deal_Room\Repositories;
```

**Replace:**
```php
namespace MADealRoom\Services\Integration\CRM;
namespace MADealRoom\REST\Controllers;
namespace MADealRoom\Repositories;
```

### Update Use Statements:

**Find:**
```php
use MA_Deal_Room\Services\Integration\CRM\CRMClientInterface;
use MA_Deal_Room\Repositories\CRMConfigRepository;
```

**Replace:**
```php
use MADealRoom\Services\Integration\CRM\CRMClientInterface;
use MADealRoom\Repositories\CRMConfigRepository;
```

**Automated Fix:**
```bash
# Use sed to update all files at once
cd /home/snova/projects/dealroom/ma-deal-room

find src -name "*.php" -type f -exec sed -i 's/namespace MA_Deal_Room\\/namespace MADealRoom\\/g' {} +
find src -name "*.php" -type f -exec sed -i 's/use MA_Deal_Room\\/use MADealRoom\\/g' {} +
```

**Testing:**
```bash
# Test PHP syntax
find src/Services/Integration/CRM -name "*.php" -exec php -l {} \;

# Expected: No syntax errors
```

---

## Complete Testing Checklist

After all fixes are applied:

### 1. Syntax Check
```bash
cd /home/snova/projects/dealroom/ma-deal-room
find . -name "*.php" -type f -exec php -l {} \; | grep -i error
```

### 2. Deactivate/Reactivate Test
```bash
wp plugin deactivate ma-deal-room
wp plugin activate ma-deal-room
wp db query "SELECT * FROM wp_ma_deal_migrations ORDER BY id DESC LIMIT 5"
```

### 3. Verify CRM Tables Created
```bash
wp db query "DESCRIBE wp_ma_deal_crm_config"
wp db query "DESCRIBE wp_ma_deal_parties" | grep crm
wp db query "DESCRIBE wp_ma_deal_custom_users" | grep crm
wp db query "DESCRIBE wp_ma_deal_transactions" | grep crm
```

### 4. Test REST API Endpoints
```bash
# List all routes
wp rest-route list | grep crm

# Test endpoint (requires auth)
curl -X GET https://dev-site.com/wp-json/ma-deal/v1/crm/configurations \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Run Deployment Test
```bash
php /home/snova/projects/dealroom/test-crm-deployment.php
```

**Expected output:**
```
Total Tests Passed: 13
✓ All tests passed - ready for deployment
```

### 6. Full Uninstall Test (CAREFUL!)
```bash
# Only on test site!
wp plugin delete ma-deal-room --yes
wp db query "SHOW TABLES LIKE 'wp_ma_deal_%'"

# Expected: No results (all tables cleaned up)
```

---

## Estimated Time

| Fix | Time | Complexity |
|-----|------|------------|
| Fix #1 - Migration 024 | 30 min | Low |
| Fix #2 - ContactSyncService | 2 hours | Medium |
| Fix #3 - Controller Registration | 15 min | Low |
| Fix #4 - Uninstall.php | 5 min | Low |
| Fix #5 - Namespaces | 1 hour | Low |
| Testing | 1 hour | - |
| **Total** | **4-5 hours** | - |

---

## Git Workflow

```bash
# Create fix branch
git checkout -b fix/crm-deployment-issues

# Make fixes
# ... apply all changes above ...

# Test
php test-crm-deployment.php

# Commit with clear message
git add .
git commit -m "Fix T3.2 CRM deployment issues

- Fix migration 024 table references (ma_contacts → ma_deal_parties)
- Update ContactSyncService to use ma_deal_parties table
- Register CRMSyncController in Plugin.php
- Add CRM/MLS tables to uninstall.php cleanup
- Standardize namespaces to MADealRoom

Fixes 5 critical deployment blockers identified by wp-plugin-deployment-agent.
See: CRM_DEPLOYMENT_ISSUES_SUMMARY.md

Test: php test-crm-deployment.php passes all tests

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"

# Push and create PR
git push origin fix/crm-deployment-issues
```

---

## After Fixes

1. Deploy to staging environment
2. Full QA testing
3. User acceptance testing
4. Deploy to production
5. Monitor WordPress debug logs for 24 hours

---

## Need Help?

- **Full Report:** `/home/snova/projects/dealroom/WP_PLUGIN_DEPLOYMENT_TEST_REPORT_CRM.md`
- **Issues Summary:** `/home/snova/projects/dealroom/CRM_DEPLOYMENT_ISSUES_SUMMARY.md`
- **Test Script:** `/home/snova/projects/dealroom/test-crm-deployment.php`

---

**IMPORTANT:** Do not skip any fixes. All 5 are critical for plugin activation to succeed.
