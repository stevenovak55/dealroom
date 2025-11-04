<?php
/**
 * VendorRequest Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\VendorRequest;

class VendorRequestRepository extends BaseRepository {
	protected $table = 'ma_deal_vendor_requests';
	protected $model_class = VendorRequest::class;

	public function findByToken(string $token): ?object {
		$results = $this->query(['token' => $token], ['limit' => 1]);
		return $results[0] ?? null;
	}

	public function findByTask(int $task_id): array {
		return $this->query(['task_id' => $task_id]);
	}

	public function updateStatus(int $id, string $status): bool {
		return $this->update($id, ['status' => $status]);
	}
}
