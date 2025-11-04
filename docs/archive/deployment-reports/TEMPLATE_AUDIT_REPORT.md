# MA Deal Room - Template & Task Audit Report
**Date:** 2025-10-31
**Audited By:** Claude (AI Assistant)
**Templates Analyzed:** 5 YAML templates (base_transaction, condo, sfh_city_water, sfh_septic, multifamily)

---

## Executive Summary

Comprehensive audit of all deal room templates reveals **significant inconsistencies** across templates that will cause issues during task instantiation. Primary issues are:

1. **Critical:** Field naming inconsistencies (`owner_role` vs `assignee_role`, `follows` vs `depends_on`)
2. **Critical:** Duplicate tasks across templates (50+ tasks repeated)
3. **High:** Missing task categorization (no DB categories assigned)
4. **High:** Inconsistent dependency references (task names vs task IDs)
5. **Medium:** Inconsistent metadata (citations, durations, priorities missing)
6. **Medium:** Condition format variations (syntax differences)

**Recommended Action:** Complete restructuring to use modular task system with shared task definitions.

---

## Template Inventory

| Template | File | Tasks | Workflows | Conditional Tasks | Property Types | Status |
|----------|------|-------|-----------|-------------------|----------------|--------|
| Base Transaction | `base_transaction.yaml` | 148 | 11 | 53 (conditional) | All | Active |
| Condo | `condo.yaml` | 96 | 0 | 0 | Condo | Active |
| SFH City Water | `sfh_city_water.yaml` | 70 | 0 | 0 | SFH | Active |
| SFH Septic | `sfh_septic.yaml` | 92 | 0 | 0 | SFH | Active |
| Multifamily | `multifamily.yaml` | 100 | 0 | 0 | Multifamily | Active |

**Total Unique Tasks (estimated after deduplication):** ~250-300
**Total Task Instances:** 506
**Duplication Rate:** ~40-50%

---

## Critical Issues

### 1. Field Naming Inconsistencies

#### Issue: Dependency Field
**Impact:** Tasks cannot find dependencies, breaks workflow sequencing

| Template | Field Used | Example |
|----------|-----------|---------|
| base_transaction | `follows: ["Collect earnest money deposit"]` | Uses task title strings |
| condo, sfh_*, multifamily | `depends_on: ["loan_commitment_track"]` | Uses task ID strings |

**Problem:** TemplateEngine expects `depends_on` with task_key values, but base_transaction uses `follows` with task titles.

**Fix Required:** Standardize all to `depends_on` with task IDs.

---

#### Issue: Owner Role Field
**Impact:** Task assignment fails or uses wrong default

| Template | Field Used | Values |
|----------|-----------|--------|
| base_transaction | `owner_role: agent` | Simple roles (agent, seller, buyer) |
| condo, sfh_*, multifamily | `assignee_role: "ListingAgent"` | Specific roles (ListingAgent, BuyerAttorney) |

**Problem:** Database schema expects `owner_role`. Different field names will cause one to be ignored.

**Values Used Across Templates:**
- **base_transaction**: agent, seller, buyer, buyer_attorney, seller_attorney
- **Property-specific**: ListingAgent, BuyerAgent, Seller, Buyer, BuyerAttorney, SellerAttorney, HOA, Inspector, fire_dept_smoke_cert

**Fix Required:**
1. Standardize to `owner_role`
2. Map specific roles to DB enum values:
   - `agent` → `listing_agent` or `buyer_agent` (context-dependent)
   - `ListingAgent` → `listing_agent`
   - `BuyerAgent` → `buyer_agent`
   - etc.

---

#### Issue: Due Date Format
**Impact:** Some tasks won't have due dates calculated correctly

| Template | Format | Example |
|----------|--------|---------|
| base_transaction | `due_days: 0` | Relative to transaction start (implied) |
| condo, sfh_*, multifamily | `due: "Closing"`, `due_offset: "-21d"` | Anchor + offset |

**Problem:** Two different due date calculation systems. TaskScheduler expects anchor + offset format.

**Fix Required:** Convert all to `due: "Anchor"` + `due_offset: "±Nd"` format.

---

### 2. Task ID/Key Issues

#### Issue: Missing Task IDs in Base Template
**Impact:** Cannot reference base template tasks as dependencies

| Template | Has Task IDs? | Format |
|----------|---------------|--------|
| base_transaction | ❌ NO | Tasks have title only |
| condo, sfh_*, multifamily | ✅ YES | `id: "task_key"` |

