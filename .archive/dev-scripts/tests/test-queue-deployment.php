<?php
/**
 * Queue System Deployment Test Script
 *
 * Comprehensive testing for T2.4 Queue System Implementation
 * Tests: Activation, Migration, Service Registration, REST API, WP-CLI, Job Processing, Deactivation, Uninstallation
 *
 * @package MADealRoom
 * @since 1.0.0
 */

// Load WordPress
require_once(__DIR__ . '/wp-load.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper functions
function print_header($text) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "  " . strtoupper($text) . "\n";
    echo str_repeat('=', 80) . "\n\n";
}

function print_subheader($text) {
    echo "\n" . str_repeat('-', 80) . "\n";
    echo "  " . $text . "\n";
    echo str_repeat('-', 80) . "\n";
}

function print_result($test_name, $passed, $details = '') {
    $status = $passed ? '[PASS]' : '[FAIL]';
    $color = $passed ? "\033[0;32m" : "\033[0;31m";
    $reset = "\033[0m";

    echo "{$color}{$status}{$reset} {$test_name}";
    if ($details) {
        echo " - {$details}";
    }
    echo "\n";

    return $passed;
}

function print_info($message) {
    echo "[INFO] {$message}\n";
}

// Test results tracking
$test_results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'tests' => [],
];

function record_test($category, $test_name, $passed, $details = '') {
    global $test_results;
    $test_results['total']++;
    if ($passed) {
        $test_results['passed']++;
    } else {
        $test_results['failed']++;
    }
    $test_results['tests'][] = [
        'category' => $category,
        'name' => $test_name,
        'passed' => $passed,
        'details' => $details,
    ];
    print_result($test_name, $passed, $details);
}

// Start testing
print_header('MA Deal Room - Queue System Deployment Tests');
echo "Test Date: " . date('Y-m-d H:i:s') . "\n";
echo "WordPress Version: " . get_bloginfo('version') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n\n";

// ==============================================================================
// TEST 1: PLUGIN ACTIVATION & MIGRATION
// ==============================================================================
print_header('Test 1: Plugin Activation & Migration');

global $wpdb;

// Check if plugin is active
$is_active = is_plugin_active('ma-deal-room/ma-deal-room.php');
record_test('activation', 'Plugin is active', $is_active);

// Check migration 019 exists
$migration_file = __DIR__ . '/ma-deal-room/database/migrations/019_create_job_queue_table.sql';
$migration_exists = file_exists($migration_file);
record_test('migration', 'Migration 019 file exists', $migration_exists, $migration_file);

// Check migration was applied
$migration_table = $wpdb->prefix . 'ma_deal_migrations';
$migration_applied = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$migration_table} WHERE migration = %s",
        '019_create_job_queue_table.sql'
    )
);
record_test('migration', 'Migration 019 applied to database', $migration_applied > 0,
    $migration_applied ? 'Applied' : 'Not applied');

// Check job queue table exists
$queue_table = $wpdb->prefix . 'ma_deal_job_queue';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$queue_table}'") === $queue_table;
record_test('migration', 'Job queue table created', $table_exists, $queue_table);

if ($table_exists) {
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE {$queue_table}", ARRAY_A);
    $column_names = array_column($columns, 'Field');

    $required_columns = [
        'id', 'job_name', 'job_type', 'priority', 'payload', 'status',
        'attempts', 'max_attempts', 'scheduled_for', 'started_at',
        'completed_at', 'failed_at', 'error_message', 'created_at', 'updated_at'
    ];

    $missing_columns = array_diff($required_columns, $column_names);
    record_test('migration', 'All required columns present', empty($missing_columns),
        empty($missing_columns) ? count($required_columns) . ' columns' : 'Missing: ' . implode(', ', $missing_columns));

    // Check indexes
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$queue_table}", ARRAY_A);
    $index_names = array_unique(array_column($indexes, 'Key_name'));

    $required_indexes = ['PRIMARY', 'idx_status', 'idx_priority', 'idx_scheduled', 'idx_job_type', 'idx_processing'];
    $has_indexes = count(array_intersect($required_indexes, $index_names)) >= 4;
    record_test('migration', 'Table indexes created', $has_indexes,
        count($index_names) . ' indexes found');
}

// ==============================================================================
// TEST 2: SERVICE REGISTRATION
// ==============================================================================
print_header('Test 2: Service Container Registration');

