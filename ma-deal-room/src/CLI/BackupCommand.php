<?php
/**
 * WP-CLI Backup Command
 *
 * Provides CLI commands for database backup and restore operations.
 *
 * @package MADealRoom\CLI
 * @since 1.0.0
 */

namespace MADealRoom\CLI;

use MADealRoom\Services\BackupService;
use WP_CLI;

/**
 * Manage database backups for MA Deal Room
 */
class BackupCommand {
	/**
	 * Backup service instance
	 *
	 * @var BackupService
	 */
	private $backup_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->backup_service = new BackupService();
	}

	/**
	 * Create a database backup
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Backup type: manual, daily, weekly, monthly
	 * ---
	 * default: manual
	 * options:
	 *   - manual
	 *   - daily
	 *   - weekly
	 *   - monthly
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a manual backup
	 *     $ wp ma-deal backup
	 *
	 *     # Create a daily backup
	 *     $ wp ma-deal backup --type=daily
	 *
	 * @when after_wp_load
	 */
	public function __invoke($args, $assoc_args) {
		$type = isset($assoc_args['type']) ? $assoc_args['type'] : 'manual';

		WP_CLI::log('Creating database backup...');

		$result = $this->backup_service->create_backup($type);

		if (is_wp_error($result)) {
			WP_CLI::error($result->get_error_message());
		}

		WP_CLI::success(sprintf(
			'Backup created successfully: %s (%s, %d tables, %ss)',
			$result['filename'],
			$result['size_formatted'],
			$result['tables_count'],
			$result['execution_time']
		));

		WP_CLI::log('Backup location: ' . $result['filepath']);
	}

	/**
	 * Restore database from backup
	 *
	 * ## OPTIONS
	 *
	 * <filename>
	 * : Backup filename to restore from
	 *
	 * [--yes]
	 * : Skip confirmation prompt
	 *
	 * ## EXAMPLES
	 *
	 *     # List available backups first
	 *     $ wp ma-deal backup list
	 *
	 *     # Restore from backup
	 *     $ wp ma-deal backup restore ma-deal-room-backup_manual_2025-11-01_12-00-00.sql.gz
	 *
	 *     # Restore without confirmation
	 *     $ wp ma-deal backup restore ma-deal-room-backup_manual_2025-11-01_12-00-00.sql.gz --yes
	 *
	 * @when after_wp_load
	 */
	public function restore($args, $assoc_args) {
		if (empty($args[0])) {
			WP_CLI::error('Please specify a backup filename');
		}

		$filename = $args[0];

		// Confirm before restoring
		if (!isset($assoc_args['yes'])) {
			WP_CLI::confirm(
				WP_CLI::colorize('%RWARNING: This will overwrite all MA Deal Room data! Are you sure?%n'),
				$assoc_args
			);
		}

		WP_CLI::log('Restoring database from backup: ' . $filename);

		$result = $this->backup_service->restore_backup($filename);

		if (is_wp_error($result)) {
			WP_CLI::error($result->get_error_message());
		}

		WP_CLI::success('Database restored successfully from: ' . $filename);
	}

	/**
	 * List all available backups
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List all backups
	 *     $ wp ma-deal backup list
	 *
	 *     # List backups in JSON format
	 *     $ wp ma-deal backup list --format=json
	 *
	 * @when after_wp_load
	 * @subcommand list
	 */
	public function list_backups($args, $assoc_args) {
		$backups = $this->backup_service->list_backups();

		if (empty($backups)) {
			WP_CLI::log('No backups found');
			return;
		}

		$format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';

		// Prepare data for display
		$display_data = array_map(function($backup) {
			return [
				'filename' => $backup['filename'],
				'size' => $backup['size_formatted'],
				'created' => $backup['created_at'],
				'age_days' => $backup['age_days'] . ' days',
			];
		}, $backups);

		WP_CLI\Utils\format_items($format, $display_data, ['filename', 'size', 'created', 'age_days']);
	}

	/**
	 * Delete a backup file
	 *
	 * ## OPTIONS
	 *
	 * <filename>
	 * : Backup filename to delete
	 *
	 * [--yes]
	 * : Skip confirmation prompt
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a backup
	 *     $ wp ma-deal backup delete ma-deal-room-backup_manual_2025-11-01_12-00-00.sql.gz
	 *
	 * @when after_wp_load
	 */
	public function delete($args, $assoc_args) {
		if (empty($args[0])) {
			WP_CLI::error('Please specify a backup filename');
		}

		$filename = $args[0];

		// Confirm before deleting
		if (!isset($assoc_args['yes'])) {
			WP_CLI::confirm(
				'Are you sure you want to delete this backup?',
				$assoc_args
			);
		}

		$result = $this->backup_service->delete_backup($filename);

		if (!$result) {
			WP_CLI::error('Failed to delete backup or backup not found');
		}

		WP_CLI::success('Backup deleted: ' . $filename);
	}

	/**
	 * Show backup statistics
	 *
	 * ## EXAMPLES
	 *
	 *     # Show backup statistics
	 *     $ wp ma-deal backup stats
	 *
	 * @when after_wp_load
	 */
	public function stats() {
		$stats = $this->backup_service->get_backup_stats();

		WP_CLI::log('');
		WP_CLI::log(WP_CLI::colorize('%G=== MA Deal Room Backup Statistics ===%n'));
		WP_CLI::log('');
		WP_CLI::log(sprintf('Total Backups:     %d', $stats['total_backups']));
		WP_CLI::log(sprintf('Total Size:        %s', $stats['total_size_formatted']));
		WP_CLI::log(sprintf('Newest Backup:     %s', $stats['newest_backup'] ?? 'N/A'));
		WP_CLI::log(sprintf('Oldest Backup:     %s', $stats['oldest_backup'] ?? 'N/A'));
		WP_CLI::log(sprintf('Backup Directory:  %s', $stats['backup_directory']));
		WP_CLI::log('');
	}

	/**
	 * Clean up old backups according to rotation policy
	 *
	 * ## EXAMPLES
	 *
	 *     # Clean up old backups
	 *     $ wp ma-deal backup cleanup
	 *
	 * @when after_wp_load
	 */
	public function cleanup() {
		WP_CLI::log('Running backup rotation...');

		$deleted = $this->backup_service->rotate_backups();

		if (empty($deleted)) {
			WP_CLI::success('No old backups to clean up');
			return;
		}

		WP_CLI::log(sprintf('Deleted %d old backup(s):', count($deleted)));
		foreach ($deleted as $filename) {
			WP_CLI::log('  - ' . $filename);
		}

		WP_CLI::success('Backup cleanup complete');
	}
}
