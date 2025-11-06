import { jsx as _jsx } from "react/jsx-runtime";
import { cn } from '@/utils/cn';
export const Loader = ({ size = 'md', className }) => {
    const sizes = {
        sm: 'h-4 w-4 border-2',
        md: 'h-8 w-8 border-3',
        lg: 'h-12 w-12 border-4',
    };
    return (_jsx("div", { className: cn('flex items-center justify-center', className), children: _jsx("div", { className: cn('animate-spin rounded-full border-primary-600 border-t-transparent', sizes[size]) }) }));
};
export const PageLoader = () => {
    return (_jsx("div", { className: "flex items-center justify-center h-64", children: _jsx(Loader, { size: "lg" }) }));
};
