-- ============================================================================
-- Rollback Migration 001: Initial Schema
-- ============================================================================
-- Description: Removes all 8 core tables for MA Deal Room plugin
-- Created: 2025-10-30
-- WARNING: This will delete ALL data. Use with caution.
-- Recommended: Backup database before rollback
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse dependency order
-- (child tables first, parent tables last)

-- Table 8: Vendor Requests (references tasks, transactions, parties)
DROP TABLE IF EXISTS `wp_ma_deal_vendor_requests`;

-- Table 7: Reminders (references tasks, transactions)
DROP TABLE IF EXISTS `wp_ma_deal_reminders`;

-- Table 6: Events (references accounts, transactions)
DROP TABLE IF EXISTS `wp_ma_deal_events`;

-- Table 5: Tasks (references transactions, templates, parties)
DROP TABLE IF EXISTS `wp_ma_deal_tasks`;

-- Table 4: Templates (references accounts)
DROP TABLE IF EXISTS `wp_ma_deal_templates`;

-- Table 3: Parties (references transactions)
DROP TABLE IF EXISTS `wp_ma_deal_parties`;

-- Table 2: Transactions (references accounts)
DROP TABLE IF EXISTS `wp_ma_deal_transactions`;

-- Table 1: Accounts (no dependencies)
DROP TABLE IF EXISTS `wp_ma_deal_accounts`;

SET FOREIGN_KEY_CHECKS = 1;

-- Remove migration record
DELETE FROM `wp_ma_deal_migrations` WHERE `migration_number` = '001';

-- ============================================================================
-- Rollback Complete
-- ============================================================================
-- All MA Deal Room tables have been removed.
-- Migration tracking record deleted.
-- ============================================================================
