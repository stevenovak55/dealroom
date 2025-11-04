<?php
/**
 * Cache Service
 *
 * Provides caching functionality with Redis support and WordPress transients fallback.
 * Implements cache tagging, TTL management, and statistics tracking.
 *
 * @package MA_Deal_Room
 * @subpackage Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use Redis;
use RedisException;

/**
 * CacheService Class
 *
 * Handles all caching operations with automatic fallback from Redis to WordPress transients.
 */
class CacheService {
	/**
	 * Redis instance
	 *
	 * @var Redis|null
	 */
	protected $redis = null;

	/**
	 * Whether Redis is available
	 *
	 * @var bool
	 */
	protected $redis_available = false;

	/**
	 * Cache prefix for all keys
	 *
	 * @var string
	 */
	protected $prefix = 'ma_deal_';

	/**
	 * Statistics tracking
	 *
	 * @var array
	 */
	protected $stats = [
		'hits'   => 0,
		'misses' => 0,
		'sets'   => 0,
		'deletes' => 0,
	];

	/**
	 * Default TTL in seconds (1 hour)
	 *
	 * @var int
	 */
	protected $default_ttl = 3600;

	/**
	 * Constructor
	 *
	 * Initializes Redis connection or falls back to WordPress transients.
	 */
	public function __construct() {
		$this->connect();
	}

	/**
	 * Connect to Redis
	 *
	 * @return bool True if connected, false otherwise
	 */
	protected function connect(): bool {
		// Check if Redis extension is loaded
		if (!extension_loaded('redis')) {
			error_log('MA Deal Room: Redis PHP extension not loaded, using WordPress transients');
			$this->redis_available = false;
			return false;
		}

		try {
			$this->redis = new Redis();

			$host = getenv('REDIS_HOST') ?: 'redis';
			$port = (int) (getenv('REDIS_PORT') ?: 6379);
			$timeout = 2.5; // 2.5 seconds connection timeout

			$connected = $this->redis->connect($host, $port, $timeout);

			if (!$connected) {
				error_log("MA Deal Room: Could not connect to Redis at {$host}:{$port}");
				$this->redis_available = false;
				return false;
			}

			// Test the connection
			if (!$this->redis->ping()) {
				error_log('MA Deal Room: Redis ping failed');
				$this->redis_available = false;
				return false;
			}

			// Set key prefix for all operations
			$this->redis->setOption(Redis::OPT_PREFIX, $this->prefix);

			$this->redis_available = true;
			error_log('MA Deal Room: Successfully connected to Redis');
			return true;

		} catch (RedisException $e) {
			error_log('MA Deal Room: Redis connection failed: ' . $e->getMessage());
			$this->redis_available = false;
			return false;
		}
	}

	/**
	 * Get a value from cache
	 *
	 * @param string $key Cache key
	 * @param mixed $default Default value if not found
	 * @return mixed Cached value or default
	 */
	public function get(string $key, $default = null) {
		if ($this->redis_available) {
			try {
				$value = $this->redis->get($key);

				if ($value === false) {
					$this->stats['misses']++;
					return $default;
				}

				$this->stats['hits']++;
				return $this->unserialize($value);

			} catch (RedisException $e) {
				error_log('MA Deal Room: Redis get failed: ' . $e->getMessage());
				// Fall through to transients
			}
		}

		// Fallback to WordPress transients
		$value = get_transient($this->prefix . $key);

		if ($value === false) {
			$this->stats['misses']++;
			return $default;
		}

		$this->stats['hits']++;
		return $value;
	}

	/**
	 * Set a value in cache
	 *
	 * @param string $key Cache key
	 * @param mixed $value Value to cache
	 * @param int|null $ttl Time to live in seconds (null = default TTL)
	 * @return bool True on success, false on failure
	 */
	public function set(string $key, $value, ?int $ttl = null): bool {
		$ttl = $ttl ?? $this->default_ttl;
		$this->stats['sets']++;

		if ($this->redis_available) {
			try {
				$serialized = $this->serialize($value);
				$result = $this->redis->setex($key, $ttl, $serialized);
				return $result !== false;

			} catch (RedisException $e) {
				error_log('MA Deal Room: Redis set failed: ' . $e->getMessage());
				// Fall through to transients
			}
		}

		// Fallback to WordPress transients
		return set_transient($this->prefix . $key, $value, $ttl);
	}

	/**
	 * Delete a value from cache
	 *
	 * @param string $key Cache key
	 * @return bool True on success, false on failure
	 */
	public function delete(string $key): bool {
		$this->stats['deletes']++;

		if ($this->redis_available) {
			try {
				$result = $this->redis->del($key);
				// Also delete from transients for safety
				delete_transient($this->prefix . $key);
				return $result > 0;

			} catch (RedisException $e) {
				error_log('MA Deal Room: Redis delete failed: ' . $e->getMessage());
				// Fall through to transients
			}
		}

		// Fallback to WordPress transients
		return delete_transient($this->prefix . $key);
	}

	/**
	 * Check if a key exists in cache
	 *
	 * @param string $key Cache key
	 * @return bool True if exists, false otherwise
	 */
	public function has(string $key): bool {
		if ($this->redis_available) {
			try {
				return $this->redis->exists($key) > 0;
			} catch (RedisException $e) {
				error_log('MA Deal Room: Redis exists failed: ' . $e->getMessage());
				// Fall through to transients
			}
		}

		// Fallback to WordPress transients
		return get_transient($this->prefix . $key) !== false;
	}

