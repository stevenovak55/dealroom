<?php
/**
 * Main Plugin Class
 *
 * @package MADealRoom\Core
 * @since 1.0.0
 */

namespace MADealRoom\Core;

use MADealRoom\Admin\AdminPages;
use MADealRoom\Database\Migrator;
use MADealRoom\Repositories\TransactionRepository;
use MADealRoom\Repositories\TaskRepository;
use MADealRoom\Repositories\TemplateRepository;
use MADealRoom\Repositories\TaskDefinitionRepository;
use MADealRoom\Repositories\TemplateTaskRepository;
use MADealRoom\Repositories\PartyRepository;
use MADealRoom\Repositories\AccountRepository;
use MADealRoom\Repositories\ReminderRepository;
use MADealRoom\Repositories\VendorRequestRepository;
use MADealRoom\Repositories\VendorMessageRepository;
use MADealRoom\Repositories\VendorAvailabilityRepository;
use MADealRoom\Repositories\VendorRatingRepository;
use MADealRoom\Repositories\DocumentRepository;
use MADealRoom\Repositories\EventRepository;
use MADealRoom\Repositories\NotificationRepository;
use MADealRoom\Repositories\CustomUserRepository;
use MADealRoom\Repositories\UserRoleRepository;
use MADealRoom\Repositories\UserSessionRepository;
use MADealRoom\Repositories\UserInvitationRepository;
use MADealRoom\Repositories\PasswordResetRepository;
use MADealRoom\Repositories\EmailVerificationRepository;
use MADealRoom\Repositories\TwoFactorRepository;
use MADealRoom\Services\TemplateEngine;
use MADealRoom\Services\TaskScheduler;
use MADealRoom\Services\ReminderService;
use MADealRoom\Services\NotificationService;
use MADealRoom\Services\VendorService;
use MADealRoom\Services\EmailService;
use MADealRoom\Services\TaskAssignmentService;
use MADealRoom\Services\AuthService;
use MADealRoom\Services\EmailVerificationService;
use MADealRoom\Services\PasswordResetService;
use MADealRoom\Services\TwoFactorAuthService;
use MADealRoom\Services\UserInvitationService;
use MADealRoom\Services\ValidationService;
use MADealRoom\Services\AccountSecurityService;
use MADealRoom\Services\Integration\MLS\MLSImportService;
use MADealRoom\Services\Integration\MLS\MLSSubmissionService;
use MADealRoom\Services\Integration\MLS\MLSSyncService;
use MADealRoom\Frontend\AgentDashboard as FrontendAgentDashboard;
use MADealRoom\REST\Controllers\TransactionController;
use MADealRoom\REST\Controllers\TaskController;
use MADealRoom\REST\Controllers\TemplateController;
use MADealRoom\REST\Controllers\TaskDefinitionController;
use MADealRoom\REST\Controllers\ReminderController;
use MADealRoom\REST\Controllers\VendorPortalController;
use MADealRoom\REST\Controllers\DocumentController;
use MADealRoom\REST\Controllers\NotificationController;
use MADealRoom\REST\Controllers\SettingsController;
use MADealRoom\REST\Controllers\SearchController;
use MADealRoom\REST\Controllers\UserProfileController;
use MADealRoom\REST\Controllers\AuthController;
use MADealRoom\REST\Controllers\TwoFactorController;
use MADealRoom\REST\Controllers\UserManagementController;
use MADealRoom\REST\Controllers\InvitationController;
use MADealRoom\REST\Controllers\TwilioWebhookController;
use MADealRoom\REST\Controllers\NotificationPreferencesController;
use MADealRoom\REST\Controllers\MLSController;
use MADealRoom\REST\Controllers\CRMSyncController;
use MADealRoom\REST\Controllers\DocuSignController;

/**
 * Main plugin singleton class
 */
class Plugin {
	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Service container
	 *
	 * @var ServiceContainer
	 */
	private $container;

	/**
	 * Hooks manager
	 *
	 * @var Hooks
	 */
	private $hooks;

	/**
	 * Private constructor for singleton
	 */
	private function __construct() {
		$this->container = ServiceContainer::instance();
		$this->hooks = new Hooks();

		$this->register_services();
		$this->init_monitoring();
		$this->init_middleware();
		$this->register_hooks();
	}

	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if (self::$instance === null) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get service container
	 *
	 * @return ServiceContainer
	 */
	public function container(): ServiceContainer {
		return $this->container;
	}

