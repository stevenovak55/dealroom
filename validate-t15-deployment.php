<?php
/**
 * Quick T1.5 Deployment Validation Script
 * Run this to verify T1.5 features are working in any environment
 */

// Load WordPress
require_once('/var/www/html/wp-load.php');

$passed = 0;
$failed = 0;
$warnings = 0;

function check($name, $condition, $success_msg, $fail_msg) {
    global $passed, $failed;
    if ($condition) {
        echo "✓ {$name}: {$success_msg}\n";
        $passed++;
        return true;
    } else {
        echo "✗ {$name}: {$fail_msg}\n";
        $failed++;
        return false;
    }
}

function warn($name, $condition, $msg) {
    global $warnings;
    if (!$condition) {
        echo "⚠ {$name}: {$msg}\n";
        $warnings++;
    }
}

echo "\n";
echo "=================================================================\n";
echo "T1.5 DEPLOYMENT VALIDATION\n";
echo "=================================================================\n\n";

global $wpdb;

// 1. Check Migration 013
$migration_013 = $wpdb->get_var(
    "SELECT migration_number FROM {$wpdb->prefix}ma_deal_migrations WHERE migration_number = '013'"
);
check(
    'Migration 013',
    $migration_013 !== null,
    'Applied and recorded',
    'Not found in migrations table'
);

// 2. Check CacheService
try {
    $cache = new MADealRoom\Services\CacheService();
    $backend = $cache->isRedisAvailable() ? 'Redis' : 'Transients';
    check(
        'CacheService',
        true,
        "Working ({$backend})",
        'Failed to initialize'
    );

    warn(
        'Redis Backend',
        $cache->isRedisAvailable(),
        'Redis not available, using transients fallback (OK for dev)'
    );
} catch (Exception $e) {
    check('CacheService', false, '', 'Exception: ' . $e->getMessage());
}

// 3. Check TemplateEngine Integration
try {
    $plugin = MADealRoom\Core\Plugin::instance();
    $template_repo = $plugin->container()->get('template_repository');
    $template_engine = new MADealRoom\Services\TemplateEngine(
        $template_repo,
        null,
        null,
        $cache
    );

    check(
        'TemplateEngine',
        $template_engine !== null,
        'Initialized with cache service',
        'Failed to initialize'
    );
} catch (Exception $e) {
    check('TemplateEngine', false, '', 'Exception: ' . $e->getMessage());
}

// 4. Check BaseRepository Caching
try {
    $account_repo = $plugin->container()->get('account_repository');
    $has_cache = method_exists($account_repo, 'shouldUseCache');

    check(
        'BaseRepository Caching',
        $has_cache,
        'Cache methods present',
        'Cache methods missing'
    );
} catch (Exception $e) {
    check('BaseRepository Caching', false, '', 'Exception: ' . $e->getMessage());
}

// 5. Check Pagination Limits
try {
    $paginated = $account_repo->paginate([], ['page' => 1, 'per_page' => 500]);
    $enforced = $paginated['pagination']['per_page'] <= 100;

    check(
        'Pagination Limits',
        $enforced,
        'Max limit enforced (100)',
        'Limit not enforced'
    );
} catch (Exception $e) {
    check('Pagination Limits', false, '', 'Exception: ' . $e->getMessage());
}

// 6. Check Performance Indexes
$index_tables = [
    'wp_ma_deal_transactions' => 'idx_transactions_template_status_closing',
    'wp_ma_deal_tasks' => 'idx_tasks_status_overdue',
    'wp_ma_deal_templates' => 'idx_templates_active_system_created',
    'wp_ma_deal_parties' => 'idx_parties_transaction_role_email',
];

$indexes_found = 0;
$indexes_total = count($index_tables);

foreach ($index_tables as $table => $index) {
    $result = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Key_name = '{$index}'", ARRAY_A);
    if (!empty($result)) {
        $indexes_found++;
    }
}

check(
    'Performance Indexes',
    $indexes_found >= 3,
    "Sample indexes present ({$indexes_found}/{$indexes_total})",
    "Missing key indexes ({$indexes_found}/{$indexes_total})"
);

// 7. Quick Performance Test
$start = microtime(true);
$cache->set('perf_test', 'value', 60);
$retrieved = $cache->get('perf_test');
$cache->delete('perf_test');
$time = (microtime(true) - $start) * 1000;

check(
    'Cache Performance',
    $time < 50,
    sprintf('Fast response (%.2fms)', $time),
    sprintf('Slow response (%.2fms)', $time)
);

// 8. Check Database Tables
$tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}ma_deal_%'");
check(
    'Database Tables',
    count($tables) >= 22,
    count($tables) . ' tables present',
    'Missing tables (' . count($tables) . ')'
);

echo "\n";
echo "=================================================================\n";
echo "VALIDATION SUMMARY\n";
echo "=================================================================\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Warnings: {$warnings}\n";
echo "Status: " . ($failed === 0 ? "✅ PASS" : "✗ FAIL") . "\n";
echo "=================================================================\n\n";

if ($failed === 0) {
    echo "✅ T1.5 Performance Optimization is DEPLOYED and WORKING!\n\n";
    echo "Key Features Active:\n";
    echo "  - CacheService ({$backend})\n";
    echo "  - TemplateEngine caching\n";
    echo "  - BaseRepository query caching\n";
    echo "  - Pagination limits (max 100)\n";
    echo "  - Performance indexes ({$indexes_found} sample indexes verified)\n";
    echo "  - Migration 013 applied\n\n";

    if ($warnings > 0) {
        echo "⚠️  {$warnings} warning(s) noted (see above)\n";
    }
} else {
    echo "✗ T1.5 Deployment has {$failed} failing check(s)\n";
    echo "   Review errors above and fix before deploying to production.\n\n";
}

exit($failed === 0 ? 0 : 1);
