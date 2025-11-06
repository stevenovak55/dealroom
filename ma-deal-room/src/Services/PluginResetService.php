<?php
/**
 * Plugin Reset Service
 *
 * Provides complete plugin reset functionality:
 * - Full database wipe
 * - Table recreation from migrations
 * - Data seeding (templates, task definitions)
 * - Verification checks
 *
 * @package MA_Deal_Room
 * @subpackage Services
 * @since 1.0.1
 */

namespace MADealRoom\Services;

use MADealRoom\Database\Migrator;
use Exception;

class PluginResetService {
	/**
	 * Reset log for tracking progress
	 *
	 * @var array
	 */
	protected $log = [];

	/**
	 * Whether to actually execute changes (false = dry run)
	 *
	 * @var bool
	 */
	protected $dry_run = false;

	/**
	 * Global WordPress database object
	 *
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * Constructor
	 *
	 * @param bool $dry_run Whether to run in dry-run mode
	 */
	public function __construct(bool $dry_run = false) {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->dry_run = $dry_run;
	}

	/**
	 * Execute complete plugin reset
	 *
	 * @return array Reset results with log and verification
	 */
	public function reset(): array {
		$this->log('Starting plugin reset', 'info');
		$this->log('Dry run mode: ' . ($this->dry_run ? 'YES' : 'NO'), 'info');

		try {
			// Step 1: Create backup
			$backup = $this->createBackup();
			$this->log('Created backup: ' . $backup['file'], 'success');

			// Step 2: Drop all MA Deal Room tables
			$dropped = $this->dropAllTables();
			$this->log("Dropped {$dropped} tables", 'success');

			// Step 3: Delete all WordPress options
			$options_deleted = $this->deleteAllOptions();
			$this->log("Deleted {$options_deleted} WordPress options", 'success');

			// Step 4: Run all migrations
			$migrations = $this->runMigrations();

			// Check if all expected migrations ran
			$expected_migration_count = 21;
			if ($migrations['count'] < $expected_migration_count) {
				$this->log("WARNING: Only {$migrations['count']} of {$expected_migration_count} expected migrations ran!", 'error');

				// List missing migrations
				$migration_files = glob(MA_DEAL_PATH . 'database/migrations/*.sql');
				$migration_files = array_filter($migration_files, function($file) {
					return strpos($file, 'rollback') === false;
				});

				$expected_migrations = array_map('basename', $migration_files);
				$ran_migrations = array_keys($migrations['results']);

				$missing = array_diff($expected_migrations, $ran_migrations);
				if (!empty($missing)) {
					$this->log("Missing migrations: " . implode(', ', $missing), 'error');
				}
			} elseif (isset($migrations['failed_count']) && $migrations['failed_count'] > 0) {
				$this->log("Ran {$migrations['count']} migrations ({$migrations['failed_count']} failed)", 'error');
			} else {
				$this->log("Ran {$migrations['count']} migrations", 'success');
			}

			// Step 5: Sync templates
			$templates = $this->syncTemplates();
			$this->log("Synced {$templates['synced']} templates", 'success');

			// Step 6: Sync task definitions
			$tasks = $this->syncTaskDefinitions();
			$this->log("Synced {$tasks['created']} task definitions", 'success');

			// Step 7: Create default account
			$account = $this->createDefaultAccount();
			$this->log("Created default account (ID: {$account['id']})", 'success');

			// Step 8: Set WordPress options
			$this->setWordPressOptions();
			$this->log('Set WordPress options', 'success');

			// Step 9: Verify everything
			$verification = $this->verify();

			if ($this->dry_run) {
				$this->log('Verification skipped - Dry run mode (no actual changes made)', 'info');
				$verification['success'] = true; // Mark as success for dry run
				$verification['dry_run_note'] = 'Verification checks cannot pass in dry-run mode because no actual data was created. This is expected behavior.';
			} elseif ($verification['success']) {
				$this->log('Verification PASSED - All checks successful!', 'success');
			} else {
				$this->log('Verification FAILED - Some checks did not pass', 'error');
			}

			return [
				'success' => $verification['success'],
				'log' => $this->log,
				'verification' => $verification,
				'backup' => $backup,
				'dry_run' => $this->dry_run,
			];

		} catch (Exception $e) {
			$this->log('FATAL ERROR: ' . $e->getMessage(), 'error');
			$this->log('Stack trace: ' . $e->getTraceAsString(), 'error');

			return [
				'success' => false,
				'log' => $this->log,
				'error' => $e->getMessage(),
				'dry_run' => $this->dry_run,
			];
		}
	}

