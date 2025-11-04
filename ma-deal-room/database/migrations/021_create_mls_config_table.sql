-- Migration: 021 - Create MLS Configuration Table
-- Description: Create table for storing MLS provider configuration
-- Date: 2025-11-02
-- Version: 1.0.0

-- Create MLS configuration table
CREATE TABLE IF NOT EXISTS {prefix}ma_deal_mls_config (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id BIGINT(20) UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL COMMENT 'Friendly name for this configuration',
    provider_type VARCHAR(50) NOT NULL COMMENT 'MLS provider type (rets, bridge, listhub, etc.)',
    credentials TEXT NOT NULL COMMENT 'Encrypted JSON credentials',
    settings TEXT DEFAULT NULL COMMENT 'JSON provider-specific settings',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Whether this configuration is active',
    last_sync_at DATETIME DEFAULT NULL COMMENT 'Last successful sync timestamp',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_account_provider (account_id, provider_type),
    KEY idx_active (is_active),
    KEY idx_last_sync (last_sync_at),
    KEY idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MLS provider configuration with encrypted credentials';
