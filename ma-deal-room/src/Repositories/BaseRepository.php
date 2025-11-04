<?php
/**
 * Base Repository
 *
 * @package MADealRoom\Repositories
 * @since 1.0.0
 */

namespace MADealRoom\Repositories;

/**
 * Base repository with common database operations
 */
abstract class BaseRepository {
	/**
	 * WordPress database instance
	 *
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * Table name (without prefix)
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Primary key column name
	 *
	 * @var string
	 */
	protected $primary_key = 'id';

	/**
	 * Model class name
	 *
	 * @var string
	 */
	protected $model_class;

	/**
	 * Allowed column names for this table (override in child classes)
	 * Used to prevent SQL injection in WHERE and ORDER BY clauses
	 *
	 * @var array
	 */
	protected $allowed_columns = [];

	/**
	 * Cache service instance
	 *
	 * @var \MADealRoom\Services\CacheService|null
	 */
	protected $cache_service;

	/**
	 * Whether caching is enabled for this repository
	 *
	 * @var bool
	 */
	protected $cache_enabled = true;

	/**
	 * Cache TTL for single records (in seconds)
	 *
	 * @var int
	 */
	protected $cache_ttl_single = 3600; // 1 hour

	/**
	 * Cache TTL for lists/queries (in seconds)
	 *
	 * @var int
	 */
	protected $cache_ttl_list = 300; // 5 minutes

	/**
	 * Default page size for pagination
	 *
	 * @var int
	 */
	protected $default_per_page = 50;

	/**
	 * Maximum page size for pagination
	 *
	 * @var int
	 */
	protected $max_per_page = 100;

	/**
	 * Constructor
	 *
	 * @param \MADealRoom\Services\CacheService|null $cache_service Optional cache service
	 */
	public function __construct($cache_service = null) {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->cache_service = $cache_service;

		// Set default allowed columns if not specified
		if (empty($this->allowed_columns)) {
			$this->allowed_columns = ['id', 'created_at', 'updated_at'];
		}
	}

	/**
	 * Get table name with prefix
	 *
	 * @return string
	 */
	protected function get_table_name(): string {
		return $this->wpdb->prefix . $this->table;
	}

	/**
	 * Validate column name to prevent SQL injection
	 *
	 * @param string $column Column name to validate
	 * @return string|null Validated column name or null if invalid
	 */
	protected function validate_column(string $column): ?string {
		// Remove any whitespace
		$column = trim($column);

		// Check if column is in allowed list
		if (in_array($column, $this->allowed_columns, true)) {
			return $column;
		}

		// Log potential SQL injection attempt
		error_log('BaseRepository: Invalid column name attempted: ' . $column . ' in table: ' . $this->table);

		return null;
	}

