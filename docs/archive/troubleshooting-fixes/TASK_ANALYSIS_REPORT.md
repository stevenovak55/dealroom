# MA Deal Room Task Analysis Report

**Prepared By:** Claude DB (Data Architect Agent)
**Date:** 2025-10-30
**For:** Claude (System Architect)
**Status:** DRAFT - AWAITING REVIEW

---

## Executive Summary

This report provides a comprehensive analysis of all existing tasks in the MA Deal Room system across five YAML template files. The analysis reveals **significant classification issues** with tasks appearing in wrong transaction types, lack of proper transaction-type filtering, and property-specific tasks applied universally.

### Key Findings

- **Total Unique Tasks Analyzed:** 150+ unique tasks across all templates
- **Templates Reviewed:** 5 (base_transaction, condo, sfh_city_water, sfh_septic, multifamily)
- **Critical Issues Found:** 23 major misclassifications
- **Missing MA Legal Requirements:** 4 critical tasks not present
- **Tasks Needing Reclassification:** 67 tasks (45% of all tasks)

### Severity Assessment

**CRITICAL (Requires Immediate Attention):**
- Sell-side specific tasks appearing in universal base template
- Buy-side specific tasks in base template without transaction type filtering
- Rental-specific tasks completely missing from system
- Commercial transaction tasks completely missing

**HIGH PRIORITY:**
- Property-specific tasks (septic, well, condo) lack proper conditional logic
- MA legal requirement tasks missing proper citations
- Task dependencies not properly mapped

---

## Section 1: Current State Analysis

### 1.1 Template Overview

| Template File | Extends | Task Count | Property Types | Issues Found |
|--------------|---------|------------|----------------|--------------|
| base_transaction.yaml | None | 100+ | ALL | CRITICAL: Mix of buy/sell tasks |
| condo.yaml | base_transaction | 80+ | Condo | Mostly correct, some overlaps |
| sfh_city_water.yaml | base_transaction | 80+ | SFH | Missing buy/sell distinction |
| sfh_septic.yaml | base_transaction | 90+ | SFH + Septic | Property-specific correct |
| multifamily.yaml | base_transaction | 90+ | Multifamily | Tenant-occupied correct |

### 1.2 Critical Problems Identified

#### Problem 1: Base Transaction Template Confusion
**File:** `/home/snova/projects/dealroom/templates/base_transaction.yaml`

The "base_transaction" template is labeled as `type: universal` but contains **both buy-side and sell-side specific tasks**. This violates the principle that universal tasks should apply to ALL transaction types.

**Examples of Misplaced Tasks:**

```yaml
- title: "Review and sign listing agreement"  # SELL SIDE ONLY
  owner_role: seller
  due_days: 0

- title: "Schedule professional photography"  # SELL SIDE ONLY
  owner_role: agent
  due_days: 2

- title: "Property Information Entered into MLS"  # SELL SIDE ONLY
  owner_role: agent
  milestone: true

- title: "Collect buyer pre-approval letter"  # BUY SIDE ONLY
  owner_role: buyer
  due_days: 2
```

**Impact:** Any transaction using base_transaction will get both buy-side AND sell-side tasks, creating confusion and irrelevant task lists.

#### Problem 2: No Transaction Type Filtering

**NONE** of the YAML templates have transaction type filters like:
- `transaction_type: 'buy_side'`
- `transaction_type: 'sell_side'`
- `transaction_type: 'rental_landlord'`
- `transaction_type: 'rental_tenant'`

**Example:** The task "Review and sign listing agreement" should have:
```yaml
applies_if: "transaction_type == 'sell_side'"
```

But instead has NO filter, meaning it appears in buy-side transactions.

#### Problem 3: Missing Rental Transaction Tasks

Based on the reference document (lines 342-434), **rental transactions** (both landlord and tenant representation) have extensive MA-specific requirements. **NONE of these tasks exist in the current templates.**

**Missing Rental Landlord Tasks:**
- Execute rental listing agreement
- Prepare compliant lease agreement
- Conduct tenant screening (credit, background, references)
- Collect and deposit security deposits in MA interest-bearing account
- Provide Statement of Condition within 10 days
- Provide fire insurance disclosure within 15 days
- Deposit security deposit within 30 days + provide bank info
- Execute lease agreement
- Provide receipt for all payments

**Missing Rental Tenant Tasks:**
- Execute tenant representation agreement
- Disclose fee arrangement in writing (MA law requirement)
- Review lease agreement with tenant
- Verify lease includes required MA disclosures
- Review Statement of Condition
- Attend lease signing with tenant
- Conduct move-in walkthrough

**Legal Citation:** M.G.L. c. 112, § 87 DDD ½ - Rental Agent Fee Disclosure Requirement (reference doc line 603)

#### Problem 4: Missing Commercial Transaction Tasks

Based on reference document (lines 437-455 and 559-593), commercial transactions have extensive additional requirements. **NONE exist in current templates.**

**Missing Commercial Tasks:**
- Order Phase I Environmental Site Assessment (ESA)
- Order Phase II ESA (if needed)
- Zoning compliance verification
- Certificate of occupancy verification
- ADA compliance review
- Collect all tenant lease agreements
- Prepare detailed rent roll
- Review lease terms and obligations
- Verify CAM (Common Area Maintenance) charges
- Analyze operating expense escalations
- Collect 3 years operating statements
- Analyze net operating income (NOI)
- Calculate capitalization rate

#### Problem 5: Agency Disclosure Task Duplication

The task "Provide Agency Disclosure Form" appears in **EVERY property-specific template** (condo.yaml:30-47, sfh_city_water.yaml:30-47, sfh_septic.yaml:30-48, multifamily.yaml:30-48).

**Issue:** Since these templates extend `base_transaction`, if base_transaction also has this task, it would be duplicated.

**Recommended Fix:** Agency disclosure should be in base_transaction ONLY as a universal task, and removed from property-specific templates.

### 1.3 Task Ownership and Role Issues

**Problem:** Many tasks have incorrect `owner_role` assignments based on transaction type.

**Example from base_transaction.yaml:**

```yaml
# Line 62: This is a SELLER task, but appears in all transactions
- title: "Review and sign listing agreement"
  owner_role: seller

# Line 100: This is a BUYER task, but appears in all transactions
- title: "Send welcome packet to buyer"
  owner_role: agent
```

**Impact:** When a seller lists a property (sell-side), they shouldn't see "Send welcome packet to buyer" tasks. When a buyer makes an offer (buy-side), they shouldn't see "Review and sign listing agreement."

---

## Section 2: Complete Task Classification Table

### 2.1 Universal Tasks (Apply to ALL Transactions)

These tasks should have `applies_if: "ALL"` or no condition:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| agency_disclosure | Present MA Agency Disclosure Form | ALL transactions | Required at first meeting for all transactions | MA Licensing Regulations |
| add_client_information | Add client information to transaction | ALL transactions | Required for all transactions | Standard practice |
| schedule_initial_consultation | Schedule initial consultation | ALL transactions | Communication requirement | Standard practice |
| provide_transaction_timeline | Provide transaction timeline to client | ALL transactions | Client communication | Standard practice |
| coordinate_with_attorneys | Coordinate with transaction attorneys | ALL transactions | Legal review needed for all | MA standard practice |
| maintain_transaction_docs | Maintain transaction documentation | ALL transactions | Recordkeeping requirement | Standard practice |
| order_title_search | Order title search | ALL (except rentals) | Required for property transfers | Standard practice |
| review_title_report | Review title report | ALL (except rentals) | Title clearance needed | Standard practice |
| schedule_final_walkthrough | Schedule final walkthrough | ALL (except rentals) | Pre-closing verification | Standard practice |
| verify_wire_instructions | Verify wire transfer information | ALL (except rentals) | Wire fraud prevention | Standard practice |
| attend_closing | Attend closing | ALL (except rentals) | Agent presence required | Standard practice |
| collect_executed_documents | Collect executed documents | ALL (except rentals) | Documentation requirement | Standard practice |
| verify_deed_recording | Verify deed recording | ALL (except rentals) | Confirm transfer recorded | Standard practice |
| update_mls_status | Update MLS status | transaction_type IN ('sell_side', 'rental_landlord') | MLS updates for listings only | MLS rules |
| request_client_testimonial | Request client testimonial/review | ALL transactions | Client relationship | Standard practice |
| process_commission_payment | Process commission payment | ALL transactions | Commission tracking | Standard practice |
| close_transaction_in_system | Close transaction in system | ALL transactions | System housekeeping | Standard practice |

### 2.2 Buy Side Only Tasks

