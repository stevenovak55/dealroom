<?php
/**
 * Manually run migration 013 to add performance indexes
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=================================================================\n";
echo "Running Migration 013: Add Performance Indexes\n";
echo "=================================================================\n\n";

// Check if migration 013 is already applied
global $wpdb;
$migrations_table = $wpdb->prefix . 'ma_deal_migrations';
$existing = $wpdb->get_var(
    "SELECT migration_number FROM {$migrations_table} WHERE migration_number = '013'"
);

if ($existing) {
    echo "Migration 013 is already applied!\n";
    echo "Applied at: " . $wpdb->get_var(
        "SELECT applied_at FROM {$migrations_table} WHERE migration_number = '013'"
    ) . "\n";
    exit(0);
}

echo "Running migrations via Migrator...\n\n";

try {
    $migrator = new MADealRoom\Database\Migrator();
    $results = $migrator->run();

    echo "Migration results:\n";
    foreach ($results as $number => $result) {
        echo sprintf(
            "  [%s] Migration %s: %s\n",
            $result['status'] === 'success' ? '✓' : ($result['status'] === 'skipped' ? '-' : '✗'),
            $number,
            $result['message']
        );
    }

    // Check if migration 013 was applied
    $applied = $wpdb->get_var(
        "SELECT migration_number FROM {$migrations_table} WHERE migration_number = '013'"
    );

    if ($applied) {
        echo "\n✓ Migration 013 successfully applied!\n";

        // Count indexes
        $test_tables = [
            'wp_ma_deal_transactions' => 4,
            'wp_ma_deal_tasks' => 3,
            'wp_ma_deal_templates' => 3,
            'wp_ma_deal_notifications' => 3,
        ];

        $total_indexes = 0;
        foreach ($test_tables as $table => $expected) {
            $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}", ARRAY_A);
            $count = count(array_unique(array_column($indexes, 'Key_name')));
            $total_indexes += $count;
            echo "  - {$table}: {$count} indexes\n";
        }

        echo "\nTotal indexes created: {$total_indexes}\n";
    } else {
        echo "\n✗ Migration 013 was not applied!\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n✗ Error running migrations: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "Migration 013 completed successfully!\n";
echo "=================================================================\n";
