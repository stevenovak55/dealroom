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
		// Get the token from URL parameters
		$token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

		if (empty($token)) {
			return '<div class="vendor-portal-error" style="padding: 20px; background: #fee; border: 1px solid #fcc; color: #c00; border-radius: 8px;">
				<h2>Invalid Access</h2>
				<p>No token provided. Please use the link from your invitation email.</p>
			</div>';
		}

		// Redirect to the React app vendor portal with the token
		$vendor_portal_url = home_url('/agent-dashboard/#/vendor-portal?token=' . $token);

		// Use JavaScript redirect to handle the hash routing
		return '<div style="padding: 40px; text-align: center;">
			<h2>Loading Vendor Portal...</h2>
			<p>Please wait while we redirect you to the vendor portal.</p>
			<script>
				window.location.href = "' . esc_js($vendor_portal_url) . '";
			</script>
			<noscript>
				<p>JavaScript is required for the vendor portal.</p>
				<p><a href="' . esc_attr($vendor_portal_url) . '">Click here to continue</a></p>
			</noscript>
		</div>';
	}
}
