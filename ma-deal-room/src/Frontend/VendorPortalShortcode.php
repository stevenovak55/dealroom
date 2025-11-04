<?php
/**
 * Vendor Portal Shortcode
 *
 * @package MADealRoom\Frontend
 * @since 2.1.0
 */

namespace MADealRoom\Frontend;

/**
 * Vendor Portal Shortcode Handler
 */
class VendorPortalShortcode {
	/**
	 * Register shortcode
	 */
	public static function register(): void {
		add_shortcode('vendor_portal', [self::class, 'render']);
	}

	/**
	 * Render vendor portal
	 *
	 * @param array $atts Shortcode attributes
	 * @return string HTML output
	 */
	public static function render($atts = []): string {
		// Load the standalone vendor portal HTML
		$plugin_path = dirname(__FILE__, 3);
		$vendor_portal_path = $plugin_path . '/assets/vendor-portal.html';

		if (!file_exists($vendor_portal_path)) {
			return '<div class="vendor-portal-error" style="padding: 20px; background: #fee; border: 1px solid #fcc; color: #c00; border-radius: 8px;">
				<h2>Vendor Portal Not Found</h2>
				<p>The vendor portal file is missing or inaccessible.</p>
			</div>';
		}

		// Read and return the HTML file
		return file_get_contents($vendor_portal_path);
	}
}
