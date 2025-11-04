-- ============================================================================
-- MA Deal Room Seed Data
-- ============================================================================
-- Purpose: Sample data for development and testing
-- Contents:
--   - 1 Account (Bay State Realty)
--   - 3 Transactions (SFH with septic, Condo, Multifamily)
--   - 15 Parties (buyers, sellers, attorneys, vendors)
--   - 1 Template (MA SFH with Septic)
--   - 24 Tasks across 3 transactions
--   - 15 Reminders
--   - 30 Events
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. ACCOUNTS
-- ============================================================================

INSERT INTO `wp_ma_deal_accounts`
(`id`, `name`, `owner_user_id`, `status`, `settings`, `subscription_tier`, `subscription_expires_at`, `created_at`)
VALUES
(1, 'Bay State Realty', 1, 'active',
 '{"timezone": "America/New_York", "branding": {"logo_url": "/assets/logo.png", "primary_color": "#1E40AF"}, "notifications": {"email": true, "sms": true}}',
 'pro', '2026-10-30 00:00:00', '2024-06-01 10:00:00');

-- ============================================================================
-- 2. TRANSACTIONS
-- ============================================================================

INSERT INTO `wp_ma_deal_transactions`
(`id`, `account_id`, `property_address`, `property_city`, `property_state`, `property_zip`,
 `property_type`, `property_year_built`, `property_metadata`, `sale_price`, `transaction_side`,
 `status`, `listing_date`, `offer_accepted_date`, `ps_agreement_date`, `closing_date`,
 `assigned_agent_id`, `created_at`)
VALUES
-- Transaction 1: SFH with septic system (requires Title 5)
(1, 1, '42 Maple Ridge Road', 'Concord', 'MA', '01742',
 'SFH', 1968,
 '{"has_septic": true, "bedrooms": 4, "bathrooms": 2.5, "sqft": 2400, "lot_acres": 1.2, "parcel_id": "R12-345"}',
 725000.00, 'listing', 'under_agreement',
 '2025-08-15', '2025-09-20', '2025-09-27', '2025-11-15',
 1, '2025-08-15 09:30:00'),

-- Transaction 2: Condo unit (requires 6(d) certificate, no septic/smoke)
(2, 1, '88 Harbor View Drive, Unit 304', 'Boston', 'MA', '02110',
 'Condo', 2015,
 '{"has_septic": false, "bedrooms": 2, "bathrooms": 2, "sqft": 1200, "hoa_name": "Harbor View Condominiums", "parking_spaces": 1}',
 565000.00, 'listing', 'under_agreement',
 '2025-09-01', '2025-10-05', '2025-10-12', '2025-12-01',
 1, '2025-09-01 14:20:00'),

-- Transaction 3: Multifamily on city water (requires smoke/CO, no septic)
(3, 1, '156-158 Washington Street', 'Somerville', 'MA', '02143',
 'Multifamily', 1925,
 '{"has_septic": false, "units": 3, "bedrooms_total": 8, "bathrooms_total": 4, "sqft": 3600, "zoning": "R3"}',
 980000.00, 'listing', 'listing_active',
 '2025-10-15', NULL, NULL, '2026-01-10',
 1, '2025-10-15 11:00:00');

-- ============================================================================
-- 3. PARTIES
-- ============================================================================