**Problem:** Base template tasks cannot be referenced by `depends_on` in other templates.

**Fix Required:** Add unique `id` field to every task in base_transaction.

---

### 3. Duplicate Tasks Across Templates

#### High-Duplication Tasks (appear in 4-5 templates):

1. **agency_disclosure** - Appears in: condo, sfh_city_water, sfh_septic, multifamily
   - Identical title, description, citations
   - Should be in base template or task definitions table

2. **lead_paint_disclosure** - Appears in: condo, sfh_city_water, sfh_septic, multifamily
   - Identical title, description, citations
   - Condition: `property.year_built < 1978`

3. **loan_commitment_track** - Appears in: condo, sfh_city_water, sfh_septic, multifamily
   - Identical title, description

4. **loan_commitment_deadline** - Appears in: condo, sfh_city_water, sfh_septic, multifamily
   - Identical title, description
   - Depends on: `loan_commitment_track`

5. **appraisal_track** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

6. **appraisal_value_check** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

7. **home_inspection_schedule** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

8. **home_inspection_response** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

9. **municipal_lien** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

10. **smoke_co_inspection** - Appears in: condo, sfh_city_water, sfh_septic, multifamily
    - Property type condition: `property.type == 'SFH'` or `property.type == 'Multifamily'`

11. **smoke_co_compliance** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

12. **insurance_track** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

13. **clear_to_close_confirm** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

14. **utilities_transfer** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

15. **attorney_docs_review** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

16. **final_walkthrough_schedule** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

17. **property_vacant_clean** - Appears in: condo (property_vacant_clean), sfh_city_water, sfh_septic, multifamily (property_ready_transfer)

18. **keys_docs_ready** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

19. **final_walkthrough_complete** - Appears in: condo, sfh_city_water, sfh_septic, multifamily

**Total Duplicate Task Instances:** ~200+ (estimated)

**Recommendation:** Create shared task definitions in `wp_ma_deal_task_definitions` table. Templates should reference these instead of redefining.

---

## High Priority Issues

### 4. Missing Task Categorization

**Issue:** Zero tasks have `category` field assigned

Database defines 14 categories:
1. deal_setup
2. party_onboarding
3. communication
4. earnest_money
5. inspection
6. ps_agreement (Purchase & Sale)
7. financing
8. title_work
9. hoa_condo
10. pre_closing
11. closing
12. post_closing
13. property_specific
14. compliance

**Current State:** Templates use `workflows` which don't map cleanly to categories.

**Mapping Needed:**

| Workflow (base_transaction) | Suggested Category |
|------------------------------|-------------------|
| Deal Setup | deal_setup |
| Party Onboarding | party_onboarding |
| Earnest Money Management | earnest_money |
| Inspection Period | inspection |
| Purchase and Sale Agreement | ps_agreement |
| Financing | financing |
| Pre-Closing Preparation | pre_closing |
| Closing Week Activities | pre_closing |
| Closing Day | closing |
| Post-Closing | post_closing |

**Fix Required:** Add `category` field to all tasks.

---

### 5. Dependency Chain Issues

#### Broken Dependencies

**Issue:** Dependencies reference tasks that may not exist or use wrong identifiers

Examples:

1. **base_transaction** line 152:
   ```yaml
   follows: ["Collect earnest money deposit"]
   ```
   - References task by title (string match required)
   - Fragile: title change breaks dependency

2. **base_transaction** line 158:
   ```yaml
   follows: ["Deposit EMD into escrow account"]
   ```

3. **base_transaction** line 184:
   ```yaml
   follows: ["Schedule home inspection"]
   ```

**Circular Dependency Risk:** Not detected in current YAML

**Missing Dependencies:** Many sequential tasks lack explicit `depends_on`:
- Example: "Negotiate inspection items" → "Finalize inspection agreement" (implicit sequence, no dependency)

**Fix Required:**
1. Standardize to `depends_on` with task IDs
2. Validate all dependencies resolve to existing task IDs
3. Add missing dependencies for sequential workflows
4. Check for circular dependencies

---

#### Dependency Validation Results

**Tasks with Dependencies:**
- base_transaction: 27 tasks have `follows` field
- condo: 17 tasks have `depends_on` field
- sfh_city_water: 11 tasks have `depends_on` field
- sfh_septic: 18 tasks have `depends_on` field
- multifamily: 18 tasks have `depends_on` field

**Total Dependencies:** ~91 dependency relationships

**Issues Found:**
- ❌ base_transaction dependencies use task titles instead of IDs
- ⚠️ No validation that referenced tasks exist
- ⚠️ No circular dependency detection

