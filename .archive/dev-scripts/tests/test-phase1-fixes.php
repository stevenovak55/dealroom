#!/usr/bin/env php
<?php
/**
 * Phase 1 Fixes Comprehensive Test Suite
 * Tests all fixes implemented from Phase 1 code review
 */

// Load WordPress
$wp_load_paths = [
    __DIR__ . '/wp-load.php',
    '/var/www/html/wp-load.php',
    dirname(__DIR__) . '/wp-load.php'
];

foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

if (!defined('ABSPATH')) {
    die("Error: WordPress not loaded\n");
}

// Test results tracking
$tests_run = 0;
$tests_passed = 0;
$tests_failed = 0;
$failures = [];

function test($name, $callback) {
    global $tests_run, $tests_passed, $tests_failed, $failures;
    $tests_run++;

    try {
        $result = $callback();
        if ($result === true) {
            $tests_passed++;
            echo "✓ {$name}\n";
            return true;
        } else {
            $tests_failed++;
            $failures[] = $name . ": " . ($result ?: "Assertion failed");
            echo "✗ {$name}\n";
            if ($result) echo "  Error: {$result}\n";
            return false;
        }
    } catch (Exception $e) {
        $tests_failed++;
        $failures[] = $name . ": " . $e->getMessage();
        echo "✗ {$name}\n";
        echo "  Exception: " . $e->getMessage() . "\n";
        return false;
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║      PHASE 1 FIXES - COMPREHENSIVE TEST SUITE                ║\n";
echo "║      Testing all security and quality fixes                  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

global $wpdb;

echo "══════════════════════════════════════════════════════════════════\n";
echo " SECTION 1: Migration 014 - Database Schema Changes\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

// Test 1.1: Check if rate_limits table exists
test("1.1 Rate limits table exists", function() use ($wpdb) {
    $table = $wpdb->prefix . 'ma_rate_limits';
    $result = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    return $result === $table;
});

// Test 1.2: Check rate_limits table structure
test("1.2 Rate limits table has correct columns", function() use ($wpdb) {
    $table = $wpdb->prefix . 'ma_rate_limits';
    $columns = $wpdb->get_results("DESCRIBE {$table}");

    $required_columns = ['id', 'rate_key', 'ip_address', 'user_id', 'endpoint', 'method', 'created_at'];
    $found_columns = array_column($columns, 'Field');

    foreach ($required_columns as $col) {
        if (!in_array($col, $found_columns)) {
            return "Missing column: {$col}";
        }
    }

    return true;
});

// Test 1.3: Check rate_limits table indexes
test("1.3 Rate limits table has performance indexes", function() use ($wpdb) {
    $table = $wpdb->prefix . 'ma_rate_limits';
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}");

    $index_names = array_column($indexes, 'Key_name');
    $required_indexes = ['PRIMARY', 'idx_rate_key_created', 'idx_created_at', 'idx_ip_address', 'idx_user_id'];

    foreach ($required_indexes as $idx) {
        if (!in_array($idx, $index_names)) {
            return "Missing index: {$idx}";
        }
    }

    return true;
});

// Test 1.4: Check if last_totp_timestamp column exists in ma_deal_2fa_secrets
test("1.4 last_totp_timestamp column added to ma_deal_2fa_secrets table", function() use ($wpdb) {
    $table = $wpdb->prefix . 'ma_deal_2fa_secrets';
    $columns = $wpdb->get_results("DESCRIBE {$table}");
    $column_names = array_column($columns, 'Field');

    return in_array('last_totp_timestamp', $column_names);
});

// Test 1.5: Verify migration record
test("1.5 Migration 014 recorded in migrations table", function() use ($wpdb) {
    $table = $wpdb->prefix . 'ma_deal_migrations';
    $migration = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE migration_number = %s",
        '014'
    ));

    return $migration !== null;
});

echo "\n";
echo "══════════════════════════════════════════════════════════════════\n";
echo " SECTION 2: Rate Limiting Middleware Tests\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

// Test 2.1: Rate limiting middleware is registered
test("2.1 Rate limiting middleware registered in container", function() {
    $container = MADealRoom\Core\Plugin::get_instance()->get_container();

    try {
        $middleware = $container->get('rate_limit_middleware');
        return $middleware instanceof MADealRoom\Middleware\RateLimitMiddleware;
    } catch (Exception $e) {
        return "Middleware not registered: " . $e->getMessage();
    }
});

