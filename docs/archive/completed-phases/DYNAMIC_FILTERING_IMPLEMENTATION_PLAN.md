# Dynamic Template & Milestone Filtering - Implementation Plan

**Date**: 2025-10-31
**Objective**: Make the system contextually intelligent by showing only relevant templates and milestones based on transaction type and property type

---

## 1. RESEARCH FINDINGS

### Buy-Side vs Sell-Side Differences

Based on Massachusetts real estate regulations and practices:

#### **Sell-Side (Listing Agent)**
- **Primary Focus**: Seller representation, property marketing, disclosure compliance
- **Key Responsibilities**:
  - Listing preparation (MLS entry, photography, staging)
  - Seller disclosures (lead paint, property condition, smoke/CO certificates)
  - Property certificates (Title 5 for septic, 6(d) for condos)
  - Maintaining property until closing
  - Broker commission payment
  - Market analysis and pricing strategy

#### **Buy-Side (Buyer's Agent)**
- **Primary Focus**: Buyer representation, property search, purchase negotiation
- **Key Responsibilities**:
  - Buyer representation agreement (mandatory as of 2024)
  - Property showings and market research
  - Home inspection coordination
  - Mortgage contingency management
  - Buyer disclosures and due diligence
  - Financing approval tracking
  - Final walkthrough

### Timeline & Milestones

| Milestone | Sell-Side | Buy-Side | Notes |
|-----------|-----------|----------|-------|
| **Listing Date** | ✅ Primary | ❌ Not applicable | When property goes on MLS |
| **Offer Accepted** | ✅ Yes | ✅ Primary | Both sides track this |
| **Home Inspection** | ✅ Facilitate | ✅ Coordinate | Buyer orders, seller facilitates access |
| **P&S Agreement** | ✅ Yes | ✅ Yes | Both sides involved |
| **Loan Commitment** | ✅ Monitor | ✅ Primary | Buyer responsibility, seller monitors |
| **Closing** | ✅ Yes | ✅ Yes | Both sides present |

---

## 2. CURRENT STATE ANALYSIS

### Database Schema
- **Transactions Table**: Has `transaction_side` ENUM('listing', 'buyer')
- **Templates Table**: Has `property_type` ENUM but NO `transaction_type` field yet
- **Transaction Model**: Has BOTH `transaction_side` AND `transaction_type` fields (inconsistent)
- **Template Model**: Has `transaction_side` field ('listing', 'buyer', 'both')

### TypeScript Frontend
- **Transaction interface**: Uses `transaction_side: 'listing' | 'buyer'`
- **Template interface**: Expects `transaction_type?: TransactionType` (buy_side, sell_side, rental, etc.)
- **Mismatch**: Frontend expects `transaction_type` on templates, but database uses `transaction_side`

### Current Templates (5 YAML files)
1. **base_transaction.yaml** - Universal tasks (125 tasks)
2. **sfh_septic.yaml** - SFH with septic (26 tasks)
3. **sfh_city_water.yaml** - SFH with city water (24 tasks)
4. **condo.yaml** - Condo-specific (28 tasks)
5. **multifamily.yaml** - Multi-family (31 tasks)

**Issue**: Templates don't specify whether they're for buy-side or sell-side transactions

---

## 3. PROPOSED SOLUTION

### Standardize Terminology

**Decision**: Use `transaction_side` consistently throughout the system

| Field | Values | Usage |
|-------|--------|-------|
| `transaction_side` | 'listing', 'buyer', 'both' | Which side the agent represents |
| `property_type` | 'SFH', 'Condo', 'Multifamily', 'Land', 'Commercial', 'Any' | Type of property |

### Template Applicability Matrix

| Template | Transaction Side | Property Types | Notes |
|----------|-----------------|----------------|-------|
| **base_transaction** | 'both' | All | Universal tasks for all transactions |
| **sfh_septic** | 'listing' | SFH | Seller responsibility for Title 5 |
| **sfh_city_water** | 'listing' | SFH | Seller responsibility for water certs |
| **condo** | 'both' | Condo | Both sides need 6(d), but some tasks seller-only |
| **multifamily** | 'both' | Multifamily | Both sides involved |

**Rationale**:
- **Septic inspections** (Title 5): Seller's legal obligation in MA
- **6(d) certificates** (Condo): Seller provides, but buyer needs to review
- **Base tasks**: Many apply to both (disclosures, attorney coordination, closing prep)
- **Property-specific**: Water/septic/HOA requirements are property-dependent

### Milestone Field Filtering

| Milestone Field | Listing (Sell-Side) | Buyer (Buy-Side) |
|-----------------|---------------------|------------------|
| **listing_date** | ✅ Required | ❌ Hidden | Not applicable to buyers |
| **offer_accepted_date** | ✅ Optional | ✅ Required | Both track this |
| **ps_agreement_date** | ✅ Required | ✅ Required | Both sign P&S |
| **loan_commitment_date** | ✅ Optional | ✅ Required | Buyer tracks actively |
| **closing_date** | ✅ Required | ✅ Required | Both need this |

