# Phase 1: Database Schema Updates - COMPLETE ✅

**Status:** Ready for Testing and Implementation
**Date:** 2025-10-30
**Phase:** 1 of 5

---

## Summary

Phase 1 database migrations have been successfully created and are ready for testing and deployment. These migrations provide the foundation for the entire task reorganization project.

---

## Files Created

### Planning & Documentation

1. **`PHASE_1_IMPLEMENTATION_PLAN.md`**
   - Comprehensive implementation guide
   - Step-by-step execution instructions
   - Validation queries and rollback procedures
   - Code update requirements

### Database Migration Scripts

1. **`007_add_transaction_type_support.sql`** (500+ lines)
   - Adds `transaction_type` column to `wp_ma_deal_transactions`
   - Adds filter columns to `wp_ma_deal_task_definitions`:
     - `transaction_type_filter`
     - `property_attribute_filter`
   - Adds MA legal requirement tracking:
     - `is_legal_requirement`
     - `legal_citation`
     - `legal_deadline`
   - Creates `wp_ma_deal_transaction_types` enumeration table
   - Seeds 6 transaction types (buy_side, sell_side, rental_landlord, rental_tenant, commercial_buy, commercial_sell)
   - Includes validation queries and rollback script

2. **`008_create_property_attributes.sql`** (300+ lines)
   - Creates `wp_ma_deal_property_attributes` table
   - 30+ property attribute fields (has_septic, has_well, tenant_occupied, etc.)
   - Foreign key to transactions table
   - Indexes for performance
   - Includes validation queries and rollback script

3. **`009_create_security_deposit_tracking.sql`** (450+ lines)
   - Creates `wp_ma_deal_security_deposits` table
   - Full MA law compliance tracking (M.G.L. c. 186, § 15B)
   - Automatic compliance checking via database trigger
   - 30-day deadline monitoring
   - Interest tracking
   - Transfer tracking for multifamily sales
   - Includes validation queries and rollback script

---

## What These Migrations Do

### Migration 007: Transaction Type Support

**Purpose:** Enable the system to differentiate between buy-side, sell-side, rental, and commercial transactions.

**Key Changes:**
- Tasks can now be filtered by transaction type (e.g., "Sign Listing Agreement" only appears in sell-side transactions)
- Tasks can be conditional on property attributes (e.g., "Title 5 Inspection" only when property has septic)
- Legal requirements are tracked with citations (e.g., "M.G.L. c. 183A, § 6(d)" for condo 6(d) certificate)
- 6 standard transaction types defined and ready to use

**Impact:** This is the foundation that enables proper task organization by transaction type.

---

### Migration 008: Property Attributes

**Purpose:** Store property-specific characteristics that determine which tasks apply.

**Property Attributes Tracked:**
- **Septic & Water:** has_septic, has_well, has_city_water, has_city_sewer
- **Features:** has_pool, has_garage, has_basement, has_fireplace, etc.
- **Occupancy:** tenant_occupied, is_new_construction, is_historical
- **Location:** on_private_road, is_waterfront, in_flood_zone
- **Systems:** heat_type, cooling_type, roof_type, siding_type
- **Size:** number_of_units, square_feet, lot_size_acres, year_built

**Impact:** Enables conditional task application (e.g., septic inspection tasks only appear when has_septic=true).

---

### Migration 009: Security Deposit Tracking

**Purpose:** Ensure MA rental law compliance with automatic monitoring.

**MA Law Compliance Features:**
- **30-Day Deadlines:** Automatic tracking of deposit, notification, and return deadlines
- **Bank Requirements:** Tracks MA bank account information
- **Interest Tracking:** Calculates interest owed to tenants
- **Transfer Tracking:** Manages deposit transfers when multifamily properties are sold
- **Compliance Monitoring:** Database trigger automatically checks compliance and flags issues

**Impact:** Protects agents and landlords from MA rental law violations (penalties up to $1,000 + triple damages).

---

## Migration File Locations

⚠️ **NOTE:** Due to permission restrictions, migration files are currently in the project root directory:

```
/home/snova/projects/dealroom/
├── 007_add_transaction_type_support.sql
├── 008_create_property_attributes.sql
└── 009_create_security_deposit_tracking.sql
```

**Before running migrations**, you should move them to the official migrations directory:

