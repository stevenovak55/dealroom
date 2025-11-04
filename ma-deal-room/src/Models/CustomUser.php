<?php
/**
 * Custom User Model
 *
 * Represents a custom user account (buyers, sellers, vendors) separate from WordPress users.
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
 * CustomUser Model Class
 *
 * Represents custom user accounts with authentication and profile data.
 */
class CustomUser {

    /**
     * User ID
     * @var int
     */
    public int $id;

    /**
     * Email address (unique, used for login)
     * @var string
     */
    public string $email;

    /**
     * Password hash (bcrypt)
     * @var string
     */
    public string $password_hash;

    /**
     * First name
     * @var string|null
     */
    public ?string $first_name = null;

    /**
     * Last name
     * @var string|null
     */
    public ?string $last_name = null;

    /**
     * Phone number
     * @var string|null
     */
    public ?string $phone = null;

    /**
     * Account status
     * @var string active|inactive|suspended|pending_verification
     */
    public string $status = 'pending_verification';

    /**
     * Email verified flag
     * @var bool
     */
    public bool $email_verified = false;

    /**
     * Email verification timestamp
     * @var string|null
     */
    public ?string $email_verified_at = null;

    /**
     * Last login timestamp
     * @var string|null
     */
    public ?string $last_login_at = null;

    /**
     * Last login IP address
     * @var string|null
     */
    public ?string $last_login_ip = null;

    /**
     * Failed login attempts counter
     * @var int
     */
    public int $failed_login_attempts = 0;

    /**
     * Account locked until timestamp
     * @var string|null
     */
    public ?string $locked_until = null;

    /**
     * Password last changed timestamp
     * @var string|null
     */
    public ?string $password_changed_at = null;

    /**
     * Must change password on next login
     * @var bool
     */
    public bool $must_change_password = false;

    /**
     * Additional metadata (JSON)
     * @var array|null
     */
    public ?array $metadata = null;

    /**
     * Created timestamp
     * @var string
     */
    public string $created_at;

    /**
     * Updated timestamp
     * @var string
     */
    public string $updated_at;

    /**
     * Soft delete timestamp
     * @var string|null
     */
    public ?string $deleted_at = null;

    /**
     * User roles (loaded separately)
     * @var array|null
     */
    private ?array $roles = null;

