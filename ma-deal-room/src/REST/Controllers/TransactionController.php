<?php
/**
 * Transaction REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Repositories\TransactionRepository;
use MADealRoom\Repositories\PartyRepository;
use MADealRoom\Repositories\EventRepository;
use MADealRoom\Repositories\TemplateRepository;
use MADealRoom\Repositories\TaskRepository;
use MADealRoom\Repositories\AccountRepository;
use MADealRoom\Services\TemplateEngine;
use MADealRoom\Services\EmailService;
use MADealRoom\Services\TaskAssignmentService;
use MADealRoom\Services\MATimelineCalculator;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Transaction controller
 */
class TransactionController extends BaseController {
	protected $rest_base = 'transactions';
	private $repository;
	private $party_repository;
	private $template_repository;
	private $task_repository;
	private $account_repository;
	private $template_engine;
	private $email_service;
	private $task_assignment_service;
	private $ma_timeline_calculator;

	public function __construct(
		TransactionRepository $repository,
		PartyRepository $party_repository,
		EventRepository $event_repository,
		TemplateRepository $template_repository,
		TaskRepository $task_repository,
		AccountRepository $account_repository,
		TemplateEngine $template_engine,
		EmailService $email_service,
		TaskAssignmentService $task_assignment_service,
		MATimelineCalculator $ma_timeline_calculator
	) {
		parent::__construct($event_repository);
		$this->repository = $repository;
		$this->party_repository = $party_repository;
		$this->template_repository = $template_repository;
		$this->task_repository = $task_repository;
		$this->account_repository = $account_repository;
		$this->template_engine = $template_engine;
		$this->email_service = $email_service;
		$this->task_assignment_service = $task_assignment_service;
		$this->ma_timeline_calculator = $ma_timeline_calculator;
	}

