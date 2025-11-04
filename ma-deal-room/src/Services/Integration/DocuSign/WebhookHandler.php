<?php
/**
 * DocuSign Webhook Handler
 *
 * @package    MADealRoom
 * @subpackage Services\Integration\DocuSign
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\DocuSign;

use MADealRoom\Core\Logger;
use MADealRoom\Repositories\DocuSignConfigRepository;

/**
 * Class WebhookHandler
 *
 * Handles DocuSign Connect webhook events
 */
class WebhookHandler {
    /**
     * Logger instance
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Config repository
     *
     * @var DocuSignConfigRepository
     */
    private DocuSignConfigRepository $config_repository;

    /**
     * Document download service
     *
     * @var DocumentDownloadService|null
     */
    private ?DocumentDownloadService $download_service = null;

    /**
     * Database instance
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Webhook log table
     *
     * @var string
     */
    private string $webhook_log_table;

    /**
     * Envelopes table
     *
     * @var string
     */
    private string $envelopes_table;

    /**
     * Constructor
     *
     * @param DocuSignConfigRepository    $config_repository Config repository
     * @param DocumentDownloadService|null $download_service  Download service
     * @param Logger|null                  $logger            Logger instance
     */
    public function __construct(
        DocuSignConfigRepository $config_repository,
        ?DocumentDownloadService $download_service = null,
        ?Logger $logger = null
    ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->config_repository = $config_repository;
        $this->download_service = $download_service;
        $this->logger = $logger ?? new Logger();
        $this->webhook_log_table = $wpdb->prefix . 'ma_deal_docusign_webhook_log';
        $this->envelopes_table = $wpdb->prefix . 'ma_deal_docusign_envelopes';
    }

