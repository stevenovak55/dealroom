<?php
/**
 * Debug Settings Controller
 */

require_once __DIR__ . '/wp-load.php';

echo "========================================\n";
echo "Debug Settings Controller\n";
echo "========================================\n\n";

try {
    echo "1. Loading controller class...\n";
    $controller = new MADealRoom\REST\Controllers\SettingsController();
    echo "✓ Controller instantiated\n\n";

    echo "2. Registering routes...\n";
    $controller->register_routes();
    echo "✓ Routes registered\n\n";

    echo "3. Checking if routes exist...\n";
    $routes = rest_get_server()->get_routes();

    $settings_routes = array_filter(array_keys($routes), function($route) {
        return strpos($route, 'settings') !== false;
    });

    if (empty($settings_routes)) {
        echo "✗ No settings routes found!\n\n";

        echo "Checking ma-deal-room routes:\n";
        $ma_routes = array_filter(array_keys($routes), function($route) {
            return strpos($route, 'ma-deal-room') !== false;
        });

        echo "Found " . count($ma_routes) . " ma-deal-room routes:\n";
        foreach (array_slice($ma_routes, 0, 10) as $route) {
            echo "  - $route\n";
        }
    } else {
        echo "✓ Found settings routes:\n";
        foreach ($settings_routes as $route) {
            echo "  - $route\n";
        }
    }

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n========================================\n";
