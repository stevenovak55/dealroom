# Global Contacts Architecture Guide

**Version:** 1.0
**Date:** 2025-11-04
**Purpose:** Document the new global contacts architecture for CRM integration

---

## Overview

The MA Deal Room plugin now includes a **global contacts table** (`ma_deal_contacts`) that stores contact information independently of transactions. This architectural change provides:

- ✅ **Better CRM Alignment**: CRM systems use global contacts
- ✅ **Contact Deduplication**: One contact record across multiple transactions
- ✅ **Efficient Sync**: Sync contacts once, use many times
- ✅ **Data Integrity**: Single source of truth for contact information

---

## Architecture

### Before (Transaction-Centric)

```
ma_deal_parties
├── transaction_id
├── contact_name
├── email
├── phone
└── (contact data embedded)
```

**Issue:** Same person in 3 transactions = 3 separate party records with potentially different data

### After (Contact-Centric)

```
ma_deal_contacts (NEW)
├── id
├── account_id
├── first_name, last_name
├── email, phone
├── crm_id, crm_provider
└── (global contact data)

ma_deal_parties (UPDATED)
├── transaction_id
├── contact_id ──→ ma_deal_contacts.id
├── role (buyer, seller, agent, etc.)
└── (transaction-specific data)
```

**Benefit:** Same person in 3 transactions = 1 contact record + 3 party links

---

## Database Schema

### ma_deal_contacts Table

```sql
CREATE TABLE ma_deal_contacts (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT(20) UNSIGNED NOT NULL,

    -- Contact Info
    contact_type ENUM('person', 'company') DEFAULT 'person',
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    company_name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    mobile VARCHAR(20),
    address VARCHAR(500),
    city VARCHAR(100),
    state VARCHAR(50),
    zip VARCHAR(20),
    country VARCHAR(100) DEFAULT 'USA',

    -- Professional Info
    job_title VARCHAR(255),
    license_number VARCHAR(100),
    bar_number VARCHAR(100),
    website VARCHAR(500),

    -- CRM Sync Fields
    crm_id VARCHAR(100),
    crm_provider VARCHAR(50),
    crm_last_sync DATETIME,
    crm_sync_status VARCHAR(20),
    crm_sync_error TEXT,

    -- Additional
    tags JSON,
    notes TEXT,
    metadata JSON,
    is_active BOOLEAN DEFAULT TRUE,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY (account_id, email),
    UNIQUE KEY (account_id, crm_provider, crm_id)
);
```

### ma_deal_parties Changes

```sql
ALTER TABLE ma_deal_parties
    ADD COLUMN contact_id BIGINT(20) UNSIGNED AFTER transaction_id,
    ADD FOREIGN KEY (contact_id) REFERENCES ma_deal_contacts(id);
```

---

## Migration Process

### 1. Run Migration 026

```bash
# Applies database schema changes
wp ma-deal migrate
```

This creates:
- `ma_deal_contacts` table
- `contact_id` column in `ma_deal_parties`

### 2. Migrate Existing Data

```bash
# Dry run first (see what would happen)
php migrate-parties-to-contacts.php --dry-run

# Apply migration
php migrate-parties-to-contacts.php
```

**What it does:**
1. Groups existing parties by email and account
2. Creates unique contact records
3. Links all parties back to their contact
4. Preserves transaction relationships

**Example:**
```
Before:
- Party #1: john@example.com (Transaction A)
- Party #2: john@example.com (Transaction B)
- Party #3: john@example.com (Transaction C)

After:
- Contact #1: john@example.com
- Party #1: → Contact #1 (Transaction A)
- Party #2: → Contact #1 (Transaction B)
- Party #3: → Contact #1 (Transaction C)
```

---

## CRM Synchronization

### Contact Sync Flow

**From CRM to Deal Room:**
```
1. Fetch contacts from Salesforce/HubSpot
2. For each CRM contact:
   - Check if exists by crm_id
   - If exists: update ma_deal_contacts
   - If not: create new in ma_deal_contacts
3. Optionally create parties for transactions
```

**From Deal Room to CRM:**
```
1. Query ma_deal_contacts WHERE crm_id IS NULL
2. For each contact:
   - Create contact in Salesforce/HubSpot
   - Store returned crm_id in ma_deal_contacts
3. Link transactions as CRM opportunities
```

### Deduplication Strategy

**By Email:**
- `UNIQUE KEY (account_id, email)` prevents duplicate contacts per account
- Same email = same contact across all transactions

**By CRM ID:**
- `UNIQUE KEY (account_id, crm_provider, crm_id)` ensures 1:1 CRM mapping
- One Salesforce contact = one Deal Room contact

---

## Usage Examples

### Creating a Contact

```php
global $wpdb;
$contacts_table = $wpdb->prefix . 'ma_deal_contacts';

$wpdb->insert($contacts_table, [
    'account_id' => 1,
    'contact_type' => 'person',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone' => '555-1234',
    'is_active' => true,
]);

$contact_id = $wpdb->insert_id;
```

### Linking to Transaction

