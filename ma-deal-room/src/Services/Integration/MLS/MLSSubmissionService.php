<?php
/**
 * MLS Submission Service
 *
 * Service for submitting property listings from deal room to MLS.
 * Handles validation, field mapping, photo upload, and submission tracking.
 *
 * @package    MA_Deal_Room
 * @subpackage Services/Integration/MLS
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\MLS;

use MADealRoom\Repositories\TransactionRepository;
use Exception;

/**
 * Class MLSSubmissionService
 *
 * Handles submitting properties from deal room to MLS.
 */
class MLSSubmissionService {

    /**
     * MLS Client Factory
     *
     * @var MLSClientFactory
     */
    private MLSClientFactory $factory;

    /**
     * Transaction Repository
     *
     * @var TransactionRepository
     */
    private TransactionRepository $transaction_repo;

    /**
     * Constructor
     *
     * @param MLSClientFactory      $factory         MLS client factory
     * @param TransactionRepository $transaction_repo Transaction repository
     */
    public function __construct(
        MLSClientFactory $factory,
        TransactionRepository $transaction_repo
    ) {
        $this->factory = $factory;
        $this->transaction_repo = $transaction_repo;
    }

    /**
     * Submit transaction to MLS
     *
     * @param int  $transaction_id Transaction ID
     * @param int  $account_id     Account ID
     * @param int  $user_id        User ID who initiated submission
     * @param bool $queue_submission Whether to queue submission as background job
     * @param int  $config_id      Optional MLS config ID
     * @return array Submission result
     *               [
     *                   'success' => bool,
     *                   'mls_number' => string,
     *                   'message' => string,
     *                   'errors' => array,
     *                   'warnings' => array
     *               ]
     */
    public function submitListing(
        int $transaction_id,
        int $account_id,
        int $user_id,
        bool $queue_submission = false,
        ?int $config_id = null
    ): array {
        try {
            // Get transaction
            $transaction = $this->transaction_repo->getById($transaction_id);

            if (!$transaction) {
                return [
                    'success' => false,
                    'mls_number' => '',
                    'message' => 'Transaction not found',
                    'errors' => ["Transaction #{$transaction_id} not found"],
                    'warnings' => [],
                ];
            }

            // Check if already submitted
            if (!empty($transaction['mls_number'])) {
                return [
                    'success' => false,
                    'mls_number' => $transaction['mls_number'],
                    'message' => 'Transaction already submitted to MLS',
                    'errors' => ["Already submitted as MLS #{$transaction['mls_number']}"],
                    'warnings' => [],
                ];
            }

            // If queueing, enqueue and return
            if ($queue_submission) {
                return $this->queueSubmission($transaction_id, $account_id, $user_id, $config_id);
            }

            // Validate transaction data
            $validation = $this->validateSubmissionData($transaction);

            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'mls_number' => '',
                    'message' => 'Validation failed',
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                ];
            }

            // Get MLS client
            $client = MLSClientFactory::createFromDatabase($account_id, $config_id);

            if (!$client) {
                return [
                    'success' => false,
                    'mls_number' => '',
                    'message' => 'No active MLS configuration found',
                    'errors' => ['MLS configuration not found or inactive'],
                    'warnings' => [],
                ];
            }

            // Check if provider supports submission
            if ($client->getProviderType() === 'rets') {
                return [
                    'success' => false,
                    'mls_number' => '',
                    'message' => 'RETS protocol does not support listing submission',
                    'errors' => ['This MLS provider does not support automated submissions. Please use the MLS web interface.'],
                    'warnings' => [],
                ];
            }

            // Map transaction to MLS format
            $listing_data = $this->mapTransactionToListing($transaction);