---

### 6. Condition/Applicability Format Issues

**Issue:** Multiple condition field names and syntax variations

| Template | Field Name | Example |
|----------|-----------|---------|
| base_transaction (some tasks) | `condition: "party.buyer_added"` | Simple condition strings |
| condo, sfh_*, multifamily | `applies_if: "property.state == 'MA'"` | Expression-style conditions |
| base_transaction (conditional_tasks) | `condition: "property.type == 'Condo'"` | Top-level for task groups |

**Syntax Variations:**
- `property.state == 'MA'`
- `property.type == 'Condo'`
- `property.year_built < 1978`
- `property.has_septic == true`
- `property.has_well`
- `loan.type == 'FHA' || loan.type == 'VA'`
- `property.type == 'Condo' && property.has_balcony`
- `property.city IN ['Boston', 'Lynn', 'Lawrence', 'Haverhill']`
- `(property.has_well || property.has_septic) && property.has_generator`

**Problems:**
1. Inconsistent field names: `condition` vs `applies_if`
2. No validation of condition syntax
3. Property attributes referenced may not exist in transaction data
4. Logical operators vary: `&&`, `||`, `IN`, comparison operators

**Fix Required:**
1. Standardize to `applies_if`
2. Define formal condition syntax/grammar
3. Validate all referenced properties exist
4. Test conditions with sample data

---

## Medium Priority Issues

### 7. Missing or Inconsistent Metadata

#### Citations

**Issue:** Citations only in property-specific templates, missing from base

| Template | Has Citations? | Format |
|----------|---------------|--------|
| base_transaction | ❌ NO | Missing |
| condo, sfh_*, multifamily | ✅ PARTIAL | `citations: [{url: "...", title: "..."}]` |

**Tasks Without Citations:**
- All 148 tasks in base_transaction
- ~40% of tasks in property-specific templates

**Legal Risk:** Many tasks reference MA regulations but lack citation links.

**Fix Required:** Add citations to all regulation-based tasks, especially:
- Lead paint disclosure (EPA, MA regulations)
- Title 5 septic (310 CMR 15.000)
- Smoke/CO detectors (M.G.L. c.148)
- Municipal liens (M.G.L. c.60 §23)
- Condo 6(d) certificates (M.G.L. c.183A §6)

---

#### Estimated Duration

**Issue:** Present in some property-specific templates, missing from most tasks

| Template | Has estimated_duration? |
|----------|-------------------------|
| base_transaction | ❌ NO |
| condo | ✅ YES (some tasks) |
| sfh_*, multifamily | ✅ YES (some tasks) |

**Tasks With Duration:**
- condo_questionnaire: "HOA typically has 10-14 days to complete"
- title5_septic_inspection: "Find inspector 1-2 weeks; inspection 2-4 hours; report 1-2 weeks"
- smoke_co_inspection: "Schedule 2-3 weeks ahead; inspection takes 30-45 minutes"

**Missing Duration:** ~450+ tasks have no estimated duration

**Fix Required:** Add estimated_duration to all tasks for better planning.

---

#### Priority Levels

**Issue:** Only appears in reminder objects, not task-level

**Current Usage:** In reminders: `priority: "high"`

**Missing:** Task-level priority (low, normal, high, critical)

**Fix Required:** Add `priority` field to tasks, use for:
- Sorting/filtering in UI
- Escalation logic
- Agent assignment

---

#### Milestone Flags

**Issue:** Inconsistent milestone marking

| Template | Milestone Field | Usage |
|----------|----------------|-------|
| base_transaction | `milestone: true` | 14 tasks marked |
| condo, sfh_*, multifamily | `mandatory: true` | Different meaning? |

**Confusion:** `mandatory` vs `milestone` vs `required`
- `milestone: true` - Key transaction checkpoints
- `mandatory: true` - Must be completed
- `required: true` - Also means must be completed

**Fix Required:** Clarify semantics, standardize field names.

---

### 8. Reminder Format Inconsistencies

**Issue:** Multiple reminder formats

**Format 1** (base_transaction):
```yaml
reminders:
  - days_before: 1
    message: "Photographer coming tomorrow"
```

**Format 2** (property-specific):
```yaml
reminders:
  - offset: "-7d"
    channels: ["email"]
  - offset: "-1d"
    channels: ["email", "sms"]
    priority: "high"
```