	/**
	 * Get hooks manager
	 *
	 * @return Hooks
	 */
	public function hooks(): Hooks {
		return $this->hooks;
	}

	/**
	 * Register all services in the container
	 *
	 * @return void
	 */
	private function register_services(): void {
		// Cache Service - Register first so other services can use it
		$this->container->register('cache_service', function() {
			return new \MADealRoom\Services\CacheService();
		});

		// Database layer
		$this->container->register('migrator', function() {
			return new Migrator();
		});

		// Repositories
		$this->container->register('transaction_repository', function($container) {
			return new TransactionRepository($container->get('cache_service'));
		});

		$this->container->register('task_repository', function($container) {
			return new TaskRepository($container->get('cache_service'));
		});

		$this->container->register('template_repository', function($container) {
			return new TemplateRepository($container->get('cache_service'));
		});

		$this->container->register('task_definition_repository', function($container) {
			return new TaskDefinitionRepository($container->get('cache_service'));
		});

		$this->container->register('template_task_repository', function($container) {
			return new TemplateTaskRepository($container->get('cache_service'));
		});

		$this->container->register('party_repository', function($container) {
			return new PartyRepository($container->get('cache_service'));
		});

		$this->container->register('account_repository', function($container) {
			return new AccountRepository($container->get('cache_service'));
		});

		$this->container->register('reminder_repository', function($container) {
			return new ReminderRepository($container->get('cache_service'));
		});

		$this->container->register('vendor_request_repository', function($container) {
			return new VendorRequestRepository($container->get('cache_service'));
		});

		$this->container->register('vendor_message_repository', function($container) {
			return new VendorMessageRepository($container->get('cache_service'));
		});

		$this->container->register('vendor_availability_repository', function($container) {
			return new VendorAvailabilityRepository($container->get('cache_service'));
		});

		$this->container->register('vendor_rating_repository', function($container) {
			return new VendorRatingRepository($container->get('cache_service'));
		});

		$this->container->register('event_repository', function($container) {
			return new EventRepository($container->get('cache_service'));
		});

		$this->container->register('document_repository', function($container) {
			return new DocumentRepository($container->get('cache_service'));
		});

		$this->container->register('notification_repository', function($container) {
			return new NotificationRepository($container->get('cache_service'));
		});

		// Auth System Repositories
		$this->container->register('custom_user_repository', function($container) {
			return new CustomUserRepository($container->get('cache_service'));
		});

		$this->container->register('user_role_repository', function($container) {
			return new UserRoleRepository($container->get('cache_service'));
		});

		$this->container->register('user_session_repository', function($container) {
			return new UserSessionRepository($container->get('cache_service'));
		});

		$this->container->register('user_invitation_repository', function($container) {
			return new UserInvitationRepository($container->get('cache_service'));
		});

		$this->container->register('password_reset_repository', function($container) {
			return new PasswordResetRepository($container->get('cache_service'));
		});

		$this->container->register('email_verification_repository', function($container) {
			return new EmailVerificationRepository($container->get('cache_service'));
		});

		$this->container->register('two_factor_repository', function($container) {
			return new TwoFactorRepository($container->get('cache_service'));
		});

		// Services
		$this->container->register('template_engine', function($container) {
			return new TemplateEngine(
				$container->get('template_repository'),
				$container->get('task_definition_repository'),
				$container->get('template_task_repository'),
				$container->get('cache_service')
			);
		});

		$this->container->register('task_scheduler', function($container) {
			return new TaskScheduler(
				$container->get('task_repository')
			);
		});

		$this->container->register('notification_service', function() {
			return new NotificationService();
		});

		$this->container->register('reminder_service', function($container) {
			return new ReminderService(
				$container->get('reminder_repository'),
				$container->get('notification_service'),
				$container->get('task_repository'),
				$container->get('transaction_repository')
			);
		});

		$this->container->register('vendor_service', function($container) {
			return new VendorService(
				$container->get('vendor_request_repository'),
				$container->get('vendor_message_repository'),
				$container->get('vendor_availability_repository'),
				$container->get('vendor_rating_repository'),
				$container->get('transaction_repository'),
				$container->get('email_service'),
				$container->get('file_storage_service')
			);
		});

		$this->container->register('email_service', function($container) {
			return new EmailService(
				$container->get('notification_repository')
			);
		});

		$this->container->register('ma_timeline_calculator', function() {
			return new \MADealRoom\Services\MATimelineCalculator();
		});

		$this->container->register('task_assignment_service', function($container) {
			return new TaskAssignmentService(
				$container->get('task_repository'),
				$container->get('party_repository'),
				$container->get('transaction_repository'),
				$container->get('email_service')
			);
		});

		// Auth System Services
		$this->container->register('auth_service', function($container) {
			return new AuthService(
				$container->get('custom_user_repository'),
				$container->get('user_session_repository')
			);
		});

		$this->container->register('email_verification_service', function($container) {
			return new EmailVerificationService(
				$container->get('email_verification_repository'),
				$container->get('email_service')
			);
		});

		$this->container->register('password_reset_service', function($container) {
			return new PasswordResetService(
				$container->get('password_reset_repository'),
				$container->get('custom_user_repository'),
				$container->get('email_service')
			);
		});

		$this->container->register('two_factor_auth_service', function($container) {
			return new TwoFactorAuthService(
				$container->get('two_factor_repository')
			);
		});

		$this->container->register('user_invitation_service', function($container) {
			return new UserInvitationService(
				$container->get('user_invitation_repository'),
				$container->get('custom_user_repository'),
				$container->get('email_service')
			);
		});

		$this->container->register('validation_service', function() {
			return new ValidationService();
		});

		$this->container->register('account_security_service', function($container) {
			return new AccountSecurityService(
				$container->get('custom_user_repository')
			);
		});

		$this->container->register('backup_service', function() {
			return new \MADealRoom\Services\BackupService();
		});

		$this->container->register('monitoring_service', function() {
			return new \MADealRoom\Services\MonitoringService();
		});

		$this->container->register('rate_limiter', function() {
			return new \MADealRoom\Services\RateLimiter();
		});

		$this->container->register('file_security_service', function() {
			return new \MADealRoom\Services\FileSecurityService();
		});

		$this->container->register('file_storage_service', function() {
			return new \MADealRoom\Services\FileStorageService();
		});

		// MLS Integration Services
		$this->container->register('mls_client_factory', function() {
			return new \MADealRoom\Services\Integration\MLS\MLSClientFactory();
		});

		$this->container->register('mls_import_service', function($container) {
			return new MLSImportService(
				$container->get('mls_client_factory'),
				$container->get('transaction_repository')
			);
		});

		$this->container->register('mls_submission_service', function($container) {
			return new MLSSubmissionService(
				$container->get('mls_client_factory'),
				$container->get('transaction_repository')
			);
		});

		$this->container->register('mls_sync_service', function($container) {
			return new MLSSyncService(
				$container->get('mls_client_factory'),
				$container->get('transaction_repository')
			);
		});

		// Middleware
		$this->container->register('https_middleware', function() {
			return new \MADealRoom\Middleware\HTTPSMiddleware();
		});

		$this->container->register('security_headers_middleware', function() {
			return new \MADealRoom\Middleware\SecurityHeadersMiddleware();
		});

		$this->container->register('rate_limit_middleware', function($container) {
			return new \MADealRoom\Middleware\RateLimitMiddleware(
				$container->get('rate_limiter')
			);
		});

		// Admin pages
		$this->container->register('admin_pages', function() {
			return new AdminPages();
		});

		// Frontend agent dashboard
		$this->container->register('frontend_agent_dashboard', function() {
			return new FrontendAgentDashboard();
		});

		// REST Controllers
		$this->container->register('transaction_controller', function($container) {
			return new TransactionController(
				$container->get('transaction_repository'),
				$container->get('party_repository'),
				$container->get('event_repository'),
				$container->get('template_repository'),
				$container->get('task_repository'),
				$container->get('account_repository'),
				$container->get('template_engine'),
				$container->get('email_service'),
				$container->get('task_assignment_service'),
				$container->get('ma_timeline_calculator')
			);
		});

		$this->container->register('task_controller', function($container) {
			return new TaskController(
				$container->get('task_repository'),
				$container->get('transaction_repository'),
				$container->get('party_repository'),
				$container->get('event_repository'),
				$container->get('email_service')
			);
		});

		$this->container->register('template_controller', function($container) {
			return new TemplateController(
				$container->get('template_repository'),
				$container->get('account_repository'),
				$container->get('transaction_repository'),
				$container->get('event_repository')
			);
		});

		$this->container->register('task_definition_controller', function($container) {
			return new TaskDefinitionController(
				$container->get('task_definition_repository'),
				$container->get('template_task_repository'),
				$container->get('event_repository')
			);
		});

		$this->container->register('reminder_controller', function($container) {
			return new ReminderController(
				$container->get('reminder_repository'),
				$container->get('task_repository'),
				$container->get('transaction_repository'),
				$container->get('event_repository')
			);
		});

		$this->container->register('vendor_portal_controller', function($container) {
			return new VendorPortalController(
				$container->get('vendor_service'),
				$container->get('vendor_message_repository'),
				$container->get('event_repository')
			);
		});

		$this->container->register('document_controller', function($container) {
			return new DocumentController(
				$container->get('document_repository'),
				$container->get('transaction_repository'),
				$container->get('party_repository'),
				$container->get('event_repository'),
				$container->get('email_service'),
				$container->get('file_security_service'),
				$container->get('file_storage_service')
			);
		});

		$this->container->register('notification_controller', function($container) {
			return new NotificationController(
				$container->get('notification_repository')
			);
		});

		$this->container->register('settings_controller', function($container) {
			return new SettingsController();
		});

		$this->container->register('search_controller', function($container) {
			return new SearchController();
		});

		$this->container->register('user_profile_controller', function($container) {
			return new UserProfileController();
		});

		// Auth System Controllers
		$this->container->register('auth_controller', function($container) {
			return new AuthController(
				$container->get('auth_service'),
				$container->get('custom_user_repository'),
				$container->get('email_verification_service'),
				$container->get('password_reset_service'),
				$container->get('validation_service')
			);
		});

		$this->container->register('two_factor_controller', function($container) {
			return new TwoFactorController(
				$container->get('two_factor_auth_service'),
				$container->get('custom_user_repository')
			);
		});

		$this->container->register('user_management_controller', function($container) {
			return new UserManagementController(
				$container->get('custom_user_repository'),
				$container->get('user_role_repository'),
				$container->get('account_security_service'),
				$container->get('validation_service')
			);
		});

		$this->container->register('invitation_controller', function($container) {
			return new InvitationController(
				$container->get('user_invitation_service'),
				$container->get('user_invitation_repository')
			);
		});

		$this->container->register('twilio_webhook_controller', function($container) {
			return new TwilioWebhookController();
		});

		$this->container->register('notification_preferences_controller', function($container) {
			return new NotificationPreferencesController();
		});

		$this->container->register('mls_controller', function($container) {
			return new MLSController(
				$container->get('mls_import_service'),
				$container->get('mls_submission_service'),
				$container->get('mls_sync_service')
			);
		});

		$this->container->register('crm_sync_controller', function($container) {
			return new CRMSyncController();
		});

		$this->container->register('docusign_controller', function($container) {
			return new DocuSignController();
		});
	}

