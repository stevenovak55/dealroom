import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';
export const LoginForm = ({ onSuccess, onForgotPassword, onRegister }) => {
    const { login, isLoading, error, clearError } = useAuth();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [remember, setRemember] = useState(false);
    const handleSubmit = async (e) => {
        e.preventDefault();
        clearError();
        try {
            await login({
                email,
                password,
                remember,
                device_name: navigator.userAgent,
                device_type: 'web',
            });
            onSuccess?.();
        }
        catch (err) {
            // Error is already set in the auth store
        }
    };
    return (_jsxs("form", { onSubmit: handleSubmit, className: "space-y-5 md:space-y-6", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-xl md:text-2xl font-bold text-gray-900", children: "Sign in to your account" }), _jsxs("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: ["Or", ' ', _jsx("button", { type: "button", onClick: onRegister, className: cn('font-medium text-primary-600 hover:text-primary-500', 'underline', 
                                // Touch-friendly padding
                                'py-1'), children: "create a new account" })] })] }), error && (_jsx("div", { className: cn('rounded-md bg-danger-50', 'p-3 md:p-4'), children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 md:h-6 md:w-6 text-danger-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", "aria-hidden": "true", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3", children: _jsx("h3", { className: "text-sm md:text-base font-medium text-danger-800", children: error }) })] }) })), _jsxs("div", { className: "space-y-4 md:space-y-5", children: [_jsx(Input, { label: "Email address", type: "email", value: email, onChange: (e) => setEmail(e.target.value), required: true, autoComplete: "email", placeholder: "you@example.com", disabled: isLoading }), _jsx(Input, { label: "Password", type: "password", value: password, onChange: (e) => setPassword(e.target.value), required: true, autoComplete: "current-password", placeholder: "Enter your password", disabled: isLoading })] }), _jsxs("div", { className: cn('flex flex-col space-y-3', 'md:flex-row md:items-center md:justify-between md:space-y-0'), children: [_jsxs("div", { className: "flex items-center", children: [_jsx("input", { id: "remember-me", name: "remember-me", type: "checkbox", checked: remember, onChange: (e) => setRemember(e.target.checked), className: cn(
                                // Touch-friendly size on mobile
                                'h-5 w-5 md:h-4 md:w-4', 'rounded border-gray-300 text-primary-600', 'focus:ring-primary-600 focus:ring-2', 'cursor-pointer'), disabled: isLoading }), _jsx("label", { htmlFor: "remember-me", className: cn('ml-2 block text-sm md:text-base text-gray-900', 'cursor-pointer', 
                                // Larger touch target
                                'py-1'), children: "Remember me" })] }), _jsx("div", { className: "text-sm md:text-base", children: _jsx("button", { type: "button", onClick: onForgotPassword, className: cn('font-medium text-primary-600 hover:text-primary-500', 'underline', 
                            // Touch-friendly padding
                            'py-1 px-1', 'transition-colors duration-fast'), disabled: isLoading, children: "Forgot your password?" }) })] }), _jsx(Button, { type: "submit", size: "lg", className: "w-full", isLoading: isLoading, children: "Sign in" })] }));
};
