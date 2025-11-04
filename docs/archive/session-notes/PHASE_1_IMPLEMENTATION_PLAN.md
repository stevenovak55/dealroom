# Phase 1 Implementation Plan
## Database Schema Updates for Transaction Type Support

**Phase:** 1 of 5
**Timeline:** Week 1
**Priority:** CRITICAL
**Status:** IN PROGRESS

---

## Objectives

Transform the database schema to support:
1. ✅ Transaction type differentiation (buy_side, sell_side, rental_landlord, rental_tenant, commercial)
2. ✅ Property attribute-based conditional tasks (septic, well, tenant-occupied, etc.)
3. ✅ MA legal requirement tracking and compliance
4. ✅ Security deposit compliance (MA rental law)

---

## Migration Scripts to Create

### Migration 007: Add Transaction Type Support
**File:** `ma-deal-room/database/migrations/007_add_transaction_type_support.sql`

**Changes:**
- Add `transaction_type` column to `wp_ma_deal_transactions`
- Add `transaction_type_filter` column to `wp_ma_deal_task_definitions`
- Add `property_attribute_filter` column to `wp_ma_deal_task_definitions`
- Add legal requirement columns to `wp_ma_deal_task_definitions`
- Create `wp_ma_deal_transaction_types` enumeration table
- Add indexes for performance

**Status:** To be created

---

### Migration 008: Create Property Attributes Table
**File:** `ma-deal-room/database/migrations/008_create_property_attributes.sql`

**Changes:**
- Create `wp_ma_deal_property_attributes` table
- Link to transactions table
- Boolean flags for property features (septic, well, pool, etc.)
- Indexes for common queries

**Status:** To be created

---

### Migration 009: Create Security Deposit Tracking
**File:** `ma-deal-room/database/migrations/009_create_security_deposit_tracking.sql`

**Changes:**
- Create `wp_ma_deal_security_deposits` table
- MA law compliance fields (30-day deadlines, bank info, interest)
- Audit trail for deposits and returns
- Foreign keys and constraints

**Status:** To be created

---

## Pre-Implementation Checklist

### Before Running Migrations

- [ ] **Backup database** - Full backup of all tables
- [ ] **Review current schema** - Document existing structure
- [ ] **Check for active transactions** - Ensure no transactions in progress
- [ ] **Set maintenance window** - Schedule downtime if needed
- [ ] **Test on dev environment** - Run all migrations on dev database first
- [ ] **Prepare rollback scripts** - Have rollback procedures ready

### Backup Commands

```bash
# Full database backup
docker exec ma-dealroom-wp mysqldump -u root -p ma_dealroom > backup_pre_phase1_$(date +%Y%m%d_%H%M%S).sql

# Specific table backups (critical tables)
docker exec ma-dealroom-wp mysqldump -u root -p ma_dealroom wp_ma_deal_transactions > backup_transactions_$(date +%Y%m%d_%H%M%S).sql
docker exec ma-dealroom-wp mysqldump -u root -p ma_dealroom wp_ma_deal_task_definitions > backup_task_definitions_$(date +%Y%m%d_%H%M%S).sql
docker exec ma-dealroom-wp mysqldump -u root -p ma_dealroom wp_ma_deal_tasks > backup_tasks_$(date +%Y%m%d_%H%M%S).sql
```

---

## Migration Execution Order

**IMPORTANT:** Run migrations in this exact order:

1. **007_add_transaction_type_support.sql** (FIRST - foundation)
2. **008_create_property_attributes.sql** (SECOND - depends on transaction_types)
3. **009_create_security_deposit_tracking.sql** (THIRD - depends on transactions)

### Execution Commands

```bash
# Navigate to migrations directory
cd /home/snova/projects/dealroom/ma-deal-room/database/migrations

# Run migration 007
docker exec -i ma-dealroom-wp mysql -u root -p ma_dealroom < 007_add_transaction_type_support.sql

# Validate migration 007
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;
SHOW COLUMNS FROM wp_ma_deal_task_definitions LIKE '%transaction%';
SHOW COLUMNS FROM wp_ma_deal_task_definitions LIKE '%legal%';
SELECT COUNT(*) FROM wp_ma_deal_transaction_types;
"

# If validation passes, run migration 008
docker exec -i ma-dealroom-wp mysql -u root -p ma_dealroom < 008_create_property_attributes.sql

# Validate migration 008
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;
SHOW TABLES LIKE 'wp_ma_deal_property_attributes';
DESCRIBE wp_ma_deal_property_attributes;
"

# If validation passes, run migration 009
docker exec -i ma-dealroom-wp mysql -u root -p ma_dealroom < 009_create_security_deposit_tracking.sql

# Validate migration 009
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;
SHOW TABLES LIKE 'wp_ma_deal_security_deposits';
DESCRIBE wp_ma_deal_security_deposits;
"
```

