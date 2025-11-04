<?php
/**
 * Test the reset functionality
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

header('Content-Type: text/plain; charset=utf-8');

echo "=== Testing Plugin Reset Functionality ===\n\n";

// Test 1: Dry Run
echo "TEST 1: Dry Run (no actual changes)\n";
echo str_repeat('-', 50) . "\n";

$reset_service = new \MADealRoom\Services\PluginResetService(true);
$dry_run_result = $reset_service->reset();

echo "Success: " . ($dry_run_result['success'] ? 'YES' : 'NO') . "\n";
echo "Dry run: " . ($dry_run_result['dry_run'] ? 'YES' : 'NO') . "\n";
echo "\nLog entries: " . count($dry_run_result['log']) . "\n";

foreach ($dry_run_result['log'] as $entry) {
	echo "[{$entry['level']}] {$entry['message']}\n";
}

if (isset($dry_run_result['verification'])) {
	echo "\nVerification: {$dry_run_result['verification']['passed_checks']}/{$dry_run_result['verification']['total_checks']} checks passed\n";
}

echo "\n" . str_repeat('=', 50) . "\n\n";

// Test 2: Check if admin page is registered
echo "TEST 2: Check Admin Page Registration\n";
echo str_repeat('-', 50) . "\n";

global $menu, $submenu;

$found = false;
if (isset($submenu['ma-deal-room'])) {
	foreach ($submenu['ma-deal-room'] as $item) {
		if (isset($item[2]) && $item[2] === 'ma-deal-room-reset') {
			$found = true;
			echo "✓ Reset page found in submenu\n";
			echo "  Title: {$item[0]}\n";
			echo "  Slug: {$item[2]}\n";
			break;
		}
	}
}

if (!$found) {
	echo "✗ Reset page NOT found in submenu\n";
	echo "  (This is normal - admin_menu hook may not have run yet)\n";
}

echo "\n" . str_repeat('=', 50) . "\n\n";

echo "Tests complete!\n";
echo "\nNext steps:\n";
echo "1. Log into WordPress admin\n";
echo "2. Go to MA Deal Room > Reset Plugin\n";
echo "3. Run the dry-run first\n";
echo "4. Check the output\n";
