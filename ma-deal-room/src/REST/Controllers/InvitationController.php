<?php
/**
 * Invitation REST Controller
 *
 * Handles role-based invitation endpoints
 *
 * @package MADealRoom\REST\Controllers
 * @since 2.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use MADealRoom\Services\UserInvitationService;
use MADealRoom\Services\ValidationService;

/**
 * InvitationController Class
 *
 * REST API endpoints for user invitations
 */
class InvitationController extends BaseController {

    /**
     * REST base
     *
     * @var string
     */
    protected $rest_base = 'invitations';

    /**
     * Invitation service
     *
     * @var UserInvitationService
     */
    private $invitation_service;

    /**
     * Invitation repository
     *
     * @var \MADealRoom\Repositories\UserInvitationRepository
     */
    private $invitation_repository;

    /**
     * Constructor
     *
     * @param UserInvitationService $invitation_service User invitation service instance
     * @param \MADealRoom\Repositories\UserInvitationRepository $invitation_repository User invitation repository instance
     */
    public function __construct(
        UserInvitationService $invitation_service,
        $invitation_repository
    ) {
        parent::__construct();
        $this->invitation_service = $invitation_service;
        $this->invitation_repository = $invitation_repository;
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes(): void {
        // Send invitation
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => 'POST',
                'callback' => [$this, 'send_invitation'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_invite_users');
                },
            ],
        ]);

        // List invitations
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => 'GET',
                'callback' => [$this, 'list_invitations'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_view_invitations');
                },
            ],
        ]);

        // Get single invitation
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_invitation'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_view_invitations');
                },
            ],
        ]);

        // Accept invitation (public endpoint - uses token)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/accept', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'accept_invitation'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        // Decline invitation (public endpoint - uses token)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/decline', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'decline_invitation'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        // Cancel invitation
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/cancel', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'cancel_invitation'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_invite_users');
                },
            ],
        ]);

        // Get pending invitations for email
        register_rest_route($this->namespace, '/' . $this->rest_base . '/pending', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_pending_for_email'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        // Get invitation statistics
        register_rest_route($this->namespace, '/' . $this->rest_base . '/stats', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_stats'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_view_invitations');
                },
            ],
        ]);
    }

    /**
     * Send invitation
     *
     * POST /invitations
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function send_invitation(WP_REST_Request $request) {
        $email = sanitize_email($request->get_param('email'));
        $role_type = sanitize_text_field($request->get_param('role_type'));
        $account_id = (int) $request->get_param('account_id');
        $transaction_id = $request->get_param('transaction_id');
        $message = sanitize_textarea_field($request->get_param('message'));

        // Validate email
        $email_validation = $this->validation_service->validate_email($email);
        if (is_wp_error($email_validation)) {
            return $email_validation;
        }

        // Validate role type
        $role_validation = $this->validation_service->validate_role_type($role_type);
        if (is_wp_error($role_validation)) {
            return $role_validation;
        }

        // Validate account ID
        $account_validation = $this->validation_service->validate_account_id($account_id);
        if (is_wp_error($account_validation)) {
            return $account_validation;
        }

        // Validate transaction ID if provided
        if ($transaction_id) {
            $transaction_validation = $this->validation_service->validate_transaction_id($transaction_id);
            if (is_wp_error($transaction_validation)) {
                return $transaction_validation;
            }
        }

        // Get current user for invited_by
        $current_user = $this->get_current_user();

        // Send invitation
        $result = $this->invitation_service->send_invitation([
            'email' => $email,
            'role_type' => $role_type,
            'account_id' => $account_id,
            'transaction_id' => $transaction_id ? (int) $transaction_id : null,
            'message' => $message,
            'invited_by_user_id' => $current_user ? $current_user['id'] : null,
            'invited_by_user_type' => $current_user ? $current_user['type'] : 'wordpress',
        ]);

        if (is_wp_error($result)) {
            return $result;
        }

        // Check for warning (invitation created but email failed)
        if (isset($result['warning'])) {
            return $this->success(
                $result['invitation'],
                $result['warning'],
                201
            );
        }

        return $this->success(
            $result['invitation'],
            $result['message'],
            201
        );
    }

    /**
     * List invitations
     *
     * GET /invitations
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function list_invitations(WP_REST_Request $request) {
        global $wpdb;

        $status = $request->get_param('status'); // pending, accepted, declined, expired
        $account_id = $request->get_param('account_id');
        $transaction_id = $request->get_param('transaction_id');
        $page = max(1, (int) $request->get_param('page') ?? 1);
        $per_page = min(100, max(1, (int) $request->get_param('per_page') ?? 20));
        $offset = ($page - 1) * $per_page;

        $table = $wpdb->prefix . 'ma_deal_user_invitations';
        $where = ['1=1'];
        $params = [];

        if ($status) {
            $where[] = 'status = %s';
            $params[] = $status;
        }

        if ($account_id) {
            $where[] = 'account_id = %d';
            $params[] = (int) $account_id;
        }

        if ($transaction_id) {
            $where[] = 'transaction_id = %d';
            $params[] = (int) $transaction_id;
        }

        $where_sql = implode(' AND ', $where);

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, ...$params);
        }
        $total = $wpdb->get_var($count_sql);

        // Get invitations
        $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $invitations = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);

        return $this->success([
            'invitations' => $invitations,
            'pagination' => [
                'total' => (int) $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page),
            ],
        ]);
    }

    /**
     * Get single invitation
     *
     * GET /invitations/{id}
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_invitation(WP_REST_Request $request) {
        global $wpdb;

        $invitation_id = (int) $request->get_param('id');
        $table = $wpdb->prefix . 'ma_deal_user_invitations';

        $invitation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $invitation_id
        ), ARRAY_A);

        if (!$invitation) {
            return $this->error(__('Invitation not found', 'ma-deal-room'), 404);
        }

        return $this->success($invitation);
    }

    /**
     * Accept invitation
     *
     * POST /invitations/accept
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function accept_invitation(WP_REST_Request $request) {
        $token = $request->get_param('token');

        if (empty($token)) {
            return $this->error(__('Invitation token is required', 'ma-deal-room'), 400);
        }

        // User data for new user registration
        $user_data = [];

        if ($request->has_param('password')) {
            $password = $request->get_param('password');
            $password_validation = $this->validation_service->validate_password($password);
            if (is_wp_error($password_validation)) {
                return $password_validation;
            }
            $user_data['password'] = $password;
        }

        if ($request->has_param('first_name')) {
            $user_data['first_name'] = sanitize_text_field($request->get_param('first_name'));
        }

        if ($request->has_param('last_name')) {
            $user_data['last_name'] = sanitize_text_field($request->get_param('last_name'));
        }

        if ($request->has_param('phone')) {
            $user_data['phone'] = sanitize_text_field($request->get_param('phone'));
        }

        // Accept invitation
        $result = $this->invitation_service->accept_invitation($token, $user_data);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success($result, __('Invitation accepted successfully', 'ma-deal-room'));
    }

    /**
     * Decline invitation
     *
     * POST /invitations/decline
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function decline_invitation(WP_REST_Request $request) {
        $token = $request->get_param('token');

        if (empty($token)) {
            return $this->error(__('Invitation token is required', 'ma-deal-room'), 400);
        }

        $result = $this->invitation_service->decline_invitation($token);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success(null, __('Invitation declined', 'ma-deal-room'));
    }

    /**
     * Cancel invitation
     *
     * POST /invitations/{id}/cancel
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function cancel_invitation(WP_REST_Request $request) {
        $invitation_id = (int) $request->get_param('id');
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('You must be logged in', 'ma-deal-room'), 401);
        }

        $result = $this->invitation_service->cancel_invitation(
            $invitation_id,
            $current_user['id']
        );

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success(null, __('Invitation cancelled successfully', 'ma-deal-room'));
    }

    /**
     * Get pending invitations for email
     *
     * GET /invitations/pending?email={email}
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_pending_for_email(WP_REST_Request $request) {
        $email = sanitize_email($request->get_param('email'));

        if (empty($email)) {
            return $this->error(__('Email is required', 'ma-deal-room'), 400);
        }

        $invitations = $this->invitation_service->get_pending_invitations_for_email($email);

        return $this->success(['invitations' => $invitations]);
    }

    /**
     * Get invitation statistics
     *
     * GET /invitations/stats
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_stats(WP_REST_Request $request) {
        $account_id = (int) $request->get_param('account_id');
        $days = min(365, max(1, (int) $request->get_param('days') ?? 30));

        if (!$account_id) {
            return $this->error(__('Account ID is required', 'ma-deal-room'), 400);
        }

        $stats = $this->invitation_service->get_invitation_stats($account_id, $days);

        return $this->success($stats);
    }
}
