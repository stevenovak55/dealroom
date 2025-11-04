<?php
/**
 * Rate Limiter Service
 *
 * Implements rate limiting for REST API endpoints to prevent:
 * - Brute force attacks
 * - Denial of service
 * - Resource enumeration
 * - API abuse
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use WP_REST_Request;
use WP_Error;

class RateLimiter {
	/**
	 * Default rate limits
	 */
	private const LIMITS = [
		// Per-second (burst protection)
		'second' => [
			'requests' => 20,
			'window' => 1,
		],
		// Per-minute (sustained rate)
		'minute' => [
			'requests' => 100,
			'window' => 60,
		],
		// Per-hour (long-term abuse prevention)
		'hour' => [
			'requests' => 1000,
			'window' => 3600,
		],
	];

	/**
	 * Strict limits for sensitive endpoints
	 */
	private const STRICT_LIMITS = [
		'second' => [
			'requests' => 5,
			'window' => 1,
		],
		'minute' => [
			'requests' => 20,
			'window' => 60,
		],
		'hour' => [
			'requests' => 100,
			'window' => 3600,
		],
	];

	/**
	 * Endpoints that require strict rate limiting
	 */
	private const STRICT_ENDPOINTS = [
		'vendor-portal',    // Prevent token brute force
		'login',           // Prevent credential stuffing
		'reset-password',  // Prevent enumeration
	];

	/**
	 * Check if request exceeds rate limits
	 *
	 * @param WP_REST_Request $request The REST request
	 * @param string $endpoint_type Optional endpoint type for custom limits
	 * @return bool|WP_Error True if allowed, WP_Error if rate limited
	 */
	public function check_rate_limit(WP_REST_Request $request, string $endpoint_type = 'default'): bool|WP_Error {
		// Determine if strict limits should be applied
		$is_strict = $this->is_strict_endpoint($request, $endpoint_type);
		$limits = $is_strict ? self::STRICT_LIMITS : self::LIMITS;

		// Allow filtering of rate limits
		$limits = apply_filters('ma_deal_room_rate_limits', $limits, $endpoint_type, $request);

		// Get identifier for tracking (IP + User ID)
		$identifier = $this->get_rate_limit_identifier($request);

		// Check each time window
		foreach ($limits as $window_name => $config) {
			$result = $this->check_window($identifier, $window_name, $config['requests'], $config['window']);

			if (is_wp_error($result)) {
				// Add rate limit headers to error
				$this->add_rate_limit_headers($identifier, $window_name, $config);
				return $result;
			}
		}

		// All checks passed, add headers showing remaining quota
		$this->add_rate_limit_headers($identifier, 'minute', $limits['minute']);

		return true;
	}

	/**
	 * Check if endpoint requires strict rate limiting
	 *
	 * @param WP_REST_Request $request The request
	 * @param string $endpoint_type Endpoint type
	 * @return bool True if strict limits should be applied
	 */
	private function is_strict_endpoint(WP_REST_Request $request, string $endpoint_type): bool {
		// Check explicit endpoint type
		if (in_array($endpoint_type, self::STRICT_ENDPOINTS, true)) {
			return true;
		}

		// Check request route
		$route = $request->get_route();
		foreach (self::STRICT_ENDPOINTS as $pattern) {
			if (strpos($route, $pattern) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get unique identifier for rate limiting
	 *
	 * Combines IP address and user ID for accurate tracking
	 *
	 * @param WP_REST_Request $request The request
	 * @return string Unique identifier
	 */
	private function get_rate_limit_identifier(WP_REST_Request $request): string {
		$parts = [];

		// Get IP address (use the same method as BaseController)
		$ip = $this->get_client_ip($request);
		$parts[] = 'ip:' . $ip;

		// Add user ID if authenticated
		$user_id = get_current_user_id();
		if ($user_id > 0) {
			$parts[] = 'user:' . $user_id;
		}

		// Add route for per-endpoint tracking
		$route = $request->get_route();
		$parts[] = 'route:' . md5($route);

		return implode('|', $parts);
	}

	/**
	 * Get client IP address (matches BaseController logic)
	 *
	 * @param WP_REST_Request $request The request
	 * @return string IP address
	 */
	private function get_client_ip(WP_REST_Request $request): string {
		// Check CloudFlare header first
		$ip = $request->get_header('CF-Connecting-IP');
		if ($ip) {
			return $ip;
		}

		// Check standard proxy headers
		$headers = ['X-Forwarded-For', 'X-Real-IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];
		foreach ($headers as $header) {
			$ip = $request->get_header($header);
			if ($ip) {
				// X-Forwarded-For can contain multiple IPs, take the first
				if (strpos($ip, ',') !== false) {
					$ip = trim(explode(',', $ip)[0]);
				}
				return $ip;
			}
		}

		// Fallback to REMOTE_ADDR
		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}

	/**
	 * Check rate limit for specific time window
	 *
	 * @param string $identifier Unique identifier
	 * @param string $window_name Window name (second, minute, hour)
	 * @param int $max_requests Maximum allowed requests
	 * @param int $window_seconds Window size in seconds
	 * @return bool|WP_Error True if allowed, WP_Error if exceeded
	 */
	private function check_window(string $identifier, string $window_name, int $max_requests, int $window_seconds): bool|WP_Error {
		$key = $this->get_transient_key($identifier, $window_name);
		$current = (int) get_transient($key);

		// If limit exceeded, return error
		if ($current >= $max_requests) {
			$reset_time = $this->get_reset_time($key, $window_seconds);
			$retry_after = max(1, $reset_time - time());

			return new WP_Error(
				'rest_rate_limit_exceeded',
				sprintf(
					__('Rate limit exceeded. Try again in %d seconds.', 'ma-deal-room'),
					$retry_after
				),
				[
					'status' => 429,
					'retry_after' => $retry_after,
					'limit' => $max_requests,
					'window' => $window_name,
				]
			);
		}

		// Increment counter
		$new_count = $current + 1;

		// Set transient with expiration equal to window size
		// If this is the first request in the window, set TTL to window size
		if ($current === 0) {
			set_transient($key, $new_count, $window_seconds);
		} else {
			// Update existing transient without changing TTL
			set_transient($key, $new_count, $window_seconds);
		}

		return true;
	}

	/**
	 * Get transient key for rate limiting
	 *
	 * @param string $identifier Unique identifier
	 * @param string $window Window name
	 * @return string Transient key
	 */
	private function get_transient_key(string $identifier, string $window): string {
		return 'ma_ratelimit_' . md5($identifier . '_' . $window);
	}

	/**
	 * Get reset timestamp for rate limit window
	 *
	 * @param string $transient_key Transient key
	 * @param int $window_seconds Window size
	 * @return int Unix timestamp when limit resets
	 */
	private function get_reset_time(string $transient_key, int $window_seconds): int {
		// Get transient timeout
		$timeout = get_option('_transient_timeout_' . $transient_key);

		if ($timeout) {
			return (int) $timeout;
		}

		// Fallback: estimate based on window size
		return time() + $window_seconds;
	}

	/**
	 * Add rate limit headers to response
	 *
	 * @param string $identifier Rate limit identifier
	 * @param string $window_name Window to report
	 * @param array $config Window configuration
	 */
	private function add_rate_limit_headers(string $identifier, string $window_name, array $config): void {
		$key = $this->get_transient_key($identifier, $window_name);
		$current = (int) get_transient($key);
		$limit = $config['requests'];
		$remaining = max(0, $limit - $current);
		$reset_time = $this->get_reset_time($key, $config['window']);

		// Add headers to next response
		add_filter('rest_post_dispatch', function($response) use ($limit, $remaining, $reset_time) {
			if (is_wp_error($response)) {
				// If this is a rate limit error, add Retry-After header
				if ($response->get_error_code() === 'rest_rate_limit_exceeded') {
					$retry_after = $response->get_error_data()['retry_after'] ?? 60;
					header('Retry-After: ' . $retry_after);
				}
			}

			// Add standard rate limit headers
			header('X-RateLimit-Limit: ' . $limit);
			header('X-RateLimit-Remaining: ' . $remaining);
			header('X-RateLimit-Reset: ' . $reset_time);

			return $response;
		}, 10, 1);
	}

	/**
	 * Reset rate limit for an identifier
	 *
	 * Useful for testing or administrative overrides
	 *
	 * @param string $identifier Rate limit identifier
	 */
	public function reset_rate_limit(string $identifier): void {
		foreach (array_keys(self::LIMITS) as $window) {
			$key = $this->get_transient_key($identifier, $window);
			delete_transient($key);
		}
	}

	/**
	 * Get current rate limit status
	 *
	 * @param string $identifier Rate limit identifier
	 * @return array Status information
	 */
	public function get_rate_limit_status(string $identifier): array {
		$status = [];

		foreach (self::LIMITS as $window_name => $config) {
			$key = $this->get_transient_key($identifier, $window_name);
			$current = (int) get_transient($key);
			$limit = $config['requests'];
			$reset_time = $this->get_reset_time($key, $config['window']);

			$status[$window_name] = [
				'limit' => $limit,
				'remaining' => max(0, $limit - $current),
				'current' => $current,
				'reset' => $reset_time,
				'reset_in' => max(0, $reset_time - time()),
			];
		}

		return $status;
	}

	/**
	 * Cleanup old rate limit transients
	 *
	 * WordPress automatically cleans up expired transients, but this
	 * can be called manually if needed
	 */
	public function cleanup_expired_limits(): void {
		global $wpdb;

		// Delete expired transients related to rate limiting
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				WHERE option_name LIKE %s
				AND option_name IN (
					SELECT option_name
					FROM {$wpdb->options}
					WHERE option_name LIKE %s
					AND option_value < %d
				)",
				'_transient_ma_ratelimit_%',
				'_transient_timeout_ma_ratelimit_%',
				time()
			)
		);
	}
}
