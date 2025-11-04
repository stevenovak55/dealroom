<?php
/**
 * Notification Queue Service
 *
 * Handles queuing and batching of notifications to prevent email spam
 * when multiple notifications are triggered in quick succession.
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

/**
 * Service for queuing and batching notifications
 */
class NotificationQueueService {
	/**
	 * Delay in minutes before processing queued notifications
	 * This allows time for notifications to batch together
	 */
	const BATCH_DELAY_MINUTES = 2;

	/**
	 * Maximum number of items to show in a bundled email
	 */
	const MAX_BUNDLE_ITEMS = 10;

	/**
	 * Database table name
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Email service instance
	 *
	 * @var EmailService
	 */
	protected $email_service;

	/**
	 * Constructor
	 *
	 * @param EmailService|null $email_service Email service instance
	 */
	public function __construct(?EmailService $email_service = null) {
		global $wpdb;
		$this->table = $wpdb->prefix . 'ma_deal_notification_queue';
		$this->email_service = $email_service ?? new EmailService();
	}

	/**
	 * Queue a notification for batched sending
	 *
	 * @param int $user_id User ID to notify
	 * @param string $notification_type Type of notification
	 * @param array $notification_data Data for the notification
	 * @param string $user_type Type of user (wordpress, custom, etc.)
	 * @return int|false Queue item ID on success, false on failure
	 */
	public function queue(
		int $user_id,
		string $notification_type,
		array $notification_data,
		string $user_type = 'wordpress'
	) {
		global $wpdb;

		// Calculate scheduled time (now + batch delay)
		$scheduled_for = date('Y-m-d H:i:s', strtotime('+' . self::BATCH_DELAY_MINUTES . ' minutes'));

		$result = $wpdb->insert(
			$this->table,
			[
				'user_id' => $user_id,
				'user_type' => $user_type,
				'notification_type' => $notification_type,
				'notification_data' => json_encode($notification_data),
				'created_at' => current_time('mysql'),
				'scheduled_for' => $scheduled_for,
				'status' => 'pending',
			],
			['%d', '%s', '%s', '%s', '%s', '%s', '%s']
		);

		if ($result === false) {
			error_log('[NotificationQueue] Failed to queue notification: ' . $wpdb->last_error);
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Process pending notifications in the queue
	 *
	 * Groups notifications by user and type, sends bundled emails
	 * when multiple notifications exist for the same user/type.
	 *
	 * @return array Processing results with counts
	 */
	public function processPending(): array {
		global $wpdb;

		$now = current_time('mysql');

		// Get all pending notifications that are ready to be processed
		$pending = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$this->table}
			 WHERE status = 'pending'
			 AND scheduled_for <= %s
			 ORDER BY user_id, notification_type, scheduled_for",
			$now
		));

		if (empty($pending)) {
			return [
				'processed' => 0,
				'sent' => 0,
				'bundled' => 0,
				'failed' => 0,
			];
		}

		// Group notifications by user_id and notification_type
		$groups = [];
		foreach ($pending as $item) {
			$key = $item->user_id . '|' . $item->notification_type;
			if (!isset($groups[$key])) {
				$groups[$key] = [];
			}
			$groups[$key][] = $item;
		}

		$stats = [
			'processed' => 0,
			'sent' => 0,
			'bundled' => 0,
			'failed' => 0,
		];

		// Process each group
		foreach ($groups as $key => $items) {
			list($user_id, $notification_type) = explode('|', $key);

			if (count($items) === 1) {
				// Single notification - send individual email
				$result = $this->sendSingleNotification($items[0]);
			} else {
				// Multiple notifications - send bundled email
				$result = $this->sendBundledNotification($items, $notification_type);
			}

			if ($result) {
				$stats['sent']++;
				if (count($items) > 1) {
					$stats['bundled']++;
				}

				// Mark all items in this group as sent
				$item_ids = array_map(function($item) { return $item->id; }, $items);
				$this->markAsSent($item_ids);
			} else {
				$stats['failed']++;

				// Mark all items in this group as failed
				$item_ids = array_map(function($item) { return $item->id; }, $items);
				$this->markAsFailed($item_ids, 'Email sending failed');
			}

			$stats['processed'] += count($items);
		}