INSERT INTO `wp_ma_deal_parties`
(`id`, `transaction_id`, `role`, `company_name`, `contact_name`, `email`, `phone`, `metadata`)
VALUES
-- Transaction 1 Parties (SFH Septic)
(1, 1, 'seller', NULL, 'Robert & Mary Chen', 'chenr@email.com', '617-555-0101', NULL),
(2, 1, 'buyer', NULL, 'Jessica Martinez', 'jmartinez@email.com', '857-555-0202', NULL),
(3, 1, 'seller_attorney', 'Burke & Associates', 'Attorney Michael Burke', 'mburke@burkelaw.com', '978-555-0303', '{"bar_number": "MA-12345"}'),
(4, 1, 'buyer_attorney', 'Whitman Legal Group', 'Attorney Sarah Whitman', 'swhitman@whitmanlegal.com', '617-555-0404', '{"bar_number": "MA-67890"}'),
(5, 1, 'buyer_lender', 'Commonwealth Mortgage', 'David Park - Loan Officer', 'dpark@commortgage.com', '800-555-0505', '{"nmls_id": "123456"}'),
(6, 1, 'septic_inspector', 'MA Septic Solutions', 'Tom Reynolds', 'tom@masepticsolutions.com', '978-555-0606', '{"license": "Title5-8765"}'),
(7, 1, 'fire_dept', 'Concord Fire Department', 'Fire Prevention Office', 'prevention@concordfire.gov', '978-318-3400', NULL),

-- Transaction 2 Parties (Condo)
(8, 2, 'seller', NULL, 'Amanda Brooks', 'abrooks@email.com', '617-555-0707', NULL),
(9, 2, 'buyer', NULL, 'Kevin & Lisa Tran', 'ktran@email.com', '617-555-0808', NULL),
(10, 2, 'seller_attorney', 'Coastal Title & Law', 'Attorney Jennifer Russo', 'jrusso@coastaltitle.com', '617-555-0909', '{"bar_number": "MA-24680"}'),
(11, 2, 'buyer_lender', 'Harbor Bank', 'Michelle Lee - Mortgage Specialist', 'mlee@harborbank.com', '800-555-1010', '{"nmls_id": "654321"}'),
(12, 2, 'hoa_manager', 'Harbor View HOA', 'Property Manager - John Silva', 'jsilva@harborviewhoa.com', '617-555-1111', NULL),

-- Transaction 3 Parties (Multifamily)
(13, 3, 'seller', NULL, 'Thompson Family Trust', 'trust@thompsonre.com', '617-555-1212', NULL),
(14, 3, 'fire_dept', 'Somerville Fire Department', 'Inspectional Services', 'inspections@somervillefire.gov', '617-625-1234', NULL),
(15, 3, 'inspector', 'Metro Home Inspections', 'Carlos Mendez', 'carlos@metrohomeinspections.com', '617-555-1313', '{"license": "HI-9988"}}');

-- ============================================================================
-- 4. TEMPLATES
-- ============================================================================

INSERT INTO `wp_ma_deal_templates`
(`id`, `account_id`, `name`, `description`, `property_type`, `template_yaml`, `is_system`, `is_active`, `version`)
VALUES
(1, NULL, 'MA SFH with Septic - Standard', 'Standard task template for Massachusetts single-family homes with septic systems',
 'SFH',
'# MA SFH with Septic - Task Template
version: 1.0
property_type: SFH

tasks:
  - key: agency_disclosure
    title: Provide Agency Disclosure Form
    owner: agent
    due: FirstMeeting+0d
    mandatory: true

  - key: lead_paint_disclosure
    title: Property Transfer Lead Paint Notification
    owner: seller
    due: Offer-2d
    mandatory: true
    applies_if: "property.year_built < 1978"
    citations:
      - https://www.mass.gov/info-details/property-transfer-lead-paint-notification

  - key: title5_septic
    title: Schedule and Complete Title 5 Septic Inspection
    owner: seller
    due: Closing-90d
    mandatory: true
    applies_if: "property.has_septic == true"
    reminders: [-120d, -90d, -60d, -30d]

  - key: smoke_co_cert
    title: Schedule Smoke & CO Detector Inspection
    owner: seller
    due: Closing-21d
    mandatory: true
    reminders: [-30d, -21d, -14d]

  - key: municipal_lien
    title: Request Municipal Lien Certificate
    owner: buyer_attorney
    due: Closing-21d
    mandatory: true

  - key: final_walkthrough
    title: Complete Final Walkthrough
    owner: agent
    due: Closing-1d
    mandatory: true',
 TRUE, TRUE, 1);

