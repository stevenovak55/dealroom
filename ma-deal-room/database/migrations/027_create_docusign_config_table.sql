-- Create DocuSign configuration table
-- Migration: 024_create_docusign_config_table
-- Description: Store DocuSign API configuration with encrypted credentials

CREATE TABLE IF NOT EXISTS {prefix}ma_deal_docusign_config (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    integration_key VARCHAR(255) NOT NULL COMMENT 'DocuSign Integration Key (OAuth Client ID)',
    user_id VARCHAR(255) NOT NULL COMMENT 'DocuSign User ID (GUID)',
    private_key TEXT NOT NULL COMMENT 'RSA Private Key (encrypted)',
    account_id_docusign VARCHAR(255) NOT NULL COMMENT 'DocuSign Account ID',
    environment VARCHAR(20) NOT NULL DEFAULT 'sandbox' COMMENT 'Environment: production or sandbox',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY idx_account_id (account_id),
    KEY idx_is_active (is_active),
    KEY idx_environment (environment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
