<?php
/**
 * Plugin Name: MA Deal Room
 * Plugin URI: https://madealroom.com
 * Description: Massachusetts real estate transaction management system with automated task tracking, reminders, and vendor coordination.
 * Version: 2.5.12
 * Author: BMN Boston Real Estate
 * Author URI: https://bmnboston.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ma-deal-room
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

// Plugin version
define('MA_DEAL_VERSION', '2.5.12');

// Plugin root file
define('MA_DEAL_FILE', __FILE__);

// Plugin directory path
define('MA_DEAL_PATH', plugin_dir_path(__FILE__));

// Plugin directory URL
define('MA_DEAL_URL', plugin_dir_url(__FILE__));

// Plugin basename
define('MA_DEAL_BASENAME', plugin_basename(__FILE__));

// Minimum requirements
define('MA_DEAL_MIN_PHP_VERSION', '8.0');
define('MA_DEAL_MIN_WP_VERSION', '6.0');

/**
 * Check minimum requirements before loading plugin
 */
function ma_deal_room_check_requirements() {
	$errors = [];

	// Check PHP version
	if (version_compare(PHP_VERSION, MA_DEAL_MIN_PHP_VERSION, '<')) {
		$errors[] = sprintf(
			'MA Deal Room requires PHP %s or higher. You are running version %s.',
			MA_DEAL_MIN_PHP_VERSION,
			PHP_VERSION
		);
	}

	// Check WordPress version
	global $wp_version;
	if (version_compare($wp_version, MA_DEAL_MIN_WP_VERSION, '<')) {
		$errors[] = sprintf(
			'MA Deal Room requires WordPress %s or higher. You are running version %s.',
			MA_DEAL_MIN_WP_VERSION,
			$wp_version
		);
	}

	// Check if composer autoloader exists
	if (!file_exists(MA_DEAL_PATH . 'vendor/autoload.php')) {
		$errors[] = 'MA Deal Room requires Composer dependencies. Please run "composer install" in the plugin directory.';
	}

	// Display errors and deactivate plugin if requirements not met
	if (!empty($errors)) {
		add_action('admin_notices', function() use ($errors) {
			echo '<div class="error"><p>';
			echo '<strong>MA Deal Room plugin could not be activated:</strong><br>';
			echo implode('<br>', $errors);
			echo '</p></div>';
		});

		// Deactivate plugin
		deactivate_plugins(MA_DEAL_BASENAME);

		if (isset($_GET['activate'])) {
			unset($_GET['activate']);
		}

		return false;
	}

	return true;
}

// Check requirements
if (!ma_deal_room_check_requirements()) {
	return;
}

// Load Composer autoloader
require_once MA_DEAL_PATH . 'vendor/autoload.php';

// Load environment variables from .env file
// This allows configuration via .env for local development and production deployments
if (file_exists(dirname(MA_DEAL_PATH) . '/.env')) {
	$dotenv = Dotenv\Dotenv::createImmutable(dirname(MA_DEAL_PATH));
	$dotenv->safeLoad(); // Use safeLoad() to avoid overwriting existing environment variables
}

/**
 * Initialize the plugin
 */
function ma_deal_room_init() {
	return MADealRoom\Core\Plugin::instance();
}

// Bootstrap the plugin
add_action('plugins_loaded', 'ma_deal_room_init');

// Suppress database errors during REST API requests to prevent "headers already sent" errors
add_action('rest_api_init', function() {
	global $wpdb;
	$wpdb->hide_errors(); // Suppress wpdb error output during REST API requests
});

