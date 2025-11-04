#!/usr/bin/env php
<?php
/**
 * Check Database Tables and Run Missing Migrations
 */

// WordPress environment
define('ABSPATH', __DIR__ . '/');
define('WP_CONTENT_DIR', __DIR__ . '/wp-content');

echo "\n=== MA Deal Room - Database Migration Check ===\n\n";

// Database configuration from .env or defaults
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'ma_dealroom';
$db_user = getenv('DB_USER') ?: 'dealroom';
$db_pass = getenv('DB_PASSWORD') ?: 'dealroom_dev_pass';
$db_port = getenv('MYSQL_PORT') ?: '3306';

// Connect to database
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ Connected to database: {$db_name}\n\n";
} catch (PDOException $e) {
    die("✗ Database connection failed: " . $e->getMessage() . "\n");
}

// Check which tables exist
$required_tables = [
    'wp_ma_deal_notifications' => '003_create_notifications_table.sql',
    'wp_ma_deal_task_definitions' => '006_create_modular_task_system.sql',
];

echo "Checking required tables:\n";
foreach ($required_tables as $table => $migration_file) {
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    $exists = $stmt->fetch();

    if ($exists) {
        echo "  ✓ {$table} exists\n";
    } else {
        echo "  ✗ {$table} MISSING - needs migration {$migration_file}\n";
    }
}

// Check if we need to run migrations
$stmt = $pdo->prepare("SHOW TABLES LIKE 'wp_ma_deal_notifications'");
$stmt->execute();
$notifications_exists = $stmt->fetch();

$stmt = $pdo->prepare("SHOW TABLES LIKE 'wp_ma_deal_task_definitions'");
$stmt->execute();
$task_definitions_exists = $stmt->fetch();

if (!$notifications_exists || !$task_definitions_exists) {
    echo "\n⚠️  Missing tables detected. Running migrations...\n\n";

    $migration_dir = __DIR__ . '/ma-deal-room/database/migrations/';

    // Run migration 003 if needed
    if (!$notifications_exists) {
        echo "Running migration 003 (notifications table)...\n";
        $sql = file_get_contents($migration_dir . '003_create_notifications_table.sql');
        try {
            $pdo->exec($sql);
            echo "  ✓ Migration 003 completed successfully\n";
        } catch (PDOException $e) {
            echo "  ✗ Migration 003 failed: " . $e->getMessage() . "\n";
        }
    }

    // Run migration 006 if needed
    if (!$task_definitions_exists) {
        echo "Running migration 006 (modular task system)...\n";
        $sql = file_get_contents($migration_dir . '006_create_modular_task_system.sql');
        try {
            $pdo->exec($sql);
            echo "  ✓ Migration 006 completed successfully\n";
        } catch (PDOException $e) {
            echo "  ✗ Migration 006 failed: " . $e->getMessage() . "\n";
        }
    }

    echo "\n✓ Migrations completed. Re-checking tables...\n\n";

    // Re-check
    foreach ($required_tables as $table => $migration_file) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->fetch();

        if ($exists) {
            echo "  ✓ {$table} now exists\n";
        } else {
            echo "  ✗ {$table} still missing\n";
        }
    }
} else {
    echo "\n✓ All required tables exist. No migrations needed.\n";
}

// Check task definitions count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM wp_ma_deal_task_definitions");
$result = $stmt->fetch();
$task_count = $result['count'] ?? 0;

echo "\n=== Task Library Status ===\n";
echo "Task definitions in database: {$task_count}\n";

if ($task_count == 0) {
    echo "⚠️  No task definitions found. You may need to import them.\n";
    echo "   Run: wp ma-deal task-definitions import\n";
}

echo "\n=== Check Complete ===\n\n";
