<?php
/**
 * MLS PIN Configuration Setup - Direct Database Version
 *
 * Creates MLS PIN configuration directly in the database
 */

echo "=== MLS PIN Configuration Setup ===\n\n";

// Database connection details (update these if needed)
$db_config = [
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'wordpress',
    'username' => 'wordpress',
    'password' => 'wordpress',
    'prefix' => 'wp_',
];

echo "Connecting to database...\n";

try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to database\n\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Get account ID (use first available account)
$stmt = $pdo->query("SELECT id FROM {$db_config['prefix']}ma_deal_accounts ORDER BY id ASC LIMIT 1");
$account_id = $stmt->fetchColumn();

if (!$account_id) {
    die("✗ No account found. Please create an account first.\n");
}

echo "Using Account ID: {$account_id}\n\n";

// MLS PIN credentials
$credentials = [
    'api_url' => 'https://api.bridgedataoutput.com/api/v2/OData/shared_mlspin_41854c5',
    'server_token' => '1c69fed3083478d187d4ce8deb8788ed',
];

// Encrypt credentials (using WordPress-style encryption)
$json = json_encode($credentials);

// Use a dummy salt for encryption (in production, this would use WordPress AUTH_KEY)
$key = 'ma_deal_room_encryption_key_change_this_in_production';
$encrypted = openssl_encrypt(
    $json,
    'AES-256-CBC',
    hash('sha256', $key),
    0,
    substr(hash('sha256', $key), 0, 16)
);
$encrypted_credentials = base64_encode($encrypted);

// Check if config already exists
$stmt = $pdo->prepare("SELECT id FROM {$db_config['prefix']}ma_deal_mls_config WHERE name = ? AND account_id = ?");
$stmt->execute(['MLS PIN', $account_id]);
$existing = $stmt->fetchColumn();

$current_time = date('Y-m-d H:i:s');

if ($existing) {
    echo "MLS PIN configuration already exists (ID: {$existing})\n";
    echo "Updating existing configuration...\n";

    $stmt = $pdo->prepare("
        UPDATE {$db_config['prefix']}ma_deal_mls_config
        SET credentials = ?, updated_at = ?
        WHERE id = ?
    ");

    $result = $stmt->execute([$encrypted_credentials, $current_time, $existing]);

    if ($result) {
        echo "✓ Configuration updated successfully!\n";
        $config_id = $existing;
    } else {
        die("✗ Failed to update configuration\n");
    }
} else {
    echo "Creating new MLS PIN configuration...\n";

    $stmt = $pdo->prepare("
        INSERT INTO {$db_config['prefix']}ma_deal_mls_config
        (account_id, name, provider_type, credentials, settings, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $result = $stmt->execute([
        $account_id,
        'MLS PIN',
        'bridge',
        $encrypted_credentials,
        null,
        1,
        $current_time,
        $current_time
    ]);

    if ($result) {
        $config_id = $pdo->lastInsertId();
        echo "✓ Configuration created successfully!\n";
        echo "Configuration ID: {$config_id}\n";
    } else {
        die("✗ Failed to create configuration\n");
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✓ MLS PIN is now configured and ready to use!\n";
echo str_repeat("=", 80) . "\n\n";

echo "Configuration Details:\n";
echo "- Configuration ID: {$config_id}\n";
echo "- Account ID: {$account_id}\n";
echo "- Provider: Bridge Interactive\n";
echo "- Status: Active\n";
echo "- API URL: https://api.bridgedataoutput.com/api/v2/OData/shared_mlspin_41854c5\n\n";

echo "You can now use the MLS PIN integration via REST API:\n\n";

echo "1. Search MLS listings:\n";
echo "   POST /wp-json/ma-deal-room/v1/mls/search\n";
echo "   {\"criteria\": {\"city\": \"Boston\", \"status\": \"Active\"}}\n\n";

echo "2. Import property:\n";
echo "   POST /wp-json/ma-deal-room/v1/mls/import\n";
echo "   {\"mls_number\": \"73350089\", \"queue_photos\": true}\n\n";

echo "3. Schedule automatic sync:\n";
echo "   POST /wp-json/ma-deal-room/v1/mls/schedule-sync\n";
echo "   {\"config_id\": {$config_id}}\n\n";

echo "Next: Test the integration by searching for properties!\n";
