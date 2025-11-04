-- Migration: Create rate limits table and add TOTP replay prevention
-- Description:
--   1. Add table to track API rate limiting across all endpoints
--   2. Add last_totp_timestamp column to ma_users for TOTP replay prevention
-- Date: 2025-11-01

CREATE TABLE IF NOT EXISTS `{prefix}ma_rate_limits` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `rate_key` varchar(255) NOT NULL COMMENT 'Unique key for rate limit (user/IP + endpoint + method)',
  `ip_address` varchar(45) NOT NULL COMMENT 'Client IP address',
  `user_id` bigint(20) UNSIGNED NULL DEFAULT NULL COMMENT 'User ID if authenticated',
  `endpoint` varchar(255) NOT NULL COMMENT 'API endpoint',
  `method` varchar(10) NOT NULL COMMENT 'HTTP method (GET, POST, etc.)',
  `created_at` datetime NOT NULL COMMENT 'Request timestamp',
  PRIMARY KEY (`id`),
  KEY `idx_rate_key_created` (`rate_key`, `created_at`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API rate limiting records';

-- Add TOTP replay prevention column to ma_deal_2fa_secrets table
ALTER TABLE `{prefix}ma_deal_2fa_secrets`
  ADD COLUMN `last_totp_timestamp` bigint(20) NULL DEFAULT NULL COMMENT 'Last TOTP verification timestamp (prevents replay attacks)' AFTER `backup_codes`;