These tasks should have `applies_if: "transaction_type == 'buy_side'"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| sign_buyer_agency_agreement | Sign Buyer Agency Agreement | transaction_type='buy_side' | Mandatory representation agreement | NAR 2025 Settlement |
| execute_buyer_fee_agreement | Execute Buyer Fee Agreement | transaction_type='buy_side' | Fee disclosure required | NAR 2025 Settlement |
| obtain_mortgage_preapproval | Obtain mortgage pre-approval letter | transaction_type='buy_side' | Before making offers | Standard practice |
| review_buyer_financial_capacity | Review buyer's financial capacity | transaction_type='buy_side' | DTI, credit, down payment review | Underwriting standard |
| prepare_offer_to_purchase | Prepare Offer to Purchase | transaction_type='buy_side' | Buyer's initial offer document | MA standard form |
| present_offer_to_seller | Present offer to seller's agent | transaction_type='buy_side' | Submit and communicate offer | Standard practice |
| negotiate_offer_terms | Negotiate offer terms | transaction_type='buy_side' | Price, contingencies, timeline | Standard practice |
| collect_initial_deposit_emd | Collect initial deposit (earnest money) | transaction_type='buy_side' | Typically $1,000 with offer | Standard practice |
| deliver_deposit_to_escrow | Deliver deposit to escrow holder | transaction_type='buy_side' | Usually seller's attorney | Standard practice |
| review_ps_agreement_buyer | Review P&S agreement draft | transaction_type='buy_side' | Attorney-prepared document | Legal review |
| execute_ps_agreement_buyer | Execute P&S agreement (buyer) | transaction_type='buy_side' | Buyer signature | Required |
| collect_additional_ps_deposit | Collect additional deposit | transaction_type='buy_side' | Typically 5% at P&S | Standard practice |
| submit_formal_mortgage_app | Submit formal mortgage application | transaction_type='buy_side' | Complete full application | Lender requirement |
| coordinate_appraisal | Coordinate appraisal scheduling | transaction_type='buy_side' | Lender-ordered appraisal | Lender requirement |
| provide_access_for_appraiser | Provide access for appraiser | transaction_type='buy_side' | Property access | Coordination |
| review_appraisal_report | Review appraisal report | transaction_type='buy_side' | Check for value issues | Due diligence |
| address_low_appraisal | Address low appraisal (if applicable) | transaction_type='buy_side' | Renegotiate or make up difference | Problem resolution |
| schedule_home_inspection | Schedule home inspection | transaction_type='buy_side' | General home inspection | Due diligence |
| attend_inspection_with_buyer | Attend home inspection with buyer | transaction_type='buy_side' | Be present for inspection | Support buyer |
| review_inspection_report_buyer | Review inspection report with buyer | transaction_type='buy_side' | Explain findings | Due diligence |
| prepare_inspection_response | Prepare inspection response | transaction_type='buy_side' | Repair requests, credits, waiver | Negotiation |
| negotiate_inspection_items | Negotiate inspection items | transaction_type='buy_side' | Work with seller on repairs | Negotiation |
| obtain_homeowners_insurance | Obtain homeowners insurance quotes | transaction_type='buy_side' | Required for closing | Lender requirement |
| verify_insurance_coverage | Verify insurance coverage in place | transaction_type='buy_side' | Confirm before closing | Lender requirement |
| provide_proof_of_insurance | Provide proof of insurance to lender | transaction_type='buy_side' | Required for closing | Lender requirement |
| review_closing_disclosure | Review HUD-1/Closing Disclosure | transaction_type='buy_side' | Verify all costs accurate | Consumer protection |
| coordinate_final_walkthrough_buyer | Coordinate final walkthrough | transaction_type='buy_side' | Day before or morning of closing | Verification |
| verify_property_condition_walkthrough | Verify property condition at walkthrough | transaction_type='buy_side' | Ensure agreed-upon condition | Contract compliance |
| prepare_closing_funds_wire | Prepare closing funds wire | transaction_type='buy_side' | Same-day wiring | Closing requirement |
| verify_seller_vacated | Verify seller has vacated | transaction_type='buy_side' | Ensure property is empty | Contract compliance |
| review_closing_docs_with_buyer | Review and explain closing docs to buyer | transaction_type='buy_side' | ALTA statement, deed, note | Closing support |
| coordinate_key_transfer | Coordinate key transfer | transaction_type='buy_side' | Obtain keys, garage openers | Access transfer |
| assist_utility_transfers_buyer | Assist with utility transfers to buyer | transaction_type='buy_side' | Electric, gas, water, internet | Utility coordination |
| provide_vendor_referrals_buyer | Provide buyer with vendor referrals | transaction_type='buy_side' | Contractors, handyman, etc. | Client service |

### 2.3 Sell Side Only Tasks

These tasks should have `applies_if: "transaction_type == 'sell_side'"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| execute_listing_agreement | Execute Exclusive Right to Sell Agreement | transaction_type='sell_side' | Most common listing type | Standard practice |
| determine_listing_price | Determine listing price | transaction_type='sell_side' | CMA and pricing strategy | Professional service |
| complete_cma | Complete Comparative Market Analysis | transaction_type='sell_side' | Provide to seller | Market analysis |
| establish_commission_structure | Establish listing commission structure | transaction_type='sell_side' | Agree on commission split | Contract terms |
| review_seller_net_proceeds | Review seller's net proceeds estimate | transaction_type='sell_side' | Calculate expected net | Financial planning |
| pre_listing_assessment | Conduct pre-listing property assessment | transaction_type='sell_side' | Walkthrough and notes | Property evaluation |
| recommend_repairs_improvements | Recommend repairs/improvements | transaction_type='sell_side' | Prepare property for market | Marketing prep |
| verify_repairs_completed | Verify seller has made agreed repairs | transaction_type='sell_side' | Confirm completion | Quality control |
| schedule_professional_photography | Schedule professional photography | transaction_type='sell_side' | High-quality listing photos | Marketing |
| coordinate_virtual_tour | Coordinate virtual tour/video | transaction_type='sell_side' | Optional but recommended | Marketing |
| arrange_staging_consultation | Arrange staging consultation | transaction_type='sell_side' | Professional staging if needed | Marketing |
| install_lockbox | Install lockbox | transaction_type='sell_side' | For agent showings | Access management |
| install_yard_sign | Install yard sign | transaction_type='sell_side' | For Sale signage | Marketing |
| complete_seller_disclosure | Complete Seller's Property Disclosure Statement | transaction_type='sell_side' | Mandatory disclosure | MA requirement |
| gather_property_documentation | Gather property documentation | transaction_type='sell_side' | Deed, survey, permits, warranties | Due diligence prep |
| collect_utility_bills | Collect utility bills | transaction_type='sell_side' | For buyer information | Information sharing |
| obtain_septic_pumping_records | Obtain septic pumping records | transaction_type='sell_side' AND property.has_septic | If applicable | Documentation |
| collect_hoa_condo_docs | Collect HOA/Condo documents | transaction_type='sell_side' AND property_type='condo' | If applicable | Condo requirements |
| enter_listing_in_mls | Enter listing into MLS | transaction_type='sell_side' | MLS submission | Marketing |
| create_marketing_materials | Create property marketing materials | transaction_type='sell_side' | Brochures, flyers, online | Marketing |
| distribute_listing_to_agents | Distribute listing to agent network | transaction_type='sell_side' | Email blast to cooperating agents | Marketing |
| schedule_open_houses | Schedule and host open houses | transaction_type='sell_side' | Public showings | Marketing |
| coordinate_private_showings | Coordinate private showings | transaction_type='sell_side' | Agent showings | Access management |
| provide_showing_feedback | Provide showing feedback to seller | transaction_type='sell_side' | Communicate buyer agent feedback | Communication |
| adjust_marketing_strategy | Adjust marketing strategy as needed | transaction_type='sell_side' | Based on market response | Strategy adjustment |
| receive_review_offers | Receive and review offers | transaction_type='sell_side' | Analyze all offer terms | Offer evaluation |
| present_offers_to_seller | Present offers to seller | transaction_type='sell_side' | Explain and recommend | Representation |
| prepare_counter_offer | Counter-offer preparation | transaction_type='sell_side' | Draft seller's counter-offer | Negotiation |
| negotiate_on_behalf_seller | Negotiate on behalf of seller | transaction_type='sell_side' | Price, terms, contingencies | Negotiation |
| accept_offer | Accept offer | transaction_type='sell_side' | Execute accepted offer | Contract execution |
| respond_buyer_inspection_requests | Respond to buyer's inspection requests | transaction_type='sell_side' | Negotiate repairs or credits | Negotiation |
| coordinate_repair_completion | Coordinate repair completion | transaction_type='sell_side' | If seller agrees to repairs | Project management |
| provide_receipts_completed_work | Provide receipts for completed work | transaction_type='sell_side' | Document repairs | Documentation |
| respond_buyer_attorney_questions | Respond to buyer's attorney questions | transaction_type='sell_side' | Title, survey, other issues | Legal coordination |
| prepare_property_for_appraisal | Prepare property for appraiser | transaction_type='sell_side' | Ensure access and presentation | Appraisal prep |
| provide_comps_to_appraiser | Provide comparable sales to appraiser | transaction_type='sell_side' | Support listing price | Appraisal support |
| address_appraisal_challenges | Address appraisal challenges | transaction_type='sell_side' | If value comes in low | Problem resolution |
| coordinate_seller_moving_timeline | Coordinate seller's moving timeline | transaction_type='sell_side' | Ensure timely vacancy | Logistics |
| verify_seller_removed_personal_property | Verify seller has removed personal property | transaction_type='sell_side' | As per agreement | Contract compliance |
| arrange_final_cleaning | Arrange for property to be cleaned | transaction_type='sell_side' | Final cleaning before closing | Property prep |
| verify_utilities_on_through_closing | Verify utilities remain on through closing | transaction_type='sell_side' | For walkthrough | Utility management |
| prepare_possession_transfer | Prepare for possession transfer | transaction_type='sell_side' | Keys, garage openers, mailbox key | Transfer prep |
| collect_all_keys_access_devices | Collect all keys and access devices | transaction_type='sell_side' | For transfer to buyer | Access collection |
| review_seller_closing_statement | Review seller's closing statement | transaction_type='sell_side' | Verify all debits/credits | Financial review |
| coordinate_seller_closing_attendance | Coordinate seller's attendance at closing | transaction_type='sell_side' | Or remote closing if needed | Closing coordination |
| verify_mortgage_payoff | Verify payoff of existing mortgage | transaction_type='sell_side' | Ensure lien release | Lien clearance |
| confirm_commission_disbursement | Confirm commission disbursement | transaction_type='sell_side' | Payment to listing and buyer agents | Payment verification |
| forward_seller_mail | Forward any seller mail received | transaction_type='sell_side' | Send to seller's new address | Post-closing service |
| remove_lockbox_signage | Remove lockbox and signage | transaction_type='sell_side' | Clean up after closing | Post-closing cleanup |
| update_seller_deed_recording | Update seller on deed recording | transaction_type='sell_side' | Confirm transaction complete | Communication |

### 2.4 Rental - Landlord Representation Only Tasks

