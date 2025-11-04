# Task & Template Reorganization Assignment
## For: wp-data-architect / gemini-implementer / claude-db Agent

**Assigned by:** Claude (System Architect)
**Date:** 2025-10-30
**Priority:** High
**Complexity:** High - Multi-file database reorganization

---

## Mission Objective

Review, analyze, and reorganize ALL existing tasks and templates in the MA Deal Room system to properly align with Massachusetts real estate transaction types and property types based on legal requirements and industry best practices.

---

## Context & Background

### Problem Statement

Currently, tasks and templates may not be properly categorized by transaction type and property type. For example:
- ❌ **WRONG:** "Sign Listing Agreement" task appearing in buy-side purchase transactions
- ✅ **RIGHT:** "Sign Listing Agreement" only in sell-side listing transactions
- ❌ **WRONG:** "Sign Buyer Agency Agreement" appearing in rental transactions
- ✅ **RIGHT:** "Sign Buyer Agency Agreement" only in buy-side purchase transactions
- ❌ **WRONG:** Title 5 septic tasks appearing in condo transactions
- ✅ **RIGHT:** Title 5 septic tasks only when property has septic system

### Your Role

You are the data architect responsible for systematically reviewing and reorganizing all tasks and templates. I (Claude) will serve as the project manager and will review your work, provide feedback, and make final approval decisions.

---

## Required Reading

**CRITICAL:** Before starting any work, read this comprehensive reference document:

📄 **`/home/snova/projects/dealroom/MA_REAL_ESTATE_TRANSACTION_REQUIREMENTS.md`**

This document contains:
- Complete taxonomy of MA transaction types
- Complete taxonomy of property types
- Universal tasks (apply to ALL transactions)
- Transaction-type specific tasks (apply only to certain transaction types)
- Property-type specific tasks (apply only to certain property types)
- MA legal requirements and timelines with citations
- Task assignment matrix

**This is your source of truth.** All reorganization decisions must be validated against this reference.

---

## Data Sources to Analyze

### 1. Database Tables

**Task Definitions:**
- Table: `wp_ma_deal_task_definitions`
- Location: Database migration `/home/snova/projects/dealroom/006_create_modular_task_system.sql`
- Review all existing task definitions

**Templates:**
- Table: `wp_ma_deal_templates`
- Review all existing templates

**Template Tasks Junction:**
- Table: `wp_ma_deal_template_tasks`
- Review how tasks are currently linked to templates

### 2. YAML Template Files

Review all YAML template files to extract existing tasks:
- `/home/snova/projects/dealroom/templates/base_transaction.yaml` (~100+ tasks)
- `/home/snova/projects/dealroom/templates/condo.yaml` (~80+ tasks)
- `/home/snova/projects/dealroom/templates/sfh_city_water.yaml` (~80+ tasks)
- `/home/snova/projects/dealroom/templates/sfh_septic.yaml` (~90+ tasks)
- `/home/snova/projects/dealroom/templates/multifamily.yaml` (~90+ tasks)

### 3. Code Files

**Models:**
- `/home/snova/projects/dealroom/ma-deal-room/src/Models/Task.php`
- `/home/snova/projects/dealroom/ma-deal-room/src/Models/TaskDefinition.php`
- `/home/snova/projects/dealroom/ma-deal-room/src/Models/Template.php`
- `/home/snova/projects/dealroom/ma-deal-room/src/Models/TemplateTask.php`

**Services:**
- `/home/snova/projects/dealroom/ma-deal-room/src/Services/TemplateEngine.php`
- `/home/snova/projects/dealroom/ma-deal-room/src/Services/TaskAssignmentService.php`

---

## Deliverables

### Deliverable 1: Analysis Report

**File:** `/home/snova/projects/dealroom/TASK_ANALYSIS_REPORT.md`

Create a comprehensive markdown report containing:

#### Section 1: Current State Analysis
- Total count of existing tasks across all templates
- List of all unique tasks found in YAML files
- Current categorization issues identified
- Tasks that appear in wrong transaction types
- Tasks that are missing transaction type restrictions
- Tasks that are missing property type restrictions
- Duplicate or redundant tasks

#### Section 2: Task Classification

Create a table for EVERY existing task with classification:

| Task Key | Task Title | Classification | Applies To | Rationale | Citation |
|----------|-----------|----------------|------------|-----------|----------|
| agency_disclosure | Present MA Agency Disclosure | Universal | ALL transactions | MA law requires at first meeting | MA Licensing Regs |
| sign_listing_agreement | Sign Listing Agreement | Sell Side Only | transaction_type='sell_side' | Only sellers sign listing agreements | Standard practice |
| sign_buyer_agency | Sign Buyer Agency Agreement | Buy Side Only | transaction_type='buy_side' | Only buyers sign buyer agency | NAR 2025 |
| title5_inspection | Schedule Title 5 Inspection | Property-Specific | property.has_septic=true | Only required for septic systems | 310 CMR 15.000 |
| condo_6d_request | Request 6(d) Certificate | Property-Specific | property_type='condo' | Only condos require 6(d) | M.G.L. c. 183A, § 6(d) |

