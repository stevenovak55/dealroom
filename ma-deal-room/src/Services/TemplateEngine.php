<?php
/**
 * Template Engine Service
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\TemplateRepository;
use Symfony\Component\Yaml\Yaml;

/**
 * Template engine for parsing YAML templates and instantiating tasks
 */
class TemplateEngine {
	private $template_repository;
	private $task_definition_repository;
	private $template_task_repository;
	private $cache_service;

	public function __construct(
		TemplateRepository $template_repository,
		$task_definition_repository = null,
		$template_task_repository = null,
		$cache_service = null
	) {
		$this->template_repository = $template_repository;
		$this->task_definition_repository = $task_definition_repository;
		$this->template_task_repository = $template_task_repository;
		$this->cache_service = $cache_service;
	}

	/**
	 * Parse YAML template content
	 *
	 * @param string $template_content YAML content
	 * @param int|null $template_id Optional template ID for better cache key
	 * @return array Parsed template data
	 */
	public function parseYaml(string $template_content, ?int $template_id = null): array {
		// Generate cache key
		if ($template_id) {
			$cache_key = "template_{$template_id}_yaml";
		} else {
			// Fallback to content hash if no template ID
			$cache_key = 'yaml_' . md5($template_content);
		}

		// Try to get from cache if cache service available
		if ($this->cache_service) {
			$cached = $this->cache_service->get($cache_key);
			if ($cached !== null) {
				return $cached;
			}
		}

		// Parse YAML
		try {
			$result = Yaml::parse($template_content);
			// Symfony YAML returns null for empty strings, convert to empty array
			$result = $result ?? [];
		} catch (\Exception $e) {
			$result = [
				'error' => 'YAML parse error: ' . $e->getMessage(),
			];
		}

		// Cache the result if cache service available (24 hour TTL as per requirements)
		if ($this->cache_service && !isset($result['error'])) {
			$this->cache_service->set($cache_key, $result, 86400); // 24 hours
			// Tag for easy invalidation
			if ($template_id) {
				$this->cache_service->tag($cache_key, "template_{$template_id}");
			}
		}

		return $result;
	}

