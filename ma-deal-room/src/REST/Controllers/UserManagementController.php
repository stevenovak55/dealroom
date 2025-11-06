<?php
/**
 * User Management REST Controller
 *
 * Handles user management endpoints for admins
 *
 * @package MADealRoom\REST\Controllers
 * @since 2.0.0
 */

namespace MADealRoom\REST\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use MADealRoom\Repositories\CustomUserRepository;
use MADealRoom\Repositories\UserRoleRepository;
use MADealRoom\Services\AccountSecurityService;
use MADealRoom\Services\ValidationService;

/**
 * UserManagementController Class
 *
 * REST API endpoints for user management (admin only)
 */
class UserManagementController extends BaseController {

    /**
     * REST base
     *
     * @var string
     */
    protected $rest_base = 'users';

    /**
     * Custom user repository
     *
     * @var CustomUserRepository
     */
    private $custom_user_repository;

    /**
     * User role repository
     *
     * @var UserRoleRepository
     */
    private $role_repo;

    /**
     * Account security service
     *
     * @var AccountSecurityService
     */
    private $security_service;

    /**
     * Validation service
     *
     * @var ValidationService
     */
    private $validation_service;

    /**
     * Constructor
     *
     * @param CustomUserRepository $custom_user_repository Custom user repository instance
     * @param UserRoleRepository $role_repo User role repository instance
     * @param AccountSecurityService $security_service Account security service instance
     * @param ValidationService $validation_service Validation service instance
     */
    public function __construct(
        CustomUserRepository $custom_user_repository,
        UserRoleRepository $role_repo,
        AccountSecurityService $security_service,
        ValidationService $validation_service
    ) {
        parent::__construct();
        $this->custom_user_repository = $custom_user_repository;
        $this->role_repo = $role_repo;
        $this->security_service = $security_service;
        $this->validation_service = $validation_service;
    }

