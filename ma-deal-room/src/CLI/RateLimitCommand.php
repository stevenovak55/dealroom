<?php
/**
 * WP-CLI Rate Limit Management Command
 *
 * @package MADealRoom\CLI
 * @since 1.0.0
 */

namespace MADealRoom\CLI;

use MADealRoom\Services\RateLimiter;
use WP_CLI;
use WP_CLI_Command;

class RateLimitCommand extends WP_CLI_Command {

	/**
	 * Rate limiter instance
	 *
	 * @var RateLimiter
	 */
	private $rate_limiter;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->rate_limiter = new RateLimiter();
	}

	/**
	 * Check rate limit status for an identifier
	 *
	 * ## OPTIONS
	 *
	 * <identifier>
	 * : The rate limit identifier (e.g., "ip:127.0.0.1|user:1|route:hash")
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal rate-limit status "ip:127.0.0.1|user:1|route:abc123"
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Named arguments
	 */
	public function status($args, $assoc_args) {
		list($identifier) = $args;

		$status = $this->rate_limiter->get_rate_limit_status($identifier);

		WP_CLI::log("Rate Limit Status for: $identifier\n");

		foreach ($status as $window => $data) {
			WP_CLI::log("$window:");
			WP_CLI::log("  Limit: {$data['limit']} requests");
			WP_CLI::log("  Current: {$data['current']} requests");
			WP_CLI::log("  Remaining: {$data['remaining']} requests");
			WP_CLI::log("  Resets in: {$data['reset_in']} seconds");
			WP_CLI::log("");
		}

		WP_CLI::success('Status retrieved successfully');
	}

	/**
	 * Reset rate limit for an identifier
	 *
	 * ## OPTIONS
	 *
	 * <identifier>
	 * : The rate limit identifier to reset
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal rate-limit reset "ip:127.0.0.1|user:1|route:abc123"
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Named arguments
	 */
	public function reset($args, $assoc_args) {
		list($identifier) = $args;

		$this->rate_limiter->reset_rate_limit($identifier);

		WP_CLI::success("Rate limit reset for: $identifier");
	}

	/**
	 * Reset all rate limits
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal rate-limit reset-all
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Named arguments
	 */
	public function reset_all($args, $assoc_args) {
		global $wpdb;

		// Delete all rate limit transients
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_ma_ratelimit_%'
			OR option_name LIKE '_transient_timeout_ma_ratelimit_%'"
		);

		WP_CLI::success("All rate limits reset. Deleted $deleted transient entries.");
	}

	/**
	 * Clean up expired rate limit transients
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal rate-limit cleanup
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Named arguments
	 */
	public function cleanup($args, $assoc_args) {
		$this->rate_limiter->cleanup_expired_limits();

		WP_CLI::success('Expired rate limits cleaned up');
	}

	/**
	 * Test rate limiting by simulating requests
	 *
	 * ## OPTIONS
	 *
	 * [--requests=<number>]
	 * : Number of requests to simulate (default: 25)
	 *
	 * [--identifier=<identifier>]
	 * : Custom identifier to test (default: auto-generated)
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal rate-limit test --requests=25
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Named arguments
	 */
	public function test($args, $assoc_args) {
		$requests = isset($assoc_args['requests']) ? (int)$assoc_args['requests'] : 25;
		$identifier = isset($assoc_args['identifier']) ? $assoc_args['identifier'] : 'ip:127.0.0.1|user:test|route:test';

		WP_CLI::log("Simulating $requests requests for: $identifier\n");

		// Reset first to start fresh
		$this->rate_limiter->reset_rate_limit($identifier);

		$allowed = 0;
		$blocked = 0;

		for ($i = 1; $i <= $requests; $i++) {
			// Create a mock request object
			$request = new \WP_REST_Request('GET', '/ma-deal/v1/test');
			$request->set_header('X-Forwarded-For', '127.0.0.1');

			// Manually check against identifier
			$key_second = 'ma_ratelimit_' . md5($identifier . '_second');
			$current_second = (int) get_transient($key_second);

			if ($current_second >= 20) {
				$blocked++;
				WP_CLI::log("Request #$i: ❌ BLOCKED (exceeded second limit)");
			} else {
				$allowed++;
				// Increment counter
				set_transient($key_second, $current_second + 1, 1);
				WP_CLI::log("Request #$i: ✅ ALLOWED");
			}

			// Small delay to avoid hitting per-second limit immediately in all iterations
			if ($i % 20 === 0) {
				WP_CLI::log("  [Pausing 1 second to simulate real traffic...]");
				sleep(1);
			}
		}

		WP_CLI::log("\n--- Test Results ---");
		WP_CLI::log("Total Requests: $requests");
		WP_CLI::log("Allowed: $allowed");
		WP_CLI::log("Blocked: $blocked");

		// Show final status
		WP_CLI::log("\n--- Final Rate Limit Status ---");
		$status = $this->rate_limiter->get_rate_limit_status($identifier);
		foreach ($status as $window => $data) {
			WP_CLI::log("$window: {$data['current']}/{$data['limit']} ({$data['remaining']} remaining)");
		}

		WP_CLI::success('Test completed');
	}

	/**
	 * Show rate limit configuration
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal rate-limit config
	 *
	 * @param array $args Positional arguments
	 * @param array $assoc_args Named arguments
	 */
	public function config($args, $assoc_args) {
		WP_CLI::log("Rate Limit Configuration\n");

		WP_CLI::log("Default Limits:");
		WP_CLI::log("  Per Second: 20 requests");
		WP_CLI::log("  Per Minute: 100 requests");
		WP_CLI::log("  Per Hour: 1000 requests\n");

		WP_CLI::log("Strict Limits (vendor portal, login, etc.):");
		WP_CLI::log("  Per Second: 5 requests");
		WP_CLI::log("  Per Minute: 20 requests");
		WP_CLI::log("  Per Hour: 100 requests\n");

		WP_CLI::log("Strict endpoints:");
		WP_CLI::log("  - vendor-portal");
		WP_CLI::log("  - login");
		WP_CLI::log("  - reset-password\n");

		WP_CLI::log("To customize limits, use the 'ma_deal_room_rate_limits' filter:");
		WP_CLI::log("add_filter('ma_deal_room_rate_limits', function(\$limits, \$endpoint_type, \$request) {");
		WP_CLI::log("    // Modify \$limits array");
		WP_CLI::log("    return \$limits;");
		WP_CLI::log("}, 10, 3);");

		WP_CLI::success('Configuration displayed');
	}
}
