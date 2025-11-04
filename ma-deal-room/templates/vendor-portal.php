<?php
/**
 * Vendor Portal Template
 * 
 * Public-facing page for vendors to respond to requests
 * 
 * @package MADealRoom
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Verify token parameter exists
$token = $_GET['token'] ?? '';

if (empty($token)) {
    wp_die('Invalid vendor portal link. Please contact the agent for assistance.', 'Invalid Link', ['response' => 403]);
}

// Get plugin URL for assets
$plugin_url = plugins_url('', dirname(__FILE__));

// Read and serve HTML with WordPress integration
$html_file = dirname(__FILE__) . '/../assets/public/vendor-portal.html';

if (!file_exists($html_file)) {
    wp_die('Vendor portal template not found.', 'Template Error', ['response' => 500]);
}

$html_content = file_get_contents($html_file);

// Inject WordPress REST API URL
$html_content = str_replace(
    "const apiUrl = window.maDealRoom?.apiUrl || '/wp-json/ma-deal/v1';",
    "const apiUrl = '" . esc_js(rest_url('ma-deal/v1')) . "';",
    $html_content
);

echo $html_content;
