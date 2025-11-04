<?php
/**
 * VendorMessage Model
 *
 * @package MADealRoom\Models
 * @since 2.1.0
 */

namespace MADealRoom\Models;

/**
 * VendorMessage model representing communication between vendors and agents
 */
class VendorMessage {
	public int $id;
	public int $vendor_request_id;
	public int $transaction_id;
	public string $sender_type; // 'vendor' or 'agent'
	public string $sender_name;
	public string $sender_email;
	public string $message;
	public bool $is_read = false;
	public ?string $read_at = null;
	public string $created_at;

	/**
	 * Constructor
	 *
	 * @param array $data Initial data
	 */
	public function __construct(array $data = []) {
		foreach ($data as $key => $value) {
			if (property_exists($this, $key)) {
				// Handle boolean conversion
				if ($key === 'is_read' && !is_bool($value)) {
					$this->$key = (bool) $value;
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
		return get_object_vars($this);
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
	 * Check if sender is vendor
	 *
	 * @return bool
	 */
	public function isFromVendor(): bool {
		return $this->sender_type === 'vendor';
	}

	/**
	 * Check if sender is agent
	 *
	 * @return bool
	 */
	public function isFromAgent(): bool {
		return $this->sender_type === 'agent';
	}

	/**
	 * Mark message as read
	 *
	 * @return void
	 */
	public function markAsRead(): void {
		$this->is_read = true;
		$this->read_at = current_time('mysql');
	}
}
