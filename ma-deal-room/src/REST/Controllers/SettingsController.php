<?php
/**
 * Settings REST Controller
 *
 * Handles account settings management
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Settings controller
 */
class SettingsController extends BaseController {

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->rest_base = 'settings';

		// GET /settings - Get current account settings
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods' => 'GET',
			'callback' => [ $this, 'get_settings' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );

		// PUT /settings - Update account settings
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods' => 'PUT',
			'callback' => [ $this, 'update_settings' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );
	}

	/**
	 * Get account settings
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_settings( WP_REST_Request $request ) {
		try {
			$account_id = $this->get_user_account_id();
			if ( ! $account_id ) {
				return new WP_Error(
					'no_account',
					'No account found for current user',
					[ 'status' => 404 ]
				);
			}

			global $wpdb;
			$table = $wpdb->prefix . 'ma_deal_accounts';

			$account = $wpdb->get_row( $wpdb->prepare(
				"SELECT id, name, settings FROM {$table} WHERE id = %d",
				$account_id
			), ARRAY_A );

			if ( ! $account ) {
				return new WP_Error(
					'account_not_found',
					'Account not found',
					[ 'status' => 404 ]
				);
			}

			// Parse settings JSON or return defaults
			$settings = $account['settings'] ? json_decode( $account['settings'], true ) : [];

			// Merge with defaults
			$default_settings = $this->get_default_settings();
			$settings = array_merge( $default_settings, $settings );

			// Add account name
			$settings['account_name'] = $account['name'];

			return $this->success( $settings );

		} catch ( \Exception $e ) {
			return $this->error(
				'Error fetching settings: ' . $e->getMessage(),
				500,
				'settings_fetch_failed'
			);
		}
	}

	/**
	 * Update account settings
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_settings( WP_REST_Request $request ) {
		try {
			$account_id = $this->get_user_account_id();
			if ( ! $account_id ) {
				return new WP_Error(
					'no_account',
					'No account found for current user',
					[ 'status' => 404 ]
				);
			}

			// Get settings from request
			$new_settings = $request->get_json_params();

			// Extract account name separately if provided
			$account_name = null;
			if ( isset( $new_settings['account_name'] ) ) {
				$account_name = sanitize_text_field( $new_settings['account_name'] );
				unset( $new_settings['account_name'] );
			}

			// Validate and sanitize settings
			$validated_settings = $this->validate_settings( $new_settings );

			global $wpdb;
			$table = $wpdb->prefix . 'ma_deal_accounts';

			// Prepare update data
			$update_data = [
				'settings' => wp_json_encode( $validated_settings ),
				'updated_at' => current_time( 'mysql' ),
			];
			$update_format = [ '%s', '%s' ];

			// Include account name if provided
			if ( $account_name !== null ) {
				$update_data['name'] = $account_name;
				$update_format[] = '%s';
			}

			// Update database
			$result = $wpdb->update(
				$table,
				$update_data,
				[ 'id' => $account_id ],
				$update_format,
				[ '%d' ]
			);

			if ( $result === false ) {
				return new WP_Error(
					'update_failed',
					'Failed to update settings: ' . $wpdb->last_error,
					[ 'status' => 500 ]
				);
			}

			// Return updated settings
			$validated_settings['account_name'] = $account_name ?: '';

			return $this->success(
				$validated_settings,
				'Settings updated successfully'
			);

		} catch ( \Exception $e ) {
			return $this->error(
				'Error updating settings: ' . $e->getMessage(),
				500,
				'settings_update_failed'
			);
		}
	}

	/**
	 * Get default settings structure
	 *
	 * @return array Default settings
	 */
	private function get_default_settings(): array {
		return [
			'account_name' => '',
			'company_name' => '',
			'email' => get_option( 'admin_email', '' ),
			'phone' => '',
			'timezone' => get_option( 'timezone_string', 'America/New_York' ),
			'notifications' => [
				'email' => true,
				'sms' => false,
				'daily_digest' => true,
			],
			'branding' => [
				'logo_url' => '',
				'primary_color' => '#3b82f6',
			],
		];
	}

	/**
	 * Validate and sanitize settings
	 *
	 * @param array $settings Raw settings from request
	 * @return array Validated settings
	 */
	private function validate_settings( array $settings ): array {
		$validated = [];

		// Company name
		if ( isset( $settings['company_name'] ) ) {
			$validated['company_name'] = sanitize_text_field( $settings['company_name'] );
		}

		// Email
		if ( isset( $settings['email'] ) ) {
			$email = sanitize_email( $settings['email'] );
			if ( is_email( $email ) ) {
				$validated['email'] = $email;
			}
		}

		// Phone
		if ( isset( $settings['phone'] ) ) {
			$validated['phone'] = sanitize_text_field( $settings['phone'] );
		}

		// Timezone
		if ( isset( $settings['timezone'] ) ) {
			$validated['timezone'] = sanitize_text_field( $settings['timezone'] );
		}

		// Notifications
		if ( isset( $settings['notifications'] ) && is_array( $settings['notifications'] ) ) {
			$validated['notifications'] = [
				'email' => isset( $settings['notifications']['email'] )
					? (bool) $settings['notifications']['email']
					: true,
				'sms' => isset( $settings['notifications']['sms'] )
					? (bool) $settings['notifications']['sms']
					: false,
				'daily_digest' => isset( $settings['notifications']['daily_digest'] )
					? (bool) $settings['notifications']['daily_digest']
					: true,
			];
		}

		// Branding
		if ( isset( $settings['branding'] ) && is_array( $settings['branding'] ) ) {
			$validated['branding'] = [
				'logo_url' => isset( $settings['branding']['logo_url'] )
					? esc_url_raw( $settings['branding']['logo_url'] )
					: '',
				'primary_color' => isset( $settings['branding']['primary_color'] )
					? sanitize_hex_color( $settings['branding']['primary_color'] )
					: '#3b82f6',
			];
		}

		return $validated;
	}
}