	/**
	 * Create database backup before reset
	 *
	 * @return array Backup info
	 */
	protected function createBackup(): array {
		$timestamp = date('Y-m-d_H-i-s');
		$backup_dir = WP_CONTENT_DIR . '/ma-deal-room-backups';

		if (!is_dir($backup_dir)) {
			wp_mkdir_p($backup_dir);
		}

		$backup_file = $backup_dir . '/backup_' . $timestamp . '.sql';

		// Get all MA Deal Room tables (including migrations table)
		$tables = $this->wpdb->get_results(
			"SHOW TABLES LIKE 'wp_ma_deal_%'",
			ARRAY_N
		);

		// Note: This includes wp_ma_deal_migrations which tracks which migrations have run

		$sql = "-- MA Deal Room Backup\n";
		$sql .= "-- Created: {$timestamp}\n\n";

		foreach ($tables as $table) {
			$table_name = $table[0];

			// Get CREATE TABLE statement
			$create = $this->wpdb->get_row("SHOW CREATE TABLE {$table_name}", ARRAY_N);
			$sql .= "DROP TABLE IF EXISTS `{$table_name}`;\n";
			$sql .= $create[1] . ";\n\n";

			// Get data
			$rows = $this->wpdb->get_results("SELECT * FROM {$table_name}", ARRAY_A);
			if (!empty($rows)) {
				foreach ($rows as $row) {
					$values = array_map(function($val) {
						return $val === null ? 'NULL' : "'" . esc_sql($val) . "'";
					}, array_values($row));

					$sql .= "INSERT INTO `{$table_name}` VALUES (" . implode(', ', $values) . ");\n";
				}
				$sql .= "\n";
			}
		}

		// Get MA Deal Room options
		$options = $this->wpdb->get_results(
			"SELECT option_name, option_value FROM {$this->wpdb->options}
			 WHERE option_name LIKE 'ma_deal_room%'",
			ARRAY_A
		);

		if (!empty($options)) {
			$sql .= "-- WordPress Options\n";
			foreach ($options as $option) {
				$sql .= "REPLACE INTO {$this->wpdb->options} (option_name, option_value) VALUES ('" .
					esc_sql($option['option_name']) . "', '" .
					esc_sql($option['option_value']) . "');\n";
			}
		}

		if (!$this->dry_run) {
			file_put_contents($backup_file, $sql);
		}

		return [
			'file' => $backup_file,
			'size' => $this->dry_run ? 0 : filesize($backup_file),
			'tables' => count($tables),
			'options' => count($options),
		];
	}

	/**
	 * Drop all MA Deal Room tables
	 *
	 * @return int Number of tables dropped
	 */
	protected function dropAllTables(): int {
		$tables = $this->wpdb->get_results(
			"SHOW TABLES LIKE '{$this->wpdb->prefix}ma_deal_%'",
			ARRAY_N
		);

		$count = 0;

		if (!$this->dry_run) {
			// Disable foreign key checks
			$this->wpdb->query('SET FOREIGN_KEY_CHECKS = 0');

			foreach ($tables as $table) {
				$table_name = $table[0];
				$this->wpdb->query("DROP TABLE IF EXISTS `{$table_name}`");
				$count++;
			}

			// Re-enable foreign key checks
			$this->wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
		} else {
			$count = count($tables);
		}

		return $count;
	}

