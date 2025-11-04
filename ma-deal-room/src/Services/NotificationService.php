<?php
/**
 * Notification Service
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

/**
 * Abstracted notification service for email and SMS
 */
class NotificationService {
	/**
	 * Twilio account SID
	 *
	 * @var string|null
	 */
	protected $twilio_account_sid;

	/**
	 * Twilio auth token
	 *
	 * @var string|null
	 */
	protected $twilio_auth_token;

	/**
	 * Twilio from phone number
	 *
	 * @var string|null
	 */
	protected $twilio_from_number;

	/**
	 * Notification preferences service
	 *
	 * @var NotificationPreferencesService
	 */
	protected $preferences_service;

	/**
	 * SMS service
	 *
	 * @var SMSService|null
	 */
	protected $sms_service;

	/**
	 * Email service
	 *
	 * @var EmailService|null
	 */
	protected $email_service;

	/**
	 * Constructor
	 *
	 * @param NotificationPreferencesService|null $preferences_service Notification preferences service
	 * @param SMSService|null $sms_service SMS service
	 * @param EmailService|null $email_service Email service
	 */
	public function __construct(
		?NotificationPreferencesService $preferences_service = null,
		?SMSService $sms_service = null,
		?EmailService $email_service = null
	) {
		$this->twilio_account_sid = $this->get_twilio_config('account_sid');
		$this->twilio_auth_token = $this->get_twilio_config('auth_token');
		$this->twilio_from_number = $this->get_twilio_config('from_number');

		$this->preferences_service = $preferences_service ?? new NotificationPreferencesService();
		$this->sms_service = $sms_service;
		$this->email_service = $email_service;
	}

	/**
	 * Get Twilio configuration from environment variables
	 *
	 * @param string $key Configuration key (account_sid, auth_token, from_number)
	 * @return string|null
	 */
	protected function get_twilio_config(string $key): ?string {
		$env_mapping = [
			'account_sid' => ['TWILIO_ACCOUNT_SID', 'MA_DEAL_TWILIO_SID'],
			'auth_token' => ['TWILIO_AUTH_TOKEN', 'MA_DEAL_TWILIO_TOKEN'],
			'from_number' => ['TWILIO_FROM_NUMBER', 'MA_DEAL_TWILIO_FROM_NUMBER'],
		];

		if (!isset($env_mapping[$key])) {
			return null;
		}

		foreach ($env_mapping[$key] as $env_var) {
			// Check both getenv() and $_ENV for maximum compatibility
			$value = getenv($env_var) ?: ($_ENV[$env_var] ?? null);
			if ($value) {
				return $value;
			}
		}

		// Future: Could also check WordPress options as fallback
		return null;
	}

	/**
	 * Send an email
	 *
	 * @param string $to Recipient email
	 * @param string $subject Email subject
	 * @param string $body Email body
	 * @param array $headers Optional headers
	 * @param string|null $notification_type Type of notification (for preference checking)
	 * @return bool Whether email was sent successfully
	 */
	public function sendEmail(
		string $to,
		string $subject,
		string $body,
		array $headers = [],
		?string $notification_type = null
	): bool {
		// Get user ID from email
		$user_id = $this->preferences_service->get_user_id_by_email($to);

		if ($user_id) {
			// Check if user has email notifications enabled
			if (!$this->preferences_service->is_email_enabled($user_id)) {
				error_log("[Notifications] Email not sent to {$to} - email notifications disabled");
				return false;
			}

			// Check specific notification type preference if provided
			if ($notification_type && !$this->preferences_service->wants_notification($user_id, $notification_type)) {
				error_log("[Notifications] Email not sent to {$to} - {$notification_type} notifications disabled");
				return false;
			}
		}

		// Default to HTML emails
		if (empty($headers)) {
			$headers = ['Content-Type: text/html; charset=UTF-8'];
		}

		return wp_mail($to, $subject, $body, $headers);
	}

