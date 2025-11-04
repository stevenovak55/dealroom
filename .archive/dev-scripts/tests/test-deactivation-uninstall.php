<?php
/**
 * Test plugin deactivation and uninstallation
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=================================================================\n";
echo "TESTING PLUGIN DEACTIVATION & UNINSTALLATION\n";
echo "=================================================================\n\n";

global $wpdb;

// TEST 1: Verify plugin is currently active
echo "[TEST 1] Plugin Status\n";
echo "-----------------------------------------------------------------\n";

$plugins = get_option('active_plugins', []);
$plugin_active = in_array('ma-deal-room/ma-deal-room.php', $plugins);

if ($plugin_active) {
    echo "✓ Plugin is currently active\n";
} else {
    echo "✗ Plugin is NOT active\n";
    exit(1);
}

// Count current tables and data
$tables_before = $wpdb->get_col(
    "SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_%'"
);
$table_count_before = count($tables_before);

echo "  - Tables found: {$table_count_before}\n";

// Count options
$options_before = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
        $wpdb->esc_like('ma_deal') . '%'
    )
);
echo "  - Plugin options: {$options_before}\n";

// TEST 2: Test deactivation (this won't actually deactivate in this context)
echo "\n[TEST 2] Deactivation Behavior\n";
echo "-----------------------------------------------------------------\n";
echo "Note: Actual deactivation must be done via wp-cli\n";
echo "  - Plugin deactivation hook: ma_deal_room_deactivate()\n";
echo "  - Expected actions:\n";
echo "    1. Clear scheduled cron jobs\n";
echo "    2. Flush rewrite rules\n";
echo "    3. Database tables remain intact\n";
echo "    4. Data is NOT deleted\n";

// Check if cron jobs exist
$next_queue_run = wp_next_scheduled('ma_deal_room_process_queue');
if ($next_queue_run) {
    echo "  ✓ Cron job 'ma_deal_room_process_queue' is scheduled\n";
} else {
    echo "  ⚠ Cron job 'ma_deal_room_process_queue' is not scheduled\n";
}

// TEST 3: Simulate uninstall check
echo "\n[TEST 3] Uninstall Readiness\n";
echo "-----------------------------------------------------------------\n";

$uninstall_file = MA_DEAL_PATH . 'uninstall.php';
if (file_exists($uninstall_file)) {
    echo "✓ uninstall.php exists\n";

    // Read uninstall.php to verify it has proper cleanup
    $uninstall_content = file_get_contents($uninstall_file);

    $checks = [
        'SET FOREIGN_KEY_CHECKS=0' => 'Foreign key handling',
        'ma_deal_migrations' => 'Migrations table cleanup',
        'ma_deal_accounts' => 'Accounts table cleanup',
        'ma_deal_transactions' => 'Transactions table cleanup',
        'DELETE FROM.*wp_options.*ma_deal' => 'Options cleanup',
    ];

    foreach ($checks as $pattern => $description) {
        if (preg_match('/' . $pattern . '/i', $uninstall_content)) {
            echo "  ✓ {$description}: Present\n";
        } else {
            echo "  ⚠ {$description}: Missing or different\n";
        }
    }
} else {
    echo "✗ uninstall.php not found\n";
}

// TEST 4: Check for orphaned data
echo "\n[TEST 4] Data Cleanup Verification\n";
echo "-----------------------------------------------------------------\n";

echo "Tables that will be dropped during uninstall:\n";
foreach ($tables_before as $table) {
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    echo "  - " . str_replace($wpdb->prefix, '', $table) . ": {$count} records\n";
}

// TEST 5: Cache cleanup
echo "\n[TEST 5] Cache Cleanup\n";
echo "-----------------------------------------------------------------\n";

try {
    $cache_service = new MADealRoom\Services\CacheService();

    // Set a test cache entry
    $cache_service->set('uninstall_test', 'test_value', 60);

    // Check it exists
    if ($cache_service->has('uninstall_test')) {
        echo "✓ Test cache entry created\n";

        // Flush cache
        $cache_service->flush();

        // Verify it's gone
        if (!$cache_service->has('uninstall_test')) {
            echo "✓ Cache flush working correctly\n";
        } else {
            echo "⚠ Cache entry still exists after flush\n";
        }
    }
} catch (Exception $e) {
    echo "⚠ Cache test failed: " . $e->getMessage() . "\n";
}

echo "\n=================================================================\n";
echo "DEACTIVATION & UNINSTALL TEST COMPLETE\n";
echo "=================================================================\n\n";

echo "MANUAL STEPS REQUIRED:\n";
echo "1. Deactivate plugin:\n";
echo "   docker exec ma-dealroom-cli wp plugin deactivate ma-deal-room\n\n";

echo "2. Verify deactivation:\n";
echo "   - Cron jobs should be cleared\n";
echo "   - Database tables should remain\n";
echo "   - Plugin options should remain\n\n";

echo "3. Reactivate plugin:\n";
echo "   docker exec ma-dealroom-cli wp plugin activate ma-deal-room\n\n";

echo "4. Verify reactivation:\n";
echo "   - All features should work\n";
echo "   - No duplicate data created\n";
echo "   - Cron jobs rescheduled\n\n";

echo "5. Uninstall plugin (DESTRUCTIVE - only in test environment):\n";
echo "   docker exec ma-dealroom-cli wp plugin uninstall ma-deal-room --deactivate\n\n";

echo "6. Verify uninstall:\n";
echo "   - All {$table_count_before} tables should be dropped\n";
echo "   - All {$options_before} plugin options should be deleted\n";
echo "   - No orphaned data should remain\n\n";

echo "=================================================================\n";
