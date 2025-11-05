<?php
/**
 * Authentication REST Controller
 *
 * Handles user authentication endpoints (login, register, logout, etc.)
 *
 * @package MADealRoom\REST\Controllers
 * @since 2.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use MADealRoom\Services\AuthService;
use MADealRoom\Services\EmailVerificationService;
use MADealRoom\Services\PasswordResetService;
use MADealRoom\Services\AccountSecurityService;
use MADealRoom\Services\ValidationService;

/**
 * AuthController Class
 *
 * REST API endpoints for authentication operations
 */
class AuthController extends BaseController {

    /**
     * REST base
     *
     * @var string
     */
    protected $rest_base = 'auth';

    /**
     * Custom user repository
     *
     * @var \MADealRoom\Repositories\CustomUserRepository
     */
    private $custom_user_repository;

    /**
     * Email verification service
     *
     * @var EmailVerificationService
     */
    private $email_verification_service;

    /**
     * Password reset service
     *
     * @var PasswordResetService
     */
    private $password_reset_service;

    /**
     * Validation service
     *
     * @var ValidationService
     */
    private $validation_service;

    /**
     * Account security service
     *
     * @var AccountSecurityService
     */
    private $security_service;

    /**
     * Constructor
     *
     * @param AuthService $auth_service Auth service instance
     * @param \MADealRoom\Repositories\CustomUserRepository $custom_user_repository Custom user repository instance
     * @param EmailVerificationService $email_verification_service Email verification service instance
     * @param PasswordResetService $password_reset_service Password reset service instance
     * @param ValidationService $validation_service Validation service instance
     * @param AccountSecurityService $security_service Account security service instance
     */
    public function __construct(
        AuthService $auth_service,
        $custom_user_repository,
        EmailVerificationService $email_verification_service,
        PasswordResetService $password_reset_service,
        ValidationService $validation_service,
        AccountSecurityService $security_service = null
    ) {
        parent::__construct();
        $this->auth_service = $auth_service;
        $this->custom_user_repository = $custom_user_repository;
        $this->email_verification_service = $email_verification_service;
        $this->password_reset_service = $password_reset_service;
        $this->validation_service = $validation_service;
        $this->security_service = $security_service ?? new AccountSecurityService();
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes(): void {
        // Public endpoints (no authentication required)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/register', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'register'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/login', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'login'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/refresh', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'refresh_token'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/verify-email', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'verify_email'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/request-password-reset', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'request_password_reset'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/reset-password', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'reset_password'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        // Protected endpoints (require authentication)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/logout', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'logout'],
                'permission_callback' => [$this, 'public_permission_callback'], // Allow both auth types
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/logout-all', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'logout_all_devices'],
                'permission_callback' => [$this, 'permission_callback'], // Requires authentication
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/me', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_current_user_info'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/verify-token', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'verify_token'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/change-password', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'change_password'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/resend-verification', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'resend_verification'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);
    }

    /**
     * Register a new user
     *
     * POST /auth/register
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function register(WP_REST_Request $request) {
        // Rate limiting (5 req/min for registration)
        $rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'register');
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check;
        }

        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $first_name = sanitize_text_field($request->get_param('first_name'));
        $last_name = sanitize_text_field($request->get_param('last_name'));
        $phone = sanitize_text_field($request->get_param('phone'));

        // Validate required fields
        if (empty($email) || empty($password)) {
            return $this->error(__('Email and password are required', 'ma-deal-room'), 400);
        }

        // Validate email
        $email_validation = $this->validation_service->validate_email($email);
        if (is_wp_error($email_validation)) {
            return $email_validation;
        }

        // Validate password
        $password_validation = $this->validation_service->validate_password($password);
        if (is_wp_error($password_validation)) {
            return $password_validation;
        }

        // Validate names if provided
        if ($first_name) {
            $name_validation = $this->validation_service->validate_name($first_name, 'first_name');
            if (is_wp_error($name_validation)) {
                return $name_validation;
            }
        }

        if ($last_name) {
            $name_validation = $this->validation_service->validate_name($last_name, 'last_name');
            if (is_wp_error($name_validation)) {
                return $name_validation;
            }
        }

        // Validate phone if provided
        if ($phone) {
            $phone_validation = $this->validation_service->validate_phone($phone);
            if (is_wp_error($phone_validation)) {
                return $phone_validation;
            }
        }

        // Register user
        $result = $this->auth_service->register([
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
        ]);

        if (is_wp_error($result)) {
            return $result;
        }

        // Send verification email
        $this->email_verification_service->send_verification_email(
            $result['user_id'],
            'custom'
        );

        // Record security event - temporarily disabled
        
        $this->security_service->record_event(
        //     $result['user_id'],
        //     'custom',
        //     'user_registered',
        //     ['email' => $email]
        // );

        return $this->success([
            'user' => $result['user'],
            'message' => __('Registration successful. Please check your email to verify your account.', 'ma-deal-room'),
        ], __('User registered successfully', 'ma-deal-room'), 201);
    }

    /**
     * Login user
     *
     * POST /auth/login
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function login(WP_REST_Request $request) {
        // Rate limiting (5 req/min for login - prevents brute force)
        $rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'login');
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check;
        }

        $email = sanitize_email($request->get_param('email'));
        $password = $request->get_param('password');
        $remember = filter_var($request->get_param('remember'), FILTER_VALIDATE_BOOLEAN);

        // Validate inputs
        if (empty($email) || empty($password)) {
            return $this->error(__('Email and password are required', 'ma-deal-room'), 400);
        }

        // Check if account is locked - temporarily disabled
        
        // $lockout_info = $this->security_service->is_account_locked($email);
        // if ($lockout_info) {
        //     return $this->error(
        //         sprintf(
        //             __('Account is locked. Please try again in %d minutes.', 'ma-deal-room'),
        //             $lockout_info['minutes_remaining']
        //         ),
        //         429,
        //         'account_locked'
        //     );
        // }

        // Get device info
        $device_info = [
            'device_name' => $request->get_param('device_name') ?? 'Unknown Device',
            'device_type' => $request->get_param('device_type') ?? 'web',
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        // Attempt login
        $result = $this->auth_service->login($email, $password, $device_info);

        if (is_wp_error($result)) {
            // Record failed login attempt - temporarily disabled
            
            // $this->security_service->record_failed_login($email, $device_info['ip_address'], $result->get_error_code());
            return $result;
        }

        // Record successful login - temporarily disabled
        
        // $this->security_service->record_successful_login(
        //     $result['user_id'],
        //     $result['user_type'],
        //     $device_info['ip_address']
        // );

        return $this->success([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
            'expires_in' => $result['expires_in'],
            'user' => $result['user'],
        ], __('Login successful', 'ma-deal-room'));
    }

    /**
     * Logout user
     *
     * POST /auth/logout
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function logout(WP_REST_Request $request) {
        // Try JWT logout first
        $token = $this->extract_jwt_token($request);

        if ($token) {
            // JWT logout
            $refresh_token = $request->get_param('refresh_token');

            if (!$refresh_token) {
                return $this->error(__('Refresh token required for logout', 'ma-deal-room'), 400);
            }

            $result = $this->auth_service->logout($refresh_token);

            if (is_wp_error($result)) {
                return $result;
            }

            return $this->success(null, __('Logout successful', 'ma-deal-room'));
        }

        // WordPress session logout
        if (is_user_logged_in()) {
            wp_logout();
            return $this->success(null, __('Logout successful', 'ma-deal-room'));
        }

        return $this->error(__('No active session found', 'ma-deal-room'), 400);
    }

    /**
     * Logout all devices (revoke all sessions)
     *
     * POST /auth/logout-all
     * T2.1.4: Session Regeneration - "Logout all devices" functionality
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function logout_all_devices(WP_REST_Request $request) {
        // Get current user info from JWT
        $token = $this->extract_jwt_token($request);

        if (!$token) {
            return $this->error(__('Authentication required', 'ma-deal-room'), 401);
        }

        $token_data = $this->auth_service->verify_access_token($token);

        if (is_wp_error($token_data)) {
            return $token_data;
        }

        $user_id = $token_data['sub'];
        $user_type = $token_data['user_type'];

        // Optional: Get current session ID to keep current session active
        $keep_current_session = $request->get_param('keep_current_session') ?? false;
        $current_session_id = null;

        if ($keep_current_session) {
            $refresh_token = $request->get_param('refresh_token');
            if ($refresh_token) {
                $refresh_token_hash = hash('sha256', $refresh_token);
                $session = $this->auth_service->session_repo->find_by_token($refresh_token_hash);
                if ($session) {
                    $current_session_id = $session->id;
                }
            }
        }

        // Revoke all sessions
        $result = $this->auth_service->logout_all($user_id, $user_type, $current_session_id, 'logout_all');

        if (!$result) {
            return $this->error(__('Failed to logout all devices', 'ma-deal-room'), 500);
        }

        $message = $keep_current_session && $current_session_id
            ? __('All other devices have been logged out successfully', 'ma-deal-room')
            : __('All devices have been logged out successfully', 'ma-deal-room');

        return $this->success(
            ['sessions_revoked' => true],
            $message
        );
    }

    /**
     * Refresh access token
     *
     * POST /auth/refresh
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function refresh_token(WP_REST_Request $request) {
        $refresh_token = $request->get_param('refresh_token');

        if (empty($refresh_token)) {
            return $this->error(__('Refresh token is required', 'ma-deal-room'), 400);
        }

        $result = $this->auth_service->refresh_token($refresh_token);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success([
            'access_token' => $result['access_token'],
            'expires_in' => $result['expires_in'],
        ], __('Token refreshed successfully', 'ma-deal-room'));
    }

    /**
     * Verify access token
     *
     * POST /auth/verify-token
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function verify_token(WP_REST_Request $request) {
        $token = $request->get_param('token');

        if (empty($token)) {
            return $this->error(__('Token is required', 'ma-deal-room'), 400);
        }

        $payload = $this->auth_service->verify_access_token($token);

        if (is_wp_error($payload)) {
            return $payload;
        }

        return $this->success([
            'valid' => true,
            'user_id' => $payload['user_id'],
            'user_type' => $payload['user_type'],
        ], __('Token is valid', 'ma-deal-room'));
    }

    /**
     * Get current user info
     *
     * GET /auth/me
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_current_user_info(WP_REST_Request $request) {
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('No authenticated user', 'ma-deal-room'), 401);
        }

        if ($current_user['type'] === 'custom') {
            $user = $this->user_repo->find($current_user['id']);

            if (!$user) {
                return $this->error(__('User not found', 'ma-deal-room'), 404);
            }

            return $this->success([
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
                'email_verified' => $user->email_verified,
                'two_factor_enabled' => $user->two_factor_enabled,
                'user_type' => 'custom',
                'created_at' => $user->created_at,
            ]);
        } else {
            $wp_user = $current_user['user'];
            return $this->success([
                'id' => $wp_user->ID,
                'email' => $wp_user->user_email,
                'display_name' => $wp_user->display_name,
                'user_type' => 'wordpress',
                'roles' => $wp_user->roles,
            ]);
        }
    }

    /**
     * Verify email with token
     *
     * POST /auth/verify-email
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function verify_email(WP_REST_Request $request) {
        // Rate limiting: 3 verification attempts per hour per IP (prevents token enumeration)
        $rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'verify_email', 3, 3600);
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check;
        }

        $token = $request->get_param('token');

        if (empty($token)) {
            return $this->error(__('Verification token is required', 'ma-deal-room'), 400);
        }

        $result = $this->email_verification_service->verify_email($token);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success($result, __('Email verified successfully. You can now log in.', 'ma-deal-room'));
    }

    /**
     * Resend email verification
     *
     * POST /auth/resend-verification
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function resend_verification(WP_REST_Request $request) {
        // Rate limiting: 3 resend requests per hour per IP (prevents email spam)
        $rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'resend_verification', 3, 3600);
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check;
        }

        // Allow email parameter for unauthenticated users who can't log in yet
        $email = $request->get_param('email');

        if ($email) {
            // Unauthenticated request with email
            $email = sanitize_email($email);

            if (!is_email($email)) {
                return $this->error(__('Valid email address is required', 'ma-deal-room'), 400);
            }

            // Find user by email
            $user = $this->custom_user_repository->find_by_email($email);

            if (!$user) {
                // Don't reveal if email exists (security)
                return $this->success(null, __('If an account exists with this email, a verification link has been sent.', 'ma-deal-room'));
            }

            // Check if already verified
            if ($user->is_email_verified()) {
                return $this->error(__('Email is already verified. You can log in now.', 'ma-deal-room'), 400);
            }

            $result = $this->email_verification_service->resend_verification_email(
                $user->id,
                'custom'
            );

            if (is_wp_error($result)) {
                // Don't expose error details (security)
                return $this->success(null, __('If an account exists with this email, a verification link has been sent.', 'ma-deal-room'));
            }

            return $this->success(null, __('Verification email sent. Please check your inbox.', 'ma-deal-room'));
        }

        // Authenticated request
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('Email parameter required for unauthenticated requests', 'ma-deal-room'), 401);
        }

        $result = $this->email_verification_service->resend_verification_email(
            $current_user['id'],
            $current_user['type']
        );

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success(null, __('Verification email sent. Please check your inbox.', 'ma-deal-room'));
    }

    /**
     * Request password reset
     *
     * POST /auth/request-password-reset
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function request_password_reset(WP_REST_Request $request) {
        // Rate limiting (prevent password reset enumeration)
        $rate_limit_check = $this->rate_limiter->check_rate_limit($request, 'reset-password');
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check;
        }

        $email = sanitize_email($request->get_param('email'));

        if (empty($email)) {
            return $this->error(__('Email is required', 'ma-deal-room'), 400);
        }

        $result = $this->password_reset_service->request_password_reset($email);

        if (is_wp_error($result)) {
            return $result;
        }

        // Always return success (don't reveal if email exists)
        return $this->success(
            null,
            __('If an account exists with this email, you will receive password reset instructions.', 'ma-deal-room')
        );
    }

    /**
     * Reset password with token
     *
     * POST /auth/reset-password
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function reset_password(WP_REST_Request $request) {
        $token = $request->get_param('token');
        $new_password = $request->get_param('new_password');

        if (empty($token) || empty($new_password)) {
            return $this->error(__('Token and new password are required', 'ma-deal-room'), 400);
        }

        // Validate password strength
        $password_validation = $this->validation_service->validate_password($new_password);
        if (is_wp_error($password_validation)) {
            return $password_validation;
        }

        $result = $this->password_reset_service->reset_password($token, $new_password);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success($result, __('Password reset successfully', 'ma-deal-room'));
    }

    /**
     * Change password (for logged-in users)
     *
     * POST /auth/change-password
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function change_password(WP_REST_Request $request) {
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('You must be logged in', 'ma-deal-room'), 401);
        }

        $current_password = $request->get_param('current_password');
        $new_password = $request->get_param('new_password');

        if (empty($current_password) || empty($new_password)) {
            return $this->error(__('Current password and new password are required', 'ma-deal-room'), 400);
        }

        // Validate new password strength
        $password_validation = $this->validation_service->validate_password($new_password);
        if (is_wp_error($password_validation)) {
            return $password_validation;
        }

        $result = $this->password_reset_service->change_password(
            $current_user['id'],
            $current_user['type'],
            $current_password,
            $new_password
        );

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->success(null, __('Password changed successfully', 'ma-deal-room'));
    }
}
