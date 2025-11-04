<?php
/**
 * Task Definitions WP-CLI Command
 *
 * @package MADealRoom\CLI
 * @since 1.0.0
 */

namespace MADealRoom\CLI;

use MADealRoom\Core\Plugin;
use WP_CLI;
use Symfony\Component\Yaml\Yaml;

/**
 * Manage task definitions
 */
class TaskDefinitionsCommand {
	/**
	 * Extract and sync task definitions from YAML templates
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal task-definitions:sync
	 *
	 * @when after_wp_load
	 */
	public function sync($args, $assoc_args) {
		WP_CLI::log('Extracting task definitions from YAML templates...');

		$templates_dir = MA_DEAL_PATH . 'assets/templates/';

		if (!is_dir($templates_dir)) {
			WP_CLI::error("Templates directory not found: {$templates_dir}");
			return;
		}

		$template_files = glob($templates_dir . '*.yaml');

		if (empty($template_files)) {
			WP_CLI::warning('No template files found.');
			return;
		}

		WP_CLI::success(sprintf('Found %d template files.', count($template_files)));

		$plugin = Plugin::instance();
		$task_def_repo = $plugin->container()->get('task_definition_repository');

		$created = 0;
		$updated = 0;
		$skipped = 0;
		$errors = 0;

		// Track all task keys we've seen
		$seen_task_keys = [];

		foreach ($template_files as $file) {
			$filename = basename($file);
			WP_CLI::log("Processing: {$filename}");

			try {
				// Read and parse YAML
				$yaml_content = file_get_contents($file);
				if ($yaml_content === false) {
					throw new \Exception("Failed to read file");
				}

				$parsed = Yaml::parse($yaml_content);

				// Extract tasks from the YAML structure
				$tasks = $this->extractTasks($parsed);

				if (empty($tasks)) {
					WP_CLI::warning("  No tasks found in {$filename}");
					continue;
				}

				WP_CLI::log(sprintf("  Found %d tasks", count($tasks)));

				foreach ($tasks as $task) {
					try {
						$task_key = $task['id'] ?? null;

						if (!$task_key) {
							WP_CLI::warning("  ✗ Skipping task with no ID");
							$skipped++;
							continue;
						}

						// Skip if we've already processed this task key in this run
						if (isset($seen_task_keys[$task_key])) {
							$skipped++;
							continue;
						}

						$seen_task_keys[$task_key] = true;

						// Prepare task definition data
						$task_data = $this->prepareTaskDefinition($task);

						// Check if task definition already exists
						$existing = $task_def_repo->findByTaskKey($task_key, null);

						if ($existing) {
							// Update existing
							$task_def_repo->update($existing->id, $task_data);
							$updated++;
						} else {
							// Create new
							$task_data['created_at'] = current_time('mysql');
							$task_data['updated_at'] = current_time('mysql');
							$task_def_repo->create($task_data);
							$created++;
						}

					} catch (\Exception $e) {
						WP_CLI::warning("  ✗ Error processing task {$task_key}: " . $e->getMessage());
						$errors++;
					}
				}

			} catch (\Exception $e) {
				WP_CLI::warning("  ✗ Error processing {$filename}: " . $e->getMessage());
				$errors++;
			}
		}

		WP_CLI::success(sprintf(
			"Task definitions sync completed. Created: %d, Updated: %d, Skipped: %d, Errors: %d",
			$created, $updated, $skipped, $errors
		));
	}

