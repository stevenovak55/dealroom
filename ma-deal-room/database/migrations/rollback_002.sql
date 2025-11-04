-- ============================================================================
-- Rollback Migration 002: Drop Documents Table
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `wp_ma_deal_documents`;

SET FOREIGN_KEY_CHECKS = 1;
