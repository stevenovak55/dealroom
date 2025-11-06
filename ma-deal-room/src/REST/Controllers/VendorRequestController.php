<?php
/**
 * Vendor Request REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Repositories\VendorRequestRepository;
use MADealRoom\Services\VendorService;
use MADealRoom\Repositories\EventRepository;
use WP_REST_Request;
use WP_Error;

class VendorRequestController extends BaseController {
	protected $rest_base = 'vendor-requests';

	private VendorRequestRepository $vendor_request_repository;
	private VendorService $vendor_service;

	public function __construct(
		VendorRequestRepository $vendor_request_repository,
		VendorService $vendor_service,
		EventRepository $event_repository
	) {
		parent::__construct($event_repository);
		$this->vendor_request_repository = $vendor_request_repository;
		$this->vendor_service = $vendor_service;
	}

	public function register_routes(): void {
		// List vendor requests
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_items'],
				'permission_callback' => [$this, 'get_items_permissions_check'],
			],
			[
				'methods' => 'POST',
				'callback' => [$this, 'create_item'],
				'permission_callback' => [$this, 'create_item_permissions_check'],
			],
		]);

		// Get single vendor request
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_item'],
				'permission_callback' => [$this, 'get_item_permissions_check'],
			],
		]);

		// Resend vendor request
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/resend', [
			[
				'methods' => 'POST',
				'callback' => [$this, 'resend_request'],
				'permission_callback' => [$this, 'update_item_permissions_check'],
			],
		]);

		// Cancel vendor request
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/cancel', [
			[
				'methods' => 'POST',
				'callback' => [$this, 'cancel_request'],
				'permission_callback' => [$this, 'update_item_permissions_check'],
			],
		]);
	}

	public function get_items(WP_REST_Request $request) {
		$transaction_id = $request->get_param('transaction_id');

		if ($transaction_id) {
			$vendor_requests = $this->vendor_request_repository->query([
				'transaction_id' => $transaction_id
			]);
		} else {
			$vendor_requests = $this->vendor_request_repository->getAll();
		}

		return $this->success($vendor_requests);
	}

	public function get_item(WP_REST_Request $request) {
		$id = $request->get_param('id');
		$vendor_request = $this->vendor_request_repository->find($id);

		if (!$vendor_request) {
			return $this->error('Vendor request not found', 404);
		}

		return $this->success($vendor_request);
	}

	public function create_item(WP_REST_Request $request) {
		$data = [
			'task_id' => $request->get_param('task_id'),
			'transaction_id' => $request->get_param('transaction_id'),
			'party_id' => $request->get_param('party_id'),
			'vendor_type' => $request->get_param('vendor_type'),
			'vendor_email' => $request->get_param('vendor_email'),
			'vendor_phone' => $request->get_param('vendor_phone'),
			'metadata' => $request->get_param('metadata'),
		];

		// Validate required fields
		if (empty($data['task_id']) || empty($data['transaction_id'])) {
			return $this->error('task_id and transaction_id are required', 400);
		}

		if (empty($data['vendor_type']) || empty($data['vendor_email'])) {
			return $this->error('vendor_type and vendor_email are required', 400);
		}

		// Generate secure token (64 characters)
		$data['token'] = bin2hex(random_bytes(32));

		// Set token expiration (30 days from now)
		$data['token_expires_at'] = date('Y-m-d H:i:s', strtotime('+30 days'));

		// Set initial status
		$data['status'] = 'sent';

		// Create vendor request
		$vendor_request_id = $this->vendor_request_repository->create($data);

		if (!$vendor_request_id) {
			return $this->error('Failed to create vendor request', 500);
		}

		// Send email to vendor
		$this->vendor_service->sendVendorRequest($vendor_request_id);

		// Log event
		$this->logEvent(
			'vendor_request',
			$vendor_request_id,
			'created',
			null,
			$data,
			null,
			$data['transaction_id']
		);

		// Return created vendor request
		$vendor_request = $this->vendor_request_repository->find($vendor_request_id);
		return $this->success($vendor_request, 'Vendor request created successfully', 201);
	}

	public function resend_request(WP_REST_Request $request) {
		$id = $request->get_param('id');
		$vendor_request = $this->vendor_request_repository->find($id);

		if (!$vendor_request) {
			return $this->error('Vendor request not found', 404);
		}

		// Update token expiration
		$this->vendor_request_repository->update($id, [
			'token_expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
			'status' => 'sent',
		]);

		// Resend email
		$this->vendor_service->sendVendorRequest($id);

		// Log event
		$this->logEvent(
			'vendor_request',
			$id,
			'resent',
			null,
			['resent_at' => current_time('mysql')],
			null,
			$vendor_request->transaction_id
		);

		// Return updated vendor request
		$vendor_request = $this->vendor_request_repository->find($id);
		return $this->success($vendor_request, 'Vendor request resent successfully');
	}

	public function cancel_request(WP_REST_Request $request) {
		$id = $request->get_param('id');
		$vendor_request = $this->vendor_request_repository->find($id);

		if (!$vendor_request) {
			return $this->error('Vendor request not found', 404);
		}

		// Update status to cancelled
		$this->vendor_request_repository->update($id, [
			'status' => 'cancelled',
		]);

		// Log event
		$this->logEvent(
			'vendor_request',
			$id,
			'cancelled',
			['status' => $vendor_request->status],
			['status' => 'cancelled'],
			null,
			$vendor_request->transaction_id
		);

		// Return updated vendor request
		$vendor_request = $this->vendor_request_repository->find($id);
		return $this->success($vendor_request, 'Vendor request cancelled successfully');
	}

	public function get_items_permissions_check($request) {
		return current_user_can('edit_posts');
	}

	public function get_item_permissions_check($request) {
		return current_user_can('edit_posts');
	}

	public function create_item_permissions_check($request) {
		return current_user_can('edit_posts');
	}

	public function update_item_permissions_check($request) {
		return current_user_can('edit_posts');
	}
}
