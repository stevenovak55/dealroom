-- Create DocuSign envelopes tracking table
-- Migration: 025_create_docusign_envelopes_table
-- Description: Track DocuSign envelopes and their status

CREATE TABLE IF NOT EXISTS {prefix}ma_deal_docusign_envelopes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id BIGINT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED NOT NULL,
    envelope_id VARCHAR(255) NOT NULL COMMENT 'DocuSign Envelope ID (GUID)',
    status VARCHAR(50) NOT NULL DEFAULT 'created' COMMENT 'Envelope status: created, sent, delivered, signed, completed, declined, voided',
    subject VARCHAR(255) NOT NULL,
    message TEXT NULL,
    recipients JSON NULL COMMENT 'Recipient information',
    documents JSON NULL COMMENT 'Document information',
    sent_at DATETIME NULL,
    completed_at DATETIME NULL,
    voided_at DATETIME NULL,
    voided_reason TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY idx_envelope_id (envelope_id),
    KEY idx_account_id (account_id),
    KEY idx_transaction_id (transaction_id),
    KEY idx_status (status),
    KEY idx_sent_at (sent_at),
    KEY idx_completed_at (completed_at),
    CONSTRAINT fk_docusign_envelope_transaction
        FOREIGN KEY (transaction_id)
        REFERENCES {prefix}ma_deal_transactions(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
