<?php
/**
 * Diagnostic script to check live site status
 * Upload this to your live site and access via browser to see what's wrong
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

header('Content-Type: text/plain');

echo "=== MA Deal Room Live Site Diagnostic ===\n\n";

// Check if plugin is active
$active_plugins = get_option('active_plugins', []);
$is_active = in_array('ma-deal-room/ma-deal-room.php', $active_plugins);

echo "1. Plugin Status: " . ($is_active ? "ACTIVE" : "INACTIVE") . "\n\n";

// Check plugin version
$plugin_data = get_plugin_data(__DIR__ . '/wp-content/plugins/ma-deal-room/ma-deal-room.php');
echo "2. Plugin Version: " . ($plugin_data['Version'] ?? 'Unknown') . "\n\n";

// Check if critical files exist
$critical_files = [
    'MLSConfig Model' => 'wp-content/plugins/ma-deal-room/src/Models/MLSConfig.php',
    'MLSConfigRepository' => 'wp-content/plugins/ma-deal-room/src/Repositories/MLSConfigRepository.php',
    'Plugin.php' => 'wp-content/plugins/ma-deal-room/src/Core/Plugin.php',
];

echo "3. Critical Files Check:\n";
foreach ($critical_files as $name => $path) {
    $full_path = __DIR__ . '/' . $path;
    $exists = file_exists($full_path);
    echo "   - {$name}: " . ($exists ? "EXISTS" : "MISSING!") . "\n";
    if ($exists) {
        $mod_time = filemtime($full_path);
        echo "     Modified: " . date('Y-m-d H:i:s', $mod_time) . "\n";
    }
}

echo "\n4. Service Container Check:\n";
try {
    if (class_exists('\MADealRoom\Core\Plugin')) {
        $plugin = \MADealRoom\Core\Plugin::instance();
        echo "   - Plugin instance: OK\n";

        try {
            $container = $plugin->container();
            echo "   - Service container: OK\n";

            // Try to get MLS repository
            try {
                $mls_repo = $container->get('mls_config_repository');
                echo "   - MLS Config Repository: REGISTERED ✓\n";
                echo "     Class: " . get_class($mls_repo) . "\n";
            } catch (Exception $e) {
                echo "   - MLS Config Repository: NOT REGISTERED ✗\n";
                echo "     Error: " . $e->getMessage() . "\n";
            }
        } catch (Exception $e) {
            echo "   - Service container: ERROR\n";
            echo "     Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   - Plugin class not found\n";
    }
} catch (Exception $e) {
    echo "   - Error: " . $e->getMessage() . "\n";
}

echo "\n5. Database Tables Check:\n";
global $wpdb;
$tables = [
    'wp_ma_deal_mls_config',
    'wp_ma_deal_mls_sync_log',
    'wp_ma_deal_task_definitions'
];

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    echo "   - {$table}: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        echo "     Records: {$count}\n";
    }
}

echo "\n6. WordPress Options:\n";
$options = [
    'ma_deal_room_version',
    'ma_deal_room_activated',
    'ma_deal_room_task_definitions_synced',
    'ma_deal_room_task_definitions_count'
];

foreach ($options as $option) {
    $value = get_option($option, 'NOT SET');
    echo "   - {$option}: {$value}\n";
}

echo "\n7. Recent PHP Errors:\n";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "   Error log: {$error_log}\n";
    $recent = shell_exec("tail -20 {$error_log} 2>/dev/null");
    if ($recent) {
        echo "   Last 20 lines:\n" . $recent;
    }
} else {
    $wp_debug_log = __DIR__ . '/wp-content/debug.log';
    if (file_exists($wp_debug_log)) {
        echo "   Debug log: {$wp_debug_log}\n";
        $recent = file_get_contents($wp_debug_log);
        $lines = explode("\n", $recent);
        $last_20 = array_slice($lines, -20);
        echo "   Last 20 lines:\n" . implode("\n", $last_20);
    } else {
        echo "   No error log found\n";
    }
}

echo "\n=== End Diagnostic ===\n";
