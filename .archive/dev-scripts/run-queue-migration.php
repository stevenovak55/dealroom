<?php
/**
 * Run Notification Queue Migration
 */

require_once __DIR__ . '/wp-load.php';

echo "========================================\n";
echo "Running Notification Queue Migration\n";
echo "========================================\n\n";

// Run migration
$plugin = MADealRoom\Core\Plugin::instance();
$migrator = new MADealRoom\Database\Migrator();
$results = $migrator->run();

echo "Migration Results:\n";
foreach ($results as $migration => $result) {
    if (isset($result['error'])) {
        echo "✗ {$migration}: " . $result['error'] . "\n";
    } elseif (isset($result['skipped'])) {
        echo "- {$migration}: Skipped (already run)\n";
    } else {
        echo "✓ {$migration}: Success\n";
    }
}

// Verify table was created
global $wpdb;
$table = $wpdb->prefix . 'ma_deal_notification_queue';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table;

echo "\n========================================\n";
echo "Verification\n";
echo "========================================\n";
echo "Table '{$table}': " . ($table_exists ? "EXISTS ✓" : "NOT FOUND ✗") . "\n";

if ($table_exists) {
    $columns = $wpdb->get_results("DESCRIBE {$table}");
    echo "\nTable Structure:\n";
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
}

echo "\n========================================\n";
echo "Queue Service Test\n";
echo "========================================\n";

// Test queue service
try {
    $queue_service = new MADealRoom\Services\NotificationQueueService();
    $stats = $queue_service->getStats();

    echo "Queue Statistics:\n";
    echo "  Pending: {$stats['pending']}\n";
    echo "  Processing: {$stats['processing']}\n";
    echo "  Sent: {$stats['sent']}\n";
    echo "  Failed: {$stats['failed']}\n";
    echo "  Ready to Process: {$stats['ready_to_process']}\n";

    echo "\n✓ Queue service is working!\n";
} catch (\Exception $e) {
    echo "✗ Queue service error: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "Done!\n";
echo "========================================\n";
