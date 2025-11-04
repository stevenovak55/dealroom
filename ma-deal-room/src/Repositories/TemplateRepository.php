<?php
/**
 * Template Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

use MADealRoom\Models\Template;

class TemplateRepository extends BaseRepository {
	protected $table = 'ma_deal_templates';
	protected $model_class = Template::class;

	/**
	 * Allowed columns for queries (SQL injection prevention)
	 *
	 * @var array
	 */
	protected $allowed_columns = [
		'id',
		'account_id',
		'name',
		'description',
		'property_type',
		'transaction_side',
		'template_yaml',
		'is_system',
		'is_active',
		'version',
		'created_by_user_id',
		'created_at',
		'updated_at'
	];

	public function findActive(?int $account_id = null): array {
		$table = $this->get_table_name();

		// Get both system templates (account_id IS NULL, is_system = 1)
		// AND account-specific templates if account_id is provided
		if ($account_id !== null) {
			$sql = $this->wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE is_active = 1
				AND (is_system = 1 OR account_id = %d)
				ORDER BY is_system DESC, created_at DESC",
				$account_id
			);
		} else {
			$sql = "SELECT * FROM {$table}
				WHERE is_active = 1
				ORDER BY is_system DESC, created_at DESC";
		}

		$results = $this->wpdb->get_results($sql, ARRAY_A);
		return $this->hydrate_models($results ?: []);
	}

	public function findSystem(): array {
		return $this->query(['is_system' => 1, 'is_active' => 1]);
	}

	public function findByPropertyType(string $property_type, ?int $account_id = null): array {
		$table = $this->get_table_name();
		$account_filter = $account_id ? $this->wpdb->prepare('AND account_id = %d', $account_id) : '';

		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$table}
			WHERE is_active = 1
			AND (property_type = %s OR property_type = 'Any')
			{$account_filter}
			ORDER BY is_system DESC, created_at DESC",
			$property_type
		);

		$results = $this->wpdb->get_results($sql, ARRAY_A);
		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Find templates by property type and transaction side with smart filtering
	 *
	 * @param array $filters Array of filters: property_type, transaction_side, account_id
	 * @return array Array of Template models
	 */
	public function findByFilters(array $filters): array {
		$table = $this->get_table_name();
		$where_clauses = ['is_active = 1'];
		$prepare_values = [];

		// Property type filter (match specific type OR 'Any')
		if (!empty($filters['property_type'])) {
			$where_clauses[] = "(property_type = %s OR property_type = 'Any')";
			$prepare_values[] = $filters['property_type'];
		}

		// Transaction side filter (match specific side OR 'both')
		if (!empty($filters['transaction_side'])) {
			$where_clauses[] = "(transaction_side = %s OR transaction_side = 'both')";
			$prepare_values[] = $filters['transaction_side'];
		}

		// Account filter (system templates OR account-specific)
		if (!empty($filters['account_id'])) {
			$where_clauses[] = "(is_system = 1 OR account_id = %d)";
			$prepare_values[] = $filters['account_id'];
		} else {
			// If no account specified, only show system templates
			$where_clauses[] = "is_system = 1";
		}

		$where_sql = implode(' AND ', $where_clauses);

		if (!empty($prepare_values)) {
			$sql = $this->wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE {$where_sql}
				ORDER BY is_system DESC, created_at DESC",
				...$prepare_values
			);
		} else {
			$sql = "SELECT * FROM {$table}
				WHERE {$where_sql}
				ORDER BY is_system DESC, created_at DESC";
		}

		$results = $this->wpdb->get_results($sql, ARRAY_A);
		return $this->hydrate_models($results ?: []);
	}

	/**
	 * Get count of templates matching filters
	 *
	 * @param array $filters Array of filters
	 * @return int Count of matching templates
	 */
	public function countByFilters(array $filters): int {
		$table = $this->get_table_name();
		$where_clauses = ['is_active = 1'];
		$prepare_values = [];

		if (!empty($filters['property_type'])) {
			$where_clauses[] = "(property_type = %s OR property_type = 'Any')";
			$prepare_values[] = $filters['property_type'];
		}

		if (!empty($filters['transaction_side'])) {
			$where_clauses[] = "(transaction_side = %s OR transaction_side = 'both')";
			$prepare_values[] = $filters['transaction_side'];
		}

		if (!empty($filters['account_id'])) {
			$where_clauses[] = "(is_system = 1 OR account_id = %d)";
			$prepare_values[] = $filters['account_id'];
		} else {
			$where_clauses[] = "is_system = 1";
		}

		$where_sql = implode(' AND ', $where_clauses);

		if (!empty($prepare_values)) {
			$sql = $this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$where_sql}",
				...$prepare_values
			);
		} else {
			$sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		}

		return (int) $this->wpdb->get_var($sql);
	}
}