try {
    $plugin = MADealRoom\Core\Plugin::instance();
    $container = $plugin->container();

    // Test JobQueueRepository
    $job_queue_repo = $container->get('job_queue_repository');
    record_test('services', 'JobQueueRepository registered', $job_queue_repo instanceof MADealRoom\Repositories\JobQueueRepository);

    // Test JobQueueService
    $queue_service = $container->get('job_queue_service');
    record_test('services', 'JobQueueService registered', $queue_service instanceof MADealRoom\Services\Queue\JobQueueService);

    // Test QueueCleanupService
    $cleanup_service = $container->get('queue_cleanup_service');
    record_test('services', 'QueueCleanupService registered', $cleanup_service instanceof MADealRoom\Services\Queue\QueueCleanupService);

    // Test QueueController (REST)
    $rest_controllers = $container->get('rest_controllers');
    $queue_controller_exists = false;
    foreach ($rest_controllers as $controller) {
        if ($controller instanceof MADealRoom\REST\Controllers\QueueController) {
            $queue_controller_exists = true;
            break;
        }
    }
    record_test('services', 'QueueController registered in REST API', $queue_controller_exists);

} catch (Exception $e) {
    record_test('services', 'Service container accessible', false, $e->getMessage());
}

// ==============================================================================
// TEST 3: REST API ENDPOINTS
// ==============================================================================
print_header('Test 3: REST API Endpoints');

$rest_server = rest_get_server();
$routes = $rest_server->get_routes();
$namespace = 'ma-deal-room/v1';

$expected_endpoints = [
    '/ma-deal-room/v1/queue/stats' => 'GET',
    '/ma-deal-room/v1/queue/jobs' => 'GET',
    '/ma-deal-room/v1/queue/failed' => 'GET',
    '/ma-deal-room/v1/queue/retry/(?P<id>\d+)' => 'POST',
    '/ma-deal-room/v1/queue/bulk-retry' => 'POST',
    '/ma-deal-room/v1/queue/counts' => 'GET',
    '/ma-deal-room/v1/queue/health' => 'GET',
];

foreach ($expected_endpoints as $endpoint => $method) {
    $route_exists = isset($routes[$endpoint]);
    $method_supported = false;

    if ($route_exists) {
        foreach ($routes[$endpoint] as $route_config) {
            if (in_array($method, $route_config['methods'], true)) {
                $method_supported = true;
                break;
            }
        }
    }

    $endpoint_name = str_replace('/ma-deal-room/v1/', '', $endpoint);
    record_test('rest-api', "{$method} {$endpoint_name}", $route_exists && $method_supported);
}

// Test health endpoint (public, no auth)
print_subheader('Testing Health Endpoint');
$health_request = new WP_REST_Request('GET', '/ma-deal-room/v1/queue/health');
$health_response = rest_do_request($health_request);
$health_data = $health_response->get_data();

record_test('rest-api', 'Health endpoint accessible', $health_response->get_status() === 200);
record_test('rest-api', 'Health endpoint returns status', isset($health_data['status']));
record_test('rest-api', 'Health endpoint returns metrics', isset($health_data['metrics']));

// ==============================================================================
// TEST 4: WP-CLI COMMANDS
// ==============================================================================
print_header('Test 4: WP-CLI Commands');

if (class_exists('WP_CLI')) {
    $cli_commands = WP_CLI::get_runner()->get_command_registry();

    record_test('wp-cli', 'WP-CLI available', true);

    // Check if ma-deal command is registered
    // Note: This is a simplified check - actual command registration happens through WP-CLI
    $queue_command_exists = class_exists('MADealRoom\CLI\QueueCommand');
    record_test('wp-cli', 'QueueCommand class exists', $queue_command_exists);

} else {
    record_test('wp-cli', 'WP-CLI available', false, 'WP-CLI not loaded in this context');
}

// ==============================================================================
// TEST 5: JOB QUEUE OPERATIONS
// ==============================================================================
print_header('Test 5: Job Queue Operations');

