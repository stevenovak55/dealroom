<?php
/**
 * SMS Service
 *
 * Handles SMS notifications via Twilio
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

/**
 * Service for sending SMS notifications via Twilio
 */
class SMSService {
	/**
	 * Twilio Account SID
	 *
	 * @var string|null
	 */
	protected $account_sid;

	/**
	 * Twilio Auth Token
	 *
	 * @var string|null
	 */
	protected $auth_token;

	/**
	 * Twilio From Number
	 *
	 * @var string|null
	 */
	protected $from_number;

	/**
	 * Twilio Client Instance
	 *
	 * @var Client|null
	 */
	protected $twilio_client;

	/**
	 * Whether Twilio is configured and enabled
	 *
	 * @var bool
	 */
	protected $is_enabled = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->account_sid = $this->get_account_sid();
		$this->auth_token = $this->get_auth_token();
		$this->from_number = $this->get_from_number();

		// Initialize Twilio client if credentials are available
		if ($this->account_sid && $this->auth_token && $this->from_number) {
			try {
				$this->twilio_client = new Client($this->account_sid, $this->auth_token);
				$this->is_enabled = true;
				error_log('[Twilio] SMS service initialized successfully');
			} catch (\Exception $e) {
				error_log('[Twilio] Failed to initialize: ' . $e->getMessage());
				$this->is_enabled = false;
			}
		} else {
			error_log('[Twilio] SMS service not configured - missing credentials');
		}
	}

	/**
	 * Get Twilio Account SID from environment
	 *
	 * @return string|null
	 */
	protected function get_account_sid(): ?string {
		$env_vars = [
			'TWILIO_ACCOUNT_SID',
			'MA_DEAL_TWILIO_SID',
		];

		foreach ($env_vars as $env_var) {
			$value = getenv($env_var) ?: ($_ENV[$env_var] ?? null);
			if ($value) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Get Twilio Auth Token from environment
	 *
	 * @return string|null
	 */
	protected function get_auth_token(): ?string {
		$env_vars = [
			'TWILIO_AUTH_TOKEN',
			'MA_DEAL_TWILIO_TOKEN',
		];

		foreach ($env_vars as $env_var) {
			$value = getenv($env_var) ?: ($_ENV[$env_var] ?? null);
			if ($value) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Get Twilio From Number from environment
	 *
	 * @return string|null
	 */
	protected function get_from_number(): ?string {
		$env_vars = [
			'TWILIO_FROM_NUMBER',
			'MA_DEAL_TWILIO_FROM_NUMBER',
		];

		foreach ($env_vars as $env_var) {
			$value = getenv($env_var) ?: ($_ENV[$env_var] ?? null);
			if ($value) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Check if SMS service is enabled and configured
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return $this->is_enabled;
	}

	/**
	 * Send SMS message
	 *
	 * @param string $to Recipient phone number (E.164 format: +1234567890)
	 * @param string $message Message body (max 160 chars for single SMS)
	 * @return bool|array Returns true on success, or error array on failure
	 */
	public function send_sms(string $to, string $message): bool|array {
		// Check if service is enabled
		if (!$this->is_enabled) {
			error_log('[Twilio] Cannot send SMS - service not configured');
			return [
				'success' => false,
				'error' => 'SMS service not configured',
			];
		}

		// Validate phone number format
		if (!$this->validate_phone_number($to)) {
			error_log('[Twilio] Invalid phone number format: ' . $to);
			return [
				'success' => false,
				'error' => 'Invalid phone number format. Use E.164 format (+1234567890)',
			];
		}

		// Truncate message if too long (160 chars for single SMS)
		if (strlen($message) > 160) {
			$message = substr($message, 0, 157) . '...';
		}

		try {
			$result = $this->twilio_client->messages->create(
				$to,
				[
					'from' => $this->from_number,
					'body' => $message,
				]
			);

			// Log successful send
			$this->log_sms($to, $message, 'sent', $result->sid);

			error_log(sprintf('[Twilio] SMS sent to %s (SID: %s)', $to, $result->sid));

			return true;
		} catch (TwilioException $e) {
			// Log failed send
			$this->log_sms($to, $message, 'failed', null, $e->getMessage());

			error_log(sprintf('[Twilio] Failed to send SMS to %s: %s', $to, $e->getMessage()));

			return [
				'success' => false,
				'error' => $e->getMessage(),
				'code' => $e->getCode(),
			];
		}
	}

	/**
	 * Send task reminder SMS
	 *
	 * @param string $to Recipient phone number
	 * @param string $task_title Task title
	 * @param string $due_date Due date
	 * @return bool|array
	 */
	public function send_task_reminder(string $to, string $task_title, string $due_date): bool|array {
		$message = $this->render_template('task_reminder', [
			'task_title' => $task_title,
			'due_date' => $due_date,
		]);

		return $this->send_sms($to, $message);
	}

	/**
	 * Send task assignment SMS
	 *
	 * @param string $to Recipient phone number
	 * @param string $task_title Task title
	 * @return bool|array
	 */
	public function send_task_assignment(string $to, string $task_title): bool|array {
		$message = $this->render_template('task_assignment', [
			'task_title' => $task_title,
		]);

		return $this->send_sms($to, $message);
	}

	/**
	 * Send transaction status update SMS
	 *
	 * @param string $to Recipient phone number
	 * @param string $transaction_address Transaction address
	 * @param string $status New status
	 * @return bool|array
	 */
	public function send_transaction_update(string $to, string $transaction_address, string $status): bool|array {
		$message = $this->render_template('transaction_update', [
			'transaction_address' => $transaction_address,
			'status' => $status,
		]);

		return $this->send_sms($to, $message);
	}

	/**
	 * Render SMS template
	 *
	 * @param string $template Template name
	 * @param array $variables Template variables
	 * @return string Rendered message
	 */
	protected function render_template(string $template, array $variables): string {
		$templates = [
			'task_reminder' => 'MA Deal Room: {{task_title}} is due {{due_date}}. Please complete ASAP.',
			'task_assignment' => 'MA Deal Room: You\'ve been assigned "{{task_title}}". Login to view details.',
			'transaction_update' => 'MA Deal Room: {{transaction_address}} status updated to {{status}}.',
			'verification_code' => 'Your MA Deal Room verification code is: {{code}}',
			'password_reset' => 'MA Deal Room: Reset password using code {{code}}. Expires in {{expiry}} minutes.',
		];

		$message = $templates[$template] ?? '{{message}}';

		// Replace variables
		foreach ($variables as $key => $value) {
			$message = str_replace('{{' . $key . '}}', $value, $message);
		}

		return $message;
	}

	/**
	 * Validate phone number format (E.164)
	 *
	 * @param string $phone Phone number
	 * @return bool
	 */
	protected function validate_phone_number(string $phone): bool {
		// E.164 format: +[country code][number]
		// Example: +12025551234
		return preg_match('/^\+[1-9]\d{1,14}$/', $phone) === 1;
	}

	/**
	 * Log SMS delivery attempt
	 *
	 * @param string $to Recipient phone number
	 * @param string $message Message body
	 * @param string $status Status (sent, failed, delivered, undelivered)
	 * @param string|null $sid Twilio message SID
	 * @param string|null $error_message Error message if failed
	 * @return void
	 */
	protected function log_sms(
		string $to,
		string $message,
		string $status,
		?string $sid = null,
		?string $error_message = null
	): void {
		global $wpdb;

		// Log to WordPress error log
		$log_entry = sprintf(
			'[SMS Log] To: %s | Message: %s | Status: %s | SID: %s%s',
			$this->mask_phone_number($to),
			substr($message, 0, 50),
			$status,
			$sid ?? 'N/A',
			$error_message ? ' | Error: ' . $error_message : ''
		);

		error_log($log_entry);

		// TODO: Store in database table for tracking and analytics
		// $wpdb->insert($wpdb->prefix . 'ma_deal_sms_logs', [
		//     'recipient' => $to,
		//     'message' => $message,
		//     'status' => $status,
		//     'twilio_sid' => $sid,
		//     'error_message' => $error_message,
		//     'sent_at' => current_time('mysql'),
		// ]);
	}

	/**
	 * Mask phone number for privacy in logs
	 *
	 * @param string $phone Phone number
	 * @return string Masked phone number
	 */
	protected function mask_phone_number(string $phone): string {
		if (strlen($phone) < 8) {
			return '***' . substr($phone, -2);
		}
		return substr($phone, 0, 4) . '****' . substr($phone, -2);
	}

	/**
	 * Handle Twilio webhook delivery status update
	 *
	 * @param array $data Webhook data from Twilio
	 * @return bool
	 */
	public function handle_delivery_status(array $data): bool {
		$message_sid = $data['MessageSid'] ?? null;
		$message_status = $data['MessageStatus'] ?? null;
		$error_code = $data['ErrorCode'] ?? null;

		if (!$message_sid || !$message_status) {
			error_log('[Twilio Webhook] Invalid webhook data received');
			return false;
		}

		// Log delivery status update
		error_log(sprintf(
			'[Twilio Webhook] Message %s status: %s%s',
			$message_sid,
			$message_status,
			$error_code ? ' (Error: ' . $error_code . ')' : ''
		));

		// TODO: Update database record with delivery status
		// global $wpdb;
		// $wpdb->update(
		//     $wpdb->prefix . 'ma_deal_sms_logs',
		//     ['status' => $message_status, 'error_code' => $error_code],
		//     ['twilio_sid' => $message_sid]
		// );

		return true;
	}

	/**
	 * Check if a phone number has opted out
	 *
	 * @param string $phone Phone number
	 * @return bool
	 */
	public function is_opted_out(string $phone): bool {
		global $wpdb;

		// TODO: Check database for opt-out status
		// $result = $wpdb->get_var($wpdb->prepare(
		//     "SELECT COUNT(*) FROM {$wpdb->prefix}ma_deal_sms_opt_outs WHERE phone = %s",
		//     $phone
		// ));
		// return $result > 0;

		return false;
	}

	/**
	 * Add phone number to opt-out list
	 *
	 * @param string $phone Phone number
	 * @return bool
	 */
	public function opt_out(string $phone): bool {
		global $wpdb;

		error_log('[Twilio] Phone number opted out: ' . $this->mask_phone_number($phone));

		// TODO: Add to database opt-out list
		// $wpdb->insert($wpdb->prefix . 'ma_deal_sms_opt_outs', [
		//     'phone' => $phone,
		//     'opted_out_at' => current_time('mysql'),
		// ]);

		return true;
	}

	/**
	 * Remove phone number from opt-out list
	 *
	 * @param string $phone Phone number
	 * @return bool
	 */
	public function opt_in(string $phone): bool {
		global $wpdb;

		error_log('[Twilio] Phone number opted in: ' . $this->mask_phone_number($phone));

		// TODO: Remove from database opt-out list
		// $wpdb->delete($wpdb->prefix . 'ma_deal_sms_opt_outs', ['phone' => $phone]);

		return true;
	}

	/**
	 * Get SMS delivery statistics
	 *
	 * @param string|null $start_date Start date (Y-m-d format)
	 * @param string|null $end_date End date (Y-m-d format)
	 * @return array Statistics array
	 */
	public function get_statistics(?string $start_date = null, ?string $end_date = null): array {
		// TODO: Query database for statistics
		return [
			'total_sent' => 0,
			'delivered' => 0,
			'failed' => 0,
			'undelivered' => 0,
			'opted_out' => 0,
		];
	}
}
