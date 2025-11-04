import { useAuthStore } from '../store/useAuthStore';
import { twoFactorApi } from '../api/services/auth';

/**
 * Main authentication hook
 * Provides access to auth state and actions
 */
export const useAuth = () => {
  const {
    user,
    userId,
    isAuthenticated,
    isLoading,
    error,
    requires2FA,
    pending2FAUserId,
    pending2FAUserType,
    initialize,
    login,
    register,
    logout,
    refreshUser,
    verifyEmail,
    resendVerification,
    requestPasswordReset,
    resetPassword,
    changePassword,
    setError,
    clearError,
    clear2FARequired,
  } = useAuthStore();

  /**
   * Complete 2FA verification after login
   */
  const verify2FA = async (code: string) => {
    if (!pending2FAUserId || !pending2FAUserType) {
      throw new Error('No pending 2FA verification');
    }

    const verified = await twoFactorApi.verifyCode(
      pending2FAUserId,
      pending2FAUserType,
      code
    );

    if (verified) {
      // 2FA successful, refresh user data
      clear2FARequired();
      await refreshUser();
    } else {
      throw new Error('Invalid verification code');
    }
  };

  /**
   * Verify backup code for 2FA
   */
  const verifyBackupCode = async (code: string) => {
    if (!pending2FAUserId || !pending2FAUserType) {
      throw new Error('No pending 2FA verification');
    }

    const verified = await twoFactorApi.verifyBackupCode(
      pending2FAUserId,
      pending2FAUserType,
      code
    );

    if (verified) {
      // Backup code successful, refresh user data
      clear2FARequired();
      await refreshUser();
    } else {
      throw new Error('Invalid backup code');
    }
  };

  return {
    // State
    user,
    userId,
    isAuthenticated,
    isLoading,
    error,
    requires2FA,

    // Actions
    initialize,
    login,
    register,
    logout,
    refreshUser,
    verifyEmail,
    resendVerification,
    requestPasswordReset,
    resetPassword,
    changePassword,
    verify2FA,
    verifyBackupCode,
    setError,
    clearError,
  };
};
