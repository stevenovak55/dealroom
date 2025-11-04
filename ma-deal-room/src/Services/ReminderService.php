<?php
/**
 * Reminder Service
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Repositories\ReminderRepository;
use MADealRoom\Repositories\TaskRepository;
use MADealRoom\Repositories\TransactionRepository;
use MADealRoom\Services\NotificationService;

/**
 * Reminder queue management service
 */
class ReminderService {
	private $reminder_repository;
	private $notification_service;
	private $task_repository;
	private $transaction_repository;

	public function __construct(ReminderRepository $reminder_repository, NotificationService $notification_service, TaskRepository $task_repository, TransactionRepository $transaction_repository) {
		$this->reminder_repository = $reminder_repository;
		$this->notification_service = $notification_service;
		$this->task_repository = $task_repository;
		$this->transaction_repository = $transaction_repository;
	}

	/**
	 * Process pending reminders in queue
	 *
	 * @param int $batch_size Number of reminders to process
	 * @return array Processing results
	 */
	public function processQueue(int $batch_size = 100): array {
		$pending_reminders = $this->reminder_repository->findPending($batch_size);

		$results = [
			'processed' => 0,
			'sent' => 0,
			'failed' => 0,
		];

		foreach ($pending_reminders as $reminder) {
			$results['processed']++;

			try {
				$this->sendReminder($reminder);
				$results['sent']++;
			} catch (\Exception $e) {
				$this->reminder_repository->markFailed($reminder->id, $e->getMessage());
				$results['failed']++;
			}
		}

		return $results;
	}

	/**
	 * Send a reminder
	 *
	 * @param object $reminder Reminder model
	 * @return bool
	 * @throws \Exception If send fails
	 */
	public function sendReminder($reminder): bool {
		// Load task and transaction data using repositories
		$task = $this->task_repository->find($reminder->task_id);

		if (!$task) {
			throw new \Exception("Task not found for reminder {$reminder->id}");
		}

		$transaction = $this->transaction_repository->find($task->transaction_id);

		if (!$transaction) {
			throw new \Exception("Transaction not found for task {$task->id}");
		}

		// Determine recipient email if not set
		$recipient_email = $reminder->recipient_email;
		if (empty($recipient_email)) {
			// Fallback: Get transaction owner's email
			$owner = get_userdata($transaction->assigned_agent_id ?? 1); // Assuming assigned_agent_id is the owner
			$recipient_email = $owner ? $owner->user_email : get_option('admin_email');
		}

		// Determine recipient phone if not set
		$recipient_phone = $reminder->recipient_phone;
		// TODO: Implement more robust logic for determining recipient phone number

		$success = false;
		$message_body = $this->buildReminderMessage($reminder, $task, $transaction);

		if ($reminder->channel === 'email' || $reminder->channel === 'both') {
			$success = $this->notification_service->sendReminder($reminder, $task, $transaction);
		}

		if (($reminder->channel === 'sms' || $reminder->channel === 'both') && !empty($recipient_phone)) {
			$sms_success = $this->notification_service->sendSms($recipient_phone, $message_body);
			$success = $success || $sms_success; // If either email or SMS is successful
		}

		// Mark as sent or failed
		if ($success) {
			$this->reminder_repository->markSent($reminder->id);
		} else {
			throw new \Exception("Failed to send reminder via any channel");
		}

		return true;
	}

	/**
	 * Build reminder message content (for both email and SMS)
	 *
	 * @param object $reminder Reminder model
	 * @param object $task Task model
	 * @param object $transaction Transaction model
	 * @return string
	 */
	private function buildReminderMessage($reminder, $task, $transaction): string {
		$message = "Reminder: {$task->title}\n";
		if (!empty($task->description)) {
			$message .= "Description: {$task->description}\n";
		}
		$message .= "Due: " . ($task->due_at ? date('F j, Y', strtotime($task->due_at)) : 'Not set') . "\n";
		$message .= "Property: {$transaction->property_address}\n";
		$message .= "View details: " . admin_url("admin.php?page=ma-deal-room&transaction_id={$transaction->transaction_id}");

		return $message;
	}

