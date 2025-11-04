-- Rollback Migration 015: Remove file security metadata columns
-- Description:
--   Removes file security columns added in migration 015
-- Date: 2025-11-01

-- Remove indexes first
DROP INDEX IF EXISTS `idx_scan_status` ON `{prefix}ma_deal_documents`;
DROP INDEX IF EXISTS `idx_checksum` ON `{prefix}ma_deal_documents`;

-- Remove columns
ALTER TABLE `{prefix}ma_deal_documents`
  DROP COLUMN IF EXISTS `scanner_used`,
  DROP COLUMN IF EXISTS `scan_date`,
  DROP COLUMN IF EXISTS `scan_status`,
  DROP COLUMN IF EXISTS `checksum`;
