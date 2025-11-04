#!/usr/bin/env php
<?php
/**
 * Apply Migration 011 - User System
 */

require_once '/var/www/html/wp-load.php';

echo "Applying Migration 011: User System\n";
echo "====================================\n\n";

// Check if migration already applied
global $wpdb;
$migrations_table = $wpdb->prefix . 'ma_deal_migrations';

$existing = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT migration FROM {$migrations_table} WHERE migration = %s",
        '011_create_user_system'
    )
);

if ($existing) {
    echo "Migration 011 already applied!\n";
    exit(0);
}

// Run the migrator
echo "Running migrator...\n";

try {
    $migrator_class = 'MADealRoom\Database\Migrator';

    if (class_exists($migrator_class)) {
        $migrator = new $migrator_class();

        // Use reflection to call the run method
        $reflection = new ReflectionClass($migrator);
        $method = $reflection->getMethod('run');
        $method->setAccessible(true);
        $method->invoke($migrator);

        echo "✓ Migration completed successfully\n";
    } else {
        echo "✗ Migrator class not found\n";
        echo "  Trying alternative approach...\n";

        // Alternative: directly load and execute migration file
        $migration_file = '/var/www/html/wp-content/plugins/ma-deal-room/database/migrations/011_create_user_system.sql';

        if (!file_exists($migration_file)) {
            echo "✗ Migration file not found: {$migration_file}\n";
            exit(1);
        }

        $sql = file_get_contents($migration_file);

        // Split into statements
        $statements = explode(';', $sql);

        $executed = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);

            // Skip empty statements and comments
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            $result = $wpdb->query($statement);

            if ($result === false) {
                echo "✗ Error executing statement:\n";
                echo "  " . $wpdb->last_error . "\n";
            } else {
                $executed++;
            }
        }

        echo "✓ Executed {$executed} SQL statements\n";

        // Record migration
        $wpdb->insert(
            $migrations_table,
            ['migration' => '011_create_user_system'],
            ['%s']
        );

        echo "✓ Migration recorded\n";
    }

    // Verify tables created
    echo "\nVerifying tables...\n";
    $tables = [
        'wp_ma_deal_custom_users',
        'wp_ma_deal_user_roles',
        'wp_ma_deal_user_sessions',
        'wp_ma_deal_password_resets',
        'wp_ma_deal_email_verifications',
        'wp_ma_deal_2fa_secrets',
        'wp_ma_deal_user_invitations',
    ];

    foreach ($tables as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        echo "  " . ($exists ? "✓" : "✗") . " {$table}\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ Migration 011 completed successfully\n";
