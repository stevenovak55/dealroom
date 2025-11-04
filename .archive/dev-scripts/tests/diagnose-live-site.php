<?php
/**
 * Comprehensive diagnostic script for live site issues
 * Upload this to your WordPress root and access via browser
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

// Set content type
header('Content-Type: text/plain; charset=utf-8');

echo "=== MA Deal Room Live Site Diagnostic ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Site URL: " . get_site_url() . "\n\n";

// 1. Plugin Status
echo "1. PLUGIN STATUS\n";
echo str_repeat('-', 50) . "\n";

$active_plugins = get_option('active_plugins', []);
$plugin_path = 'ma-deal-room/ma-deal-room.php';
$is_active = in_array($plugin_path, $active_plugins);

echo "Plugin active: " . ($is_active ? "YES ✓" : "NO ✗") . "\n";

if ($is_active) {
    $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
    echo "Plugin version: " . ($plugin_data['Version'] ?? 'Unknown') . "\n";
}

// Check critical files
$critical_files = [
    'Main plugin file' => WP_PLUGIN_DIR . '/ma-deal-room/ma-deal-room.php',
    'Plugin.php' => WP_PLUGIN_DIR . '/ma-deal-room/src/Core/Plugin.php',
    'MLSConfig model' => WP_PLUGIN_DIR . '/ma-deal-room/src/Models/MLSConfig.php',
    'MLSConfigRepository' => WP_PLUGIN_DIR . '/ma-deal-room/src/Repositories/MLSConfigRepository.php',
    'AnalyticsController' => WP_PLUGIN_DIR . '/ma-deal-room/src/REST/Controllers/AnalyticsController.php',
    'MLSController' => WP_PLUGIN_DIR . '/ma-deal-room/src/REST/Controllers/MLSController.php',
];

echo "\nCritical files:\n";
foreach ($critical_files as $name => $path) {
    $exists = file_exists($path);
    echo "  " . ($exists ? "✓" : "✗") . " {$name}\n";
    if (!$exists) {
        echo "    Missing: {$path}\n";
    }
}

// 2. WordPress Options
echo "\n2. WORDPRESS OPTIONS\n";
echo str_repeat('-', 50) . "\n";

$options = [
    'ma_deal_room_version',
    'ma_deal_room_activated',
    'ma_deal_room_task_definitions_synced',
    'ma_deal_room_task_definitions_count',
    'ma_deal_room_templates_synced',
    'ma_deal_room_templates_sync_count',
];

foreach ($options as $option) {
    $value = get_option($option, 'NOT SET');
    echo "{$option}: {$value}\n";
}

// 3. Database Tables
echo "\n3. DATABASE TABLES\n";
echo str_repeat('-', 50) . "\n";

global $wpdb;

$required_tables = [
    'wp_ma_deal_accounts',
    'wp_ma_deal_transactions',
    'wp_ma_deal_tasks',
    'wp_ma_deal_task_definitions',
    'wp_ma_deal_templates',
    'wp_ma_deal_documents',
    'wp_ma_deal_notifications',
    'wp_ma_deal_users',
    'wp_ma_deal_mls_config',
    'wp_ma_deal_mls_sync_log',
];

echo "Table status:\n";
foreach ($required_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        echo "  ✓ {$table} ({$count} records)\n";
    } else {
        echo "  ✗ {$table} MISSING\n";
    }
}

// 4. REST API Routes
echo "\n4. REST API ROUTES\n";
echo str_repeat('-', 50) . "\n";

$rest_server = rest_get_server();
$namespaces = $rest_server->get_namespaces();

echo "MA Deal Room namespace registered: " . (in_array('ma-deal-room/v1', $namespaces) ? "YES ✓" : "NO ✗") . "\n";

if (in_array('ma-deal-room/v1', $namespaces)) {
    $routes = $rest_server->get_routes('ma-deal-room/v1');
    echo "\nRegistered routes (" . count($routes) . "):\n";

    $critical_routes = [
        '/ma-deal-room/v1/analytics/overview',
        '/ma-deal-room/v1/analytics/agent-performance',
        '/ma-deal-room/v1/analytics/transaction-trends',
        '/ma-deal-room/v1/mls',
        '/ma-deal-room/v1/mls/config',
        '/ma-deal-room/v1/mls/providers',
    ];

    foreach ($critical_routes as $route) {
        $exists = isset($routes[$route]);
        echo "  " . ($exists ? "✓" : "✗") . " {$route}\n";
    }
}

// 5. Service Container
echo "\n5. SERVICE CONTAINER\n";
echo str_repeat('-', 50) . "\n";

try {
    if (class_exists('\\MADealRoom\\Core\\Plugin')) {
        $plugin = \MADealRoom\Core\Plugin::instance();
        echo "Plugin instance: OK ✓\n";

        $container = $plugin->container();
        echo "Service container: OK ✓\n";

        // Check critical services
        $critical_services = [
            'analytics_service',
            'mls_config_repository',
            'transaction_repository',
            'task_definition_repository',
        ];

        echo "\nCritical services:\n";
        foreach ($critical_services as $service) {
            try {
                $instance = $container->get($service);
                echo "  ✓ {$service}: " . get_class($instance) . "\n";
            } catch (Exception $e) {
                echo "  ✗ {$service}: NOT REGISTERED\n";
                echo "    Error: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "✗ Plugin class not found\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

// 6. Test REST API Endpoints
echo "\n6. REST API ENDPOINT TESTS\n";
echo str_repeat('-', 50) . "\n";

// Get current user
$current_user = wp_get_current_user();
echo "Current user: " . ($current_user->ID ? $current_user->user_login . " (ID: {$current_user->ID})" : "Not logged in") . "\n";
echo "User roles: " . implode(', ', $current_user->roles) . "\n\n";

// Test analytics overview endpoint
echo "Testing analytics/overview endpoint:\n";
$request = new WP_REST_Request('GET', '/ma-deal-room/v1/analytics/overview');
$response = rest_do_request($request);

if (is_wp_error($response)) {
    echo "  ✗ Error: " . $response->get_error_message() . "\n";
} else {
    $status = $response->get_status();
    $data = $response->get_data();

    echo "  Status: {$status}\n";

    if ($status === 200) {
        echo "  ✓ Success\n";
        echo "  Data keys: " . implode(', ', array_keys($data)) . "\n";
    } elseif ($status === 403) {
        echo "  ✗ Forbidden (permission issue)\n";
        if (isset($data['message'])) {
            echo "  Message: {$data['message']}\n";
        }
    } else {
        echo "  ✗ Failed\n";
        if (isset($data['message'])) {
            echo "  Message: {$data['message']}\n";
        }
    }
}

// Test MLS providers endpoint
echo "\nTesting mls/providers endpoint:\n";
$request = new WP_REST_Request('GET', '/ma-deal-room/v1/mls/providers');
$response = rest_do_request($request);

if (is_wp_error($response)) {
    echo "  ✗ Error: " . $response->get_error_message() . "\n";
} else {
    $status = $response->get_status();
    $data = $response->get_data();

    echo "  Status: {$status}\n";

    if ($status === 200) {
        echo "  ✓ Success\n";
        if (is_array($data)) {
            echo "  Providers: " . count($data) . "\n";
        }
    } else {
        echo "  ✗ Failed\n";
        if (isset($data['message'])) {
            echo "  Message: {$data['message']}\n";
        }
    }
}

// 7. Recent Errors
echo "\n7. RECENT PHP ERRORS\n";
echo str_repeat('-', 50) . "\n";

$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "Error log: {$error_log}\n\n";
    $recent = shell_exec("tail -30 {$error_log} 2>/dev/null | grep -i 'ma.deal\\|madealroom' | tail -10");
    if ($recent) {
        echo "Last 10 MA Deal Room errors:\n";
        echo $recent;
    } else {
        echo "No recent MA Deal Room errors found ✓\n";
    }
} else {
    $wp_debug_log = ABSPATH . 'wp-content/debug.log';
    if (file_exists($wp_debug_log)) {
        echo "Debug log: {$wp_debug_log}\n\n";
        $content = file_get_contents($wp_debug_log);
        $lines = explode("\n", $content);
        $ma_deal_lines = array_filter($lines, function($line) {
            return stripos($line, 'ma deal') !== false || stripos($line, 'madealroom') !== false;
        });
        $last_10 = array_slice($ma_deal_lines, -10);

        if (!empty($last_10)) {
            echo "Last 10 MA Deal Room errors:\n";
            echo implode("\n", $last_10) . "\n";
        } else {
            echo "No recent MA Deal Room errors found ✓\n";
        }
    } else {
        echo "No error log found\n";
    }
}

// 8. WordPress Configuration
echo "\n8. WORDPRESS CONFIGURATION\n";
echo str_repeat('-', 50) . "\n";

echo "WP_DEBUG: " . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . "\n";
echo "WP_DEBUG_LOG: " . (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'true' : 'false') . "\n";
echo "WP_DEBUG_DISPLAY: " . (defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'true' : 'false') . "\n";
echo "WordPress version: " . get_bloginfo('version') . "\n";
echo "PHP version: " . PHP_VERSION . "\n";
echo "MySQL version: " . $wpdb->db_version() . "\n";

// 9. Recommendations
echo "\n9. RECOMMENDATIONS\n";
echo str_repeat('-', 50) . "\n";

$issues = [];

if (!$is_active) {
    $issues[] = "Plugin is not active - activate it in Plugins menu";
}

$task_def_count = get_option('ma_deal_room_task_definitions_count', 0);
if ($task_def_count < 100) {
    $issues[] = "Task definitions not synced ({$task_def_count} found, should be 276)";
}

if (!in_array('ma-deal-room/v1', $namespaces)) {
    $issues[] = "REST API routes not registered - try deactivating and reactivating plugin";
}

if (empty($issues)) {
    echo "✓ No critical issues detected\n";
    echo "\nIf you're still seeing errors, please:\n";
    echo "1. Check browser console for JavaScript errors (F12)\n";
    echo "2. Clear browser cache and hard refresh (Ctrl+Shift+R)\n";
    echo "3. Check Network tab in browser console to see which API calls are failing\n";
} else {
    echo "Issues detected:\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". {$issue}\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Diagnostic complete\n";
