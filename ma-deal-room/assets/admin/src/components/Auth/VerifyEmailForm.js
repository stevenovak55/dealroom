import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';
export const VerifyEmailForm = ({ token, email, onSuccess, onBackToLogin }) => {
    const { verifyEmail, resendVerification, isLoading, error, clearError } = useAuth();
    const [verified, setVerified] = useState(false);
    const [resending, setResending] = useState(false);
    const [resent, setResent] = useState(false);
    useEffect(() => {
        if (token) {
            // Automatically verify if token is provided
            handleVerify();
        }
    }, [token]);
    const handleVerify = async () => {
        if (!token)
            return;
        clearError();
        try {
            await verifyEmail(token);
            setVerified(true);
            onSuccess?.();
        }
        catch (err) {
            // Error is already set in the auth store
        }
    };
    const handleResend = async () => {
        clearError();
        setResending(true);
        try {
            await resendVerification();
            setResent(true);
            setTimeout(() => setResent(false), 3000);
        }
        catch (err) {
            // Error is already set in the auth store
        }
        finally {
            setResending(false);
        }
    };
    if (verified) {
        return (_jsxs("div", { className: "space-y-5 md:space-y-6", children: [_jsxs("div", { className: "text-center", children: [_jsx("div", { className: cn('mx-auto flex items-center justify-center rounded-full bg-success-100', 
                            // Larger icon on mobile
                            'h-14 w-14 md:h-12 md:w-12'), children: _jsx("svg", { className: "h-7 w-7 md:h-6 md:w-6 text-success-600", xmlns: "http://www.w3.org/2000/svg", fill: "none", viewBox: "0 0 24 24", stroke: "currentColor", "aria-hidden": "true", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 2, d: "M5 13l4 4L19 7" }) }) }), _jsx("h2", { className: "mt-4 text-xl md:text-2xl font-bold text-gray-900", children: "Email verified!" }), _jsx("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: "Your email has been successfully verified. You can now access all features of your account." })] }), _jsx(Button, { type: "button", size: "lg", className: "w-full", onClick: onBackToLogin, children: "Continue to sign in" })] }));
    }
    return (_jsxs("div", { className: "space-y-5 md:space-y-6", children: [_jsxs("div", { className: "text-center", children: [_jsx("div", { className: cn('mx-auto flex items-center justify-center rounded-full bg-primary-100', 
                        // Larger icon on mobile
                        'h-14 w-14 md:h-12 md:w-12'), children: _jsx("svg", { className: "h-7 w-7 md:h-6 md:w-6 text-primary-600", xmlns: "http://www.w3.org/2000/svg", fill: "none", viewBox: "0 0 24 24", stroke: "currentColor", "aria-hidden": "true", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 2, d: "M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" }) }) }), _jsx("h2", { className: "mt-4 text-xl md:text-2xl font-bold text-gray-900", children: "Verify your email" }), _jsx("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: email ? (_jsxs(_Fragment, { children: ["We've sent a verification email to ", _jsx("strong", { children: email }), ". Please check your inbox and click the verification link."] })) : (_jsx(_Fragment, { children: "Please verify your email address to continue." })) })] }), error && (_jsx("div", { className: cn('rounded-md bg-danger-50', 'p-3 md:p-4'), children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 md:h-6 md:w-6 text-danger-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", "aria-hidden": "true", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3", children: _jsx("h3", { className: "text-sm md:text-base font-medium text-danger-800", children: error }) })] }) })), resent && (_jsx("div", { className: cn('rounded-md bg-success-50', 'p-3 md:p-4'), children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 md:h-6 md:w-6 text-success-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", "aria-hidden": "true", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3", children: _jsx("h3", { className: "text-sm md:text-base font-medium text-success-800", children: "Verification email sent! Check your inbox." }) })] }) })), _jsxs("div", { className: "space-y-3 md:space-y-4", children: [_jsx("p", { className: "text-sm md:text-base text-gray-500", children: "Didn't receive the email? Check your spam folder or request a new verification email." }), _jsxs("div", { className: "space-y-3", children: [token && (_jsx(Button, { type: "button", size: "lg", className: "w-full", onClick: handleVerify, isLoading: isLoading, children: "Verify now" })), _jsx(Button, { type: "button", variant: "secondary", size: "lg", className: "w-full", onClick: handleResend, isLoading: resending, disabled: isLoading, children: "Resend verification email" }), _jsx(Button, { type: "button", variant: "ghost", size: "lg", className: "w-full", onClick: onBackToLogin, disabled: isLoading || resending, children: "Back to sign in" })] })] })] }));
};