if (isset($queue_service)) {
    // Test enqueue operation
    try {
        // Create a test job
        $test_job = new class implements MADealRoom\Services\Queue\JobInterface {
            public function getName(): string { return 'Test Job'; }
            public function getType(): string { return 'test.job'; }
            public function getPriority(): string { return 'normal'; }
            public function getMaxAttempts(): int { return 3; }
            public function getPayload(): array { return ['test' => 'data']; }
            public function handle(): bool { return true; }
            public function shouldRetry(\Exception $e): bool { return true; }
            public function getRetryDelay(int $attempt): int { return $attempt * 60; }
            public static function fromPayload(array $payload): self { return new self(); }
        };

        // Register handler
        $queue_service->registerHandler('test.job', get_class($test_job));

        // Enqueue job
        $job_id = $queue_service->dispatch($test_job);
        record_test('queue-ops', 'Job enqueue operation', $job_id !== false, "Job ID: {$job_id}");

        // Verify job was inserted
        $job_data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$queue_table} WHERE id = %d", $job_id),
            ARRAY_A
        );
        record_test('queue-ops', 'Job stored in database', !empty($job_data));
        record_test('queue-ops', 'Job status is pending', $job_data['status'] === 'pending');
        record_test('queue-ops', 'Job priority set correctly', $job_data['priority'] === 'normal');

        // Test stats
        $stats = $queue_service->getStats();
        record_test('queue-ops', 'Queue stats accessible', is_array($stats));
        record_test('queue-ops', 'Pending count in stats', isset($stats['pending']) && $stats['pending'] >= 1);

        // Test counts by status
        $counts = $queue_service->countByStatus();
        record_test('queue-ops', 'Count by status works', is_array($counts) && isset($counts['pending']));

        // Clean up test job
        $wpdb->delete($queue_table, ['id' => $job_id], ['%d']);

    } catch (Exception $e) {
        record_test('queue-ops', 'Job queue operations', false, $e->getMessage());
    }
}

// ==============================================================================
// TEST 6: JOB HANDLERS REGISTRATION
// ==============================================================================
print_header('Test 6: Job Handler Registration');

if (isset($queue_service)) {
    // Check if default job handlers are registered
    $handlers = $queue_service->getHandlers();

    record_test('job-handlers', 'Handlers array accessible', is_array($handlers));

    // Expected job types from T2.4.2
    $expected_handlers = [
        'email.send' => 'MADealRoom\Services\Queue\Jobs\SendEmailJob',
        'task.reminder' => 'MADealRoom\Services\Queue\Jobs\TaskReminderJob',
        'report.generate' => 'MADealRoom\Services\Queue\Jobs\ReportGenerationJob',
    ];

    foreach ($expected_handlers as $job_type => $job_class) {
        $handler_registered = isset($handlers[$job_type]);
        $class_exists = $handler_registered && class_exists($handlers[$job_type]);
        record_test('job-handlers', "Handler '{$job_type}' registered", $handler_registered);
        if ($handler_registered) {
            record_test('job-handlers', "Handler class '{$job_class}' exists", $class_exists, $handlers[$job_type]);
        }
    }
}

// ==============================================================================
// TEST 7: CLEANUP SCHEDULER
// ==============================================================================
print_header('Test 7: Cleanup Scheduler & WP-Cron Integration');

// Check if cleanup cron is scheduled
$cleanup_hook = 'ma_deal_queue_cleanup';
$next_scheduled = wp_next_scheduled($cleanup_hook);
record_test('cleanup', 'Cleanup cron job scheduled', $next_scheduled !== false,
    $next_scheduled ? 'Next run: ' . date('Y-m-d H:i:s', $next_scheduled) : 'Not scheduled');

// Check if cleanup service has init method
if (isset($cleanup_service)) {
    record_test('cleanup', 'Cleanup service has init method', method_exists($cleanup_service, 'init'));
    record_test('cleanup', 'Cleanup service has manualCleanup method', method_exists($cleanup_service, 'manualCleanup'));
}

// ==============================================================================
// TEST 8: PLUGIN DEACTIVATION
// ==============================================================================
print_header('Test 8: Plugin Deactivation');

// We won't actually deactivate, just check the deactivation hook is registered
$deactivation_hook_file = __DIR__ . '/ma-deal-room/ma-deal-room.php';
$plugin_code = file_get_contents($deactivation_hook_file);

$has_deactivation_hook = strpos($plugin_code, 'register_deactivation_hook') !== false;
record_test('deactivation', 'Deactivation hook registered', $has_deactivation_hook);

$clears_queue_cron = strpos($plugin_code, "wp_clear_scheduled_hook('ma_deal_room_process_queue')") !== false ||
                     strpos($plugin_code, "wp_clear_scheduled_hook('ma_deal_queue_cleanup')") !== false;
record_test('deactivation', 'Deactivation clears scheduled crons', $clears_queue_cron);

// ==============================================================================
// TEST 9: UNINSTALL CLEANUP
// ==============================================================================
print_header('Test 9: Uninstall Cleanup Preparation');

$uninstall_file = __DIR__ . '/ma-deal-room/uninstall.php';
$uninstall_exists = file_exists($uninstall_file);
record_test('uninstall', 'Uninstall file exists', $uninstall_exists, $uninstall_file);

