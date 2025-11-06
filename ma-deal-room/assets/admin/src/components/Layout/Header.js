import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Search, Bell, User, LogOut, Settings, CheckCheck } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import { useGetNotifications, useGetUnreadCount, useMarkAsRead, useMarkAllAsRead } from '../../api/queries/useNotifications';
import { useSearch } from '../../api/queries/useSearch';
import { GlobalSearchDropdown } from '../Search/GlobalSearchDropdown';
import { formatDistanceToNow } from 'date-fns';
export const Header = () => {
    const navigate = useNavigate();
    const { logout, user } = useAuth();
    const [searchQuery, setSearchQuery] = useState('');
    const [debouncedQuery, setDebouncedQuery] = useState('');
    const [showSearchResults, setShowSearchResults] = useState(false);
    const [showNotifications, setShowNotifications] = useState(false);
    const [showUserMenu, setShowUserMenu] = useState(false);
    const searchRef = useRef(null);
    const notificationsRef = useRef(null);
    const userMenuRef = useRef(null);
    // Fetch notifications
    const { data: notificationsData } = useGetNotifications({ limit: 20 });
    const { data: unreadCount = 0 } = useGetUnreadCount();
    const markAsRead = useMarkAsRead();
    const markAllAsRead = useMarkAllAsRead();
    const notifications = notificationsData?.data || [];
    const hasUnread = unreadCount > 0;
    // Search functionality
    const { data: searchResults, isLoading: isSearching } = useSearch(debouncedQuery, showSearchResults);
    // Debounce search query
    useEffect(() => {
        const timer = setTimeout(() => {
            setDebouncedQuery(searchQuery);
        }, 300);
        return () => clearTimeout(timer);
    }, [searchQuery]);
    // Show search results when user types
    useEffect(() => {
        if (searchQuery.length >= 2) {
            setShowSearchResults(true);
        }
        else {
            setShowSearchResults(false);
        }
    }, [searchQuery]);
    // Close dropdowns when clicking outside
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (searchRef.current && !searchRef.current.contains(event.target)) {
                setShowSearchResults(false);
            }
            if (notificationsRef.current && !notificationsRef.current.contains(event.target)) {
                setShowNotifications(false);
            }
            if (userMenuRef.current && !userMenuRef.current.contains(event.target)) {
                setShowUserMenu(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);
    const handleNotificationClick = (notification) => {
        if (!notification.is_read) {
            markAsRead.mutate(notification.id);
        }
        if (notification.link) {
            window.location.href = notification.link;
        }
        setShowNotifications(false);
    };
    const handleMarkAllAsRead = () => {
        markAllAsRead.mutate();
    };
    return (_jsxs("header", { className: "h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6", children: [_jsx("div", { className: "flex-1 max-w-lg", ref: searchRef, children: _jsxs("div", { className: "relative", children: [_jsx(Search, { className: "absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 pointer-events-none" }), _jsx("input", { type: "text", placeholder: "Search transactions, tasks, parties...", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), onFocus: () => {
                                if (searchQuery.length >= 2) {
                                    setShowSearchResults(true);
                                }
                            }, className: "w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" }), showSearchResults && searchResults && (_jsx(GlobalSearchDropdown, { results: searchResults, isLoading: isSearching, query: searchQuery, onClose: () => {
                                setShowSearchResults(false);
                                setSearchQuery('');
                            } }))] }) }), _jsxs("div", { className: "flex items-center gap-4 ml-6", children: [_jsxs("div", { className: "relative", ref: notificationsRef, children: [_jsxs("button", { onClick: () => setShowNotifications(!showNotifications), className: "relative p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors", children: [_jsx(Bell, { className: "h-5 w-5" }), hasUnread && (_jsx("span", { className: "absolute top-1 right-1 h-2 w-2 bg-danger-500 rounded-full" }))] }), showNotifications && (_jsxs("div", { className: "absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50", children: [_jsxs("div", { className: "p-4 border-b border-gray-200 flex items-center justify-between", children: [_jsxs("h3", { className: "text-sm font-semibold text-gray-900", children: ["Notifications ", hasUnread && _jsxs("span", { className: "text-primary-600", children: ["(", unreadCount, ")"] })] }), hasUnread && (_jsxs("button", { onClick: handleMarkAllAsRead, className: "text-xs text-primary-600 hover:text-primary-700 flex items-center gap-1", children: [_jsx(CheckCheck, { className: "h-3 w-3" }), "Mark all as read"] }))] }), _jsx("div", { className: "max-h-96 overflow-y-auto", children: notifications.length === 0 ? (_jsxs("div", { className: "p-8 text-center text-gray-500 text-sm", children: [_jsx(Bell, { className: "h-12 w-12 mx-auto mb-2 text-gray-300" }), _jsx("p", { children: "No notifications" }), _jsx("p", { className: "text-xs mt-1", children: "You're all caught up!" })] })) : (_jsx("div", { className: "divide-y divide-gray-100", children: notifications.map((notification) => (_jsx("button", { onClick: () => handleNotificationClick(notification), className: `w-full text-left p-4 hover:bg-gray-50 transition-colors ${!notification.is_read ? 'bg-primary-50' : ''}`, children: _jsxs("div", { className: "flex items-start gap-3", children: [!notification.is_read && (_jsx("div", { className: "flex-shrink-0 h-2 w-2 bg-primary-600 rounded-full mt-2" })), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("p", { className: `text-sm ${!notification.is_read ? 'font-semibold text-gray-900' : 'text-gray-700'}`, children: notification.title }), _jsx("p", { className: "text-sm text-gray-600 mt-1", children: notification.message }), _jsx("p", { className: "text-xs text-gray-400 mt-1", children: formatDistanceToNow(new Date(notification.created_at), { addSuffix: true }) })] })] }) }, notification.id))) })) })] }))] }), _jsxs("div", { className: "relative", ref: userMenuRef, children: [_jsxs("button", { onClick: () => setShowUserMenu(!showUserMenu), className: "flex items-center gap-2 p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors", children: [_jsx("div", { className: "h-8 w-8 bg-primary-600 rounded-full flex items-center justify-center", children: _jsx(User, { className: "h-5 w-5 text-white" }) }), _jsx("span", { className: "text-sm font-medium", children: user ? `${user.first_name || user.email?.split('@')[0] || 'User'}` : 'Admin' })] }), showUserMenu && (_jsx("div", { className: "absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50", children: _jsxs("div", { className: "p-2", children: [_jsxs("button", { onClick: () => {
                                                setShowUserMenu(false);
                                                navigate('/profile');
                                            }, className: "w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors", children: [_jsx(Settings, { className: "h-4 w-4" }), "Profile Settings"] }), _jsxs("button", { onClick: async () => {
                                                setShowUserMenu(false);
                                                if (confirm('Are you sure you want to log out?')) {
                                                    await logout();
                                                }
                                            }, className: "w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md transition-colors", children: [_jsx(LogOut, { className: "h-4 w-4" }), "Log Out"] })] }) }))] })] })] }));
};
