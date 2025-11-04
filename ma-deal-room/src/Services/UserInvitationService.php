<?php
/**
 * User Invitation Service
 *
 * Handles role-based user invitations to transactions.
 *
 * @package    MADealRoom\Services
 * @since      2.0.0
 */

namespace MADealRoom\Services;

use MADealRoom\Models\UserInvitation;
use MADealRoom\Models\UserRole;
use MADealRoom\Repositories\UserInvitationRepository;
use MADealRoom\Repositories\CustomUserRepository;
use MADealRoom\Repositories\UserRoleRepository;
use MADealRoom\Services\EmailService;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UserInvitationService Class
 *
 * Manages invitation creation, acceptance, and role assignment.
 */
class UserInvitationService {

    /**
     * Invitation repository
     *
     * @var UserInvitationRepository
     */
    private $invitation_repo;

    /**
     * User repository
     *
     * @var CustomUserRepository
     */
    private $user_repo;

    /**
     * Role repository
     *
     * @var UserRoleRepository
     */
    private $role_repo;

    /**
     * Email service
     *
     * @var EmailService
     */
    private $email_service;

    /**
     * Invitation expiration time (days)
     *
     * @var int
     */
    private $expiry_days = 7;

    /**
     * Constructor
     */
    public function __construct() {
        $this->invitation_repo = new UserInvitationRepository();
        $this->user_repo = new CustomUserRepository();
        $this->role_repo = new UserRoleRepository();
        $this->email_service = new EmailService();

        // Allow customization via filters
        $this->expiry_days = apply_filters('ma_deal_invitation_expiry_days', $this->expiry_days);
    }

