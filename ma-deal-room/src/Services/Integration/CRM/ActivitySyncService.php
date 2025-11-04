<?php
/**
 * Activity Sync Service
 *
 * Handles synchronization of Deal Room activities to CRM timeline.
 * Logs task completions, document uploads, status changes, and vendor interactions.
 *
 * @package MA_Deal_Room\Services\Integration\CRM
 * @since 1.0.0
 */

namespace MADealRoom\Services\Integration\CRM;

use MADealRoom\Repositories\CRMConfigRepository;

class ActivitySyncService {
    private $config_repository;
    private $factory;
    private $batch_queue = [];
    private $batch_size = 10;

    public function __construct() {
        $this->config_repository = new CRMConfigRepository();
        $this->factory = new CRMClientFactory();
    }

    /**
     * Log task completion to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $task_data Task data
     * @return array Results for each CRM provider
     */
    public function logTaskCompletion(int $transaction_id, array $task_data): array {
        return $this->logActivity($transaction_id, [
            'type' => 'task_completion',
            'subject' => 'Task Completed: ' . ($task_data['title'] ?? $task_data['name'] ?? 'Task'),
            'description' => $this->formatTaskDescription($task_data),
            'date' => $task_data['completed_at'] ?? date('Y-m-d'),
            'status' => 'Completed',
            'priority' => $task_data['priority'] ?? 'Normal',
        ]);
    }

    /**
     * Log document upload to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $document_data Document data
     * @return array Results for each CRM provider
     */
    public function logDocumentUpload(int $transaction_id, array $document_data): array {
        return $this->logActivity($transaction_id, [
            'type' => 'document_upload',
            'subject' => 'Document Uploaded: ' . ($document_data['filename'] ?? 'Document'),
            'description' => $this->formatDocumentDescription($document_data),
            'date' => $document_data['uploaded_at'] ?? date('Y-m-d'),
            'status' => 'Completed',
        ]);
    }

    /**
     * Log transaction status change to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @return array Results for each CRM provider
     */
    public function logStatusChange(int $transaction_id, string $old_status, string $new_status): array {
        return $this->logActivity($transaction_id, [
            'type' => 'status_change',
            'subject' => 'Status Changed: ' . ucfirst(str_replace('_', ' ', $old_status)) . ' → ' . ucfirst(str_replace('_', ' ', $new_status)),
            'description' => "Transaction status updated from '{$old_status}' to '{$new_status}'.",
            'date' => date('Y-m-d'),
            'status' => 'Completed',
        ]);
    }

    /**
     * Log vendor interaction to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $vendor_data Vendor interaction data
     * @return array Results for each CRM provider
     */
    public function logVendorInteraction(int $transaction_id, array $vendor_data): array {
        return $this->logActivity($transaction_id, [
            'type' => 'vendor_interaction',
            'subject' => 'Vendor Request: ' . ($vendor_data['vendor_name'] ?? 'Vendor'),
            'description' => $this->formatVendorDescription($vendor_data),
            'date' => $vendor_data['sent_at'] ?? date('Y-m-d'),
            'status' => $vendor_data['status'] ?? 'Not Started',
        ]);
    }

    /**
     * Log note added to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $note_data Note data
     * @return array Results for each CRM provider
     */
    public function logNote(int $transaction_id, array $note_data): array {
        return $this->logActivity($transaction_id, [
            'type' => 'note',
            'subject' => 'Note Added',
            'description' => $note_data['content'] ?? $note_data['note'] ?? '',
            'date' => $note_data['created_at'] ?? date('Y-m-d'),
            'status' => 'Completed',
        ]);
    }

    /**
     * Log email sent to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $email_data Email data
     * @return array Results for each CRM provider
     */
    public function logEmailSent(int $transaction_id, array $email_data): array {
        return $this->logActivity($transaction_id, [
            'type' => 'email',
            'subject' => 'Email Sent: ' . ($email_data['subject'] ?? 'Email'),
            'description' => $this->formatEmailDescription($email_data),
            'date' => date('Y-m-d'),
            'status' => 'Completed',
        ]);
    }