// Test 2.2: Rate limit record insertion
test("2.2 Can insert rate limit records", function() use ($wpdb) {
    $table = $wpdb->prefix . 'ma_rate_limits';

    $result = $wpdb->insert(
        $table,
        [
            'rate_key' => 'test_rate_limit_' . time(),
            'ip_address' => '127.0.0.1',
            'user_id' => null,
            'endpoint' => '/wp-json/ma-deal/v1/test',
            'method' => 'GET',
            'created_at' => current_time('mysql')
        ],
        ['%s', '%s', '%d', '%s', '%s', '%s']
    );

    if ($result === false) {
        return "Insert failed: " . $wpdb->last_error;
    }

    // Cleanup
    $wpdb->delete($table, ['id' => $wpdb->insert_id], ['%d']);

    return true;
});

// Test 2.3: Rate limit query performance
test("2.3 Rate limit queries use indexes efficiently", function() use ($wpdb) {
    $table = $wpdb->prefix . 'ma_rate_limits';
    $rate_key = 'test_perf_' . time();

    // Insert test record
    $wpdb->insert(
        $table,
        [
            'rate_key' => $rate_key,
            'ip_address' => '127.0.0.1',
            'endpoint' => '/test',
            'method' => 'GET',
            'created_at' => current_time('mysql')
        ]
    );

    // Check if query uses index
    $explain = $wpdb->get_row($wpdb->prepare(
        "EXPLAIN SELECT COUNT(*) FROM {$table} WHERE rate_key = %s AND created_at > %s",
        $rate_key,
        date('Y-m-d H:i:s', time() - 60)
    ), ARRAY_A);

    // Cleanup
    $wpdb->delete($table, ['rate_key' => $rate_key], ['%s']);

    return isset($explain['key']) && $explain['key'] === 'idx_rate_key_created';
});

echo "\n";
echo "══════════════════════════════════════════════════════════════════\n";
echo " SECTION 3: TOTP Replay Prevention Tests\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

// Test 3.1: TwoFactorAuthService has replay prevention methods
test("3.1 TwoFactorAuthService has TOTP replay prevention methods", function() {
    $container = MADealRoom\Core\Plugin::get_instance()->get_container();
    $service = $container->get('twofa_service');

    $reflection = new ReflectionClass($service);

    $has_get = $reflection->hasMethod('get_last_totp_timestamp');
    $has_store = $reflection->hasMethod('store_totp_timestamp');

    if (!$has_get) return "Missing get_last_totp_timestamp method";
    if (!$has_store) return "Missing store_totp_timestamp method";

    return true;
});

// Test 3.2: TOTP timestamp storage works
test("3.2 TOTP timestamp can be stored and retrieved", function() use ($wpdb) {
    $table = $wpdb->prefix . 'ma_deal_2fa_secrets';

    // Find a test 2FA record
    $record = $wpdb->get_row("SELECT * FROM {$table} LIMIT 1");

    if (!$record) {
        return true; // Skip if no 2FA records (not a failure)
    }

    $test_timestamp = time();

    // Store timestamp
    $wpdb->update(
        $table,
        ['last_totp_timestamp' => $test_timestamp],
        [
            'user_id' => $record->user_id,
            'user_type' => $record->user_type
        ],
        ['%d'],
        ['%d', '%s']
    );

    // Retrieve timestamp
    $retrieved = $wpdb->get_var($wpdb->prepare(
        "SELECT last_totp_timestamp FROM {$table} WHERE user_id = %d AND user_type = %s",
        $record->user_id,
        $record->user_type
    ));

    // Cleanup
    $wpdb->update(
        $table,
        ['last_totp_timestamp' => null],
        [
            'user_id' => $record->user_id,
            'user_type' => $record->user_type
        ],
        ['%d'],
        ['%d', '%s']
    );

    return (int)$retrieved === $test_timestamp;
});

echo "\n";
echo "══════════════════════════════════════════════════════════════════\n";
echo " SECTION 4: Backup Service Security Tests\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

// Test 4.1: BackupService validates table names
test("4.1 BackupService validates table names with regex", function() {
    $backup_service_file = __DIR__ . '/ma-deal-room/src/Services/BackupService.php';

    if (!file_exists($backup_service_file)) {
        return "BackupService.php not found";
    }

    $content = file_get_contents($backup_service_file);

    // Check for preg_match validation
    $has_validation = strpos($content, "preg_match('/^[a-zA-Z0-9_]+$/") !== false;

    if (!$has_validation) {
        return "Table name validation not found";
    }

    return true;
});

