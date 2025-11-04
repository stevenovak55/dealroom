<?php
/**
 * Template Model
 *
 * @package MADealRoom\Models
 * @since 1.0.0
 */

namespace MADealRoom\Models;

/**
 * Template model representing a task template
 */
class Template {
	public int $id;
	public ?int $account_id = null;
	public string $name;
	public ?string $description = null;
	public string $property_type = 'Any'; // SFH, Condo, Multifamily, Land, Commercial, Any
	public string $transaction_side = 'both'; // listing, buyer, both
	public string $template_yaml;
	public bool $is_system = false;
	public bool $is_active = true;
	public int $version = 1;
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

		// Add computed task_count by parsing YAML
		$array['task_count'] = $this->getTaskCount();

		// Add template_id as alias for id (for React compatibility)
		$array['template_id'] = $this->id;

		return $array;
	}

	/**
	 * Get count of tasks in template
	 *
	 * @return int
	 */
	private function getTaskCount(): int {
		try {
			// Try to parse YAML properly
			if (class_exists('\Symfony\Component\Yaml\Yaml')) {
				$parsed = \Symfony\Component\Yaml\Yaml::parse($this->template_yaml);
				if (isset($parsed['tasks']) && is_array($parsed['tasks'])) {
					return count($parsed['tasks']);
				}
			}

			// Fallback: count lines with "- key:" or "- id:" at task level
			// This matches both "  - key:" and "  - id:" patterns
			preg_match_all('/^\s+- (?:key|id):/m', $this->template_yaml, $matches);
			return count($matches[0]);
		} catch (\Exception $e) {
			return 0;
		}
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
