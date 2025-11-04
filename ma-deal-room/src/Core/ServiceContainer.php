<?php
/**
 * Service Container for Dependency Injection
 *
 * @package MADealRoom\Core
 * @since 1.0.0
 */

namespace MADealRoom\Core;

use Exception;

/**
 * Simple service container for dependency injection
 */
class ServiceContainer {
	/**
	 * Container instance
	 *
	 * @var ServiceContainer
	 */
	private static $instance = null;

	/**
	 * Registered services
	 *
	 * @var array
	 */
	private $services = [];

	/**
	 * Resolved service instances
	 *
	 * @var array
	 */
	private $instances = [];

	/**
	 * Private constructor for singleton
	 */
	private function __construct() {}

	/**
	 * Get container instance
	 *
	 * @return ServiceContainer
	 */
	public static function instance(): ServiceContainer {
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register a service
	 *
	 * @param string $name Service name
	 * @param callable $resolver Resolver function
	 * @param bool $singleton Whether to return same instance every time
	 * @return void
	 */
	public function register(string $name, callable $resolver, bool $singleton = true): void {
		$this->services[$name] = [
			'resolver' => $resolver,
			'singleton' => $singleton,
		];
	}

	/**
	 * Get a service from the container
	 *
	 * @param string $name Service name
	 * @return mixed Service instance
	 * @throws Exception If service not found
	 */
	public function get(string $name) {
		if (!isset($this->services[$name])) {
			throw new Exception("Service '{$name}' not found in container");
		}

		$service = $this->services[$name];

		// Return cached instance if singleton
		if ($service['singleton'] && isset($this->instances[$name])) {
			return $this->instances[$name];
		}

		// Resolve service
		$instance = call_user_func($service['resolver'], $this);

		// Cache if singleton
		if ($service['singleton']) {
			$this->instances[$name] = $instance;
		}

		return $instance;
	}

	/**
	 * Check if service exists
	 *
	 * @param string $name Service name
	 * @return bool
	 */
	public function has(string $name): bool {
		return isset($this->services[$name]);
	}

	/**
	 * Remove a service from the container
	 *
	 * @param string $name Service name
	 * @return void
	 */
	public function remove(string $name): void {
		unset($this->services[$name]);
		unset($this->instances[$name]);
	}

	/**
	 * Clear all services
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->services = [];
		$this->instances = [];
	}
}
