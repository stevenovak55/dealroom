<?php
/**
 * DocuSign REST API Controller
 *
 * @package    MADealRoom
 * @subpackage REST\Controllers
 * @since      1.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use MADealRoom\Services\Integration\DocuSign\DocuSignClient;
use MADealRoom\Services\Integration\DocuSign\DocuSignTemplateService;
use MADealRoom\Services\Integration\DocuSign\EnvelopeService;
use MADealRoom\Services\Integration\DocuSign\WebhookHandler;
use MADealRoom\Services\Integration\DocuSign\DocumentDownloadService;
use MADealRoom\Repositories\DocuSignConfigRepository;

/**
 * Class DocuSignController
 *
 * Handles DocuSign API endpoints
 */
class DocuSignController extends BaseController {
    /**
     * Route base
     */
    protected $rest_base = 'docusign';

    /**
     * DocuSign client
     *
     * @var DocuSignClient|null
     */
    private ?DocuSignClient $client = null;

    /**
     * Template service
     *
     * @var DocuSignTemplateService|null
     */
    private ?DocuSignTemplateService $template_service = null;

    /**
     * Envelope service
     *
     * @var EnvelopeService|null
     */
    private ?EnvelopeService $envelope_service = null;

    /**
     * Config repository
     *
     * @var DocuSignConfigRepository
     */
    private DocuSignConfigRepository $config_repository;

    /**
     * Webhook handler
     *
     * @var WebhookHandler|null
     */
    private ?WebhookHandler $webhook_handler = null;

