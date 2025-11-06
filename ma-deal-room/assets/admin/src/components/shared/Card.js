import { jsx as _jsx } from "react/jsx-runtime";
import { cn } from '@/utils/cn';
export const Card = ({ children, className, interactive = false }) => {
    return (_jsx("div", { className: cn('rounded-lg border border-gray-200 bg-white shadow-sm', 'transition-shadow duration-fast', interactive && 'hover:shadow-md cursor-pointer active:scale-[0.99]', className), children: children }));
};
export const CardHeader = ({ children, className }) => {
    return (_jsx("div", { className: cn(
        // Mobile-first padding
        'px-4 py-3', 'md:px-6 md:py-4', 'border-b border-gray-200', className), children: children }));
};
export const CardTitle = ({ children, className }) => {
    return (_jsx("h3", { className: cn(
        // Mobile-first text size
        'text-base font-semibold text-gray-900', 'md:text-lg', className), children: children }));
};
export const CardContent = ({ children, className }) => {
    return (_jsx("div", { className: cn(
        // Mobile-first padding
        'px-4 py-3', 'md:px-6 md:py-4', className), children: children }));
};
export const CardFooter = ({ children, className }) => {
    return (_jsx("div", { className: cn(
        // Mobile-first padding
        'px-4 py-3', 'md:px-6 md:py-4', 'border-t border-gray-200 bg-gray-50', className), children: children }));
};
