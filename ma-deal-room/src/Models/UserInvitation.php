<?php
/**
 * User Invitation Model
 *
 * Represents a role-based invitation to join a transaction or agency.
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
 * UserInvitation Model Class
 *
 * Manages user invitations for transaction participation.
 */
class UserInvitation {

    /**
     * Invitation ID
     * @var int
     */
    public int $id;

    /**
     * Email address to invite
     * @var string
     */
    public string $email;

    /**
     * Role type being offered
     * @var string
     */
    public string $role_type;

    /**
     * User who sent invitation
     * @var int
     */
    public int $invited_by_user_id;

    /**
     * Inviter's user type (custom or wordpress)
     * @var string
     */
    public string $invited_by_user_type;

    /**
     * Account ID (agency/brokerage)
     * @var int
     */
    public int $account_id;

    /**
     * Transaction ID (NULL for general agency invite)
     * @var int|null
     */
    public ?int $transaction_id = null;

    /**
     * Unique invitation token hash
     * @var string
     */
    public string $token_hash;

    /**
     * Personal message from inviter
     * @var string|null
     */
    public ?string $message = null;

    /**
     * Specific permissions for this role
     * @var array|null
     */
    public ?array $permissions = null;

    /**
     * Additional invitation metadata
     * @var array|null
     */
    public ?array $metadata = null;

    /**
     * Expiration timestamp
     * @var string
     */
    public string $expires_at;

    /**
     * Acceptance timestamp
     * @var string|null
     */
    public ?string $accepted_at = null;

    /**
     * User who accepted
     * @var int|null
     */
    public ?int $accepted_by_user_id = null;

    /**
     * Declined timestamp
     * @var string|null
     */
    public ?string $declined_at = null;

    /**
     * Cancellation timestamp
     * @var string|null
     */
    public ?string $cancelled_at = null;

    /**
     * Created timestamp
     * @var string
     */
    public string $created_at;