    /**
     * Handle incoming webhook
     *
     * @param array  $payload   Webhook payload
     * @param string $signature HMAC signature from headers
     * @param string $secret    Webhook secret from config
     * @return array Response data
     */
    public function handleWebhook(array $payload, string $signature, string $secret): array {
        try {
            // Verify HMAC signature
            if (!$this->verifySignature($payload, $signature, $secret)) {
                $this->logger->error('Webhook signature verification failed', [
                    'signature' => $signature,
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid signature',
                ];
            }

            // Parse event data
            $event = $this->parseEvent($payload);

            if (!$event) {
                $this->logger->error('Failed to parse webhook event', [
                    'payload' => $payload,
                ]);
                return [
                    'success' => false,
                    'error' => 'Invalid payload',
                ];
            }

            // Log webhook event
            $log_id = $this->logWebhookEvent(
                $event['envelope_id'],
                $event['event_type'],
                $payload
            );

            // Process the event
            $result = $this->processEvent($event);

            // Update log with processing status
            $this->updateWebhookLog($log_id, 'processed', null);

            $this->logger->info('Webhook processed successfully', [
                'envelope_id' => $event['envelope_id'],
                'event_type' => $event['event_type'],
                'log_id' => $log_id,
            ]);

            return [
                'success' => true,
                'event_type' => $event['event_type'],
                'envelope_id' => $event['envelope_id'],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update log with error if we have a log ID
            if (isset($log_id)) {
                $this->updateWebhookLog($log_id, 'failed', $e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify HMAC signature
     *
     * @param array  $payload   Webhook payload
     * @param string $signature HMAC signature from headers
     * @param string $secret    Webhook secret
     * @return bool True if valid
     */
    public function verifySignature(array $payload, string $signature, string $secret): bool {
        // Convert payload to canonical form for verification
        $canonical_payload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Calculate expected signature
        $expected_signature = base64_encode(hash_hmac('sha256', $canonical_payload, $secret, true));

        // Constant-time comparison to prevent timing attacks
        return hash_equals($expected_signature, $signature);
    }

    /**
     * Parse webhook event
     *
     * @param array $payload Webhook payload
     * @return array|null Parsed event data
     */
    private function parseEvent(array $payload): ?array {
        // DocuSign webhook format varies, handle both XML and JSON
        // For now, assume JSON format from Connect

        // Extract envelope ID
        $envelope_id = $payload['envelopeId'] ?? $payload['envelope_id'] ?? null;
        if (!$envelope_id) {
            return null;
        }

        // Extract event type
        $event_type = $payload['event'] ?? $payload['eventType'] ?? 'unknown';

        // Normalize event type
        $event_type = $this->normalizeEventType($event_type);

        // Extract additional data
        $status = $payload['status'] ?? null;
        $recipients = $payload['recipients'] ?? [];
        $completed_at = $payload['completedDateTime'] ?? null;
        $declined_at = $payload['declinedDateTime'] ?? null;
        $voided_at = $payload['voidedDateTime'] ?? null;

        return [
            'envelope_id' => $envelope_id,
            'event_type' => $event_type,
            'status' => $status,
            'recipients' => $recipients,
            'completed_at' => $completed_at,
            'declined_at' => $declined_at,
            'voided_at' => $voided_at,
            'payload' => $payload,
        ];
    }

    /**
     * Normalize event type
     *
     * @param string $event_type Raw event type
     * @return string Normalized event type
     */
    private function normalizeEventType(string $event_type): string {
        $event_type = strtolower($event_type);

        // Map various event names to standard types
        $event_map = [
            'envelope-sent' => 'sent',
            'envelope-delivered' => 'delivered',
            'envelope-signed' => 'signed',
            'envelope-completed' => 'completed',
            'envelope-declined' => 'declined',
            'envelope-voided' => 'voided',
            'recipient-signed' => 'signed',
            'recipient-delivered' => 'delivered',
        ];

        return $event_map[$event_type] ?? $event_type;
    }

    /**
     * Process webhook event
     *
     * @param array $event Event data
     * @return bool Success status
     */
    private function processEvent(array $event): bool {
        $envelope_id = $event['envelope_id'];
        $event_type = $event['event_type'];

        // Update envelope status in database
        $this->updateEnvelopeStatus($envelope_id, $event);

        // Handle specific event types
        switch ($event_type) {
            case 'completed':
                // Envelope completed - download documents
                if ($this->download_service) {
                    $transaction_id = $this->getTransactionIdForEnvelope($envelope_id);
                    if ($transaction_id) {
                        $this->download_service->downloadCompletedEnvelope($envelope_id, $transaction_id);
                    }
                }
                break;

            case 'declined':
                // Envelope declined - log reason
                $this->logger->warning('Envelope declined', [
                    'envelope_id' => $envelope_id,
                    'payload' => $event['payload'],
                ]);
                break;

            case 'voided':
                // Envelope voided
                $this->logger->info('Envelope voided via webhook', [
                    'envelope_id' => $envelope_id,
                ]);
                break;
        }

        // Trigger action hook for extensions
        do_action('ma_deal_docusign_webhook_processed', $event_type, $envelope_id, $event);

        return true;
    }

    /**
     * Update envelope status in database
     *
     * @param string $envelope_id Envelope ID
     * @param array  $event       Event data
     * @return bool Success status
     */
    private function updateEnvelopeStatus(string $envelope_id, array $event): bool {
        $update_data = [
            'updated_at' => current_time('mysql'),
        ];

        // Update status if provided
        if (isset($event['status'])) {
            $update_data['status'] = $event['status'];
        } elseif ($event['event_type']) {
            // Map event type to status
            $status_map = [
                'sent' => 'sent',
                'delivered' => 'delivered',
                'signed' => 'signed',
                'completed' => 'completed',
                'declined' => 'declined',
                'voided' => 'voided',
            ];
            if (isset($status_map[$event['event_type']])) {
                $update_data['status'] = $status_map[$event['event_type']];
            }
        }

        // Update timestamps
        if ($event['completed_at']) {
            $update_data['completed_at'] = $event['completed_at'];
        }
        if ($event['declined_at']) {
            $update_data['declined_at'] = $event['declined_at'];
        }
        if ($event['voided_at']) {
            $update_data['voided_at'] = $event['voided_at'];
        }

        $result = $this->wpdb->update(
            $this->envelopes_table,
            $update_data,
            ['envelope_id' => $envelope_id],
            array_fill(0, count($update_data), '%s'),
            ['%s']
        );

        return $result !== false;
    }

    /**
     * Log webhook event
     *
     * @param string $envelope_id Envelope ID
     * @param string $event_type  Event type
     * @param array  $payload     Full payload
     * @return int|false Log ID or false on failure
     */
    private function logWebhookEvent(string $envelope_id, string $event_type, array $payload) {
        $result = $this->wpdb->insert(
            $this->webhook_log_table,
            [
                'envelope_id' => $envelope_id,
                'event_type' => $event_type,
                'payload' => json_encode($payload),
                'processing_status' => 'pending',
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Update webhook log
     *
     * @param int         $log_id   Log ID
     * @param string      $status   Processing status
     * @param string|null $error    Error message if failed
     * @return bool Success status
     */
    private function updateWebhookLog(int $log_id, string $status, ?string $error): bool {
        $update_data = [
            'processing_status' => $status,
            'processed_at' => current_time('mysql'),
        ];

        if ($error) {
            $update_data['error_message'] = $error;
        }

        $result = $this->wpdb->update(
            $this->webhook_log_table,
            $update_data,
            ['id' => $log_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Get transaction ID for envelope
     *
     * @param string $envelope_id Envelope ID
     * @return int|null Transaction ID
     */
    private function getTransactionIdForEnvelope(string $envelope_id): ?int {
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT transaction_id FROM {$this->envelopes_table} WHERE envelope_id = %s",
                $envelope_id
            )
        );

        return $result ? (int) $result : null;
    }
}