	/**
	 * Extract tasks from parsed YAML
	 *
	 * @param array $parsed Parsed YAML data
	 * @return array Array of tasks
	 */
	private function extractTasks(array $parsed): array {
		$tasks = [];

		// Extract from workflows (nested structure)
		if (isset($parsed['workflows']) && is_array($parsed['workflows'])) {
			foreach ($parsed['workflows'] as $workflow) {
				if (isset($workflow['tasks']) && is_array($workflow['tasks'])) {
					$tasks = array_merge($tasks, $workflow['tasks']);
				}
			}
		}

		// Extract from flat tasks array
		if (isset($parsed['tasks']) && is_array($parsed['tasks'])) {
			$tasks = array_merge($tasks, $parsed['tasks']);
		}

		// Extract from conditional_tasks
		if (isset($parsed['conditional_tasks']) && is_array($parsed['conditional_tasks'])) {
			foreach ($parsed['conditional_tasks'] as $conditional_group) {
				if (isset($conditional_group['tasks']) && is_array($conditional_group['tasks'])) {
					foreach ($conditional_group['tasks'] as $task) {
						// Add condition to task
						if (isset($conditional_group['condition'])) {
							$task['applies_if'] = $conditional_group['condition'];
						}
						$tasks[] = $task;
					}
				}
			}
		}

		return $tasks;
	}

	/**
	 * Prepare task definition data for database
	 *
	 * @param array $task Task data from YAML
	 * @return array Task definition data
	 */
	private function prepareTaskDefinition(array $task): array {
		// Build due calculation from due + due_offset
		$due_calculation = null;
		if (isset($task['due'])) {
			$anchor = $task['due'];
			$offset = $task['due_offset'] ?? '+0d';
			$due_calculation = $anchor . $offset;
		}

		// Handle depends_on
		$depends_on = null;
		if (isset($task['depends_on']) && is_array($task['depends_on']) && !empty($task['depends_on'])) {
			$depends_on = wp_json_encode($task['depends_on']);
		}

		// Build metadata from various fields
		$metadata = [];
		if (isset($task['documents'])) {
			$metadata['documents'] = $task['documents'];
		}
		if (isset($task['reminders'])) {
			$metadata['reminders'] = $task['reminders'];
		}
		if (isset($task['citations'])) {
			$metadata['citations'] = $task['citations'];
		}
		if (isset($task['vendor_type'])) {
			$metadata['vendor_type'] = $task['vendor_type'];
		}
		if (isset($task['notes'])) {
			$metadata['notes'] = $task['notes'];
		}

		return [
			'task_key' => $task['id'],
			'category' => $task['category'] ?? 'other',
			'title' => $task['title'] ?? $task['id'],
			'description' => $task['description'] ?? '',
			'owner_role' => $task['owner_role'] ?? 'agent',
			'priority' => $task['priority'] ?? 'normal',
			'estimated_duration' => isset($task['estimated_duration']) && is_numeric($task['estimated_duration'])
				? (int)$task['estimated_duration']
				: null,
			'due_calculation' => $due_calculation,
			'applies_if' => $task['applies_if'] ?? null,
			'depends_on' => $depends_on,
			'metadata' => !empty($metadata) ? wp_json_encode($metadata) : null,
			'is_system' => 1,
			'is_milestone' => isset($task['milestone']) && $task['milestone'] ? 1 : 0,
			'is_required' => isset($task['mandatory']) && $task['mandatory'] ? 1 : 0,
			'account_id' => null,
			'created_by_user_id' => null,
			'updated_at' => current_time('mysql'),
		];
	}

	/**
	 * List all task definitions
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal task-definitions:list
	 *
	 * @when after_wp_load
	 */
	public function list($args, $assoc_args) {
		$plugin = Plugin::instance();
		$task_def_repo = $plugin->container()->get('task_definition_repository');

		$task_defs = $task_def_repo->findSystem();

		if (empty($task_defs)) {
			WP_CLI::log('No task definitions found.');
			return;
		}

		$items = [];
		foreach ($task_defs as $task_def) {
			$items[] = [
				'ID' => $task_def->id,
				'Key' => $task_def->task_key,
				'Title' => mb_substr($task_def->title, 0, 40),
				'Category' => $task_def->category,
				'Owner' => $task_def->owner_role,
				'System' => $task_def->is_system ? 'Yes' : 'No',
			];
		}

		WP_CLI\Utils\format_items('table', $items, ['ID', 'Key', 'Title', 'Category', 'Owner', 'System']);
		WP_CLI::success(sprintf('Total: %d task definitions', count($items)));
	}
}
