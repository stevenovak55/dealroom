-- Migration: Add property details fields
-- Description: Add fields for bedrooms, bathrooms, square footage, list price, and accepted offer price

-- Add new property detail fields
ALTER TABLE {prefix}ma_deal_transactions
ADD COLUMN list_price DECIMAL(12,2) NULL COMMENT 'Original listing price' AFTER sale_price,
ADD COLUMN accepted_offer_price DECIMAL(12,2) NULL COMMENT 'Accepted offer amount' AFTER list_price,
ADD COLUMN bedrooms DECIMAL(3,1) NULL COMMENT 'Number of bedrooms (supports 2.5 format)' AFTER accepted_offer_price,
ADD COLUMN bathrooms DECIMAL(3,1) NULL COMMENT 'Number of bathrooms (supports 2.5 format)' AFTER bedrooms,
ADD COLUMN square_feet INT UNSIGNED NULL COMMENT 'Property square footage' AFTER bathrooms,
ADD COLUMN lot_size DECIMAL(10,2) NULL COMMENT 'Lot size in acres' AFTER square_feet,
ADD COLUMN parking_spaces TINYINT UNSIGNED NULL COMMENT 'Number of parking spaces' AFTER lot_size;

-- Add index for common queries
CREATE INDEX idx_bedrooms_bathrooms ON {prefix}ma_deal_transactions(bedrooms, bathrooms);
CREATE INDEX idx_price_range ON {prefix}ma_deal_transactions(list_price, accepted_offer_price);
