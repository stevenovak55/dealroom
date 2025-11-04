#!/usr/bin/env php
<?php
/**
 * Comprehensive MA Deal Room Plugin Activation Test
 *
 * Tests:
 * 1. Plugin activation/reactivation
 * 2. Database migration execution
 * 3. Table creation and structure
 * 4. Migration tracking
 * 5. Error detection
 *
 * Usage: php test-plugin-activation-comprehensive.php
 */

// Load WordPress
$wordpress_path = '/var/www/html';
if (!file_exists($wordpress_path . '/wp-load.php')) {
    $wordpress_path = dirname(dirname(__DIR__));
}

require_once $wordpress_path . '/wp-load.php';

// ANSI color codes for terminal output
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'magenta' => "\033[35m",
    'cyan' => "\033[36m",
    'white' => "\033[37m",
    'bold' => "\033[1m",
];

function print_header($text, $color = 'cyan') {
    global $colors;
    echo "\n" . $colors['bold'] . $colors[$color] . str_repeat('=', 80) . $colors['reset'] . "\n";
    echo $colors['bold'] . $colors[$color] . $text . $colors['reset'] . "\n";
    echo $colors['bold'] . $colors[$color] . str_repeat('=', 80) . $colors['reset'] . "\n\n";
}

function print_success($text) {
    global $colors;
    echo $colors['green'] . '✓ ' . $text . $colors['reset'] . "\n";
}

function print_error($text) {
    global $colors;
    echo $colors['red'] . '✗ ' . $text . $colors['reset'] . "\n";
}

function print_warning($text) {
    global $colors;
    echo $colors['yellow'] . '⚠ ' . $text . $colors['reset'] . "\n";
}

function print_info($text) {
    global $colors;
    echo $colors['blue'] . 'ℹ ' . $text . $colors['reset'] . "\n";
}

// Test results tracking
$test_results = [
    'total_tests' => 0,
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
];

print_header('MA DEAL ROOM - COMPREHENSIVE PLUGIN ACTIVATION TEST', 'magenta');

// ============================================================================
// STEP 1: Check Current Plugin Status
// ============================================================================
print_header('STEP 1: Current Plugin Status', 'cyan');

$plugin_file = 'ma-deal-room/ma-deal-room.php';
$is_active = is_plugin_active($plugin_file);

print_info('Plugin: ' . $plugin_file);
print_info('Currently Active: ' . ($is_active ? 'YES' : 'NO'));
print_info('Plugin Version: ' . (defined('MA_DEAL_VERSION') ? MA_DEAL_VERSION : 'Unknown'));

// Get current table count before any operations
global $wpdb;
$current_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_%'");
print_info('Current table count: ' . count($current_tables));

// ============================================================================
// STEP 2: Count Migration Files
// ============================================================================
print_header('STEP 2: Migration Files Inventory', 'cyan');

$migrations_dir = WP_PLUGIN_DIR . '/ma-deal-room/database/migrations/';
$migration_files = glob($migrations_dir . '[0-9][0-9][0-9]_*.sql');
sort($migration_files);

print_info('Migrations directory: ' . $migrations_dir);
print_info('Total migration files found: ' . count($migration_files));

$migration_numbers = [];
foreach ($migration_files as $file) {
    $basename = basename($file);
    preg_match('/^(\d{3})_/', $basename, $matches);
    if (isset($matches[1])) {
        $migration_numbers[] = $matches[1];
    }
}

print_info('Migration sequence: ' . implode(', ', $migration_numbers));

// Check for gaps in sequence
$expected_migrations = range(1, max(array_map('intval', $migration_numbers)));
$missing_migrations = [];
foreach ($expected_migrations as $num) {
    $num_str = sprintf('%03d', $num);
    if (!in_array($num_str, $migration_numbers)) {
        $missing_migrations[] = $num_str;
    }
}

if (!empty($missing_migrations)) {
    print_warning('Missing migration numbers (gaps in sequence): ' . implode(', ', $missing_migrations));
}