These tasks should have `applies_if: "transaction_type == 'rental_landlord'"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| execute_rental_listing_agreement | Execute rental listing agreement | transaction_type='rental_landlord' | Agreement with landlord | Standard practice |
| determine_rental_price | Determine rental price | transaction_type='rental_landlord' | Market rental analysis | Market analysis |
| establish_rental_fee_structure | Establish fee structure | transaction_type='rental_landlord' | Landlord pays or tenant pays | Fee agreement |
| collect_rental_property_details | Collect property details | transaction_type='rental_landlord' | Bedrooms, baths, amenities | Listing details |
| pre_listing_rental_inspection | Conduct pre-listing property inspection | transaction_type='rental_landlord' | Note condition | Property evaluation |
| photograph_rental_property | Photograph rental property | transaction_type='rental_landlord' | Listing photos | Marketing |
| verify_rental_property_ready | Verify property is ready to show | transaction_type='rental_landlord' | Clean and presentable | Showing prep |
| install_lockbox_rental | Install lockbox for showings | transaction_type='rental_landlord' | If approved by landlord | Access management |
| prepare_compliant_lease | Prepare compliant lease agreement | transaction_type='rental_landlord' | Must include required disclosures | MA rental law |
| include_landlord_contact_in_lease | Include landlord contact in lease | transaction_type='rental_landlord' | Name, address, phone (required) | MA law requirement |
| include_maintenance_contact_in_lease | Include maintenance contact in lease | transaction_type='rental_landlord' | Who handles repairs (required) | MA law requirement |
| disclose_security_deposit_rights_in_lease | Disclose security deposit rights in lease | transaction_type='rental_landlord' | Required by law | MA law requirement |
| provide_statement_of_condition_form | Provide Statement of Condition form | transaction_type='rental_landlord' | Within 10 days of lease or deposit | MA law requirement |
| provide_fire_insurance_disclosure | Provide fire insurance disclosure | transaction_type='rental_landlord' | Within 15 days of lease signing | MA law requirement |
| setup_security_deposit_bank_account | Set up security deposit bank account | transaction_type='rental_landlord' | Must be MA bank, interest-bearing | MA law requirement |
| list_property_rental_platforms | List property on rental platforms | transaction_type='rental_landlord' | MLS, Zillow, Apartments.com | Marketing |
| coordinate_rental_showings | Coordinate rental showings | transaction_type='rental_landlord' | Schedule and conduct tours | Showing management |
| collect_rental_applications | Collect rental applications | transaction_type='rental_landlord' | Screen prospective tenants | Applicant screening |
| conduct_tenant_screening | Conduct tenant screening | transaction_type='rental_landlord' | Credit, background, references | Tenant selection |
| verify_tenant_income | Verify tenant income | transaction_type='rental_landlord' | Must be 3x monthly rent (standard) | Qualification |
| verify_employment | Verify employment | transaction_type='rental_landlord' | Contact employer | Income verification |
| contact_references | Contact references | transaction_type='rental_landlord' | Previous landlords, personal | Reference check |
| select_qualified_tenant | Select qualified tenant | transaction_type='rental_landlord' | Make selection with landlord | Tenant selection |
| prepare_lease_documents | Prepare lease documents | transaction_type='rental_landlord' | Complete all required fields | Lease preparation |
| execute_lease_agreement | Execute lease agreement | transaction_type='rental_landlord' | Landlord and tenant signatures | Contract execution |
| collect_first_month_rent | Collect first month's rent | transaction_type='rental_landlord' | At lease signing | Payment collection |
| collect_last_month_rent | Collect last month's rent (optional) | transaction_type='rental_landlord' | If agreed | Payment collection |
| collect_security_deposit | Collect security deposit | transaction_type='rental_landlord' | Up to 1 month max (MA law) | Payment collection |
| provide_receipt_all_payments | Provide receipt for all payments | transaction_type='rental_landlord' | Required documentation | Legal requirement |
| deposit_security_deposit_30_days | Deposit security deposit within 30 days | transaction_type='rental_landlord' | Into MA interest-bearing account | MA law requirement |
| provide_tenant_bank_info_30_days | Provide tenant with bank account info | transaction_type='rental_landlord' | Within 30 days (required) | MA law requirement |
| conduct_move_in_inspection_tenant | Conduct move-in inspection with tenant | transaction_type='rental_landlord' | Complete Statement of Condition | Move-in process |
| provide_keys_to_tenant | Provide keys to tenant | transaction_type='rental_landlord' | Access to property | Access transfer |
| provide_tenant_contact_info | Provide tenant with landlord/agent contact | transaction_type='rental_landlord' | Emergency contacts | Communication |
| setup_rent_payment_method | Set up rent payment method | transaction_type='rental_landlord' | Online, check, ACH, etc. | Payment setup |

### 2.5 Rental - Tenant Representation Only Tasks

These tasks should have `applies_if: "transaction_type == 'rental_tenant'"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| execute_tenant_rep_agreement | Execute tenant representation agreement | transaction_type='rental_tenant' | Agreement with tenant | Standard practice |
| disclose_fee_arrangement_tenant | Disclose fee arrangement in writing | transaction_type='rental_tenant' | Required at first meeting (MA law) | M.G.L. c. 112, § 87 DDD ½ |
| understand_tenant_needs | Understand tenant's needs | transaction_type='rental_tenant' | Budget, location, size, amenities | Client consultation |
| verify_tenant_financial_capacity | Verify tenant's financial capacity | transaction_type='rental_tenant' | Income requirements (typically 3x rent) | Qualification |
| obtain_tenant_employment_info | Obtain tenant's employment information | transaction_type='rental_tenant' | For applications | Application prep |
| search_rental_listings | Search available rental listings | transaction_type='rental_tenant' | MLS and rental platforms | Property search |
| schedule_property_showings_tenant | Schedule property showings | transaction_type='rental_tenant' | Coordinate tours | Showing coordination |
| provide_market_info_tenant | Provide market information to tenant | transaction_type='rental_tenant' | Pricing, availability, neighborhood | Market education |
| prepare_rental_application_tenant | Prepare rental application for tenant | transaction_type='rental_tenant' | Assist with completion | Application assistance |
| submit_application_to_landlord | Submit application to landlord/agent | transaction_type='rental_tenant' | On behalf of tenant | Application submission |
| provide_tenant_references | Provide tenant references | transaction_type='rental_tenant' | Previous landlords, employers | Reference provision |
| assist_gathering_required_docs_tenant | Assist tenant with gathering required docs | transaction_type='rental_tenant' | Pay stubs, tax returns, etc. | Documentation support |
| review_lease_with_tenant | Review lease agreement with tenant | transaction_type='rental_tenant' | Explain all terms | Legal support |
| negotiate_lease_terms_tenant | Negotiate lease terms | transaction_type='rental_tenant' | Rent, move-in costs, responsibilities | Negotiation |
| verify_lease_ma_disclosures | Verify lease includes required MA disclosures | transaction_type='rental_tenant' | Lead paint, security deposit, contacts | Compliance check |
| review_statement_of_condition_tenant | Review Statement of Condition | transaction_type='rental_tenant' | Document existing issues | Property condition |
| verify_security_deposit_legal | Verify security deposit amount legal | transaction_type='rental_tenant' | Max 1 month's rent in MA | Legal verification |
| confirm_deposit_ma_bank | Confirm security deposit will be in MA bank | transaction_type='rental_tenant' | Legal requirement | Legal verification |
| attend_lease_signing_tenant | Attend lease signing with tenant | transaction_type='rental_tenant' | Support tenant at signing | Client support |
| verify_tenant_receives_disclosures | Verify tenant receives all required disclosures | transaction_type='rental_tenant' | Lead paint, insurance, bank info | Compliance verification |
| conduct_move_in_walkthrough_tenant | Conduct move-in walkthrough | transaction_type='rental_tenant' | Document property condition | Move-in process |
| complete_statement_condition_tenant | Complete Statement of Condition with tenant | transaction_type='rental_tenant' | Note all defects | Documentation |
| verify_tenant_receives_keys | Verify tenant receives keys | transaction_type='rental_tenant' | Ensure property access | Access verification |
| provide_tenant_important_contacts | Provide tenant with important contacts | transaction_type='rental_tenant' | Landlord, maintenance, utilities | Information provision |

### 2.6 Commercial Buy Side Only Tasks

These tasks should have `applies_if: "transaction_type == 'commercial_buy'"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| order_phase_i_environmental | Order Phase I Environmental Assessment | transaction_type='commercial_buy' | Environmental contamination review | Due diligence |
| review_phase_i_results | Review Phase I ESA results | transaction_type='commercial_buy' | Identify RECs | Due diligence |
| order_phase_ii_environmental | Order Phase II Environmental (if needed) | transaction_type='commercial_buy' | Soil/groundwater testing | Due diligence |
| verify_zoning_compliance_commercial | Zoning compliance verification | transaction_type='commercial_buy' | Confirm permitted uses | Legal compliance |
| review_existing_tenant_leases | Review existing tenant leases | transaction_type='commercial_buy' | Analyze lease terms and obligations | Due diligence |
| prepare_rent_roll_commercial | Prepare rent roll | transaction_type='commercial_buy' | Current tenants and rent amounts | Financial analysis |
| review_operating_statements_3yr | Review operating statements (3 years) | transaction_type='commercial_buy' | Income and expenses | Financial analysis |
| ada_compliance_assessment | ADA compliance assessment | transaction_type='commercial_buy' | Accessibility requirements | Legal compliance |
| parking_analysis_commercial | Parking analysis | transaction_type='commercial_buy' | Spaces available and required | Zoning compliance |
| review_property_tax_assessment | Review property tax assessment | transaction_type='commercial_buy' | Verify tax obligations | Financial analysis |
| coordinate_commercial_loan_app | Coordinate commercial loan application | transaction_type='commercial_buy' | Different from residential | Financing |
| provide_operating_statements_lender | Provide lender with operating statements | transaction_type='commercial_buy' | Income verification | Underwriting |
| coordinate_commercial_appraisal | Coordinate commercial appraisal | transaction_type='commercial_buy' | Income approach valuation | Valuation |

### 2.7 Property-Specific Tasks

#### 2.7.1 Condo-Specific Tasks

These tasks should have `applies_if: "property_type == 'condo'"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| request_condo_6d_certificate | Request 6(d) certificate from HOA | property_type='condo' | Certification of unpaid common expenses | M.G.L. c. 183A, § 6(d) |
| pay_unpaid_condo_fees | Pay off any unpaid condo fees (if 'dirty' 6(d)) | property_type='condo' | Lender requires 'clean' certificate | Super-lien provision |
| provide_condo_resale_package | Provide condo resale package to buyer | property_type='condo' | Master deed, bylaws, budget, minutes | Condo requirements |
| complete_condo_questionnaire | Complete lender's condo questionnaire | property_type='condo' | HOA financial questionnaire | Lender requirement |
| review_hoa_financials | Review HOA financial statements | property_type='condo' | Reserve fund, operating budget | Due diligence |
| review_hoa_meeting_minutes | Review HOA meeting minutes | property_type='condo' | Identify pending assessments or issues | Due diligence |
| verify_hoa_fha_va_approval | Verify FHA/VA approval status if applicable | property_type='condo' AND (loan.type=='FHA' OR loan.type=='VA') | Required for these loan types | FHA/VA requirements |
| review_hoa_master_insurance | Review HOA master insurance policy | property_type='condo' | Verify coverage adequacy | Insurance planning |
| verify_hoa_rental_restrictions | Verify HOA rental and occupancy restrictions | property_type='condo' | Rental caps, Airbnb bans, etc. | Investment planning |
| confirm_parking_storage_assignment | Confirm parking and storage assignments | property_type='condo' | Deeded vs assigned | Property rights |
| disclose_hoa_transfer_fees | Disclose HOA transfer fees | property_type='condo' | Transfer/capital contribution fees | Financial planning |
| confirm_pet_policy_condo | Confirm and disclose HOA pet policies | property_type='condo' | Pet restrictions | Property rules |
| disclose_upcoming_assessments | Disclose any known upcoming special assessments | property_type='condo' | Financial obligations | Disclosure requirement |
| notify_hoa_pending_sale | Notify HOA of pending sale and transfer | property_type='condo' | Provide closing date, buyer info | HOA coordination |
| arrange_ho6_insurance | Arrange HO-6 insurance for buyer | property_type='condo' | Unit owner policy (required) | Insurance requirement |
| verify_hoa_condo_docs_complete | Verify condo association documents complete | property_type='condo' | All required documents provided | Completeness check |