	/**
	 * Initialize middleware
	 *
	 * @return void
	 */
	private function init_middleware(): void {
		// Initialize HTTPS middleware
		$this->container->get('https_middleware')->init();

		// Initialize security headers middleware
		$this->container->get('security_headers_middleware')->init();

		// Initialize rate limit middleware
		$this->container->get('rate_limit_middleware')->init();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// REST API initialization
		$this->hooks->add_action('rest_api_init', [$this, 'register_rest_routes']);
		$this->hooks->add_filter('rest_authentication_errors', [$this, 'rest_authentication_errors']);

		// Frontend dashboard template & assets
		$this->container->get('frontend_agent_dashboard')->register();

		// Admin initialization
		if (is_admin()) {
			$this->hooks->add_action('admin_menu', [$this, 'register_admin_menu']);
			$this->hooks->add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		}

		// WP-CLI commands
		if (defined('WP_CLI') && WP_CLI) {
			$this->register_cli_commands();
		}

		// Cron jobs
		$this->hooks->add_action('ma_deal_room_process_reminders', [$this, 'process_reminders_cron']);
		$this->hooks->add_action('ma_deal_room_process_queue', [$this, 'process_queue_cron']);
		$this->hooks->add_action('ma_deal_room_cleanup_notifications', [$this, 'cleanup_notifications_cron']);
		$this->hooks->add_action('ma_deal_room_daily_backup', [$this, 'daily_backup_cron']);

		// Schedule cron jobs if not already scheduled
		if (!wp_next_scheduled('ma_deal_room_process_reminders')) {
			wp_schedule_event(time(), 'hourly', 'ma_deal_room_process_reminders');
		}

		if (!wp_next_scheduled('ma_deal_room_process_queue')) {
			wp_schedule_event(time(), 'hourly', 'ma_deal_room_process_queue');
		}

		if (!wp_next_scheduled('ma_deal_room_cleanup_notifications')) {
			wp_schedule_event(time(), 'daily', 'ma_deal_room_cleanup_notifications');
		}

		if (!wp_next_scheduled('ma_deal_room_daily_backup')) {
			// Schedule daily backup at 3 AM server time
			$tomorrow_3am = strtotime('tomorrow 3:00 AM');
			wp_schedule_event($tomorrow_3am, 'daily', 'ma_deal_room_daily_backup');
		}
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		$this->container->get('transaction_controller')->register_routes();
		$this->container->get('task_controller')->register_routes();
		$this->container->get('template_controller')->register_routes();
		$this->container->get('task_definition_controller')->register_routes();
		$this->container->get('reminder_controller')->register_routes();
		$this->container->get('vendor_portal_controller')->register_routes();
		$this->container->get('document_controller')->register_routes();
		$this->container->get('notification_controller')->register_routes();
		$this->container->get('settings_controller')->register_routes();
		$this->container->get('search_controller')->register_routes();
		$this->container->get('user_profile_controller')->register_routes();

		// Auth System Routes
		$this->container->get('auth_controller')->register_routes();
		$this->container->get('two_factor_controller')->register_routes();
		$this->container->get('user_management_controller')->register_routes();
		$this->container->get('invitation_controller')->register_routes();

		// Integration Webhooks
		$this->container->get('twilio_webhook_controller')->register_routes();

		// Notification Preferences
		$this->container->get('notification_preferences_controller')->register_routes();

		// MLS Integration
		$this->container->get('mls_controller')->register_routes();

		// CRM Integration
		$this->container->get('crm_sync_controller')->register_routes();

		// DocuSign Integration
		$this->container->get('docusign_controller')->register_routes();
	}

