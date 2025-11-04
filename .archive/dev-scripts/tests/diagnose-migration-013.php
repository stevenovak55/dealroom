#!/usr/bin/env php
<?php
/**
 * Diagnose Migration 013 Failure
 */

require_once '/var/www/html/wp-load.php';

global $wpdb;

echo "Diagnosing Migration 013...\n\n";

// Read migration file
$migration_file = '/var/www/html/wp-content/plugins/ma-deal-room/database/migrations/013_add_performance_indexes.sql';
$sql = file_get_contents($migration_file);

if ($sql === false) {
    die("ERROR: Could not read migration file\n");
}

// Replace prefix
$sql = str_replace('{prefix}', $wpdb->prefix, $sql);

// Remove comments
$lines = explode("\n", $sql);
$cleaned_lines = [];
foreach ($lines as $line) {
    $trimmed = trim($line);
    if (!empty($trimmed) && strpos($trimmed, '--') !== 0) {
        $cleaned_lines[] = $line;
    }
}
$sql = implode("\n", $cleaned_lines);

// Split into statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($statement) {
        return !empty($statement);
    }
);

echo "Found " . count($statements) . " SQL statements\n\n";

// Try each statement
$success = 0;
$failed = 0;

foreach ($statements as $i => $statement) {
    $num = $i + 1;
    echo "Statement {$num}: ";

    // Show first 100 chars
    $preview = substr(str_replace("\n", " ", $statement), 0, 100);
    echo $preview . "...\n";

    // Try to execute
    $result = $wpdb->query($statement);

    if ($result === false) {
        echo "  ERROR: " . $wpdb->last_error . "\n";
        $failed++;

        // Show full statement on error
        echo "  Full SQL:\n";
        echo "  " . str_replace("\n", "\n  ", $statement) . "\n";
        break; // Stop on first error
    } else {
        echo "  SUCCESS\n";
        $success++;
    }
}

echo "\n";
echo "Results:\n";
echo "  Success: {$success}\n";
echo "  Failed:  {$failed}\n";

if ($failed > 0) {
    exit(1);
} else {
    echo "\nAll statements executed successfully!\n";
    exit(0);
}
