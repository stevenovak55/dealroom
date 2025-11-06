import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { forwardRef } from 'react';
import { cn } from '@/utils/cn';
export const Button = forwardRef(({ className, variant = 'primary', size = 'md', isLoading, isMobile = false, children, disabled, ...props }, ref) => {
    // Base styles - mobile-first with touch-friendly defaults
    const baseStyles = cn('inline-flex items-center justify-center', 'rounded-lg font-medium', 'transition-all duration-fast', 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2', 'disabled:pointer-events-none disabled:opacity-50', 'active:scale-95', // Tactile feedback on press
    // Touch-friendly minimum on mobile
    isMobile && 'min-h-touch min-w-touch');
    const variants = {
        primary: 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800 focus-visible:ring-primary-600 shadow-sm hover:shadow',
        secondary: 'bg-gray-100 text-gray-900 hover:bg-gray-200 active:bg-gray-300 focus-visible:ring-gray-500 border border-gray-300',
        danger: 'bg-danger-600 text-white hover:bg-danger-700 active:bg-danger-800 focus-visible:ring-danger-600 shadow-sm hover:shadow',
        ghost: 'hover:bg-gray-100 active:bg-gray-200 text-gray-700 focus-visible:ring-gray-500',
    };
    // Mobile-first sizes - start small, scale up on desktop
    const sizes = {
        // Small: Touch-friendly on mobile, compact on desktop
        sm: cn('min-h-touch px-3 py-2 text-sm', 'md:h-9 md:px-3 md:py-1.5'),
        // Medium: Comfortable touch target on mobile, standard on desktop
        md: cn('min-h-touch px-4 py-3 text-base', 'md:h-10 md:px-4 md:py-2 md:text-sm'),
        // Large: Extra comfortable on mobile, prominent on desktop
        lg: cn('min-h-touch-lg px-6 py-3 text-lg', 'md:h-12 md:px-8 md:py-3 md:text-base'),
        // Icon: Perfect square touch target
        icon: cn('min-h-touch min-w-touch p-0', 'md:h-10 md:w-10'),
    };
    return (_jsxs("button", { ref: ref, className: cn(baseStyles, variants[variant], sizes[size], className), disabled: disabled || isLoading, ...props, children: [isLoading && (_jsxs("svg", { className: "mr-2 h-4 w-4 animate-spin", xmlns: "http://www.w3.org/2000/svg", fill: "none", viewBox: "0 0 24 24", children: [_jsx("circle", { className: "opacity-25", cx: "12", cy: "12", r: "10", stroke: "currentColor", strokeWidth: "4" }), _jsx("path", { className: "opacity-75", fill: "currentColor", d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" })] })), children] }));
});
Button.displayName = 'Button';
