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
	protected $allowed_columns = [
		'id', 'task_id', 'transaction_id', 'party_id', 'vendor_type',
		'vendor_name', 'vendor_company', 'average_rating', 'vendor_email',
		'vendor_phone', 'token', 'token_expires_at', 'status',
		'scheduled_date', 'scheduled_time', 'completion_date',
		'completion_notes', 'document_url', 'last_opened_at',
		'confirmation_sent_at', 'metadata', 'created_at', 'updated_at'
	];

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
