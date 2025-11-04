<?php
/**
 * CRM Activity Logger
 *
 * Utility class for easy CRM activity logging from anywhere in the codebase.
 * Provides static methods for common activity types.
 *
 * @package MA_Deal_Room\Services\Integration\CRM
 * @since 1.0.0
 */

namespace MADealRoom\Services\Integration\CRM;

class CRMActivityLogger {
    /**
     * Log task completion to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $task_data Task data (title, description, completed_by, etc.)
     */
    public static function logTaskCompletion(int $transaction_id, array $task_data): void {
        try {
            $service = new ActivitySyncService();
            $service->logTaskCompletion($transaction_id, $task_data);
        } catch (\Exception $e) {
            error_log('CRM task logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log document upload to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $document_data Document data (filename, file_type, file_size, etc.)
     */
    public static function logDocumentUpload(int $transaction_id, array $document_data): void {
        try {
            $service = new ActivitySyncService();
            $service->logDocumentUpload($transaction_id, $document_data);
        } catch (\Exception $e) {
            error_log('CRM document logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log transaction status change to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param string $old_status Old status
     * @param string $new_status New status
     */
    public static function logStatusChange(int $transaction_id, string $old_status, string $new_status): void {
        try {
            $service = new ActivitySyncService();
            $service->logStatusChange($transaction_id, $old_status, $new_status);
        } catch (\Exception $e) {
            error_log('CRM status change logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log vendor interaction to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $vendor_data Vendor data (vendor_name, service_type, status, etc.)
     */
    public static function logVendorInteraction(int $transaction_id, array $vendor_data): void {
        try {
            $service = new ActivitySyncService();
            $service->logVendorInteraction($transaction_id, $vendor_data);
        } catch (\Exception $e) {
            error_log('CRM vendor logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log note added to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $note_data Note data (content, created_by, etc.)
     */
    public static function logNote(int $transaction_id, array $note_data): void {
        try {
            $service = new ActivitySyncService();
            $service->logNote($transaction_id, $note_data);
        } catch (\Exception $e) {
            error_log('CRM note logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log email sent to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param array $email_data Email data (to, subject, body, etc.)
     */
    public static function logEmailSent(int $transaction_id, array $email_data): void {
        try {
            $service = new ActivitySyncService();
            $service->logEmailSent($transaction_id, $email_data);
        } catch (\Exception $e) {
            error_log('CRM email logging error: ' . $e->getMessage());
        }
    }

    /**
     * Log custom activity to CRM
     *
     * @param int $transaction_id Transaction ID
     * @param string $subject Activity subject
     * @param string $description Activity description
     * @param array $options Optional settings (type, date, status, priority)
     */
    public static function logCustomActivity(int $transaction_id, string $subject, string $description, array $options = []): void {
        try {
            $activity_data = [
                'type' => $options['type'] ?? 'custom',
                'subject' => $subject,
                'description' => $description,
                'date' => $options['date'] ?? date('Y-m-d'),
                'status' => $options['status'] ?? 'Completed',
                'priority' => $options['priority'] ?? 'Normal',
            ];

            $service = new ActivitySyncService();

            // Use reflection to call the private logActivity method
            $reflection = new \ReflectionClass($service);
            $method = $reflection->getMethod('logActivity');
            $method->setAccessible(true);
            $method->invoke($service, $transaction_id, $activity_data);
        } catch (\Exception $e) {
            error_log('CRM custom activity logging error: ' . $e->getMessage());
        }
    }
}