-- ============================================================================
-- 5. TASKS
-- ============================================================================

INSERT INTO `wp_ma_deal_tasks`
(`id`, `transaction_id`, `template_id`, `task_key`, `title`, `description`, `status`,
 `owner_role`, `assigned_party_id`, `due_at`, `completed_at`, `metadata`, `sort_order`)
VALUES
-- Transaction 1 Tasks (SFH with Septic) - 10 tasks
(1, 1, 1, 'agency_disclosure', 'Provide Agency Disclosure Form',
 'Present mandatory licensee-consumer relationship disclosure',
 'completed', 'agent', NULL, '2025-08-15 10:00:00', '2025-08-15 10:30:00',
 '{"citations": ["https://www.mass.gov/doc/real-estate-board-agency-disclosure-form-english/download"]}', 1),

(2, 1, 1, 'lead_paint_disclosure', 'Property Transfer Lead Paint Notification',
 'Provide lead paint disclosure for pre-1978 property',
 'completed', 'seller', 1, '2025-09-18 17:00:00', '2025-09-19 09:00:00',
 '{"citations": ["https://www.mass.gov/info-details/property-transfer-lead-paint-notification"], "applies_condition": "property.year_built < 1978"}', 2),

(3, 1, 1, 'title5_septic', 'Schedule and Complete Title 5 Septic Inspection',
 'Arrange certified Title 5 inspection with MA Septic Solutions',
 'completed', 'seller', 6, '2025-08-17 12:00:00', '2025-08-20 14:30:00',
 '{"citations": ["https://www.mass.gov/guides/buying-or-selling-property-with-a-septic-system"], "inspection_date": "2025-08-20", "result": "passed"}', 3),

(4, 1, 1, 'smoke_co_cert', 'Schedule Smoke & CO Detector Inspection',
 'Contact Concord Fire Dept for certificate of compliance',
 'in_progress', 'seller', 7, '2025-10-25 12:00:00', NULL,
 '{"citations": ["https://www.mass.gov/doc/consumer-guide-to-smoke-detectors-when-selling-home/download"]}', 4),

(5, 1, 1, 'municipal_lien', 'Request Municipal Lien Certificate',
 'Obtain certificate from Concord Tax Collector',
 'pending', 'buyer_attorney', 4, '2025-10-25 12:00:00', NULL,
 '{"citations": ["M.G.L. c.60 §23"], "fee_estimate": "$35"}', 5),

(6, 1, 1, 'loan_commitment', 'Monitor Buyer Loan Commitment Status',
 'Track Commonwealth Mortgage loan approval',
 'in_progress', 'agent', NULL, '2025-10-18 17:00:00', NULL,
 '{"lender": "Commonwealth Mortgage", "contingency_date": "2025-11-01"}', 6),

(7, 1, 1, 'appraisal', 'Monitor Property Appraisal Progress',
 'Ensure appraisal supports $725K sales price',
 'completed', 'agent', NULL, '2025-10-18 17:00:00', '2025-10-16 10:00:00',
 '{"appraisal_value": 730000, "appraisal_date": "2025-10-16"}', 7),

(8, 1, 1, 'home_inspection', 'Coordinate Home Inspection Access',
 'Schedule and provide access for buyer inspection',
 'completed', 'agent', NULL, '2025-10-04 12:00:00', '2025-10-03 15:00:00',
 '{"inspection_date": "2025-10-03", "inspector": "New England Home Inspections"}', 8),

(9, 1, 1, 'final_walkthrough', 'Complete Final Walkthrough',
 'Final property inspection 24-48 hours before closing',
 'pending', 'agent', NULL, '2025-11-14 10:00:00', NULL, '{}', 9),

(10, 1, 1, 'clear_to_close', 'Confirm Lender Clear to Close',
 'Verify Commonwealth Mortgage final approval',
 'pending', 'agent', NULL, '2025-11-10 17:00:00', NULL, '{}', 10),