```bash
# Move migration files (requires appropriate permissions)
mv /home/snova/projects/dealroom/007_add_transaction_type_support.sql \
   /home/snova/projects/dealroom/ma-deal-room/database/migrations/

mv /home/snova/projects/dealroom/008_create_property_attributes.sql \
   /home/snova/projects/dealroom/ma-deal-room/database/migrations/

mv /home/snova/projects/dealroom/009_create_security_deposit_tracking.sql \
   /home/snova/projects/dealroom/ma-deal-room/database/migrations/
```

---

## Next Steps: Running the Migrations

### Step 1: Backup Database

**CRITICAL:** Always backup before running migrations!

```bash
# Full database backup
docker exec ma-dealroom-wp mysqldump -u root -p ma_dealroom > backup_pre_phase1_$(date +%Y%m%d_%H%M%S).sql

# Or backup specific critical tables
docker exec ma-dealroom-wp mysqldump -u root -p ma_dealroom \
  wp_ma_deal_transactions \
  wp_ma_deal_task_definitions \
  wp_ma_deal_tasks \
  > backup_critical_tables_$(date +%Y%m%d_%H%M%S).sql
```

---

### Step 2: Run Migrations (IN ORDER!)

**⚠️ IMPORTANT:** Run migrations in this exact order:

#### Migration 007 (Foundation)
```bash
docker exec -i ma-dealroom-wp mysql -u root -p ma_dealroom < /home/snova/projects/dealroom/007_add_transaction_type_support.sql
```

**Validate Migration 007:**
```bash
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;

-- Verify transaction_type column added
SHOW COLUMNS FROM wp_ma_deal_transactions LIKE 'transaction_type';

-- Verify task definition filter columns added
SHOW COLUMNS FROM wp_ma_deal_task_definitions LIKE '%filter%';

-- Verify transaction types seeded
SELECT type_key, name FROM wp_ma_deal_transaction_types ORDER BY sort_order;
"
```

**Expected Results:**
- transaction_type column exists in wp_ma_deal_transactions
- 5 new columns in wp_ma_deal_task_definitions (transaction_type_filter, property_attribute_filter, is_legal_requirement, legal_citation, legal_deadline)
- 6 transaction types in wp_ma_deal_transaction_types

#### Migration 008 (Property Attributes)
```bash
docker exec -i ma-dealroom-wp mysql -u root -p ma_dealroom < /home/snova/projects/dealroom/008_create_property_attributes.sql
```

**Validate Migration 008:**
```bash
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;

-- Verify table created
SHOW TABLES LIKE 'wp_ma_deal_property_attributes';

-- Verify foreign key
SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'wp_ma_deal_property_attributes'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
"
```

**Expected Results:**
- wp_ma_deal_property_attributes table exists
- Foreign key to wp_ma_deal_transactions exists

#### Migration 009 (Security Deposits)
```bash
docker exec -i ma-dealroom-wp mysql -u root -p ma_dealroom < /home/snova/projects/dealroom/009_create_security_deposit_tracking.sql
```

**Validate Migration 009:**
```bash
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;

-- Verify table created
SHOW TABLES LIKE 'wp_ma_deal_security_deposits';

-- Verify compliance trigger created
SELECT TRIGGER_NAME, EVENT_MANIPULATION
FROM INFORMATION_SCHEMA.TRIGGERS
WHERE TRIGGER_SCHEMA = 'ma_dealroom'
  AND TRIGGER_NAME LIKE '%security_deposit%';
"
```

**Expected Results:**
- wp_ma_deal_security_deposits table exists
- check_security_deposit_compliance_before_update trigger exists

---

### Step 3: Verify No Errors

After running all 3 migrations, verify:

```bash
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;

-- Check transaction counts unchanged
SELECT COUNT(*) as transaction_count FROM wp_ma_deal_transactions;

-- Check task definitions unchanged
SELECT COUNT(*) as task_definition_count FROM wp_ma_deal_task_definitions;

-- Check all new tables exist
SELECT TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'ma_dealroom'
  AND TABLE_NAME IN ('wp_ma_deal_transaction_types',
                     'wp_ma_deal_property_attributes',
                     'wp_ma_deal_security_deposits');
"
```

**Expected:** All counts match pre-migration, all 3 new tables exist.

---

## Rollback Procedure (If Needed)

If something goes wrong, rollback in REVERSE order:

