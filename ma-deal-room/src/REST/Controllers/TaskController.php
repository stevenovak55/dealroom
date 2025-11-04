<?php
/**
 * Task REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Repositories\TaskRepository;
use MADealRoom\Repositories\TransactionRepository;
use MADealRoom\Repositories\PartyRepository;
use MADealRoom\Repositories\EventRepository;
use MADealRoom\Services\EmailService;
use WP_REST_Request;

class TaskController extends BaseController {
	protected $rest_base = 'tasks';
	private $repository;
	private $transaction_repository;
	private $party_repository;
	private $email_service;

	public function __construct(
		TaskRepository $repository,
		TransactionRepository $transaction_repository,
		PartyRepository $party_repository,
		EventRepository $event_repository,
		EmailService $email_service
	) {
		parent::__construct($event_repository);
		$this->repository = $repository;
		$this->transaction_repository = $transaction_repository;
		$this->party_repository = $party_repository;
		$this->email_service = $email_service;
	}

	public function register_routes(): void {
		// GET & POST /tasks - List and create tasks
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_items'],
				'permission_callback' => [$this, 'permission_callback']
			],
			[
				'methods' => 'POST',
				'callback' => [$this, 'create_task'],
				'permission_callback' => [$this, 'permission_callback'],
				'nonce_callback' => [$this, 'verify_nonce']
			],
		]);

		// GET, PUT, DELETE /tasks/{id} - Single task operations
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_item'],
				'permission_callback' => [$this, 'permission_callback']
			],
			[
				'methods' => 'PUT',
				'callback' => [$this, 'update_task'],
				'permission_callback' => [$this, 'permission_callback'],
				'nonce_callback' => [$this, 'verify_nonce']
			],
			[
				'methods' => 'DELETE',
				'callback' => [$this, 'delete_task'],
				'permission_callback' => [$this, 'permission_callback'],
				'nonce_callback' => [$this, 'verify_nonce']
			],
		]);

		// POST /tasks/{id}/complete - Mark complete
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/complete', [
			['methods' => 'POST', 'callback' => [$this, 'complete_task'], 'permission_callback' => [$this, 'permission_callback'], 'nonce_callback' => [$this, 'verify_nonce']],
		]);

		// POST /tasks/{id}/skip - Skip task
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/skip', [
			['methods' => 'POST', 'callback' => [$this, 'skip_task'], 'permission_callback' => [$this, 'permission_callback'], 'nonce_callback' => [$this, 'verify_nonce']],
		]);

		// POST /tasks/{id}/reassign - Reassign task
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/reassign', [
			['methods' => 'POST', 'callback' => [$this, 'reassign_task'], 'permission_callback' => [$this, 'permission_callback'], 'nonce_callback' => [$this, 'verify_nonce']],
		]);
	}

	public function get_items(WP_REST_Request $request) {
		$transaction_id = $request->get_param('transaction_id');
		$status = $request->get_param('status');
		$search = $request->get_param('search');
		$limit = $request->get_param('per_page') ?? 100;

		// SECURITY: When searching, verify account access through transaction_id if provided
		if ($search && !empty(trim($search))) {
			// Use search method
			$transaction_id_int = $transaction_id && is_numeric($transaction_id) ? (int) $transaction_id : null;

			// If transaction_id provided, verify access
			if ($transaction_id_int) {
				$transaction = $this->transaction_repository->find($transaction_id_int);
				if (!$transaction) {
					return $this->error('Transaction not found', 404);
				}
				if (!$this->verify_account_access($transaction->account_id)) {
					return $this->error('You do not have permission to access this transaction', 403);
				}

				// Search within specific transaction
				$tasks = $this->repository->search(
					trim($search),
					$transaction_id_int,
					$status,
					['limit' => $limit]
				);
			} else {
				// Search across all tasks, then filter by account
				$user_account_id = $this->get_user_account_id();
				if (!$user_account_id && !current_user_can('manage_options')) {
					return $this->success(['data' => [], 'pagination' => ['total' => 0, 'page' => 1, 'per_page' => $limit, 'total_pages' => 0]]);
				}

				// Get search results with higher limit to account for filtering
				$all_tasks = $this->repository->search(
					trim($search),
					null,
					$status,
					['limit' => $limit * 10]
				);

				// Filter by account
				$tasks = array_filter($all_tasks, function($task) use ($user_account_id) {
					if (current_user_can('manage_options')) {
						return true; // Admins see all
					}
					$transaction = $this->transaction_repository->find($task->transaction_id);
					return $transaction && $transaction->account_id === $user_account_id;
				});
				$tasks = array_slice($tasks, 0, $limit);
			}
		} elseif ($transaction_id && is_numeric($transaction_id)) {
			// SECURITY: When filtering by transaction, verify account access
			$transaction_id = (int) $transaction_id;

			// Verify transaction exists and user has access
			$transaction = $this->transaction_repository->find($transaction_id);
			if (!$transaction) {
				return $this->error('Transaction not found', 404);
			}
			if (!$this->verify_account_access($transaction->account_id)) {
				return $this->error('You do not have permission to access this transaction', 403);
			}

			$tasks = $this->repository->findByTransaction($transaction_id);
		} elseif ($status) {
			// SECURITY: Filter tasks by current user's account
			$user_account_id = $this->get_user_account_id();
			if (!$user_account_id && !current_user_can('manage_options')) {
				return $this->success(['data' => [], 'pagination' => ['total' => 0, 'page' => 1, 'per_page' => $limit, 'total_pages' => 0]]);
			}

			// Get all tasks and filter by account
			$all_tasks = $this->repository->query(['status' => $status], ['limit' => $limit * 10]); // Get more to filter
			$tasks = array_filter($all_tasks, function($task) use ($user_account_id) {
				if (current_user_can('manage_options')) {
					return true; // Admins see all
				}
				$transaction = $this->transaction_repository->find($task->transaction_id);
				return $transaction && $transaction->account_id === $user_account_id;
			});
			$tasks = array_slice($tasks, 0, $limit);
		} else {
			// SECURITY: Filter all tasks by current user's account
			$user_account_id = $this->get_user_account_id();
			if (!$user_account_id && !current_user_can('manage_options')) {
				return $this->success(['data' => [], 'pagination' => ['total' => 0, 'page' => 1, 'per_page' => $limit, 'total_pages' => 0]]);
			}

			// Get all tasks and filter by account
			$all_tasks = $this->repository->findAll($limit * 10); // Get more to filter
			$tasks = array_filter($all_tasks, function($task) use ($user_account_id) {
				if (current_user_can('manage_options')) {
					return true; // Admins see all
				}
				$transaction = $this->transaction_repository->find($task->transaction_id);
				return $transaction && $transaction->account_id === $user_account_id;
			});
			$tasks = array_slice($tasks, 0, $limit);
		}

		// Convert models to arrays
		$tasks_array = array_map(function($task) {
			$array = $task->toArray();
			// Add task_id alias for React compatibility
			$array['task_id'] = $task->id;
			return $array;
		}, $tasks);

		// Return in paginated format matching React expectations
		return $this->success([
			'data' => $tasks_array,
			'pagination' => [
				'total' => count($tasks_array),
				'page' => 1,
				'per_page' => $limit,
				'total_pages' => 1,
			],
		]);
	}

	public function get_item(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$task = $this->repository->find($id);

		if (!$task) {
			return $this->error('Task not found', 404);
		}

		// SECURITY: Verify user has access to this task's transaction account
		$transaction = $this->transaction_repository->find($task->transaction_id);
		if (!$transaction) {
			return $this->error('Associated transaction not found', 404);
		}
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to access this task', 403);
		}

		return $this->success($task);
	}

	public function complete_task(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$old_task = $this->repository->find($id);

		if (!$old_task) {
			return $this->error('Task not found', 404);
		}

		// SECURITY: Verify user has access to this task's transaction account
		$transaction = $this->transaction_repository->find($old_task->transaction_id);
		if (!$transaction) {
			return $this->error('Associated transaction not found', 404);
		}
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to modify this task', 403);
		}

		$success = $this->repository->markComplete($id);

		if (!$success) {
			return $this->error('Failed to complete task', 500);
		}

		// Return updated task
		$task = $this->repository->find($id);
		$task_array = $task->toArray();
		$task_array['task_id'] = $task->id;

		// Get transaction to fetch account_id
		$transaction = $this->transaction_repository->find($task->transaction_id);
		$account_id = $transaction ? $transaction->account_id : null;

		$this->logEvent(
			'task',
			$task->id,
			'completed',
			$old_task->toArray(),
			$task->toArray(),
			$account_id,
			$task->transaction_id
		);

		return $this->success($task_array, 'Task marked as complete');
	}

	public function skip_task(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$old_task = $this->repository->find($id);

		if (!$old_task) {
			return $this->error('Task not found', 404);
		}

		// SECURITY: Verify user has access to this task's transaction account
		$transaction = $this->transaction_repository->find($old_task->transaction_id);
		if (!$transaction) {
			return $this->error('Associated transaction not found', 404);
		}
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to modify this task', 403);
		}

		$success = $this->repository->update($id, ['status' => 'skipped']);

		if (!$success) {
			return $this->error('Failed to skip task', 500);
		}

		// Return updated task
		$task = $this->repository->find($id);
		$task_array = $task->toArray();
		$task_array['task_id'] = $task->id;

		// Get transaction to fetch account_id
		$transaction = $this->transaction_repository->find($task->transaction_id);
		$account_id = $transaction ? $transaction->account_id : null;

		$this->logEvent(
			'task',
			$task->id,
			'skipped',
			$old_task->toArray(),
			$task->toArray(),
			$account_id,
			$task->transaction_id
		);

		return $this->success($task_array, 'Task skipped');
	}

	public function create_task(WP_REST_Request $request) {
		// Get and validate request data
		$data = $this->sanitize_data($request->get_json_params());

		// Validation rules
		$validation = $this->validate_request($request, [
			'transaction_id' => ['required' => true, 'type' => 'integer'],
			'task_key' => ['required' => true, 'type' => 'string'],
			'title' => ['required' => true, 'type' => 'string', 'min_length' => 3],
			'owner_role' => ['type' => 'string'],
			'status' => ['type' => 'string'],
			'due_at' => ['type' => 'string'],
		]);

		if (is_wp_error($validation)) {
			return $validation;
		}

		// Verify transaction exists
		$transaction = $this->transaction_repository->find($data['transaction_id']);
		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to create tasks in this transaction', 403);
		}

		// Validate status if provided
		$valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'blocked', 'skipped'];
		if (isset($data['status']) && !in_array($data['status'], $valid_statuses)) {
			return $this->error('Invalid status. Must be one of: ' . implode(', ', $valid_statuses), 400);
		}

		// Validate owner_role if provided
		$valid_roles = ['agent', 'seller', 'buyer', 'seller_attorney', 'buyer_attorney', 'vendor', 'system'];
		if (isset($data['owner_role']) && !in_array($data['owner_role'], $valid_roles)) {
			return $this->error('Invalid owner_role. Must be one of: ' . implode(', ', $valid_roles), 400);
		}

		// Create task
		$task_id = $this->repository->create($data);

		if (!$task_id) {
			return $this->error('Failed to create task', 500);
		}

		// Get the created task
		$task = $this->repository->find($task_id);
		$task_array = $task->toArray();
		$task_array['task_id'] = $task->id;

		// Log event
		$this->logEvent(
			'task',
			$task_id,
			'created',
			[],
			$task->toArray(),
			$transaction->account_id,
			$transaction->id
		);

		// Send email notification if task is assigned to a party
		if (!empty($task->assigned_party_id)) {
			$assignee = $this->party_repository->find($task->assigned_party_id);
			if ($assignee) {
				$this->email_service->sendTaskAssignedNotification($task, $transaction, $assignee);
			}
		}

		return $this->success($task_array, 'Task created successfully', 201);
	}

	public function update_task(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');

		// Get existing task
		$old_task = $this->repository->find($id);
		if (!$old_task) {
			return $this->error('Task not found', 404);
		}

		// SECURITY: Verify user has access to this task's transaction account
		$transaction = $this->transaction_repository->find($old_task->transaction_id);
		if (!$transaction) {
			return $this->error('Associated transaction not found', 404);
		}
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to modify this task', 403);
		}

		// Get and validate request data
		$data = $this->sanitize_data($request->get_json_params());

		// Validation rules (all optional for update)
		$validation = $this->validate_request($request, [
			'title' => ['type' => 'string', 'min_length' => 3],
			'description' => ['type' => 'string'],
			'status' => ['type' => 'string'],
			'owner_role' => ['type' => 'string'],
			'assigned_party_id' => ['type' => 'integer'],
			'due_at' => ['type' => 'string'],
			'sort_order' => ['type' => 'integer'],
		]);

		if (is_wp_error($validation)) {
			return $validation;
		}

		// Validate status if provided
		if (isset($data['status'])) {
			$valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled', 'blocked', 'skipped'];
			if (!in_array($data['status'], $valid_statuses)) {
				return $this->error('Invalid status. Must be one of: ' . implode(', ', $valid_statuses), 400);
			}
		}

		// Validate owner_role if provided
		if (isset($data['owner_role'])) {
			$valid_roles = ['agent', 'seller', 'buyer', 'seller_attorney', 'buyer_attorney', 'vendor', 'system'];
			if (!in_array($data['owner_role'], $valid_roles)) {
				return $this->error('Invalid owner_role. Must be one of: ' . implode(', ', $valid_roles), 400);
			}
		}

		// Don't allow changing transaction_id or id
		unset($data['transaction_id']);
		unset($data['id']);

		// Update task
		$success = $this->repository->update($id, $data);

		if (!$success) {
			return $this->error('Failed to update task', 500);
		}

		// Get updated task
		$task = $this->repository->find($id);
		$task_array = $task->toArray();
		$task_array['task_id'] = $task->id;

		// Get transaction to fetch account_id
		$transaction = $this->transaction_repository->find($task->transaction_id);
		$account_id = $transaction ? $transaction->account_id : null;

		// Log event
		$this->logEvent(
			'task',
			$id,
			'updated',
			$old_task->toArray(),
			$task->toArray(),
			$account_id,
			$task->transaction_id
		);

		// Send email notification if status changed
		if (isset($data['status']) && $data['status'] !== $old_task->status) {
			$transaction = $this->transaction_repository->find($task->transaction_id);
			if ($transaction && !empty($task->assigned_party_id)) {
				$assignee = $this->party_repository->find($task->assigned_party_id);
				if ($assignee) {
					$this->email_service->sendTaskStatusUpdatedNotification(
						$task,
						$transaction,
						$old_task->status,
						$task->status,
						$assignee
					);
				}
			}
		}

		return $this->success($task_array, 'Task updated successfully');
	}

	public function delete_task(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');

		// Get existing task
		$task = $this->repository->find($id);
		if (!$task) {
			return $this->error('Task not found', 404);
		}

		// Get transaction to fetch account_id
		$transaction = $this->transaction_repository->find($task->transaction_id);
		if (!$transaction) {
			return $this->error('Associated transaction not found', 404);
		}

		// SECURITY: Verify user has access to this task's transaction account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to delete this task', 403);
		}

		// Store data for audit before deletion
		$task_data = $task->toArray();
		$account_id = $transaction->account_id;

		// Log event BEFORE deletion to avoid foreign key constraint issues
		$this->logEvent(
			'task',
			$id,
			'deleted',
			$task_data,
			[],
			$account_id,
			$task->transaction_id
		);

		// Delete task
		$success = $this->repository->delete($id);

		if (!$success) {
			return $this->error('Failed to delete task', 500);
		}

		return $this->success(null, 'Task deleted successfully');
	}

	public function reassign_task(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');

		// Get existing task
		$old_task = $this->repository->find($id);
		if (!$old_task) {
			return $this->error('Task not found', 404);
		}

		// SECURITY: Verify user has access to this task's transaction account
		$transaction = $this->transaction_repository->find($old_task->transaction_id);
		if (!$transaction) {
			return $this->error('Associated transaction not found', 404);
		}
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to modify this task', 403);
		}

		// Get request data
		$data = $request->get_json_params();

		// Validation
		$validation = $this->validate_request($request, [
			'assigned_party_id' => ['type' => 'integer'],
			'owner_role' => ['type' => 'string'],
		]);

		if (is_wp_error($validation)) {
			return $validation;
		}

		// Must provide at least one field
		if (!isset($data['assigned_party_id']) && !isset($data['owner_role'])) {
			return $this->error('Must provide assigned_party_id or owner_role', 400);
		}

		// Validate owner_role if provided
		if (isset($data['owner_role'])) {
			$valid_roles = ['agent', 'seller', 'buyer', 'seller_attorney', 'buyer_attorney', 'vendor', 'system'];
			if (!in_array($data['owner_role'], $valid_roles)) {
				return $this->error('Invalid owner_role. Must be one of: ' . implode(', ', $valid_roles), 400);
			}
		}

		// Build update data
		$update_data = [];
		if (isset($data['assigned_party_id'])) {
			$update_data['assigned_party_id'] = (int) $data['assigned_party_id'];
		}
		if (isset($data['owner_role'])) {
			$update_data['owner_role'] = $data['owner_role'];
		}

		// Update task
		$success = $this->repository->update($id, $update_data);

		if (!$success) {
			return $this->error('Failed to reassign task', 500);
		}

		// Get updated task
		$task = $this->repository->find($id);
		$task_array = $task->toArray();
		$task_array['task_id'] = $task->id;

		// Get transaction to fetch account_id
		$transaction = $this->transaction_repository->find($task->transaction_id);
		$account_id = $transaction ? $transaction->account_id : null;

		// Log event
		$this->logEvent(
			'task',
			$id,
			'reassigned',
			$old_task->toArray(),
			$task->toArray(),
			$account_id,
			$task->transaction_id
		);

		return $this->success($task_array, 'Task reassigned successfully');
	}
}
