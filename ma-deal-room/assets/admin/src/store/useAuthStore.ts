import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { getCurrentUser as getWpCurrentUser } from '@/api/client';
import {
  authApi,
  type User,
  type LoginRequest,
  type RegisterRequest,
  type ChangePasswordRequest,
  type ResetPasswordRequest,
} from '@/api/services/auth';
import { tokenManager } from '@/api/client';

interface AuthState {
  // State
  user: User | null;
  userId: number;
  isAuthenticated: boolean;
  isLoading: boolean;
  error: string | null;
  requires2FA: boolean;
  pending2FAUserId: number | null;
  pending2FAUserType: string | null;

  // Actions
  initialize: () => Promise<void>;
  login: (data: LoginRequest) => Promise<void>;
  register: (data: RegisterRequest) => Promise<User>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  verifyEmail: (token: string) => Promise<void>;
  resendVerification: () => Promise<void>;
  requestPasswordReset: (email: string) => Promise<void>;
  resetPassword: (data: ResetPasswordRequest) => Promise<void>;
  changePassword: (data: ChangePasswordRequest) => Promise<void>;
  setError: (error: string | null) => void;
  clearError: () => void;
  set2FARequired: (userId: number, userType: string) => void;
  clear2FARequired: () => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      // Initial state
      user: null,
      userId: 0,
      isAuthenticated: false,
      isLoading: false,
      error: null,
      requires2FA: false,
      pending2FAUserId: null,
      pending2FAUserType: null,

      /**
       * Initialize auth state
       * Checks for stored tokens or WordPress session
       */
      initialize: async () => {
        set({ isLoading: true, error: null });

        try {
          // Check if we have JWT tokens (custom user)
          if (tokenManager.hasTokens()) {
            try {
              const user = await authApi.getCurrentUser();
              set({
                user,
                userId: user.id,
                isAuthenticated: true,
                isLoading: false,
              });
              return;
            } catch (error) {
              // Token invalid, clear it
              tokenManager.clearTokens();
            }
          }

          // Check for WordPress session
          const wpUserId = getWpCurrentUser();
          if (wpUserId > 0) {
            // WordPress user is logged in
            // Try to fetch user details
            try {
              const user = await authApi.getCurrentUser();
              set({
                user,
                userId: wpUserId,
                isAuthenticated: true,
                isLoading: false,
              });
            } catch (error) {
              // Fallback: just set WordPress user as authenticated
              set({
                userId: wpUserId,
                isAuthenticated: true,
                isLoading: false,
              });
            }
            return;
          }

          // No authentication found
          set({
            user: null,
            userId: 0,
            isAuthenticated: false,
            isLoading: false,
          });
        } catch (error: any) {
          set({
            error: error.message || 'Failed to initialize authentication',
            isLoading: false,
          });
        }
      },

      /**
       * Login user
       */
      login: async (data: LoginRequest) => {
        set({ isLoading: true, error: null });

        try {
          const response = await authApi.login(data);

          // Check if 2FA is required
          if (response.user.two_factor_enabled) {
            // Don't set authenticated yet, wait for 2FA
            set({
              requires2FA: true,
              pending2FAUserId: response.user.id,
              pending2FAUserType: response.user.user_type,
              isLoading: false,
            });
            return;
          }

          // Login successful, no 2FA required
          set({
            user: response.user,
            userId: response.user.id,
            isAuthenticated: true,
            isLoading: false,
            error: null,
          });
        } catch (error: any) {
          set({
            error: error.message || 'Login failed',
            isLoading: false,
          });
          throw error;
        }
      },

      /**
       * Register new user
       */
      register: async (data: RegisterRequest) => {
        set({ isLoading: true, error: null });

        try {
          const user = await authApi.register(data);
          set({
            isLoading: false,
            error: null,
          });
          return user;
        } catch (error: any) {
          set({
            error: error.message || 'Registration failed',
            isLoading: false,
          });
          throw error;
        }
      },

