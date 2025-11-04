# Phase 3: Rental Transaction Support - Implementation Plan

**Date:** 2025-10-31
**Status:** Ready for Execution
**Phase:** 3 of 5 (Rental Transaction Support)

---

## Overview

Phase 3 adds comprehensive support for residential rental transactions in Massachusetts, including both landlord representation (rental listings) and tenant representation (tenant placement). This phase implements MA-specific rental law compliance, security deposit tracking, and lease agreement workflows.

---

## Current State

From Phase 2 results:
- **Existing Rental Tasks:** ~5-10 (mostly universal tasks that apply to rentals)
- **Rental Landlord Filter:** 1 task currently
- **Rental Tenant Filter:** 0 tasks currently
- **Security Deposit Model:** Created in Phase 1, ready for use

**Gap:** System lacks rental-specific workflows and MA rental law compliance tasks.

---

## Phase 3 Goals

1. **Add 50+ rental-specific task definitions**
   - 30+ landlord representation tasks
   - 20+ tenant representation tasks

2. **Implement MA rental law compliance**
   - Security deposit requirements (M.G.L. c. 186, § 15B)
   - Lead paint disclosure (42 U.S.C. § 4852d)
   - Smoke/CO detector requirements (M.G.L. c. 148, § 26F)
   - Tenant screening legal compliance
   - Lease agreement requirements

3. **Create rental-specific workflows**
   - Rental listing process
   - Tenant screening process
   - Lease execution process
   - Security deposit handling
   - Move-in/move-out procedures

4. **Integrate security deposit compliance tracking**
   - Use SecurityDeposit model from Phase 1
   - Automatic compliance monitoring
   - 30-day deadline tracking

---

## MA Rental Law Requirements

### M.G.L. c. 186, § 15B - Security Deposits

**Key Requirements:**
1. **Amount Limit:** Maximum 1 month's rent
2. **Bank Account:** Must be in MA bank, interest-bearing
3. **Receipt:** Must provide within 30 days
4. **Statement of Condition:** Must provide within 10 days
5. **Bank Notification:** Notify tenant of bank details within 30 days
6. **Interest:** Must pay 5% or bank's rate annually
7. **Return:** Within 30 days of lease end with itemized deductions
8. **Transfer:** Must transfer to new owner if property sold

**Penalties:** Triple damages + attorney fees for violations

### M.G.L. c. 151B - Fair Housing

**Prohibited Discrimination Based On:**
- Race, color, religion, national origin
- Sex, sexual orientation, gender identity
- Age, familial status, disability
- Veteran/military status
- Source of income (Section 8, etc.)

**Key Compliance Tasks:**
- Fair housing training
- Consistent screening criteria
- Documented rental decisions
- Reasonable accommodations for disabilities

### 42 U.S.C. § 4852d - Lead Paint Disclosure

**Requirements for Pre-1978 Properties:**
- Provide EPA pamphlet
- Disclose known lead paint hazards
- 10-day inspection period (if buyer/tenant requests)
- Signed acknowledgment required

### M.G.L. c. 148, § 26F - Smoke & CO Detectors

**Requirements:**
- Working smoke detectors on all floors
- Working CO detectors on all floors
- Certification by fire department
- Must be compliant before occupancy

---

## Rental Task Categories

### Category 1: Landlord Representation (Rental Listings)

**Total Tasks:** ~30

**Subcategories:**
1. **Listing Setup** (5 tasks)
   - Execute rental listing agreement
   - Determine rental price
   - Establish fee structure
   - Collect property details
   - Pre-listing property inspection

2. **Marketing & Showings** (6 tasks)
   - Photograph rental property
   - List on rental platforms (MLS, Zillow, Apartments.com)
   - Install lockbox (if approved)
   - Coordinate showings
   - Provide showing feedback

3. **Tenant Screening** (8 tasks)
   - Collect rental applications
   - Conduct credit check
   - Conduct background check
   - Verify income (3x rent rule)
   - Verify employment
   - Contact previous landlords
   - Contact personal references
   - Fair housing compliance review

4. **Lease Execution** (7 tasks)
   - Prepare compliant lease agreement
   - Include required MA disclosures
   - Execute lease agreement
   - Collect first month's rent
   - Collect last month's rent (if applicable)
   - Collect security deposit
   - Provide security deposit receipt

5. **Security Deposit Compliance** (4 tasks)
   - Set up MA bank account for deposit
   - Deposit funds within 30 days
   - Provide Statement of Condition form
   - Notify tenant of bank details

### Category 2: Tenant Representation

**Total Tasks:** ~20

**Subcategories:**
1. **Initial Consultation** (3 tasks)
   - Execute tenant representation agreement
   - Review tenant's budget and requirements
   - Explain rental process and timeline

