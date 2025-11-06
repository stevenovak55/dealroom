<?php
/**
 * User Profile REST Controller
 *
 * Handles user profile data and preferences
 *
 * @package MADealRoom\REST\Controllers
 * @since 1.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * User Profile controller
 */
class UserProfileController extends BaseController {

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->rest_base = 'profile';

		// GET /profile - Get current user profile
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods' => 'GET',
			'callback' => [ $this, 'get_profile' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );

		// PUT /profile - Update user profile
		register_rest_route( $this->namespace, '/' . $this->rest_base, [
			'methods' => 'PUT',
			'callback' => [ $this, 'update_profile' ],
			'permission_callback' => [ $this, 'permission_callback' ],
		] );
	}

	/**
	 * Get current user profile
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_profile( WP_REST_Request $request ) {
		try {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				return new WP_Error(
					'not_authenticated',
					'User not authenticated',
					[ 'status' => 401 ]
				);
			}

			$user = get_userdata( $user_id );

			if ( ! $user ) {
				return new WP_Error(
					'user_not_found',
					'User not found',
					[ 'status' => 404 ]
				);
			}

			// Get user meta for preferences
			$preferences = get_user_meta( $user_id, 'ma_deal_preferences', true );
			if ( ! $preferences ) {
				$preferences = $this->get_default_preferences();
			}

			$profile = [
				'id' => $user->ID,
				'username' => $user->user_login,
				'email' => $user->user_email,
				'display_name' => $user->display_name,
				'first_name' => $user->first_name,
				'last_name' => $user->last_name,
				'avatar_url' => get_avatar_url( $user->ID, [ 'size' => 96 ] ),
				'role' => ! empty( $user->roles ) ? $user->roles[0] : 'subscriber',
				'registered_date' => $user->user_registered,
				'preferences' => $preferences,
			];

			return $this->success( $profile );

		} catch ( \Exception $e ) {
			return $this->error(
				'Error fetching profile: ' . $e->getMessage(),
				500,
				'profile_fetch_failed'
			);
		}
	}

	/**
	 * Update user profile
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_profile( WP_REST_Request $request ) {
		try {
			$user_id = get_current_user_id();

			if ( ! $user_id ) {
				return new WP_Error(
					'not_authenticated',
					'User not authenticated',
					[ 'status' => 401 ]
				);
			}

			$data = $request->get_json_params();

			// Update user data
			$user_data = [];

			if ( isset( $data['display_name'] ) ) {
				$user_data['ID'] = $user_id;
				$user_data['display_name'] = sanitize_text_field( $data['display_name'] );
			}

			if ( isset( $data['first_name'] ) ) {
				$user_data['first_name'] = sanitize_text_field( $data['first_name'] );
			}

			if ( isset( $data['last_name'] ) ) {
				$user_data['last_name'] = sanitize_text_field( $data['last_name'] );
			}

			// Update user if we have data to update
			if ( ! empty( $user_data ) ) {
				$user_data['ID'] = $user_id;
				$result = wp_update_user( $user_data );

				if ( is_wp_error( $result ) ) {
					return $this->error(
						'Failed to update user: ' . $result->get_error_message(),
						500,
						'user_update_failed'
					);
				}
			}

			// Update preferences
			if ( isset( $data['preferences'] ) ) {
				$validated_preferences = $this->validate_preferences( $data['preferences'] );
				update_user_meta( $user_id, 'ma_deal_preferences', $validated_preferences );
			}

			// Get updated profile
			return $this->get_profile( $request );

		} catch ( \Exception $e ) {
			return $this->error(
				'Error updating profile: ' . $e->getMessage(),
				500,
				'profile_update_failed'
			);
		}
	}

	/**
	 * Get default user preferences
	 *
	 * @return array Default preferences
	 */
	private function get_default_preferences(): array {
		return [
			'language' => 'en',
			'date_format' => 'm/d/Y',
			'time_format' => 'g:i A',
			'items_per_page' => 25,
			'email_notifications' => true,
		];
	}

	/**
	 * Validate user preferences
	 *
	 * @param array $preferences Raw preferences
	 * @return array Validated preferences
	 */
	private function validate_preferences( array $preferences ): array {
		$validated = [];

		if ( isset( $preferences['language'] ) ) {
			$validated['language'] = sanitize_text_field( $preferences['language'] );
		}

		if ( isset( $preferences['date_format'] ) ) {
			$validated['date_format'] = sanitize_text_field( $preferences['date_format'] );
		}

		if ( isset( $preferences['time_format'] ) ) {
			$validated['time_format'] = sanitize_text_field( $preferences['time_format'] );
		}

		if ( isset( $preferences['items_per_page'] ) ) {
			$validated['items_per_page'] = absint( $preferences['items_per_page'] );
		}

		if ( isset( $preferences['email_notifications'] ) ) {
			$validated['email_notifications'] = (bool) $preferences['email_notifications'];
		}

		return $validated;
	}
}
