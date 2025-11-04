<?php
/**
 * CRM Sync REST API Controller
 *
 * Handles REST API endpoints for CRM synchronization operations.
 * Provides endpoints for syncing contacts, deals, and monitoring sync status.
 *
 * @package MA_Deal_Room\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Services\Integration\CRM\ContactSyncService;
use MADealRoom\Services\Integration\CRM\DealSyncService;
use MADealRoom\Services\Integration\CRM\CRMClientFactory;
use MADealRoom\Repositories\CRMConfigRepository;

class CRMSyncController {
    private $namespace = 'ma-deal/v1';
    private $contact_sync;
    private $deal_sync;
    private $config_repository;
    private $factory;

    public function __construct() {
        $this->contact_sync = new ContactSyncService();
        $this->deal_sync = new DealSyncService();
        $this->config_repository = new CRMConfigRepository();
        $this->factory = CRMClientFactory::getInstance();
    }

    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        // Contact sync endpoints
        register_rest_route($this->namespace, '/crm/sync/contacts', [
            'methods' => 'POST',
            'callback' => [$this, 'sync_contacts'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Deal sync endpoints
        register_rest_route($this->namespace, '/crm/sync/deals', [
            'methods' => 'POST',
            'callback' => [$this, 'sync_deals'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Sync transaction status change to CRM
        register_rest_route($this->namespace, '/crm/sync/transaction/(?P<id>\d+)/status', [
            'methods' => 'POST',
            'callback' => [$this, 'sync_transaction_status'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Get sync status
        register_rest_route($this->namespace, '/crm/sync/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sync_status'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Configure CRM connection
        register_rest_route($this->namespace, '/crm/configure', [
            'methods' => 'POST',
            'callback' => [$this, 'configure_crm'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Test CRM connection
        register_rest_route($this->namespace, '/crm/test-connection', [
            'methods' => 'POST',
            'callback' => [$this, 'test_connection'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Get CRM configurations
        register_rest_route($this->namespace, '/crm/configurations', [
            'methods' => 'GET',
            'callback' => [$this, 'get_configurations'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Delete CRM configuration
        register_rest_route($this->namespace, '/crm/configuration/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_configuration'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // OAuth callback endpoint - Requires authentication
        register_rest_route($this->namespace, '/crm/oauth/callback', [
            'methods' => 'GET',
            'callback' => [$this, 'oauth_callback'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);
    }

    /**
     * Sync contacts endpoint
     *
     * POST /wp-json/ma-deal/v1/crm/sync/contacts
     * Body: {
     *   "provider": "salesforce|hubspot",
     *   "direction": "from_crm|to_crm|bidirectional",
     *   "limit": 100
     * }
     */
    public function sync_contacts(\WP_REST_Request $request) {
        try {
            $account_id = $this->get_current_account_id();
            $provider = $request->get_param('provider');
            $direction = $request->get_param('direction') ?? 'bidirectional';
            $limit = (int)($request->get_param('limit') ?? 100);

            if (!$provider) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Provider is required',
                ], 400);
            }

            $result = [
                'success' => true,
                'from_crm' => null,
                'to_crm' => null,
            ];

            // Sync from CRM to Deal Room
            if (in_array($direction, ['from_crm', 'bidirectional'])) {
                $result['from_crm'] = $this->contact_sync->syncFromCRM($account_id, $provider, [
                    'limit' => $limit,
                ]);
            }

            // Sync from Deal Room to CRM
            if (in_array($direction, ['to_crm', 'bidirectional'])) {
                $result['to_crm'] = $this->contact_sync->syncToCRM($account_id, $provider, [
                    'limit' => $limit,
                ]);
            }

            return new \WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            error_log('CRM sync error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status endpoint
     *
     * GET /wp-json/ma-deal/v1/crm/sync/status?provider=salesforce
     */
    public function get_sync_status(\WP_REST_Request $request) {
        try {
            $account_id = $this->get_current_account_id();
            $provider = $request->get_param('provider');

            if (!$provider) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Provider is required',
                ], 400);
            }

            $stats = $this->contact_sync->getSyncStats($account_id, $provider);
            $config = $this->config_repository->getByAccountAndProvider($account_id, $provider);

            return new \WP_REST_Response([
                'success' => true,
                'provider' => $provider,
                'sync_enabled' => $config['sync_enabled'] ?? false,
                'contacts' => $stats,
                'last_sync' => $config['last_sync_at'] ?? null,
            ], 200);
        } catch (\Exception $e) {
            error_log('Get sync status error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Configure CRM connection endpoint
     *
     * POST /wp-json/ma-deal/v1/crm/configure
     * Body: {
     *   "provider": "salesforce|hubspot",
     *   "credentials": {...},
     *   "sync_enabled": true,
     *   "sync_contacts": true,
     *   "sync_deals": true,
     *   "field_mapping": {...}
     * }
     */
    public function configure_crm(\WP_REST_Request $request) {
        try {
            $account_id = $this->get_current_account_id();
            $provider = $request->get_param('provider');

            if (!$provider) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Provider is required',
                ], 400);
            }

            if (!CRMClientFactory::isProviderSupported($provider)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Unsupported provider: ' . $provider,
                ], 400);
            }

            $config = [
                'credentials' => $request->get_param('credentials') ?? [],
                'instance_url' => $request->get_param('instance_url'),
                'refresh_token' => $request->get_param('refresh_token'),
                'sync_enabled' => $request->get_param('sync_enabled') ?? true,
                'sync_contacts' => $request->get_param('sync_contacts') ?? true,
                'sync_deals' => $request->get_param('sync_deals') ?? true,
                'sync_activities' => $request->get_param('sync_activities') ?? true,
                'field_mapping' => $request->get_param('field_mapping'),
                'stage_mapping' => $request->get_param('stage_mapping'),
                'sync_direction' => $request->get_param('sync_direction') ?? 'bidirectional',
                'conflict_resolution' => $request->get_param('conflict_resolution') ?? 'last_write_wins',
            ];

            $config_id = $this->config_repository->upsert($account_id, $provider, $config);

            if (!$config_id) {
                throw new \Exception('Failed to save CRM configuration');
            }

            // Clear cached client instance
            CRMClientFactory::clearCache($account_id, $provider);

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'CRM configuration saved successfully',
                'config_id' => $config_id,
            ], 200);
        } catch (\Exception $e) {
            error_log('CRM configure error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test CRM connection endpoint
     *
     * POST /wp-json/ma-deal/v1/crm/test-connection
     * Body: {"provider": "salesforce"}
     */
    public function test_connection(\WP_REST_Request $request) {
        try {
            $account_id = $this->get_current_account_id();
            $provider = $request->get_param('provider');

            if (!$provider) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Provider is required',
                ], 400);
            }

            $result = $this->factory->testConnection($account_id, $provider);

            return new \WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            error_log('CRM test connection error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all CRM configurations for current account
     *
     * GET /wp-json/ma-deal/v1/crm/configurations
     */
    public function get_configurations(\WP_REST_Request $request) {
        try {
            $account_id = $this->get_current_account_id();

            $configs = $this->config_repository->getByAccount($account_id);

            // Remove sensitive data before returning
            foreach ($configs as &$config) {
                unset($config['credentials']);
                unset($config['refresh_token']);
                unset($config['credentials_decrypted']);
                unset($config['refresh_token_decrypted']);
            }

            return new \WP_REST_Response([
                'success' => true,
                'configurations' => $configs,
                'supported_providers' => CRMClientFactory::getSupportedProviders(),
            ], 200);
        } catch (\Exception $e) {
            error_log('Get CRM configurations error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete CRM configuration
     *
     * DELETE /wp-json/ma-deal/v1/crm/configuration/123
     */
    public function delete_configuration(\WP_REST_Request $request) {
        try {
            $config_id = (int)$request->get_param('id');

            // Verify config belongs to current account
            $config = $this->config_repository->getById($config_id);
            if (!$config || $config['account_id'] != $this->get_current_account_id()) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Configuration not found or access denied',
                ], 404);
            }

            $deleted = $this->config_repository->delete($config_id);

            if (!$deleted) {
                throw new \Exception('Failed to delete configuration');
            }

            // Clear cached client instance
            CRMClientFactory::clearCache($config['account_id'], $config['provider_type']);

            return new \WP_REST_Response([
                'success' => true,
                'message' => 'Configuration deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            error_log('Delete CRM configuration error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * OAuth callback endpoint
     *
     * GET /wp-json/ma-deal/v1/crm/oauth/callback?code=xxx&state=xxx&provider=salesforce
     */
    public function oauth_callback(\WP_REST_Request $request) {
        try {
            $code = $request->get_param('code');
            $state = $request->get_param('state');
            $provider = $request->get_param('provider');
            $error = $request->get_param('error');

            if ($error) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'OAuth error: ' . ($request->get_param('error_description') ?? $error),
                ], 400);
            }

            if (!$code || !$provider || !$state) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Missing required parameters',
                ], 400);
            }

            // Verify state parameter to prevent CSRF attacks
            if (!$this->validate_oauth_state($state)) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Invalid or expired state parameter - CSRF protection',
                ], 403);
            }

            // Clear the state from session after validation
            $this->clear_oauth_state($state);

            // Return success page with code for client-side handling
            return new \WP_REST_Response([
                'success' => true,
                'provider' => $provider,
                'code' => $code,
                'message' => 'OAuth authentication successful. Please complete setup.',
            ], 200);
        } catch (\Exception $e) {
            error_log('OAuth callback error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate OAuth state token for CSRF protection
     *
     * @return string Generated state token
     */
    public function generate_oauth_state(): string {
        // Generate random state token
        $state = bin2hex(random_bytes(32));

        // Store in transient (WordPress temporary option) for 10 minutes
        set_transient('ma_deal_oauth_state_' . $state, get_current_user_id(), 10 * MINUTE_IN_SECONDS);

        return $state;
    }

    /**
     * Validate OAuth state token
     *
     * @param string $state State token to validate
     * @return bool True if state is valid and not expired
     */
    private function validate_oauth_state(string $state): bool {
        if (empty($state)) {
            return false;
        }

        // Check if state exists in transient
        $user_id = get_transient('ma_deal_oauth_state_' . $state);

        if ($user_id === false) {
            return false; // State not found or expired
        }

        // Verify state belongs to current user
        if ((int)$user_id !== get_current_user_id()) {
            return false;
        }

        return true;
    }

    /**
     * Clear OAuth state token after validation
     *
     * @param string $state State token to clear
     */
    private function clear_oauth_state(string $state): void {
        delete_transient('ma_deal_oauth_state_' . $state);
    }

    /**
     * Sync deals endpoint
     *
     * POST /wp-json/ma-deal/v1/crm/sync/deals
     * Body: {
     *   "provider": "salesforce|hubspot",
     *   "direction": "from_crm|to_crm|bidirectional",
     *   "limit": 100
     * }
     */
    public function sync_deals(\WP_REST_Request $request) {
        try {
            $account_id = $this->get_current_account_id();
            $provider = $request->get_param('provider');
            $direction = $request->get_param('direction') ?? 'bidirectional';
            $limit = (int)($request->get_param('limit') ?? 100);

            if (!$provider) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Provider is required',
                ], 400);
            }

            $result = [
                'success' => true,
                'from_crm' => null,
                'to_crm' => null,
            ];

            // Sync from CRM to Deal Room
            if (in_array($direction, ['from_crm', 'bidirectional'])) {
                $result['from_crm'] = $this->deal_sync->syncFromCRM($account_id, $provider, [
                    'limit' => $limit,
                ]);
            }

            // Sync from Deal Room to CRM
            if (in_array($direction, ['to_crm', 'bidirectional'])) {
                $result['to_crm'] = $this->deal_sync->syncToCRM($account_id, $provider, [
                    'limit' => $limit,
                ]);
            }

            return new \WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            error_log('CRM deal sync error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync transaction status change to CRM
     *
     * POST /wp-json/ma-deal/v1/crm/sync/transaction/123/status
     * Body: {"status": "closing"}
     */
    public function sync_transaction_status(\WP_REST_Request $request) {
        try {
            $transaction_id = (int)$request->get_param('id');
            $new_status = $request->get_param('status');

            if (!$new_status) {
                return new \WP_REST_Response([
                    'success' => false,
                    'message' => 'Status is required',
                ], 400);
            }

            $results = $this->deal_sync->syncStatusChange($transaction_id, $new_status);

            $all_success = true;
            foreach ($results as $result) {
                if (!$result['success']) {
                    $all_success = false;
                    break;
                }
            }

            return new \WP_REST_Response([
                'success' => $all_success,
                'results' => $results,
                'message' => $all_success ? 'Status synced to all CRMs' : 'Some CRM syncs failed',
            ], $all_success ? 200 : 207); // 207 = Multi-Status
        } catch (\Exception $e) {
            error_log('CRM status sync error: ' . $e->getMessage());
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if current user has admin permission
     */
    public function check_admin_permission(): bool {
        return current_user_can('manage_options');
    }

    /**
     * Get current account ID from session/user
     *
     * @return int Account ID from current user or 0 if not found
     * @throws \Exception If user is not authenticated or has no account
     */
    private function get_current_account_id(): int {
        // Get current WordPress user
        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            throw new \Exception('User not authenticated');
        }

        // Get account ID from user meta or session
        $account_id = (int)get_user_meta($current_user_id, 'ma_deal_account_id', true);

        if (!$account_id) {
            // Try to get from session if available
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['ma_deal_account_id'])) {
                $account_id = (int)$_SESSION['ma_deal_account_id'];
            }
        }

        if (!$account_id) {
            throw new \Exception('User has no associated account');
        }

        return $account_id;
    }
}