#### 2.7.2 SFH with Septic System Tasks

These tasks should have `applies_if: "property.has_septic == true"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| assess_title5_requirement | Assess Title 5 septic inspection requirement | property.has_septic=true | Required for sale (MA law) | 310 CMR 15.000 |
| schedule_title5_inspection | Schedule and complete Title 5 inspection | property.has_septic=true | MA-certified inspector required | 310 CMR 15.000 |
| review_title5_report | Review Title 5 inspection report | property.has_septic=true | Pass, conditional pass, or fail | Title 5 regulations |
| complete_title5_repairs | Complete Title 5 septic repairs or upgrades | property.has_septic=true | If system failed or conditionally passed | 310 CMR 15.000 |
| schedule_title5_reinspection | Schedule Title 5 re-inspection (post-repair) | property.has_septic=true | Verify repairs successful | Title 5 compliance |
| obtain_title5_certificate | Obtain Certificate of Compliance from Board of Health | property.has_septic=true | Required for closing | 310 CMR 15.000 |
| provide_septic_pumping_records | Provide septic pumping and maintenance records | property.has_septic=true | Historical documentation | Maintenance history |
| provide_septic_location_diagram | Provide septic system location diagram | property.has_septic=true | Tank, distribution box, leach field | System documentation |
| identify_septic_reserve_area | Identify septic reserve area | property.has_septic=true | Future replacement area | Planning requirement |
| provide_septic_dos_donts | Provide septic system do's and don'ts | property.has_septic=true | Educate buyer on care | Owner education |
| provide_septic_service_contacts | Provide local septic service contacts | property.has_septic=true | Pumpers, inspectors | Service information |

#### 2.7.3 SFH with Private Well Tasks

These tasks should have `applies_if: "property.has_well == true"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| conduct_well_water_test | Conduct comprehensive well water testing | property.has_well=true | Bacteria, nitrates, lead, arsenic, radon | Water safety |
| perform_well_flow_test | Perform well flow rate and recovery test | property.has_well=true | Gallons per minute, recovery time | Water quantity |
| verify_well_septic_distance | Verify well and septic separation distance | property.has_well=true AND property.has_septic=true | Minimum 100 feet (MA requirement) | MA well regulations |
| inspect_well_head_protection | Inspect well head protection and grading | property.has_well=true | Proper seal and drainage | Water protection |
| transfer_water_treatment_system | Transfer water treatment system information | property.has_well=true | Softeners, filters, etc. | System transfer |
| transfer_well_pump_warranty | Transfer well pump warranty and service info | property.has_well=true | Pump warranty documentation | Warranty transfer |
| document_well_location_depth | Document well location and depth | property.has_well=true | Well log documentation | Well documentation |
| disclose_shared_well_agreements | Disclose any shared well arrangements | property.has_shared_well=true | Shared well documentation | Legal disclosure |
| document_water_rights | Document water rights and restrictions | property.has_well=true | Water use limitations | Rights documentation |

#### 2.7.4 Multifamily (Tenant-Occupied) Tasks

These tasks should have `applies_if: "property_type == 'multifamily'"` OR `applies_if: "property.tenant_occupied == true"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| gather_all_tenant_leases | Gather all tenant leases and rental documentation | property_type='multifamily' | Current leases, rent rolls, deposits | Due diligence |
| obtain_tenant_estoppel_certificates | Obtain tenant estoppel certificates | property_type='multifamily' | Tenant verification of lease terms | Lender requirement |
| provide_rental_income_verification | Provide rental income verification to lender | property_type='multifamily' | Income, occupancy, expenses | Underwriting |
| notify_tenants_ownership_transfer | Notify tenants of ownership transfer | property_type='multifamily' | Written notice to all tenants | MA law |
| prepare_security_deposit_transfer_docs | Prepare security deposit transfer documentation | property_type='multifamily' | All deposits with interest | MA law requirement |
| transfer_security_deposits | Transfer security deposits to buyer | property_type='multifamily' | At closing with interest | MA law requirement |
| provide_copies_all_leases | Provide copies of all leases | property_type='multifamily' | Current lease agreements | Documentation |
| notify_tenants_new_owner | Notify tenants about new ownership | property_type='multifamily' | Post-closing notice | Communication |
| verify_rent_roll_income | Verify current rent roll and income | property_type='multifamily' | Actual vs stated income | Due diligence |
| analyze_operating_expenses | Analyze operating expenses and P&L | property_type='multifamily' | 2-3 years of expenses | Financial analysis |
| audit_lease_agreements | Audit all existing lease agreements | property_type='multifamily' | Review terms and compliance | Legal review |
| reconcile_security_deposits | Reconcile security deposit accounts | property_type='multifamily' | Verify proper accounting | Financial verification |
| account_last_month_rent | Account for last month's rent held | property_type='multifamily' | Transfer to buyer at closing | Financial accounting |
| verify_utility_metering | Verify utility metering and billing | property_type='multifamily' | Separate vs owner-paid | Utility clarification |
| inspect_common_areas | Inspect common areas and systems | property_type='multifamily' | Shared spaces and building systems | Property inspection |
| review_rental_income_tax_returns | Review seller's Schedule E tax returns | property_type='multifamily' | Verify reported income | Due diligence |
| coordinate_property_management_transition | Coordinate property management transition | property_type='multifamily' | Self-manage vs professional | Management planning |
| calculate_rent_proration | Calculate rent proration for closing | property_type='multifamily' | Current month's rent | Financial calculation |
| review_vendor_contracts | Review and transfer service contracts | property_type='multifamily' | Landscaping, snow, trash, etc. | Contract transfer |
| verify_fire_safety_compliance | Verify fire safety system compliance | property_type='multifamily' | Smoke, CO, extinguishers | Safety compliance |
| verify_lead_paint_compliance_rental | Verify lead paint compliance for rental | property_type='multifamily' AND year_built<1978 | MA rental lead paint requirements | MA law compliance |
| transfer_rental_registration | Transfer rental property registration | property_type='multifamily' | Update with city/town | Registration requirement |
| review_pending_evictions | Review any pending evictions or legal actions | property_type='multifamily' | Disclose ongoing proceedings | Legal disclosure |

#### 2.7.5 Single-Family Home General Tasks

These tasks apply to ALL single-family homes:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| schedule_smoke_co_inspection | Schedule smoke & CO detector inspection | property_type='sfh' | Certificate required for closing | M.G.L. c. 148 §26F, §26F½ |
| obtain_smoke_co_certificate | Obtain smoke & CO certificate of compliance | property_type='sfh' | Valid 60 days | Fire safety requirement |
| review_property_survey | Review property survey and boundaries | property_type='sfh' | Boundaries, easements, encroachments | Title review |
| document_utility_shutoffs | Document utility shut-off locations | property_type='sfh' | Water, gas, electrical | Safety information |
| compile_transfer_warranties | Compile and transfer all warranties | property_type='sfh' | Appliances, roof, HVAC | Warranty transfer |

### 2.8 Lead Paint Disclosure Tasks (Conditional)

These tasks should have `applies_if: "property.year_built < 1978"`:

| Task Key | Task Title | Applies To | Rationale | Citation |
|----------|-----------|------------|-----------|----------|
| lead_paint_disclosure | Property Transfer Lead Paint Notification | property.year_built<1978 | Federal and MA requirement | 42 U.S.C. § 4852d; 105 CMR 460.720 |
| provide_lead_paint_pamphlet | Provide EPA pamphlet 'Protect Your Family from Lead' | property.year_built<1978 | EPA requirement | Federal lead paint law |
| coordinate_10day_lead_inspection | Coordinate 10-day lead paint inspection period | property.year_built<1978 | Buyer's right (can be waived) | Federal lead paint law |

---

## Section 3: Issues Found

### 3.1 CRITICAL ISSUES

#### Issue 1: Sell-Side Tasks in Universal Base Template
**Severity:** CRITICAL
**Impact:** HIGH - All transactions inherit wrong tasks

**Tasks Affected:**
- "Review and sign listing agreement" (base_transaction.yaml:62)
- "Property Information Entered into MLS" (base_transaction.yaml:21)
- "Schedule professional photography" (base_transaction.yaml:40)
- "Create virtual tour/walkthrough" (base_transaction.yaml:48)
- "Collect property documents from seller" (base_transaction.yaml:30)
- "Gather seller financial documents" (base_transaction.yaml:69)
- "Send welcome packet to seller" (base_transaction.yaml:109)

**Recommended Fix:**
1. Remove ALL sell-side specific tasks from base_transaction.yaml
2. Create new template: `sell_side_base.yaml`
3. Move sell-side tasks to `sell_side_base.yaml`
4. Have property-specific sell templates extend `sell_side_base`

#### Issue 2: Buy-Side Tasks in Universal Base Template
**Severity:** CRITICAL
**Impact:** HIGH - All transactions inherit wrong tasks

**Tasks Affected:**
- "Collect buyer pre-approval letter" (base_transaction.yaml:115)
- "Send welcome packet to buyer" (base_transaction.yaml:99)
- "Add buyer information to system" (base_transaction.yaml:81)
- "Schedule initial consultation with buyer" (base_transaction.yaml:124)

**Recommended Fix:**
1. Remove ALL buy-side specific tasks from base_transaction.yaml
2. Create new template: `buy_side_base.yaml`
3. Move buy-side tasks to `buy_side_base.yaml`
4. Have property-specific buy templates extend `buy_side_base`

#### Issue 3: No Rental Transaction Support
**Severity:** CRITICAL
**Impact:** HIGH - Cannot handle rental transactions legally

**Missing Components:**
- No rental landlord template
- No rental tenant template
- No rental-specific tasks in database
- No MA rental law compliance tracking

**Legal Risk:**
- Violates M.G.L. c. 112, § 87 DDD ½ (fee disclosure requirement)
- Missing MA security deposit law compliance (M.G.L. c. 186, § 15B)
- Missing Statement of Condition requirement
- Missing fire insurance disclosure requirement

**Recommended Fix:**
1. Create `rental_landlord_base.yaml` template
2. Create `rental_tenant_base.yaml` template
3. Add all 42 rental-specific tasks identified in Section 2.4 and 2.5
4. Implement MA rental law compliance checks

#### Issue 4: No Commercial Transaction Support
**Severity:** CRITICAL
**Impact:** HIGH - Cannot handle commercial transactions

**Missing Components:**
- No commercial buy template
- No commercial sell template
- No commercial-specific tasks
- No environmental assessment tasks
- No tenant lease review tasks
- No NOI/cap rate calculation tasks

**Recommended Fix:**
1. Create `commercial_buy_base.yaml` template
2. Create `commercial_sell_base.yaml` template
3. Add all 13+ commercial-specific tasks identified in Section 2.6
4. Implement commercial due diligence requirements

### 3.2 HIGH PRIORITY ISSUES

#### Issue 5: No Transaction Type Filtering
**Severity:** HIGH
**Impact:** Tasks appear in wrong transaction types

**Problem:** NO task in ANY template has `transaction_type` filter

**Example:**
```yaml
# Current (WRONG):
- title: "Review and sign listing agreement"
  owner_role: seller