	/**
	 * Retry failed reminders with exponential backoff
	 *
	 * @return array Retry results
	 */
	public function retryFailed(): array {
		// Find failed reminders eligible for retry with exponential backoff
		// Backoff formula: 2^retry_count minutes
		// retry_count=0: immediate, retry_count=1: 2min, retry_count=2: 4min, retry_count=3: 8min
		$failed_reminders = $this->reminder_repository->findFailedEligibleForRetry();

		$results = [
			'retried' => 0,
			'succeeded' => 0,
			'failed' => 0,
		];

		foreach ($failed_reminders as $reminder) {
			$results['retried']++;

			// Increment retry count
			$this->reminder_repository->incrementRetryCount($reminder->id);

			try {
				// Attempt to resend
				$this->sendReminder($reminder);
				$results['succeeded']++;
			} catch (\Exception $e) {
				// Mark as failed again
				$this->reminder_repository->markFailed($reminder->id, $e->getMessage());
				$results['failed']++;
			}
		}

		return $results;
	}

	/**
	 * Mark reminder as sent
	 *
	 * @param int $reminder_id Reminder ID
	 * @return bool
	 */
	public function markSent(int $reminder_id): bool {
		return $this->reminder_repository->markSent($reminder_id);
	}

	/**
	 * Cancel pending reminders for a task
	 *
	 * @param int $task_id Task ID
	 * @return int Number of reminders cancelled
	 */
	public function cancelForTask(int $task_id): int {
		global $wpdb;
		$table = $wpdb->prefix . 'ma_deal_reminders';

		$result = $wpdb->update(
			$table,
			['status' => 'cancelled'],
			['task_id' => $task_id, 'status' => 'pending']
		);

		return $result ?: 0;
	}

	/**
	 * Create reminders for a task from its reminder configuration
	 *
	 * @param object $task Task model
	 * @param object $transaction Transaction model
	 * @return int Number of reminders created
	 */
	public function createRemindersForTask($task, $transaction): int {
		// Parse reminders configuration from task
		$reminders_config = json_decode($task->reminders_config ?? '[]', true);

		if (empty($reminders_config)) {
			return 0;
		}

		$created_count = 0;
		$task_due_at = strtotime($task->due_at);

		foreach ($reminders_config as $reminder_config) {
			// Parse offset (e.g., "-30d", "-7d", "-1h")
			$offset = $reminder_config['offset'] ?? '-7d';
			$channels = $reminder_config['channels'] ?? ['email'];
			$priority = $reminder_config['priority'] ?? 'normal';

			// Calculate scheduled time based on offset from task due date
			if (preg_match('/^([\+\-])(\d+)(d|h|m)$/', $offset, $matches)) {
				$operator = $matches[1];
				$amount = (int)$matches[2];
				$unit = $matches[3];

				$multiplier = ['d' => 86400, 'h' => 3600, 'm' => 60][$unit];
				$offset_seconds = $amount * $multiplier;

				$scheduled_timestamp = $operator === '-'
					? $task_due_at - $offset_seconds
					: $task_due_at + $offset_seconds;

				$scheduled_at = date('Y-m-d H:i:s', $scheduled_timestamp);

				// Determine channel
				$channel = in_array('sms', $channels) && in_array('email', $channels)
					? 'both'
					: (in_array('sms', $channels) ? 'sms' : 'email');

				// Create reminder record
				global $wpdb;
				$table = $wpdb->prefix . 'ma_deal_reminders';

				$wpdb->insert($table, [
					'task_id' => $task->task_id,
					'transaction_id' => $transaction->transaction_id,
					'recipient_type' => 'agent', // Default to agent
					'recipient_email' => null, // Will be determined at send time
					'channel' => $channel,
					'scheduled_at' => $scheduled_at,
					'status' => 'pending',
					'metadata' => json_encode([
						'priority' => $priority,
						'offset' => $offset,
					]),
				]);

				if ($wpdb->insert_id) {
					$created_count++;
				}
			}
		}

		return $created_count;
	}
}
