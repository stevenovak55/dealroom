<?php
/**
 * MLS REST Controller
 *
 * REST API endpoints for MLS integration operations.
 *
 * @package    MA_Deal_Room
 * @subpackage REST/Controllers
 * @since      1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Services\Integration\MLS\MLSImportService;
use MADealRoom\Services\Integration\MLS\MLSSubmissionService;
use MADealRoom\Services\Integration\MLS\MLSSyncService;
use MADealRoom\Services\Integration\MLS\MLSClientFactory;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class MLSController
 *
 * Handles MLS-related REST API endpoints.
 */
class MLSController extends BaseController {

    /**
     * MLS Import Service
     *
     * @var MLSImportService
     */
    private MLSImportService $import_service;

    /**
     * MLS Submission Service
     *
     * @var MLSSubmissionService
     */
    private MLSSubmissionService $submission_service;

    /**
     * MLS Sync Service
     *
     * @var MLSSyncService
     */
    private MLSSyncService $sync_service;

    /**
     * Constructor
     *
     * @param MLSImportService     $import_service     MLS import service
     * @param MLSSubmissionService $submission_service MLS submission service
     * @param MLSSyncService       $sync_service       MLS sync service
     */
    public function __construct(
        MLSImportService $import_service,
        MLSSubmissionService $submission_service,
        MLSSyncService $sync_service
    ) {
        parent::__construct();
        $this->namespace = 'ma-deal-room/v1';
        $this->rest_base = 'mls';
        $this->import_service = $import_service;
        $this->submission_service = $submission_service;
        $this->sync_service = $sync_service;
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes(): void {
        // Search MLS listings
        register_rest_route($this->namespace, '/' . $this->rest_base . '/search', [
            'methods' => 'POST',
            'callback' => [$this, 'search_listings'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'criteria' => [
                    'required' => true,
                    'type' => 'object',
                    'description' => 'Search criteria',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Get property details by MLS number
        register_rest_route($this->namespace, '/' . $this->rest_base . '/property/(?P<mls_number>[^/]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_property_details'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'mls_number' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'MLS listing number',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Import single property
        register_rest_route($this->namespace, '/' . $this->rest_base . '/import', [
            'methods' => 'POST',
            'callback' => [$this, 'import_property'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'mls_number' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'MLS listing number',
                ],
                'queue_photos' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Queue photo downloads',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Batch import properties
        register_rest_route($this->namespace, '/' . $this->rest_base . '/import-batch', [
            'methods' => 'POST',
            'callback' => [$this, 'import_batch'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'mls_numbers' => [
                    'required' => true,
                    'type' => 'array',
                    'description' => 'Array of MLS listing numbers',
                ],
                'queue_photos' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Queue photo downloads',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Get import statistics
        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_import_stats'],
            'permission_callback' => [$this, 'permission_callback'],
        ]);

        // Test MLS connection
        register_rest_route($this->namespace, '/' . $this->rest_base . '/test-connection', [
            'methods' => 'POST',
            'callback' => [$this, 'test_connection'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'provider_type' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'MLS provider type',
                ],
                'config' => [
                    'required' => true,
                    'type' => 'object',
                    'description' => 'MLS configuration',
                ],
            ],
        ]);

        // Get supported MLS providers
        register_rest_route($this->namespace, '/' . $this->rest_base . '/providers', [
            'methods' => 'GET',
            'callback' => [$this, 'get_providers'],
            'permission_callback' => [$this, 'permission_callback'],
        ]);

        // Get required fields for a provider
        register_rest_route($this->namespace, '/' . $this->rest_base . '/providers/(?P<provider_type>[^/]+)/fields', [
            'methods' => 'GET',
            'callback' => [$this, 'get_provider_fields'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'provider_type' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'MLS provider type',
                ],
            ],
        ]);

        // Submit transaction to MLS
        register_rest_route($this->namespace, '/' . $this->rest_base . '/submit', [
            'methods' => 'POST',
            'callback' => [$this, 'submit_listing'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'transaction_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Transaction ID',
                ],
                'queue_submission' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Queue submission as background job',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Get submission status
        register_rest_route($this->namespace, '/' . $this->rest_base . '/submission/(?P<transaction_id>\d+)/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_submission_status'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'transaction_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Transaction ID',
                ],
            ],
        ]);

        // Update MLS listing
        register_rest_route($this->namespace, '/' . $this->rest_base . '/update', [
            'methods' => 'POST',
            'callback' => [$this, 'update_listing'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'transaction_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Transaction ID',
                ],
                'updates' => [
                    'required' => true,
                    'type' => 'object',
                    'description' => 'Fields to update',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Update MLS listing status
        register_rest_route($this->namespace, '/' . $this->rest_base . '/status', [
            'methods' => 'POST',
            'callback' => [$this, 'update_status'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'transaction_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Transaction ID',
                ],
                'status' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'New status',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Withdraw listing from MLS
        register_rest_route($this->namespace, '/' . $this->rest_base . '/withdraw', [
            'methods' => 'POST',
            'callback' => [$this, 'withdraw_listing'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'transaction_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Transaction ID',
                ],
                'reason' => [
                    'required' => false,
                    'type' => 'string',
                    'default' => '',
                    'description' => 'Reason for withdrawal',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Sync transaction with MLS
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sync', [
            'methods' => 'POST',
            'callback' => [$this, 'sync_transaction'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'transaction_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Transaction ID',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Bulk sync all MLS transactions
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sync-all', [
            'methods' => 'POST',
            'callback' => [$this, 'sync_all_transactions'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'queue_sync' => [
                    'required' => false,
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Queue sync jobs',
                ],
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Get sync history for transaction
        register_rest_route($this->namespace, '/' . $this->rest_base . '/sync-history/(?P<transaction_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_sync_history'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'transaction_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Transaction ID',
                ],
                'limit' => [
                    'required' => false,
                    'type' => 'integer',
                    'default' => 50,
                    'description' => 'Max records to return',
                ],
            ],
        ]);

        // Schedule automatic sync
        register_rest_route($this->namespace, '/' . $this->rest_base . '/schedule-sync', [
            'methods' => 'POST',
            'callback' => [$this, 'schedule_auto_sync'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Unschedule automatic sync
        register_rest_route($this->namespace, '/' . $this->rest_base . '/unschedule-sync', [
            'methods' => 'POST',
            'callback' => [$this, 'unschedule_auto_sync'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'config_id' => [
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'MLS configuration ID',
                ],
            ],
        ]);

        // Get MLS configurations
        register_rest_route($this->namespace, '/' . $this->rest_base . '/config', [
            'methods' => 'GET',
            'callback' => [$this, 'get_configurations'],
            'permission_callback' => [$this, 'permission_callback'], // Allow authenticated users to view configs
        ]);

        // Get single MLS configuration
        register_rest_route($this->namespace, '/' . $this->rest_base . '/config/(?P<config_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_configuration'],
            'permission_callback' => [$this, 'permission_callback'], // Allow authenticated users to view configs
            'args' => [
                'config_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Configuration ID',
                ],
            ],
        ]);

        // Create MLS configuration
        register_rest_route($this->namespace, '/' . $this->rest_base . '/config', [
            'methods' => 'POST',
            'callback' => [$this, 'create_configuration'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'name' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'Configuration name',
                ],
                'provider_type' => [
                    'required' => true,
                    'type' => 'string',
                    'description' => 'MLS provider type',
                ],
                'credentials' => [
                    'required' => true,
                    'type' => 'object',
                    'description' => 'MLS credentials',
                ],
                'settings' => [
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Additional settings',
                ],
            ],
        ]);

        // Update MLS configuration
        register_rest_route($this->namespace, '/' . $this->rest_base . '/config/(?P<config_id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'update_configuration'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'config_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Configuration ID',
                ],
                'name' => [
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Configuration name',
                ],
                'credentials' => [
                    'required' => false,
                    'type' => 'object',
                    'description' => 'MLS credentials',
                ],
                'settings' => [
                    'required' => false,
                    'type' => 'object',
                    'description' => 'Additional settings',
                ],
                'is_active' => [
                    'required' => false,
                    'type' => 'boolean',
                    'description' => 'Active status',
                ],
            ],
        ]);

        // Delete MLS configuration
        register_rest_route($this->namespace, '/' . $this->rest_base . '/config/(?P<config_id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_configuration'],
            'permission_callback' => [$this, 'permission_callback'],
            'args' => [
                'config_id' => [
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Configuration ID',
                ],
            ],
        ]);
    }

    /**
     * Search MLS listings
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function search_listings(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $criteria = $request->get_param('criteria');
        $config_id = $request->get_param('config_id');

        $result = $this->import_service->searchListings($account_id, $criteria, $config_id);

        if (!$result['success']) {
            return new WP_Error(
                'mls_search_failed',
                $result['message'],
                ['status' => 400]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'listings' => $result['listings'],
                'count' => $result['count'],
            ],
            'message' => $result['message'],
        ], 200);
    }

    /**
     * Get property details by MLS number
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function get_property_details(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $mls_number = $request->get_param('mls_number');
        $config_id = $request->get_param('config_id');

        try {
            $client = MLSClientFactory::createFromDatabase($account_id, $config_id);

            if (!$client) {
                return new WP_Error(
                    'mls_config_not_found',
                    'No active MLS configuration found',
                    ['status' => 404]
                );
            }

            $listing = $client->getListingDetails($mls_number);

            if (!$listing) {
                return new WP_Error(
                    'mls_listing_not_found',
                    "Listing #{$mls_number} not found in MLS",
                    ['status' => 404]
                );
            }

            // Check if already imported
            $listing['is_imported'] = $this->import_service->isListingImported($account_id, $mls_number);
            $listing['existing_transaction_id'] = $this->import_service->getExistingTransactionId($account_id, $mls_number);

            return new WP_REST_Response([
                'success' => true,
                'listing' => $listing,
            ], 200);

        } catch (\Exception $e) {
            return new WP_Error(
                'mls_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Import property from MLS
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function import_property(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $user_id = get_current_user_id();
        $mls_number = $request->get_param('mls_number');
        $queue_photos = $request->get_param('queue_photos');
        $config_id = $request->get_param('config_id');

        $result = $this->import_service->importProperty(
            $account_id,
            $mls_number,
            $user_id,
            $queue_photos,
            $config_id
        );

        if (!$result['success']) {
            return new WP_Error(
                'mls_import_failed',
                $result['message'],
                ['status' => 400, 'errors' => $result['errors']]
            );
        }

        // Log the action
        $this->logEvent(
            'transaction',
            $result['transaction_id'],
            'mls_import',
            [],
            [
                'mls_number' => $mls_number,
            ],
            $account_id,
            $result['transaction_id']
        );

        return new WP_REST_Response($result, 201);
    }

    /**
     * Import multiple properties (batch import)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function import_batch(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $user_id = get_current_user_id();
        $mls_numbers = $request->get_param('mls_numbers');
        $queue_photos = $request->get_param('queue_photos');
        $config_id = $request->get_param('config_id');

        if (empty($mls_numbers) || !is_array($mls_numbers)) {
            return new WP_Error(
                'invalid_request',
                'mls_numbers must be a non-empty array',
                ['status' => 400]
            );
        }

        $result = $this->import_service->importMultiple(
            $account_id,
            $mls_numbers,
            $user_id,
            $queue_photos,
            $config_id
        );

        // Log the batch import
        $this->logEvent('mls_batch_import', "Batch imported {$result['imported']} properties", [
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
        ]);

        return new WP_REST_Response($result, 200);
    }

    /**
     * Get import statistics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_import_stats(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);

        $stats = $this->import_service->getImportStats($account_id);

        return new WP_REST_Response([
            'success' => true,
            'data' => $stats,
        ], 200);
    }

    /**
     * Test MLS connection
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function test_connection(WP_REST_Request $request) {
        $provider_type = $request->get_param('provider_type');
        $config = $request->get_param('config');

        $result = MLSClientFactory::testConnection($provider_type, $config);

        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get supported MLS providers
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_providers(WP_REST_Request $request) {
        $providers_data = MLSClientFactory::getSupportedProviders();

        // Transform associative array to indexed array with type field and fields
        $providers = [];
        foreach ($providers_data as $type => $provider) {
            // Get required fields for this provider
            $fields_data = MLSClientFactory::getRequiredFields($type);

            // Transform fields to match frontend expectations
            $fields = [];
            foreach ($fields_data as $field_name => $field_config) {
                $fields[] = [
                    'name' => $field_name,
                    'label' => $field_config['label'],
                    'type' => $field_config['type'],
                    'required' => $field_config['required'],
                    'options' => $field_config['options'] ?? null,
                ];
            }

            $providers[] = [
                'type' => $type,
                'name' => $provider['name'],
                'description' => $provider['description'],
                'fields' => $fields,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $providers,
        ], 200);
    }

    /**
     * Get required fields for a provider
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function get_provider_fields(WP_REST_Request $request) {
        $provider_type = $request->get_param('provider_type');

        $fields_data = MLSClientFactory::getRequiredFields($provider_type);

        if (empty($fields_data)) {
            return new WP_Error(
                'invalid_provider',
                "Unknown provider type: {$provider_type}",
                ['status' => 400]
            );
        }

        // Transform associative array to indexed array with name field
        $fields = [];
        foreach ($fields_data as $field_name => $field_config) {
            $fields[] = [
                'name' => $field_name,
                'label' => $field_config['label'],
                'type' => $field_config['type'],
                'required' => $field_config['required'],
                'description' => $field_config['description'] ?? null,
                'placeholder' => $field_config['placeholder'] ?? null,
                'default' => $field_config['default'] ?? null,
                'options' => $field_config['options'] ?? null,
            ];
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $fields,
        ], 200);
    }

    /**
     * Submit listing to MLS
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function submit_listing(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $user_id = get_current_user_id();
        $transaction_id = $request->get_param('transaction_id');
        $queue_submission = $request->get_param('queue_submission');
        $config_id = $request->get_param('config_id');

        $result = $this->submission_service->submitListing(
            $transaction_id,
            $account_id,
            $user_id,
            $queue_submission,
            $config_id
        );

        if (!$result['success']) {
            return new WP_Error(
                'mls_submission_failed',
                $result['message'],
                ['status' => 400, 'errors' => $result['errors'], 'warnings' => $result['warnings']]
            );
        }

        // Log the action
        $this->logEvent('mls_submit', "Submitted transaction #{$transaction_id} to MLS", [
            'transaction_id' => $transaction_id,
            'mls_number' => $result['mls_number'],
        ]);

        return new WP_REST_Response($result, 201);
    }

    /**
     * Get submission status for a transaction
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function get_submission_status(WP_REST_Request $request) {
        $transaction_id = $request->get_param('transaction_id');

        $status = $this->submission_service->getSubmissionStatus($transaction_id);

        return new WP_REST_Response([
            'success' => true,
            'status' => $status,
        ], 200);
    }

    /**
     * Update MLS listing
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function update_listing(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $transaction_id = $request->get_param('transaction_id');
        $updates = $request->get_param('updates');
        $config_id = $request->get_param('config_id');

        $result = $this->submission_service->updateListing(
            $transaction_id,
            $account_id,
            $updates,
            $config_id
        );

        if (!$result['success']) {
            return new WP_Error(
                'mls_update_failed',
                $result['message'],
                ['status' => 400, 'errors' => $result['errors']]
            );
        }

        // Log the action
        $this->logEvent('mls_update', "Updated MLS listing for transaction #{$transaction_id}", [
            'transaction_id' => $transaction_id,
            'updates' => array_keys($updates),
        ]);

        return new WP_REST_Response($result, 200);
    }

    /**
     * Update MLS listing status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function update_status(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $transaction_id = $request->get_param('transaction_id');
        $status = $request->get_param('status');
        $config_id = $request->get_param('config_id');

        $result = $this->submission_service->updateStatus(
            $transaction_id,
            $account_id,
            $status,
            $config_id
        );

        if (!$result['success']) {
            return new WP_Error(
                'mls_status_update_failed',
                $result['message'],
                ['status' => 400]
            );
        }

        // Log the action
        $this->logEvent('mls_status_update', "Updated MLS status for transaction #{$transaction_id} to {$status}", [
            'transaction_id' => $transaction_id,
            'status' => $status,
        ]);

        return new WP_REST_Response($result, 200);
    }

    /**
     * Withdraw listing from MLS
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function withdraw_listing(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $transaction_id = $request->get_param('transaction_id');
        $reason = $request->get_param('reason');
        $config_id = $request->get_param('config_id');

        $result = $this->submission_service->withdrawListing(
            $transaction_id,
            $account_id,
            $reason,
            $config_id
        );

        if (!$result['success']) {
            return new WP_Error(
                'mls_withdrawal_failed',
                $result['message'],
                ['status' => 400]
            );
        }

        // Log the action
        $this->logEvent('mls_withdraw', "Withdrew transaction #{$transaction_id} from MLS", [
            'transaction_id' => $transaction_id,
            'reason' => $reason,
        ]);

        return new WP_REST_Response($result, 200);
    }

    /**
     * Sync transaction with MLS
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function sync_transaction(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $transaction_id = $request->get_param('transaction_id');
        $config_id = $request->get_param('config_id');

        $result = $this->sync_service->syncTransaction(
            $transaction_id,
            $account_id,
            'manual',
            $config_id
        );

        if (!$result['success']) {
            return new WP_Error(
                'mls_sync_failed',
                $result['message'],
                ['status' => 400]
            );
        }

        $this->logEvent('mls_sync', "Synced transaction #{$transaction_id} with MLS", [
            'transaction_id' => $transaction_id,
            'has_changes' => $result['has_changes'] ?? false,
            'changes_count' => count($result['changes'] ?? []),
        ]);

        return new WP_REST_Response($result, 200);
    }

    /**
     * Sync all MLS transactions
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function sync_all_transactions(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $queue_sync = $request->get_param('queue_sync');
        $config_id = $request->get_param('config_id');

        $result = $this->sync_service->syncAllTransactions(
            $account_id,
            $queue_sync,
            $config_id
        );

        if (!$result['success']) {
            return new WP_Error(
                'mls_bulk_sync_failed',
                $result['message'],
                ['status' => 400]
            );
        }

        $this->logEvent('mls_bulk_sync', "Bulk sync initiated", [
            'account_id' => $account_id,
            'queued' => $queue_sync,
            'stats' => $result['stats'] ?? [],
        ]);

        return new WP_REST_Response($result, 200);
    }

    /**
     * Get sync history for transaction
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function get_sync_history(WP_REST_Request $request) {
        $transaction_id = $request->get_param('transaction_id');
        $limit = $request->get_param('limit');

        $history = $this->sync_service->getSyncHistory($transaction_id, $limit);

        return new WP_REST_Response([
            'success' => true,
            'history' => $history,
            'count' => count($history),
        ], 200);
    }

    /**
     * Schedule automatic sync
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function schedule_auto_sync(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $config_id = $request->get_param('config_id');

        $scheduled = $this->sync_service->scheduleAutoSync($account_id, $config_id);

        if (!$scheduled) {
            return new WP_Error(
                'mls_schedule_failed',
                'Failed to schedule automatic sync',
                ['status' => 500]
            );
        }

        $this->logEvent('mls_schedule_sync', "Scheduled automatic MLS sync", [
            'account_id' => $account_id,
            'config_id' => $config_id,
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Automatic sync scheduled successfully',
        ], 200);
    }

    /**
     * Unschedule automatic sync
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function unschedule_auto_sync(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $config_id = $request->get_param('config_id');

        $unscheduled = $this->sync_service->unscheduleAutoSync($account_id, $config_id);

        if (!$unscheduled) {
            return new WP_Error(
                'mls_unschedule_failed',
                'Failed to unschedule automatic sync',
                ['status' => 500]
            );
        }

        $this->logEvent('mls_unschedule_sync', "Unscheduled automatic MLS sync", [
            'account_id' => $account_id,
            'config_id' => $config_id,
        ]);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Automatic sync unscheduled successfully',
        ], 200);
    }

    /**
     * Get all MLS configurations
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function get_configurations(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);

        global $wpdb;
        $configs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, account_id, name, provider_type, credentials, settings, is_active, last_sync_at, created_at, updated_at
                FROM {$wpdb->prefix}ma_deal_mls_config
                WHERE account_id = %d
                ORDER BY name ASC",
                $account_id
            ),
            ARRAY_A
        );

        // Decode JSON fields and decrypt credentials
        foreach ($configs as &$config) {
            // Decrypt credentials
            if (!empty($config['credentials'])) {
                $config['credentials'] = $this->decrypt_credentials($config['credentials']);
            } else {
                $config['credentials'] = [];
            }

            // Decode settings
            if (!empty($config['settings'])) {
                $config['settings'] = json_decode($config['settings'], true);
            } else {
                $config['settings'] = [];
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $configs,
        ], 200);
    }

    /**
     * Get single MLS configuration
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function get_configuration(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $config_id = $request->get_param('config_id');

        global $wpdb;
        $config = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, account_id, name, provider_type, settings, is_active, created_at, updated_at
                FROM {$wpdb->prefix}ma_deal_mls_config
                WHERE id = %d AND account_id = %d",
                $config_id,
                $account_id
            ),
            ARRAY_A
        );

        if (!$config) {
            return new WP_Error(
                'mls_config_not_found',
                'Configuration not found',
                ['status' => 404]
            );
        }

        // Decode settings JSON
        if (!empty($config['settings'])) {
            $config['settings'] = json_decode($config['settings'], true);
        } else {
            $config['settings'] = [];
        }

        // Don't expose actual credentials, just indicate if they exist
        $config['has_credentials'] = !empty($config['credentials']);
        unset($config['credentials']);

        return new WP_REST_Response([
            'success' => true,
            'configuration' => $config,
        ], 200);
    }

    /**
     * Create MLS configuration
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function create_configuration(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $name = $request->get_param('name');
        $provider_type = $request->get_param('provider_type');
        $credentials = $request->get_param('credentials');
        $settings = $request->get_param('settings') ?? [];

        // Encrypt credentials
        $encrypted_credentials = $this->encrypt_credentials($credentials);

        global $wpdb;
        $result = $wpdb->insert(
            "{$wpdb->prefix}ma_deal_mls_config",
            [
                'account_id' => $account_id,
                'name' => $name,
                'provider_type' => $provider_type,
                'credentials' => $encrypted_credentials,
                'settings' => !empty($settings) ? wp_json_encode($settings) : null,
                'is_active' => 1,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error(
                'mls_config_create_failed',
                'Failed to create configuration',
                ['status' => 500]
            );
        }

        $config_id = $wpdb->insert_id;

        $this->logEvent(
            'mls_config',
            $config_id,
            'created',
            [],
            [
                'name' => $name,
                'provider_type' => $provider_type,
            ],
            $account_id
        );

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Configuration created successfully',
            'config_id' => $config_id,
        ], 201);
    }

    /**
     * Update MLS configuration
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function update_configuration(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $config_id = $request->get_param('config_id');

        // Build update data
        $update_data = [];
        $formats = [];

        if ($request->has_param('name')) {
            $update_data['name'] = $request->get_param('name');
            $formats[] = '%s';
        }

        if ($request->has_param('credentials')) {
            $update_data['credentials'] = $this->encrypt_credentials($request->get_param('credentials'));
            $formats[] = '%s';
        }

        if ($request->has_param('settings')) {
            $update_data['settings'] = wp_json_encode($request->get_param('settings'));
            $formats[] = '%s';
        }

        if ($request->has_param('is_active')) {
            $update_data['is_active'] = $request->get_param('is_active') ? 1 : 0;
            $formats[] = '%d';
        }

        if (empty($update_data)) {
            return new WP_Error(
                'mls_config_no_changes',
                'No changes provided',
                ['status' => 400]
            );
        }

        $update_data['updated_at'] = current_time('mysql');
        $formats[] = '%s';

        global $wpdb;
        $updated = $wpdb->update(
            "{$wpdb->prefix}ma_deal_mls_config",
            $update_data,
            [
                'id' => $config_id,
                'account_id' => $account_id,
            ],
            $formats,
            ['%d', '%d']
        );

        if ($updated === false) {
            return new WP_Error(
                'mls_config_update_failed',
                'Failed to update configuration',
                ['status' => 500]
            );
        }

        $this->logEvent(
            'mls_config',
            $config_id,
            'updated',
            [],
            [
                'fields_updated' => array_keys($update_data),
            ],
            $account_id
        );

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Configuration updated successfully',
        ], 200);
    }

    /**
     * Delete MLS configuration
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function delete_configuration(WP_REST_Request $request) {
        $account_id = $this->get_account_id($request);
        $config_id = $request->get_param('config_id');

        global $wpdb;
        $deleted = $wpdb->delete(
            "{$wpdb->prefix}ma_deal_mls_config",
            [
                'id' => $config_id,
                'account_id' => $account_id,
            ],
            ['%d', '%d']
        );

        if ($deleted === false || $deleted === 0) {
            return new WP_Error(
                'mls_config_delete_failed',
                'Configuration not found or failed to delete',
                ['status' => 404]
            );
        }

        $this->logEvent(
            'mls_config',
            $config_id,
            'deleted',
            [],
            [],
            $account_id
        );

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Configuration deleted successfully',
        ], 200);
    }

    /**
     * Encrypt credentials for storage
     *
     * @param array $credentials Credentials to encrypt
     * @return string Encrypted credentials
     */
    private function encrypt_credentials(array $credentials): string {
        $json = wp_json_encode($credentials);

        // Use WordPress AUTH_KEY constant to match decrypt method
        $key = defined('AUTH_KEY') ? AUTH_KEY : '';

        if (empty($key)) {
            throw new \Exception('WordPress AUTH_KEY not defined');
        }

        // Generate random IV
        $iv = openssl_random_pseudo_bytes(16);

        // Encrypt with binary hash (true parameter) to match decrypt
        $encrypted = openssl_encrypt(
            $json,
            'AES-256-CBC',
            hash('sha256', $key, true),
            0,
            $iv
        );

        if ($encrypted === false) {
            throw new \Exception('Encryption failed');
        }

        // Prepend IV to encrypted data, then base64 encode (matches decrypt expectations)
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt credentials from storage
     *
     * @param string $encrypted_data Encrypted credentials
     * @return array Decrypted credentials
     */
    private function decrypt_credentials(string $encrypted_data): array {
        // Use WordPress AUTH_KEY constant
        $key = defined('AUTH_KEY') ? AUTH_KEY : '';

        if (empty($key)) {
            throw new \Exception('WordPress AUTH_KEY not defined');
        }

        // Base64 decode the data
        $data = base64_decode($encrypted_data, true);

        if ($data === false) {
            throw new \Exception('Invalid encrypted data');
        }

        // Extract IV (first 16 bytes) and encrypted string
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        // Decrypt with binary hash (true parameter)
        $decrypted = openssl_decrypt(
            $encrypted,
            'AES-256-CBC',
            hash('sha256', $key, true),
            0,
            $iv
        );

        if ($decrypted === false) {
            throw new \Exception('Decryption failed');
        }

        // Decode JSON
        $credentials = json_decode($decrypted, true);

        if ($credentials === null) {
            throw new \Exception('Invalid credentials JSON');
        }

        return $credentials;
    }

    /**
     * Get account ID from request
     *
     * @param WP_REST_Request $request Request object
     * @return int Account ID
     */
    private function get_account_id(WP_REST_Request $request): int {
        // For now, use the first account associated with the user
        // In a multi-tenant system, this would come from request context
        $user_id = get_current_user_id();

        global $wpdb;
        $table = $wpdb->prefix . 'ma_deal_accounts';

        $account_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE owner_user_id = %d LIMIT 1",
            $user_id
        ));

        return $account_id ? (int) $account_id : 0;
    }

}