if ($uninstall_exists) {
    $uninstall_code = file_get_contents($uninstall_file);

    // Check if job queue table is in the uninstall list
    $includes_job_queue = strpos($uninstall_code, 'ma_deal_job_queue') !== false;
    record_test('uninstall', 'Uninstall includes job queue table', $includes_job_queue);

    // Check foreign key handling
    $has_fk_disable = strpos($uninstall_code, 'SET FOREIGN_KEY_CHECKS=0') !== false;
    record_test('uninstall', 'Uninstall disables foreign key checks', $has_fk_disable);

    // Check options cleanup
    $clears_options = strpos($uninstall_code, "ma_deal_room_%") !== false;
    record_test('uninstall', 'Uninstall clears plugin options', $clears_options);
}

// ==============================================================================
// TEST 10: DEPLOYMENT READINESS
// ==============================================================================
print_header('Test 10: Deployment Readiness Checks');

// Check process-queue.php exists
$process_queue_file = __DIR__ . '/process-queue.php';
$process_queue_exists = file_exists($process_queue_file);
record_test('deployment', 'process-queue.php exists', $process_queue_exists, $process_queue_file);

// Check deployment documentation
$deployment_doc = __DIR__ . '/ma-deal-room/deployment/QUEUE_DEPLOYMENT.md';
$deployment_doc_exists = file_exists($deployment_doc);
record_test('deployment', 'Queue deployment guide exists', $deployment_doc_exists, $deployment_doc);

// Check systemd service file
$systemd_service = __DIR__ . '/ma-deal-room/deployment/systemd/ma-deal-queue.service';
$systemd_exists = file_exists($systemd_service);
record_test('deployment', 'systemd service file exists', $systemd_exists, $systemd_service);

// Check migration 020 (retry mechanism enhancement)
$migration_020 = __DIR__ . '/ma-deal-room/database/migrations/020_add_retry_columns.sql';
$migration_020_exists = file_exists($migration_020);
record_test('deployment', 'Migration 020 (retry columns) exists', $migration_020_exists);

if ($migration_020_exists && $table_exists) {
    $next_retry_column = $wpdb->get_var(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = '{$queue_table}'
         AND COLUMN_NAME = 'next_retry_at'"
    );
    record_test('deployment', 'Retry delay column (next_retry_at) exists',
        $next_retry_column === 'next_retry_at');
}

// ==============================================================================
// FINAL SUMMARY
// ==============================================================================
print_header('Test Summary');

echo "Total Tests: {$test_results['total']}\n";
echo "Passed: \033[0;32m{$test_results['passed']}\033[0m\n";
echo "Failed: \033[0;31m{$test_results['failed']}\033[0m\n";
echo "Success Rate: " . round(($test_results['passed'] / $test_results['total']) * 100, 2) . "%\n\n";

// Group results by category
$by_category = [];
foreach ($test_results['tests'] as $test) {
    $category = $test['category'];
    if (!isset($by_category[$category])) {
        $by_category[$category] = ['total' => 0, 'passed' => 0, 'failed' => 0];
    }
    $by_category[$category]['total']++;
    if ($test['passed']) {
        $by_category[$category]['passed']++;
    } else {
        $by_category[$category]['failed']++;
    }
}

print_subheader('Results by Category');
foreach ($by_category as $category => $stats) {
    $pass_rate = round(($stats['passed'] / $stats['total']) * 100, 1);
    $color = $pass_rate === 100.0 ? "\033[0;32m" : ($pass_rate >= 80 ? "\033[1;33m" : "\033[0;31m");
    echo sprintf(
        "%s%-20s%s: %d/%d tests passed (%s%.1f%%%s)\n",
        str_pad(ucfirst($category), 20),
        '',
        '',
        $stats['passed'],
        $stats['total'],
        $color,
        $pass_rate,
        "\033[0m"
    );
}

// List failed tests
if ($test_results['failed'] > 0) {
    print_subheader('Failed Tests');
    foreach ($test_results['tests'] as $test) {
        if (!$test['passed']) {
            echo "  [{$test['category']}] {$test['name']}";
            if ($test['details']) {
                echo " - {$test['details']}";
            }
            echo "\n";
        }
    }
}

echo "\n";
print_header('Deployment Test Complete');

$overall_status = $test_results['failed'] === 0 ? 'READY FOR PRODUCTION' : 'ISSUES FOUND - REVIEW REQUIRED';
$status_color = $test_results['failed'] === 0 ? "\033[0;32m" : "\033[0;31m";
echo "{$status_color}{$overall_status}\033[0m\n\n";

// Exit with appropriate code
exit($test_results['failed'] === 0 ? 0 : 1);