---

## Validation Queries

After running all migrations, verify the changes:

### Validation 1: Transaction Type Columns Exist
```sql
USE ma_dealroom;

-- Check task_definitions columns
SELECT
  COLUMN_NAME,
  COLUMN_TYPE,
  IS_NULLABLE,
  COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'wp_ma_deal_task_definitions'
  AND COLUMN_NAME IN ('transaction_type_filter', 'property_attribute_filter', 'is_legal_requirement', 'legal_citation', 'legal_deadline');
```

**Expected Result:** 5 rows showing all new columns

---

### Validation 2: Transaction Types Seeded
```sql
SELECT type_key, name, requires_property_transfer
FROM wp_ma_deal_transaction_types
ORDER BY type_key;
```

**Expected Result:** 6 rows
- buy_side
- sell_side
- rental_landlord
- rental_tenant
- commercial_buy
- commercial_sell

---

### Validation 3: Property Attributes Table Created
```sql
DESCRIBE wp_ma_deal_property_attributes;
```

**Expected Result:** Table with columns: has_septic, has_well, tenant_occupied, etc.

---

### Validation 4: Security Deposits Table Created
```sql
DESCRIBE wp_ma_deal_security_deposits;
```

**Expected Result:** Table with MA compliance fields

---

### Validation 5: Existing Transactions Not Broken
```sql
SELECT COUNT(*) as total_transactions
FROM wp_ma_deal_transactions;

SELECT transaction_type, COUNT(*) as count
FROM wp_ma_deal_transactions
GROUP BY transaction_type;
```

**Expected Result:**
- Same number of total transactions as before migration
- All should have default transaction_type = 'buy_side' (or NULL)

---

### Validation 6: Foreign Keys Working
```sql
-- Try to insert property attributes for transaction ID 1 (if exists)
INSERT INTO wp_ma_deal_property_attributes
  (transaction_id, has_septic, has_well, created_at, updated_at)
VALUES (1, 0, 0, NOW(), NOW());

-- Should succeed if transaction 1 exists, fail if not
-- Clean up test
DELETE FROM wp_ma_deal_property_attributes WHERE transaction_id = 1;
```

---

## Rollback Procedures

If something goes wrong, rollback in REVERSE order:

### Rollback Migration 009 (Security Deposits)
```sql
USE ma_dealroom;
DROP TABLE IF EXISTS wp_ma_deal_security_deposits;
```

### Rollback Migration 008 (Property Attributes)
```sql
USE ma_dealroom;
DROP TABLE IF EXISTS wp_ma_deal_property_attributes;
```

### Rollback Migration 007 (Transaction Type Support)
```sql
USE ma_dealroom;

-- Drop new columns from task_definitions
ALTER TABLE wp_ma_deal_task_definitions
  DROP COLUMN IF EXISTS transaction_type_filter,
  DROP COLUMN IF EXISTS property_attribute_filter,
  DROP COLUMN IF EXISTS is_legal_requirement,
  DROP COLUMN IF EXISTS legal_citation,
  DROP COLUMN IF EXISTS legal_deadline;

-- Drop transaction_type column from transactions
ALTER TABLE wp_ma_deal_transactions
  DROP COLUMN IF EXISTS transaction_type;

-- Drop transaction types table
DROP TABLE IF EXISTS wp_ma_deal_transaction_types;
```

### Complete Rollback from Backup
```bash
# If rollback SQL fails, restore from backup
docker exec -i ma-dealroom-wp mysql -u root -p ma_dealroom < backup_pre_phase1_YYYYMMDD_HHMMSS.sql
```

---

## Code Updates Required

After database migrations are complete, update PHP code:

### 1. Transaction Model
**File:** `ma-deal-room/src/Models/Transaction.php`

**Add property:**
```php
protected $transaction_type; // 'buy_side', 'sell_side', etc.
```

