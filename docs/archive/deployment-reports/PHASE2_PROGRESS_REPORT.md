# Phase 2 Progress Report: Template Standardization
**Date:** 2025-10-31
**Status:** In Progress (37% Complete)

---

## Progress Summary

### ✅ Completed: base_transaction.yaml (Partial)

**Workflows Standardized:** 7 of 11 workflows
**Tasks Converted:** 55 of ~148 tasks (37%)

| Workflow | Tasks | Status | Category Mapping |
|----------|-------|--------|------------------|
| Deal Setup | 7 | ✅ Complete | deal_setup, title_work |
| Party Onboarding | 8 | ✅ Complete | party_onboarding, communication, financing |
| Earnest Money Management | 4 | ✅ Complete | earnest_money |
| Inspection Period | 9 | ✅ Complete | inspection |
| Purchase and Sale Agreement | 7 | ✅ Complete | ps_agreement |
| Financing | 10 | ✅ Complete | financing |
| Pre-Closing Preparation | 10 | ✅ Complete | title_work, pre_closing |
| **Closing Week Activities** | ~8 | 🔄 Pending | pre_closing, closing |
| **Closing Day** | ~9 | 🔄 Pending | closing |
| **Post-Closing** | ~14 | 🔄 Pending | post_closing |
| **Conditional Tasks Section** | ~62 | 🔄 Pending | property_specific |

---

## Standardization Pattern Applied

### ✅ Changes Made to Each Task:

1. **Added unique `id` field:**
   ```yaml
   id: "task_key_snake_case"
   ```

2. **Added `category` field:**
   ```yaml
   category: deal_setup  # Maps to DB category enum
   ```

3. **Converted `due_days` → `due` + `due_offset`:**
   ```yaml
   # BEFORE:
   due_days: 5

   # AFTER:
   due: "PS"
   due_offset: "+5d"
   ```

4. **Converted `follows` → `depends_on` with task IDs:**
   ```yaml
   # BEFORE:
   follows: ["Collect earnest money deposit"]

   # AFTER:
   depends_on: ["collect_emd"]
   ```

5. **Converted `condition` → `applies_if`:**
   ```yaml
   # BEFORE:
   condition: "party.buyer_added"

   # AFTER:
   applies_if: "transaction.type IN ['buy_side', 'rental_tenant']"
   ```

6. **Converted `required` → `mandatory`:**
   ```yaml
   mandatory: true
   ```

7. **Standardized reminder format:**
   ```yaml
   # BEFORE:
   reminders:
     - days_before: 1
       message: "..."

   # AFTER:
   reminders:
     - offset: "-1d"
       channels: ["email"]
       message: "..."
   ```

8. **Added empty `depends_on: []` where no dependencies:**
   ```yaml
   depends_on: []  # Explicit no dependencies
   ```

---

## Remaining Work

### 🔄 base_transaction.yaml - Remaining Sections

#### 1. Closing Week Activities (~8 tasks)
**Lines:** 648-712
**Tasks to Convert:**
- confirm_wire_instructions
- obtain_funds
- conduct_final_walkthrough
- address_walkthrough_issues
- confirm_closing_attendance
- review_closing_disclosure
- review_seller_statement
- prepare_poa

**Categories:** pre_closing, closing

---

#### 2. Closing Day (~9 tasks)
**Lines:** 713-760
**Tasks to Convert:**
- final_loan_doc_review
- sign_closing_docs
- seller_signs_transfer
- transfer_keys_access
- record_deed_mortgage
- disburse_funds
- provide_buyer_closing_package
- provide_seller_closing_package
- update_mls_closed

**Categories:** closing

---

#### 3. Post-Closing (~14 tasks)
**Lines:** 761-859
**Tasks to Convert:**
- confirm_deed_recording
- distribute_recorded_docs
- file_transaction_docs
- process_commissions
- send_buyer_gift
- send_seller_gift
- request_testimonials
- add_to_crm
- schedule_30day_followup
- submit_to_broker
- confirm_title_policy_delivery
- provide_tax_info
- update_transaction_status

**Categories:** post_closing

**⚠️ Note:** Some tasks questionable for transaction tracking (gifts, CRM tasks) - consider removing or marking optional

