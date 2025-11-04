<?php
/**
 * Event Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Event model representing an audit log entry
 */
class Event {
	public int $id;
	public int $account_id;
	public ?int $transaction_id = null;
	public string $entity_type; // transaction, task, party, template, reminder, vendor_request
	public int $entity_id;
	public string $event_type; // created, updated, deleted, status_changed, completed, reminded, escalated, assigned
	public ?int $user_id = null;
	public ?array $old_data = null;
	public ?array $new_data = null;
	public ?string $ip_address = null;
	public ?string $user_agent = null;
	public string $created_at;

	/**
	 * Constructor
	 *
	 * @param array $data Initial data
	 */
	public function __construct(array $data = []) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				// Handle JSON fields
				if (in_array($key, ['old_data', 'new_data']) && is_string($value)) {
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

		// Convert JSON fields to strings if needed
		foreach (['old_data', 'new_data'] as $field) {
			if (isset($data[$field]) && is_array($data[$field])) {
				$data[$field] = json_encode($data[$field]);
			}
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