-- Transaction 2 Tasks (Condo) - 8 tasks
(11, 2, 1, 'agency_disclosure', 'Provide Agency Disclosure Form',
 'Present mandatory disclosure', 'completed', 'agent', NULL,
 '2025-09-01 14:00:00', '2025-09-01 14:30:00', '{}', 1),

(12, 2, 1, 'condo_6d', 'Request Condo 6(d) Certificate from HOA',
 'Obtain certificate of unpaid common expenses from Harbor View HOA',
 'in_progress', 'seller_attorney', 12, '2025-11-17 12:00:00', NULL,
 '{"citations": ["M.G.L. c.183A §6(d)"], "hoa_contact": "John Silva"}', 2),

(13, 2, 1, 'municipal_lien', 'Request Municipal Lien Certificate',
 'Obtain certificate from Boston Tax Collector',
 'pending', 'buyer_attorney', NULL, '2025-11-10 12:00:00', NULL,
 '{"fee_estimate": "$50", "city": "Boston"}', 3),

(14, 2, 1, 'loan_commitment', 'Monitor Buyer Loan Commitment Status',
 'Track Harbor Bank loan approval',
 'in_progress', 'agent', NULL, '2025-11-02 17:00:00', NULL,
 '{"lender": "Harbor Bank", "contingency_date": "2025-11-16"}', 4),

(15, 2, 1, 'appraisal', 'Monitor Property Appraisal Progress',
 'Ensure appraisal supports $565K sales price',
 'pending', 'agent', NULL, '2025-11-02 17:00:00', NULL, '{}', 5),

(16, 2, 1, 'home_inspection', 'Coordinate Home Inspection Access',
 'Schedule buyer inspection',
 'completed', 'agent', NULL, '2025-10-19 12:00:00', '2025-10-17 11:00:00',
 '{"inspection_date": "2025-10-17"}', 6),

(17, 2, 1, 'final_walkthrough', 'Complete Final Walkthrough',
 'Final inspection before closing',
 'pending', 'agent', NULL, '2025-11-30 10:00:00', NULL, '{}', 7),

(18, 2, 1, 'clear_to_close', 'Confirm Lender Clear to Close',
 'Verify Harbor Bank final approval',
 'pending', 'agent', NULL, '2025-11-26 17:00:00', NULL, '{}', 8),

-- Transaction 3 Tasks (Multifamily) - 6 tasks
(19, 3, 1, 'agency_disclosure', 'Provide Agency Disclosure Form',
 'Present mandatory disclosure', 'completed', 'agent', NULL,
 '2025-10-15 11:00:00', '2025-10-15 11:30:00', '{}', 1),

(20, 3, 1, 'smoke_co_cert', 'Schedule Smoke & CO Detector Inspection',
 'Contact Somerville Fire Dept for 3-family inspection',
 'pending', 'seller', 14, '2025-12-20 12:00:00', NULL,
 '{"property_units": 3, "estimated_fee": "$150"}', 2),

(21, 3, 1, 'municipal_lien', 'Request Municipal Lien Certificate',
 'Obtain certificate from Somerville Tax Collector',
 'pending', 'buyer_attorney', NULL, '2025-12-20 12:00:00', NULL,
 '{"fee_estimate": "$35"}', 3),

(22, 3, 1, 'home_inspection', 'Coordinate Home Inspection Access',
 'Multi-unit inspection - all 3 units',
 'pending', 'agent', NULL, '2025-11-05 12:00:00', NULL,
 '{"property_units": 3}', 4),

(23, 3, 1, 'final_walkthrough', 'Complete Final Walkthrough',
 'Final inspection all units',
 'pending', 'agent', NULL, '2026-01-09 10:00:00', NULL, '{}', 5),

(24, 3, 1, 'clear_to_close', 'Confirm Lender Clear to Close',
 'Verify final approval',
 'pending', 'agent', NULL, '2026-01-05 17:00:00', NULL, '{}', 6);

-- ============================================================================
-- 6. REMINDERS
-- ============================================================================

