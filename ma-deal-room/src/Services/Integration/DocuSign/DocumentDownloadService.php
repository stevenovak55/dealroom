<?php
/**
 * DocuSign Document Download Service
 *
 * @package    MADealRoom
 * @subpackage Services\Integration\DocuSign
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\DocuSign;

use MADealRoom\Core\Logger;

/**
 * Class DocumentDownloadService
 *
 * Handles automatic download of completed DocuSign documents
 */
class DocumentDownloadService {
    /**
     * DocuSign client
     *
     * @var DocuSignClient
     */
    private DocuSignClient $client;

    /**
     * Logger instance
     *
     * @var Logger
     */
    private Logger $logger;

    /**
     * Database instance
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Upload base directory
     *
     * @var string
     */
    private string $upload_base_dir;

    /**
     * Constructor
     *
     * @param DocuSignClient $client DocuSign client
     * @param Logger|null    $logger Logger instance
     */
    public function __construct(DocuSignClient $client, ?Logger $logger = null) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->client = $client;
        $this->logger = $logger ?? new Logger();

        // Set upload base directory
        $upload_dir = wp_upload_dir();
        $this->upload_base_dir = trailingslashit($upload_dir['basedir']) . 'ma-deal-room/transactions/';
    }

    /**
     * Download completed envelope documents
     *
     * @param string $envelope_id   Envelope ID
     * @param int    $transaction_id Transaction ID
     * @return array Download results
     */
    public function downloadCompletedEnvelope(string $envelope_id, int $transaction_id): array {
        try {
            $this->logger->info('Starting document download for completed envelope', [
                'envelope_id' => $envelope_id,
                'transaction_id' => $transaction_id,
            ]);

            $results = [];

            // Download combined documents (all docs in one PDF)
            $combined_result = $this->downloadAndStoreDocument(
                $envelope_id,
                $transaction_id,
                'combined',
                'Combined Signed Documents'
            );

            if ($combined_result['success']) {
                $results['combined'] = $combined_result;
            }

            // Download certificate of completion
            $certificate_result = $this->downloadAndStoreCertificate(
                $envelope_id,
                $transaction_id
            );

            if ($certificate_result['success']) {
                $results['certificate'] = $certificate_result;
            }

            $this->logger->info('Document download completed', [
                'envelope_id' => $envelope_id,
                'transaction_id' => $transaction_id,
                'downloads' => count($results),
            ]);

            return [
                'success' => true,
                'envelope_id' => $envelope_id,
                'transaction_id' => $transaction_id,
                'results' => $results,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Document download failed', [
                'envelope_id' => $envelope_id,
                'transaction_id' => $transaction_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Download and store document
     *
     * @param string $envelope_id   Envelope ID
     * @param int    $transaction_id Transaction ID
     * @param string $document_id   Document ID ('combined' for all)
     * @param string $document_name Document name
     * @return array Result with file path
     */
    private function downloadAndStoreDocument(
        string $envelope_id,
        int $transaction_id,
        string $document_id,
        string $document_name
    ): array {
        try {
            // Download from DocuSign
            $content = $this->client->downloadDocument($envelope_id, $document_id);

            if (!$content) {
                throw new \Exception('Failed to download document from DocuSign');
            }

            // Generate filename
            $filename = $this->generateFilename($envelope_id, $document_name, 'pdf');

            // Store file
            $file_path = $this->storeFile($transaction_id, $filename, $content);

            // Store in WordPress media library and attach to transaction
            $attachment_id = $this->createAttachment($file_path, $transaction_id, $document_name);

            return [
                'success' => true,
                'file_path' => $file_path,
                'attachment_id' => $attachment_id,
                'document_id' => $document_id,
                'document_name' => $document_name,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to download and store document', [
                'envelope_id' => $envelope_id,
                'document_id' => $document_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Download and store certificate of completion
     *
     * @param string $envelope_id   Envelope ID
     * @param int    $transaction_id Transaction ID
     * @return array Result with file path
     */
    private function downloadAndStoreCertificate(string $envelope_id, int $transaction_id): array {
        try {
            // Download certificate from DocuSign
            $content = $this->client->downloadCertificate($envelope_id);

            if (!$content) {
                throw new \Exception('Failed to download certificate from DocuSign');
            }

            // Generate filename
            $filename = $this->generateFilename($envelope_id, 'Certificate of Completion', 'pdf');

            // Store file
            $file_path = $this->storeFile($transaction_id, $filename, $content);

            // Store in WordPress media library
            $attachment_id = $this->createAttachment(
                $file_path,
                $transaction_id,
                'Certificate of Completion'
            );

            return [
                'success' => true,
                'file_path' => $file_path,
                'attachment_id' => $attachment_id,
                'document_type' => 'certificate',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to download and store certificate', [
                'envelope_id' => $envelope_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Store file in transaction upload directory
     *
     * @param int    $transaction_id Transaction ID
     * @param string $filename       Filename
     * @param string $content        File content
     * @return string Full file path
     * @throws \Exception If storage fails
     */
    private function storeFile(int $transaction_id, string $filename, string $content): string {
        // Create transaction directory if it doesn't exist
        $transaction_dir = $this->upload_base_dir . $transaction_id . '/';

        if (!file_exists($transaction_dir)) {
            if (!wp_mkdir_p($transaction_dir)) {
                throw new \Exception("Failed to create directory: {$transaction_dir}");
            }

            // Add .htaccess for security
            $htaccess_content = "Order Deny,Allow\nDeny from all\n";
            file_put_contents($transaction_dir . '.htaccess', $htaccess_content);
        }

        // Full file path
        $file_path = $transaction_dir . $filename;

        // Write file
        $bytes_written = file_put_contents($file_path, $content);

        if ($bytes_written === false) {
            throw new \Exception("Failed to write file: {$file_path}");
        }

        // Set permissions
        chmod($file_path, 0644);

        $this->logger->info('File stored successfully', [
            'file_path' => $file_path,
            'size' => $bytes_written,
        ]);

        return $file_path;
    }

    /**
     * Create WordPress attachment
     *
     * @param string $file_path      Full file path
     * @param int    $transaction_id Transaction ID
     * @param string $title          Attachment title
     * @return int|false Attachment ID or false on failure
     */
    private function createAttachment(string $file_path, int $transaction_id, string $title) {
        $file_type = wp_check_filetype(basename($file_path), null);

        $attachment = [
            'guid' => $file_path,
            'post_mime_type' => $file_type['type'],
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path, $transaction_id);

        if (is_wp_error($attachment_id)) {
            $this->logger->error('Failed to create attachment', [
                'error' => $attachment_id->get_error_message(),
            ]);
            return false;
        }

        // Generate metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Store reference in transaction meta
        $this->addTransactionDocument($transaction_id, $attachment_id, $title);

        return $attachment_id;
    }

    /**
     * Add document to transaction meta
     *
     * @param int    $transaction_id Transaction ID
     * @param int    $attachment_id  Attachment ID
     * @param string $title          Document title
     * @return void
     */
    private function addTransactionDocument(int $transaction_id, int $attachment_id, string $title): void {
        // Get existing documents
        $documents = get_post_meta($transaction_id, 'docusign_documents', true);
        if (!is_array($documents)) {
            $documents = [];
        }

        // Add new document
        $documents[] = [
            'attachment_id' => $attachment_id,
            'title' => $title,
            'downloaded_at' => current_time('mysql'),
        ];

        // Update meta
        update_post_meta($transaction_id, 'docusign_documents', $documents);
    }

    /**
     * Generate filename
     *
     * @param string $envelope_id   Envelope ID
     * @param string $document_name Document name
     * @param string $extension     File extension
     * @return string Filename
     */
    private function generateFilename(string $envelope_id, string $document_name, string $extension): string {
        // Sanitize document name
        $sanitized_name = sanitize_file_name($document_name);

        // Add timestamp and envelope ID
        $timestamp = current_time('Ymd-His');
        $short_envelope_id = substr($envelope_id, 0, 8);

        return "{$timestamp}_{$short_envelope_id}_{$sanitized_name}.{$extension}";
    }

    /**
     * Get download status for envelope
     *
     * @param string $envelope_id Envelope ID
     * @return array Download status
     */
    public function getDownloadStatus(string $envelope_id): array {
        // Get transaction ID
        $transaction_id = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT transaction_id FROM {$this->wpdb->prefix}ma_deal_docusign_envelopes WHERE envelope_id = %s",
                $envelope_id
            )
        );

        if (!$transaction_id) {
            return [
                'downloaded' => false,
                'reason' => 'Transaction not found',
            ];
        }

        // Check for downloaded documents
        $documents = get_post_meta($transaction_id, 'docusign_documents', true);

        if (empty($documents)) {
            return [
                'downloaded' => false,
                'transaction_id' => $transaction_id,
            ];
        }

        return [
            'downloaded' => true,
            'transaction_id' => $transaction_id,
            'document_count' => count($documents),
            'documents' => $documents,
        ];
    }
}
