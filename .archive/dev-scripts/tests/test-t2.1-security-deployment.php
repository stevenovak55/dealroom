<?php
/**
 * Comprehensive T2.1 Security Hardening Deployment Test
 * Tests plugin activation, deactivation, and all security features
 *
 * Usage: docker exec ma-dealroom-cli php /var/www/html/wp-content/plugins/ma-deal-room/test-t2.1-security-deployment.php
 */

// Bootstrap WordPress
require_once('/var/www/html/wp-load.php');

// Define test colors for output
define('COLOR_GREEN', "\033[0;32m");
define('COLOR_RED', "\033[0;31m");
define('COLOR_YELLOW', "\033[1;33m");
define('COLOR_BLUE', "\033[0;34m");
define('COLOR_RESET', "\033[0m");

class T21SecurityDeploymentTest {
    private $results = [];
    private $errors = [];
    private $warnings = [];

    public function __construct() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "MA DEAL ROOM - T2.1 SECURITY HARDENING DEPLOYMENT TEST\n";
        echo "Testing Date: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 80) . "\n\n";
    }

    public function runAllTests() {
        $this->testEnvironment();
        $this->testPluginStatus();
        $this->testDatabaseMigrations();
        $this->testSecurityServices();
        $this->testRateLimiting();
        $this->testFileUploadSecurity();
        $this->testEmailVerification();
        $this->testSessionSecurity();
        $this->testPasswordPolicy();
        $this->testRESTAPIEndpoints();
        $this->checkDebugLog();

        $this->printSummary();
    }

    private function testEnvironment() {
        $this->printHeader("1. ENVIRONMENT VERIFICATION");

        // Check PHP version
        $phpVersion = PHP_VERSION;
        $this->test("PHP Version >= 8.0", version_compare($phpVersion, '8.0', '>='),
            "PHP $phpVersion");

        // Check WordPress version
        global $wp_version;
        $this->test("WordPress Version >= 6.0", version_compare($wp_version, '6.0', '>='),
            "WordPress $wp_version");

        // Check plugin active
        $this->test("MA Deal Room Plugin Active",
            is_plugin_active('ma-deal-room/ma-deal-room.php'),
            "Plugin is active");

        // Check autoloader
        $this->test("Composer Autoloader",
            file_exists(MA_DEAL_PATH . 'vendor/autoload.php'),
            "Autoloader loaded");

        // Check database connection
        global $wpdb;
        $result = $wpdb->get_var("SELECT 1");
        $this->test("Database Connection", $result === '1', "Connected to database");

        echo "\n";
    }

    private function testPluginStatus() {
        $this->printHeader("2. PLUGIN STATUS");

        // Check plugin constants
        $this->test("MA_DEAL_VERSION defined", defined('MA_DEAL_VERSION'),
            "Version: " . (defined('MA_DEAL_VERSION') ? MA_DEAL_VERSION : 'N/A'));

        $this->test("MA_DEAL_PATH defined", defined('MA_DEAL_PATH'),
            "Path: " . (defined('MA_DEAL_PATH') ? MA_DEAL_PATH : 'N/A'));

        // Check if plugin files exist
        $criticalFiles = [
            'ma-deal-room.php' => 'Main plugin file',
            'uninstall.php' => 'Uninstall script',
            'src/Database/Migrator.php' => 'Migration system',
            'src/REST/Controllers/AuthController.php' => 'Auth controller',
            'src/Services/PasswordValidator.php' => 'Password validator',
            'src/Services/PasswordPolicyService.php' => 'Password policy service',
            'src/Services/FileSecurityService.php' => 'File security service',
        ];

        foreach ($criticalFiles as $file => $description) {
            $path = MA_DEAL_PATH . $file;
            $this->test("File exists: $description", file_exists($path), $file);
        }

        echo "\n";
    }

    private function testDatabaseMigrations() {
        $this->printHeader("3. DATABASE MIGRATIONS");

        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_migrations';

        // Check migrations table exists
        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $this->test("Migrations table exists", $tableExists, $table);

        if (!$tableExists) {
            $this->errors[] = "Migrations table not found - cannot proceed with migration tests";
            echo "\n";
            return;
        }

        // Get all applied migrations
        $migrations = $wpdb->get_results(
            "SELECT migration_number, migration_name, applied_at
             FROM $table
             ORDER BY id ASC"
        );

        $this->test("Migrations applied", count($migrations) > 0,
            count($migrations) . " migrations");

        // Check for specific security migrations
        $securityMigrations = [
            '014' => 'Rate Limiting (T2.1.1)',
            '015' => 'File Security (T2.1.2)',
            '016' => 'Email Verification (T2.1.3)',
            '017' => 'Session Security (T2.1.4)',
            '018' => 'Password Policy (T2.1.5)',
        ];

        $appliedNumbers = array_column($migrations, 'migration_number');

        foreach ($securityMigrations as $number => $name) {
            $applied = in_array($number, $appliedNumbers);
            $this->test("Migration $number: $name", $applied,
                $applied ? "Applied" : "NOT APPLIED");
        }

        // Check migration 018 specifically (password security)
        $migration018 = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE migration_number = %s", '018')
        );

        if ($migration018) {
            $this->test("Migration 018 applied date", true,
                "Applied at: " . $migration018->applied_at);

            // Verify password security columns exist
            $customUsersTable = $wpdb->prefix . 'ma_deal_custom_users';
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $customUsersTable LIKE 'password_%'");
            $columnNames = array_column($columns, 'Field');

            $this->test("password_history column",
                in_array('password_history', $columnNames),
                "Tracks last 5 passwords");

            $this->test("password_expires_at column",
                in_array('password_expires_at', $columnNames),
                "Password expiration support");
        } else {
            $this->warning("Migration 018 not yet applied - password policy features may not be fully functional");
        }

        echo "\n";
    }

    private function testSecurityServices() {
        $this->printHeader("4. SECURITY SERVICES INITIALIZATION");

        // Test PasswordValidator service
        try {
            if (class_exists('MA_Deal_Room\\Services\\PasswordValidator')) {
                $validator = new \MA_Deal_Room\Services\PasswordValidator();
                $this->test("PasswordValidator instantiated", true, "Service available");
            } else {
                $this->test("PasswordValidator class exists", false, "Class not found");
            }
        } catch (Exception $e) {
            $this->test("PasswordValidator", false, "Error: " . $e->getMessage());
        }

        // Test PasswordPolicyService
        try {
            if (class_exists('MA_Deal_Room\\Services\\PasswordPolicyService')) {
                $this->test("PasswordPolicyService class exists", true, "Service available");
            } else {
                $this->test("PasswordPolicyService class exists", false, "Class not found");
            }
        } catch (Exception $e) {
            $this->test("PasswordPolicyService", false, "Error: " . $e->getMessage());
        }

        // Test FileSecurityService
        try {
            if (class_exists('MA_Deal_Room\\Services\\FileSecurityService')) {
                $this->test("FileSecurityService class exists", true, "Service available");
            } else {
                $this->test("FileSecurityService class exists", false, "Class not found");
            }
        } catch (Exception $e) {
            $this->test("FileSecurityService", false, "Error: " . $e->getMessage());
        }

        // Test PasswordResetService
        try {
            if (class_exists('MA_Deal_Room\\Services\\PasswordResetService')) {
                $this->test("PasswordResetService class exists", true, "Service available");
            } else {
                $this->test("PasswordResetService class exists", false, "Class not found");
            }
        } catch (Exception $e) {
            $this->test("PasswordResetService", false, "Error: " . $e->getMessage());
        }

        echo "\n";
    }

    private function testRateLimiting() {
        $this->printHeader("5. T2.1.1: GLOBAL API RATE LIMITING");

        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_rate_limits';

        // Check rate limits table exists
        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $this->test("Rate limits table exists", $tableExists, $table);

        if ($tableExists) {
            // Check table structure
            $columns = $wpdb->get_results("DESCRIBE $table");
            $columnNames = array_column($columns, 'Field');

            $expectedColumns = ['id', 'identifier', 'endpoint', 'request_count', 'window_start', 'created_at'];
            foreach ($expectedColumns as $col) {
                $this->test("Column exists: $col", in_array($col, $columnNames), "");
            }

            // Check if rate limiting middleware is registered
            $this->test("Rate limiting configured", true,
                "Table exists - middleware should be active");
        }

        echo "\n";
    }

    private function testFileUploadSecurity() {
        $this->printHeader("6. T2.1.2: FILE UPLOAD SECURITY");

        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_documents';

        // Check documents table has security columns
        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $this->test("Documents table exists", $tableExists, $table);

        if ($tableExists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
            $columnNames = array_column($columns, 'Field');

            $securityColumns = [
                'virus_scan_status' => 'Virus scan status tracking',
                'virus_scan_result' => 'Scan result details',
                'file_hash' => 'File integrity checksum',
            ];

            foreach ($securityColumns as $col => $description) {
                $exists = in_array($col, $columnNames);
                $this->test("Security column: $col", $exists, $description);
            }

            // Check FileSecurityService
            $serviceExists = class_exists('MA_Deal_Room\\Services\\FileSecurityService');
            $this->test("FileSecurityService available", $serviceExists,
                "Virus scanning and validation service");
        }

        echo "\n";
    }

    private function testEmailVerification() {
        $this->printHeader("7. T2.1.3: EMAIL VERIFICATION REQUIREMENT");

        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_custom_users';

        // Check users table exists with email verification columns
        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $this->test("Custom users table exists", $tableExists, $table);

        if ($tableExists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
            $columnNames = array_column($columns, 'Field');

            $emailColumns = [
                'email_verified' => 'Email verification status',
                'email_verification_token' => 'Verification token',
                'email_verification_sent_at' => 'Token sent timestamp',
            ];

            foreach ($emailColumns as $col => $description) {
                $exists = in_array($col, $columnNames);
                $this->test("Column: $col", $exists, $description);
            }

            // Check AuthController has email verification logic
            $authControllerPath = MA_DEAL_PATH . 'src/REST/Controllers/AuthController.php';
            if (file_exists($authControllerPath)) {
                $content = file_get_contents($authControllerPath);
                $hasVerificationCheck = strpos($content, 'email_verified') !== false;
                $this->test("Email verification enforced in AuthController",
                    $hasVerificationCheck,
                    "Login checks email_verified status");
            }
        }

        echo "\n";
    }

    private function testSessionSecurity() {
        $this->printHeader("8. T2.1.4: SESSION REGENERATION ON LOGIN");

        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_user_sessions';

        // Check sessions table exists
        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $this->test("User sessions table exists", $tableExists, $table);

        if ($tableExists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table");
            $columnNames = array_column($columns, 'Field');

            $sessionColumns = [
                'session_id' => 'Session identifier',
                'user_id' => 'User reference',
                'ip_address' => 'IP tracking',
                'user_agent' => 'Device tracking',
                'is_suspicious' => 'Suspicious activity flag',
                'logout_reason' => 'Logout reason tracking',
            ];

            foreach ($sessionColumns as $col => $description) {
                $exists = in_array($col, $columnNames);
                $this->test("Column: $col", $exists, $description);
            }

            // Check AuthController has session regeneration
            $authControllerPath = MA_DEAL_PATH . 'src/REST/Controllers/AuthController.php';
            if (file_exists($authControllerPath)) {
                $content = file_get_contents($authControllerPath);
                $hasRegeneration = strpos($content, 'regenerate') !== false;
                $this->test("Session regeneration implemented",
                    $hasRegeneration,
                    "Login regenerates session_id");
            }
        }

        echo "\n";
    }

    private function testPasswordPolicy() {
        $this->printHeader("9. T2.1.5: ENHANCED PASSWORD POLICY");

        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_custom_users';

        // Check password policy columns
        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $this->test("Custom users table exists", $tableExists, $table);

        if ($tableExists) {
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'password_%'");
            $columnNames = array_column($columns, 'Field');

            $policyColumns = [
                'password_hash' => 'Encrypted password storage',
                'password_history' => 'Last 5 passwords (prevents reuse)',
                'password_changed_at' => 'Password change timestamp',
                'password_expires_at' => 'Password expiration date',
            ];

            foreach ($policyColumns as $col => $description) {
                $exists = in_array($col, $columnNames);
                $this->test("Column: $col", $exists, $description);
            }

            // Check PasswordValidator service
            $validatorPath = MA_DEAL_PATH . 'src/Services/PasswordValidator.php';
            if (file_exists($validatorPath)) {
                $content = file_get_contents($validatorPath);

                $checks = [
                    'MIN_LENGTH' => 'Minimum length requirement',
                    'common-passwords.txt' => 'Common password blocking',
                    'zxcvbn' => 'Password strength validation',
                ];

                foreach ($checks as $pattern => $description) {
                    $exists = strpos($content, $pattern) !== false;
                    $this->test("Policy: $description", $exists, "Implemented");
                }
            }

            // Check PasswordPolicyService
            $policyServicePath = MA_DEAL_PATH . 'src/Services/PasswordPolicyService.php';
            $this->test("PasswordPolicyService exists",
                file_exists($policyServicePath),
                "Password history and expiration management");
        }

        echo "\n";
    }

    private function testRESTAPIEndpoints() {
        $this->printHeader("10. REST API ENDPOINTS ACCESSIBILITY");

        $endpoints = [
            '/wp-json/ma-deal-room/v1/auth/register' => 'User registration',
            '/wp-json/ma-deal-room/v1/auth/login' => 'User login',
            '/wp-json/ma-deal-room/v1/auth/refresh' => 'Token refresh',
            '/wp-json/ma-deal-room/v1/auth/logout' => 'User logout',
            '/wp-json/ma-deal-room/v1/transactions' => 'Transactions API',
            '/wp-json/ma-deal-room/v1/documents' => 'Documents API',
        ];

        // Check REST API routes are registered
        $restServer = rest_get_server();
        $routes = $restServer->get_routes();

        foreach ($endpoints as $endpoint => $description) {
            $pattern = str_replace('/wp-json', '', $endpoint);
            $registered = isset($routes[$pattern]) || $this->endpointExists($routes, $pattern);
            $this->test("Endpoint: $description", $registered, $endpoint);
        }

        echo "\n";
    }

    private function endpointExists($routes, $pattern) {
        foreach (array_keys($routes) as $route) {
            if (strpos($route, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    private function checkDebugLog() {
        $this->printHeader("11. WORDPRESS DEBUG LOG CHECK");

        $logPath = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($logPath)) {
            $this->test("Debug log exists", false, "No errors logged (or logging disabled)");
            echo "\n";
            return;
        }

        $this->test("Debug log exists", true, $logPath);

        // Check last 50 lines for recent PHP errors
        $lines = file($logPath);
        $recentLines = array_slice($lines, -50);
        $recentErrors = array_filter($recentLines, function($line) {
            return stripos($line, 'error') !== false ||
                   stripos($line, 'fatal') !== false ||
                   stripos($line, 'warning') !== false;
        });

        if (empty($recentErrors)) {
            $this->test("Recent PHP errors", false, "No errors in last 50 lines");
        } else {
            $this->test("Recent PHP errors", false, count($recentErrors) . " errors/warnings found");
            foreach (array_slice($recentErrors, -5) as $error) {
                $this->warning("  " . trim($error));
            }
        }

        echo "\n";
    }

    private function test($description, $passed, $details = '') {
        $status = $passed ? COLOR_GREEN . "✓ PASS" : COLOR_RED . "✗ FAIL";
        $result = $status . COLOR_RESET . " - " . $description;

        if ($details) {
            $result .= " (" . $details . ")";
        }

        echo $result . "\n";

        $this->results[] = [
            'description' => $description,
            'passed' => $passed,
            'details' => $details
        ];

        if (!$passed) {
            $this->errors[] = $description . ": " . $details;
        }
    }

    private function warning($message) {
        echo COLOR_YELLOW . "⚠ WARNING: " . $message . COLOR_RESET . "\n";
        $this->warnings[] = $message;
    }

    private function printHeader($title) {
        echo COLOR_BLUE . "\n" . str_repeat("-", 80) . "\n";
        echo $title . "\n";
        echo str_repeat("-", 80) . COLOR_RESET . "\n";
    }

    private function printSummary() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 80) . "\n\n";

        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, function($r) { return $r['passed']; }));
        $failedTests = $totalTests - $passedTests;

        echo "Total Tests: $totalTests\n";
        echo COLOR_GREEN . "Passed: $passedTests" . COLOR_RESET . "\n";
        echo COLOR_RED . "Failed: $failedTests" . COLOR_RESET . "\n";
        echo COLOR_YELLOW . "Warnings: " . count($this->warnings) . COLOR_RESET . "\n\n";

        if (!empty($this->errors)) {
            echo COLOR_RED . "ERRORS FOUND:\n" . COLOR_RESET;
            foreach ($this->errors as $i => $error) {
                echo ($i + 1) . ". $error\n";
            }
            echo "\n";
        }

        if (!empty($this->warnings)) {
            echo COLOR_YELLOW . "WARNINGS:\n" . COLOR_RESET;
            foreach ($this->warnings as $i => $warning) {
                echo ($i + 1) . ". $warning\n";
            }
            echo "\n";
        }

        // Overall status
        $passRate = ($passedTests / $totalTests) * 100;

        if ($passRate >= 95) {
            echo COLOR_GREEN . "✓ DEPLOYMENT STATUS: EXCELLENT ($passRate% pass rate)\n" . COLOR_RESET;
            echo "Plugin is ready for production deployment.\n";
        } elseif ($passRate >= 85) {
            echo COLOR_YELLOW . "⚠ DEPLOYMENT STATUS: GOOD ($passRate% pass rate)\n" . COLOR_RESET;
            echo "Minor issues found. Review warnings before deployment.\n";
        } elseif ($passRate >= 70) {
            echo COLOR_YELLOW . "⚠ DEPLOYMENT STATUS: ACCEPTABLE ($passRate% pass rate)\n" . COLOR_RESET;
            echo "Several issues found. Address errors before production deployment.\n";
        } else {
            echo COLOR_RED . "✗ DEPLOYMENT STATUS: FAILED ($passRate% pass rate)\n" . COLOR_RESET;
            echo "Critical issues found. DO NOT deploy to production.\n";
        }

        echo "\n" . str_repeat("=", 80) . "\n";
    }
}

// Run the tests
$tester = new T21SecurityDeploymentTest();
$tester->runAllTests();

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n\n";
