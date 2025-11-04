<?php
/**
 * Base REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use MADealRoom\Repositories\EventRepository;
use MADealRoom\Repositories\CustomUserRepository;
use MADealRoom\Services\RateLimiter;
use MADealRoom\Services\AuthService;
use MADealRoom\Core\UserRoles;

/**

 * Base controller with common functionality

 */

abstract class BaseController {

	/**

	 * API namespace

	 *

	 * @var string

	 */

	protected $namespace = 'ma-deal-room/v1';



	/**

	 * Resource route base

	 *

	 * @var string

	 */

	protected $rest_base;

	/**
	 * Event repository for audit logging
	 *
	 * @var EventRepository|null
	 */
	protected $event_repository;

	/**
	 * Rate limiter for request throttling
	 *
	 * @var RateLimiter
	 */
	protected $rate_limiter;

	/**
	 * Current custom user (if authenticated via JWT)
	 *
	 * @var object|null
	 */
	protected $current_custom_user = null;

	/**
	 * Current user type (wordpress|custom)
	 *
	 * @var string|null
	 */
	protected $current_user_type = null;

	/**
	 * Auth service
	 *
	 * @var AuthService
	 */
	protected $auth_service;

	/**
	 * Custom user repository
	 *
	 * @var CustomUserRepository
	 */
	protected $user_repo;

	/**
	 * Constructor
	 *
	 * @param EventRepository|null $event_repository Optional event repository for audit logging
	 */
	public function __construct(?EventRepository $event_repository = null) {
		$this->event_repository = $event_repository;
		$this->rate_limiter = new RateLimiter();
		$this->auth_service = new AuthService();
		$this->user_repo = new CustomUserRepository();
	}

	/**

	 * Register routes

	 *

	 * @return void

	 */

	abstract public function register_routes(): void;

