<?php
/**
 * Record migration 013 in migrations table
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

global $wpdb;

// Check if already recorded
$existing = $wpdb->get_var(
    "SELECT migration_number FROM {$wpdb->prefix}ma_deal_migrations WHERE migration_number = '013'"
);

if ($existing) {
    echo "Migration 013 is already recorded.\n";
    exit(0);
}

// Record it
$result = $wpdb->insert(
    $wpdb->prefix . 'ma_deal_migrations',
    [
        'migration_number' => '013',
        'migration_name' => 'Add Performance Indexes',
        'applied_at' => current_time('mysql'),
        'rollback_available' => 1,
    ]
);

if ($wpdb->insert_id) {
    echo "✓ Migration 013 recorded successfully!\n";
    echo "Migration ID: " . $wpdb->insert_id . "\n";
} else {
    echo "✗ Failed to record migration 013: " . $wpdb->last_error . "\n";
    exit(1);
}
