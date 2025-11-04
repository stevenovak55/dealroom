-- Migration 016: Mark existing users as email verified (grandfather clause)
-- Description:
--   As of T2.1.3, email verification is now mandatory for login
--   This migration marks all existing users as verified to prevent lockout
--   New users registered after this migration will require email verification
-- Date: 2025-11-02

-- Mark all existing custom users as verified
-- Only update users who are NOT already verified to avoid unnecessary updates
UPDATE `{prefix}ma_deal_custom_users`
SET
  `email_verified` = 1,
  `email_verified_at` = CURRENT_TIMESTAMP
WHERE `email_verified` = 0;

-- Note: WordPress users are handled separately in wp_users table
-- They don't use our custom email verification system
