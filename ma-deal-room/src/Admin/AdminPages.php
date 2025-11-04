<?php
/**
 * Admin Pages
 *
 * @package MADealRoom\Admin
 * @since 1.0.0
 */

namespace MADealRoom\Admin;

/**
 * Admin menu and pages
 */
class AdminPages {
	/**
	 * Register admin menu
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__('MA Deal Room', 'ma-deal-room'),
			__('MA Deal Room', 'ma-deal-room'),
			'edit_posts',
			'ma-deal-room',
			[$this, 'render_admin_page'],
			'dashicons-clipboard',
			30
		);

		// Sub-menu items
		add_submenu_page(
			'ma-deal-room',
			__('Dashboard', 'ma-deal-room'),
			__('Dashboard', 'ma-deal-room'),
			'edit_posts',
			'ma-deal-room',
			[$this, 'render_admin_page']
		);

		add_submenu_page(
			'ma-deal-room',
			__('Transactions', 'ma-deal-room'),
			__('Transactions', 'ma-deal-room'),
			'edit_posts',
			'ma-deal-room-transactions',
			[$this, 'render_admin_page']
		);

		add_submenu_page(
			'ma-deal-room',
			__('Templates', 'ma-deal-room'),
			__('Templates', 'ma-deal-room'),
			'edit_posts',
			'ma-deal-room-templates',
			[$this, 'render_admin_page']
		);

		add_submenu_page(
			'ma-deal-room',
			__('Settings', 'ma-deal-room'),
			__('Settings', 'ma-deal-room'),
			'manage_options',
			'ma-deal-room-settings',
			[$this, 'render_admin_page']
		);
	}

	/**
	 * Render admin page
	 *
	 * @return void
	 */
	public function render_admin_page(): void {
		?>
		<div class="wrap">
			<!-- React app will mount here -->
			<div id="ma-deal-room-app"></div>

			<!-- Fallback message if React fails to load -->
			<noscript>
				<div class="notice notice-error">
					<p><?php _e('JavaScript is required to use the MA Deal Room admin interface.', 'ma-deal-room'); ?></p>
				</div>
			</noscript>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public function enqueue_admin_assets(string $hook): void {
		// Only load on MA Deal Room pages
		if (strpos($hook, 'ma-deal-room') === false) {
			return;
		}

		// Enqueue React app bundle
		$asset_file = MA_DEAL_PATH . 'assets/admin/dist/assets/index.js';
		$css_file = MA_DEAL_PATH . 'assets/admin/dist/assets/index.css';

		if (file_exists($asset_file)) {
			wp_enqueue_script(
				'ma-deal-room-admin',
				MA_DEAL_URL . 'assets/admin/dist/assets/index.js',
				[],
				filemtime($asset_file),
				true
			);

			// Localize script with WordPress data
			wp_localize_script('ma-deal-room-admin', 'maDealRoom', [
				'apiUrl' => rest_url('ma-deal-room/v1'),
				'nonce' => wp_create_nonce('wp_rest'),
				'currentUser' => get_current_user_id(),
			]);
		}

		if (file_exists($css_file)) {
			wp_enqueue_style(
				'ma-deal-room-admin',
				MA_DEAL_URL . 'assets/admin/dist/assets/index.css',
				[],
				filemtime($css_file)
			);
		}
	}
}
