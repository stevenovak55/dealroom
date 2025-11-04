<?php
/**
 * Monitoring Service
 *
 * Handles error monitoring, performance tracking, and logging using Sentry.
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

use Sentry\ClientBuilder;
use Sentry\SentrySdk;
use Sentry\State\Scope;

/**
 * Monitoring Service class
 */
class MonitoringService {
	/**
	 * Whether Sentry is initialized
	 *
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * Initialize Sentry monitoring
	 *
	 * @return void
	 */
	public static function init(): void {
		// Prevent double initialization
		if (self::$initialized) {
			return;
		}

		// Get Sentry DSN from environment
		$dsn = self::get_sentry_dsn();

		// Don't initialize if DSN is not set or we're in development
		if (empty($dsn) || self::is_development()) {
			return;
		}

		// Initialize Sentry
		$client = ClientBuilder::create([
			'dsn' => $dsn,
			'environment' => self::get_environment(),
			'release' => self::get_release_version(),
			'sample_rate' => 1.0, // Capture 100% of errors
			'traces_sample_rate' => self::get_traces_sample_rate(),
			'send_default_pii' => false, // Don't send PII by default
			'max_breadcrumbs' => 50,
			'attach_stacktrace' => true,
			'before_send' => [self::class, 'before_send_callback'],
		])->getClient();

		SentrySdk::init()->bindClient($client);

		// Set initial context
		self::set_initial_context();

		self::$initialized = true;
	}

	/**
	 * Get Sentry DSN from environment
	 *
	 * @return string|null
	 */
	private static function get_sentry_dsn(): ?string {
		// Try multiple environment variable naming conventions
		$env_vars = [
			'SENTRY_DSN',
			'MA_DEAL_SENTRY_DSN',
		];

		foreach ($env_vars as $env_var) {
			// Check both getenv() and $_ENV for compatibility
			$dsn = getenv($env_var) ?: ($_ENV[$env_var] ?? null);
			if ($dsn) {
				return $dsn;
			}
		}

		// Fallback to WordPress option
		return get_option('ma_deal_sentry_dsn', null);
	}

	/**
	 * Get current environment
	 *
	 * @return string
	 */
	private static function get_environment(): string {
		// Check environment variable
		$env = getenv('ENVIRONMENT') ?: ($_ENV['ENVIRONMENT'] ?? null);
		if ($env) {
			return $env;
		}

		// Check WordPress constant
		if (defined('WP_ENVIRONMENT_TYPE')) {
			return WP_ENVIRONMENT_TYPE;
		}

		// Fallback: detect from domain
		if (isset($_SERVER['HTTP_HOST'])) {
			$host = $_SERVER['HTTP_HOST'];
			if (strpos($host, 'localhost') !== false || strpos($host, '.local') !== false) {
				return 'development';
			}
			if (strpos($host, 'staging') !== false) {
				return 'staging';
			}
		}

		return 'production';
	}

	/**
	 * Check if we're in development environment
	 *
	 * @return bool
	 */
	private static function is_development(): bool {
		return self::get_environment() === 'development';
	}

	/**
	 * Get release version
	 *
	 * @return string
	 */
	private static function get_release_version(): string {
		if (defined('MA_DEAL_VERSION')) {
			return 'ma-deal-room@' . MA_DEAL_VERSION;
		}
		return 'ma-deal-room@unknown';
	}

	/**
	 * Get traces sample rate based on environment
	 *
	 * @return float
	 */
	private static function get_traces_sample_rate(): float {
		$env = self::get_environment();

		// Sample rates by environment
		$rates = [
			'development' => 0.0,  // Don't send traces in dev
			'staging' => 1.0,      // 100% in staging
			'production' => 0.2,   // 20% in production
		];

		return $rates[$env] ?? 0.1;
	}

	/**
	 * Set initial Sentry context
	 *
	 * @return void
	 */
	private static function set_initial_context(): void {
		\Sentry\configureScope(function (Scope $scope): void {
			// WordPress version
			global $wp_version;
			$scope->setTag('wordpress_version', $wp_version);

			// Plugin version
			if (defined('MA_DEAL_VERSION')) {
				$scope->setTag('plugin_version', MA_DEAL_VERSION);
			}

			// PHP version
			$scope->setTag('php_version', PHP_VERSION);

			// Server software
			if (isset($_SERVER['SERVER_SOFTWARE'])) {
				$scope->setTag('server_software', $_SERVER['SERVER_SOFTWARE']);
			}

			// Set user context if logged in
			if (is_user_logged_in()) {
				$user = wp_get_current_user();
				$scope->setUser([
					'id' => (string) $user->ID,
					'username' => $user->user_login,
					'email' => $user->user_email,
					'roles' => implode(',', $user->roles),
				]);
			}
		});
	}

	/**
	 * Capture an exception
	 *
	 * @param \Throwable $exception The exception to capture
	 * @param array      $context Additional context
	 * @return string|null Event ID
	 */
	public static function capture_exception(\Throwable $exception, array $context = []): ?string {
		if (!self::$initialized) {
			self::init();
		}

		if (!self::$initialized) {
			// Sentry not available, log to error_log
			error_log(sprintf(
				'[MA Deal Room] Exception: %s in %s:%d',
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine()
			));
			return null;
		}

		// Set additional context if provided
		if (!empty($context)) {
			\Sentry\configureScope(function (Scope $scope) use ($context): void {
				foreach ($context as $key => $value) {
					$scope->setContext($key, $value);
				}
			});
		}

		return \Sentry\captureException($exception);
	}

