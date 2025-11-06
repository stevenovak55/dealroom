import { apiClient, tokenManager } from '../client';
/**
 * Authentication API service
 */
export const authApi = {
    /**
     * Register a new user
     */
    async register(data) {
        try {
            const response = await apiClient.post('/auth/register', data);
            console.log('Registration response:', response);
            // Try multiple response formats for compatibility
            let user;
            // Format 1: { success: true, data: { user: {...}, message: "..." } }
            if (response.data?.data?.user) {
                user = response.data.data.user;
            }
            // Format 2: { data: { user: {...} } } (if BaseController wrapping is different)
            else if (response.data?.data && typeof response.data.data === 'object' && 'user' in response.data.data) {
                user = response.data.data.user;
            }
            // Format 3: Direct user response
            else if (response.data?.id && response.data?.email) {
                user = response.data;
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
        }
        catch (error) {
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
    async login(data) {
        try {
            const response = await apiClient.post('/auth/login', data);
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
        }
        catch (error) {
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
    async logout() {
        const refreshToken = tokenManager.getRefreshToken();
        if (refreshToken) {
            try {
                await apiClient.post('/auth/logout', { refresh_token: refreshToken });
            }
            catch (error) {
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
    async refreshToken() {
        const refreshToken = tokenManager.getRefreshToken();
        if (!refreshToken) {
            throw new Error('No refresh token available');
        }
        const response = await apiClient.post('/auth/refresh', {
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
    async getCurrentUser() {
        try {
            const response = await apiClient.get('/auth/me');
            if (!response.data?.data) {
                throw new Error('Invalid user data response');
            }
            return response.data.data;
        }
        catch (error) {
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
    async verifyEmail(token) {
        await apiClient.post('/auth/verify-email', { token });
    },
    /**
     * Resend verification email
     */
    async resendVerification() {
        await apiClient.post('/auth/resend-verification');
    },
    /**
     * Request password reset
     */
    async requestPasswordReset(email) {
        await apiClient.post('/auth/request-password-reset', { email });
    },
    /**
     * Reset password with token
     */
    async resetPassword(data) {
        await apiClient.post('/auth/reset-password', data);
    },
    /**
     * Change password (logged in user)
     */
    async changePassword(data) {
        await apiClient.post('/auth/change-password', data);
    },
    /**
     * Verify JWT token
     */
    async verifyToken(token) {
        try {
            const response = await apiClient.post('/auth/verify-token', { token });
            return response.data.data.valid;
        }
        catch (error) {
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
    async enable2FA() {
        const response = await apiClient.post('/2fa/enable');
        return response.data.data;
    },
    /**
     * Verify 2FA setup - Step 2: Verify code and activate
     */
    async verifySetup(code) {
        await apiClient.post('/2fa/verify-setup', { code });
    },
    /**
     * Disable 2FA
     */
    async disable2FA(password) {
        await apiClient.post('/2fa/disable', { password });
    },
    /**
     * Verify 2FA code (for login)
     */
    async verifyCode(userId, userType, code) {
        try {
            const response = await apiClient.post('/2fa/verify', {
                user_id: userId,
                user_type: userType,
                code,
            });
            return response.data.data.verified;
        }
        catch (error) {
            return false;
        }
    },
    /**
     * Verify backup code
     */
    async verifyBackupCode(userId, userType, code) {
        try {
            const response = await apiClient.post('/2fa/verify-backup', {
                user_id: userId,
                user_type: userType,
                code,
            });
            return response.data.data.verified;
        }
        catch (error) {
            return false;
        }
    },
    /**
     * Regenerate backup codes
     */
    async regenerateBackupCodes(password) {
        const response = await apiClient.post('/2fa/backup-codes/regenerate', {
            password,
        });
        return response.data.data.backup_codes;
    },
    /**
     * Get 2FA status
     */
    async getStatus() {
        const response = await apiClient.get('/2fa/status');
        return response.data.data;
    },
};
