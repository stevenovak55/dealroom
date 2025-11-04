# Rental Transaction Template Design

**Date**: 2025-10-31
**Purpose**: Design separate templates for rental transactions (landlord & tenant sides)
**Status**: Planning Phase

---

## RATIONALE

Rental transactions have fundamentally different workflows than property sales:

| Aspect | Property Sales | Rentals |
|--------|---------------|---------|
| **Duration** | One-time transaction | Ongoing relationship |
| **Documentation** | Title transfer, deed, mortgage | Lease agreement, no deed transfer |
| **Milestones** | P&S, loan commitment, closing | Application, screening, lease signing, move-in |
| **Financial** | Down payment, mortgage, closing costs | Security deposit, first/last month, application fee |
| **Compliance** | Title 5, lead paint, smoke/CO | Sanitary code, habitability, rent control |
| **Parties** | Buyers, sellers, attorneys, lenders | Landlords, tenants, property managers |
| **Typical Timeline** | 30-60 days | 7-30 days |

---

## RENTAL TRANSACTION TYPES

### 1. Landlord Side (Rental Listing)
**Agent represents property owner seeking tenants**

**Typical Workflow**:
1. **Pre-Listing**: Prepare property, set rent, create listing
2. **Marketing**: Advertise, schedule showings
3. **Applications**: Collect applications, screen tenants
4. **Lease Execution**: Draft lease, sign documents
5. **Move-In**: Security deposit, keys, condition report
6. **Ongoing**: Maintenance, renewals, inspections

### 2. Tenant Side (Rental Search)
**Agent represents tenant seeking rental property**

**Typical Workflow**:
1. **Search**: Define criteria, view properties
2. **Application**: Submit application, pay fees
3. **Screening**: Background check, credit check, references
4. **Lease Review**: Review terms, negotiate
5. **Move-In**: Pay deposits, sign lease, receive keys
6. **Ongoing**: Know tenant rights, document issues

---

## MASSACHUSETTS RENTAL REGULATIONS

### Mandatory Landlord Responsibilities
1. **Lead Paint Disclosure** (Pre-1978 properties)
2. **Smoke & CO Detectors** (Must be inspected and certified)
3. **Sanitary Code Compliance** (105 CMR 410.000)
4. **Security Deposit** (Must be in separate interest-bearing account)
5. **Statement of Condition** (Required at move-in/move-out)
6. **Rent Control** (In certain municipalities: Boston, Cambridge, Brookline)

### Tenant Rights & Protections
1. **Habitability** (Heat, hot water, no pests, safe structure)
2. **Security Deposit Interest** (5% annually or prevailing rate)
3. **Quiet Enjoyment** (No landlord harassment)
4. **Retaliation Protection** (Against complaints)
5. **30-Day Notice** (For rent increases or lease termination)

---

## PROPOSED TEMPLATE STRUCTURE

### Template 1: Landlord Rental Listing
**File**: `templates/rental_landlord.yaml`

