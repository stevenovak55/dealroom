-- =====================================================
-- Migration 030: Enhanced Vendor Portal
-- Description: Adds tables and fields for enhanced vendor portal functionality
-- Author: Claude Code
-- Date: 2025-11-04
-- Dependencies: 001_initial_schema.sql (vendor_requests table)
-- =====================================================

-- Add new fields to existing vendor_requests table
ALTER TABLE `{prefix}ma_deal_vendor_requests`
ADD COLUMN `vendor_name` VARCHAR(100) DEFAULT NULL COMMENT 'Vendor contact name' AFTER `vendor_type`,
ADD COLUMN `vendor_company` VARCHAR(255) DEFAULT NULL COMMENT 'Vendor company/business name' AFTER `vendor_name`,
ADD COLUMN `average_rating` DECIMAL(3,2) DEFAULT NULL COMMENT 'Average rating (0.00-5.00)' AFTER `vendor_company`,
ADD COLUMN `completion_date` DATE DEFAULT NULL COMMENT 'Actual completion date' AFTER `scheduled_time`,
ADD COLUMN `confirmation_sent_at` DATETIME DEFAULT NULL COMMENT 'When confirmation email was sent' AFTER `last_opened_at`;

-- Create index on vendor email for finding all requests by vendor
CREATE INDEX `idx_vendor_requests_vendor_email`
ON `{prefix}ma_deal_vendor_requests` (`vendor_email`, `status`, `created_at`)
COMMENT 'Optimizes vendor request history queries';

-- =====================================================
-- TABLE: Vendor Messages
-- Description: Communication between vendors and agents
-- =====================================================
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_vendor_messages` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_request_id` BIGINT(20) UNSIGNED NOT NULL,
  `transaction_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Denormalized for performance',
  `sender_type` ENUM('vendor', 'agent') NOT NULL COMMENT 'Who sent the message',
  `sender_name` VARCHAR(100) NOT NULL,
  `sender_email` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Foreign keys
  CONSTRAINT `fk_vendor_messages_request`
    FOREIGN KEY (`vendor_request_id`)
    REFERENCES `{prefix}ma_deal_vendor_requests` (`id`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_vendor_messages_transaction`
    FOREIGN KEY (`transaction_id`)
    REFERENCES `{prefix}ma_deal_transactions` (`id`)
    ON DELETE CASCADE,

  -- Indexes
  INDEX `idx_vendor_messages_request` (`vendor_request_id`, `created_at`),
  INDEX `idx_vendor_messages_unread` (`vendor_request_id`, `is_read`, `created_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Communication messages between vendors and agents';

-- =====================================================
-- TABLE: Vendor Availability
-- Description: Vendor availability windows for scheduling
-- =====================================================
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_vendor_availability` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_request_id` BIGINT(20) UNSIGNED NOT NULL,
  `available_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `timezone` VARCHAR(50) DEFAULT 'America/New_York',
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Foreign key
  CONSTRAINT `fk_vendor_availability_request`
    FOREIGN KEY (`vendor_request_id`)
    REFERENCES `{prefix}ma_deal_vendor_requests` (`id`)
    ON DELETE CASCADE,

  -- Indexes
  INDEX `idx_vendor_availability_request` (`vendor_request_id`, `available_date`),
  INDEX `idx_vendor_availability_date` (`available_date`, `start_time`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Vendor availability windows for appointment scheduling';

-- =====================================================
-- TABLE: Vendor Ratings
-- Description: Agent ratings for vendor performance
-- =====================================================
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_vendor_ratings` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_request_id` BIGINT(20) UNSIGNED NOT NULL,
  `transaction_id` BIGINT(20) UNSIGNED NOT NULL,
  `vendor_email` VARCHAR(255) NOT NULL COMMENT 'Denormalized for vendor profile queries',
  `rated_by_user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Agent who rated',
  `rating` TINYINT(1) UNSIGNED NOT NULL COMMENT '1-5 stars',
  `timeliness_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '1-5 stars for timeliness',
  `quality_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '1-5 stars for work quality',
  `communication_rating` TINYINT(1) UNSIGNED DEFAULT NULL COMMENT '1-5 stars for communication',
  `review` TEXT DEFAULT NULL,
  `would_recommend` TINYINT(1) DEFAULT 1 COMMENT 'Would recommend to others',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- Foreign keys
  CONSTRAINT `fk_vendor_ratings_request`
    FOREIGN KEY (`vendor_request_id`)
    REFERENCES `{prefix}ma_deal_vendor_requests` (`id`)
    ON DELETE CASCADE,

  CONSTRAINT `fk_vendor_ratings_transaction`
    FOREIGN KEY (`transaction_id`)
    REFERENCES `{prefix}ma_deal_transactions` (`id`)
    ON DELETE CASCADE,

  -- Indexes
  INDEX `idx_vendor_ratings_request` (`vendor_request_id`),
  INDEX `idx_vendor_ratings_vendor` (`vendor_email`, `created_at`),
  INDEX `idx_vendor_ratings_user` (`rated_by_user_id`, `created_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent ratings and reviews for vendor performance';

-- =====================================================
-- SUMMARY
-- =====================================================
-- Tables modified: 1 (ma_deal_vendor_requests)
-- Tables created: 3 (vendor_messages, vendor_availability, vendor_ratings)
-- Indexes added: 8
-- Foreign keys added: 6
-- New columns added: 6
--
-- FEATURES ENABLED:
-- ✓ Vendor contact information (name, company)
-- ✓ Vendor performance tracking (ratings)
-- ✓ Vendor-agent messaging system
-- ✓ Vendor availability scheduling
-- ✓ Detailed vendor ratings (timeliness, quality, communication)
-- ✓ Vendor profile aggregation (by email)
--
-- ROLLBACK: See rollback_030.sql
-- =====================================================
