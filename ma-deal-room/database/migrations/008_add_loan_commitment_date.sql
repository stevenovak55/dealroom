-- Migration: Add loan_commitment_date column to transactions table
-- Version: 008
-- Description: Add missing loan_commitment_date column that is required for the MA transaction workflow

ALTER TABLE `{prefix}ma_deal_transactions`
ADD COLUMN `loan_commitment_date` DATE NULL AFTER `ps_agreement_date`;
