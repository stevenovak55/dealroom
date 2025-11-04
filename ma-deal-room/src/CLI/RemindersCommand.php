<?php
/**
 * Reminders WP-CLI Command
 *
 * @package MADealRoom\CLI
 * @since 1.0.0
 */

namespace MADealRoom\CLI;

use MADealRoom\Core\Plugin;
use WP_CLI;

/**
 * Process reminder queue
 */
class RemindersCommand {
	/**
	 * Send pending reminders
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<limit>]
	 * : Maximum number of reminders to send
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal reminders:send
	 *     wp ma-deal reminders:send --limit=50
	 *
	 * @when after_wp_load
	 */
	public function send($args, $assoc_args) {
		$limit = $assoc_args['limit'] ?? 100;

		WP_CLI::log("Processing up to {$limit} pending reminders...");

		$plugin = Plugin::instance();
		$reminder_service = $plugin->container()->get('reminder_service');

		$results = $reminder_service->processQueue($limit);

		WP_CLI::log(sprintf(
			'Processed: %d | Sent: %d | Failed: %d',
			$results['processed'],
			$results['sent'],
			$results['failed']
		));

		WP_CLI::success('Reminder processing completed.');
	}

	/**
	 * Retry failed reminders with exponential backoff
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal reminders:retry
	 *
	 * @when after_wp_load
	 */
	public function retry($args, $assoc_args) {
		WP_CLI::log("Retrying failed reminders with exponential backoff...");

		$plugin = Plugin::instance();
		$reminder_service = $plugin->container()->get('reminder_service');

		$results = $reminder_service->retryFailed();

		WP_CLI::log(sprintf(
			'Retried: %d | Succeeded: %d | Failed: %d',
			$results['retried'],
			$results['succeeded'],
			$results['failed']
		));

		if ($results['failed'] > 0) {
			WP_CLI::warning(sprintf(
				'%d reminder(s) failed retry and will be attempted again with longer backoff.',
				$results['failed']
			));
		}

		WP_CLI::success('Retry processing completed.');
	}

	/**
	 * Show reminder queue status
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal reminders:status
	 *
	 * @when after_wp_load
	 */
	public function status($args, $assoc_args) {
		global $wpdb;
		$table = $wpdb->prefix . 'ma_deal_reminders';

		// Get counts by status
		$counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
			ARRAY_A
		);

		WP_CLI::log("\n=== Reminder Queue Status ===\n");

		foreach ($counts as $row) {
			WP_CLI::log(sprintf('%-15s: %d', ucfirst($row['status']), $row['count']));
		}

		// Get overdue pending reminders
		$overdue = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table}
			WHERE status = 'pending' AND scheduled_at < NOW()"
		);

		if ($overdue > 0) {
			WP_CLI::warning(sprintf("\n%d overdue reminders need processing!", $overdue));
		}

		// Get upcoming reminders (next 24 hours)
		$upcoming = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table}
			WHERE status = 'pending'
			  AND scheduled_at >= NOW()
			  AND scheduled_at <= DATE_ADD(NOW(), INTERVAL 24 HOUR)"
		);

		WP_CLI::log(sprintf("\n%d reminders scheduled for next 24 hours", $upcoming));

		WP_CLI::success('Status check completed.');
	}
}
