import { useAuthStore } from '../store/useAuthStore';
/**
 * Hook to access current user data
 */
export const useCurrentUser = () => {
    const { user, userId, isAuthenticated } = useAuthStore();
    const isCustomUser = user?.user_type === 'custom';
    const isWordPressUser = user?.user_type === 'wordpress';
    const isEmailVerified = user?.email_verified ?? false;
    const has2FAEnabled = user?.two_factor_enabled ?? false;
    const fullName = user
        ? user.user_type === 'custom'
            ? `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.email
            : user.display_name || user.email
        : '';
    return {
        user,
        userId,
        isAuthenticated,
        isCustomUser,
        isWordPressUser,
        isEmailVerified,
        has2FAEnabled,
        fullName,
    };
};