// Handle email verification requests
add_action('init', function() {
	if (!isset($_GET['ma_verify_email'])) {
		return;
	}

	$token = sanitize_text_field($_GET['ma_verify_email']);
	if (empty($token)) {
		return;
	}

	// Get the email verification service from the plugin container
	try {
		$plugin = MADealRoom\Core\Plugin::instance();
		$email_verification_service = $plugin->container()->get('email_verification_service');

		// Attempt to verify the email
		$result = $email_verification_service->verify_email($token);

		if (is_wp_error($result)) {
			// Show error message
			wp_die(
				'<h1>Email Verification Failed</h1>' .
				'<p>' . esc_html($result->get_error_message()) . '</p>' .
				'<p><a href="' . esc_url(home_url('/agent-dashboard/#/auth/login')) . '">Go to Login</a></p>',
				'Email Verification Error'
			);
		} else {
			// Show success message and redirect to login
			wp_die(
				'<h1>Email Verified Successfully!</h1>' .
				'<p>Your email has been verified. You can now log in to your account.</p>' .
				'<p><a href="' . esc_url(home_url('/agent-dashboard/#/auth/login')) . '">Go to Login</a></p>',
				'Email Verified',
				['response' => 200]
			);
		}
	} catch (Exception $e) {
		wp_die(
			'<h1>Verification Error</h1>' .
			'<p>An error occurred while verifying your email.</p>' .
			'<p><a href="' . esc_url(home_url()) . '">Go Home</a></p>',
			'Verification Error'
		);
	}
}, 1);

// Initialize Plugin Reset admin page
if (is_admin()) {
	$reset_page = new MADealRoom\Admin\PluginResetPage();
	$reset_page->init();
}

// Configure email delivery based on environment
// Only use MailHog in development (when SendGrid is not configured)
add_action('phpmailer_init', function($phpmailer) {
	// Check if SendGrid is configured
	$sendgrid_key = getenv('SENDGRID_API_KEY') ?: ($_ENV['SENDGRID_API_KEY'] ?? null);

	// If SendGrid is configured, EmailService will handle it - don't override
	if ($sendgrid_key) {
		error_log('[Email Config] SendGrid API key detected - using SendGrid for email delivery');
		return;
	}

	// Check if in development environment
	$env = getenv('WP_ENV') ?: ($_ENV['WP_ENV'] ?? 'production');
	$is_dev = in_array($env, ['development', 'dev', 'local'], true) ||
	          (defined('WP_DEBUG') && WP_DEBUG) ||
	          (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'local');

	// Only configure MailHog for development environments
	if ($is_dev) {
		$phpmailer->isSMTP();
		$phpmailer->Host = 'avn-mailhog';
		$phpmailer->Port = 1025;
		$phpmailer->SMTPAuth = false;
		$phpmailer->SMTPSecure = '';
		$phpmailer->SMTPAutoTLS = false;
		// Disable fallback to sendmail/mail() functions
		$phpmailer->Mailer = 'smtp';
		error_log('[Email Config] Development environment detected - using MailHog: ' . $phpmailer->Host . ':' . $phpmailer->Port . ' (Mailer: ' . $phpmailer->Mailer . ')');
	} else {
		error_log('[Email Config] Production environment - using default wp_mail configuration');
	}
}, 10, 1);

// Set default From email and name
add_filter('wp_mail_from', function($from_email) {
	// Override if empty or using WordPress default patterns
	if (empty($from_email) ||
	    $from_email === 'wordpress@' . $_SERVER['SERVER_NAME'] ||
	    $from_email === 'wordpress@localhost' ||
	    strpos($from_email, 'wordpress@') === 0 ||
	    strpos($from_email, 'noreply@localhost') === 0) {
		$site_url = get_site_url();
		$domain = parse_url($site_url, PHP_URL_HOST) ?? 'madealroom.local';

		// Ensure domain has TLD for valid email address
		if ($domain === 'localhost' || strpos($domain, '.') === false) {
			$domain = 'madealroom.test';
		}

		return 'noreply@' . $domain;
	}
	return $from_email;
});

add_filter('wp_mail_from_name', function($from_name) {
	// Only override if not already set
	if (empty($from_name) || $from_name === 'WordPress') {
		return 'MA Deal Room';
	}
	return $from_name;
});

