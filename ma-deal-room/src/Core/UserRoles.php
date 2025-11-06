<?php
/**
 * User Roles and Capabilities Management
 *
 * Manages WordPress roles and capabilities for the MA Deal Room plugin.
 * Supports both WordPress users and custom users with role-based access control.
 *
 * @package    MA_Deal_Room
 * @subpackage Core
 * @since      2.0.0
 */

namespace MADealRoom\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * UserRoles Class
 *
 * Handles registration and management of custom WordPress roles and capabilities
 * for the Deal Room plugin's user system.
 */
class UserRoles {

    /**
     * Custom capability prefix
     */
    const CAP_PREFIX = 'ma_deal_';

    /**
     * Initialize the user roles system
     */
    public function init(): void {
        // Register activation hook to setup roles
        register_activation_hook(MA_DEAL_ROOM_PLUGIN_FILE, [$this, 'setup_roles_on_activation']);

        // Register deactivation hook to cleanup roles
        register_deactivation_hook(MA_DEAL_ROOM_PLUGIN_FILE, [$this, 'cleanup_roles_on_deactivation']);

        // Check and update capabilities on admin init (for plugin updates)
        add_action('admin_init', [$this, 'check_and_update_capabilities']);
    }

    /**
     * Check and update capabilities if needed (runs on admin_init)
     *
     * This ensures capabilities are up-to-date after plugin updates
     * without requiring reactivation
     */
    public function check_and_update_capabilities(): void {
        // Get stored version
        $stored_version = get_option('ma_deal_room_roles_version', '0.0.0');
        $current_version = defined('MA_DEAL_VERSION') ? MA_DEAL_VERSION : '2.5.0';

        // Only run if version changed or never run
        if (version_compare($stored_version, $current_version, '<')) {
            // Update capabilities for existing roles
            $this->assign_capabilities_to_existing_roles();

            // Update stored version
            update_option('ma_deal_room_roles_version', $current_version);

            // Clear any cached user capabilities
            wp_cache_flush();
        }
    }

    /**
     * Setup roles and capabilities on plugin activation
     */
    public function setup_roles_on_activation(): void {
        $this->register_capabilities();
        $this->register_roles();
        $this->assign_capabilities_to_existing_roles();
    }

    /**
     * Cleanup roles on plugin deactivation (optional - keeps roles by default)
     */
    public function cleanup_roles_on_deactivation(): void {
        // Note: We don't remove roles on deactivation to preserve user data
        // Only remove on uninstall (handled in uninstall.php)
    }