    /**
     * Document download service
     *
     * @var DocumentDownloadService|null
     */
    private ?DocumentDownloadService $download_service = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->config_repository = new DocuSignConfigRepository();
    }

    /**
     * Register routes
     */
    public function register_routes(): void {
        // Configuration endpoints
        register_rest_route($this->namespace, '/' . $this->rest_base . '/config', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_config'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'save_config'],
                'permission_callback' => [$this, 'check_admin_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/test-connection', [
            'methods' => 'POST',
            'callback' => [$this, 'test_connection'],
            'permission_callback' => [$this, 'check_admin_permission'],
        ]);

        // Template endpoints
        register_rest_route($this->namespace, '/' . $this->rest_base . '/templates', [
            'methods' => 'GET',
            'callback' => [$this, 'list_templates'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/templates/(?P<template_id>[a-f0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_template'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/templates/preview', [
            'methods' => 'POST',
            'callback' => [$this, 'preview_template'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Envelope endpoints
        register_rest_route($this->namespace, '/' . $this->rest_base . '/envelopes', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'list_envelopes'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_envelope'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/envelopes/(?P<envelope_id>[a-f0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_envelope'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/envelopes/(?P<envelope_id>[a-f0-9-]+)/send', [
            'methods' => 'POST',
            'callback' => [$this, 'send_envelope'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/envelopes/(?P<envelope_id>[a-f0-9-]+)/void', [
            'methods' => 'POST',
            'callback' => [$this, 'void_envelope'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/envelopes/(?P<envelope_id>[a-f0-9-]+)/resend', [
            'methods' => 'POST',
            'callback' => [$this, 'resend_envelope'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/envelopes/(?P<envelope_id>[a-f0-9-]+)/recipients', [
            'methods' => 'GET',
            'callback' => [$this, 'get_envelope_recipients'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/envelopes/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_envelope_stats'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Webhook endpoint (public, authenticated via HMAC)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true', // Public endpoint, validated via HMAC
        ]);
    }

    /**
     * Get configuration
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_config(WP_REST_Request $request) {
        try {
            $account_id = $this->get_user_account_id();
            $config = $this->config_repository->getByAccountId($account_id);

            if (!$config) {
                return new WP_REST_Response([
                    'configured' => false,
                    'message' => 'DocuSign not configured',
                ], 200);
            }

            // Don't return the private key
            unset($config['private_key']);

            return new WP_REST_Response([
                'configured' => true,
                'config' => $config,
            ], 200);
        } catch (\Exception $e) {
            return new WP_Error('config_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Save configuration
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function save_config(WP_REST_Request $request) {
        try {
            $account_id = $this->get_user_account_id();

            $config_data = [
                'account_id' => $account_id,
                'integration_key' => $request->get_param('integration_key'),
                'user_id' => $request->get_param('user_id'),
                'account_id_docusign' => $request->get_param('account_id_docusign'),
                'environment' => $request->get_param('environment') ?? 'sandbox',
                'is_active' => 1,
            ];

            // Handle private key (if provided)
            if ($request->has_param('private_key')) {
                $config_data['private_key'] = $request->get_param('private_key');
            }

            // Check if config exists
            if ($this->config_repository->existsForAccount($account_id)) {
                $this->config_repository->updateByAccountId($account_id, $config_data);
            } else {
                $this->config_repository->create($config_data);
            }

            return new WP_REST_Response([
                'success' => true,
                'message' => 'DocuSign configuration saved successfully',
            ], 200);
        } catch (\Exception $e) {
            return new WP_Error('save_config_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Test connection
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function test_connection(WP_REST_Request $request) {
        try {
            $this->ensureClientInitialized();

            if (!$this->client) {
                return new WP_Error('not_configured', 'DocuSign not configured', ['status' => 400]);
            }

            $result = $this->client->testConnection();

            return new WP_REST_Response($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return new WP_Error('connection_test_failed', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * List templates
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function list_templates(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $options = [];
            if ($request->has_param('search')) {
                $options['search_text'] = $request->get_param('search');
            }

            $templates = $this->template_service->getTemplates($options);

            return new WP_REST_Response([
                'templates' => $templates,
                'count' => count($templates),
            ], 200);
        } catch (\Exception $e) {
            return new WP_Error('list_templates_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get template details
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_template(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $template_id = $request->get_param('template_id');
            $template = $this->template_service->getTemplate($template_id);

            return new WP_REST_Response($template, 200);
        } catch (\Exception $e) {
            return new WP_Error('get_template_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Preview template with transaction data
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function preview_template(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $template_id = $request->get_param('template_id');
            $transaction_id = $request->get_param('transaction_id');

            // Get transaction data
            global $wpdb;
            $transaction = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ma_transactions WHERE id = %d",
                    $transaction_id
                ),
                ARRAY_A
            );

            if (!$transaction) {
                return new WP_Error('transaction_not_found', 'Transaction not found', ['status' => 404]);
            }

            $preview = $this->template_service->previewTemplate($template_id, $transaction);

            return new WP_REST_Response($preview, 200);
        } catch (\Exception $e) {
            return new WP_Error('preview_template_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Create envelope
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function create_envelope(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $transaction_id = $request->get_param('transaction_id');
            $template_id = $request->get_param('template_id');
            $recipients = $request->get_param('recipients');
            $options = [
                'subject' => $request->get_param('subject'),
                'message' => $request->get_param('message'),
            ];

            $result = $this->envelope_service->createEnvelopeFromTemplate(
                $transaction_id,
                $template_id,
                $recipients,
                $options
            );

            return new WP_REST_Response($result, 201);
        } catch (\Exception $e) {
            return new WP_Error('create_envelope_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get envelope details
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_envelope(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $envelope_id = $request->get_param('envelope_id');

            // Get from database
            $envelope = $this->envelope_service->getEnvelope($envelope_id);

            if (!$envelope) {
                return new WP_Error('envelope_not_found', 'Envelope not found', ['status' => 404]);
            }

            // Get latest status from DocuSign
            $status = $this->client->getEnvelopeStatus($envelope_id);

            return new WP_REST_Response([
                'envelope' => $envelope,
                'docusign_status' => $status,
            ], 200);
        } catch (\Exception $e) {
            return new WP_Error('get_envelope_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Send envelope
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function send_envelope(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $envelope_id = $request->get_param('envelope_id');
            $result = $this->client->sendEnvelope($envelope_id);

            // Update status in database
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'ma_deal_docusign_envelopes',
                [
                    'status' => 'sent',
                    'sent_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ],
                ['envelope_id' => $envelope_id],
                ['%s', '%s', '%s'],
                ['%s']
            );

            return new WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            return new WP_Error('send_envelope_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Void envelope
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function void_envelope(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $envelope_id = $request->get_param('envelope_id');
            $void_reason = $request->get_param('reason') ?? 'Voided by user';

            $result = $this->client->voidEnvelope($envelope_id, $void_reason);

            // Update status in database
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'ma_deal_docusign_envelopes',
                [
                    'status' => 'voided',
                    'voided_at' => current_time('mysql'),
                    'voided_reason' => $void_reason,
                    'updated_at' => current_time('mysql'),
                ],
                ['envelope_id' => $envelope_id],
                ['%s', '%s', '%s', '%s'],
                ['%s']
            );

            return new WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            return new WP_Error('void_envelope_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Resend envelope
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function resend_envelope(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $envelope_id = $request->get_param('envelope_id');
            $result = $this->client->resendEnvelope($envelope_id);

            return new WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            return new WP_Error('resend_envelope_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get envelope recipients
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_envelope_recipients(WP_REST_Request $request) {
        try {
            $this->ensureServicesInitialized();

            $envelope_id = $request->get_param('envelope_id');
            $result = $this->client->getEnvelopeRecipients($envelope_id);

            return new WP_REST_Response($result, 200);
        } catch (\Exception $e) {
            return new WP_Error('get_recipients_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * List envelopes
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function list_envelopes(WP_REST_Request $request) {
        try {
            global $wpdb;

            $account_id = $this->get_user_account_id();
            $transaction_id = $request->get_param('transaction_id');

            $where = "account_id = %d";
            $params = [$account_id];

            if ($transaction_id) {
                $where .= " AND transaction_id = %d";
                $params[] = $transaction_id;
            }

            $envelopes = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ma_deal_docusign_envelopes
                    WHERE {$where}
                    ORDER BY created_at DESC",
                    ...$params
                ),
                ARRAY_A
            );

            // Decode JSON fields
            foreach ($envelopes as &$envelope) {
                $envelope['recipients'] = json_decode($envelope['recipients'], true);
                $envelope['documents'] = json_decode($envelope['documents'], true);
            }

            return new WP_REST_Response([
                'envelopes' => $envelopes,
                'count' => count($envelopes),
            ], 200);
        } catch (\Exception $e) {
            return new WP_Error('list_envelopes_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Get envelope statistics
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_envelope_stats(WP_REST_Request $request) {
        try {
            global $wpdb;

            $account_id = $this->get_user_account_id();

            $stats = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT status, COUNT(*) as count
                    FROM {$wpdb->prefix}ma_deal_docusign_envelopes
                    WHERE account_id = %d
                    GROUP BY status",
                    $account_id
                ),
                ARRAY_A
            );

            $formatted_stats = [];
            foreach ($stats as $stat) {
                $formatted_stats[$stat['status']] = (int) $stat['count'];
            }

            return new WP_REST_Response([
                'stats' => $formatted_stats,
                'total' => array_sum(array_values($formatted_stats)),
            ], 200);
        } catch (\Exception $e) {
            return new WP_Error('get_stats_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Ensure DocuSign client is initialized
     *
     * @throws \Exception If configuration not found
     */
    private function ensureClientInitialized(): void {
        if ($this->client) {
            return;
        }

        $account_id = $this->get_user_account_id();
        $config = $this->config_repository->getActiveByAccountId($account_id);

        if (!$config) {
            throw new \Exception('DocuSign not configured');
        }

        $this->client = new DocuSignClient(
            $config['integration_key'],
            $config['user_id'],
            $config['private_key'],
            $config['account_id_docusign'],
            $config['environment']
        );
    }

    /**
     * Ensure all services are initialized
     *
     * @throws \Exception If configuration not found
     */
    private function ensureServicesInitialized(): void {
        $this->ensureClientInitialized();

        if (!$this->template_service) {
            $this->template_service = new DocuSignTemplateService($this->client);
        }

        if (!$this->envelope_service) {
            $this->envelope_service = new EnvelopeService($this->client, $this->template_service);
        }
    }

    /**
     * Check if user has admin permission
     *
     * @return bool
     */
    public function check_admin_permission(): bool {
        return current_user_can('manage_options') || current_user_can('ma_deal_admin');
    }

    /**
     * Check if user has general permission
     *
     * @return bool
     */
    public function check_permission(): bool {
        return current_user_can('ma_deal_agent') ||
               current_user_can('ma_deal_admin') ||
               current_user_can('manage_options');
    }

    /**
     * Handle DocuSign webhook
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function handle_webhook(WP_REST_Request $request) {
        try {
            // Get webhook secret from account configuration
            // For now, use a filter to allow configuration
            $webhook_secret = apply_filters('ma_deal_docusign_webhook_secret', '');

            if (empty($webhook_secret)) {
                return new WP_Error(
                    'webhook_not_configured',
                    'Webhook secret not configured. Set via ma_deal_docusign_webhook_secret filter.',
                    ['status' => 500]
                );
            }

            // Get HMAC signature from headers
            $signature = $request->get_header('X-DocuSign-Signature-1') ??
                        $request->get_header('x-docusign-signature-1') ?? '';

            if (empty($signature)) {
                return new WP_Error('missing_signature', 'Missing HMAC signature', ['status' => 401]);
            }

            // Get payload
            $payload = $request->get_json_params();

            if (empty($payload)) {
                return new WP_Error('invalid_payload', 'Invalid or empty payload', ['status' => 400]);
            }

            // Ensure webhook handler is initialized
            $this->ensureWebhookHandlerInitialized();

            // Process webhook
            $result = $this->webhook_handler->handleWebhook($payload, $signature, $webhook_secret);

            if (!$result['success']) {
                return new WP_Error(
                    'webhook_processing_failed',
                    $result['error'] ?? 'Webhook processing failed',
                    ['status' => 400]
                );
            }

            // Return success
            return new WP_REST_Response([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'envelope_id' => $result['envelope_id'] ?? null,
                'event_type' => $result['event_type'] ?? null,
            ], 200);
        } catch (\Exception $e) {
            return new WP_Error('webhook_error', $e->getMessage(), ['status' => 500]);
        }
    }

    /**
     * Ensure webhook handler is initialized
     *
     * @return void
     */
    private function ensureWebhookHandlerInitialized(): void {
        if ($this->webhook_handler) {
            return;
        }

        // Initialize services if needed
        $this->ensureServicesInitialized();

        // Initialize download service
        if (!$this->download_service && $this->client) {
            $this->download_service = new DocumentDownloadService($this->client);
        }

        // Initialize webhook handler
        $this->webhook_handler = new WebhookHandler(
            $this->config_repository,
            $this->download_service
        );
    }
}