INSERT INTO `wp_ma_deal_reminders`
(`id`, `task_id`, `transaction_id`, `recipient_type`, `recipient_email`, `channel`,
 `scheduled_at`, `status`, `message_template`, `metadata`)
VALUES
-- Transaction 1 Reminders
(1, 4, 1, 'seller', 'chenr@email.com', 'email', '2025-10-15 09:00:00', 'sent',
 'smoke_co_reminder_21d', '{"days_until_due": 10, "sent_at": "2025-10-15 09:05:00"}'),

(2, 4, 1, 'agent', 'agent@baystaterealty.com', 'email', '2025-10-15 09:00:00', 'sent',
 'smoke_co_reminder_21d', '{"days_until_due": 10, "sent_at": "2025-10-15 09:05:00"}'),

(3, 5, 1, 'buyer_attorney', 'swhitman@whitmanlegal.com', 'email', '2025-10-25 09:00:00', 'pending',
 'municipal_lien_reminder', '{"days_until_due": 0}'),

(4, 6, 1, 'agent', 'agent@baystaterealty.com', 'email', '2025-10-18 09:00:00', 'pending',
 'loan_commitment_check', '{"contingency_date": "2025-11-01"}'),

(5, 9, 1, 'agent', 'agent@baystaterealty.com', 'email', '2025-11-13 09:00:00', 'pending',
 'final_walkthrough_reminder', '{"days_until_due": 1}'),

-- Transaction 2 Reminders
(6, 12, 2, 'seller_attorney', 'jrusso@coastaltitle.com', 'email', '2025-11-07 09:00:00', 'sent',
 'condo_6d_reminder', '{"days_until_due": 10, "sent_at": "2025-11-07 09:15:00"}'),

(7, 12, 2, 'party', 'jsilva@harborviewhoa.com', 'email', '2025-11-07 09:00:00', 'sent',
 'condo_6d_hoa_request', '{"days_until_due": 10, "sent_at": "2025-11-07 09:15:00"}'),

(8, 13, 2, 'buyer_attorney', NULL, 'email', '2025-11-10 09:00:00', 'pending',
 'municipal_lien_reminder', '{"days_until_due": 0}'),

(9, 14, 2, 'agent', 'agent@baystaterealty.com', 'email', '2025-11-02 09:00:00', 'pending',
 'loan_commitment_check', '{"contingency_date": "2025-11-16"}'),

(10, 17, 2, 'agent', 'agent@baystaterealty.com', 'email', '2025-11-29 09:00:00', 'pending',
 'final_walkthrough_reminder', '{"days_until_due": 1}'),

-- Transaction 3 Reminders
(11, 20, 3, 'seller', 'trust@thompsonre.com', 'email', '2025-11-20 09:00:00', 'pending',
 'smoke_co_reminder_30d', '{"days_until_due": 30}'),

(12, 21, 3, 'buyer_attorney', NULL, 'email', '2025-11-30 09:00:00', 'pending',
 'municipal_lien_reminder', '{"days_until_due": 20}'),

(13, 22, 3, 'agent', 'agent@baystaterealty.com', 'email', '2025-10-26 09:00:00', 'pending',
 'home_inspection_reminder', '{"days_until_due": 10}'),

(14, 23, 3, 'agent', 'agent@baystaterealty.com', 'email', '2026-01-08 09:00:00', 'pending',
 'final_walkthrough_reminder', '{"days_until_due": 1}'),

(15, 24, 3, 'agent', 'agent@baystaterealty.com', 'email', '2026-01-05 09:00:00', 'pending',
 'clear_to_close_check', '{"days_until_closing": 5}');

-- ============================================================================
-- 7. EVENTS (Audit Log)
-- ============================================================================

INSERT INTO `wp_ma_deal_events`
(`id`, `account_id`, `transaction_id`, `entity_type`, `entity_id`, `event_type`,
 `user_id`, `new_data`, `ip_address`, `created_at`)