2. **Property Search** (5 tasks)
   - Search available rental listings
   - Schedule property viewings
   - Accompany tenant to showings
   - Provide neighborhood information
   - Compare rental options

3. **Application & Screening** (4 tasks)
   - Prepare rental application
   - Gather required documents (pay stubs, references)
   - Submit application to landlord
   - Follow up on application status

4. **Lease Negotiation** (4 tasks)
   - Review lease agreement with tenant
   - Negotiate lease terms
   - Explain tenant rights and responsibilities
   - Review security deposit requirements

5. **Move-In** (4 tasks)
   - Complete Statement of Condition with tenant
   - Verify security deposit handling
   - Coordinate key transfer
   - Confirm utility setup

---

## Task Creation Strategy

### Naming Convention

**Landlord Tasks:**
```
rental-landlord-{category}-{action}

Examples:
- rental-landlord-listing-execute-agreement
- rental-landlord-screening-credit-check
- rental-landlord-deposit-setup-bank-account
```

**Tenant Tasks:**
```
rental-tenant-{category}-{action}

Examples:
- rental-tenant-search-schedule-viewings
- rental-tenant-application-gather-documents
- rental-tenant-lease-review-agreement
```

### Owner Roles

- **Landlord Tasks:** `owner_role = 'agent'` (agent representing landlord)
- **Tenant Tasks:** `owner_role = 'agent'` (agent representing tenant)
- **Some tasks:** `owner_role = 'buyer'` or `'seller'` (renamed to tenant/landlord in context)

### Filters

**Landlord Tasks:**
```sql
transaction_type_filter = 'rental_landlord'
```

**Tenant Tasks:**
```sql
transaction_type_filter = 'rental_tenant'
```

**Both:**
```sql
transaction_type_filter = 'rental_landlord,rental_tenant'
```

---

## Implementation Steps

### Step 1: Create SQL Script for Rental Tasks

Create `011_create_rental_tasks.sql` with:

1. **Rental Landlord Tasks** (30 tasks)
2. **Rental Tenant Tasks** (20 tasks)
3. **MA Rental Law Requirements** (legal citations)
4. **Security Deposit Compliance Tasks** (with deadlines)

### Step 2: Create Task Categories

Add rental-specific categories if needed:

```sql
INSERT INTO wp_ma_deal_task_categories
(category_key, name, sort_order, created_at, updated_at)
VALUES
('rental_listing', 'Rental Listing', 70, NOW(), NOW()),
('rental_screening', 'Tenant Screening', 71, NOW(), NOW()),
('rental_lease', 'Lease Execution', 72, NOW(), NOW()),
('rental_deposit', 'Security Deposit', 73, NOW(), NOW()),
('rental_search', 'Property Search', 74, NOW(), NOW());
```

### Step 3: Run Migration

Execute the rental tasks creation script.

### Step 4: Test Rental Filtering

Create test scenarios:
- Rental landlord transaction
- Rental tenant transaction
- Verify correct task application
- Verify security deposit tracking

### Step 5: Documentation

Create completion summary and testing results.

---

## Detailed Task Definitions

### Rental Landlord Tasks (30)

#### Listing Setup (5 tasks)

1. **Execute Rental Listing Agreement**
   - Key: `rental-landlord-listing-execute-agreement`
   - Category: `rental_listing`
   - Owner: `agent`
   - Filter: `rental_landlord`
   - Description: Sign exclusive rental listing agreement with landlord

2. **Determine Rental Price**
   - Key: `rental-landlord-listing-determine-price`
   - Category: `rental_listing`
   - Owner: `agent`
   - Filter: `rental_landlord`
   - Description: Conduct rental market analysis and set competitive rental price

3. **Establish Fee Structure**
   - Key: `rental-landlord-listing-establish-fee`
   - Category: `rental_listing`
   - Owner: `agent`
   - Filter: `rental_landlord`
   - Description: Agree on commission/fee structure (typically 1 month rent)

4. **Collect Property Details**
   - Key: `rental-landlord-listing-collect-details`
   - Category: `rental_listing`
   - Owner: `agent`
   - Filter: `rental_landlord`
   - Description: Gather bedrooms, bathrooms, amenities, lease terms, restrictions

5. **Conduct Pre-Listing Inspection**
   - Key: `rental-landlord-listing-pre-inspection`
   - Category: `rental_listing`
   - Owner: `agent`
   - Filter: `rental_landlord`
   - Description: Walk through property and note condition

#### Marketing & Showings (6 tasks)

6. **Photograph Rental Property**
7. **List on Rental Platforms**
8. **Install Lockbox (if approved)**
9. **Coordinate Rental Showings**
10. **Provide Showing Feedback to Landlord**
11. **Verify Property Ready to Show**