---

#### 4. Conditional Tasks Section (~62 tasks)
**Lines:** 860-end
**Property-Specific Task Groups:**

##### Condo-Specific (14 tasks):
- obtain_condo_docs_6d
- review_hoa_financials
- review_hoa_rules
- etc.

**Category:** property_specific, hoa_condo

##### Septic-Specific (8 tasks):
- order_septic_inspection
- review_septic_results
- negotiate_septic_repairs
- etc.

**Category:** property_specific, compliance

##### Well-Specific (5 tasks):
- order_well_inspection
- review_water_test
- etc.

**Category:** property_specific

##### Pool, New Construction, Cash, Tenant-Occupied (35 tasks)
**Category:** property_specific

---

## Systematic Completion Guide

### Step-by-Step Conversion Process

For each remaining task, apply this transformation:

#### Template:

```yaml
- id: "[task_key_from_title]"
  title: [Original Title]
  category: [assign from mapping below]
  owner_role: [keep as-is]
  description: [keep as-is]
  due: "[Anchor: Listing|Offer|PS|Closing]"
  due_offset: "[±Nd]"
  [milestone: true] # if present
  [mandatory: true] # if required: true
  applies_if: "[condition if present]"
  depends_on: [["task_id_1", "task_id_2"] or []]
  [documents: ...] # if present
  [reminders: ...] # convert format
```

#### Category Mapping Guide:

| Workflow/Section | Primary Category | Secondary Category |
|------------------|------------------|--------------------|
| Deal Setup | deal_setup | title_work |
| Party Onboarding | party_onboarding | communication |
| Earnest Money | earnest_money | - |
| Inspection | inspection | - |
| P&S Agreement | ps_agreement | - |
| Financing | financing | - |
| Pre-Closing Prep | pre_closing | title_work |
| Closing Week | pre_closing | closing |
| Closing Day | closing | - |
| Post-Closing | post_closing | - |
| Condo Tasks | property_specific | hoa_condo |
| Septic/Well/Pool | property_specific | compliance |

#### Due Date Anchor Rules:

| Current `due_days` | Context | Convert To |
|--------------------|---------|------------|
| 0-10 (early workflow) | Pre-listing | `due: "Listing"`, `due_offset: "+Nd"` |
| 0-10 (offer phase) | Post-offer | `due: "Offer"`, `due_offset: "+Nd"` |
| 0-60 (post-P&S) | After P&S | `due: "PS"`, `due_offset: "+Nd"` |
| 0-30 (before close) | Pre-closing | `due: "Closing"`, `due_offset: "-Nd"` |
| 0 (at closing) | Closing day | `due: "Closing"`, `due_offset: "0d"` |
| 1-90 (after close) | Post-closing | `due: "Closing"`, `due_offset: "+Nd"` |

#### Dependency Conversion:

1. Identify `follows: [...]` field
2. Convert task names to task IDs:
   - "Collect earnest money deposit" → "collect_emd"
   - "Submit repair request or waiver" → "submit_repair_request"
   - Title case with spaces → lowercase_with_underscores
3. Replace: `depends_on: ["task_id"]`

#### Condition Conversion:

| Old Format | New Format |
|------------|------------|
| `condition: "party.buyer_added"` | `applies_if: "transaction.type IN ['buy_side']"` |
| `condition: "property.needs_special_inspection"` | `applies_if: "property.needs_special_inspection"` |
| `condition: "property.type == 'Condo'"` | `applies_if: "property.type == 'Condo'"` |
| `condition: "transaction.is_cash"` | `applies_if: "transaction.financing_type == 'cash'"` |

---

## Property-Specific Templates Status

### 🔄 Pending: condo.yaml, sfh_city_water.yaml, sfh_septic.yaml, multifamily.yaml

**Issue:** These templates already use the NEW format (have `id`, `depends_on`, etc.)

**Action Required:**
1. ✅ Field names are correct (`owner_role` not `assignee_role` confusion in some)
2. ✅ Most have IDs already
3. ⚠️ Missing `category` field (all tasks)
4. ⚠️ Some have `assignee_role` instead of `owner_role`
5. ✅ Already use `depends_on` instead of `follows`
6. ✅ Already use `applies_if` instead of `condition`
7. ⚠️ Need to check for duplicate tasks that should be moved to base

