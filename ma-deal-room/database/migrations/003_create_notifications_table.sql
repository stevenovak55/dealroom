-- ============================================================================
-- Migration 003: Create Notifications Table
-- ============================================================================
-- Description: Creates notifications table for in-app notifications
-- Created: 2025-10-30
-- Idempotent: Yes (CREATE TABLE IF NOT EXISTS)
-- Rollback: See rollback_003.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table: Notifications
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_notifications` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'WordPress user who receives this notification',
  `type` VARCHAR(50) NOT NULL COMMENT 'Notification type: task_assigned, document_uploaded, etc.',
  `title` VARCHAR(255) NOT NULL COMMENT 'Notification title',
  `message` TEXT NOT NULL COMMENT 'Notification message',
  `link` VARCHAR(500) DEFAULT NULL COMMENT 'Optional link to related resource',
  `entity_type` VARCHAR(50) DEFAULT NULL COMMENT 'Related entity type: transaction, task, document',
  `entity_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Related entity ID',
  `is_read` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Whether notification has been read',
  `read_at` DATETIME DEFAULT NULL COMMENT 'When notification was read',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Stores in-app notifications for users';

SET FOREIGN_KEY_CHECKS = 1;
