import { apiClient, tokenManager } from '../client';

export interface LoginRequest {
  email: string;
  password: string;
  remember?: boolean;
  device_name?: string;
  device_type?: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  first_name?: string;
  last_name?: string;
  phone?: string;
}

export interface ChangePasswordRequest {
  current_password: string;
  new_password: string;
}

export interface ResetPasswordRequest {
  token: string;
  new_password: string;
}

export interface User {
  id: number;
  email: string;
  first_name?: string;
  last_name?: string;
  phone?: string;
  email_verified: boolean;
  two_factor_enabled: boolean;
  user_type: 'custom' | 'wordpress';
  created_at?: string;
  display_name?: string;
  roles?: string[];
}

export interface LoginResponse {
  access_token: string;
  refresh_token: string;
  expires_in: number;
  user: User;
}

export interface AuthResponse {
  success: boolean;
  message?: string;
  data: any;
}

/**
 * Authentication API service
 */
export const authApi = {
  /**
   * Register a new user
   */
  async register(data: RegisterRequest): Promise<User> {
    try {
      const response = await apiClient.post<AuthResponse>('/auth/register', data);
      console.log('Registration response:', response);

      // Try multiple response formats for compatibility
      let user: User | undefined;

      // Format 1: { success: true, data: { user: {...}, message: "..." } }
      if (response.data?.data?.user) {
        user = response.data.data.user;
      }
      // Format 2: { data: { user: {...} } } (if BaseController wrapping is different)
      else if (response.data?.data && typeof response.data.data === 'object' && 'user' in response.data.data) {
        user = (response.data.data as any).user;
      }
      // Format 3: Direct user response
      else if ((response.data as any)?.id && (response.data as any)?.email) {
        user = response.data as any as User;
      }

      if (!user) {
        console.error('Could not extract user from response. Response structure:', {
          hasData: !!response.data,
          dataKeys: Object.keys(response.data || {}),
          nestedDataKeys: Object.keys(response.data?.data || {}),
          fullResponse: response.data,
        });
        throw new Error('Invalid registration response format - could not extract user data');
      }

      return user;
    } catch (error: any) {
      console.error('Registration error:', error);
      // Provide a more helpful error message
      if (error.response?.data?.message) {
        throw new Error(error.response.data.message);
      }
      if (error.message) {
        throw error;
      }
      throw new Error('Registration failed: ' + JSON.stringify(error));
    }
  },

  /**
   * Login user
   */
  async login(data: LoginRequest): Promise<LoginResponse> {
    try {
      const response = await apiClient.post<AuthResponse>('/auth/login', data);
      if (!response.data?.data) {
        throw new Error('Invalid login response format');
      }
      const { access_token, refresh_token, expires_in, user } = response.data.data;

      if (!access_token || !refresh_token || !user) {
        throw new Error('Missing required authentication data in response');
      }

      // Store tokens
      tokenManager.setTokens(access_token, refresh_token);

      return { access_token, refresh_token, expires_in, user };
    } catch (error: any) {
      // Provide a more helpful error message
      if (error.response?.data?.message) {
        throw new Error(error.response.data.message);
      }
      throw error;
    }
  },

  /**
   * Logout user
   */
  async logout(): Promise<void> {
    const refreshToken = tokenManager.getRefreshToken();

    if (refreshToken) {
      try {
        await apiClient.post('/auth/logout', { refresh_token: refreshToken });
      } catch (error) {
        // Continue even if logout request fails
        console.error('Logout request failed:', error);
      }
    }

    // Clear tokens regardless of API call success
    tokenManager.clearTokens();
  },

  /**
   * Refresh access token
   */
  async refreshToken(): Promise<string> {
    const refreshToken = tokenManager.getRefreshToken();

    if (!refreshToken) {
      throw new Error('No refresh token available');
    }

    const response = await apiClient.post<AuthResponse>('/auth/refresh', {
      refresh_token: refreshToken,
    });

    const { access_token } = response.data.data;

    // Update access token (keep same refresh token)
    tokenManager.setTokens(access_token, refreshToken);

    return access_token;
  },

  /**
   * Get current user info
   */
  async getCurrentUser(): Promise<User> {
    try {
      const response = await apiClient.get<AuthResponse>('/auth/me');
      if (!response.data?.data) {
        throw new Error('Invalid user data response');
      }
      return response.data.data;
    } catch (error: any) {
      // Provide a more helpful error message
      if (error.response?.data?.message) {
        throw new Error(error.response.data.message);
      }
      throw error;
    }
  },

  /**
   * Verify email with token
   */
  async verifyEmail(token: string): Promise<void> {
    await apiClient.post<AuthResponse>('/auth/verify-email', { token });
  },

  /**
   * Resend verification email
   */
  async resendVerification(): Promise<void> {
    await apiClient.post<AuthResponse>('/auth/resend-verification');
  },

  /**
   * Request password reset
   */
  async requestPasswordReset(email: string): Promise<void> {
    await apiClient.post<AuthResponse>('/auth/request-password-reset', { email });
  },

  /**
   * Reset password with token
   */
  async resetPassword(data: ResetPasswordRequest): Promise<void> {
    await apiClient.post<AuthResponse>('/auth/reset-password', data);
  },

  /**
   * Change password (logged in user)
   */
  async changePassword(data: ChangePasswordRequest): Promise<void> {
    await apiClient.post<AuthResponse>('/auth/change-password', data);
  },

  /**
   * Verify JWT token
   */
  async verifyToken(token: string): Promise<boolean> {
    try {
      const response = await apiClient.post<AuthResponse>('/auth/verify-token', { token });
      return response.data.data.valid;
    } catch (error) {
      return false;
    }
  },
};

/**
 * 2FA API service
 */
export const twoFactorApi = {
  /**
   * Enable 2FA - Step 1: Get QR code
   */
  async enable2FA(): Promise<{
    secret: string;
    qr_code: string;
    backup_codes: string[];
  }> {
    const response = await apiClient.post<AuthResponse>('/2fa/enable');
    return response.data.data;
  },

  /**
   * Verify 2FA setup - Step 2: Verify code and activate
   */
  async verifySetup(code: string): Promise<void> {
    await apiClient.post<AuthResponse>('/2fa/verify-setup', { code });
  },

  /**
   * Disable 2FA
   */
  async disable2FA(password: string): Promise<void> {
    await apiClient.post<AuthResponse>('/2fa/disable', { password });
  },

  /**
   * Verify 2FA code (for login)
   */
  async verifyCode(userId: number, userType: string, code: string): Promise<boolean> {
    try {
      const response = await apiClient.post<AuthResponse>('/2fa/verify', {
        user_id: userId,
        user_type: userType,
        code,
      });
      return response.data.data.verified;
    } catch (error) {
      return false;
    }
  },

  /**
   * Verify backup code
   */
  async verifyBackupCode(userId: number, userType: string, code: string): Promise<boolean> {
    try {
      const response = await apiClient.post<AuthResponse>('/2fa/verify-backup', {
        user_id: userId,
        user_type: userType,
        code,
      });
      return response.data.data.verified;
    } catch (error) {
      return false;
    }
  },

  /**
   * Regenerate backup codes
   */
  async regenerateBackupCodes(password: string): Promise<string[]> {
    const response = await apiClient.post<AuthResponse>('/2fa/backup-codes/regenerate', {
      password,
    });
    return response.data.data.backup_codes;
  },

  /**
   * Get 2FA status
   */
  async getStatus(): Promise<{ enabled: boolean; method: string | null }> {
    const response = await apiClient.get<AuthResponse>('/2fa/status');
    return response.data.data;
  },
};
