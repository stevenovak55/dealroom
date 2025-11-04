<?php
/**
 * Test script to verify plugin activation produces no unexpected output
 */

// Start output buffering to capture any output
ob_start();

// Load WordPress
require_once(__DIR__ . '/wp-content/plugins/ma-deal-room/ma-deal-room.php');

// Get any output that was generated
$output = ob_get_clean();

echo "=== Activation Output Test ===\n\n";

if (empty($output)) {
    echo "✓ SUCCESS: No unexpected output during plugin load\n";
    echo "Output length: 0 characters\n";
} else {
    echo "✗ FAILED: Plugin generated unexpected output\n";
    echo "Output length: " . strlen($output) . " characters\n";
    echo "\nOutput content:\n";
    echo "---BEGIN OUTPUT---\n";
    echo $output;
    echo "\n---END OUTPUT---\n";
}

echo "\n=== Test Complete ===\n";
