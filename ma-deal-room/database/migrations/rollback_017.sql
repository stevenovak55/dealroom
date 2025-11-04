-- Rollback Migration 017: Remove session enhancement columns
-- Description:
--   Removes columns added in migration 017 for session regeneration
--   and suspicious activity detection
-- Date: 2025-11-02

-- Drop the added index
DROP INDEX `idx_last_activity_tracking` ON `{prefix}ma_deal_user_sessions`;

-- Remove added columns
ALTER TABLE `{prefix}ma_deal_user_sessions`
  DROP COLUMN `invalidation_reason`,
  DROP COLUMN `last_activity_user_agent`,
  DROP COLUMN `last_activity_ip`,
  DROP COLUMN `platform`,
  DROP COLUMN `browser`,
  DROP KEY `uk_session_id`,
  DROP COLUMN `session_id`;

-- Note: This rollback is safe to run
-- Session tracking will continue with original columns
-- Existing sessions remain valid
