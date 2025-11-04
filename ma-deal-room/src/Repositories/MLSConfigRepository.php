<?php
/**
 * MLS Configuration Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\MLSConfig;

class MLSConfigRepository extends BaseRepository {
	protected $table = 'ma_deal_mls_config';
	protected $model_class = MLSConfig::class;

	/**
	 * Allowed columns for queries (SQL injection prevention)
	 *
	 * @var array
	 */
	protected $allowed_columns = [
		'id',
		'account_id',
		'name',
		'provider_type',
		'credentials',
		'settings',
		'is_active',
		'created_at',
		'updated_at'
	];

	/**
	 * Find active MLS configurations for an account
	 *
	 * @param int|null $account_id Account ID
	 * @return array
	 */
	public function findActive(?int $account_id = null): array {
		$conditions = ['is_active' => 1];

		if ($account_id !== null) {
			$conditions['account_id'] = $account_id;
		}

		return $this->query($conditions);
	}

	/**
	 * Find MLS configuration by provider type
	 *
	 * @param string $provider_type Provider type (bridge, mlspin, etc.)
	 * @param int|null $account_id Account ID
	 * @return MLSConfig|null
	 */
	public function findByProvider(string $provider_type, ?int $account_id = null): ?object {
		$conditions = [
			'provider_type' => $provider_type,
			'is_active' => 1
		];

		if ($account_id !== null) {
			$conditions['account_id'] = $account_id;
		}

		$results = $this->query($conditions, 1);
		return !empty($results) ? $results[0] : null;
	}

	/**
	 * Find MLS configuration by name
	 *
	 * @param string $name Configuration name
	 * @param int|null $account_id Account ID
	 * @return MLSConfig|null
	 */
	public function findByName(string $name, ?int $account_id = null): ?object {
		$conditions = ['name' => $name];

		if ($account_id !== null) {
			$conditions['account_id'] = $account_id;
		}

		$results = $this->query($conditions, 1);
		return !empty($results) ? $results[0] : null;
	}
}
