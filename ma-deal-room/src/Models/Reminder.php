<?php
/**
 * Reminder Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Reminder model representing a queued reminder
 */
class Reminder {
	public int $id;
	public int $task_id;
	public int $transaction_id;
	public string $recipient_type; // agent, seller, buyer, attorney, party
	public ?string $recipient_email = null;
	public ?string $recipient_phone = null;
	public string $channel = 'email'; // email, sms, both
	public string $scheduled_at;
	public ?string $sent_at = null;
	public string $status = 'pending'; // pending, sent, failed, cancelled
	public ?string $failure_reason = null;
	public int $retry_count = 0;
	public int $max_retries = 3;
	public ?string $message_template = null;
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