// ============================================================================
// STEP 3: Deactivate Plugin (if active)
// ============================================================================
print_header('STEP 3: Deactivate Plugin', 'cyan');

if ($is_active) {
    print_info('Deactivating plugin to test fresh activation...');
    deactivate_plugins($plugin_file);

    if (!is_plugin_active($plugin_file)) {
        print_success('Plugin deactivated successfully');
    } else {
        print_error('Failed to deactivate plugin');
        exit(1);
    }
} else {
    print_info('Plugin already deactivated - skipping');
}

// ============================================================================
// STEP 4: Activate Plugin
// ============================================================================
print_header('STEP 4: Activate Plugin', 'cyan');

print_info('Activating plugin...');

// Capture activation output
ob_start();
$result = activate_plugin($plugin_file, '', false, true);
$activation_output = ob_get_clean();

if (is_wp_error($result)) {
    print_error('Plugin activation failed!');
    print_error('Error: ' . $result->get_error_message());
    exit(1);
}

if (!is_plugin_active($plugin_file)) {
    print_error('Plugin activation completed but plugin is not active');
    print_error('Activation output: ' . $activation_output);
    exit(1);
}

print_success('Plugin activated successfully');

if (!empty($activation_output)) {
    print_warning('Activation produced output (should be silent):');
    echo $activation_output . "\n";
}

// ============================================================================
// STEP 5: Check Migration Execution
// ============================================================================
print_header('STEP 5: Verify Migrations', 'cyan');

$migrations_table = $wpdb->prefix . 'ma_deal_migrations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$migrations_table}'") === $migrations_table;

if (!$table_exists) {
    print_error('Migration tracking table does not exist!');
    $test_results['failed']++;
} else {
    print_success('Migration tracking table exists');

    // Get applied migrations
    $applied = $wpdb->get_results("SELECT * FROM {$migrations_table} ORDER BY migration_number ASC");
    print_info('Applied migrations: ' . count($applied));

    echo "\n";
    printf("%-15s %-50s %-20s\n", 'Migration', 'Name', 'Applied At');
    echo str_repeat('-', 85) . "\n";

    foreach ($applied as $migration) {
        printf("%-15s %-50s %-20s\n",
            $migration->migration_number,
            substr($migration->migration_name, 0, 50),
            $migration->applied_at
        );
    }

    // Check if all migration files were applied
    $applied_numbers = array_column($applied, 'migration_number');
    $unapplied = array_diff($migration_numbers, $applied_numbers);

    if (!empty($unapplied)) {
        print_warning('Some migrations were not applied: ' . implode(', ', $unapplied));
        $test_results['warnings']++;
    } else {
        print_success('All migration files have been applied');
        $test_results['passed']++;
    }
}

// ============================================================================
// STEP 6: List All Tables
// ============================================================================
print_header('STEP 6: Database Tables', 'cyan');

$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_%'");
sort($tables);

print_info('Total tables created: ' . count($tables));
echo "\n";

$expected_tables = [
    'ma_deal_migrations',
    'ma_deal_accounts',
    'ma_deal_templates',
    'ma_deal_transactions',
    'ma_deal_tasks',
    'ma_deal_parties',
    'ma_deal_documents',
    'ma_deal_notifications',
    'ma_deal_task_definitions',
    'ma_deal_template_tasks',
    'ma_deal_task_categories',
    'ma_deal_transaction_types',
    'ma_deal_property_attributes',
    'ma_deal_security_deposits',
    'ma_deal_reminders',
    'ma_deal_events',
    'ma_deal_vendor_requests',
    'ma_deal_transaction_custom_tasks',
    'ma_deal_notification_queue',
    'ma_deal_rate_limits',
    'ma_deal_users',
    'ma_deal_user_sessions',
    'ma_deal_password_reset_tokens',
    'ma_deal_mls_config',
    'ma_deal_crm_config',
    'ma_deal_contacts',
    'ma_deal_docusign_config',
    'ma_deal_docusign_envelopes',
    'ma_deal_docusign_webhook_log',
];

