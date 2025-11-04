<?php
/**
 * Task Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Task model representing an instantiated task
 */
class Task {
	public int $id;
	public int $transaction_id;
	public ?int $template_id = null;
	public string $task_key;
	public string $title;
	public ?string $description = null;
	public string $status = 'pending'; // pending, in_progress, completed, cancelled, blocked, skipped
	public string $owner_role = 'agent'; // agent, seller, buyer, seller_attorney, buyer_attorney, vendor, system
	public ?int $assigned_party_id = null;
	public ?string $due_at = null;
	public ?string $completed_at = null;
	public ?array $depends_on_task_ids = null;
	public ?string $applies_if_condition = null;
	public ?array $metadata = null;
	public int $sort_order = 0;
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
				if (in_array($key, ['depends_on_task_ids', 'metadata']) && is_string($value)) {
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
		foreach (['depends_on_task_ids', 'metadata'] as $field) {
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
