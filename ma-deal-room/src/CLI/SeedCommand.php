<?php
/**
 * Seed WP-CLI Command
 *
 * @package MADealRoom\CLI
 * @since 1.0.0
 */

namespace MADealRoom\CLI;

use WP_CLI;

/**
 * Seed database with sample data
 */
class SeedCommand {
	/**
	 * Seed database with sample data
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal seed
	 *
	 * @when after_wp_load
	 */
	public function __invoke($args, $assoc_args) {
		WP_CLI::log('Seeding database with sample data...');

		$seed_file = MA_DEAL_PATH . 'database/seed.sql';

		if (!file_exists($seed_file)) {
			WP_CLI::error("Seed file not found: {$seed_file}");
			return;
		}

		global $wpdb;

		$sql = file_get_contents($seed_file);
		$statements = array_filter(
			array_map('trim', explode(';', $sql)),
			function($statement) {
				return !empty($statement) && strpos($statement, '--') !== 0;
			}
		);

		$success_count = 0;
		$error_count = 0;

		foreach ($statements as $statement) {
			$result = $wpdb->query($statement);

			if ($result === false) {
				$error_count++;
				WP_CLI::warning("Failed to execute statement: " . $wpdb->last_error);
			} else {
				$success_count++;
			}
		}

		WP_CLI::log(sprintf(
			'Executed %d statements (%d successful, %d failed)',
			count($statements),
			$success_count,
			$error_count
		));

		if ($error_count === 0) {
			WP_CLI::success('Database seeded successfully.');
		} else {
			WP_CLI::warning('Database seeded with some errors.');
		}
	}
}
