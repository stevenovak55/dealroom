import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { NavLink } from 'react-router-dom';
import { X, User, LogOut, Home, FileText, FolderOpen, Database, Users, FileSignature, Layout, Library, Bell, Settings, UserCog } from 'lucide-react';
import { useEffect } from 'react';
import { cn } from '@/utils/cn';
import { useAuth } from '@/hooks/useAuth';
const navigationItems = [
    { name: 'Dashboard', href: '/', icon: Home },
    { name: 'Transactions', href: '/transactions', icon: FileText },
    { name: 'Documents', href: '/documents', icon: FolderOpen },
    { name: 'Users', href: '/users', icon: UserCog },
    { name: 'MLS Integration', href: '/mls', icon: Database },
    { name: 'CRM Integration', href: '/integrations/crm', icon: Users },
    { name: 'DocuSign', href: '/integrations/docusign', icon: FileSignature },
    { name: 'Templates', href: '/templates', icon: Layout },
    { name: 'Task Library', href: '/task-library', icon: Library },
    { name: 'Reminders', href: '/reminders', icon: Bell },
    { name: 'Settings', href: '/settings', icon: Settings },
];
export const MobileMenu = ({ isOpen, onClose }) => {
    const { user, logout } = useAuth();
    // Close on escape key
    useEffect(() => {
        if (!isOpen)
            return;
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                onClose();
            }
        };
        document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [isOpen, onClose]);
    // Prevent body scroll when menu is open
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        }
        else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);
    // Don't render if not open (but keep in DOM for animations)
    if (!isOpen) {
        return null;
    }
    const handleLogout = async () => {
        if (confirm('Are you sure you want to log out?')) {
            await logout();
            onClose();
        }
    };
    return (_jsxs(_Fragment, { children: [_jsx("div", { className: cn('fixed inset-0 bg-black/50 z-mobile-menu', 'md:hidden', 'animate-in fade-in duration-normal'), onClick: onClose, "aria-hidden": "true" }), _jsxs("nav", { className: cn('fixed top-0 left-0 bottom-0 z-mobile-menu', 'w-[280px] max-w-[85vw]', 'bg-white', 'flex flex-col', 'md:hidden', 'animate-in slide-in-from-left duration-normal', 'pt-safe-top pb-safe-bottom pl-safe-left'), role: "navigation", "aria-label": "Main navigation", children: [_jsxs("div", { className: "flex items-center justify-between px-6 py-4 border-b border-gray-200", children: [_jsx("h2", { className: "text-lg font-bold text-gray-900", children: "MA Deal Room" }), _jsx("button", { type: "button", onClick: onClose, className: cn('min-w-touch min-h-touch', 'flex items-center justify-center', 'p-2 -mr-2 rounded-lg', 'text-gray-500 hover:text-gray-700 hover:bg-gray-100', 'transition-colors duration-fast'), "aria-label": "Close menu", children: _jsx(X, { className: "h-6 w-6" }) })] }), user && (_jsxs("div", { className: "px-6 py-4 border-b border-gray-200", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx("div", { className: "h-10 w-10 bg-primary-600 rounded-full flex items-center justify-center flex-shrink-0", children: _jsx(User, { className: "h-6 w-6 text-white" }) }), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("p", { className: "text-sm font-semibold text-gray-900 truncate", children: user.first_name && user.last_name
                                                    ? `${user.first_name} ${user.last_name}`
                                                    : user.email?.split('@')[0] || 'User' }), _jsx("p", { className: "text-xs text-gray-500 truncate", children: user.email })] })] }), _jsx(NavLink, { to: "/profile", onClick: onClose, className: "mt-3 block w-full text-center py-2 px-4 text-sm font-medium text-primary-600 hover:bg-primary-50 rounded-lg transition-colors duration-fast", children: "View Profile" })] })), _jsx("div", { className: "flex-1 overflow-y-auto py-4", children: _jsx("div", { className: "px-3 space-y-1", children: navigationItems.map((item) => {
                                const Icon = item.icon;
                                return (_jsx(NavLink, { to: item.href, onClick: onClose, className: ({ isActive }) => cn('flex items-center gap-3', 'min-h-touch px-3 py-3 rounded-lg', 'text-sm font-medium', 'transition-colors duration-fast', 'relative', isActive
                                        ? 'bg-primary-50 text-primary-600'
                                        : 'text-gray-700 hover:bg-gray-50 active:bg-gray-100'), children: ({ isActive }) => (_jsxs(_Fragment, { children: [_jsx(Icon, { className: cn('h-5 w-5 flex-shrink-0') }), _jsx("span", { className: "flex-1", children: item.name }), item.badge !== undefined && item.badge > 0 && (_jsx("span", { className: cn('px-2 py-0.5 text-xs font-bold rounded-full', isActive
                                                    ? 'bg-primary-600 text-white'
                                                    : 'bg-gray-200 text-gray-700'), children: item.badge > 99 ? '99+' : item.badge }))] })) }, item.href));
                            }) }) }), _jsxs("div", { className: "px-3 py-4 border-t border-gray-200", children: [_jsxs("button", { type: "button", onClick: handleLogout, className: cn('flex items-center gap-3', 'w-full min-h-touch px-3 py-3 rounded-lg', 'text-sm font-medium', 'text-red-600 hover:bg-red-50 active:bg-red-100', 'transition-colors duration-fast'), children: [_jsx(LogOut, { className: "h-5 w-5" }), _jsx("span", { children: "Log Out" })] }), _jsx("div", { className: "mt-4 px-3", children: _jsx("p", { className: "text-xs text-gray-400", children: "Version 2.0.0" }) })] })] })] }));
};