### Rollback Migration 009
```bash
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;
DROP TRIGGER IF EXISTS check_security_deposit_compliance_before_update;
DROP TABLE IF EXISTS wp_ma_deal_security_deposits;
"
```

### Rollback Migration 008
```bash
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;
DROP TABLE IF EXISTS wp_ma_deal_property_attributes;
"
```

### Rollback Migration 007
```bash
docker exec ma-dealroom-wp mysql -u root -p -e "
USE ma_dealroom;

ALTER TABLE wp_ma_deal_task_definitions
  DROP COLUMN IF EXISTS transaction_type_filter,
  DROP COLUMN IF EXISTS property_attribute_filter,
  DROP COLUMN IF EXISTS is_legal_requirement,
  DROP COLUMN IF EXISTS legal_citation,
  DROP COLUMN IF EXISTS legal_deadline;

ALTER TABLE wp_ma_deal_transactions
  DROP COLUMN IF EXISTS transaction_type;

DROP TABLE IF EXISTS wp_ma_deal_transaction_types;
"
```

### Complete Rollback from Backup
```bash
# Restore from backup file
docker exec -i ma-dealroom-wp mysql -u root -p ma_dealroom < backup_pre_phase1_YYYYMMDD_HHMMSS.sql
```

---

## After Migrations Complete

Once all 3 migrations run successfully, you're ready for the code updates:

### Code Files to Update:

1. **Transaction Model** (`ma-deal-room/src/Models/Transaction.php`)
   - Add `transaction_type` property
   - Add getter/setter methods

2. **TaskDefinition Model** (`ma-deal-room/src/Models/TaskDefinition.php`)
   - Add filter properties
   - Add `appliesTo(Transaction $transaction)` method

3. **PropertyAttributes Model** (NEW - create `ma-deal-room/src/Models/PropertyAttributes.php`)
   - Full model for property_attributes table

4. **SecurityDeposit Model** (NEW - create `ma-deal-room/src/Models/SecurityDeposit.php`)
   - Full model for security_deposits table
   - Compliance check methods

5. **TemplateEngine** (`ma-deal-room/src/Services/TemplateEngine.php`)
   - Update task filtering to use transaction_type_filter
   - Update to check property_attribute_filter

All code examples are in **`PHASE_1_IMPLEMENTATION_PLAN.md`** (Sections 5.1 through 5.5).

---

## Success Criteria

Phase 1 is complete when:

- ✅ All 3 migration scripts created (DONE)
- ⏳ All 3 migrations run successfully without errors
- ⏳ All validation queries return expected results
- ⏳ No existing transactions or tasks are broken
- ⏳ Code models updated to use new columns
- ⏳ TemplateEngine filters tasks by transaction type

---

## Current Status

**Completed:**
- ✅ Phase 1 planning document created
- ✅ Migration 007 created (transaction type support)
- ✅ Migration 008 created (property attributes)
- ✅ Migration 009 created (security deposit tracking)
- ✅ All migrations include validation and rollback scripts

**Next Actions:**
1. **Move migration files** to official migrations directory (if desired)
2. **Backup database** - Critical before running migrations!
3. **Run migration 007** - Test and validate
4. **Run migration 008** - Test and validate
5. **Run migration 009** - Test and validate
6. **Update code models** - Add new properties and methods
7. **Test integration** - Verify everything works together

---

## Estimated Time

- **Migration execution:** 10-15 minutes
- **Validation:** 10-15 minutes
- **Code updates:** 2-3 hours
- **Testing:** 2-3 hours
- **Total Phase 1:** 4-7 hours

---

## Support Documentation

- **Detailed Plan:** `PHASE_1_IMPLEMENTATION_PLAN.md`
- **Research Reference:** `MA_REAL_ESTATE_TRANSACTION_REQUIREMENTS.md`
- **Analysis Report:** `TASK_ANALYSIS_REPORT.md`
- **Architectural Decisions:** `CLAUDE_REVIEW_AND_DECISIONS.md`

---

## Questions or Issues?

If you encounter any issues:

1. **Check validation queries** - Each migration includes validation
2. **Review error messages** - SQL errors are usually descriptive
3. **Check rollback procedures** - Each migration can be reversed
4. **Restore from backup** - You did create a backup, right? 😊

---

**Phase 1 Status:** 🟢 READY FOR TESTING
**Next Phase:** Phase 2 - Task Reclassification (after Phase 1 validation)

Good luck with the migrations! 🚀
