<?php
/**
 * TaskDefinition Model
 *
 * @package MADealRoom\Models
 * @since 2.0.0
 */

namespace MADealRoom\Models;

/**
 * TaskDefinition model representing an individual reusable task definition
 */
class TaskDefinition {
	public int $id;
	public string $task_key;
	public string $category;
	public string $title;
	public ?string $description = null;
	public string $owner_role = 'agent';
	public string $priority = 'normal';
	public ?int $estimated_duration = null;
	public ?string $due_calculation = null;
	public ?string $applies_if = null;
	public ?string $transaction_type_filter = null; // Comma-separated: buy_side,sell_side,etc.
	public ?string $property_attribute_filter = null; // JSON array: ["has_septic", "has_well"]
	public array $depends_on = [];
	public array $metadata = [];
	public bool $is_system = false;
	public bool $is_milestone = false;
	public bool $is_required = false;
	public bool $is_legal_requirement = false;
	public ?string $legal_citation = null;
	public ?string $legal_deadline = null;
	public ?int $account_id = null;
	public ?int $created_by_user_id = null;
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
				if ($key === 'depends_on') {
					if (is_string($value)) {
						$this->$key = json_decode($value, true) ?: [];
					} elseif ($value === null) {
						$this->$key = [];
					} else {
						$this->$key = $value;
					}
				} elseif ($key === 'metadata') {
					if (is_string($value)) {
						$this->$key = json_decode($value, true) ?: [];
					} elseif ($value === null) {
						$this->$key = [];
					} else {
						$this->$key = $value;
					}
				} elseif ($key === 'property_attribute_filter' && is_string($value)) {
					// Keep as string for now (will be decoded when needed)
					$this->$key = $value;
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
		$array = get_object_vars($this);

		// Ensure JSON fields are arrays, not strings
		if (is_string($array['depends_on'])) {
			$array['depends_on'] = json_decode($array['depends_on'], true) ?: [];
		}
		if (is_string($array['metadata'])) {
			$array['metadata'] = json_decode($array['metadata'], true) ?: [];
		}

		// Add task_definition_id as alias for id (for React compatibility)
		$array['task_definition_id'] = $this->id;

		return $array;
	}

	/**
	 * Get task category information
	 *
	 * @return array|null Category data or null if not found
	 */
	public function getCategoryInfo(): ?array {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$category = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$prefix}ma_deal_task_categories WHERE category_key = %s",
			$this->category
		), ARRAY_A);

		return $category ?: null;
	}

	/**
	 * Get templates using this task definition
	 *
	 * @return array Array of template IDs
	 */
	public function getTemplates(): array {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$template_ids = $wpdb->get_col($wpdb->prepare(
			"SELECT template_id FROM {$prefix}ma_deal_template_tasks WHERE task_definition_id = %d",
			$this->id
		));

		return $template_ids;
	}

	/**
	 * Check if this task applies to given context
	 *
	 * @param array $context Context data (property info, etc.)
	 * @return bool Whether task applies
	 */
	public function appliesTo(array $context): bool {
		if (empty($this->applies_if)) {
			return true;
		}

		// Use TemplateEngine's evaluateCondition method
		$container = \MADealRoom\Core\Plugin::instance()->container();
		$template_engine = $container->get('template_engine');

		return $template_engine->evaluateCondition($this->applies_if, $context);
	}

	/**
	 * Check if this task applies to a specific Transaction
	 *
	 * @param Transaction $transaction Transaction to check against
	 * @param array|null $property_attributes Optional property attributes (from PropertyAttributes model)
	 * @return bool Whether task applies to this transaction
	 */
	public function appliesToTransaction(Transaction $transaction, ?array $property_attributes = null): bool {
		// Check transaction_type_filter
		if (!empty($this->transaction_type_filter)) {
			$allowed_types = array_map('trim', explode(',', $this->transaction_type_filter));
			if (!in_array($transaction->transaction_type, $allowed_types)) {
				return false; // Task doesn't apply to this transaction type
			}
		}

		// Check property_attribute_filter
		if (!empty($this->property_attribute_filter)) {
			// Decode JSON if it's a string
			$required_attributes = is_string($this->property_attribute_filter)
				? json_decode($this->property_attribute_filter, true)
				: $this->property_attribute_filter;

			if (!is_array($required_attributes) || empty($required_attributes)) {
				return true; // Invalid filter, allow task
			}

			// If no property attributes provided, can't validate
			if ($property_attributes === null) {
				return false; // Can't determine if task applies without property data
			}

			// Check each required attribute
			foreach ($required_attributes as $attr) {
				// Handle both object and array access
				$value = is_object($property_attributes)
					? ($property_attributes->$attr ?? false)
					: ($property_attributes[$attr] ?? false);

				if (!$value) {
					return false; // Required attribute not present or false
				}
			}
		}

		// Check applies_if expression (existing logic)
		if (!empty($this->applies_if)) {
			$context = [
				'transaction' => $transaction,
				'property' => $property_attributes ?? [],
			];

			return $this->appliesTo($context);
		}

		return true; // No filters, task applies
	}

	/**
	 * Calculate due date for this task
	 *
	 * @param array $key_dates Key dates for calculation (closing_date, ps_date, listing_date)
	 * @return string|null Calculated due date
	 */
	public function calculateDueDate(array $key_dates): ?string {
		if (empty($this->due_calculation)) {
			return null;
		}

		// Use TemplateEngine's calculateDueDate method
		$container = \MADealRoom\Core\Plugin::instance()->container();
		$template_engine = $container->get('template_engine');

		return $template_engine->calculateDueDate($this->due_calculation, $key_dates);
	}

	/**
	 * Check if task is custom (user-created)
	 *
	 * @return bool
	 */
	public function isCustom(): bool {
		return !$this->is_system && $this->account_id !== null;
	}

	/**
	 * Get validation errors
	 *
	 * @return array Array of error messages
	 */
	public function validate(): array {
		$errors = [];

		if (empty($this->task_key)) {
			$errors[] = 'Task key is required';
		}

		if (empty($this->title)) {
			$errors[] = 'Title is required';
		}

		if (empty($this->category)) {
			$errors[] = 'Category is required';
		}

		if (empty($this->owner_role)) {
			$errors[] = 'Owner role is required';
		}

		// Validate owner_role is valid
		$valid_roles = ['agent', 'buyer', 'seller', 'buyer_attorney', 'seller_attorney', 'vendor', 'lender', 'title_company'];
		if (!in_array($this->owner_role, $valid_roles)) {
			$errors[] = 'Invalid owner role: ' . $this->owner_role;
		}

		// Validate priority
		$valid_priorities = ['low', 'normal', 'high', 'critical'];
		if (!in_array($this->priority, $valid_priorities)) {
			$errors[] = 'Invalid priority: ' . $this->priority;
		}

		return $errors;
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
