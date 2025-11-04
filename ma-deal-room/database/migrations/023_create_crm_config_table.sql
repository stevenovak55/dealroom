-- Migration: 023 - Create CRM Configuration Table
-- Description: Store CRM integration configurations (Salesforce, HubSpot, etc.) with encrypted credentials
-- Author: AI Agent (Claude Code)
-- Date: 2025-11-03

CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_crm_config` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` bigint(20) UNSIGNED NOT NULL,
    `provider_type` varchar(50) NOT NULL COMMENT 'salesforce, hubspot, etc.',
    `credentials` text NOT NULL COMMENT 'Encrypted JSON containing OAuth tokens and credentials',
    `instance_url` varchar(255) DEFAULT NULL COMMENT 'Salesforce instance URL or API base URL',
    `refresh_token` text DEFAULT NULL COMMENT 'OAuth refresh token (encrypted)',
    `sync_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Enable/disable sync',
    `sync_contacts` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Sync contacts',
    `sync_deals` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Sync deals/opportunities',
    `sync_activities` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Sync activities to timeline',
    `field_mapping` text DEFAULT NULL COMMENT 'JSON field mapping configuration',
    `stage_mapping` text DEFAULT NULL COMMENT 'JSON stage/status mapping',
    `sync_direction` varchar(20) NOT NULL DEFAULT 'bidirectional' COMMENT 'oneway_to_crm, oneway_from_crm, bidirectional',
    `conflict_resolution` varchar(20) NOT NULL DEFAULT 'last_write_wins' COMMENT 'last_write_wins, manual, crm_wins, dealroom_wins',
    `last_sync_at` datetime DEFAULT NULL COMMENT 'Timestamp of last successful sync',
    `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Active status',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_account_id` (`account_id`),
    KEY `idx_provider_type` (`provider_type`),
    KEY `idx_active` (`is_active`),
    KEY `idx_sync_enabled` (`sync_enabled`),
    KEY `idx_last_sync` (`last_sync_at`),
    UNIQUE KEY `idx_account_provider` (`account_id`, `provider_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='CRM integration configurations with OAuth credentials';
