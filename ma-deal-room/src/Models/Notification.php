<?php
/**
 * Notification Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Notification model representing an in-app notification
 */
class Notification {
	public int $id;
	public int $user_id;
	public string $user_type = 'wordpress'; // 'wordpress' or 'custom'
	public string $type;
	public string $title;
	public string $message;
	public ?string $link = null;
	public ?string $entity_type = null;
	public ?int $entity_id = null;
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
				$this->$key = $value;
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
}
