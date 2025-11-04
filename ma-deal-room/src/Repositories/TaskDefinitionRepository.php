<?php
/**
 * TaskDefinition Repository
 *
 * @package MADealRoom\Repositories
 * @since 2.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\TaskDefinition;

class TaskDefinitionRepository extends BaseRepository {
	protected $table = 'ma_deal_task_definitions';
	protected $model_class = TaskDefinition::class;

	protected $allowed_columns = [
		'id',
		'task_key',
		'category',
		'title',
		'description',
		'owner_role',
		'priority',
		'estimated_duration',
		'due_calculation',
		'applies_if',
		'depends_on',
		'metadata',
		'is_system',
		'is_milestone',
		'is_required',
		'account_id',
		'created_by_user_id',
		'created_at',
		'updated_at',
	];

	/**
	 * Find all system task definitions
	 *
	 * @return array Array of TaskDefinition models
	 */
	public function findSystem(): array {
		return $this->query(['is_system' => 1], ['order_by' => 'category']);
	}

	/**
	 * Find task definitions by category
	 *
	 * @param string $category Category key
	 * @param bool $system_only Only return system tasks
	 * @return array Array of TaskDefinition models
	 */
	public function findByCategory(string $category, bool $system_only = false): array {
		$conditions = ['category' => $category];

		if ($system_only) {
			$conditions['is_system'] = 1;
		}

		return $this->query($conditions, ['order_by' => 'title', 'order' => 'ASC']);
	}

	/**
	 * Find task definitions by account (custom tasks)
	 *
	 * @param int $account_id Account ID
	 * @return array Array of TaskDefinition models
	 */
	public function findByAccount(int $account_id): array {
		return $this->query(['account_id' => $account_id], ['order_by' => 'created_at', 'order' => 'DESC']);
	}

	/**
	 * Find task definition by task_key
	 *
	 * @param string $task_key Task key
	 * @param int|null $account_id Account ID (null for system tasks)
	 * @return TaskDefinition|null
	 */
	public function findByTaskKey(string $task_key, ?int $account_id = null): ?TaskDefinition {
		$conditions = ['task_key' => $task_key];

		if ($account_id === null) {
			// System task - account_id IS NULL
			$table = $this->get_table_name();
			$result = $this->wpdb->get_row(
				$this->wpdb->prepare(
					"SELECT * FROM {$table} WHERE task_key = %s AND account_id IS NULL",
					$task_key
				),
				ARRAY_A
			);

			if (!$result) {
				return null;
			}

			return TaskDefinition::fromArray($result);
		} else {
			$conditions['account_id'] = $account_id;
			$results = $this->query($conditions, ['limit' => 1]);
			return !empty($results) ? $results[0] : null;
		}
	}

	/**
	 * Find by ID (alias for find() for consistency)
	 *
	 * @param int $id Record ID
	 * @return TaskDefinition|null
	 */
	public function findById(int $id): ?TaskDefinition {
		return $this->find($id);
	}

	/**
	 * Search task definitions by keyword
	 *
	 * @param string $keyword Search keyword
	 * @param array $options Additional filters (category, is_system, account_id)
	 * @return array Array of TaskDefinition models
	 */
	public function search(string $keyword, array $options = []): array {
		$table = $this->get_table_name();

		$where_parts = [];
		$params = [];

		// Search in title and description
		$where_parts[] = "(title LIKE %s OR description LIKE %s)";
		$search_term = '%' . $this->wpdb->esc_like($keyword) . '%';
		$params[] = $search_term;
		$params[] = $search_term;

		// Additional filters
		if (isset($options['category'])) {
			$where_parts[] = "category = %s";
			$params[] = $options['category'];
		}

		if (isset($options['is_system'])) {
			$where_parts[] = "is_system = %d";
			$params[] = $options['is_system'];
		}

		if (isset($options['account_id'])) {
			if ($options['account_id'] === null) {
				$where_parts[] = "account_id IS NULL";
			} else {
				$where_parts[] = "account_id = %d";
				$params[] = $options['account_id'];
			}
		}

		$where_clause = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

		$sql = "SELECT * FROM {$table} {$where_clause} ORDER BY title ASC";

		$prepared_sql = !empty($params) ? $this->wpdb->prepare($sql, ...$params) : $sql;

		$results = $this->wpdb->get_results($prepared_sql, ARRAY_A);

		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Get task definitions for a specific template
	 *
	 * @param int $template_id Template ID
	 * @return array Array of TaskDefinition models with template_task relationship data
	 */
	public function findByTemplate(int $template_id): array {
		$table = $this->get_table_name();
		$prefix = $this->wpdb->prefix;

		$sql = $this->wpdb->prepare(
			"SELECT td.*, tt.id as template_task_id, tt.sort_order, tt.override_due_calculation,
			        tt.override_owner_role, tt.override_applies_if, tt.is_optional
			 FROM {$table} td
			 INNER JOIN {$prefix}ma_deal_template_tasks tt ON td.id = tt.task_definition_id
			 WHERE tt.template_id = %d
			 ORDER BY tt.sort_order ASC",
			$template_id
		);

		$results = $this->wpdb->get_results($sql, ARRAY_A);

		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Get count of templates using this task definition
	 *
	 * @param int $task_definition_id Task definition ID
	 * @return int Template count
	 */
	public function getTemplateCount(int $task_definition_id): int {
		$prefix = $this->wpdb->prefix;

		return (int) $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT COUNT(*) FROM {$prefix}ma_deal_template_tasks WHERE task_definition_id = %d",
			$task_definition_id
		));
	}

	/**
	 * Get task definitions grouped by category
	 *
	 * @param bool $system_only Only return system tasks
	 * @return array Associative array with category_key => tasks
	 */
	public function findAllGroupedByCategory(bool $system_only = false): array {
		$prefix = $this->wpdb->prefix;
		$table = $this->get_table_name();

		$where_clause = $system_only ? 'WHERE td.is_system = 1' : '';

		$sql = "
			SELECT
				c.category_key,
				c.name as category_name,
				c.sort_order as category_sort_order,
				td.*
			FROM {$prefix}ma_deal_task_categories c
			LEFT JOIN {$table} td ON c.category_key = td.category {$where_clause}
			ORDER BY c.sort_order ASC, td.title ASC
		";

		$results = $this->wpdb->get_results($sql, ARRAY_A);

		$grouped = [];
		foreach ($results as $row) {
			$category_key = $row['category_key'];

			if (!isset($grouped[$category_key])) {
				$grouped[$category_key] = [
					'category_key' => $category_key,
					'category_name' => $row['category_name'],
					'category_sort_order' => $row['category_sort_order'],
					'tasks' => [],
				];
			}

			// Only add task if it exists (LEFT JOIN may return null tasks)
			if ($row['id']) {
				// Remove category metadata from task data
				$task_data = $row;
				unset($task_data['category_key']);
				unset($task_data['category_name']);
				unset($task_data['category_sort_order']);

				$grouped[$category_key]['tasks'][] = TaskDefinition::fromArray($task_data);
			}
		}

		return $grouped;
	}

	/**
	 * Create task definition with validation
	 *
	 * @param array $data Task definition data
	 * @return int|false Task definition ID or false on failure
	 */
	public function createValidated(array $data) {
		// Create temp model for validation
		$temp_task = new TaskDefinition($data);
		$errors = $temp_task->validate();

		if (!empty($errors)) {
			// Store errors for retrieval
			$this->last_validation_errors = $errors;
			return false;
		}

		// Ensure JSON fields are encoded
		if (isset($data['depends_on']) && is_array($data['depends_on'])) {
			$data['depends_on'] = json_encode($data['depends_on']);
		}
		if (isset($data['metadata']) && is_array($data['metadata'])) {
			$data['metadata'] = json_encode($data['metadata']);
		}

		// Set timestamps
		$data['created_at'] = $data['created_at'] ?? current_time('mysql');
		$data['updated_at'] = $data['updated_at'] ?? current_time('mysql');

		return $this->create($data);
	}

	/**
	 * Get last validation errors
	 *
	 * @var array
	 */
	private $last_validation_errors = [];

	/**
	 * Get last validation errors
	 *
	 * @return array
	 */
	public function getLastValidationErrors(): array {
		return $this->last_validation_errors;
	}
}
