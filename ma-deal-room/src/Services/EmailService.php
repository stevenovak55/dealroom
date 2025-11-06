<?php
/**
 * Email Service
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Models\Transaction;
use MADealRoom\Models\Task;
use MADealRoom\Models\Document;
use MADealRoom\Models\Party;
use MADealRoom\Repositories\NotificationRepository;
use SendGrid;
use SendGrid\Mail\Mail;

/**
 * Service for sending email notifications and creating in-app notifications
 */
class EmailService {
	protected $notification_repository;

	/**
	 * Email template service
	 *
	 * @var EmailTemplateService
	 */
	protected $template_service;

	/**
	 * Notification preferences service
	 *
	 * @var NotificationPreferencesService
	 */
	protected $preferences_service;

	/**
	 * SendGrid API key (optional - for production email delivery)
	 *
	 * @var string|null
	 */
	protected $sendgrid_api_key;

	/**
	 * SendGrid client instance
	 *
	 * @var SendGrid|null
	 */
	protected $sendgrid_client;

	/**
	 * Whether to use SendGrid for email delivery
	 *
	 * @var bool
	 */
	protected $use_sendgrid = false;

	/**
	 * Email delivery mode (sendgrid, wp_mail, mailhog)
	 *
	 * @var string
	 */
	protected $delivery_mode;

	public function __construct(
		?NotificationRepository $notification_repository = null,
		?EmailTemplateService $template_service = null,
		?NotificationPreferencesService $preferences_service = null
	) {
		$this->notification_repository = $notification_repository;
		$this->template_service = $template_service ?? new EmailTemplateService();
		$this->preferences_service = $preferences_service ?? new NotificationPreferencesService();
		$this->sendgrid_api_key = $this->get_sendgrid_api_key();

		// Initialize SendGrid if API key is available
		if ($this->sendgrid_api_key) {
			$this->sendgrid_client = new SendGrid($this->sendgrid_api_key);
			$this->use_sendgrid = true;
			$this->delivery_mode = 'sendgrid';
		} else {
			// Determine fallback mode based on environment
			$this->delivery_mode = $this->is_development() ? 'mailhog' : 'wp_mail';
		}
	}

	/**
	 * Get SendGrid API key from environment variables
	 *
	 * @return string|null
	 */
	protected function get_sendgrid_api_key(): ?string {
		// Try multiple environment variable naming conventions
		$env_vars = [
			'SENDGRID_API_KEY',
			'MA_DEAL_SENDGRID_API_KEY',
		];

		foreach ($env_vars as $env_var) {
			// Check both getenv() and $_ENV for maximum compatibility
			$key = getenv($env_var) ?: ($_ENV[$env_var] ?? null);
			if ($key) {
				return $key;
			}
		}

		// Future: Could also check WordPress options as fallback
		return null;
	}