            // Submit to MLS
            $result = $client->submitListing($listing_data);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'mls_number' => '',
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? [$result['message']],
                    'warnings' => $validation['warnings'],
                ];
            }

            // Update transaction with MLS number
            $this->transaction_repo->update($transaction_id, [
                'mls_number' => $result['mls_number'],
                'mls_status' => 'Active',
                'mls_last_sync' => current_time('mysql'),
                'mls_feed_id' => $config_id,
            ]);

            // Upload photos if available
            $photo_result = $this->uploadPhotos($transaction_id, $result['mls_number'], $client);

            // Log the submission
            do_action('ma_deal_mls_listing_submitted', $transaction_id, $result['mls_number'], $account_id);

            return [
                'success' => true,
                'mls_number' => $result['mls_number'],
                'message' => 'Listing submitted successfully to MLS',
                'errors' => [],
                'warnings' => array_merge($validation['warnings'], $photo_result['errors'] ?? []),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'mls_number' => '',
                'message' => 'Submission failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
                'warnings' => [],
            ];
        }
    }

    /**
     * Update existing MLS listing
     *
     * @param int    $transaction_id Transaction ID
     * @param int    $account_id     Account ID
     * @param array  $updates        Fields to update
     * @param int    $config_id      Optional MLS config ID
     * @return array Update result
     *               [
                   'success' => bool,
     *                   'message' => string,
     *                   'errors' => array
     *               ]
     */
    public function updateListing(
        int $transaction_id,
        int $account_id,
        array $updates,
        ?int $config_id = null
    ): array {
        try {
            // Get transaction
            $transaction = $this->transaction_repo->getById($transaction_id);

            if (!$transaction || empty($transaction['mls_number'])) {
                return [
                    'success' => false,
                    'message' => 'Transaction not submitted to MLS',
                    'errors' => ['Cannot update: transaction not submitted to MLS'],
                ];
            }

            // Get MLS client
            $client = MLSClientFactory::createFromDatabase($account_id, $config_id);

            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'No active MLS configuration found',
                    'errors' => ['MLS configuration not found or inactive'],
                ];
            }

            // Map updates to MLS format
            $mls_updates = $this->mapTransactionToListing($updates);

            // Update listing
            $result = $client->updateListing($transaction['mls_number'], $mls_updates);

            if ($result['success']) {
                // Update last sync time
                $this->transaction_repo->update($transaction_id, [
                    'mls_last_sync' => current_time('mysql'),
                ]);

                // Log the update
                do_action('ma_deal_mls_listing_updated', $transaction_id, $transaction['mls_number'], $account_id);
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Update MLS listing status
     *
     * @param int    $transaction_id Transaction ID
     * @param int    $account_id     Account ID
     * @param string $status         New status
     * @param int    $config_id      Optional MLS config ID
     * @return array Status update result
     */
    public function updateStatus(
        int $transaction_id,
        int $account_id,
        string $status,
        ?int $config_id = null
    ): array {
        try {
            // Get transaction
            $transaction = $this->transaction_repo->getById($transaction_id);

            if (!$transaction || empty($transaction['mls_number'])) {
                return [
                    'success' => false,
                    'message' => 'Transaction not submitted to MLS',
                ];
            }

            // Get MLS client
            $client = MLSClientFactory::createFromDatabase($account_id, $config_id);

            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'No active MLS configuration found',
                ];
            }

            // Update status
            $result = $client->updateStatus($transaction['mls_number'], $status);

            if ($result['success']) {
                // Update transaction
                $this->transaction_repo->update($transaction_id, [
                    'mls_status' => $status,
                    'mls_last_sync' => current_time('mysql'),
                ]);

                // Log the status change
                do_action('ma_deal_mls_status_updated', $transaction_id, $transaction['mls_number'], $status, $account_id);
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Status update failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Withdraw listing from MLS
     *
     * @param int    $transaction_id Transaction ID
     * @param int    $account_id     Account ID
     * @param string $reason         Reason for withdrawal
     * @param int    $config_id      Optional MLS config ID
     * @return array Withdrawal result
     */
    public function withdrawListing(
        int $transaction_id,
        int $account_id,
        string $reason = '',
        ?int $config_id = null
    ): array {
        try {
            // Get transaction
            $transaction = $this->transaction_repo->getById($transaction_id);

            if (!$transaction || empty($transaction['mls_number'])) {
                return [
                    'success' => false,
                    'message' => 'Transaction not submitted to MLS',
                ];
            }

            // Get MLS client
            $client = MLSClientFactory::createFromDatabase($account_id, $config_id);

            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'No active MLS configuration found',
                ];
            }

            // Delete/withdraw listing
            $result = $client->deleteListing($transaction['mls_number'], $reason);

            if ($result['success']) {
                // Update transaction (keep mls_number for history)
                $this->transaction_repo->update($transaction_id, [
                    'mls_status' => 'Withdrawn',
                    'mls_last_sync' => current_time('mysql'),
                ]);

                // Log the withdrawal
                do_action('ma_deal_mls_listing_withdrawn', $transaction_id, $transaction['mls_number'], $reason, $account_id);
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Withdrawal failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get submission status for a transaction
     *
     * @param int $transaction_id Transaction ID
     * @return array Submission status
     *               [
     *                   'submitted' => bool,
     *                   'mls_number' => string|null,
     *                   'mls_status' => string|null,
     *                   'last_sync' => string|null,
     *                   'can_submit' => bool,
     *                   'can_update' => bool
     *               ]
     */
    public function getSubmissionStatus(int $transaction_id): array {
        $transaction = $this->transaction_repo->getById($transaction_id);

        if (!$transaction) {
            return [
                'submitted' => false,
                'mls_number' => null,
                'mls_status' => null,
                'last_sync' => null,
                'can_submit' => false,
                'can_update' => false,
            ];
        }

        $submitted = !empty($transaction['mls_number']);

        return [
            'submitted' => $submitted,
            'mls_number' => $transaction['mls_number'] ?? null,
            'mls_status' => $transaction['mls_status'] ?? null,
            'last_sync' => $transaction['mls_last_sync'] ?? null,
            'can_submit' => !$submitted,
            'can_update' => $submitted,
        ];
    }

    /**
     * Validate submission data
     *
     * @param array $transaction Transaction data
     * @return array Validation result
     *               [
     *                   'valid' => bool,
     *                   'errors' => array,
     *                   'warnings' => array
     *               ]
     */
    private function validateSubmissionData(array $transaction): array {
        $errors = [];
        $warnings = [];

        // Required fields for MLS submission
        $required_fields = [
            'property_address' => 'Property address',
            'city' => 'City',
            'state' => 'State',
            'zip_code' => 'ZIP code',
            'purchase_price' => 'Price',
        ];

        foreach ($required_fields as $field => $label) {
            if (empty($transaction[$field])) {
                $errors[] = "{$label} is required for MLS submission";
            }
        }

        // Validate price
        if (!empty($transaction['purchase_price']) && $transaction['purchase_price'] <= 0) {
            $errors[] = "Price must be greater than zero";
        }

        // Warn if no description
        if (empty($transaction['description'])) {
            $warnings[] = "No property description provided - listing may be less appealing";
        }

        // Warn if transaction type not suitable for MLS
        $suitable_types = ['sale_seller', 'sale_buyer', 'sale_commercial'];
        if (!empty($transaction['transaction_type']) && !in_array($transaction['transaction_type'], $suitable_types)) {
            $warnings[] = "Transaction type '{$transaction['transaction_type']}' may not be suitable for MLS listing";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Map transaction data to MLS listing format
     *
     * @param array $transaction Transaction data
     * @return array MLS listing data
     */
    private function mapTransactionToListing(array $transaction): array {
        return [
            'address' => $transaction['property_address'] ?? '',
            'city' => $transaction['city'] ?? '',
            'state' => $transaction['state'] ?? '',
            'zip' => $transaction['zip_code'] ?? '',
            'price' => $transaction['purchase_price'] ?? 0,
            'bedrooms' => $transaction['bedrooms'] ?? 0,
            'bathrooms' => $transaction['bathrooms'] ?? 0,
            'square_feet' => $transaction['square_feet'] ?? 0,
            'property_type' => $this->mapTransactionTypeToPropertyType($transaction['transaction_type'] ?? ''),
            'description' => $transaction['description'] ?? '',
            'status' => 'Active',
        ];
    }

    /**
     * Map transaction type to MLS property type
     *
     * @param string $transaction_type Transaction type
     * @return string MLS property type
     */
    private function mapTransactionTypeToPropertyType(string $transaction_type): string {
        $type_map = [
            'sale_seller' => 'Residential',
            'sale_buyer' => 'Residential',
            'sale_commercial' => 'Commercial',
            'rental_landlord' => 'Residential',
            'rental_tenant' => 'Residential',
        ];

        return $type_map[$transaction_type] ?? 'Residential';
    }

    /**
     * Upload photos for a listing
     *
     * @param int                  $transaction_id Transaction ID
     * @param string               $mls_number     MLS listing number
     * @param MLSClientInterface   $client         MLS client
     * @return array Upload result
     */
    private function uploadPhotos(int $transaction_id, string $mls_number, MLSClientInterface $client): array {
        global $wpdb;

        // Get photos/documents for transaction
        $attachments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ma_deal_documents
             WHERE transaction_id = %d AND document_type IN ('photo', 'image')
             ORDER BY display_order ASC LIMIT 20",
            $transaction_id
        ), ARRAY_A);

        if (empty($attachments)) {
            return [
                'success' => true,
                'uploaded' => 0,
                'errors' => [],
            ];
        }

        $photo_paths = [];

        foreach ($attachments as $attachment) {
            if (!empty($attachment['file_path']) && file_exists($attachment['file_path'])) {
                $photo_paths[] = $attachment['file_path'];
            }
        }

        if (empty($photo_paths)) {
            return [
                'success' => true,
                'uploaded' => 0,
                'errors' => [],
            ];
        }

        // Upload photos
        return $client->uploadPhotos($mls_number, $photo_paths);
    }

    /**
     * Queue submission as background job
     *
     * DISABLED: Queue system not implemented
     * Submissions now process synchronously
     *
     * @param int      $transaction_id Transaction ID
     * @param int      $account_id     Account ID
     * @param int      $user_id        User ID
     * @param int|null $config_id      MLS config ID
     * @return array Queue result
     */
    private function queueSubmission(
        int $transaction_id,
        int $account_id,
        int $user_id,
        ?int $config_id
    ): array {
        // Queue system not implemented - return error to force synchronous submission
        return [
            'success' => false,
            'mls_number' => '',
            'message' => 'Background queue not available - use synchronous submission instead',
            'errors' => ['Queue system not implemented'],
            'warnings' => [],
            'job_id' => null,
        ];
    }
}
