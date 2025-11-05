<?php
/**
 * Uninstall MA Deal Room Plugin
 *
 * Runs when the plugin is deleted (not just deactivated).
 * Removes all plugin tables and data from the database.
 *
 * @package MADealRoom
 * @since 1.0.0
 */

// Exit if accessed directly or not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

// Get table prefix
$prefix = $wpdb->prefix;

/**
 * List of all plugin tables to drop
 * IMPORTANT: Order matters due to foreign key constraints!
 * Drop child tables (those with foreign keys) before parent tables
 */
$tables = [
	// Child tables first (tables that reference other tables)
	$prefix . 'ma_deal_events',
	$prefix . 'ma_deal_vendor_requests',
	$prefix . 'ma_deal_reminders',
	$prefix . 'ma_deal_documents',
	$prefix . 'ma_deal_notifications',
	$prefix . 'ma_deal_notification_queue',
	$prefix . 'ma_deal_template_tasks',
	$prefix . 'ma_deal_transaction_custom_tasks',
	$prefix . 'ma_deal_property_attributes',
	$prefix . 'ma_deal_security_deposits',
	$prefix . 'ma_deal_tasks',
	$prefix . 'ma_deal_parties',

	// User system tables (from migration 011)
	$prefix . 'ma_deal_user_sessions',
	$prefix . 'ma_deal_user_roles',
	$prefix . 'ma_deal_user_invitations',
	$prefix . 'ma_deal_password_resets',
	$prefix . 'ma_deal_email_verifications',
	$prefix . 'ma_deal_2fa_secrets',
	$prefix . 'ma_deal_custom_users',

	// Parent tables (referenced by foreign keys)
	$prefix . 'ma_deal_transactions',
	$prefix . 'ma_deal_templates',
	$prefix . 'ma_deal_task_definitions',
	$prefix . 'ma_deal_accounts',

	// Lookup/reference tables
	$prefix . 'ma_deal_task_categories',
	$prefix . 'ma_deal_transaction_types',

	// Integration tables (from migrations 021, 023, 026, 027)
	$prefix . 'ma_deal_mls_config',
	$prefix . 'ma_deal_crm_config',
	$prefix . 'ma_deal_contacts',
	$prefix . 'ma_deal_docusign_config',

	// DocuSign tables (from migrations 028, 029)
	$prefix . 'ma_deal_docusign_envelopes',
	$prefix . 'ma_deal_docusign_webhook_log',

	// Queue system tables (from migration 019)
	$prefix . 'ma_deal_job_queue',

	// Security tables (from migration 014)
	$prefix . 'ma_deal_rate_limits',
	$prefix . 'ma_deal_security_events',
	$prefix . 'ma_deal_login_attempts',

	// Audit and logging
	$prefix . 'ma_deal_audit_log',

	// Backup tables (from migrations/testing)
	$prefix . 'ma_deal_task_definitions_phase2_backup',

	// Migration tracking (no foreign keys)
	$prefix . 'ma_deal_migrations',
];

/**
 * Drop all plugin tables
 * Use SET FOREIGN_KEY_CHECKS=0 to avoid constraint issues
 */
$wpdb->query('SET FOREIGN_KEY_CHECKS=0');

foreach ($tables as $table) {
	$wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

$wpdb->query('SET FOREIGN_KEY_CHECKS=1');

/**
 * Delete all plugin options
 */
$option_patterns = [
	'ma_deal_room_%',
	'ma_deal_%',
];

foreach ($option_patterns as $pattern) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$pattern
		)
	);
}

/**
 * Delete plugin pages
 */
$plugin_pages = [
	'agent-dashboard',
];

foreach ($plugin_pages as $page_slug) {
	$page = get_page_by_path($page_slug);
	if ($page) {
		wp_delete_post($page->ID, true); // Force delete (bypass trash)
	}
}

/**
 * Clear any cached data
 */
wp_cache_flush();

/**
 * Optional: Log uninstall for debugging
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
	error_log('MA Deal Room plugin uninstalled - all data removed');
}
