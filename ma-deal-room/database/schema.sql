-- ============================================================================
-- MA Deal Room Database Schema
-- ============================================================================
-- Version: 1.0
-- Purpose: WordPress plugin for Massachusetts real estate transaction management
-- Tables: 8 custom tables with wp_ma_deal_ prefix
-- Engine: InnoDB with utf8mb4 character set for emoji/international support
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- Table 1: ACCOUNTS (Multi-tenant Organizations)
-- ============================================================================
-- Purpose: Support multi-tenant architecture for agencies/brokerages
-- Scale: Thousands of accounts
-- Key Indexes: status for filtering active accounts
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_ma_deal_accounts` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL COMMENT 'Agency/brokerage name',
  `owner_user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'WordPress user ID of account owner',
  `status` ENUM('active', 'suspended', 'trial', 'cancelled') NOT NULL DEFAULT 'active',
  `settings` JSON DEFAULT NULL COMMENT 'Account-level settings (timezone, branding, notification preferences)',
  `subscription_tier` VARCHAR(50) DEFAULT 'free' COMMENT 'Pricing tier: free, pro, enterprise',
  `subscription_expires_at` DATETIME DEFAULT NULL COMMENT 'Subscription expiration date',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_owner_user` (`owner_user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_subscription_expires` (`subscription_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Multi-tenant accounts for agencies/brokerages';

