import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Fragment, useState, useEffect, useMemo } from 'react';
import { Search, Command as CommandIcon, FileText } from 'lucide-react';
import { cn } from '@/utils/cn';
export const CommandPalette = ({ isOpen, onClose, commands }) => {
    const [search, setSearch] = useState('');
    const [selectedIndex, setSelectedIndex] = useState(0);
    // Filter commands based on search
    const filteredCommands = useMemo(() => {
        if (!search)
            return commands;
        const searchLower = search.toLowerCase();
        return commands.filter(cmd => {
            const labelMatch = cmd.label.toLowerCase().includes(searchLower);
            const descMatch = cmd.description?.toLowerCase().includes(searchLower);
            const keywordsMatch = cmd.keywords?.some(k => k.toLowerCase().includes(searchLower));
            return labelMatch || descMatch || keywordsMatch;
        });
    }, [commands, search]);
    // Group commands by category
    const groupedCommands = useMemo(() => {
        const groups = {};
        filteredCommands.forEach(cmd => {
            const category = cmd.category || 'Other';
            if (!groups[category])
                groups[category] = [];
            groups[category].push(cmd);
        });
        return groups;
    }, [filteredCommands]);
    // Reset selection when filtered commands change
    useEffect(() => {
        setSelectedIndex(0);
    }, [filteredCommands]);
    // Handle keyboard navigation
    useEffect(() => {
        if (!isOpen)
            return;
        const handleKeyDown = (e) => {
            if (e.key === 'Escape') {
                onClose();
            }
            else if (e.key === 'ArrowDown') {
                e.preventDefault();
                setSelectedIndex(prev => prev < filteredCommands.length - 1 ? prev + 1 : prev);
            }
            else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setSelectedIndex(prev => prev > 0 ? prev - 1 : prev);
            }
            else if (e.key === 'Enter' && filteredCommands[selectedIndex]) {
                e.preventDefault();
                filteredCommands[selectedIndex].onSelect();
                onClose();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, onClose, filteredCommands, selectedIndex]);
    // Reset search when closed
    useEffect(() => {
        if (!isOpen) {
            setSearch('');
            setSelectedIndex(0);
        }
    }, [isOpen]);
    if (!isOpen)
        return null;
    let flatIndex = 0;
    return (_jsxs(Fragment, { children: [_jsx("div", { className: "fixed inset-0 bg-black/50 z-50 animate-fade-in", onClick: onClose }), _jsx("div", { className: "fixed top-20 left-1/2 transform -translate-x-1/2 w-full max-w-2xl z-50 animate-scale-up", children: _jsxs("div", { className: "bg-white rounded-lg shadow-2xl border border-gray-200 overflow-hidden", children: [_jsxs("div", { className: "flex items-center gap-3 px-4 py-3 border-b border-gray-200", children: [_jsx(Search, { className: "h-5 w-5 text-gray-400 flex-shrink-0" }), _jsx("input", { type: "text", value: search, onChange: (e) => setSearch(e.target.value), placeholder: "Type a command or search...", className: "flex-1 outline-none text-gray-900 placeholder-gray-500", autoFocus: true }), _jsxs("div", { className: "flex items-center gap-2 text-xs text-gray-500", children: [_jsx("kbd", { className: "px-2 py-1 bg-gray-100 rounded border border-gray-300 font-mono", children: "ESC" }), "to close"] })] }), _jsx("div", { className: "max-h-96 overflow-y-auto", children: filteredCommands.length === 0 ? (_jsxs("div", { className: "px-4 py-8 text-center text-gray-500", children: [_jsx(CommandIcon, { className: "h-12 w-12 mx-auto mb-3 opacity-50" }), _jsx("p", { children: "No commands found" }), _jsx("p", { className: "text-sm mt-1", children: "Try a different search term" })] })) : (_jsx("div", { className: "py-2", children: Object.entries(groupedCommands).map(([category, cmds]) => (_jsxs("div", { className: "mb-2", children: [_jsx("div", { className: "px-4 py-1 text-xs font-semibold text-gray-500 uppercase tracking-wider", children: category }), cmds.map(cmd => {
                                            const currentIndex = flatIndex++;
                                            const Icon = cmd.icon || FileText;
                                            const isSelected = currentIndex === selectedIndex;
                                            return (_jsxs("button", { onClick: () => {
                                                    cmd.onSelect();
                                                    onClose();
                                                }, onMouseEnter: () => setSelectedIndex(currentIndex), className: cn('w-full flex items-center gap-3 px-4 py-2.5 text-left transition-colors', isSelected
                                                    ? 'bg-primary-50 text-primary-900'
                                                    : 'text-gray-700 hover:bg-gray-50'), children: [_jsx(Icon, { className: cn('h-5 w-5 flex-shrink-0', isSelected ? 'text-primary-600' : 'text-gray-400') }), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("div", { className: "font-medium", children: cmd.label }), cmd.description && (_jsx("div", { className: "text-sm text-gray-500 truncate", children: cmd.description }))] })] }, cmd.id));
                                        })] }, category))) })) }), _jsxs("div", { className: "px-4 py-2 bg-gray-50 border-t border-gray-200 flex items-center justify-between text-xs text-gray-500", children: [_jsxs("div", { className: "flex items-center gap-4", children: [_jsxs("span", { className: "flex items-center gap-1", children: [_jsx("kbd", { className: "px-1.5 py-0.5 bg-white rounded border border-gray-300 font-mono", children: "\u2191" }), _jsx("kbd", { className: "px-1.5 py-0.5 bg-white rounded border border-gray-300 font-mono", children: "\u2193" }), "navigate"] }), _jsxs("span", { className: "flex items-center gap-1", children: [_jsx("kbd", { className: "px-1.5 py-0.5 bg-white rounded border border-gray-300 font-mono", children: "\u21B5" }), "select"] })] }), _jsxs("span", { className: "flex items-center gap-1", children: ["Open with", _jsxs("kbd", { className: "px-1.5 py-0.5 bg-white rounded border border-gray-300 font-mono", children: [navigator.platform.includes('Mac') ? '⌘' : 'Ctrl', "+K"] })] })] })] }) })] }));
};
// Hook to manage command palette state and keyboard shortcut
export const useCommandPalette = () => {
    const [isOpen, setIsOpen] = useState(false);
    useEffect(() => {
        const handleKeyDown = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setIsOpen(prev => !prev);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);
    return {
        isOpen,
        open: () => setIsOpen(true),
        close: () => setIsOpen(false),
        toggle: () => setIsOpen(prev => !prev),
    };
};
