<?php
/**
 * Party Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Party;

class PartyRepository extends BaseRepository {
	protected $table = 'ma_deal_parties';
	protected $model_class = Party::class;

	/**
	 * Allowed columns for queries (SQL injection prevention)
	 *
	 * @var array
	 */
	protected $allowed_columns = [
		'id',
		'transaction_id',
		'role',
		'company_name',
		'contact_name',
		'email',
		'phone',
		'address',
		'metadata',
		'created_at',
		'updated_at'
	];

	public function findByTransaction(int $transaction_id): array {
		return $this->query(['transaction_id' => $transaction_id]);
	}

	public function findByRole(int $transaction_id, string $role): ?object {
		$results = $this->query(['transaction_id' => $transaction_id, 'role' => $role], ['limit' => 1]);
		return $results[0] ?? null;
	}
}
