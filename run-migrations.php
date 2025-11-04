<?php
/**
 * Run pending migrations
 */

require_once('/var/www/html/wp-load.php');

echo "Running MA Deal Room Migrations...\n\n";

try {
    $migrator = new \MADealRoom\Database\Migrator();
    $results = $migrator->run();

    echo "Migration Results:\n";
    echo str_repeat("=", 80) . "\n";

    foreach ($results as $number => $result) {
        $status = $result['status'] === 'success' ? '✓ SUCCESS' :
                 ($result['status'] === 'skipped' ? '⊘ SKIPPED' : '✗ ERROR');
        echo sprintf("Migration %s: %s - %s\n", $number, $status, $result['message']);
    }

    echo str_repeat("=", 80) . "\n";
    echo "Migration run complete!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
