<?php
/**
 * Rate Limit Middleware
 *
 * Global API rate limiting for all REST endpoints
 *
 * @package MADealRoom\Middleware
 * @since 1.0.0
 */

namespace MADealRoom\Middleware;

use MADealRoom\Services\RateLimiter;

class RateLimitMiddleware {
	/**
	 * Rate limiter service
	 *
	 * @var RateLimiter
	 */
	private $rate_limiter;

	/**
	 * Rate limit configuration
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Constructor
	 *
	 * @param RateLimiter $rate_limiter Rate limiter service
	 */
	public function __construct(RateLimiter $rate_limiter) {
		$this->rate_limiter = $rate_limiter;

		// Configure rate limits per endpoint type
		$this->config = [
			// Authentication endpoints (stricter limits)
			'auth' => [
				'limit' => 5,
				'window' => 60, // 1 minute
				'endpoints' => ['/login', '/register', '/reset-password', '/verify-email', '/refresh-token']
			],
			// Write endpoints (moderate limits)
			'write' => [
				'limit' => 30,
				'window' => 60, // 1 minute
				'methods' => ['POST', 'PUT', 'PATCH', 'DELETE']
			],
			// Read endpoints (generous limits)
			'read' => [
				'limit' => 60,
				'window' => 60, // 1 minute
				'methods' => ['GET', 'HEAD', 'OPTIONS']
			],
		];

		// Allow configuration via environment variables
		if (defined('API_RATE_LIMIT_AUTH') && is_numeric(API_RATE_LIMIT_AUTH)) {
			$this->config['auth']['limit'] = (int) API_RATE_LIMIT_AUTH;
		}
		if (defined('API_RATE_LIMIT_WRITE') && is_numeric(API_RATE_LIMIT_WRITE)) {
			$this->config['write']['limit'] = (int) API_RATE_LIMIT_WRITE;
		}
		if (defined('API_RATE_LIMIT_READ') && is_numeric(API_RATE_LIMIT_READ)) {
			$this->config['read']['limit'] = (int) API_RATE_LIMIT_READ;
		}
	}

	/**
	 * Initialize rate limiting
	 */
	public function init() {
		// Hook into REST API request processing
		add_filter('rest_pre_dispatch', [$this, 'check_rate_limit'], 10, 3);

		// Add rate limit headers to response
		add_filter('rest_post_dispatch', [$this, 'add_rate_limit_headers'], 10, 3);
	}

	/**
	 * Check rate limit before processing request
	 *
	 * @param mixed            $result  Response to replace the requested version with
	 * @param \WP_REST_Server  $server  Server instance
	 * @param \WP_REST_Request $request Request object
	 * @return mixed|\WP_Error
	 */
	public function check_rate_limit($result, $server, $request) {
		// Skip rate limiting for local development
		if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local') {
			return $result;
		}

		// Skip rate limiting if explicitly disabled
		if (defined('DISABLE_API_RATE_LIMITING') && DISABLE_API_RATE_LIMITING === true) {
			return $result;
		}

		// Get request details
		$route = $request->get_route();
		$method = $request->get_method();

		// Determine rate limit config for this request
		$limit_config = $this->get_limit_config($route, $method);

		if (!$limit_config) {
			// No rate limiting for this endpoint
			return $result;
		}

		// Generate rate limit key
		$ip_address = $this->get_client_ip();
		$user_id = get_current_user_id();

		// Use user ID if authenticated, otherwise IP address
		$identifier = $user_id ? "user_{$user_id}" : "ip_{$ip_address}";
		$key = "api_rate_limit_{$identifier}_{$method}_{$route}";