    /**
     * Core method to log activity to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $activity_data Activity data
     * @param bool $batch Whether to batch this activity
     * @return array Results for each CRM provider
     */
    private function logActivity(int $transaction_id, array $activity_data, bool $batch = false): array {
        $transaction = $this->getTransaction($transaction_id);
        if (!$transaction) {
            return [];
        }

        $account_id = $transaction['account_id'];
        $configs = $this->config_repository->getByAccount($account_id);

        $results = [];

        foreach ($configs as $config) {
            if (!$config['sync_activities'] || !$config['sync_enabled']) {
                continue;
            }

            $provider_type = $config['provider_type'];

            // Check if transaction is synced to this CRM
            $crm_id = $this->getTransactionCrmId($transaction, $provider_type);
            if (!$crm_id) {
                continue; // Transaction not synced to this CRM yet
            }

            try {
                if ($batch) {
                    $this->addToBatch($provider_type, $account_id, $crm_id, $activity_data);
                    $results[$provider_type] = ['success' => true, 'message' => 'Queued for batch processing'];
                } else {
                    $client = $this->factory->createFromAccount($account_id, $provider_type);
                    $response = $client->logActivity('deal', $crm_id, $activity_data);
                    $results[$provider_type] = ['success' => true, 'crm_activity_id' => $response['id'] ?? null];
                }
            } catch (\Exception $e) {
                $results[$provider_type] = ['success' => false, 'message' => $e->getMessage()];
                error_log("CRM activity logging error ({$provider_type}): " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Add activity to batch queue
     *
     * @param string $provider_type CRM provider
     * @param int $account_id Account ID
     * @param string $crm_id CRM object ID
     * @param array $activity_data Activity data
     */
    private function addToBatch(string $provider_type, int $account_id, string $crm_id, array $activity_data): void {
        $key = "{$provider_type}_{$account_id}";

        if (!isset($this->batch_queue[$key])) {
            $this->batch_queue[$key] = [
                'provider_type' => $provider_type,
                'account_id' => $account_id,
                'activities' => [],
            ];
        }

        $this->batch_queue[$key]['activities'][] = [
            'crm_id' => $crm_id,
            'data' => $activity_data,
        ];

        // Auto-flush when batch size is reached
        if (count($this->batch_queue[$key]['activities']) >= $this->batch_size) {
            $this->flushBatch($key);
        }
    }

    /**
     * Flush batch queue for a specific provider/account
     *
     * @param string $key Batch queue key
     * @return array Processing results
     */
    private function flushBatch(string $key): array {
        if (!isset($this->batch_queue[$key]) || empty($this->batch_queue[$key]['activities'])) {
            return [];
        }

        $batch = $this->batch_queue[$key];
        $provider_type = $batch['provider_type'];
        $account_id = $batch['account_id'];
        $activities = $batch['activities'];

        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $client = $this->factory->createFromAccount($account_id, $provider_type);

            foreach ($activities as $activity) {
                try {
                    $client->logActivity('deal', $activity['crm_id'], $activity['data']);
                    $results['successful']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                    error_log("Batch activity logging error: " . $e->getMessage());
                }
                $results['processed']++;
            }
        } catch (\Exception $e) {
            $results['failed'] = count($activities);
            $results['errors'][] = "Batch processing failed: " . $e->getMessage();
            error_log("CRM batch processing error: " . $e->getMessage());
        }

        // Clear the batch
        unset($this->batch_queue[$key]);

        return $results;
    }

    /**
     * Flush all pending batches
     *
     * @return array Results for each batch
     */
    public function flushAllBatches(): array {
        $results = [];

        foreach (array_keys($this->batch_queue) as $key) {
            $results[$key] = $this->flushBatch($key);
        }

        return $results;
    }

    /**
     * Format task description
     */
    private function formatTaskDescription(array $task_data): string {
        $parts = [];

        if (isset($task_data['title']) || isset($task_data['name'])) {
            $parts[] = "Task: " . ($task_data['title'] ?? $task_data['name']);
        }

        if (isset($task_data['description'])) {
            $parts[] = "Description: " . $task_data['description'];
        }

        if (isset($task_data['completed_by'])) {
            $parts[] = "Completed by: " . $task_data['completed_by'];
        }

        if (isset($task_data['due_date'])) {
            $parts[] = "Due date: " . $task_data['due_date'];
        }

        return implode("\n", $parts);
    }

    /**
     * Format document description
     */
    private function formatDocumentDescription(array $document_data): string {
        $parts = [];

        if (isset($document_data['filename'])) {
            $parts[] = "Filename: " . $document_data['filename'];
        }

        if (isset($document_data['file_type'])) {
            $parts[] = "Type: " . $document_data['file_type'];
        }

        if (isset($document_data['file_size'])) {
            $parts[] = "Size: " . $this->formatFileSize($document_data['file_size']);
        }

        if (isset($document_data['uploaded_by'])) {
            $parts[] = "Uploaded by: " . $document_data['uploaded_by'];
        }

        if (isset($document_data['category'])) {
            $parts[] = "Category: " . $document_data['category'];
        }

        return implode("\n", $parts);
    }

    /**
     * Format vendor description
     */
    private function formatVendorDescription(array $vendor_data): string {
        $parts = [];

        if (isset($vendor_data['vendor_name'])) {
            $parts[] = "Vendor: " . $vendor_data['vendor_name'];
        }

        if (isset($vendor_data['service_type'])) {
            $parts[] = "Service: " . $vendor_data['service_type'];
        }

        if (isset($vendor_data['status'])) {
            $parts[] = "Status: " . $vendor_data['status'];
        }

        if (isset($vendor_data['notes'])) {
            $parts[] = "Notes: " . $vendor_data['notes'];
        }

        return implode("\n", $parts);
    }

    /**
     * Format email description
     */
    private function formatEmailDescription(array $email_data): string {
        $parts = [];

        if (isset($email_data['to'])) {
            $parts[] = "To: " . (is_array($email_data['to']) ? implode(', ', $email_data['to']) : $email_data['to']);
        }

        if (isset($email_data['subject'])) {
            $parts[] = "Subject: " . $email_data['subject'];
        }

        if (isset($email_data['body'])) {
            $parts[] = "Body: " . substr(strip_tags($email_data['body']), 0, 500);
        }

        return implode("\n", $parts);
    }

    /**
     * Format file size
     */
    private function formatFileSize(int $bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get transaction by ID
     */
    private function getTransaction(int $id): ?array {
        global $wpdb;

        $transaction = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ma_deal_transactions WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $transaction ?: null;
    }

    /**
     * Get CRM ID from transaction
     */
    private function getTransactionCrmId(array $transaction, string $provider_type): ?string {
        if ($provider_type === 'salesforce') {
            return $transaction['crm_opportunity_id'] ?? null;
        } else {
            return $transaction['crm_deal_id'] ?? null;
        }
    }

    /**
     * Hook into WordPress action to automatically log activities
     */
    public static function registerHooks(): void {
        // These hooks should be called by the respective services
        add_action('ma_deal_task_completed', [self::class, 'hookTaskCompletion'], 10, 2);
        add_action('ma_deal_document_uploaded', [self::class, 'hookDocumentUpload'], 10, 2);
        add_action('ma_deal_status_changed', [self::class, 'hookStatusChange'], 10, 3);
        add_action('ma_deal_vendor_request_sent', [self::class, 'hookVendorInteraction'], 10, 2);
        add_action('ma_deal_note_added', [self::class, 'hookNoteAdded'], 10, 2);
        add_action('ma_deal_email_sent', [self::class, 'hookEmailSent'], 10, 2);
    }

    /**
     * Hook handler for task completion
     */
    public static function hookTaskCompletion(int $transaction_id, array $task_data): void {
        $service = new self();
        $service->logTaskCompletion($transaction_id, $task_data);
    }

    /**
     * Hook handler for document upload
     */
    public static function hookDocumentUpload(int $transaction_id, array $document_data): void {
        $service = new self();
        $service->logDocumentUpload($transaction_id, $document_data);
    }

    /**
     * Hook handler for status change
     */
    public static function hookStatusChange(int $transaction_id, string $old_status, string $new_status): void {
        $service = new self();
        $service->logStatusChange($transaction_id, $old_status, $new_status);
    }

    /**
     * Hook handler for vendor interaction
     */
    public static function hookVendorInteraction(int $transaction_id, array $vendor_data): void {
        $service = new self();
        $service->logVendorInteraction($transaction_id, $vendor_data);
    }

    /**
     * Hook handler for note added
     */
    public static function hookNoteAdded(int $transaction_id, array $note_data): void {
        $service = new self();
        $service->logNote($transaction_id, $note_data);
    }

    /**
     * Hook handler for email sent
     */
    public static function hookEmailSent(int $transaction_id, array $email_data): void {
        $service = new self();
        $service->logEmailSent($transaction_id, $email_data);
    }
}
