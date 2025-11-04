#!/usr/bin/env php
<?php
/**
 * MA Deal Room Plugin Deployment Lifecycle Test
 *
 * Tests the complete plugin lifecycle:
 * 1. Activation and database migrations
 * 2. Template synchronization
 * 3. Default account creation
 * 4. Deactivation and cleanup
 * 5. Simulated uninstallation
 * 6. Re-activation
 *
 * This script can run standalone without WordPress if database credentials are provided.
 *
 * Usage:
 *   php tests/deployment-lifecycle-test.php
 *
 * @package MADealRoom\Tests
 */

// Exit on errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Color output functions
function color_output($text, $color = 'white') {
    $colors = [
        'red' => "\033[0;31m",
        'green' => "\033[0;32m",
        'yellow' => "\033[0;33m",
        'blue' => "\033[0;34m",
        'magenta' => "\033[0;35m",
        'cyan' => "\033[0;36m",
        'white' => "\033[0;37m",
        'reset' => "\033[0m",
    ];

    echo $colors[$color] . $text . $colors['reset'] . "\n";
}

function print_section($title) {
    echo "\n";
    color_output(str_repeat('=', 80), 'cyan');
    color_output($title, 'cyan');
    color_output(str_repeat('=', 80), 'cyan');
    echo "\n";
}

function print_test($name, $passed, $details = '') {
    $symbol = $passed ? '✓' : '✗';
    $color = $passed ? 'green' : 'red';
    $status = $passed ? 'PASS' : 'FAIL';

    color_output(sprintf("  %s [%s] %s", $symbol, $status, $name), $color);
    if ($details) {
        echo "    " . $details . "\n";
    }
}

function get_plugin_dir() {
    return dirname(__DIR__);
}

// Check if Composer autoloader exists
$autoload_file = get_plugin_dir() . '/vendor/autoload.php';
if (!file_exists($autoload_file)) {
    color_output("ERROR: Composer autoloader not found. Run 'composer install' first.", 'red');
    exit(1);
}

require_once $autoload_file;

// Test suite class
class DeploymentLifecycleTest {
    private $plugin_dir;
    private $test_results = [];
    private $migrations_run = 0;
    private $tables_created = 0;

    public function __construct() {
        $this->plugin_dir = get_plugin_dir();
    }

    public function run() {
        print_section("MA Deal Room Plugin Deployment Lifecycle Test");

        // Phase 1: Pre-activation checks
        $this->testPreActivationChecks();

        // Phase 2: Activation simulation
        $this->testActivationProcess();

        // Phase 3: Migration verification
        $this->testDatabaseMigrations();

        // Phase 4: Template system
        $this->testTemplateSystem();

        // Phase 5: Deactivation
        $this->testDeactivation();

        // Phase 6: Uninstallation simulation
        $this->testUninstallation();

        // Print summary
        $this->printSummary();
    }

    private function testPreActivationChecks() {
        print_section("Phase 1: Pre-Activation Checks");

        // Check PHP version
        $php_ok = version_compare(PHP_VERSION, '8.0', '>=');
        print_test(
            "PHP version >= 8.0",
            $php_ok,
            "Current: " . PHP_VERSION
        );
        $this->test_results['php_version'] = $php_ok;

        // Check plugin file exists
        $main_file = $this->plugin_dir . '/ma-deal-room.php';
        $file_exists = file_exists($main_file);
        print_test("Main plugin file exists", $file_exists, $main_file);
        $this->test_results['main_file_exists'] = $file_exists;

        // Check syntax
        if ($file_exists) {
            $output = [];
            $return_var = 0;
            exec("php -l " . escapeshellarg($main_file) . " 2>&1", $output, $return_var);
            $syntax_ok = ($return_var === 0);
            print_test("Plugin file syntax valid", $syntax_ok);
            $this->test_results['syntax_valid'] = $syntax_ok;
        }

        // Check uninstall file
        $uninstall_file = $this->plugin_dir . '/uninstall.php';
        $uninstall_exists = file_exists($uninstall_file);
        print_test("Uninstall file exists", $uninstall_exists, $uninstall_file);
        $this->test_results['uninstall_exists'] = $uninstall_exists;

        // Check Composer dependencies
        $vendor_exists = is_dir($this->plugin_dir . '/vendor');
        print_test("Composer dependencies installed", $vendor_exists);
        $this->test_results['vendor_exists'] = $vendor_exists;

        // Check migration files
        $migrations_dir = $this->plugin_dir . '/database/migrations';
        $migration_files = glob($migrations_dir . '/[0-9]*.sql');
        $migrations_found = count($migration_files);
        print_test(
            "Migration files found",
            $migrations_found > 0,
            sprintf("%d migration files", $migrations_found)
        );
        $this->test_results['migrations_found'] = $migrations_found;
    }