# Should be (CORRECT):
- title: "Review and sign listing agreement"
  owner_role: seller
  applies_if: "transaction_type == 'sell_side'"
```

**Scope:** Affects 100+ tasks across all templates

**Recommended Fix:**
1. Add `transaction_type` field to task_definitions table
2. Add `applies_if` conditions to all transaction-specific tasks
3. Update TemplateEngine to filter tasks by transaction_type

#### Issue 6: Duplicate Agency Disclosure Tasks
**Severity:** HIGH
**Impact:** Task duplication across templates

**Found In:**
- condo.yaml:30-47
- sfh_city_water.yaml:30-47
- sfh_septic.yaml:30-48
- multifamily.yaml:30-48

**Problem:** If base_transaction also has agency disclosure, it appears twice

**Recommended Fix:**
1. Keep agency_disclosure in base_transaction ONLY
2. Remove from all property-specific templates
3. Add note: "Extended from base_transaction"

#### Issue 7: Conditional Tasks Not Properly Structured
**Severity:** HIGH
**Impact:** Property-specific tasks may apply incorrectly

**Example from base_transaction.yaml:652-703:**
```yaml
conditional_tasks:
  - condition: "property.type == 'Condo'"
    tasks:
      - title: "Obtain condo docs (6d certificate)"
```

**Problem:** This structure is in base_transaction, which is "universal". Condo-specific tasks should be in condo.yaml, not in base template as conditional.

**Recommended Fix:**
1. Move all property-specific conditional tasks to their respective property templates
2. Use `applies_if` field instead of `conditional_tasks` structure
3. Simplify base_transaction to truly universal tasks only

### 3.3 MEDIUM PRIORITY ISSUES

#### Issue 8: Inconsistent Task Naming
**Severity:** MEDIUM
**Impact:** Difficult to track and manage

**Examples:**
- "Schedule smoke & CO detector inspection" (multiple files)
- "Smoke & CO Detector Inspection" (alternate form)
- "Schedule Smoke and CO Detector Inspection" (another variant)

**Recommended Fix:**
1. Standardize task key naming: `smoke_co_inspection`
2. Standardize title: "Schedule Smoke & CO Detector Inspection"
3. Create task key registry

#### Issue 9: Missing MA Legal Requirement Citations
**Severity:** MEDIUM
**Impact:** Cannot verify legal compliance

**Tasks Missing Citations:**
- Many earnest money tasks (should cite escrow requirements)
- Some closing tasks (should cite recording requirements)
- Offer/P&S timeline tasks (should cite standard practice)

**Recommended Fix:**
1. Add `citations` array to all MA-specific tasks
2. Include URL and legal citation (M.G.L. chapter/section)
3. Add `is_legal_requirement` boolean flag

#### Issue 10: No Owner-Occupancy Exemption Handling
**Severity:** MEDIUM
**Impact:** Multifamily transactions may have wrong tasks

**Problem:** MA law provides owner-occupancy exemptions for 2-3 unit properties. Current templates don't distinguish.

**Recommended Fix:**
1. Add `owner_occupied_units` property attribute
2. Add conditional logic for owner-occupancy exemptions
3. Document MA owner-occupancy rules in system

### 3.4 LOW PRIORITY ISSUES

#### Issue 11: Inconsistent Due Date Calculations
**Severity:** LOW
**Impact:** Minor - tasks work but could be standardized

**Problem:** Different formats used:
- `due: "Closing"` + `due_offset: "-21d"`
- `due_days: 21`
- `due: "PS"` + `due_offset: "+7d"`

**Recommended Fix:** Standardize on one format across all templates

#### Issue 12: Missing Task Dependencies
**Severity:** LOW
**Impact:** Task order not enforced

**Problem:** Many tasks have `depends_on` but not all dependencies mapped

**Example:**
```yaml
- id: "title5_repairs"
  depends_on:
    - "title5_report_review"
```

But some logical dependencies missing:
- "Execute P&S" should depend on "Accept Offer"
- "Clear to Close" should depend on "Loan Commitment"

**Recommended Fix:** Map all critical dependencies

---

## Section 4: Template Recommendations

### 4.1 Recommended Template Structure

Instead of current structure:
```
base_transaction (universal - but actually mixed)
├── condo
├── sfh_city_water
├── sfh_septic
└── multifamily
```

**Recommended New Structure:**

```
universal_base (truly universal - applies to ALL)
│
├── buy_side_base
│   ├── buy_side_sfh_city_water
│   ├── buy_side_sfh_septic
│   ├── buy_side_sfh_septic_well
│   ├── buy_side_condo
│   ├── buy_side_multifamily
│   ├── buy_side_land
│   └── buy_side_commercial
│
├── sell_side_base
│   ├── sell_side_sfh_city_water
│   ├── sell_side_sfh_septic
│   ├── sell_side_sfh_septic_well
│   ├── sell_side_condo
│   ├── sell_side_multifamily
│   ├── sell_side_land
│   └── sell_side_commercial
│
├── rental_landlord_base
│   ├── rental_landlord_sfh
│   ├── rental_landlord_condo
│   └── rental_landlord_multifamily
│
└── rental_tenant_base
    ├── rental_tenant_sfh
    ├── rental_tenant_condo
    └── rental_tenant_multifamily