```php
// When adding a party to a transaction
$wpdb->insert($parties_table, [
    'transaction_id' => 123,
    'contact_id' => $contact_id, // Link to global contact
    'role' => 'buyer',
]);
```

### Finding Contact by Email

```php
$contact = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$contacts_table}
     WHERE account_id = %d AND email = %s",
    $account_id,
    $email
), ARRAY_A);
```

### Syncing with CRM

```php
use MADealRoom\Services\Integration\CRM\ContactSyncService;

$sync_service = new ContactSyncService();

// Sync from CRM to Deal Room
$result = $sync_service->syncFromCRM($account_id, 'salesforce', [
    'limit' => 100
]);

// Sync from Deal Room to CRM
$result = $sync_service->syncToCRM($account_id, 'salesforce');
```

---

## Backward Compatibility

### Handling NULL contact_id

Existing code that only uses `ma_deal_parties` continues to work:

```php
// Still works - party has inline contact data
$party = $wpdb->get_row("SELECT * FROM ma_deal_parties WHERE id = 123");
echo $party->contact_name; // ✅ Still works
echo $party->email;        // ✅ Still works
```

**Recommendation:** Gradually migrate code to use contacts:

```php
// Better approach - use linked contact
$party = $wpdb->get_row("
    SELECT p.*, c.*
    FROM ma_deal_parties p
    LEFT JOIN ma_deal_contacts c ON p.contact_id = c.id
    WHERE p.id = 123
");

if ($party->contact_id) {
    // Use contact data (preferred)
    echo $party->first_name . ' ' . $party->last_name;
} else {
    // Fallback to party data
    echo $party->contact_name;
}
```

---

## Benefits

### 1. CRM Integration
- **Before:** Sync 3 transactions with same person = 3 CRM contacts created
- **After:** Sync 3 transactions with same person = 1 CRM contact, 3 linked opportunities

### 2. Data Quality
- **Before:** Update email on Party A, Party B and C still have old email
- **After:** Update email once on contact, all parties reflect new email

### 3. Performance
- **Before:** Sync 1000 parties, check 1000 times if CRM contact exists
- **After:** Sync 300 unique contacts, create 1000 party-transaction links

### 4. Reporting
```sql
-- Count unique contacts (deduplicated)
SELECT COUNT(DISTINCT contact_id) FROM ma_deal_parties;

-- Find all transactions for a contact
SELECT t.*
FROM ma_deal_transactions t
JOIN ma_deal_parties p ON t.id = p.transaction_id
WHERE p.contact_id = 123;
```

---

## Testing Checklist

After migration:

- [ ] Verify contacts table populated
- [ ] Check parties have contact_id
- [ ] Test CRM sync (from/to)
- [ ] Verify no duplicate contacts
- [ ] Check email uniqueness constraint
- [ ] Test transaction creation with existing contact
- [ ] Test transaction creation with new contact
- [ ] Verify backward compatibility (NULL contact_id)

---

## Troubleshooting

### Duplicate Email Error

**Error:** `Duplicate entry for key 'idx_account_email'`

**Solution:** Contact with that email already exists. Retrieve it:
```php
$existing = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM ma_deal_contacts
     WHERE account_id = %d AND email = %s",
    $account_id, $email
));
```

### Migration Script Errors

**Issue:** Script fails with "contact_id column missing"

**Solution:** Run migration 026 first:
```bash
wp ma-deal migrate
```

### CRM Sync Not Creating Contacts

**Check:**
1. Is ContactSyncService using `ma_deal_contacts`?
2. Check line 25 of ContactSyncService.php
3. Should be: `$this->contacts_table = $wpdb->prefix . 'ma_deal_contacts';`

---

## Future Enhancements

### Phase 1 (Current)
- ✅ Global contacts table
- ✅ CRM sync to contacts
- ✅ Party-contact linking
- ✅ Data migration script

### Phase 2 (Recommended)
- [ ] Contact merge UI (combine duplicates)
- [ ] Contact history timeline
- [ ] Contact-level document attachments
- [ ] Contact tags and segmentation
- [ ] Contact search and filters

### Phase 3 (Advanced)
- [ ] Contact custom fields
- [ ] Contact activity feed
- [ ] Contact email campaigns
- [ ] Contact scoring
- [ ] Contact relationship mapping

---

## API Reference

### ContactSyncService

**Methods:**
- `syncFromCRM(int $account_id, string $provider, array $options): array`
- `syncToCRM(int $account_id, string $provider, array $options): array`

**Returns:**
```php
[
    'synced' => 50,
    'created' => 20,
    'updated' => 30,
    'errors' => [
        ['crm_id' => '12345', 'message' => 'Invalid email']
    ]
]
```

---

## Files Modified

**Migrations:**
- `024_add_crm_sync_fields.sql` - Updated (custom_users only)
- `026_create_contacts_table.sql` - New (global contacts)

**Services:**
- `ContactSyncService.php` - Updated (uses ma_deal_contacts)

**Scripts:**
- `migrate-parties-to-contacts.php` - New (data migration)

**Documentation:**
- This file

---

**For Questions:** See CRM_FIXES_APPLIED.md or contact development team

**Last Updated:** 2025-11-04