	/**
	 * Get value from cache or store it if not found
	 *
	 * @param string $key Cache key
	 * @param callable $callback Callback to generate value if not cached
	 * @param int|null $ttl Time to live in seconds (null = default TTL)
	 * @return mixed Cached or generated value
	 */
	public function remember(string $key, callable $callback, ?int $ttl = null) {
		$value = $this->get($key);

		if ($value !== null) {
			return $value;
		}

		$value = $callback();
		$this->set($key, $value, $ttl);

		return $value;
	}

	/**
	 * Flush all cache entries
	 *
	 * @return bool True on success, false on failure
	 */
	public function flush(): bool {
		if ($this->redis_available) {
			try {
				// Flush only keys with our prefix
				$pattern = $this->prefix . '*';
				$keys = $this->redis->keys($pattern);

				if (!empty($keys)) {
					$this->redis->del($keys);
				}

				return true;

			} catch (RedisException $e) {
				error_log('MA Deal Room: Redis flush failed: ' . $e->getMessage());
				// Fall through to transients
			}
		}

		// Fallback: Delete WordPress transients with our prefix
		global $wpdb;

		// Check if WordPress is available
		if (!isset($wpdb) || !$wpdb) {
			// If WordPress not available, we can't flush transients
			// This is okay for standalone testing
			error_log('MA Deal Room: Cannot flush transients, WordPress $wpdb not available');
			return false;
		}

		$pattern = $wpdb->esc_like('_transient_' . $this->prefix) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$pattern,
				$wpdb->esc_like('_transient_timeout_' . $this->prefix) . '%'
			)
		);

		return true;
	}

	/**
	 * Tag a cache key
	 *
	 * @param string $key Cache key
	 * @param string|array $tags Tag(s) to associate with the key
	 * @return bool True on success, false on failure
	 */
	public function tag(string $key, $tags): bool {
		$tags = (array) $tags;

		if ($this->redis_available) {
			try {
				foreach ($tags as $tag) {
					$tag_key = "tag:{$tag}";
					$this->redis->sAdd($tag_key, $key);
					// Tags don't expire, but you could set a long TTL if needed
				}
				return true;

			} catch (RedisException $e) {
				error_log('MA Deal Room: Redis tag failed: ' . $e->getMessage());
				return false;
			}
		}

		// Transients don't support tagging well, so we'll skip it
		return false;
	}

	/**
	 * Flush all cache entries with a specific tag
	 *
	 * @param string $tag Tag to flush
	 * @return bool True on success, false on failure
	 */
	public function flushTag(string $tag): bool {
		if (!$this->redis_available) {
			return false;
		}

		try {
			$tag_key = "tag:{$tag}";
			$keys = $this->redis->sMembers($tag_key);

			if (!empty($keys)) {
				$this->redis->del($keys);
				$this->redis->del($tag_key);
			}

			return true;

		} catch (RedisException $e) {
			error_log('MA Deal Room: Redis flush tag failed: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Statistics array with hits, misses, sets, deletes, hit_rate
	 */
	public function getStats(): array {
		$total = $this->stats['hits'] + $this->stats['misses'];
		$hit_rate = $total > 0 ? round(($this->stats['hits'] / $total) * 100, 2) : 0;

		$stats = [
			'hits'       => $this->stats['hits'],
			'misses'     => $this->stats['misses'],
			'sets'       => $this->stats['sets'],
			'deletes'    => $this->stats['deletes'],
			'hit_rate'   => $hit_rate . '%',
			'backend'    => $this->redis_available ? 'Redis' : 'WordPress Transients',
		];

		// Add Redis-specific stats if available
		if ($this->redis_available) {
			try {
				$info = $this->redis->info();
				$stats['redis_version'] = $info['redis_version'] ?? 'unknown';
				$stats['redis_memory'] = $info['used_memory_human'] ?? 'unknown';
				$stats['redis_keys'] = $this->redis->dbSize();
			} catch (RedisException $e) {
				// Ignore
			}
		}

		return $stats;
	}

	/**
	 * Check if Redis is available
	 *
	 * @return bool True if Redis is available, false otherwise
	 */
	public function isRedisAvailable(): bool {
		return $this->redis_available;
	}

	/**
	 * Get the underlying Redis instance
	 *
	 * @return Redis|null Redis instance or null if not available
	 */
	public function getRedis(): ?Redis {
		return $this->redis;
	}

	/**
	 * Serialize a value for storage
	 *
	 * @param mixed $value Value to serialize
	 * @return string Serialized value
	 */
	protected function serialize($value): string {
		// Use JSON for simple types, PHP serialize for complex types
		if (is_string($value) || is_numeric($value) || is_bool($value)) {
			return json_encode($value);
		}

		return serialize($value);
	}

	/**
	 * Unserialize a value from storage
	 *
	 * @param string $value Serialized value
	 * @return mixed Unserialized value
	 */
	protected function unserialize(string $value) {
		// Try JSON first
		$json = json_decode($value, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			return $json;
		}

		// Fall back to PHP unserialize
		return unserialize($value);
	}

	/**
	 * Destructor
	 *
	 * Closes Redis connection if open
	 */
	public function __destruct() {
		if ($this->redis_available && $this->redis) {
			try {
				$this->redis->close();
			} catch (RedisException $e) {
				// Ignore
			}
		}
	}
}
