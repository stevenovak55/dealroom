<?php
/**
 * Transaction Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Transaction model representing a property transaction
 */
class Transaction {
	public int $id;
	public int $account_id;
	public string $property_address;
	public string $property_city;
	public string $property_state = 'MA';
	public string $property_zip;
	public string $property_type; // SFH, Condo, Multifamily, Land, Commercial
	public ?int $property_year_built = null;
	public ?array $property_metadata = null;
	public ?float $sale_price = null;
	public string $transaction_side = 'listing'; // listing, buyer
	public string $transaction_type = 'buy_side'; // buy_side, sell_side, rental_landlord, rental_tenant, commercial_buy, commercial_sell
	public string $status = 'prospect'; // prospect, listing_active, under_agreement, closed, cancelled
	public ?string $listing_date = null;
	public ?string $offer_accepted_date = null;
	public ?string $ps_agreement_date = null;
	public ?string $loan_commitment_date = null;
	public ?string $closing_date = null;
	public ?string $actual_closing_date = null;
	public ?int $assigned_agent_id = null;
	public ?int $template_id = null;
	public ?string $notes = null;
	public ?string $mls_number = null;
	public ?float $list_price = null;
	public ?float $accepted_offer_price = null;
	public ?float $bedrooms = null;
	public ?float $bathrooms = null;
	public ?int $square_feet = null;
	public ?float $lot_size = null;
	public ?int $parking_spaces = null;
	public ?string $crm_opportunity_id = null;
	public ?string $crm_deal_id = null;
	public ?string $crm_provider = null;
	public ?string $crm_last_sync = null;
	public ?string $crm_sync_status = null;
	public ?string $crm_stage_mapping = null;
	public ?string $crm_url = null;
	public string $created_at;
	public string $updated_at;

