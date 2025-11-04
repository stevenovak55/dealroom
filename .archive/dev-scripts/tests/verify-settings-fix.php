<?php
/**
 * Verify Settings Fix
 */

require_once __DIR__ . '/wp-load.php';

echo "========================================\n";
echo "Verifying Settings Fix\n";
echo "========================================\n\n";

echo "1. Checking API namespace in localized script...\n";

// Simulate what WordPress would do
$api_url = rest_url('ma-deal-room/v1');
echo "   API URL: $api_url\n";

if (strpos($api_url, 'ma-deal-room/v1') !== false) {
    echo "   ✓ Correct namespace\n\n";
} else {
    echo "   ✗ Wrong namespace!\n\n";
}

echo "2. Checking settings endpoint...\n";
$routes = rest_get_server()->get_routes();
$settings_route = null;
foreach ($routes as $route => $handlers) {
    if (strpos($route, 'settings') !== false && strpos($route, 'ma-deal-room') !== false) {
        $settings_route = $route;
        break;
    }
}

if ($settings_route) {
    echo "   ✓ Settings route exists: $settings_route\n\n";
} else {
    echo "   ✗ Settings route not found!\n\n";
}

echo "3. Testing with WordPress user authentication...\n";

// Get first admin user
$admin = get_users(['role' => 'administrator', 'number' => 1])[0] ?? null;

if ($admin) {
    echo "   Found admin user: {$admin->user_login} (ID: {$admin->ID})\n";

    // Set current user
    wp_set_current_user($admin->ID);

    // Test GET request
    $request = new WP_REST_Request('GET', '/ma-deal-room/v1/settings');
    $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));

    $response = rest_do_request($request);

    echo "   GET /ma-deal-room/v1/settings: ";
    if ($response->is_error()) {
        echo "✗ " . $response->as_error()->get_error_message() . "\n";
    } else {
        echo "✓ Success\n";
        $data = $response->get_data();
        if (isset($data['data'])) {
            echo "   Settings returned:\n";
            echo "     - account_name: " . ($data['data']['account_name'] ?? 'N/A') . "\n";
            echo "     - company_name: " . ($data['data']['company_name'] ?? 'N/A') . "\n";
            echo "     - email: " . ($data['data']['email'] ?? 'N/A') . "\n";
        }
    }
} else {
    echo "   ✗ No admin user found\n";
}

echo "\n========================================\n";
echo "Instructions for Testing in Browser:\n";
echo "========================================\n";
echo "1. Go to: http://localhost:8080/agent-dashboard/#/settings\n";
echo "2. Make some changes to any field\n";
echo "3. Click 'Save Settings'\n";
echo "4. You should see: 'Settings saved successfully!'\n";
echo "5. Refresh the page - your changes should persist\n";
echo "\n";
echo "If you still see errors, clear your browser cache:\n";
echo "- Chrome/Edge: Ctrl+Shift+Delete > Clear cached images and files\n";
echo "- Firefox: Ctrl+Shift+Delete > Cached Web Content\n";
echo "- Or use Incognito/Private mode\n";
echo "========================================\n";
