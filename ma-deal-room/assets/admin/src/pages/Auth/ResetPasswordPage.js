import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useNavigate, useSearchParams, Link } from 'react-router-dom';
import { ResetPasswordForm } from '@/components/Auth/ResetPasswordForm';
import { useEffect, useState } from 'react';
import { cn } from '@/utils/cn';
/**
 * ResetPasswordPage Component (Mobile-First Redesign)
 *
 * Responsive password reset page with mobile-first design:
 * - Mobile (<768px): Full-width with touch-friendly spacing
 * - Desktop (>=768px): Centered card with max-width
 *
 * Features:
 * - Token validation from URL params
 * - Invalid token state with clear error message
 * - Touch-friendly password reset form
 * - Responsive text sizing
 * - Auto-redirect to login after success
 */
export const ResetPasswordPage = () => {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const [token, setToken] = useState(null);
    useEffect(() => {
        const tokenParam = searchParams.get('token');
        if (!tokenParam) {
            // If no token, redirect to forgot password page
            navigate('/auth/forgot-password', { replace: true });
        }
        else {
            setToken(tokenParam);
        }
    }, [searchParams, navigate]);
    const handleSuccess = () => {
        // Form already shows success state with "Go to sign in" button
        setTimeout(() => {
            navigate('/auth/login');
        }, 2000);
    };
    // Invalid token state - responsive design
    if (!token) {
        return (_jsx("div", { className: cn('flex min-h-screen items-center justify-center', 'bg-gray-50', 'px-4 py-8 md:px-6 md:py-12 lg:px-8'), children: _jsxs("div", { className: "w-full max-w-md space-y-6 md:space-y-8 text-center", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-xl md:text-2xl font-bold text-gray-900", children: "Invalid Reset Link" }), _jsx("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: "This password reset link is invalid or has expired." })] }), _jsx(Link, { to: "/auth/forgot-password", className: cn('inline-block rounded-md bg-primary-600 text-white', 'hover:bg-primary-700 transition-colors duration-fast', 
                        // Touch-friendly button
                        'px-5 py-3 md:px-4 md:py-2', 'text-base md:text-sm font-medium', 'min-h-touch md:min-h-0'), children: "Request a new reset link" })] }) }));
    }
    return (_jsx("div", { className: cn('flex min-h-screen items-center justify-center', 'bg-gray-50', 
        // Mobile-first padding
        'px-4 py-8 md:px-6 md:py-12 lg:px-8'), children: _jsxs("div", { className: "w-full max-w-md space-y-6 md:space-y-8", children: [_jsxs("div", { className: "text-center", children: [_jsx("h1", { className: "text-3xl md:text-4xl font-bold text-gray-900", children: "MA Deal Room" }), _jsx("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: "Real estate transaction management platform" })] }), _jsx("div", { className: cn('rounded-lg border border-gray-200 bg-white shadow-sm', 
                    // Mobile-first padding: larger on mobile for easier touch
                    'px-5 py-8 md:px-8 md:py-10'), children: _jsx(ResetPasswordForm, { token: token, onSuccess: handleSuccess }) }), _jsx("div", { className: "text-center text-sm md:text-base text-gray-600", children: _jsxs("p", { children: ["Remember your password?", ' ', _jsx(Link, { to: "/auth/login", className: cn('font-medium text-primary-600 hover:text-primary-500', 
                                // Increase touch target size
                                'inline-block py-1'), children: "Sign in" })] }) })] }) }));
};
