<?php
/**
 * Event Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Event;

class EventRepository extends BaseRepository {
	protected $table = 'ma_deal_events';
	protected $model_class = Event::class;

	protected $allowed_columns = [
		'id',
		'account_id',
		'transaction_id',
		'entity_type',
		'entity_id',
		'event_type',
		'user_id',
		'old_data',
		'new_data',
		'ip_address',
		'user_agent',
		'created_at',
	];

	public function findByTransaction(int $transaction_id, int $limit = 100): array {
		return $this->query(
			['transaction_id' => $transaction_id],
			['order_by' => 'created_at', 'order' => 'DESC', 'limit' => $limit]
		);
	}

	public function findByEntity(string $entity_type, int $entity_id): array {
		return $this->query(
			['entity_type' => $entity_type, 'entity_id' => $entity_id],
			['order_by' => 'created_at', 'order' => 'DESC']
		);
	}

	public function logEvent(array $data): int {
		// Add current user and request info
		$data['user_id'] = $data['user_id'] ?? get_current_user_id();
		$data['ip_address'] = $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
		$data['user_agent'] = $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null;

		return $this->create($data);
	}
}