	/**
	 * Verify nonce for state-changing operations (CSRF protection)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error
	 */
	public function verify_nonce(WP_REST_Request $request) {
		$nonce = $request->get_header('X-WP-Nonce');

		if (!$nonce) {
			$nonce = $request->get_param('_wpnonce');
		}

		if (!$nonce || !wp_verify_nonce($nonce, 'wp_rest')) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__('Cookie nonce is invalid', 'ma-deal-room'),
				['status' => 403]
			);
		}

		return true;
	}

	/**
	 * Extract JWT token from Authorization header
	 *
	 * @param WP_REST_Request $request Request object
	 * @return string|null JWT token or null if not found
	 */
	protected function extract_jwt_token(WP_REST_Request $request): ?string {
		$auth_header = $request->get_header('Authorization');

		if (!$auth_header) {
			return null;
		}

		// Check for "Bearer {token}" format
		if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Authenticate user via JWT token
	 *
	 * Sets $this->current_custom_user and $this->current_user_type if valid
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error True if authenticated, error otherwise
	 */
	protected function authenticate_jwt(WP_REST_Request $request) {
		$token = $this->extract_jwt_token($request);

		if (!$token) {
			return new WP_Error(
				'no_auth_token',
				__('No authentication token provided', 'ma-deal-room'),
				['status' => 401]
			);
		}

		// Verify token
		$payload = $this->auth_service->verify_access_token($token);

		if (is_wp_error($payload)) {
			return $payload;
		}

		// Get user
		$user = $this->user_repo->find($payload['sub']);

		if (!$user) {
			return new WP_Error(
				'user_not_found',
				__('User not found', 'ma-deal-room'),
				['status' => 404]
			);
		}

		// Set current user
		$this->current_custom_user = $user;
		$this->current_user_type = 'custom';

		return true;
	}

	/**
	 * Get current user (WordPress or custom)
	 *
	 * @return array|null Array with 'id', 'type', and 'user' keys
	 */
	protected function get_current_user(): ?array {
		// Check for WordPress user first
		if (is_user_logged_in()) {
			$wp_user = wp_get_current_user();
			return [
				'id' => $wp_user->ID,
				'type' => 'wordpress',
				'user' => $wp_user,
			];
		}

		// Check for custom user (JWT)
		if ($this->current_custom_user) {
			return [
				'id' => $this->current_custom_user->id,
				'type' => 'custom',
				'user' => $this->current_custom_user,
			];
		}

		return null;
	}

	/**
	 * Check if current user has capability
	 *
	 * Works for both WordPress and custom users
	 *
	 * @param string $capability Capability to check
	 * @return bool
	 */
	protected function user_can(string $capability): bool {
		$current_user = $this->get_current_user();

		if (!$current_user) {
			return false;
		}

		if ($current_user['type'] === 'wordpress') {
			return current_user_can($capability);
		} else {
			// Custom user - check via UserRoles
			// Convert CustomUser object to array format expected by UserRoles::user_can()
			$user_data = [
				'user_id' => $current_user['user']->id,
				'user_type' => 'custom'
			];
			return UserRoles::user_can($user_data, $capability);
		}
	}

	/**
	 * Check user permissions
	 *
	 * @param WP_REST_Request $request Request object
	 * @param string $required_capability The capability required for this action
	 * @return bool|WP_Error
	 */
	public function permission_callback(WP_REST_Request $request) {
		// SECURITY: Check rate limits first (before authentication)
		// This prevents brute force attacks and DoS attempts
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request);
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		// Try WordPress session authentication first
		$is_wordpress_user = is_user_logged_in();

		// If not WordPress user, try JWT authentication
		if (!$is_wordpress_user) {
			$jwt_auth = $this->authenticate_jwt($request);
			if (is_wp_error($jwt_auth)) {
				// Not authenticated via WordPress or JWT
				return new WP_Error(
					'rest_forbidden',
					__('You must be logged in to access this resource.', 'ma-deal-room'),
					['status' => 401]
				);
			}
		}

		// Verify nonce for state-changing requests (CSRF protection)
		// Only required for WordPress session auth (JWT uses stateless auth)
		if ($is_wordpress_user && in_array($request->get_method(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
			$nonce_check = $this->verify_nonce($request);
			if (is_wp_error($nonce_check)) {
				return $nonce_check;
			}
		}

		// Check if user has appropriate capabilities
		// Require editor level for modification operations
		$method = $request->get_method();
		if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
			if (!$this->user_can('edit_others_posts')) {
				return new WP_Error(
					'rest_forbidden',
					__('You do not have permission to modify resources.', 'ma-deal-room'),
					['status' => 403]
				);
			}
		} else {
			// Read operations require at least edit_posts
			if (!$this->user_can('edit_posts')) {
				return new WP_Error(
					'rest_forbidden',
					__('You do not have permission to access this resource.', 'ma-deal-room'),
					['status' => 403]
				);
			}
		}

		return true;
	}

	/**
	 * Permission callback for public endpoints (no authentication required)
	 *
	 * Still checks rate limits for security
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool|WP_Error
	 */
	public function public_permission_callback(WP_REST_Request $request) {
		// SECURITY: Check rate limits (even for public endpoints)
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request);
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		return true;
	}

	/**
	 * Permission callback with custom capability check
	 *
	 * @param WP_REST_Request $request Request object
	 * @param string $required_capability Required capability
	 * @return bool|WP_Error
	 */
	public function permission_callback_with_cap(WP_REST_Request $request, string $required_capability) {
		// First do standard auth check
		$auth_check = $this->permission_callback($request);
		if (is_wp_error($auth_check)) {
			return $auth_check;
		}

		// Then check specific capability
		if (!$this->user_can($required_capability)) {
			return new WP_Error(
				'rest_forbidden',
				sprintf(__('You need the %s capability to access this resource.', 'ma-deal-room'), $required_capability),
				['status' => 403]
			);
		}

		return true;
	}

	/**
	 * Verify user has access to a specific account
	 *
	 * @param int $account_id Account ID to check access for
	 * @return bool True if user has access, false otherwise
	 */
	protected function verify_account_access(int $account_id): bool {
		// Admins have access to all accounts
		if (current_user_can('manage_options')) {
			return true;
		}

		$current_user = $this->get_current_user();
		if (!$current_user) {
			return false;
		}

		// Get account repository if needed
		if (!isset($this->account_repository)) {
			$container = \MADealRoom\Core\Plugin::instance()->container();
			$account_repository = $container->get('account_repository');
		} else {
			$account_repository = $this->account_repository;
		}

		$account = $account_repository->find($account_id);

		if (!$account) {
			return false;
		}

		// For WordPress users: check owner_user_id
		if ($current_user['type'] === 'wordpress') {
			return $account->owner_user_id === $current_user['id'];
		}

		// For custom users: check via user_roles table
		global $wpdb;
		$user_roles_table = $wpdb->prefix . 'ma_deal_user_roles';

		$has_access = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$user_roles_table}
			 WHERE user_id = %d
			   AND user_type = 'custom'
			   AND account_id = %d
			   AND (revoked_at IS NULL OR revoked_at > NOW())
			   AND (expires_at IS NULL OR expires_at > NOW())",
			$current_user['id'],
			$account_id
		));

		return $has_access > 0;
	}

	/**
	 * Get current user's account ID(s)
	 *
	 * @return int|null Primary account ID for current user
	 */
	protected function get_user_account_id(): ?int {
		$current_user = $this->get_current_user();
		if (!$current_user) {
			return null;
		}

		// Get account repository
		if (!isset($this->account_repository)) {
			$container = \MADealRoom\Core\Plugin::instance()->container();
			$account_repository = $container->get('account_repository');
		} else {
			$account_repository = $this->account_repository;
		}

		// Find user's account using findByUserId which handles both user types
		$account = $account_repository->findByUserId(
			$current_user['id'],
			$current_user['type']
		);

		return $account ? $account->id : null;
	}

	/**
	 * Validate request data
	 *
	 * @param WP_REST_Request $request Request object
	 * @param array $rules Validation rules
	 * @return bool|WP_Error
	 */
	protected function validate_request(WP_REST_Request $request, array $rules) {
		$errors = [];

		foreach ($rules as $field => $rule) {
			$value = $request->get_param($field);

			if (isset($rule['required']) && $rule['required'] && (empty($value) && $value !== 0 && $value !== '0')) {
				$errors[$field] = sprintf(__('%s is required.', 'ma-deal-room'), $field);
				continue;
			}

			// Skip further validation if value is empty and not required
			if (empty($value) && !isset($rule['required'])) {
				continue;
			}

			if (isset($rule['type'])) {
				$valid = false;

				switch ($rule['type']) {
					case 'integer':
						$valid = is_numeric($value) && (int)$value == $value;
						break;
					case 'string':
						$valid = is_string($value);
						break;
					case 'email':
						$valid = is_email($value);
						break;
					case 'url':
						$valid = filter_var($value, FILTER_VALIDATE_URL) !== false;
						break;
					case 'boolean':
						$valid = is_bool($value) || in_array($value, ['true', 'false', '1', '0'], true);
						break;
					case 'array':
						$valid = is_array($value);
						break;
					case 'json':
						json_decode($value);
						$valid = (json_last_error() === JSON_ERROR_NONE);
						break;
				}

				if (!$valid) {
					$errors[$field] = sprintf(__('%s has invalid type or format.', 'ma-deal-room'), $field);
					continue;
				}
			}

			// Min/Max Length for strings
			if (is_string($value)) {
				if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
					$errors[$field] = sprintf(__('%s must be at least %d characters long.', 'ma-deal-room'), $field, $rule['min_length']);
					continue;
				}
				if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
					$errors[$field] = sprintf(__('%s cannot exceed %d characters.', 'ma-deal-room'), $field, $rule['max_length']);
					continue;
				}
			}

			// Min/Max Value for numbers
			if (is_numeric($value)) {
				$numeric_value = (float)$value;
				if (isset($rule['min_value']) && $numeric_value < $rule['min_value']) {
					$errors[$field] = sprintf(__('%s must be at least %s.', 'ma-deal-room'), $field, $rule['min_value']);
					continue;
				}
				if (isset($rule['max_value']) && $numeric_value > $rule['max_value']) {
					$errors[$field] = sprintf(__('%s cannot exceed %s.', 'ma-deal-room'), $field, $rule['max_length']);
					continue;
				}
			}

			// Enum validation
			if (isset($rule['enum']) && is_array($rule['enum'])) {
				if (!in_array($value, $rule['enum'], true)) {
					$errors[$field] = sprintf(__('%s must be one of: %s.', 'ma-deal-room'), $field, implode(', ', $rule['enum']));
					continue;
				}
			}
		}

		if (!empty($errors)) {
			return new WP_Error(
				'rest_invalid_param',
				__('Invalid parameters.', 'ma-deal-room'),
				['status' => 400, 'errors' => $errors]
			);
		}

		return true;
	}

	/**
	 * Get real client IP address (accounting for proxies)
	 *
	 * @return string Client IP address
	 */
	protected function get_client_ip(): string {
		// Check for CloudFlare
		if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$ip = filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP);
			if ($ip) {
				return $ip;
			}
		}

		// Check for proxy headers
		$proxy_headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];
		foreach ($proxy_headers as $header) {
			if (!empty($_SERVER[$header])) {
				// X-Forwarded-For can contain multiple IPs, take the first one
				$ip_list = explode(',', $_SERVER[$header]);
				$ip = trim($ip_list[0]);

				// Validate IP and ensure it's not a private/reserved IP
				if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
					return $ip;
				}
			}
		}

		// Fallback to REMOTE_ADDR
		return filter_var($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', FILTER_VALIDATE_IP) ?: 'UNKNOWN';
	}

	/**
	 * Log an event to the audit trail
	 *
	 * @param string $entity_type Type of entity (e.g., 'transaction', 'task')
	 * @param int $entity_id ID of the entity
	 * @param string $event_type Type of event (e.g., 'created', 'updated', 'deleted')
	 * @param array $old_data Optional: Old data before change
	 * @param array $new_data Optional: New data after change
	 * @param int|null $account_id Optional: Account ID associated with the event
	 * @param int|null $transaction_id Optional: Transaction ID associated with the event
	 * @return void
	 */
	protected function logEvent(
		string $entity_type,
		int $entity_id,
		string $event_type,
		array $old_data = [],
		array $new_data = [],
		int $account_id = null,
		int $transaction_id = null
	): void {
		if (!$this->event_repository) {
			return; // Event repository not available
		}

		$current_user = $this->get_current_user();
		$user_id = $current_user ? $current_user['id'] : 0;
		$user_type = $current_user ? $current_user['type'] : 'wordpress';

		$ip_address = $this->get_client_ip();
		$user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? 'CLI');

		$this->event_repository->create([
			'account_id' => $account_id,
			'transaction_id' => $transaction_id,
			'entity_type' => $entity_type,
			'entity_id' => $entity_id,
			'event_type' => $event_type,
			'user_id' => $user_id,
			'user_type' => $user_type,
			'old_data' => !empty($old_data) ? json_encode($old_data) : null,
			'new_data' => !empty($new_data) ? json_encode($new_data) : null,
			'ip_address' => $ip_address,
			'user_agent' => $user_agent,
		]);
	}

	/**
	 * Return success response
	 *
	 * @param mixed $data Response data
	 * @param string $message Success message
	 * @param int $status HTTP status code
	 * @return WP_REST_Response
	 */
	protected function success($data = null, string $message = '', int $status = 200): WP_REST_Response {
		$response = [
			'success' => true,
		];

		if ($message) {
			$response['message'] = $message;
		}

		if ($data !== null) {
			$response['data'] = $data;
		}

		return new WP_REST_Response($response, $status);
	}

	/**
	 * Return error response
	 *
	 * @param string $message Error message
	 * @param int $status HTTP status code
	 * @param string $code Error code
	 * @param mixed $data Additional error data
	 * @return WP_Error
	 */
	protected function error(string $message, int $status = 400, string $code = 'error', $data = null): WP_Error {
		$error_data = ['status' => $status];

		if ($data !== null) {
			$error_data['data'] = $data;
		}

		return new WP_Error($code, $message, $error_data);
	}

	/**
	 * SECURITY: Return secure error response that logs detailed error server-side
	 * but returns generic message to client
	 *
	 * This prevents information disclosure vulnerabilities where database errors,
	 * file paths, or other sensitive information is exposed to clients.
	 *
	 * @param string $generic_message Generic message safe for client
	 * @param string $detailed_error Detailed error for server logs
	 * @param int $status HTTP status code
	 * @param string $code Error code
	 * @param array $context Additional context for logging
	 * @return WP_Error
	 */
	protected function secure_error(
		string $generic_message,
		string $detailed_error,
		int $status = 500,
		string $code = 'internal_error',
		array $context = []
	): WP_Error {
		// SECURITY: Log detailed error server-side with context
		$log_message = sprintf(
			'[SECURE ERROR] %s | Details: %s | Context: %s',
			$generic_message,
			$detailed_error,
			json_encode($context)
		);

		// Log to WordPress debug log
		if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log($log_message);
		}

		// SECURITY: Return ONLY generic message to client
		// Never expose: database errors, file paths, internal state, etc.
		return $this->error($generic_message, $status, $code);
	}

	/**
	 * SECURITY: Handle database operation failures securely
	 *
	 * Logs database error details server-side but returns generic message to client.
	 * This prevents database schema disclosure and information leakage.
	 *
	 * @param string $operation Operation that failed (e.g., "create transaction")
	 * @param string|null $db_error Database error message (from $wpdb->last_error)
	 * @param array $context Additional context
	 * @return WP_Error
	 */
	protected function database_error(string $operation, ?string $db_error = null, array $context = []): WP_Error {
		// Generic message for client (no database details)
		$generic_message = sprintf(
			__('Unable to %s. Please try again or contact support if the problem persists.', 'ma-deal-room'),
			$operation
		);

		// Detailed error for server logs
		$detailed_error = $db_error ? "Database error: $db_error" : "Database operation failed";

		// Add operation context
		$context['operation'] = $operation;
		$context['user_id'] = get_current_user_id();
		$context['timestamp'] = current_time('mysql');

		return $this->secure_error(
			$generic_message,
			$detailed_error,
			500,
			'database_error',
			$context
		);
	}

	/**
	 * SECURITY: Handle file system operation failures securely
	 *
	 * Logs file path and error details server-side but returns generic message to client.
	 * This prevents file path disclosure and directory structure leakage.
	 *
	 * @param string $operation Operation that failed
	 * @param string $file_path File path (will be logged, not exposed)
	 * @param string $error_details Error details
	 * @return WP_Error
	 */
	protected function filesystem_error(string $operation, string $file_path, string $error_details): WP_Error {
		$generic_message = sprintf(
			__('File operation failed: %s. Please try again or contact support.', 'ma-deal-room'),
			$operation
		);

		$detailed_error = "Filesystem error: $error_details | Path: $file_path";

		return $this->secure_error(
			$generic_message,
			$detailed_error,
			500,
			'filesystem_error',
			['operation' => $operation]
		);
	}

	/**
	 * Sanitize request data
	 *
	 * @param array $data Data to sanitize
	 * @param array $fields Field definitions with sanitization rules
	 * @return array Sanitized data
	 */
	protected function sanitize_data(array $data, array $fields = []): array {
		$sanitized = [];

		foreach ($data as $key => $value) {
			// If specific field rules are provided, use them
			if (!empty($fields) && isset($fields[$key])) {
				$field_type = $fields[$key]['type'] ?? 'text';

				switch ($field_type) {
					case 'integer':
					case 'int':
						$sanitized[$key] = intval($value);
						break;
					case 'float':
					case 'decimal':
						$sanitized[$key] = floatval($value);
						break;
					case 'boolean':
					case 'bool':
						$sanitized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
						break;
					case 'email':
						$sanitized[$key] = sanitize_email($value);
						break;
					case 'url':
						$sanitized[$key] = esc_url_raw($value);
						break;
					case 'textarea':
						$sanitized[$key] = sanitize_textarea_field($value);
						break;
					case 'array':
						$sanitized[$key] = is_array($value) ? array_map('sanitize_text_field', $value) : [];
						break;
					case 'json':
						$sanitized[$key] = is_string($value) ? $value : json_encode($value);
						break;
					default:
						$sanitized[$key] = sanitize_text_field($value);
				}
			} else {
				// Default sanitization based on value type
				if (is_array($value)) {
					$sanitized[$key] = $this->sanitize_data($value);
				} elseif (is_string($value)) {
					$sanitized[$key] = sanitize_text_field($value);
				} elseif (is_numeric($value)) {
					$sanitized[$key] = $value;
				} elseif (is_bool($value)) {
					$sanitized[$key] = $value;
				} else {
					$sanitized[$key] = sanitize_text_field(strval($value));
				}
			}
		}

		return $sanitized;
	}

}
