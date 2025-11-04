-- Migration: Create Modular Task System
-- Version: 006
-- Description: Convert from embedded YAML tasks to individual task definitions that can be reused across templates

-- =====================================================
-- TASK DEFINITIONS TABLE
-- Individual reusable task definitions
-- =====================================================
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_task_definitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_key` varchar(100) NOT NULL COMMENT 'Unique identifier for this task (e.g., agency_disclosure, lead_paint_disclosure)',
  `category` varchar(50) NOT NULL COMMENT 'Task category (deal_setup, inspection, financing, closing, etc.)',
  `title` varchar(255) NOT NULL COMMENT 'Task title',
  `description` text COMMENT 'Task description',
  `owner_role` varchar(50) NOT NULL DEFAULT 'agent' COMMENT 'Default role assigned (agent, buyer, seller, attorney, vendor)',
  `priority` varchar(20) DEFAULT 'normal' COMMENT 'Task priority (low, normal, high, critical)',
  `estimated_duration` int(11) DEFAULT NULL COMMENT 'Estimated duration in minutes',
  `due_calculation` varchar(50) DEFAULT NULL COMMENT 'How to calculate due date (Closing-21d, PS+7d, listing_date+5d)',
  `applies_if` text COMMENT 'Condition for task applicability (property.type == "Condo")',
  `depends_on` text COMMENT 'JSON array of task_keys this depends on',
  `metadata` longtext COMMENT 'JSON metadata (citations, notes, documents, reminders)',
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'System task vs user-created',
  `is_milestone` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is this a milestone task',
  `is_required` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is this task required',
  `account_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Account that created custom task (NULL for system)',
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_key_account` (`task_key`, `account_id`),
  KEY `category` (`category`),
  KEY `owner_role` (`owner_role`),
  KEY `is_system` (`is_system`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual reusable task definitions';

-- =====================================================
-- TEMPLATE TASKS JUNCTION TABLE
-- Links templates to task definitions with ordering
-- =====================================================
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_template_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` bigint(20) unsigned NOT NULL COMMENT 'Reference to template',
  `task_definition_id` bigint(20) unsigned NOT NULL COMMENT 'Reference to task definition',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Display order within template',
  `override_due_calculation` varchar(50) DEFAULT NULL COMMENT 'Override default due calculation for this template',
  `override_owner_role` varchar(50) DEFAULT NULL COMMENT 'Override default owner role',
  `override_applies_if` text COMMENT 'Override applicability condition',
  `is_optional` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Is this task optional in this template',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_task_unique` (`template_id`, `task_definition_id`),
  KEY `template_id` (`template_id`),
  KEY `task_definition_id` (`task_definition_id`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `fk_template_tasks_template` FOREIGN KEY (`template_id`)
    REFERENCES `{prefix}ma_deal_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_template_tasks_task_def` FOREIGN KEY (`task_definition_id`)
    REFERENCES `{prefix}ma_deal_task_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Template to task definition relationships';

-- =====================================================
-- TRANSACTION CUSTOM TASKS
-- Individual tasks added directly to transactions (not from templates)
-- =====================================================
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_transaction_custom_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` bigint(20) unsigned NOT NULL,
  `task_definition_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Reference to task definition if based on one',
  `title` varchar(255) NOT NULL,
  `description` text,
  `owner_role` varchar(50) NOT NULL DEFAULT 'agent',
  `assigned_party_id` bigint(20) unsigned DEFAULT NULL,
  `due_at` datetime DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `metadata` longtext COMMENT 'JSON metadata',
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `task_definition_id` (`task_definition_id`),
  KEY `status` (`status`),
  KEY `due_at` (`due_at`),
  CONSTRAINT `fk_transaction_custom_tasks_transaction` FOREIGN KEY (`transaction_id`)
    REFERENCES `{prefix}ma_deal_transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transaction_custom_tasks_task_def` FOREIGN KEY (`task_definition_id`)
    REFERENCES `{prefix}ma_deal_task_definitions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Custom tasks added to individual transactions';

-- =====================================================
-- UPDATE TEMPLATES TABLE
-- Add fields to support template type and inheritance
-- =====================================================
ALTER TABLE `{prefix}ma_deal_templates`
  ADD COLUMN `template_type` varchar(20) DEFAULT 'standard' COMMENT 'standard, base, custom' AFTER `property_type`,
  ADD COLUMN `extends_template_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Inherits tasks from another template' AFTER `template_type`,
  ADD COLUMN `is_editable` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Can users edit this template' AFTER `is_system`,
  ADD KEY `template_type` (`template_type`),
  ADD KEY `extends_template_id` (`extends_template_id`);

-- =====================================================
-- TASK DEFINITION CATEGORIES
-- Enumeration table for task categories
-- =====================================================
CREATE TABLE IF NOT EXISTS `{prefix}ma_deal_task_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT NULL COMMENT 'Icon identifier for UI',
  `color` varchar(20) DEFAULT NULL COMMENT 'Color for UI display',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_key` (`category_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Task category definitions';

-- Insert default categories
INSERT INTO `{prefix}ma_deal_task_categories` (`category_key`, `name`, `description`, `sort_order`, `created_at`) VALUES
('deal_setup', 'Deal Setup', 'Initial transaction setup and documentation', 10, NOW()),
('party_onboarding', 'Party Onboarding', 'Adding and onboarding all transaction parties', 20, NOW()),
('communication', 'Communication', 'Welcome letters, introductions, and status updates', 25, NOW()),
('earnest_money', 'Earnest Money', 'Earnest money deposit and escrow management', 30, NOW()),
('inspection', 'Inspection', 'Property inspection and due diligence', 40, NOW()),
('ps_agreement', 'Purchase & Sale Agreement', 'P&S negotiation and execution', 50, NOW()),
('financing', 'Financing', 'Mortgage application and approval', 60, NOW()),
('title_work', 'Title Work', 'Title search, insurance, and clearance', 70, NOW()),
('hoa_condo', 'HOA/Condo', 'HOA and condominium-specific requirements', 75, NOW()),
('pre_closing', 'Pre-Closing', 'Final preparations before closing', 80, NOW()),
('closing', 'Closing', 'Closing day activities', 90, NOW()),
('post_closing', 'Post-Closing', 'Post-closing follow-up and finalization', 100, NOW()),
('property_specific', 'Property-Specific', 'Property type-specific requirements', 110, NOW()),
('compliance', 'Compliance', 'Regulatory and legal compliance', 120, NOW());

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX idx_task_def_category_system ON `{prefix}ma_deal_task_definitions` (`category`, `is_system`);
CREATE INDEX idx_task_def_account ON `{prefix}ma_deal_task_definitions` (`account_id`, `is_system`);
CREATE INDEX idx_template_tasks_order ON `{prefix}ma_deal_template_tasks` (`template_id`, `sort_order`);