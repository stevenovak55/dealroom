<?php
/**
 * Vendor Portal REST Controller
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use MADealRoom\Services\VendorService;
use MADealRoom\Repositories\EventRepository;
use MADealRoom\Repositories\VendorMessageRepository;
use WP_REST_Request;
use WP_Error;

class VendorPortalController extends BaseController {
	protected $rest_base = 'vendor';
	private $vendor_service;
	private $vendor_message_repository;

	public function __construct(
		VendorService $vendor_service,
		VendorMessageRepository $vendor_message_repository,
		EventRepository $event_repository
	) {
		parent::__construct($event_repository);
		$this->vendor_service = $vendor_service;
		$this->vendor_message_repository = $vendor_message_repository;
	}

	public function register_routes(): void {
		// Public endpoints - no authentication required (token-based access)

		// Main portal endpoint
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})', [
			['methods' => 'GET', 'callback' => [$this, 'get_vendor_portal'], 'permission_callback' => '__return_true'],
			['methods' => 'POST', 'callback' => [$this, 'update_vendor_request'], 'permission_callback' => '__return_true'],
		]);

		// Schedule appointment
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})/schedule', [
			'methods' => 'POST',
			'callback' => [$this, 'schedule_appointment'],
			'permission_callback' => '__return_true',
		]);

		// Submit availability
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})/availability', [
			'methods' => 'POST',
			'callback' => [$this, 'submit_availability'],
			'permission_callback' => '__return_true',
		]);

		// Upload document
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})/upload', [
			'methods' => 'POST',
			'callback' => [$this, 'upload_document'],
			'permission_callback' => '__return_true',
		]);

		// Complete request
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})/complete', [
			'methods' => 'POST',
			'callback' => [$this, 'complete_request'],
			'permission_callback' => '__return_true',
		]);

		// Send message
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})/message', [
			'methods' => 'POST',
			'callback' => [$this, 'send_message'],
			'permission_callback' => '__return_true',
		]);

		// Get messages
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})/messages', [
			'methods' => 'GET',
			'callback' => [$this, 'get_messages'],
			'permission_callback' => '__return_true',
		]);

		// Mark messages as read
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})/messages/read', [
			'methods' => 'POST',
			'callback' => [$this, 'mark_messages_read'],
			'permission_callback' => '__return_true',
		]);

		// Download ICS calendar file
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})/calendar', [
			'methods' => 'GET',
			'callback' => [$this, 'download_calendar'],
			'permission_callback' => '__return_true',
		]);
	}

	public function get_vendor_portal(WP_REST_Request $request) {
		// SECURITY: Apply strict rate limiting for public vendor portal
		// Prevents brute force attacks on 64-character tokens
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$portal_data = $this->vendor_service->getVendorPortalData($token);

		if (!$portal_data) {
			return $this->error('Invalid or expired token', 404);
		}

		return $this->success($portal_data);
	}

	public function update_vendor_request(WP_REST_Request $request) {
		// SECURITY: Apply strict rate limiting for public vendor portal
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		// Generic update - log the event
		$this->logEvent(
			'vendor_request',
			$vendor_request->id,
			'updated',
			$vendor_request->toArray(),
			$request->get_params(),
			null,
			$vendor_request->transaction_id
		);

		return $this->success(null, 'Vendor request updated successfully');
	}

	public function schedule_appointment(WP_REST_Request $request) {
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		// Validate required fields
		$scheduled_date = $request->get_param('scheduled_date');
		if (empty($scheduled_date)) {
			return $this->error('scheduled_date is required', 400);
		}

		$schedule_data = [
			'scheduled_date' => sanitize_text_field($scheduled_date),
			'scheduled_time' => $request->get_param('scheduled_time') ? sanitize_text_field($request->get_param('scheduled_time')) : null,
			'vendor_name' => $request->get_param('vendor_name') ? sanitize_text_field($request->get_param('vendor_name')) : null,
			'vendor_company' => $request->get_param('vendor_company') ? sanitize_text_field($request->get_param('vendor_company')) : null,
		];

		$result = $this->vendor_service->scheduleAppointment($vendor_request->id, $schedule_data);

		if (!$result) {
			return $this->error('Failed to schedule appointment', 500);
		}

		// Log event
		$this->logEvent(
			'vendor_request',
			$vendor_request->id,
			'scheduled',
			[],
			$schedule_data,
			null,
			$vendor_request->transaction_id
		);

		return $this->success(['scheduled' => true], 'Appointment scheduled successfully');
	}

	public function submit_availability(WP_REST_Request $request) {
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		$availability_windows = $request->get_param('availability');
		if (!is_array($availability_windows) || empty($availability_windows)) {
			return $this->error('availability array is required', 400);
		}

		// Validate and sanitize availability windows
		$sanitized_windows = [];
		foreach ($availability_windows as $window) {
			if (!isset($window['available_date']) || !isset($window['start_time']) || !isset($window['end_time'])) {
				return $this->error('Each availability window must have available_date, start_time, and end_time', 400);
			}

			$sanitized_windows[] = [
				'available_date' => sanitize_text_field($window['available_date']),
				'start_time' => sanitize_text_field($window['start_time']),
				'end_time' => sanitize_text_field($window['end_time']),
				'timezone' => isset($window['timezone']) ? sanitize_text_field($window['timezone']) : 'America/New_York',
				'notes' => isset($window['notes']) ? sanitize_textarea_field($window['notes']) : null,
			];
		}

		$result = $this->vendor_service->submitAvailability($vendor_request->id, $sanitized_windows);

		if (!$result) {
			return $this->error('Failed to submit availability', 500);
		}

		// Log event
		$this->logEvent(
			'vendor_request',
			$vendor_request->id,
			'availability_submitted',
			[],
			['windows_count' => count($sanitized_windows)],
			null,
			$vendor_request->transaction_id
		);

		return $this->success(['submitted' => true], 'Availability submitted successfully');
	}

	public function upload_document(WP_REST_Request $request) {
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal-upload');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		// Check if file was uploaded
		$files = $request->get_file_params();
		if (empty($files['file'])) {
			return $this->error('No file uploaded', 400);
		}

		$file = $files['file'];

		// Validate file type (allow common document types)
		$allowed_types = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
		if (!in_array($file['type'], $allowed_types)) {
			return $this->error('Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX', 400);
		}

		// Upload document
		$document_url = $this->vendor_service->uploadDocument($vendor_request->id, $file);

		if (!$document_url) {
			return $this->error('Failed to upload document', 500);
		}

		// Log event
		$this->logEvent(
			'vendor_request',
			$vendor_request->id,
			'document_uploaded',
			[],
			['document_url' => $document_url],
			null,
			$vendor_request->transaction_id
		);

		return $this->success(['document_url' => $document_url], 'Document uploaded successfully');
	}

	public function complete_request(WP_REST_Request $request) {
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		$completion_data = [
			'completion_date' => $request->get_param('completion_date') ? sanitize_text_field($request->get_param('completion_date')) : date('Y-m-d'),
			'completion_notes' => $request->get_param('completion_notes') ? sanitize_textarea_field($request->get_param('completion_notes')) : null,
		];

		$result = $this->vendor_service->completeRequest($vendor_request->id, $completion_data);

		if (!$result) {
			return $this->error('Failed to complete request', 500);
		}

		// Log event
		$this->logEvent(
			'vendor_request',
			$vendor_request->id,
			'completed',
			[],
			$completion_data,
			null,
			$vendor_request->transaction_id
		);

		return $this->success(['completed' => true], 'Request marked as completed');
	}

	public function send_message(WP_REST_Request $request) {
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal-message');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		// Validate required fields
		$message = $request->get_param('message');
		$sender_name = $request->get_param('sender_name');

		if (empty($message) || empty($sender_name)) {
			return $this->error('message and sender_name are required', 400);
		}

		$message_id = $this->vendor_service->sendMessage(
			$vendor_request->id,
			'vendor', // Always from vendor on this endpoint
			sanitize_text_field($sender_name),
			$vendor_request->vendor_email,
			sanitize_textarea_field($message)
		);

		if (!$message_id) {
			return $this->error('Failed to send message', 500);
		}

		return $this->success(['message_id' => $message_id], 'Message sent successfully');
	}

	public function get_messages(WP_REST_Request $request) {
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$portal_data = $this->vendor_service->getVendorPortalData($token);

		if (!$portal_data) {
			return $this->error('Invalid or expired token', 404);
		}

		return $this->success($portal_data['messages']);
	}

	public function mark_messages_read(WP_REST_Request $request) {
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		$result = $this->vendor_message_repository->markAllAsRead($vendor_request->id, 'vendor');

		return $this->success(['marked_read' => $result], 'Messages marked as read');
	}

	public function download_calendar(WP_REST_Request $request) {
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		// Check if appointment is scheduled
		if (!$vendor_request->scheduled_date) {
			return $this->error('No appointment scheduled', 400);
		}

		// Generate ICS file
		$event_data = [
			'uid' => 'vendor-' . $vendor_request->id,
			'summary' => $vendor_request->vendor_type . ' - ' . ($vendor_request->vendor_company ?? 'Vendor Request'),
			'start' => $vendor_request->scheduled_date . ' ' . ($vendor_request->scheduled_time ?? '09:00:00'),
			'description' => 'Vendor request for ' . $vendor_request->vendor_type,
			'location' => '', // Could add property address if needed
		];

		$ics_content = $this->vendor_service->generateICS($event_data);

		// Set headers for ICS download
		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="appointment.ics"');

		echo $ics_content;
		exit;
	}
}
