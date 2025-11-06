import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useNavigate, useSearchParams, useLocation, Link } from 'react-router-dom';
import { VerifyEmailForm } from '@/components/Auth/VerifyEmailForm';
import { cn } from '@/utils/cn';
/**
 * VerifyEmailPage Component (Mobile-First Redesign)
 *
 * Responsive email verification page with mobile-first design:
 * - Mobile (<768px): Full-width with touch-friendly spacing
 * - Desktop (>=768px): Centered card with max-width
 *
 * Features:
 * - Automatic verification if token in URL
 * - Manual resend verification option
 * - Touch-friendly buttons and links
 * - Responsive text sizing
 * - Auto-redirect to login after success
 */
export const VerifyEmailPage = () => {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const location = useLocation();
    // Get token from URL query parameter
    const token = searchParams.get('token') || undefined;
    // Get email from location state (passed from registration)
    const email = location.state?.email;
    const handleSuccess = () => {
        // Redirect to login after successful verification
        setTimeout(() => {
            navigate('/auth/login');
        }, 2000);
    };
    const handleBackToLogin = () => {
        navigate('/auth/login');
    };
    return (_jsx("div", { className: cn('flex min-h-screen items-center justify-center', 'bg-gray-50', 
        // Mobile-first padding
        'px-4 py-8 md:px-6 md:py-12 lg:px-8'), children: _jsxs("div", { className: "w-full max-w-md space-y-6 md:space-y-8", children: [_jsxs("div", { className: "text-center", children: [_jsx("h1", { className: "text-3xl md:text-4xl font-bold text-gray-900", children: "MA Deal Room" }), _jsx("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: "Real estate transaction management platform" })] }), _jsx("div", { className: cn('rounded-lg border border-gray-200 bg-white shadow-sm', 
                    // Mobile-first padding: larger on mobile for easier touch
                    'px-5 py-8 md:px-8 md:py-10'), children: _jsx(VerifyEmailForm, { token: token, email: email, onSuccess: handleSuccess, onBackToLogin: handleBackToLogin }) }), _jsx("div", { className: "text-center text-sm md:text-base text-gray-600", children: _jsxs("p", { children: ["Already verified?", ' ', _jsx(Link, { to: "/auth/login", className: cn('font-medium text-primary-600 hover:text-primary-500', 
                                // Increase touch target size
                                'inline-block py-1'), children: "Sign in" })] }) })] }) }));
};