	/**
	 * Allow cookie authentication for REST API requests
	 *
	 * @param WP_Error|null|bool $result Error from another authentication handler, null if not determined yet, or true if authentication succeeded
	 * @return WP_Error|null|bool
	 */
	public function rest_authentication_errors($result) {
		// If another authentication method was used, respect that
		if (!empty($result)) {
			return $result;
		}

		// Check if user is logged in via cookie
		if (is_user_logged_in()) {
			return true;
		}

		// Let other handlers determine authentication
		return $result;
	}

	/**
	 * Register admin menu
	 *
	 * @return void
	 */
	public function register_admin_menu(): void {
		$this->container->get('admin_pages')->register_menu();
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public function enqueue_admin_assets(string $hook): void {
		$this->container->get('admin_pages')->enqueue_admin_assets($hook);
	}

	/**
	 * Register WP-CLI commands
	 *
	 * @return void
	 */
	private function register_cli_commands(): void {
		\WP_CLI::add_command('ma-deal migrate', 'MADealRoom\CLI\MigrateCommand');
		\WP_CLI::add_command('ma-deal queue:run', 'MADealRoom\CLI\QueueCommand');
		\WP_CLI::add_command('ma-deal reminders:send', 'MADealRoom\CLI\RemindersCommand');
		\WP_CLI::add_command('ma-deal templates:sync', 'MADealRoom\CLI\TemplatesCommand');
		\WP_CLI::add_command('ma-deal task-definitions:sync', 'MADealRoom\CLI\TaskDefinitionsCommand');
		\WP_CLI::add_command('ma-deal task-definitions:list', 'MADealRoom\CLI\TaskDefinitionsCommand');
		\WP_CLI::add_command('ma-deal seed', 'MADealRoom\CLI\SeedCommand');
		\WP_CLI::add_command('ma-deal rate-limit', 'MADealRoom\CLI\RateLimitCommand');
		\WP_CLI::add_command('ma-deal backup', 'MADealRoom\CLI\BackupCommand');
	}

	/**
	 * Process reminders cron job
	 *
	 * @return void
	 */
	public function process_reminders_cron(): void {
		$reminder_service = $this->container->get('reminder_service');
		$reminder_service->processQueue();
	}

	/**
	 * Process queue cron job
	 *
	 * @return void
	 */
	public function process_queue_cron(): void {
		// Placeholder for general queue processing logic
		error_log('MA Deal Room: General queue cron job executed.');
		do_action('ma_deal_room_queue_processed');
	}

	/**
	 * Cleanup old notifications cron job
	 *
	 * @return void
	 */
	public function cleanup_notifications_cron(): void {
		$notification_repository = $this->container->get('notification_repository');
		$deleted = $notification_repository->deleteOldRead(30); // Delete read notifications older than 30 days
		if ($deleted > 0) {
			error_log("MA Deal Room: Deleted {$deleted} old read notifications.");
		}
		do_action('ma_deal_room_notifications_cleaned', $deleted);
	}

	/**
	 * Daily backup cron job
	 *
	 * @return void
	 */
	public function daily_backup_cron(): void {
		$backup_service = $this->container->get('backup_service');
		$result = $backup_service->create_backup('daily');

		if (is_wp_error($result)) {
			error_log('MA Deal Room: Daily backup failed: ' . $result->get_error_message());
			do_action('ma_deal_room_backup_failed', $result);
		} else {
			error_log(sprintf(
				'MA Deal Room: Daily backup completed successfully (%s, %s)',
				$result['filename'],
				$result['size_formatted']
			));
			do_action('ma_deal_room_backup_completed', $result);
		}
	}

	/**
	 * Initialize monitoring and error handling
	 *
	 * @return void
	 */
	private function init_monitoring(): void {
		// Initialize Sentry
		\MADealRoom\Services\MonitoringService::init();

		// Set up PHP error handler
		set_error_handler(function($errno, $errstr, $errfile, $errline) {
			// Don't report errors if error reporting is turned off
			if (!(error_reporting() & $errno)) {
				return false;
			}

			// Create exception from error
			$exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);

			// Capture in Sentry
			\MADealRoom\Services\MonitoringService::capture_exception($exception, [
				'error' => [
					'type' => 'PHP Error',
					'errno' => $errno,
					'severity' => $this->error_type_to_string($errno),
				],
			]);

			// Don't prevent default error handler
			return false;
		});

		// Set up exception handler
		set_exception_handler(function($exception) {
			// Capture in Sentry
			\MADealRoom\Services\MonitoringService::capture_exception($exception);

			// Log to PHP error log as well
			error_log(sprintf(
				'[MA Deal Room] Uncaught Exception: %s in %s:%d',
				$exception->getMessage(),
				$exception->getFile(),
				$exception->getLine()
			));
		});

		// Set up shutdown handler for fatal errors
		register_shutdown_function(function() {
			$error = error_get_last();
			if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
				// Create exception from fatal error
				$exception = new \ErrorException(
					$error['message'],
					0,
					$error['type'],
					$error['file'],
					$error['line']
				);

				// Capture in Sentry
				\MADealRoom\Services\MonitoringService::capture_exception($exception, [
					'error' => [
						'type' => 'Fatal Error',
						'severity' => $this->error_type_to_string($error['type']),
					],
				]);

				// Flush Sentry to ensure event is sent
				\MADealRoom\Services\MonitoringService::flush();
			}
		});