VALUES
-- Transaction 1 Events
(1, 1, 1, 'transaction', 1, 'created', 1,
 '{"property_address": "42 Maple Ridge Road", "status": "prospect"}',
 '192.168.1.100', '2025-08-15 09:30:00'),

(2, 1, 1, 'transaction', 1, 'status_changed', 1,
 '{"old_status": "prospect", "new_status": "listing_active"}',
 '192.168.1.100', '2025-08-15 10:00:00'),

(3, 1, 1, 'task', 1, 'created', 1,
 '{"task_key": "agency_disclosure", "title": "Provide Agency Disclosure Form"}',
 '192.168.1.100', '2025-08-15 10:05:00'),

(4, 1, 1, 'task', 1, 'completed', 1,
 '{"completed_by": "Agent", "completed_at": "2025-08-15 10:30:00"}',
 '192.168.1.100', '2025-08-15 10:30:00'),

(5, 1, 1, 'transaction', 1, 'status_changed', 1,
 '{"old_status": "listing_active", "new_status": "under_agreement"}',
 '192.168.1.100', '2025-09-20 16:30:00'),

(6, 1, 1, 'task', 2, 'created', 1,
 '{"task_key": "lead_paint_disclosure"}',
 '192.168.1.100', '2025-09-20 17:00:00'),

(7, 1, 1, 'task', 2, 'completed', 1,
 '{"completed_by": "Seller"}',
 '192.168.1.100', '2025-09-19 09:00:00'),

(8, 1, 1, 'task', 3, 'completed', 1,
 '{"completed_by": "Vendor", "inspection_result": "passed"}',
 '192.168.1.100', '2025-08-20 14:30:00'),

(9, 1, 1, 'reminder', 1, 'sent', NULL,
 '{"recipient": "chenr@email.com", "template": "smoke_co_reminder_21d"}',
 '10.0.0.50', '2025-10-15 09:05:00'),

(10, 1, 1, 'task', 7, 'completed', 1,
 '{"appraisal_value": 730000}',
 '192.168.1.100', '2025-10-16 10:00:00'),

-- Transaction 2 Events
(11, 1, 2, 'transaction', 2, 'created', 1,
 '{"property_address": "88 Harbor View Drive, Unit 304", "status": "prospect"}',
 '192.168.1.101', '2025-09-01 14:20:00'),

(12, 1, 2, 'transaction', 2, 'status_changed', 1,
 '{"old_status": "prospect", "new_status": "listing_active"}',
 '192.168.1.101', '2025-09-01 14:30:00'),

(13, 1, 2, 'transaction', 2, 'status_changed', 1,
 '{"old_status": "listing_active", "new_status": "under_agreement"}',
 '192.168.1.101', '2025-10-05 15:00:00'),

(14, 1, 2, 'task', 11, 'created', 1,
 '{"task_key": "agency_disclosure"}',
 '192.168.1.101', '2025-09-01 14:35:00'),

(15, 1, 2, 'task', 11, 'completed', 1,
 '{"completed_by": "Agent"}',
 '192.168.1.101', '2025-09-01 14:30:00'),

(16, 1, 2, 'task', 12, 'created', 1,
 '{"task_key": "condo_6d"}',
 '192.168.1.101', '2025-10-05 15:30:00'),

(17, 1, 2, 'task', 16, 'completed', 1,
 '{"inspection_date": "2025-10-17"}',
 '192.168.1.101', '2025-10-17 11:00:00'),

(18, 1, 2, 'reminder', 6, 'sent', NULL,
 '{"recipient": "jrusso@coastaltitle.com"}',
 '10.0.0.50', '2025-11-07 09:15:00'),

(19, 1, 2, 'reminder', 7, 'sent', NULL,
 '{"recipient": "jsilva@harborviewhoa.com"}',
 '10.0.0.50', '2025-11-07 09:15:00'),

(20, 1, 2, 'task', 12, 'status_changed', 1,
 '{"old_status": "pending", "new_status": "in_progress"}',
 '192.168.1.101', '2025-11-07 10:00:00'),

