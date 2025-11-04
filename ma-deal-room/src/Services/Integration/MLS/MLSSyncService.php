<?php
/**
 * MLS Synchronization Service
 *
 * Handles automatic status synchronization between MLS and deal room transactions.
 * Fetches listing updates from MLS, applies changes to transactions, and logs all sync activity.
 *
 * @package    MA_Deal_Room
 * @subpackage Services/Integration/MLS
 * @since      1.0.0
 */

namespace MADealRoom\Services\Integration\MLS;

use MADealRoom\Repositories\TransactionRepository;
use Exception;
use wpdb;

/**
 * Class MLSSyncService
 *
 * Synchronizes listing status and data between MLS and deal room.
 */
class MLSSyncService {

	/**
	 * MLS Client Factory
	 *
	 * @var MLSClientFactory
	 */
	private MLSClientFactory $client_factory;

	/**
	 * Transaction Repository
	 *
	 * @var TransactionRepository
	 */
	private TransactionRepository $transaction_repository;

	/**
	 * WordPress Database
	 *
	 * @var wpdb
	 */
	private wpdb $wpdb;

	/**
	 * Constructor
	 *
	 * @param MLSClientFactory      $client_factory         MLS client factory.
	 * @param TransactionRepository $transaction_repository Transaction repository.
	 */
	public function __construct(
		MLSClientFactory $client_factory,
		TransactionRepository $transaction_repository
	) {
		global $wpdb;

		$this->client_factory         = $client_factory;
		$this->transaction_repository = $transaction_repository;
		$this->wpdb                   = $wpdb;
	}