**Problems:**
1. Different field names: `days_before` vs `offset`
2. Different formats: integer vs string with unit
3. Missing channels in Format 1
4. Inconsistent priority usage

**Fix Required:** Standardize to Format 2 (more expressive).

---

### 9. Vendor Integration Inconsistencies

**Issue:** Vendor references use different approaches

**Approach 1** - `vendor_type` field:
```yaml
vendor_type: "fire_dept_smoke_cert"
```

**Approach 2** - `actions` object:
```yaml
actions:
  - type: "request_vendor"
    vendor_type: "hoa_management"
```

**Vendor Types Used:**
- fire_dept_smoke_cert
- septic_inspector
- septic_contractor
- well_inspector
- hoa_management
- pool_inspector
- chimney_inspector
- radon_inspector
- oil_tank_inspector
- pest_inspector
- fire_inspector

**Fix Required:** Standardize to single approach (recommend `vendor_type` field + optional `actions`).

---

## Property-Specific Issues

### 10. Template-Specific Task Analysis

#### Condo Template (96 tasks)

**Unique Tasks:**
- Condo document gathering (6d certificate, HOA docs)
- HOA-specific tasks (financials, meeting minutes, insurance, questionnaire)
- Building amenities and transfer
- Renovation rules, pet policies

**Issues:**
- Heavy duplication with base (50+ tasks identical)
- Some tasks applicable to all properties (agency_disclosure, lead paint) should be in base

**Recommendations:**
- Move universal tasks to base
- Keep only condo-specific tasks in this template
- Use `extends: "base_transaction"` properly

---

#### SFH City Water Template (70 tasks)

**Unique Tasks:**
- Water final reading (Boston-specific)
- Single-family property features (garage, yard, attic, basement)
- Roof, siding, driveway inspections

**Issues:**
- Many tasks overlap with SFH Septic (should share common SFH tasks)
- Universal tasks duplicated from other templates

**Recommendations:**
- Create common SFH task definitions
- Differentiate only on water/septic/well-specific tasks

---

#### SFH Septic Template (92 tasks)

**Unique Tasks:**
- Title 5 septic inspection workflow (7 tasks)
- Well water testing and flow rate
- Septic system maintenance and documentation
- Well head protection and separation distance

**Issues:**
- Most tasks identical to SFH City Water except septic/well-specific
- Should extend common SFH template

**Recommendations:**
- Extract common SFH tasks
- Create septic-specific task group
- Create well-specific task group

---

#### Multifamily Template (100 tasks)

**Unique Tasks:**
- Tenant estoppel certificates
- Rental income verification
- Security deposit transfer and reconciliation
- Tenant notifications
- Operating expense analysis
- Multi-unit inspection coordination
- Property management transition

**Issues:**
- Some unique tasks well-defined (good example)
- Still duplicates many universal tasks

**Recommendations:**
- Good differentiation of multifamily-specific tasks
- Move universal tasks to base
- Consider separating by unit count (2-4 units vs 5+ units)

---

## Obsolete or Questionable Tasks

### Tasks to Review for Removal

1. **base_transaction** - "Schedule professional photography" (line 40)
   - **Question:** Is this a deal room task or marketing task?
   - **Recommendation:** Likely pre-listing, may not belong in transaction management

2. **base_transaction** - "Create virtual tour/walkthrough" (line 48)
   - **Question:** Marketing task?
   - **Recommendation:** Consider removing

3. **base_transaction** - "Send closing gift to buyer" (line 598)
   - **Question:** Is this tracked task or agent courtesy?
   - **Recommendation:** Optional/low priority

4. **base_transaction** - "Request client testimonials" (line 607)
   - **Question:** Marketing/CRM task?
   - **Recommendation:** Move to CRM system

5. **base_transaction** - "Add clients to CRM for follow-up" (line 612)
   - **Question:** Automated by CRM?
   - **Recommendation:** May not need tracking

### Regulatory Updates Needed

**Lead Paint Disclosure:**
- Current citations reference 2023 requirements
- Need annual review to ensure compliance
- EPA penalties updated regularly

**Title 5 Septic:**
- Last major update: 2024 (per notes)
- Review exemption periods (currently 2-3 years)
- Verify Board of Health procedures

**Smoke/CO Detectors:**
- M.G.L. c.148 requirements stable
- Local municipality requirements vary
- Fee schedules change

**Condo 6(d) Certificates:**
- M.G.L. c.183A §6(d) stable
- HOA processing times updated
- Fee schedules vary by management company

---

## Condition Evaluation Test Results

### Sample Property Test Cases

