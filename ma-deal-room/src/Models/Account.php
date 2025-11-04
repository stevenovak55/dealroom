<?php
/**
 * Account Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Account model representing a multi-tenant organization
 */
class Account {
	/**
	 * Account ID
	 *
	 * @var int
	 */
	public int $id;

	/**
	 * Account name (agency/brokerage name)
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * WordPress user ID of account owner
	 *
	 * @var int
	 */
	public int $owner_user_id;

	/**
	 * Account status
	 *
	 * @var string (active, suspended, trial, cancelled)
	 */
	public string $status = 'active';

	/**
	 * Account settings (JSON)
	 *
	 * @var array|null
	 */
	public ?array $settings = null;

	/**
	 * Subscription tier
	 *
	 * @var string
	 */
	public string $subscription_tier = 'free';

	/**
	 * Subscription expiration date
	 *
	 * @var string|null
	 */
	public ?string $subscription_expires_at = null;

	/**
	 * Created at timestamp
	 *
	 * @var string
	 */
	public string $created_at;

	/**
	 * Updated at timestamp
	 *
	 * @var string
	 */
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
				if ($key === 'settings' && is_string($value)) {
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

		// Convert settings array to JSON string if needed
		if (isset($data['settings']) && is_array($data['settings'])) {
			$data['settings'] = json_encode($data['settings']);
		}

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
}