// Print table comparison
printf("%-40s %-10s\n", 'Table Name', 'Status');
echo str_repeat('-', 50) . "\n";

foreach ($expected_tables as $expected) {
    $full_name = $wpdb->prefix . $expected;
    $exists = in_array($full_name, $tables);

    if ($exists) {
        print_success($expected);
        $test_results['passed']++;
    } else {
        print_error($expected . ' (MISSING)');
        $test_results['failed']++;
    }
}

// Check for unexpected tables
$expected_full_names = array_map(function($name) use ($wpdb) {
    return $wpdb->prefix . $name;
}, $expected_tables);

$unexpected_tables = array_diff($tables, $expected_full_names);
if (!empty($unexpected_tables)) {
    print_warning('Unexpected tables found:');
    foreach ($unexpected_tables as $table) {
        echo "  - " . $table . "\n";
    }
}

// ============================================================================
// STEP 7: Verify Key Table Structures
// ============================================================================
print_header('STEP 7: Verify Table Structures', 'cyan');

$key_tables = [
    'ma_deal_transactions' => ['id', 'account_id', 'template_id', 'property_address', 'offer_accepted_date', 'ps_agreement_date', 'loan_commitment_date', 'closing_date', 'mls_number'],
    'ma_deal_tasks' => ['id', 'transaction_id', 'title', 'status', 'due_date'],
    'ma_deal_accounts' => ['id', 'name', 'owner_user_id', 'status', 'subscription_tier'],
    'ma_deal_task_definitions' => ['id', 'task_key', 'category', 'title', 'due_calculation'],
    'ma_deal_documents' => ['id', 'transaction_id', 'file_name', 'file_path', 'encrypted'],
    'ma_deal_mls_config' => ['id', 'provider', 'api_key'],
    'ma_deal_crm_config' => ['id', 'provider', 'api_key'],
    'ma_deal_contacts' => ['id', 'account_id', 'first_name', 'last_name', 'email'],
    'ma_deal_docusign_config' => ['id', 'account_id', 'integration_key'],
    'ma_deal_docusign_envelopes' => ['id', 'transaction_id', 'envelope_id', 'status'],
];

foreach ($key_tables as $table_name => $required_columns) {
    $full_table = $wpdb->prefix . $table_name;

    if (!in_array($full_table, $tables)) {
        print_error("Table {$table_name} does not exist");
        $test_results['failed']++;
        continue;
    }

    // Get table columns
    $columns = $wpdb->get_results("DESCRIBE {$full_table}");
    $column_names = array_column($columns, 'Field');

    $missing_columns = array_diff($required_columns, $column_names);

    if (empty($missing_columns)) {
        print_success("{$table_name} - All required columns present");
        $test_results['passed']++;
    } else {
        print_error("{$table_name} - Missing columns: " . implode(', ', $missing_columns));
        $test_results['failed']++;
    }
}

// ============================================================================
// STEP 8: Check for Specific Migration Features
// ============================================================================
print_header('STEP 8: Verify Migration-Specific Features', 'cyan');

// Check migration 022 - mls_number column
$transactions_table = $wpdb->prefix . 'ma_deal_transactions';
$columns = $wpdb->get_results("DESCRIBE {$transactions_table}");
$column_names = array_column($columns, 'Field');

if (in_array('mls_number', $column_names)) {
    print_success('Migration 022: mls_number column exists in transactions table');
    $test_results['passed']++;
} else {
    print_error('Migration 022: mls_number column MISSING from transactions table');
    $test_results['failed']++;
}

// Check migration 027-029 - DocuSign tables
$docusign_tables = [
    'ma_deal_docusign_config',
    'ma_deal_docusign_envelopes',
    'ma_deal_docusign_webhook_log',
];

foreach ($docusign_tables as $table_name) {
    $full_table = $wpdb->prefix . $table_name;
    if (in_array($full_table, $tables)) {
        print_success("Migration 027-029: {$table_name} exists");
        $test_results['passed']++;
    } else {
        print_error("Migration 027-029: {$table_name} MISSING");
        $test_results['failed']++;
    }
}

