<?php
/**
 * WordPress Hooks Manager
 *
 * @package MADealRoom\Core
 * @since 1.0.0
 */

namespace MADealRoom\Core;

/**
 * Centralized hook management
 */
class Hooks {
	/**
	 * Registered actions
	 *
	 * @var array
	 */
	private $actions = [];

	/**
	 * Registered filters
	 *
	 * @var array
	 */
	private $filters = [];

	/**
	 * Add an action hook
	 *
	 * @param string $hook Hook name
	 * @param callable $callback Callback function
	 * @param int $priority Priority (default 10)
	 * @param int $accepted_args Number of arguments (default 1)
	 * @return void
	 */
	public function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void {
		add_action($hook, $callback, $priority, $accepted_args);

		$this->actions[] = [
			'hook' => $hook,
			'callback' => $callback,
			'priority' => $priority,
			'accepted_args' => $accepted_args,
		];
	}

	/**
	 * Add a filter hook
	 *
	 * @param string $hook Hook name
	 * @param callable $callback Callback function
	 * @param int $priority Priority (default 10)
	 * @param int $accepted_args Number of arguments (default 1)
	 * @return void
	 */
	public function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void {
		add_filter($hook, $callback, $priority, $accepted_args);

		$this->filters[] = [
			'hook' => $hook,
			'callback' => $callback,
			'priority' => $priority,
			'accepted_args' => $accepted_args,
		];
	}

	/**
	 * Remove an action hook
	 *
	 * @param string $hook Hook name
	 * @param callable $callback Callback function
	 * @param int $priority Priority (default 10)
	 * @return bool Whether the hook was removed
	 */
	public function remove_action(string $hook, callable $callback, int $priority = 10): bool {
		return remove_action($hook, $callback, $priority);
	}

	/**
	 * Remove a filter hook
	 *
	 * @param string $hook Hook name
	 * @param callable $callback Callback function
	 * @param int $priority Priority (default 10)
	 * @return bool Whether the hook was removed
	 */
	public function remove_filter(string $hook, callable $callback, int $priority = 10): bool {
		return remove_filter($hook, $callback, $priority);
	}

	/**
	 * Get all registered actions
	 *
	 * @return array
	 */
	public function get_actions(): array {
		return $this->actions;
	}

	/**
	 * Get all registered filters
	 *
	 * @return array
	 */
	public function get_filters(): array {
		return $this->filters;
	}

	/**
	 * Remove all registered hooks
	 *
	 * @return void
	 */
	public function remove_all(): void {
		foreach ($this->actions as $action) {
			remove_action($action['hook'], $action['callback'], $action['priority']);
		}

		foreach ($this->filters as $filter) {
			remove_filter($filter['hook'], $filter['callback'], $filter['priority']);
		}

		$this->actions = [];
		$this->filters = [];
	}
}