```yaml
template_id: rental_landlord
version: 1.0.0
title: Rental Property - Landlord Side
description: Complete checklist for landlords listing rental properties in Massachusetts
transaction_side: listing
property_types:
  - SFH
  - Condo
  - Multifamily
  - Commercial
created_date: '2025-10-31'
author: MA Deal Room Team

workflows:
  - name: Pre-Listing Preparation
    description: Prepare property for rental listing
    tasks:
      - id: property_inspection_landlord
        title: Inspect Property for Rentability
        category: property_prep
        owner_role: agent
        description: Ensure property meets MA sanitary code requirements
        due: Listing
        due_offset: -7d
        mandatory: true
        priority: high
        estimated_duration: 2-3 hours
        citations:
          - url: https://www.mass.gov/regulations/105-CMR-410000-state-sanitary-code-chapter-ii-minimum-standards-of-fitness-for-human
            title: 105 CMR 410.000 - MA Sanitary Code

      - id: smoke_co_certificate
        title: Obtain Smoke & CO Detector Certificate
        category: property_prep
        owner_role: agent
        description: Required MA certificate showing working smoke and CO detectors
        due: Listing
        due_offset: -5d
        mandatory: true
        priority: high
        estimated_duration: 1-2 weeks
        citations:
          - url: https://www.mass.gov/info-details/about-the-smoke-and-carbon-monoxide-detector-law
            title: MA Smoke & CO Detector Law
        vendor_type: fire_dept

      - id: lead_paint_disclosure
        title: Prepare Lead Paint Disclosure (Pre-1978)
        category: compliance
        owner_role: agent
        description: Required for all properties built before 1978
        due: Listing
        due_offset: -3d
        mandatory: true
        applies_if: property_year_built < 1978
        priority: high
        citations:
          - url: https://www.mass.gov/info-details/about-lead-in-paint
            title: MA Lead Paint Law

      - id: security_deposit_account
        title: Set Up Security Deposit Bank Account
        category: financial
        owner_role: seller
        description: Must be separate, interest-bearing account in MA bank
        due: Listing
        due_offset: -7d
        mandatory: true
        priority: high
        citations:
          - url: https://www.mass.gov/info-details/your-rights-and-responsibilities-as-a-landlord
            title: MGL c. 186 §15B - Security Deposits

      - id: create_rental_listing
        title: Create Rental Listing
        category: marketing
        owner_role: agent
        description: Draft listing with photos, description, rent, terms
        due: Listing
        due_offset: +0d
        mandatory: true
        priority: normal

      - id: set_rental_criteria
        title: Establish Tenant Screening Criteria
        category: tenant_screening
        owner_role: agent
        description: Define income requirements, credit score, background check criteria (must be consistent and non-discriminatory)
        due: Listing
        due_offset: +1d
        mandatory: true
        priority: high

  - name: Marketing & Showings
    description: Advertise property and conduct showings
    tasks:
      - id: list_on_platforms
        title: Post Listing on Rental Platforms
        category: marketing
        owner_role: agent
        description: MLS, Zillow, Craigslist, Apartments.com, etc.
        due: Listing
        due_offset: +1d
        mandatory: false

      - id: schedule_showings
        title: Schedule Property Showings
        category: marketing
        owner_role: agent
        description: Coordinate showing times with landlord and prospects
        due: Listing
        due_offset: +2d
        mandatory: false

  - name: Application & Screening
    description: Collect and process rental applications
    tasks:
      - id: collect_applications
        title: Collect Rental Applications
        category: tenant_screening
        owner_role: agent
        description: Collect completed applications with fee ($50 max in MA)
        due: Offer_Accepted
        due_offset: +0d
        mandatory: true
        priority: high

      - id: credit_background_check
        title: Run Credit & Background Checks
        category: tenant_screening
        owner_role: agent
        description: Verify income, credit history, criminal background, rental history
        due: Offer_Accepted
        due_offset: +1d
        mandatory: true
        priority: high
        estimated_duration: 2-3 days

      - id: verify_employment
        title: Verify Employment & Income
        category: tenant_screening
        owner_role: agent
        description: Contact employer, review pay stubs (income should be 3x rent)
        due: Offer_Accepted
        due_offset: +2d
        mandatory: true
        priority: high

      - id: check_references
        title: Check Previous Landlord References
        category: tenant_screening
        owner_role: agent
        description: Contact previous landlords for rental history
        due: Offer_Accepted
        due_offset: +2d
        mandatory: true
        priority: normal

      - id: approve_tenant
        title: Approve Tenant Application
        category: tenant_screening
        owner_role: seller
        description: Landlord makes final decision on tenant selection
        due: Offer_Accepted
        due_offset: +3d
        mandatory: true
        priority: high
        depends_on:
          - credit_background_check
          - verify_employment
          - check_references

  - name: Lease Execution
    description: Prepare and sign lease agreement
    tasks:
      - id: draft_lease
        title: Draft Rental Lease Agreement
        category: legal
        owner_role: agent
        description: Prepare lease using MA-compliant template or attorney-drafted lease
        due: PS_Agreement
        due_offset: +0d
        mandatory: true
        priority: high
        estimated_duration: 2-4 hours

      - id: review_lease_with_landlord
        title: Review Lease with Landlord
        category: legal
        owner_role: agent
        description: Go through lease terms, special conditions, rent amounts
        due: PS_Agreement
        due_offset: +1d
        mandatory: true

      - id: tenant_lease_review
        title: Tenant Reviews and Signs Lease
        category: legal
        owner_role: agent
        description: Tenant reviews, asks questions, and signs lease
        due: PS_Agreement
        due_offset: +2d
        mandatory: true
        priority: high

      - id: landlord_signs_lease
        title: Landlord Signs Lease
        category: legal
        owner_role: seller
        description: Landlord executes lease agreement
        due: PS_Agreement
        due_offset: +3d
        mandatory: true
        priority: high

  - name: Move-In Preparation
    description: Collect funds and prepare for tenant move-in
    tasks:
      - id: collect_security_deposit
        title: Collect Security Deposit
        category: financial
        owner_role: agent
        description: Collect security deposit (max 1 month's rent in MA)
        due: Closing
        due_offset: -3d
        mandatory: true
        priority: high

      - id: collect_first_last_month
        title: Collect First and Last Month's Rent
        category: financial
        owner_role: agent
        description: Collect first and last month's rent payments
        due: Closing
        due_offset: -3d
        mandatory: true
        priority: high

      - id: statement_of_condition
        title: Complete Statement of Condition
        category: move_in
        owner_role: agent
        description: Document property condition with photos at move-in (required by MA law)
        due: Closing
        due_offset: -1d
        mandatory: true
        priority: high
        citations:
          - url: https://www.mass.gov/info-details/your-rights-and-responsibilities-as-a-landlord
            title: MGL c. 186 §15B(2)(a) - Statement of Condition

      - id: provide_keys
        title: Provide Keys and Access Codes
        category: move_in
        owner_role: seller
        description: Provide tenant with keys, mailbox key, parking pass, etc.
        due: Closing
        due_offset: +0d
        mandatory: true
        priority: high

      - id: provide_security_deposit_receipt
        title: Provide Security Deposit Receipt
        category: financial
        owner_role: agent
        description: Give tenant receipt with bank name, account number (required by MA law)
        due: Closing
        due_offset: +0d
        mandatory: true
        priority: high
        citations:
          - url: https://www.mass.gov/info-details/your-rights-and-responsibilities-as-a-landlord
            title: MGL c. 186 §15B - Security Deposit Receipt
```

