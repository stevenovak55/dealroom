-- Migration 015: Add file security metadata columns to documents table
-- Description:
--   Adds columns for virus scanning metadata and file integrity verification
--   - checksum: SHA-256 hash for file integrity verification
--   - scan_status: Status of virus scan (clean, infected, pending, disabled, no_scanner)
--   - scan_date: Timestamp when file was scanned
--   - scanner_used: Which scanner was used (clamav, virustotal, null)
-- Date: 2025-11-01

ALTER TABLE `{prefix}ma_deal_documents`
  ADD COLUMN `checksum` varchar(64) NULL AFTER `mime_type`,
  ADD COLUMN `scan_status` ENUM('clean', 'infected', 'pending', 'disabled', 'no_scanner') NOT NULL DEFAULT 'pending' AFTER `checksum`,
  ADD COLUMN `scan_date` datetime NULL AFTER `scan_status`,
  ADD COLUMN `scanner_used` varchar(50) NULL AFTER `scan_date`;

-- Add index on scan_status for querying infected/pending files
CREATE INDEX `idx_scan_status` ON `{prefix}ma_deal_documents` (`scan_status`);

-- Add index on checksum for duplicate detection
CREATE INDEX `idx_checksum` ON `{prefix}ma_deal_documents` (`checksum`);
