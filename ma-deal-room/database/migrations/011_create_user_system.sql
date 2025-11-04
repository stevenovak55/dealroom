-- Migration 011: Create User System Tables
-- Description: Comprehensive user authentication and authorization system
-- Supports: Custom users, WordPress users, roles, sessions, 2FA, invitations
-- Date: 2025-10-31

-- ============================================================================
-- 1. CUSTOM USERS TABLE
-- ============================================================================
-- Stores client accounts (buyers, sellers, vendors) separate from WordPress users
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_custom_users` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL COMMENT 'Unique email address for login',
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hash of password',
  `first_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'suspended', 'pending_verification') NOT NULL DEFAULT 'pending_verification',
  `email_verified` BOOLEAN NOT NULL DEFAULT FALSE,
  `email_verified_at` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6 address',
  `failed_login_attempts` INT(11) NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL COMMENT 'Account lockout expiration',
  `password_changed_at` DATETIME DEFAULT NULL,
  `must_change_password` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Force password change on next login',
  `metadata` JSON DEFAULT NULL COMMENT 'Extensible storage for custom fields',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL COMMENT 'Soft delete timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_email_verified` (`email_verified`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Custom user accounts for clients (buyers, sellers, vendors)';

-- ============================================================================
-- 2. USER ROLES TABLE
-- ============================================================================
-- Maps users to roles with flexible multi-role support
-- Users can have different roles in different contexts (e.g., buyer in one transaction, seller in another)
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_user_roles` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'User ID (custom or WordPress)',
  `user_type` ENUM('custom', 'wordpress') NOT NULL COMMENT 'Type of user account',
  `role_type` VARCHAR(50) NOT NULL COMMENT 'Role: buyer, seller, agent, attorney, broker, lender, inspector, vendor, title_company, escrow',
  `account_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Agency/brokerage account for multi-tenancy',
  `transaction_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Transaction context for role (NULL = global role)',
  `is_primary` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Primary role for the user',
  `permissions` JSON DEFAULT NULL COMMENT 'Role-specific permission overrides',
  `metadata` JSON DEFAULT NULL COMMENT 'Additional role-specific data (license number, bar number, etc.)',
  `assigned_by_user_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'User who assigned this role',
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL COMMENT 'Role expiration (for temporary access)',
  `revoked_at` DATETIME DEFAULT NULL COMMENT 'Role revocation timestamp',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_role_context` (`user_id`, `user_type`, `role_type`, `transaction_id`, `account_id`),
  KEY `idx_user` (`user_id`, `user_type`),
  KEY `idx_role_type` (`role_type`),
  KEY `idx_account` (`account_id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_is_primary` (`is_primary`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_user_roles_account` FOREIGN KEY (`account_id`) REFERENCES `{prefix}ma_deal_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_roles_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `{prefix}ma_deal_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='User role assignments with multi-role and context support';