	/**
	 * Delete all MA Deal Room WordPress options
	 *
	 * @return int Number of options deleted
	 */
	protected function deleteAllOptions(): int {
		if (!$this->dry_run) {
			$result = $this->wpdb->query(
				"DELETE FROM {$this->wpdb->options} WHERE option_name LIKE 'ma_deal_room%'"
			);
			return $result ?: 0;
		}

		// Dry run - just count
		return (int) $this->wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->wpdb->options} WHERE option_name LIKE 'ma_deal_room%'"
		);
	}

	/**
	 * Run all database migrations
	 *
	 * @return array Migration results
	 */
	protected function runMigrations(): array {
		if ($this->dry_run) {
			$migration_files = glob(MA_DEAL_PATH . 'database/migrations/*.sql');
			$migration_files = array_filter($migration_files, function($file) {
				return strpos($file, 'rollback') === false;
			});

			return [
				'count' => count($migration_files),
				'results' => [],
			];
		}

		try {
			$migrator = new Migrator();
			$results = $migrator->run();

			// Log each migration result
			$success_count = 0;
			$failed_count = 0;
			$skipped_count = 0;

			foreach ($results as $migration => $result) {
				$status = $result['status'] ?? 'unknown';
				$message = $result['message'] ?? 'No message';

				if ($status === 'success') {
					$success_count++;
					$this->log("Migration {$migration}: SUCCESS", 'info');
				} elseif ($status === 'skipped') {
					$skipped_count++;
					$this->log("Migration {$migration}: SKIPPED - {$message}", 'info');
				} elseif ($status === 'error') {
					$failed_count++;
					$this->log("Migration {$migration}: FAILED - {$message}", 'error');
				} else {
					$this->log("Migration {$migration}: UNKNOWN STATUS - {$message}", 'error');
				}
			}

			if ($failed_count > 0) {
				$this->log("Migrations completed with {$failed_count} failures out of " . count($results) . " total", 'error');
			}

			return [
				'count' => count($results),
				'success_count' => $success_count,
				'failed_count' => $failed_count,
				'results' => $results,
			];
		} catch (\Exception $e) {
			$this->log("Migration error: " . $e->getMessage(), 'error');
			$this->log("Stack trace: " . $e->getTraceAsString(), 'error');

			return [
				'count' => 0,
				'success_count' => 0,
				'failed_count' => 0,
				'results' => [],
				'error' => $e->getMessage(),
			];
		}
	}

	/**
	 * Sync system templates from YAML files
	 *
	 * @return array Sync results
	 */
	protected function syncTemplates(): array {
		if ($this->dry_run) {
			$templates_dir = MA_DEAL_PATH . 'assets/templates/';
			$template_files = glob($templates_dir . '*.yaml');

			return [
				'synced' => count($template_files),
				'errors' => 0,
			];
		}

		// Call the global function that's already defined in ma-deal-room.php
		return ma_deal_room_sync_system_templates();
	}

	/**
	 * Sync task definitions from YAML templates
	 *
	 * @return array Sync results
	 */
	protected function syncTaskDefinitions(): array {
		if ($this->dry_run) {
			$templates_dir = MA_DEAL_PATH . 'assets/templates/';
			$template_files = glob($templates_dir . '*.yaml');

			// Rough estimate: 7 templates × ~40 tasks each = ~280 tasks
			return [
				'created' => 276,
				'updated' => 0,
				'skipped' => 0,
				'errors' => 0,
			];
		}

		// Call the global function that's already defined in ma-deal-room.php
		return ma_deal_room_sync_task_definitions();
	}

	/**
	 * Create default account
	 *
	 * @return array Account info
	 */
	protected function createDefaultAccount(): array {
		if ($this->dry_run) {
			return ['id' => 1];
		}

		// Call the global function that's already defined in ma-deal-room.php
		ma_deal_room_ensure_default_account();

		$account = $this->wpdb->get_row(
			"SELECT * FROM wp_ma_deal_accounts WHERE id = 1",
			ARRAY_A
		);

		return $account ?: ['id' => null];
	}

	/**
	 * Set WordPress options
	 */
	protected function setWordPressOptions(): void {
		if ($this->dry_run) {
			return;
		}

		update_option('ma_deal_room_activated', true);
		update_option('ma_deal_room_version', MA_DEAL_VERSION);
		update_option('ma_deal_room_last_reset', current_time('mysql'));
		update_option('ma_deal_room_templates_synced', true);
		update_option('ma_deal_room_task_definitions_synced', true);
	}

	/**
	 * Verify all tables and data
	 *
	 * @return array Verification results
	 */
	protected function verify(): array {
		$checks = [];
		$all_passed = true;

		// Get table prefix
		$prefix = $this->wpdb->prefix;

		// Check 1: All tables exist
		$required_tables = [
			'ma_deal_accounts',
			'ma_deal_transactions',
			'ma_deal_tasks',
			'ma_deal_task_definitions',
			'ma_deal_templates',
			'ma_deal_documents',
			'ma_deal_notifications',
			'ma_deal_mls_config',
			'ma_deal_mls_sync_log',
		];

		foreach ($required_tables as $table) {
			$full_table_name = $prefix . $table;
			$exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
			$checks["table_{$table}"] = [
				'name' => "Table exists: {$full_table_name}",
				'passed' => (bool) $exists,
			];

			if (!$exists) {
				$all_passed = false;
				$this->log("Verification check failed: Table {$full_table_name} does not exist", 'error');
			}
		}

		// Check 2: Task definitions count
		$task_count = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$prefix}ma_deal_task_definitions");
		$checks['task_definitions_count'] = [
			'name' => 'Task definitions count (should be 276)',
			'passed' => $task_count === 276,
			'actual' => $task_count,
			'expected' => 276,
		];

		if ($task_count !== 276) {
			$all_passed = false;
			$this->log("Verification check failed: Task definitions count is {$task_count}, expected 276", 'error');
		}

		// Check 3: Templates count
		$template_count = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$prefix}ma_deal_templates");
		$checks['templates_count'] = [
			'name' => 'Templates count (should be 7)',
			'passed' => $template_count === 7,
			'actual' => $template_count,
			'expected' => 7,
		];

		if ($template_count !== 7) {
			$all_passed = false;
			$this->log("Verification check failed: Templates count is {$template_count}, expected 7", 'error');
		}

		// Check 4: Default account exists
		$account_exists = (bool) $this->wpdb->get_var("SELECT COUNT(*) FROM {$prefix}ma_deal_accounts WHERE id = 1");
		$checks['default_account'] = [
			'name' => 'Default account exists (ID: 1)',
			'passed' => $account_exists,
		];

		if (!$account_exists) {
			$all_passed = false;
			$this->log("Verification check failed: Default account (ID: 1) does not exist", 'error');
		}

		// Check 5: WordPress options set
		$version = get_option('ma_deal_room_version');
		$checks['version_option'] = [
			'name' => 'Version option set',
			'passed' => $version === MA_DEAL_VERSION,
			'actual' => $version,
			'expected' => MA_DEAL_VERSION,
		];

		if ($version !== MA_DEAL_VERSION) {
			$all_passed = false;
			$this->log("Verification check failed: Version option is '{$version}', expected '" . MA_DEAL_VERSION . "'", 'error');
		}

		return [
			'success' => $all_passed,
			'checks' => $checks,
			'total_checks' => count($checks),
			'passed_checks' => count(array_filter($checks, function($check) {
				return $check['passed'];
			})),
		];
	}

	/**
	 * Add entry to log
	 *
	 * @param string $message Log message
	 * @param string $level Log level (info, success, warning, error)
	 */
	protected function log(string $message, string $level = 'info'): void {
		$this->log[] = [
			'timestamp' => current_time('mysql'),
			'level' => $level,
			'message' => $message,
		];

		// Also log to WordPress debug log if enabled
		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log("MA Deal Room Reset [{$level}]: {$message}");
		}
	}

	/**
	 * Get the log
	 *
	 * @return array
	 */
	public function getLog(): array {
		return $this->log;
	}
}