-- Transaction 3 Events
(21, 1, 3, 'transaction', 3, 'created', 1,
 '{"property_address": "156-158 Washington Street", "status": "prospect"}',
 '192.168.1.102', '2025-10-15 11:00:00'),

(22, 1, 3, 'transaction', 3, 'status_changed', 1,
 '{"old_status": "prospect", "new_status": "listing_active"}',
 '192.168.1.102', '2025-10-15 11:15:00'),

(23, 1, 3, 'task', 19, 'created', 1,
 '{"task_key": "agency_disclosure"}',
 '192.168.1.102', '2025-10-15 11:20:00'),

(24, 1, 3, 'task', 19, 'completed', 1,
 '{"completed_by": "Agent"}',
 '192.168.1.102', '2025-10-15 11:30:00'),

(25, 1, 3, 'task', 20, 'created', 1,
 '{"task_key": "smoke_co_cert", "property_units": 3}',
 '192.168.1.102', '2025-10-15 11:35:00'),

-- General Account Events
(26, 1, NULL, 'template', 1, 'created', 1,
 '{"template_name": "MA SFH with Septic - Standard"}',
 '192.168.1.100', '2024-06-01 10:30:00'),

(27, 1, 1, 'party', 1, 'created', 1,
 '{"role": "seller", "name": "Robert & Mary Chen"}',
 '192.168.1.100', '2025-08-15 09:45:00'),

(28, 1, 1, 'party', 2, 'created', 1,
 '{"role": "buyer", "name": "Jessica Martinez"}',
 '192.168.1.100', '2025-09-20 16:45:00'),

(29, 1, 2, 'party', 8, 'created', 1,
 '{"role": "seller", "name": "Amanda Brooks"}',
 '192.168.1.101', '2025-09-01 14:40:00'),

(30, 1, 3, 'party', 13, 'created', 1,
 '{"role": "seller", "name": "Thompson Family Trust"}',
 '192.168.1.102', '2025-10-15 11:10:00');

-- ============================================================================
-- 8. VENDOR REQUESTS (Sample)
-- ============================================================================

INSERT INTO `wp_ma_deal_vendor_requests`
(`id`, `task_id`, `transaction_id`, `party_id`, `vendor_type`, `vendor_email`, `vendor_phone`,
 `token`, `token_expires_at`, `status`, `scheduled_date`, `completion_notes`, `document_url`, `created_at`)
VALUES
(1, 3, 1, 6, 'septic_inspector', 'tom@masepticsolutions.com', '978-555-0606',
 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2',
 '2025-09-20 23:59:59', 'completed', '2025-08-20',
 'Title 5 inspection completed. System passed inspection. Report attached.',
 'https://storage.example.com/reports/title5-42maple-20250820.pdf',
 '2025-08-16 10:00:00'),

(2, 4, 1, 7, 'fire_dept', 'prevention@concordfire.gov', '978-318-3400',
 'b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3',
 '2025-11-30 23:59:59', 'scheduled', '2025-10-28',
 NULL, NULL,
 '2025-10-15 12:00:00'),

(3, 12, 2, 12, 'hoa_manager', 'jsilva@harborviewhoa.com', '617-555-1111',
 'c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2g3h4',
 '2025-12-15 23:59:59', 'opened', NULL,
 NULL, NULL,
 '2025-11-07 10:30:00');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED DATA COMPLETE
-- ============================================================================
-- Summary:
--   ✓ 1 Account (Bay State Realty)
--   ✓ 3 Transactions (SFH Septic, Condo, Multifamily)
--   ✓ 15 Parties across all transactions
--   ✓ 1 Template (MA SFH with Septic)
--   ✓ 24 Tasks (various statuses)
--   ✓ 15 Reminders (sent and pending)
--   ✓ 30 Events (audit trail)
--   ✓ 3 Vendor Requests (septic inspector, fire dept, HOA)
-- ============================================================================
