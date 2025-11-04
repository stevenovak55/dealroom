<?php
/**
 * Notification Preferences Service
 *
 * Manages user notification preferences for email and SMS notifications
 *
 * @package MADealRoom\Services
 * @since 1.0.0
 */

namespace MADealRoom\Services;

/**
 * Service for managing user notification preferences
 */
class NotificationPreferencesService {
	/**
	 * Meta key for storing notification preferences
	 *
	 * @var string
	 */
	const META_KEY = 'ma_deal_notification_preferences';

	/**
	 * Default notification preferences
	 *
	 * @var array
	 */
	const DEFAULT_PREFERENCES = [
		'email_enabled' => true,
		'sms_enabled' => true,
		'task_reminders' => [
			'1_day_before' => true,
			'3_days_before' => true,
			'1_week_before' => true,
		],
		'daily_digest' => true,
		'transaction_updates' => true,
		'document_uploads' => true,
		'task_assignments' => true,
		'task_status_changes' => true,
		'party_added' => true,
		'vendor_requests' => true,
	];

	/**
	 * Get user notification preferences
	 *
	 * @param int $user_id WordPress user ID
	 * @return array User preferences merged with defaults
	 */
	public function get_preferences(int $user_id): array {
		$preferences = get_user_meta($user_id, self::META_KEY, true);

		// If no preferences set, return defaults
		if (empty($preferences) || !is_array($preferences)) {
			return self::DEFAULT_PREFERENCES;
		}

		// Merge with defaults to ensure all keys exist
		return array_merge(self::DEFAULT_PREFERENCES, $preferences);
	}

	/**
	 * Update user notification preferences
	 *
	 * @param int $user_id WordPress user ID
	 * @param array $preferences Preferences to update
	 * @return bool Whether preferences were updated successfully
	 */
	public function update_preferences(int $user_id, array $preferences): bool {
		// Get current preferences
		$current = $this->get_preferences($user_id);

		// Merge with provided preferences (only update specified keys)
		$updated = array_merge($current, $preferences);

		// Validate preferences structure
		if (!$this->validate_preferences($updated)) {
			return false;
		}

		// Update user meta
		return update_user_meta($user_id, self::META_KEY, $updated);
	}

	/**
	 * Check if user has email notifications enabled
	 *
	 * @param int $user_id WordPress user ID
	 * @return bool
	 */
	public function is_email_enabled(int $user_id): bool {
		$preferences = $this->get_preferences($user_id);
		return (bool) ($preferences['email_enabled'] ?? true);
	}

	/**
	 * Check if user has SMS notifications enabled
	 *
	 * @param int $user_id WordPress user ID
	 * @return bool
	 */
	public function is_sms_enabled(int $user_id): bool {
		$preferences = $this->get_preferences($user_id);
		return (bool) ($preferences['sms_enabled'] ?? true);
	}

	/**
	 * Check if user wants specific notification type
	 *
	 * @param int $user_id WordPress user ID
	 * @param string $notification_type Type of notification (daily_digest, transaction_updates, etc.)
	 * @return bool
	 */
	public function wants_notification(int $user_id, string $notification_type): bool {
		$preferences = $this->get_preferences($user_id);
		return (bool) ($preferences[$notification_type] ?? true);
	}

	/**
	 * Check if user wants task reminder at specific interval
	 *
	 * @param int $user_id WordPress user ID
	 * @param string $interval Interval (1_day_before, 3_days_before, 1_week_before)
	 * @return bool
	 */
	public function wants_task_reminder(int $user_id, string $interval): bool {
		$preferences = $this->get_preferences($user_id);
		return (bool) ($preferences['task_reminders'][$interval] ?? true);
	}

	/**
	 * Disable all email notifications for user
	 *
	 * @param int $user_id WordPress user ID
	 * @return bool
	 */
	public function disable_email(int $user_id): bool {
		return $this->update_preferences($user_id, ['email_enabled' => false]);
	}

	/**
	 * Disable all SMS notifications for user
	 *
	 * @param int $user_id WordPress user ID
	 * @return bool
	 */
	public function disable_sms(int $user_id): bool {
		return $this->update_preferences($user_id, ['sms_enabled' => false]);
	}

	/**
	 * Enable email notifications for user
	 *
	 * @param int $user_id WordPress user ID
	 * @return bool
	 */
	public function enable_email(int $user_id): bool {
		return $this->update_preferences($user_id, ['email_enabled' => true]);
	}

	/**
	 * Enable SMS notifications for user
	 *
	 * @param int $user_id WordPress user ID
	 * @return bool
	 */
	public function enable_sms(int $user_id): bool {
		return $this->update_preferences($user_id, ['sms_enabled' => true]);
	}

