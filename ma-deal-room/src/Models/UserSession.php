<?php
/**
 * User Session Model
 *
 * Represents a user session with JWT refresh token.
 *
 * @package    MA_Deal_Room
 * @subpackage Models
 * @since      2.0.0
 */

namespace MADealRoom\Models;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UserSession Model Class
 *
 * Manages user sessions and refresh tokens for JWT authentication.
 */
class UserSession {

    /**
     * Session ID (primary key)
     * @var int
     */
    public int $id;

    /**
     * Session ID (unique session identifier for JWT token)
     * @var string|null
     */
    public ?string $session_id = null;

    /**
     * User ID
     * @var int
     */
    public int $user_id;

    /**
     * User type (custom or wordpress)
     * @var string
     */
    public string $user_type;

    /**
     * Refresh token hash
     * @var string
     */
    public string $refresh_token_hash;

    /**
     * Device name
     * @var string|null
     */
    public ?string $device_name = null;

    /**
     * Device type (desktop, mobile, tablet)
     * @var string|null
     */
    public ?string $device_type = null;

    /**
     * Browser name
     * @var string|null
     */
    public ?string $browser = null;

    /**
     * Platform/OS name
     * @var string|null
     */
    public ?string $platform = null;

    /**
     * Initial IP address
     * @var string
     */
    public string $ip_address;

    /**
     * Last activity IP address (for suspicious activity detection)
     * @var string
     */
    public string $last_activity_ip;

    /**
     * User agent string
     * @var string|null
     */
    public ?string $user_agent = null;

    /**
     * Last activity user agent (for suspicious activity detection)
     * @var string|null
     */
    public ?string $last_activity_user_agent = null;

    /**
     * Approximate location
     * @var string|null
     */
    public ?string $location = null;

    /**
     * Last used timestamp
     * @var string
     */
    public string $last_used_at;

    /**
     * Expiration timestamp
     * @var string
     */
    public string $expires_at;

    /**
     * Revocation timestamp
     * @var string|null
     */
    public ?string $revoked_at = null;

    /**
     * Invalidation reason
     * @var string|null
     */
    public ?string $invalidation_reason = null;

    /**
     * Created timestamp
     * @var string
     */
    public string $created_at;