**Add to $fillable array:**
```php
protected $fillable = [
    // ... existing fields ...
    'transaction_type',
];
```

**Add getter/setter:**
```php
public function getTransactionType(): ?string {
    return $this->transaction_type;
}

public function setTransactionType(string $type): void {
    $validTypes = ['buy_side', 'sell_side', 'rental_landlord', 'rental_tenant', 'commercial_buy', 'commercial_sell'];
    if (!in_array($type, $validTypes)) {
        throw new \InvalidArgumentException("Invalid transaction type: {$type}");
    }
    $this->transaction_type = $type;
}
```

---

### 2. TaskDefinition Model
**File:** `ma-deal-room/src/Models/TaskDefinition.php`

**Add properties:**
```php
protected $transaction_type_filter; // String: 'buy_side,sell_side'
protected $property_attribute_filter; // JSON: ['has_septic', 'has_well']
protected $is_legal_requirement; // Boolean
protected $legal_citation; // String
protected $legal_deadline; // String
```

**Add to $fillable array:**
```php
protected $fillable = [
    // ... existing fields ...
    'transaction_type_filter',
    'property_attribute_filter',
    'is_legal_requirement',
    'legal_citation',
    'legal_deadline',
];
```

**Add helper methods:**
```php
public function appliesTo(Transaction $transaction): bool {
    // Check transaction type filter
    if ($this->transaction_type_filter) {
        $allowedTypes = explode(',', $this->transaction_type_filter);
        if (!in_array($transaction->transaction_type, $allowedTypes)) {
            return false;
        }
    }

    // Check property attribute filter
    if ($this->property_attribute_filter) {
        $requiredAttributes = json_decode($this->property_attribute_filter, true);
        $propertyAttrs = $transaction->propertyAttributes; // Relationship

        foreach ($requiredAttributes as $attr) {
            if (!$propertyAttrs || !$propertyAttrs->$attr) {
                return false;
            }
        }
    }

    return true;
}
```

---

### 3. PropertyAttributes Model (NEW)
**File:** `ma-deal-room/src/Models/PropertyAttributes.php`

**Create new model:**
```php
<?php

namespace MaDealRoom\Models;

class PropertyAttributes extends BaseModel {
    protected $table = 'wp_ma_deal_property_attributes';

    protected $fillable = [
        'transaction_id',
        'has_septic',
        'has_well',
        'has_pool',
        'has_fireplace',
        'has_garage',
        'has_basement',
        'tenant_occupied',
        'is_new_construction',
        'is_historical',
        'has_shared_well',
        'on_private_road',
        'heat_type',
        'number_of_units',
    ];

    protected $casts = [
        'has_septic' => 'boolean',
        'has_well' => 'boolean',
        'has_pool' => 'boolean',
        'has_fireplace' => 'boolean',
        'has_garage' => 'boolean',
        'has_basement' => 'boolean',
        'tenant_occupied' => 'boolean',
        'is_new_construction' => 'boolean',
        'is_historical' => 'boolean',
        'has_shared_well' => 'boolean',
        'on_private_road' => 'boolean',
        'number_of_units' => 'integer',
    ];

    public function transaction() {
        return $this->belongsTo(Transaction::class);
    }
}
```

---

### 4. SecurityDeposit Model (NEW)
**File:** `ma-deal-room/src/Models/SecurityDeposit.php`

**Create new model:**
```php
<?php

namespace MaDealRoom\Models;

class SecurityDeposit extends BaseModel {
    protected $table = 'wp_ma_deal_security_deposits';

    protected $fillable = [
        'transaction_id',
        'tenant_party_id',
        'deposit_amount',
        'last_month_rent_amount',
        'received_date',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'deposited_date',
        'tenant_notified_date',
        'interest_rate',
        'interest_accrued',
        'transferred_to_new_owner',
        'transfer_date',
        'return_date',
        'return_amount',
        'deductions_itemized',
    ];

    protected $casts = [
        'deposit_amount' => 'decimal:2',
        'last_month_rent_amount' => 'decimal:2',
        'interest_rate' => 'decimal:4',
        'interest_accrued' => 'decimal:2',
        'transferred_to_new_owner' => 'boolean',
        'return_amount' => 'decimal:2',
    ];

    // MA Law compliance check: Deposited within 30 days?
    public function isDepositedOnTime(): bool {
        if (!$this->deposited_date || !$this->received_date) {
            return false;
        }

        $received = new \DateTime($this->received_date);
        $deposited = new \DateTime($this->deposited_date);
        $diff = $received->diff($deposited);

        return $diff->days <= 30;
    }

    // MA Law compliance check: Tenant notified within 30 days?
    public function isTenantNotifiedOnTime(): bool {
        if (!$this->tenant_notified_date || !$this->received_date) {
            return false;
        }

        $received = new \DateTime($this->received_date);
        $notified = new \DateTime($this->tenant_notified_date);
        $diff = $received->diff($notified);

        return $diff->days <= 30;
    }
}
```

