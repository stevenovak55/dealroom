-- Migration 022: Add MLS number field to transactions table
-- Description: Already included in migration 001, this migration is a no-op safety check
-- Note: mls_number column and indexes are created in migration 001

-- This migration is kept for backwards compatibility but does nothing
-- since the mls_number column already exists in the initial schema

SET FOREIGN_KEY_CHECKS = 0;

-- Check and only add if it doesn't exist (safety check for old installs)
SET @column_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{prefix}ma_deal_transactions' AND COLUMN_NAME = 'mls_number');

-- If column doesn't exist (shouldn't happen in normal flow), add it
-- This is just for safety with old installations that might not have migration 001
-- SET @sql = IF(@column_exists = 0, CONCAT('ALTER TABLE `{prefix}ma_deal_transactions` ADD COLUMN `mls_number` VARCHAR(50) DEFAULT NULL COMMENT \"MLS listing number if imported from MLS\"'), 'SELECT 1 AS dummy');
-- PREPARE stmt FROM @sql;
-- EXECUTE stmt;
-- DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