	/**
	 * Extract task definitions from parsed YAML
	 * Handles both flat tasks array and nested workflows structure
	 *
	 * @param array $parsed Parsed YAML data
	 * @return array Array of task definitions
	 */
	private function extractTasksFromParsed(array $parsed): array {
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
					// Add condition to each task
					foreach ($conditional_group['tasks'] as $task) {
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
	 * Instantiate tasks from template for a transaction
	 * Uses modular task system if repositories are available, otherwise falls back to YAML
	 *
	 * @param object $template Template model
	 * @param object $transaction Transaction model
	 * @param array $property_data Property metadata
	 * @return array Array of task data ready for creation
	 */
	public function instantiateTasks($template, $transaction, array $property_data = []): array {
		// Start performance monitoring
		$span = \MADealRoom\Services\MonitoringService::start_transaction(
			'template.instantiate_tasks',
			'template.apply'
		);

		// Use modular task system if repositories are available AND template has linked tasks
		if ($this->task_definition_repository && $this->template_task_repository) {
			// Check if template has any linked tasks in modular system
			$template_tasks = $this->template_task_repository->query([
				'template_id' => $template->id,
			]);

			// If template has linked tasks (more than just a few), use modular system
			// Otherwise fall back to YAML parsing
			if (count($template_tasks) > 10) {
				$result = $this->instantiateTasksFromModular($template, $transaction, $property_data);
				if ($span) {
					$span->finish();
				}
				return $result;
			}
		}

		// Fallback to YAML parsing
		$result = $this->instantiateTasksFromYaml($template, $transaction, $property_data);
		if ($span) {
			$span->finish();
		}
		return $result;
	}

	/**
	 * Instantiate tasks from modular task system (database)
	 *
	 * @param object $template Template model
	 * @param object $transaction Transaction model
	 * @param array $property_data Property metadata
	 * @return array Array of task data ready for creation
	 */
	public function instantiateTasksFromModular($template, $transaction, array $property_data = []): array {
		// Get template tasks with their definitions
		$template_tasks = $this->template_task_repository->findByTemplate($template->id, true);

		$tasks = [];
		$task_id_map = [];

		// Key dates for due date calculation
		$key_dates = [
			'listing_date' => $transaction->listing_date,
			'offer_accepted_date' => $transaction->offer_accepted_date,
			'ps_date' => $transaction->ps_agreement_date,
			'loan_commitment_date' => $transaction->loan_commitment_date,
			'closing_date' => $transaction->closing_date,
		];

		// Get property attributes for this transaction (if available)
		$property_attributes = null;
		if (!empty($property_data)) {
			$property_attributes = $property_data;
		} else {
			// Try to load from database
			global $wpdb;
			$prefix = $wpdb->prefix;
			$prop_data = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM {$prefix}ma_deal_property_attributes WHERE transaction_id = %d",
				$transaction->id
			), ARRAY_A);

			if ($prop_data) {
				$property_attributes = $prop_data;
			}
		}

		foreach ($template_tasks as $template_task) {
			$task_def = $template_task->task_definition;

			if (!$task_def) {
				continue; // Skip if task definition not found
			}

			// NEW: Check transaction_type_filter and property_attribute_filter
			// Use TaskDefinition::appliesToTransaction() method
			if (!$task_def->appliesToTransaction($transaction, $property_attributes)) {
				continue; // Skip tasks that don't apply to this transaction type or property
			}

			// Check legacy applies_if condition (for backward compatibility)
			$applies_if = $template_task->getEffectiveAppliesIf();
			if ($applies_if && !$this->evaluateCondition($applies_if, $property_data ?: $property_attributes ?: [])) {
				continue; // Skip tasks that don't apply
			}

			// Calculate due date using effective due calculation
			$due_at = null;
			$due_calculation = $template_task->getEffectiveDueCalculation();
			if ($due_calculation) {
				$due_at = $this->calculateDueDate($due_calculation, $key_dates);
			}

			// Get effective owner role
			$owner_role = $template_task->getEffectiveOwnerRole();

			// Build task data
			$task_key = $task_def->task_key;
			$task_id_map[$task_key] = count($tasks);

			$tasks[] = [
				'transaction_id' => $transaction->id,
				'template_id' => $template->id,
				'task_definition_id' => $task_def->id, // Link to task definition
				'task_key' => $task_key,
				'title' => $task_def->title,
				'description' => $task_def->description,
				'owner_role' => $owner_role,
				'due_at' => $due_at,
				'status' => 'pending',
				'depends_on_task_ids' => json_encode($task_def->depends_on),
				'metadata' => json_encode(array_merge(
					$task_def->metadata,
					[
						'is_milestone' => $task_def->is_milestone,
						'is_required' => $task_def->is_required,
						'is_optional' => $template_task->is_optional,
						'priority' => $task_def->priority,
						'estimated_duration' => $task_def->estimated_duration,
					]
				)),
			];
		}

		// Resolve dependencies using topological sort
		$tasks = $this->resolveDependencies($tasks, $task_id_map);

		return $tasks;
	}

