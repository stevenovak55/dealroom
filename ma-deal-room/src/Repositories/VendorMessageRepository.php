<?php
/**
 * VendorMessage Repository
 *
 * @package MADealRoom\Repositories
 * @since 2.1.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\VendorMessage;

class VendorMessageRepository extends BaseRepository {
	protected $table = 'ma_deal_vendor_messages';
	protected $model_class = VendorMessage::class;
	protected $allowed_columns = [
		'id', 'vendor_request_id', 'transaction_id', 'sender_type',
		'sender_name', 'sender_email', 'message', 'is_read',
		'read_at', 'created_at', 'updated_at'
	];

	/**
	 * Get all messages for a vendor request
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param array $options Query options (limit, offset, order)
	 * @return array
	 */
	public function getByVendorRequest(int $vendor_request_id, array $options = []): array {
		$defaults = [
			'order' => 'created_at',
			'direction' => 'ASC',
		];
		$options = array_merge($defaults, $options);

		return $this->query([
			'vendor_request_id' => $vendor_request_id,
		], $options);
	}

	/**
	 * Get unread messages for a vendor request
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param string $recipient_type 'vendor' or 'agent' (who should read them)
	 * @return array
	 */
	public function getUnreadMessages(int $vendor_request_id, string $recipient_type = 'vendor'): array {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		// If recipient is vendor, get unread messages from agent (and vice versa)
		$sender_type = $recipient_type === 'vendor' ? 'agent' : 'vendor';

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE vendor_request_id = %d
			AND sender_type = %s
			AND is_read = 0
			ORDER BY created_at ASC",
			$vendor_request_id,
			$sender_type
		);

		$results = $wpdb->get_results($sql, ARRAY_A);
		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Get unread count for a vendor request
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param string $recipient_type 'vendor' or 'agent'
	 * @return int
	 */
	public function getUnreadCount(int $vendor_request_id, string $recipient_type = 'vendor'): int {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$sender_type = $recipient_type === 'vendor' ? 'agent' : 'vendor';

		return (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$table}
			WHERE vendor_request_id = %d
			AND sender_type = %s
			AND is_read = 0",
			$vendor_request_id,
			$sender_type
		));
	}

	/**
	 * Mark message as read
	 *
	 * @param int $id Message ID
	 * @return bool
	 */
	public function markAsRead(int $id): bool {
		return $this->update($id, [
			'is_read' => 1,
			'read_at' => current_time('mysql'),
		]);
	}

	/**
	 * Mark all messages as read for a vendor request
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @param string $recipient_type 'vendor' or 'agent'
	 * @return bool
	 */
	public function markAllAsRead(int $vendor_request_id, string $recipient_type = 'vendor'): bool {
		global $wpdb;
		$table = $wpdb->prefix . $this->table;

		$sender_type = $recipient_type === 'vendor' ? 'agent' : 'vendor';

		$result = $wpdb->query($wpdb->prepare(
			"UPDATE {$table}
			SET is_read = 1, read_at = %s
			WHERE vendor_request_id = %d
			AND sender_type = %s
			AND is_read = 0",
			current_time('mysql'),
			$vendor_request_id,
			$sender_type
		));

		return $result !== false;
	}

	/**
	 * Create a new message
	 *
	 * @param array $data Message data
	 * @return int|false Message ID or false on failure
	 */
	public function createMessage(array $data) {
		$required = ['vendor_request_id', 'transaction_id', 'sender_type', 'sender_name', 'sender_email', 'message'];
		foreach ($required as $field) {
			if (!isset($data[$field])) {
				return false;
			}
		}

		return $this->create($data);
	}

	/**
	 * Get message thread for display
	 *
	 * @param int $vendor_request_id Vendor request ID
	 * @return array Formatted thread with metadata
	 */
	public function getThreadWithMetadata(int $vendor_request_id): array {
		$messages = $this->getByVendorRequest($vendor_request_id);

		return [
			'messages' => $messages,
			'total_count' => count($messages),
			'unread_vendor' => $this->getUnreadCount($vendor_request_id, 'vendor'),
			'unread_agent' => $this->getUnreadCount($vendor_request_id, 'agent'),
		];
	}
}
