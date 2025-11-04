#!/usr/bin/env php
<?php
require_once '/var/www/html/wp-load.php';
global $wpdb;

echo "Testing Migration 017...\n\n";

// First, check existing columns
echo "Current columns in user_sessions table:\n";
$columns = $wpdb->get_results("DESCRIBE {$wpdb->prefix}ma_deal_user_sessions");
foreach ($columns as $col) {
    echo "  - {$col->Field} ({$col->Type})\n";
}

echo "\nAttempting migration 017...\n";

$sql_file = '/var/www/html/wp-content/plugins/ma-deal-room/database/migrations/017_enhance_user_sessions_table.sql';
$sql = file_get_contents($sql_file);
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
    function($stmt) { return !empty($stmt); }
);

echo "Found " . count($statements) . " statements\n\n";

foreach ($statements as $i => $stmt) {
    echo "Statement " . ($i + 1) . ":\n";
    echo substr($stmt, 0, 100) . "...\n";

    $result = $wpdb->query($stmt);
    if ($result === false) {
        echo "  ERROR: " . $wpdb->last_error . "\n\n";
        echo "Full statement:\n" . $stmt . "\n\n";
        break;
    } else {
        echo "  SUCCESS\n\n";
    }
}