    /**
     * Get all custom capabilities
     *
     * @return array Array of capabilities grouped by category
     */
    public static function get_capabilities(): array {
        return [
            // ==================== TRANSACTION CAPABILITIES ====================
            'transaction' => [
                self::CAP_PREFIX . 'view_transactions' => __('View all transactions', 'ma-deal-room'),
                self::CAP_PREFIX . 'view_own_transactions' => __('View own transactions', 'ma-deal-room'),
                self::CAP_PREFIX . 'create_transaction' => __('Create new transactions', 'ma-deal-room'),
                self::CAP_PREFIX . 'edit_transaction' => __('Edit transactions', 'ma-deal-room'),
                self::CAP_PREFIX . 'edit_own_transaction' => __('Edit own transactions', 'ma-deal-room'),
                self::CAP_PREFIX . 'delete_transaction' => __('Delete transactions', 'ma-deal-room'),
                self::CAP_PREFIX . 'export_transactions' => __('Export transaction data', 'ma-deal-room'),
            ],

            // ==================== TASK CAPABILITIES ====================
            'task' => [
                self::CAP_PREFIX . 'view_tasks' => __('View all tasks', 'ma-deal-room'),
                self::CAP_PREFIX . 'view_own_tasks' => __('View assigned tasks', 'ma-deal-room'),
                self::CAP_PREFIX . 'create_task' => __('Create tasks', 'ma-deal-room'),
                self::CAP_PREFIX . 'edit_task' => __('Edit tasks', 'ma-deal-room'),
                self::CAP_PREFIX . 'edit_own_task' => __('Edit assigned tasks', 'ma-deal-room'),
                self::CAP_PREFIX . 'delete_task' => __('Delete tasks', 'ma-deal-room'),
                self::CAP_PREFIX . 'complete_task' => __('Mark tasks complete', 'ma-deal-room'),
                self::CAP_PREFIX . 'skip_task' => __('Skip tasks', 'ma-deal-room'),
                self::CAP_PREFIX . 'assign_task' => __('Assign tasks to users', 'ma-deal-room'),
            ],

            // ==================== DOCUMENT CAPABILITIES ====================
            'document' => [
                self::CAP_PREFIX . 'view_documents' => __('View all documents', 'ma-deal-room'),
                self::CAP_PREFIX . 'view_own_documents' => __('View own documents', 'ma-deal-room'),
                self::CAP_PREFIX . 'upload_documents' => __('Upload documents', 'ma-deal-room'),
                self::CAP_PREFIX . 'download_documents' => __('Download documents', 'ma-deal-room'),
                self::CAP_PREFIX . 'delete_documents' => __('Delete documents', 'ma-deal-room'),
                self::CAP_PREFIX . 'manage_document_permissions' => __('Manage document sharing', 'ma-deal-room'),
            ],

            // ==================== TEMPLATE CAPABILITIES ====================
            'template' => [
                self::CAP_PREFIX . 'view_templates' => __('View templates', 'ma-deal-room'),
                self::CAP_PREFIX . 'create_template' => __('Create templates', 'ma-deal-room'),
                self::CAP_PREFIX . 'edit_template' => __('Edit templates', 'ma-deal-room'),
                self::CAP_PREFIX . 'delete_template' => __('Delete templates', 'ma-deal-room'),
                self::CAP_PREFIX . 'import_template' => __('Import templates', 'ma-deal-room'),
                self::CAP_PREFIX . 'export_template' => __('Export templates', 'ma-deal-room'),
            ],

            // ==================== PARTY CAPABILITIES ====================
            'party' => [
                self::CAP_PREFIX . 'view_parties' => __('View all parties', 'ma-deal-room'),
                self::CAP_PREFIX . 'create_party' => __('Add parties to transactions', 'ma-deal-room'),
                self::CAP_PREFIX . 'edit_party' => __('Edit party information', 'ma-deal-room'),
                self::CAP_PREFIX . 'delete_party' => __('Remove parties from transactions', 'ma-deal-room'),
                self::CAP_PREFIX . 'invite_party' => __('Invite parties to create accounts', 'ma-deal-room'),
            ],

            // ==================== USER MANAGEMENT CAPABILITIES ====================
            'user_management' => [
                self::CAP_PREFIX . 'manage_users' => __('Manage all users', 'ma-deal-room'),
                self::CAP_PREFIX . 'manage_clients' => __('Manage client users (buyers/sellers)', 'ma-deal-room'),
                self::CAP_PREFIX . 'invite_users' => __('Send user invitations', 'ma-deal-room'),
                self::CAP_PREFIX . 'assign_roles' => __('Assign user roles', 'ma-deal-room'),
                self::CAP_PREFIX . 'view_user_activity' => __('View user activity logs', 'ma-deal-room'),
            ],

            // ==================== REPORTING CAPABILITIES ====================
            'reporting' => [
                self::CAP_PREFIX . 'view_reports' => __('View basic reports', 'ma-deal-room'),
                self::CAP_PREFIX . 'view_advanced_reports' => __('View advanced analytics', 'ma-deal-room'),
                self::CAP_PREFIX . 'export_reports' => __('Export report data', 'ma-deal-room'),
            ],

            // ==================== SETTINGS CAPABILITIES ====================
            'settings' => [
                self::CAP_PREFIX . 'manage_settings' => __('Manage plugin settings', 'ma-deal-room'),
                self::CAP_PREFIX . 'manage_account_settings' => __('Manage account settings', 'ma-deal-room'),
                self::CAP_PREFIX . 'manage_integrations' => __('Manage third-party integrations', 'ma-deal-room'),
            ],

            // ==================== NOTIFICATION CAPABILITIES ====================
            'notification' => [
                self::CAP_PREFIX . 'view_notifications' => __('View notifications', 'ma-deal-room'),
                self::CAP_PREFIX . 'send_notifications' => __('Send notifications to users', 'ma-deal-room'),
            ],
        ];
    }