		// Track user context on login
		add_action('wp_login', function($user_login, $user) {
			\MADealRoom\Services\MonitoringService::set_user(
				$user->ID,
				$user->user_login,
				$user->user_email,
				['roles' => implode(',', $user->roles)]
			);
		}, 10, 2);

		// Clear user context on logout
		add_action('wp_logout', function() {
			\MADealRoom\Services\MonitoringService::clear_user();
		});
	}

	/**
	 * Convert error type constant to string
	 *
	 * @param int $type Error type constant
	 * @return string
	 */
	private function error_type_to_string(int $type): string {
		$errors = [
			E_ERROR => 'E_ERROR',
			E_WARNING => 'E_WARNING',
			E_PARSE => 'E_PARSE',
			E_NOTICE => 'E_NOTICE',
			E_CORE_ERROR => 'E_CORE_ERROR',
			E_CORE_WARNING => 'E_CORE_WARNING',
			E_COMPILE_ERROR => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR => 'E_USER_ERROR',
			E_USER_WARNING => 'E_USER_WARNING',
			E_USER_NOTICE => 'E_USER_NOTICE',
			E_STRICT => 'E_STRICT',
			E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED => 'E_DEPRECATED',
			E_USER_DEPRECATED => 'E_USER_DEPRECATED',
		];

		return $errors[$type] ?? 'UNKNOWN';
	}
}