	public function register_routes(): void {
		// GET /transactions - List transactions
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			'methods' => 'GET',
			'callback' => [$this, 'get_items'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// GET /transactions/{id} - Get single transaction
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			'methods' => 'GET',
			'callback' => [$this, 'get_item'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// POST /transactions - Create transaction
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			'methods' => 'POST',
			'callback' => [$this, 'create_item'],
			'permission_callback' => [$this, 'permission_callback'],
			'nonce_callback' => [$this, 'verify_nonce'],
		]);

		// PUT /transactions/{id} - Update transaction
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			'methods' => 'PUT',
			'callback' => [$this, 'update_item'],
			'permission_callback' => [$this, 'permission_callback'],
			'nonce_callback' => [$this, 'verify_nonce'],
		]);

		// DELETE /transactions/{id} - Delete transaction
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			'methods' => 'DELETE',
			'callback' => [$this, 'delete_item'],
			'permission_callback' => [$this, 'permission_callback'],
			'nonce_callback' => [$this, 'verify_nonce'],
		]);

		// POST /transactions/{id}/apply-template - Apply template to existing transaction
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/apply-template', [
			'methods' => 'POST',
			'callback' => [$this, 'apply_template'],
			'permission_callback' => [$this, 'permission_callback'],
			'nonce_callback' => [$this, 'verify_nonce'],
		]);

		// GET /transactions/{id}/parties - Get transaction parties
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/parties', [
			'methods' => 'GET',
			'callback' => [$this, 'get_parties'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// POST /transactions/{id}/parties - Add party to transaction
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/parties', [
			'methods' => 'POST',
			'callback' => [$this, 'create_party'],
			'permission_callback' => [$this, 'permission_callback'],
			'nonce_callback' => [$this, 'verify_nonce'],
		]);

		// GET /transactions/{transaction_id}/parties/{party_id} - Get single party
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<transaction_id>[\d]+)/parties/(?P<party_id>[\d]+)', [
			'methods' => 'GET',
			'callback' => [$this, 'get_party'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// PUT /transactions/{transaction_id}/parties/{party_id} - Update party
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<transaction_id>[\d]+)/parties/(?P<party_id>[\d]+)', [
			'methods' => 'PUT',
			'callback' => [$this, 'update_party'],
			'permission_callback' => [$this, 'permission_callback'],
			'nonce_callback' => [$this, 'verify_nonce'],
		]);

		// DELETE /transactions/{transaction_id}/parties/{party_id} - Delete party
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<transaction_id>[\d]+)/parties/(?P<party_id>[\d]+)', [
			'methods' => 'DELETE',
			'callback' => [$this, 'delete_party'],
			'permission_callback' => [$this, 'permission_callback'],
			'nonce_callback' => [$this, 'verify_nonce'],
		]);

		// GET /transactions/{id}/events - Get transaction events
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)/events', [
			'methods' => 'GET',
			'callback' => [$this, 'get_events'],
			'permission_callback' => [$this, 'permission_callback'],
		]);
	}

	public function get_items(WP_REST_Request $request): WP_REST_Response {
		$account_id = $request->get_param('account_id');
		$status = $request->get_param('status');
		$limit = $request->get_param('limit') ?? 100;
		$offset = $request->get_param('offset') ?? 0;
		$page = $request->get_param('page') ?? 1;

		// SECURITY: Verify user has access to requested account
		if ($account_id) {
			if (!$this->verify_account_access((int)$account_id)) {
				return $this->error('You do not have permission to access this account', 403);
			}
		} else {
			// If no account_id provided, use current user's account
			$account_id = $this->get_user_account_id();
			if (!$account_id) {
				// User has no account, return empty results
				return $this->success([
					'data' => [],
					'pagination' => [
						'total' => 0,
						'page' => 1,
						'per_page' => (int)$limit,
						'total_pages' => 0,
					],
				]);
			}
		}

		if ($status) {
			$transactions = $this->repository->findByStatus($status, $account_id);
		} else {
			$transactions = $this->repository->findByAccount($account_id, [
				'limit' => $limit,
				'offset' => $offset,
			]);
		}

		// Convert models to arrays to include computed fields like transaction_id
		$transactions_array = array_map(function($transaction) {
			return $transaction->toArray();
		}, $transactions);

		// Get total count for pagination (filtered by account)
		$total = $this->repository->count(['account_id' => $account_id]);

		// Return in paginated format matching React expectations
		return $this->success([
			'data' => $transactions_array,
			'pagination' => [
				'total' => (int)$total,
				'page' => (int)$page,
				'per_page' => (int)$limit,
				'total_pages' => ceil($total / $limit),
			],
		]);
	}

	public function get_item(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$transaction = $this->repository->find($id);

		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to access this transaction', 403);
		}

		return $this->success($transaction->toArray());
	}

	public function create_item(WP_REST_Request $request) {
		// Start performance monitoring
		$transaction_span = \MADealRoom\Services\MonitoringService::start_transaction(
			'POST /transactions',
			'http.server'
		);

		$data = $this->sanitize_data($request->get_params());

		// Convert empty strings to NULL for nullable fields
		$nullable_fields = [
			'mls_number', 'property_year_built', 'property_metadata', 'sale_price',
			'list_price', 'accepted_offer_price', 'bedrooms', 'bathrooms', 'square_feet',
			'lot_size', 'parking_spaces', 'actual_closing_date', 'assigned_agent_id',
			'template_id', 'notes', 'crm_opportunity_id', 'crm_deal_id', 'crm_provider',
			'crm_last_sync', 'crm_sync_status', 'crm_stage_mapping', 'crm_url'
		];
		foreach ($nullable_fields as $field) {
			if (isset($data[$field]) && $data[$field] === '') {
				$data[$field] = null;
			}
		}

		// Validate required fields
		$validation = $this->validate_request($request, [
			'property_address' => ['required' => true, 'type' => 'string'],
			'property_city' => ['required' => true, 'type' => 'string'],
			'property_zip' => ['required' => true, 'type' => 'string'],
			'property_type' => ['required' => true, 'type' => 'string', 'enum' => ['SFH', 'Condo', 'Multifamily', 'Land', 'Commercial']],
		]);

		if (is_wp_error($validation)) {
			return $validation;
		}

		// SECURITY: Validate account_id access
		if (!empty($data['account_id'])) {
			// User specified an account_id, verify they have access to it
			if (!$this->verify_account_access((int)$data['account_id'])) {
				return $this->error('You do not have permission to create transactions in this account', 403);
			}
		} else {
			// Auto-assign user's account
			$current_user = $this->get_current_user();
			if (!$current_user) {
				return $this->error('Authentication required', 401);
			}

			// Find account based on user type (WordPress or custom)
			$account = $this->account_repository->findByUserId(
				$current_user['id'],
				$current_user['type']
			);

			if (!$account) {
				return $this->error('You must have an account to create transactions', 403);
			}

			$data['account_id'] = $account->id;
		}

		// SECURITY: Validate template ownership if template_id provided
		if (!empty($data['template_id'])) {
			$template = $this->template_repository->find((int)$data['template_id']);

			if (!$template) {
				return $this->error('Template not found', 404);
			}

			// Verify template access: must be system template OR belong to user's account
			if (!$template->is_system && $template->account_id !== $data['account_id']) {
				return $this->error('You do not have permission to use this template', 403);
			}
		}

		// AUTO-CALCULATE MILESTONE DATES: If offer_accepted_date is provided but P&S or closing dates are not,
		// use MA Timeline Calculator to suggest dates based on typical Massachusetts transaction timelines
		if (!empty($data['offer_accepted_date'])) {
			$transaction_side = $data['transaction_side'] ?? 'buyer';
			$property_data = [
				'year_built' => $data['property_year_built'] ?? null,
			];

			// Get suggested dates (only if not already provided by user)
			$suggested_dates = $this->ma_timeline_calculator->suggestTransactionDates(
				$transaction_side,
				$data['offer_accepted_date'],
				$data['listing_date'] ?? null,
				$property_data
			);

			// Apply suggested dates only if not already set
			if (empty($data['ps_agreement_date']) && !empty($suggested_dates['ps_agreement_date'])) {
				$data['ps_agreement_date'] = $suggested_dates['ps_agreement_date'];
			}

			if (empty($data['closing_date']) && !empty($suggested_dates['closing_date'])) {
				$data['closing_date'] = $suggested_dates['closing_date'];
			}
		}

		// Start database transaction
		global $wpdb;
		$wpdb->query('START TRANSACTION');

		$id = $this->repository->create($data);

		if (!$id) {
			$wpdb->query('ROLLBACK');

			// Check if error was due to duplicate MLS number
			$last_error = $wpdb->last_error;
			if (strpos($last_error, 'uk_account_mls') !== false || strpos($last_error, 'Duplicate entry') !== false) {
				return $this->error('A transaction with this MLS number already exists for this account', 409);
			}

			return $this->error('Failed to create transaction', 500);
		}

		$transaction = $this->repository->find($id);

		// If template_id is provided, instantiate tasks from template
		if (!empty($data['template_id'])) {
			$template = $this->template_repository->find((int)$data['template_id']);

			if ($template) {
				// Prepare property metadata for conditions
				$property_data = array_merge(
					$transaction->property_metadata ?? [],
					[
						'year_built' => $transaction->property_year_built ?? null,
						'has_septic' => $transaction->property_metadata['has_septic'] ?? false,
					]
				);

				// Generate tasks from template
				$task_definitions = $this->template_engine->instantiateTasks(
					$template,
					$transaction,
					$property_data
				);

				// DEBUG: Log how many task definitions were generated
				error_log(sprintf(
					'[TransactionController] Transaction #%d: instantiateTasks() returned %d task definitions',
					$id,
					count($task_definitions)
				));

				// Create tasks in database
				foreach ($task_definitions as $task_data) {
					$task_data['transaction_id'] = $id;
					$task_data['template_id'] = $template->id;
					$task_created = $this->task_repository->create($task_data);
					if (!$task_created) {
						$wpdb->query('ROLLBACK');
						return $this->error('Failed to create tasks from template', 500);
					}
				}
			}
		}

		// Commit transaction
		$wpdb->query('COMMIT');

		// Auto-assign tasks if there are parties available
		if (!empty($data['template_id'])) {
			$this->task_assignment_service->performAutoAssignment($id, true);
		}

		$this->logEvent(
			'transaction',
			$transaction->id,
			'created',
			[],
			$transaction->toArray(),
			$transaction->account_id,
			$transaction->id
		);

		// Send notification emails about the new transaction
		$account = $this->account_repository->find($transaction->account_id);
		if ($account) {
			$this->email_service->sendTransactionCreatedNotification(
				$transaction,
				$account->owner_user_id,
				!empty($data['assigned_agent_id']) ? (int)$data['assigned_agent_id'] : null
			);
		}

		// Finish performance monitoring
		if ($transaction_span) {
			$transaction_span->finish();
		}

		return $this->success($transaction->toArray(), 'Transaction created successfully', 201);
	}

	public function update_item(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$old_transaction = $this->repository->find($id);

		if (!$old_transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($old_transaction->account_id)) {
			return $this->error('You do not have permission to update this transaction', 403);
		}

		$data = $this->sanitize_data($request->get_params());
		unset($data['id']); // Don't allow ID updates

		// Convert empty strings to NULL for nullable fields
		$nullable_fields = [
			'mls_number', 'property_year_built', 'property_metadata', 'sale_price',
			'list_price', 'accepted_offer_price', 'bedrooms', 'bathrooms', 'square_feet',
			'lot_size', 'parking_spaces', 'actual_closing_date', 'assigned_agent_id',
			'template_id', 'notes', 'crm_opportunity_id', 'crm_deal_id', 'crm_provider',
			'crm_last_sync', 'crm_sync_status', 'crm_stage_mapping', 'crm_url'
		];
		foreach ($nullable_fields as $field) {
			if (isset($data[$field]) && $data[$field] === '') {
				$data[$field] = null;
			}
		}

		// SECURITY: Prevent changing account_id to an unauthorized account
		if (isset($data['account_id']) && $data['account_id'] !== $old_transaction->account_id) {
			if (!$this->verify_account_access((int)$data['account_id'])) {
				return $this->error('You do not have permission to transfer this transaction to another account', 403);
			}
		}

		$success = $this->repository->update($id, $data);

		if (!$success) {
			return $this->error('Failed to update transaction', 500);
		}

		$updated_transaction = $this->repository->find($id);

		$this->logEvent(
			'transaction',
			$updated_transaction->id,
			'updated',
			$old_transaction->toArray(),
			$updated_transaction->toArray(),
			$updated_transaction->account_id,
			$updated_transaction->id
		);

		// Send notification if status changed
		if (isset($data['status']) && $data['status'] !== $old_transaction->status) {
			$parties = $this->party_repository->query(['transaction_id' => $id]);
			$recipients = [];
			
			foreach ($parties as $party) {
				if ($party->email) {
					$recipients[] = $party->email;
				}
			}

			// Also add account owner
			$account = $this->account_repository->find($updated_transaction->account_id);
			if ($account) {
				$owner = get_user_by('id', $account->owner_user_id);
				if ($owner && !\in_array($owner->user_email, $recipients)) {
					$recipients[] = $owner->user_email;
				}
			}

			if (!empty($recipients)) {
				$this->email_service->sendTransactionStatusChangedNotification(
					$updated_transaction,
					$old_transaction->status,
					$data['status'],
					$recipients
				);
			}
		}

		return $this->success($updated_transaction->toArray(), 'Transaction updated successfully');
	}

	public function delete_item(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$transaction = $this->repository->find($id);

		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to delete this transaction', 403);
		}

		// Log the delete event BEFORE deleting the transaction to avoid foreign key constraint violation
		$this->logEvent(
			'transaction',
			$transaction->id,
			'deleted',
			$transaction->toArray(),
			[],
			$transaction->account_id,
			$transaction->id
		);

		$success = $this->repository->delete($id);

		if (!$success) {
			return $this->error('Failed to delete transaction', 500);
		}

		return $this->success(null, 'Transaction deleted successfully');
	}

	/**
	 * Apply a template to an existing transaction
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function apply_template(WP_REST_Request $request) {
		$transaction_id = (int) $request->get_param('id');
		$template_id = (int) $request->get_param('template_id');

		if (!$template_id) {
			return $this->error('Template ID is required', 400);
		}

		// Get transaction
		$transaction = $this->repository->find($transaction_id);
		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to modify this transaction', 403);
		}

		// Get template
		$template = $this->template_repository->find($template_id);
		if (!$template) {
			return $this->error('Template not found', 404);
		}

		// SECURITY: Verify template access
		if (!$template->is_system && $template->account_id !== $transaction->account_id) {
			return $this->error('You do not have permission to use this template', 403);
		}

		// Prepare property metadata for conditions
		$property_data = array_merge(
			$transaction->property_metadata ?? [],
			[
				'year_built' => $transaction->property_year_built ?? null,
				'has_septic' => $transaction->property_metadata['has_septic'] ?? false,
			]
		);

		// Start database transaction
		global $wpdb;
		$wpdb->query('START TRANSACTION');

		try {
			// Generate tasks from template
			$task_definitions = $this->template_engine->instantiateTasks(
				$template,
				$transaction,
				$property_data
			);

			// Create tasks in database
			$created_tasks = [];
			foreach ($task_definitions as $task_data) {
				$task_data['transaction_id'] = $transaction_id;
				$task_data['template_id'] = $template->id;
				$task_id = $this->task_repository->create($task_data);
				if (!$task_id) {
					$wpdb->query('ROLLBACK');
					return $this->error('Failed to create tasks from template', 500);
				}
				$created_tasks[] = $task_id;
			}

			// Commit transaction
			$wpdb->query('COMMIT');

			// Auto-assign tasks if there are parties available
			$this->task_assignment_service->performAutoAssignment($transaction_id, true);

			// Log event
			$this->logEvent(
				'transaction',
				$transaction_id,
				'template_applied',
				['template_id' => $template_id],
				['template_name' => $template->name, 'tasks_created' => count($created_tasks)],
				$transaction->account_id,
				$transaction_id
			);

			return $this->success([
				'message' => sprintf('Successfully applied template "%s" and created %d tasks', $template->name, count($created_tasks)),
				'tasks_created' => count($created_tasks),
				'task_ids' => $created_tasks,
			]);

		} catch (\Exception $e) {
			$wpdb->query('ROLLBACK');
			return $this->error('Failed to apply template: ' . $e->getMessage(), 500);
		}
	}

	public function get_parties(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$transaction = $this->repository->find($id);

		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to access this transaction', 403);
		}

		$parties = $this->party_repository->findByTransaction($id);

		return $this->success($parties);
	}

	public function get_party(WP_REST_Request $request) {
		$transaction_id = (int) $request->get_param('transaction_id');
		$party_id = (int) $request->get_param('party_id');

		// Verify transaction exists
		$transaction = $this->repository->find($transaction_id);
		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to access this transaction', 403);
		}

		// Get party
		$party = $this->party_repository->find($party_id);
		if (!$party) {
			return $this->error('Party not found', 404);
		}

		// Verify party belongs to this transaction
		if ($party->transaction_id !== $transaction_id) {
			return $this->error('Party does not belong to this transaction', 403);
		}

		return $this->success($party);
	}

	public function create_party(WP_REST_Request $request) {
		$transaction_id = (int) $request->get_param('id');

		// Verify transaction exists
		$transaction = $this->repository->find($transaction_id);
		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to modify this transaction', 403);
		}

		// Get and validate request data
		$data = $this->sanitize_data($request->get_json_params());

		// Validation rules
		$validation = $this->validate_request($request, [
			'role' => ['required' => true, 'type' => 'string'],
			'contact_name' => ['required' => true, 'type' => 'string', 'min_length' => 2],
			'email' => ['type' => 'email'],
			'phone' => ['type' => 'string'],
		]);

		if (is_wp_error($validation)) {
			return $validation;
		}

		// Validate role is one of the allowed values
		$valid_roles = [
			'buyer', 'seller', 'buyer_attorney', 'seller_attorney',
			'buyer_lender', 'buyer_agent', 'seller_agent', 'title_company',
			'inspector', 'appraiser', 'hoa_manager', 'septic_inspector',
			'fire_dept', 'other'
		];

		if (!in_array($data['role'], $valid_roles)) {
			return $this->error('Invalid party role. Must be one of: ' . implode(', ', $valid_roles), 400);
		}

		// Add transaction_id
		$data['transaction_id'] = $transaction_id;

		// Create party
		$party_id = $this->party_repository->create($data);

		if (!$party_id) {
			return $this->error('Failed to create party', 500);
		}

		// Get the created party
		$party = $this->party_repository->find($party_id);

		// Log event
		$this->logEvent(
			'party',
			$party_id,
			'created',
			[],
			$party->toArray(),
			$transaction->account_id,
			$transaction_id
		);

		// Send welcome email to the newly added party
		if ($party->email) {
			$this->email_service->sendPartyAddedNotification($party, $transaction);
		}

		// Auto-assign tasks to the newly added party
		$this->task_assignment_service->autoAssignTasksToNewParty($party_id);

		return $this->success($party, 'Party created successfully', 201);
	}

	public function update_party(WP_REST_Request $request) {
		$transaction_id = (int) $request->get_param('transaction_id');
		$party_id = (int) $request->get_param('party_id');

		// Verify transaction exists
		$transaction = $this->repository->find($transaction_id);
		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to modify this transaction', 403);
		}

		// Get existing party
		$existing_party = $this->party_repository->find($party_id);
		if (!$existing_party) {
			return $this->error('Party not found', 404);
		}

		// Verify party belongs to this transaction
		if ($existing_party->transaction_id !== $transaction_id) {
			return $this->error('Party does not belong to this transaction', 403);
		}

		// Get and validate request data
		$data = $this->sanitize_data($request->get_json_params());

		// Validation rules (all optional for update)
		$validation = $this->validate_request($request, [
			'role' => ['type' => 'string'],
			'contact_name' => ['type' => 'string', 'min_length' => 2],
			'email' => ['type' => 'email'],
			'phone' => ['type' => 'string'],
		]);

		if (is_wp_error($validation)) {
			return $validation;
		}

		// Validate role if provided
		if (isset($data['role'])) {
			$valid_roles = [
				'buyer', 'seller', 'buyer_attorney', 'seller_attorney',
				'buyer_lender', 'buyer_agent', 'seller_agent', 'title_company',
				'inspector', 'appraiser', 'hoa_manager', 'septic_inspector',
				'fire_dept', 'other'
			];

			if (!in_array($data['role'], $valid_roles)) {
				return $this->error('Invalid party role. Must be one of: ' . implode(', ', $valid_roles), 400);
			}
		}

		// Don't allow changing transaction_id
		unset($data['transaction_id']);
		unset($data['id']);

		// Store old data for audit
		$old_data = $existing_party->toArray();

		// Update party
		$success = $this->party_repository->update($party_id, $data);

		if (!$success) {
			return $this->error('Failed to update party', 500);
		}

		// Get updated party
		$updated_party = $this->party_repository->find($party_id);

		// Log event
		$this->logEvent(
			'party',
			$party_id,
			'updated',
			$old_data,
			$updated_party->toArray(),
			$transaction->account_id,
			$transaction_id
		);

		return $this->success($updated_party, 'Party updated successfully');
	}

	public function delete_party(WP_REST_Request $request) {
		$transaction_id = (int) $request->get_param('transaction_id');
		$party_id = (int) $request->get_param('party_id');

		// Verify transaction exists
		$transaction = $this->repository->find($transaction_id);
		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to modify this transaction', 403);
		}

		// Get existing party
		$party = $this->party_repository->find($party_id);
		if (!$party) {
			return $this->error('Party not found', 404);
		}

		// Verify party belongs to this transaction
		if ($party->transaction_id !== $transaction_id) {
			return $this->error('Party does not belong to this transaction', 403);
		}

		// Store data for audit before deletion
		$party_data = $party->toArray();

		// Delete party
		$success = $this->party_repository->delete($party_id);

		if (!$success) {
			return $this->error('Failed to delete party', 500);
		}

		// Log event
		$this->logEvent(
			'party',
			$party_id,
			'deleted',
			$party_data,
			[],
			$transaction->account_id,
			$transaction_id
		);

		return $this->success(null, 'Party deleted successfully');
	}

	public function get_events(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$transaction = $this->repository->find($id);

		if (!$transaction) {
			return $this->error('Transaction not found', 404);
		}

		// SECURITY: Verify user has access to this transaction's account
		if (!$this->verify_account_access($transaction->account_id)) {
			return $this->error('You do not have permission to access this transaction', 403);
		}

		$limit = $request->get_param('per_page') ?? 100;
		$events = $this->event_repository->findByTransaction($id, $limit);

		// Return paginated format for consistency
		return $this->success([
			'data' => $events,
			'pagination' => [
				'total' => count($events),
				'page' => 1,
				'per_page' => $limit,
				'total_pages' => 1,
			],
		]);
	}
}
