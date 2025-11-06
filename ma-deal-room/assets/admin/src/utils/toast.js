import { jsx as _jsx } from "react/jsx-runtime";
import toast from 'react-hot-toast';
import { CheckCircle, XCircle, AlertCircle, Info } from 'lucide-react';
// Custom toast configurations with icons
export const showToast = {
    success: (message) => {
        toast.success(message, {
            duration: 4000,
            icon: _jsx(CheckCircle, { className: "h-5 w-5 text-green-500" }),
            style: {
                background: '#f0fdf4',
                border: '1px solid #86efac',
                color: '#166534',
            },
        });
    },
    error: (message) => {
        toast.error(message, {
            duration: 5000,
            icon: _jsx(XCircle, { className: "h-5 w-5 text-red-500" }),
            style: {
                background: '#fef2f2',
                border: '1px solid #fca5a5',
                color: '#991b1b',
            },
        });
    },
    warning: (message) => {
        toast(message, {
            duration: 4000,
            icon: _jsx(AlertCircle, { className: "h-5 w-5 text-orange-500" }),
            style: {
                background: '#fffbeb',
                border: '1px solid #fde68a',
                color: '#92400e',
            },
        });
    },
    info: (message) => {
        toast(message, {
            duration: 4000,
            icon: _jsx(Info, { className: "h-5 w-5 text-blue-500" }),
            style: {
                background: '#eff6ff',
                border: '1px solid #93c5fd',
                color: '#1e40af',
            },
        });
    },
    promise: (promise, messages) => {
        return toast.promise(promise, messages, {
            success: {
                icon: _jsx(CheckCircle, { className: "h-5 w-5 text-green-500" }),
            },
            error: {
                icon: _jsx(XCircle, { className: "h-5 w-5 text-red-500" }),
            },
        });
    },
};
export default showToast;
