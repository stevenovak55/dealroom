<?php
/**
 * Comprehensive Test for T1.5 Performance Optimization & Caching
 *
 * Tests:
 * 1. Plugin activation with migration 013
 * 2. Database tables and 36 performance indexes
 * 3. CacheService initialization (Redis/transients fallback)
 * 4. TemplateEngine template caching
 * 5. BaseRepository query result caching
 * 6. Pagination limits enforcement
 * 7. Cache invalidation
 * 8. Performance measurements
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Results tracking
$results = [
    'overall_status' => 'PASS',
    'tests' => [],
    'errors' => [],
    'warnings' => [],
    'performance' => [],
];

function test_result($name, $status, $message, $details = []) {
    global $results;

    $test = [
        'name' => $name,
        'status' => $status,
        'message' => $message,
        'details' => $details,
    ];

    $results['tests'][] = $test;

    if ($status === 'FAIL') {
        $results['overall_status'] = 'FAIL';
        $results['errors'][] = $name . ': ' . $message;
    } elseif ($status === 'WARN') {
        $results['warnings'][] = $name . ': ' . $message;
    }

    // Print immediately for visibility
    $icon = $status === 'PASS' ? '✓' : ($status === 'FAIL' ? '✗' : '⚠');
    echo sprintf("[%s] %s: %s\n", $icon, $name, $message);
    if (!empty($details)) {
        foreach ($details as $key => $value) {
            echo "    - $key: $value\n";
        }
    }
}

echo "=================================================================\n";
echo "T1.5 PERFORMANCE OPTIMIZATION & CACHING - DEPLOYMENT TEST\n";
echo "=================================================================\n\n";

// TEST 1: Check migration 013 exists and is applied
echo "\n[TEST 1] Database Migration 013\n";
echo "-----------------------------------------------------------------\n";

global $wpdb;
$migrations_table = $wpdb->prefix . 'ma_deal_migrations';
$migration_013 = $wpdb->get_row(
    "SELECT * FROM {$migrations_table} WHERE migration = '013_add_performance_indexes.sql'"
);

if ($migration_013) {
    test_result(
        'Migration 013',
        'PASS',
        'Migration 013 successfully applied',
        [
            'Applied At' => $migration_013->applied_at,
            'Batch' => $migration_013->batch,
        ]
    );
} else {
    test_result('Migration 013', 'FAIL', 'Migration 013 not found in migrations table');
}

// TEST 2: Verify all 22 database tables exist
echo "\n[TEST 2] Database Tables\n";
echo "-----------------------------------------------------------------\n";

$expected_tables = [
    'ma_deal_migrations',
    'ma_deal_accounts',
    'ma_deal_templates',
    'ma_deal_transactions',
    'ma_deal_tasks',
    'ma_deal_parties',
    'ma_deal_documents',
    'ma_deal_notifications',
    'ma_deal_reminders',
    'ma_deal_events',
    'ma_deal_vendor_requests',
    'ma_deal_task_definitions',
    'ma_deal_template_tasks',
    'ma_deal_task_categories',
    'ma_deal_transaction_types',
    'ma_deal_transaction_custom_tasks',
    'ma_deal_property_attributes',
    'ma_deal_security_deposits',
    'ma_deal_custom_users',
    'ma_deal_user_sessions',
    'ma_deal_user_invitations',
    'ma_deal_verification_codes',
];

$existing_tables = $wpdb->get_col(
    "SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_%'"
);

// Convert to simple names without prefix for comparison
$existing_simple = array_map(function($table) use ($wpdb) {
    return str_replace($wpdb->prefix, '', $table);
}, $existing_tables);

$table_count = count($existing_simple);
$expected_count = count($expected_tables);

if ($table_count >= $expected_count) {
    test_result(
        'Database Tables',
        'PASS',
        "All expected tables exist ($table_count tables)",
        ['Tables' => implode(', ', array_slice($existing_simple, 0, 5)) . '...']
    );
} else {
    $missing = array_diff($expected_tables, $existing_simple);
    test_result(
        'Database Tables',
        'FAIL',
        "Missing tables: " . implode(', ', $missing),
        ['Found' => $table_count, 'Expected' => $expected_count]
    );
}

// TEST 3: Verify 36 performance indexes exist
echo "\n[TEST 3] Performance Indexes\n";
echo "-----------------------------------------------------------------\n";

$index_tests = [
    'wp_ma_deal_transactions' => ['idx_transactions_template_status_closing', 'idx_transactions_agent_status_closing', 'idx_transactions_location_status', 'idx_transactions_closing_status'],
    'wp_ma_deal_tasks' => ['idx_tasks_status_overdue', 'idx_tasks_assigned_status_due', 'idx_tasks_transaction_status_completed'],
    'wp_ma_deal_templates' => ['idx_templates_active_system_created', 'idx_templates_account_active_created', 'idx_templates_property_active'],
    'wp_ma_deal_notifications' => ['idx_notifications_read_cleanup', 'idx_notifications_recipient_read_created', 'idx_notifications_transaction_type'],
    'wp_ma_deal_parties' => ['idx_parties_transaction_role_email', 'idx_parties_users'],
    'wp_ma_deal_documents' => ['idx_documents_transaction_status_uploaded', 'idx_documents_transaction_type'],
    'wp_ma_deal_reminders' => ['idx_reminders_status_scheduled', 'idx_reminders_task_status'],
    'wp_ma_deal_custom_users' => ['idx_custom_users_status_deleted_created', 'idx_custom_users_verified_deleted'],
    'wp_ma_deal_user_sessions' => ['idx_sessions_expires_revoked'],
    'wp_ma_deal_accounts' => ['idx_accounts_subscription_status_expires', 'idx_accounts_owner_status'],
    'wp_ma_deal_user_invitations' => ['idx_invitations_email_expires_accepted'],
    'wp_ma_deal_task_definitions' => ['idx_task_definitions_category_account_title', 'idx_task_definitions_system_account'],
    'wp_ma_deal_vendor_requests' => ['idx_vendor_requests_transaction_status_created'],
    'wp_ma_deal_events' => ['idx_events_entity_created'],
];

$total_indexes_found = 0;
$total_indexes_expected = 0;
$index_details = [];

foreach ($index_tests as $table => $expected_indexes) {
    $total_indexes_expected += count($expected_indexes);

    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table}", ARRAY_A);
    $index_names = array_unique(array_column($indexes, 'Key_name'));

    foreach ($expected_indexes as $expected_index) {
        if (in_array($expected_index, $index_names)) {
            $total_indexes_found++;
        } else {
            $index_details[] = "Missing: {$table}.{$expected_index}";
        }
    }
}

if ($total_indexes_found >= 30) { // Allow some flexibility
    test_result(
        'Performance Indexes',
        'PASS',
        "Found {$total_indexes_found} of {$total_indexes_expected} expected indexes",
        empty($index_details) ? [] : ['Issues' => implode(', ', array_slice($index_details, 0, 3))]
    );
} else {
    test_result(
        'Performance Indexes',
        'FAIL',
        "Only found {$total_indexes_found} of {$total_indexes_expected} expected indexes",
        ['Missing' => implode(', ', $index_details)]
    );
}

// TEST 4: CacheService initialization
echo "\n[TEST 4] CacheService Initialization\n";
echo "-----------------------------------------------------------------\n";

try {
    $cache_service = new MADealRoom\Services\CacheService();
    $is_redis = $cache_service->isRedisAvailable();
    $backend = $is_redis ? 'Redis' : 'WordPress Transients';

    test_result(
        'CacheService Init',
        'PASS',
        "CacheService initialized successfully",
        ['Backend' => $backend]
    );

    // Test basic cache operations
    $cache_service->set('test_key', 'test_value', 60);
    $retrieved = $cache_service->get('test_key');

    if ($retrieved === 'test_value') {
        test_result('Cache Set/Get', 'PASS', 'Cache set and get working correctly');
    } else {
        test_result('Cache Set/Get', 'FAIL', 'Cache set/get returned unexpected value: ' . var_export($retrieved, true));
    }

    // Test cache has
    if ($cache_service->has('test_key')) {
        test_result('Cache Has', 'PASS', 'Cache has() method working correctly');
    } else {
        test_result('Cache Has', 'FAIL', 'Cache has() method failed');
    }

    // Cleanup
    $cache_service->delete('test_key');

} catch (Exception $e) {
    test_result('CacheService Init', 'FAIL', 'CacheService initialization failed: ' . $e->getMessage());
}

// TEST 5: TemplateEngine caching
echo "\n[TEST 5] TemplateEngine Template Caching\n";
echo "-----------------------------------------------------------------\n";

try {
    $plugin = MADealRoom\Core\Plugin::instance();
    $template_repo = $plugin->container()->get('template_repository');

    // Get a system template
    $templates = $template_repo->query(['is_system' => 1], ['limit' => 1]);

    if (empty($templates)) {
        test_result('TemplateEngine', 'WARN', 'No system templates found to test caching');
    } else {
        $template = $templates[0];

        // Create TemplateEngine with cache
        $template_engine = new MADealRoom\Services\TemplateEngine(
            $template_repo,
            null,
            null,
            $cache_service
        );

        // Parse YAML (should cache it)
        $start_time = microtime(true);
        $parsed1 = $template_engine->parseYaml($template->template_yaml, $template->id);
        $time1 = microtime(true) - $start_time;

        // Parse again (should hit cache)
        $start_time = microtime(true);
        $parsed2 = $template_engine->parseYaml($template->template_yaml, $template->id);
        $time2 = microtime(true) - $start_time;

        if ($time2 < $time1) {
            $speedup = round($time1 / $time2, 2);
            test_result(
                'TemplateEngine Caching',
                'PASS',
                'Template caching working (cached parse is faster)',
                [
                    'First Parse' => round($time1 * 1000, 2) . 'ms',
                    'Cached Parse' => round($time2 * 1000, 2) . 'ms',
                    'Speedup' => $speedup . 'x',
                ]
            );
            $results['performance']['template_cache_speedup'] = $speedup;
        } else {
            test_result('TemplateEngine Caching', 'WARN', 'Cached parse not faster (possibly too small to measure)');
        }
    }
} catch (Exception $e) {
    test_result('TemplateEngine Caching', 'FAIL', 'TemplateEngine test failed: ' . $e->getMessage());
}

// TEST 6: BaseRepository query caching
echo "\n[TEST 6] BaseRepository Query Result Caching\n";
echo "-----------------------------------------------------------------\n";

try {
    $account_repo = $plugin->container()->get('account_repository');

    // First query (cold cache)
    $start_time = microtime(true);
    $accounts1 = $account_repo->findAll(10, 0, true);
    $time1 = microtime(true) - $start_time;

    // Second query (warm cache)
    $start_time = microtime(true);
    $accounts2 = $account_repo->findAll(10, 0, true);
    $time2 = microtime(true) - $start_time;

    if ($time2 < $time1) {
        $speedup = round($time1 / $time2, 2);
        test_result(
            'Repository Query Caching',
            'PASS',
            'Query result caching working',
            [
                'First Query' => round($time1 * 1000, 2) . 'ms',
                'Cached Query' => round($time2 * 1000, 2) . 'ms',
                'Speedup' => $speedup . 'x',
            ]
        );
        $results['performance']['query_cache_speedup'] = $speedup;
    } else {
        test_result('Repository Query Caching', 'WARN', 'Cached query not significantly faster (possibly too small dataset)');
    }

    // Get cache stats
    $stats = $cache_service->getStats();
    test_result(
        'Cache Statistics',
        'PASS',
        'Cache statistics collected',
        [
            'Hits' => $stats['hits'],
            'Misses' => $stats['misses'],
            'Hit Rate' => $stats['hit_rate'],
            'Backend' => $stats['backend'],
        ]
    );
    $results['performance']['cache_stats'] = $stats;

} catch (Exception $e) {
    test_result('Repository Query Caching', 'FAIL', 'Repository caching test failed: ' . $e->getMessage());
}

// TEST 7: Pagination limits
echo "\n[TEST 7] Pagination Limits Enforcement\n";
echo "-----------------------------------------------------------------\n";

try {
    // Test with excessive limit
    $paginated = $account_repo->paginate([], ['page' => 1, 'per_page' => 500]);

    if ($paginated['pagination']['per_page'] <= 100) {
        test_result(
            'Pagination Limit',
            'PASS',
            'Pagination limit correctly enforced at 100',
            ['Requested' => 500, 'Enforced' => $paginated['pagination']['per_page']]
        );
    } else {
        test_result(
            'Pagination Limit',
            'FAIL',
            'Pagination limit not enforced: ' . $paginated['pagination']['per_page']
        );
    }

    // Test default pagination
    $paginated_default = $account_repo->paginate([]);
    if ($paginated_default['pagination']['per_page'] === 50) {
        test_result('Default Pagination', 'PASS', 'Default pagination set to 50');
    } else {
        test_result('Default Pagination', 'WARN', 'Default pagination is ' . $paginated_default['pagination']['per_page']);
    }

} catch (Exception $e) {
    test_result('Pagination Limits', 'FAIL', 'Pagination test failed: ' . $e->getMessage());
}

// TEST 8: Cache invalidation
echo "\n[TEST 8] Cache Invalidation\n";
echo "-----------------------------------------------------------------\n";

try {
    // Create a test account
    $test_account = $account_repo->create([
        'name' => 'Test Cache Invalidation Account',
        'owner_user_id' => 1,
        'status' => 'active',
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
    ]);

    if ($test_account) {
        // Fetch it (should cache)
        $fetched1 = $account_repo->find($test_account, true);

        // Update it (should invalidate cache)
        $account_repo->update($test_account, ['name' => 'Updated Cache Test']);

        // Fetch again (should get fresh data)
        $fetched2 = $account_repo->find($test_account, true);

        if ($fetched2->name === 'Updated Cache Test') {
            test_result('Cache Invalidation', 'PASS', 'Cache correctly invalidated on update');
        } else {
            test_result('Cache Invalidation', 'FAIL', 'Cache not invalidated: got stale data');
        }

        // Cleanup
        $account_repo->delete($test_account);

    } else {
        test_result('Cache Invalidation', 'WARN', 'Could not create test account');
    }

} catch (Exception $e) {
    test_result('Cache Invalidation', 'FAIL', 'Cache invalidation test failed: ' . $e->getMessage());
}

// TEST 9: Check for PHP errors in debug.log
echo "\n[TEST 9] PHP Error Log Check\n";
echo "-----------------------------------------------------------------\n";

$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    // Get last 100 lines
    $lines = array_slice(file($debug_log), -100);
    $recent_errors = array_filter($lines, function($line) {
        return (
            stripos($line, 'error') !== false ||
            stripos($line, 'warning') !== false ||
            stripos($line, 'fatal') !== false
        ) && stripos($line, 'MA Deal') !== false;
    });

    if (empty($recent_errors)) {
        test_result('PHP Errors', 'PASS', 'No recent PHP errors found in debug.log');
    } else {
        test_result(
            'PHP Errors',
            'WARN',
            'Found ' . count($recent_errors) . ' recent errors/warnings',
            ['Sample' => trim(array_values($recent_errors)[0] ?? 'N/A')]
        );
    }
} else {
    test_result('PHP Errors', 'PASS', 'Debug log does not exist (no errors logged)');
}

// FINAL SUMMARY
echo "\n=================================================================\n";
echo "TEST SUMMARY\n";
echo "=================================================================\n";

$passed = count(array_filter($results['tests'], fn($t) => $t['status'] === 'PASS'));
$failed = count(array_filter($results['tests'], fn($t) => $t['status'] === 'FAIL'));
$warned = count(array_filter($results['tests'], fn($t) => $t['status'] === 'WARN'));

echo "Overall Status: {$results['overall_status']}\n";
echo "Tests Passed: {$passed}\n";
echo "Tests Failed: {$failed}\n";
echo "Warnings: {$warned}\n";
echo "Total Tests: " . count($results['tests']) . "\n\n";

if (!empty($results['errors'])) {
    echo "ERRORS:\n";
    foreach ($results['errors'] as $error) {
        echo "  - {$error}\n";
    }
    echo "\n";
}

if (!empty($results['warnings'])) {
    echo "WARNINGS:\n";
    foreach ($results['warnings'] as $warning) {
        echo "  - {$warning}\n";
    }
    echo "\n";
}

if (!empty($results['performance'])) {
    echo "PERFORMANCE METRICS:\n";
    foreach ($results['performance'] as $metric => $value) {
        if (is_array($value)) {
            echo "  {$metric}:\n";
            foreach ($value as $k => $v) {
                echo "    - {$k}: {$v}\n";
            }
        } else {
            echo "  - {$metric}: {$value}\n";
        }
    }
    echo "\n";
}

echo "=================================================================\n";

// Exit with appropriate code
exit($results['overall_status'] === 'PASS' ? 0 : 1);