    /**
     * Get all Deal Room role types (used in database)
     *
     * @return array Array of role types with descriptions
     */
    public static function get_role_types(): array {
        return [
            'broker' => __('Broker/Agency Admin', 'ma-deal-room'),
            'agent' => __('Real Estate Agent', 'ma-deal-room'),
            'buyer' => __('Buyer', 'ma-deal-room'),
            'seller' => __('Seller', 'ma-deal-room'),
            'buyer_attorney' => __('Buyer\'s Attorney', 'ma-deal-room'),
            'seller_attorney' => __('Seller\'s Attorney', 'ma-deal-room'),
            'lender' => __('Mortgage Lender', 'ma-deal-room'),
            'inspector' => __('Home Inspector', 'ma-deal-room'),
            'appraiser' => __('Appraiser', 'ma-deal-room'),
            'vendor' => __('General Vendor', 'ma-deal-room'),
            'title_company' => __('Title Company', 'ma-deal-room'),
            'escrow' => __('Escrow Officer', 'ma-deal-room'),
            'hoa_manager' => __('HOA Manager', 'ma-deal-room'),
            'septic_inspector' => __('Septic Inspector', 'ma-deal-room'),
            'fire_dept' => __('Fire Department', 'ma-deal-room'),
        ];
    }

    /**
     * Register custom capabilities
     */
    private function register_capabilities(): void {
        // Capabilities are registered as part of roles
        // This method can be used for capability-only registration if needed
    }

    /**
     * Register custom WordPress roles
     */
    private function register_roles(): void {

        // ==================== BROKER ROLE ====================
        // Full admin access to agency/brokerage account
        add_role(
            'ma_broker',
            __('Deal Room Broker', 'ma-deal-room'),
            $this->get_broker_capabilities()
        );

        // ==================== AGENT ROLE ====================
        // Manages transactions for clients
        add_role(
            'ma_agent',
            __('Deal Room Agent', 'ma-deal-room'),
            $this->get_agent_capabilities()
        );

        // ==================== BUYER ROLE ====================
        // Client role - view own transaction
        add_role(
            'ma_buyer',
            __('Deal Room Buyer', 'ma-deal-room'),
            $this->get_buyer_capabilities()
        );

        // ==================== SELLER ROLE ====================
        // Client role - view own transaction
        add_role(
            'ma_seller',
            __('Deal Room Seller', 'ma-deal-room'),
            $this->get_seller_capabilities()
        );

        // ==================== ATTORNEY ROLE ====================
        // Legal professional - view client transactions
        add_role(
            'ma_attorney',
            __('Deal Room Attorney', 'ma-deal-room'),
            $this->get_attorney_capabilities()
        );

        // ==================== LENDER ROLE ====================
        // Mortgage lender - limited access to financing tasks
        add_role(
            'ma_lender',
            __('Deal Room Lender', 'ma-deal-room'),
            $this->get_lender_capabilities()
        );

        // ==================== INSPECTOR ROLE ====================
        // Home inspector - access to inspection tasks
        add_role(
            'ma_inspector',
            __('Deal Room Inspector', 'ma-deal-room'),
            $this->get_inspector_capabilities()
        );

        // ==================== VENDOR ROLE ====================
        // General vendor - task-specific access
        add_role(
            'ma_vendor',
            __('Deal Room Vendor', 'ma-deal-room'),
            $this->get_vendor_capabilities()
        );

        // ==================== TITLE COMPANY ROLE ====================
        // Title company - title and closing tasks
        add_role(
            'ma_title_company',
            __('Deal Room Title Company', 'ma-deal-room'),
            $this->get_title_company_capabilities()
        );

        // ==================== ESCROW ROLE ====================
        // Escrow officer - escrow and closing tasks
        add_role(
            'ma_escrow',
            __('Deal Room Escrow', 'ma-deal-room'),
            $this->get_escrow_capabilities()
        );
    }

