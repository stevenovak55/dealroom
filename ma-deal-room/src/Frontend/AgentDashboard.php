<?php
/**
 * Frontend Agent Dashboard Integration
 *
 * @package MADealRoom\Frontend
 * @since 1.1.0
 */

namespace MADealRoom\Frontend;

/**
 * Registers the agent dashboard page template and assets on the public site.
 */
class AgentDashboard {
	/**
	 * Page template slug.
	 *
	 * @var string
	 */
	private const TEMPLATE_SLUG = 'ma-deal-room-agent-dashboard.php';

	/**
	 * Template file relative to plugin root.
	 *
	 * @var string
	 */
	private const TEMPLATE_FILE = 'assets/public/agent-dashboard.php';

	/**
	 * Bootstrap the hooks required for the dashboard.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter('theme_page_templates', [$this, 'register_template'], 10, 4);
		add_action('init', [$this, 'flush_template_cache']);
		add_filter('template_include', [$this, 'inject_template']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
		add_filter('show_admin_bar', [$this, 'maybe_hide_admin_bar']);
	}

	/**
	 * Make the dashboard template selectable in the page editor.
	 *
	 * @param array       $templates Existing templates.
	 * @param \WP_Theme   $theme     Current theme.
	 * @param \WP_Post    $post      Current post object.
	 * @param string      $post_type Current post type.
	 * @return array
	 */
	public function register_template($templates, $theme, $post, $post_type) {
		if (!is_array($templates)) {
			$templates = [];
		}

		$templates[self::TEMPLATE_SLUG] = __('MA Deal Room Agent Dashboard', 'ma-deal-room');
		return $templates;
	}

	/**
	 * Clear the page template cache so WordPress picks up the plugin template.
	 *
	 * @return void
	 */
	public function flush_template_cache(): void {
		if (!is_admin()) {
			return;
		}

		$cache_key = 'page_templates-' . md5(get_stylesheet() . ':' . get_template());
		wp_cache_delete($cache_key, 'themes');
	}

	/**
	 * Swap in the plugin template when selected.
	 *
	 * @param string $template Current template path.
	 * @return string
	 */
	public function inject_template(string $template): string {
		if (!is_singular('page')) {
			return $template;
		}

		global $post;

		if (!$post) {
			return $template;
		}

		if (get_page_template_slug($post) !== self::TEMPLATE_SLUG) {
			return $template;
		}

		$plugin_template = MA_DEAL_PATH . self::TEMPLATE_FILE;

		if (file_exists($plugin_template)) {
			return $plugin_template;
		}

		return $template;
	}

	/**
	 * Enqueue dashboard assets when the template is in use.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$post = get_queried_object();

		if (!$post instanceof \WP_Post || get_page_template_slug($post) !== self::TEMPLATE_SLUG) {
			return;
		}

		$css_path = MA_DEAL_PATH . 'assets/admin/dist/assets/index.css';
		if (file_exists($css_path)) {
			wp_enqueue_style(
				'ma-deal-room-admin',
				MA_DEAL_URL . 'assets/admin/dist/assets/index.css',
				[],
				filemtime($css_path)
			);
		}

		$js_path = MA_DEAL_PATH . 'assets/admin/dist/assets/index.js';
		if (file_exists($js_path)) {
			wp_enqueue_script(
				'ma-deal-room-admin',
				MA_DEAL_URL . 'assets/admin/dist/assets/index.js',
				[],
				filemtime($js_path),
				true
			);

			$current_user = wp_get_current_user();

			wp_localize_script('ma-deal-room-admin', 'maDealRoom', [
				'apiUrl' => rest_url('ma-deal-room/v1'),
				'nonce' => wp_create_nonce('wp_rest'),
				'currentUser' => $current_user->ID,
			]);
		}
	}

	/**
	 * Hide the WordPress admin toolbar on the front-end dashboard.
	 *
	 * @param bool $show Current state.
	 * @return bool
	 */
	public function maybe_hide_admin_bar(bool $show): bool {
		if (is_page_template(self::TEMPLATE_SLUG)) {
			return false;
		}

		return $show;
	}
}
