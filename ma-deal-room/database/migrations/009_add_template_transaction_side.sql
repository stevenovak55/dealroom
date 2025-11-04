-- ============================================================================
-- Migration 009: Add transaction_side to templates table
-- ============================================================================
-- Description: Adds transaction_side field to wp_ma_deal_templates to enable
--              filtering templates by which side of the transaction they apply to
-- Created: 2025-10-31
-- Idempotent: Yes (uses IF NOT EXISTS)
-- Rollback: See rollback_009.sql
-- ============================================================================

-- Check if this migration already applied
SET @migration_exists = (SELECT COUNT(*) FROM `{prefix}ma_deal_migrations` WHERE `migration_number` = '009');

-- Only proceed if migration not yet applied
SET @skip_migration = IF(@migration_exists > 0, 1, 0);

-- ============================================================================
-- Execute migration
-- ============================================================================

-- Add transaction_side column to templates table
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'wp_ma_deal_templates'
    AND COLUMN_NAME = 'transaction_side'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE wp_ma_deal_templates
    ADD COLUMN transaction_side ENUM(\'listing\', \'buyer\', \'both\')
    NOT NULL DEFAULT \'both\'
    COMMENT \'Which side of transaction this template applies to: listing (seller), buyer, or both\'
    AFTER property_type',
    'SELECT "Column transaction_side already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for efficient filtering
SET @index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'wp_ma_deal_templates'
    AND INDEX_NAME = 'idx_transaction_side'
);

SET @sql = IF(@index_exists = 0,
    'ALTER TABLE wp_ma_deal_templates
    ADD INDEX idx_transaction_side (transaction_side)',
    'SELECT "Index idx_transaction_side already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add composite index for property_type + transaction_side filtering
SET @composite_index_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'wp_ma_deal_templates'
    AND INDEX_NAME = 'idx_property_transaction'
);

SET @sql = IF(@composite_index_exists = 0,
    'ALTER TABLE wp_ma_deal_templates
    ADD INDEX idx_property_transaction (property_type, transaction_side)',
    'SELECT "Index idx_property_transaction already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- Update existing system templates with appropriate transaction_side values
-- ============================================================================

-- Base transaction template applies to both sides
UPDATE wp_ma_deal_templates
SET transaction_side = 'both'
WHERE is_system = TRUE
  AND (name LIKE '%Base%' OR name LIKE '%base%')
  AND transaction_side != 'both';

-- Septic templates are listing-side (seller's responsibility for Title 5)
UPDATE wp_ma_deal_templates
SET transaction_side = 'listing'
WHERE is_system = TRUE
  AND (name LIKE '%Septic%' OR name LIKE '%septic%')
  AND transaction_side != 'listing';

-- City water templates are listing-side (seller's responsibility)
UPDATE wp_ma_deal_templates
SET transaction_side = 'listing'
WHERE is_system = TRUE
  AND (name LIKE '%City Water%' OR name LIKE '%city_water%' OR name LIKE '%Water%')
  AND transaction_side != 'listing';

-- Condo templates apply to both (seller provides 6(d), buyer reviews)
UPDATE wp_ma_deal_templates
SET transaction_side = 'both'
WHERE is_system = TRUE
  AND (name LIKE '%Condo%' OR name LIKE '%condo%')
  AND transaction_side != 'both';

-- Multifamily templates apply to both
UPDATE wp_ma_deal_templates
SET transaction_side = 'both'
WHERE is_system = TRUE
  AND (name LIKE '%Multifamily%' OR name LIKE '%multifamily%' OR name LIKE '%Multi%')
  AND transaction_side != 'both';

-- ============================================================================
-- Record migration
-- ============================================================================

INSERT IGNORE INTO `{prefix}ma_deal_migrations` (`migration_number`, `migration_name`, `applied_at`, `rollback_available`)
VALUES ('009', 'Add transaction_side to templates table', NOW(), TRUE);

-- ============================================================================
-- Verify migration
-- ============================================================================

SELECT
    'Migration 009 complete' AS status,
    COUNT(*) AS template_count,
    SUM(CASE WHEN transaction_side = 'both' THEN 1 ELSE 0 END) AS both_count,
    SUM(CASE WHEN transaction_side = 'listing' THEN 1 ELSE 0 END) AS listing_count,
    SUM(CASE WHEN transaction_side = 'buyer' THEN 1 ELSE 0 END) AS buyer_count
FROM wp_ma_deal_templates
WHERE is_system = TRUE;
