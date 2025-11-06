import axios from 'axios';
// Token storage keys
const ACCESS_TOKEN_KEY = 'ma_deal_access_token';
const REFRESH_TOKEN_KEY = 'ma_deal_refresh_token';
// Token management utilities
export const tokenManager = {
    getAccessToken: () => {
        return localStorage.getItem(ACCESS_TOKEN_KEY);
    },
    getRefreshToken: () => {
        return localStorage.getItem(REFRESH_TOKEN_KEY);
    },
    setTokens: (accessToken, refreshToken) => {
        localStorage.setItem(ACCESS_TOKEN_KEY, accessToken);
        localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
    },
    clearTokens: () => {
        localStorage.removeItem(ACCESS_TOKEN_KEY);
        localStorage.removeItem(REFRESH_TOKEN_KEY);
    },
    hasTokens: () => {
        return !!(tokenManager.getAccessToken() && tokenManager.getRefreshToken());
    },
};
/**
 * Ensure URL uses HTTPS in production
 */
const ensureHttps = (url) => {
    // Only enforce HTTPS in production
    const isProduction = window.location.protocol === 'https:';
    if (isProduction && url.startsWith('http://')) {
        return url.replace('http://', 'https://');
    }
    return url;
};
// Get WordPress data from localized script
const getWpData = () => {
    if (window.maDealRoom) {
        return {
            ...window.maDealRoom,
            apiUrl: ensureHttps(window.maDealRoom.apiUrl),
        };
    }
    // Fallback for development
    return {
        apiUrl: '/wp-json/ma-deal-room/v1',
        nonce: '',
        currentUser: 0,
    };
};
// Track if we're currently refreshing the token
let isRefreshing = false;
let failedQueue = [];
const processQueue = (error = null) => {
    failedQueue.forEach((prom) => {
        if (error) {
            prom.reject(error);
        }
        else {
            prom.resolve();
        }
    });
    failedQueue = [];
};
// Create axios instance
const createApiClient = () => {
    const wpData = getWpData();
    const client = axios.create({
        baseURL: wpData.apiUrl,
        headers: {
            'Content-Type': 'application/json',
        },
    });
    // Request interceptor
    client.interceptors.request.use((config) => {
        // Add JWT token if available (for custom users)
        const accessToken = tokenManager.getAccessToken();
        if (accessToken && config.headers) {
            config.headers.Authorization = `Bearer ${accessToken}`;
        }
        // Add WordPress nonce (for WordPress users)
        if (wpData.nonce && config.headers) {
            config.headers['X-WP-Nonce'] = wpData.nonce;
        }
        // If the data is FormData, remove Content-Type header to let browser set it with boundary
        if (config.data instanceof FormData) {
            if (config.headers) {
                delete config.headers['Content-Type'];
            }
        }
        return config;
    }, (error) => {
        return Promise.reject(error);
    });
    // Response interceptor
    client.interceptors.response.use((response) => {
        // Unwrap WordPress REST API response
        return response;
    }, async (error) => {
        const originalRequest = error.config;
        // Handle error responses
        if (error.response) {
            const data = error.response.data;
            // Check for 401 Unauthorized (token expired)
            if (error.response.status === 401 && !originalRequest._retry) {
                // Only try to refresh token if we have a refresh token
                const refreshToken = tokenManager.getRefreshToken();
                if (refreshToken) {
                    if (isRefreshing) {
                        // Token refresh already in progress, queue this request
                        return new Promise((resolve, reject) => {
                            failedQueue.push({ resolve, reject });
                        })
                            .then(() => {
                            return client(originalRequest);
                        })
                            .catch((err) => {
                            return Promise.reject(err);
                        });
                    }
                    originalRequest._retry = true;
                    isRefreshing = true;
                    try {
                        // Refresh the token
                        const response = await axios.post(`${wpData.apiUrl}/auth/refresh`, { refresh_token: refreshToken });
                        const { access_token } = response.data.data;
                        // Update stored access token
                        if (access_token) {
                            tokenManager.setTokens(access_token, refreshToken);
                        }
                        // Process queued requests
                        processQueue(null);
                        // Retry original request with new token
                        if (originalRequest.headers) {
                            originalRequest.headers.Authorization = `Bearer ${access_token}`;
                        }
                        return client(originalRequest);
                    }
                    catch (refreshError) {
                        // Refresh failed, clear tokens and redirect to login
                        processQueue(refreshError);
                        tokenManager.clearTokens();
                        // Dispatch custom event for app to handle
                        window.dispatchEvent(new CustomEvent('auth:session-expired'));
                        return Promise.reject(refreshError);
                    }
                    finally {
                        isRefreshing = false;
                    }
                }
            }
            // WordPress REST API error format
            if (data?.code && data?.message) {
                return Promise.reject({
                    code: data.code,
                    message: data.message,
                    data: data.data,
                });
            }
        }
        return Promise.reject(error);
    });
    return client;
};
export const apiClient = createApiClient();
export const getCurrentUser = () => {
    return getWpData().currentUser;
};
export const getApiUrl = () => {
    return getWpData().apiUrl;
};
