<?php
/**
 * Notification Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Notification;

class NotificationRepository extends BaseRepository {
	protected $table = 'ma_deal_notifications';
	protected $model_class = Notification::class;

	/**
	 * Allowed columns for queries (SQL injection prevention)
	 *
	 * @var array
	 */
	protected $allowed_columns = [
		'id',
		'user_id',
		'user_type',
		'type',
		'title',
		'message',
		'link',
		'entity_type',
		'entity_id',
		'is_read',
		'read_at',
		'created_at'
	];

	/**
	 * Find notifications for a user
	 *
	 * @param int $user_id User ID
	 * @param bool $unread_only Only get unread notifications
	 * @param int $limit Limit results
	 * @return array
	 */
	public function findByUser(int $user_id, bool $unread_only = false, int $limit = 50): array {
		$conditions = ['user_id' => $user_id];

		if ($unread_only) {
			$conditions['is_read'] = false;
		}

		return $this->query($conditions, [
			'order_by' => 'created_at',
			'order' => 'DESC',
			'limit' => $limit
		]);
	}

	/**
	 * Mark notification as read
	 *
	 * @param int $id Notification ID
	 * @return bool
	 */
	public function markAsRead(int $id): bool {
		return $this->update($id, [
			'is_read' => true,
			'read_at' => current_time('mysql')
		]);
	}

	/**
	 * Mark all notifications as read for a user
	 *
	 * @param int $user_id User ID
	 * @return bool
	 */
	public function markAllAsRead(int $user_id): bool {
		$table = $this->get_table_name();
		$result = $this->wpdb->update(
			$table,
			[
				'is_read' => true,
				'read_at' => current_time('mysql')
			],
			['user_id' => $user_id, 'is_read' => false]
		);
		return $result !== false;
	}

	/**
	 * Get unread count for a user
	 *
	 * @param int $user_id User ID
	 * @return int
	 */
	public function getUnreadCount(int $user_id): int {
		return $this->count([
			'user_id' => $user_id,
			'is_read' => false
		]);
	}

	/**
	 * Delete old read notifications
	 *
	 * @param int $days_old Delete notifications older than this many days
	 * @return int Number of deleted notifications
	 */
	public function deleteOldRead(int $days_old = 30): int {
		$table = $this->get_table_name();
		$date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));

		$result = $this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$table} WHERE is_read = 1 AND read_at < %s",
				$date
			)
		);

		return $result ?: 0;
	}
}
