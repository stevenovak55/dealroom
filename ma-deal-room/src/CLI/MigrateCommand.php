<?php
/**
 * Migration WP-CLI Command
 *
 * @package MADealRoom\CLI
 * @since 1.0.0
 */

namespace MADealRoom\CLI;

use MADealRoom\Database\Migrator;
use WP_CLI;

/**
 * Manage database migrations
 */
class MigrateCommand {
	/**
	 * Run pending database migrations
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal migrate
	 *
	 * @when after_wp_load
	 */
	public function __invoke($args, $assoc_args) {
		WP_CLI::log('Running database migrations...');

		$migrator = new Migrator();
		$results = $migrator->run();

		if (empty($results)) {
			WP_CLI::success('No migrations to run.');
			return;
		}

		foreach ($results as $number => $result) {
			if ($result['status'] === 'success') {
				WP_CLI::success("Migration {$number}: {$result['message']}");
			} elseif ($result['status'] === 'skipped') {
				WP_CLI::log("Migration {$number}: {$result['message']}");
			} else {
				WP_CLI::error("Migration {$number}: {$result['message']}", false);
			}
		}

		WP_CLI::success('Migrations completed.');
	}

	/**
	 * Rollback the last migration
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal migrate:rollback
	 *
	 * @when after_wp_load
	 */
	public function rollback($args, $assoc_args) {
		WP_CLI::log('Rolling back last migration...');

		$migrator = new Migrator();
		$result = $migrator->rollback();

		if ($result['status'] === 'success') {
			WP_CLI::success($result['message']);
		} else {
			WP_CLI::error($result['message']);
		}
	}

	/**
	 * Show migration status
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal migrate:status
	 *
	 * @when after_wp_load
	 */
	public function status($args, $assoc_args) {
		$migrator = new Migrator();
		$status = $migrator->status();

		if (empty($status)) {
			WP_CLI::log('No migrations found.');
			return;
		}

		$items = [];
		foreach ($status as $migration) {
			$items[] = [
				'Number' => $migration['number'],
				'Name' => $migration['name'],
				'Applied' => $migration['applied'] ? 'Yes' : 'No',
			];
		}

		WP_CLI\Utils\format_items('table', $items, ['Number', 'Name', 'Applied']);
	}
}