	/**
	 * Check if running in development environment
	 *
	 * @return bool
	 */
	protected function is_development(): bool {
		// Check for common development indicators
		$env = getenv('WP_ENV') ?: ($_ENV['WP_ENV'] ?? 'production');
		return in_array($env, ['development', 'dev', 'local'], true) ||
		       (defined('WP_DEBUG') && WP_DEBUG) ||
		       (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local');
	}
	/**
	 * Send task assigned notification
	 *
	 * @param Task $task Task that was assigned
	 * @param Transaction $transaction Related transaction
	 * @param Party|null $assignee Party the task was assigned to
	 * @return bool Whether email was sent successfully
	 */
	public function sendTaskAssignedNotification(Task $task, Transaction $transaction, ?Party $assignee = null): bool {
		if (!$assignee || !$assignee->email) {
			return false;
		}

		$subject = sprintf(
			'[MA Deal Room] New Task Assigned: %s',
			$task->title
		);

		$body = $this->renderTemplate('task-assigned', [
			'task' => $task,
			'transaction' => $transaction,
			'assignee' => $assignee,
			'recipient_email' => $assignee->email,
		]);

		// Create in-app notification
		$user_id = $this->getUserIdByEmail($assignee->email);
		if ($user_id) {
			$this->createNotification(
				$user_id,
				'task_assigned',
				'New Task Assigned',
				sprintf('You have been assigned: %s', $task->title),
				$this->getTransactionUrl($transaction->id),
				'task',
				$task->id
			);
		}

		return $this->send($assignee->email, $subject, $body);
	}

	/**
	 * Send task status updated notification
	 *
	 * @param Task $task Task that was updated
	 * @param Transaction $transaction Related transaction
	 * @param string $old_status Previous status
	 * @param string $new_status New status
	 * @param Party|null $assignee Party assigned to the task
	 * @return bool Whether email was sent successfully
	 */
	public function sendTaskStatusUpdatedNotification(
		Task $task,
		Transaction $transaction,
		string $old_status,
		string $new_status,
		?Party $assignee = null
	): bool {
		if (!$assignee || !$assignee->email) {
			return false;
		}

		$subject = sprintf(
			'[MA Deal Room] Task Status Updated: %s',
			$task->title
		);

		$body = $this->renderTemplate('task-status-updated', [
			'task' => $task,
			'transaction' => $transaction,
			'assignee' => $assignee,
			'old_status' => $old_status,
			'new_status' => $new_status,
			'recipient_email' => $assignee->email,
		]);

		// Create in-app notification
		$user_id = $this->getUserIdByEmail($assignee->email);
		if ($user_id) {
			$this->createNotification(
				$user_id,
				'task_updated',
				'Task Status Updated',
				sprintf('Task "%s" status changed to %s', $task->title, $new_status),
				$this->getTransactionUrl($transaction->id),
				'task',
				$task->id
			);
		}

		return $this->send($assignee->email, $subject, $body);
	}

	/**
	 * Send document uploaded notification
	 *
	 * @param Document $document Document that was uploaded
	 * @param Transaction $transaction Related transaction
	 * @param array $recipients Array of email addresses to notify
	 * @return bool Whether all emails were sent successfully
	 */
	public function sendDocumentUploadedNotification(
		Document $document,
		Transaction $transaction,
		array $recipients
	): bool {
		if (empty($recipients)) {
			return false;
		}

		$subject = sprintf(
			'[MA Deal Room] New Document Uploaded: %s',
			$document->title ?: $document->file_name
		);

		$success = true;
		foreach ($recipients as $email) {
			// Render template with recipient-specific data
			$body = $this->renderTemplate('document-uploaded', [
				'document' => $document,
				'transaction' => $transaction,
				'recipient_email' => $email,
			]);

			// Send email
			if (!$this->send($email, $subject, $body)) {
				$success = false;
			}

			// Create in-app notification
			$user_id = $this->getUserIdByEmail($email);
			if ($user_id) {
				$this->createNotification(
					$user_id,
					'document_uploaded',
					'New Document Uploaded',
					sprintf('New document: %s', $document->title ?: $document->file_name),
					$this->getTransactionUrl($transaction->id) . '#documents',
					'document',
					$document->id
				);
			}
		}

		return $success;
	}

	/**
	 * Send transaction status changed notification
	 *
	 * @param Transaction $transaction Transaction that was updated
	 * @param string $old_status Previous status
	 * @param string $new_status New status
	 * @param array $recipients Array of email addresses to notify
	 * @return bool Whether all emails were sent successfully
	 */
	public function sendTransactionStatusChangedNotification(
		Transaction $transaction,
		string $old_status,
		string $new_status,
		array $recipients
	): bool {
		if (empty($recipients)) {
			return false;
		}

		$subject = sprintf(
			'[MA Deal Room] Transaction Status Updated: %s',
			$transaction->property_address
		);

		$success = true;
		foreach ($recipients as $email) {
			// Render template with recipient-specific data
			$body = $this->renderTemplate('transaction-status-changed', [
				'transaction' => $transaction,
				'old_status' => $old_status,
				'new_status' => $new_status,
				'recipient_email' => $email,
			]);

			// Send email
			if (!$this->send($email, $subject, $body)) {
				$success = false;
			}

			// Create in-app notification
			$user_id = $this->getUserIdByEmail($email);
			if ($user_id) {
				$this->createNotification(
					$user_id,
					'transaction_updated',
					'Transaction Status Updated',
					sprintf('Transaction "%s" status changed to %s', $transaction->property_address, $new_status),
					$this->getTransactionUrl($transaction->id),
					'transaction',
					$transaction->id
				);
			}
		}

		return $success;
	}

	/**
	 * Send new party added notification
	 *
	 * @param Party $party Party that was added
	 * @param Transaction $transaction Related transaction
	 * @return bool Whether email was sent successfully
	 */
	public function sendPartyAddedNotification(Party $party, Transaction $transaction): bool {
		if (!$party->email) {
			return false;
		}

		$subject = sprintf(
			'[MA Deal Room] You\'ve been added to: %s',
			$transaction->property_address
		);

		$body = $this->renderTemplate('party-added', [
			'party' => $party,
			'transaction' => $transaction,
			'recipient_email' => $party->email,
		]);

		// Create in-app notification
		$user_id = $this->getUserIdByEmail($party->email);
		if ($user_id) {
			$this->createNotification(
				$user_id,
				'party_added',
				'Added to Transaction',
				sprintf('You\'ve been added to transaction: %s', $transaction->property_address),
				$this->getTransactionUrl($transaction->id),
				'transaction',
				$transaction->id
			);
		}

		return $this->send($party->email, $subject, $body);
	}

	/**
	 * Send transaction created notification
	 *
	 * @param Transaction $transaction Transaction that was created
	 * @param int $owner_user_id Account owner user ID to notify
	 * @param int|null $assigned_agent_id Assigned agent user ID (optional)
	 * @return bool Whether all emails were sent successfully
	 */
	public function sendTransactionCreatedNotification(
		Transaction $transaction,
		int $owner_user_id,
		?int $assigned_agent_id = null
	): bool {
		$success = true;
		$recipients = [];

		// Get owner user email
		$owner_user = get_user_by('id', $owner_user_id);
		if ($owner_user && $owner_user->user_email) {
			$recipients[] = [
				'email' => $owner_user->user_email,
				'name' => $owner_user->display_name,
				'type' => 'owner',
			];
		}

		// Get assigned agent email if provided
		if ($assigned_agent_id && $assigned_agent_id !== $owner_user_id) {
			$agent_user = get_user_by('id', $assigned_agent_id);
			if ($agent_user && $agent_user->user_email) {
				$recipients[] = [
					'email' => $agent_user->user_email,
					'name' => $agent_user->display_name,
					'type' => 'agent',
				];
			}
		}

		// Send email to each recipient
		foreach ($recipients as $recipient) {
			$subject = sprintf(
				'[MA Deal Room] New Transaction: %s',
				$transaction->property_address
			);

			$body = $this->renderTemplate('transaction-created', [
				'transaction' => $transaction,
				'recipient_email' => $recipient['email'],
				'recipient_name' => $recipient['name'],
				'recipient_type' => $recipient['type'],
			]);

			// Send email
			if (!$this->send($recipient['email'], $subject, $body)) {
				$success = false;
				error_log(sprintf('[EmailService] Failed to send transaction created notification to %s', $recipient['email']));
			}

			// Create in-app notification
			$user_id = $this->getUserIdByEmail($recipient['email']);
			if ($user_id) {
				$message = sprintf(
					'%s has created transaction: %s',
					$owner_user ? $owner_user->display_name : 'New',
					$transaction->property_address
				);
				$this->createNotification(
					$user_id,
					'transaction_created',
					'New Transaction Created',
					$message,
					$this->getTransactionUrl($transaction->id),
					'transaction',
					$transaction->id
				);
			}
		}

		return $success;
	}

	/**
	 * Send email using appropriate delivery method (SendGrid or wp_mail)
	 *
	 * @param string $to Recipient email address
	 * @param string $subject Email subject
	 * @param string $body Email body (HTML)
	 * @param array $headers Optional headers
	 * @return bool Whether email was sent successfully
	 */
	protected function send(string $to, string $subject, string $body, array $headers = []): bool {
		// Route to appropriate delivery method
		if ($this->use_sendgrid && $this->sendgrid_client) {
			return $this->sendWithSendGrid($to, $subject, $body, $headers);
		} else {
			return $this->sendWithWPMail($to, $subject, $body, $headers);
		}
	}

	/**
	 * Send email using SendGrid API
	 *
	 * @param string $to Recipient email address
	 * @param string $subject Email subject
	 * @param string $body Email body (HTML)
	 * @param array $headers Optional headers
	 * @return bool Whether email was sent successfully
	 */
	protected function sendWithSendGrid(string $to, string $subject, string $body, array $headers = []): bool {
		try {
			$from_email = 'noreply@' . $this->getDomain();
			$from_name = 'MA Deal Room';

			// Create SendGrid Mail object
			$email = new Mail();
			$email->setFrom($from_email, $from_name);
			$email->setSubject($subject);
			$email->addTo($to);
			$email->addContent('text/html', $body);

			// Parse custom headers if provided
			foreach ($headers as $header) {
				if (stripos($header, 'From:') === 0) {
					// Parse From header
					preg_match('/From:\s*(.+?)\s*<(.+?)>/', $header, $matches);
					if (count($matches) >= 3) {
						$email->setFrom($matches[2], $matches[1]);
					}
				}
			}

			// Send email via SendGrid
			$response = $this->sendgrid_client->send($email);

			// Check response
			if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
				error_log(sprintf('[SendGrid] Email sent to %s: %s (Status: %d)', $to, $subject, $response->statusCode()));
				$this->logEmailDelivery($to, $subject, 'sent', 'sendgrid', $response->statusCode());
				return true;
			} else {
				error_log(sprintf('[SendGrid] Failed to send email to %s: %s (Status: %d, Body: %s)',
					$to, $subject, $response->statusCode(), $response->body()));
				$this->logEmailDelivery($to, $subject, 'failed', 'sendgrid', $response->statusCode(), $response->body());
				return false;
			}
		} catch (\Exception $e) {
			error_log(sprintf('[SendGrid] Exception sending email to %s: %s - %s', $to, $subject, $e->getMessage()));
			$this->logEmailDelivery($to, $subject, 'failed', 'sendgrid', 0, $e->getMessage());

			// Fallback to wp_mail if SendGrid fails
			error_log('[SendGrid] Falling back to wp_mail');
			return $this->sendWithWPMail($to, $subject, $body, $headers);
		}
	}