	/**
	 * Find record by ID
	 *
	 * @param int $id Record ID
	 * @param bool $use_cache Whether to use cache (default: auto-detect)
	 * @return object|null Model instance or null
	 */
	public function find(int $id, bool $use_cache = true): ?object {
		// Try to get from cache first
		if ($use_cache && $this->shouldUseCache()) {
			$cache_key = $this->getCacheKey('find', $id);
			$cached = $this->cache_service->get($cache_key);

			if ($cached !== null) {
				return $cached;
			}
		}

		// Fetch from database
		$table = $this->get_table_name();

		$result = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$this->primary_key} = %d",
				$id
			),
			ARRAY_A
		);

		if (!$result) {
			return null;
		}

		$model = $this->model_class ? $this->model_class::fromArray($result) : (object)$result;

		// Cache the result
		if ($use_cache && $this->shouldUseCache()) {
			$cache_key = $this->getCacheKey('find', $id);
			$this->cache_service->set($cache_key, $model, $this->cache_ttl_single);
			$this->cache_service->tag($cache_key, $this->table);
		}

		return $model;
	}

	/**
	 * Get all records
	 *
	 * @param int $limit Limit number of results (max: 100)
	 * @param int $offset Offset for pagination
	 * @param bool $use_cache Whether to use cache (default: auto-detect)
	 * @return array Array of model instances
	 */
	public function findAll(int $limit = 100, int $offset = 0, bool $use_cache = true): array {
		// Enforce maximum limit
		$limit = min($limit, $this->max_per_page);

		// Try to get from cache first
		if ($use_cache && $this->shouldUseCache()) {
			$cache_key = $this->getCacheKey('findAll', $limit, $offset);
			$cached = $this->cache_service->get($cache_key);

			if ($cached !== null) {
				return $cached;
			}
		}

		// Fetch from database
		$table = $this->get_table_name();

		$results = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY {$this->primary_key} DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			),
			ARRAY_A
		);

		$models = $this->hydrate_models($results);

		// Cache the result
		if ($use_cache && $this->shouldUseCache()) {
			$cache_key = $this->getCacheKey('findAll', $limit, $offset);
			$this->cache_service->set($cache_key, $models, $this->cache_ttl_list);
			$this->cache_service->tag($cache_key, $this->table);
		}

		return $models;
	}

	/**
	 * Create a new record
	 *
	 * @param array $data Record data
	 * @return int|false Inserted ID or false on failure
	 */
	public function create(array $data) {
		$table = $this->get_table_name();

		$result = $this->wpdb->insert($table, $data);

		if ($result === false) {
			return false;
		}

		$inserted_id = $this->wpdb->insert_id;

		// Invalidate cache after creating new record
		$this->invalidateCache();

		return $inserted_id;
	}

	/**
	 * Update a record
	 *
	 * @param int $id Record ID
	 * @param array $data Updated data
	 * @return bool Whether the update was successful
	 */
	public function update(int $id, array $data): bool {
		$table = $this->get_table_name();

		$result = $this->wpdb->update(
			$table,
			$data,
			[$this->primary_key => $id]
		);

		if ($result !== false) {
			// Invalidate cache after updating record
			$this->invalidateCache($id);
		}

		return $result !== false;
	}

	/**
	 * Delete a record
	 *
	 * @param int $id Record ID
	 * @return bool Whether the deletion was successful
	 */
	public function delete(int $id): bool {
		$table = $this->get_table_name();

		$result = $this->wpdb->delete(
			$table,
			[$this->primary_key => $id]
		);

		if ($result !== false) {
			// Invalidate cache after deleting record
			$this->invalidateCache($id);
		}

		return $result !== false;
	}

	/**
	 * Query records with conditions
	 *
	 * @param array $conditions Where conditions
	 * @param array $options Query options (order_by, order, limit, offset, use_cache)
	 * @return array Array of model instances
	 */
	public function query(array $conditions = [], array $options = []): array {
		$use_cache = $options['use_cache'] ?? true;

		// Try to get from cache first
		if ($use_cache && $this->shouldUseCache()) {
			$cache_key = $this->getCacheKey('query', $conditions, $options);
			$cached = $this->cache_service->get($cache_key);

			if ($cached !== null) {
				return $cached;
			}
		}

		// Fetch from database
		$table = $this->get_table_name();

		list($where_clause, $where_values) = $this->build_where_clause($conditions);
		$order_clause = $this->build_order_clause($options);

		$sql = "SELECT * FROM {$table}";
		$params = [];

		if (!empty($where_clause)) {
			$sql .= " {$where_clause}";
			$params = array_merge($params, $where_values);
		}

		if (!empty($order_clause)) {
			$sql .= " {$order_clause}";
		}

		// Handle LIMIT and OFFSET
		$limit = $options['limit'] ?? null;
		$offset = $options['offset'] ?? null;

		if ($limit !== null) {
			$sql .= " LIMIT %d";
			$params[] = $limit;
		}

		if ($offset !== null) {
			$sql .= " OFFSET %d";
			$params[] = $offset;
		}

		// Prepare the entire query
		$prepared_sql = $this->wpdb->prepare($sql, ...$params);

		$results = $this->wpdb->get_results($prepared_sql, ARRAY_A);

		$models = $this->hydrate_models($results ?: []);

		// Cache the result
		if ($use_cache && $this->shouldUseCache()) {
			$cache_key = $this->getCacheKey('query', $conditions, $options);
			$this->cache_service->set($cache_key, $models, $this->cache_ttl_list);
			$this->cache_service->tag($cache_key, $this->table);
		}

		return $models;
	}

	/**
	 * Count records with conditions
	 *
	 * @param array $conditions Where conditions
	 * @return int Record count
	 */
	public function count(array $conditions = []): int {
		$table = $this->get_table_name();
		list($where_clause, $where_values) = $this->build_where_clause($conditions);

		$sql = "SELECT COUNT(*) FROM {$table}";
		$params = [];

		if (!empty($where_clause)) {
			$sql .= " {$where_clause}";
			$params = array_merge($params, $where_values);
		}

		$prepared_sql = $this->wpdb->prepare($sql, ...$params);

		return (int) $this->wpdb->get_var($prepared_sql);
	}

	/**
	 * Validate and normalize per_page parameter
	 *
	 * @param int|null $per_page Requested page size
	 * @return int Validated page size
	 */
	protected function validatePerPage(?int $per_page): int {
		// Use default if not specified
		if ($per_page === null || $per_page <= 0) {
			return $this->default_per_page;
		}

		// Enforce maximum
		if ($per_page > $this->max_per_page) {
			return $this->max_per_page;
		}

		return $per_page;
	}

	/**
	 * Get paginated results with metadata
	 *
	 * @param array $conditions Where conditions
	 * @param array $options Query options (order_by, order, page, per_page, use_cache)
	 * @return array Paginated results with metadata
	 */
	public function paginate(array $conditions = [], array $options = []): array {
		// Extract pagination parameters
		$page = isset($options['page']) ? max(1, (int) $options['page']) : 1;
		$per_page = $this->validatePerPage($options['per_page'] ?? null);

		// Get total count
		$total = $this->count($conditions);

		// Calculate pagination metadata
		$last_page = max(1, (int) ceil($total / $per_page));
		$page = min($page, $last_page); // Don't exceed last page
		$offset = ($page - 1) * $per_page;
		$from = $total > 0 ? $offset + 1 : 0;
		$to = min($offset + $per_page, $total);

		// Update options with calculated values
		$options['limit'] = $per_page;
		$options['offset'] = $offset;

		// Get the data
		$data = $this->query($conditions, $options);

		return [
			'data' => $data,
			'pagination' => [
				'total' => $total,
				'per_page' => $per_page,
				'current_page' => $page,
				'last_page' => $last_page,
				'from' => $from,
				'to' => $to,
				'has_more' => $page < $last_page,
			],
		];
	}

	/**
	 * Build WHERE clause from conditions
	 *
	 * @param array $conditions Conditions array
	 * @return array WHERE clause and values array
	 */
	protected function build_where_clause(array $conditions): array {
		if (empty($conditions)) {
			return ['', []];
		}

		$where_parts = [];
		$values = [];

		foreach ($conditions as $column => $value) {
			// Validate column name to prevent SQL injection
			$validated_column = $this->validate_column($column);
			if ($validated_column === null) {
				// Skip invalid columns, don't add to WHERE clause
				error_log('BaseRepository: Skipping invalid column in WHERE: ' . $column);
				continue;
			}

			if (is_array($value)) {
				// IN clause
				$placeholders = implode(',', array_fill(0, count($value), '%s'));
				$where_parts[] = "{$validated_column} IN ({$placeholders})";
				$values = array_merge($values, $value);
			} elseif ($value === null) {
				$where_parts[] = "{$validated_column} IS NULL";
			} else {
				$where_parts[] = "{$validated_column} = %s";
				$values[] = $value;
			}
		}

		if (empty($where_parts)) {
			return ['', []];
		}

		return ['WHERE ' . implode(' AND ', $where_parts), $values];
	}

	/**
	 * Build ORDER BY clause
	 *
	 * @param array $options Query options
	 * @return string ORDER BY clause
	 */
	protected function build_order_clause(array $options): string {
		if (!isset($options['order_by'])) {
			return '';
		}

		// Validate column name to prevent SQL injection
		$validated_column = $this->validate_column($options['order_by']);
		if ($validated_column === null) {
			// Fall back to primary key if invalid column
			error_log('BaseRepository: Invalid ORDER BY column: ' . $options['order_by'] . ', using primary key instead');
			$validated_column = $this->primary_key;
		}

		$order = isset($options['order']) && strtoupper($options['order']) === 'ASC' ? 'ASC' : 'DESC';

		return "ORDER BY {$validated_column} {$order}";
	}

	/**
	 * Hydrate array of results into model instances
	 *
	 * @param array $results Database results
	 * @return array Model instances
	 */
	protected function hydrate_models(array $results): array {
		if (!$this->model_class) {
			return array_map(fn($row) => (object)$row, $results);
		}

		return array_map(
			fn($row) => $this->model_class::fromArray($row),
			$results
		);
	}

	/**
	 * Get last error
	 *
	 * @return string
	 */
	public function get_last_error(): string {
		return $this->wpdb->last_error;
	}

	/**
	 * Generate cache key for a record or query
	 *
	 * @param string $operation Operation type (find, findAll, query, count)
	 * @param mixed ...$params Parameters for the operation
	 * @return string Cache key
	 */
	protected function getCacheKey(string $operation, ...$params): string {
		$key_parts = [$this->table, $operation];

		foreach ($params as $param) {
			if (is_array($param)) {
				$key_parts[] = md5(json_encode($param));
			} else {
				$key_parts[] = $param;
			}
		}

		return implode('_', $key_parts);
	}

	/**
	 * Invalidate all cache entries for this repository
	 *
	 * @param int|null $id Optional specific record ID to invalidate
	 * @return bool Whether cache was invalidated
	 */
	protected function invalidateCache(?int $id = null): bool {
		// If cache service is available, use it
		if ($this->cache_service && $this->cache_enabled) {
			// Invalidate specific record cache
			if ($id !== null) {
				$cache_key = $this->getCacheKey('find', $id);
				$this->cache_service->delete($cache_key);
			}

			// Invalidate using tag (will clear all cached queries for this table)
			$this->cache_service->flushTag($this->table);
		}

		// FALLBACK: Always clear WordPress transients for this table as a safety net
		// This ensures cache invalidation works even if cache service is unavailable
		global $wpdb;
		$table_pattern = '%_transient_ma_deal_' . $this->table . '%';
		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE %s",
			$table_pattern
		));

		return true;
	}

	/**
	 * Check if caching should be used for current request
	 * Admins get fresh data, regular users get cached data
	 *
	 * @return bool Whether to use cache
	 */
	protected function shouldUseCache(): bool {
		if (!$this->cache_service || !$this->cache_enabled) {
			return false;
		}

		// Check if current user is admin (bypass cache for fresh data)
		// This requires WordPress functions, so we check if they exist
		if (function_exists('current_user_can') && current_user_can('manage_options')) {
			return false;
		}

		return true;
	}
}
