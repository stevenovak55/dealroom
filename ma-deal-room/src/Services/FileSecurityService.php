<?php
/**
 * File Security Service
 *
 * Handles virus scanning, file validation, and security checks for uploaded files
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use WP_Error;

class FileSecurityService {
	/**
	 * Allowed MIME types for file uploads
	 *
	 * @var array
	 */
	private $allowed_mime_types = [
		'application/pdf',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel',
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'image/jpeg',
		'image/png',
		'image/gif',
		'text/plain',
	];

	/**
	 * Blocked file extensions (executables and scripts)
	 *
	 * @var array
	 */
	private $blocked_extensions = [
		'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js',
		'php', 'phtml', 'php3', 'php4', 'php5', 'asp', 'aspx',
		'sh', 'bash', 'csh', 'pl', 'py', 'rb', 'java', 'jar',
	];

	/**
	 * Maximum file size in bytes (configurable via environment)
	 *
	 * @var int
	 */
	private $max_file_size;

	/**
	 * ClamAV executable path
	 *
	 * @var string|null
	 */
	private $clamav_path;

	/**
	 * VirusTotal API key
	 *
	 * @var string|null
	 */
	private $virustotal_api_key;

	/**
	 * Enable virus scanning
	 *
	 * @var bool
	 */
	private $enable_virus_scanning;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->max_file_size = $this->get_env_int('MAX_FILE_SIZE', 10485760); // 10MB default
		$this->clamav_path = getenv('CLAMAV_PATH') ?: '/usr/bin/clamscan';
		$this->virustotal_api_key = getenv('VIRUSTOTAL_API_KEY') ?: null;
		$this->enable_virus_scanning = $this->get_env_bool('ENABLE_VIRUS_SCANNING', true);
	}

	/**
	 * Validate uploaded file
	 *
	 * @param array $file Uploaded file data from $_FILES
	 * @return true|WP_Error True if valid, WP_Error if validation fails
	 */
	public function validate_file(array $file) {
		// Check if file was uploaded
		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return new WP_Error('invalid_file', __('Invalid file upload', 'ma-deal-room'));
		}

		// Validate file size
		$size_check = $this->validate_file_size($file['size']);
		if (is_wp_error($size_check)) {
			return $size_check;
		}

		// Validate file type
		$type_check = $this->validate_file_type($file['tmp_name'], $file['name']);
		if (is_wp_error($type_check)) {
			return $type_check;
		}

		// Validate file extension
		$ext_check = $this->validate_file_extension($file['name']);
		if (is_wp_error($ext_check)) {
			return $ext_check;
		}

		// Scan for viruses
		if ($this->enable_virus_scanning) {
			$virus_check = $this->scan_for_viruses($file['tmp_name'], $file['name']);
			if (is_wp_error($virus_check)) {
				return $virus_check;
			}
		}

		return true;
	}

	/**
	 * Validate file size
	 *
	 * @param int $size File size in bytes
	 * @return true|WP_Error
	 */
	public function validate_file_size(int $size) {
		if ($size <= 0) {
			return new WP_Error('empty_file', __('File is empty', 'ma-deal-room'));
		}

		if ($size > $this->max_file_size) {
			$max_mb = round($this->max_file_size / 1048576, 2);
			return new WP_Error(
				'file_too_large',
				sprintf(__('File size exceeds maximum allowed (%s MB)', 'ma-deal-room'), $max_mb)
			);
		}

		return true;
	}

	/**
	 * Validate file type using MIME detection
	 *
	 * @param string $file_path Path to file
	 * @param string $file_name Original file name
	 * @return true|WP_Error
	 */
	public function validate_file_type(string $file_path, string $file_name) {
		// Use WordPress function for comprehensive validation
		$wp_filetype = wp_check_filetype_and_ext($file_path, $file_name);

		// Check if file type was detected
		if (!$wp_filetype['type']) {
			return new WP_Error('invalid_file_type', __('Unable to determine file type', 'ma-deal-room'));
		}

		// Verify MIME type is in allowed list
		if (!in_array($wp_filetype['type'], $this->allowed_mime_types, true)) {
			return new WP_Error(
				'disallowed_file_type',
				__('File type not allowed. Allowed types: PDF, Word, Excel, Images, Text', 'ma-deal-room')
			);
		}

		// Additional MIME type detection for extra security
		if (function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$mime_type = finfo_file($finfo, $file_path);
			finfo_close($finfo);

			// Verify MIME type matches WordPress detection
			if ($mime_type !== $wp_filetype['type']) {
				error_log(sprintf(
					'[FileSecurityService] MIME type mismatch: wp_check=%s, finfo=%s, file=%s',
					$wp_filetype['type'],
					$mime_type,
					$file_name
				));

				return new WP_Error(
					'mime_mismatch',
					__('File content does not match file extension', 'ma-deal-room')
				);
			}
		}

		return true;
	}

	/**
	 * Validate file extension
	 *
	 * @param string $file_name File name
	 * @return true|WP_Error
	 */
	public function validate_file_extension(string $file_name) {
		$extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

		if (in_array($extension, $this->blocked_extensions, true)) {
			return new WP_Error(
				'blocked_extension',
				__('File extension is blocked for security reasons', 'ma-deal-room')
			);
		}

		return true;
	}

	/**
	 * Scan file for viruses
	 *
	 * @param string $file_path Path to file
	 * @param string $file_name Original file name
	 * @return true|WP_Error
	 */
	public function scan_for_viruses(string $file_path, string $file_name) {
		// Try ClamAV first
		if ($this->is_clamav_available()) {
			return $this->scan_with_clamav($file_path, $file_name);
		}

		// Try VirusTotal if API key is available
		if ($this->virustotal_api_key) {
			return $this->scan_with_virustotal($file_path, $file_name);
		}

		// Log warning if no scanner available
		error_log('[FileSecurityService] Virus scanning enabled but no scanner available (ClamAV or VirusTotal)');

		// Return success but log the gap
		return true;
	}

	/**
	 * Check if ClamAV is available
	 *
	 * @return bool
	 */
	private function is_clamav_available(): bool {
		return file_exists($this->clamav_path) && is_executable($this->clamav_path);
	}

	/**
	 * Scan file with ClamAV
	 *
	 * @param string $file_path Path to file
	 * @param string $file_name Original file name
	 * @return true|WP_Error
	 */
	private function scan_with_clamav(string $file_path, string $file_name) {
		$escaped_path = escapeshellarg($file_path);
		$command = sprintf('%s --no-summary %s', $this->clamav_path, $escaped_path);

		// Execute ClamAV scan
		exec($command, $output, $return_code);

		// ClamAV return codes:
		// 0 = No virus found
		// 1 = Virus found
		// 2+ = Error

		if ($return_code === 0) {
			// No virus found
			error_log(sprintf('[FileSecurityService] ClamAV scan passed for file: %s', $file_name));
			return true;
		}

		if ($return_code === 1) {
			// Virus detected
			error_log(sprintf(
				'[FileSecurityService] VIRUS DETECTED by ClamAV in file: %s, output: %s',
				$file_name,
				implode("\n", $output)
			));

			// Quarantine the file
			$this->quarantine_file($file_path, $file_name, 'ClamAV: ' . implode(', ', $output));

			return new WP_Error(
				'virus_detected',
				__('File failed virus scan. Upload has been blocked and the incident has been logged.', 'ma-deal-room')
			);
		}

		// Scan error
		error_log(sprintf(
			'[FileSecurityService] ClamAV scan error (code %d) for file: %s, output: %s',
			$return_code,
			$file_name,
			implode("\n", $output)
		));

		// On scan error, fail safe and reject upload
		return new WP_Error(
			'scan_error',
			__('Unable to complete virus scan. Please try again or contact support.', 'ma-deal-room')
		);
	}

	/**
	 * Scan file with VirusTotal API
	 *
	 * @param string $file_path Path to file
	 * @param string $file_name Original file name
	 * @return true|WP_Error
	 */
	private function scan_with_virustotal(string $file_path, string $file_name) {
		// Get file SHA-256 hash
		$file_hash = hash_file('sha256', $file_path);

		// Check if file was previously scanned
		$url = 'https://www.virustotal.com/vtapi/v2/file/report';
		$params = [
			'apikey' => $this->virustotal_api_key,
			'resource' => $file_hash
		];

		$response = wp_remote_get($url . '?' . http_build_query($params));

		if (is_wp_error($response)) {
			error_log(sprintf(
				'[FileSecurityService] VirusTotal API error: %s',
				$response->get_error_message()
			));
			return true; // Fail open if API is unavailable
		}

		$body = json_decode(wp_remote_retrieve_body($response), true);

		if (isset($body['response_code']) && $body['response_code'] === 1) {
			// File was previously scanned
			$positives = $body['positives'] ?? 0;

			if ($positives > 0) {
				error_log(sprintf(
					'[FileSecurityService] VirusTotal detected virus in file: %s (%d/%d scanners flagged)',
					$file_name,
					$positives,
					$body['total'] ?? 0
				));

				$this->quarantine_file($file_path, $file_name, sprintf('VirusTotal: %d positives', $positives));

				return new WP_Error(
					'virus_detected',
					__('File failed virus scan. Upload has been blocked and the incident has been logged.', 'ma-deal-room')
				);
			}

			// File is clean
			return true;
		}

		// File not in database, upload and scan
		// Note: This is async and would require implementing a queue system
		// For now, we'll just log and pass through
		error_log(sprintf(
			'[FileSecurityService] File %s not in VirusTotal database (hash: %s). Consider implementing async scanning.',
			$file_name,
			$file_hash
		));

		return true;
	}

	/**
	 * Quarantine infected file
	 *
	 * @param string $file_path Path to infected file
	 * @param string $file_name Original file name
	 * @param string $scan_result Scan result details
	 * @return void
	 */
	private function quarantine_file(string $file_path, string $file_name, string $scan_result): void {
		// Create quarantine directory
		$upload_dir = wp_upload_dir();
		$quarantine_dir = $upload_dir['basedir'] . '/ma-deal-room/quarantine';

		if (!file_exists($quarantine_dir)) {
			wp_mkdir_p($quarantine_dir);
		}

		// Move file to quarantine with timestamp
		$quarantine_file = sprintf(
			'%s/%s_%s',
			$quarantine_dir,
			date('Y-m-d_H-i-s'),
			basename($file_name)
		);

		@rename($file_path, $quarantine_file);

		// Set restrictive permissions
		@chmod($quarantine_file, 0000);

		// Log quarantine action
		error_log(sprintf(
			'[FileSecurityService] File quarantined: %s -> %s (Reason: %s)',
			$file_name,
			$quarantine_file,
			$scan_result
		));

		// TODO: Send admin notification email
	}

	/**
	 * Calculate file checksum (SHA-256)
	 *
	 * @param string $file_path Path to file
	 * @return string SHA-256 checksum
	 */
	public function calculate_checksum(string $file_path): string {
		return hash_file('sha256', $file_path);
	}

	/**
	 * Verify file checksum
	 *
	 * @param string $file_path Path to file
	 * @param string $expected_checksum Expected SHA-256 checksum
	 * @return bool True if checksum matches
	 */
	public function verify_checksum(string $file_path, string $expected_checksum): bool {
		$actual_checksum = $this->calculate_checksum($file_path);
		return hash_equals($expected_checksum, $actual_checksum);
	}

	/**
	 * Get file scan result for logging
	 *
	 * @param string $file_path Path to file
	 * @param string $file_name Original file name
	 * @return array Scan result metadata
	 */
	public function get_scan_metadata(string $file_path, string $file_name): array {
		$metadata = [
			'checksum' => $this->calculate_checksum($file_path),
			'scan_date' => current_time('mysql'),
			'scan_status' => 'pending',
			'scanner_used' => null
		];

		if (!$this->enable_virus_scanning) {
			$metadata['scan_status'] = 'disabled';
			return $metadata;
		}

		if ($this->is_clamav_available()) {
			$metadata['scanner_used'] = 'clamav';
			$scan_result = $this->scan_with_clamav($file_path, $file_name);
			$metadata['scan_status'] = is_wp_error($scan_result) ? 'infected' : 'clean';
		} elseif ($this->virustotal_api_key) {
			$metadata['scanner_used'] = 'virustotal';
			$scan_result = $this->scan_with_virustotal($file_path, $file_name);
			$metadata['scan_status'] = is_wp_error($scan_result) ? 'infected' : 'clean';
		} else {
			$metadata['scan_status'] = 'no_scanner';
		}

		return $metadata;
	}

	/**
	 * Get environment variable as integer
	 *
	 * @param string $key Environment variable key
	 * @param int $default Default value
	 * @return int
	 */
	private function get_env_int(string $key, int $default): int {
		$value = getenv($key);
		return $value !== false ? (int) $value : $default;
	}

	/**
	 * Get environment variable as boolean
	 *
	 * @param string $key Environment variable key
	 * @param bool $default Default value
	 * @return bool
	 */
	private function get_env_bool(string $key, bool $default): bool {
		$value = getenv($key);
		if ($value === false) {
			return $default;
		}
		return filter_var($value, FILTER_VALIDATE_BOOLEAN);
	}
}