#### Tenant Screening (8 tasks)

12. **Collect Rental Applications**
13. **Conduct Credit Check**
14. **Conduct Background Check**
15. **Verify Tenant Income (3x Rent Rule)**
16. **Verify Employment**
17. **Contact Previous Landlords**
18. **Contact Personal References**
19. **Fair Housing Compliance Review**

#### Lease Execution (7 tasks)

20. **Prepare Compliant Lease Agreement** (MA law)
21. **Include Required MA Disclosures** (Lead paint, etc.)
22. **Execute Lease Agreement**
23. **Collect First Month Rent**
24. **Collect Last Month Rent (if applicable)**
25. **Collect Security Deposit**
26. **Provide Security Deposit Receipt**

#### Security Deposit Compliance (4 tasks)

27. **Set Up MA Bank Account for Deposit** (Legal: M.G.L. c. 186, § 15B)
28. **Deposit Funds into Bank Within 30 Days** (Legal: M.G.L. c. 186, § 15B)
29. **Provide Statement of Condition Form** (Legal: Within 10 days)
30. **Notify Tenant of Bank Account Details** (Legal: Within 30 days)

### Rental Tenant Tasks (20)

#### Initial Consultation (3 tasks)

1. **Execute Tenant Representation Agreement**
2. **Review Tenant Budget and Requirements**
3. **Explain Rental Process and Timeline**

#### Property Search (5 tasks)

4. **Search Available Rental Listings**
5. **Schedule Property Viewings**
6. **Accompany Tenant to Showings**
7. **Provide Neighborhood Information**
8. **Compare Rental Options**

#### Application & Screening (4 tasks)

9. **Prepare Rental Application**
10. **Gather Required Documents**
11. **Submit Application to Landlord**
12. **Follow Up on Application Status**

#### Lease Negotiation (4 tasks)

13. **Review Lease Agreement with Tenant**
14. **Negotiate Lease Terms**
15. **Explain Tenant Rights and Responsibilities**
16. **Review Security Deposit Requirements**

#### Move-In (4 tasks)

17. **Complete Statement of Condition with Tenant**
18. **Verify Security Deposit Handling**
19. **Coordinate Key Transfer**
20. **Confirm Utility Setup**

---

## Legal Requirements Matrix

| Requirement | Law | Deadline | Landlord Task | Tenant Task |
|-------------|-----|----------|---------------|-------------|
| Security Deposit Receipt | M.G.L. c. 186, § 15B | Immediate | ✅ | ✅ |
| Deposit to Bank | M.G.L. c. 186, § 15B | 30 days | ✅ | - |
| Bank Notification | M.G.L. c. 186, § 15B | 30 days | ✅ | ✅ |
| Statement of Condition | M.G.L. c. 186, § 15B | 10 days | ✅ | ✅ |
| Lead Paint Disclosure | 42 U.S.C. § 4852d | Before lease | ✅ | ✅ |
| Smoke/CO Detectors | M.G.L. c. 148, § 26F | Before occupancy | ✅ | - |
| Fair Housing Training | M.G.L. c. 151B | Ongoing | ✅ | - |

---

## Success Criteria

Phase 3 is complete when:

- ✅ 50+ rental-specific tasks created (30 landlord, 20 tenant)
- ✅ All MA rental law requirements flagged with citations
- ✅ Security deposit compliance tasks integrated
- ✅ Rental landlord transactions show correct tasks
- ✅ Rental tenant transactions show correct tasks
- ✅ Rental tasks properly filtered from buy/sell transactions
- ✅ Testing validates rental task filtering

---

## Timeline Estimate

- **Planning:** 30 minutes ✅ (this document)
- **SQL Script Creation:** 2-3 hours (50+ task definitions)
- **Task Category Creation:** 15 minutes
- **Execution:** 15 minutes
- **Testing:** 1 hour
- **Documentation:** 30 minutes
- **Total:** ~4-5 hours

---

## Risk Mitigation

### Risk 1: Complex MA Rental Law
- **Mitigation:** Reference MA rental law guide, use SecurityDeposit model
- **Validation:** Legal citations on all compliance tasks

### Risk 2: Overlapping Tasks with Buy/Sell
- **Mitigation:** Clear transaction_type_filter on all rental tasks
- **Testing:** Verify no rental tasks appear on buy/sell transactions

### Risk 3: Security Deposit Compliance Complexity
- **Mitigation:** Use existing SecurityDeposit model with compliance checking
- **Documentation:** Clear instructions for agents on MA law requirements

---

## Next Steps

1. Create detailed SQL script with all 50+ rental tasks
2. Add rental task categories
3. Execute migration
4. Test rental task filtering
5. Document results

**Ready to create the rental tasks SQL script!**