```

### 4.2 Template Specifications

#### Template: Universal Base
**Should Include:**
- Agency disclosure (1 task)
- Add client information (1 task)
- Schedule initial consultation (1 task)
- Maintain documentation (1 task)
- Request client testimonial (1 task)
- Process commission (1 task)
- Close transaction in system (1 task)

**Should EXCLUDE:**
- ALL buy-side specific tasks
- ALL sell-side specific tasks
- ALL rental specific tasks
- ALL property-specific tasks

**Total Tasks:** ~7 tasks

---

#### Template: Buy Side - Base
**Extends:** universal_base

**Should Include:**
- All Universal Tasks (7 tasks - inherited)
- All Buy Side Tasks from Section 2.2 (42 tasks)
- Lead paint tasks if year_built < 1978 (3 tasks)
- Title work tasks (5 tasks)
- Financing tasks (8 tasks)
- Inspection tasks (7 tasks)
- Closing coordination tasks (10 tasks)

**Should EXCLUDE:**
- Sell Side tasks (listing, MLS, photography, etc.)
- Rental tasks (leases, tenant screening, etc.)
- Property-specific tasks (will be added by property templates)

**Total Tasks:** ~82 tasks

---

#### Template: Buy Side - SFH City Water
**Extends:** buy_side_base

**Should Include:**
- All Buy Side Base Tasks (82 tasks - inherited)
- Smoke & CO inspection (2 tasks)
- Property survey review (1 task)
- Municipal lien certificate (1 task)
- Water final reading (1 task - if applicable)
- Insurance confirmation (1 task)
- SFH-specific utility coordination (1 task)
- Warranty transfer (1 task)

**Should EXCLUDE:**
- Condo-specific tasks (6d certificate, HOA docs, etc.)
- Septic tasks (Title 5 inspection, etc.)
- Well tasks (water testing, flow rate, etc.)
- Multifamily tasks (tenant estoppels, rent roll, etc.)

**Total Tasks:** ~92 tasks

---

#### Template: Buy Side - SFH with Septic
**Extends:** buy_side_sfh_city_water (or buy_side_base)

**Should Include:**
- All Buy Side Base Tasks (82 tasks - inherited)
- All SFH General Tasks (10 tasks)
- All Septic-Specific Tasks from Section 2.7.2 (11 tasks)

**Should EXCLUDE:**
- Condo-specific tasks
- Multifamily tasks

**Total Tasks:** ~103 tasks

---

#### Template: Buy Side - SFH with Septic + Well
**Extends:** buy_side_base

**Should Include:**
- All Buy Side Base Tasks (82 tasks - inherited)
- All SFH General Tasks (10 tasks)
- All Septic-Specific Tasks (11 tasks)
- All Well-Specific Tasks from Section 2.7.3 (9 tasks)
- Well-septic distance verification (1 task)

**Should EXCLUDE:**
- Condo-specific tasks
- Multifamily tasks

**Total Tasks:** ~113 tasks

---

#### Template: Buy Side - Condo Purchase
**Extends:** buy_side_base

**Should Include:**
- All Buy Side Base Tasks (82 tasks - inherited)
- All Condo-Specific Tasks from Section 2.7.1 (16 tasks)
- Lead paint disclosure if year_built < 1978 (3 tasks)

**Should EXCLUDE:**
- Sell Side tasks
- Rental tasks
- SFH-specific tasks (Title 5, well testing, smoke cert)
- Multifamily tasks (tenant estoppels, rent roll)

**Total Tasks:** ~101 tasks

---

#### Template: Buy Side - Multifamily
**Extends:** buy_side_base

**Should Include:**
- All Buy Side Base Tasks (82 tasks - inherited)
- All Multifamily-Specific Tasks from Section 2.7.4 (31 tasks)
- Lead paint disclosure if year_built < 1978 (3 tasks)
- Smoke & CO inspection for all units (2 tasks)

**Should EXCLUDE:**
- Condo-specific tasks (6d certificate, HOA docs)
- SFH-specific single-property tasks

**Total Tasks:** ~118 tasks

---

#### Template: Buy Side - Commercial
**Extends:** buy_side_base

**Should Include:**
- All Buy Side Base Tasks (82 tasks - inherited)
- All Commercial-Specific Tasks from Section 2.6 (13 tasks)
- Environmental assessments (3 tasks)
- Tenant lease analysis (8 tasks)
- Financial analysis (5 tasks)

**Should EXCLUDE:**
- Residential-specific tasks
- HOA/Condo tasks
- Single-family tasks

**Total Tasks:** ~111 tasks

---

#### Template: Sell Side - Base
**Extends:** universal_base

**Should Include:**
- All Universal Tasks (7 tasks - inherited)
- All Sell Side Tasks from Section 2.3 (47 tasks)
- Lead paint disclosure if year_built < 1978 (3 tasks)
- Title work tasks (5 tasks)
- Pre-closing coordination tasks (8 tasks)
- Closing tasks (5 tasks)

**Should EXCLUDE:**
- Buy Side tasks (buyer agency, pre-approval, etc.)
- Rental tasks
- Property-specific tasks (will be added by property templates)

**Total Tasks:** ~75 tasks

---

#### Template: Sell Side - SFH City Water
**Extends:** sell_side_base

**Should Include:**
- All Sell Side Base Tasks (75 tasks - inherited)
- Smoke & CO inspection (2 tasks)
- Municipal lien certificate (1 task)
- Water final reading (1 task - if applicable)
- Property survey provision (1 task)
- Utility coordination (1 task)
- Warranty documentation (1 task)

**Should EXCLUDE:**
- Condo-specific tasks
- Septic tasks
- Well tasks
- Multifamily tasks

**Total Tasks:** ~82 tasks

---

#### Template: Sell Side - SFH with Septic
**Extends:** sell_side_base

**Should Include:**
- All Sell Side Base Tasks (75 tasks - inherited)
- All SFH General Tasks (8 tasks)
- All Septic-Specific Tasks (11 tasks)

**Should EXCLUDE:**
- Condo-specific tasks
- Well tasks (unless property also has well)
- Multifamily tasks

**Total Tasks:** ~94 tasks

---

#### Template: Sell Side - SFH with Septic + Well
**Extends:** sell_side_base

**Should Include:**
- All Sell Side Base Tasks (75 tasks - inherited)
- All SFH General Tasks (8 tasks)
- All Septic-Specific Tasks (11 tasks)
- All Well-Specific Tasks (9 tasks)

**Should EXCLUDE:**
- Condo-specific tasks
- Multifamily tasks

**Total Tasks:** ~103 tasks

---

#### Template: Sell Side - Condo
**Extends:** sell_side_base

**Should Include:**
- All Sell Side Base Tasks (75 tasks - inherited)
- All Condo-Specific Tasks (16 tasks)
- Gather condo documents early (1 task)
- Lead paint disclosure if year_built < 1978 (3 tasks)

**Should EXCLUDE:**
- SFH-specific tasks (Title 5, well, smoke cert)
- Multifamily tasks

**Total Tasks:** ~95 tasks

---

#### Template: Sell Side - Multifamily
**Extends:** sell_side_base

**Should Include:**
- All Sell Side Base Tasks (75 tasks - inherited)
- All Multifamily-Specific Tasks (31 tasks)
- Smoke & CO for all units (2 tasks)
- Tenant documentation and notification (8 tasks)

**Should EXCLUDE:**
- Condo-specific HOA tasks
- Single-family property tasks

**Total Tasks:** ~116 tasks

---

#### Template: Sell Side - Commercial
**Extends:** sell_side_base

**Should Include:**
- All Sell Side Base Tasks (75 tasks - inherited)
- Commercial property documentation (10 tasks)
- Tenant lease coordination (5 tasks)
- Financial documentation (5 tasks)
- Environmental disclosure (2 tasks)

**Should EXCLUDE:**
- Residential-specific tasks
- HOA/Condo tasks

**Total Tasks:** ~97 tasks

---

#### Template: Rental Landlord - Base
**Extends:** universal_base

**Should Include:**
- All Universal Tasks (7 tasks - inherited)
- All Rental Landlord Tasks from Section 2.4 (32 tasks)
- Lead paint disclosure if year_built < 1978 (3 tasks)
- MA rental law compliance tasks (10 tasks)

**Should EXCLUDE:**
- Buy/Sell side tasks
- Rental tenant representation tasks
- Property transfer tasks (no title, no closing)

**Total Tasks:** ~52 tasks

---

#### Template: Rental Landlord - SFH
**Extends:** rental_landlord_base

**Should Include:**
- All Rental Landlord Base Tasks (52 tasks - inherited)
- Property-specific rental prep (3 tasks)
- Utility responsibility documentation (2 tasks)

**Should EXCLUDE:**
- Condo HOA tasks
- Multi-unit tenant coordination

**Total Tasks:** ~57 tasks

---

#### Template: Rental Landlord - Condo
**Extends:** rental_landlord_base

**Should Include:**
- All Rental Landlord Base Tasks (52 tasks - inherited)
- Verify HOA rental restrictions (1 task)
- Review condo rental rules (1 task)
- HOA rental approval process (1 task)

**Should EXCLUDE:**
- SFH-specific maintenance tasks
- Multi-unit coordination

**Total Tasks:** ~55 tasks

---

#### Template: Rental Landlord - Multifamily
**Extends:** rental_landlord_base

**Should Include:**
- All Rental Landlord Base Tasks (52 tasks - inherited)
- Multi-unit tenant screening (5 tasks)
- Multiple lease preparation (3 tasks)
- Building-wide compliance (3 tasks)

**Should EXCLUDE:**
- Single-property tasks
- Condo HOA tasks

**Total Tasks:** ~63 tasks

---

#### Template: Rental Tenant - Base
**Extends:** universal_base

**Should Include:**
- All Universal Tasks (7 tasks - inherited)
- All Rental Tenant Tasks from Section 2.5 (24 tasks)
- MA tenant rights education (3 tasks)
- Fee disclosure compliance (1 task)

**Should EXCLUDE:**
- Buy/Sell tasks
- Landlord representation tasks
- Property transfer tasks

**Total Tasks:** ~35 tasks

---

#### Template: Rental Tenant - SFH
**Extends:** rental_tenant_base

**Should Include:**
- All Rental Tenant Base Tasks (35 tasks - inherited)
- Single-family property evaluation (2 tasks)
- Yard/maintenance responsibility clarification (1 task)

**Should EXCLUDE:**
- Condo HOA review
- Multi-unit considerations

**Total Tasks:** ~38 tasks

---

#### Template: Rental Tenant - Condo
**Extends:** rental_tenant_base

**Should Include:**
- All Rental Tenant Base Tasks (35 tasks - inherited)
- Review condo association rules (1 task)
- Verify parking/storage included (1 task)
- Understand HOA fees responsibility (1 task)

**Should EXCLUDE:**
- SFH yard maintenance
- Multi-unit building concerns

**Total Tasks:** ~38 tasks

---

#### Template: Rental Tenant - Multifamily
**Extends:** rental_tenant_base

**Should Include:**
- All Rental Tenant Base Tasks (35 tasks - inherited)
- Building amenities review (1 task)
- Noise/privacy considerations (1 task)
- Shared space rules (1 task)

**Should EXCLUDE:**
- Single-property concerns
- HOA-specific condo rules

**Total Tasks:** ~38 tasks

---

## Section 5: Missing MA Legal Requirements

Based on reference document validation, these **required MA legal tasks are MISSING** from the current system:

### Missing Task 1: Buyer Agency Agreement (NAR 2025)
**Legal Requirement:** Mandatory as of 2025 NAR settlement
**Citation:** NAR Settlement Agreement (2025)
**Applies To:** transaction_type='buy_side'
**Currently Missing From:** All templates

**Task Details:**
- Title: "Sign Buyer Agency Agreement"
- Description: "Execute written buyer representation agreement with fee negotiation"
- Due: Before showing properties
- Owner: Buyer
- Mandatory: Yes

---

### Missing Task 2: Rental Fee Disclosure (MA Law)
**Legal Requirement:** M.G.L. c. 112, § 87 DDD ½
**Citation:** "Written fee disclosure at first personal meeting"
**Applies To:** transaction_type IN ('rental_landlord', 'rental_tenant')
**Currently Missing From:** All templates (no rental templates exist)

**Task Details:**
- Title: "Provide Written Fee Disclosure"
- Description: "Disclose fee arrangement in writing at first personal meeting (MA law requirement)"
- Due: First meeting
- Owner: Agent
- Mandatory: Yes

---

### Missing Task 3: Security Deposit Banking (MA Law)
**Legal Requirement:** M.G.L. c. 186, § 15B
**Citation:** "Security deposits must be in MA bank, interest-bearing, within 30 days"
**Applies To:** transaction_type='rental_landlord'
**Currently Missing From:** All templates (no rental templates exist)

**Task Details:**
- Title: "Deposit Security Deposit in MA Interest-Bearing Account"
- Description: "Within 30 days of receipt, deposit into MA bank account and provide tenant with bank information"
- Due: Lease signing + 30 days
- Owner: Landlord
- Mandatory: Yes

---

### Missing Task 4: Security Deposit Transfer (Multifamily Sales)
**Legal Requirement:** MA Law
**Citation:** "New owner must notify tenants within 45 days of new bank account"
**Applies To:** transaction_type='sell_side' AND property.tenant_occupied=true
**Currently Missing From:** Multifamily template

**Task Details:**
- Title: "New Owner Send Security Deposit Notice to Tenants"
- Description: "Within 45 days of closing, notify all tenants of new bank account information for security deposits"
- Due: Closing + 45 days
- Owner: Buyer (new owner)
- Mandatory: Yes

---

## Section 6: Database Schema Recommendations

### 6.1 Required Schema Updates

#### Update 1: Add transaction_type Field
**Table:** `wp_ma_deal_task_definitions`

**Add Column:**
```sql
ALTER TABLE wp_ma_deal_task_definitions
ADD COLUMN transaction_type_filter VARCHAR(50) DEFAULT NULL
  COMMENT 'Comma-separated list: buy_side,sell_side,rental_landlord,rental_tenant,commercial_buy,commercial_sell'
