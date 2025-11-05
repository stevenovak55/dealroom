<?php
/**
 * Vendor Request Controller (Admin Side)
 *
 * Handles CRUD operations for vendor requests from the admin interface.
 * This is separate from VendorPortalController which handles public vendor portal endpoints.
 *
 * @package MADealRoom\REST\Controllers
 * @since 2.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Services\VendorService;
use MADealRoom\Services\NotificationService;
use MADealRoom\Repositories\VendorRequestRepository;
use MADealRoom\Repositories\VendorRatingRepository;
use MADealRoom\Repositories\EventRepository;
use WP_REST_Request;
use WP_Error;

class VendorRequestController extends BaseController {
	protected $rest_base = 'vendor-requests';
	private $vendor_service;
	private $notification_service;
	private $vendor_request_repository;
	private $vendor_rating_repository;

	public function __construct(
		VendorService $vendor_service,
		NotificationService $notification_service,
		VendorRequestRepository $vendor_request_repository,
		VendorRatingRepository $vendor_rating_repository,
		EventRepository $event_repository
	) {
		parent::__construct($event_repository);
		$this->vendor_service = $vendor_service;
		$this->notification_service = $notification_service;
		$this->vendor_request_repository = $vendor_request_repository;
		$this->vendor_rating_repository = $vendor_rating_repository;
	}

	public function register_routes(): void {
		// List vendor requests (with filters)
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			'methods' => 'GET',
			'callback' => [$this, 'list_vendor_requests'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// Create vendor request
		register_rest_route($this->namespace, '/' . $this->rest_base, [
			'methods' => 'POST',
			'callback' => [$this, 'create_vendor_request'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// Get single vendor request
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
			'methods' => 'GET',
			'callback' => [$this, 'get_vendor_request'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// Update vendor request
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
			'methods' => 'PUT',
			'callback' => [$this, 'update_vendor_request'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// Delete vendor request
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
			'methods' => 'DELETE',
			'callback' => [$this, 'delete_vendor_request'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// Resend invitation email
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/resend', [
			'methods' => 'POST',
			'callback' => [$this, 'resend_invitation'],
			'permission_callback' => [$this, 'permission_callback'],
		]);

		// Submit vendor rating
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/rate', [
			'methods' => 'POST',
			'callback' => [$this, 'rate_vendor'],
			'permission_callback' => [$this, 'permission_callback'],
		]);
	}

	/**
	 * List vendor requests with optional filters
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function list_vendor_requests(WP_REST_Request $request) {
		$filters = [];

		// Transaction filter
		if ($request->get_param('transaction_id')) {
			$filters['transaction_id'] = (int) $request->get_param('transaction_id');
		}

		// Status filter
		if ($request->get_param('status')) {
			$filters['status'] = sanitize_text_field($request->get_param('status'));
		}

		// Vendor type filter
		if ($request->get_param('vendor_type')) {
			$filters['vendor_type'] = sanitize_text_field($request->get_param('vendor_type'));
		}

		// Task filter
		if ($request->get_param('task_id')) {
			$filters['task_id'] = (int) $request->get_param('task_id');
		}

		// Pagination
		$page = $request->get_param('page') ?? 1;
		$per_page = $request->get_param('per_page') ?? 20;

		$options = [
			'limit' => (int) $per_page,
			'offset' => ((int) $page - 1) * (int) $per_page,
			'order' => 'created_at',
			'direction' => 'DESC',
		];

		$vendor_requests = $this->vendor_request_repository->query($filters, $options);
		$total = $this->vendor_request_repository->count($filters);

		return $this->success([
			'vendor_requests' => array_map(function($vr) {
				return $vr->toArray();
			}, $vendor_requests),
			'total' => $total,
			'page' => (int) $page,
			'per_page' => (int) $per_page,
			'pages' => ceil($total / (int) $per_page),
		]);
	}

	/**
	 * Create a new vendor request
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function create_vendor_request(WP_REST_Request $request) {
		// Validate required fields
		$required = ['transaction_id', 'vendor_type', 'vendor_email'];
		foreach ($required as $field) {
			if (empty($request->get_param($field))) {
				return $this->error("Missing required field: {$field}", 400);
			}
		}

		// Validate email
		$vendor_email = sanitize_email($request->get_param('vendor_email'));
		if (!is_email($vendor_email)) {
			return $this->error('Invalid vendor email address', 400);
		}

		// Validate vendor type
		$valid_types = ['fire_dept', 'septic_inspector', 'hoa_manager', 'title_company', 'appraiser', 'inspector', 'attorney', 'contractor', 'other'];
		$vendor_type = sanitize_text_field($request->get_param('vendor_type'));
		if (!in_array($vendor_type, $valid_types)) {
			return $this->error('Invalid vendor type', 400);
		}

		// Generate secure token before creating the request (required by database constraint)
		$token = bin2hex(random_bytes(32));
		$token_expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

		// Prepare vendor request data WITH token
		$data = [
			'transaction_id' => (int) $request->get_param('transaction_id'),
			'task_id' => $request->get_param('task_id') ? (int) $request->get_param('task_id') : null,
			'party_id' => $request->get_param('party_id') ? (int) $request->get_param('party_id') : null,
			'vendor_type' => $vendor_type,
			'vendor_email' => $vendor_email,
			'vendor_phone' => $request->get_param('vendor_phone') ? sanitize_text_field($request->get_param('vendor_phone')) : null,
			'vendor_name' => $request->get_param('vendor_name') ? sanitize_text_field($request->get_param('vendor_name')) : null,
			'vendor_company' => $request->get_param('vendor_company') ? sanitize_text_field($request->get_param('vendor_company')) : null,
			'token' => $token,
			'token_expires_at' => $token_expires_at,
			'status' => 'sent',
			'created_at' => current_time('mysql'),
			'updated_at' => current_time('mysql'),
			'metadata' => $request->get_param('notes') ? json_encode(['notes' => sanitize_textarea_field($request->get_param('notes'))]) : null,
		];

		// Create vendor request with token already included
		$vendor_request_id = $this->vendor_request_repository->create($data);

		if (!$vendor_request_id) {
			return $this->error('Failed to create vendor request', 500);
		}

		// Fetch the full vendor request object
		$vendor_request = $this->vendor_request_repository->find($vendor_request_id);

		if (!$vendor_request) {
			return $this->error('Failed to retrieve vendor request', 500);
		}

		// Generate portal URL (public vendor portal, not agent dashboard)
		$portal_url = home_url('/vendor-portal/?token=' . $token);

		// Send invitation email
		try {
			error_log('VendorRequestController: Attempting to send email to ' . $vendor_email);
			error_log('VendorRequestController: Portal URL: ' . $portal_url);

			$email_sent = $this->notification_service->sendVendorRequest($vendor_request, $portal_url);

			if (!$email_sent) {
				error_log('VendorRequestController: Failed to send invitation email to ' . $vendor_email);
			} else {
				error_log('VendorRequestController: Email sent successfully to ' . $vendor_email);
			}
		} catch (\Exception $e) {
			error_log('VendorRequestController: Email error - ' . $e->getMessage());
			$email_sent = false;
		}

		// Log event
		$this->logEvent(
			'vendor_request',
			$vendor_request->id,
			'created',
			[],
			$data,
			null,
			$vendor_request->transaction_id
		);

		// No need to refresh - we already have the full vendor request object

		return $this->success([
			'vendor_request' => $vendor_request->toArray(),
			'portal_url' => $portal_url,
			'email_sent' => $email_sent ?? false,
		], 'Vendor request created successfully');
	}

	/**
	 * Get a single vendor request
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function get_vendor_request(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$vendor_request = $this->vendor_request_repository->find($id);

		if (!$vendor_request) {
			return $this->error('Vendor request not found', 404);
		}

		// Get portal URL if token exists
		$portal_url = null;
		if ($vendor_request->token) {
			$portal_url = home_url('/agent-dashboard/#/vendor-portal?token=' . $vendor_request->token);
		}

		return $this->success([
			'vendor_request' => $vendor_request->toArray(),
			'portal_url' => $portal_url,
		]);
	}

	/**
	 * Update a vendor request
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function update_vendor_request(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$vendor_request = $this->vendor_request_repository->find($id);

		if (!$vendor_request) {
			return $this->error('Vendor request not found', 404);
		}

		// Prepare update data
		$update_data = [];

		// Allowed fields to update
		$allowed_fields = [
			'vendor_email', 'vendor_phone', 'vendor_name', 'vendor_company',
			'status', 'scheduled_date', 'scheduled_time', 'completion_notes'
		];

		foreach ($allowed_fields as $field) {
			if ($request->has_param($field)) {
				$value = $request->get_param($field);

				if ($field === 'vendor_email') {
					$value = sanitize_email($value);
					if (!is_email($value)) {
						return $this->error('Invalid email address', 400);
					}
				} else {
					$value = sanitize_text_field($value);
				}

				$update_data[$field] = $value;
			}
		}

		if (empty($update_data)) {
			return $this->error('No valid fields to update', 400);
		}

		$update_data['updated_at'] = current_time('mysql');

		// Update vendor request
		$result = $this->vendor_request_repository->update($id, $update_data);

		if (!$result) {
			return $this->error('Failed to update vendor request', 500);
		}

		// Log event
		$this->logEvent(
			'vendor_request',
			$id,
			'updated',
			$vendor_request->toArray(),
			$update_data,
			null,
			$vendor_request->transaction_id
		);

		// Get updated vendor request
		$updated_vendor_request = $this->vendor_request_repository->find($id);

		return $this->success([
			'vendor_request' => $updated_vendor_request->toArray(),
		], 'Vendor request updated successfully');
	}

	/**
	 * Delete a vendor request
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function delete_vendor_request(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$vendor_request = $this->vendor_request_repository->find($id);

		if (!$vendor_request) {
			return $this->error('Vendor request not found', 404);
		}

		// Prevent deletion of completed requests
		if ($vendor_request->status === 'completed') {
			return $this->error('Cannot delete completed vendor requests', 403);
		}

		// Log event before deletion
		$this->logEvent(
			'vendor_request',
			$id,
			'deleted',
			$vendor_request->toArray(),
			[],
			null,
			$vendor_request->transaction_id
		);

		// Delete vendor request (CASCADE will delete related messages, availability, ratings)
		$result = $this->vendor_request_repository->delete($id);

		if (!$result) {
			return $this->error('Failed to delete vendor request', 500);
		}

		return $this->success(['deleted' => true], 'Vendor request deleted successfully');
	}

	/**
	 * Resend invitation email
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function resend_invitation(WP_REST_Request $request) {
		$id = (int) $request->get_param('id');
		$vendor_request = $this->vendor_request_repository->find($id);

		if (!$vendor_request) {
			return $this->error('Vendor request not found', 404);
		}

		// Only allow resending for sent or opened status
		if (!in_array($vendor_request->status, ['sent', 'opened'])) {
			return $this->error('Can only resend invitations for sent or opened requests', 400);
		}

		// Generate new token if expired or missing
		if (!$vendor_request->token || strtotime($vendor_request->token_expires_at) < time()) {
			$token = $this->vendor_service->generateSignedUrl($vendor_request->id, 30);
			$vendor_request = $this->vendor_request_repository->find($id); // Refresh
		}

		// Generate portal URL
		$portal_url = home_url('/agent-dashboard/#/vendor-portal?token=' . $vendor_request->token);

		// Send invitation email
		try {
			$email_sent = $this->notification_service->sendVendorRequest($vendor_request, $portal_url);

			if (!$email_sent) {
				return $this->error('Failed to send invitation email', 500);
			}
		} catch (\Exception $e) {
			error_log('VendorRequestController: Email error - ' . $e->getMessage());
			return $this->error('Error sending email: ' . $e->getMessage(), 500);
		}

		// Log event
		$this->logEvent(
			'vendor_request',
			$id,
			'invitation_resent',
			[],
			['resent_at' => current_time('mysql')],
			null,
			$vendor_request->transaction_id
		);

		return $this->success([
			'sent' => true,
			'portal_url' => $portal_url,
		], 'Invitation email sent successfully');
	}

	/**
	 * Submit vendor rating
	 *
	 * @param WP_REST_Request $request
	 * @return array|WP_Error
	 */
	public function rate_vendor(WP_REST_Request $request) {
		$vendor_request_id = (int) $request->get_param('id');
		$vendor_request = $this->vendor_request_repository->find($vendor_request_id);

		if (!$vendor_request) {
			return $this->error('Vendor request not found', 404);
		}

		// Only allow rating completed requests
		if ($vendor_request->status !== 'completed') {
			return $this->error('Can only rate completed vendor requests', 400);
		}

		// Check if already rated
		$existing_rating = $this->vendor_rating_repository->getByVendorRequest($vendor_request_id);
		if ($existing_rating) {
			return $this->error('This vendor request has already been rated', 400);
		}

		// Validate rating (1-5)
		$rating = (int) $request->get_param('rating');
		if ($rating < 1 || $rating > 5) {
			return $this->error('Rating must be between 1 and 5', 400);
		}

		// Prepare rating data
		$rating_data = [
			'vendor_request_id' => $vendor_request_id,
			'transaction_id' => $vendor_request->transaction_id,
			'vendor_email' => $vendor_request->vendor_email,
			'rated_by_user_id' => get_current_user_id(),
			'rating' => $rating,
			'timeliness_rating' => $request->get_param('timeliness_rating') ? (int) $request->get_param('timeliness_rating') : null,
			'quality_rating' => $request->get_param('quality_rating') ? (int) $request->get_param('quality_rating') : null,
			'communication_rating' => $request->get_param('communication_rating') ? (int) $request->get_param('communication_rating') : null,
			'review' => $request->get_param('review') ? sanitize_textarea_field($request->get_param('review')) : null,
			'would_recommend' => $request->get_param('would_recommend') ? (int) $request->get_param('would_recommend') : 1,
			'created_at' => current_time('mysql'),
		];

		// Create rating
		$rating_id = $this->vendor_rating_repository->create($rating_data);

		if (!$rating_id) {
			return $this->error('Failed to create vendor rating', 500);
		}

		// Update vendor request with average rating
		$this->vendor_rating_repository->updateVendorRequestRatings($vendor_request->vendor_email);

		// Log event
		$this->logEvent(
			'vendor_request',
			$vendor_request_id,
			'rated',
			[],
			$rating_data,
			null,
			$vendor_request->transaction_id
		);

		return $this->success([
			'rating_id' => $rating_id,
		], 'Vendor rated successfully');
	}

	/**
	 * Check if user has permission to manage vendor requests
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return current_user_can('manage_deal_room_vendor_requests') || current_user_can('administrator');
	}
}