    /**
     * Register routes
     *
     * @return void
     */
    public function register_routes(): void {
        // List users
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => 'GET',
                'callback' => [$this, 'list_users'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Get single user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_user'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Update user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_user'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Delete user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)', [
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_user'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Lock user account
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/lock', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'lock_user'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Unlock user account
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/unlock', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'unlock_user'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Get user roles
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/roles', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_user_roles'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Assign role to user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/roles', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'assign_role'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Remove role from user
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/roles/(?P<role_id>\d+)', [
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'remove_role'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Get user security events
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>\d+)/security-events', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_security_events'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_manage_users');
                },
            ],
        ]);

        // Search users
        register_rest_route($this->namespace, '/' . $this->rest_base . '/search', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'search_users'],
                'permission_callback' => function($request) {
                    return $this->permission_callback_with_cap($request, 'ma_deal_view_users');
                },
            ],
        ]);
    }

    /**
     * List users
     *
     * GET /users
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function list_users(WP_REST_Request $request) {
        $user_type = $request->get_param('user_type') ?? 'custom';
        $page = max(1, (int) $request->get_param('page') ?? 1);
        $per_page = min(100, max(1, (int) $request->get_param('per_page') ?? 20));
        $offset = ($page - 1) * $per_page;

        if ($user_type === 'custom') {
            // Get custom users
            $users = $this->custom_user_repository->query([], [
                'limit' => $per_page,
                'offset' => $offset,
                'order_by' => 'created_at',
                'order' => 'DESC',
            ]);

            $total = $this->custom_user_repository->count();

            $users_data = array_map(function($user) {
                return $user->to_array();
            }, $users);
        } else {
            // Get WordPress users
            $wp_users = get_users([
                'number' => $per_page,
                'offset' => $offset,
                'orderby' => 'registered',
                'order' => 'DESC',
            ]);

            $total = count_users()['total_users'];

            $users_data = array_map(function($wp_user) {
                return [
                    'id' => $wp_user->ID,
                    'email' => $wp_user->user_email,
                    'display_name' => $wp_user->display_name,
                    'roles' => $wp_user->roles,
                    'registered' => $wp_user->user_registered,
                ];
            }, $wp_users);
        }

        return $this->success([
            'users' => $users_data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => ceil($total / $per_page),
            ],
        ]);
    }

    /**
     * Get single user
     *
     * GET /users/{id}
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_user(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $user_type = $request->get_param('user_type') ?? 'custom';

        if ($user_type === 'custom') {
            $user = $this->custom_user_repository->find($user_id);

            if (!$user) {
                return $this->error(__('User not found', 'ma-deal-room'), 404);
            }

            return $this->success($user->to_array());
        } else {
            $wp_user = get_user_by('ID', $user_id);

            if (!$wp_user) {
                return $this->error(__('User not found', 'ma-deal-room'), 404);
            }

            return $this->success([
                'id' => $wp_user->ID,
                'email' => $wp_user->user_email,
                'display_name' => $wp_user->display_name,
                'roles' => $wp_user->roles,
                'registered' => $wp_user->user_registered,
            ]);
        }
    }

    /**
     * Update user
     *
     * PUT /users/{id}
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_user(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $user_type = $request->get_param('user_type') ?? 'custom';

        if ($user_type === 'custom') {
            $user = $this->custom_user_repository->find($user_id);

            if (!$user) {
                return $this->error(__('User not found', 'ma-deal-room'), 404);
            }

            $data = [];

            // Validate and sanitize fields
            if ($request->has_param('first_name')) {
                $first_name = sanitize_text_field($request->get_param('first_name'));
                $validation = $this->validation_service->validate_name($first_name, 'first_name');
                if (is_wp_error($validation)) {
                    return $validation;
                }
                $data['first_name'] = $first_name;
            }

            if ($request->has_param('last_name')) {
                $last_name = sanitize_text_field($request->get_param('last_name'));
                $validation = $this->validation_service->validate_name($last_name, 'last_name');
                if (is_wp_error($validation)) {
                    return $validation;
                }
                $data['last_name'] = $last_name;
            }

            if ($request->has_param('phone')) {
                $phone = sanitize_text_field($request->get_param('phone'));
                $validation = $this->validation_service->validate_phone($phone);
                if (is_wp_error($validation)) {
                    return $validation;
                }
                $data['phone'] = $phone;
            }

            if (empty($data)) {
                return $this->error(__('No valid fields to update', 'ma-deal-room'), 400);
            }

            // Update user
            $updated = $this->custom_user_repository->update($user_id, $data);

            if (!$updated) {
                return $this->error(__('Failed to update user', 'ma-deal-room'), 500);
            }

            // Get updated user
            $user = $this->custom_user_repository->find($user_id);

            return $this->success($user->to_array(), __('User updated successfully', 'ma-deal-room'));
        } else {
            return $this->error(__('WordPress user updates not supported via this endpoint', 'ma-deal-room'), 400);
        }
    }

    /**
     * Delete user
     *
     * DELETE /users/{id}
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function delete_user(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $user_type = $request->get_param('user_type') ?? 'custom';

        if ($user_type === 'custom') {
            $user = $this->custom_user_repository->find($user_id);

            if (!$user) {
                return $this->error(__('User not found', 'ma-deal-room'), 404);
            }

            // Soft delete
            $deleted = $this->custom_user_repository->delete($user_id);

            if (!$deleted) {
                return $this->error(__('Failed to delete user', 'ma-deal-room'), 500);
            }

            return $this->success(null, __('User deleted successfully', 'ma-deal-room'));
        } else {
            return $this->error(__('WordPress user deletion not supported via this endpoint', 'ma-deal-room'), 400);
        }
    }

    /**
     * Lock user account
     *
     * POST /users/{id}/lock
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function lock_user(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $user_type = $request->get_param('user_type') ?? 'custom';

        if ($user_type === 'custom') {
            $user = $this->custom_user_repository->find($user_id);

            if (!$user) {
                return $this->error(__('User not found', 'ma-deal-room'), 404);
            }

            $locked = $this->security_service->lock_account($user->email);

            if (!$locked) {
                return $this->error(__('Failed to lock account', 'ma-deal-room'), 500);
            }

            return $this->success(null, __('User account locked successfully', 'ma-deal-room'));
        } else {
            return $this->error(__('WordPress user locking not supported via this endpoint', 'ma-deal-room'), 400);
        }
    }

    /**
     * Unlock user account
     *
     * POST /users/{id}/unlock
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function unlock_user(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $user_type = $request->get_param('user_type') ?? 'custom';

        if ($user_type === 'custom') {
            $user = $this->custom_user_repository->find($user_id);

            if (!$user) {
                return $this->error(__('User not found', 'ma-deal-room'), 404);
            }

            $unlocked = $this->security_service->unlock_account($user->email);

            if (!$unlocked) {
                return $this->error(__('Failed to unlock account', 'ma-deal-room'), 500);
            }

            return $this->success(null, __('User account unlocked successfully', 'ma-deal-room'));
        } else {
            return $this->error(__('WordPress user unlocking not supported via this endpoint', 'ma-deal-room'), 400);
        }
    }

    /**
     * Get user roles
     *
     * GET /users/{id}/roles
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_user_roles(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $user_type = $request->get_param('user_type') ?? 'custom';

        $roles = $this->role_repo->get_user_roles($user_id, $user_type);

        return $this->success(['roles' => $roles]);
    }

    /**
     * Assign role to user
     *
     * POST /users/{id}/roles
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function assign_role(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $user_type = $request->get_param('user_type') ?? 'custom';
        $role_type = sanitize_text_field($request->get_param('role_type'));
        $transaction_id = $request->get_param('transaction_id');

        // Validate role type
        $validation = $this->validation_service->validate_role_type($role_type);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Create role
        $role = new \MADealRoom\Models\UserRole();
        $role->user_id = $user_id;
        $role->user_type = $user_type;
        $role->role_type = $role_type;
        $role->transaction_id = $transaction_id ? (int) $transaction_id : null;
        $role->is_primary = filter_var($request->get_param('is_primary'), FILTER_VALIDATE_BOOLEAN);

        $current_user = $this->get_current_user();
        $role->assigned_by_user_id = $current_user ? $current_user['id'] : null;

        // Assign role
        $role_id = $this->role_repo->assign_role($role);

        if (!$role_id) {
            return $this->error(__('Failed to assign role', 'ma-deal-room'), 500);
        }

        return $this->success(['role_id' => $role_id], __('Role assigned successfully', 'ma-deal-room'), 201);
    }

    /**
     * Remove role from user
     *
     * DELETE /users/{id}/roles/{role_id}
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function remove_role(WP_REST_Request $request) {
        $role_id = (int) $request->get_param('role_id');

        $removed = $this->role_repo->remove_role($role_id);

        if (!$removed) {
            return $this->error(__('Failed to remove role', 'ma-deal-room'), 500);
        }

        return $this->success(null, __('Role removed successfully', 'ma-deal-room'));
    }

    /**
     * Get user security events
     *
     * GET /users/{id}/security-events
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_security_events(WP_REST_Request $request) {
        $user_id = (int) $request->get_param('id');
        $user_type = $request->get_param('user_type') ?? 'custom';
        $limit = min(100, max(1, (int) $request->get_param('limit') ?? 50));
        $offset = max(0, (int) $request->get_param('offset') ?? 0);

        $events = $this->security_service->get_user_security_events(
            $user_id,
            $user_type,
            $limit,
            $offset
        );

        return $this->success(['events' => $events]);
    }

    /**
     * Search users
     *
     * GET /users/search
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function search_users(WP_REST_Request $request) {
        $search = sanitize_text_field($request->get_param('q'));
        $user_type = $request->get_param('user_type') ?? 'custom';
        $limit = min(50, max(1, (int) $request->get_param('limit') ?? 20));

        if (empty($search)) {
            return $this->error(__('Search query is required', 'ma-deal-room'), 400);
        }

        if ($user_type === 'custom') {
            $users = $this->custom_user_repository->search($search, $limit);

            $users_data = array_map(function($user) {
                return $user->to_array();
            }, $users);
        } else {
            $wp_users = get_users([
                'search' => '*' . $search . '*',
                'number' => $limit,
            ]);

            $users_data = array_map(function($wp_user) {
                return [
                    'id' => $wp_user->ID,
                    'email' => $wp_user->user_email,
                    'display_name' => $wp_user->display_name,
                    'roles' => $wp_user->roles,
                ];
            }, $wp_users);
        }

        return $this->success(['users' => $users_data]);
    }
}