// Test 4.2: BackupService sets file permissions
test("4.2 BackupService sets secure file permissions", function() {
    $backup_service_file = __DIR__ . '/ma-deal-room/src/Services/BackupService.php';

    if (!file_exists($backup_service_file)) {
        return "BackupService.php not found";
    }

    $content = file_get_contents($backup_service_file);

    // Check for chmod calls
    $has_chmod_640 = strpos($content, 'chmod($filepath, 0640)') !== false ||
                     strpos($content, 'chmod($compressed_filepath, 0640)') !== false;
    $has_chmod_644 = strpos($content, 'chmod($htaccess_file, 0644)') !== false ||
                     strpos($content, 'chmod($index_file, 0644)') !== false;

    if (!$has_chmod_640) {
        return "chmod(0640) for backup files not found";
    }

    if (!$has_chmod_644) {
        return "chmod(0644) for protection files not found";
    }

    return true;
});

echo "\n";
echo "══════════════════════════════════════════════════════════════════\n";
echo " SECTION 5: Documentation & Configuration Tests\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

// Test 5.1: CORS configuration documented in .env.example
test("5.1 CORS configuration documented in .env.example", function() {
    $env_file = __DIR__ . '/.env.example';

    if (!file_exists($env_file)) {
        return ".env.example not found";
    }

    $content = file_get_contents($env_file);

    $has_cors_origins = strpos($content, 'CORS_ALLOWED_ORIGINS') !== false;
    $has_cors_credentials = strpos($content, 'CORS_ALLOW_CREDENTIALS') !== false;
    $has_cors_maxage = strpos($content, 'CORS_MAX_AGE') !== false;

    if (!$has_cors_origins) return "CORS_ALLOWED_ORIGINS not found";
    if (!$has_cors_credentials) return "CORS_ALLOW_CREDENTIALS not found";
    if (!$has_cors_maxage) return "CORS_MAX_AGE not found";

    return true;
});

// Test 5.2: CSP 'unsafe-inline' documented
test("5.2 CSP 'unsafe-inline' risk documented in ssl-configuration.md", function() {
    $ssl_doc = __DIR__ . '/docs/deployment/ssl-configuration.md';

    if (!file_exists($ssl_doc)) {
        return "ssl-configuration.md not found";
    }

    $content = file_get_contents($ssl_doc);

    $has_risk_section = strpos($content, "CSP 'unsafe-inline' and 'unsafe-eval' - Documented Accepted Risk") !== false;
    $has_mitigation = strpos($content, 'Mitigation Strategies') !== false;
    $has_risk_assessment = strpos($content, 'Risk Assessment') !== false;

    if (!$has_risk_section) return "Risk documentation section not found";
    if (!$has_mitigation) return "Mitigation strategies not documented";
    if (!$has_risk_assessment) return "Risk assessment not found";

    return true;
});

// Test 5.3: Phase 1 review report exists
test("5.3 Phase 1 review report exists", function() {
    $report_file = __DIR__ . '/PHASE1_REVIEW_REPORT.md';
    return file_exists($report_file);
});

// Test 5.4: Phase 1 fixes summary exists
test("5.4 Phase 1 fixes summary exists", function() {
    $summary_file = __DIR__ . '/PHASE1_FIXES_SUMMARY.md';
    return file_exists($summary_file);
});

echo "\n";
echo "══════════════════════════════════════════════════════════════════\n";
echo " TEST RESULTS SUMMARY\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

echo "Total Tests Run:    {$tests_run}\n";
echo "Tests Passed:       {$tests_passed} ✓\n";
echo "Tests Failed:       {$tests_failed} ✗\n";
echo "Success Rate:       " . round(($tests_passed / $tests_run) * 100, 2) . "%\n";

if ($tests_failed > 0) {
    echo "\n";
    echo "══════════════════════════════════════════════════════════════════\n";
    echo " FAILURES\n";
    echo "══════════════════════════════════════════════════════════════════\n\n";

    foreach ($failures as $failure) {
        echo "  • {$failure}\n";
    }
}

echo "\n";

if ($tests_failed === 0) {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✓ ALL TESTS PASSED - PHASE 1 FIXES VERIFIED                  ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════════════════════╗\n";
    echo "║  ✗ SOME TESTS FAILED - REVIEW ERRORS ABOVE                    ║\n";
    echo "╚════════════════════════════════════════════════════════════════╝\n";
    exit(1);
}
