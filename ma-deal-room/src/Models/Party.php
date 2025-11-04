<?php
/**
 * Party Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Party model representing a transaction contact
 */
class Party {
	public int $id;
	public int $transaction_id;
	public string $role; // buyer, seller, buyer_attorney, seller_attorney, buyer_lender, buyer_agent, seller_agent, title_company, inspector, appraiser, hoa_manager, septic_inspector, fire_dept, other
	public ?string $company_name = null;
	public string $contact_name;
	public ?string $email = null;
	public ?string $phone = null;
	public ?string $address = null;
	public ?array $metadata = null;
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
				if ($key === 'metadata' && is_string($value)) {
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
		if (isset($data['metadata']) && is_array($data['metadata'])) {
			$data['metadata'] = json_encode($data['metadata']);
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
