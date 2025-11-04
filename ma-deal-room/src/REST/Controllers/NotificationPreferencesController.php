<?php
/**
 * Notification Preferences Controller
 *
 * REST API controller for managing notification preferences and unsubscribe requests
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use MADealRoom\Services\NotificationPreferencesService;

/**
 * REST API controller for notification preferences
 */
class NotificationPreferencesController extends BaseController {
	/**
	 * Notification preferences service
	 *
	 * @var NotificationPreferencesService
	 */
	protected $preferences_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->namespace = 'ma-deal-room/v1';
		$this->rest_base = 'notifications';
		$this->preferences_service = new NotificationPreferencesService();
	}

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Get user notification preferences
		register_rest_route($this->namespace, '/' . $this->rest_base . '/preferences', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'get_preferences'],
				'permission_callback' => [$this, 'check_user_permission'],
			],
		]);

		// Update user notification preferences
		register_rest_route($this->namespace, '/' . $this->rest_base . '/preferences', [
			[
				'methods' => 'POST',
				'callback' => [$this, 'update_preferences'],
				'permission_callback' => [$this, 'check_user_permission'],
			],
		]);

		// Unsubscribe from notifications (public endpoint with token)
		register_rest_route($this->namespace, '/' . $this->rest_base . '/unsubscribe', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'unsubscribe'],
				'permission_callback' => '__return_true', // Public endpoint, uses token validation
			],
		]);

		// Re-subscribe to notifications
		register_rest_route($this->namespace, '/' . $this->rest_base . '/resubscribe', [
			[
				'methods' => 'POST',
				'callback' => [$this, 'resubscribe'],
				'permission_callback' => [$this, 'check_user_permission'],
			],
		]);
	}

	/**
	 * Get user notification preferences
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_preferences(WP_REST_Request $request): WP_REST_Response {
		$user_id = get_current_user_id();

		if (!$user_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User not authenticated',
			], 401);
		}

		$preferences = $this->preferences_service->get_preferences($user_id);

		return new WP_REST_Response([
			'success' => true,
			'preferences' => $preferences,
		], 200);
	}

	/**
	 * Update user notification preferences
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function update_preferences(WP_REST_Request $request): WP_REST_Response {
		$user_id = get_current_user_id();

		if (!$user_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User not authenticated',
			], 401);
		}

		$params = $request->get_json_params();

		if (empty($params)) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'No preferences provided',
			], 400);
		}

		$result = $this->preferences_service->update_preferences($user_id, $params);

		if ($result) {
			$updated_preferences = $this->preferences_service->get_preferences($user_id);

			return new WP_REST_Response([
				'success' => true,
				'message' => 'Preferences updated successfully',
				'preferences' => $updated_preferences,
			], 200);
		} else {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Failed to update preferences',
			], 500);
		}
	}

	/**
	 * Handle unsubscribe request
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function unsubscribe(WP_REST_Request $request): WP_REST_Response {
		$user_id = $request->get_param('user_id');
		$token = $request->get_param('token');
		$type = $request->get_param('type') ?? 'email';

		// Validate required parameters
		if (!$user_id || !$token) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Missing required parameters: user_id and token',
			], 400);
		}

		// Process unsubscribe
		$result = $this->preferences_service->process_unsubscribe((int) $user_id, $token, $type);

		if ($result['success']) {
			return new WP_REST_Response($result, 200);
		} else {
			return new WP_REST_Response($result, 400);
		}
	}

	/**
	 * Handle re-subscribe request
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function resubscribe(WP_REST_Request $request): WP_REST_Response {
		$user_id = get_current_user_id();

		if (!$user_id) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'User not authenticated',
			], 401);
		}

		$params = $request->get_json_params();
		$type = $params['type'] ?? 'email';

		$result = false;
		$message = '';

		switch ($type) {
			case 'email':
				$result = $this->preferences_service->enable_email($user_id);
				$message = 'Email notifications enabled successfully';
				break;

			case 'sms':
				$result = $this->preferences_service->enable_sms($user_id);
				$message = 'SMS notifications enabled successfully';
				break;

			case 'all':
				$email_result = $this->preferences_service->enable_email($user_id);
				$sms_result = $this->preferences_service->enable_sms($user_id);
				$result = $email_result && $sms_result;
				$message = 'All notifications enabled successfully';
				break;

			default:
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Invalid notification type',
				], 400);
		}

		if ($result) {
			return new WP_REST_Response([
				'success' => true,
				'message' => $message,
			], 200);
		} else {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Failed to enable notifications',
			], 500);
		}
	}

	/**
	 * Check if user has permission to access preferences
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool
	 */
	public function check_user_permission(WP_REST_Request $request): bool {
		// User must be logged in
		return is_user_logged_in();
	}
}
