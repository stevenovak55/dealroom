<?php
/**
 * Run DocuSign database migrations
 */

// Load WordPress
$wp_load_path = '/var/www/html/wp-load.php';

if (!file_exists($wp_load_path)) {
    die("✗ Could not find wp-load.php at: $wp_load_path\n");
}

require_once($wp_load_path);

global $wpdb;

echo "\n=== Running DocuSign Database Migrations ===\n\n";

// Migration files in the plugin directory
$plugin_dir = '/var/www/html/wp-content/plugins/ma-deal-room';
$migrations_dir = $plugin_dir . '/database/migrations/';

// Migration files
$migrations = [
    '024_create_docusign_config_table.sql',
    '025_create_docusign_envelopes_table.sql',
    '026_create_docusign_webhook_log_table.sql',
];

$success_count = 0;
$error_count = 0;

foreach ($migrations as $migration_file) {
    $file_path = $migrations_dir . $migration_file;

    if (!file_exists($file_path)) {
        echo "✗ Migration file not found: $migration_file\n";
        echo "  Looked at: $file_path\n";
        $error_count++;
        continue;
    }

    echo "Running: $migration_file...\n";

    // Read migration SQL
    $sql = file_get_contents($file_path);

    // Replace {prefix} placeholder with actual table prefix
    $sql = str_replace('{prefix}', $wpdb->prefix, $sql);

    // Execute migration
    $result = $wpdb->query($sql);

    if ($result === false) {
        echo "✗ FAILED: {$migration_file}\n";
        echo "  Error: " . $wpdb->last_error . "\n";
        $error_count++;
    } else {
        echo "✓ SUCCESS: {$migration_file}\n";
        $success_count++;
    }

    echo "\n";
}

echo "=== Migration Summary ===\n";
echo "✓ Successful: $success_count\n";
echo "✗ Failed: $error_count\n";

// Verify tables exist
echo "\n=== Verifying Tables ===\n";

$tables_to_check = [
    $wpdb->prefix . 'ma_deal_docusign_config',
    $wpdb->prefix . 'ma_deal_docusign_envelopes',
    $wpdb->prefix . 'ma_deal_docusign_webhook_log',
];

foreach ($tables_to_check as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "✓ Table exists: $table (rows: $count)\n";
    } else {
        echo "✗ Table missing: $table\n";
    }
}

echo "\n=== DocuSign Migrations Complete ===\n";
