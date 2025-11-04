<?php
/**
 * DocuSign Envelope Service
 *
 * @package    MADealRoom
 * @subpackage Services\Integration\DocuSign
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\DocuSign;

use MADealRoom\Core\Logger;
use MADealRoom\Repositories\DocuSignConfigRepository;

/**
 * Class EnvelopeService
 *
 * Manages DocuSign envelope creation and management
 */
class EnvelopeService {
    /**
     * DocuSign client
     *
     * @var DocuSignClient
     */
    private DocuSignClient $client;

    /**
     * Template service
     *
     * @var DocuSignTemplateService
     */
    private DocuSignTemplateService $template_service;

    /**
     * Database instance
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Envelopes table
     *
     * @var string
     */
    private string $envelopes_table;

    /**
     * Logger instance
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Constructor
     *
     * @param DocuSignClient           $client           DocuSign client
     * @param DocuSignTemplateService  $template_service Template service
     * @param Logger|null              $logger           Logger instance
     */
    public function __construct(
        DocuSignClient $client,
        DocuSignTemplateService $template_service,
        ?Logger $logger = null
    ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->client = $client;
        $this->template_service = $template_service;
        $this->logger = $logger ?? new Logger();
        $this->envelopes_table = $wpdb->prefix . 'ma_deal_docusign_envelopes';
    }

    /**
     * Create envelope from template
     *
     * @param int    $transaction_id Transaction ID
     * @param string $template_id    Template ID
     * @param array  $recipients     Recipients array
     * @param array  $options        Additional options
     * @return array Created envelope data
     */
    public function createEnvelopeFromTemplate(
        int $transaction_id,
        string $template_id,
        array $recipients,
        array $options = []
    ): array {
        try {
            // Get transaction data
            $transaction = $this->getTransactionData($transaction_id);

            // Get template and map fields
            $template = $this->template_service->getTemplate($template_id);
            $mapped_tabs = $this->template_service->mapTransactionDataToTemplate($template, $transaction);

            // Prepare envelope data
            $envelope_data = [
                'templateId' => $template_id,
                'status' => 'created', // Don't send immediately
                'emailSubject' => $options['subject'] ?? "Please sign: {$transaction['title']}",
                'emailBlurb' => $options['message'] ?? 'Please review and sign the attached documents.',
                'recipients' => $this->formatRecipients($recipients, $mapped_tabs),
            ];

            // Add branding if configured
            if (isset($options['brand_id'])) {
                $envelope_data['brandId'] = $options['brand_id'];
            }

            // Create envelope via API
            $result = $this->client->createEnvelopeFromTemplate($template_id, $envelope_data);

            // Store envelope in database
            $envelope_id = $result['envelopeId'];
            $this->storeEnvelope([
                'account_id' => $this->getCurrentAccountId(),
                'transaction_id' => $transaction_id,
                'envelope_id' => $envelope_id,
                'status' => $result['status'],
                'subject' => $envelope_data['emailSubject'],
                'message' => $envelope_data['emailBlurb'],
                'recipients' => json_encode($recipients),
                'documents' => json_encode([
                    'template_id' => $template_id,
                    'template_name' => $template['name'] ?? 'Unknown Template',
                ]),
            ]);

            $this->logger->info('Envelope created from template', [
                'envelope_id' => $envelope_id,
                'transaction_id' => $transaction_id,
                'template_id' => $template_id,
            ]);

            return [
                'envelope_id' => $envelope_id,
                'status' => $result['status'],
                'uri' => $result['uri'] ?? null,
                'recipients' => $recipients,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create envelope from template', [
                'transaction_id' => $transaction_id,
                'template_id' => $template_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create envelope from documents
     *
     * @param int   $transaction_id Transaction ID
     * @param array $document_ids   Document IDs from deal room
     * @param array $recipients     Recipients array
     * @param array $options        Additional options
     * @return array Created envelope data
     */
    public function createEnvelopeFromDocuments(
        int $transaction_id,
        array $document_ids,
        array $recipients,
        array $options = []
    ): array {
        try {
            // Get transaction data
            $transaction = $this->getTransactionData($transaction_id);

            // Prepare documents
            $documents = $this->prepareDocuments($document_ids);

            // Prepare envelope definition
            $envelope_data = [
                'status' => 'created',
                'emailSubject' => $options['subject'] ?? "Please sign: {$transaction['title']}",
                'emailBlurb' => $options['message'] ?? 'Please review and sign the attached documents.',
                'documents' => $documents,
                'recipients' => $this->formatRecipients($recipients, []),
            ];

            // Add branding if configured
            if (isset($options['brand_id'])) {
                $envelope_data['brandId'] = $options['brand_id'];
            }

            // Create envelope via API
            $result = $this->client->createEnvelope($envelope_data);

            // Store envelope in database
            $envelope_id = $result['envelopeId'];
            $this->storeEnvelope([
                'account_id' => $this->getCurrentAccountId(),
                'transaction_id' => $transaction_id,
                'envelope_id' => $envelope_id,
                'status' => $result['status'],
                'subject' => $envelope_data['emailSubject'],
                'message' => $envelope_data['emailBlurb'],
                'recipients' => json_encode($recipients),
                'documents' => json_encode($documents),
            ]);

            $this->logger->info('Envelope created from documents', [
                'envelope_id' => $envelope_id,
                'transaction_id' => $transaction_id,
                'document_count' => count($documents),
            ]);

            return [
                'envelope_id' => $envelope_id,
                'status' => $result['status'],
                'uri' => $result['uri'] ?? null,
                'recipients' => $recipients,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create envelope from documents', [
                'transaction_id' => $transaction_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get envelope details
     *
     * @param string $envelope_id Envelope ID
     * @return array|null Envelope data
     */
    public function getEnvelope(string $envelope_id): ?array {
        $envelope = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->envelopes_table} WHERE envelope_id = %s",
                $envelope_id
            ),
            ARRAY_A
        );

        if (!$envelope) {
            return null;
        }

        // Decode JSON fields
        $envelope['recipients'] = json_decode($envelope['recipients'], true);
        $envelope['documents'] = json_decode($envelope['documents'], true);

        return $envelope;
    }

    /**
     * Get envelopes for transaction
     *
     * @param int $transaction_id Transaction ID
     * @return array List of envelopes
     */
    public function getEnvelopesForTransaction(int $transaction_id): array {
        $envelopes = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->envelopes_table}
                WHERE transaction_id = %d
                ORDER BY created_at DESC",
                $transaction_id
            ),
            ARRAY_A
        );

        // Decode JSON fields
        foreach ($envelopes as &$envelope) {
            $envelope['recipients'] = json_decode($envelope['recipients'], true);
            $envelope['documents'] = json_decode($envelope['documents'], true);
        }

        return $envelopes;
    }