---

## 4. IMPLEMENTATION STEPS

### Step 1: Database Migration ✅
**File**: `ma-deal-room/database/migrations/009_add_template_transaction_side.sql`

```sql
-- Add transaction_side to templates table (if not exists)
ALTER TABLE wp_ma_deal_templates
ADD COLUMN IF NOT EXISTS transaction_side
ENUM('listing', 'buyer', 'both')
NOT NULL DEFAULT 'both'
COMMENT 'Which side of transaction this template applies to'
AFTER property_type;

-- Add index for filtering
ALTER TABLE wp_ma_deal_templates
ADD INDEX IF NOT EXISTS idx_transaction_side (transaction_side);

-- Update existing system templates
UPDATE wp_ma_deal_templates
SET transaction_side = 'both'
WHERE name = 'Base Transaction Template';

UPDATE wp_ma_deal_templates
SET transaction_side = 'listing'
WHERE name LIKE '%Septic%' OR name LIKE '%City Water%';

UPDATE wp_ma_deal_templates
SET transaction_side = 'both'
WHERE name LIKE '%Condo%' OR name LIKE '%Multifamily%';
```

### Step 2: Update YAML Templates ✅

Add `transaction_side` metadata to each template:

```yaml
template_id: base_transaction
transaction_side: both  # ← ADD THIS
property_types:
  - SFH
  - Condo
  - Multifamily
  - Commercial
  - Land
```

```yaml
template_id: sfh_septic
transaction_side: listing  # ← Seller-focused
property_types:
  - SFH
```

```yaml
template_id: condo
transaction_side: both  # ← Both sides need condo tasks
property_types:
  - Condo
```

### Step 3: Update Template Model ✅

Already has `transaction_side` field - just ensure it's used correctly.

### Step 4: Update TemplateRepository ✅

Add filtering method:

```php
public function findByFilters(array $filters): array {
    $where = [];
    $params = [];

    if (!empty($filters['property_type'])) {
        $where[] = "(property_type = %s OR property_type = 'Any')";
        $params[] = $filters['property_type'];
    }

    if (!empty($filters['transaction_side'])) {
        $where[] = "(transaction_side = %s OR transaction_side = 'both')";
        $params[] = $filters['transaction_side'];
    }

    // Add to query...
}
```

### Step 5: Update TemplateController API ✅

```php
public function list_templates(WP_REST_Request $request): WP_REST_Response {
    $filters = [
        'property_type' => $request->get_param('property_type'),
        'transaction_side' => $request->get_param('transaction_side'),
        'is_active' => true
    ];

    $templates = $this->template_repository->findByFilters($filters);

    return new WP_REST_Response(['data' => $templates], 200);
}
```

### Step 6: Update React CreateTransactionWizard ✅

Implement cascading dropdowns:

```tsx
// Step 1: Select Transaction Side
<Select
  label="What side do you represent?"
  value={transactionSide}
  onChange={handleTransactionSideChange}
  options={[
    { value: 'listing', label: 'Listing (Seller) Side', description: 'I represent the seller' },
    { value: 'buyer', label: 'Buyer Side', description: 'I represent the buyer' }
  ]}
/>

// Step 2: Select Property Type
<Select
  label="Property Type"
  value={propertyType}
  onChange={handlePropertyTypeChange}
  disabled={!transactionSide}
  options={propertyTypes}
/>

// Step 3: Select Template (filtered)
<Select
  label="Transaction Template"
  value={templateId}
  disabled={!propertyType}
  options={filteredTemplates}  // ← Filtered by API based on transaction_side + property_type
/>
```

### Step 7: Milestone Field Filtering ✅

```tsx
// In CreateTransactionForm
const getMilestoneFields = (transactionSide: 'listing' | 'buyer') => {
  const common = ['offer_accepted_date', 'ps_agreement_date', 'closing_date'];

  if (transactionSide === 'listing') {
    return ['listing_date', ...common, 'loan_commitment_date'];
  } else {
    return [...common, 'loan_commitment_date'];
  }
};

// Render only applicable fields
{getMilestoneFields(transactionSide).map(field => (
  <DateField key={field} name={field} label={getFieldLabel(field)} />
))}
```

### Step 8: Add Contextual Help ✅

```tsx
const HELP_TEXT = {
  listing: {
    listing_date: "The date your listing agreement was signed and the property goes live on MLS.",
    ps_agreement_date: "The date the Purchase & Sale Agreement is signed (typically 7-10 days after offer acceptance).",
    loan_commitment_date: "The date the buyer's lender issues a commitment letter (typically 3-4 weeks after P&S)."
  },
  buyer: {
    offer_accepted_date: "The date the seller accepted your buyer's offer.",
    loan_commitment_date: "The critical date when your buyer must receive mortgage approval. Track this closely!",
    closing_date: "The target date for final closing at the registry of deeds or attorney's office."
  }
};

// Show contextual tooltips
<Tooltip content={HELP_TEXT[transactionSide][fieldName]}>
  <InfoIcon />
</Tooltip>
```

