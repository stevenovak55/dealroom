<?php
/**
 * Manually run migration 017 to enhance user sessions table
 * T2.1.4: Session Regeneration on Login
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

echo "=================================================================\n";
echo "Running Migration 017: Enhance User Sessions Table\n";
echo "T2.1.4: Session Regeneration on Login\n";
echo "=================================================================\n\n";

// Check if migration 017 is already applied
global $wpdb;
$migrations_table = $wpdb->prefix . 'ma_deal_migrations';
$existing = $wpdb->get_var(
    "SELECT migration_number FROM {$migrations_table} WHERE migration_number = '017'"
);

if ($existing) {
    echo "Migration 017 is already applied!\n";
    echo "Applied at: " . $wpdb->get_var(
        "SELECT applied_at FROM {$migrations_table} WHERE migration_number = '017'"
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

    // Check if migration 017 was applied
    $applied = $wpdb->get_var(
        "SELECT migration_number FROM {$migrations_table} WHERE migration_number = '017'"
    );

    if ($applied) {
        echo "\n✓ Migration 017 successfully applied!\n";

        // Verify new columns were added
        $table_name = $wpdb->prefix . 'ma_deal_user_sessions';
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}", ARRAY_A);
        $column_names = array_column($columns, 'Field');

        $expected_columns = [
            'session_id',
            'browser',
            'platform',
            'last_activity_ip',
            'last_activity_user_agent',
            'invalidation_reason',
        ];

        echo "\nVerifying new columns:\n";
        foreach ($expected_columns as $col) {
            $exists = in_array($col, $column_names);
            echo sprintf(
                "  [%s] %s: %s\n",
                $exists ? '✓' : '✗',
                $col,
                $exists ? 'Added' : 'Missing'
            );
        }

        // Verify new index
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}", ARRAY_A);
        $index_names = array_unique(array_column($indexes, 'Key_name'));
        $has_activity_index = in_array('idx_last_activity_tracking', $index_names);

        echo sprintf(
            "  [%s] idx_last_activity_tracking: %s\n",
            $has_activity_index ? '✓' : '✗',
            $has_activity_index ? 'Created' : 'Missing'
        );

    } else {
        echo "\n✗ Migration 017 was not applied!\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "\n✗ Error running migrations: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "Migration 017 completed successfully!\n";
echo "=================================================================\n";