	/**
	 * Validate preferences structure
	 *
	 * @param array $preferences Preferences to validate
	 * @return bool
	 */
	protected function validate_preferences(array $preferences): bool {
		// Check that required keys exist
		$required_keys = ['email_enabled', 'sms_enabled', 'task_reminders'];
		foreach ($required_keys as $key) {
			if (!isset($preferences[$key])) {
				return false;
			}
		}

		// Validate task_reminders is an array
		if (!is_array($preferences['task_reminders'])) {
			return false;
		}

		// Validate boolean values
		$boolean_keys = [
			'email_enabled',
			'sms_enabled',
			'daily_digest',
			'transaction_updates',
			'document_uploads',
			'task_assignments',
			'task_status_changes',
			'party_added',
			'vendor_requests',
		];

		foreach ($boolean_keys as $key) {
			if (isset($preferences[$key]) && !is_bool($preferences[$key])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Generate unsubscribe token for user
	 *
	 * @param int $user_id WordPress user ID
	 * @param string $type Type of unsubscribe (email, sms, all)
	 * @return string Unsubscribe token
	 */
	public function generate_unsubscribe_token(int $user_id, string $type = 'email'): string {
		// Create a hash based on user ID, type, and secret
		$secret = wp_salt('nonce');
		$data = $user_id . '|' . $type . '|' . $secret;
		return hash_hmac('sha256', $data, $secret);
	}

	/**
	 * Verify unsubscribe token
	 *
	 * @param int $user_id WordPress user ID
	 * @param string $token Token to verify
	 * @param string $type Type of unsubscribe (email, sms, all)
	 * @return bool Whether token is valid
	 */
	public function verify_unsubscribe_token(int $user_id, string $token, string $type = 'email'): bool {
		$expected_token = $this->generate_unsubscribe_token($user_id, $type);
		return hash_equals($expected_token, $token);
	}

	/**
	 * Generate unsubscribe URL for user
	 *
	 * @param int $user_id WordPress user ID
	 * @param string $type Type of unsubscribe (email, sms, all)
	 * @return string Unsubscribe URL
	 */
	public function generate_unsubscribe_url(int $user_id, string $type = 'email'): string {
		$token = $this->generate_unsubscribe_token($user_id, $type);
		$base_url = rest_url('ma-deal-room/v1/notifications/unsubscribe');

		return add_query_arg([
			'user_id' => $user_id,
			'token' => $token,
			'type' => $type,
		], $base_url);
	}

	/**
	 * Process unsubscribe request
	 *
	 * @param int $user_id WordPress user ID
	 * @param string $token Unsubscribe token
	 * @param string $type Type of unsubscribe (email, sms, all)
	 * @return array Result with success status and message
	 */
	public function process_unsubscribe(int $user_id, string $token, string $type = 'email'): array {
		// Verify token
		if (!$this->verify_unsubscribe_token($user_id, $token, $type)) {
			return [
				'success' => false,
				'message' => 'Invalid unsubscribe token.',
			];
		}

		// Process unsubscribe based on type
		$result = false;
		$message = '';

		switch ($type) {
			case 'email':
				$result = $this->disable_email($user_id);
				$message = 'You have been unsubscribed from email notifications.';
				error_log("[Notifications] User {$user_id} unsubscribed from email notifications");
				break;

			case 'sms':
				$result = $this->disable_sms($user_id);
				$message = 'You have been unsubscribed from SMS notifications.';
				error_log("[Notifications] User {$user_id} unsubscribed from SMS notifications");
				break;

			case 'all':
				$email_result = $this->disable_email($user_id);
				$sms_result = $this->disable_sms($user_id);
				$result = $email_result && $sms_result;
				$message = 'You have been unsubscribed from all notifications.';
				error_log("[Notifications] User {$user_id} unsubscribed from all notifications");
				break;

			default:
				return [
					'success' => false,
					'message' => 'Invalid unsubscribe type.',
				];
		}

		if ($result) {
			return [
				'success' => true,
				'message' => $message,
			];
		} else {
			return [
				'success' => false,
				'message' => 'Failed to update notification preferences.',
			];
		}
	}

	/**
	 * Get user email address by user ID
	 *
	 * @param int $user_id WordPress user ID
	 * @return string|null User email or null if not found
	 */
	public function get_user_email(int $user_id): ?string {
		$user = get_userdata($user_id);
		return $user ? $user->user_email : null;
	}

	/**
	 * Get user ID by email address
	 *
	 * @param string $email Email address
	 * @return int|null User ID or null if not found
	 */
	public function get_user_id_by_email(string $email): ?int {
		$user = get_user_by('email', $email);
		return $user ? $user->ID : null;
	}
}