---

## 5. TASK-LEVEL FILTERING (FUTURE ENHANCEMENT)

### Option: Add `applicable_transaction_sides` to Tasks

```yaml
workflows:
  - name: Listing Preparation
    tasks:
      - id: schedule_photography
        title: Schedule professional photography
        applicable_transaction_sides: ['listing']  # ← Only for listing agents

      - id: buyer_representation_agreement
        title: Sign buyer representation agreement
        applicable_transaction_sides: ['buyer']  # ← Only for buyer agents

      - id: attorney_selection
        title: Select real estate attorney
        applicable_transaction_sides: ['both']  # ← Both sides need attorneys
```

**Implementation**: TemplateEngine would filter tasks based on transaction_side when instantiating.

---

## 6. TESTING PLAN

### Unit Tests
- ✅ Test TemplateRepository filtering logic
- ✅ Test API endpoint with different filter combinations
- ✅ Test React component renders correct fields

### Integration Tests
1. Create listing-side transaction → Should only show listing templates
2. Create buyer-side transaction → Should only show buyer templates
3. SFH listing → Should show base + SFH septic/water templates
4. Condo buyer → Should show base + condo templates
5. Multifamily both → Should show base + multifamily templates

### Edge Cases
- What if no templates match? → Show message "No templates available for this combination"
- What if custom template? → Allow any combination
- What if property type changes? → Warn about re-applying template

---

## 7. USER EXPERIENCE IMPROVEMENTS

### For Newer Agents
1. **Contextual Help**: Tooltip on every field explaining what it means and why it matters
2. **Smart Defaults**: Pre-fill listing_date with today if listing side
3. **Visual Guides**: Show example timeline after dates are entered
4. **Task Explanations**: Each task shows "Why this matters" and "When to do it"
5. **Progress Indicators**: Show % complete for each workflow phase

### For Experienced Agents
1. **Quick Create**: Skip wizard if they've done this 100 times
2. **Template Favorites**: Pin frequently-used templates
3. **Bulk Operations**: Clone transactions, bulk update dates
4. **Keyboard Shortcuts**: Fast navigation without mouse

---

## 8. DATA MIGRATION PLAN

### For Existing Transactions
```sql
-- Existing transactions without transaction_side set
UPDATE wp_ma_deal_transactions
SET transaction_side = 'listing'
WHERE transaction_side IS NULL AND listing_date IS NOT NULL;

UPDATE wp_ma_deal_transactions
SET transaction_side = 'buyer'
WHERE transaction_side IS NULL AND listing_date IS NULL;
```

### For Existing Templates
```sql
-- System templates get appropriate transaction_side
UPDATE wp_ma_deal_templates
SET transaction_side = 'both'
WHERE is_system = TRUE AND name NOT LIKE '%Septic%' AND name NOT LIKE '%Water%';

UPDATE wp_ma_deal_templates
SET transaction_side = 'listing'
WHERE is_system = TRUE AND (name LIKE '%Septic%' OR name LIKE '%Water%');
```

---

## 9. SUCCESS METRICS

- ✅ **Zero irrelevant templates** shown to users
- ✅ **Reduced confusion** for newer agents (measured by support tickets)
- ✅ **Faster transaction creation** (target: 30% faster)
- ✅ **Higher task completion rates** (users understand what's required)
- ✅ **Lower error rates** (fewer wrong templates selected)

---

## 10. ROLLOUT PLAN

### Phase 1: Backend (Week 1)
- ✅ Database migration
- ✅ Model updates
- ✅ Repository filtering logic
- ✅ API endpoint updates
- ✅ Unit tests

### Phase 2: Frontend (Week 2)
- ✅ Update CreateTransactionWizard
- ✅ Add cascading filters
- ✅ Add contextual help
- ✅ Update TypeScript types
- ✅ Integration tests

### Phase 3: Templates (Week 3)
- ✅ Update YAML templates with transaction_side
- ✅ Sync templates to database
- ✅ Test template filtering in UI
- ✅ User acceptance testing

### Phase 4: Documentation & Training (Week 4)
- ✅ Update user guide
- ✅ Create video tutorials
- ✅ Update API documentation
- ✅ Release notes

---

## 11. RISKS & MITIGATION

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Existing transactions break | High | Provide default values in migration |
| Custom templates incompatible | Medium | Allow 'both' as safe default |
| Users confused by new UI | Medium | Phased rollout with training |
| Performance impact | Low | Indexed database fields |

---

## NEXT STEPS

1. Review this plan with stakeholders
2. Get approval on template applicability matrix
3. Begin Phase 1 implementation
4. Set up staging environment for testing
5. Create user documentation

---

**Questions for Consideration**:
1. Should rental transactions have separate templates?
2. Should commercial deals be separate from residential?
3. Do we need dual agency handling?
4. Should we support buyer-pays-commission workflows (2024 NAR changes)?