**Test Case 1: Pre-1978 Condo in Boston**
```
property.state = 'MA'
property.type = 'Condo'
property.year_built = 1965
property.city = 'Boston'
```

**Expected Tasks:**
- ✅ agency_disclosure (applies_if: state == 'MA')
- ✅ lead_paint_disclosure (applies_if: year_built < 1978)
- ✅ condo_docs_gathering (applies_if: type == 'Condo')
- ✅ condo_6d_request (applies_if: type == 'Condo')
- ✅ water_final_reading (applies_if: city IN ['Boston', ...])

**Issues Found:**
- ⚠️ Condition syntax not formally validated
- ⚠️ Property attributes must match exactly (case-sensitive)

---

**Test Case 2: New SFH with Septic and Well**
```
property.state = 'MA'
property.type = 'SFH'
property.year_built = 2020
property.has_septic = true
property.has_well = true
```

**Expected Tasks:**
- ✅ agency_disclosure
- ❌ lead_paint_disclosure (year_built >= 1978)
- ✅ title5_septic_assessment
- ✅ title5_septic_inspection
- ✅ well_water_comprehensive_test
- ✅ well_septic_distance_verification

**Issues Found:**
- ✅ Conditions evaluate correctly
- ⚠️ Depends on exact property attribute names

---

**Test Case 3: Multifamily with 4 units, built 1985**
```
property.state = 'MA'
property.type = 'Multifamily'
property.year_built = 1985
property.units = 4
```

**Expected Tasks:**
- ✅ agency_disclosure
- ❌ lead_paint_disclosure (year_built >= 1978)
- ✅ rental_docs_gathering
- ✅ tenant_estoppel_certificates
- ✅ smoke_co_inspection (multifamily)

**Issues Found:**
- ⚠️ No unit-count-specific tasks (2-4 units vs 5+)
- ⚠️ Same template used for duplex and large apartment building

---

### Condition Syntax Issues Found

1. **Inconsistent property path notation:**
   - `property.type` vs `property_type`
   - `property.has_septic` vs `has_septic`

2. **Boolean evaluation:**
   - `property.has_septic == true` vs `property.has_septic`
   - Both should work, but inconsistent

3. **Array/List operators:**
   - `property.city IN ['Boston', 'Lynn', ...]`
   - Non-standard SQL syntax, needs JS equivalent: `['Boston', 'Lynn'].includes(property.city)`

4. **Compound conditions:**
   - `property.type == 'Condo' && loan.type == 'FHA' || loan.type == 'VA'`
   - Operator precedence unclear, needs parentheses

5. **Missing property definitions:**
   - Many conditions reference properties not documented
   - Need property attribute schema

---

## Recommendations

### Immediate Actions (Phase 1-2)

1. **Standardize Field Names** (Critical)
   - Change all `assignee_role` → `owner_role`
   - Change all `follows` → `depends_on`
   - Change all `due_days` → `due` + `due_offset`
   - Standardize to `applies_if` for conditions

2. **Fix Dependency References** (Critical)
   - Add unique `id` to all base_transaction tasks
   - Convert base_transaction `follows` task names to IDs
   - Validate all dependency references resolve

3. **Add Task Categorization** (High)
   - Map all tasks to 14 DB categories
   - Add `category` field to every task

4. **Remove Duplicate Tasks** (High)
   - Identify 20-30 common tasks
   - Move to base_transaction or task definitions table
   - Remove from property-specific templates
   - Use task references instead

### Medium-Term Actions (Phase 3-4)

5. **Standardize Metadata**
   - Add citations to all regulatory tasks
   - Add estimated_duration to all tasks
   - Add priority levels
   - Clarify milestone/mandatory/required semantics
   - Standardize reminder format

6. **Validate Conditions**
   - Create property attribute schema
   - Define formal condition syntax
   - Validate all conditions
   - Add condition tests
   - Fix syntax issues (array operators, etc.)

7. **Fix Vendor Integration**
   - Standardize vendor_type usage
   - Document all vendor types
   - Ensure VendorPortalController compatibility

### Long-Term Actions (Phase 5-6)

8. **Migrate to Modular Task System**
   - Create task definitions in `wp_ma_deal_task_definitions`
   - Create template-task relationships in `wp_ma_deal_template_tasks`
   - Deprecate YAML-embedded tasks
   - Keep templates as lightweight wrappers

9. **Remove Obsolete Tasks**
   - Review marketing tasks (photography, virtual tour)
   - Review CRM tasks (testimonials, follow-ups)
   - Remove or mark optional

