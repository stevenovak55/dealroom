import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useNavigate } from 'react-router-dom';
import { FileText, CheckSquare, Users, File, Layout, Loader2 } from 'lucide-react';
const typeIcons = {
    transaction: Layout,
    task: CheckSquare,
    party: Users,
    document: File,
    template: FileText,
};
const typeLabels = {
    transactions: 'Transactions',
    tasks: 'Tasks',
    parties: 'Parties',
    documents: 'Documents',
    templates: 'Templates',
};
export const GlobalSearchDropdown = ({ results, isLoading, query, onClose }) => {
    const navigate = useNavigate();
    const handleResultClick = (result) => {
        navigate(result.url);
        onClose();
    };
    const handleKeyDown = (e, result) => {
        if (e.key === 'Enter') {
            handleResultClick(result);
        }
    };
    if (query.length === 0) {
        return null;
    }
    if (query.length < 2) {
        return (_jsx("div", { className: "absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-4", children: _jsx("p", { className: "text-sm text-gray-500 text-center", children: "Type at least 2 characters to search" }) }));
    }
    if (isLoading) {
        return (_jsx("div", { className: "absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-4", children: _jsxs("div", { className: "flex items-center justify-center gap-2", children: [_jsx(Loader2, { className: "h-4 w-4 animate-spin text-primary-600" }), _jsx("p", { className: "text-sm text-gray-500", children: "Searching..." })] }) }));
    }
    if (results.total === 0) {
        return (_jsx("div", { className: "absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 p-4", children: _jsxs("p", { className: "text-sm text-gray-500 text-center", children: ["No results found for \"", query, "\""] }) }));
    }
    return (_jsxs("div", { className: "absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-h-96 overflow-y-auto", children: [Object.entries(results).map(([key, items]) => {
                if (key === 'total' || !Array.isArray(items) || items.length === 0) {
                    return null;
                }
                const typedKey = key;
                const firstItem = items[0];
                const Icon = typeIcons[firstItem.type];
                return (_jsxs("div", { className: "border-b border-gray-200 last:border-0", children: [_jsx("div", { className: "px-4 py-2 bg-gray-50", children: _jsxs("h3", { className: "text-xs font-semibold text-gray-700 uppercase tracking-wider flex items-center gap-2", children: [_jsx(Icon, { className: "h-3.5 w-3.5" }), typeLabels[typedKey]] }) }), _jsx("div", { children: items.map((result) => {
                                const ResultIcon = typeIcons[result.type];
                                return (_jsx("div", { onClick: () => handleResultClick(result), onKeyDown: (e) => handleKeyDown(e, result), className: "px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors focus:bg-gray-50 focus:outline-none", role: "button", tabIndex: 0, children: _jsxs("div", { className: "flex items-start gap-3", children: [_jsx(ResultIcon, { className: "h-5 w-5 text-gray-400 mt-0.5 flex-shrink-0" }), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("p", { className: "text-sm font-medium text-gray-900 truncate", children: result.title }), result.subtitle && (_jsx("p", { className: "text-sm text-gray-500 truncate", children: result.subtitle })), result.meta && (_jsx("p", { className: "text-xs text-gray-400 mt-1", children: result.meta }))] })] }) }, `${result.type}-${result.id}`));
                            }) })] }, key));
            }), _jsx("div", { className: "px-4 py-2 bg-gray-50 border-t border-gray-200", children: _jsxs("p", { className: "text-xs text-gray-500 text-center", children: ["Showing ", results.total, " result", results.total !== 1 ? 's' : '', " for \"", query, "\""] }) })] }));
};
