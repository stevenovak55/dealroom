-- Create DocuSign webhook log table
-- Migration: 026_create_docusign_webhook_log_table
-- Description: Log DocuSign Connect webhook events for debugging and audit

CREATE TABLE IF NOT EXISTS {prefix}ma_deal_docusign_webhook_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    envelope_id VARCHAR(255) NOT NULL COMMENT 'DocuSign Envelope ID',
    event_type VARCHAR(100) NOT NULL COMMENT 'Webhook event type: sent, delivered, signed, completed, declined, voided',
    payload JSON NOT NULL COMMENT 'Full webhook payload',
    processed_at DATETIME NULL COMMENT 'When the webhook was processed',
    processing_status VARCHAR(50) DEFAULT 'pending' COMMENT 'processing status: pending, processed, failed',
    error_message TEXT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_envelope_id (envelope_id),
    KEY idx_event_type (event_type),
    KEY idx_processed_at (processed_at),
    KEY idx_processing_status (processing_status),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