function ma_deal_room_activate() {
	// Start output buffering to prevent any unexpected output during activation
	ob_start();

	try {
		// Run database migrations
		if (class_exists('MADealRoom\Database\Migrator')) {
			$migrator = new MADealRoom\Database\Migrator();
			$results = $migrator->run();

			// Log migration results if debug enabled (don't output to browser)
			if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
				error_log('MA Deal Room migrations: ' . print_r($results, true));
			}
		}
	} catch (Exception $e) {
		// Log error but don't output to browser
		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log('MA Deal Room activation error: ' . $e->getMessage());
		}
	}

	// Sync system templates from YAML files
	$template_sync_result = ma_deal_room_sync_system_templates();
	if (isset($template_sync_result['synced']) && $template_sync_result['synced'] > 0) {
		update_option('ma_deal_room_templates_synced', true);
		update_option('ma_deal_room_templates_sync_count', $template_sync_result['synced']);

		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log(sprintf(
				'MA Deal Room: Synced %d templates (%d errors)',
				$template_sync_result['synced'],
				$template_sync_result['errors']
			));
		}
	}

	// Sync task definitions from YAML templates
	$task_def_sync_result = ma_deal_room_sync_task_definitions();
	if (isset($task_def_sync_result['created']) || isset($task_def_sync_result['updated'])) {
		$total_synced = ($task_def_sync_result['created'] ?? 0) + ($task_def_sync_result['updated'] ?? 0);
		update_option('ma_deal_room_task_definitions_synced', true);
		update_option('ma_deal_room_task_definitions_count', $total_synced);

		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log(sprintf(
				'MA Deal Room: Synced %d task definitions (created: %d, updated: %d, errors: %d)',
				$total_synced,
				$task_def_sync_result['created'] ?? 0,
				$task_def_sync_result['updated'] ?? 0,
				$task_def_sync_result['errors'] ?? 0
			));
		}
	}

	// Register custom capabilities
	ma_deal_room_register_capabilities();

	// Create default account if none exists
	ma_deal_room_ensure_default_account();

	// Set activation flag
	update_option('ma_deal_room_activated', true);
	update_option('ma_deal_room_version', MA_DEAL_VERSION);

	// Flush rewrite rules for REST API
	flush_rewrite_rules();

	// Ensure front-end agent dashboard page exists
	ma_deal_room_ensure_agent_dashboard_page();
	update_option('ma_deal_room_agent_dashboard_page_created', 1);

	// Clean output buffer and discard any unexpected output
	ob_end_clean();
}
register_activation_hook(__FILE__, 'ma_deal_room_activate');

/**
 * Plugin deactivation hook
 * Note: Does NOT delete data - use uninstall.php for that
 */
function ma_deal_room_deactivate() {
	// Flush rewrite rules
	flush_rewrite_rules();

	// Clear any scheduled cron jobs
	wp_clear_scheduled_hook('ma_deal_room_daily_cleanup');
	wp_clear_scheduled_hook('ma_deal_room_process_queue');

	// Log deactivation
	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log('MA Deal Room: Plugin deactivated');
	}

	// Keep activation flag but add deactivation timestamp
	update_option('ma_deal_room_last_deactivated', current_time('mysql'));
}
register_deactivation_hook(__FILE__, 'ma_deal_room_deactivate');

/**
 * Check for plugin updates and run upgrade routines
 */
add_action('plugins_loaded', function() {
	// Get installed version from database
	$installed_version = get_option('ma_deal_room_version', '0.0.0');
	$current_version = MA_DEAL_VERSION;

	// Version upgrade detected
	if (version_compare($installed_version, $current_version, '<')) {
		// Log upgrade
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log(sprintf(
				'MA Deal Room: Upgrading from %s to %s',
				$installed_version,
				$current_version
			));
		}

		// Run upgrade routine
		ma_deal_room_upgrade($installed_version, $current_version);

		// Update version in database
		update_option('ma_deal_room_version', $current_version);
		update_option('ma_deal_room_last_upgraded', current_time('mysql'));
		update_option('ma_deal_room_previous_version', $installed_version);
	}
});

/**
 * Plugin upgrade routine
 * Runs when plugin version changes
 *
 * @param string $from_version Previous version
 * @param string $to_version New version
 */
