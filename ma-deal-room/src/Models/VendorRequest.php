<?php
/**
 * VendorRequest Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * VendorRequest model representing an external vendor task
 */
class VendorRequest {
	public int $id;
	public int $task_id;
	public int $transaction_id;
	public ?int $party_id = null;
	public string $vendor_type; // fire_dept, septic_inspector, hoa_manager, title_company, appraiser, inspector, other
	public ?string $vendor_name = null;
	public ?string $vendor_company = null;
	public ?float $average_rating = null;
	public string $vendor_email;
	public ?string $vendor_phone = null;
	public string $token;
	public string $token_expires_at;
	public string $status = 'sent'; // sent, opened, scheduled, completed, expired, cancelled
	public ?string $scheduled_date = null;
	public ?string $scheduled_time = null;
	public ?string $completion_date = null;
	public ?string $completion_notes = null;
	public ?string $document_url = null;
	public ?string $last_opened_at = null;
	public ?string $confirmation_sent_at = null;
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
