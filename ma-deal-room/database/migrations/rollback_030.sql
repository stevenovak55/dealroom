-- =====================================================
-- Rollback Migration 030: Enhanced Vendor Portal
-- Description: Removes vendor portal enhancement tables and columns
-- Author: Claude Code
-- Date: 2025-11-04
-- =====================================================

-- Drop new tables (in reverse dependency order)
DROP TABLE IF EXISTS `{prefix}ma_deal_vendor_ratings`;
DROP TABLE IF EXISTS `{prefix}ma_deal_vendor_availability`;
DROP TABLE IF EXISTS `{prefix}ma_deal_vendor_messages`;

-- Remove index from vendor_requests
DROP INDEX IF EXISTS `idx_vendor_requests_vendor_email` ON `{prefix}ma_deal_vendor_requests`;

-- Remove new columns from vendor_requests table
ALTER TABLE `{prefix}ma_deal_vendor_requests`
DROP COLUMN IF EXISTS `confirmation_sent_at`,
DROP COLUMN IF EXISTS `completion_date`,
DROP COLUMN IF EXISTS `average_rating`,
DROP COLUMN IF EXISTS `vendor_company`,
DROP COLUMN IF EXISTS `vendor_name`;

-- Rollback complete