function ma_deal_room_upgrade($from_version, $to_version) {
	// Run database migrations
	if (class_exists('MADealRoom\Database\Migrator')) {
		$migrator = new MADealRoom\Database\Migrator();
		$results = $migrator->run();

		if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
			error_log('MA Deal Room upgrade migrations: ' . print_r($results, true));
		}
	}

	// Update capabilities for all versions (ensures new capabilities are added)
	ma_deal_room_update_capabilities();

	// Version-specific upgrades
	if (version_compare($from_version, '1.0.1', '<')) {
		// Upgrading to 1.0.1 - ensure MLS repository is available
		// Re-sync task definitions if needed
		$task_def_count = get_option('ma_deal_room_task_definitions_count', 0);
		if ($task_def_count < 100) {
			$result = ma_deal_room_sync_task_definitions();
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log(sprintf(
					'MA Deal Room 1.0.1 upgrade: Re-synced task definitions (created: %d, updated: %d)',
					$result['created'] ?? 0,
					$result['updated'] ?? 0
				));
			}
		}
	}

	// Clear any caches
	if (function_exists('wp_cache_flush')) {
		wp_cache_flush();
	}

	// Flush rewrite rules
	flush_rewrite_rules();
}

/**
 * Update user capabilities
 * Ensures all roles have the latest capabilities
 */
function ma_deal_room_update_capabilities() {
	if (!class_exists('MADealRoom\Core\UserRoles')) {
		return;
	}

	// Create instance and assign capabilities
	$user_roles = new MADealRoom\Core\UserRoles();

	// Get the protected method via reflection to call it
	$reflection = new \ReflectionClass($user_roles);
	$method = $reflection->getMethod('assign_capabilities_to_existing_roles');
	$method->setAccessible(true);
	$method->invoke($user_roles);

	if (defined('WP_DEBUG') && WP_DEBUG) {
		error_log('MA Deal Room: Updated user capabilities');
	}
}

add_action('init', function() {
	if (!is_admin()) {
		return;
	}

	if (!get_option('ma_deal_room_agent_dashboard_page_created')) {
		ma_deal_room_ensure_agent_dashboard_page();
		update_option('ma_deal_room_agent_dashboard_page_created', 1);
	}
});

/**
 * Ensure the agent dashboard page exists and uses the correct template.
 *
 * @return void
 */
function ma_deal_room_ensure_agent_dashboard_page(): void {
	$page_slug = 'agent-dashboard';
	$page_title = __('Agent Dashboard', 'ma-deal-room');

	$page = get_page_by_path($page_slug);

	if (!$page) {
		$page_id = wp_insert_post([
			'post_title' => $page_title,
			'post_name' => $page_slug,
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_content' => '',
		]);

		if (is_wp_error($page_id) || !$page_id) {
			return;
		}

		$page = get_post($page_id);
	}

	if (!$page) {
		return;
	}

	update_post_meta($page->ID, '_wp_page_template', 'ma-deal-room-agent-dashboard.php');
}

/**
 * Sync system templates from YAML files to database
 * Called during plugin activation
 *
 * @return array Results of template sync
 */