-- ============================================================================
-- Table 2: TRANSACTIONS (Property Transactions)
-- ============================================================================
-- Purpose: Core transaction entity with property details and key dates
-- Scale: 10,000+ transactions per account
-- Key Indexes: Composite (account_id, status, closing_date) for dashboard queries
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_ma_deal_transactions` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` BIGINT(20) UNSIGNED NOT NULL,
  `property_address` VARCHAR(500) NOT NULL,
  `property_city` VARCHAR(100) NOT NULL,
  `property_state` CHAR(2) NOT NULL DEFAULT 'MA',
  `property_zip` VARCHAR(10) NOT NULL,
  `property_type` ENUM('SFH', 'Condo', 'Multifamily', 'Land', 'Commercial') NOT NULL COMMENT 'Property classification',
  `property_year_built` SMALLINT(4) UNSIGNED DEFAULT NULL COMMENT 'For lead paint condition (pre-1978)',
  `property_metadata` JSON DEFAULT NULL COMMENT 'has_septic, bedrooms, bathrooms, sqft, parcel_id, etc.',
  `sale_price` DECIMAL(12,2) DEFAULT NULL,
  `transaction_side` ENUM('listing', 'buyer') NOT NULL DEFAULT 'listing' COMMENT 'Which side agent represents',
  `status` ENUM('prospect', 'listing_active', 'under_agreement', 'closed', 'cancelled') NOT NULL DEFAULT 'prospect',
  `listing_date` DATE DEFAULT NULL COMMENT 'Date property listed',
  `offer_accepted_date` DATE DEFAULT NULL COMMENT 'Date offer accepted',
  `ps_agreement_date` DATE DEFAULT NULL COMMENT 'Purchase & Sale Agreement signed',
  `closing_date` DATE DEFAULT NULL COMMENT 'Scheduled closing date',
  `actual_closing_date` DATE DEFAULT NULL COMMENT 'Actual closing date if different',
  `assigned_agent_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'WordPress user ID of assigned agent',
  `template_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Task template used for this transaction',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_account_status_closing` (`account_id`, `status`, `closing_date`),
  KEY `idx_status_closing` (`status`, `closing_date`),
  KEY `idx_assigned_agent` (`assigned_agent_id`),
  KEY `idx_template` (`template_id`),
  KEY `idx_property_city` (`property_city`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_transaction_account` FOREIGN KEY (`account_id`)
    REFERENCES `wp_ma_deal_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Property transactions with dates and metadata';

-- ============================================================================
-- Table 3: PARTIES (Transaction Contacts)
-- ============================================================================
-- Purpose: Track attorneys, lenders, HOA, inspectors, buyers, sellers
-- Scale: 5-15 parties per transaction
-- Key Indexes: (transaction_id, role) for quick party lookup
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_ma_deal_parties` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_id` BIGINT(20) UNSIGNED NOT NULL,
  `role` ENUM('buyer', 'seller', 'buyer_attorney', 'seller_attorney', 'buyer_lender',
              'buyer_agent', 'seller_agent', 'title_company', 'inspector', 'appraiser',
              'hoa_manager', 'septic_inspector', 'fire_dept', 'other') NOT NULL,
  `company_name` VARCHAR(255) DEFAULT NULL,
  `contact_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` VARCHAR(500) DEFAULT NULL,
  `metadata` JSON DEFAULT NULL COMMENT 'Additional fields: website, bar_number, license_number, etc.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_role` (`transaction_id`, `role`),
  KEY `idx_email` (`email`),
  CONSTRAINT `fk_party_transaction` FOREIGN KEY (`transaction_id`)
    REFERENCES `wp_ma_deal_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Transaction parties and contacts (attorneys, lenders, HOA, inspectors)';

-- ============================================================================
-- Table 4: TEMPLATES (Task Templates)
-- ============================================================================
-- Purpose: Store YAML task templates for different property types
-- Scale: Hundreds of templates (custom + system defaults)
-- Key Indexes: (account_id, is_active) for template selection
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_ma_deal_templates` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'NULL for system/default templates',
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `property_type` ENUM('SFH', 'Condo', 'Multifamily', 'Land', 'Commercial', 'Any') DEFAULT 'Any',
  `template_yaml` LONGTEXT NOT NULL COMMENT 'YAML task definition with conditions and dependencies',
  `is_system` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'System template (cannot be edited by users)',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `version` INT(11) NOT NULL DEFAULT 1 COMMENT 'Template version for change tracking',
  `created_by_user_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_account_active` (`account_id`, `is_active`),
  KEY `idx_property_type` (`property_type`),
  KEY `idx_system` (`is_system`),
  CONSTRAINT `fk_template_account` FOREIGN KEY (`account_id`)
    REFERENCES `wp_ma_deal_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='YAML task templates for different property types';

-- ============================================================================
-- Table 5: TASKS (Instantiated Tasks)
-- ============================================================================
-- Purpose: Individual tasks generated from templates with status tracking
-- Scale: 20-30 tasks per transaction × 10,000 transactions = 200,000+ rows
-- Key Indexes: Critical composite index (transaction_id, status, due_at) for timeline
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_ma_deal_tasks` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_id` BIGINT(20) UNSIGNED NOT NULL,
  `template_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Source template if generated',
  `task_key` VARCHAR(100) NOT NULL COMMENT 'Unique identifier from template (e.g., title5_septic)',
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'in_progress', 'completed', 'cancelled', 'blocked', 'skipped')
    NOT NULL DEFAULT 'pending',
  `owner_role` ENUM('agent', 'seller', 'buyer', 'seller_attorney', 'buyer_attorney',
                    'vendor', 'system') NOT NULL DEFAULT 'agent',
  `assigned_party_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'FK to wp_ma_deal_parties if vendor',
  `due_at` DATETIME DEFAULT NULL COMMENT 'Calculated due date based on template offset',
  `completed_at` DATETIME DEFAULT NULL,
  `depends_on_task_ids` JSON DEFAULT NULL COMMENT 'Array of task IDs that must complete first',
  `applies_if_condition` TEXT DEFAULT NULL COMMENT 'Stored condition from template for audit',
  `metadata` JSON DEFAULT NULL COMMENT 'Citations, notes, estimated_duration, external_url',
  `sort_order` INT(11) DEFAULT 0 COMMENT 'Display order within transaction',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_status_due` (`transaction_id`, `status`, `due_at`),
  KEY `idx_status_due` (`status`, `due_at`),
  KEY `idx_due_at` (`due_at`),
  KEY `idx_task_key` (`task_key`),
  KEY `idx_assigned_party` (`assigned_party_id`),
  CONSTRAINT `fk_task_transaction` FOREIGN KEY (`transaction_id`)
    REFERENCES `wp_ma_deal_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_template` FOREIGN KEY (`template_id`)
    REFERENCES `wp_ma_deal_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_task_party` FOREIGN KEY (`assigned_party_id`)
    REFERENCES `wp_ma_deal_parties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Instantiated tasks with status, due dates, and dependencies';

-- ============================================================================
-- Table 6: EVENTS (Audit Log)
-- ============================================================================
-- Purpose: Compliance audit trail for all state changes
-- Scale: High volume (100+ events per transaction)
-- Key Indexes: (transaction_id, created_at) for timeline, (entity_type, entity_id) for entity history
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_ma_deal_events` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `account_id` BIGINT(20) UNSIGNED NOT NULL,
  `transaction_id` BIGINT(20) UNSIGNED DEFAULT NULL,
  `entity_type` ENUM('transaction', 'task', 'party', 'template', 'reminder', 'vendor_request') NOT NULL,
  `entity_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'ID of entity that changed',
  `event_type` ENUM('created', 'updated', 'deleted', 'status_changed', 'completed',
                    'reminded', 'escalated', 'assigned') NOT NULL,
  `user_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'WordPress user who triggered event',
  `old_data` JSON DEFAULT NULL COMMENT 'Previous state for updates',
  `new_data` JSON DEFAULT NULL COMMENT 'New state',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6',
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_created` (`transaction_id`, `created_at`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_account_created` (`account_id`, `created_at`),
  KEY `idx_user` (`user_id`),
  KEY `idx_event_type` (`event_type`),
  CONSTRAINT `fk_event_account` FOREIGN KEY (`account_id`)
    REFERENCES `wp_ma_deal_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_transaction` FOREIGN KEY (`transaction_id`)
    REFERENCES `wp_ma_deal_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit log for compliance and change tracking';

-- ============================================================================
-- Table 7: REMINDERS (Reminder Queue)
-- ============================================================================
-- Purpose: Email/SMS reminder queue with processing status
-- Scale: 3-5 reminders per task × 200,000 tasks = 600,000+ rows
-- Key Indexes: (status, scheduled_at) for queue processing
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_ma_deal_reminders` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` BIGINT(20) UNSIGNED NOT NULL,
  `transaction_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Denormalized for query performance',
  `recipient_type` ENUM('agent', 'seller', 'buyer', 'attorney', 'party') NOT NULL,
  `recipient_email` VARCHAR(255) DEFAULT NULL,
  `recipient_phone` VARCHAR(20) DEFAULT NULL COMMENT 'For SMS reminders',
  `channel` ENUM('email', 'sms', 'both') NOT NULL DEFAULT 'email',
  `scheduled_at` DATETIME NOT NULL COMMENT 'When to send reminder',
  `sent_at` DATETIME DEFAULT NULL,
  `status` ENUM('pending', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
  `failure_reason` VARCHAR(500) DEFAULT NULL,
  `retry_count` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `max_retries` TINYINT(3) UNSIGNED NOT NULL DEFAULT 3,
  `message_template` VARCHAR(100) DEFAULT NULL COMMENT 'Template key for message content',
  `metadata` JSON DEFAULT NULL COMMENT 'Merge fields, tracking data',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status_scheduled` (`status`, `scheduled_at`),
  KEY `idx_task` (`task_id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_recipient_email` (`recipient_email`),
  CONSTRAINT `fk_reminder_task` FOREIGN KEY (`task_id`)
    REFERENCES `wp_ma_deal_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reminder_transaction` FOREIGN KEY (`transaction_id`)
    REFERENCES `wp_ma_deal_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Reminder queue for email/SMS notifications';

-- ============================================================================
-- Table 8: VENDOR REQUESTS (External Vendor Scheduling)
-- ============================================================================
-- Purpose: Track external vendor tasks with signed URLs for public access
-- Scale: 5-10 vendor requests per transaction
-- Key Indexes: (token) for public URL lookup, (status, created_at) for monitoring
-- ============================================================================

CREATE TABLE IF NOT EXISTS `wp_ma_deal_vendor_requests` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` BIGINT(20) UNSIGNED NOT NULL,
  `transaction_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Denormalized for performance',
  `party_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'FK to wp_ma_deal_parties',
  `vendor_type` ENUM('fire_dept', 'septic_inspector', 'hoa_manager', 'title_company',
                     'appraiser', 'inspector', 'other') NOT NULL,
  `vendor_email` VARCHAR(255) NOT NULL,
  `vendor_phone` VARCHAR(20) DEFAULT NULL,
  `token` VARCHAR(64) NOT NULL COMMENT 'Signed URL token for public access',
  `token_expires_at` DATETIME NOT NULL COMMENT 'URL expiration (typically 30-60 days)',
  `status` ENUM('sent', 'opened', 'scheduled', 'completed', 'expired', 'cancelled')
    NOT NULL DEFAULT 'sent',
  `scheduled_date` DATE DEFAULT NULL COMMENT 'Date vendor scheduled (e.g., inspection date)',
  `scheduled_time` TIME DEFAULT NULL,
  `completion_notes` TEXT DEFAULT NULL,
  `document_url` VARCHAR(500) DEFAULT NULL COMMENT 'Certificate/report uploaded by vendor',
  `last_opened_at` DATETIME DEFAULT NULL COMMENT 'Last time vendor accessed URL',
  `metadata` JSON DEFAULT NULL COMMENT 'Custom fields per vendor type',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_token` (`token`),
  KEY `idx_status_created` (`status`, `created_at`),
  KEY `idx_task` (`task_id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_party` (`party_id`),
  KEY `idx_token_expires` (`token_expires_at`),
  CONSTRAINT `fk_vendor_task` FOREIGN KEY (`task_id`)
    REFERENCES `wp_ma_deal_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vendor_transaction` FOREIGN KEY (`transaction_id`)
    REFERENCES `wp_ma_deal_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vendor_party` FOREIGN KEY (`party_id`)
    REFERENCES `wp_ma_deal_parties` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='External vendor scheduling with signed URLs';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SCHEMA COMPLETE
-- ============================================================================
-- Total Tables: 8
-- Estimated Storage (10K transactions): 2-5 GB
-- Primary Query Patterns: Timeline view, overdue tasks, reminder queue
-- Scaling Strategy: Partition events table by created_at, archive old reminders
-- ============================================================================
