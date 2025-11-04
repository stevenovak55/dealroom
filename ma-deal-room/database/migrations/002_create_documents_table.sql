-- ============================================================================
-- Migration 002: Create Documents Table
-- ============================================================================
-- Description: Creates documents table for file management
-- Created: 2025-10-30
-- Idempotent: Yes (CREATE TABLE IF NOT EXISTS)
-- Rollback: See rollback_002.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table: Documents
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_documents` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `transaction_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Transaction this document belongs to',
  `account_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Account for multi-tenancy',
  `uploaded_by_user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'WordPress user who uploaded',
  `file_name` VARCHAR(255) NOT NULL COMMENT 'Original file name',
  `file_path` VARCHAR(500) NOT NULL COMMENT 'Path to file in uploads directory',
  `file_size` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'File size in bytes',
  `mime_type` VARCHAR(100) NOT NULL COMMENT 'MIME type (e.g., application/pdf)',
  `document_type` VARCHAR(50) DEFAULT NULL COMMENT 'Category: contract, inspection, disclosure, etc.',
  `title` VARCHAR(255) DEFAULT NULL COMMENT 'Optional display title',
  `description` TEXT DEFAULT NULL COMMENT 'Optional description',
  `metadata` JSON DEFAULT NULL COMMENT 'Additional metadata (OCR text, tags, etc.)',
  `is_public` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether document is accessible via public link',
  `public_token` VARCHAR(64) DEFAULT NULL COMMENT 'Token for public access if is_public=true',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_account` (`account_id`),
  KEY `idx_uploaded_by` (`uploaded_by_user_id`),
  KEY `idx_document_type` (`document_type`),
  KEY `idx_public_token` (`public_token`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_documents_transaction`
    FOREIGN KEY (`transaction_id`)
    REFERENCES `{prefix}ma_deal_transactions` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_documents_account`
    FOREIGN KEY (`account_id`)
    REFERENCES `{prefix}ma_deal_accounts` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores document metadata and file references for transactions';

SET FOREIGN_KEY_CHECKS = 1;
