import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useNavigate, useLocation, Link } from 'react-router-dom';
import { LoginForm } from '@/components/Auth/LoginForm';
import { useAuth } from '@/hooks/useAuth';
import { useEffect } from 'react';
import { cn } from '@/utils/cn';
/**
 * LoginPage Component (Mobile-First Redesign)
 *
 * Responsive authentication page with mobile-first design:
 * - Mobile (<768px): Full-width with touch-friendly spacing
 * - Desktop (>=768px): Centered card with max-width
 *
 * Features:
 * - Touch-friendly links and buttons
 * - Responsive text sizing
 * - Safe padding on mobile
 * - Smooth redirects after authentication
 */
export const LoginPage = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const { isAuthenticated, requires2FA } = useAuth();
    // Get the path user was trying to access before being redirected to login
    const from = location.state?.from?.pathname || '/';
    useEffect(() => {
        // If already authenticated, redirect to the original destination
        if (isAuthenticated && !requires2FA) {
            navigate(from, { replace: true });
        }
    }, [isAuthenticated, requires2FA, from, navigate]);
    const handleLoginSuccess = () => {
        if (requires2FA) {
            navigate('/auth/2fa-verify');
        }
        else {
            navigate(from, { replace: true });
        }
    };
    const handleForgotPassword = () => {
        navigate('/auth/forgot-password');
    };
    const handleRegister = () => {
        navigate('/auth/register');
    };
    return (_jsx("div", { className: cn('flex min-h-screen items-center justify-center', 'bg-gray-50', 
        // Mobile-first padding
        'px-4 py-8 md:px-6 md:py-12 lg:px-8'), children: _jsxs("div", { className: "w-full max-w-md space-y-6 md:space-y-8", children: [_jsxs("div", { className: "text-center", children: [_jsx("h1", { className: "text-3xl md:text-4xl font-bold text-gray-900", children: "MA Deal Room" }), _jsx("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: "Real estate transaction management platform" })] }), _jsx("div", { className: cn('rounded-lg border border-gray-200 bg-white shadow-sm', 
                    // Mobile-first padding: larger on mobile for easier touch
                    'px-5 py-8 md:px-8 md:py-10'), children: _jsx(LoginForm, { onSuccess: handleLoginSuccess, onForgotPassword: handleForgotPassword, onRegister: handleRegister }) }), _jsx("div", { className: "text-center text-sm md:text-base text-gray-600", children: _jsxs("p", { children: ["Don't have an account?", ' ', _jsx(Link, { to: "/auth/register", className: cn('font-medium text-primary-600 hover:text-primary-500', 
                                // Increase touch target size
                                'inline-block py-1'), children: "Sign up for free" })] }) })] }) }));
};