---

### 5. TemplateEngine Updates
**File:** `ma-deal-room/src/Services/TemplateEngine.php`

**Update task filtering logic:**

Find the method that instantiates tasks (likely `instantiateTasksFromTemplate()` or similar) and update to filter by transaction type:

```php
public function instantiateTasksFromTemplate(Transaction $transaction, Template $template): array {
    $taskDefinitions = $template->getTaskDefinitions(); // Get all task definitions
    $instantiatedTasks = [];

    foreach ($taskDefinitions as $taskDef) {
        // NEW: Check if task applies to this transaction
        if (!$this->taskAppliesTo($taskDef, $transaction)) {
            continue; // Skip this task
        }

        // Existing instantiation logic
        $task = new Task();
        $task->transaction_id = $transaction->id;
        $task->task_definition_id = $taskDef->id;
        // ... rest of task creation ...

        $instantiatedTasks[] = $task;
    }

    return $instantiatedTasks;
}

private function taskAppliesTo(TaskDefinition $taskDef, Transaction $transaction): bool {
    // Check transaction type filter
    if ($taskDef->transaction_type_filter) {
        $allowedTypes = explode(',', $taskDef->transaction_type_filter);
        if (!in_array($transaction->transaction_type, $allowedTypes)) {
            return false;
        }
    }

    // Check property attribute filter
    if ($taskDef->property_attribute_filter) {
        $requiredAttrs = json_decode($taskDef->property_attribute_filter, true);
        $propertyAttrs = $transaction->propertyAttributes;

        if (!$propertyAttrs) {
            return false; // No property attributes set
        }

        foreach ($requiredAttrs as $attr) {
            if (!isset($propertyAttrs->$attr) || !$propertyAttrs->$attr) {
                return false;
            }
        }
    }

    // Check applies_if expression (existing logic)
    if ($taskDef->applies_if) {
        return $this->evaluateAppliesIf($taskDef->applies_if, $transaction);
    }

    return true;
}
```

---

## Testing Checklist

After all migrations and code updates:

### Database Tests
- [ ] All 3 migrations run without errors
- [ ] All validation queries return expected results
- [ ] No existing transactions broken
- [ ] Foreign keys enforce referential integrity
- [ ] Indexes created and functional

### Model Tests
- [ ] Transaction model has transaction_type property
- [ ] TaskDefinition model has new filter properties
- [ ] PropertyAttributes model can be created and linked to transaction
- [ ] SecurityDeposit model can be created with proper validations

### Integration Tests
- [ ] Create new buy_side transaction with property attributes
- [ ] TemplateEngine correctly filters tasks by transaction type
- [ ] Property-specific tasks only appear when attributes match
- [ ] Legal requirement flags visible in task definitions

---

## Success Criteria

Phase 1 is complete when:

✅ All 3 migration scripts created and tested
✅ All migrations run successfully on dev database
✅ All validation queries pass
✅ All 4 models updated/created
✅ TemplateEngine filters tasks by transaction type
✅ No existing functionality broken
✅ Rollback procedures tested and documented

---

## Next Steps (Phase 2)

After Phase 1 completion:
1. Begin task reclassification
2. Create new base templates (universal, buy_side, sell_side)
3. Update YAML template files
4. Migrate existing tasks to new structure

---

## Timeline

**Start Date:** 2025-10-30
**Estimated Duration:** 1 week
**Status:** In Progress - Creating migration scripts

---

## Notes

- Keep backups of all original data
- Test each migration individually before proceeding
- Document any issues or deviations from plan
- Update this document with actual execution notes

---

**Phase 1 Status:** 🟡 IN PROGRESS
**Next Action:** Create migration 007