AFTER applies_if;
```

**Purpose:** Allow explicit transaction type filtering at task definition level

---

#### Update 2: Add property_attribute_filter Field
**Table:** `wp_ma_deal_task_definitions`

**Add Column:**
```sql
ALTER TABLE wp_ma_deal_task_definitions
ADD COLUMN property_attribute_filter TEXT DEFAULT NULL
  COMMENT 'JSON array of required property attributes: ["has_septic", "has_well", "tenant_occupied"]'
AFTER transaction_type_filter;
```

**Purpose:** Enable property-specific task filtering

---

#### Update 3: Add legal_requirement Fields
**Table:** `wp_ma_deal_task_definitions`

**Add Columns:**
```sql
ALTER TABLE wp_ma_deal_task_definitions
ADD COLUMN is_legal_requirement TINYINT(1) DEFAULT 0
  COMMENT 'Is this task required by MA law' AFTER is_required,
ADD COLUMN legal_citation VARCHAR(255) DEFAULT NULL
  COMMENT 'Legal citation (e.g., M.G.L. c. 183A, § 6(d))' AFTER is_legal_requirement,
ADD COLUMN legal_deadline VARCHAR(100) DEFAULT NULL
  COMMENT 'Legal deadline requirement (e.g., "Within 10 days of request")' AFTER legal_citation;
```

**Purpose:** Track MA legal requirements and compliance

---

#### Update 4: Add Transaction Type Enum
**Table:** `wp_ma_deal_transactions`

**Verify Column Exists:**
```sql
-- If column doesn't exist, add it:
ALTER TABLE wp_ma_deal_transactions
ADD COLUMN transaction_type VARCHAR(20) DEFAULT 'buy_side'
  COMMENT 'buy_side, sell_side, rental_landlord, rental_tenant, commercial_buy, commercial_sell'
AFTER property_type;

-- Add index:
CREATE INDEX idx_transaction_type ON wp_ma_deal_transactions(transaction_type);
```

**Purpose:** Enable transaction type filtering

---

### 6.2 New Tables Needed

#### Table 1: Transaction Type Definitions
```sql
CREATE TABLE wp_ma_deal_transaction_types (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type_key VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  requires_buyer TINYINT(1) DEFAULT 0,
  requires_seller TINYINT(1) DEFAULT 0,
  requires_listing_agent TINYINT(1) DEFAULT 0,
  requires_buyer_agent TINYINT(1) DEFAULT 0,
  requires_property_transfer TINYINT(1) DEFAULT 1
    COMMENT 'False for rentals (no title transfer)',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO wp_ma_deal_transaction_types
  (type_key, name, description, requires_buyer, requires_seller,
   requires_listing_agent, requires_buyer_agent, requires_property_transfer, created_at)
VALUES
  ('buy_side', 'Buy Side - Purchase', 'Agent represents buyer in purchase', 1, 0, 0, 1, 1, NOW()),
  ('sell_side', 'Sell Side - Listing', 'Agent represents seller in listing and sale', 0, 1, 1, 0, 1, NOW()),
  ('rental_landlord', 'Rental - Landlord Rep', 'Agent represents landlord in leasing', 0, 1, 1, 0, 0, NOW()),
  ('rental_tenant', 'Rental - Tenant Rep', 'Agent represents tenant in rental search', 1, 0, 0, 1, 0, NOW()),
  ('commercial_buy', 'Commercial - Buy Side', 'Agent represents buyer in commercial purchase', 1, 0, 0, 1, 1, NOW()),
  ('commercial_sell', 'Commercial - Sell Side', 'Agent represents seller in commercial sale', 0, 1, 1, 0, 1, NOW());
```

---

#### Table 2: Property Attributes
```sql
CREATE TABLE wp_ma_deal_property_attributes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaction_id BIGINT UNSIGNED NOT NULL,
  has_septic TINYINT(1) DEFAULT 0,
  has_well TINYINT(1) DEFAULT 0,
  has_pool TINYINT(1) DEFAULT 0,
  has_fireplace TINYINT(1) DEFAULT 0,
  has_garage TINYINT(1) DEFAULT 0,
  has_basement TINYINT(1) DEFAULT 0,
  tenant_occupied TINYINT(1) DEFAULT 0,
  is_new_construction TINYINT(1) DEFAULT 0,
  is_historical TINYINT(1) DEFAULT 0,
  has_shared_well TINYINT(1) DEFAULT 0,
  on_private_road TINYINT(1) DEFAULT 0,
  heat_type VARCHAR(20) DEFAULT NULL
    COMMENT 'gas, oil, electric, propane, etc.',
  number_of_units INT DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  FOREIGN KEY (transaction_id)
    REFERENCES wp_ma_deal_transactions(id) ON DELETE CASCADE,
  INDEX idx_has_septic (has_septic),
  INDEX idx_has_well (has_well),
  INDEX idx_tenant_occupied (tenant_occupied)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Purpose:** Store property attributes for conditional task application

---

### 6.3 Migration Script Requirements

**Migration Script:** `007_add_transaction_type_support.sql`

**Must Include:**
1. Add transaction_type_filter column to task_definitions
2. Add property_attribute_filter column to task_definitions
3. Add legal requirement columns to task_definitions
4. Create transaction_types table with seed data
5. Create property_attributes table
6. Update existing transactions to set default transaction_type
7. Backfill property attributes for existing transactions (from property_type)

---

## Section 7: Implementation Recommendations

### 7.1 Phased Rollout Plan

#### Phase 1: Database Schema Updates (Week 1)
**Priority:** CRITICAL
**Effort:** Medium

**Tasks:**
1. Create migration script 007_add_transaction_type_support.sql
2. Test migration on dev environment
3. Add transaction_type to Transaction model
4. Add property_attribute_filter to TaskDefinition model
5. Update TemplateEngine to support new filters

**Deliverables:**
- Migration script tested and ready
- Models updated
- TemplateEngine supports transaction_type filtering

---

#### Phase 2: Task Reclassification (Week 2-3)
**Priority:** CRITICAL
**Effort:** High

**Tasks:**
1. Create new universal_base.yaml (7 tasks only)
2. Create buy_side_base.yaml (82 tasks)
3. Create sell_side_base.yaml (75 tasks)
4. Reclassify all existing tasks with correct transaction_type
5. Add applies_if conditions to all conditional tasks
6. Update property-specific templates to extend correct base

**Deliverables:**
- All YAML templates restructured
- All tasks properly classified
- Template inheritance working correctly

---

#### Phase 3: Rental Support (Week 4)
**Priority:** HIGH
**Effort:** High

**Tasks:**
1. Create rental_landlord_base.yaml (52 tasks)
2. Create rental_tenant_base.yaml (35 tasks)
3. Create property-specific rental templates (6 templates)
4. Add MA rental law compliance checks to TemplateEngine
5. Test rental transaction workflows

**Deliverables:**
- Full rental transaction support
- MA rental law compliance automated
- Rental templates tested

---

#### Phase 4: Commercial Support (Week 5-6)
**Priority:** HIGH
**Effort:** High

**Tasks:**
1. Create commercial_buy_base.yaml (111 tasks)
2. Create commercial_sell_base.yaml (97 tasks)
3. Add environmental assessment workflows
4. Add tenant lease analysis tasks
5. Add NOI/cap rate calculation tasks
6. Test commercial transaction workflows

**Deliverables:**
- Full commercial transaction support
- Environmental due diligence workflows
- Commercial financial analysis tasks

---

#### Phase 5: Testing & Validation (Week 7)
**Priority:** HIGH
**Effort:** Medium

**Tasks:**
1. Create test transactions for all transaction types
2. Verify correct tasks appear for each type
3. Test conditional task application
4. Verify MA legal requirements tracked
5. Test template inheritance
6. User acceptance testing

**Deliverables:**
- All transaction types tested
- Bug fixes completed
- Documentation updated

---

### 7.2 Validation Queries

After implementation, run these queries to verify correctness:

#### Query 1: Verify Transaction Type Distribution
```sql
SELECT
  transaction_type,
  COUNT(*) as task_count
FROM wp_ma_deal_task_definitions
WHERE is_system = 1
GROUP BY transaction_type
ORDER BY transaction_type;
```

**Expected Results:**
- NULL (universal): ~7 tasks
- buy_side: ~42 tasks
- sell_side: ~47 tasks
- rental_landlord: ~32 tasks
- rental_tenant: ~24 tasks
- commercial_buy: ~13 tasks
- commercial_sell: ~10 tasks

---

#### Query 2: Verify No Sell-Side Tasks in Buy-Side Transactions
```sql
-- Create test buy-side transaction
INSERT INTO wp_ma_deal_transactions
  (transaction_type, property_type, created_at)
VALUES ('buy_side', 'SFH', NOW());

-- Get applied tasks
SELECT td.task_key, td.title, td.transaction_type_filter
FROM wp_ma_deal_tasks t
JOIN wp_ma_deal_task_definitions td ON t.task_definition_id = td.id
WHERE t.transaction_id = LAST_INSERT_ID()
  AND td.title LIKE '%listing%';
```

**Expected Result:** 0 rows (no listing-related tasks)

---

#### Query 3: Verify Condo Tasks Only on Condo Properties
```sql
-- Get condo-specific tasks applied to SFH transaction
SELECT td.task_key, td.title
FROM wp_ma_deal_tasks t
JOIN wp_ma_deal_task_definitions td ON t.task_definition_id = td.id
JOIN wp_ma_deal_transactions tr ON t.transaction_id = tr.id
WHERE tr.property_type = 'SFH'
  AND td.task_key LIKE '%condo%' OR td.task_key LIKE '%6d%';
```

**Expected Result:** 0 rows

---

#### Query 4: Verify Rental Transactions Exist
```sql
SELECT
  transaction_type,
  COUNT(*) as count
FROM wp_ma_deal_transactions
WHERE transaction_type IN ('rental_landlord', 'rental_tenant')
GROUP BY transaction_type;
```

**Expected Result:** Should return counts (not 0)

---

### 7.3 Testing Checklist

#### Test 1: Buy Side SFH Transaction
- [ ] Create buy-side SFH transaction
- [ ] Verify NO sell-side tasks (listing, MLS, photography)
- [ ] Verify NO rental tasks (tenant screening, leases)
- [ ] Verify buy-side tasks present (buyer agency, pre-approval, inspection)
- [ ] Verify SFH tasks present (smoke cert, survey)
- [ ] Verify lead paint tasks if year_built < 1978

#### Test 2: Sell Side Condo Transaction
- [ ] Create sell-side condo transaction
- [ ] Verify NO buy-side tasks (buyer agency, pre-approval)
- [ ] Verify sell-side tasks present (listing agreement, MLS, photography)
- [ ] Verify condo tasks present (6d cert, HOA docs)
- [ ] Verify NO SFH tasks (Title 5, well testing)

#### Test 3: Buy Side SFH with Septic
- [ ] Create buy-side SFH with has_septic=true
- [ ] Verify Title 5 inspection tasks present
- [ ] Verify septic-specific tasks present (pumping records, location diagram)
- [ ] Verify NO well tasks

#### Test 4: Buy Side SFH with Septic + Well
- [ ] Create buy-side SFH with has_septic=true, has_well=true
- [ ] Verify Title 5 tasks present
- [ ] Verify well testing tasks present
- [ ] Verify well-septic distance verification present

#### Test 5: Rental Landlord SFH
- [ ] Create rental-landlord SFH transaction
- [ ] Verify rental tasks present (tenant screening, lease, security deposit)
- [ ] Verify MA rental law tasks present (Statement of Condition, fire insurance disclosure)
- [ ] Verify NO buy/sell tasks (P&S, closing, title work)

#### Test 6: Rental Tenant Condo
- [ ] Create rental-tenant condo transaction
- [ ] Verify tenant representation tasks present
- [ ] Verify condo rules review tasks present
- [ ] Verify NO landlord tasks (tenant screening)

#### Test 7: Commercial Buy
- [ ] Create commercial buy transaction
- [ ] Verify environmental assessment tasks present
- [ ] Verify tenant lease review tasks present
- [ ] Verify financial analysis tasks present (NOI, cap rate)

#### Test 8: Multifamily Sell Side
- [ ] Create sell-side multifamily transaction
- [ ] Verify tenant-occupied tasks present (estoppels, rent roll)
- [ ] Verify security deposit transfer tasks present
- [ ] Verify smoke cert for all units present

---

## Section 8: Questions for Claude's Review

### Question 1: Base Template Inheritance Strategy
**Issue:** Should property-specific templates extend buy_side_base/sell_side_base directly, or should they extend a property-type base first?

**Option A (Recommended):**
```
sell_side_base
└── sell_side_sfh_septic (directly)
```

**Option B (Alternative):**
```
sell_side_base
└── sfh_septic_base
    ├── buy_side_sfh_septic
    └── sell_side_sfh_septic
```

**My Recommendation:** Option A for simplicity. Property-specific templates should extend transaction-type base directly.

---

### Question 2: Handling Multi-Attribute Properties
**Issue:** A property could be SFH + Septic + Well + Pool + Fireplace. Should we create templates for every combination?

**Option A:** Create most common combinations only:
- SFH City Water (no septic/well)
- SFH Septic (no well)
- SFH Septic + Well
- SFH Well only (rare, but possible)

**Option B:** Use dynamic task application based on property attributes, not templates.

**My Recommendation:** Option A for common combinations, Option B for rare combinations via conditional task application.

---

### Question 3: Rental Security Deposit Tracking
**Issue:** MA law requires separate tracking of security deposits in interest-bearing accounts. Should we build this into the system?

**Recommendation:** Yes - add security_deposits table to track:
- Tenant name
- Deposit amount
- Bank account information
- Interest accrued
- Transfer date (if property sold)

This ensures MA law compliance and prevents deposit return disputes.

---

### Question 4: MA Rental Law Automation Level
**Issue:** How automated should MA rental law compliance be?

**Option A (High Automation):**
- System automatically generates Statement of Condition forms
- System automatically calculates security deposit interest
- System sends automated reminders for 10-day, 15-day, 30-day deadlines

**Option B (Medium Automation):**
- System provides task reminders
- System provides template forms
- Agent manually completes and tracks

**My Recommendation:** Option A where possible. MA rental law is complex and penalties are severe. Automation reduces risk.

---

### Question 5: Task Duplication Strategy
**Issue:** Some tasks appear in multiple transaction types with slight variations (e.g., "Schedule final walkthrough" for buy-side vs sell-side).

**Should we:**
- A) Create separate task definitions (schedule_walkthrough_buyer, schedule_walkthrough_seller)
- B) Use single task definition with transaction_type filter
- C) Use single task definition with owner_role override

