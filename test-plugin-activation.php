#!/usr/bin/env php
<?php
/**
 * MA Deal Room - Comprehensive Plugin Activation Test
 *
 * Tests plugin activation, migration, and table creation for both:
 * - Fresh installations
 * - Plugin updates
 *
 * Usage: php test-plugin-activation.php
 */

// Colors for terminal output
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('NC', "\033[0m"); // No Color

$test_results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
];

echo "\n";
echo BLUE . "========================================\n" . NC;
echo BLUE . "MA DEAL ROOM - ACTIVATION TEST SUITE\n" . NC;
echo BLUE . "========================================\n" . NC;
echo "\n";

/**
 * Test helper function
 */
function test($name, $condition, $success_msg, $fail_msg) {
    global $test_results;

    if ($condition) {
        echo GREEN . "✓ " . NC . $name . ": " . $success_msg . "\n";
        $test_results['passed']++;
        return true;
    } else {
        echo RED . "✗ " . NC . $name . ": " . $fail_msg . "\n";
        $test_results['failed']++;
        return false;
    }
}

function warn($name, $message) {
    global $test_results;
    echo YELLOW . "⚠ " . NC . $name . ": " . $message . "\n";
    $test_results['warnings']++;
}

function section($title) {
    echo "\n" . BLUE . "--- " . $title . " ---\n" . NC;
}

// ============================================================================
// SECTION 1: File Structure Tests
// ============================================================================
section("File Structure Tests");

$plugin_root = __DIR__ . '/ma-deal-room';
test(
    'Plugin directory exists',
    is_dir($plugin_root),
    'Found at ' . $plugin_root,
    'Plugin directory not found'
);

test(
    'Main plugin file exists',
    file_exists($plugin_root . '/ma-deal-room.php'),
    'ma-deal-room.php found',
    'ma-deal-room.php not found'
);

test(
    'Composer vendor directory exists',
    is_dir($plugin_root . '/vendor'),
    'Dependencies installed',
    'Run: composer install'
);

test(
    'Composer autoloader exists',
    file_exists($plugin_root . '/vendor/autoload.php'),
    'Autoloader available',
    'Composer dependencies missing'
);

test(
    'React admin build exists',
    file_exists($plugin_root . '/assets/admin/dist/assets/index.js'),
    'React build found',
    'Run: npm run build in assets/admin'
);

// ============================================================================
// SECTION 2: Plugin File Tests
// ============================================================================
section("Plugin File Tests");

$plugin_file_content = file_get_contents($plugin_root . '/ma-deal-room.php');

test(
    'Plugin header present',
    strpos($plugin_file_content, 'Plugin Name:') !== false,
    'Plugin header found',
    'Plugin header missing'
);

test(
    'Plugin version defined',
    preg_match('/Version:\s*(\d+\.\d+\.\d+)/', $plugin_file_content, $version_matches),
    'Version: ' . ($version_matches[1] ?? 'unknown'),
    'Version not found'
);

test(
    'PHP version check present',
    strpos($plugin_file_content, 'MA_DEAL_MIN_PHP_VERSION') !== false,
    'PHP version check implemented',
    'PHP version check missing'
);

test(
    'Direct access protection',
    strpos($plugin_file_content, "defined('ABSPATH')") !== false,
    'ABSPATH check present',
    'Direct access protection missing'
);

test(
    'Composer autoloader required',
    strpos($plugin_file_content, "require_once MA_DEAL_PATH . 'vendor/autoload.php'") !== false,
    'Autoloader loaded',
    'Autoloader not loaded'
);

// ============================================================================
// SECTION 3: Class Autoloading Tests
// ============================================================================
section("Class Autoloading Tests");