    /**
     * Assign capabilities to existing WordPress roles
     */
    private function assign_capabilities_to_existing_roles(): void {
        // Administrator gets all capabilities
        $admin = get_role('administrator');
        if ($admin) {
            $all_caps = $this->get_broker_capabilities();
            foreach ($all_caps as $cap => $granted) {
                if ($granted) {
                    $admin->add_cap($cap);
                }
            }
        }

        // Editor gets agent-level capabilities
        $editor = get_role('editor');
        if ($editor) {
            $agent_caps = $this->get_agent_capabilities();
            foreach ($agent_caps as $cap => $granted) {
                if ($granted) {
                    $editor->add_cap($cap);
                }
            }
        }
    }

    /**
     * Get broker capabilities (full access)
     */
    private function get_broker_capabilities(): array {
        return array_merge(
            ['read' => true], // Basic WordPress capability
            $this->flatten_capabilities(self::get_capabilities())
        );
    }

    /**
     * Get agent capabilities
     */
    private function get_agent_capabilities(): array {
        return [
            // WordPress core capabilities for BaseController compatibility
            'read' => true,
            'edit_posts' => true,              // Required for GET requests
            'edit_others_posts' => true,       // Required for POST/PUT/DELETE requests

            // Transactions
            self::CAP_PREFIX . 'view_transactions' => true,
            self::CAP_PREFIX . 'create_transaction' => true,
            self::CAP_PREFIX . 'edit_transaction' => true,
            self::CAP_PREFIX . 'export_transactions' => true,

            // Tasks
            self::CAP_PREFIX . 'view_tasks' => true,
            self::CAP_PREFIX . 'create_task' => true,
            self::CAP_PREFIX . 'edit_task' => true,
            self::CAP_PREFIX . 'complete_task' => true,
            self::CAP_PREFIX . 'skip_task' => true,
            self::CAP_PREFIX . 'assign_task' => true,

            // Documents
            self::CAP_PREFIX . 'view_documents' => true,
            self::CAP_PREFIX . 'upload_documents' => true,
            self::CAP_PREFIX . 'download_documents' => true,
            self::CAP_PREFIX . 'delete_documents' => true,
            self::CAP_PREFIX . 'manage_document_permissions' => true,

            // Templates
            self::CAP_PREFIX . 'view_templates' => true,

            // Parties
            self::CAP_PREFIX . 'view_parties' => true,
            self::CAP_PREFIX . 'create_party' => true,
            self::CAP_PREFIX . 'edit_party' => true,
            self::CAP_PREFIX . 'delete_party' => true,
            self::CAP_PREFIX . 'invite_party' => true,

            // User Management
            self::CAP_PREFIX . 'manage_clients' => true,
            self::CAP_PREFIX . 'invite_users' => true,

            // Reporting
            self::CAP_PREFIX . 'view_reports' => true,
            self::CAP_PREFIX . 'export_reports' => true,

            // Notifications
            self::CAP_PREFIX . 'view_notifications' => true,
            self::CAP_PREFIX . 'send_notifications' => true,
        ];
    }

    /**
     * Get buyer capabilities (client - limited access)
     */
    private function get_buyer_capabilities(): array {
        return [
            'read' => true,

            // Transactions (own only)
            self::CAP_PREFIX . 'view_own_transactions' => true,

            // Tasks (assigned only)
            self::CAP_PREFIX . 'view_own_tasks' => true,
            self::CAP_PREFIX . 'complete_task' => true,

            // Documents
            self::CAP_PREFIX . 'view_own_documents' => true,
            self::CAP_PREFIX . 'upload_documents' => true,
            self::CAP_PREFIX . 'download_documents' => true,

            // Notifications
            self::CAP_PREFIX . 'view_notifications' => true,
        ];
    }

