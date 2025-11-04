import { useCurrentUser } from './useCurrentUser';

// MA Deal Room custom capabilities
export const CAPABILITIES = {
  // Transaction management
  VIEW_TRANSACTIONS: 'ma_deal_view_transactions',
  CREATE_TRANSACTION: 'ma_deal_create_transaction',
  EDIT_TRANSACTION: 'ma_deal_edit_transaction',
  DELETE_TRANSACTION: 'ma_deal_delete_transaction',

  // Document management
  VIEW_DOCUMENTS: 'ma_deal_view_documents',
  UPLOAD_DOCUMENTS: 'ma_deal_upload_documents',
  DELETE_DOCUMENTS: 'ma_deal_delete_documents',

  // Task management
  VIEW_TASKS: 'ma_deal_view_tasks',
  EDIT_TASKS: 'ma_deal_edit_tasks',
  COMPLETE_TASKS: 'ma_deal_complete_tasks',

  // User management
  VIEW_USERS: 'ma_deal_view_users',
  MANAGE_USERS: 'ma_deal_manage_users',
  INVITE_USERS: 'ma_deal_invite_users',

  // Template management
  VIEW_TEMPLATES: 'ma_deal_view_templates',
  EDIT_TEMPLATES: 'ma_deal_edit_templates',
  CREATE_TEMPLATES: 'ma_deal_create_templates',

  // Notifications
  VIEW_NOTIFICATIONS: 'ma_deal_view_notifications',
  MANAGE_NOTIFICATIONS: 'ma_deal_manage_notifications',

  // System
  MANAGE_SETTINGS: 'ma_deal_manage_settings',
  VIEW_REPORTS: 'ma_deal_view_reports',
} as const;

/**
 * Hook to check user permissions/capabilities
 *
 * Note: This is a client-side check for UI purposes only.
 * Always verify permissions on the backend as well.
 */
export const usePermissions = () => {
  const { user, isAuthenticated } = useCurrentUser();

  /**
   * Check if user has a specific capability
   * For WordPress users, checks roles
   * For custom users, would need to fetch from API (simplified here)
   */
  const can = (capability: string): boolean => {
    if (!isAuthenticated || !user) {
      return false;
    }

    // WordPress users - check roles
    if (user.user_type === 'wordpress' && user.roles) {
      // Administrators can do everything
      if (user.roles.includes('administrator')) {
        return true;
      }

      // For other roles, we'd need a capability map
      // This is a simplified version - in production, fetch from API
      const roleCapabilities: Record<string, string[]> = {
        ma_broker: [
          CAPABILITIES.VIEW_TRANSACTIONS,
          CAPABILITIES.CREATE_TRANSACTION,
          CAPABILITIES.EDIT_TRANSACTION,
          CAPABILITIES.VIEW_DOCUMENTS,
          CAPABILITIES.UPLOAD_DOCUMENTS,
          CAPABILITIES.VIEW_TASKS,
          CAPABILITIES.EDIT_TASKS,
          CAPABILITIES.VIEW_USERS,
          CAPABILITIES.INVITE_USERS,
          CAPABILITIES.VIEW_TEMPLATES,
          CAPABILITIES.VIEW_NOTIFICATIONS,
          CAPABILITIES.VIEW_REPORTS,
        ],
        ma_agent: [
          CAPABILITIES.VIEW_TRANSACTIONS,
          CAPABILITIES.CREATE_TRANSACTION,
          CAPABILITIES.EDIT_TRANSACTION,
          CAPABILITIES.VIEW_DOCUMENTS,
          CAPABILITIES.UPLOAD_DOCUMENTS,
          CAPABILITIES.VIEW_TASKS,
          CAPABILITIES.EDIT_TASKS,
          CAPABILITIES.COMPLETE_TASKS,
          CAPABILITIES.VIEW_USERS,
          CAPABILITIES.INVITE_USERS,
          CAPABILITIES.VIEW_TEMPLATES,
          CAPABILITIES.VIEW_NOTIFICATIONS,
        ],
        ma_buyer: [
          CAPABILITIES.VIEW_TRANSACTIONS,
          CAPABILITIES.VIEW_DOCUMENTS,
          CAPABILITIES.UPLOAD_DOCUMENTS,
          CAPABILITIES.VIEW_TASKS,
          CAPABILITIES.VIEW_NOTIFICATIONS,
        ],
        ma_seller: [
          CAPABILITIES.VIEW_TRANSACTIONS,
          CAPABILITIES.VIEW_DOCUMENTS,
          CAPABILITIES.UPLOAD_DOCUMENTS,
          CAPABILITIES.VIEW_TASKS,
          CAPABILITIES.VIEW_NOTIFICATIONS,
        ],
        ma_attorney: [
          CAPABILITIES.VIEW_TRANSACTIONS,
          CAPABILITIES.VIEW_DOCUMENTS,
          CAPABILITIES.UPLOAD_DOCUMENTS,
          CAPABILITIES.VIEW_TASKS,
          CAPABILITIES.EDIT_TASKS,
          CAPABILITIES.COMPLETE_TASKS,
          CAPABILITIES.VIEW_NOTIFICATIONS,
        ],
        ma_lender: [
          CAPABILITIES.VIEW_TRANSACTIONS,
          CAPABILITIES.VIEW_DOCUMENTS,
          CAPABILITIES.UPLOAD_DOCUMENTS,
          CAPABILITIES.VIEW_TASKS,
          CAPABILITIES.VIEW_NOTIFICATIONS,
        ],
      };

      for (const role of user.roles) {
        if (roleCapabilities[role]?.includes(capability)) {
          return true;
        }
      }
    }

    // Custom users - would need to fetch from API
    // For now, return false
    return false;
  };

  /**
   * Check if user has ANY of the specified capabilities
   */
  const canAny = (capabilities: string[]): boolean => {
    return capabilities.some((cap) => can(cap));
  };

  /**
   * Check if user has ALL of the specified capabilities
   */
  const canAll = (capabilities: string[]): boolean => {
    return capabilities.every((cap) => can(cap));
  };

  /**
   * Check if user is an admin
   */
  const isAdmin = (): boolean => {
    if (!isAuthenticated || !user) {
      return false;
    }

    if (user.user_type === 'wordpress' && user.roles) {
      return user.roles.includes('administrator');
    }

    return false;
  };

  /**
   * Check if user has a specific role
   */
  const hasRole = (role: string): boolean => {
    if (!isAuthenticated || !user) {
      return false;
    }

    if (user.user_type === 'wordpress' && user.roles) {
      return user.roles.includes(role);
    }

    return false;
  };

  return {
    can,
    canAny,
    canAll,
    isAdmin,
    hasRole,
  };
};