	/**
	 * Constructor
	 *
	 * @param array $data Initial data
	 */
	public function __construct(array $data = []) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				// Handle JSON fields
				if ($key === 'property_metadata' && is_string($value)) {
					$this->$key = json_decode($value, true);
				} else {
					$this->$key = $value;
				}
			}
		}
	}

	/**
	 * Convert model to array
	 *
	 * @return array
	 */
	public function toArray(): array {
		$data = get_object_vars($this);

		// Convert metadata array to JSON string if needed
		if (isset($data['property_metadata']) && is_array($data['property_metadata'])) {
			$data['property_metadata'] = json_encode($data['property_metadata']);
		}

		// Add transaction_id as alias for id (for React compatibility)
		$data['transaction_id'] = $this->id;

		return $data;
	}

	/**
	 * Create instance from array
	 *
	 * @param array $data Data array
	 * @return self
	 */
	public static function fromArray(array $data): self {
		return new self($data);
	}

	/**
	 * Get valid transaction types
	 *
	 * @return array
	 */
	public static function getValidTransactionTypes(): array {
		return [
			'buy_side',
			'sell_side',
			'rental_landlord',
			'rental_tenant',
			'commercial_buy',
			'commercial_sell'
		];
	}

	/**
	 * Set transaction type with validation
	 *
	 * @param string $type Transaction type
	 * @throws \InvalidArgumentException If type is invalid
	 * @return void
	 */
	public function setTransactionType(string $type): void {
		if (!in_array($type, self::getValidTransactionTypes())) {
			throw new \InvalidArgumentException("Invalid transaction type: {$type}");
		}
		$this->transaction_type = $type;
	}

	/**
	 * Get transaction type
	 *
	 * @return string
	 */
	public function getTransactionType(): string {
		return $this->transaction_type;
	}

	/**
	 * Check if this is a buy-side transaction
	 *
	 * @return bool
	 */
	public function isBuySide(): bool {
		return $this->transaction_type === 'buy_side' || $this->transaction_type === 'commercial_buy';
	}

	/**
	 * Check if this is a sell-side transaction
	 *
	 * @return bool
	 */
	public function isSellSide(): bool {
		return $this->transaction_type === 'sell_side' || $this->transaction_type === 'commercial_sell';
	}

	/**
	 * Check if this is a rental transaction
	 *
	 * @return bool
	 */
	public function isRental(): bool {
		return $this->transaction_type === 'rental_landlord' || $this->transaction_type === 'rental_tenant';
	}

	/**
	 * Check if this involves property transfer (not rentals)
	 *
	 * @return bool
	 */
	public function involvesPropertyTransfer(): bool {
		return !$this->isRental();
	}

	/**
	 * Sync this transaction to CRM
	 *
	 * @param string|null $provider_type Optional CRM provider (salesforce/hubspot). If null, syncs to all active CRMs.
	 * @return array Sync results
	 */
	public function syncToCRM(?string $provider_type = null): array {
		// Lazy load the DealSyncService to avoid circular dependencies
		$sync_service = new \MA_Deal_Room\Services\Integration\CRM\DealSyncService();

		$results = [];

		if ($provider_type) {
			// Sync to specific provider
			try {
				$result = $sync_service->syncToCRM($this->account_id, $provider_type, [
					'transaction_ids' => [$this->id],
				]);
				$results[$provider_type] = $result;
			} catch (\Exception $e) {
				$results[$provider_type] = [
					'success' => false,
					'message' => $e->getMessage(),
				];
			}
		} else {
			// Sync to all active CRM configs
			$config_repository = new \MA_Deal_Room\Repositories\CRMConfigRepository();
			$configs = $config_repository->getByAccount($this->account_id);

			foreach ($configs as $config) {
				if (!$config['sync_deals'] || !$config['sync_enabled']) {
					continue;
				}

				try {
					$result = $sync_service->syncToCRM($this->account_id, $config['provider_type'], [
						'transaction_ids' => [$this->id],
					]);
					$results[$config['provider_type']] = $result;
				} catch (\Exception $e) {
					$results[$config['provider_type']] = [
						'success' => false,
						'message' => $e->getMessage(),
					];
				}
			}
		}

		return $results;
	}

	/**
	 * Update CRM stage when transaction status changes
	 *
	 * @param string $new_status New transaction status
	 * @param string|null $old_status Optional old status for activity logging
	 * @return array Sync results for each configured CRM
	 */
	public function updateCRMStage(string $new_status, ?string $old_status = null): array {
		$sync_service = new \MA_Deal_Room\Services\Integration\CRM\DealSyncService();
		$results = $sync_service->syncStatusChange($this->id, $new_status);

		// Also log the status change as an activity
		if ($old_status && $old_status !== $new_status) {
			$activity_service = new \MA_Deal_Room\Services\Integration\CRM\ActivitySyncService();
			$activity_service->logStatusChange($this->id, $old_status, $new_status);
		}

		return $results;
	}

	/**
	 * Get CRM URL for this transaction
	 *
	 * @param string $provider_type CRM provider (salesforce/hubspot)
	 * @return string|null CRM URL or null if not synced
	 */
	public function getCRMUrl(string $provider_type): ?string {
		global $wpdb;

		$transaction_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT crm_opportunity_id, crm_deal_id, crm_url FROM {$wpdb->prefix}ma_deal_transactions WHERE id = %d",
				$this->id
			),
			ARRAY_A
		);

		if (!$transaction_data) {
			return null;
		}

		// If stored URL exists, return it
		if (!empty($transaction_data['crm_url'])) {
			return $transaction_data['crm_url'];
		}

		// Otherwise construct URL from CRM ID
		if ($provider_type === 'salesforce' && !empty($transaction_data['crm_opportunity_id'])) {
			$config_repository = new \MA_Deal_Room\Repositories\CRMConfigRepository();
			$config = $config_repository->getByAccountAndProvider($this->account_id, 'salesforce');
			$instance_url = $config['instance_url'] ?? 'https://login.salesforce.com';
			return "{$instance_url}/{$transaction_data['crm_opportunity_id']}";
		}

		if ($provider_type === 'hubspot' && !empty($transaction_data['crm_deal_id'])) {
			return "https://app.hubspot.com/contacts/{$transaction_data['crm_deal_id']}";
		}

		return null;
	}

	/**
	 * Check if transaction is synced to CRM
	 *
	 * @param string $provider_type CRM provider
	 * @return bool True if synced
	 */
	public function isSyncedToCRM(string $provider_type): bool {
		global $wpdb;

		$field = $provider_type === 'salesforce' ? 'crm_opportunity_id' : 'crm_deal_id';

		$crm_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$field} FROM {$wpdb->prefix}ma_deal_transactions WHERE id = %d AND crm_provider = %s",
				$this->id,
				$provider_type
			)
		);

		return !empty($crm_id);
	}
}