**Conversion Steps for Property-Specific Templates:**

1. **Add `category` field to all tasks**
2. **Convert `assignee_role` → `owner_role`** (if present)
3. **Identify and remove duplicate tasks** that exist in base_transaction
4. **Validate all `depends_on` references** resolve to existing task IDs

---

## Next Steps

### Immediate (Complete Phase 2):

1. **Finish base_transaction.yaml conversions:**
   - [ ] Closing Week Activities (8 tasks)
   - [ ] Closing Day (9 tasks)
   - [ ] Post-Closing (14 tasks)
   - [ ] Conditional Tasks (62 tasks)

2. **Standardize property-specific templates:**
   - [ ] condo.yaml - Add categories, check field names
   - [ ] sfh_city_water.yaml - Add categories, check field names
   - [ ] sfh_septic.yaml - Add categories, check field names
   - [ ] multifamily.yaml - Add categories, check field names

3. **Validate dependency chains:**
   - [ ] Run validation script to check all `depends_on` resolve
   - [ ] Check for circular dependencies
   - [ ] Verify dependency sequencing makes logical sense

### Phase 3: Remove Duplicates

1. **Identify common tasks** across all templates:
   - agency_disclosure (appears in 4 templates)
   - lead_paint_disclosure (appears in 4 templates)
   - loan_commitment_track (appears in 4 templates)
   - home_inspection_schedule (appears in 4 templates)
   - etc. (~20-30 tasks)

2. **Move to base_transaction or task definitions table**

3. **Remove from property-specific templates**

### Phase 4: Add Missing Metadata

1. Add `estimated_duration` where missing
2. Add `priority` levels
3. Add `citations` for regulatory tasks
4. Standardize `milestone` and `mandatory` flags

---

## Automated Completion Script (Pseudo-code)

```python
# For systematic conversion of remaining tasks

def convert_task(old_task, workflow_name, prev_task_id=None):
    # Generate task_key from title
    task_id = old_task['title'].lower().replace(' ', '_').replace('/', '_')

    # Map workflow to category
    category = WORKFLOW_CATEGORY_MAP[workflow_name]

    # Convert due date
    if workflow_name in ['Closing Week Activities', 'Closing Day']:
        anchor = 'Closing'
        offset = calculate_offset_from_closing(old_task.get('due_days', 0))
    elif workflow_name == 'Post-Closing':
        anchor = 'Closing'
        offset = f"+{old_task.get('due_days', 0)}d"
    elif workflow_name in ['Deal Setup']:
        anchor = 'Listing'
        offset = f"+{old_task.get('due_days', 0)}d"
    else:
        anchor = 'PS'
        offset = f"+{old_task.get('due_days', 0)}d"

    # Convert dependencies
    depends_on = []
    if 'follows' in old_task:
        for title in old_task['follows']:
            depends_on.append(title_to_task_id(title))
    elif prev_task_id:
        depends_on.append(prev_task_id)  # Sequential dependency

    # Convert condition
    applies_if = None
    if 'condition' in old_task:
        applies_if = convert_condition(old_task['condition'])

    # Build new task
    new_task = {
        'id': task_id,
        'title': old_task['title'],
        'category': category,
        'owner_role': old_task.get('owner_role'),
        'description': old_task['description'],
        'due': anchor,
        'due_offset': offset,
        'depends_on': depends_on or [],
    }

    # Add optional fields
    if old_task.get('milestone'):
        new_task['milestone'] = True
    if old_task.get('required'):
        new_task['mandatory'] = True
    if applies_if:
        new_task['applies_if'] = applies_if
    if 'documents' in old_task:
        new_task['documents'] = old_task['documents']
    if 'reminders' in old_task:
        new_task['reminders'] = convert_reminders(old_task['reminders'])

    return new_task
```

---

## Summary

**Completed:** 55 tasks across 7 workflows in base_transaction.yaml
**Remaining:** 93 tasks in base_transaction + full cleanup of 4 property-specific templates
**Pattern Established:** ✅ Clear, repeatable conversion process documented
**Ready For:** Systematic completion or automated script

---

**End of Progress Report**