function ma_deal_room_sync_system_templates() {
	// Check if tables exist before syncing
	global $wpdb;
	$templates_table = $wpdb->prefix . 'ma_deal_templates';
	$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$templates_table}'") === $templates_table;

	if (!$table_exists) {
		// Tables don't exist yet - skip sync (will be done during reset)
		return ['error' => 'Database tables not initialized yet', 'skipped' => true];
	}

	$templates_dir = MA_DEAL_PATH . 'assets/templates/';

	if (!is_dir($templates_dir)) {
		return ['error' => 'Templates directory not found'];
	}

	$template_files = glob($templates_dir . '*.yaml');

	if (empty($template_files)) {
		return ['error' => 'No template files found'];
	}

	// Get template repository - skip if Plugin not initialized yet (during activation)
	try {
		if (!class_exists('MADealRoom\Core\Plugin')) {
			return ['error' => 'Plugin not initialized yet', 'skipped' => true];
		}
		$plugin = MADealRoom\Core\Plugin::instance();
		if (!$plugin || !method_exists($plugin, 'container')) {
			return ['error' => 'Plugin container not available', 'skipped' => true];
		}
		$template_repo = $plugin->container()->get('template_repository');
	} catch (\Exception $e) {
		return ['error' => 'Failed to get template repository: ' . $e->getMessage(), 'skipped' => true];
	}

	$synced = 0;
	$errors = 0;

	foreach ($template_files as $file) {
		$filename = basename($file);

		try {
			// Read YAML file
			$yaml_content = file_get_contents($file);
			if ($yaml_content === false) {
				throw new \Exception("Failed to read file");
			}

			// Parse YAML to extract metadata
			$parsed = \Symfony\Component\Yaml\Yaml::parse($yaml_content);

			// Extract template metadata
			$template_id = $parsed['template_id'] ?? pathinfo($filename, PATHINFO_FILENAME);
			$name = $parsed['title'] ?? $parsed['name'] ?? pathinfo($filename, PATHINFO_FILENAME);
			$description = $parsed['description'] ?? '';

			// Determine property type
			$property_types = $parsed['property_types'] ?? [];
			$property_type = 'Any';
			if (!empty($property_types)) {
				if (count($property_types) === 1) {
					$property_type = $property_types[0];
				}
			}

			// Check if template already exists
			global $wpdb;
			$table = $wpdb->prefix . 'ma_deal_templates';
			$existing = $wpdb->get_var($wpdb->prepare(
				"SELECT id FROM {$table} WHERE name = %s AND is_system = 1",
				$name
			));

			$template_data = [
				'account_id' => null,
				'name' => $name,
				'description' => $description,
				'property_type' => $property_type,
				'template_yaml' => $yaml_content,
				'is_system' => 1,
				'is_active' => 1,
				'version' => (int)($parsed['version'] ?? 1),
			];

			if ($existing) {
				// Update existing template
				$template_repo->update($existing, $template_data);
			} else {
				// Create new template
				$template_data['created_at'] = current_time('mysql');
				$template_data['updated_at'] = current_time('mysql');
				$template_repo->create($template_data);
			}

			$synced++;

		} catch (\Exception $e) {
			$errors++;
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log("Template sync error for {$filename}: " . $e->getMessage());
			}
		}
	}

	return [
		'synced' => $synced,
		'errors' => $errors,
		'total' => count($template_files),
	];
}

/**
 * Sync task definitions from YAML templates
 * Extracts task definitions from template YAML files and populates the task_definitions table
 *
 * @return array Results of the sync operation
 */
