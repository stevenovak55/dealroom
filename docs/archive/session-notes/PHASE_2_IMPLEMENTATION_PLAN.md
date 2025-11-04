# Phase 2: Task Reclassification Implementation Plan

**Date:** 2025-10-31
**Status:** Ready for Execution
**Phase:** 2 of 5 (Task Reclassification)

---

## Overview

Phase 2 involves reclassifying 285 existing task definitions to add proper `transaction_type_filter` and `property_attribute_filter` values. This enables transaction-type-specific and property-conditional task application.

---

## Current State

- **Total Task Definitions:** 285
- **Tasks with transaction_type_filter:** 0 (0%)
- **Tasks with property_attribute_filter:** 0 (0%)
- **Tasks requiring classification:** 285 (100%)

---

## Classification Strategy

Based on TASK_ANALYSIS_REPORT.md, tasks will be classified into:

### 1. Universal Tasks (7-16 tasks)
- **Filter:** `transaction_type_filter = NULL` (apply to ALL)
- **Exceptions:** Some tasks exclude rentals
- **Examples:**
  - Agency disclosure
  - Initial consultation
  - Coordinate with attorneys
  - Transaction documentation

### 2. Universal (Except Rentals) Tasks (~12 tasks)
- **Filter:** `transaction_type_filter = 'buy_side,sell_side,commercial_buy,commercial_sell'`
- **Examples:**
  - Order title search
  - Review title report
  - Final walkthrough
  - Attend closing
  - Verify deed recording

### 3. Buy-Side Only Tasks (~42 tasks)
- **Filter:** `transaction_type_filter = 'buy_side'` (or `buy_side,commercial_buy`)
- **Examples:**
  - Sign buyer agency agreement
  - Obtain mortgage pre-approval
  - Schedule home inspection
  - Review appraisal report
  - Coordinate final walkthrough

### 4. Sell-Side Only Tasks (~47 tasks)
- **Filter:** `transaction_type_filter = 'sell_side'` (or `sell_side,commercial_sell`)
- **Examples:**
  - Execute listing agreement
  - Complete CMA
  - Schedule professional photography
  - Enter listing in MLS
  - Receive and review offers

### 5. Property-Specific Tasks (~15 tasks)
- **Filter:** `property_attribute_filter` set to required attributes
- **Examples:**
  - `["has_septic"]` - Title 5 septic inspection
  - `["has_well"]` - Well water testing
  - `["has_pool"]` - Pool safety inspection
  - `["tenant_occupied"]` - Tenant notification tasks

### 6. Rental Tasks (~27+ tasks)
- **Filter:** `transaction_type_filter = 'rental_landlord'` or `'rental_tenant'`
- **Note:** Most rental tasks don't exist yet (Phase 3)

---

## Implementation Approach

### Step 1: Pattern-Based Classification

Use keyword matching on `task_key` and `title` fields to identify task types:

#### Universal Task Patterns:
```sql
- 'agency' OR 'disclosure'
- 'client-information' OR 'add-client'
- 'consultation' OR 'initial-meeting'
- 'timeline' OR 'transaction-timeline'
- 'coordinate-with-attorneys'
- 'maintain-transaction-docs' OR 'documentation'
```

#### Buy-Side Patterns:
```sql
- 'buyer%' (buyer agency, buyer fee, buyer financial, etc.)
- 'mortgage' OR 'pre-approval' OR 'preapproval' OR 'loan'
- 'appraisal' (buyer side of appraisal coordination)
- 'home-inspection' OR 'inspection-report-buyer'
- 'offer-to-purchase' OR 'prepare-offer'
- 'earnest-money' OR 'deposit%'
```

#### Sell-Side Patterns:
```sql
- 'listing%' (listing agreement, listing price, etc.)
- 'seller%' (seller disclosure, seller net proceeds, etc.)
- 'CMA' OR 'comparative-market-analysis'
- 'photography' OR 'virtual-tour' OR 'staging'
- 'MLS' OR 'enter-listing'
- 'open-house' OR 'showing%'
- 'lockbox' OR 'yard-sign'
- 'receive-offers' OR 'review-offers' OR 'counter-offer'
```

#### Property-Specific Patterns:
```sql
- 'septic' OR 'title-5' -> has_septic
- 'well' OR 'water-test' -> has_well
- 'pool' -> has_pool
- '6d' OR '6(d)' OR 'condo-certificate' -> (condo specific)
- 'tenant-occupied' OR 'tenant-notification' -> tenant_occupied
```

### Step 2: Manual Verification

After automated classification, review edge cases:
- Tasks that could apply to multiple types
- Tasks with complex conditional logic
- Tasks requiring legal citation

### Step 3: Database Updates

Execute SQL UPDATE statements in batches:
1. Universal tasks (no filter)
2. Universal except rentals
3. Buy-side tasks
4. Sell-side tasks
5. Property-specific tasks
6. Combined filters (type + property)

---

## SQL Update Patterns

### Pattern 1: Set Transaction Type Filter
```sql
UPDATE wp_ma_deal_task_definitions
SET transaction_type_filter = 'buy_side'
WHERE task_key IN ('list', 'of', 'task', 'keys');
```

### Pattern 2: Set Property Attribute Filter
```sql
UPDATE wp_ma_deal_task_definitions
SET property_attribute_filter = '["has_septic"]'
WHERE task_key LIKE '%septic%' OR title LIKE '%septic%';
```

### Pattern 3: Set Both Filters
```sql
UPDATE wp_ma_deal_task_definitions
SET
    transaction_type_filter = 'buy_side,sell_side',
    property_attribute_filter = '["has_septic"]'
WHERE task_key = 'schedule-and-complete-title-5-septic-inspection';
```

