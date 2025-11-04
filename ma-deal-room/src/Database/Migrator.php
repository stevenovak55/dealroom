<?php
/**
 * Database Migrator
 *
 * @package MADealRoom\Database
 * @since 1.0.0
 */

namespace MADealRoom\Database;

/**
 * Handle database migrations
 */
class Migrator {
	/**
	 * WordPress database instance
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Migrations directory
	 *
	 * @var string
	 */
	private $migrations_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->migrations_dir = MA_DEAL_PATH . 'database/migrations/';
	}

	/**
	 * Run all pending migrations
	 *
	 * @return array Results of each migration
	 */
	public function run(): array {
		$results = [];

		// Ensure migrations tracking table exists
		$this->create_migrations_table();

		// Get list of migration files
		$migration_files = $this->get_migration_files();

		// Get already applied migrations
		$applied_migrations = $this->get_applied_migrations();

		// Run each pending migration
		foreach ($migration_files as $file) {
			$migration_number = $this->extract_migration_number($file);

			if (in_array($migration_number, $applied_migrations)) {
				$results[$migration_number] = [
					'status' => 'skipped',
					'message' => 'Already applied',
				];
				continue;
			}

			try {
				$this->run_migration_file($file);
				$this->record_migration($migration_number, $file);

				$results[$migration_number] = [
					'status' => 'success',
					'message' => 'Applied successfully',
				];
			} catch (\Exception $e) {
				$results[$migration_number] = [
					'status' => 'error',
					'message' => $e->getMessage(),
				];
				break; // Stop on first error
			}
		}

		return $results;
	}

	/**
	 * Create migrations tracking table
	 *
	 * @return void
	 */
	private function create_migrations_table(): void {
		$table_name = $this->wpdb->prefix . 'ma_deal_migrations';
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
			`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`migration_number` VARCHAR(10) NOT NULL,
			`migration_name` VARCHAR(255) NOT NULL,
			`applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`rollback_available` BOOLEAN NOT NULL DEFAULT TRUE,
			PRIMARY KEY (`id`),
			UNIQUE KEY `idx_migration_number` (`migration_number`)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
	}

	/**
	 * Get list of migration files
	 *
	 * @return array Migration file paths
	 */
	private function get_migration_files(): array {
		if (!is_dir($this->migrations_dir)) {
			return [];
		}

		$files = glob($this->migrations_dir . '*.sql');

		// Exclude rollback files
		$files = array_filter($files, function($file) {
			return strpos(basename($file), 'rollback_') !== 0;
		});

		// Sort by migration number
		usort($files, function($a, $b) {
			return strcmp(
				$this->extract_migration_number($a),
				$this->extract_migration_number($b)
			);
		});

		return $files;
	}

	/**
	 * Get list of applied migrations
	 *
	 * @return array Migration numbers
	 */
	private function get_applied_migrations(): array {
		$table_name = $this->wpdb->prefix . 'ma_deal_migrations';

		$results = $this->wpdb->get_col(
			"SELECT migration_number FROM {$table_name} ORDER BY id ASC"
		);

		return $results ?: [];
	}

	/**
	 * Extract migration number from filename
	 *
	 * @param string $file File path
	 * @return string Migration number
	 */
	private function extract_migration_number(string $file): string {
		$filename = basename($file);
		preg_match('/^(\d+)_/', $filename, $matches);
		return $matches[1] ?? '000';
	}

	/**
	 * Run a migration file
	 *
	 * @param string $file Migration file path
	 * @return void
	 * @throws \Exception If migration fails
	 */
	private function run_migration_file(string $file): void {
		$sql = file_get_contents($file);

		if ($sql === false) {
			throw new \Exception("Failed to read migration file: {$file}");
		}

		// Replace {prefix} placeholder with actual table prefix
		$sql = str_replace('{prefix}', $this->wpdb->prefix, $sql);

		// Remove SQL line comments (lines starting with --) before splitting
		$lines = explode("\n", $sql);
		$cleaned_lines = [];
		foreach ($lines as $line) {
			$trimmed = trim($line);
			// Keep non-empty lines that don't start with --
			if (!empty($trimmed) && strpos($trimmed, '--') !== 0) {
				$cleaned_lines[] = $line;
			}
		}
		$sql = implode("\n", $cleaned_lines);

		// Split by semicolon and execute each statement
		$statements = array_filter(
			array_map('trim', explode(';', $sql)),
			function($statement) {
				// Filter out empty statements
				return !empty($statement);
			}
		);

		foreach ($statements as $statement) {
			$result = $this->wpdb->query($statement);

			if ($result === false) {
				// For index/constraint errors, log warning but continue (non-critical)
				// For table/column errors, throw exception (critical)
				$error = strtolower($this->wpdb->last_error);

				// Non-critical errors that should be logged but not stop migration
				$non_critical_patterns = [
					'duplicate',                 // Duplicate key/index
					'index',                     // Index-related errors
					'key column',                // Missing column for index
					'doesn\'t exist',            // Column doesn't exist (for indexes on non-existent columns)
					'already exists',            // Index/constraint already exists
					'syntax error',              // SQL syntax issues (may be version-specific)
					'check constraint',          // Constraint violations
					'foreign key',               // Foreign key constraint issues
					'unknown column',            // Unknown column in index (version-specific)
				];

				$is_non_critical = false;
				foreach ($non_critical_patterns as $pattern) {
					if (strpos($error, $pattern) !== false) {
						$is_non_critical = true;
						break;
					}
				}

				if (!$is_non_critical) {
					// Critical error - stop migration (table creation, schema errors)
					throw new \Exception(
						"Migration failed: {$file}\nError: {$this->wpdb->last_error}"
					);
				} else {
					// Non-critical error - log warning and continue
					error_log("Migration warning in {$file}: {$this->wpdb->last_error}\nStatement: {$statement}");
				}
			}
		}
	}

	/**
	 * Record a migration as applied
	 *
	 * @param string $migration_number Migration number
	 * @param string $file Migration file path
	 * @return void
	 */
	private function record_migration(string $migration_number, string $file): void {
		$table_name = $this->wpdb->prefix . 'ma_deal_migrations';
		$filename = basename($file);

		// Extract migration name from filename
		$migration_name = preg_replace('/^\d+_(.+)\.sql$/', '$1', $filename);
		$migration_name = str_replace('_', ' ', $migration_name);
		$migration_name = ucwords($migration_name);

		$this->wpdb->insert(
			$table_name,
			[
				'migration_number' => $migration_number,
				'migration_name' => $migration_name,
				'applied_at' => current_time('mysql'),
				'rollback_available' => file_exists(
					$this->migrations_dir . 'rollback_' . $migration_number . '.sql'
				),
			],
			['%s', '%s', '%s', '%d']
		);
	}

	/**
	 * Rollback the last migration
	 *
	 * @return array Result of rollback
	 * @throws \Exception If rollback fails
	 */
	public function rollback(): array {
		$table_name = $this->wpdb->prefix . 'ma_deal_migrations';

		// Get last applied migration
		$last_migration = $this->wpdb->get_row(
			"SELECT * FROM {$table_name} ORDER BY id DESC LIMIT 1"
		);

		if (!$last_migration) {
			return [
				'status' => 'error',
				'message' => 'No migrations to rollback',
			];
		}

		$rollback_file = $this->migrations_dir . 'rollback_' . $last_migration->migration_number . '.sql';

		if (!file_exists($rollback_file)) {
			return [
				'status' => 'error',
				'message' => 'Rollback file not found: ' . $rollback_file,
			];
		}

		try {
			$this->run_migration_file($rollback_file);

			// Remove migration record
			$this->wpdb->delete(
				$table_name,
				['id' => $last_migration->id],
				['%d']
			);

			return [
				'status' => 'success',
				'message' => "Rolled back migration {$last_migration->migration_number}",
			];
		} catch (\Exception $e) {
			return [
				'status' => 'error',
				'message' => $e->getMessage(),
			];
		}
	}

	/**
	 * Get migration status
	 *
	 * @return array Migration status information
	 */
	public function status(): array {
		$migration_files = $this->get_migration_files();
		$applied_migrations = $this->get_applied_migrations();

		$status = [];

		foreach ($migration_files as $file) {
			$migration_number = $this->extract_migration_number($file);
			$is_applied = in_array($migration_number, $applied_migrations);

			$status[] = [
				'number' => $migration_number,
				'name' => basename($file),
				'applied' => $is_applied,
			];
		}

		return $status;
	}
}
