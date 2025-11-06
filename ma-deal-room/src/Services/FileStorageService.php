<?php
/**
 * File Storage Service
 *
 * Handles secure file storage, retrieval, and deletion with proper permissions
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use WP_Error;

class FileStorageService {
	/**
	 * Base storage directory (outside web root if possible)
	 *
	 * @var string
	 */
	private $storage_base_dir;

	/**
	 * Storage subdirectory for MA Deal Room files
	 *
	 * @var string
	 */
	private $storage_subdir = 'ma-deal-room/secure-uploads';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Try to use storage outside web root
		$upload_dir = wp_upload_dir();
		$this->storage_base_dir = $upload_dir['basedir'];

		// Check if we can use a directory above web root
		$above_webroot = dirname(ABSPATH) . '/ma-deal-room-storage';
		if ($this->can_create_directory($above_webroot)) {
			$this->storage_base_dir = $above_webroot;
			error_log('[FileStorageService] Using storage outside web root: ' . $above_webroot);
		} else {
			error_log('[FileStorageService] Using storage inside web root (uploads): ' . $this->storage_base_dir);
		}

		// Ensure base directory exists and is protected
		$this->initialize_storage();
	}

	/**
	 * Initialize storage directory with security protections
	 *
	 * @return void
	 */
	private function initialize_storage(): void {
		$full_storage_dir = $this->get_full_storage_path();

		// Create directory if it doesn't exist
		if (!file_exists($full_storage_dir)) {
			wp_mkdir_p($full_storage_dir);
		}

		// Create .htaccess to prevent direct access
		$htaccess_file = $full_storage_dir . '/.htaccess';
		if (!file_exists($htaccess_file)) {
			$htaccess_content = "# Prevent direct access to uploaded files\n";
			$htaccess_content .= "Order deny,allow\n";
			$htaccess_content .= "Deny from all\n";
			$htaccess_content .= "# Files must be served through authenticated endpoint\n";

			file_put_contents($htaccess_file, $htaccess_content);
			chmod($htaccess_file, 0644);
		}

		// Create index.php to prevent directory listing
		$index_file = $full_storage_dir . '/index.php';
		if (!file_exists($index_file)) {
			$index_content = "<?php\n// Silence is golden\n";
			file_put_contents($index_file, $index_content);
			chmod($index_file, 0644);
		}

		// Set directory permissions
		chmod($full_storage_dir, 0755);
	}

	/**
	 * Get full storage path
	 *
	 * @param string $subpath Optional subpath within storage
	 * @return string
	 */
	private function get_full_storage_path(string $subpath = ''): string {
		$base = $this->storage_base_dir . '/' . $this->storage_subdir;

		if ($subpath) {
			return $base . '/' . ltrim($subpath, '/');
		}

		return $base;
	}

	/**
	 * Check if directory can be created and is writable
	 *
	 * @param string $path Directory path
	 * @return bool
	 */
	private function can_create_directory(string $path): bool {
		// Check if path is allowed by open_basedir restriction
		if (ini_get('open_basedir')) {
			$allowed_paths = explode(PATH_SEPARATOR, ini_get('open_basedir'));
			$is_allowed = false;
			foreach ($allowed_paths as $allowed_path) {
				if (strpos($path, rtrim($allowed_path, '/')) === 0) {
					$is_allowed = true;
					break;
				}
			}
			if (!$is_allowed) {
				return false;
			}
		}

		// If directory exists, check if writable
		if (@file_exists($path)) {
			return @is_writable($path);
		}

		// Try to create directory
		$parent = dirname($path);
		if (!@is_writable($parent)) {
			return false;
		}

		// Test creation
		if (@mkdir($path, 0755, true)) {
			return true;
		}

		return false;
	}

	/**
	 * Store uploaded file securely
	 *
	 * @param string $temp_file_path Path to temporary uploaded file
	 * @param int $transaction_id Transaction ID for organizing files
	 * @param string $original_filename Original filename (for reference only)
	 * @return array|WP_Error Array with storage info on success, WP_Error on failure
	 */
	public function store_file(string $temp_file_path, int $transaction_id, string $original_filename) {
		// Generate secure subdirectory structure
		$subdir = $this->generate_subdirectory($transaction_id);
		$target_dir = $this->get_full_storage_path($subdir);

		// Create directory if it doesn't exist
		if (!file_exists($target_dir)) {
			if (!wp_mkdir_p($target_dir)) {
				return new WP_Error(
					'storage_error',
					__('Failed to create storage directory', 'ma-deal-room')
				);
			}
			chmod($target_dir, 0755);
		}

		// Generate secure random filename
		$secure_filename = $this->generate_secure_filename($original_filename);
		$target_file = $target_dir . '/' . $secure_filename;

		// Move file to secure storage
		if (!@rename($temp_file_path, $target_file)) {
			// If rename fails, try copy
			if (!@copy($temp_file_path, $target_file)) {
				return new WP_Error(
					'storage_error',
					__('Failed to move file to secure storage', 'ma-deal-room')
				);
			}
			@unlink($temp_file_path);
		}

		// Set restrictive file permissions (read/write for owner, read for group, none for others)
		chmod($target_file, 0640);

		// Return storage information
		return [
			'file_path' => $subdir . '/' . $secure_filename,
			'secure_filename' => $secure_filename,
			'storage_directory' => $target_dir,
			'full_path' => $target_file,
			'original_filename' => $original_filename
		];
	}

	/**
	 * Generate subdirectory structure for organizing files
	 *
	 * @param int $transaction_id Transaction ID
	 * @return string Subdirectory path (relative)
	 */
	private function generate_subdirectory(int $transaction_id): string {
		// Organize by transaction and year/month for easier management
		return sprintf(
			'transactions/%d/%s/%s',
			$transaction_id,
			date('Y'),
			date('m')
		);
	}

	/**
	 * Generate secure random filename
	 *
	 * @param string $original_filename Original filename
	 * @return string Secure random filename with preserved extension
	 */
	private function generate_secure_filename(string $original_filename): string {
		// Get original extension
		$extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

		// Generate random filename
		// Use 32 bytes of random data for 64 character hex string
		$random = bin2hex(random_bytes(32));

		// Add timestamp for uniqueness
		$timestamp = time();

		// Combine: timestamp-random.ext
		return sprintf('%d-%s.%s', $timestamp, $random, $extension);
	}

	/**
	 * Retrieve file for download
	 *
	 * @param string $file_path Relative file path
	 * @return array|WP_Error File information on success, WP_Error on failure
	 */
	public function retrieve_file(string $file_path) {
		$full_path = $this->get_full_storage_path($file_path);

		// Validate file path (prevent directory traversal)
		$real_storage_base = realpath($this->get_full_storage_path());
		$real_file_path = realpath($full_path);

		// If realpath returns false, file doesn't exist
		if ($real_file_path === false) {
			return new WP_Error(
				'file_not_found',
				__('File not found', 'ma-deal-room')
			);
		}

		// Ensure file is within storage directory (prevent path traversal)
		if (strpos($real_file_path, $real_storage_base) !== 0) {
			error_log(sprintf(
				'[FileStorageService] Path traversal attempt detected: %s',
				$file_path
			));

			return new WP_Error(
				'security_error',
				__('Invalid file path', 'ma-deal-room')
			);
		}

		// Check if file exists and is readable
		if (!file_exists($real_file_path) || !is_readable($real_file_path)) {
			return new WP_Error(
				'file_not_found',
				__('File not found or not readable', 'ma-deal-room')
			);
		}

		// Get file information
		return [
			'full_path' => $real_file_path,
			'size' => filesize($real_file_path),
			'modified' => filemtime($real_file_path)
		];
	}

	/**
	 * Serve file for download with proper headers
	 *
	 * @param string $file_path Relative file path
	 * @param string $original_filename Original filename for Content-Disposition
	 * @param string $mime_type MIME type
	 * @return void|WP_Error Exits with file output on success, WP_Error on failure
	 */
	public function serve_file(string $file_path, string $original_filename, string $mime_type) {
		$file_info = $this->retrieve_file($file_path);

		if (is_wp_error($file_info)) {
			return $file_info;
		}

		// Clear any previous output
		if (ob_get_level()) {
			ob_end_clean();
		}

		// Set headers for secure file download
		header('Content-Type: ' . $mime_type);
		header('Content-Disposition: attachment; filename="' . addslashes($original_filename) . '"');
		header('Content-Length: ' . $file_info['size']);
		header('Content-Transfer-Encoding: binary');
		header('Cache-Control: private, max-age=0, must-revalidate');
		header('Pragma: public');
		header('Expires: 0');

		// Security headers
		header('X-Content-Type-Options: nosniff');
		header('X-Frame-Options: DENY');

		// Output file
		readfile($file_info['full_path']);
		exit;
	}

	/**
	 * Delete file from storage
	 *
	 * @param string $file_path Relative file path
	 * @return true|WP_Error True on success, WP_Error on failure
	 */
	public function delete_file(string $file_path) {
		$file_info = $this->retrieve_file($file_path);

		if (is_wp_error($file_info)) {
			return $file_info;
		}

		// Delete file
		if (!@unlink($file_info['full_path'])) {
			return new WP_Error(
				'delete_error',
				__('Failed to delete file', 'ma-deal-room')
			);
		}

		// Try to remove empty parent directories
		$this->cleanup_empty_directories($file_path);

		return true;
	}

	/**
	 * Cleanup empty parent directories after file deletion
	 *
	 * @param string $file_path Relative file path
	 * @return void
	 */
	private function cleanup_empty_directories(string $file_path): void {
		$dir = dirname($this->get_full_storage_path($file_path));
		$base = $this->get_full_storage_path();

		// Walk up directory tree and remove empty directories
		while ($dir !== $base && is_dir($dir)) {
			// Check if directory is empty (only contains . and ..)
			$files = scandir($dir);
			$files = array_diff($files, ['.', '..', '.htaccess', 'index.php']);

			if (empty($files)) {
				@rmdir($dir);
				$dir = dirname($dir);
			} else {
				break;
			}
		}
	}

	/**
	 * Get storage statistics
	 *
	 * @return array Storage statistics
	 */
	public function get_storage_stats(): array {
		$storage_dir = $this->get_full_storage_path();

		$stats = [
			'storage_location' => $storage_dir,
			'is_outside_webroot' => strpos($storage_dir, ABSPATH) === false,
			'total_size' => 0,
			'file_count' => 0,
		];

		// Calculate total size and count (recursively)
		if (file_exists($storage_dir)) {
			$this->calculate_directory_stats($storage_dir, $stats);
		}

		return $stats;
	}

	/**
	 * Calculate directory statistics recursively
	 *
	 * @param string $dir Directory path
	 * @param array &$stats Statistics array (passed by reference)
	 * @return void
	 */
	private function calculate_directory_stats(string $dir, array &$stats): void {
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $file) {
			if ($file->isFile() && !in_array($file->getFilename(), ['.htaccess', 'index.php'])) {
				$stats['total_size'] += $file->getSize();
				$stats['file_count']++;
			}
		}
	}

	/**
	 * Validate checksum after storage
	 *
	 * @param string $file_path Relative file path
	 * @param string $expected_checksum Expected SHA-256 checksum
	 * @return bool True if checksum matches
	 */
	public function validate_stored_file_checksum(string $file_path, string $expected_checksum): bool {
		$file_info = $this->retrieve_file($file_path);

		if (is_wp_error($file_info)) {
			return false;
		}

		$actual_checksum = hash_file('sha256', $file_info['full_path']);
		return hash_equals($expected_checksum, $actual_checksum);
	}
}
