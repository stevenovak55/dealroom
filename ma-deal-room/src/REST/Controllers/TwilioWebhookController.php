<?php
/**
 * Twilio Webhook Controller
 *
 * Handles incoming webhooks from Twilio for SMS delivery status updates
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use MADealRoom\Services\SMSService;

/**
 * REST API controller for Twilio webhooks
 */
class TwilioWebhookController extends BaseController {
	/**
	 * SMS Service instance
	 *
	 * @var SMSService
	 */
	protected $sms_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->namespace = 'ma-deal-room/v1';
		$this->rest_base = 'twilio';
		$this->sms_service = new SMSService();
	}

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Webhook for SMS delivery status updates
		register_rest_route($this->namespace, '/' . $this->rest_base . '/webhook/status', [
			[
				'methods' => 'POST',
				'callback' => [$this, 'handle_status_webhook'],
				'permission_callback' => [$this, 'validate_twilio_signature'],
			],
		]);

		// Webhook for incoming SMS (for STOP/START keywords)
		register_rest_route($this->namespace, '/' . $this->rest_base . '/webhook/incoming', [
			[
				'methods' => 'POST',
				'callback' => [$this, 'handle_incoming_sms'],
				'permission_callback' => [$this, 'validate_twilio_signature'],
			],
		]);

		// Test endpoint to verify webhook URL is accessible
		register_rest_route($this->namespace, '/' . $this->rest_base . '/webhook/test', [
			[
				'methods' => 'GET',
				'callback' => [$this, 'test_webhook'],
				'permission_callback' => '__return_true',
			],
		]);
	}

	/**
	 * Handle SMS delivery status webhook
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function handle_status_webhook(WP_REST_Request $request): WP_REST_Response {
		$params = $request->get_params();

		// Extract Twilio webhook data
		$data = [
			'MessageSid' => $params['MessageSid'] ?? null,
			'MessageStatus' => $params['MessageStatus'] ?? null,
			'To' => $params['To'] ?? null,
			'From' => $params['From'] ?? null,
			'ErrorCode' => $params['ErrorCode'] ?? null,
			'ErrorMessage' => $params['ErrorMessage'] ?? null,
		];

		// Log webhook receipt
		error_log('[Twilio Webhook] Delivery status received: ' . json_encode($data));

		// Process delivery status
		$result = $this->sms_service->handle_delivery_status($data);

		if ($result) {
			return new WP_REST_Response([
				'success' => true,
				'message' => 'Delivery status processed successfully',
			], 200);
		} else {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Failed to process delivery status',
			], 400);
		}
	}

	/**
	 * Handle incoming SMS webhook (for opt-out keywords)
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function handle_incoming_sms(WP_REST_Request $request): WP_REST_Response {
		$params = $request->get_params();

		$from = $params['From'] ?? null;
		$body = $params['Body'] ?? '';
		$message_sid = $params['MessageSid'] ?? null;

		// Log incoming SMS
		error_log(sprintf(
			'[Twilio Webhook] Incoming SMS from %s: "%s" (SID: %s)',
			$from,
			$body,
			$message_sid
		));

		// Check for opt-out keywords (STOP, STOPALL, UNSUBSCRIBE, CANCEL, END, QUIT)
		$opt_out_keywords = ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'];
		$body_upper = strtoupper(trim($body));

		if (in_array($body_upper, $opt_out_keywords, true)) {
			// Add to opt-out list
			$this->sms_service->opt_out($from);

			// Respond with confirmation (Twilio will send this back to user)
			$response_body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$response_body .= '<Response>' . "\n";
			$response_body .= '  <Message>You have been unsubscribed from MA Deal Room SMS notifications. Reply START to re-subscribe.</Message>' . "\n";
			$response_body .= '</Response>';

			return new WP_REST_Response($response_body, 200, [
				'Content-Type' => 'application/xml',
			]);
		}

		// Check for opt-in keywords (START, YES, UNSTOP)
		$opt_in_keywords = ['START', 'YES', 'UNSTOP'];

		if (in_array($body_upper, $opt_in_keywords, true)) {
			// Remove from opt-out list
			$this->sms_service->opt_in($from);

			// Respond with confirmation
			$response_body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
			$response_body .= '<Response>' . "\n";
			$response_body .= '  <Message>Welcome back! You have been re-subscribed to MA Deal Room SMS notifications. Reply STOP to unsubscribe.</Message>' . "\n";
			$response_body .= '</Response>';

			return new WP_REST_Response($response_body, 200, [
				'Content-Type' => 'application/xml',
			]);
		}

		// For other messages, just acknowledge receipt
		return new WP_REST_Response([
			'success' => true,
			'message' => 'SMS received',
		], 200);
	}

	/**
	 * Test webhook endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function test_webhook(WP_REST_Request $request): WP_REST_Response {
		return new WP_REST_Response([
			'success' => true,
			'message' => 'Twilio webhook endpoint is accessible',
			'timestamp' => current_time('mysql'),
			'webhook_url' => rest_url($this->namespace . '/' . $this->rest_base . '/webhook'),
		], 200);
	}

	/**
	 * Validate Twilio request signature
	 *
	 * Security: Ensures webhook requests actually come from Twilio
	 *
	 * @param WP_REST_Request $request Request object
	 * @return bool
	 */
	public function validate_twilio_signature(WP_REST_Request $request): bool {
		// Get Twilio auth token
		$auth_token = getenv('TWILIO_AUTH_TOKEN') ?: ($_ENV['TWILIO_AUTH_TOKEN'] ?? null);

		// If auth token not configured, log warning and allow (for testing)
		if (!$auth_token) {
			error_log('[Twilio Webhook] WARNING: Auth token not configured - signature validation skipped');
			return true; // Allow for testing, but log warning
		}

		// Get signature from request header
		$twilio_signature = $request->get_header('X-Twilio-Signature');

		if (!$twilio_signature) {
			error_log('[Twilio Webhook] ERROR: Missing X-Twilio-Signature header');
			return false;
		}

		// Get full URL (Twilio signs the complete URL)
		$url = rest_url($request->get_route());

		// Get POST parameters
		$params = $request->get_params();

		// Build signature data string
		$data = $url;
		ksort($params);
		foreach ($params as $key => $value) {
			$data .= $key . $value;
		}

		// Compute expected signature
		$expected_signature = base64_encode(hash_hmac('sha1', $data, $auth_token, true));

		// Compare signatures
		if (hash_equals($expected_signature, $twilio_signature)) {
			return true;
		}

		error_log('[Twilio Webhook] ERROR: Invalid signature - possible spoofed request');
		return false;
	}
}