    /**
     * Get seller capabilities (same as buyer)
     */
    private function get_seller_capabilities(): array {
        return $this->get_buyer_capabilities();
    }

    /**
     * Get attorney capabilities
     */
    private function get_attorney_capabilities(): array {
        return [
            'read' => true,

            // Transactions (client transactions)
            self::CAP_PREFIX . 'view_transactions' => true,
            self::CAP_PREFIX . 'edit_own_transaction' => true,

            // Tasks
            self::CAP_PREFIX . 'view_tasks' => true,
            self::CAP_PREFIX . 'edit_own_task' => true,
            self::CAP_PREFIX . 'complete_task' => true,

            // Documents
            self::CAP_PREFIX . 'view_documents' => true,
            self::CAP_PREFIX . 'upload_documents' => true,
            self::CAP_PREFIX . 'download_documents' => true,

            // Parties
            self::CAP_PREFIX . 'view_parties' => true,

            // Notifications
            self::CAP_PREFIX . 'view_notifications' => true,
        ];
    }

    /**
     * Get lender capabilities
     */
    private function get_lender_capabilities(): array {
        return [
            'read' => true,

            // Transactions (related transactions only)
            self::CAP_PREFIX . 'view_own_transactions' => true,

            // Tasks (loan-related tasks)
            self::CAP_PREFIX . 'view_own_tasks' => true,
            self::CAP_PREFIX . 'edit_own_task' => true,
            self::CAP_PREFIX . 'complete_task' => true,

            // Documents
            self::CAP_PREFIX . 'view_own_documents' => true,
            self::CAP_PREFIX . 'upload_documents' => true,
            self::CAP_PREFIX . 'download_documents' => true,

            // Notifications
            self::CAP_PREFIX . 'view_notifications' => true,
        ];
    }

    /**
     * Get inspector capabilities
     */
    private function get_inspector_capabilities(): array {
        return [
            'read' => true,

            // Transactions (assigned transactions)
            self::CAP_PREFIX . 'view_own_transactions' => true,

            // Tasks (inspection tasks)
            self::CAP_PREFIX . 'view_own_tasks' => true,
            self::CAP_PREFIX . 'complete_task' => true,

            // Documents
            self::CAP_PREFIX . 'view_own_documents' => true,
            self::CAP_PREFIX . 'upload_documents' => true,
            self::CAP_PREFIX . 'download_documents' => true,

            // Notifications
            self::CAP_PREFIX . 'view_notifications' => true,
        ];
    }

    /**
     * Get vendor capabilities
     */
    private function get_vendor_capabilities(): array {
        return [
            'read' => true,

            // Tasks (assigned tasks)
            self::CAP_PREFIX . 'view_own_tasks' => true,
            self::CAP_PREFIX . 'complete_task' => true,

            // Documents
            self::CAP_PREFIX . 'view_own_documents' => true,
            self::CAP_PREFIX . 'upload_documents' => true,
            self::CAP_PREFIX . 'download_documents' => true,

            // Notifications
            self::CAP_PREFIX . 'view_notifications' => true,
        ];
    }

    /**
     * Get title company capabilities
     */
    private function get_title_company_capabilities(): array {
        return [
            'read' => true,

            // Transactions
            self::CAP_PREFIX . 'view_own_transactions' => true,

            // Tasks
            self::CAP_PREFIX . 'view_own_tasks' => true,
            self::CAP_PREFIX . 'complete_task' => true,

            // Documents
            self::CAP_PREFIX . 'view_documents' => true,
            self::CAP_PREFIX . 'upload_documents' => true,
            self::CAP_PREFIX . 'download_documents' => true,

            // Notifications
            self::CAP_PREFIX . 'view_notifications' => true,
        ];
    }