    /**
     * Send invitation
     *
     * @param array $data Invitation data
     * @return array|WP_Error Invitation data or error
     */
    public function send_invitation(array $data) {
        // Validate required fields
        $required = ['email', 'role_type', 'account_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new WP_Error(
                    'missing_field',
                    sprintf(__('Field %s is required', 'ma-deal-room'), $field),
                    ['status' => 400]
                );
            }
        }

        // Validate email
        if (!is_email($data['email'])) {
            return new WP_Error(
                'invalid_email',
                __('Invalid email address', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check if user already exists with this email
        $existing_user = $this->user_repo->find_by_email($data['email']);
        $existing_wp_user = get_user_by('email', $data['email']);

        if ($existing_user || $existing_wp_user) {
            // User exists - check if they already have this role
            $user_id = $existing_user ? $existing_user->id : $existing_wp_user->ID;
            $user_type = $existing_user ? 'custom' : 'wordpress';

            $has_role = $this->role_repo->user_has_role(
                $user_id,
                $user_type,
                $data['role_type'],
                $data['transaction_id'] ?? null
            );

            if ($has_role) {
                return new WP_Error(
                    'role_already_assigned',
                    __('User already has this role for the transaction', 'ma-deal-room'),
                    ['status' => 409]
                );
            }
        }

        // Check if pending invitation already exists
        $has_pending = $this->invitation_repo->has_pending_invitation(
            $data['email'],
            $data['role_type'],
            $data['transaction_id'] ?? null
        );

        if ($has_pending) {
            return new WP_Error(
                'invitation_exists',
                __('A pending invitation already exists for this email and role', 'ma-deal-room'),
                ['status' => 409]
            );
        }

        // Get inviter info
        $inviter_id = $data['invited_by_user_id'] ?? get_current_user_id();
        $inviter_type = $data['invited_by_user_type'] ?? 'wordpress';

        // Create invitation
        $invitation = new UserInvitation();
        $invitation->email = sanitize_email($data['email']);
        $invitation->role_type = sanitize_text_field($data['role_type']);
        $invitation->invited_by_user_id = $inviter_id;
        $invitation->invited_by_user_type = $inviter_type;
        $invitation->account_id = (int) $data['account_id'];
        $invitation->transaction_id = !empty($data['transaction_id']) ? (int) $data['transaction_id'] : null;
        $invitation->message = !empty($data['message']) ? sanitize_textarea_field($data['message']) : null;
        $invitation->permissions = $data['permissions'] ?? null;
        $invitation->metadata = $data['metadata'] ?? null;

        // Generate invitation token
        $token = $this->generate_invitation_token();
        $invitation->token_hash = hash('sha256', $token);
        $invitation->expires_at = date('Y-m-d H:i:s', strtotime("+{$this->expiry_days} days"));

        // Save invitation
        $invitation_id = $this->invitation_repo->create_invitation($invitation);

        if (!$invitation_id) {
            return new WP_Error(
                'invitation_failed',
                __('Failed to create invitation', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        $invitation->id = $invitation_id;

        // Generate invitation URL
        $invitation_url = $this->get_invitation_url($token);

        // Get inviter name
        if ($inviter_type === 'custom') {
            $inviter = $this->user_repo->find($inviter_id);
            $inviter_name = $inviter ? $inviter->get_full_name() : __('Someone', 'ma-deal-room');
        } else {
            $inviter_wp = get_user_by('ID', $inviter_id);
            $inviter_name = $inviter_wp ? $inviter_wp->display_name : __('Someone', 'ma-deal-room');
        }

        // Send invitation email
        $email_sent = $this->email_service->send_invitation_email(
            $data['email'],
            $inviter_name,
            $data['role_type'],
            $invitation_url,
            $invitation->message,
            $this->expiry_days
        );

        if (is_wp_error($email_sent)) {
            // Email failed but invitation created - return warning
            return [
                'invitation' => $invitation->to_array(),
                'warning' => __('Invitation created but email delivery failed', 'ma-deal-room'),
            ];
        }

        // Log event
        do_action('ma_deal_invitation_sent', $invitation);

        return [
            'invitation' => $invitation->to_array(),
            'message' => __('Invitation sent successfully', 'ma-deal-room'),
        ];
    }

    /**
     * Accept invitation
     *
     * @param string $token Invitation token
     * @param array $user_data User data for registration (if new user)
     * @return array|WP_Error Success data or error
     */
    public function accept_invitation(string $token, array $user_data = []) {
        $token_hash = hash('sha256', $token);

        // Find invitation
        $invitation = $this->invitation_repo->find_by_token($token_hash);

        if (!$invitation) {
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired invitation token', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Check if invitation is pending
        if (!$invitation->is_pending()) {
            $status = $invitation->get_status();
            return new WP_Error(
                'invitation_not_pending',
                sprintf(__('Invitation has already been %s', 'ma-deal-room'), $status),
                ['status' => 400]
            );
        }

        // Check if user already exists
        $existing_user = $this->user_repo->find_by_email($invitation->email);
        $existing_wp_user = get_user_by('email', $invitation->email);

        if ($existing_user) {
            // Custom user exists - just assign role
            $user_id = $existing_user->id;
            $user_type = 'custom';
        } elseif ($existing_wp_user) {
            // WordPress user exists - just assign role
            $user_id = $existing_wp_user->ID;
            $user_type = 'wordpress';
        } else {
            // New user - create account
            $registration_result = $this->register_invited_user($invitation, $user_data);

            if (is_wp_error($registration_result)) {
                return $registration_result;
            }

            $user_id = $registration_result['user_id'];
            $user_type = $registration_result['user_type'];
        }

        // Assign role
        $role = new UserRole();
        $role->user_id = $user_id;
        $role->user_type = $user_type;
        $role->role_type = $invitation->role_type;
        $role->account_id = $invitation->account_id;
        $role->transaction_id = $invitation->transaction_id;
        $role->permissions = $invitation->permissions;
        $role->metadata = $invitation->metadata;
        $role->assigned_by_user_id = $invitation->invited_by_user_id;

        $role_id = $this->role_repo->assign_role($role);

        if (!$role_id) {
            return new WP_Error(
                'role_assignment_failed',
                __('Failed to assign role', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Mark invitation as accepted
        $this->invitation_repo->accept_invitation($invitation->id, $user_id);

        // Log event
        do_action('ma_deal_invitation_accepted', $invitation, $user_id, $user_type);

        return [
            'success' => true,
            'user_id' => $user_id,
            'user_type' => $user_type,
            'role_id' => $role_id,
            'message' => __('Invitation accepted successfully', 'ma-deal-room'),
        ];
    }

    /**
     * Decline invitation
     *
     * @param string $token Invitation token
     * @return true|WP_Error True on success, error on failure
     */
    public function decline_invitation(string $token) {
        $token_hash = hash('sha256', $token);

        // Find invitation
        $invitation = $this->invitation_repo->find_by_token($token_hash);

        if (!$invitation) {
            return new WP_Error(
                'invalid_token',
                __('Invalid or expired invitation token', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        if (!$invitation->is_pending()) {
            return new WP_Error(
                'invitation_not_pending',
                __('Invitation is not pending', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Mark as declined
        $declined = $this->invitation_repo->decline_invitation($invitation->id);

        if (!$declined) {
            return new WP_Error(
                'decline_failed',
                __('Failed to decline invitation', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Log event
        do_action('ma_deal_invitation_declined', $invitation);

        return true;
    }

    /**
     * Cancel invitation
     *
     * @param int $invitation_id Invitation ID
     * @param int $canceller_id User who is cancelling
     * @return true|WP_Error True on success, error on failure
     */
    public function cancel_invitation(int $invitation_id, int $canceller_id) {
        // Get invitation
        $invitation = $this->invitation_repo->find($invitation_id);

        if (!$invitation) {
            return new WP_Error(
                'invitation_not_found',
                __('Invitation not found', 'ma-deal-room'),
                ['status' => 404]
            );
        }

        // Check if user has permission to cancel
        if ($invitation->invited_by_user_id !== $canceller_id && !current_user_can('manage_options')) {
            return new WP_Error(
                'permission_denied',
                __('You do not have permission to cancel this invitation', 'ma-deal-room'),
                ['status' => 403]
            );
        }

        if (!$invitation->is_pending()) {
            return new WP_Error(
                'invitation_not_pending',
                __('Only pending invitations can be cancelled', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Cancel invitation
        $cancelled = $this->invitation_repo->cancel_invitation($invitation_id);

        if (!$cancelled) {
            return new WP_Error(
                'cancel_failed',
                __('Failed to cancel invitation', 'ma-deal-room'),
                ['status' => 500]
            );
        }

        // Log event
        do_action('ma_deal_invitation_cancelled', $invitation, $canceller_id);

        return true;
    }

    /**
     * Get pending invitations for email
     *
     * @param string $email Email address
     * @return array Array of invitations
     */
    public function get_pending_invitations_for_email(string $email): array {
        return $this->invitation_repo->get_invitations_by_email($email, true);
    }

    /**
     * Send reminder for expiring invitations
     *
     * @param int $hours_before_expiry Send reminder this many hours before expiry
     * @return int Number of reminders sent
     */
    public function send_invitation_reminders(int $hours_before_expiry = 24): int {
        $expiring = $this->invitation_repo->get_expiring_invitations($hours_before_expiry);
        $sent_count = 0;

        foreach ($expiring as $invitation) {
            // Regenerate invitation URL
            // Note: In production, you'd need to store/retrieve the original token
            $invitation_url = home_url('/accept-invitation');

            // Get inviter name
            if ($invitation->invited_by_user_type === 'custom') {
                $inviter = $this->user_repo->find($invitation->invited_by_user_id);
                $inviter_name = $inviter ? $inviter->get_full_name() : __('Someone', 'ma-deal-room');
            } else {
                $inviter_wp = get_user_by('ID', $invitation->invited_by_user_id);
                $inviter_name = $inviter_wp ? $inviter_wp->display_name : __('Someone', 'ma-deal-room');
            }

            // Send reminder
            $email_sent = $this->email_service->send_invitation_reminder(
                $invitation->email,
                $inviter_name,
                $invitation->role_type,
                $invitation_url,
                $hours_before_expiry / 24
            );

            if (!is_wp_error($email_sent)) {
                $sent_count++;
            }
        }

        return $sent_count;
    }

    /**
     * Register invited user
     *
     * @param UserInvitation $invitation Invitation model
     * @param array $user_data User registration data
     * @return array|WP_Error User data or error
     */
    private function register_invited_user(UserInvitation $invitation, array $user_data) {
        // Require password for new users
        if (empty($user_data['password'])) {
            return new WP_Error(
                'missing_password',
                __('Password is required for new users', 'ma-deal-room'),
                ['status' => 400]
            );
        }

        // Create custom user
        $user = $this->user_repo->find_by_email($invitation->email);
        if (!$user) {
            // User doesn't exist - create new
            $auth_service = new AuthService();
            $registration = $auth_service->register([
                'email' => $invitation->email,
                'password' => $user_data['password'],
                'first_name' => $user_data['first_name'] ?? '',
                'last_name' => $user_data['last_name'] ?? '',
                'phone' => $user_data['phone'] ?? '',
            ]);

            if (is_wp_error($registration)) {
                return $registration;
            }

            $user = $this->user_repo->find_by_email($invitation->email);

            return [
                'user_id' => $user->id,
                'user_type' => 'custom',
            ];
        }

        return [
            'user_id' => $user->id,
            'user_type' => 'custom',
        ];
    }

    /**
     * Generate invitation token
     *
     * @return string Random token
     */
    private function generate_invitation_token(): string {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get invitation URL
     *
     * @param string $token Invitation token
     * @return string Invitation URL
     */
    private function get_invitation_url(string $token): string {
        $base_url = apply_filters('ma_deal_invitation_url_base', home_url('/accept-invitation'));
        return add_query_arg('token', $token, $base_url);
    }

    /**
     * Get invitation statistics
     *
     * @param int $account_id Account ID
     * @param int $days Number of days
     * @return array Statistics
     */
    public function get_invitation_stats(int $account_id, int $days = 30): array {
        return $this->invitation_repo->get_invitation_stats($account_id, $days);
    }
}
