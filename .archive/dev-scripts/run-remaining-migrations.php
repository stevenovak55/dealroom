#!/usr/bin/env php
<?php
/**
 * Run Remaining MA Deal Room Migrations
 *
 * This script manually triggers the migration system to apply
 * any pending migrations that weren't applied during activation.
 */

// Load WordPress
require_once '/var/www/html/wp-load.php';

echo "========================================\n";
echo "MA Deal Room - Run Remaining Migrations\n";
echo "========================================\n\n";

if (!class_exists('MADealRoom\Database\Migrator')) {
    echo "ERROR: Migrator class not found. Is the plugin activated?\n";
    exit(1);
}

echo "Creating Migrator instance...\n";
$migrator = new MADealRoom\Database\Migrator();

echo "Running migrations...\n\n";

try {
    $results = $migrator->run();

    echo "Migration Results:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-15s %-15s %-50s\n", 'Migration', 'Status', 'Message');
    echo str_repeat('-', 80) . "\n";

    $stats = [
        'success' => 0,
        'skipped' => 0,
        'error' => 0,
    ];

    foreach ($results as $migration_number => $result) {
        $status = $result['status'];
        $stats[$status]++;

        $color = '';
        if ($status === 'success') {
            $color = "\033[32m"; // Green
        } elseif ($status === 'error') {
            $color = "\033[31m"; // Red
        } else {
            $color = "\033[33m"; // Yellow
        }

        printf(
            "%-15s %s%-15s\033[0m %-50s\n",
            $migration_number,
            $color,
            $status,
            substr($result['message'], 0, 50)
        );
    }

    echo str_repeat('-', 80) . "\n";
    echo "\nSummary:\n";
    echo "  ✓ Applied:  {$stats['success']}\n";
    echo "  ⊘ Skipped:  {$stats['skipped']}\n";
    echo "  ✗ Errors:   {$stats['error']}\n";

    if ($stats['error'] > 0) {
        echo "\nERROR: Some migrations failed. Check the output above.\n";
        exit(1);
    } else {
        echo "\nSUCCESS: All migrations completed!\n";
        exit(0);
    }

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