		// Normalize key (remove special characters)
		$key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);

		// Check rate limit
		global $wpdb;
		$table = $wpdb->prefix . 'ma_rate_limits';

		// Get recent requests
		$cutoff_time = date('Y-m-d H:i:s', time() - $limit_config['window']);
		$count = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$table}
			WHERE rate_key = %s
			AND created_at > %s",
			$key,
			$cutoff_time
		));

		// Check if limit exceeded
		if ($count >= $limit_config['limit']) {
			// Calculate retry-after time
			$oldest_request = $wpdb->get_var($wpdb->prepare(
				"SELECT created_at FROM {$table}
				WHERE rate_key = %s
				AND created_at > %s
				ORDER BY created_at ASC
				LIMIT 1",
				$key,
				$cutoff_time
			));

			$retry_after = $limit_config['window'];
			if ($oldest_request) {
				$retry_after = max(1, $limit_config['window'] - (time() - strtotime($oldest_request)));
			}

			// Return 429 Too Many Requests
			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					'Rate limit exceeded. Please try again in %d seconds.',
					$retry_after
				),
				[
					'status' => 429,
					'retry_after' => $retry_after,
					'limit' => $limit_config['limit'],
					'window' => $limit_config['window']
				]
			);
		}

		// Record this request
		$wpdb->insert(
			$table,
			[
				'rate_key' => $key,
				'ip_address' => $ip_address,
				'user_id' => $user_id ?: null,
				'endpoint' => $route,
				'method' => $method,
				'created_at' => current_time('mysql')
			],
			['%s', '%s', '%d', '%s', '%s', '%s']
		);

		// Cleanup old records (probabilistic - 1% chance)
		if (rand(1, 100) === 1) {
			$wpdb->query($wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				date('Y-m-d H:i:s', time() - 3600) // Delete records older than 1 hour
			));
		}

		return $result;
	}

	/**
	 * Add rate limit headers to response
	 *
	 * @param \WP_REST_Response $response Response object
	 * @param \WP_REST_Server   $server   Server instance
	 * @param \WP_REST_Request  $request  Request object
	 * @return \WP_REST_Response
	 */
	public function add_rate_limit_headers($response, $server, $request) {
		// Skip if rate limiting is disabled
		if (defined('DISABLE_API_RATE_LIMITING') && DISABLE_API_RATE_LIMITING === true) {
			return $response;
		}

		$route = $request->get_route();
		$method = $request->get_method();
		$limit_config = $this->get_limit_config($route, $method);

		if (!$limit_config) {
			return $response;
		}

		// Get current usage
		$ip_address = $this->get_client_ip();
		$user_id = get_current_user_id();
		$identifier = $user_id ? "user_{$user_id}" : "ip_{$ip_address}";
		$key = "api_rate_limit_{$identifier}_{$method}_{$route}";
		$key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);

		global $wpdb;
		$table = $wpdb->prefix . 'ma_rate_limits';
		$cutoff_time = date('Y-m-d H:i:s', time() - $limit_config['window']);
		$count = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$table}
			WHERE rate_key = %s
			AND created_at > %s",
			$key,
			$cutoff_time
		));

		// Add rate limit headers
		$response->header('X-RateLimit-Limit', $limit_config['limit']);
		$response->header('X-RateLimit-Remaining', max(0, $limit_config['limit'] - $count));
		$response->header('X-RateLimit-Reset', time() + $limit_config['window']);

		return $response;
	}

	/**
	 * Get rate limit configuration for a route and method
	 *
	 * @param string $route  API route
	 * @param string $method HTTP method
	 * @return array|null Rate limit config or null if no limit applies
	 */
	private function get_limit_config($route, $method) {
		// Check auth endpoints first (highest priority)
		foreach ($this->config['auth']['endpoints'] as $auth_endpoint) {
			if (strpos($route, $auth_endpoint) !== false) {
				return $this->config['auth'];
			}
		}

		// Check method-based limits
		if (in_array($method, $this->config['write']['methods'])) {
			return $this->config['write'];
		}

		if (in_array($method, $this->config['read']['methods'])) {
			return $this->config['read'];
		}

		return null;
	}

	/**
	 * Get client IP address (supports proxies and CloudFlare)
	 *
	 * @return string IP address
	 */
	private function get_client_ip() {
		$ip_keys = [
			'HTTP_CF_CONNECTING_IP', // CloudFlare
			'HTTP_X_FORWARDED_FOR',  // Proxy
			'HTTP_X_REAL_IP',        // Nginx proxy
			'REMOTE_ADDR'            // Direct connection
		];

		foreach ($ip_keys as $key) {
			if (!empty($_SERVER[$key])) {
				$ip = $_SERVER[$key];

				// Handle comma-separated IPs (X-Forwarded-For can contain multiple IPs)
				if (strpos($ip, ',') !== false) {
					$ip = trim(explode(',', $ip)[0]);
				}

				if (filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}