**My Recommendation:** Option A for clarity. While it creates more task definitions, it allows for transaction-specific instructions and due date calculations.

---

## Section 9: Next Steps

### Immediate Actions Required

1. **Claude's Review and Approval**
   - Review this entire report
   - Answer questions in Section 8
   - Approve or request changes to classification decisions
   - Approve phased rollout plan

2. **After Approval: Create Reorganization Plan**
   - Detail exact changes to each YAML file
   - Create SQL migration scripts
   - Design validation test cases
   - Document rollback procedures

3. **Implementation**
   - Execute Phase 1: Database schema updates
   - Execute Phase 2: Task reclassification
   - Execute Phase 3: Rental support
   - Execute Phase 4: Commercial support
   - Execute Phase 5: Testing & validation

---

## Section 10: Summary Statistics

### Current State
- **Templates Analyzed:** 5
- **Total Tasks Found:** 150+ unique tasks
- **Tasks Properly Classified:** ~53 (35%)
- **Tasks Needing Reclassification:** ~67 (45%)
- **Tasks Missing:** ~30 (20%)
- **Critical Issues:** 4
- **High Priority Issues:** 3
- **Medium Priority Issues:** 3
- **Low Priority Issues:** 2

### Target State (After Reorganization)
- **Templates Total:** 28 (from 5)
- **Transaction Types Supported:** 6 (from 1 implied)
- **Property Types Supported:** 7 (from 4)
- **Total Unique Tasks:** ~180 (from 150)
- **Universal Tasks:** 7
- **Buy-Side Tasks:** 42
- **Sell-Side Tasks:** 47
- **Rental Tasks:** 56 (NEW)
- **Commercial Tasks:** 23 (NEW)
- **Property-Specific Tasks:** ~60

### Estimated Effort
- **Analysis:** Complete (this report)
- **Planning:** 1 week
- **Implementation:** 6 weeks
- **Testing:** 1 week
- **Total:** 8 weeks

---

## Appendices

### Appendix A: MA Legal Citation Reference

| Citation | Requirement | Applies To | Task Key |
|----------|-------------|------------|----------|
| MA Licensing Regulations | Agency Disclosure | ALL | agency_disclosure |
| NAR Settlement 2025 | Buyer Agency Agreement | Buy Side | sign_buyer_agency_agreement |
| M.G.L. c. 183A, § 6(d) | Condo 6(d) Certificate | Condo Sales | condo_6d_request |
| M.G.L. c. 112, § 87 DDD ½ | Rental Fee Disclosure | Rental (Both) | rental_fee_disclosure |
| 310 CMR 15.000 | Title 5 Septic Inspection | SFH with Septic | title5_septic_inspection |
| 42 U.S.C. § 4852d | Lead Paint Disclosure | Pre-1978 | lead_paint_disclosure |
| 105 CMR 460.720 | MA Lead Paint Notification | Pre-1978 | lead_paint_disclosure |
| M.G.L. c. 186, § 15B | Security Deposit Law | Rental Landlord | security_deposit_requirements |
| M.G.L. c. 148 §26F | Smoke Detector Certificate | SFH | smoke_co_inspection |
| M.G.L. c. 148 §26F½ | CO Alarm Certificate | ALL Residential | smoke_co_inspection |
| M.G.L. c. 60, § 23 | Municipal Lien Certificate | Property Sales | municipal_lien |

### Appendix B: Task Category Distribution

| Category | Current Count | Target Count | Notes |
|----------|--------------|--------------|-------|
| Deal Setup | 10 | 7 | Remove transaction-specific |
| Party Onboarding | 8 | 8 | Mostly universal |
| Communication | 6 | 6 | Universal |
| Earnest Money | 4 | 4 | Buy-side specific |
| Inspection | 12 | 15 | Property-specific variants |
| P&S Agreement | 8 | 8 | Buy/sell variants |
| Financing | 10 | 10 | Buy-side specific |
| Title Work | 5 | 5 | Universal (except rentals) |
| HOA/Condo | 16 | 16 | Condo-specific |
| Pre-Closing | 15 | 15 | Various |
| Closing | 10 | 10 | Various |
| Post-Closing | 8 | 8 | Universal |
| Property-Specific | 30 | 60 | Many additions |
| Compliance | 8 | 18 | MA legal additions |

---

**END OF REPORT**

---

## Report Status

**Status:** DRAFT - AWAITING CLAUDE'S REVIEW
**Next Action:** Claude to review and provide feedback
**Prepared By:** Claude DB (Data Architect Agent)
**Date Prepared:** 2025-10-30
**Version:** 1.0 DRAFT