// ============================================================================
// STEP 9: Check for SQL Errors
// ============================================================================
print_header('STEP 9: Check for Errors', 'cyan');

// Check if wpdb has any errors
if (!empty($wpdb->last_error)) {
    print_error('Database error detected: ' . $wpdb->last_error);
    $test_results['failed']++;
} else {
    print_success('No database errors detected');
    $test_results['passed']++;
}

// Check WordPress debug log for recent errors (if accessible)
$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log) && is_readable($debug_log)) {
    $log_size = filesize($debug_log);
    if ($log_size > 0) {
        // Read last 50 lines
        $lines = [];
        $fp = fopen($debug_log, 'r');
        fseek($fp, -min($log_size, 10000), SEEK_END);
        while (!feof($fp)) {
            $lines[] = fgets($fp);
        }
        fclose($fp);

        $recent_errors = array_filter($lines, function($line) {
            return stripos($line, 'ma deal') !== false ||
                   stripos($line, 'ma_deal') !== false;
        });

        if (!empty($recent_errors)) {
            print_warning('Recent debug log entries found:');
            foreach (array_slice($recent_errors, -5) as $error) {
                echo "  " . trim($error) . "\n";
            }
        } else {
            print_success('No MA Deal Room errors in recent debug log');
        }
    } else {
        print_success('Debug log is empty (no errors)');
    }
} else {
    print_info('Debug log not accessible or does not exist');
}

// ============================================================================
// STEP 10: Verify Plugin Options
// ============================================================================
print_header('STEP 10: Plugin Options & Settings', 'cyan');

$options = [
    'ma_deal_room_activated' => 'Plugin activation flag',
    'ma_deal_room_version' => 'Plugin version',
    'ma_deal_room_templates_synced' => 'Templates synced flag',
    'ma_deal_room_templates_sync_count' => 'Templates count',
    'ma_deal_room_task_definitions_synced' => 'Task definitions synced',
    'ma_deal_room_task_definitions_count' => 'Task definitions count',
];

foreach ($options as $option_name => $description) {
    $value = get_option($option_name, null);
    if ($value !== null && $value !== false) {
        print_success("{$description}: " . (is_array($value) ? json_encode($value) : $value));
    } else {
        print_warning("{$description}: Not set");
    }
}

// ============================================================================
// FINAL SUMMARY
// ============================================================================
print_header('TEST SUMMARY', 'magenta');

$total_tests = $test_results['passed'] + $test_results['failed'];
$test_results['total_tests'] = $total_tests;
$pass_rate = $total_tests > 0 ? ($test_results['passed'] / $total_tests * 100) : 0;

echo $colors['bold'] . "Total Tests:    " . $colors['reset'] . $total_tests . "\n";
echo $colors['green'] . $colors['bold'] . "Passed:         " . $colors['reset'] . $test_results['passed'] . "\n";
echo $colors['red'] . $colors['bold'] . "Failed:         " . $colors['reset'] . $test_results['failed'] . "\n";
echo $colors['yellow'] . $colors['bold'] . "Warnings:       " . $colors['reset'] . $test_results['warnings'] . "\n";
echo $colors['blue'] . $colors['bold'] . "Pass Rate:      " . $colors['reset'] . number_format($pass_rate, 1) . "%\n\n";

echo $colors['bold'] . "Migration Files:    " . $colors['reset'] . count($migration_files) . "\n";
echo $colors['bold'] . "Applied Migrations: " . $colors['reset'] . (isset($applied) ? count($applied) : 0) . "\n";
echo $colors['bold'] . "Database Tables:    " . $colors['reset'] . count($tables) . "\n";
echo $colors['bold'] . "Expected Tables:    " . $colors['reset'] . count($expected_tables) . "\n\n";

if ($test_results['failed'] == 0) {
    print_success('ALL TESTS PASSED - Plugin is ready for use!');
    exit(0);
} else {
    print_error('SOME TESTS FAILED - Please review errors above');
    exit(1);
}
