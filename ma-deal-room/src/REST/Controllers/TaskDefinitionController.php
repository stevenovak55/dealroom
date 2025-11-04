<?php
/**
 * Task Definition REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 2.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use MADealRoom\Repositories\TaskDefinitionRepository;
use MADealRoom\Repositories\TemplateTaskRepository;
use MADealRoom\Repositories\EventRepository;

/**
 * REST controller for task definitions
 */
class TaskDefinitionController extends BaseController {
	protected $rest_base = 'task-definitions';

	private $task_definition_repository;
	private $template_task_repository;

	/**
	 * Constructor
	 *
	 * @param TaskDefinitionRepository $task_definition_repository Task definition repository
	 * @param TemplateTaskRepository $template_task_repository Template task repository
	 * @param EventRepository|null $event_repository Event repository for audit logging
	 */
	public function __construct(
		TaskDefinitionRepository $task_definition_repository,
		TemplateTaskRepository $template_task_repository,
		?EventRepository $event_repository = null
	) {
		parent::__construct($event_repository);
		$this->task_definition_repository = $task_definition_repository;
		$this->template_task_repository = $template_task_repository;
	}

	/**
	 * Register routes
	 */
	public function register_routes(): void {
		// GET /task-definitions - Browse task library
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_items'],
				'permission_callback' => [$this, 'permission_callback'],
				'args' => [
					'category' => [
						'description' => 'Filter by category',
						'type' => 'string',
					],
					'search' => [
						'description' => 'Search keyword in title and description',
						'type' => 'string',
					],
					'is_system' => [
						'description' => 'Filter system vs custom tasks',
						'type' => 'boolean',
					],
					'account_id' => [
						'description' => 'Filter by account (custom tasks)',
						'type' => 'integer',
					],
					'grouped' => [
						'description' => 'Return tasks grouped by category',
						'type' => 'boolean',
						'default' => false,
					],
				],
			],
		]);

		// POST /task-definitions - Create custom task
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => 'POST',
				'callback' => [$this, 'create_item'],
				'permission_callback' => [$this, 'permission_callback'],
				'args' => $this->get_item_schema(),
			],
		]);

		// GET /task-definitions/{id} - Get single task
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_item'],
				'permission_callback' => [$this, 'permission_callback'],
				'args' => [
					'id' => [
						'description' => 'Task definition ID',
						'type' => 'integer',
						'required' => true,
					],
				],
			],
		]);

		// PUT /task-definitions/{id} - Update task
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
			[
				'methods' => 'PUT',
				'callback' => [$this, 'update_item'],
				'permission_callback' => [$this, 'permission_callback'],
				'args' => array_merge(
					['id' => ['type' => 'integer', 'required' => true]],
					$this->get_item_schema()
				),
			],
		]);

		// DELETE /task-definitions/{id} - Delete custom task
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
			[
				'methods' => 'DELETE',
				'callback' => [$this, 'delete_item'],
				'permission_callback' => [$this, 'permission_callback'],
				'args' => [
					'id' => [
						'description' => 'Task definition ID',
						'type' => 'integer',
						'required' => true,
					],
				],
			],
		]);

		// GET /task-definitions/{id}/templates - Get templates using this task
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/templates', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_task_templates'],
				'permission_callback' => [$this, 'permission_callback'],
				'args' => [
					'id' => [
						'description' => 'Task definition ID',
						'type' => 'integer',
						'required' => true,
					],
				],
			],
		]);

		// GET /task-categories - Get all categories
		register_rest_route($this->namespace, '/task-categories', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_categories'],
				'permission_callback' => [$this, 'permission_callback'],
			],
		]);
	}

	/**
	 * Get task definitions (browse task library)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function get_items(WP_REST_Request $request) {
		$category = $request->get_param('category');
		$search = $request->get_param('search');
		$is_system = $request->get_param('is_system');
		$account_id = $request->get_param('account_id');
		$grouped = $request->get_param('grouped');

		try {
			// Return grouped by category
			if ($grouped) {
				// Support search even when grouped
				if ($search) {
					$options = [];
					if ($is_system !== null) {
						$options['is_system'] = $is_system ? 1 : 0;
					}
					$tasks_array = $this->task_definition_repository->search($search, $options);

					// Group the search results by category
					$grouped_tasks = [];
					foreach ($tasks_array as $task) {
						$category = $task->category;
						if (!isset($grouped_tasks[$category])) {
							$grouped_tasks[$category] = [
								'category_key' => $category,
								'category_name' => ucwords(str_replace('_', ' ', $category)),
								'tasks' => []
							];
						}
						$grouped_tasks[$category]['tasks'][] = $task->toArray();
					}

					return new WP_REST_Response([
						'success' => true,
						'data' => $grouped_tasks,
					], 200);
				}

				$tasks = $this->task_definition_repository->findAllGroupedByCategory($is_system ?? true);

				return new WP_REST_Response([
					'success' => true,
					'data' => $tasks,
				], 200);
			}

			// Search with keyword
			if ($search) {
				$options = [];
				if ($category) {
					$options['category'] = $category;
				}
				if ($is_system !== null) {
					$options['is_system'] = $is_system ? 1 : 0;
				}
				if ($account_id !== null) {
					// SECURITY: Verify account access when filtering by account
					if (!$this->verify_account_access($account_id)) {
						return new WP_Error(
							'rest_forbidden',
							__('You do not have permission to access this account', 'ma-deal-room'),
							['status' => 403]
						);
					}
					$options['account_id'] = $account_id;
				}

				$tasks = $this->task_definition_repository->search($search, $options);
			}
			// Filter by category
			elseif ($category) {
				$tasks = $this->task_definition_repository->findByCategory($category, $is_system ?? false);
			}
			// Filter by account (custom tasks)
			elseif ($account_id) {
				// SECURITY: Verify account access when filtering by account
				if (!$this->verify_account_access($account_id)) {
					return new WP_Error(
						'rest_forbidden',
						__('You do not have permission to access this account', 'ma-deal-room'),
						['status' => 403]
					);
				}
				$tasks = $this->task_definition_repository->findByAccount($account_id);
			}
			// Get all system tasks
			else {
				$tasks = $this->task_definition_repository->findSystem();
			}

			return new WP_REST_Response([
				'success' => true,
				'data' => array_map(function($task) {
					return $task->toArray();
				}, $tasks),
				'total' => count($tasks),
			], 200);

		} catch (\Exception $e) {
			// SECURITY FIX: Don't expose exception details to client
			// Log full exception server-side
			error_log(sprintf(
				'[TaskDefinition] Exception: %s | File: %s:%d | User: %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				get_current_user_id()
			));

			return new WP_Error(
				'task_definition_error',
				__('An error occurred processing your request. Please try again or contact support.', 'ma-deal-room'),
				['status' => 500]
			);
		}
	}

	/**
	 * Get single task definition
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function get_item(WP_REST_Request $request) {
		$id = $request->get_param('id');

		try {
			$task = $this->task_definition_repository->findById($id);

			if (!$task) {
				return new WP_Error(
					'task_not_found',
					__('Task definition not found.', 'ma-deal-room'),
					['status' => 404]
				);
			}

			// SECURITY: Verify account access for custom (non-system) tasks
			if (!$task->is_system && $task->account_id) {
				if (!$this->verify_account_access($task->account_id)) {
					return new WP_Error(
						'rest_forbidden',
						__('You do not have permission to access this task definition', 'ma-deal-room'),
						['status' => 403]
					);
				}
			}

			return new WP_REST_Response([
				'success' => true,
				'data' => $task->toArray(),
			], 200);

		} catch (\Exception $e) {
			// SECURITY FIX: Don't expose exception details to client
			// Log full exception server-side
			error_log(sprintf(
				'[TaskDefinition] Exception: %s | File: %s:%d | User: %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				get_current_user_id()
			));

			return new WP_Error(
				'task_definition_error',
				__('An error occurred processing your request. Please try again or contact support.', 'ma-deal-room'),
				['status' => 500]
			);
		}
	}

	/**
	 * Create custom task definition
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function create_item(WP_REST_Request $request) {
		// Only allow custom tasks (not system tasks)
		$user = wp_get_current_user();

		// SECURITY: Determine account_id and verify access
		$account_id = $request->get_param('account_id');
		if (!$account_id) {
			// Get user's account if not specified
			$account_id = $this->get_user_account_id();
			if (!$account_id) {
				return new WP_Error(
					'no_account',
					__('You must have an account to create task definitions', 'ma-deal-room'),
					['status' => 403]
				);
			}
		} else {
			// SECURITY: Verify account access when account_id is specified
			if (!$this->verify_account_access($account_id)) {
				return new WP_Error(
					'rest_forbidden',
					__('You do not have permission to create task definitions for this account', 'ma-deal-room'),
					['status' => 403]
				);
			}
		}

		$data = [
			'task_key' => $request->get_param('task_key') ?? sanitize_title($request->get_param('title')),
			'category' => $request->get_param('category'),
			'title' => $request->get_param('title'),
			'description' => $request->get_param('description'),
			'owner_role' => $request->get_param('owner_role') ?? 'agent',
			'priority' => $request->get_param('priority') ?? 'normal',
			'estimated_duration' => $request->get_param('estimated_duration'),
			'due_calculation' => $request->get_param('due_calculation'),
			'applies_if' => $request->get_param('applies_if'),
			'depends_on' => $request->get_param('depends_on') ?? [],
			'metadata' => $request->get_param('metadata') ?? [],
			'is_system' => 0, // Custom tasks are never system tasks
			'is_milestone' => $request->get_param('is_milestone') ?? 0,
			'is_required' => $request->get_param('is_required') ?? 0,
			'account_id' => $account_id,
			'created_by_user_id' => $user->ID,
		];

		try {
			$id = $this->task_definition_repository->createValidated($data);

			if (!$id) {
				$errors = $this->task_definition_repository->getLastValidationErrors();
				return new WP_Error(
					'validation_error',
					__('Validation failed.', 'ma-deal-room'),
					['status' => 400, 'errors' => $errors]
				);
			}

			$task = $this->task_definition_repository->findById($id);

			// Log event
			if ($this->event_repository && $task) {
				$this->event_repository->create([
					'account_id' => $data['account_id'],
					'user_id' => $user->ID,
					'event_type' => 'task_definition.created',
					'entity_type' => 'task_definition',
					'entity_id' => $id,
					'description' => "Created custom task definition: {$task->title}",
					'created_at' => current_time('mysql'),
				]);
			}

			return new WP_REST_Response([
				'success' => true,
				'data' => $task->toArray(),
				'message' => __('Task definition created successfully.', 'ma-deal-room'),
			], 201);

		} catch (\Exception $e) {
			// SECURITY FIX: Don't expose exception details to client
			// Log full exception server-side
			error_log(sprintf(
				'[TaskDefinition] Exception: %s | File: %s:%d | User: %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				get_current_user_id()
			));

			return new WP_Error(
				'task_definition_error',
				__('An error occurred processing your request. Please try again or contact support.', 'ma-deal-room'),
				['status' => 500]
			);
		}
	}

	/**
	 * Update task definition
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function update_item(WP_REST_Request $request) {
		$id = $request->get_param('id');

		try {
			$task = $this->task_definition_repository->findById($id);

			if (!$task) {
				return new WP_Error(
					'task_not_found',
					__('Task definition not found.', 'ma-deal-room'),
					['status' => 404]
				);
			}

			// Only allow updating custom tasks (not system tasks)
			if ($task->is_system) {
				return new WP_Error(
					'cannot_edit_system_task',
					__('System tasks cannot be edited.', 'ma-deal-room'),
					['status' => 403]
				);
			}

			// SECURITY: Verify account ownership for custom tasks
			if ($task->account_id && !$this->verify_account_access($task->account_id)) {
				return new WP_Error(
					'rest_forbidden',
					__('You do not have permission to modify this task definition', 'ma-deal-room'),
					['status' => 403]
				);
			}

			$data = [];
			$fields = ['title', 'description', 'category', 'owner_role', 'priority',
			           'estimated_duration', 'due_calculation', 'applies_if', 'depends_on',
			           'metadata', 'is_milestone', 'is_required'];

			foreach ($fields as $field) {
				if ($request->has_param($field)) {
					$data[$field] = $request->get_param($field);
				}
			}

			if (!empty($data)) {
				$data['updated_at'] = current_time('mysql');

				// Encode JSON fields
				if (isset($data['depends_on']) && is_array($data['depends_on'])) {
					$data['depends_on'] = json_encode($data['depends_on']);
				}
				if (isset($data['metadata']) && is_array($data['metadata'])) {
					$data['metadata'] = json_encode($data['metadata']);
				}

				$result = $this->task_definition_repository->update($id, $data);

				if (!$result) {
					return new WP_Error(
						'update_failed',
						__('Failed to update task definition.', 'ma-deal-room'),
						['status' => 500]
					);
				}

				// Log event
				if ($this->event_repository) {
					$user = wp_get_current_user();
					$this->event_repository->create([
						'account_id' => $task->account_id,
						'user_id' => $user->ID,
						'event_type' => 'task_definition.updated',
						'entity_type' => 'task_definition',
						'entity_id' => $id,
						'description' => "Updated task definition: {$task->title}",
						'created_at' => current_time('mysql'),
					]);
				}
			}

			$updated_task = $this->task_definition_repository->findById($id);

			return new WP_REST_Response([
				'success' => true,
				'data' => $updated_task->toArray(),
				'message' => __('Task definition updated successfully.', 'ma-deal-room'),
			], 200);

		} catch (\Exception $e) {
			// SECURITY FIX: Don't expose exception details to client
			// Log full exception server-side
			error_log(sprintf(
				'[TaskDefinition] Exception: %s | File: %s:%d | User: %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				get_current_user_id()
			));

			return new WP_Error(
				'task_definition_error',
				__('An error occurred processing your request. Please try again or contact support.', 'ma-deal-room'),
				['status' => 500]
			);
		}
	}

	/**
	 * Delete custom task definition
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function delete_item(WP_REST_Request $request) {
		$id = $request->get_param('id');

		try {
			$task = $this->task_definition_repository->findById($id);

			if (!$task) {
				return new WP_Error(
					'task_not_found',
					__('Task definition not found.', 'ma-deal-room'),
					['status' => 404]
				);
			}

			// Only allow deleting custom tasks (not system tasks)
			if ($task->is_system) {
				return new WP_Error(
					'cannot_delete_system_task',
					__('System tasks cannot be deleted.', 'ma-deal-room'),
					['status' => 403]
				);
			}

			// SECURITY: Verify account ownership for custom tasks
			if ($task->account_id && !$this->verify_account_access($task->account_id)) {
				return new WP_Error(
					'rest_forbidden',
					__('You do not have permission to delete this task definition', 'ma-deal-room'),
					['status' => 403]
				);
			}

			// Check if task is used in any templates
			$template_count = $this->task_definition_repository->getTemplateCount($id);
			if ($template_count > 0) {
				return new WP_Error(
					'task_in_use',
					sprintf(__('This task is used in %d template(s) and cannot be deleted.', 'ma-deal-room'), $template_count),
					['status' => 409]
				);
			}

			$result = $this->task_definition_repository->delete($id);

			if (!$result) {
				return new WP_Error(
					'delete_failed',
					__('Failed to delete task definition.', 'ma-deal-room'),
					['status' => 500]
				);
			}

			// Log event
			if ($this->event_repository) {
				$user = wp_get_current_user();
				$this->event_repository->create([
					'account_id' => $task->account_id,
					'user_id' => $user->ID,
					'event_type' => 'task_definition.deleted',
					'entity_type' => 'task_definition',
					'entity_id' => $id,
					'description' => "Deleted task definition: {$task->title}",
					'created_at' => current_time('mysql'),
				]);
			}

			return new WP_REST_Response([
				'success' => true,
				'message' => __('Task definition deleted successfully.', 'ma-deal-room'),
			], 200);

		} catch (\Exception $e) {
			// SECURITY FIX: Don't expose exception details to client
			// Log full exception server-side
			error_log(sprintf(
				'[TaskDefinition] Exception: %s | File: %s:%d | User: %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				get_current_user_id()
			));

			return new WP_Error(
				'task_definition_error',
				__('An error occurred processing your request. Please try again or contact support.', 'ma-deal-room'),
				['status' => 500]
			);
		}
	}

	/**
	 * Get templates using this task
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function get_task_templates(WP_REST_Request $request) {
		$id = $request->get_param('id');

		try {
			$task = $this->task_definition_repository->findById($id);

			if (!$task) {
				return new WP_Error(
					'task_not_found',
					__('Task definition not found.', 'ma-deal-room'),
					['status' => 404]
				);
			}

			// SECURITY: Verify account access for custom (non-system) tasks
			if (!$task->is_system && $task->account_id) {
				if (!$this->verify_account_access($task->account_id)) {
					return new WP_Error(
						'rest_forbidden',
						__('You do not have permission to access this task definition', 'ma-deal-room'),
						['status' => 403]
					);
				}
			}

			$templates = $this->template_task_repository->getTemplatesUsingTask($id);

			return new WP_REST_Response([
				'success' => true,
				'data' => $templates,
				'total' => count($templates),
			], 200);

		} catch (\Exception $e) {
			// SECURITY FIX: Don't expose exception details to client
			// Log full exception server-side
			error_log(sprintf(
				'[TaskDefinition] Exception: %s | File: %s:%d | User: %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				get_current_user_id()
			));

			return new WP_Error(
				'task_definition_error',
				__('An error occurred processing your request. Please try again or contact support.', 'ma-deal-room'),
				['status' => 500]
			);
		}
	}

	/**
	 * Get all task categories
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error Response object
	 */
	public function get_categories(WP_REST_Request $request) {
		global $wpdb;
		$prefix = $wpdb->prefix;

		try {
			$categories = $wpdb->get_results(
				"SELECT * FROM {$prefix}ma_deal_task_categories ORDER BY sort_order ASC",
				ARRAY_A
			);

			return new WP_REST_Response([
				'success' => true,
				'data' => $categories,
			], 200);

		} catch (\Exception $e) {
			// SECURITY FIX: Don't expose exception details to client
			// Log full exception server-side
			error_log(sprintf(
				'[TaskDefinition] Categories exception: %s | File: %s:%d | User: %d',
				$e->getMessage(),
				$e->getFile(),
				$e->getLine(),
				get_current_user_id()
			));

			return new WP_Error(
				'categories_error',
				__('Unable to retrieve task categories. Please try again or contact support.', 'ma-deal-room'),
				['status' => 500]
			);
		}
	}

	/**
	 * Get item schema for validation
	 *
	 * @return array Schema definition
	 */
	protected function get_item_schema(): array {
		return [
			'title' => [
				'description' => 'Task title',
				'type' => 'string',
				'required' => true,
			],
			'category' => [
				'description' => 'Task category',
				'type' => 'string',
				'required' => true,
			],
			'description' => [
				'description' => 'Task description',
				'type' => 'string',
			],
			'owner_role' => [
				'description' => 'Default owner role',
				'type' => 'string',
				'enum' => ['agent', 'buyer', 'seller', 'buyer_attorney', 'seller_attorney', 'vendor', 'lender', 'title_company'],
			],
			'priority' => [
				'description' => 'Task priority',
				'type' => 'string',
				'enum' => ['low', 'normal', 'high', 'critical'],
			],
			'estimated_duration' => [
				'description' => 'Estimated duration in minutes',
				'type' => 'integer',
			],
			'due_calculation' => [
				'description' => 'Due date calculation formula',
				'type' => 'string',
			],
			'applies_if' => [
				'description' => 'Condition for task applicability',
				'type' => 'string',
			],
			'depends_on' => [
				'description' => 'Array of task keys this depends on',
				'type' => 'array',
			],
			'metadata' => [
				'description' => 'Additional metadata (JSON object)',
				'type' => 'object',
			],
			'is_milestone' => [
				'description' => 'Is this a milestone task',
				'type' => 'boolean',
			],
			'is_required' => [
				'description' => 'Is this task required',
				'type' => 'boolean',
			],
		];
	}
}
