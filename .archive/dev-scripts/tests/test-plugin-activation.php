<?php
/**
 * Test script to verify plugin activation produces no unexpected output
 */

echo "=== Plugin Activation Output Test ===\n\n";

// Load WordPress
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/wp-load.php');

echo "Step 1: WordPress loaded successfully\n";

// Check if plugin is active
$active_plugins = get_option('active_plugins', []);
$plugin_path = 'ma-deal-room/ma-deal-room.php';
$is_active = in_array($plugin_path, $active_plugins);

echo "Step 2: Plugin status: " . ($is_active ? "ACTIVE" : "INACTIVE") . "\n";

if ($is_active) {
    echo "Step 3: Attempting to deactivate and reactivate plugin\n";

    // Deactivate
    deactivate_plugins($plugin_path);
    echo "  - Plugin deactivated\n";

    // Start output buffering before reactivation
    ob_start();

    // Reactivate (this runs the activation hook)
    $result = activate_plugin($plugin_path);

    // Capture any output
    $activation_output = ob_get_clean();

    if (is_wp_error($result)) {
        echo "  - ✗ ACTIVATION FAILED: " . $result->get_error_message() . "\n";
    } else {
        echo "  - ✓ Plugin reactivated successfully\n";
    }

    echo "\nStep 4: Checking for unexpected output\n";
    if (empty($activation_output)) {
        echo "  - ✓ SUCCESS: No unexpected output during activation\n";
        echo "  - Output length: 0 characters\n";
    } else {
        echo "  - ✗ FAILED: Plugin generated unexpected output\n";
        echo "  - Output length: " . strlen($activation_output) . " characters\n";
        echo "\n  Output content:\n";
        echo "  ---BEGIN OUTPUT---\n";
        echo $activation_output;
        echo "\n  ---END OUTPUT---\n";
    }
} else {
    echo "Step 3: Plugin not active, activating from scratch\n";

    // Start output buffering before activation
    ob_start();

    // Activate plugin
    $result = activate_plugin($plugin_path);

    // Capture any output
    $activation_output = ob_get_clean();

    if (is_wp_error($result)) {
        echo "  - ✗ ACTIVATION FAILED: " . $result->get_error_message() . "\n";
    } else {
        echo "  - ✓ Plugin activated successfully\n";
    }

    echo "\nStep 4: Checking for unexpected output\n";
    if (empty($activation_output)) {
        echo "  - ✓ SUCCESS: No unexpected output during activation\n";
        echo "  - Output length: 0 characters\n";
    } else {
        echo "  - ✗ FAILED: Plugin generated unexpected output\n";
        echo "  - Output length: " . strlen($activation_output) . " characters\n";
        echo "\n  Output content:\n";
        echo "  ---BEGIN OUTPUT---\n";
        echo $activation_output;
        echo "\n  ---END OUTPUT---\n";
    }
}

echo "\n=== Test Complete ===\n";
