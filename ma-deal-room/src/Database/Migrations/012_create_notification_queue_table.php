<?php
/**
 * Migration: Create Notification Queue Table
 *
 * This table stores notifications that are queued for batching
 * to avoid sending multiple individual emails for bulk operations.
 *
 * @package MADealRoom\Database\Migrations
 * @since 1.0.0
 */

return [
	'version' => '1.0.7',
	'up' => function() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ma_deal_notification_queue';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`user_id` bigint(20) unsigned NOT NULL,
			`user_type` varchar(20) NOT NULL DEFAULT 'wordpress',
			`notification_type` varchar(50) NOT NULL,
			`notification_data` longtext NOT NULL COMMENT 'JSON encoded notification data',
			`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`scheduled_for` datetime NOT NULL COMMENT 'When this notification should be processed',
			`status` varchar(20) NOT NULL DEFAULT 'pending',
			`processed_at` datetime DEFAULT NULL,
			`error_message` text DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `user_id` (`user_id`),
			KEY `status_scheduled` (`status`, `scheduled_for`),
			KEY `notification_type` (`notification_type`),
			KEY `created_at` (`created_at`)
		) {$charset_collate};";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		return $wpdb->last_error ? ['error' => $wpdb->last_error] : ['success' => true];
	},
	'down' => function() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ma_deal_notification_queue';
		$wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
		return ['success' => true];
	}
];
