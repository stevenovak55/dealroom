import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { twoFactorApi } from '@/api/services/auth';
export const TwoFactorSetupWizard = ({ onSuccess, onCancel }) => {
    const [step, setStep] = useState('scan');
    const [setupData, setSetupData] = useState(null);
    const [verificationCode, setVerificationCode] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);
    const handleStartSetup = async () => {
        setIsLoading(true);
        setError(null);
        try {
            const data = await twoFactorApi.enable2FA();
            setSetupData(data);
            setStep('scan');
        }
        catch (err) {
            setError(err.response?.data?.message || 'Failed to initialize 2FA setup');
        }
        finally {
            setIsLoading(false);
        }
    };
    const handleVerify = async (e) => {
        e.preventDefault();
        setIsLoading(true);
        setError(null);
        try {
            await twoFactorApi.verifySetup(verificationCode);
            setStep('backup-codes');
        }
        catch (err) {
            setError(err.response?.data?.message || 'Invalid verification code');
        }
        finally {
            setIsLoading(false);
        }
    };
    const handleComplete = () => {
        setStep('complete');
        onSuccess?.();
    };
    const handleDownloadBackupCodes = () => {
        if (!setupData?.backup_codes)
            return;
        const content = [
            'MA Deal Room - Two-Factor Authentication Backup Codes',
            '',
            'Keep these codes in a safe place. Each code can only be used once.',
            'Generated: ' + new Date().toLocaleString(),
            '',
            ...setupData.backup_codes.map((code, i) => `${i + 1}. ${code}`),
        ].join('\n');
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'ma-deal-room-backup-codes.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    };
    // Initial state - not started
    if (!setupData && step === 'scan') {
        return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-2xl font-bold text-gray-900", children: "Enable two-factor authentication" }), _jsx("p", { className: "mt-2 text-sm text-gray-600", children: "Add an extra layer of security to your account by requiring a verification code in addition to your password." })] }), error && (_jsx("div", { className: "rounded-md bg-danger-50 p-4", children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 text-danger-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3", children: _jsx("h3", { className: "text-sm font-medium text-danger-800", children: error }) })] }) })), _jsxs("div", { className: "rounded-lg border border-gray-200 bg-gray-50 p-6", children: [_jsx("h3", { className: "text-lg font-medium text-gray-900", children: "How it works:" }), _jsxs("ol", { className: "mt-4 space-y-3 text-sm text-gray-600", children: [_jsxs("li", { className: "flex", children: [_jsx("span", { className: "mr-3 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700", children: "1" }), _jsx("span", { children: "Download an authenticator app on your phone (Google Authenticator, Authy, etc.)" })] }), _jsxs("li", { className: "flex", children: [_jsx("span", { className: "mr-3 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700", children: "2" }), _jsx("span", { children: "Scan the QR code with your authenticator app" })] }), _jsxs("li", { className: "flex", children: [_jsx("span", { className: "mr-3 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700", children: "3" }), _jsx("span", { children: "Enter the 6-digit code from your app to verify" })] }), _jsxs("li", { className: "flex", children: [_jsx("span", { className: "mr-3 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700", children: "4" }), _jsx("span", { children: "Save your backup codes in a secure location" })] })] })] }), _jsxs("div", { className: "flex space-x-3", children: [_jsx(Button, { onClick: handleStartSetup, isLoading: isLoading, className: "flex-1", children: "Get started" }), onCancel && (_jsx(Button, { variant: "secondary", onClick: onCancel, disabled: isLoading, children: "Cancel" }))] })] }));
    }
    // Step 1: Scan QR code
    if (step === 'scan' && setupData) {
        return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-2xl font-bold text-gray-900", children: "Scan QR code" }), _jsx("p", { className: "mt-2 text-sm text-gray-600", children: "Open your authenticator app and scan this QR code." })] }), _jsxs("div", { className: "flex flex-col items-center space-y-4 rounded-lg border border-gray-200 bg-white p-6", children: [_jsx("div", { className: "rounded-lg bg-white p-4", children: _jsx("img", { src: setupData.qr_code, alt: "QR Code", className: "h-48 w-48" }) }), _jsxs("div", { className: "text-center", children: [_jsx("p", { className: "text-sm font-medium text-gray-700", children: "Can't scan the code?" }), _jsx("p", { className: "mt-1 text-xs text-gray-500", children: "Enter this code manually in your app:" }), _jsx("code", { className: "mt-2 block rounded bg-gray-100 px-3 py-2 text-sm font-mono text-gray-900", children: setupData.secret })] })] }), _jsx(Button, { onClick: () => setStep('verify'), className: "w-full", children: "Next: Verify code" })] }));
    }
    // Step 2: Verify code
    if (step === 'verify' && setupData) {
        return (_jsxs("form", { onSubmit: handleVerify, className: "space-y-6", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-2xl font-bold text-gray-900", children: "Verify your setup" }), _jsx("p", { className: "mt-2 text-sm text-gray-600", children: "Enter the 6-digit code from your authenticator app to complete setup." })] }), error && (_jsx("div", { className: "rounded-md bg-danger-50 p-4", children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 text-danger-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z", clipRule: "evenodd" }) }) }), _jsx("div", { className: "ml-3", children: _jsx("h3", { className: "text-sm font-medium text-danger-800", children: error }) })] }) })), _jsx(Input, { label: "Verification code", type: "text", value: verificationCode, onChange: (e) => {
                        const value = e.target.value.replace(/\D/g, '');
                        if (value.length <= 6) {
                            setVerificationCode(value);
                        }
                    }, required: true, autoComplete: "off", placeholder: "000000", disabled: isLoading, autoFocus: true, maxLength: 6, className: "text-center text-2xl tracking-wider" }), _jsxs("div", { className: "flex space-x-3", children: [_jsx(Button, { type: "button", variant: "secondary", onClick: () => setStep('scan'), disabled: isLoading, children: "Back" }), _jsx(Button, { type: "submit", isLoading: isLoading, className: "flex-1", children: "Verify and continue" })] })] }));
    }
    // Step 3: Save backup codes
    if (step === 'backup-codes' && setupData) {
        return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-2xl font-bold text-gray-900", children: "Save your backup codes" }), _jsx("p", { className: "mt-2 text-sm text-gray-600", children: "Store these codes in a safe place. You can use them to access your account if you lose your phone." })] }), _jsx("div", { className: "rounded-md bg-warning-50 p-4", children: _jsxs("div", { className: "flex", children: [_jsx("div", { className: "flex-shrink-0", children: _jsx("svg", { className: "h-5 w-5 text-warning-400", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", children: _jsx("path", { fillRule: "evenodd", d: "M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z", clipRule: "evenodd" }) }) }), _jsxs("div", { className: "ml-3", children: [_jsx("h3", { className: "text-sm font-medium text-warning-800", children: "Each code can only be used once" }), _jsx("p", { className: "mt-1 text-sm text-warning-700", children: "Download or write down these codes now. You won't be able to see them again." })] })] }) }), _jsx("div", { className: "rounded-lg border border-gray-200 bg-gray-50 p-6", children: _jsx("div", { className: "grid grid-cols-2 gap-3 font-mono text-sm", children: setupData.backup_codes.map((code) => (_jsx("div", { className: "rounded bg-white px-3 py-2 text-center text-gray-900 shadow-sm", children: code }, code))) }) }), _jsxs("div", { className: "flex space-x-3", children: [_jsx(Button, { variant: "secondary", onClick: handleDownloadBackupCodes, className: "flex-1", children: "Download codes" }), _jsx(Button, { onClick: handleComplete, className: "flex-1", children: "I've saved my codes" })] })] }));
    }
    // Step 4: Complete
    if (step === 'complete') {
        return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "text-center", children: [_jsx("div", { className: "mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-100", children: _jsx("svg", { className: "h-6 w-6 text-success-600", xmlns: "http://www.w3.org/2000/svg", fill: "none", viewBox: "0 0 24 24", stroke: "currentColor", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 2, d: "M5 13l4 4L19 7" }) }) }), _jsx("h2", { className: "mt-4 text-2xl font-bold text-gray-900", children: "Two-factor authentication enabled!" }), _jsx("p", { className: "mt-2 text-sm text-gray-600", children: "Your account is now protected with two-factor authentication. You'll need to enter a code from your authenticator app each time you sign in." })] }), _jsxs("div", { className: "rounded-lg border border-gray-200 bg-gray-50 p-4", children: [_jsx("h3", { className: "text-sm font-medium text-gray-900", children: "What happens next?" }), _jsxs("ul", { className: "mt-2 space-y-2 text-sm text-gray-600", children: [_jsxs("li", { className: "flex items-start", children: [_jsx("svg", { className: "mr-2 h-5 w-5 flex-shrink-0 text-success-500", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z", clipRule: "evenodd" }) }), _jsx("span", { children: "You'll be asked for a code when signing in from a new device" })] }), _jsxs("li", { className: "flex items-start", children: [_jsx("svg", { className: "mr-2 h-5 w-5 flex-shrink-0 text-success-500", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z", clipRule: "evenodd" }) }), _jsx("span", { children: "Keep your backup codes safe in case you lose access to your phone" })] }), _jsxs("li", { className: "flex items-start", children: [_jsx("svg", { className: "mr-2 h-5 w-5 flex-shrink-0 text-success-500", xmlns: "http://www.w3.org/2000/svg", viewBox: "0 0 20 20", fill: "currentColor", children: _jsx("path", { fillRule: "evenodd", d: "M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z", clipRule: "evenodd" }) }), _jsx("span", { children: "You can regenerate backup codes from your account settings anytime" })] })] })] }), _jsx(Button, { onClick: onSuccess, className: "w-full", children: "Done" })] }));
    }
    return null;
};
