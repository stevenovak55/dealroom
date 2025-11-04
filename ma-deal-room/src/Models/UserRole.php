<?php
/**
 * User Role Model
 *
 * Represents a role assignment for a user (custom or WordPress).
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
 * UserRole Model Class
 *
 * Manages role assignments with support for context-specific roles
 * (e.g., user can be buyer in one transaction, seller in another).
 */
class UserRole {

    /**
     * Role assignment ID
     * @var int
     */
    public int $id;

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
     * Role type (buyer, seller, agent, attorney, etc.)
     * @var string
     */
    public string $role_type;

    /**
     * Account ID (for multi-tenancy)
     * @var int|null
     */
    public ?int $account_id = null;

    /**
     * Transaction ID (context for role, NULL = global role)
     * @var int|null
     */
    public ?int $transaction_id = null;

    /**
     * Is primary role for this user
     * @var bool
     */
    public bool $is_primary = false;

    /**
     * Permission overrides (JSON)
     * @var array|null
     */
    public ?array $permissions = null;

    /**
     * Role-specific metadata (license numbers, bar numbers, etc.)
     * @var array|null
     */
    public ?array $metadata = null;

    /**
     * User who assigned this role
     * @var int|null
     */
    public ?int $assigned_by_user_id = null;

    /**
     * Assignment timestamp
     * @var string
     */
    public string $assigned_at;

    /**
     * Role expiration timestamp
     * @var string|null
     */
    public ?string $expires_at = null;

    /**
     * Role revocation timestamp
     * @var string|null
     */
    public ?string $revoked_at = null;

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
     * Constructor
     *
     * @param array $data Role data from database
     */
    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Fill model with data
     *
     * @param array $data Role data
     * @return self
     */
    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                // Handle JSON fields
                if (in_array($key, ['permissions', 'metadata']) && is_string($value)) {
                    $this->$key = json_decode($value, true);
                }
                // Handle boolean fields
                elseif ($key === 'is_primary') {
                    $this->$key = (bool) $value;
                }
                // Handle integer fields
                elseif (in_array($key, ['id', 'user_id', 'account_id', 'transaction_id', 'assigned_by_user_id'])) {
                    $this->$key = $value !== null ? (int) $value : null;
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
     * @return array
     */
    public function to_array(): array {
        return [
            'id' => $this->id ?? null,
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'role_type' => $this->role_type,
            'account_id' => $this->account_id,
            'transaction_id' => $this->transaction_id,
            'is_primary' => $this->is_primary,
            'permissions' => $this->permissions,
            'metadata' => $this->metadata,
            'assigned_by_user_id' => $this->assigned_by_user_id,
            'assigned_at' => $this->assigned_at ?? null,
            'expires_at' => $this->expires_at,
            'revoked_at' => $this->revoked_at,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null,
        ];
    }

    /**
     * Get database insert/update data
     *
     * @return array
     */
    public function get_database_data(): array {
        return [
            'user_id' => $this->user_id,
            'user_type' => $this->user_type,
            'role_type' => $this->role_type,
            'account_id' => $this->account_id,
            'transaction_id' => $this->transaction_id,
            'is_primary' => $this->is_primary ? 1 : 0,
            'permissions' => $this->permissions ? json_encode($this->permissions) : null,
            'metadata' => $this->metadata ? json_encode($this->metadata) : null,
            'assigned_by_user_id' => $this->assigned_by_user_id,
            'expires_at' => $this->expires_at,
            'revoked_at' => $this->revoked_at,
        ];
    }

    /**
     * Check if role is active
     *
     * @return bool
     */
    public function is_active(): bool {
        // Not revoked
        if ($this->revoked_at && strtotime($this->revoked_at) <= time()) {
            return false;
        }

        // Not expired
        if ($this->expires_at && strtotime($this->expires_at) <= time()) {
            return false;
        }

        return true;
    }

    /**
     * Check if role is global (not transaction-specific)
     *
     * @return bool
     */
    public function is_global(): bool {
        return $this->transaction_id === null;
    }

    /**
     * Revoke role
     */
    public function revoke(): void {
        $this->revoked_at = current_time('mysql');
    }

    /**
     * Restore revoked role
     */
    public function restore(): void {
        $this->revoked_at = null;
    }

    /**
     * Set as primary role
     */
    public function set_as_primary(): void {
        $this->is_primary = true;
    }

    /**
     * Remove primary status
     */
    public function remove_primary_status(): void {
        $this->is_primary = false;
    }

    /**
     * Get role display name
     *
     * @return string
     */
    public function get_display_name(): string {
        $names = [
            'broker' => __('Broker', 'ma-deal-room'),
            'agent' => __('Agent', 'ma-deal-room'),
            'buyer' => __('Buyer', 'ma-deal-room'),
            'seller' => __('Seller', 'ma-deal-room'),
            'buyer_attorney' => __('Buyer\'s Attorney', 'ma-deal-room'),
            'seller_attorney' => __('Seller\'s Attorney', 'ma-deal-room'),
            'lender' => __('Lender', 'ma-deal-room'),
            'inspector' => __('Inspector', 'ma-deal-room'),
            'appraiser' => __('Appraiser', 'ma-deal-room'),
            'vendor' => __('Vendor', 'ma-deal-room'),
            'title_company' => __('Title Company', 'ma-deal-room'),
            'escrow' => __('Escrow Officer', 'ma-deal-room'),
            'hoa_manager' => __('HOA Manager', 'ma-deal-room'),
            'septic_inspector' => __('Septic Inspector', 'ma-deal-room'),
            'fire_dept' => __('Fire Department', 'ma-deal-room'),
        ];

        return $names[$this->role_type] ?? ucwords(str_replace('_', ' ', $this->role_type));
    }
}
