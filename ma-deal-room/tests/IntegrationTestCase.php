<?php
/**
 * Integration Test Case Base Class
 *
 * Provides infrastructure for testing WordPress REST API endpoints with database.
 *
 * @package MADealRoom\Tests
 */

namespace MADealRoom\Tests;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Base class for integration tests
 *
 * NOTE: Integration tests require WordPress test environment.
 * Set WP_TESTS_DIR environment variable to enable.
 */
abstract class IntegrationTestCase extends TestCase
{
    /**
     * Current test user ID
     *
     * @var int
     */
    protected $test_user_id;

    /**
     * Current test account ID
     *
     * @var int
     */
    protected $test_account_id;

    /**
     * Authentication token for API requests
     *
     * @var string
     */
    protected $auth_token;

    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Check if WordPress test environment is available
        if (!$this->isWordPressTestEnvironmentAvailable()) {
            $this->markTestSkipped(
                'Integration tests require WordPress test environment. ' .
                'Set WP_TESTS_DIR environment variable and run install-wp-tests.sh'
            );
        }

        // Start database transaction
        $this->startTransaction();

        // Create test user and account
        $this->createTestUser();
        $this->createTestAccount();
    }

    /**
     * Cleanup after each test
     */
    protected function tearDown(): void
    {
        // Rollback database transaction
        $this->rollbackTransaction();

        // Clear authentication
        $this->auth_token = null;
        $this->test_user_id = null;
        $this->test_account_id = null;

        parent::tearDown();
    }

    /**
     * Check if WordPress test environment is available
     *
     * @return bool
     */
    protected function isWordPressTestEnvironmentAvailable(): bool
    {
        return getenv('WP_TESTS_DIR') && class_exists('WP_REST_Request');
    }

    /**
     * Create an authenticated REST API request
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $route REST API route
     * @param array $params Request parameters
     * @param array $headers Additional headers
     * @return WP_REST_Request
     */
    protected function createAuthenticatedRequest(
        string $method,
        string $route,
        array $params = [],
        array $headers = []
    ): WP_REST_Request {
        $request = new WP_REST_Request($method, $route);

        // Add authentication header
        if ($this->auth_token) {
            $request->set_header('Authorization', 'Bearer ' . $this->auth_token);
        }

        // Add custom headers
        foreach ($headers as $key => $value) {
            $request->set_header($key, $value);
        }

        // Set parameters
        if ($method === 'GET') {
            $request->set_query_params($params);
        } else {
            $request->set_body_params($params);
        }

        return $request;
    }

    /**
     * Execute a REST API request
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    protected function executeRequest(WP_REST_Request $request)
    {
        $server = rest_get_server();
        return $server->dispatch($request);
    }

    /**
     * Create test user with specified role
     *
     * @param string $role User role (default: subscriber)
     * @return int User ID
     */
    protected function createTestUser(string $role = 'subscriber'): int
    {
        if (!function_exists('wp_insert_user')) {
            return 0;
        }

        $user_data = [
            'user_login' => 'test_user_' . time(),
            'user_email' => 'test' . time() . '@example.com',
            'user_pass' => 'Test123!@#',
            'role' => $role,
        ];

        $user_id = wp_insert_user($user_data);

        if (!is_wp_error($user_id)) {
            $this->test_user_id = $user_id;
            wp_set_current_user($user_id);
        }

        return $user_id;
    }

    /**
     * Create test account
     *
     * @return int Account ID
     */
    protected function createTestAccount(): int
    {
        if (!$this->test_user_id) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_accounts';

        $wpdb->insert($table, [
            'account_id' => $this->randomUuid(),
            'owner_user_id' => $this->test_user_id,
            'business_name' => 'Test Account',
            'subscription_tier' => 'professional',
            'subscription_status' => 'active',
            'subscription_start_date' => current_time('mysql'),
            'subscription_end_date' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'created_at' => current_time('mysql'),
        ]);

        $this->test_account_id = $wpdb->insert_id;

        return $this->test_account_id;
    }

    /**
     * Generate authentication token for test user
     *
     * @return string JWT token
     */
    protected function generateAuthToken(): string
    {
        // This would use the actual AuthService to generate a token
        // For now, return a placeholder
        $this->auth_token = 'test_token_' . time();
        return $this->auth_token;
    }

    /**
     * Login as test user and get authentication token
     *
     * @return string Authentication token
     */
    protected function loginAsTestUser(): string
    {
        if (!$this->test_user_id) {
            $this->createTestUser();
        }

        wp_set_current_user($this->test_user_id);
        return $this->generateAuthToken();
    }

    /**
     * Assert REST API response has expected status code
     *
     * @param int $expected_code
     * @param WP_REST_Response|WP_Error $response
     * @param string $message
     */
    protected function assertResponseStatus(
        int $expected_code,
        $response,
        string $message = ''
    ): void {
        if (is_wp_error($response)) {
            $this->fail('Response is WP_Error: ' . $response->get_error_message());
        }

        $actual_code = $response->get_status();
        $this->assertEquals(
            $expected_code,
            $actual_code,
            $message ?: "Expected status code $expected_code, got $actual_code"
        );
    }

    /**
     * Assert REST API response contains expected data
     *
     * @param array $expected_keys
     * @param WP_REST_Response $response
     * @param string $message
     */
    protected function assertResponseHasKeys(
        array $expected_keys,
        WP_REST_Response $response,
        string $message = ''
    ): void {
        $data = $response->get_data();

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey(
                $key,
                $data,
                $message ?: "Response missing expected key: $key"
            );
        }
    }

    /**
     * Assert REST API response is error
     *
     * @param WP_REST_Response|WP_Error $response
     * @param string $expected_code Error code to check
     */
    protected function assertResponseIsError(
        $response,
        string $expected_code = ''
    ): void {
        if (is_wp_error($response)) {
            if ($expected_code) {
                $this->assertEquals($expected_code, $response->get_error_code());
            }
            return;
        }

        if ($response instanceof WP_REST_Response) {
            $status = $response->get_status();
            $this->assertGreaterThanOrEqual(
                400,
                $status,
                "Expected error response (4xx or 5xx), got $status"
            );
        }
    }

    /**
     * Start database transaction
     */
    protected function startTransaction(): void
    {
        global $wpdb;
        if ($wpdb) {
            $wpdb->query('START TRANSACTION');
        }
    }

    /**
     * Rollback database transaction
     */
    protected function rollbackTransaction(): void
    {
        global $wpdb;
        if ($wpdb) {
            $wpdb->query('ROLLBACK');
        }
    }

    /**
     * Create test transaction
     *
     * @param array $overrides
     * @return int Transaction ID
     */
    protected function createTestTransaction(array $overrides = []): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_transactions';

        $defaults = [
            'transaction_id' => $this->randomUuid(),
            'account_id' => $this->test_account_id,
            'property_address' => '123 Test St, Boston, MA 02101',
            'property_type' => 'sfh',
            'transaction_side' => 'buyer',
            'status' => 'under_agreement',
            'created_by_user_id' => $this->test_user_id,
            'created_at' => current_time('mysql'),
        ];

        $data = array_merge($defaults, $overrides);
        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Create test task
     *
     * @param int $transaction_id
     * @param array $overrides
     * @return int Task ID
     */
    protected function createTestTask(int $transaction_id, array $overrides = []): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_tasks';

        $defaults = [
            'task_id' => $this->randomUuid(),
            'transaction_id' => $transaction_id,
            'title' => 'Test Task',
            'description' => 'Test task description',
            'status' => 'pending',
            'owner_role' => 'agent',
            'created_at' => current_time('mysql'),
        ];

        $data = array_merge($defaults, $overrides);
        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }
}
