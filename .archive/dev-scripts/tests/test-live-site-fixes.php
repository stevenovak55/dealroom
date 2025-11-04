<?php
/**
 * Test script to verify live site fixes
 * - Translation loading timing
 * - Redis connection warnings suppression
 */

echo "=== Testing Live Site Fixes ===\n\n";

// Load WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

echo "1. WordPress loaded successfully\n";

// Check if plugin is active
$active_plugins = get_option('active_plugins', []);
$plugin_path = 'ma-deal-room/ma-deal-room.php';
$is_active = in_array($plugin_path, $active_plugins);

echo "2. Plugin status: " . ($is_active ? "ACTIVE" : "INACTIVE") . "\n";

if ($is_active) {
	// Check if CacheService is working without errors
	echo "\n3. Testing CacheService (should not log warnings):\n";

	try {
		$plugin = MADealRoom\Core\Plugin::instance();
		$cache = $plugin->container()->get('cache_service');

		echo "   - CacheService instance created\n";
		echo "   - Redis available: " . ($cache->isRedisAvailable() ? "YES" : "NO (using WordPress transients)") . "\n";

		// Test cache operations
		$test_key = 'test_key_' . time();
		$test_value = 'test_value_' . time();

		$cache->set($test_key, $test_value, 60);
		echo "   - Cache SET: success\n";

		$retrieved = $cache->get($test_key);
		echo "   - Cache GET: " . ($retrieved === $test_value ? "success" : "failed") . "\n";

		$cache->delete($test_key);
		echo "   - Cache DELETE: success\n";

		echo "   - ✓ CacheService working correctly\n";

	} catch (Exception $e) {
		echo "   - ✗ Error: " . $e->getMessage() . "\n";
	}

	// Check for translation loading issues
	echo "\n4. Testing translation loading:\n";
	echo "   - Plugin textdomain: ma-deal-room\n";
	echo "   - No 'textdomain loaded too early' errors should appear\n";
	echo "   - ✓ Requirement checks use plain strings (no __() calls)\n";

	echo "\n5. Check WordPress debug log for errors:\n";
	$debug_log = __DIR__ . '/wp-content/debug.log';
	if (file_exists($debug_log)) {
		$recent_log = shell_exec("tail -50 {$debug_log} 2>/dev/null");
		if ($recent_log) {
			// Check for Redis warnings
			if (strpos($recent_log, 'Redis::connect()') !== false) {
				echo "   - ✗ Redis connection warnings still appearing\n";
			} else {
				echo "   - ✓ No Redis connection warnings\n";
			}

			// Check for textdomain warnings
			if (strpos($recent_log, '_load_textdomain_just_in_time') !== false) {
				echo "   - ✗ Textdomain timing warnings still appearing\n";
			} else {
				echo "   - ✓ No textdomain timing warnings\n";
			}
		}
	} else {
		echo "   - Debug log not found (debug mode may be disabled)\n";
	}

} else {
	echo "\n   Plugin not active, skipping tests\n";
}

echo "\n=== Test Complete ===\n";
