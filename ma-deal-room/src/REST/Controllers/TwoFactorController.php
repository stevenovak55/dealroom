<?php
/**
 * Two-Factor Authentication REST Controller
 *
 * Handles 2FA management endpoints
 *
 * @package MADealRoom\REST\Controllers
 * @since 2.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use MADealRoom\Services\TwoFactorAuthService;
use MADealRoom\Services\AccountSecurityService;

/**
 * TwoFactorController Class
 *
 * REST API endpoints for two-factor authentication
 */
class TwoFactorController extends BaseController {

    /**
     * REST base
     *
     * @var string
     */
    protected $rest_base = '2fa';

    /**
     * Two-factor auth service
     *
     * @var TwoFactorAuthService
     */
    private $twofa_service;

    /**
     * Custom user repository
     *
     * @var \MADealRoom\Repositories\CustomUserRepository
     */
    private $custom_user_repository;

    /**
     * Account security service
     *
     * @var AccountSecurityService
     */
    private $security_service;

    /**
     * Constructor
     *
     * @param TwoFactorAuthService $twofa_service Two-factor auth service instance
     * @param \MADealRoom\Repositories\CustomUserRepository $custom_user_repository Custom user repository instance
     * @param AccountSecurityService $security_service Account security service instance
     */
    public function __construct(
        TwoFactorAuthService $twofa_service,
        $custom_user_repository,
        AccountSecurityService $security_service = null
    ) {
        parent::__construct();
        $this->twofa_service = $twofa_service;
        $this->custom_user_repository = $custom_user_repository;
        $this->security_service = $security_service ?? new AccountSecurityService();
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes(): void {
        // Enable 2FA - Step 1: Get QR code
        register_rest_route($this->namespace, '/' . $this->rest_base . '/enable', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'enable_2fa'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Enable 2FA - Step 2: Verify and activate
        register_rest_route($this->namespace, '/' . $this->rest_base . '/verify-setup', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'verify_setup'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Disable 2FA
        register_rest_route($this->namespace, '/' . $this->rest_base . '/disable', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'disable_2fa'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Verify 2FA code (for login)
        register_rest_route($this->namespace, '/' . $this->rest_base . '/verify', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'verify_code'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);

        // Regenerate backup codes
        register_rest_route($this->namespace, '/' . $this->rest_base . '/backup-codes/regenerate', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'regenerate_backup_codes'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Get 2FA status
        register_rest_route($this->namespace, '/' . $this->rest_base . '/status', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_status'],
                'permission_callback' => [$this, 'permission_callback'],
            ],
        ]);

        // Verify backup code
        register_rest_route($this->namespace, '/' . $this->rest_base . '/verify-backup', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'verify_backup_code'],
                'permission_callback' => [$this, 'public_permission_callback'],
            ],
        ]);
    }