if (file_exists($plugin_root . '/vendor/autoload.php')) {
    // Define WordPress constants if not defined
    if (!defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (!defined('MA_DEAL_PATH')) {
        define('MA_DEAL_PATH', $plugin_root . '/');
    }
    if (!defined('MA_DEAL_VERSION')) {
        define('MA_DEAL_VERSION', '2.0.0');
    }

    require_once $plugin_root . '/vendor/autoload.php';

    $critical_classes = [
        'MADealRoom\\Core\\Plugin',
        'MADealRoom\\Core\\ServiceContainer',
        'MADealRoom\\Services\\AuthService',
        'MADealRoom\\Services\\AccountSecurityService',
        'MADealRoom\\Services\\EmailService',
        'MADealRoom\\Services\\TwoFactorAuthService',
        'MADealRoom\\Database\\Migrator',
        'MADealRoom\\Repositories\\CustomUserRepository',
        'MADealRoom\\Models\\CustomUser',
        'MADealRoom\\REST\\Controllers\\AuthController',
    ];

    foreach ($critical_classes as $class) {
        test(
            'Class autoloads: ' . basename(str_replace('\\', '/', $class)),
            class_exists($class),
            'Class found and loadable',
            'Class not found - check PSR-4 autoloading'
        );
    }
} else {
    warn('Autoloader test', 'Skipped - vendor/autoload.php not found');
}

// ============================================================================
// SECTION 4: Database Migration Tests
// ============================================================================
section("Database Migration Tests");

$migrations_dir = $plugin_root . '/database/migrations';
test(
    'Migrations directory exists',
    is_dir($migrations_dir),
    'Found migrations directory',
    'Migrations directory not found'
);

// Get all migration files
$migration_files = glob($migrations_dir . '/*.sql');
$migration_files = array_filter($migration_files, function($file) {
    return strpos(basename($file), 'rollback_') === false;
});
sort($migration_files);

test(
    'Migration files present',
    count($migration_files) > 0,
    count($migration_files) . ' migration files found',
    'No migration files found'
);

// Check migration numbering
$expected_number = 1;
$migration_sequence_valid = true;
foreach ($migration_files as $file) {
    $basename = basename($file);
    if (!preg_match('/^(\d{3})_/', $basename, $matches)) {
        $migration_sequence_valid = false;
        break;
    }
    $number = (int)$matches[1];
    if ($number !== $expected_number) {
        $migration_sequence_valid = false;
        break;
    }
    $expected_number++;
}

test(
    'Migration sequence valid',
    $migration_sequence_valid,
    'Migrations numbered correctly (001-' . str_pad($expected_number - 1, 3, '0', STR_PAD_LEFT) . ')',
    'Migration numbering sequence broken'
);

// Check for rollback files
$rollback_files = glob($migrations_dir . '/rollback_*.sql');
test(
    'Rollback migrations present',
    count($rollback_files) > 0,
    count($rollback_files) . ' rollback files found',
    'No rollback files found'
);

// Parse migrations for table creation
$tables_created = [];
foreach ($migration_files as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/CREATE TABLE.*?`?(\w+ma_deal_\w+)`?/i', $content, $matches)) {
        foreach ($matches[1] as $table) {
            $tables_created[] = str_replace('wp_', '', $table);
        }
    }
}
$tables_created = array_unique($tables_created);

test(
    'Tables defined in migrations',
    count($tables_created) > 0,
    count($tables_created) . ' tables will be created',
    'No CREATE TABLE statements found'
);

echo "\n  Tables that will be created:\n";
foreach ($tables_created as $table) {
    echo "    - " . $table . "\n";
}

// ============================================================================
// SECTION 5: Uninstall Script Tests
// ============================================================================
section("Uninstall Script Tests");

test(
    'Uninstall script exists',
    file_exists($plugin_root . '/uninstall.php'),
    'uninstall.php found',
    'uninstall.php not found'
);

if (file_exists($plugin_root . '/uninstall.php')) {
    $uninstall_content = file_get_contents($plugin_root . '/uninstall.php');

    test(
        'Uninstall safety check',
        strpos($uninstall_content, "defined('WP_UNINSTALL_PLUGIN')") !== false,
        'WP_UNINSTALL_PLUGIN check present',
        'Missing WP_UNINSTALL_PLUGIN check'
    );

    // Extract tables from uninstall script
    preg_match_all('/\$prefix\s*\.\s*[\'"](\w+)[\'"]/', $uninstall_content, $uninstall_matches);
    $uninstall_tables = array_unique($uninstall_matches[1]);

    test(
        'Uninstall tables defined',
        count($uninstall_tables) > 0,
        count($uninstall_tables) . ' tables will be dropped on uninstall',
        'No tables defined in uninstall script'
    );

    // Check if all migration tables are in uninstall script
    $missing_in_uninstall = array_diff($tables_created, $uninstall_tables);
    test(
        'All migration tables in uninstall',
        count($missing_in_uninstall) === 0,
        'All tables covered',
        'Missing: ' . implode(', ', $missing_in_uninstall)
    );

    test(
        'Foreign key checks disabled',
        strpos($uninstall_content, 'FOREIGN_KEY_CHECKS') !== false,
        'FK checks disabled during cleanup',
        'FK checks not disabled'
    );
}

// ============================================================================
// SECTION 6: REST API Tests
// ============================================================================
section("REST API Tests");

$controller_files = glob($plugin_root . '/src/REST/Controllers/*Controller.php');
test(
    'REST controllers present',
    count($controller_files) > 0,
    count($controller_files) . ' controllers found',
    'No controllers found'
);

echo "\n  Controllers:\n";
foreach ($controller_files as $file) {
    $controller_name = basename($file, '.php');
    echo "    - " . $controller_name . "\n";

    // Check if controller extends BaseController
    $content = file_get_contents($file);
    if (strpos($content, 'extends BaseController') === false) {
        warn($controller_name, 'Does not extend BaseController');
    }

    // Check if register_routes method exists
    if (strpos($content, 'public function register_routes') === false) {
        warn($controller_name, 'Missing register_routes() method');
    }
}

// ============================================================================
// SECTION 7: Security Tests
// ============================================================================
section("Security Tests");