	/**
	 * Sync a single transaction with MLS
	 *
	 * @param int    $transaction_id Transaction ID.
	 * @param int    $account_id     Account ID.
	 * @param string $sync_type      Sync type (automatic or manual).
	 * @param int    $config_id      Optional MLS config ID.
	 * @return array Sync result with success status and details.
	 */
	public function syncTransaction(
		int $transaction_id,
		int $account_id,
		string $sync_type = 'automatic',
		?int $config_id = null
	): array {
		// Get transaction
		$transaction = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->wpdb->prefix}ma_transactions
				WHERE id = %d AND account_id = %d",
				$transaction_id,
				$account_id
			),
			ARRAY_A
		);

		if (!$transaction) {
			return [
				'success' => false,
				'message' => 'Transaction not found',
			];
		}

		// Check if transaction has MLS number
		if (empty($transaction['mls_number'])) {
			return [
				'success' => false,
				'message' => 'Transaction is not linked to MLS',
			];
		}

		try {
			// Get MLS client
			$client = $this->client_factory->create($account_id, $config_id);

			// Fetch listing from MLS
			$listing = $client->getListing($transaction['mls_number']);

			if (!$listing) {
				$this->logSync(
					$transaction_id,
					$transaction['mls_number'],
					null,
					null,
					null,
					null,
					$sync_type,
					'failed',
					'Listing not found in MLS'
				);

				return [
					'success' => false,
					'message' => 'Listing not found in MLS',
				];
			}

			// Detect changes
			$changes = $this->detectChanges($transaction, $listing);

			if (empty($changes)) {
				// No changes - still log as successful sync
				$this->logSync(
					$transaction_id,
					$transaction['mls_number'],
					$transaction['mls_status'],
					$transaction['mls_status'],
					$transaction['purchase_price'],
					$transaction['purchase_price'],
					$sync_type,
					'success',
					null,
					[]
				);

				return [
					'success'        => true,
					'message'        => 'No changes detected',
					'changes'        => [],
					'has_changes'    => false,
					'mls_last_sync'  => current_time('mysql'),
				];
			}

			// Apply changes to transaction
			$update_data = [];
			$old_status = $transaction['mls_status'];
			$new_status = $listing['status'];
			$old_price = $transaction['purchase_price'];
			$new_price = $listing['price'];

			foreach ($changes as $field => $change) {
				$update_data[$field] = $change['new'];
			}

			// Always update last sync timestamp
			$update_data['mls_last_sync'] = current_time('mysql');

			// Update transaction
			$updated = $this->wpdb->update(
				"{$this->wpdb->prefix}ma_transactions",
				$update_data,
				[
					'id'         => $transaction_id,
					'account_id' => $account_id,
				],
				$this->getUpdateFormats($update_data),
				['%d', '%d']
			);

			if ($updated === false) {
				throw new Exception('Failed to update transaction');
			}

			// Log successful sync
			$this->logSync(
				$transaction_id,
				$transaction['mls_number'],
				$old_status,
				$new_status,
				$old_price,
				$new_price,
				$sync_type,
				'success',
				null,
				$changes
			);

			// Fire action hook
			do_action('ma_deal_mls_transaction_synced', $transaction_id, $changes, $listing);

			return [
				'success'       => true,
				'message'       => sprintf('%d field(s) updated', count($changes)),
				'changes'       => $changes,
				'has_changes'   => true,
				'mls_last_sync' => $update_data['mls_last_sync'],
			];

		} catch (Exception $e) {
			// Log failed sync
			$this->logSync(
				$transaction_id,
				$transaction['mls_number'],
				null,
				null,
				null,
				null,
				$sync_type,
				'failed',
				$e->getMessage()
			);

			return [
				'success' => false,
				'message' => $e->getMessage(),
			];
		}
	}

	/**
	 * Sync all MLS-linked transactions for an account
	 *
	 * @param int    $account_id Account ID.
	 * @param bool   $queue_sync Whether to queue sync jobs.
	 * @param int    $config_id  Optional MLS config ID.
	 * @return array Sync results with statistics.
	 */
	public function syncAllTransactions(
		int $account_id,
		bool $queue_sync = true,
		?int $config_id = null
	): array {
		// Get all MLS-linked transactions
		$transactions = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT id, mls_number FROM {$this->wpdb->prefix}ma_transactions
				WHERE account_id = %d AND mls_number IS NOT NULL AND mls_number != ''",
				$account_id
			),
			ARRAY_A
		);

		if (empty($transactions)) {
			return [
				'success' => true,
				'message' => 'No MLS-linked transactions found',
				'stats'   => [
					'total'     => 0,
					'synced'    => 0,
					'failed'    => 0,
					'unchanged' => 0,
					'queued'    => 0,
				],
			];
		}

		// DISABLED: Queue system not implemented - always sync synchronously
		// if ($queue_sync) {
		// 	... queue logic removed ...
		// }

		// Sync synchronously
		$stats = [
			'total'     => count($transactions),
			'synced'    => 0,
			'failed'    => 0,
			'unchanged' => 0,
			'queued'    => 0,
		];

		foreach ($transactions as $transaction) {
			$result = $this->syncTransaction(
				$transaction['id'],
				$account_id,
				'automatic',
				$config_id
			);

			if ($result['success']) {
				if ($result['has_changes'] ?? false) {
					$stats['synced']++;
				} else {
					$stats['unchanged']++;
				}
			} else {
				$stats['failed']++;
			}
		}

		return [
			'success' => true,
			'message' => sprintf(
				'Synced %d, Failed %d, Unchanged %d',
				$stats['synced'],
				$stats['failed'],
				$stats['unchanged']
			),
			'stats'   => $stats,
		];
	}

	/**
	 * Get sync history for a transaction
	 *
	 * @param int $transaction_id Transaction ID.
	 * @param int $limit          Max number of records to return.
	 * @return array Sync history records.
	 */
	public function getSyncHistory(int $transaction_id, int $limit = 50): array {
		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->wpdb->prefix}ma_deal_mls_sync_log
				WHERE transaction_id = %d
				ORDER BY sync_date DESC
				LIMIT %d",
				$transaction_id,
				$limit
			),
			ARRAY_A
		);

		// Decode JSON changes
		foreach ($results as &$record) {
			if (!empty($record['changes'])) {
				$record['changes'] = json_decode($record['changes'], true);
			} else {
				$record['changes'] = [];
			}
		}

		return $results;
	}

	/**
	 * Get latest sync status for a transaction
	 *
	 * @param int $transaction_id Transaction ID.
	 * @return array|null Latest sync record or null.
	 */
	public function getLatestSync(int $transaction_id): ?array {
		$record = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->wpdb->prefix}ma_deal_mls_sync_log
				WHERE transaction_id = %d
				ORDER BY sync_date DESC
				LIMIT 1",
				$transaction_id
			),
			ARRAY_A
		);

		if (!$record) {
			return null;
		}

		// Decode JSON changes
		if (!empty($record['changes'])) {
			$record['changes'] = json_decode($record['changes'], true);
		} else {
			$record['changes'] = [];
		}

		return $record;
	}

	/**
	 * Detect changes between transaction and MLS listing
	 *
	 * @param array $transaction Current transaction data.
	 * @param array $listing     MLS listing data.
	 * @return array Array of changes with old and new values.
	 */
	private function detectChanges(array $transaction, array $listing): array {
		$changes = [];

		// Field mappings (transaction field => MLS field)
		$field_map = [
			'mls_status'       => 'status',
			'purchase_price'   => 'price',
			'property_address' => 'address',
			'city'             => 'city',
			'state'            => 'state',
			'zip_code'         => 'zip',
			'bedrooms'         => 'bedrooms',
			'bathrooms'        => 'bathrooms',
			'square_feet'      => 'square_feet',
		];

		foreach ($field_map as $transaction_field => $mls_field) {
			if (!isset($listing[$mls_field])) {
				continue;
			}

			$old_value = $transaction[$transaction_field] ?? null;
			$new_value = $listing[$mls_field];

			// Normalize values for comparison
			$old_normalized = $this->normalizeValue($old_value);
			$new_normalized = $this->normalizeValue($new_value);

			if ($old_normalized !== $new_normalized) {
				$changes[$transaction_field] = [
					'old' => $old_value,
					'new' => $new_value,
				];
			}
		}

		return $changes;
	}

	/**
	 * Normalize value for comparison
	 *
	 * @param mixed $value Value to normalize.
	 * @return mixed Normalized value.
	 */
	private function normalizeValue($value) {
		if (is_null($value)) {
			return '';
		}

		if (is_numeric($value)) {
			return (float) $value;
		}

		return trim((string) $value);
	}

	/**
	 * Log sync activity
	 *
	 * @param int         $transaction_id Transaction ID.
	 * @param string      $mls_number     MLS number.
	 * @param string|null $old_status     Old status.
	 * @param string|null $new_status     New status.
	 * @param float|null  $old_price      Old price.
	 * @param float|null  $new_price      New price.
	 * @param string      $sync_type      Sync type (automatic or manual).
	 * @param string      $sync_result    Sync result (success, failed, partial).
	 * @param string|null $error_message  Error message if failed.
	 * @param array       $changes        Array of all field changes.
	 * @return bool True if logged successfully.
	 */
	private function logSync(
		int $transaction_id,
		string $mls_number,
		?string $old_status,
		?string $new_status,
		?float $old_price,
		?float $new_price,
		string $sync_type,
		string $sync_result,
		?string $error_message = null,
		array $changes = []
	): bool {
		$result = $this->wpdb->insert(
			"{$this->wpdb->prefix}ma_deal_mls_sync_log",
			[
				'transaction_id' => $transaction_id,
				'mls_number'     => $mls_number,
				'old_status'     => $old_status,
				'new_status'     => $new_status,
				'old_price'      => $old_price,
				'new_price'      => $new_price,
				'sync_date'      => current_time('mysql'),
				'changes'        => !empty($changes) ? wp_json_encode($changes) : null,
				'sync_type'      => $sync_type,
				'sync_result'    => $sync_result,
				'error_message'  => $error_message,
			],
			['%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%s']
		);

		return $result !== false;
	}

	/**
	 * Get format strings for wpdb update
	 *
	 * @param array $data Data being updated.
	 * @return array Format strings.
	 */
	private function getUpdateFormats(array $data): array {
		$formats = [];

		foreach ($data as $key => $value) {
			if (in_array($key, ['purchase_price', 'bedrooms', 'bathrooms', 'square_feet'], true)) {
				$formats[] = is_float($value) ? '%f' : '%d';
			} else {
				$formats[] = '%s';
			}
		}

		return $formats;
	}

	/**
	 * Schedule automatic hourly sync
	 *
	 * @param int  $account_id Account ID.
	 * @param int  $config_id  Optional MLS config ID.
	 * @return bool True if scheduled successfully.
	 */
	public function scheduleAutoSync(int $account_id, ?int $config_id = null): bool {
		// Check if already scheduled
		$scheduled = wp_next_scheduled('ma_deal_mls_auto_sync', [$account_id, $config_id]);

		if ($scheduled) {
			return true; // Already scheduled
		}

		// Schedule hourly sync
		return wp_schedule_event(
			time(),
			'hourly',
			'ma_deal_mls_auto_sync',
			[$account_id, $config_id]
		) !== false;
	}

	/**
	 * Unschedule automatic sync
	 *
	 * @param int  $account_id Account ID.
	 * @param int  $config_id  Optional MLS config ID.
	 * @return bool True if unscheduled successfully.
	 */
	public function unscheduleAutoSync(int $account_id, ?int $config_id = null): bool {
		$scheduled = wp_next_scheduled('ma_deal_mls_auto_sync', [$account_id, $config_id]);

		if (!$scheduled) {
			return true; // Not scheduled
		}

		return wp_unschedule_event(
			$scheduled,
			'ma_deal_mls_auto_sync',
			[$account_id, $config_id]
		) !== false;
	}
}