### Pattern 4: Set Legal Requirement
```sql
UPDATE wp_ma_deal_task_definitions
SET
    is_legal_requirement = 1,
    legal_citation = '310 CMR 15.000',
    legal_deadline = 'Before deed transfer'
WHERE task_key = 'schedule-and-complete-title-5-septic-inspection';
```

---

## Execution Plan

### Pre-Execution
1. ✅ Backup database (already done in Phase 1)
2. ✅ Review TASK_ANALYSIS_REPORT.md
3. Create classification SQL script
4. Test on small sample (5-10 tasks)
5. Validate test results

### Execution
1. **Batch 1:** Update universal tasks (no filter)
2. **Batch 2:** Update universal except rentals
3. **Batch 3:** Update buy-side tasks
4. **Batch 4:** Update sell-side tasks
5. **Batch 5:** Update property-specific tasks
6. **Batch 6:** Update legal requirements
7. **Batch 7:** Handle edge cases manually

### Post-Execution
1. Run validation queries
2. Check for unclassified tasks
3. Verify no misclassifications
4. Test task instantiation
5. Document results

---

## Validation Queries

### Check Classification Coverage
```sql
SELECT
    COUNT(*) as total_tasks,
    COUNT(CASE WHEN transaction_type_filter IS NOT NULL THEN 1 END) as with_type_filter,
    COUNT(CASE WHEN property_attribute_filter IS NOT NULL THEN 1 END) as with_property_filter,
    COUNT(CASE WHEN transaction_type_filter IS NULL AND property_attribute_filter IS NULL THEN 1 END) as no_filter
FROM wp_ma_deal_task_definitions;
```

### Check Distribution
```sql
SELECT
    COALESCE(transaction_type_filter, 'UNIVERSAL') as filter_type,
    COUNT(*) as task_count
FROM wp_ma_deal_task_definitions
GROUP BY transaction_type_filter
ORDER BY task_count DESC;
```

### Find Unclassified Tasks
```sql
SELECT task_key, title, owner_role
FROM wp_ma_deal_task_definitions
WHERE transaction_type_filter IS NULL
  AND property_attribute_filter IS NULL
  AND task_key NOT IN ('list', 'of', 'known', 'universal', 'tasks')
ORDER BY task_key;
```

### Verify No Conflicts
```sql
-- Tasks that might be misclassified
SELECT task_key, title, transaction_type_filter
FROM wp_ma_deal_task_definitions
WHERE (task_key LIKE '%buyer%' AND transaction_type_filter LIKE '%sell%')
   OR (task_key LIKE '%seller%' AND transaction_type_filter LIKE '%buy%')
   OR (task_key LIKE '%listing%' AND transaction_type_filter LIKE '%buy%');
```

---

## Rollback Procedure

If classification needs to be reversed:

```sql
-- Backup current state first
CREATE TABLE wp_ma_deal_task_definitions_phase2_backup AS
SELECT * FROM wp_ma_deal_task_definitions;

-- Rollback (remove all filters)
UPDATE wp_ma_deal_task_definitions
SET
    transaction_type_filter = NULL,
    property_attribute_filter = NULL,
    is_legal_requirement = 0,
    legal_citation = NULL,
    legal_deadline = NULL;

-- Restore from backup if needed
-- DROP TABLE wp_ma_deal_task_definitions;
-- RENAME TABLE wp_ma_deal_task_definitions_phase2_backup TO wp_ma_deal_task_definitions;
```

---

## Expected Results

After Phase 2 completion:

| Category | Task Count | % of Total |
|----------|------------|------------|
| Universal (all types) | ~16 | 6% |
| Universal (except rentals) | ~12 | 4% |
| Buy-Side Only | ~42 | 15% |
| Sell-Side Only | ~47 | 16% |
| Both Buy & Sell | ~150 | 53% |
| Property-Specific | ~15 | 5% |
| Unclassified/Edge Cases | ~3 | 1% |

**Total Classified:** ~285 tasks (100%)

---

## Success Criteria

Phase 2 is complete when:

- ✅ All 285 tasks reviewed and classified
- ✅ Transaction type filters applied where appropriate
- ✅ Property attribute filters applied where appropriate
- ✅ Legal requirements flagged with citations
- ✅ Validation queries return expected distributions
- ✅ No obvious misclassifications found
- ✅ Test transaction instantiation works correctly

---

## Risk Mitigation

### Risk 1: Misclassification
- **Mitigation:** Review classifications against TASK_ANALYSIS_REPORT
- **Validation:** Run conflict detection queries
- **Backup:** Can rollback individual tasks if needed

### Risk 2: Tasks Apply to Multiple Types
- **Mitigation:** Use comma-separated transaction types: `'buy_side,sell_side'`
- **Example:** Title search applies to both buy and sell

### Risk 3: Rental/Commercial Tasks
- **Mitigation:** Phase 2 focuses on buy/sell only
- **Note:** Rental and commercial tasks are Phase 3 & 4

---

## Timeline

- **Planning:** 30 minutes (document creation)
- **SQL Script Creation:** 1-2 hours (classification rules)
- **Testing:** 30 minutes (small sample)
- **Execution:** 30 minutes (run updates)
- **Validation:** 30 minutes (verify results)
- **Total Estimated Time:** 3-4 hours

---

## Next Steps

1. Create SQL classification script
2. Test on sample tasks
3. Execute full classification
4. Validate results
5. Document any edge cases
6. Test task instantiation with classified tasks

**Ready to proceed with SQL script creation!**