	/**
	 * Send email using WordPress wp_mail with retry logic
	 *
	 * @param string $to Recipient email address
	 * @param string $subject Email subject
	 * @param string $body Email body (HTML)
	 * @param array $headers Optional headers
	 * @return bool Whether email was sent successfully
	 */
	protected function sendWithWPMail(string $to, string $subject, string $body, array $headers = []): bool {
		// Default headers for HTML email
		$default_headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: MA Deal Room <noreply@' . $this->getDomain() . '>',
		];

		$headers = array_merge($default_headers, $headers);

		// Retry logic with exponential backoff
		$max_retries = 3;
		$retry_delay = 1; // Start with 1 second delay
		$result = false;

		for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
			$result = wp_mail($to, $subject, $body, $headers);

			if ($result) {
				// Success - log and return
				error_log(sprintf('[wp_mail] Email sent to %s: %s (attempt %d)', $to, $subject, $attempt));
				$this->logEmailDelivery($to, $subject, 'sent', 'wp_mail');
				break;
			} else {
				// Failed - log the attempt
				error_log(sprintf('[wp_mail] Failed to send email to %s: %s (attempt %d of %d)', $to, $subject, $attempt, $max_retries));

				// If not the last attempt, wait before retrying
				if ($attempt < $max_retries) {
					sleep($retry_delay);
					$retry_delay *= 2; // Exponential backoff: 1s, 2s, 4s
				}
			}
		}

		// If all retries failed, log final failure
		if (!$result) {
			error_log(sprintf('[wp_mail] Email permanently failed after %d attempts to %s: %s', $max_retries, $to, $subject));
			$this->logEmailDelivery($to, $subject, 'failed', 'wp_mail');
		}

		return $result;
	}

	/**
	 * Log email delivery attempt to database
	 *
	 * @param string $to Recipient email
	 * @param string $subject Email subject
	 * @param string $status Status (sent, failed, bounced, spam)
	 * @param string $provider Delivery provider (sendgrid, wp_mail, mailhog)
	 * @param int $status_code HTTP status code (for SendGrid)
	 * @param string|null $error_message Error message if failed
	 * @return void
	 */
	protected function logEmailDelivery(
		string $to,
		string $subject,
		string $status,
		string $provider,
		int $status_code = 0,
		?string $error_message = null
	): void {
		global $wpdb;

		// Future: Create dedicated email_logs table
		// For now, just log to WordPress error log
		$log_entry = sprintf(
			'[Email Log] To: %s | Subject: %s | Status: %s | Provider: %s | Code: %d%s',
			$to,
			$subject,
			$status,
			$provider,
			$status_code,
			$error_message ? ' | Error: ' . $error_message : ''
		);

		error_log($log_entry);

		// TODO: Store in database table for tracking and analytics
		// $wpdb->insert($wpdb->prefix . 'ma_deal_email_logs', [
		//     'recipient' => $to,
		//     'subject' => $subject,
		//     'status' => $status,
		//     'provider' => $provider,
		//     'status_code' => $status_code,
		//     'error_message' => $error_message,
		//     'sent_at' => current_time('mysql'),
		// ]);
	}

	/**
	 * Get current delivery mode
	 *
	 * @return string Delivery mode (sendgrid, wp_mail, mailhog)
	 */
	public function getDeliveryMode(): string {
		return $this->delivery_mode;
	}

	/**
	 * Render email template
	 *
	 * @param string $template Template name
	 * @param array $data Data to pass to template
	 * @return string Rendered HTML
	 */
	protected function renderTemplate(string $template, array $data): string {
		// Add unsubscribe link if user email is provided
		if (!isset($data['unsubscribe_url']) && isset($data['recipient_email'])) {
			$user_id = $this->preferences_service->get_user_id_by_email($data['recipient_email']);
			if ($user_id) {
				$data['unsubscribe_url'] = $this->preferences_service->generate_unsubscribe_url($user_id, 'email');
			}
		}

		try {
			// Try to render with EmailTemplateService
			return $this->template_service->render($template, $data);
		} catch (\RuntimeException $e) {
			// Fallback to basic template if template not found
			error_log(sprintf('Email template "%s" not found, using fallback: %s', $template, $e->getMessage()));
			return $this->renderBasicTemplate($template, $data);
		}
	}

	/**
	 * Render basic email template (fallback)
	 *
	 * @param string $template Template name
	 * @param array $data Template data
	 * @return string Rendered HTML
	 */
	protected function renderBasicTemplate(string $template, array $data): string {
		$html = $this->getEmailHeader();

		// Generate content based on template type
		switch ($template) {
			case 'transaction-created':
				$html .= $this->renderTransactionCreatedContent($data);
				break;
			case 'task-assigned':
				$html .= $this->renderTaskAssignedContent($data);
				break;
			case 'task-status-updated':
				$html .= $this->renderTaskStatusUpdatedContent($data);
				break;
			case 'document-uploaded':
				$html .= $this->renderDocumentUploadedContent($data);
				break;
			case 'transaction-status-changed':
				$html .= $this->renderTransactionStatusChangedContent($data);
				break;
			case 'party-added':
				$html .= $this->renderPartyAddedContent($data);
				break;
			case 'user-invitation':
				$html .= $this->renderUserInvitationContent($data);
				break;
			default:
				$html .= '<p>Notification from MA Deal Room</p>';
		}

		$html .= $this->getEmailFooter();
		return $html;
	}

	/**
	 * Render task assigned content
	 */
	protected function renderTaskAssignedContent(array $data): string {
		$task = $data['task'];
		$transaction = $data['transaction'];
		$assignee = $data['assignee'];

		$html = '<h2>New Task Assigned</h2>';
		$html .= '<p>Hi ' . esc_html($assignee->contact_name) . ',</p>';
		$html .= '<p>A new task has been assigned to you:</p>';
		$html .= '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">';
		$html .= '<h3 style="margin-top: 0;">' . esc_html($task->title) . '</h3>';
		if ($task->description) {
			$html .= '<p>' . nl2br(esc_html($task->description)) . '</p>';
		}
		$html .= '<p><strong>Transaction:</strong> ' . esc_html($transaction->property_address) . '</p>';
		if ($task->due_at) {
			$html .= '<p><strong>Due Date:</strong> ' . date('F j, Y', strtotime($task->due_at)) . '</p>';
		}
		$html .= '</div>';
		$html .= '<p><a href="' . $this->getTransactionUrl($transaction->id) . '" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">View Transaction</a></p>';

		return $html;
	}

	/**
	 * Render task status updated content
	 */
	protected function renderTaskStatusUpdatedContent(array $data): string {
		$task = $data['task'];
		$transaction = $data['transaction'];
		$old_status = $data['old_status'];
		$new_status = $data['new_status'];

		$html = '<h2>Task Status Updated</h2>';
		$html .= '<p>The status of your task has been updated:</p>';
		$html .= '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">';
		$html .= '<h3 style="margin-top: 0;">' . esc_html($task->title) . '</h3>';
		$html .= '<p><strong>Transaction:</strong> ' . esc_html($transaction->property_address) . '</p>';
		$html .= '<p><strong>Status:</strong> ' . ucfirst($old_status) . ' → <strong>' . ucfirst($new_status) . '</strong></p>';
		$html .= '</div>';
		$html .= '<p><a href="' . $this->getTransactionUrl($transaction->id) . '" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">View Transaction</a></p>';

		return $html;
	}

	/**
	 * Render document uploaded content
	 */
	protected function renderDocumentUploadedContent(array $data): string {
		$document = $data['document'];
		$transaction = $data['transaction'];

		$html = '<h2>New Document Uploaded</h2>';
		$html .= '<p>A new document has been uploaded to your transaction:</p>';
		$html .= '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">';
		$html .= '<h3 style="margin-top: 0;">' . esc_html($document->title ?: $document->file_name) . '</h3>';
		$html .= '<p><strong>Transaction:</strong> ' . esc_html($transaction->property_address) . '</p>';
		if ($document->document_type) {
			$html .= '<p><strong>Type:</strong> ' . ucwords(str_replace('_', ' ', $document->document_type)) . '</p>';
		}
		if ($document->description) {
			$html .= '<p>' . nl2br(esc_html($document->description)) . '</p>';
		}
		$html .= '</div>';
		$html .= '<p><a href="' . $this->getTransactionUrl($transaction->id) . '#documents" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">View Documents</a></p>';

		return $html;
	}

	/**
	 * Render transaction status changed content
	 */
	protected function renderTransactionStatusChangedContent(array $data): string {
		$transaction = $data['transaction'];
		$old_status = $data['old_status'];
		$new_status = $data['new_status'];

		$html = '<h2>Transaction Status Updated</h2>';
		$html .= '<p>The status of your transaction has changed:</p>';
		$html .= '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">';
		$html .= '<h3 style="margin-top: 0;">' . esc_html($transaction->property_address) . '</h3>';
		$html .= '<p>' . esc_html($transaction->property_city) . ', ' . esc_html($transaction->property_state) . ' ' . esc_html($transaction->property_zip) . '</p>';
		$html .= '<p><strong>Status:</strong> ' . ucfirst($old_status) . ' → <strong>' . ucfirst($new_status) . '</strong></p>';
		$html .= '</div>';
		$html .= '<p><a href="' . $this->getTransactionUrl($transaction->id) . '" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">View Transaction</a></p>';

		return $html;
	}

	/**
	 * Render transaction created content
	 */
	protected function renderTransactionCreatedContent(array $data): string {
		$transaction = $data['transaction'];
		$recipient_name = $data['recipient_name'] ?? 'User';
		$recipient_type = $data['recipient_type'] ?? 'user';

		$html = '<h2>New Transaction Created</h2>';
		$html .= '<p>Hi ' . esc_html($recipient_name) . ',</p>';

		$role_text = $recipient_type === 'agent' ? 'as the assigned agent' : 'in your account';
		$html .= '<p>A new transaction has been created ' . $role_text . ':</p>';

		$html .= '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">';
		$html .= '<h3 style="margin-top: 0;">' . esc_html($transaction->property_address) . '</h3>';
		$html .= '<p>' . esc_html($transaction->property_city) . ', ' . esc_html($transaction->property_state) . ' ' . esc_html($transaction->property_zip) . '</p>';

		if ($transaction->property_type) {
			$html .= '<p><strong>Property Type:</strong> ' . esc_html($transaction->property_type) . '</p>';
		}

		if ($transaction->sale_price) {
			$html .= '<p><strong>Sale Price:</strong> $' . number_format($transaction->sale_price, 0) . '</p>';
		}

		$html .= '<p><strong>Status:</strong> <span style="background: #ffc107; color: #000; padding: 2px 8px; border-radius: 3px; font-weight: bold;">' . ucfirst(str_replace('_', ' ', $transaction->status)) . '</span></p>';

		if ($transaction->closing_date) {
			$html .= '<p><strong>Expected Closing Date:</strong> ' . date('F j, Y', strtotime($transaction->closing_date)) . '</p>';
		}

		$html .= '</div>';
		$html .= '<p><a href="' . $this->getTransactionUrl($transaction->id) . '" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">View Transaction Details</a></p>';

		return $html;
	}

	/**
	 * Render party added content
	 */
	protected function renderPartyAddedContent(array $data): string {
		$party = $data['party'];
		$transaction = $data['transaction'];

		$html = '<h2>Welcome to MA Deal Room</h2>';
		$html .= '<p>Hi ' . esc_html($party->contact_name) . ',</p>';
		$html .= '<p>You\'ve been added to a transaction as <strong>' . ucwords(str_replace('_', ' ', $party->role)) . '</strong>:</p>';
		$html .= '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px;">';
		$html .= '<h3 style="margin-top: 0;">' . esc_html($transaction->property_address) . '</h3>';
		$html .= '<p>' . esc_html($transaction->property_city) . ', ' . esc_html($transaction->property_state) . ' ' . esc_html($transaction->property_zip) . '</p>';
		$html .= '<p><strong>Type:</strong> ' . ucfirst($transaction->property_type) . '</p>';
		if ($transaction->closing_date) {
			$html .= '<p><strong>Closing Date:</strong> ' . date('F j, Y', strtotime($transaction->closing_date)) . '</p>';
		}
		$html .= '</div>';
		$html .= '<p><a href="' . $this->getTransactionUrl($transaction->id) . '" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">View Transaction</a></p>';

		return $html;
	}

	/**
	 * Render user invitation content
	 */
	protected function renderUserInvitationContent(array $data): string {
		$inviter_name = $data['inviter_name'] ?? 'Someone';
		$role = $data['role'] ?? 'user';
		$invitation_url = $data['invitation_url'];
		$message = $data['message'] ?? null;
		$expiry_days = $data['expiry_days'] ?? 7;

		$html = '<h2>You\'re Invited to Join MA Deal Room</h2>';
		$html .= '<p><strong>' . esc_html($inviter_name) . '</strong> has invited you to join MA Deal Room with the role: <strong>' . esc_html(ucwords(str_replace('_', ' ', $role))) . '</strong>.</p>';

		if ($message) {
			$html .= '<div style="background: #f9f9f9; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0;">';
			$html .= '<p style="margin: 0; font-style: italic;">"' . nl2br(esc_html($message)) . '"</p>';
			$html .= '<p style="margin: 10px 0 0 0; font-size: 12px; color: #666;">— ' . esc_html($inviter_name) . '</p>';
			$html .= '</div>';
		}

		$html .= '<div style="background: #f5f5f5; padding: 20px; margin: 20px 0; border-radius: 5px; text-align: center;">';
		$html .= '<p><strong>Your Role:</strong> ' . esc_html(ucwords(str_replace('_', ' ', $role))) . '</p>';
		$html .= '<p style="margin-top: 20px;"><a href="' . esc_url($invitation_url) . '" style="background: #0073aa; color: white; padding: 12px 30px; text-decoration: none; border-radius: 3px; display: inline-block; font-weight: bold;">Accept Invitation</a></p>';
		$html .= '<p style="margin-top: 15px; font-size: 12px; color: #666;">This invitation will expire in ' . $expiry_days . ' day' . ($expiry_days > 1 ? 's' : '') . '.</p>';
		$html .= '</div>';

		$html .= '<p style="color: #666; font-size: 14px;">If you don\'t want to accept this invitation, you can simply ignore this email.</p>';

		return $html;
	}

	/**
	 * Get email header HTML
	 */
	protected function getEmailHeader(): string {
		return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>MA Deal Room Notification</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
	<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
		<div style="background: #0073aa; color: white; padding: 20px; text-align: center; margin-bottom: 30px;">
			<h1 style="margin: 0; font-size: 24px;">MA Deal Room</h1>
		</div>
		<div style="padding: 0 20px;">';
	}

	/**
	 * Get email footer HTML
	 */
	protected function getEmailFooter(): string {
		return '</div>
		<div style="margin-top: 40px; padding: 20px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd;">
			<p>This is an automated notification from MA Deal Room.</p>
			<p>If you have questions, please contact your transaction coordinator.</p>
		</div>
	</div>
</body>
</html>';
	}

	/**
	 * Get transaction URL
	 */
	protected function getTransactionUrl(int $transaction_id): string {
		return admin_url('admin.php?page=ma-deal-room#/transactions/' . $transaction_id);
	}

	/**
	 * Get domain for from address
	 */
	protected function getDomain(): string {
		$site_url = get_site_url();
		$parsed = parse_url($site_url);
		return $parsed['host'] ?? 'localhost';
	}

	/**
	 * Get WordPress user ID from email
	 *
	 * @param string $email Email address
	 * @return int|null User ID or null if not found
	 */
	protected function getUserIdByEmail(string $email): ?int {
		$user = get_user_by('email', $email);
		return $user ? $user->ID : null;
	}

	/**
	 * Create in-app notification for a user
	 *
	 * @param int $user_id User ID to notify
	 * @param string $type Notification type
	 * @param string $title Notification title
	 * @param string $message Notification message
	 * @param string|null $link Optional link
	 * @param string|null $entity_type Optional entity type
	 * @param int|null $entity_id Optional entity ID
	 * @return bool
	 */
	protected function createNotification(
		int $user_id,
		string $type,
		string $title,
		string $message,
		?string $link = null,
		?string $entity_type = null,
		?int $entity_id = null
	): bool {
		if (!$this->notification_repository) {
			return false;
		}

		$notification_id = $this->notification_repository->create([
			'user_id' => $user_id,
			'type' => $type,
			'title' => $title,
			'message' => $message,
			'link' => $link,
			'entity_type' => $entity_type,
			'entity_id' => $entity_id
		]);

		return (bool) $notification_id;
	}

	/**
	 * Send email verification email
	 *
	 * @param string $email Recipient email address
	 * @param string $name Recipient name
	 * @param string $verification_url Verification URL
	 * @param int $expiry_hours Hours until token expires
	 * @return bool Whether email was sent successfully
	 */
	public function send_email_verification(string $email, string $name, string $verification_url, int $expiry_hours = 24): bool {
		$subject = 'Verify Your Email Address - MA Deal Room';

		$body = $this->renderTemplate('email-verification', [
			'user_name' => $name,
			'user_email' => $email,
			'verification_url' => $verification_url,
			'expiry_hours' => $expiry_hours,
			'recipient_email' => $email,
		]);

		return $this->send($email, $subject, $body);
	}

	/**
	 * Send password reset email
	 *
	 * @param string $email Recipient email address
	 * @param string $name Recipient name
	 * @param string $reset_url Password reset URL
	 * @param int $expiry_hours Hours until token expires
	 * @return bool Whether email was sent successfully
	 */
	public function send_password_reset(string $email, string $name, string $reset_url, int $expiry_hours = 1): bool {
		$subject = 'Reset Your Password - MA Deal Room';

		$body = $this->renderTemplate('password-reset', [
			'user_name' => $name,
			'user_email' => $email,
			'reset_url' => $reset_url,
			'expiry_hours' => $expiry_hours,
			'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
			'recipient_email' => $email,
		]);

		return $this->send($email, $subject, $body);
	}

	/**
	 * Send user invitation email
	 *
	 * @param string $email Recipient email address
	 * @param string $inviter_name Name of person sending invitation
	 * @param string $role Role being invited to
	 * @param string $invitation_url Invitation acceptance URL
	 * @param string|null $message Optional personal message
	 * @param int $expiry_days Days until invitation expires
	 * @return bool Whether email was sent successfully
	 */
	public function send_invitation_email(string $email, string $inviter_name, string $role, string $invitation_url, ?string $message = null, int $expiry_days = 7): bool {
		$subject = sprintf('You Have Been Invited to MA Deal Room by %s', $inviter_name);

		$body = $this->renderTemplate('user-invitation', [
			'inviter_name' => $inviter_name,
			'role' => $role,
			'invitation_url' => $invitation_url,
			'message' => $message,
			'expiry_days' => $expiry_days,
			'recipient_email' => $email,
		]);

		return $this->send($email, $subject, $body);
	}

	/**
	 * Send welcome email to new user
	 *
	 * @param string $email Recipient email address
	 * @param string $name Recipient name
	 * @param string $login_url Login URL
	 * @return bool Whether email was sent successfully
	 */
	public function send_welcome_email(string $email, string $name, string $login_url): bool {
		$subject = 'Welcome to MA Deal Room!';

		$body = $this->renderTemplate('welcome', [
			'user_name' => $name,
			'user_email' => $email,
			'login_url' => $login_url,
			'recipient_email' => $email,
		]);

		return $this->send($email, $subject, $body);
	}

	/**
	 * Send bundled tasks assigned notification
	 *
	 * Sends a single email for multiple tasks assigned at the same time
	 *
	 * @param array $items Array of notification data items
	 * @param string $user_email Recipient email
	 * @return bool Whether email was sent successfully
	 */
	public function sendTasksAssignedBundleNotification(array $items, string $user_email): bool {
		if (empty($items)) {
			return false;
		}

		// Extract common data
		$first_item = $items[0];
		$transaction = is_array($first_item) && isset($first_item['transaction']) ?
			(object)$first_item['transaction'] : null;
		$party = is_array($first_item) && isset($first_item['party']) ?
			(object)$first_item['party'] : null;

		$user_name = $party ? $party->contact_name : 'there';
		$tasks_count = count($items);

		$subject = sprintf('[MA Deal Room] %d Tasks Assigned to You', $tasks_count);

		// Determine the best URL - if all tasks are for the same transaction, link to that transaction
		$summary_url = admin_url('admin.php?page=ma-deal-room#/tasks');
		if ($transaction && isset($transaction->id)) {
			$summary_url = admin_url('admin.php?page=ma-deal-room#/transactions/' . $transaction->id);
		}

		$body = $this->renderTemplate('tasks-assigned-bundle', [
			'tasks_count' => $tasks_count,
			'tasks' => $items,
			'transaction' => $transaction,
			'user_name' => $user_name,
			'summary_url' => $summary_url,
			'recipient_email' => $user_email,
		]);

		return $this->send($user_email, $subject, $body);
	}

	/**
	 * Send bundled documents uploaded notification
	 *
	 * Sends a single email for multiple documents uploaded at the same time
	 *
	 * @param array $items Array of notification data items
	 * @param string $user_email Recipient email
	 * @return bool Whether email was sent successfully
	 */
	public function sendDocumentsUploadedBundleNotification(array $items, string $user_email): bool {
		if (empty($items)) {
			return false;
		}

		// Extract common data
		$first_item = $items[0];
		$transaction = is_array($first_item) && isset($first_item['transaction']) ?
			(object)$first_item['transaction'] : null;

		$documents_count = count($items);

		$subject = sprintf('[MA Deal Room] %d New Documents', $documents_count);

		$body = $this->renderTemplate('documents-uploaded-bundle', [
			'documents_count' => $documents_count,
			'documents' => $items,
			'transaction' => $transaction,
			'summary_url' => admin_url('admin.php?page=ma-deal-room#/documents'),
			'recipient_email' => $user_email,
		]);

		return $this->send($user_email, $subject, $body);
	}
}