    /**
     * Get escrow capabilities
     */
    private function get_escrow_capabilities(): array {
        return [
            'read' => true,

            // Transactions
            self::CAP_PREFIX . 'view_own_transactions' => true,

            // Tasks
            self::CAP_PREFIX . 'view_own_tasks' => true,
            self::CAP_PREFIX . 'complete_task' => true,

            // Documents
            self::CAP_PREFIX . 'view_documents' => true,
            self::CAP_PREFIX . 'upload_documents' => true,
            self::CAP_PREFIX . 'download_documents' => true,

            // Notifications
            self::CAP_PREFIX . 'view_notifications' => true,
        ];
    }

    /**
     * Flatten nested capabilities array
     */
    private function flatten_capabilities(array $caps): array {
        $result = [];
        foreach ($caps as $category) {
            foreach ($category as $cap => $label) {
                $result[$cap] = true;
            }
        }
        return $result;
    }

    /**
     * Check if user has capability (works for both WP and custom users)
     *
     * @param mixed $user User object or array with user_id and user_type
     * @param string $capability Capability to check
     * @return bool
     */
    public static function user_can($user, string $capability): bool {
        // WordPress user
        if (is_object($user) && isset($user->ID)) {
            return user_can($user, $capability);
        }

        // Custom user - check via user_roles table
        if (is_array($user) && isset($user['user_id'], $user['user_type'])) {
            global $wpdb;

            // Get user's roles from database
            $table = $wpdb->prefix . 'ma_deal_user_roles';
            $roles = $wpdb->get_results($wpdb->prepare(
                "SELECT role_type, permissions FROM $table
                WHERE user_id = %d AND user_type = %s
                AND (revoked_at IS NULL OR revoked_at > NOW())
                AND (expires_at IS NULL OR expires_at > NOW())",
                $user['user_id'],
                $user['user_type']
            ));

            foreach ($roles as $role) {
                // Get role capabilities
                $role_caps = self::get_role_capabilities($role->role_type);

                // Check role permissions override
                if ($role->permissions) {
                    $custom_perms = json_decode($role->permissions, true);
                    if (isset($custom_perms[$capability])) {
                        return (bool) $custom_perms[$capability];
                    }
                }

                // Check role default capabilities
                if (isset($role_caps[$capability]) && $role_caps[$capability]) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get capabilities for a role type
     *
     * @param string $role_type Role type (buyer, seller, agent, etc.)
     * @return array
     */
    private static function get_role_capabilities(string $role_type): array {
        $instance = new self();

        $method_map = [
            'broker' => 'get_broker_capabilities',
            'agent' => 'get_agent_capabilities',
            'buyer' => 'get_buyer_capabilities',
            'seller' => 'get_seller_capabilities',
            'buyer_attorney' => 'get_attorney_capabilities',
            'seller_attorney' => 'get_attorney_capabilities',
            'lender' => 'get_lender_capabilities',
            'inspector' => 'get_inspector_capabilities',
            'appraiser' => 'get_inspector_capabilities', // Same as inspector
            'vendor' => 'get_vendor_capabilities',
            'title_company' => 'get_title_company_capabilities',
            'escrow' => 'get_escrow_capabilities',
        ];

        if (isset($method_map[$role_type]) && method_exists($instance, $method_map[$role_type])) {
            return $instance->{$method_map[$role_type]}();
        }

        return ['read' => true]; // Minimal capabilities
    }

    /**
     * Remove all custom roles and capabilities (for uninstall)
     */
    public static function remove_all_roles(): void {
        $roles = [
            'ma_broker',
            'ma_agent',
            'ma_buyer',
            'ma_seller',
            'ma_attorney',
            'ma_lender',
            'ma_inspector',
            'ma_vendor',
            'ma_title_company',
            'ma_escrow',
        ];

        foreach ($roles as $role) {
            remove_role($role);
        }

        // Remove capabilities from admin and editor
        $admin = get_role('administrator');
        $editor = get_role('editor');

        $all_caps = (new self())->flatten_capabilities(self::get_capabilities());

        foreach ($all_caps as $cap => $granted) {
            if ($admin) {
                $admin->remove_cap($cap);
            }
            if ($editor) {
                $editor->remove_cap($cap);
            }
        }
    }
}