	/**
	 * Capture a message
	 *
	 * @param string $message The message to capture
	 * @param string $level Severity level (debug, info, warning, error, fatal)
	 * @param array  $context Additional context
	 * @return string|null Event ID
	 */
	public static function capture_message(string $message, string $level = 'info', array $context = []): ?string {
		if (!self::$initialized) {
			self::init();
		}

		if (!self::$initialized) {
			// Sentry not available, log to error_log
			error_log("[MA Deal Room] [{$level}] {$message}");
			return null;
		}

		// Set additional context if provided
		if (!empty($context)) {
			\Sentry\configureScope(function (Scope $scope) use ($context): void {
				foreach ($context as $key => $value) {
					$scope->setContext($key, $value);
				}
			});
		}

		// Convert level string to Sentry severity
		$severity = \Sentry\Severity::info();
		switch ($level) {
			case 'debug':
				$severity = \Sentry\Severity::debug();
				break;
			case 'warning':
				$severity = \Sentry\Severity::warning();
				break;
			case 'error':
				$severity = \Sentry\Severity::error();
				break;
			case 'fatal':
				$severity = \Sentry\Severity::fatal();
				break;
		}

		return \Sentry\captureMessage($message, $severity);
	}

	/**
	 * Start a performance transaction
	 *
	 * @param string $name Transaction name
	 * @param string $operation Operation type (e.g., 'http.server', 'db.query')
	 * @return \Sentry\Tracing\Transaction|null
	 */
	public static function start_transaction(string $name, string $operation = 'http.server') {
		if (!self::$initialized) {
			self::init();
		}

		if (!self::$initialized) {
			return null;
		}

		$hub = \Sentry\SentrySdk::getCurrentHub();

		$context = new \Sentry\Tracing\TransactionContext();
		$context->setName($name);
		$context->setOp($operation);

		return $hub->startTransaction($context);
	}

	/**
	 * Add a breadcrumb
	 *
	 * @param string $message Breadcrumb message
	 * @param string $category Category (e.g., 'auth', 'navigation', 'http')
	 * @param string $level Severity level
	 * @param array  $data Additional data
	 * @return void
	 */
	public static function add_breadcrumb(
		string $message,
		string $category = 'default',
		string $level = 'info',
		array $data = []
	): void {
		if (!self::$initialized) {
			self::init();
		}

		if (!self::$initialized) {
			return;
		}

		\Sentry\addBreadcrumb(
			new \Sentry\Breadcrumb(
				self::string_to_breadcrumb_level($level),
				\Sentry\Breadcrumb::TYPE_DEFAULT,
				$category,
				$message,
				$data
			)
		);
	}

	/**
	 * Convert string level to Breadcrumb level
	 *
	 * @param string $level Level string
	 * @return string
	 */
	private static function string_to_breadcrumb_level(string $level): string {
		$mapping = [
			'debug' => \Sentry\Breadcrumb::LEVEL_DEBUG,
			'info' => \Sentry\Breadcrumb::LEVEL_INFO,
			'warning' => \Sentry\Breadcrumb::LEVEL_WARNING,
			'error' => \Sentry\Breadcrumb::LEVEL_ERROR,
			'fatal' => \Sentry\Breadcrumb::LEVEL_FATAL,
		];

		return $mapping[$level] ?? \Sentry\Breadcrumb::LEVEL_INFO;
	}

	/**
	 * Before send callback - modify or filter events before sending
	 *
	 * @param \Sentry\Event $event The event to send
	 * @param \Sentry\EventHint|null $hint Optional hint
	 * @return \Sentry\Event|null Return null to not send the event
	 */
	public static function before_send_callback(\Sentry\Event $event, ?\Sentry\EventHint $hint = null): ?\Sentry\Event {
		// Filter out known WordPress errors that aren't critical
		$message = $event->getMessage();

		$ignored_messages = [
			'Undefined index',
			'Undefined variable',
			'Undefined offset',
		];

		foreach ($ignored_messages as $ignored) {
			if ($message && strpos($message, $ignored) !== false) {
				// Don't send this event
				return null;
			}
		}

		// Filter out local/development requests
		if (self::is_development()) {
			return null;
		}

		return $event;
	}

	/**
	 * Set user context
	 *
	 * @param int    $user_id WordPress user ID
	 * @param string $username Username
	 * @param string $email User email
	 * @param array  $extra Extra user data
	 * @return void
	 */
	public static function set_user(int $user_id, string $username, string $email, array $extra = []): void {
		if (!self::$initialized) {
			self::init();
		}

		if (!self::$initialized) {
			return;
		}

		\Sentry\configureScope(function (Scope $scope) use ($user_id, $username, $email, $extra): void {
			$user_data = [
				'id' => (string) $user_id,
				'username' => $username,
				'email' => $email,
			];

			// Merge extra data
			$user_data = array_merge($user_data, $extra);

			$scope->setUser($user_data);
		});
	}

	/**
	 * Clear user context (for logout)
	 *
	 * @return void
	 */
	public static function clear_user(): void {
		if (!self::$initialized) {
			return;
		}

		\Sentry\configureScope(function (Scope $scope): void {
			$scope->setUser(null);
		});
	}

	/**
	 * Flush pending events (call before script termination)
	 *
	 * @param int $timeout Maximum time to wait in seconds
	 * @return bool
	 */
	public static function flush(int $timeout = 2): bool {
		if (!self::$initialized) {
			return false;
		}

		$hub = \Sentry\SentrySdk::getCurrentHub();
		$client = $hub->getClient();

		if ($client) {
			return $client->flush($timeout);
		}

		return false;
	}
}
