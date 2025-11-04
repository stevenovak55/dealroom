<?php
/**
 * Templates WP-CLI Command
 *
 * @package MADealRoom\CLI
 * @since 1.0.0
 */

namespace MADealRoom\CLI;

use MADealRoom\Core\Plugin;
use WP_CLI;

/**
 * Manage task templates
 */
class TemplatesCommand {
	/**
	 * Sync system templates
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal templates:sync
	 *
	 * @when after_wp_load
	 */
	public function sync($args, $assoc_args) {
		WP_CLI::log('Syncing system templates...');

		$templates_dir = MA_DEAL_PATH . 'assets/templates/';

		if (!is_dir($templates_dir)) {
			WP_CLI::error("Templates directory not found: {$templates_dir}");
			return;
		}

		$template_files = glob($templates_dir . '*.yaml');

		if (empty($template_files)) {
			WP_CLI::warning('No template files found.');
			return;
		}

		WP_CLI::success(sprintf('Found %d template files.', count($template_files)));

		$plugin = Plugin::instance();
		$template_repo = $plugin->container()->get('template_repository');

		$synced = 0;
		$errors = 0;

		foreach ($template_files as $file) {
			$filename = basename($file);
			WP_CLI::log("Processing: {$filename}");

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
					// If multiple types, use 'Any', otherwise use the specific type
					if (count($property_types) === 1) {
						$property_type = $property_types[0];
					}
				}

				// Determine transaction side
				$transaction_side = $parsed['transaction_side'] ?? 'both';
				// Validate transaction_side value
				if (!in_array($transaction_side, ['listing', 'buyer', 'both'])) {
					$transaction_side = 'both';
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
					'transaction_side' => $transaction_side,
					'template_yaml' => $yaml_content,
					'is_system' => 1,
					'is_active' => 1,
					'version' => (int)($parsed['version'] ?? 1),
				];

				if ($existing) {
					// Update existing template
					$template_repo->update($existing, $template_data);
					WP_CLI::log("  ✓ Updated: {$name}");
				} else {
					// Create new template
					$template_data['created_at'] = current_time('mysql');
					$template_data['updated_at'] = current_time('mysql');
					$template_repo->create($template_data);
					WP_CLI::log("  ✓ Created: {$name}");
				}

				$synced++;

			} catch (\Exception $e) {
				WP_CLI::warning("  ✗ Error processing {$filename}: " . $e->getMessage());
				$errors++;
			}
		}

		if ($synced > 0) {
			WP_CLI::success("Template sync completed. Synced: {$synced}, Errors: {$errors}");
		} else {
			WP_CLI::error("No templates were synced successfully.");
		}
	}

	/**
	 * List available templates
	 *
	 * ## EXAMPLES
	 *
	 *     wp ma-deal templates:list
	 *
	 * @when after_wp_load
	 */
	public function list($args, $assoc_args) {
		$plugin = Plugin::instance();
		$template_repo = $plugin->container()->get('template_repository');

		$templates = $template_repo->findAll();

		if (empty($templates)) {
			WP_CLI::log('No templates found.');
			return;
		}

		$items = [];
		foreach ($templates as $template) {
			$items[] = [
				'ID' => $template->id,
				'Name' => $template->name,
				'Type' => $template->property_type,
				'System' => $template->is_system ? 'Yes' : 'No',
				'Active' => $template->is_active ? 'Yes' : 'No',
			];
		}

		WP_CLI\Utils\format_items('table', $items, ['ID', 'Name', 'Type', 'System', 'Active']);
	}
}