	/**
	 * Instantiate tasks from YAML template (legacy method)
	 *
	 * @param object $template Template model
	 * @param object $transaction Transaction model
	 * @param array $property_data Property metadata
	 * @return array Array of task data ready for creation
	 */
	public function instantiateTasksFromYaml($template, $transaction, array $property_data = []): array {
		$parsed = $this->parseYaml($template->template_yaml, $template->id ?? null);

		if (isset($parsed['error'])) {
			return [];
		}

		// Collect all task definitions from various sources
		$all_task_defs = [];

		// Handle extends directive - load base template
		if (isset($parsed['extends'])) {
			$base_template_id = trim($parsed['extends'], '"\'');

			// Try to find template by template_id first (from YAML), then by name
			// Parse the template's YAML to get its template_id
			$all_templates = $this->template_repository->findSystem();
			$base_template = null;

			foreach ($all_templates as $tmpl) {
				$tmpl_parsed = $this->parseYaml($tmpl->template_yaml, $tmpl->id ?? null);
				if (isset($tmpl_parsed['template_id']) && $tmpl_parsed['template_id'] === $base_template_id) {
					$base_template = $tmpl;
					break;
				}
			}

			// Fallback: try matching by name
			if (!$base_template) {
				$base_template = $this->template_repository->query([
					'name' => $base_template_id,
					'is_system' => 1,
					'is_active' => 1
				]);
				$base_template = !empty($base_template) ? $base_template[0] : null;
			}

			if ($base_template) {
				$base_parsed = $this->parseYaml($base_template->template_yaml, $base_template->id ?? null);
				$base_tasks = $this->extractTasksFromParsed($base_parsed);
				$all_task_defs = array_merge($all_task_defs, $base_tasks);
			}
		}

		// Extract tasks from current template
		$template_tasks = $this->extractTasksFromParsed($parsed);
		$all_task_defs = array_merge($all_task_defs, $template_tasks);

		$tasks = [];
		$task_id_map = []; // Maps template task IDs to array indices

		// First pass: Create tasks that apply
		foreach ($all_task_defs as $index => $task_def) {
			// Check if task applies
			if (isset($task_def['applies_if'])) {
				$applies = $this->evaluateCondition($task_def['applies_if'], $property_data);
				if (!$applies) {
					continue;
				}
			}

			// Calculate due date from anchor + offset
			$due_at = null;
			if (isset($task_def['due'])) {
				$anchor = $task_def['due'];
				$offset = $task_def['due_offset'] ?? '0d';
				$due_at = $this->calculateDueDate($anchor . $offset, [
					'listing_date' => $transaction->listing_date,
					'offer_accepted_date' => $transaction->offer_accepted_date,
					'ps_date' => $transaction->ps_agreement_date,
					'loan_commitment_date' => $transaction->loan_commitment_date,
					'closing_date' => $transaction->closing_date,
				]);
			} elseif (isset($task_def['due_days'])) {
				// Handle due_days format (days from transaction start)
				$days = (int)$task_def['due_days'];
				$base_date = $transaction->listing_date ?? $transaction->created_at ?? date('Y-m-d');
				$timestamp = strtotime($base_date) + ($days * 86400);
				$due_at = date('Y-m-d H:i:s', $timestamp);
			}

			$task_id = $task_def['id'] ?? 'task_' . $index;
			$task_id_map[$task_id] = count($tasks);

			// Determine owner_role from various possible field names
			$owner_role = $task_def['owner_role']
				?? $task_def['assignee_role']
				?? $task_def['owner']
				?? 'agent';

			// Map role names to standard format
			$role_mapping = [
				'ListingAgent' => 'agent',
				'BuyerAgent' => 'agent',
				'Seller' => 'seller',
				'Buyer' => 'buyer',
				'SellerAttorney' => 'seller_attorney',
				'BuyerAttorney' => 'buyer_attorney',
				'Inspector' => 'vendor',
				'HOA' => 'vendor',
			];
			$owner_role = $role_mapping[$owner_role] ?? strtolower($owner_role);

			$tasks[] = [
				'transaction_id' => $transaction->id,
				'template_id' => $template->id,
				'task_key' => $task_id,
				'title' => $task_def['title'] ?? 'Untitled Task',
				'description' => $task_def['description'] ?? null,
				'owner_role' => $owner_role,
				'due_at' => $due_at,
				'status' => 'pending',
				'depends_on_task_ids' => json_encode($task_def['depends_on'] ?? []),
				'metadata' => json_encode([
					'citations' => $task_def['citations'] ?? [],
					'notes' => $task_def['notes'] ?? '',
					'mandatory' => $task_def['mandatory'] ?? false,
					'reminders' => $task_def['reminders'] ?? [],
				]),
			];
		}

		// Second pass: Resolve dependencies using topological sort
		$tasks = $this->resolveDependencies($tasks, $task_id_map);

		return $tasks;
	}