function ma_deal_room_sync_task_definitions() {
	// Check if tables exist before syncing
	global $wpdb;
	$task_defs_table = $wpdb->prefix . 'ma_deal_task_definitions';
	$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$task_defs_table}'") === $task_defs_table;

	if (!$table_exists) {
		// Tables don't exist yet - skip sync (will be done during reset)
		return ['error' => 'Database tables not initialized yet', 'skipped' => true];
	}

	$templates_dir = MA_DEAL_PATH . 'assets/templates/';

	if (!is_dir($templates_dir)) {
		return ['error' => 'Templates directory not found'];
	}

	$template_files = glob($templates_dir . '*.yaml');

	if (empty($template_files)) {
		return ['error' => 'No template files found'];
	}

	// Get task definition repository - skip if Plugin not initialized yet (during activation)
	try {
		if (!class_exists('MADealRoom\Core\Plugin')) {
			return ['error' => 'Plugin not initialized yet', 'skipped' => true];
		}
		$plugin = MADealRoom\Core\Plugin::instance();
		if (!$plugin || !method_exists($plugin, 'container')) {
			return ['error' => 'Plugin container not available', 'skipped' => true];
		}
		$task_def_repo = $plugin->container()->get('task_definition_repository');
	} catch (\Exception $e) {
		return ['error' => 'Failed to get task definition repository: ' . $e->getMessage(), 'skipped' => true];
	}

	$created = 0;
	$updated = 0;
	$skipped = 0;
	$errors = 0;

	// Track all task keys we've seen to avoid duplicates
	$seen_task_keys = [];

	foreach ($template_files as $file) {
		$filename = basename($file);

		try {
			// Read and parse YAML
			$yaml_content = file_get_contents($file);
			if ($yaml_content === false) {
				throw new \Exception("Failed to read file");
			}

			$parsed = \Symfony\Component\Yaml\Yaml::parse($yaml_content);

			// Extract tasks from the YAML structure
			$tasks = ma_deal_room_extract_tasks_from_yaml($parsed);

			if (empty($tasks)) {
				continue;
			}

			foreach ($tasks as $task) {
				try {
					$task_key = $task['id'] ?? null;

					if (!$task_key) {
						$skipped++;
						continue;
					}

					// Skip if we've already processed this task key in this run
					if (isset($seen_task_keys[$task_key])) {
						$skipped++;
						continue;
					}

					$seen_task_keys[$task_key] = true;

					// Prepare task definition data
					$task_data = ma_deal_room_prepare_task_definition($task);

					// Check if task definition already exists
					$existing = $task_def_repo->findByTaskKey($task_key, null);

					if ($existing) {
						// Update existing
						$task_def_repo->update($existing->id, $task_data);
						$updated++;
					} else {
						// Create new
						$task_data['created_at'] = current_time('mysql');
						$task_data['updated_at'] = current_time('mysql');
						$task_def_repo->create($task_data);
						$created++;
					}

				} catch (\Exception $e) {
					$errors++;
					if (defined('WP_DEBUG') && WP_DEBUG) {
						error_log("Task definition error for {$task_key}: " . $e->getMessage());
					}
				}
			}

		} catch (\Exception $e) {
			$errors++;
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log("Template processing error for {$filename}: " . $e->getMessage());
			}
		}
	}

	return [
		'created' => $created,
		'updated' => $updated,
		'skipped' => $skipped,
		'errors' => $errors,
		'total' => count($template_files),
	];
}

/**
 * Extract tasks from parsed YAML template
 *
 * @param array $parsed Parsed YAML data
 * @return array Array of tasks
 */
function ma_deal_room_extract_tasks_from_yaml(array $parsed): array {
	$tasks = [];

	// Extract from workflows (nested structure)
	if (isset($parsed['workflows']) && is_array($parsed['workflows'])) {
		foreach ($parsed['workflows'] as $workflow) {
			if (isset($workflow['tasks']) && is_array($workflow['tasks'])) {
				$tasks = array_merge($tasks, $workflow['tasks']);
			}
		}
	}

	// Extract from flat tasks array
	if (isset($parsed['tasks']) && is_array($parsed['tasks'])) {
		$tasks = array_merge($tasks, $parsed['tasks']);
	}

	// Extract from conditional_tasks
	if (isset($parsed['conditional_tasks']) && is_array($parsed['conditional_tasks'])) {
		foreach ($parsed['conditional_tasks'] as $conditional_group) {
			if (isset($conditional_group['tasks']) && is_array($conditional_group['tasks'])) {
				foreach ($conditional_group['tasks'] as $task) {
					// Add condition to task
					if (isset($conditional_group['condition'])) {
						$task['applies_if'] = $conditional_group['condition'];
					}
					$tasks[] = $task;
				}
			}
		}
	}

	return $tasks;
}

/**
 * Prepare task definition data for database
 *
 * @param array $task Task data from YAML
 * @return array Task definition data
 */
