<?php
/**
 * MLS Import Service
 *
 * Service for importing property listings from MLS into deal room transactions.
 * Handles search, duplicate detection, field mapping, and photo import.
 *
 * @package    MA_Deal_Room
 * @subpackage Services/Integration/MLS
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\MLS;

use MADealRoom\Repositories\TransactionRepository;
use Exception;

/**
 * Class MLSImportService
 *
 * Handles importing properties from MLS into deal room.
 */
class MLSImportService {

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
     * Search MLS listings
     *
     * @param int   $account_id Account ID
     * @param array $criteria   Search criteria
     * @param int   $config_id  Optional MLS config ID
     * @return array Search results
     *               [
     *                   'success' => bool,
     *                   'listings' => array,
     *                   'count' => int,
     *                   'message' => string
     *               ]
     */
    public function searchListings(int $account_id, array $criteria, ?int $config_id = null): array {
        try {
            $client = MLSClientFactory::createFromDatabase($account_id, $config_id);

            if (!$client) {
                return [
                    'success' => false,
                    'listings' => [],
                    'count' => 0,
                    'message' => 'No active MLS configuration found for this account',
                ];
            }

            $listings = $client->searchListings($criteria);

            // Check for duplicates
            foreach ($listings as &$listing) {
                $listing['is_imported'] = $this->isListingImported($account_id, $listing['mls_number']);
                $listing['existing_transaction_id'] = $this->getExistingTransactionId($account_id, $listing['mls_number']);
            }

            return [
                'success' => true,
                'listings' => $listings,
                'count' => count($listings),
                'message' => 'Search completed successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'listings' => [],
                'count' => 0,
                'message' => 'Search failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Import a single property from MLS
     *
     * @param int    $account_id  Account ID
     * @param string $mls_number  MLS listing number
     * @param int    $user_id     User ID who initiated import
     * @param bool   $queue_photos Whether to queue photo downloads
     * @param int    $config_id   Optional MLS config ID
     * @return array Import result
     *               [
     *                   'success' => bool,
     *                   'transaction_id' => int,
     *                   'message' => string,
     *                   'errors' => array
     *               ]
     */
    public function importProperty(
        int $account_id,
        string $mls_number,
        int $user_id,
        bool $queue_photos = true,
        ?int $config_id = null
    ): array {
        try {
            // Check for duplicate
            if ($this->isListingImported($account_id, $mls_number)) {
                $existing_id = $this->getExistingTransactionId($account_id, $mls_number);
                return [
                    'success' => false,
                    'transaction_id' => $existing_id,
                    'message' => 'Property already imported',
                    'errors' => ["MLS #{$mls_number} is already imported as transaction #{$existing_id}"],
                ];
            }

            // Get MLS client
            $client = MLSClientFactory::createFromDatabase($account_id, $config_id);

            if (!$client) {
                return [
                    'success' => false,
                    'transaction_id' => 0,
                    'message' => 'No active MLS configuration found',
                    'errors' => ['MLS configuration not found or inactive'],
                ];
            }

            // Fetch listing details
            $listing = $client->getListingDetails($mls_number);

            if (!$listing) {
                return [
                    'success' => false,
                    'transaction_id' => 0,
                    'message' => 'Listing not found in MLS',
                    'errors' => ["MLS #{$mls_number} not found"],
                ];
            }

            // Map MLS data to transaction data
            $transaction_data = $this->mapListingToTransaction($listing, $account_id, $user_id, $config_id);

            // Create transaction
            $transaction_id = $this->transaction_repo->create($transaction_data);

            if (!$transaction_id) {
                return [
                    'success' => false,
                    'transaction_id' => 0,
                    'message' => 'Failed to create transaction',
                    'errors' => ['Database insert failed'],
                ];
            }

            // Note: Photo download disabled (no queue system)
            // Photos can be added manually or implemented as synchronous download later
            // if ($queue_photos && !empty($listing['photos'])) {
            //     // Download photos synchronously here if needed
            // }

            // Log the import
            do_action('ma_deal_mls_property_imported', $transaction_id, $mls_number, $account_id);

            return [
                'success' => true,
                'transaction_id' => $transaction_id,
                'message' => 'Property imported successfully',
                'errors' => [],
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'transaction_id' => 0,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Import multiple properties (batch import)
     *
     * @param int   $account_id   Account ID
     * @param array $mls_numbers  Array of MLS listing numbers
     * @param int   $user_id      User ID
     * @param bool  $queue_photos Whether to queue photo downloads
     * @param int   $config_id    Optional MLS config ID
     * @return array Batch import result
     *               [
     *                   'success' => bool,
     *                   'imported' => int,
     *                   'skipped' => int,
     *                   'failed' => int,
     *                   'results' => array,
     *                   'message' => string
     *               ]
     */
    public function importMultiple(
        int $account_id,
        array $mls_numbers,
        int $user_id,
        bool $queue_photos = true,
        ?int $config_id = null
    ): array {
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $results = [];

        foreach ($mls_numbers as $mls_number) {
            $result = $this->importProperty($account_id, $mls_number, $user_id, $queue_photos, $config_id);

            $results[$mls_number] = $result;

            if ($result['success']) {
                $imported++;
            } elseif (strpos($result['message'], 'already imported') !== false) {
                $skipped++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $imported > 0,
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed,
            'results' => $results,
            'message' => "Imported {$imported}, skipped {$skipped}, failed {$failed}",
        ];
    }

    /**
     * Check if listing is already imported
     *
     * @param int    $account_id Account ID
     * @param string $mls_number MLS listing number
     * @return bool True if already imported
     */
    public function isListingImported(int $account_id, string $mls_number): bool {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_transactions';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE account_id = %d AND mls_number = %s",
            $account_id,
            $mls_number
        ));

        return $count > 0;
    }

    /**
     * Get existing transaction ID for MLS listing
     *
     * @param int    $account_id Account ID
     * @param string $mls_number MLS listing number
     * @return int|null Transaction ID or null if not found
     */
    public function getExistingTransactionId(int $account_id, string $mls_number): ?int {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_transactions';

        $transaction_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE account_id = %d AND mls_number = %s LIMIT 1",
            $account_id,
            $mls_number
        ));

        return $transaction_id ? (int) $transaction_id : null;
    }

    /**
     * Map MLS listing data to transaction data
     *
     * @param array    $listing    MLS listing data
     * @param int      $account_id Account ID
     * @param int      $user_id    User ID
     * @param int|null $config_id  MLS config ID
     * @return array Transaction data
     */
    private function mapListingToTransaction(
        array $listing,
        int $account_id,
        int $user_id,
        ?int $config_id = null
    ): array {
        // Determine transaction type based on property type
        $transaction_type = $this->mapPropertyTypeToTransactionType($listing['property_type']);

        // Build property address string
        $property_address = trim(sprintf(
            '%s, %s, %s %s',
            $listing['address'],
            $listing['city'],
            $listing['state'],
            $listing['zip']
        ));

        // Build transaction title
        $title = sprintf(
            '%s - %s',
            $property_address,
            $listing['mls_number']
        );

        return [
            'account_id' => $account_id,
            'title' => $title,
            'transaction_type' => $transaction_type,
            'status' => $this->mapMLSStatusToTransactionStatus($listing['status']),
            'property_address' => $property_address,
            'city' => $listing['city'],
            'state' => $listing['state'],
            'zip_code' => $listing['zip'],
            'purchase_price' => $listing['price'],
            'description' => $listing['description'],
            'mls_number' => $listing['mls_number'],
            'mls_status' => $listing['status'],
            'mls_last_sync' => current_time('mysql'),
            'mls_feed_id' => $config_id,
            'created_by' => $user_id,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
    }

    /**
     * Map MLS property type to transaction type
     *
     * @param string $property_type MLS property type
     * @return string Transaction type
     */
    private function mapPropertyTypeToTransactionType(string $property_type): string {
        $property_type_lower = strtolower($property_type);

        if (strpos($property_type_lower, 'commercial') !== false) {
            return 'sale_commercial';
        }

        if (strpos($property_type_lower, 'land') !== false) {
            return 'sale_buyer';
        }

        // Default to residential sale
        return 'sale_seller';
    }

    /**
     * Map MLS status to transaction status
     *
     * @param string $mls_status MLS status
     * @return string Transaction status
     */
    private function mapMLSStatusToTransactionStatus(string $mls_status): string {
        $status_map = [
            'Active' => 'active',
            'Pending' => 'under_contract',
            'Sold' => 'closed',
            'Closed' => 'closed',
            'Expired' => 'cancelled',
            'Withdrawn' => 'cancelled',
            'Canceled' => 'cancelled',
        ];

        return $status_map[$mls_status] ?? 'active';
    }

    /**
     * Queue photo download for a transaction
     *
     * DISABLED: Queue system not implemented
     * Photos can be downloaded synchronously or added manually
     *
     * @param int   $transaction_id Transaction ID
     * @param array $photo_urls     Array of photo URLs
     * @param int   $account_id     Account ID
     * @return void
     */
    private function queuePhotoDownload(int $transaction_id, array $photo_urls, int $account_id): void {
        // Queue system not implemented - photos disabled for now
        // Can be implemented as synchronous download if needed
        return;
    }

    /**
     * Get import statistics for an account
     *
     * @param int $account_id Account ID
     * @return array Import statistics
     *               [
     *                   'total_imported' => int,
     *                   'by_status' => array,
     *                   'by_type' => array,
     *                   'last_import' => string|null
     *               ]
     */
    public function getImportStats(int $account_id): array {
        global $wpdb;

        $table = $wpdb->prefix . 'ma_deal_transactions';

        // Total imported
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE account_id = %d AND mls_number IS NOT NULL",
            $account_id
        ));

        // By status
        $by_status = $wpdb->get_results($wpdb->prepare(
            "SELECT mls_status, COUNT(*) as count FROM {$table}
             WHERE account_id = %d AND mls_number IS NOT NULL
             GROUP BY mls_status",
            $account_id
        ), ARRAY_A);

        // By type
        $by_type = $wpdb->get_results($wpdb->prepare(
            "SELECT transaction_type, COUNT(*) as count FROM {$table}
             WHERE account_id = %d AND mls_number IS NOT NULL
             GROUP BY transaction_type",
            $account_id
        ), ARRAY_A);

        // Last import
        $last_import = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(mls_last_sync) FROM {$table} WHERE account_id = %d AND mls_number IS NOT NULL",
            $account_id
        ));

        return [
            'total_imported' => (int) $total,
            'by_status' => $by_status,
            'by_type' => $by_type,
            'last_import' => $last_import,
        ];
    }

    /**
     * Validate import data
     *
     * @param array $listing MLS listing data
     * @return array Validation result
     *               [
     *                   'valid' => bool,
     *                   'errors' => array,
     *                   'warnings' => array
     *               ]
     */
    public function validateImportData(array $listing): array {
        $errors = [];
        $warnings = [];

        // Required fields
        $required_fields = ['mls_number', 'address', 'city', 'state', 'zip', 'price'];

        foreach ($required_fields as $field) {
            if (empty($listing[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }

        // Validate price
        if (isset($listing['price']) && (!is_numeric($listing['price']) || $listing['price'] <= 0)) {
            $errors[] = "Invalid price value";
        }

        // Validate ZIP code format
        if (!empty($listing['zip']) && !preg_match('/^\d{5}(-\d{4})?$/', $listing['zip'])) {
            $warnings[] = "ZIP code format may be invalid";
        }

        // Warn if no photos
        if (empty($listing['photos'])) {
            $warnings[] = "No photos available for this listing";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