-- ============================================================================
-- 3. USER SESSIONS TABLE
-- ============================================================================
-- Stores refresh tokens and session information for custom users
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_user_sessions` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_type` ENUM('custom', 'wordpress') NOT NULL,
  `refresh_token_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed refresh token',
  `device_name` VARCHAR(255) DEFAULT NULL COMMENT 'Device identifier (browser, mobile app)',
  `device_type` VARCHAR(50) DEFAULT NULL COMMENT 'desktop, mobile, tablet',
  `ip_address` VARCHAR(45) NOT NULL COMMENT 'IP address of session',
  `user_agent` VARCHAR(500) DEFAULT NULL COMMENT 'Browser user agent string',
  `location` VARCHAR(255) DEFAULT NULL COMMENT 'Approximate location (city, country)',
  `last_used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL COMMENT 'Refresh token expiration',
  `revoked_at` DATETIME DEFAULT NULL COMMENT 'Manual session termination',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_refresh_token` (`refresh_token_hash`),
  KEY `idx_user` (`user_id`, `user_type`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_revoked_at` (`revoked_at`),
  KEY `idx_last_used_at` (`last_used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='JWT refresh token storage and session management';

-- ============================================================================
-- 4. PASSWORD RESETS TABLE
-- ============================================================================
-- Stores password reset tokens
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_password_resets` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL COMMENT 'Email address for reset',
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed reset token',
  `user_type` ENUM('custom', 'wordpress') NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP that requested reset',
  `expires_at` DATETIME NOT NULL COMMENT 'Token expiration (typically 1 hour)',
  `used_at` DATETIME DEFAULT NULL COMMENT 'When token was used',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token_hash`),
  KEY `idx_email` (`email`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_used_at` (`used_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Password reset tokens';

-- ============================================================================
-- 5. EMAIL VERIFICATIONS TABLE
-- ============================================================================
-- Stores email verification tokens for new accounts
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_email_verifications` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_type` ENUM('custom', 'wordpress') NOT NULL,
  `email` VARCHAR(255) NOT NULL COMMENT 'Email to verify',
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'Hashed verification token',
  `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IP that requested verification',
  `expires_at` DATETIME NOT NULL COMMENT 'Token expiration (typically 24 hours)',
  `verified_at` DATETIME DEFAULT NULL COMMENT 'When email was verified',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token_hash`),
  KEY `idx_user` (`user_id`, `user_type`),
  KEY `idx_email` (`email`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_verified_at` (`verified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Email verification tokens';

-- ============================================================================
-- 6. TWO-FACTOR AUTH SECRETS TABLE
-- ============================================================================
-- Stores TOTP secrets and backup codes for 2FA
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_2fa_secrets` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_type` ENUM('custom', 'wordpress') NOT NULL,
  `secret` VARCHAR(255) NOT NULL COMMENT 'Encrypted TOTP secret',
  `backup_codes` JSON DEFAULT NULL COMMENT 'Array of hashed backup codes',
  `enabled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` DATETIME DEFAULT NULL COMMENT 'Last successful 2FA verification',
  `disabled_at` DATETIME DEFAULT NULL COMMENT 'When 2FA was disabled',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user` (`user_id`, `user_type`),
  KEY `idx_enabled_at` (`enabled_at`),
  KEY `idx_disabled_at` (`disabled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Two-factor authentication secrets';

-- ============================================================================
-- 7. USER INVITATIONS TABLE
-- ============================================================================
-- Stores role-based invitations to join transactions
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_user_invitations` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL COMMENT 'Email address to invite',
  `role_type` VARCHAR(50) NOT NULL COMMENT 'Role being offered: buyer, seller, attorney, etc.',
  `invited_by_user_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'User who sent invitation',
  `invited_by_user_type` ENUM('custom', 'wordpress') NOT NULL,
  `account_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'Agency/brokerage account',
  `transaction_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Transaction to join (NULL for general agency invite)',
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'Unique invitation token hash',
  `message` TEXT DEFAULT NULL COMMENT 'Personal message from inviter',
  `permissions` JSON DEFAULT NULL COMMENT 'Specific permissions for this role',
  `metadata` JSON DEFAULT NULL COMMENT 'Additional invitation data',
  `expires_at` DATETIME NOT NULL COMMENT 'Invitation expiration (typically 7 days)',
  `accepted_at` DATETIME DEFAULT NULL COMMENT 'When invitation was accepted',
  `accepted_by_user_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'User who accepted (may be new user)',
  `declined_at` DATETIME DEFAULT NULL COMMENT 'When invitation was declined',
  `cancelled_at` DATETIME DEFAULT NULL COMMENT 'When invitation was cancelled by sender',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token_hash`),
  KEY `idx_email` (`email`),
  KEY `idx_inviter` (`invited_by_user_id`, `invited_by_user_type`),
  KEY `idx_account` (`account_id`),
  KEY `idx_transaction` (`transaction_id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_status` (`accepted_at`, `declined_at`, `cancelled_at`),
  CONSTRAINT `fk_invitations_account` FOREIGN KEY (`account_id`) REFERENCES `{prefix}ma_deal_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invitations_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `{prefix}ma_deal_transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Role-based user invitations';

-- ============================================================================
-- 8. ALTER PARTIES TABLE - ADD USER LINKAGE
-- ============================================================================
-- Link party records to user accounts (WordPress or custom)
ALTER TABLE `{prefix}ma_deal_parties`
  ADD COLUMN `custom_user_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Link to custom user account' AFTER `id`,
  ADD COLUMN `{prefix}user_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Link to WordPress user account' AFTER `custom_user_id`,
  ADD COLUMN `user_linked_at` DATETIME DEFAULT NULL COMMENT 'When party was linked to user account' AFTER `{prefix}user_id`,
  ADD COLUMN `invitation_id` BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Invitation that created this link' AFTER `user_linked_at`,
  ADD KEY `idx_custom_user` (`custom_user_id`),
  ADD KEY `idx_wp_user` (`{prefix}user_id`),
  ADD KEY `idx_invitation` (`invitation_id`),
  ADD CONSTRAINT `fk_parties_custom_user` FOREIGN KEY (`custom_user_id`) REFERENCES `{prefix}ma_deal_custom_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_parties_invitation` FOREIGN KEY (`invitation_id`) REFERENCES `{prefix}ma_deal_user_invitations` (`id`) ON DELETE SET NULL;

-- Note: No FK to WordPress users table since it's managed by WordPress core

-- ============================================================================
-- 9. ALTER NOTIFICATIONS TABLE - SUPPORT CUSTOM USERS
-- ============================================================================
-- Allow notifications for both WordPress and custom users
ALTER TABLE `{prefix}ma_deal_notifications`
  ADD COLUMN `user_type` ENUM('wordpress', 'custom') NOT NULL DEFAULT 'wordpress' COMMENT 'Type of user' AFTER `user_id`,
  ADD KEY `idx_user_type` (`user_id`, `user_type`);

-- Update existing records to have user_type = 'wordpress'
UPDATE `{prefix}ma_deal_notifications` SET `user_type` = 'wordpress' WHERE `user_type` IS NULL;

-- ============================================================================
-- 10. ALTER EVENTS TABLE - SUPPORT CUSTOM USERS
-- ============================================================================
-- Track audit events for both WordPress and custom users
ALTER TABLE `{prefix}ma_deal_events`
  ADD COLUMN `user_type` ENUM('wordpress', 'custom') DEFAULT NULL COMMENT 'Type of user who triggered event' AFTER `user_id`,
  ADD KEY `idx_user_type` (`user_id`, `user_type`);

-- Update existing records to have user_type = 'wordpress'
UPDATE `{prefix}ma_deal_events` SET `user_type` = 'wordpress' WHERE `user_id` IS NOT NULL AND `user_type` IS NULL;

-- ============================================================================
-- 11. CREATE INDEXES FOR PERFORMANCE
-- ============================================================================

-- Composite index for common user lookup
CREATE INDEX `idx_custom_users_email_status` ON `{prefix}ma_deal_custom_users` (`email`, `status`);

-- Composite index for active sessions lookup
CREATE INDEX `idx_sessions_active` ON `{prefix}ma_deal_user_sessions` (`user_id`, `user_type`, `expires_at`, `revoked_at`);

-- Composite index for pending invitations
CREATE INDEX `idx_invitations_pending` ON `{prefix}ma_deal_user_invitations` (`email`, `expires_at`, `accepted_at`, `cancelled_at`);

-- Composite index for role lookups
CREATE INDEX `idx_user_roles_active` ON `{prefix}ma_deal_user_roles` (`user_id`, `user_type`, `role_type`, `revoked_at`);

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================
-- Tables created: 7 new tables
-- Tables modified: 3 existing tables (parties, notifications, events)
-- Indexes created: Multiple performance indexes
-- Foreign keys: 5 constraints for referential integrity
--
-- Next steps:
-- 1. Create UserRoles.php for WordPress capabilities
-- 2. Create Model classes for all new tables
-- 3. Create Repository classes for database operations
-- 4. Create Service classes for business logic
-- ============================================================================