function ma_deal_room_prepare_task_definition(array $task): array {
	// Build due calculation from due + due_offset
	$due_calculation = null;
	if (isset($task['due'])) {
		$anchor = $task['due'];
		$offset = $task['due_offset'] ?? '+0d';
		$due_calculation = $anchor . $offset;
	}

	// Handle depends_on
	$depends_on = null;
	if (isset($task['depends_on']) && is_array($task['depends_on']) && !empty($task['depends_on'])) {
		$depends_on = wp_json_encode($task['depends_on']);
	}

	// Build metadata from various fields
	$metadata = [];
	if (isset($task['documents'])) {
		$metadata['documents'] = $task['documents'];
	}
	if (isset($task['reminders'])) {
		$metadata['reminders'] = $task['reminders'];
	}
	if (isset($task['citations'])) {
		$metadata['citations'] = $task['citations'];
	}
	if (isset($task['vendor_type'])) {
		$metadata['vendor_type'] = $task['vendor_type'];
	}
	if (isset($task['notes'])) {
		$metadata['notes'] = $task['notes'];
	}

	return [
		'task_key' => $task['id'],
		'category' => $task['category'] ?? 'other',
		'title' => $task['title'] ?? $task['id'],
		'description' => $task['description'] ?? '',
		'owner_role' => $task['owner_role'] ?? 'agent',
		'priority' => $task['priority'] ?? 'normal',
		'estimated_duration' => isset($task['estimated_duration']) && is_numeric($task['estimated_duration'])
			? (int)$task['estimated_duration']
			: null,
		'due_calculation' => $due_calculation,
		'applies_if' => $task['applies_if'] ?? null,
		'depends_on' => $depends_on,
		'metadata' => !empty($metadata) ? wp_json_encode($metadata) : null,
		'is_system' => 1,
		'is_milestone' => isset($task['milestone']) && $task['milestone'] ? 1 : 0,
		'is_required' => isset($task['mandatory']) && $task['mandatory'] ? 1 : 0,
		'account_id' => null,
		'created_by_user_id' => null,
		'updated_at' => current_time('mysql'),
	];
}

/**
 * Register custom capabilities for the plugin
 */
function ma_deal_room_register_capabilities() {
	$roles = get_editable_roles();

	$capabilities = [
		'read_deal_room_transactions',
		'manage_deal_room_transactions',
		'read_deal_room_tasks',
		'manage_deal_room_tasks',
		'read_deal_room_reminders',
		'manage_deal_room_reminders',
		'read_deal_room_templates',
		'manage_deal_room_templates',
		'read_deal_room_vendor_requests',
		'manage_deal_room_vendor_requests',
	];

	foreach ($roles as $role_name => $role_info) {
		$role = get_role($role_name);

		// Grant all custom capabilities to administrators
		if ($role_name === 'administrator') {
			foreach ($capabilities as $cap) {
				$role->add_cap($cap);
			}
		}

		// Example: Grant read capabilities to editors
		if ($role_name === 'editor') {
			$role->add_cap('read_deal_room_transactions');
			$role->add_cap('read_deal_room_tasks');
			$role->add_cap('read_deal_room_reminders');
			$role->add_cap('read_deal_room_templates');
			$role->add_cap('read_deal_room_vendor_requests');
		}
	}
}

/**
 * Ensure a default account exists for the site
 * Creates an account for the first admin user if none exists
 */
function ma_deal_room_ensure_default_account() {
	global $wpdb;

	// Check if tables exist before trying to create account
	$accounts_table = $wpdb->prefix . 'ma_deal_accounts';
	$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$accounts_table}'") === $accounts_table;

	if (!$table_exists) {
		// Tables don't exist yet - skip (will be done during reset)
		return;
	}

	// Check if any accounts exist
	$account_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ma_deal_accounts");

	if ($account_count > 0) {
		// Accounts already exist, nothing to do
		return;
	}

	// Get the first admin user
	$admin_users = get_users([
		'role' => 'administrator',
		'number' => 1,
		'orderby' => 'ID',
		'order' => 'ASC',
	]);

	if (empty($admin_users)) {
		// No admin users found, can't create account
		return;
	}

	$admin_user = $admin_users[0];

	// Get plugin instance and account repository - skip if Plugin not initialized yet (during activation)
	try {
		if (!class_exists('MADealRoom\Core\Plugin')) {
			return; // Skip if Plugin class not loaded yet
		}
		$plugin = MADealRoom\Core\Plugin::instance();
		if (!$plugin || !method_exists($plugin, 'container')) {
			return; // Skip if Plugin container not available
		}
		$account_repo = $plugin->container()->get('account_repository');
	} catch (\Exception $e) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('MA Deal Room: Failed to create default account: ' . $e->getMessage());
		}
		return;
	}

	// Create default account
	$account_data = [
		'name' => get_bloginfo('name') . ' Real Estate',
		'owner_user_id' => $admin_user->ID,
		'status' => 'active',
		'subscription_tier' => 'professional',
		'subscription_expires_at' => date('Y-m-d H:i:s', strtotime('+10 years')),
		'settings' => json_encode([
			'max_transactions' => 1000,
			'max_users' => 50,
			'custom_templates' => true,
			'api_access' => true,
		]),
		'created_at' => current_time('mysql'),
		'updated_at' => current_time('mysql'),
	];

	$account = $account_repo->create($account_data);

	if ($account && defined('WP_DEBUG') && WP_DEBUG) {
		error_log(sprintf(
			'MA Deal Room: Created default account (ID: %d) for user %s',
			$account->id,
			$admin_user->user_login
		));
	}
}

