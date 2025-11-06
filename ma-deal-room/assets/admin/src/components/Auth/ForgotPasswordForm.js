import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';
export const ForgotPasswordForm = ({ onSuccess, onBackToLogin }) => {
    const { requestPasswordReset, isLoading, error, clearError } = useAuth();
    const [email, setEmail] = useState('');
    const [submitted, setSubmitted] = useState(false);
    const handleSubmit = async (e) => {
        e.preventDefault();
        clearError();
        try {
            await requestPasswordReset(email);
            setSubmitted(true);
            onSuccess?.();
        }
        catch (err) {
            // Error is already set in the auth store
        }
    };
    if (submitted) {
        return (_jsxs("div", { className: "space-y-5 md:space-y-6", children: [_jsxs("div", { className: "text-center", children: [_jsx("div", { className: cn('mx-auto flex items-center justify-center rounded-full bg-success-100', 
                            // Larger icon on mobile
                            'h-14 w-14 md:h-12 md:w-12'), children: _jsx("svg", { className: "h-7 w-7 md:h-6 md:w-6 text-success-600", xmlns: "http://www.w3.org/2000/svg", fill: "none", viewBox: "0 0 24 24", stroke: "currentColor", "aria-hidden": "true", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 2, d: "M5 13l4 4L19 7" }) }) }), _jsx("h2", { className: "mt-4 text-xl md:text-2xl font-bold text-gray-900", children: "Check your email" }), _jsxs("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: ["If an account exists with the email ", _jsx("strong", { children: email }), ", you will receive password reset instructions."] })] }), _jsxs("div", { className: "space-y-3", children: [_jsx("p", { className: "text-sm md:text-base text-gray-500", children: "Didn't receive an email? Check your spam folder or try again with a different email address." }), _jsx(Button, { type: "button", variant: "secondary", size: "lg", className: "w-full", onClick: onBackToLogin, children: "Back to sign in" })] })] }));
    }
    return (_jsxs("form", { onSubmit: handleSubmit, className: "space-y-5 md:space-y-6", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-xl md:text-2xl font-bold text-gray-900", children: "Forgot your password?" }), _jsx("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: "No worries! Enter your email address and we'll send you instructions to reset your password." })] }), error && (_jsx("div", { className: cn('rounded-md bg-danger-50', 'p-3 md:p-4'), children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 md:h-6 md:w-6 text-danger-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", "aria-hidden": "true", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3", children: _jsx("h3", { className: "text-sm md:text-base font-medium text-danger-800", children: error }) })] }) })), _jsx(Input, { label: "Email address", type: "email", value: email, onChange: (e) => setEmail(e.target.value), required: true, autoComplete: "email", placeholder: "you@example.com", disabled: isLoading }), _jsxs("div", { className: "space-y-3", children: [_jsx(Button, { type: "submit", size: "lg", className: "w-full", isLoading: isLoading, children: "Send reset instructions" }), _jsx(Button, { type: "button", variant: "ghost", size: "lg", className: "w-full", onClick: onBackToLogin, disabled: isLoading, children: "Back to sign in" })] })] }));
};