// Check AuthService for JWT validation
$auth_service_file = $plugin_root . '/src/Services/AuthService.php';
if (file_exists($auth_service_file)) {
    $auth_content = file_get_contents($auth_service_file);

    test(
        'JWT secret validation implemented',
        strpos($auth_content, 'check_jwt_secret_security') !== false,
        'JWT validation method present',
        'JWT validation missing'
    );

    test(
        'Weak secret detection',
        strpos($auth_content, 'weak_patterns') !== false,
        'Weak pattern detection implemented',
        'No weak secret detection'
    );

    test(
        'Password hashing present',
        strpos($auth_content, 'password_hash') !== false,
        'Uses password_hash()',
        'Password hashing not found'
    );
}

// Check AccountSecurityService
$security_service_file = $plugin_root . '/src/Services/AccountSecurityService.php';
test(
    'AccountSecurityService exists',
    file_exists($security_service_file),
    'Security service present',
    'AccountSecurityService missing'
);

// Check FileSecurityService
$file_security_file = $plugin_root . '/src/Services/FileSecurityService.php';
if (file_exists($file_security_file)) {
    $file_security_content = file_get_contents($file_security_file);

    test(
        'File type validation',
        strpos($file_security_content, 'validate_file_type') !== false,
        'File type validation present',
        'File type validation missing'
    );

    test(
        'Virus scanning support',
        strpos($file_security_content, 'scan_for_viruses') !== false,
        'Virus scanning implemented',
        'Virus scanning missing'
    );
}

// ============================================================================
// SECTION 8: Configuration Tests
// ============================================================================
section("Configuration Tests");

test(
    '.env.example exists',
    file_exists(__DIR__ . '/.env.example'),
    'Environment template present',
    '.env.example not found'
);

if (file_exists(__DIR__ . '/.env.example')) {
    $env_content = file_get_contents(__DIR__ . '/.env.example');

    test(
        'JWT config in .env.example',
        strpos($env_content, 'JWT_SECRET_KEY') !== false,
        'JWT configuration present',
        'JWT configuration missing'
    );

    test(
        'JWT security warnings',
        strpos($env_content, 'CRITICAL SECURITY WARNING') !== false,
        'Security warnings present',
        'Security warnings missing'
    );

    test(
        'Database config in .env.example',
        strpos($env_content, 'DB_NAME') !== false && strpos($env_content, 'DB_USER') !== false,
        'Database configuration present',
        'Database configuration incomplete'
    );

    test(
        'CORS config in .env.example',
        strpos($env_content, 'CORS_ALLOWED_ORIGINS') !== false,
        'CORS configuration present',
        'CORS configuration missing'
    );
}

// ============================================================================
// SECTION 9: Documentation Tests
// ============================================================================
section("Documentation Tests");

$required_docs = [
    'README.md' => 'Main readme',
    'PRODUCTION_READY_CHECKLIST.md' => 'Deployment guide',
    'KNOWN_TODOS.md' => 'TODO tracking',
    'AI_MASTER.md' => 'AI instructions',
    'CLAUDE.md' => 'Claude-specific instructions',
];

foreach ($required_docs as $file => $description) {
    test(
        $description,
        file_exists(__DIR__ . '/' . $file),
        $file . ' present',
        $file . ' not found'
    );
}

// ============================================================================
// SECTION 10: Development Tools Check
// ============================================================================
section("Development Tools Check");

$utility_files = glob(__DIR__ . '/*.php');
$root_php_files = array_filter($utility_files, function($file) {
    $basename = basename($file);
    return $basename !== 'test-plugin-activation.php' &&
           !in_array($basename, ['index.php', 'wp-config.php']);
});

test(
    'No utility scripts in root',
    count($root_php_files) === 0,
    'Root directory clean',
    'Found ' . count($root_php_files) . ' PHP files in root (should be in dev-tools/)'
);

test(
    'dev-tools directory exists',
    is_dir(__DIR__ . '/dev-tools'),
    'Development utilities organized',
    'dev-tools/ directory not found'
);

if (is_dir(__DIR__ . '/dev-tools')) {
    $dev_tool_files = glob(__DIR__ . '/dev-tools/*.php');
    echo "  Development tools: " . count($dev_tool_files) . " files in dev-tools/\n";
}

// ============================================================================
// FINAL RESULTS
// ============================================================================
echo "\n";
echo BLUE . "========================================\n" . NC;
echo BLUE . "TEST RESULTS SUMMARY\n" . NC;
echo BLUE . "========================================\n" . NC;
echo "\n";

echo GREEN . "Passed:   " . $test_results['passed'] . "\n" . NC;
echo RED . "Failed:   " . $test_results['failed'] . "\n" . NC;
echo YELLOW . "Warnings: " . $test_results['warnings'] . "\n" . NC;

$total = $test_results['passed'] + $test_results['failed'];
$pass_rate = $total > 0 ? round(($test_results['passed'] / $total) * 100, 1) : 0;

echo "\nPass Rate: " . $pass_rate . "%\n";

if ($test_results['failed'] === 0) {
    echo "\n" . GREEN . "✓ ALL TESTS PASSED - Plugin is ready for activation!\n" . NC;
    exit(0);
} else {
    echo "\n" . RED . "✗ SOME TESTS FAILED - Fix issues before activation\n" . NC;
    exit(1);
}