/**
 * Schedule notification queue processing
 */
add_action('init', function() {
	if (!wp_next_scheduled('ma_deal_room_process_queue')) {
		// Schedule to run every 2 minutes
		wp_schedule_event(time(), 'ma_deal_room_two_minutes', 'ma_deal_room_process_queue');
	}
});

/**
 * Add custom cron schedule for queue processing
 */
add_filter('cron_schedules', function($schedules) {
	$schedules['ma_deal_room_two_minutes'] = [
		'interval' => 120, // 2 minutes in seconds
		'display' => __('Every 2 Minutes', 'ma-deal-room')
	];
	return $schedules;
});

/**
 * Process notification queue cron job
 */
add_action('ma_deal_room_process_queue', function() {
	$queue_service = new MADealRoom\Services\NotificationQueueService();
	$stats = $queue_service->processPending();

	if (defined('WP_DEBUG') && WP_DEBUG && $stats['processed'] > 0) {
		error_log(sprintf(
			'[NotificationQueue] Processed %d notifications - Sent: %d, Bundled: %d, Failed: %d',
			$stats['processed'],
			$stats['sent'],
			$stats['bundled'],
			$stats['failed']
		));
	}
});

/**
 * Handle email verification from email link
 *
 * Intercepts requests to /verify-email?token=... and processes the verification
 */
add_action('template_redirect', function() {
	// Check if requesting /verify-email
	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$parsed_url = parse_url($request_uri);
	$path = $parsed_url['path'] ?? '';

	// Check if path matches /verify-email or /verify-email/
	if (strpos($path, '/verify-email') !== 0) {
		return;
	}

	// Get the token from query parameter
	$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

	if (empty($token)) {
		// No token provided, redirect to home
		wp_safe_redirect(home_url('/'));
		exit;
	}

	try {
		// Verify the email token via the service
		if (class_exists('MADealRoom\Services\EmailVerificationService')) {
			$verification_service = new MADealRoom\Services\EmailVerificationService();
			$result = $verification_service->verify_email($token);

			error_log('[Email Verification] Verification attempt for token: ' . substr($token, 0, 10) . '...');

			if (is_wp_error($result)) {
				// Token invalid or expired
				$error_message = $result->get_error_message();
				error_log('[Email Verification] Verification failed: ' . $error_message);

				// Redirect to verify-email page with error status
				wp_safe_redirect(add_query_arg([
					'verification_failed' => '1',
					'error' => urlencode($error_message)
				], home_url('/admin/#/auth/verify-email')));
			} else {
				// Verification successful, redirect to login page with success message
				error_log('[Email Verification] Verification successful!');
				wp_safe_redirect(add_query_arg('verified', '1', home_url('/admin/#/auth/login')));
			}
		} else {
			// Plugin not initialized, redirect to home
			error_log('[Email Verification] Plugin class not found');
			wp_safe_redirect(home_url('/'));
		}
	} catch (Exception $e) {
		// Log error and redirect
		error_log('[Email Verification] Exception: ' . $e->getMessage());
		wp_safe_redirect(home_url('/'));
	}

	exit;
});

/**
 * Plugin uninstall hook (defined in uninstall.php)
 */
// See uninstall.php for cleanup logic