		return $stats;
	}

	/**
	 * Send a single notification email
	 *
	 * @param object $queue_item Queue item from database
	 * @return bool Success status
	 */
	protected function sendSingleNotification(object $queue_item): bool {
		$data = json_decode($queue_item->notification_data, true);
		if (!$data) {
			error_log('[NotificationQueue] Invalid notification data for item ' . $queue_item->id);
			return false;
		}

		// Get user email
		$user_email = $this->getUserEmail($queue_item->user_id, $queue_item->user_type);
		if (!$user_email) {
			error_log('[NotificationQueue] No email found for user ' . $queue_item->user_id);
			return false;
		}

		// Send based on notification type
		try {
			switch ($queue_item->notification_type) {
				case 'task_assigned':
					return $this->email_service->sendTaskAssignedNotification(
						$data['task'],
						$data['transaction'],
						$data['party']
					);

				case 'task_reminder':
					return $this->email_service->sendTaskReminderNotification(
						$data['task'],
						$data['transaction'],
						$data['party']
					);

				case 'document_uploaded':
					return $this->email_service->sendDocumentUploadedNotification(
						$data['document'],
						$data['transaction'],
						$data['party']
					);

				// Add more notification types as needed

				default:
					error_log('[NotificationQueue] Unknown notification type: ' . $queue_item->notification_type);
					return false;
			}
		} catch (\Exception $e) {
			error_log('[NotificationQueue] Error sending notification: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Send a bundled notification email
	 *
	 * @param array $queue_items Array of queue items
	 * @param string $notification_type Notification type
	 * @return bool Success status
	 */
	protected function sendBundledNotification(array $queue_items, string $notification_type): bool {
		if (empty($queue_items)) {
			return false;
		}

		$first_item = $queue_items[0];

		// Get user email
		$user_email = $this->getUserEmail($first_item->user_id, $first_item->user_type);
		if (!$user_email) {
			error_log('[NotificationQueue] No email found for user ' . $first_item->user_id);
			return false;
		}

		// Collect all notification data
		$items = [];
		foreach ($queue_items as $queue_item) {
			$data = json_decode($queue_item->notification_data, true);
			if ($data) {
				$items[] = $data;
			}
		}

		if (empty($items)) {
			return false;
		}

		// Send bundled email based on notification type
		try {
			switch ($notification_type) {
				case 'task_assigned':
					return $this->email_service->sendTasksAssignedBundleNotification(
						$items,
						$user_email
					);

				case 'document_uploaded':
					return $this->email_service->sendDocumentsUploadedBundleNotification(
						$items,
						$user_email
					);

				// Add more bundled notification types as needed

				default:
					// Fallback: send individual notifications
					error_log('[NotificationQueue] No bundle handler for type: ' . $notification_type . ', sending individually');
					$success = true;
					foreach ($queue_items as $item) {
						$success = $success && $this->sendSingleNotification($item);
					}
					return $success;
			}
		} catch (\Exception $e) {
			error_log('[NotificationQueue] Error sending bundled notification: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Mark queue items as sent
	 *
	 * @param array $item_ids Array of queue item IDs
	 * @return bool Success status
	 */
	protected function markAsSent(array $item_ids): bool {
		if (empty($item_ids)) {
			return false;
		}

		global $wpdb;

		$ids_placeholder = implode(',', array_fill(0, count($item_ids), '%d'));

		$result = $wpdb->query($wpdb->prepare(
			"UPDATE {$this->table}
			 SET status = 'sent', processed_at = %s
			 WHERE id IN ({$ids_placeholder})",
			array_merge([current_time('mysql')], $item_ids)
		));

		return $result !== false;
	}

	/**
	 * Mark queue items as failed
	 *
	 * @param array $item_ids Array of queue item IDs
	 * @param string $error_message Error message
	 * @return bool Success status
	 */
	protected function markAsFailed(array $item_ids, string $error_message): bool {
		if (empty($item_ids)) {
			return false;
		}

		global $wpdb;

		$ids_placeholder = implode(',', array_fill(0, count($item_ids), '%d'));

		$result = $wpdb->query($wpdb->prepare(
			"UPDATE {$this->table}
			 SET status = 'failed', processed_at = %s, error_message = %s
			 WHERE id IN ({$ids_placeholder})",
			array_merge([current_time('mysql'), $error_message], $item_ids)
		));

		return $result !== false;
	}

	/**
	 * Get user email address
	 *
	 * @param int $user_id User ID
	 * @param string $user_type User type
	 * @return string|null Email address or null if not found
	 */
	protected function getUserEmail(int $user_id, string $user_type): ?string {
		if ($user_type === 'wordpress') {
			$user = get_user_by('id', $user_id);
			return $user ? $user->user_email : null;
		}

		// Handle custom user types if needed
		return null;
	}

	/**
	 * Clean up old processed queue items
	 *
	 * @param int $days_old Number of days to keep processed items
	 * @return int Number of items deleted
	 */
	public function cleanup(int $days_old = 30): int {
		global $wpdb;

		$cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));

		$deleted = $wpdb->query($wpdb->prepare(
			"DELETE FROM {$this->table}
			 WHERE status IN ('sent', 'failed')
			 AND processed_at < %s",
			$cutoff_date
		));

		return $deleted !== false ? $deleted : 0;
	}

	/**
	 * Get queue statistics
	 *
	 * @return array Statistics about the queue
	 */
	public function getStats(): array {
		global $wpdb;

		$stats = [
			'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'"),
			'processing' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'processing'"),
			'sent' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'sent'"),
			'failed' => $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'failed'"),
			'ready_to_process' => $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table}
				 WHERE status = 'pending' AND scheduled_for <= %s",
				current_time('mysql')
			)),
		];

		return $stats;
	}
}
