-- Migration 017: Enhance user sessions table for session regeneration and suspicious activity detection
-- Description:
--   Adds new fields to wp_ma_deal_user_sessions table for:
--   - Session ID tracking (separate from primary key)
--   - Browser and platform detection
--   - Last activity tracking (IP and user agent for suspicious activity detection)
--   - Invalidation reason tracking
-- Date: 2025-11-02
-- Implements: T2.1.4 Session Regeneration on Login

-- Add session_id column (unique identifier for sessions, used in JWT token)
ALTER TABLE `{prefix}ma_deal_user_sessions`
  ADD COLUMN `session_id` varchar(64) NULL AFTER `id`,
  ADD UNIQUE KEY `uk_session_id` (`session_id`);

-- Add browser and platform columns for better device tracking
ALTER TABLE `{prefix}ma_deal_user_sessions`
  ADD COLUMN `browser` varchar(50) NULL AFTER `device_type`,
  ADD COLUMN `platform` varchar(50) NULL COMMENT 'Windows, macOS, Linux, iOS, Android' AFTER `browser`;

-- Add last activity tracking for suspicious activity detection
ALTER TABLE `{prefix}ma_deal_user_sessions`
  ADD COLUMN `last_activity_ip` varchar(45) NOT NULL AFTER `ip_address`,
  ADD COLUMN `last_activity_user_agent` varchar(500) NULL AFTER `last_activity_ip`;

-- Add invalidation reason for audit trail
ALTER TABLE `{prefix}ma_deal_user_sessions`
  ADD COLUMN `invalidation_reason` varchar(100) NULL COMMENT 'logout, password_change, suspicious_activity, manual_revoke, expired' AFTER `revoked_at`;

-- Add index for suspicious activity queries
CREATE INDEX `idx_last_activity_tracking` ON `{prefix}ma_deal_user_sessions` (`user_id`, `user_type`, `last_used_at`, `revoked_at`);

-- Initialize new columns for existing rows
UPDATE `{prefix}ma_deal_user_sessions`
SET
  `last_activity_ip` = `ip_address`,
  `last_activity_user_agent` = `user_agent`
WHERE `last_activity_ip` IS NULL OR `last_activity_ip` = '';

-- Generate session_id for existing sessions (NULL is acceptable for old sessions)
-- New sessions will have session_id populated on creation
