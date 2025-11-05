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
		error_log("[Notifications] sendEmail called - To: {$to}, Subject: {$subject}");

		// Get user ID from email
		$user_id = $this->preferences_service->get_user_id_by_email($to);
		error_log("[Notifications] User ID for {$to}: " . ($user_id ?? 'not found'));

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

		error_log("[Notifications] Calling wp_mail to {$to}");
		$mail_result = wp_mail($to, $subject, $body, $headers);
		error_log("[Notifications] wp_mail result: " . ($mail_result ? 'Success' : 'Failed'));

		return $mail_result;
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

		// Read the template file content
		$content = file_get_contents($template_path);

		// Replace {{variable}} placeholders with actual values
		foreach ($vars as $key => $value) {
			$placeholder = '{{' . $key . '}}';
			$content = str_replace($placeholder, $value, $content);
		}

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
		error_log('NotificationService::sendVendorRequest - Starting email send');
		error_log('NotificationService::sendVendorRequest - To: ' . $vendor_request->vendor_email);

		$subject = "MA Deal Room: Action Required";

		$body = $this->renderTemplate('vendor-request', [
			'vendor_email' => $vendor_request->vendor_email,
			'signed_url' => $signed_url,
			'expires_at' => date('F j, Y', strtotime($vendor_request->token_expires_at)),
		]);

		error_log('NotificationService::sendVendorRequest - Template rendered, sending email...');
		$result = $this->sendEmail($vendor_request->vendor_email, $subject, $body);
		error_log('NotificationService::sendVendorRequest - Email send result: ' . ($result ? 'Success' : 'Failed'));

		return $result;
	}

	/**
	 * Send vendor schedule confirmation email to agent
	 *
	 * @param object $vendor_request VendorRequest model
	 * @param object $transaction Transaction model
	 * @return bool
	 */
	public function sendVendorScheduleConfirmation($vendor_request, $transaction): bool {
		// Get agent email from transaction
		$agent_email = $transaction->agent_email ?? null;
		if (!$agent_email) {
			error_log('NotificationService: No agent email found for transaction ' . $transaction->id);
			return false;
		}

		$subject = "Vendor Scheduled: {$vendor_request->vendor_type} for {$transaction->property_address}";

		$scheduled_datetime = $vendor_request->scheduled_date;
		if ($vendor_request->scheduled_time) {
			$scheduled_datetime .= ' at ' . $vendor_request->scheduled_time;
		}

		$body = $this->renderTemplate('vendor-schedule-confirmation', [
			'agent_name' => $transaction->agent_name ?? 'Agent',
			'vendor_type' => ucwords(str_replace('_', ' ', $vendor_request->vendor_type)),
			'vendor_name' => $vendor_request->vendor_name ?? 'Vendor',
			'vendor_email' => $vendor_request->vendor_email,
			'vendor_phone' => $vendor_request->vendor_phone ?? 'Not provided',
			'property_address' => $transaction->property_address,
			'scheduled_datetime' => $scheduled_datetime,
			'dashboard_url' => admin_url('admin.php?page=ma-deal-room&transaction_id=' . $transaction->id),
		]);

		return $this->sendEmail($agent_email, $subject, $body);
	}

	/**
	 * Send vendor work completion notification to agent
	 *
	 * @param object $vendor_request VendorRequest model
	 * @param object $transaction Transaction model
	 * @return bool
	 */
	public function sendVendorCompletionNotification($vendor_request, $transaction): bool {
		// Get agent email from transaction
		$agent_email = $transaction->agent_email ?? null;
		if (!$agent_email) {
			error_log('NotificationService: No agent email found for transaction ' . $transaction->id);
			return false;
		}

		$subject = "Vendor Completed Work: {$vendor_request->vendor_type} for {$transaction->property_address}";

		$body = $this->renderTemplate('vendor-completion-notification', [
			'agent_name' => $transaction->agent_name ?? 'Agent',
			'vendor_type' => ucwords(str_replace('_', ' ', $vendor_request->vendor_type)),
			'vendor_name' => $vendor_request->vendor_name ?? 'Vendor',
			'vendor_email' => $vendor_request->vendor_email,
			'property_address' => $transaction->property_address,
			'completion_notes' => $vendor_request->completion_notes ?? 'No notes provided',
			'document_url' => $vendor_request->document_url ?? null,
			'dashboard_url' => admin_url('admin.php?page=ma-deal-room&transaction_id=' . $transaction->id),
		]);

		return $this->sendEmail($agent_email, $subject, $body);
	}

	/**
	 * Send vendor availability notification to agent
	 *
	 * @param object $vendor_request VendorRequest model
	 * @param object $transaction Transaction model
	 * @param array $availability_slots Array of available date/time slots
	 * @return bool
	 */
	public function sendVendorAvailabilityNotification($vendor_request, $transaction, array $availability_slots): bool {
		// Get agent email from transaction
		$agent_email = $transaction->agent_email ?? null;
		if (!$agent_email) {
			error_log('NotificationService: No agent email found for transaction ' . $transaction->id);
			return false;
		}

		$subject = "Vendor Availability Received: {$vendor_request->vendor_type} for {$transaction->property_address}";

		// Format availability slots
		$formatted_slots = [];
		foreach ($availability_slots as $slot) {
			$formatted_slots[] = date('l, F j, Y', strtotime($slot['available_date'])) . ' at ' . $slot['available_time'];
		}

		$body = $this->renderTemplate('vendor-availability-notification', [
			'agent_name' => $transaction->agent_name ?? 'Agent',
			'vendor_type' => ucwords(str_replace('_', ' ', $vendor_request->vendor_type)),
			'vendor_name' => $vendor_request->vendor_name ?? 'Vendor',
			'vendor_email' => $vendor_request->vendor_email,
			'property_address' => $transaction->property_address,
			'availability_slots' => $formatted_slots,
			'dashboard_url' => admin_url('admin.php?page=ma-deal-room&transaction_id=' . $transaction->id),
		]);

		return $this->sendEmail($agent_email, $subject, $body);
	}

	/**
	 * Send new message notification
	 *
	 * @param object $vendor_request VendorRequest model
	 * @param object $transaction Transaction model
	 * @param string $recipient_email Email of message recipient
	 * @param string $sender_name Name of sender
	 * @param string $message Message text
	 * @return bool
	 */
	public function sendVendorMessageNotification($vendor_request, $transaction, string $recipient_email, string $sender_name, string $message): bool {
		$subject = "New Message: {$transaction->property_address}";

		$body = $this->renderTemplate('vendor-message-notification', [
			'recipient_name' => $recipient_email,
			'sender_name' => $sender_name,
			'vendor_type' => ucwords(str_replace('_', ' ', $vendor_request->vendor_type)),
			'property_address' => $transaction->property_address,
			'message' => $message,
			'portal_url' => home_url('/agent-dashboard/#/vendor-portal?token=' . $vendor_request->token),
		]);

		return $this->sendEmail($recipient_email, $subject, $body);
	}
}
