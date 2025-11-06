import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { cn } from '@/utils/cn';
export const Label = ({ className, children, required, ...props }) => {
    return (_jsxs("label", { className: cn('block text-sm font-medium text-gray-700', className), ...props, children: [children, required && _jsx("span", { className: "text-red-500 ml-1", children: "*" })] }));
};