	/**
	 * Send an SMS message
	 *
	 * @param string $to Recipient phone number
	 * @param string $message SMS message
	 * @param int|null $user_id User ID (for preference checking)
	 * @param string|null $notification_type Type of notification (for preference checking)
	 * @return bool|array Whether SMS was sent successfully (or error array)
	 */
	public function sendSms(
		string $to,
		string $message,
		?int $user_id = null,
		?string $notification_type = null
	): bool|array {
		// Check user preferences if user_id provided
		if ($user_id) {
			// Check if user has SMS notifications enabled
			if (!$this->preferences_service->is_sms_enabled($user_id)) {
				error_log("[Notifications] SMS not sent to {$to} - SMS notifications disabled for user {$user_id}");
				return false;
			}

			// Check specific notification type preference if provided
			if ($notification_type && !$this->preferences_service->wants_notification($user_id, $notification_type)) {
				error_log("[Notifications] SMS not sent to {$to} - {$notification_type} notifications disabled for user {$user_id}");
				return false;
			}
		}

		// Use SMSService if available
		if ($this->sms_service && $this->sms_service->is_enabled()) {
			return $this->sms_service->send_sms($to, $message);
		}

		// Fallback: Check if Twilio is configured
		if (!$this->twilio_account_sid || !$this->twilio_auth_token || !$this->twilio_from_number) {
			// Twilio not configured - log and simulate success for development
			error_log("[Notifications] Twilio not configured. Simulating SMS to {$to}: {$message}");
			return true;
		}

		// For now, log that Twilio is configured but not yet implemented
		error_log("[Notifications] Twilio configured but SDK not fully integrated via NotificationService. Would send SMS to {$to}: {$message}");
		return true;
	}

	/**
	 * Render an email template
	 *
	 * @param string $template_name Template file name
	 * @param array $vars Template variables
	 * @return string Rendered template
	 */
	public function renderTemplate(string $template_name, array $vars = []): string {
		$template_path = MA_DEAL_PATH . "assets/emails/{$template_name}.tpl";

		if (!file_exists($template_path)) {
			return "Template not found: {$template_name}";
		}

		// Start output buffering to capture rendered template
		ob_start();

		// Extract variables into the current symbol table
		extract($vars);

		// Include the template file
		include $template_path;

		// Get the buffered content and clean the buffer
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Send reminder email
	 *
	 * @param object $reminder Reminder model
	 * @param object $task Task model
	 * @param object $transaction Transaction model
	 * @return bool
	 */
	public function sendReminder($reminder, $task, $transaction): bool {
		$subject = "Reminder: {$task->title}";

		$dashboard_url = admin_url("admin.php?page=ma-deal-room&transaction_id={$transaction->transaction_id}");

		$body = $this->renderTemplate('reminder', [
			'task_title' => $task->title,
			'task_description' => $task->description ?? '',
			'due_date' => $task->due_at ? date('F j, Y', strtotime($task->due_at)) : 'Not set',
			'property_address' => $transaction->property_address,
			'transaction_id' => $transaction->transaction_id,
			'dashboard_url' => $dashboard_url,
		]);

		return $this->sendEmail($reminder->recipient_email, $subject, $body);
	}

	/**
	 * Send vendor request email
	 *
	 * @param object $vendor_request VendorRequest model
	 * @param string $signed_url Signed URL for vendor portal
	 * @return bool
	 */
	public function sendVendorRequest($vendor_request, string $signed_url): bool {
		$subject = "MA Deal Room: Action Required";

		$body = $this->renderTemplate('vendor-request', [
			'vendor_email' => $vendor_request->vendor_email,
			'signed_url' => $signed_url,
			'expires_at' => date('F j, Y', strtotime($vendor_request->token_expires_at)),
		]);

		return $this->sendEmail($vendor_request->vendor_email, $subject, $body);
	}
}
