<?php
/**
 * Reminder Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Reminder;

class ReminderRepository extends BaseRepository {
	protected $table = 'ma_deal_reminders';
	protected $model_class = Reminder::class;

	public function findPending(int $limit = 100): array {
		$table = $this->get_table_name();

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE status = 'pending'
				AND scheduled_at <= NOW()
				ORDER BY scheduled_at ASC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return $this->hydrate_models($results ?: []);
	}

	public function findByTask(int $task_id): array {
		return $this->query(['task_id' => $task_id], ['order_by' => 'scheduled_at']);
	}

	public function markSent(int $reminder_id): bool {
		return $this->update($reminder_id, [
			'status' => 'sent',
			'sent_at' => current_time('mysql'),
		]);
	}

	public function markFailed(int $reminder_id, string $reason): bool {
		return $this->update($reminder_id, [
			'status' => 'failed',
			'failure_reason' => $reason,
		]);
	}

	/**
	 * Find failed reminders eligible for retry with exponential backoff
	 *
	 * @param int $limit Number of reminders to retrieve
	 * @return array Array of Reminder model instances
	 */
	public function findFailedEligibleForRetry(int $limit = 50): array {
		$table = $this->get_table_name();

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT r.*,
				       POW(2, r.retry_count) AS backoff_minutes,
				       TIMESTAMPDIFF(MINUTE, r.updated_at, NOW()) AS minutes_since_failure
				FROM {$table} r
				WHERE r.status = 'failed'
				  AND r.retry_count < r.max_retries
				  AND TIMESTAMPDIFF(MINUTE, r.updated_at, NOW()) >= POW(2, r.retry_count)
				ORDER BY r.updated_at ASC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Increment retry count for a reminder
	 *
	 * @param int $reminder_id Reminder ID
	 * @return bool
	 */
	public function incrementRetryCount(int $reminder_id): bool {
		$table = $this->get_table_name();
		$result = $this->wpdb->query(
			$this->wpdb->prepare(
				"UPDATE {$table} SET retry_count = retry_count + 1, updated_at = CURRENT_TIMESTAMP WHERE id = %d",
				$reminder_id
			)
		);

		return $result !== false;
	}
}