	/**
	 * Evaluate conditional expression
	 *
	 * @param string $condition Condition expression
	 * @param array $context Context data
	 * @return bool Whether condition is met
	 */
	public function evaluateCondition(string $condition, array $context): bool {
		// Handle transaction.field == value (treat same as property for now)
		if (preg_match('/transaction\.(\w+)\s*==\s*["\']?(\w+)["\']?/', $condition, $matches)) {
			$field = $matches[1];
			$expected = $matches[2];

			if ($expected === 'true') {
				return !empty($context[$field]) && $context[$field] !== false;
			}
			if ($expected === 'false') {
				return empty($context[$field]) || $context[$field] === false;
			}

			return isset($context[$field]) && $context[$field] == $expected;
		}

		// Handle property.field == value
		if (preg_match('/property\.(\w+)\s*==\s*["\']?(\w+)["\']?/', $condition, $matches)) {
			$field = $matches[1];
			$expected = $matches[2];

			if ($expected === 'true') {
				return !empty($context[$field]) && $context[$field] !== false;
			}
			if ($expected === 'false') {
				return empty($context[$field]) || $context[$field] === false;
			}

			return isset($context[$field]) && $context[$field] == $expected;
		}

		// Handle property.field != value
		if (preg_match('/property\.(\w+)\s*!=\s*["\']?(\w+)["\']?/', $condition, $matches)) {
			$field = $matches[1];
			$expected = $matches[2];

			return !isset($context[$field]) || $context[$field] != $expected;
		}

		// Handle property.field < number
		if (preg_match('/property\.(\w+)\s*<\s*(\d+)/', $condition, $matches)) {
			$field = $matches[1];
			$threshold = (int)$matches[2];

			return isset($context[$field]) && (int)$context[$field] < $threshold;
		}

		// Handle property.field > number
		if (preg_match('/property\.(\w+)\s*>\s*(\d+)/', $condition, $matches)) {
			$field = $matches[1];
			$threshold = (int)$matches[2];

			return isset($context[$field]) && (int)$context[$field] > $threshold;
		}

		// Handle property.field <= number
		if (preg_match('/property\.(\w+)\s*<=\s*(\d+)/', $condition, $matches)) {
			$field = $matches[1];
			$threshold = (int)$matches[2];

			return isset($context[$field]) && (int)$context[$field] <= $threshold;
		}

		// Handle property.field >= number
		if (preg_match('/property\.(\w+)\s*>=\s*(\d+)/', $condition, $matches)) {
			$field = $matches[1];
			$threshold = (int)$matches[2];

			return isset($context[$field]) && (int)$context[$field] >= $threshold;
		}

		// Handle AND conditions: property.type == 'SFH' && property.has_septic == true
		if (strpos($condition, '&&') !== false) {
			$parts = explode('&&', $condition);
			foreach ($parts as $part) {
				if (!$this->evaluateCondition(trim($part), $context)) {
					return false;
				}
			}
			return true;
		}

		// Handle OR conditions: property.type == 'SFH' || property.type == 'Condo'
		if (strpos($condition, '||') !== false) {
			$parts = explode('||', $condition);
			foreach ($parts as $part) {
				if ($this->evaluateCondition(trim($part), $context)) {
					return true;
				}
			}
			return false;
		}

		// Default: assume true if can't parse (fail open for forward compatibility)
		return true;
	}

	/**
	 * Calculate due date from relative offset
	 *
	 * @param string $relative_date Relative date string (e.g., "Closing-21d")
	 * @param array $key_dates Key dates for calculation
	 * @return string|null Calculated due date in MySQL format
	 */
	public function calculateDueDate(string $relative_date, array $key_dates): ?string {
		// Handle special case: exact date anchor with no offset
		if (preg_match('/^(Closing|PS|Listing|Offer|LoanCommitment|FirstMeeting)$/', $relative_date, $matches)) {
			$anchor = $matches[1];
			$anchor_date = $this->getAnchorDate($anchor, $key_dates);

			if (!$anchor_date) {
				return null;
			}

			return date('Y-m-d H:i:s', strtotime($anchor_date));
		}

		// Parse format: "Closing-21d" or "PS+7d" or "Listing+0d" or "Offer+14d" or "LoanCommitment-7d"
		if (preg_match('/^(Closing|PS|Listing|Offer|LoanCommitment|FirstMeeting)([\+\-])(\d+)d$/', $relative_date, $matches)) {
			$anchor = $matches[1];
			$operator = $matches[2];
			$days = (int)$matches[3];

			$anchor_date = $this->getAnchorDate($anchor, $key_dates);

			if (!$anchor_date) {
				return null;
			}

			$timestamp = strtotime($anchor_date);
			if ($operator === '+') {
				$timestamp += $days * 86400;
			} else {
				$timestamp -= $days * 86400;
			}

			return date('Y-m-d H:i:s', $timestamp);
		}

		return null;
	}

