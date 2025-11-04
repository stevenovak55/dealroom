-- Rollback Migration 016: Unverify users that were grandfathered in
-- Description:
--   Reverts all users that were automatically verified by migration 016
--   back to unverified status
-- Date: 2025-11-02
-- WARNING: This rollback is potentially destructive as it cannot distinguish
--          between users who were grandfathered vs. users who manually verified

-- Note: This rollback cannot safely be performed as we cannot distinguish
-- between users who were auto-verified vs. manually verified after the migration
-- The safest approach is to not rollback this migration

-- If you must rollback, you would need to:
-- 1. Have a backup of the database from before migration 016
-- 2. Restore email_verified and email_verified_at from that backup
-- 3. Or manually unverify specific users if needed

-- Placeholder query (does nothing):
SELECT 'Migration 016 rollback not supported - see comments for details' as message;