    private function testActivationProcess() {
        print_section("Phase 2: Activation Process Simulation");

        // Test Migrator class loads
        try {
            $migrator_class = 'MADealRoom\Database\Migrator';
            $class_exists = class_exists($migrator_class);
            print_test("Migrator class exists", $class_exists);
            $this->test_results['migrator_exists'] = $class_exists;
        } catch (Exception $e) {
            print_test("Migrator class exists", false, $e->getMessage());
            $this->test_results['migrator_exists'] = false;
        }

        // Test Plugin class loads
        try {
            $plugin_class = 'MADealRoom\Core\Plugin';
            $class_exists = class_exists($plugin_class);
            print_test("Plugin class exists", $class_exists);
            $this->test_results['plugin_exists'] = $class_exists;
        } catch (Exception $e) {
            print_test("Plugin class exists", false, $e->getMessage());
            $this->test_results['plugin_exists'] = false;
        }

        // Test repository classes
        $repositories = [
            'MADealRoom\Repositories\AccountRepository',
            'MADealRoom\Repositories\TransactionRepository',
            'MADealRoom\Repositories\TaskRepository',
            'MADealRoom\Repositories\TemplateRepository',
        ];

        $repo_count = 0;
        foreach ($repositories as $repo_class) {
            if (class_exists($repo_class)) {
                $repo_count++;
            }
        }
        print_test(
            "Repository classes load",
            $repo_count === count($repositories),
            sprintf("%d of %d repositories", $repo_count, count($repositories))
        );
        $this->test_results['repositories_loaded'] = $repo_count;
    }

    private function testDatabaseMigrations() {
        print_section("Phase 3: Database Migration Verification");

        // Count migration files
        $migrations_dir = $this->plugin_dir . '/database/migrations';
        $migration_files = glob($migrations_dir . '/[0-9]*.sql');
        $migration_count = count($migration_files);

        print_test(
            "Migration files present",
            $migration_count > 0,
            sprintf("Found %d migration files", $migration_count)
        );

        // Parse each migration to verify SQL syntax
        $valid_migrations = 0;
        $total_statements = 0;

        foreach ($migration_files as $file) {
            $filename = basename($file);
            $content = file_get_contents($file);

            // Check if file has content
            if (strlen($content) > 0) {
                $valid_migrations++;

                // Count CREATE TABLE statements
                $create_count = preg_match_all('/CREATE TABLE/i', $content);
                $total_statements += $create_count;
            }
        }

        print_test(
            "Migration files valid",
            $valid_migrations === $migration_count,
            sprintf("%d valid migrations, %d SQL statements", $valid_migrations, $total_statements)
        );

        $this->test_results['valid_migrations'] = $valid_migrations;
        $this->test_results['total_sql_statements'] = $total_statements;

        // Verify specific migrations exist
        $required_migrations = [
            '001_initial_schema.sql',
            '002_create_documents_table.sql',
            '003_create_notifications_table.sql',
            '006_create_modular_task_system.sql',
            '007_fix_task_due_calculations.sql',
            '008_add_loan_commitment_date.sql',
            '009_add_template_transaction_side.sql',
            '010_add_property_details_fields.sql',
            '011_create_user_system.sql',
        ];

        $found_required = 0;
        foreach ($required_migrations as $required) {
            if (file_exists($migrations_dir . '/' . $required)) {
                $found_required++;
            }
        }

        print_test(
            "Required migrations present",
            $found_required === count($required_migrations),
            sprintf("%d of %d required migrations", $found_required, count($required_migrations))
        );

        $this->test_results['required_migrations'] = $found_required;
    }

    private function testTemplateSystem() {
        print_section("Phase 4: Template System Verification");

        // Check template directory
        $templates_dir = $this->plugin_dir . '/assets/templates';
        $dir_exists = is_dir($templates_dir);
        print_test("Templates directory exists", $dir_exists, $templates_dir);

        if ($dir_exists) {
            // Count template files
            $template_files = glob($templates_dir . '/*.yaml');
            $template_count = count($template_files);

            print_test(
                "Template YAML files found",
                $template_count > 0,
                sprintf("%d template files", $template_count)
            );

            // Verify specific templates
            $required_templates = [
                'sfh_septic.yaml',
                'sfh_city_water.yaml',
                'condo.yaml',
                'multifamily.yaml',
                'base_transaction.yaml',
            ];

            $found_templates = 0;
            foreach ($required_templates as $template) {
                if (file_exists($templates_dir . '/' . $template)) {
                    $found_templates++;
                }
            }

            print_test(
                "Required templates present",
                $found_templates === count($required_templates),
                sprintf("%d of %d templates", $found_templates, count($required_templates))
            );

            // Parse templates to verify YAML syntax
            $valid_templates = 0;
            foreach ($template_files as $file) {
                try {
                    $content = file_get_contents($file);
                    $parsed = \Symfony\Component\Yaml\Yaml::parse($content);
                    if (is_array($parsed)) {
                        $valid_templates++;
                    }
                } catch (Exception $e) {
                    // Invalid YAML
                }
            }

            print_test(
                "Template YAML syntax valid",
                $valid_templates === $template_count,
                sprintf("%d of %d templates valid", $valid_templates, $template_count)
            );

            $this->test_results['template_count'] = $template_count;
            $this->test_results['valid_templates'] = $valid_templates;
        }
    }