    /**
     * Enable 2FA - Step 1
     *
     * POST /2fa/enable
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function enable_2fa(WP_REST_Request $request) {
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('You must be logged in', 'ma-deal-room'), 401);
        }

        // Check if 2FA is already enabled
        if ($current_user['type'] === 'custom') {
            $user = $this->user_repo->find($current_user['id']);
            if ($user && $user->two_factor_enabled) {
                return $this->error(__('Two-factor authentication is already enabled', 'ma-deal-room'), 400);
            }
        }

        // Generate secret and QR code
        $result = $this->twofa_service->enable_2fa(
            $current_user['id'],
            $current_user['type']
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Record security event - temporarily disabled
        
        $this->security_service->record_event(
        			$current_user['id'],
        			$current_user['type'],
        			'2fa_setup_initiated'
        // );

        return $this->success([
            'secret' => $result['secret'],
            'qr_code' => $result['qr_code'],
            'backup_codes' => $result['backup_codes'],
            'message' => __(
                'Scan the QR code with your authenticator app (Google Authenticator, Authy, etc.), then verify with a code to complete setup.',
                'ma-deal-room'
            ),
        ], __('2FA setup initiated', 'ma-deal-room'));
    }

    /**
     * Verify and activate 2FA - Step 2
     *
     * POST /2fa/verify-setup
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function verify_setup(WP_REST_Request $request) {
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('You must be logged in', 'ma-deal-room'), 401);
        }

        $code = $request->get_param('code');

        if (empty($code)) {
            return $this->error(__('Verification code is required', 'ma-deal-room'), 400);
        }

        // Verify setup code
        $result = $this->twofa_service->verify_2fa_setup(
            $current_user['id'],
            $current_user['type'],
            $code
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Record security event - temporarily disabled
        
        $this->security_service->record_event(
        			$current_user['id'],
        			$current_user['type'],
        			'2fa_enabled'
        // );

        return $this->success($result, __('Two-factor authentication enabled successfully', 'ma-deal-room'));
    }

    /**
     * Disable 2FA
     *
     * POST /2fa/disable
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function disable_2fa(WP_REST_Request $request) {
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('You must be logged in', 'ma-deal-room'), 401);
        }

        $password = $request->get_param('password');

        if (empty($password)) {
            return $this->error(__('Password is required to disable 2FA', 'ma-deal-room'), 400);
        }

        // Disable 2FA
        $result = $this->twofa_service->disable_2fa(
            $current_user['id'],
            $current_user['type'],
            $password
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Record security event - temporarily disabled
        
        $this->security_service->record_event(
        			$current_user['id'],
        			$current_user['type'],
        			'2fa_disabled'
        // );

        return $this->success(null, __('Two-factor authentication disabled successfully', 'ma-deal-room'));
    }

    /**
     * Verify 2FA code
     *
     * POST /2fa/verify
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function verify_code(WP_REST_Request $request) {
        $user_id = $request->get_param('user_id');
        $user_type = $request->get_param('user_type');
        $code = $request->get_param('code');

        if (empty($user_id) || empty($user_type) || empty($code)) {
            return $this->error(__('User ID, user type, and code are required', 'ma-deal-room'), 400);
        }

        // Verify code
        $result = $this->twofa_service->verify_2fa_code(
            (int) $user_id,
            $user_type,
            $code
        );

        if (is_wp_error($result)) {
            // Record failed attempt - temporarily disabled
            
            $this->security_service->record_event(
            			$user_id,
            			$user_type,
            			'2fa_verification_failed',
            			['code_attempted' => substr($code, 0, 2) . '****']
            // );

            return $result;
        }

        // Record successful verification - temporarily disabled
        
        $this->security_service->record_event(
        			$user_id,
        			$user_type,
        			'2fa_verified'
        // );

        return $this->success(['verified' => true], __('Code verified successfully', 'ma-deal-room'));
    }

    /**
     * Verify backup code
     *
     * POST /2fa/verify-backup
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function verify_backup_code(WP_REST_Request $request) {
        $user_id = $request->get_param('user_id');
        $user_type = $request->get_param('user_type');
        $code = $request->get_param('code');

        if (empty($user_id) || empty($user_type) || empty($code)) {
            return $this->error(__('User ID, user type, and backup code are required', 'ma-deal-room'), 400);
        }

        // Verify backup code
        $result = $this->twofa_service->verify_2fa_code(
            (int) $user_id,
            $user_type,
            $code
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Record backup code usage - temporarily disabled
        
        $this->security_service->record_event(
        			$user_id,
        			$user_type,
        			'2fa_backup_used'
        // );

        return $this->success([
            'verified' => true,
            'message' => __('Backup code accepted. Consider regenerating your backup codes.', 'ma-deal-room'),
        ], __('Backup code verified successfully', 'ma-deal-room'));
    }

    /**
     * Regenerate backup codes
     *
     * POST /2fa/backup-codes/regenerate
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function regenerate_backup_codes(WP_REST_Request $request) {
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('You must be logged in', 'ma-deal-room'), 401);
        }

        $password = $request->get_param('password');

        if (empty($password)) {
            return $this->error(__('Password is required to regenerate backup codes', 'ma-deal-room'), 400);
        }

        // Regenerate codes
        $result = $this->twofa_service->regenerate_backup_codes(
            $current_user['id'],
            $current_user['type'],
            $password
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Record security event - temporarily disabled
        
        $this->security_service->record_event(
        			$current_user['id'],
        			$current_user['type'],
        			'2fa_backup_codes_regenerated'
        // );

        return $this->success([
            'backup_codes' => $result['backup_codes'],
            'message' => __('New backup codes generated. Save these in a secure location.', 'ma-deal-room'),
        ], __('Backup codes regenerated successfully', 'ma-deal-room'));
    }

    /**
     * Get 2FA status for current user
     *
     * GET /2fa/status
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_status(WP_REST_Request $request) {
        $current_user = $this->get_current_user();

        if (!$current_user) {
            return $this->error(__('You must be logged in', 'ma-deal-room'), 401);
        }

        if ($current_user['type'] === 'custom') {
            $user = $this->user_repo->find($current_user['id']);

            if (!$user) {
                return $this->error(__('User not found', 'ma-deal-room'), 404);
            }

            return $this->success([
                'enabled' => $user->two_factor_enabled,
                'method' => $user->two_factor_enabled ? 'totp' : null,
            ]);
        } else {
            // Check WordPress user meta
            $enabled = (bool) get_user_meta($current_user['id'], 'ma_deal_2fa_enabled', true);

            return $this->success([
                'enabled' => $enabled,
                'method' => $enabled ? 'totp' : null,
            ]);
        }
    }
}
