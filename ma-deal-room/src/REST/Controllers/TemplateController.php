<?php
/**
 * Template REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Repositories\TemplateRepository;
use MADealRoom\Repositories\AccountRepository;
use MADealRoom\Repositories\TransactionRepository;
use MADealRoom\Repositories\EventRepository;
use WP_REST_Request;

class TemplateController extends BaseController {
	protected $rest_base = 'templates';
	private $repository;
	private $account_repository;
	private $transaction_repository;

	public function __construct(TemplateRepository $repository, AccountRepository $account_repository, TransactionRepository $transaction_repository, EventRepository $event_repository) {
		parent::__construct($event_repository);
		$this->repository = $repository;
		$this->account_repository = $account_repository;
		$this->transaction_repository = $transaction_repository;
	}

	public function register_routes(): void {
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			['methods' => 'GET', 'callback' => [$this, 'get_items'], 'permission_callback' => [$this, 'permission_callback']],
			['methods' => 'POST', 'callback' => [$this, 'create_item'], 'permission_callback' => [$this, 'permission_callback'], 'nonce_callback' => [$this, 'verify_nonce']],
		]);

		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
			['methods' => 'GET', 'callback' => [$this, 'get_item'], 'permission_callback' => [$this, 'permission_callback']],
			['methods' => 'PUT', 'callback' => [$this, 'update_item'], 'permission_callback' => [$this, 'permission_callback'], 'nonce_callback' => [$this, 'verify_nonce']],
			['methods' => 'DELETE', 'callback' => [$this, 'delete_item'], 'permission_callback' => [$this, 'permission_callback'], 'nonce_callback' => [$this, 'verify_nonce']],
		]);
	}

	public function get_items(WP_REST_Request $request) {
		$account_id = $request->get_param('account_id');
		$property_type = $request->get_param('property_type');
		$transaction_side = $request->get_param('transaction_side');

		// SECURITY: Verify account access when account_id is specified
		if ($account_id) {
			if (!$this->verify_account_access((int)$account_id)) {
				return $this->error('You do not have permission to access this account', 403);
			}
		} else {
			// If no account specified, use current user's account
			$account_id = $this->get_user_account_id();
		}

		// Use new filtering method if property_type or transaction_side is specified
		if ($property_type || $transaction_side) {
			$filters = [
				'account_id' => $account_id,
			];

			if ($property_type) {
				$filters['property_type'] = $property_type;
			}

			if ($transaction_side) {
				$filters['transaction_side'] = $transaction_side;
			}

			$templates = $this->repository->findByFilters($filters);
		} else {
			// Fallback to existing method for backwards compatibility
			$templates = $this->repository->findActive($account_id);
		}

		// Convert models to arrays to include computed fields like task_count
		$templates_array = array_map(function($template) {
			return $template->toArray();
		}, $templates);

		// Return in paginated format matching React expectations
		return $this->success([
			'data' => $templates_array,
			'pagination' => [
				'total' => count($templates_array),
				'page' => 1,
				'per_page' => count($templates_array),
				'total_pages' => 1,
			],
		]);
	}

	public function get_item(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$template = $this->repository->find($id);

		if (!$template) {
			return $this->error('Template not found', 404);
		}

		// SECURITY: Verify account access for non-system templates
		if (!$template->is_system && $template->account_id) {
			if (!$this->verify_account_access($template->account_id)) {
				return $this->error('You do not have permission to access this template', 403);
			}
		}

		return $this->success($template);
	}

	public function create_item(WP_REST_Request $request) {
		$data = $this->sanitize_data($request->get_params());

		// Preserve line breaks in template_yaml (sanitize_text_field strips them)
		if (isset($request['template_yaml'])) {
			$data['template_yaml'] = sanitize_textarea_field($request['template_yaml']);
		}

		// Validate required fields
		$validation = $this->validate_request($request, [
			'name' => ['required' => true, 'type' => 'string'],
			'property_type' => ['required' => true, 'type' => 'string'],
			'template_yaml' => ['required' => true, 'type' => 'string'],
		]);

		if (is_wp_error($validation)) {
			return $validation;
		}

		// Auto-assign account_id if not provided
		if (empty($data['account_id'])) {
			$user_id = get_current_user_id();
			$account = $this->account_repository->findByOwnerUserId($user_id);

			if (!$account) {
				$account = $this->account_repository->findFirstActive();
			}

			if ($account) {
				$data['account_id'] = $account->id;
			}
		} else {
			// SECURITY: Verify account access when account_id is specified
			if (!$this->verify_account_access((int)$data['account_id'])) {
				return $this->error('You do not have permission to create templates for this account', 403);
			}
		}

		// Set defaults
		$data['is_system'] = false;
		$data['is_active'] = $data['is_active'] ?? true;
		$data['version'] = $data['version'] ?? '1.0';

		$id = $this->repository->create($data);

		if (!$id) {
			return $this->error('Failed to create template', 500);
		}

		$template = $this->repository->find($id);
		$this->logEvent(
			'template',
			$template->id,
			'created',
			[],
			$template->toArray(),
			$template->account_id ?? null
		);
		return $this->success($template->toArray(), 'Template created successfully', 201);
	}

	public function update_item(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$old_template = $this->repository->find($id);

		if (!$old_template) {
			return $this->error('Template not found', 404);
		}

		// Prevent updating system templates
		if ($old_template->is_system) {
			return $this->error('Cannot update system templates', 403);
		}

		// SECURITY: Verify account access for non-system templates
		if ($old_template->account_id && !$this->verify_account_access($old_template->account_id)) {
			return $this->error('You do not have permission to modify this template', 403);
		}

		$data = $this->sanitize_data($request->get_params());

		// Preserve line breaks in template_yaml (sanitize_text_field strips them)
		if (isset($request['template_yaml'])) {
			$data['template_yaml'] = sanitize_textarea_field($request['template_yaml']);
		}

		unset($data['id']); // Don't allow ID updates
		unset($data['is_system']); // Don't allow changing system flag

		$success = $this->repository->update($id, $data);

		if (!$success) {
			return $this->error('Failed to update template', 500);
		}

		$updated_template = $this->repository->find($id);
		$this->logEvent(
			'template',
			$updated_template->id,
			'updated',
			$old_template->toArray(),
			$updated_template->toArray(),
			$updated_template->account_id ?? null
		);
		return $this->success($updated_template->toArray(), 'Template updated successfully');
	}

	public function delete_item(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$template = $this->repository->find($id);

		if (!$template) {
			return $this->error('Template not found', 404);
		}

		// Prevent deleting system templates
		if ($template->is_system) {
			return $this->error('Cannot delete system templates', 403);
		}

		// SECURITY: Verify account access for non-system templates
		if ($template->account_id && !$this->verify_account_access($template->account_id)) {
			return $this->error('You do not have permission to delete this template', 403);
		}

		// Check if template is in use
		$in_use = $this->transaction_repository->countByTemplateId($id);

		if ($in_use > 0) {
			return $this->error('Cannot delete template that is in use by transactions', 400);
		}

		$success = $this->repository->delete($id);

		if (!$success) {
			return $this->error('Failed to delete template', 500);
		}

		$this->logEvent(
			'template',
			$template->id,
			'deleted',
			$template->toArray(),
			[],
			$template->account_id ?? null
		);

		return $this->success(null, 'Template deleted successfully');
	}
}