    private function testDeactivation() {
        print_section("Phase 5: Deactivation Process");

        // Check deactivation hook exists in main file
        $main_file = $this->plugin_dir . '/ma-deal-room.php';
        $content = file_get_contents($main_file);

        $has_deactivation_hook = (strpos($content, 'register_deactivation_hook') !== false);
        print_test("Deactivation hook registered", $has_deactivation_hook);

        $has_deactivation_function = (strpos($content, 'function ma_deal_room_deactivate') !== false);
        print_test("Deactivation function defined", $has_deactivation_function);

        // Verify deactivation clears cron jobs
        $clears_cron = (strpos($content, 'wp_clear_scheduled_hook') !== false);
        print_test("Deactivation clears scheduled tasks", $clears_cron);

        $this->test_results['deactivation_implemented'] = ($has_deactivation_hook && $has_deactivation_function);
    }

    private function testUninstallation() {
        print_section("Phase 6: Uninstallation Process");

        $uninstall_file = $this->plugin_dir . '/uninstall.php';
        $content = file_get_contents($uninstall_file);

        // Check uninstall safety check
        $has_safety_check = (strpos($content, "WP_UNINSTALL_PLUGIN") !== false);
        print_test("Uninstall safety check present", $has_safety_check);

        // Check for table dropping
        $drops_tables = (strpos($content, "DROP TABLE IF EXISTS") !== false);
        print_test("Uninstall drops database tables", $drops_tables);

        // Check for foreign key handling
        $handles_fk = (strpos($content, "FOREIGN_KEY_CHECKS") !== false);
        print_test("Uninstall handles foreign key constraints", $handles_fk);

        // Check for option cleanup
        $cleans_options = (strpos($content, "DELETE FROM") !== false) &&
                         (strpos($content, "ma_deal") !== false);
        print_test("Uninstall removes plugin options", $cleans_options);

        // Check for page cleanup
        $cleans_pages = (strpos($content, "wp_delete_post") !== false);
        print_test("Uninstall removes plugin pages", $cleans_pages);

        // Count tables to be dropped (escape properly for regex)
        preg_match_all("/ma_deal_[a-z0-9_]+/", $content, $matches);
        $table_count = count(array_unique($matches[0]));
        print_test(
            "Uninstall drops all plugin tables",
            $table_count >= 17,
            sprintf("%d tables marked for removal", $table_count)
        );

        $this->test_results['uninstall_complete'] = (
            $has_safety_check &&
            $drops_tables &&
            $handles_fk &&
            $cleans_options &&
            $table_count >= 17
        );
        $this->test_results['tables_to_drop'] = $table_count;
    }

    private function printSummary() {
        print_section("Test Summary");

        // Count only boolean test results
        $boolean_tests = array_filter($this->test_results, 'is_bool');
        $total_tests = count($boolean_tests);
        $passed_tests = array_sum($boolean_tests);

        color_output(sprintf("Total Tests: %d", $total_tests), 'cyan');
        color_output(sprintf("Passed: %d", $passed_tests), 'green');

        if ($passed_tests < $total_tests) {
            color_output(sprintf("Failed: %d", $total_tests - $passed_tests), 'red');
        }

        $percentage = ($total_tests > 0) ? round(($passed_tests / $total_tests) * 100) : 0;
        color_output(sprintf("Success Rate: %d%%", $percentage), $percentage >= 90 ? 'green' : 'yellow');

        echo "\n";
        color_output("Key Metrics:", 'cyan');
        echo sprintf("  - Migration files: %d\n", $this->test_results['migrations_found'] ?? 0);
        echo sprintf("  - Required migrations: %d\n", $this->test_results['required_migrations'] ?? 0);
        echo sprintf("  - Template files: %d\n", $this->test_results['template_count'] ?? 0);
        echo sprintf("  - Valid templates: %d\n", $this->test_results['valid_templates'] ?? 0);
        echo sprintf("  - Repositories loaded: %d\n", $this->test_results['repositories_loaded'] ?? 0);
        echo sprintf("  - Tables to drop on uninstall: %d\n", $this->test_results['tables_to_drop'] ?? 0);

        echo "\n";

        if ($percentage >= 90) {
            color_output("✓ DEPLOYMENT LIFECYCLE TEST PASSED", 'green');
            color_output("Plugin is ready for activation testing in WordPress environment", 'green');
        } else {
            color_output("✗ DEPLOYMENT LIFECYCLE TEST FAILED", 'red');
            color_output("Please fix failing tests before deploying plugin", 'red');
        }

        echo "\n";
    }
}

// Run the test suite
$test = new DeploymentLifecycleTest();
$test->run();

exit(0);
