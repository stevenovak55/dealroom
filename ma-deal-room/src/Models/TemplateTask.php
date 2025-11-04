<?php
/**
 * TemplateTask Model
 *
 * @package MADealRoom\Models
 * @since 2.0.0
 */

namespace MADealRoom\Models;

/**
 * TemplateTask model representing the relationship between a template and a task definition
 */
class TemplateTask {
	public int $id;
	public int $template_id;
	public int $task_definition_id;
	public int $sort_order = 0;
	public ?string $override_due_calculation = null;
	public ?string $override_owner_role = null;
	public ?string $override_applies_if = null;
	public bool $is_optional = false;
	public string $created_at;

	// Related objects (not stored in DB, loaded on demand)
	public ?Template $template = null;
	public ?TaskDefinition $task_definition = null;

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
		$array = get_object_vars($this);

		// Remove related objects if they're loaded
		unset($array['template']);
		unset($array['task_definition']);

		// Add template_task_id as alias for id (for React compatibility)
		$array['template_task_id'] = $this->id;

		// Include task_definition data if loaded
		if ($this->task_definition) {
			$array['task_definition'] = $this->task_definition->toArray();
		}

		return $array;
	}

	/**
	 * Load the related task definition
	 *
	 * @return TaskDefinition|null
	 */
	public function loadTaskDefinition(): ?TaskDefinition {
		if ($this->task_definition) {
			return $this->task_definition;
		}

		$container = \MADealRoom\Core\Plugin::instance()->container();
		$task_def_repo = $container->get('task_definition_repository');

		$this->task_definition = $task_def_repo->findById($this->task_definition_id);

		return $this->task_definition;
	}

	/**
	 * Load the related template
	 *
	 * @return Template|null
	 */
	public function loadTemplate(): ?Template {
		if ($this->template) {
			return $this->template;
		}

		$container = \MADealRoom\Core\Plugin::instance()->container();
		$template_repo = $container->get('template_repository');

		$this->template = $template_repo->findById($this->template_id);

		return $this->template;
	}

	/**
	 * Get the effective due calculation (override or default)
	 *
	 * @return string|null
	 */
	public function getEffectiveDueCalculation(): ?string {
		if ($this->override_due_calculation) {
			return $this->override_due_calculation;
		}

		if (!$this->task_definition) {
			$this->loadTaskDefinition();
		}

		return $this->task_definition ? $this->task_definition->due_calculation : null;
	}

	/**
	 * Get the effective owner role (override or default)
	 *
	 * @return string
	 */
	public function getEffectiveOwnerRole(): string {
		if ($this->override_owner_role) {
			return $this->override_owner_role;
		}

		if (!$this->task_definition) {
			$this->loadTaskDefinition();
		}

		return $this->task_definition ? $this->task_definition->owner_role : 'agent';
	}

	/**
	 * Get the effective applies_if condition (override or default)
	 *
	 * @return string|null
	 */
	public function getEffectiveAppliesIf(): ?string {
		if ($this->override_applies_if) {
			return $this->override_applies_if;
		}

		if (!$this->task_definition) {
			$this->loadTaskDefinition();
		}

		return $this->task_definition ? $this->task_definition->applies_if : null;
	}

	/**
	 * Check if this task applies to given context
	 *
	 * @param array $context Context data (property info, etc.)
	 * @return bool Whether task applies
	 */
	public function appliesTo(array $context): bool {
		$applies_if = $this->getEffectiveAppliesIf();

		if (empty($applies_if)) {
			return true;
		}

		// Use TemplateEngine's evaluateCondition method
		$container = \MADealRoom\Core\Plugin::instance()->container();
		$template_engine = $container->get('template_engine');

		return $template_engine->evaluateCondition($applies_if, $context);
	}

	/**
	 * Calculate due date for this task
	 *
	 * @param array $key_dates Key dates for calculation (closing_date, ps_date, listing_date)
	 * @return string|null Calculated due date
	 */
	public function calculateDueDate(array $key_dates): ?string {
		$due_calculation = $this->getEffectiveDueCalculation();

		if (empty($due_calculation)) {
			return null;
		}

		// Use TemplateEngine's calculateDueDate method
		$container = \MADealRoom\Core\Plugin::instance()->container();
		$template_engine = $container->get('template_engine');

		return $template_engine->calculateDueDate($due_calculation, $key_dates);
	}

	/**
	 * Get validation errors
	 *
	 * @return array Array of error messages
	 */
	public function validate(): array {
		$errors = [];

		if (empty($this->template_id)) {
			$errors[] = 'Template ID is required';
		}

		if (empty($this->task_definition_id)) {
			$errors[] = 'Task definition ID is required';
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
