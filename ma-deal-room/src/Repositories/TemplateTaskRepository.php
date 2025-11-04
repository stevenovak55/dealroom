<?php
/**
 * TemplateTask Repository
 *
 * @package MADealRoom\Repositories
 * @since 2.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\TemplateTask;

class TemplateTaskRepository extends BaseRepository {
	protected $table = 'ma_deal_template_tasks';
	protected $model_class = TemplateTask::class;

	/**
	 * Allowed columns for queries (SQL injection prevention)
	 *
	 * @var array
	 */
	protected $allowed_columns = [
		'id',
		'template_id',
		'task_definition_id',
		'sort_order',
		'override_due_calculation',
		'override_owner_role',
		'override_applies_if',
		'is_optional',
		'created_at'
	];

	/**
	 * Find all template tasks for a template
	 *
	 * @param int $template_id Template ID
	 * @param bool $load_definitions Whether to eager-load task definitions
	 * @return array Array of TemplateTask models
	 */
	public function findByTemplate(int $template_id, bool $load_definitions = false): array {
		$results = $this->query(
			['template_id' => $template_id],
			['order_by' => 'sort_order', 'order' => 'ASC']
		);

		if ($load_definitions) {
			foreach ($results as $template_task) {
				$template_task->loadTaskDefinition();
			}
		}

		return $results;
	}

	/**
	 * Find all templates using a task definition
	 *
	 * @param int $task_definition_id Task definition ID
	 * @return array Array of TemplateTask models
	 */
	public function findByTaskDefinition(int $task_definition_id): array {
		return $this->query(['task_definition_id' => $task_definition_id]);
	}

	/**
	 * Find by ID (alias for find() for consistency)
	 *
	 * @param int $id Record ID
	 * @return TemplateTask|null
	 */
	public function findById(int $id): ?TemplateTask {
		return $this->find($id);
	}

	/**
	 * Add task to template
	 *
	 * @param int $template_id Template ID
	 * @param int $task_definition_id Task definition ID
	 * @param array $options Optional overrides and settings
	 * @return int|false TemplateTask ID or false on failure
	 */
	public function addTaskToTemplate(int $template_id, int $task_definition_id, array $options = []) {
		// Check if link already exists
		$existing = $this->query([
			'template_id' => $template_id,
			'task_definition_id' => $task_definition_id,
		]);

		if (!empty($existing)) {
			return false; // Already linked
		}

		// Get next sort_order
		$max_order = $this->getMaxSortOrder($template_id);

		$data = [
			'template_id' => $template_id,
			'task_definition_id' => $task_definition_id,
			'sort_order' => $options['sort_order'] ?? ($max_order + 1),
			'override_due_calculation' => $options['override_due_calculation'] ?? null,
			'override_owner_role' => $options['override_owner_role'] ?? null,
			'override_applies_if' => $options['override_applies_if'] ?? null,
			'is_optional' => $options['is_optional'] ?? 0,
			'created_at' => current_time('mysql'),
		];

		return $this->create($data);
	}

	/**
	 * Remove task from template
	 *
	 * @param int $template_id Template ID
	 * @param int $task_definition_id Task definition ID
	 * @return bool Success
	 */
	public function removeTaskFromTemplate(int $template_id, int $task_definition_id): bool {
		$table = $this->get_table_name();

		$result = $this->wpdb->delete(
			$table,
			[
				'template_id' => $template_id,
				'task_definition_id' => $task_definition_id,
			],
			['%d', '%d']
		);

		// Re-order remaining tasks
		if ($result !== false) {
			$this->reorderTasks($template_id);
		}

		return $result !== false;
	}

	/**
	 * Update task override settings
	 *
	 * @param int $template_task_id TemplateTask ID
	 * @param array $overrides Override settings
	 * @return bool Success
	 */
	public function updateOverrides(int $template_task_id, array $overrides): bool {
		$allowed_fields = [
			'override_due_calculation',
			'override_owner_role',
			'override_applies_if',
			'is_optional',
		];

		$data = array_intersect_key($overrides, array_flip($allowed_fields));

		if (empty($data)) {
			return false;
		}

		return $this->update($template_task_id, $data);
	}

	/**
	 * Reorder tasks for a template
	 *
	 * @param int $template_id Template ID
	 * @param array $task_order Array of task_definition_ids in desired order
	 * @return bool Success
	 */
	public function reorderTasks(int $template_id, array $task_order = []): bool {
		if (empty($task_order)) {
			// Auto-reorder by current sort_order
			$template_tasks = $this->findByTemplate($template_id);

			$sort_order = 0;
			foreach ($template_tasks as $template_task) {
				$this->update($template_task->id, ['sort_order' => $sort_order++]);
			}

			return true;
		}

		// Reorder according to provided order
		$sort_order = 0;
		foreach ($task_order as $task_definition_id) {
			$template_tasks = $this->query([
				'template_id' => $template_id,
				'task_definition_id' => $task_definition_id,
			]);

			if (!empty($template_tasks)) {
				$this->update($template_tasks[0]->id, ['sort_order' => $sort_order++]);
			}
		}

		return true;
	}

	/**
	 * Get maximum sort_order for a template
	 *
	 * @param int $template_id Template ID
	 * @return int Maximum sort_order
	 */
	public function getMaxSortOrder(int $template_id): int {
		$table = $this->get_table_name();

		$max = $this->wpdb->get_var($this->wpdb->prepare(
			"SELECT MAX(sort_order) FROM {$table} WHERE template_id = %d",
			$template_id
		));

		return (int) ($max ?? 0);
	}

	/**
	 * Bulk add tasks to template
	 *
	 * @param int $template_id Template ID
	 * @param array $task_definition_ids Array of task definition IDs
	 * @param array $default_options Default options for all tasks
	 * @return array Array of created IDs
	 */
	public function bulkAddTasksToTemplate(int $template_id, array $task_definition_ids, array $default_options = []): array {
		$created_ids = [];
		$sort_order = $this->getMaxSortOrder($template_id) + 1;

		foreach ($task_definition_ids as $task_definition_id) {
			$options = array_merge($default_options, ['sort_order' => $sort_order++]);
			$id = $this->addTaskToTemplate($template_id, $task_definition_id, $options);

			if ($id !== false) {
				$created_ids[] = $id;
			}
		}

		return $created_ids;
	}

	/**
	 * Clone template tasks to another template
	 *
	 * @param int $source_template_id Source template ID
	 * @param int $target_template_id Target template ID
	 * @param bool $preserve_overrides Whether to preserve override settings
	 * @return int Number of tasks cloned
	 */
	public function cloneToTemplate(int $source_template_id, int $target_template_id, bool $preserve_overrides = true): int {
		$source_tasks = $this->findByTemplate($source_template_id);
		$cloned = 0;

		foreach ($source_tasks as $template_task) {
			$options = ['sort_order' => $template_task->sort_order];

			if ($preserve_overrides) {
				$options['override_due_calculation'] = $template_task->override_due_calculation;
				$options['override_owner_role'] = $template_task->override_owner_role;
				$options['override_applies_if'] = $template_task->override_applies_if;
				$options['is_optional'] = $template_task->is_optional;
			}

			$result = $this->addTaskToTemplate($target_template_id, $template_task->task_definition_id, $options);

			if ($result !== false) {
				$cloned++;
			}
		}

		return $cloned;
	}

	/**
	 * Get task count for a template
	 *
	 * @param int $template_id Template ID
	 * @return int Task count
	 */
	public function getTaskCount(int $template_id): int {
		return $this->count(['template_id' => $template_id]);
	}

	/**
	 * Get templates using a task definition with details
	 *
	 * @param int $task_definition_id Task definition ID
	 * @return array Array with template info
	 */
	public function getTemplatesUsingTask(int $task_definition_id): array {
		$table = $this->get_table_name();
		$prefix = $this->wpdb->prefix;

		$sql = $this->wpdb->prepare(
			"SELECT t.id, t.name, t.property_type, tt.sort_order, tt.is_optional
			 FROM {$prefix}ma_deal_templates t
			 INNER JOIN {$table} tt ON t.id = tt.template_id
			 WHERE tt.task_definition_id = %d
			 ORDER BY t.name ASC",
			$task_definition_id
		);

		return $this->wpdb->get_results($sql, ARRAY_A);
	}
}
