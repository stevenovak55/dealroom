import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { forwardRef } from 'react';
import { cn } from '@/utils/cn';
export const Input = forwardRef(({ className, label, error, helperText, id, type = 'text', ...props }, ref) => {
    const inputId = id || label?.toLowerCase().replace(/\s+/g, '-');
    // Auto-set inputMode based on type for better mobile keyboards
    const getInputMode = () => {
        if (props.inputMode)
            return props.inputMode;
        switch (type) {
            case 'email':
                return 'email';
            case 'tel':
                return 'tel';
            case 'number':
                return 'decimal';
            case 'url':
                return 'url';
            case 'search':
                return 'search';
            default:
                return undefined;
        }
    };
    return (_jsxs("div", { className: "w-full", children: [label && (_jsxs("label", { htmlFor: inputId, className: cn('block font-medium text-gray-700 mb-1.5', 'text-sm', 'md:text-sm md:mb-1'), children: [label, props.required && _jsx("span", { className: "text-danger-500 ml-1", children: "*" })] })), _jsx("input", { id: inputId, ref: ref, type: type, inputMode: getInputMode(), className: cn('flex w-full rounded-lg border border-gray-300 bg-white', 
                // Mobile-first sizing (touch-friendly)
                'min-h-touch px-4 py-3 text-base', 
                // Desktop sizing
                'md:h-10 md:px-3 md:py-2 md:text-sm', 
                // Styling
                'ring-offset-white', 'placeholder:text-gray-400', 'transition-colors duration-fast', 
                // Focus states
                'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500', 
                // Disabled state
                'disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-gray-50', 
                // File input styling
                'file:border-0 file:bg-transparent file:text-sm file:font-medium', 
                // Error state
                error && 'border-danger-500 focus:ring-danger-500 focus:border-danger-500', className), ...props }), error && (_jsx("p", { className: "mt-1.5 text-sm text-danger-600", children: error })), helperText && !error && (_jsx("p", { className: "mt-1.5 text-sm text-gray-500", children: helperText }))] }));
});
Input.displayName = 'Input';
