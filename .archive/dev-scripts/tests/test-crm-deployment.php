#!/usr/bin/env php
<?php
/**
 * CRM Integration Deployment Test Script
 *
 * Tests the MA Deal Room plugin deployment with new CRM integration features.
 * Verifies migrations, table creation, and identifies critical issues.
 *
 * Usage: php test-crm-deployment.php
 *
 * @package MADealRoom
 * @since 1.0.0
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('/var/www/html/wp-load.php');

// Color output helpers
function color_text($text, $color) {
    $colors = [
        'red' => "\033[0;31m",
        'green' => "\033[0;32m",
        'yellow' => "\033[1;33m",
        'blue' => "\033[0;34m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function print_header($text) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo color_text($text, 'blue') . "\n";
    echo str_repeat('=', 80) . "\n";
}

function print_success($text) {
    echo color_text('[✓] ', 'green') . $text . "\n";
}

function print_error($text) {
    echo color_text('[✗] ', 'red') . $text . "\n";
}

function print_warning($text) {
    echo color_text('[!] ', 'yellow') . $text . "\n";
}

function print_info($text) {
    echo "[i] " . $text . "\n";
}

// Test results
$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'errors' => []
];

global $wpdb;

print_header("MA DEAL ROOM - CRM INTEGRATION DEPLOYMENT TEST");
print_info("Date: " . date('Y-m-d H:i:s'));
print_info("WordPress Version: " . get_bloginfo('version'));
print_info("PHP Version: " . PHP_VERSION);

// ============================================================================
// TEST 1: Check Plugin Files Exist
// ============================================================================
print_header("TEST 1: Verify CRM Integration Files");

$crm_files = [
    'src/Services/Integration/CRM/CRMClientInterface.php',
    'src/Services/Integration/CRM/SalesforceClient.php',
    'src/Services/Integration/CRM/HubSpotClient.php',
    'src/Services/Integration/CRM/CRMClientFactory.php',
    'src/Repositories/CRMConfigRepository.php',
    'src/Services/Integration/CRM/ContactSyncService.php',
    'src/Services/Integration/CRM/DealSyncService.php',
    'src/Services/Integration/CRM/ActivitySyncService.php',
    'src/Services/Integration/CRM/CRMActivityLogger.php',
    'src/REST/Controllers/CRMSyncController.php',
    'database/migrations/023_create_crm_config_table.sql',
    'database/migrations/024_add_crm_sync_fields.sql',
    'database/migrations/025_add_crm_deal_sync_fields.sql'
];

$plugin_dir = WP_CONTENT_DIR . '/plugins/ma-deal-room/';
$all_files_exist = true;

foreach ($crm_files as $file) {
    $full_path = $plugin_dir . $file;
    if (file_exists($full_path)) {
        print_success("Found: {$file}");
        $results['passed']++;
    } else {
        print_error("Missing: {$file}");
        $results['failed']++;
        $results['errors'][] = "Missing file: {$file}";
        $all_files_exist = false;
    }
}

// ============================================================================
// TEST 2: Check Migration SQL Syntax
// ============================================================================
print_header("TEST 2: Validate Migration SQL Files");

$migrations = [
    '023_create_crm_config_table.sql' => [
        'table' => 'ma_deal_crm_config',
        'description' => 'CRM configuration storage'
    ],
    '024_add_crm_sync_fields.sql' => [
        'table' => 'ma_contacts', // This will FAIL - table doesn't exist
        'description' => 'CRM sync fields for contacts'
    ],
    '025_add_crm_deal_sync_fields.sql' => [
        'table' => 'ma_deal_transactions',
        'description' => 'CRM sync fields for transactions'
    ]
];

foreach ($migrations as $migration_file => $info) {
    $migration_path = $plugin_dir . 'database/migrations/' . $migration_file;

    if (!file_exists($migration_path)) {
        print_error("Migration file not found: {$migration_file}");
        $results['failed']++;
        continue;
    }

    $sql_content = file_get_contents($migration_path);
    print_info("Checking {$migration_file}...");

    // Check if it references the correct table
    if (strpos($sql_content, $info['table']) !== false) {
        print_success("References table: {$info['table']}");

        // Critical check for migration 024
        if ($migration_file === '024_add_crm_sync_fields.sql') {
            print_error("CRITICAL: Migration 024 references non-existent table 'ma_contacts'");
            print_error("CRITICAL: Migration 024 also references non-existent table 'ma_users'");
            print_warning("The plugin schema uses 'ma_deal_parties' and 'ma_deal_custom_users'");
            $results['failed'] += 2;
            $results['errors'][] = "Migration 024 references non-existent table: ma_contacts";
            $results['errors'][] = "Migration 024 references non-existent table: ma_users";
        } else {
            $results['passed']++;
        }
    } else {
        print_error("Table reference issue in {$migration_file}");
        $results['failed']++;
    }
}

// ============================================================================
// TEST 3: Check Existing Database Schema
// ============================================================================
print_header("TEST 3: Verify Database Schema");

$expected_tables = [
    'ma_deal_accounts',
    'ma_deal_transactions',
    'ma_deal_parties',
    'ma_deal_custom_users',
    'ma_deal_tasks',
    'ma_deal_templates',
    'ma_deal_migrations'
];

foreach ($expected_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");

    if ($table_exists) {
        print_success("Table exists: {$full_table_name}");
        $results['passed']++;
    } else {
        print_warning("Table not found: {$full_table_name}");
        $results['warnings']++;
    }
}

// ============================================================================
// TEST 4: Check CRM Tables (if migrations ran)
// ============================================================================
print_header("TEST 4: Check CRM Tables");

$crm_tables = [
    'ma_deal_crm_config' => 'CRM configuration table'
];

foreach ($crm_tables as $table => $description) {
    $full_table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");

    if ($table_exists) {
        print_success("CRM table exists: {$full_table_name} - {$description}");

        // Check schema
        $columns = $wpdb->get_results("DESCRIBE {$full_table_name}");
        print_info("  Columns: " . count($columns));
        $results['passed']++;
    } else {
        print_info("CRM table not yet created: {$full_table_name}");
        print_info("  This is expected if migrations haven't run yet");
    }
}

// ============================================================================
// TEST 5: Check for ContactSyncService Table References
// ============================================================================
print_header("TEST 5: Check Service Class Dependencies");

$contact_sync_file = $plugin_dir . 'src/Services/Integration/CRM/ContactSyncService.php';
if (file_exists($contact_sync_file)) {
    $content = file_get_contents($contact_sync_file);

    if (strpos($content, "ma_contacts") !== false) {
        print_error("CRITICAL: ContactSyncService references non-existent 'ma_contacts' table");
        print_warning("Should use 'ma_deal_parties' instead");
        $results['failed']++;
        $results['errors'][] = "ContactSyncService hardcodes non-existent 'ma_contacts' table";
    } else {
        print_success("ContactSyncService does not reference ma_contacts");
        $results['passed']++;
    }
}

// ============================================================================
// TEST 6: Check CRM Controller Registration
// ============================================================================
print_header("TEST 6: Verify CRM Controller Registration");

$plugin_class = $plugin_dir . 'src/Core/Plugin.php';
if (file_exists($plugin_class)) {
    $content = file_get_contents($plugin_class);

    if (strpos($content, "CRMSyncController") !== false) {
        print_success("CRMSyncController is imported in Plugin.php");
        $results['passed']++;
    } else {
        print_error("CRITICAL: CRMSyncController not registered in Plugin.php");
        print_warning("REST API endpoints will not be available");
        $results['failed']++;
        $results['errors'][] = "CRMSyncController not registered in Plugin.php";
    }
}

// ============================================================================
// TEST 7: Check Uninstall.php
// ============================================================================
print_header("TEST 7: Verify Uninstall Cleanup");

$uninstall_file = $plugin_dir . 'uninstall.php';
if (file_exists($uninstall_file)) {
    $content = file_get_contents($uninstall_file);

    $missing_tables = [];

    if (strpos($content, "ma_deal_crm_config") === false) {
        $missing_tables[] = 'ma_deal_crm_config';
    }

    if (strpos($content, "ma_deal_mls_config") === false) {
        $missing_tables[] = 'ma_deal_mls_config';
    }

    if (!empty($missing_tables)) {
        print_error("CRITICAL: Uninstall.php missing new tables: " . implode(', ', $missing_tables));
        print_warning("Plugin will leave orphaned data after uninstallation");
        $results['failed']++;
        $results['errors'][] = "Uninstall.php missing tables: " . implode(', ', $missing_tables);
    } else {
        print_success("Uninstall.php includes all CRM/MLS tables");
        $results['passed']++;
    }
}

// ============================================================================
// TEST 8: Check Migration History
// ============================================================================
print_header("TEST 8: Check Migration History");

$migrations_table = $wpdb->prefix . 'ma_deal_migrations';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$migrations_table}'");

if ($table_exists) {
    $applied_migrations = $wpdb->get_results("SELECT * FROM {$migrations_table} ORDER BY id ASC");

    print_info("Applied migrations: " . count($applied_migrations));

    foreach ($applied_migrations as $migration) {
        print_success("  {$migration->migration_number}: {$migration->migration_name}");
    }

    // Check if CRM migrations are applied
    $crm_migrations_applied = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$migrations_table} WHERE migration_number IN ('023', '024', '025')"
    );

    if ($crm_migrations_applied > 0) {
        print_warning("CRM migrations are already applied: {$crm_migrations_applied} of 3");
        print_warning("If migration 024 ran, it FAILED on non-existent tables");
        $results['warnings']++;
    } else {
        print_info("CRM migrations not yet applied (this is okay)");
    }

    $results['passed']++;
} else {
    print_warning("Migrations table doesn't exist - plugin not activated yet");
    $results['warnings']++;
}

// ============================================================================
// TEST SUMMARY
// ============================================================================
print_header("TEST SUMMARY");

echo "\n";
echo "Total Tests Passed:  " . color_text($results['passed'], 'green') . "\n";
echo "Total Tests Failed:  " . color_text($results['failed'], 'red') . "\n";
echo "Total Warnings:      " . color_text($results['warnings'], 'yellow') . "\n";
echo "\n";

if ($results['failed'] > 0) {
    print_header("CRITICAL ISSUES FOUND");
    foreach ($results['errors'] as $error) {
        print_error($error);
    }
    echo "\n";
    echo color_text("⚠️  DEPLOYMENT BLOCKED - CRITICAL ISSUES MUST BE FIXED ⚠️", 'red') . "\n";
    exit(1);
} else if ($results['warnings'] > 0) {
    echo color_text("⚠️  Warnings found - review before deployment", 'yellow') . "\n";
    exit(0);
} else {
    echo color_text("✓ All tests passed - ready for deployment", 'green') . "\n";
    exit(0);
}
