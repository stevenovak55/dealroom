-- Rollback Migration 014: Create rate limits table and TOTP replay prevention
-- Description:
--   1. Drop rate limits table
--   2. Remove last_totp_timestamp column from ma_deal_2fa_secrets
-- Date: 2025-11-01

-- Remove TOTP replay prevention column
ALTER TABLE `{prefix}ma_deal_2fa_secrets`
  DROP COLUMN IF EXISTS `last_totp_timestamp`;

-- Drop rate limits table
DROP TABLE IF EXISTS `{prefix}ma_rate_limits`;