    /**
     * Constructor
     *
     * @param array $data Invitation data from database
     */
    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->fill($data);
        }
    }

    /**
     * Fill model with data
     *
     * @param array $data Invitation data
     * @return self
     */
    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                // Handle JSON fields
                if (in_array($key, ['permissions', 'metadata']) && is_string($value)) {
                    $this->$key = json_decode($value, true);
                }
                // Handle integer fields
                elseif (in_array($key, ['id', 'invited_by_user_id', 'account_id', 'transaction_id', 'accepted_by_user_id'])) {
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
     * @param bool $include_token_hash Include token hash
     * @return array
     */
    public function to_array(bool $include_token_hash = false): array {
        $data = [
            'id' => $this->id ?? null,
            'email' => $this->email,
            'role_type' => $this->role_type,
            'invited_by_user_id' => $this->invited_by_user_id,
            'invited_by_user_type' => $this->invited_by_user_type,
            'account_id' => $this->account_id,
            'transaction_id' => $this->transaction_id,
            'message' => $this->message,
            'permissions' => $this->permissions,
            'metadata' => $this->metadata,
            'expires_at' => $this->expires_at,
            'accepted_at' => $this->accepted_at,
            'accepted_by_user_id' => $this->accepted_by_user_id,
            'declined_at' => $this->declined_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at ?? null,
        ];

        if ($include_token_hash) {
            $data['token_hash'] = $this->token_hash;
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
            'email' => $this->email,
            'role_type' => $this->role_type,
            'invited_by_user_id' => $this->invited_by_user_id,
            'invited_by_user_type' => $this->invited_by_user_type,
            'account_id' => $this->account_id,
            'transaction_id' => $this->transaction_id,
            'token_hash' => $this->token_hash,
            'message' => $this->message,
            'permissions' => $this->permissions ? json_encode($this->permissions) : null,
            'metadata' => $this->metadata ? json_encode($this->metadata) : null,
            'expires_at' => $this->expires_at,
            'accepted_at' => $this->accepted_at,
            'accepted_by_user_id' => $this->accepted_by_user_id,
            'declined_at' => $this->declined_at,
            'cancelled_at' => $this->cancelled_at,
        ];
    }

    /**
     * Check if invitation is pending
     *
     * @return bool
     */
    public function is_pending(): bool {
        return $this->accepted_at === null
            && $this->declined_at === null
            && $this->cancelled_at === null
            && !$this->is_expired();
    }

    /**
     * Check if invitation is expired
     *
     * @return bool
     */
    public function is_expired(): bool {
        return strtotime($this->expires_at) <= time();
    }

    /**
     * Check if invitation is accepted
     *
     * @return bool
     */
    public function is_accepted(): bool {
        return $this->accepted_at !== null;
    }

    /**
     * Check if invitation is declined
     *
     * @return bool
     */
    public function is_declined(): bool {
        return $this->declined_at !== null;
    }

    /**
     * Check if invitation is cancelled
     *
     * @return bool
     */
    public function is_cancelled(): bool {
        return $this->cancelled_at !== null;
    }

    /**
     * Accept invitation
     *
     * @param int $user_id User who accepted
     */
    public function accept(int $user_id): void {
        $this->accepted_at = current_time('mysql');
        $this->accepted_by_user_id = $user_id;
    }

    /**
     * Decline invitation
     */
    public function decline(): void {
        $this->declined_at = current_time('mysql');
    }

    /**
     * Cancel invitation
     */
    public function cancel(): void {
        $this->cancelled_at = current_time('mysql');
    }

    /**
     * Get invitation status
     *
     * @return string pending|accepted|declined|cancelled|expired
     */
    public function get_status(): string {
        if ($this->is_accepted()) {
            return 'accepted';
        }
        if ($this->is_declined()) {
            return 'declined';
        }
        if ($this->is_cancelled()) {
            return 'cancelled';
        }
        if ($this->is_expired()) {
            return 'expired';
        }
        return 'pending';
    }

    /**
     * Get status display label
     *
     * @return string
     */
    public function get_status_label(): string {
        $labels = [
            'pending' => __('Pending', 'ma-deal-room'),
            'accepted' => __('Accepted', 'ma-deal-room'),
            'declined' => __('Declined', 'ma-deal-room'),
            'cancelled' => __('Cancelled', 'ma-deal-room'),
            'expired' => __('Expired', 'ma-deal-room'),
        ];

        return $labels[$this->get_status()] ?? __('Unknown', 'ma-deal-room');
    }

    /**
     * Get role display name
     *
     * @return string
     */
    public function get_role_display_name(): string {
        $names = [
            'broker' => __('Broker', 'ma-deal-room'),
            'agent' => __('Agent', 'ma-deal-room'),
            'buyer' => __('Buyer', 'ma-deal-room'),
            'seller' => __('Seller', 'ma-deal-room'),
            'buyer_attorney' => __('Buyer\'s Attorney', 'ma-deal-room'),
            'seller_attorney' => __('Seller\'s Attorney', 'ma-deal-room'),
            'lender' => __('Lender', 'ma-deal-room'),
            'inspector' => __('Inspector', 'ma-deal-room'),
            'vendor' => __('Vendor', 'ma-deal-room'),
            'title_company' => __('Title Company', 'ma-deal-room'),
            'escrow' => __('Escrow Officer', 'ma-deal-room'),
        ];

        return $names[$this->role_type] ?? ucwords(str_replace('_', ' ', $this->role_type));
    }

    /**
     * Get time until expiration
     *
     * @return string Human readable time
     */
    public function get_expires_in_human(): string {
        if ($this->is_expired()) {
            return __('Expired', 'ma-deal-room');
        }

        return sprintf(
            __('Expires in %s', 'ma-deal-room'),
            human_time_diff(current_time('timestamp'), strtotime($this->expires_at))
        );
    }
}
