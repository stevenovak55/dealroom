-- Migration: 025 - Add CRM Deal Sync Fields to Transactions
-- Description: Add tracking fields for CRM deal/opportunity synchronization to transactions table
-- Author: AI Agent (Claude Code)
-- Date: 2025-11-03

-- Add CRM sync fields to transactions table
ALTER TABLE `{prefix}ma_deal_transactions`
    ADD COLUMN `crm_opportunity_id` varchar(100) DEFAULT NULL COMMENT 'Salesforce Opportunity ID',
    ADD COLUMN `crm_deal_id` varchar(100) DEFAULT NULL COMMENT 'HubSpot Deal ID',
    ADD COLUMN `crm_provider` varchar(50) DEFAULT NULL COMMENT 'CRM provider type (salesforce/hubspot)',
    ADD COLUMN `crm_last_sync` datetime DEFAULT NULL COMMENT 'Last successful sync timestamp',
    ADD COLUMN `crm_sync_status` varchar(20) DEFAULT NULL COMMENT 'Sync status (synced/pending/error)',
    ADD COLUMN `crm_stage_mapping` text DEFAULT NULL COMMENT 'JSON stage mapping configuration',
    ADD COLUMN `crm_url` varchar(500) DEFAULT NULL COMMENT 'Direct URL to CRM record',
    ADD INDEX `idx_crm_opportunity_id` (`crm_opportunity_id`),
    ADD INDEX `idx_crm_deal_id` (`crm_deal_id`),
    ADD INDEX `idx_crm_provider` (`crm_provider`),
    ADD INDEX `idx_crm_sync_status` (`crm_sync_status`),
    ADD INDEX `idx_crm_last_sync` (`crm_last_sync`),
    ADD UNIQUE INDEX `idx_crm_opportunity_unique` (`crm_provider`, `crm_opportunity_id`),
    ADD UNIQUE INDEX `idx_crm_deal_unique` (`crm_provider`, `crm_deal_id`);
