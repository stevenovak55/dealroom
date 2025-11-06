import { jsx as _jsx, Fragment as _Fragment, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';
import { cn } from '@/utils/cn';
export const TwoFactorVerifyForm = ({ onSuccess, onCancel }) => {
    const { verify2FA, verifyBackupCode, isLoading, error, clearError } = useAuth();
    const [code, setCode] = useState('');
    const [useBackupCode, setUseBackupCode] = useState(false);
    const handleSubmit = async (e) => {
        e.preventDefault();
        clearError();
        if (!code.trim()) {
            return;
        }
        try {
            if (useBackupCode) {
                await verifyBackupCode(code.trim());
            }
            else {
                await verify2FA(code.trim());
            }
            onSuccess?.();
        }
        catch (err) {
            // Error is already set in the auth store
        }
    };
    const toggleCodeType = () => {
        setCode('');
        setUseBackupCode(!useBackupCode);
        clearError();
    };
    return (_jsxs("form", { onSubmit: handleSubmit, className: "space-y-5 md:space-y-6", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-xl md:text-2xl font-bold text-gray-900", children: "Two-factor authentication" }), _jsx("p", { className: "mt-2 text-sm md:text-base text-gray-600", children: useBackupCode ? (_jsx(_Fragment, { children: "Enter one of your backup codes to sign in." })) : (_jsx(_Fragment, { children: "Enter the 6-digit code from your authenticator app." })) })] }), error && (_jsx("div", { className: cn('rounded-md bg-danger-50', 'p-3 md:p-4'), children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 md:h-6 md:w-6 text-danger-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", "aria-hidden": "true", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3", children: _jsx("h3", { className: "text-sm md:text-base font-medium text-danger-800", children: error }) })] }) })), _jsx(Input, { label: useBackupCode ? 'Backup code' : 'Authentication code', type: "text", value: code, onChange: (e) => {
                    const value = e.target.value.replace(/\s/g, '');
                    if (useBackupCode) {
                        // Backup codes are alphanumeric
                        setCode(value.toUpperCase());
                    }
                    else {
                        // TOTP codes are 6 digits
                        if (/^\d{0,6}$/.test(value)) {
                            setCode(value);
                        }
                    }
                }, required: true, autoComplete: "off", placeholder: useBackupCode ? 'XXXXXXXX' : '000000', disabled: isLoading, autoFocus: true, maxLength: useBackupCode ? 16 : 6, 
                // Large, centered text for easy code entry
                className: "text-center text-2xl md:text-3xl tracking-wider font-mono", inputMode: useBackupCode ? 'text' : 'numeric' }), _jsxs("div", { className: "space-y-3", children: [_jsx(Button, { type: "submit", size: "lg", className: "w-full", isLoading: isLoading, children: "Verify and sign in" }), _jsx("button", { type: "button", onClick: toggleCodeType, className: cn('w-full text-center font-medium text-primary-600 hover:text-primary-500', 'text-sm md:text-base', 
                        // Touch-friendly padding
                        'py-2 px-4', 'transition-colors duration-fast', 'underline'), disabled: isLoading, children: useBackupCode ? 'Use authenticator app code' : 'Use backup code instead' }), onCancel && (_jsx(Button, { type: "button", variant: "ghost", size: "lg", className: "w-full", onClick: onCancel, disabled: isLoading, children: "Cancel" }))] }), _jsx("div", { className: cn('rounded-md bg-gray-50', 'p-3 md:p-4'), children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 md:h-6 md:w-6 text-gray-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", "aria-hidden": "true", children: _jsx("path", { fillRule: "evenodd", d: "M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3 flex-1", children: _jsx("p", { className: "text-sm md:text-base text-gray-700", children: useBackupCode ? (_jsx(_Fragment, { children: "Each backup code can only be used once. After using a backup code, make sure to regenerate your backup codes from your account settings." })) : (_jsx(_Fragment, { children: "Open your authenticator app and enter the 6-digit code. The code changes every 30 seconds." })) }) })] }) })] }));
};