    /**
     * Store envelope in database
     *
     * @param array $data Envelope data
     * @return int|false Envelope database ID
     */
    private function storeEnvelope(array $data) {
        $data['created_at'] = current_time('mysql');
        $data['updated_at'] = current_time('mysql');

        $result = $this->wpdb->insert($this->envelopes_table, $data);

        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Format recipients for DocuSign API
     *
     * @param array $recipients  Recipients array
     * @param array $mapped_tabs Mapped tabs from template
     * @return array Formatted recipients
     */
    private function formatRecipients(array $recipients, array $mapped_tabs): array {
        $formatted = [
            'signers' => [],
            'carbonCopies' => [],
            'certifiedDeliveries' => [],
        ];

        foreach ($recipients as $index => $recipient) {
            $role = $recipient['role'] ?? 'signer';
            $routing_order = $recipient['routing_order'] ?? ($index + 1);

            $formatted_recipient = [
                'email' => $recipient['email'],
                'name' => $recipient['name'],
                'recipientId' => (string) ($index + 1),
                'routingOrder' => (string) $routing_order,
            ];

            // Add tabs for signers
            if ($role === 'signer' && !empty($mapped_tabs)) {
                $formatted_recipient['tabs'] = $mapped_tabs;
            }

            // Add to appropriate recipient type
            switch ($role) {
                case 'signer':
                    $formatted['signers'][] = $formatted_recipient;
                    break;
                case 'cc':
                case 'carbon_copy':
                    $formatted['carbonCopies'][] = $formatted_recipient;
                    break;
                case 'certified_delivery':
                    $formatted['certifiedDeliveries'][] = $formatted_recipient;
                    break;
            }
        }

        // Remove empty recipient types
        return array_filter($formatted);
    }

    /**
     * Prepare documents for envelope
     *
     * @param array $document_ids Document IDs
     * @return array Formatted documents
     */
    private function prepareDocuments(array $document_ids): array {
        $documents = [];

        foreach ($document_ids as $index => $document_id) {
            $document = $this->getDocumentData($document_id);

            if (!$document) {
                continue;
            }

            $documents[] = [
                'documentId' => (string) ($index + 1),
                'name' => $document['name'],
                'fileExtension' => $document['extension'],
                'documentBase64' => $document['content_base64'],
            ];
        }

        return $documents;
    }

    /**
     * Get transaction data
     *
     * @param int $transaction_id Transaction ID
     * @return array Transaction data
     */
    private function getTransactionData(int $transaction_id): array {
        $transaction = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->wpdb->prefix}ma_transactions WHERE id = %d",
                $transaction_id
            ),
            ARRAY_A
        );

        if (!$transaction) {
            throw new \Exception('Transaction not found');
        }

        return $transaction;
    }

    /**
     * Get document data
     *
     * @param int $document_id Document ID
     * @return array|null Document data
     */
    private function getDocumentData(int $document_id): ?array {
        // Get document from WordPress media library or custom document storage
        $attachment = get_post($document_id);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return null;
        }

        $file_path = get_attached_file($document_id);
        if (!file_exists($file_path)) {
            return null;
        }

        $content = file_get_contents($file_path);

        return [
            'name' => basename($file_path),
            'extension' => pathinfo($file_path, PATHINFO_EXTENSION),
            'content_base64' => base64_encode($content),
        ];
    }

    /**
     * Get current user's account ID
     *
     * @return int Account ID from user meta or session
     * @throws \Exception If user is not authenticated or has no account
     */
    private function getCurrentAccountId(): int {
        // Get current WordPress user
        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            throw new \Exception('User not authenticated');
        }

        // Get account ID from user meta
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
