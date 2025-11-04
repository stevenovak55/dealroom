-- ============================================================================
-- Rollback Migration 011: User System Tables
-- ============================================================================
-- Description: Removes all user system tables and reverts modifications
-- Created: 2025-10-31
-- WARNING: This will delete ALL user data. Use with caution.
-- Recommended: Backup database before rollback
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. REVERT TABLE MODIFICATIONS
-- ============================================================================

-- Revert wp_ma_deal_events table
ALTER TABLE `wp_ma_deal_events`
  DROP COLUMN `user_type`;

-- Revert wp_ma_deal_notifications table
ALTER TABLE `wp_ma_deal_notifications`
  DROP COLUMN `user_type`;

-- Revert wp_ma_deal_parties table
ALTER TABLE `wp_ma_deal_parties`
  DROP FOREIGN KEY `fk_parties_custom_user`,
  DROP FOREIGN KEY `fk_parties_invitation`,
  DROP COLUMN `custom_user_id`,
  DROP COLUMN `wp_user_id`,
  DROP COLUMN `user_linked_at`,
  DROP COLUMN `invitation_id`;

-- ============================================================================
-- 2. DROP USER SYSTEM TABLES (in reverse dependency order)
-- ============================================================================

-- Drop tables that depend on others first
DROP TABLE IF EXISTS `wp_ma_deal_user_invitations`;
DROP TABLE IF EXISTS `wp_ma_deal_2fa_secrets`;
DROP TABLE IF EXISTS `wp_ma_deal_email_verifications`;
DROP TABLE IF EXISTS `wp_ma_deal_password_resets`;
DROP TABLE IF EXISTS `wp_ma_deal_user_sessions`;
DROP TABLE IF EXISTS `wp_ma_deal_user_roles`;
DROP TABLE IF EXISTS `wp_ma_deal_custom_users`;

SET FOREIGN_KEY_CHECKS = 1;

-- Remove migration record
DELETE FROM `wp_ma_deal_migrations` WHERE `migration_number` = '011';

-- ============================================================================
-- Rollback Complete
-- ============================================================================
-- All user system tables have been removed.
-- Table modifications have been reverted.
-- Migration tracking record deleted.
-- ============================================================================
