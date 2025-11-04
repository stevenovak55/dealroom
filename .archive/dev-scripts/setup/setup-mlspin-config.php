<?php
/**
 * MLS PIN Configuration Setup
 *
 * Creates MLS PIN configuration in the database
 */

// Load WordPress
$wp_load_paths = [
    __DIR__ . '/../../wordpress/wp-load.php',
    __DIR__ . '/../wordpress/wp-load.php',
    '/var/www/html/wp-load.php',
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("Error: Could not load WordPress. Please run this from your WordPress directory.\n");
}

echo "=== MLS PIN Configuration Setup ===\n\n";

// Get account ID (use first available account)
global $wpdb;
$account_id = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}ma_deal_accounts ORDER BY id ASC LIMIT 1");

if (!$account_id) {
    die("Error: No account found. Please create an account first.\n");
}

echo "Using Account ID: {$account_id}\n\n";

// MLS PIN credentials
$credentials = [
    'api_url' => 'https://api.bridgedataoutput.com/api/v2/OData/shared_mlspin_41854c5',
    'server_token' => '1c69fed3083478d187d4ce8deb8788ed',
];

// Encrypt credentials
$json = wp_json_encode($credentials);
$key = wp_salt('auth');
$encrypted = openssl_encrypt(
    $json,
    'AES-256-CBC',
    hash('sha256', $key),
    0,
    substr(hash('sha256', $key), 0, 16)
);
$encrypted_credentials = base64_encode($encrypted);

// Check if config already exists
$existing = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$wpdb->prefix}ma_deal_mls_config WHERE name = %s AND account_id = %d",
    'MLS PIN',
    $account_id
));

if ($existing) {
    echo "MLS PIN configuration already exists (ID: {$existing})\n";
    echo "Updating existing configuration...\n";

    $result = $wpdb->update(
        "{$wpdb->prefix}ma_deal_mls_config",
        [
            'credentials' => $encrypted_credentials,
            'updated_at' => current_time('mysql'),
        ],
        [
            'id' => $existing,
        ],
        ['%s', '%s'],
        ['%d']
    );

    if ($result !== false) {
        echo "✓ Configuration updated successfully!\n";
    } else {
        die("✗ Failed to update configuration\n");
    }
} else {
    echo "Creating new MLS PIN configuration...\n";

    $result = $wpdb->insert(
        "{$wpdb->prefix}ma_deal_mls_config",
        [
            'account_id' => $account_id,
            'name' => 'MLS PIN',
            'provider_type' => 'bridge',
            'credentials' => $encrypted_credentials,
            'settings' => null,
            'is_active' => 1,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ],
        ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
    );

    if ($result) {
        $config_id = $wpdb->insert_id;
        echo "✓ Configuration created successfully!\n";
        echo "Configuration ID: {$config_id}\n";
    } else {
        die("✗ Failed to create configuration\n");
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✓ MLS PIN is now configured and ready to use!\n";
echo str_repeat("=", 80) . "\n\n";

echo "You can now:\n";
echo "1. Search MLS listings via: POST /wp-json/ma-deal-room/v1/mls/search\n";
echo "2. Import properties via: POST /wp-json/ma-deal-room/v1/mls/import\n";
echo "3. Schedule automatic sync via: POST /wp-json/ma-deal-room/v1/mls/schedule-sync\n\n";

echo "Example search request:\n";
echo "curl -X POST 'http://yoursite.com/wp-json/ma-deal-room/v1/mls/search' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\n";
echo "    \"criteria\": {\n";
echo "      \"city\": \"Boston\",\n";
echo "      \"status\": \"Active\",\n";
echo "      \"min_price\": 500000\n";
echo "    }\n";
echo "  }'\n\n";
