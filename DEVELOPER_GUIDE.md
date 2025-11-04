# MA Deal Room - Developer Guide

**Version:** 2.0
**Last Updated:** 2025-10-31
**For:** Developers maintaining the template system

---

## 📖 **Table of Contents**

1. [Architecture Overview](#architecture-overview)
2. [Template Structure](#template-structure)
3. [Adding New Tasks](#adding-new-tasks)
4. [Modifying Existing Tasks](#modifying-existing-tasks)
5. [Creating New Property Types](#creating-new-property-types)
6. [Validation & Testing](#validation--testing)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

---

## 🏗️ **Architecture Overview**

### **Template Hierarchy**

```
base_transaction.yaml (125 universal tasks)
    ↓ extends
├── sfh_septic.yaml (26 property-specific tasks)
├── sfh_city_water.yaml (24 property-specific tasks)
├── condo.yaml (28 property-specific tasks)
└── multifamily.yaml (31 property-specific tasks)
```

### **How It Works**

1. **base_transaction.yaml** contains tasks common to all MA transactions
2. Property templates **extend** base and add property-specific tasks
3. TemplateEngine merges base + property tasks when creating transaction
4. Applicability conditions filter tasks based on property attributes

---

## 📄 **Template Structure**

### **Base Template Format**

```yaml
---
template_id: "base_transaction"
version: "1.0.0"
title: "Base Transaction Template"
name: "Base Transaction Template"
type: universal
description: "Comprehensive base template for all transaction types"
created_date: "2025-10-30"
author: "MA Deal Room Team"
property_types:
  - SFH
  - Condo
  - Multifamily
  - Commercial
  - Land

workflows:
  - name: "Deal Setup"
    description: "Initial transaction setup"
    tasks:
      - id: "task_unique_id"
        title: "Human-Readable Task Title"
        category: deal_setup
        priority: "normal"
        description: "Detailed description of what needs to be done"
        mandatory: true
        applies_if: "property.state == 'MA'"
        due: "Listing"
        due_offset: "+0d"
        owner_role: agent
        depends_on: []
        reminders:
          - offset: "-7d"
            channels: ["email"]
        notes: "Additional guidance for users"
```

### **Property Template Format**

```yaml
---
template_id: "sfh_septic"
version: "2.0.0"
title: "Single-Family Home (Septic System)"
description: "Tasks for SFH with septic"
property_types:
  - "SFH"
created_date: "2025-10-30"
author: "MA Deal Room Team"

required_attributes:
  - "property_type"
  - "state"
  - "has_septic"

extends: "base_transaction"  # REQUIRED

tasks:
  - id: "property_specific_task"
    title: "Property-Specific Task"
    category: property_specific
    priority: "high"
    # ... other fields
```

---

## ✅ **Required Fields**

Every task MUST have these fields:

| Field | Type | Description | Example |
|-------|------|-------------|---------|
| `id` | string | Unique task identifier | `"title5_septic_inspection"` |
| `title` | string | Human-readable title | `"Schedule Title 5 Inspection"` |
| `category` | enum | One of 14 categories | `"property_specific"` |
| `priority` | enum | critical/high/normal/low | `"critical"` |
| `owner_role` | enum | Task owner | `"seller"` |
| `due` | enum | Date anchor | `"Closing"` |
| `due_offset` | string | Offset from anchor | `"-21d"` |
| `depends_on` | array | Task IDs | `["other_task_id"]` or `[]` |
| `mandatory` | boolean | Required task | `true` |

---

## ➕ **Adding New Tasks**

### **Step 1: Determine Location**

**Add to base_transaction.yaml if:**
- Task applies to all MA real estate transactions
- Task is required by MA law for all property types
- Task is standard practice across all transactions

**Add to property template if:**
- Task is specific to property type
- Task only applies under certain conditions
- Task relates to unique property features

### **Step 2: Choose Appropriate Workflow** (for base tasks)

```yaml
workflows:
  - name: "Deal Setup"              # Initial setup
  - name: "Party Onboarding"        # Adding parties
  - name: "Earnest Money"           # EMD handling
  - name: "Inspection Period"       # Inspections
  - name: "Purchase and Sale"       # P&S execution
  - name: "Financing"               # Mortgage/loans
  - name: "Pre-Closing"             # Final preparations
  - name: "Closing Week"            # Week before closing
  - name: "Closing Day"             # Closing activities
  - name: "Post-Closing"            # Follow-up
```

### **Step 3: Define Task Fields**

```yaml
- id: "new_task_id"                # Unique, snake_case
  title: "Descriptive Task Title"   # Title Case, clear
  category: category_name           # From valid 14 categories
  priority: "normal"                # Based on importance
  description: "Clear description of what to do"
  mandatory: true                   # true for required tasks
  applies_if: "condition == true"   # Optional condition
  due: "Closing"                    # Milestone anchor
  due_offset: "-14d"                # Relative offset
  owner_role: agent                 # Who owns this task
  depends_on:                       # Task dependencies
    - "prerequisite_task_id"
  reminders:                        # Optional reminders
    - offset: "-7d"
      channels: ["email"]
  estimated_duration: "2-3 weeks"   # Optional time estimate
  vendor_type: "inspector"          # Optional vendor type
  citations:                        # Optional legal citations
    - url: "https://..."
      title: "MA Law Reference"
  notes: "Helpful tips for users"   # Optional guidance
```

### **Step 4: Add to Template File**

**For base_transaction.yaml:**
```yaml
workflows:
  - name: "Appropriate Workflow"
    tasks:
      # ... existing tasks ...

      - id: "new_task_id"
        # ... task definition ...
```

**For property template:**
```yaml
extends: "base_transaction"

tasks:
  # ... existing tasks ...

  - id: "new_task_id"
    # ... task definition ...
```

### **Step 5: Validate**

```bash
python3 /tmp/comprehensive_validation.py
```

---

## ✏️ **Modifying Existing Tasks**

### **Safe Modifications**

These changes are safe and don't break dependencies:
- ✅ Update `title` (user-facing text)
- ✅ Update `description` (user-facing text)
- ✅ Update `notes` (user-facing text)
- ✅ Add/modify `reminders`
- ✅ Add/modify `citations`
- ✅ Add/modify `estimated_duration`
- ✅ Update `priority` (if justifiable)

### **Caution Required**

These changes may break functionality:
- ⚠️ Changing `id` (breaks dependencies)
- ⚠️ Changing `category` (affects filtering)
- ⚠️ Changing `owner_role` (affects assignments)
- ⚠️ Modifying `depends_on` (affects ordering)
- ⚠️ Changing `due` or `due_offset` (affects scheduling)

### **Breaking Changes**

These changes require coordination with dev team:
- ❌ Deleting tasks (check for dependencies first)
- ❌ Renaming `id` (update all dependencies)
- ❌ Changing template inheritance structure
- ❌ Modifying `applies_if` conditions (test thoroughly)

### **Modification Workflow**

1. **Create backup:**
   ```bash
   cp template.yaml template.yaml.backup
   ```

2. **Make changes** in YAML editor

3. **Validate:**
   ```bash
   python3 /tmp/comprehensive_validation.py
   ```

4. **Test with sample transaction:**
   ```bash
   php test-template-engine.php
   ```

5. **Deploy to production**

---

## 🆕 **Creating New Property Types**

### **Step 1: Create Template File**

```bash
cd /home/snova/projects/dealroom/templates
cp sfh_septic.yaml new_property_type.yaml
```

### **Step 2: Update Template Metadata**

```yaml
---
template_id: "new_property_type"  # Unique identifier
version: "1.0.0"
title: "New Property Type"
description: "Description of property type"
property_types:
  - "NewType"  # Match property_type value
created_date: "2025-10-31"
author: "Your Name"

required_attributes:
  - "property_type"
  - "state"
  - "custom_attribute"  # Any required attributes

extends: "base_transaction"  # MUST extend base

tasks:
  # Define property-specific tasks
```

### **Step 3: Add Property-Specific Tasks**

Only include tasks that are **unique** to this property type.

```yaml
tasks:
  - id: "property_specific_task_1"
    title: "Task Specific to This Property Type"
    category: property_specific
    priority: "normal"
    # ... complete task definition
```

### **Step 4: Test Applicability Conditions**

```yaml
applies_if: "property.type == 'NewType'"
applies_if: "property.has_special_feature == true"
applies_if: "property.city IN ['Boston', 'Cambridge']"
```

### **Step 5: Validate**

```bash
python3 /tmp/comprehensive_validation.py
```

### **Step 6: Register in Database**

```sql
INSERT INTO wp_ma_deal_templates (
  template_id,
  name,
  property_types,
  status
) VALUES (
  'new_property_type',
  'New Property Type',
  'NewType',
  'active'
);
```

---

## ✅ **Validation & Testing**

### **Automated Validation**

Run comprehensive validation before committing changes:

```bash
cd /home/snova/projects/dealroom/templates
python3 /tmp/comprehensive_validation.py
```

**8 Validation Tests:**
1. Dependency Resolution (all depends_on resolve)
2. Required Fields (all 7 required fields present)
3. Applicability Conditions (valid syntax)
4. Priority Assignment (all tasks have priority)
5. Category Validation (valid category enum)
6. Template Inheritance (extends correctly)
7. Metadata Coverage (priority at 100%)
8. Due Date Configuration (valid anchors/offsets)

### **Manual Testing**

**Test with TemplateEngine:**
```php
<?php
// test-template.php

require_once 'ma-deal-room/src/Services/TemplateEngine.php';

$engine = new TemplateEngine();

// Test loading template
$tasks = $engine->loadTemplate('sfh_septic');

// Test task instantiation
$property = [
    'type' => 'SFH',
    'has_septic' => true,
    'year_built' => 1985,
    'state' => 'MA'
];

$instantiated = $engine->instantiateTasksFromYaml('sfh_septic', $property);

// Verify task count
assert(count($instantiated) > 0, 'Tasks should be instantiated');

// Verify applicability conditions work
foreach ($instantiated as $task) {
    echo "Task: {$task['title']}\n";
    echo "  Applies: " . ($engine->evaluateCondition($task, $property) ? 'Yes' : 'No') . "\n";
}
```

### **Test Checklist**

Before deploying changes:

- [ ] All validation tests pass
- [ ] No YAML parsing errors
- [ ] Dependencies resolve correctly
- [ ] Applicability conditions work as expected
- [ ] Due dates calculate correctly
- [ ] Tasks appear in correct workflows
- [ ] Priority-based reminders trigger appropriately

---

## 📚 **Best Practices**

### **Task IDs**
✅ **DO:**
- Use lowercase snake_case: `title5_septic_inspection`
- Be descriptive: `loan_commitment_deadline`
- Keep consistent naming: `*_schedule`, `*_confirm`, `*_review`

❌ **DON'T:**
- Use spaces or special characters
- Use generic names: `task1`, `check_thing`
- Duplicate IDs across templates

### **Task Titles**
✅ **DO:**
- Use Title Case: "Schedule Title 5 Inspection"
- Be action-oriented: "Verify", "Schedule", "Confirm"
- Be specific: "Request Condo 6D Certificate from HOA"

❌ **DON'T:**
- Use all caps: "SCHEDULE INSPECTION"
- Be vague: "Do inspection stuff"
- Use jargon without context

### **Priorities**
✅ **DO:**
- Use `critical` for legal requirements and closing blockers
- Use `high` for contractual deadlines
- Use `normal` for standard workflow
- Use `low` for optional/post-closing

❌ **DON'T:**
- Mark everything as critical (causes alarm fatigue)
- Use low for tasks that actually matter
- Change priorities without business justification

### **Dependencies**
✅ **DO:**
- List direct prerequisites only
- Use empty array `[]` for no dependencies
- Ensure dependency tasks exist

❌ **DON'T:**
- Create circular dependencies
- Reference non-existent task IDs
- Over-specify dependencies (creates brittleness)

### **Applicability Conditions**
✅ **DO:**
- Use explicit comparisons: `property.has_septic == true`
- Check for existence: `property.hoa_fee != null`
- Use IN for multiple values: `property.city IN ['Boston', 'Cambridge']`

❌ **DON'T:**
- Use bare boolean: `property.has_septic` (add `== true`)
- Use complex logic (keep conditions simple)
- Reference undefined property attributes

### **Legal Citations**
✅ **DO:**
- Link to official sources (Mass.gov, MaLegislature.gov)
- Include specific statute sections
- Keep URLs permanent (avoid dated pages)

❌ **DON'T:**
- Link to unofficial sources
- Use shortened URLs
- Reference outdated regulations

---

## 🐛 **Troubleshooting**

### **YAML Parsing Errors**

**Error:** `yaml.scanner.ScannerError: while scanning a simple key`

**Cause:** Indentation error or special character

**Fix:**
```yaml
# WRONG:
title: Schedule inspection

# RIGHT:
title: "Schedule inspection"
```

---

### **Dependency Not Resolving**

**Error:** Task depends on non-existent ID

**Fix:**
1. Check if dependency task ID exists
2. Verify spelling exactly matches
3. Ensure dependency is in same template or base

```yaml
# Check dependency exists:
depends_on:
  - "loan_commitment_track"  # Must exist in base or property template
```

---

### **Condition Not Filtering**

**Error:** Task appears when it shouldn't

**Fix:**
```yaml
# WRONG (no comparison operator):
applies_if: "property.has_septic"

# RIGHT:
applies_if: "property.has_septic == true"
```

---

### **Task Not Appearing**

**Causes:**
1. Applicability condition evaluates to false
2. Template not properly extending base
3. Task in wrong workflow section

**Fix:**
1. Check `applies_if` condition
2. Verify `extends: "base_transaction"` present
3. Check task is in `workflows` or `tasks` section

---

### **Due Date Incorrect**

**Error:** Task due date not calculating correctly

**Fix:**
```yaml
# Ensure proper format:
due: "Closing"           # Must be valid anchor
due_offset: "-21d"       # Must be ±Nd format
```

**Valid anchors:**
- `Listing`
- `Offer`
- `PS`
- `Closing`
- `FirstMeeting`

**Valid offset format:**
- `+0d`, `+7d`, `+30d` (after anchor)
- `-7d`, `-21d`, `-45d` (before anchor)

---

## 🔄 **Version Control**

### **Git Workflow**

```bash
# Create feature branch
git checkout -b feature/add-commercial-template

# Make changes
vim templates/commercial.yaml

# Validate
python3 /tmp/comprehensive_validation.py

# Commit with descriptive message
git add templates/commercial.yaml
git commit -m "Add commercial property template with 15 unique tasks"

# Push and create PR
git push origin feature/add-commercial-template
```

### **Commit Message Format**

```
<type>: <description>

[optional body]
```

**Types:**
- `feat`: New feature (new template, new tasks)
- `fix`: Bug fix (fix validation error, fix dependency)
- `docs`: Documentation only
- `style`: Formatting (indentation, quotes)
- `refactor`: Code restructure (no functionality change)
- `test`: Adding tests
- `chore`: Maintenance (update citations, etc.)

**Examples:**
```
feat: Add Title 5 septic inspection tasks

- Added title5_septic_inspection task
- Added title5_certificate task
- Included MA regulation citations
- Set critical priority for compliance

fix: Correct dependency reference in loan_commitment_deadline

Changed depends_on from "loan_application" to "loan_commitment_track"
to match actual task ID in base template.

docs: Update user guide with condo 6D certificate timeline

Added clarification that HOA has 10 business days to provide
certificate per M.G.L. c.183A §6.
```

---

## 📊 **Template Statistics**

### **Current State (After Phase 5)**

| Template | Tasks | Lines | Status |
|----------|-------|-------|--------|
| base_transaction.yaml | 125 | ~3,500 | ✅ Valid |
| sfh_septic.yaml | 26 | ~900 | ✅ Valid |
| sfh_city_water.yaml | 24 | ~850 | ✅ Valid |
| condo.yaml | 28 | ~950 | ✅ Valid |
| multifamily.yaml | 31 | ~1,050 | ✅ Valid |
| **Total** | **234** | **~7,250** | ✅ All Valid |

### **Category Distribution**

| Category | Count | Percentage |
|----------|-------|------------|
| property_specific | 81 | 34.6% |
| pre_closing | 23 | 9.8% |
| hoa_condo | 22 | 9.4% |
| financing | 17 | 7.3% |
| inspection | 13 | 5.6% |
| post_closing | 13 | 5.6% |
| Other categories | 65 | 27.7% |

---

## 🚀 **Deployment**

### **Pre-Deployment Checklist**

- [ ] All validation tests pass
- [ ] Backups created
- [ ] Changes documented
- [ ] Team notified
- [ ] Staging environment tested

### **Deployment Steps**

```bash
# 1. Backup production templates
ssh production
cd /var/www/html/wp-content/plugins/ma-deal-room/templates
tar -czf templates-backup-$(date +%Y%m%d).tar.gz *.yaml

# 2. Copy new templates
scp templates/*.yaml production:/path/to/templates/

# 3. Clear any caches
wp cache flush

# 4. Monitor logs
tail -f /var/log/deal-room/error.log
```

### **Rollback Plan**

```bash
# If issues occur, restore backup
cd /var/www/html/wp-content/plugins/ma-deal-room/templates
tar -xzf templates-backup-YYYYMMDD.tar.gz
wp cache flush
```

---

## 📞 **Support**

### **For Template Questions:**
- Review this guide
- Check validation output
- Review phase completion reports

### **For System Integration:**
- Contact backend development team
- Review TemplateEngine.php
- Check TaskScheduler.php

### **For MA Regulations:**
- Mass.gov: https://www.mass.gov/
- MA Legislature: https://malegislature.gov/

---

**Document Version:** 2.0
**Last Updated:** 2025-10-31
**Maintained By:** MA Deal Room Development Team

*For questions or suggestions, contact the development team.*