### Template 2: Tenant Rental Search
**File**: `templates/rental_tenant.yaml`

```yaml
template_id: rental_tenant
version: 1.0.0
title: Rental Property - Tenant Side
description: Complete checklist for tenants searching for rental properties in Massachusetts
transaction_side: buyer
property_types:
  - SFH
  - Condo
  - Multifamily
  - Commercial
created_date: '2025-10-31'
author: MA Deal Room Team

workflows:
  - name: Search & Application
    description: Find suitable rental and submit application
    tasks:
      - id: define_search_criteria
        title: Define Rental Search Criteria
        category: search
        owner_role: buyer
        description: Location, budget, bedrooms, amenities, move-in date
        due: Listing
        due_offset: +0d

      - id: gather_application_docs
        title: Gather Application Documents
        category: application
        owner_role: buyer
        description: Pay stubs, ID, references, bank statements, credit report
        due: Listing
        due_offset: +2d

      - id: view_properties
        title: Schedule and Attend Property Viewings
        category: search
        owner_role: agent
        description: View properties matching tenant criteria
        due: Listing
        due_offset: +3d

      - id: submit_application
        title: Submit Rental Application
        category: application
        owner_role: buyer
        description: Complete application, pay application fee (max $50 in MA)
        due: Offer_Accepted
        due_offset: +0d
        mandatory: true
        priority: high

  - name: Application Processing
    description: Wait for landlord decision and prepare for approval
    tasks:
      - id: await_screening
        title: Await Background & Credit Check
        category: application
        owner_role: buyer
        description: Landlord runs checks; respond promptly to requests
        due: Offer_Accepted
        due_offset: +2d

      - id: receive_approval
        title: Receive Application Approval
        category: application
        owner_role: buyer
        description: Get approval notification from landlord/agent
        due: Offer_Accepted
        due_offset: +3d
        mandatory: true

  - name: Lease Review
    description: Review and sign rental lease
    tasks:
      - id: receive_lease
        title: Receive Draft Lease Agreement
        category: legal
        owner_role: buyer
        description: Get lease from landlord for review
        due: PS_Agreement
        due_offset: +0d
        mandatory: true

      - id: review_lease_terms
        title: Review Lease Terms Carefully
        category: legal
        owner_role: buyer
        description: Read all terms, ask questions, consider attorney review
        due: PS_Agreement
        due_offset: +1d
        mandatory: true
        priority: high
        notes: Check rent amount, lease duration, utilities, pets, parking, maintenance responsibilities

      - id: negotiate_terms
        title: Negotiate Lease Terms (if needed)
        category: legal
        owner_role: agent
        description: Request modifications if certain terms are unacceptable
        due: PS_Agreement
        due_offset: +2d

      - id: sign_lease
        title: Sign Lease Agreement
        category: legal
        owner_role: buyer
        description: Execute lease after all questions answered
        due: PS_Agreement
        due_offset: +3d
        mandatory: true
        priority: high

  - name: Move-In
    description: Pay move-in costs and move into property
    tasks:
      - id: pay_security_deposit
        title: Pay Security Deposit
        category: financial
        owner_role: buyer
        description: Pay security deposit (max 1 month in MA)
        due: Closing
        due_offset: -3d
        mandatory: true
        priority: high

      - id: pay_first_last
        title: Pay First and Last Month's Rent
        category: financial
        owner_role: buyer
        description: Pay first and last month rent as required
        due: Closing
        due_offset: -3d
        mandatory: true
        priority: high

      - id: receive_security_deposit_receipt
        title: Receive Security Deposit Receipt
        category: financial
        owner_role: buyer
        description: Get receipt with bank info (required by MA law)
        due: Closing
        due_offset: -2d
        mandatory: true
        priority: high
        citations:
          - url: https://www.mass.gov/info-details/your-rights-and-responsibilities-as-a-tenant
            title: MGL c. 186 §15B - Tenant Rights

      - id: walkthrough_statement_of_condition
        title: Complete Move-In Walkthrough
        category: move_in
        owner_role: buyer
        description: Document property condition with photos, sign statement
        due: Closing
        due_offset: -1d
        mandatory: true
        priority: high
        notes: Take detailed photos/videos of any existing damage

      - id: receive_keys
        title: Receive Keys and Access
        category: move_in
        owner_role: buyer
        description: Get keys, mailbox key, parking pass, access codes
        due: Closing
        due_offset: +0d
        mandatory: true
        priority: high

      - id: setup_utilities
        title: Set Up Utilities in Tenant Name
        category: move_in
        owner_role: buyer
        description: Transfer electric, gas, internet, cable if tenant-paid
        due: Closing
        due_offset: +0d
        mandatory: false

      - id: renters_insurance
        title: Obtain Renters Insurance
        category: move_in
        owner_role: buyer
        description: Get renters insurance policy (often required by landlord)
        due: Closing
        due_offset: +0d
        mandatory: false
        priority: normal
```