	/**
	 * Get anchor date from key dates array
	 *
	 * @param string $anchor Anchor name
	 * @param array $key_dates Key dates array
	 * @return string|null Anchor date
	 */
	private function getAnchorDate(string $anchor, array $key_dates): ?string {
		$mapping = [
			'Closing' => 'closing_date',
			'PS' => 'ps_date',
			'Listing' => 'listing_date',
			'ListingDate' => 'listing_date',
			'Offer' => 'offer_accepted_date',
			'LoanCommitment' => 'loan_commitment_date',
			'FirstMeeting' => 'listing_date', // Use listing as proxy for first meeting
		];

		$key = $mapping[$anchor] ?? null;

		return $key && isset($key_dates[$key]) ? $key_dates[$key] : null;
	}

	/**
	 * Resolve task dependencies using topological sort
	 *
	 * @param array $tasks Array of task data
	 * @param array $task_id_map Mapping of task IDs to array indices
	 * @return array Sorted tasks with resolved dependencies
	 */
	private function resolveDependencies(array $tasks, array $task_id_map): array {
		// Build dependency graph
		$graph = [];
		$in_degree = [];

		foreach ($tasks as $index => $task) {
			$graph[$index] = [];
			$in_degree[$index] = 0;
		}

		// Build edges: dependency -> dependent
		foreach ($tasks as $index => $task) {
			$depends_on = json_decode($task['depends_on_task_ids'], true);
			if (!empty($depends_on)) {
				foreach ($depends_on as $dep_id) {
					if (isset($task_id_map[$dep_id])) {
						$dep_index = $task_id_map[$dep_id];
						$graph[$dep_index][] = $index;
						$in_degree[$index]++;
					}
				}
			}
		}

		// Topological sort using Kahn's algorithm
		$queue = [];
		$sorted_indices = [];

		// Find all tasks with no dependencies
		foreach ($in_degree as $index => $degree) {
			if ($degree === 0) {
				$queue[] = $index;
			}
		}

		while (!empty($queue)) {
			$current = array_shift($queue);
			$sorted_indices[] = $current;

			// Reduce in-degree for dependent tasks
			foreach ($graph[$current] as $dependent) {
				$in_degree[$dependent]--;
				if ($in_degree[$dependent] === 0) {
					$queue[] = $dependent;
				}
			}
		}

		// If cycle detected, return original order
		if (count($sorted_indices) !== count($tasks)) {
			return $tasks;
		}

		// Rebuild tasks array in topological order
		$sorted_tasks = [];
		foreach ($sorted_indices as $index) {
			$sorted_tasks[] = $tasks[$index];
		}

		return $sorted_tasks;
	}

	/**
	 * Invalidate cached data for a template
	 * Call this when a template is updated or deleted
	 *
	 * @param int $template_id Template ID
	 * @return bool True if cache service available and invalidation successful
	 */
	public function invalidateTemplateCache(int $template_id): bool {
		if (!$this->cache_service) {
			return false;
		}

		// Invalidate YAML cache
		$yaml_cache_key = "template_{$template_id}_yaml";
		$this->cache_service->delete($yaml_cache_key);

		// Invalidate using tag (will clear all related caches)
		$this->cache_service->flushTag("template_{$template_id}");

		return true;
	}

	/**
	 * Warm up cache for a template
	 * Pre-parse and cache YAML for faster subsequent access
	 *
	 * @param object $template Template model with id and template_yaml properties
	 * @return bool True if successful
	 */
	public function warmTemplateCache($template): bool {
		if (!$this->cache_service || !isset($template->id, $template->template_yaml)) {
			return false;
		}

		// Parse and cache YAML (will automatically cache)
		$this->parseYaml($template->template_yaml, $template->id);

		return true;
	}
}
