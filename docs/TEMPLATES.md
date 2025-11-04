# MA Deal Room - Template DSL Documentation

**Version**: 1.0.0
**Last Updated**: 2025-10-30

---

## Overview

The MA Deal Room Template DSL (Domain-Specific Language) is a YAML-based system for defining property-type-specific transaction checklists with:
- Conditional task logic
- Automated due date calculation
- Dependency management
- Multi-channel reminders
- Regulatory compliance citations

Templates eliminate manual checklist creation and ensure Massachusetts regulatory requirements are never missed.

---

## Table of Contents

1. [Template Structure](#template-structure)
2. [Field Reference](#field-reference)
3. [Conditional Logic](#conditional-logic)
4. [Relative Due Dates](#relative-due-dates)
5. [Dependencies](#dependencies)
6. [Reminders](#reminders)
7. [Examples](#examples)
8. [Best Practices](#best-practices)

---

## Template Structure

### Basic Template Format

```yaml
# Template Metadata
template_id: "sfh_septic"
version: "1.0.0"
title: "Single-Family Home with Septic System"
description: "Complete transaction checklist for SFH properties with on-site septic systems, including Title 5 compliance"
property_types:
  - "SFH"
created_date: "2025-10-30"
author: "MA Deal Room Team"

# Required Property Attributes
required_attributes:
  - "property_type"
  - "has_septic"
  - "year_built"
  - "ps_date"      # Purchase & Sale Agreement date
  - "closing_date"

# Task Definitions
tasks:
  - id: "agency_disclosure"
    title: "Provide Agency Disclosure Form"
    description: "Present Massachusetts Mandatory Licensee-Consumer Relationship Disclosure before entering into contract"
    mandatory: true

    # Conditional Logic
    applies_if: "property.state == 'MA'"

    # Relative Due Date
    due: "FirstMeeting"
    due_offset: "0d"

    # Assignment
    assignee_role: "ListingAgent"

    # Dependencies
    depends_on: []

    # Reminders
    reminders:
      - offset: "-7d"
        channels: ["email"]
      - offset: "-1d"
        channels: ["email", "sms"]

    # Regulatory Citations
    citations:
      - url: "https://www.mass.gov/doc/real-estate-board-agency-disclosure-form-english/download"
        title: "MA Mandatory Licensee-Consumer Relationship Disclosure"

    # Additional Context
    notes: "Must be presented electronically or in person before entering into contract with consumer (2023 updated requirement)"

    # Actions (for automation)
    actions:
      - type: "send_form"
        form_type: "agency_disclosure"
```

---

## Field Reference

### Template-Level Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `template_id` | string | ✅ | Unique identifier (slug format) |
| `version` | string | ✅ | Semantic version (e.g., "1.0.0") |
| `title` | string | ✅ | Human-readable template name |
| `description` | string | ✅ | Detailed template description |
| `property_types` | array | ✅ | Property types this template applies to (SFH, Condo, Multifamily, Land, Commercial) |
| `required_attributes` | array | ✅ | Property fields needed to instantiate template |
| `created_date` | string | ❌ | ISO date when template was created |
| `author` | string | ❌ | Template author/organization |
| `tasks` | array | ✅ | List of task definitions |

### Task-Level Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | string | ✅ | Unique task identifier within template |
| `title` | string | ✅ | Task title (action-oriented, e.g., "Schedule X") |
| `description` | string | ✅ | Detailed task description with context |
| `mandatory` | boolean | ✅ | true = required, false = recommended |
| `applies_if` | string | ❌ | Conditional expression (task only created if true) |
| `due` | string | ✅ | Due date anchor (Offer, PS, Closing, FirstMeeting, ListingDate) |
| `due_offset` | string | ✅ | Offset from anchor (e.g., "-21d", "+7d") |
| `assignee_role` | string | ✅ | Who is responsible (Seller, ListingAgent, BuyerAgent, Attorney, etc.) |
| `depends_on` | array | ❌ | Task IDs that must complete first |
| `reminders` | array | ❌ | Reminder configuration |
| `citations` | array | ❌ | Regulatory or legal references |
| `notes` | string | ❌ | Additional context or instructions |
| `actions` | array | ❌ | Automated actions to trigger |
| `estimated_duration` | string | ❌ | How long task takes (e.g., "2-3 weeks") |
| `vendor_type` | string | ❌ | External vendor category (fire_dept, septic_inspector, etc.) |

---

## Conditional Logic

The `applies_if` field uses a simple expression language to determine if a task should be created.

### Syntax

```yaml
applies_if: "<property_field> <operator> <value> [<logical_operator> ...]"
```

### Operators

- `==` : Equal to
- `!=` : Not equal to
- `<` : Less than
- `>` : Greater than
- `<=` : Less than or equal
- `>=` : Greater than or equal
- `IN` : Value in list
- `NOT IN` : Value not in list

### Logical Operators

- `AND` : Both conditions must be true
- `OR` : At least one condition must be true

### Available Property Fields

- `property.type` : SFH, Condo, Multifamily, Land, Commercial
- `property.state` : State abbreviation (MA)
- `property.city` : City name
- `property.has_septic` : boolean
- `property.year_built` : integer
- `property.bedrooms` : integer
- `property.sqft` : integer
- `transaction.status` : active, pending, closed, cancelled

### Examples

```yaml
# Simple equality
applies_if: "property.type == 'SFH'"

# Numeric comparison
applies_if: "property.year_built < 1978"

# List membership
applies_if: "property.city IN ['Boston', 'Lynn', 'Lawrence', 'Haverhill']"

# Multiple conditions (AND)
applies_if: "property.type == 'SFH' AND property.has_septic == true"

# Multiple conditions (OR)
applies_if: "property.type == 'Multifamily' OR property.type == 'Commercial'"

# Complex expression
applies_if: "property.type IN ['SFH', 'Condo', 'Multifamily'] AND property.year_built < 1978 AND property.state == 'MA'"

# Property type exclusion
applies_if: "property.type != 'Condo'"
```

---

## Relative Due Dates

Tasks use relative due dates anchored to key transaction milestones.

### Anchors

| Anchor | Description | Example |
|--------|-------------|---------|
| `ListingDate` | Date property was listed | When listing agreement signed |
| `Offer` | Date offer was accepted | Verbal or written acceptance |
| `PS` | Purchase & Sale Agreement date | P&S signing date |
| `Closing` | Scheduled closing date | Target closing date |
| `FirstMeeting` | First meeting with client | Initial consultation |

### Offset Format

```yaml
due: "PS"          # Anchor point
due_offset: "-21d" # 21 days BEFORE P&S
```

```yaml
due: "Closing"
due_offset: "+3d"  # 3 days AFTER closing (post-closing task)
```

### Offset Units

- `d` : Days (e.g., "7d" = 7 days)
- `w` : Weeks (e.g., "2w" = 2 weeks = 14 days)
- `m` : Months (e.g., "1m" = 1 month = 30 days)

### Sign Conventions

- `-` : BEFORE anchor (e.g., "Closing-21d" = 21 days before closing)
- `+` : AFTER anchor (e.g., "PS+7d" = 7 days after P&S)
- No sign : ON anchor (e.g., "Closing" = on closing date)

### Examples

```yaml
# 7 days before closing
due: "Closing"
due_offset: "-7d"

# 3 weeks before P&S (21 days)
due: "PS"
due_offset: "-3w"

# On the day of offer acceptance
due: "Offer"
due_offset: "0d"

# 10 days after P&S signing
due: "PS"
due_offset: "+10d"

# At first meeting (no offset)
due: "FirstMeeting"
due_offset: "0d"
```

---

## Dependencies

Tasks can depend on other tasks completing first. Dependencies are specified using task IDs.

### Format

```yaml
depends_on:
  - "task_id_1"
  - "task_id_2"
```

### Behavior

- Task becomes "blocked" until all dependencies complete
- Task automatically becomes "pending" when dependencies satisfied
- Circular dependencies are detected and rejected
- Missing dependency IDs throw validation errors

### Example

```yaml
tasks:
  - id: "title5_septic_inspection"
    title: "Schedule Title 5 Septic Inspection"
    depends_on: []
    # ... other fields

  - id: "title5_repairs"
    title: "Complete Title 5 Septic Repairs"
    applies_if: "property.has_septic == true"
    depends_on:
      - "title5_septic_inspection"  # Can't repair until inspected
    # ... other fields

  - id: "title5_reinspection"
    title: "Schedule Title 5 Re-inspection (if repairs made)"
    depends_on:
      - "title5_repairs"  # Can't re-inspect until repairs complete
    # ... other fields
```

### Dependency Resolution

The system uses topological sorting to:
1. Determine task execution order
2. Detect circular dependencies
3. Calculate adjusted due dates if dependency chain is tight

---

## Reminders

Tasks can have multiple reminders at different time offsets.

### Reminder Format

```yaml
reminders:
  - offset: "-21d"
    channels: ["email"]
    message: "Optional custom message"
  - offset: "-7d"
    channels: ["email", "sms"]
  - offset: "-1d"
    channels: ["email", "sms"]
    priority: "high"
```

### Reminder Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `offset` | string | ✅ | When to send relative to task due date |
| `channels` | array | ✅ | Delivery channels: email, sms, both |
| `message` | string | ❌ | Custom reminder message (default uses task title) |
| `priority` | string | ❌ | low, normal, high (affects styling/urgency) |

### Offset Format

Same as due date offsets:
- `-21d` : 21 days before task due date
- `-1w` : 1 week before task due date
- `-1d` : 1 day before task due date
- `0d` : On the task due date

### Channels

- `email` : Send email to assignee or parties
- `sms` : Send SMS if phone number available
- Both can be specified: `["email", "sms"]`

### Examples

```yaml
# Conservative: Email only, 3 weeks and 1 week out
reminders:
  - offset: "-21d"
    channels: ["email"]
  - offset: "-7d"
    channels: ["email"]

# Aggressive: Multiple reminders with SMS escalation
reminders:
  - offset: "-30d"
    channels: ["email"]
  - offset: "-14d"
    channels: ["email"]
  - offset: "-7d"
    channels: ["email", "sms"]
  - offset: "-3d"
    channels: ["email", "sms"]
    priority: "high"
  - offset: "-1d"
    channels: ["email", "sms"]
    priority: "high"

# Simple: Just day-before reminder
reminders:
  - offset: "-1d"
    channels: ["email"]
```

---

## Examples

### Example 1: Simple Task

```yaml
- id: "agency_disclosure"
  title: "Provide Agency Disclosure Form"
  description: "Present MA Mandatory Licensee-Consumer Relationship Disclosure"
  mandatory: true
  applies_if: "property.state == 'MA'"
  due: "FirstMeeting"
  due_offset: "0d"
  assignee_role: "ListingAgent"
  depends_on: []
  reminders:
    - offset: "-1d"
      channels: ["email"]
  citations:
    - url: "https://www.mass.gov/doc/real-estate-board-agency-disclosure-form-english/download"
      title: "MA Agency Disclosure Form"
  notes: "Must be presented before entering into contract (2023 requirement)"
```

### Example 2: Conditional Task with Dependencies

```yaml
- id: "lead_paint_disclosure"
  title: "Property Transfer Lead Paint Notification"
  description: "Provide EPA lead paint disclosure and MA notification for pre-1978 homes"
  mandatory: true
  applies_if: "property.year_built < 1978 AND property.type IN ['SFH', 'Condo', 'Multifamily']"
  due: "Offer"
  due_offset: "-2d"
  assignee_role: "Seller"
  depends_on: []
  reminders:
    - offset: "-14d"
      channels: ["email"]
    - offset: "-7d"
      channels: ["email"]
    - offset: "-2d"
      channels: ["email", "sms"]
  citations:
    - url: "https://www.mass.gov/info-details/property-transfer-lead-paint-notification"
      title: "Property Transfer Lead Paint Notification | Mass.gov"
    - url: "https://www.mass.gov/doc/105-cmr-460-lead-poisoning-prevention-and-control/download"
      title: "105 CMR 460.720"
  notes: "Federal EPA penalties up to $11,000 per error. Provide EPA pamphlet and disclosure form."
```

### Example 3: Task with Vendor Request

```yaml
- id: "smoke_co_inspection"
  title: "Schedule Smoke & CO Detector Inspection"
  description: "Contact local fire department to schedule certificate of compliance inspection"
  mandatory: true
  applies_if: "property.type IN ['SFH', 'Multifamily']"
  due: "Closing"
  due_offset: "-21d"
  assignee_role: "Seller"
  vendor_type: "fire_dept_smoke_cert"
  estimated_duration: "Schedule 2-3 weeks ahead; inspection takes 30-45 minutes"
  depends_on: []
  reminders:
    - offset: "-28d"
      channels: ["email"]
    - offset: "-21d"
      channels: ["email"]
    - offset: "-14d"
      channels: ["email", "sms"]
  actions:
    - type: "request_vendor"
      vendor_type: "fire_dept_smoke_cert"
  citations:
    - url: "https://www.mass.gov/doc/consumer-guide-to-smoke-detectors-when-selling-home/download"
      title: "MA Smoke & CO Detector Guide"
    - url: "https://malegislature.gov/Laws/GeneralLaws/PartI/TitleXX/Chapter148/Section26F"
      title: "M.G.L. c.148 §26F - Smoke Detectors"
  notes: "Certificate valid 60 days. Fee typically $50-$100 per municipality."
```

---

## Best Practices

### 1. Task Granularity
- **Good**: "Schedule Title 5 Inspection", "Review Inspection Report", "Complete Repairs"
- **Bad**: "Handle Title 5 Stuff"

### 2. Clear Assignee Roles
Use standardized roles:
- `Seller` : Property seller
- `Buyer` : Property buyer
- `ListingAgent` : Seller's agent
- `BuyerAgent` : Buyer's agent
- `SellerAttorney` : Seller's lawyer
- `BuyerAttorney` : Buyer's lawyer
- `Lender` : Buyer's mortgage lender
- `TitleCompany` : Title/escrow company
- `HOA` : Homeowners association
- `FireDept` : Local fire department
- `Inspector` : Home inspector, septic inspector, etc.

### 3. Reminder Strategy
**Critical regulatory tasks** (Title 5, smoke cert, lead paint):
- Start reminders 3-4 weeks out
- Escalate to SMS in final week

**Standard tasks** (scheduling, coordination):
- 1-2 week email reminders sufficient

**Administrative tasks** (document prep):
- Single reminder 2-3 days before

### 4. Conditional Logic
Test conditions thoroughly:
```yaml
# ✅ Good: Clear, specific
applies_if: "property.type == 'SFH' AND property.has_septic == true"

# ❌ Bad: Too complex, hard to read
applies_if: "(property.type == 'SFH' OR property.type == 'Multifamily') AND (property.has_septic == true OR (property.city IN ['Boston', 'Lynn'] AND property.year_built < 1950))"
```

Break complex conditions into separate tasks.

### 5. Citation Quality
Always include:
- **URL**: Direct link to official source
- **Title**: Clear description of the reference
- **Optional**: Regulation number (e.g., "310 CMR 15.000")

```yaml
citations:
  - url: "https://www.mass.gov/regulations/310-CMR-15000-septic-systems-title-5"
    title: "310 CMR 15.000: Septic Systems (Title 5)"
  - url: "https://www.mass.gov/guides/buying-or-selling-property-with-a-septic-system"
    title: "Buying or Selling Property with a Septic System | Mass.gov"
```

### 6. Dependency Chains
Keep dependency chains short (ideally ≤3 levels):
```yaml
# ✅ Good: Short chain
Task A → Task B → Task C

# ⚠️ Acceptable but monitor due dates
Task A → Task B → Task C → Task D

# ❌ Bad: Too long, high risk of delays
Task A → Task B → Task C → Task D → Task E → Task F
```

### 7. Due Date Buffer
For tasks requiring external parties (vendors, inspectors):
- Add extra buffer beyond minimum requirement
- Example: Smoke cert requires scheduling "soon after P&S" but due at closing
  - Minimum: Schedule 7 days before closing
  - Better: Schedule 21 days before closing (allows for rescheduling)

### 8. Version Control
When updating templates:
- Increment version number (semantic versioning)
- Document changes in template comments
- Test with sample transactions before deploying

---

## Template Testing

Before deploying a new template:

1. **Validate YAML Syntax**
   ```bash
   wp ma-deal templates:validate templates/your-template.yaml
   ```

2. **Test Instantiation**
   Create test transaction with template to verify:
   - All tasks created correctly
   - Conditional logic works
   - Due dates calculate properly
   - Dependencies resolve
   - No circular dependencies

3. **Review Generated Timeline**
   Check that task sequence makes logical sense

4. **Verify Citations**
   All URLs should be accessible and point to correct resources

---

## Template Versioning

Use semantic versioning:
- **Major** (1.0.0 → 2.0.0): Breaking changes (field renames, removed tasks)
- **Minor** (1.0.0 → 1.1.0): New tasks added, enhanced functionality
- **Patch** (1.0.0 → 1.0.1): Bug fixes, clarifications, citation updates

Active transactions continue using the template version they started with. New transactions use the latest version.

---

## Additional Resources

- **Research**: `docs/RESEARCH.md` - Massachusetts regulatory requirements
- **Task Matrix**: `docs/research/tasks-matrix.csv` - Structured task data
- **Database**: `ma-deal-room/database/README.md` - Schema documentation
- **API**: `docs/API.md` - REST endpoints for templates

---

**Maintained by**: MA Deal Room Team
**Questions**: See project README.md for support information