      /**
       * Logout user
       */
      logout: async () => {
        set({ isLoading: true, error: null });

        try {
          await authApi.logout();
        } catch (error) {
          // Continue with logout even if API call fails
          console.error('Logout API call failed:', error);
        }

        // Clear tokens from tokenManager
        tokenManager.clearTokens();

        // Clear state
        set({
          user: null,
          userId: 0,
          isAuthenticated: false,
          isLoading: false,
          error: null,
          requires2FA: false,
          pending2FAUserId: null,
          pending2FAUserType: null,
        });

        // Clear persisted storage
        localStorage.removeItem('ma-deal-auth');

        // Redirect to login page (use hash routing for SPA)
        window.location.href = '/#/auth/login';
      },

      /**
       * Refresh current user data
       */
      refreshUser: async () => {
        if (!get().isAuthenticated) {
          return;
        }

        try {
          const user = await authApi.getCurrentUser();
          set({ user, userId: user.id });
        } catch (error: any) {
          // If refresh fails with 401, logout
          if (error.status === 401) {
            get().logout();
          }
        }
      },

      /**
       * Verify email with token
       */
      verifyEmail: async (token: string) => {
        set({ isLoading: true, error: null });

        try {
          await authApi.verifyEmail(token);

          // Refresh user to get updated email_verified status
          await get().refreshUser();

          set({ isLoading: false });
        } catch (error: any) {
          set({
            error: error.message || 'Email verification failed',
            isLoading: false,
          });
          throw error;
        }
      },

      /**
       * Resend verification email
       */
      resendVerification: async () => {
        set({ isLoading: true, error: null });

        try {
          await authApi.resendVerification();
          set({ isLoading: false });
        } catch (error: any) {
          set({
            error: error.message || 'Failed to resend verification email',
            isLoading: false,
          });
          throw error;
        }
      },

      /**
       * Request password reset
       */
      requestPasswordReset: async (email: string) => {
        set({ isLoading: true, error: null });

        try {
          await authApi.requestPasswordReset(email);
          set({ isLoading: false });
        } catch (error: any) {
          set({
            error: error.message || 'Failed to request password reset',
            isLoading: false,
          });
          throw error;
        }
      },

      /**
       * Reset password with token
       */
      resetPassword: async (data: ResetPasswordRequest) => {
        set({ isLoading: true, error: null });

        try {
          await authApi.resetPassword(data);
          set({ isLoading: false });
        } catch (error: any) {
          set({
            error: error.message || 'Password reset failed',
            isLoading: false,
          });
          throw error;
        }
      },

      /**
       * Change password (logged in user)
       */
      changePassword: async (data: ChangePasswordRequest) => {
        set({ isLoading: true, error: null });

        try {
          await authApi.changePassword(data);
          set({ isLoading: false });
        } catch (error: any) {
          set({
            error: error.message || 'Failed to change password',
            isLoading: false,
          });
          throw error;
        }
      },

      /**
       * Set error message
       */
      setError: (error: string | null) => {
        set({ error });
      },

      /**
       * Clear error message
       */
      clearError: () => {
        set({ error: null });
      },

      /**
       * Set 2FA required state
       */
      set2FARequired: (userId: number, userType: string) => {
        set({
          requires2FA: true,
          pending2FAUserId: userId,
          pending2FAUserType: userType,
        });
      },

      /**
       * Clear 2FA required state
       */
      clear2FARequired: () => {
        set({
          requires2FA: false,
          pending2FAUserId: null,
          pending2FAUserType: null,
        });
      },
    }),
    {
      name: 'ma-deal-auth',
      partialize: (state) => ({
        // Only persist user data, not loading/error states
        user: state.user,
        userId: state.userId,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
);

// Listen for session expiration event
window.addEventListener('auth:session-expired', () => {
  useAuthStore.getState().logout();
});
