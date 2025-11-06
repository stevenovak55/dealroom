import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Keyboard } from 'lucide-react';
import { Modal } from './Modal';
import { getShortcutDisplay } from '@/hooks/useKeyboardShortcuts';
export const KeyboardShortcutsDialog = ({ isOpen, onClose, shortcuts, }) => {
    // Group shortcuts by category
    const grouped = shortcuts.reduce((acc, shortcut) => {
        const category = shortcut.category || 'General';
        if (!acc[category]) {
            acc[category] = [];
        }
        acc[category].push(shortcut);
        return acc;
    }, {});
    return (_jsx(Modal, { isOpen: isOpen, onClose: onClose, title: "Keyboard Shortcuts", size: "lg", children: _jsxs("div", { className: "p-6", children: [_jsxs("div", { className: "flex items-center gap-3 mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200", children: [_jsx(Keyboard, { className: "h-6 w-6 text-blue-600" }), _jsxs("div", { className: "flex-1", children: [_jsx("p", { className: "text-sm text-blue-900 font-medium", children: "Use keyboard shortcuts to navigate and perform actions quickly" }), _jsxs("p", { className: "text-xs text-blue-700 mt-1", children: ["Press ", _jsx("kbd", { className: "px-1.5 py-0.5 bg-white rounded border border-blue-300 font-mono text-xs", children: "?" }), " to open this dialog anytime"] })] })] }), _jsx("div", { className: "space-y-6", children: Object.entries(grouped).map(([category, categoryShortcuts]) => (_jsxs("div", { children: [_jsx("h3", { className: "text-sm font-semibold text-gray-900 mb-3 uppercase tracking-wide", children: category }), _jsx("div", { className: "space-y-2", children: categoryShortcuts.map((shortcut, index) => (_jsxs("div", { className: "flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50", children: [_jsx("span", { className: "text-sm text-gray-700", children: shortcut.description }), _jsx("kbd", { className: "px-3 py-1.5 bg-gray-100 text-gray-900 rounded border border-gray-300 font-mono text-sm font-semibold", children: getShortcutDisplay(shortcut) })] }, index))) })] }, category))) }), _jsx("div", { className: "mt-6 pt-6 border-t border-gray-200", children: _jsx("button", { onClick: onClose, className: "w-full px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors", children: "Close" }) })] }) }));
};
