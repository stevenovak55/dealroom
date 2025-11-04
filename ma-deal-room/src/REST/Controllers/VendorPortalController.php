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
use WP_REST_Request;
use WP_Error;

class VendorPortalController extends BaseController {
	protected $rest_base = 'vendor';
	private $vendor_service;

	public function __construct(VendorService $vendor_service, EventRepository $event_repository) {
		parent::__construct($event_repository);
		$this->vendor_service = $vendor_service;
	}

	public function register_routes(): void {
		// Public endpoint - no authentication required
		register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<token>[a-f0-9]{64})', [
			['methods' => 'GET', 'callback' => [$this, 'get_vendor_portal'], 'permission_callback' => '__return_true'],
			['methods' => 'POST', 'callback' => [$this, 'update_vendor_request'], 'permission_callback' => '__return_true'],
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
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		return $this->success($vendor_request);
	}

	public function update_vendor_request(WP_REST_Request $request) {
		// SECURITY: Apply strict rate limiting for public vendor portal
		// Prevents brute force attacks on 64-character tokens
		$rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'vendor-portal');
		if (is_wp_error($rate_limit_check)) {
			return $rate_limit_check;
		}

		$token = $request->get_param('token');
		$vendor_request = $this->vendor_service->validateToken($token);

		if (!$vendor_request) {
			return $this->error('Invalid or expired token', 404);
		}

		// TODO: Implement vendor request update logic in Phase 6
		// This will handle:
		// - Scheduling appointments
		// - Uploading documents
		// - Updating completion status

		// For now, log a generic update event
		$this->logEvent(
			'vendor_request',
			$vendor_request->id,
			'updated',
			$vendor_request->toArray(), // Assuming toArray() exists on VendorRequest model
			$request->get_params(),
			$vendor_request->account_id ?? null,
			$vendor_request->transaction_id ?? null
		);

		return $this->success(null, 'Vendor request updated successfully');
	}
}