---

## MILESTONES FOR RENTALS

Rental milestones are different from sales:

| Milestone | Rental Equivalent | Typical Timeline |
|-----------|------------------|------------------|
| **Listing** | Property listed for rent | Day 0 |
| **Offer Accepted** | Application submitted | +1-5 days |
| **P&S Agreement** | Lease signed | +7-10 days |
| **Closing** | Move-in date | +14-21 days |

**Note**: Rentals don't have "loan commitment" since no mortgage is involved.

---

## IMPLEMENTATION STEPS

### 1. Create YAML Files
- `templates/rental_landlord.yaml`
- `templates/rental_tenant.yaml`

### 2. Update Database
Add rental templates to system templates table

### 3. Update Transaction Model
Consider adding `transaction_category` ENUM:
- `sale` (default)
- `rental`
- `commercial_sale`
- `commercial_lease`

### 4. Update UI
- Add "Transaction Type" selector: Sale vs Rental
- Show different milestone fields for rentals
- Filter templates by transaction type

### 5. Adjust Timeline Calculator
Rentals use different timeline:
- Application to lease: 7-10 days
- Lease to move-in: 7-14 days
- No loan commitment milestone

---

## METRICS TO TRACK

1. **Time to Tenant Placement**: Days from listing to lease signed
2. **Application Volume**: Number of applications per listing
3. **Approval Rate**: % of applications approved
4. **Move-In Success**: % of approved tenants who actually move in
5. **Landlord Satisfaction**: Feedback on agent performance

---

## FUTURE ENHANCEMENTS

1. **Tenant Portal**: Allow tenants to track application status
2. **Automated Screening**: Integration with credit check services
3. **Lease Templates**: Library of MA-compliant lease clauses
4. **Rent Collection**: Track ongoing rent payments
5. **Maintenance Requests**: System for tenant maintenance requests
6. **Renewal Tracking**: Alerts for lease renewals (60-90 days before expiration)

---

## QUESTIONS FOR STAKEHOLDER

1. **Do you handle both residential and commercial rentals?** Should we create separate templates?
2. **What's your typical screening criteria?** (Income 3x rent? Credit score minimum?)
3. **Do you use standard lease forms or attorney-drafted?**
4. **Do you track ongoing tenant relationships** or just the initial lease-up?
5. **Do you handle property management** or just leasing/placement?

---

**Next Steps**: Review this design, provide feedback, then I'll implement the rental templates.
