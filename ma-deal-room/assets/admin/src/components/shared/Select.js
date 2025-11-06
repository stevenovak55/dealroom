import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { forwardRef } from 'react';
import { cn } from '@/utils/cn';
export const Select = forwardRef(({ className, label, error, helperText, options, id, ...props }, ref) => {
    const selectId = id || label?.toLowerCase().replace(/\s+/g, '-');
    return (_jsxs("div", { className: "w-full", children: [label && (_jsxs("label", { htmlFor: selectId, className: cn('block font-medium text-gray-700 mb-1.5', 'text-sm', 'md:text-sm md:mb-1'), children: [label, props.required && _jsx("span", { className: "text-danger-500 ml-1", children: "*" })] })), _jsx("select", { id: selectId, ref: ref, className: cn('flex w-full rounded-lg border border-gray-300 bg-white', 
                // Mobile-first sizing (touch-friendly)
                'min-h-touch px-4 py-3 text-base', 
                // Desktop sizing
                'md:h-10 md:px-3 md:py-2 md:text-sm', 
                // Styling
                'ring-offset-white', 'transition-colors duration-fast', 
                // Focus states
                'focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500', 
                // Disabled state
                'disabled:cursor-not-allowed disabled:opacity-50 disabled:bg-gray-50', 
                // Error state
                error && 'border-danger-500 focus:ring-danger-500 focus:border-danger-500', 
                // Custom arrow (native on mobile)
                'appearance-none bg-no-repeat bg-right', 'pr-10', // Room for dropdown arrow
                className), style: {
                    backgroundImage: `url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e")`,
                    backgroundPosition: 'right 0.5rem center',
                    backgroundSize: '1.5em 1.5em',
                }, ...props, children: options.map((option) => (_jsx("option", { value: option.value, children: option.label }, option.value))) }), error && (_jsx("p", { className: "mt-1.5 text-sm text-danger-600", children: error })), helperText && !error && (_jsx("p", { className: "mt-1.5 text-sm text-gray-500", children: helperText }))] }));
});
Select.displayName = 'Select';
