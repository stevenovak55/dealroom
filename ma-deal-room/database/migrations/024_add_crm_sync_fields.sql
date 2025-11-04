-- Migration: 024 - Add CRM Sync Fields to Custom Users
-- Description: Add CRM tracking fields for portal users
-- Author: AI Agent (Claude Code)
-- Date: 2025-11-03
-- Note: CRM sync fields for contacts are in migration 026 (ma_deal_contacts table)

-- Add CRM sync fields to custom users table (portal users)
-- This links portal user accounts to CRM contact records
ALTER TABLE `{prefix}ma_deal_custom_users`
    ADD COLUMN `crm_contact_id` varchar(100) DEFAULT NULL COMMENT 'CRM contact record ID (links to Salesforce/HubSpot contact)',
    ADD COLUMN `crm_provider` varchar(50) DEFAULT NULL COMMENT 'CRM provider type (salesforce/hubspot)',
    ADD INDEX `idx_user_crm_contact` (`crm_contact_id`),
    ADD INDEX `idx_user_crm_provider` (`crm_provider`);
