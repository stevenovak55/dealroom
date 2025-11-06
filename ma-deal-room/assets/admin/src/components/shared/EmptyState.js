import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Button } from './Button';
export const EmptyState = ({ icon: Icon, title, description, action }) => {
    return (_jsxs("div", { className: "text-center py-12", children: [Icon && (_jsx(Icon, { className: "mx-auto h-12 w-12 text-gray-400 mb-4", strokeWidth: 1.5 })), _jsx("h3", { className: "text-lg font-medium text-gray-900 mb-2", children: title }), description && _jsx("p", { className: "text-sm text-gray-500 mb-6 max-w-md mx-auto", children: description }), action && (_jsx(Button, { onClick: action.onClick, variant: "primary", children: action.label }))] }));
};