    /**
     * Constructor
     *
     * @param array $data User data from database
     */
    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Fill model with data
     *
     * @param array $data User data
     * @return self
     */
    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                // Handle JSON metadata
                if ($key === 'metadata' && is_string($value)) {
                    $this->metadata = json_decode($value, true);
                }
                // Handle boolean fields
                elseif (in_array($key, ['email_verified', 'must_change_password'])) {
                    $this->$key = (bool) $value;
                }
                // Handle integer fields
                elseif (in_array($key, ['id', 'failed_login_attempts'])) {
                    $this->$key = (int) $value;
                }
                else {
                    $this->$key = $value;
                }
            }
        }

        return $this;
    }

    /**
     * Convert model to array
     *
     * @param bool $include_password Include password hash in array
     * @return array
     */
    public function to_array(bool $include_password = false): array {
        $data = [
            'id' => $this->id ?? null,
            'email' => $this->email ?? null,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'status' => $this->status,
            'email_verified' => $this->email_verified,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'last_login_ip' => $this->last_login_ip,
            'failed_login_attempts' => $this->failed_login_attempts,
            'locked_until' => $this->locked_until,
            'password_changed_at' => $this->password_changed_at,
            'must_change_password' => $this->must_change_password,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null,
            'deleted_at' => $this->deleted_at,
        ];

        if ($include_password) {
            $data['password_hash'] = $this->password_hash ?? null;
        }

        return $data;
    }

    /**
     * Get database insert/update data
     *
     * @return array
     */
    public function get_database_data(): array {
        $data = [
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'status' => $this->status,
            'email_verified' => $this->email_verified ? 1 : 0,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'last_login_ip' => $this->last_login_ip,
            'failed_login_attempts' => $this->failed_login_attempts,
            'locked_until' => $this->locked_until,
            'password_changed_at' => $this->password_changed_at,
            'must_change_password' => $this->must_change_password ? 1 : 0,
            'metadata' => $this->metadata ? json_encode($this->metadata) : null,
        ];

        if (isset($this->password_hash)) {
            $data['password_hash'] = $this->password_hash;
        }

        if (isset($this->deleted_at)) {
            $data['deleted_at'] = $this->deleted_at;
        }

        return $data;
    }

    /**
     * Get full name
     *
     * @return string
     */
    public function get_full_name(): string {
        $parts = array_filter([$this->first_name, $this->last_name]);
        return implode(' ', $parts) ?: $this->email;
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function get_display_name(): string {
        return $this->get_full_name();
    }

    /**
     * Check if account is active
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === 'active';
    }

    /**
     * Check if account is locked
     *
     * @return bool
     */
    public function is_locked(): bool {
        if (!$this->locked_until) {
            return false;
        }

        $locked_until_time = strtotime($this->locked_until);
        return $locked_until_time > time();
    }

    /**
     * Check if email is verified
     *
     * @return bool
     */
    public function is_email_verified(): bool {
        return $this->email_verified;
    }

    /**
     * Check if password needs to be changed
     *
     * @return bool
     */
    public function needs_password_change(): bool {
        return $this->must_change_password;
    }

    /**
     * Get user roles
     *
     * @return array
     */
    public function get_roles(): array {
        if ($this->roles === null) {
            global $wpdb;
            $table = $wpdb->prefix . 'ma_deal_user_roles';

            $this->roles = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table
                WHERE user_id = %d AND user_type = 'custom'
                AND (revoked_at IS NULL OR revoked_at > NOW())
                AND (expires_at IS NULL OR expires_at > NOW())",
                $this->id
            ), ARRAY_A);
        }

        return $this->roles;
    }

    /**
     * Get primary role
     *
     * @return string|null
     */
    public function get_primary_role(): ?string {
        $roles = $this->get_roles();

        foreach ($roles as $role) {
            if ($role['is_primary']) {
                return $role['role_type'];
            }
        }

        return $roles[0]['role_type'] ?? null;
    }

    /**
     * Check if user has role
     *
     * @param string $role_type Role to check
     * @param int|null $transaction_id Optional transaction context
     * @return bool
     */
    public function has_role(string $role_type, ?int $transaction_id = null): bool {
        $roles = $this->get_roles();

        foreach ($roles as $role) {
            if ($role['role_type'] === $role_type) {
                if ($transaction_id === null || $role['transaction_id'] === $transaction_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Record login attempt
     */
    public function record_login_attempt(bool $successful, string $ip_address): void {
        if ($successful) {
            $this->failed_login_attempts = 0;
            $this->last_login_at = current_time('mysql');
            $this->last_login_ip = $ip_address;
            $this->locked_until = null;
        } else {
            $this->failed_login_attempts++;

            // Lock account after 5 failed attempts
            if ($this->failed_login_attempts >= 5) {
                $this->locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            }
        }
    }

    /**
     * Verify password
     *
     * @param string $password Plain text password
     * @return bool
     */
    public function verify_password(string $password): bool {
        return password_verify($password, $this->password_hash);
    }

    /**
     * Set password
     *
     * @param string $password Plain text password
     */
    public function set_password(string $password): void {
        $this->password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->password_changed_at = current_time('mysql');
        $this->must_change_password = false;
    }

    /**
     * Mark email as verified
     */
    public function mark_email_verified(): void {
        $this->email_verified = true;
        $this->email_verified_at = current_time('mysql');

        // Activate account if it was pending verification
        if ($this->status === 'pending_verification') {
            $this->status = 'active';
        }
    }

    /**
     * Soft delete user
     */
    public function delete(): void {
        $this->deleted_at = current_time('mysql');
        $this->status = 'inactive';
    }

    /**
     * Restore soft deleted user
     */
    public function restore(): void {
        $this->deleted_at = null;
        $this->status = 'active';
    }
}
