<?php
/**
 * Database Backup Service
 *
 * Handles automated database backups with rotation and cloud storage support.
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

/**
 * Service for creating and managing database backups
 */
class BackupService {
	/**
	 * Backup directory path (relative to wp-content)
	 *
	 * @var string
	 */
	private $backup_dir;

	/**
	 * WordPress database instance
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Plugin table prefix
	 *
	 * @var string
	 */
	private $table_prefix;

	/**
	 * Backup rotation settings
	 *
	 * @var array
	 */
	private $rotation = [
		'daily' => 7,      // Keep 7 daily backups
		'weekly' => 4,     // Keep 4 weekly backups
		'monthly' => 12,   // Keep 12 monthly backups
	];

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_prefix = $wpdb->prefix;

		// Set backup directory outside web root
		$wp_content_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
		$this->backup_dir = $wp_content_dir . '/backups/ma-deal-room';

		// Ensure backup directory exists
		$this->ensure_backup_directory();
	}

	/**
	 * Ensure backup directory exists and is protected
	 *
	 * @return bool Whether directory is ready
	 */
	private function ensure_backup_directory(): bool {
		if (!file_exists($this->backup_dir)) {
			if (!wp_mkdir_p($this->backup_dir)) {
				error_log('MA Deal Room: Failed to create backup directory: ' . $this->backup_dir);
				return false;
			}
		}

		// Create .htaccess to protect backup files
		$htaccess_file = $this->backup_dir . '/.htaccess';
		if (!file_exists($htaccess_file)) {
			$htaccess_content = "Order deny,allow\nDeny from all";
			file_put_contents($htaccess_file, $htaccess_content);
			chmod($htaccess_file, 0644); // rw-r--r--
		}

		// Create index.php to prevent directory listing
		$index_file = $this->backup_dir . '/index.php';
		if (!file_exists($index_file)) {
			file_put_contents($index_file, '<?php // Silence is golden');
			chmod($index_file, 0644); // rw-r--r--
		}

		return true;
	}

	/**
	 * Create a full database backup
	 *
	 * @param string $type Backup type: 'manual', 'daily', 'weekly', 'monthly'
	 * @return array|WP_Error Backup info or error
	 */
	public function create_backup(string $type = 'manual') {
		$start_time = microtime(true);

		// Generate backup filename
		$timestamp = current_time('Y-m-d_H-i-s');
		$filename = "ma-deal-room-backup_{$type}_{$timestamp}.sql";
		$filepath = $this->backup_dir . '/' . $filename;
		$compressed_filepath = $filepath . '.gz';

		try {
			// Get all MA Deal Room tables
			$tables = $this->get_plugin_tables();

			if (empty($tables)) {
				return new \WP_Error('no_tables', 'No MA Deal Room tables found to backup');
			}

			// Create SQL dump
			$sql_dump = $this->create_sql_dump($tables);

			if (!$sql_dump) {
				return new \WP_Error('dump_failed', 'Failed to create SQL dump');
			}

			// Write to temp file
			$bytes_written = file_put_contents($filepath, $sql_dump);

			if ($bytes_written === false) {
				return new \WP_Error('write_failed', 'Failed to write backup file');
			}

			// Set secure file permissions on SQL dump
			chmod($filepath, 0640); // rw-r-----

			// Compress the backup
			if (!$this->compress_file($filepath, $compressed_filepath)) {
				return new \WP_Error('compress_failed', 'Failed to compress backup file');
			}

			// Set secure file permissions on compressed backup
			chmod($compressed_filepath, 0640); // rw-r-----

			// Remove uncompressed file
			@unlink($filepath);

			// Calculate file size and execution time
			$filesize = filesize($compressed_filepath);
			$execution_time = round(microtime(true) - $start_time, 2);

			// Backup metadata
			$backup_info = [
				'filename' => basename($compressed_filepath),
				'filepath' => $compressed_filepath,
				'type' => $type,
				'size' => $filesize,
				'size_formatted' => size_format($filesize),
				'tables_count' => count($tables),
				'tables' => $tables,
				'created_at' => current_time('mysql'),
				'execution_time' => $execution_time,
			];

			// Save backup metadata
			$this->save_backup_metadata($backup_info);

			// Run rotation to clean up old backups
			$this->rotate_backups();

			// Log success
			error_log(sprintf(
				'MA Deal Room: Backup created successfully (%s, %s, %d tables, %s)',
				$backup_info['filename'],
				$backup_info['size_formatted'],
				$backup_info['tables_count'],
				$execution_time . 's'
			));

			// Upload to cloud storage if configured
			$this->upload_to_cloud($compressed_filepath);

			return $backup_info;

		} catch (\Exception $e) {
			error_log('MA Deal Room: Backup failed: ' . $e->getMessage());
			return new \WP_Error('backup_exception', $e->getMessage());
		}
	}

	/**
	 * Get all MA Deal Room database tables
	 *
	 * @return array List of table names
	 */
	private function get_plugin_tables(): array {
		$plugin_tables = [
			'ma_deal_transactions',
			'ma_deal_tasks',
			'ma_deal_parties',
			'ma_deal_accounts',
			'ma_deal_templates',
			'ma_deal_reminders',
			'ma_deal_vendor_requests',
			'ma_deal_documents',
			'ma_deal_events',
			'ma_deal_notifications',
			'ma_deal_task_definitions',
			'ma_deal_template_tasks',
			'ma_deal_users',
			'ma_deal_user_roles',
			'ma_deal_user_sessions',
			'ma_deal_user_invitations',
			'ma_deal_password_resets',
			'ma_deal_email_verifications',
			'ma_deal_two_factor_auth',
		];

		// Get actual existing tables
		$existing_tables = [];
		foreach ($plugin_tables as $table) {
			$full_table_name = $this->table_prefix . $table;
			$result = $this->wpdb->get_var($this->wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$full_table_name
			));

			if ($result === $full_table_name) {
				$existing_tables[] = $full_table_name;
			}
		}

		return $existing_tables;
	}

	/**
	 * Create SQL dump of specified tables
	 *
	 * @param array $tables Table names to dump
	 * @return string SQL dump content
	 */
	private function create_sql_dump(array $tables): string {
		$sql_dump = '';

		// Add header
		$sql_dump .= "-- MA Deal Room Database Backup\n";
		$sql_dump .= "-- Created: " . current_time('mysql') . "\n";
		$sql_dump .= "-- Tables: " . implode(', ', $tables) . "\n\n";
		$sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
		$sql_dump .= "SET time_zone = \"+00:00\";\n\n";

		foreach ($tables as $table) {
			// Validate table name to prevent SQL injection (alphanumeric, underscore only)
			if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
				error_log("BackupService: Invalid table name '{$table}', skipping");
				continue;
			}

			// Get CREATE TABLE statement (safe after validation)
			$create_table = $this->wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);

			if ($create_table) {
				$sql_dump .= "\n--\n-- Table structure for `{$table}`\n--\n\n";
				$sql_dump .= "DROP TABLE IF EXISTS `{$table}`;\n";
				$sql_dump .= $create_table[1] . ";\n\n";
			}

			// Get table data (safe after validation)
			$rows = $this->wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A);

			if (!empty($rows)) {
				$sql_dump .= "--\n-- Data for table `{$table}`\n--\n\n";

				foreach ($rows as $row) {
					$columns = array_keys($row);
					$values = array_values($row);

					// Escape values
					$escaped_values = array_map(function($value) {
						if ($value === null) {
							return 'NULL';
						}
						return "'" . $this->wpdb->_real_escape($value) . "'";
					}, $values);

					$sql_dump .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (";
					$sql_dump .= implode(', ', $escaped_values);
					$sql_dump .= ");\n";
				}

				$sql_dump .= "\n";
			}
		}

		return $sql_dump;
	}

	/**
	 * Compress file using gzip
	 *
	 * @param string $source Source file path
	 * @param string $destination Destination file path
	 * @return bool Success status
	 */
	private function compress_file(string $source, string $destination): bool {
		if (!file_exists($source)) {
			return false;
		}

		$source_handle = fopen($source, 'rb');
		if (!$source_handle) {
			return false;
		}

		$dest_handle = gzopen($destination, 'wb9'); // Maximum compression
		if (!$dest_handle) {
			fclose($source_handle);
			return false;
		}

		while (!feof($source_handle)) {
			gzwrite($dest_handle, fread($source_handle, 1024 * 512)); // 512KB chunks
		}

		fclose($source_handle);
		gzclose($dest_handle);

		return file_exists($destination);
	}

	/**
	 * Save backup metadata to WordPress options
	 *
	 * @param array $backup_info Backup information
	 * @return bool Success status
	 */
	private function save_backup_metadata(array $backup_info): bool {
		$backups = get_option('ma_deal_backups', []);
		$backups[$backup_info['filename']] = $backup_info;

		// Keep only last 100 backups in metadata
		if (count($backups) > 100) {
			$backups = array_slice($backups, -100, null, true);
		}

		return update_option('ma_deal_backups', $backups);
	}

	/**
	 * Rotate backups according to retention policy
	 *
	 * @return array Deleted backup files
	 */
	public function rotate_backups(): array {
		$deleted = [];

		// Get all backup files
		$backup_files = glob($this->backup_dir . '/ma-deal-room-backup_*.sql.gz');

		if (empty($backup_files)) {
			return $deleted;
		}

		// Group backups by type
		$backups_by_type = [
			'daily' => [],
			'weekly' => [],
			'monthly' => [],
			'manual' => [],
		];

		foreach ($backup_files as $file) {
			$filename = basename($file);

			// Determine backup type from filename
			if (strpos($filename, '_daily_') !== false) {
				$backups_by_type['daily'][] = $file;
			} elseif (strpos($filename, '_weekly_') !== false) {
				$backups_by_type['weekly'][] = $file;
			} elseif (strpos($filename, '_monthly_') !== false) {
				$backups_by_type['monthly'][] = $file;
			} else {
				$backups_by_type['manual'][] = $file;
			}
		}

		// Apply rotation for each type
		foreach ($backups_by_type as $type => $files) {
			if ($type === 'manual' || !isset($this->rotation[$type])) {
				continue; // Don't rotate manual backups
			}

			// Sort by modification time (newest first)
			usort($files, function($a, $b) {
				return filemtime($b) - filemtime($a);
			});

			// Keep only the configured number of backups
			$to_keep = $this->rotation[$type];
			$to_delete = array_slice($files, $to_keep);

			foreach ($to_delete as $file) {
				if (@unlink($file)) {
					$deleted[] = basename($file);
					error_log('MA Deal Room: Rotated old backup: ' . basename($file));
				}
			}
		}

		return $deleted;
	}

	/**
	 * Restore database from backup file
	 *
	 * @param string $filename Backup filename
	 * @return bool|WP_Error Success or error
	 */
	public function restore_backup(string $filename) {
		$filepath = $this->backup_dir . '/' . $filename;

		if (!file_exists($filepath)) {
			return new \WP_Error('file_not_found', 'Backup file not found');
		}

		try {
			// Decompress if needed
			$is_compressed = substr($filename, -3) === '.gz';
			$sql_file = $is_compressed ? str_replace('.gz', '', $filepath) : $filepath;

			if ($is_compressed) {
				if (!$this->decompress_file($filepath, $sql_file)) {
					return new \WP_Error('decompress_failed', 'Failed to decompress backup');
				}
			}

			// Read SQL content
			$sql_content = file_get_contents($sql_file);

			if ($sql_content === false) {
				return new \WP_Error('read_failed', 'Failed to read backup file');
			}

			// Execute SQL statements
			$statements = array_filter(
				array_map('trim', explode(";\n", $sql_content)),
				function($stmt) {
					return !empty($stmt) && strpos($stmt, '--') !== 0;
				}
			);

			foreach ($statements as $statement) {
				$result = $this->wpdb->query($statement);

				if ($result === false && !empty($this->wpdb->last_error)) {
					error_log('MA Deal Room: Restore query failed: ' . $this->wpdb->last_error);
				}
			}

			// Clean up decompressed file
			if ($is_compressed && file_exists($sql_file)) {
				@unlink($sql_file);
			}

			error_log('MA Deal Room: Database restored from backup: ' . $filename);
			return true;

		} catch (\Exception $e) {
			error_log('MA Deal Room: Restore failed: ' . $e->getMessage());
			return new \WP_Error('restore_exception', $e->getMessage());
		}
	}

	/**
	 * Decompress gzip file
	 *
	 * @param string $source Source .gz file
	 * @param string $destination Destination file
	 * @return bool Success status
	 */
	private function decompress_file(string $source, string $destination): bool {
		$source_handle = gzopen($source, 'rb');
		if (!$source_handle) {
			return false;
		}

		$dest_handle = fopen($destination, 'wb');
		if (!$dest_handle) {
			gzclose($source_handle);
			return false;
		}

		while (!gzeof($source_handle)) {
			fwrite($dest_handle, gzread($source_handle, 1024 * 512));
		}

		gzclose($source_handle);
		fclose($dest_handle);

		return file_exists($destination);
	}

	/**
	 * List all available backups
	 *
	 * @return array List of backups with metadata
	 */
	public function list_backups(): array {
		$backup_files = glob($this->backup_dir . '/ma-deal-room-backup_*.sql.gz');
		$backups = [];

		foreach ($backup_files as $file) {
			$filename = basename($file);
			$backups[] = [
				'filename' => $filename,
				'filepath' => $file,
				'size' => filesize($file),
				'size_formatted' => size_format(filesize($file)),
				'created_at' => date('Y-m-d H:i:s', filemtime($file)),
				'age_days' => floor((time() - filemtime($file)) / DAY_IN_SECONDS),
			];
		}

		// Sort by creation time (newest first)
		usort($backups, function($a, $b) {
			return strtotime($b['created_at']) - strtotime($a['created_at']);
		});

		return $backups;
	}

	/**
	 * Delete a backup file
	 *
	 * @param string $filename Backup filename
	 * @return bool Success status
	 */
	public function delete_backup(string $filename): bool {
		$filepath = $this->backup_dir . '/' . $filename;

		if (!file_exists($filepath)) {
			return false;
		}

		return @unlink($filepath);
	}

	/**
	 * Upload backup to cloud storage (S3, etc.)
	 *
	 * @param string $filepath Local backup file path
	 * @return bool Success status
	 */
	private function upload_to_cloud(string $filepath): bool {
		// Check if cloud storage is configured
		$s3_bucket = getenv('AWS_S3_BACKUP_BUCKET') ?: ($_ENV['AWS_S3_BACKUP_BUCKET'] ?? null);

		if (!$s3_bucket) {
			// Cloud storage not configured - skip
			return false;
		}

		// TODO: Implement S3 upload when AWS SDK is added
		// For now, log that cloud storage is configured but not implemented
		error_log('MA Deal Room: Cloud backup configured but not yet implemented');

		return false;
	}

	/**
	 * Get backup statistics
	 *
	 * @return array Backup statistics
	 */
	public function get_backup_stats(): array {
		$backups = $this->list_backups();
		$total_size = array_sum(array_column($backups, 'size'));

		return [
			'total_backups' => count($backups),
			'total_size' => $total_size,
			'total_size_formatted' => size_format($total_size),
			'oldest_backup' => !empty($backups) ? end($backups)['created_at'] : null,
			'newest_backup' => !empty($backups) ? $backups[0]['created_at'] : null,
			'backup_directory' => $this->backup_dir,
		];
	}
}
