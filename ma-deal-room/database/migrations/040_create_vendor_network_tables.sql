-- ============================================================================
-- Vendor Network & Directory Tables
-- ============================================================================
-- Creates comprehensive vendor management system with profiles, ratings,
-- categories, and preferred vendor lists
-- ============================================================================

-- Vendor Profiles Table
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_vendor_profiles` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `company` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `state` VARCHAR(2) DEFAULT NULL,
    `zip` VARCHAR(10) DEFAULT NULL,
    `license_number` VARCHAR(100) DEFAULT NULL,
    `license_state` VARCHAR(2) DEFAULT NULL,
    `insurance_expires` DATE DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `profile_photo` VARCHAR(255) DEFAULT NULL,
    `years_experience` INT(11) DEFAULT NULL,
    `service_areas` JSON DEFAULT NULL COMMENT 'Array of zip codes or cities',
    `certifications` JSON DEFAULT NULL COMMENT 'Array of certification objects',
    `languages` JSON DEFAULT NULL COMMENT 'Array of languages spoken',
    `average_rating` DECIMAL(3,2) DEFAULT 0.00,
    `total_reviews` INT(11) DEFAULT 0,
    `total_completed` INT(11) DEFAULT 0,
    `response_time_hours` INT(11) DEFAULT NULL COMMENT 'Average response time',
    `completion_rate` DECIMAL(5,2) DEFAULT NULL COMMENT 'Percentage of completed requests',
    `is_verified` TINYINT(1) DEFAULT 0,
    `verified_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `metadata` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_vendor_email` (`email`),
    KEY `idx_vendor_state` (`state`),
    KEY `idx_vendor_rating` (`average_rating`),
    KEY `idx_vendor_active` (`is_active`),
    FULLTEXT KEY `idx_vendor_search` (`name`, `company`, `bio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendor Categories Table
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_vendor_categories` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_profile_id` INT(11) UNSIGNED NOT NULL,
    `vendor_type` VARCHAR(50) NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `specialties` JSON DEFAULT NULL COMMENT 'Array of specialty services',
    `typical_turnaround_days` INT(11) DEFAULT NULL,
    `price_range_min` DECIMAL(10,2) DEFAULT NULL,
    `price_range_max` DECIMAL(10,2) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_vendor_category` (`vendor_profile_id`, `vendor_type`),
    KEY `idx_vendor_type` (`vendor_type`),
    CONSTRAINT `fk_vendor_category_profile` FOREIGN KEY (`vendor_profile_id`)
        REFERENCES `{prefix}ma_deal_vendor_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendor Reviews Table
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_vendor_reviews` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_profile_id` INT(11) UNSIGNED NOT NULL,
    `vendor_request_id` INT(11) UNSIGNED DEFAULT NULL,
    `reviewer_user_id` INT(11) UNSIGNED DEFAULT NULL,
    `reviewer_name` VARCHAR(255) NOT NULL,
    `rating` INT(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
    `review_text` TEXT DEFAULT NULL,
    `service_type` VARCHAR(50) DEFAULT NULL,
    `professionalism` INT(1) DEFAULT NULL CHECK (`professionalism` >= 1 AND `professionalism` <= 5),
    `communication` INT(1) DEFAULT NULL CHECK (`communication` >= 1 AND `communication` <= 5),
    `timeliness` INT(1) DEFAULT NULL CHECK (`timeliness` >= 1 AND `timeliness` <= 5),
    `value` INT(1) DEFAULT NULL CHECK (`value` >= 1 AND `value` <= 5),
    `would_recommend` TINYINT(1) DEFAULT NULL,
    `is_verified_transaction` TINYINT(1) DEFAULT 0,
    `response_from_vendor` TEXT DEFAULT NULL,
    `response_date` DATETIME DEFAULT NULL,
    `is_published` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_review_vendor` (`vendor_profile_id`),
    KEY `idx_review_request` (`vendor_request_id`),
    KEY `idx_review_rating` (`rating`),
    KEY `idx_review_date` (`created_at`),
    CONSTRAINT `fk_review_vendor_profile` FOREIGN KEY (`vendor_profile_id`)
        REFERENCES `{prefix}ma_deal_vendor_profiles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_vendor_request` FOREIGN KEY (`vendor_request_id`)
        REFERENCES `{prefix}ma_deal_vendor_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Preferred Vendors Table
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_preferred_vendors` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) UNSIGNED NOT NULL,
    `vendor_profile_id` INT(11) UNSIGNED NOT NULL,
    `vendor_type` VARCHAR(50) NOT NULL,
    `preference_order` INT(11) DEFAULT 0,
    `notes` TEXT DEFAULT NULL,
    `tags` JSON DEFAULT NULL COMMENT 'Custom tags for organization',
    `last_used_at` DATETIME DEFAULT NULL,
    `times_used` INT(11) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_vendor_type` (`user_id`, `vendor_profile_id`, `vendor_type`),
    KEY `idx_preferred_user` (`user_id`),
    KEY `idx_preferred_vendor` (`vendor_profile_id`),
    KEY `idx_preferred_type` (`vendor_type`),
    CONSTRAINT `fk_preferred_vendor_profile` FOREIGN KEY (`vendor_profile_id`)
        REFERENCES `{prefix}ma_deal_vendor_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendor Invitations History Table
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_vendor_invitations` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_profile_id` INT(11) UNSIGNED NOT NULL,
    `vendor_request_id` INT(11) UNSIGNED NOT NULL,
    `invited_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
    `invitation_sent_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `invitation_opened_at` DATETIME DEFAULT NULL,
    `response_status` ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending',
    `response_at` DATETIME DEFAULT NULL,
    `decline_reason` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_invitation_vendor` (`vendor_profile_id`),
    KEY `idx_invitation_request` (`vendor_request_id`),
    KEY `idx_invitation_status` (`response_status`),
    CONSTRAINT `fk_invitation_vendor_profile` FOREIGN KEY (`vendor_profile_id`)
        REFERENCES `{prefix}ma_deal_vendor_profiles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_invitation_vendor_request` FOREIGN KEY (`vendor_request_id`)
        REFERENCES `{prefix}ma_deal_vendor_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendor Documents Table (for licenses, insurance, etc.)
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_vendor_documents` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `vendor_profile_id` INT(11) UNSIGNED NOT NULL,
    `document_type` ENUM('license', 'insurance', 'certification', 'portfolio', 'other') NOT NULL,
    `document_name` VARCHAR(255) NOT NULL,
    `document_url` VARCHAR(500) NOT NULL,
    `file_size` INT(11) DEFAULT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `expires_at` DATE DEFAULT NULL,
    `is_verified` TINYINT(1) DEFAULT 0,
    `verified_by_user_id` INT(11) UNSIGNED DEFAULT NULL,
    `verified_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_document_vendor` (`vendor_profile_id`),
    KEY `idx_document_type` (`document_type`),
    CONSTRAINT `fk_document_vendor_profile` FOREIGN KEY (`vendor_profile_id`)
        REFERENCES `{prefix}ma_deal_vendor_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for performance
CREATE INDEX idx_vendor_location ON `{prefix}ma_deal_vendor_profiles` (`city`, `state`, `zip`);
CREATE INDEX idx_vendor_verified ON `{prefix}ma_deal_vendor_profiles` (`is_verified`, `is_active`);
CREATE INDEX idx_review_verified ON `{prefix}ma_deal_vendor_reviews` (`is_verified_transaction`, `is_published`);
CREATE INDEX idx_preferred_order ON `{prefix}ma_deal_preferred_vendors` (`user_id`, `vendor_type`, `preference_order`);

-- Trigger to update vendor profile stats after review
DELIMITER $$
CREATE TRIGGER update_vendor_stats_after_review
AFTER INSERT ON `{prefix}ma_deal_vendor_reviews`
FOR EACH ROW
BEGIN
    UPDATE `{prefix}ma_deal_vendor_profiles` p
    SET
        p.average_rating = (
            SELECT AVG(rating)
            FROM `{prefix}ma_deal_vendor_reviews`
            WHERE vendor_profile_id = NEW.vendor_profile_id
            AND is_published = 1
        ),
        p.total_reviews = (
            SELECT COUNT(*)
            FROM `{prefix}ma_deal_vendor_reviews`
            WHERE vendor_profile_id = NEW.vendor_profile_id
            AND is_published = 1
        )
    WHERE p.id = NEW.vendor_profile_id;
END$$
DELIMITER ;

-- Sample vendor types for categories
-- 'inspector', 'appraiser', 'title_company', 'attorney', 'contractor',
-- 'fire_dept', 'septic_inspector', 'hoa_manager', 'photographer',
-- 'stager', 'cleaning_service', 'moving_company', 'surveyor'