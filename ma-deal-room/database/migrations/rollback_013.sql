-- =====================================================
-- Rollback Migration: 013 - Remove Performance Indexes
-- Description: Remove all performance indexes added in migration 013
-- Author: Claude Code
-- Date: 2025-11-01
-- =====================================================

-- TRANSACTION INDEXES
DROP INDEX IF EXISTS `idx_transactions_template_status_closing` ON `wp_ma_deal_transactions`;
DROP INDEX IF EXISTS `idx_transactions_agent_status_closing` ON `wp_ma_deal_transactions`;
DROP INDEX IF EXISTS `idx_transactions_location_status` ON `wp_ma_deal_transactions`;
DROP INDEX IF EXISTS `idx_transactions_closing_status` ON `wp_ma_deal_transactions`;

-- TASK INDEXES
DROP INDEX IF EXISTS `idx_tasks_status_overdue` ON `wp_ma_deal_tasks`;
DROP INDEX IF EXISTS `idx_tasks_assigned_status_due` ON `wp_ma_deal_tasks`;
DROP INDEX IF EXISTS `idx_tasks_transaction_status_completed` ON `wp_ma_deal_tasks`;

-- TEMPLATE INDEXES
DROP INDEX IF EXISTS `idx_templates_active_system_created` ON `wp_ma_deal_templates`;
DROP INDEX IF EXISTS `idx_templates_account_active_created` ON `wp_ma_deal_templates`;
DROP INDEX IF EXISTS `idx_templates_property_active` ON `wp_ma_deal_templates`;

-- NOTIFICATION INDEXES
DROP INDEX IF EXISTS `idx_notifications_read_cleanup` ON `wp_ma_deal_notifications`;
DROP INDEX IF EXISTS `idx_notifications_recipient_read_created` ON `wp_ma_deal_notifications`;
DROP INDEX IF EXISTS `idx_notifications_transaction_type` ON `wp_ma_deal_notifications`;

-- PARTY INDEXES
DROP INDEX IF EXISTS `idx_parties_transaction_role_email` ON `wp_ma_deal_parties`;
DROP INDEX IF EXISTS `idx_parties_users` ON `wp_ma_deal_parties`;

-- DOCUMENT INDEXES
DROP INDEX IF EXISTS `idx_documents_transaction_status_uploaded` ON `wp_ma_deal_documents`;
DROP INDEX IF EXISTS `idx_documents_transaction_type` ON `wp_ma_deal_documents`;

-- REMINDER INDEXES
DROP INDEX IF EXISTS `idx_reminders_status_scheduled` ON `wp_ma_deal_reminders`;
DROP INDEX IF EXISTS `idx_reminders_task_status` ON `wp_ma_deal_reminders`;

-- CUSTOM USER INDEXES
DROP INDEX IF EXISTS `idx_custom_users_status_deleted_created` ON `wp_ma_deal_custom_users`;
DROP INDEX IF EXISTS `idx_custom_users_verified_deleted` ON `wp_ma_deal_custom_users`;

-- USER SESSION INDEXES
DROP INDEX IF EXISTS `idx_sessions_expires_revoked` ON `wp_ma_deal_user_sessions`;

-- ACCOUNT INDEXES
DROP INDEX IF EXISTS `idx_accounts_subscription_status_expires` ON `wp_ma_deal_accounts`;
DROP INDEX IF EXISTS `idx_accounts_owner_status` ON `wp_ma_deal_accounts`;

-- USER INVITATION INDEXES
DROP INDEX IF EXISTS `idx_invitations_email_expires_accepted` ON `wp_ma_deal_user_invitations`;

-- TASK DEFINITION INDEXES
DROP INDEX IF EXISTS `idx_task_definitions_category_account_title` ON `wp_ma_deal_task_definitions`;
DROP INDEX IF EXISTS `idx_task_definitions_system_account` ON `wp_ma_deal_task_definitions`;

-- VENDOR REQUEST INDEXES
DROP INDEX IF EXISTS `idx_vendor_requests_transaction_status_created` ON `wp_ma_deal_vendor_requests`;

-- EVENT LOG INDEXES
DROP INDEX IF EXISTS `idx_events_entity_created` ON `wp_ma_deal_events`;

-- Rollback complete