    /**
     * Constructor
     *
     * @param array $data Session data from database
     */
    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Fill model with data
     *
     * @param array $data Session data
     * @return self
     */
    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['id', 'user_id'])) {
                    $this->$key = (int) $value;
                } else {
                    $this->$key = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Convert model to array
     *
     * @param bool $include_token_hash Include refresh token hash
     * @return array
     */
    public function to_array(bool $include_token_hash = false): array {
        $data = [
            'id' => $this->id ?? null,
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'platform' => $this->platform,
            'ip_address' => $this->ip_address,
            'last_activity_ip' => $this->last_activity_ip ?? $this->ip_address,
            'user_agent' => $this->user_agent,
            'last_activity_user_agent' => $this->last_activity_user_agent ?? $this->user_agent,
            'location' => $this->location,
            'last_used_at' => $this->last_used_at,
            'expires_at' => $this->expires_at,
            'revoked_at' => $this->revoked_at,
            'invalidation_reason' => $this->invalidation_reason,
            'created_at' => $this->created_at ?? null,
        ];

        if ($include_token_hash) {
            $data['refresh_token_hash'] = $this->refresh_token_hash;
        }

        return $data;
    }

    /**
     * Get database insert/update data
     *
     * @return array
     */
    public function get_database_data(): array {
        return [
            'session_id' => $this->session_id,
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'refresh_token_hash' => $this->refresh_token_hash,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'platform' => $this->platform,
            'ip_address' => $this->ip_address,
            'last_activity_ip' => $this->last_activity_ip ?? $this->ip_address,
            'user_agent' => $this->user_agent,
            'last_activity_user_agent' => $this->last_activity_user_agent ?? $this->user_agent,
            'location' => $this->location,
            'last_used_at' => $this->last_used_at,
            'expires_at' => $this->expires_at,
            'revoked_at' => $this->revoked_at,
            'invalidation_reason' => $this->invalidation_reason,
        ];
    }

    /**
     * Check if session is active
     *
     * @return bool
     */
    public function is_active(): bool {
        // Not revoked
        if ($this->revoked_at) {
            return false;
        }

        // Not expired
        if (strtotime($this->expires_at) <= time()) {
            return false;
        }

        return true;
    }

    /**
     * Check if session is expired
     *
     * @return bool
     */
    public function is_expired(): bool {
        return strtotime($this->expires_at) <= time();
    }

    /**
     * Check if session is revoked
     *
     * @return bool
     */
    public function is_revoked(): bool {
        return $this->revoked_at !== null;
    }

    /**
     * Revoke session
     */
    public function revoke(): void {
        $this->revoked_at = current_time('mysql');
    }

    /**
     * Update last used timestamp
     */
    public function touch(): void {
        $this->last_used_at = current_time('mysql');
    }

    /**
     * Get device info for display
     *
     * @return string
     */
    public function get_device_display(): string {
        if ($this->device_name) {
            return $this->device_name;
        }

        if ($this->device_type) {
            return ucfirst($this->device_type);
        }

        // Parse from user agent
        if ($this->user_agent) {
            if (stripos($this->user_agent, 'mobile') !== false) {
                return __('Mobile Device', 'ma-deal-room');
            }
            if (stripos($this->user_agent, 'tablet') !== false) {
                return __('Tablet', 'ma-deal-room');
            }
            if (stripos($this->user_agent, 'chrome') !== false) {
                return 'Chrome Browser';
            }
            if (stripos($this->user_agent, 'firefox') !== false) {
                return 'Firefox Browser';
            }
            if (stripos($this->user_agent, 'safari') !== false) {
                return 'Safari Browser';
            }
        }

        return __('Unknown Device', 'ma-deal-room');
    }

    /**
     * Get time since last use
     *
     * @return string Human readable time
     */
    public function get_last_used_human(): string {
        return human_time_diff(strtotime($this->last_used_at), current_time('timestamp')) . ' ' . __('ago', 'ma-deal-room');
    }

    /**
     * Check if this is current session
     *
     * @param string $token_hash Current session token hash
     * @return bool
     */
    public function is_current_session(string $token_hash): bool {
        return $this->refresh_token_hash === $token_hash;
    }

    /**
     * Detect suspicious activity (IP or user agent change)
     *
     * @param string $current_ip Current IP address
     * @param string $current_user_agent Current user agent
     * @return array|null Returns array with details if suspicious, null if normal
     */
    public function detect_suspicious_activity(string $current_ip, string $current_user_agent): ?array {
        $suspicious = [];

        // Check for IP address change
        if ($this->last_activity_ip !== $current_ip && $this->ip_address !== $current_ip) {
            $suspicious['ip_changed'] = [
                'from' => $this->last_activity_ip,
                'to' => $current_ip,
            ];
        }

        // Check for user agent change (significant changes, not minor version updates)
        if ($this->last_activity_user_agent && $current_user_agent) {
            $similarity = $this->calculate_user_agent_similarity(
                $this->last_activity_user_agent,
                $current_user_agent
            );

            // If user agents are less than 70% similar, consider it suspicious
            if ($similarity < 0.70) {
                $suspicious['user_agent_changed'] = [
                    'from' => $this->last_activity_user_agent,
                    'to' => $current_user_agent,
                    'similarity' => $similarity,
                ];
            }
        }

        return !empty($suspicious) ? $suspicious : null;
    }

    /**
     * Calculate similarity between two user agent strings
     *
     * @param string $ua1 First user agent
     * @param string $ua2 Second user agent
     * @return float Similarity score (0-1)
     */
    private function calculate_user_agent_similarity(string $ua1, string $ua2): float {
        // Extract key components (browser, OS, device)
        $extract = function($ua) {
            preg_match('/\((.*?)\)/', $ua, $os_match);
            preg_match('/(Chrome|Firefox|Safari|Edge|Opera)\/[\d.]+/', $ua, $browser_match);

            return [
                'os' => $os_match[1] ?? '',
                'browser' => $browser_match[0] ?? '',
            ];
        };

        $components1 = $extract($ua1);
        $components2 = $extract($ua2);

        // Calculate similarity based on key components
        $os_match = $components1['os'] === $components2['os'] ? 1 : 0;
        $browser_match = $components1['browser'] === $components2['browser'] ? 1 : 0;

        // Weighted average: browser is more important than exact OS match
        return ($browser_match * 0.7) + ($os_match * 0.3);
    }

    /**
     * Update activity tracking
     *
     * @param string $ip Current IP address
     * @param string $user_agent Current user agent
     */
    public function update_activity_tracking(string $ip, string $user_agent): void {
        $this->last_activity_ip = $ip;
        $this->last_activity_user_agent = $user_agent;
        $this->touch();
    }
}
