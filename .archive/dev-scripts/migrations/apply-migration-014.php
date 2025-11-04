#!/usr/bin/env php
<?php
/**
 * Apply Migration 014 - Rate Limits Table and TOTP Replay Prevention
 */

// Load WordPress
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    '/var/www/html/wp-load.php',
    dirname(__DIR__) . '/wp-load.php'
];

foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

if (!defined('ABSPATH')) {
    die("Error: WordPress not loaded\n");
}

global $wpdb;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          APPLYING MIGRATION 014                                ║\n";
echo "║  Rate Limits Table + TOTP Replay Prevention                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check if migration already applied
$migrations_table = $wpdb->prefix . 'ma_migrations';
$existing = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$migrations_table} WHERE migration = %s",
    '014_create_rate_limits_table'
));

if ($existing) {
    echo "⚠️  Migration 014 already applied on " . $existing->applied_at . "\n";
    echo "    Skipping to avoid duplicate execution.\n\n";

    // Check if tables exist
    $rate_limits_table = $wpdb->prefix . 'ma_rate_limits';
    $rate_limits_exists = $wpdb->get_var("SHOW TABLES LIKE '{$rate_limits_table}'");

    if ($rate_limits_exists) {
        echo "✓ Rate limits table exists\n";
    } else {
        echo "✗ Rate limits table missing (migration may have failed)\n";
    }

    // Check if column exists
    $users_table = $wpdb->prefix . 'ma_users';
    $columns = $wpdb->get_results("DESCRIBE {$users_table}");
    $column_names = array_column($columns, 'Field');

    if (in_array('last_totp_timestamp', $column_names)) {
        echo "✓ last_totp_timestamp column exists in ma_users\n";
    } else {
        echo "✗ last_totp_timestamp column missing (migration may have failed)\n";
    }

    echo "\n";
    exit(0);
}

echo "Applying migration 014...\n\n";

// Read migration file
$migration_file = __DIR__ . '/ma-deal-room/database/migrations/014_create_rate_limits_table.sql';

if (!file_exists($migration_file)) {
    die("Error: Migration file not found: {$migration_file}\n");
}

$sql = file_get_contents($migration_file);

// Replace {prefix} with actual table prefix
$sql = str_replace('{prefix}', $wpdb->prefix, $sql);

// Split into individual statements (separated by semicolons)
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success = true;
$executed = 0;

foreach ($statements as $statement) {
    // Skip comments and empty statements
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }

    echo "Executing: " . substr($statement, 0, 50) . "...\n";

    $result = $wpdb->query($statement);

    if ($result === false) {
        echo "✗ Failed: " . $wpdb->last_error . "\n";
        $success = false;
        break;
    } else {
        echo "✓ Success\n";
        $executed++;
    }
}

if ($success) {
    // Record migration
    $wpdb->insert(
        $migrations_table,
        [
            'migration' => '014_create_rate_limits_table',
            'applied_at' => current_time('mysql')
        ],
        ['%s', '%s']
    );

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ MIGRATION 014 APPLIED SUCCESSFULLY                         ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Statements executed: {$executed}\n";
    echo "\n";
    echo "Changes made:\n";
    echo "  ✓ Created table: {$wpdb->prefix}ma_rate_limits\n";
    echo "  ✓ Added column: {$wpdb->prefix}ma_users.last_totp_timestamp\n";
    echo "\n";

    exit(0);
} else {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✗ MIGRATION 014 FAILED                                        ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    echo "Error: " . $wpdb->last_error . "\n";
    echo "\n";

    exit(1);
}
