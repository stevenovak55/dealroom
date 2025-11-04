-- Migration: 026 - Create Global Contacts Table
-- Description: Create ma_deal_contacts table for global contact management with CRM sync support
-- Author: AI Agent (Claude Code)
-- Date: 2025-11-04

-- Create global contacts table
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_contacts` (
    `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Account that owns this contact',

    -- Contact Information
    `contact_type` ENUM('person', 'company') NOT NULL DEFAULT 'person',
    `first_name` VARCHAR(100) DEFAULT NULL,
    `last_name` VARCHAR(100) DEFAULT NULL,
    `company_name` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `mobile` VARCHAR(20) DEFAULT NULL,
    `address` VARCHAR(500) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(50) DEFAULT NULL,
    `zip` VARCHAR(20) DEFAULT NULL,
    `country` VARCHAR(100) DEFAULT 'USA',

    -- Professional Information
    `job_title` VARCHAR(255) DEFAULT NULL,
    `license_number` VARCHAR(100) DEFAULT NULL COMMENT 'For licensed professionals (agents, attorneys)',
    `bar_number` VARCHAR(100) DEFAULT NULL COMMENT 'For attorneys',
    `website` VARCHAR(500) DEFAULT NULL,

    -- CRM Sync Fields
    `crm_id` VARCHAR(100) DEFAULT NULL COMMENT 'CRM record ID (Salesforce/HubSpot)',
    `crm_provider` VARCHAR(50) DEFAULT NULL COMMENT 'CRM provider type (salesforce/hubspot)',
    `crm_last_sync` DATETIME DEFAULT NULL COMMENT 'Last successful sync timestamp',
    `crm_sync_status` VARCHAR(20) DEFAULT NULL COMMENT 'Sync status (synced/pending/error)',
    `crm_sync_error` TEXT DEFAULT NULL COMMENT 'Last sync error message',

    -- Additional Data
    `tags` JSON DEFAULT NULL COMMENT 'Contact tags/categories',
    `notes` TEXT DEFAULT NULL COMMENT 'Internal notes about contact',
    `metadata` JSON DEFAULT NULL COMMENT 'Additional custom fields',

    -- Status
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,

    -- Timestamps
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Indexes for lookups
    KEY `idx_account` (`account_id`),
    KEY `idx_email` (`email`),
    KEY `idx_name` (`last_name`, `first_name`),
    KEY `idx_company` (`company_name`),
    KEY `idx_phone` (`phone`),
    KEY `idx_type` (`contact_type`),

    -- CRM sync indexes
    KEY `idx_crm_id` (`crm_id`),
    KEY `idx_crm_provider` (`crm_provider`),
    KEY `idx_crm_sync_status` (`crm_sync_status`),
    KEY `idx_crm_last_sync` (`crm_last_sync`),
    UNIQUE KEY `idx_crm_unique` (`account_id`, `crm_provider`, `crm_id`),

    -- Prevent duplicate contacts by email within account
    UNIQUE KEY `idx_account_email` (`account_id`, `email`),

    -- Foreign key to accounts
    CONSTRAINT `fk_contact_account` FOREIGN KEY (`account_id`)
        REFERENCES `{prefix}ma_deal_accounts` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Global contacts table with CRM synchronization support';

-- Add contact_id to ma_deal_parties to link to global contacts
ALTER TABLE `{prefix}ma_deal_parties`
    ADD COLUMN `contact_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Reference to global contact' AFTER `transaction_id`,
    ADD KEY `idx_contact` (`contact_id`),
    ADD CONSTRAINT `fk_party_contact` FOREIGN KEY (`contact_id`)
        REFERENCES `{prefix}ma_deal_contacts` (`id`) ON DELETE SET NULL;

-- Note: Existing party records will have contact_id=NULL
-- A data migration script should be run to create contacts from existing parties