10. **Create Test Suite**
    - Property scenarios for each type
    - Validate task generation
    - Validate conditions
    - Validate dependencies
    - Performance testing (100+ tasks)

---

## Migration Path to Modular System

### Current State: YAML-Only

```
Template (YAML blob)
  ↓ contains
Tasks (inline definitions)
  ↓ instantiated as
wp_ma_deal_tasks (rows)
```

### Target State: Modular with Task Definitions

```
TaskDefinitions (wp_ma_deal_task_definitions)
  ↓ referenced by
TemplateTaskslink (wp_ma_deal_template_tasks)
  ↓ used by
Template (lightweight, just metadata)
  ↓ instantiates
wp_ma_deal_tasks (rows)
```

### Migration Steps:

1. **Extract Common Tasks** (20-30 universal tasks)
   - Create task definitions
   - Insert into `wp_ma_deal_task_definitions` with `is_system = true`

2. **Create Template-Task Links**
   - For each template, create relationships
   - Insert into `wp_ma_deal_template_tasks`
   - Set `sort_order` for display

3. **Update TemplateEngine**
   - Prefer modular system
   - Fallback to YAML only if no template_tasks exist

4. **Deprecate YAML Tasks**
   - Keep `template_yaml` for compatibility
   - Mark as legacy
   - Eventually remove

---

## Appendices

### Appendix A: Task Count by Category (Estimated)

| Category | Task Count | Examples |
|----------|-----------|----------|
| deal_setup | 15 | MLS listing, documents, photography |
| party_onboarding | 8 | Add parties, send welcome packets |
| communication | 5 | Notifications, updates |
| earnest_money | 4 | Collect, deposit, track EMD |
| inspection | 25 | Home, septic, well, pest, lead paint |
| ps_agreement | 7 | Draft, review, execute P&S |
| financing | 12 | Loan app, appraisal, commitment |
| title_work | 8 | Title search, lien certificate, insurance |
| hoa_condo | 20 | Condo-specific (6d, HOA docs, etc.) |
| pre_closing | 25 | Final prep, walkthrough, utilities |
| closing | 15 | Signing, recording, disbursement |
| post_closing | 12 | Follow-up, documentation, gifts |
| property_specific | 80 | Septic, well, multifamily, etc. |
| compliance | 15 | Smoke/CO, lead paint, registrations |

### Appendix B: Owner Role Mapping

| YAML Value | DB Enum Value | Party Role(s) |
|------------|---------------|---------------|
| agent | agent | buyer_agent, seller_agent |
| ListingAgent | agent | seller_agent |
| BuyerAgent | agent | buyer_agent |
| Seller | seller | seller |
| Buyer | buyer | buyer |
| SellerAttorney | seller_attorney | seller_attorney |
| BuyerAttorney | buyer_attorney | buyer_attorney |
| HOA | vendor | hoa_manager |
| Inspector | vendor | inspector |
| fire_dept_smoke_cert | vendor | fire_dept |

### Appendix C: Anchor Date Reference

| Anchor | Description | Typical Use |
|--------|-------------|-------------|
| FirstMeeting | Initial client meeting | Agency disclosure |
| Listing | Listing date | Pre-listing tasks |
| ListingDate | Same as Listing | Alternative name |
| Offer | Offer accepted date | Offer contingencies |
| PS | Purchase & Sale executed | Post-P&S tasks |
| Closing | Closing date | Pre-closing, closing tasks |

### Appendix D: File Locations

```
/home/snova/projects/dealroom/templates/
├── base_transaction.yaml (854 lines, ~148 tasks)
├── condo.yaml (713 lines, ~96 tasks)
├── sfh_city_water.yaml (677 lines, ~70 tasks)
├── sfh_septic.yaml (750 lines, ~92 tasks)
└── multifamily.yaml (790 lines, ~100 tasks)
```

---

## Conclusion

The template system is functional but requires significant cleanup to ensure reliability, maintainability, and scalability. **Primary focus should be on fixing critical field inconsistencies and removing duplicates** before adding new features.

**Estimated Cleanup Effort:** 20-30 hours spread across 6 phases.

**Priority Order:**
1. Fix critical field naming (enables system to work correctly)
2. Fix dependencies (enables proper workflow sequencing)
3. Remove duplicates (reduces maintenance burden by 40%)
4. Add categories (enables better filtering and reporting)
5. Standardize metadata (improves user experience)
6. Validate conditions (prevents runtime errors)

---

**End of Audit Report**