**Instructions:**
- Classify EVERY task found in the system
- Use the reference document to determine proper classification
- Provide legal citation or industry practice rationale for each classification
- Flag any tasks you're uncertain about for my review

#### Section 3: Template Recommendations

For each transaction type + property type combination, recommend template structure:

**Example:**
```markdown
### Template: Buy Side - Condo Purchase

**Should Include:**
- All Universal Tasks (36 tasks)
- All Buy Side Tasks (42 tasks)
- All Condo-Specific Tasks (18 tasks)
- Lead Paint tasks (if build year < 1978)

**Should EXCLUDE:**
- Sell Side tasks (listing agreement, seller disclosures, etc.)
- Rental tasks (lease, tenant screening, etc.)
- SFH-specific tasks (Title 5, well testing)
- Commercial tasks (environmental assessments, etc.)

**Total Estimated Tasks:** 96 tasks
```

Repeat for all combinations:
- Buy Side: SFH (city water), SFH (septic), SFH (septic+well), Condo, Multifamily, Land, Commercial
- Sell Side: SFH (city water), SFH (septic), SFH (septic+well), Condo, Multifamily, Land, Commercial
- Rental Landlord: SFH, Condo, Multifamily
- Rental Tenant: SFH, Condo, Multifamily
- Commercial: Office, Retail, Industrial, Mixed-Use

#### Section 4: Issues & Recommendations

- Critical issues found (tasks in wrong categories, missing legal requirements, etc.)
- Recommended changes to database schema (if needed)
- Recommended changes to `applies_if` logic
- Missing tasks that should be added based on MA requirements
- Obsolete tasks that should be removed or deprecated

---

### Deliverable 2: Reorganization Plan

**File:** `/home/snova/projects/dealroom/TASK_REORGANIZATION_PLAN.md`

Create a detailed implementation plan containing:

#### Phase 1: Database Schema Updates (if needed)
- Required changes to `wp_ma_deal_task_definitions` table
- Required changes to `wp_ma_deal_templates` table
- Required changes to `wp_ma_deal_template_tasks` table
- New fields needed (e.g., `transaction_type_filter`, `property_type_filter`)

#### Phase 2: Task Definition Updates

For each task that needs changes, specify:

```markdown
### Task: sign_listing_agreement

**Current State:**
- `applies_if`: null
- `transaction_type_filter`: null
- Appears in templates: base_transaction.yaml

**Proposed Changes:**
- `applies_if`: "transaction_type == 'sell_side'"
- `transaction_type_filter`: ['sell_side']
- Remove from: base_transaction.yaml (universal)
- Add to: Templates for sell-side only

**Rationale:** Listing agreements are ONLY signed in sell-side transactions per MA standard practice.

**SQL Update:**
```sql
UPDATE wp_ma_deal_task_definitions
SET applies_if = "transaction_type == 'sell_side'",
    metadata = JSON_SET(metadata, '$.transaction_type_filter', JSON_ARRAY('sell_side'))
WHERE task_key = 'sign_listing_agreement';
```
```

**Provide this for EVERY task that needs updating.**

#### Phase 3: Template Reorganization

Specify exactly which tasks should be in which templates.

#### Phase 4: Migration Script

Provide SQL migration script or PHP script to implement all changes.

#### Phase 5: Validation & Testing

- Test cases to verify correct task application
- Edge cases to test
- Validation queries to confirm data integrity

---

### Deliverable 3: Implementation Code/Scripts

**Files:** Create as needed in `/home/snova/projects/dealroom/migrations/` or appropriate location

Provide:
1. **SQL Migration Script** - Database updates for task definitions
2. **YAML Template Updates** - Updated template files
3. **Validation Script** - PHP or SQL script to validate reorganization
4. **Rollback Script** - In case we need to revert changes

---

## Work Process & Collaboration

### Your Process:

1. **Read the reference document thoroughly** - `/home/snova/projects/dealroom/MA_REAL_ESTATE_TRANSACTION_REQUIREMENTS.md`
2. **Explore and analyze current tasks** - Use Read, Grep, Glob tools to examine all existing tasks
3. **Create Analysis Report** - Document current state and classifications
4. **Share Analysis Report with me (Claude)** - I will review and provide feedback
5. **Wait for my feedback** - Do NOT proceed to implementation until I approve your analysis
6. **Incorporate my feedback** - Make revisions based on my review
7. **Create Reorganization Plan** - Detail exactly what changes to make
8. **Share Reorganization Plan with me** - I will review and approve/reject/request changes
9. **Iterate until I approve** - We may go through multiple rounds
10. **Implement approved changes** - Only after I give final approval
11. **Create validation scripts** - Ensure changes work correctly
12. **Share implementation results** - I will perform final verification

### Communication Protocol:

- **Create markdown files for all analysis and plans** - Do NOT just output to console
- **Save all work in `/home/snova/projects/dealroom/`** - Persistent documentation
- **Reference specific line numbers** - When discussing tasks, use `file:line` format
- **Provide rationale for every decision** - With legal or practice citations
- **Ask questions if uncertain** - Better to ask than make wrong assumptions
- **Mark items for my review** - Flag anything you're unsure about

### Review Cycles:

I will review your work and respond with:
- ✅ **APPROVED** - Proceed to next phase
- 🔄 **REVISE** - Make specific changes and resubmit
- ❌ **REJECTED** - Rethink approach and start over
- ❓ **CLARIFICATION NEEDED** - Answer questions before proceeding

**DO NOT PROCEED WITHOUT MY APPROVAL AT EACH STAGE.**

---

## Success Criteria

Your work will be considered successful when:

1. ✅ All existing tasks are properly classified by transaction type and property type
2. ✅ No task appears in incompatible transaction types
3. ✅ All MA legal requirements are properly represented with citations
4. ✅ Universal tasks are clearly identified and apply to all transactions
5. ✅ Transaction-specific tasks only appear in appropriate transaction types
6. ✅ Property-specific tasks only appear when that property attribute is present
7. ✅ All templates generate appropriate task lists for their transaction/property type
8. ✅ `applies_if` logic is correct and tested
9. ✅ Database schema supports the reorganization
10. ✅ Migration scripts are safe and include rollback capability
11. ✅ Documentation is complete and clear
12. ✅ I (Claude) have reviewed and approved all deliverables

---

## Critical Rules & Constraints

### DO:
- ✅ Read the entire reference document before starting
- ✅ Create markdown documentation for all analysis
- ✅ Provide legal citations for MA-specific requirements
- ✅ Flag uncertain items for my review
- ✅ Test your logic and provide validation scripts
- ✅ Preserve existing data (no destructive changes without approval)
- ✅ Follow the review cycle (wait for my approval)

### DO NOT:
- ❌ Make assumptions about MA law without citing sources
- ❌ Proceed to implementation without my approval
- ❌ Delete or modify data without migration scripts
- ❌ Skip documentation
- ❌ Rush through analysis
- ❌ Implement changes that aren't in the approved plan
- ❌ Ignore edge cases
- ❌ Make irreversible changes without rollback capability

---

## Resources Available to You

### Tools:
- **Read** - Read files and examine code
- **Grep** - Search for patterns in code
- **Glob** - Find files by pattern
- **Bash** - Run SQL queries, check database
- **Write** - Create documentation and scripts
- **Edit** - Modify existing files

### Reference Materials:
- Primary: `/home/snova/projects/dealroom/MA_REAL_ESTATE_TRANSACTION_REQUIREMENTS.md`
- Database schema: `/home/snova/projects/dealroom/006_create_modular_task_system.sql`
- Templates: `/home/snova/projects/dealroom/templates/*.yaml`
- Models: `/home/snova/projects/dealroom/ma-deal-room/src/Models/`

### Support:
- **Ask me (Claude) questions** - I'm here to clarify and guide
- **Request additional research** - If you need more info about MA law
- **Propose alternative approaches** - If you see a better way

---

## Timeline Expectations

**Phase 1 - Analysis (1-2 hours):**
- Read reference document
- Analyze existing tasks
- Create analysis report
- Submit for my review

**Phase 2 - Planning (1-2 hours after my approval):**
- Create reorganization plan
- Design migration approach
- Submit for my review

**Phase 3 - Implementation (2-3 hours after my approval):**
- Write migration scripts
- Update templates
- Create validation scripts
- Submit for my review

**Phase 4 - Testing & Refinement (1-2 hours):**
- Address my feedback
- Refine implementation
- Final validation
- Completion

**Total Estimated Time:** 5-9 hours of focused work + review cycles

---

## Getting Started

### Step 1: Acknowledge Assignment
When you receive this task, respond with:
- Confirmation you've read this assignment
- Confirmation you've read the reference document
- Your understanding of the objective
- Any immediate questions or clarifications needed

### Step 2: Begin Analysis
Start by reading the reference document and exploring the existing task structure.

### Step 3: Create Analysis Report
Document your findings in `/home/snova/projects/dealroom/TASK_ANALYSIS_REPORT.md`

### Step 4: Submit for Review
Let me (Claude) know when your analysis is ready for review.

---

## Questions?

If anything is unclear:
1. Re-read this assignment
2. Re-read the reference document
3. Ask me specific questions
4. Propose your understanding for me to validate

---

## Final Notes

This is a critical project that will improve the accuracy and compliance of the MA Deal Room system. Take your time, be thorough, and don't hesitate to ask questions. I'm here to support you, provide feedback, and ensure we get this right.

Remember: **I make the final decisions. You are the data architect, I am the project manager and final approver.**

Let's work together to create a properly organized task system that accurately reflects Massachusetts real estate law and practice.

Good luck! 🚀

---

**Assigned by:** Claude (System Architect)
**Assignment Date:** 2025-10-30
**Status:** PENDING AGENT ACCEPTANCE
