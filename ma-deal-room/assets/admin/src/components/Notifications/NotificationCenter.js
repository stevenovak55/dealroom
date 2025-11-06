import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { Bell, Check, CheckCheck, X, Settings, Trash2, Mail, AlertCircle, CheckCircle, Info, AlertTriangle } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card, CardContent } from '@/components/shared/Card';
import { formatDistanceToNow } from 'date-fns';
import toast from 'react-hot-toast';
const NOTIFICATION_ICONS = {
    task_assigned: CheckCircle,
    task_completed: CheckCheck,
    task_overdue: AlertCircle,
    transaction_update: Info,
    document_uploaded: Mail,
    comment_mention: AlertCircle,
    deadline_approaching: AlertTriangle,
    system_alert: AlertCircle,
};
const NOTIFICATION_COLORS = {
    task_assigned: 'bg-blue-100 text-blue-600',
    task_completed: 'bg-green-100 text-green-600',
    task_overdue: 'bg-red-100 text-red-600',
    transaction_update: 'bg-purple-100 text-purple-600',
    document_uploaded: 'bg-indigo-100 text-indigo-600',
    comment_mention: 'bg-amber-100 text-amber-600',
    deadline_approaching: 'bg-orange-100 text-orange-600',
    system_alert: 'bg-gray-100 text-gray-600',
};
const PRIORITY_COLORS = {
    low: 'border-gray-200',
    medium: 'border-blue-300',
    high: 'border-amber-400',
    urgent: 'border-red-500',
};
export const NotificationCenter = ({ notifications, onMarkAsRead, onMarkAllAsRead, onDelete, onNotificationClick, }) => {
    const [filterType, setFilterType] = useState('all');
    const [selectedIds, setSelectedIds] = useState([]);
    const [showSettings, setShowSettings] = useState(false);
    // Filter notifications
    const filteredNotifications = useMemo(() => {
        let filtered = [...notifications];
        if (filterType === 'unread') {
            filtered = filtered.filter(n => !n.is_read);
        }
        else if (filterType === 'read') {
            filtered = filtered.filter(n => n.is_read);
        }
        // Sort by date (newest first)
        return filtered.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
    }, [notifications, filterType]);
    // Unread count
    const unreadCount = useMemo(() => {
        return notifications.filter(n => !n.is_read).length;
    }, [notifications]);
    const handleToggleSelect = (id) => {
        setSelectedIds(prev => {
            if (prev.includes(id)) {
                return prev.filter(i => i !== id);
            }
            else {
                return [...prev, id];
            }
        });
    };
    const handleSelectAll = () => {
        if (selectedIds.length === filteredNotifications.length) {
            setSelectedIds([]);
        }
        else {
            setSelectedIds(filteredNotifications.map(n => n.id));
        }
    };
    const handleMarkSelectedAsRead = async () => {
        if (selectedIds.length === 0) {
            toast.error('No notifications selected');
            return;
        }
        try {
            await onMarkAsRead(selectedIds);
            setSelectedIds([]);
            toast.success(`Marked ${selectedIds.length} notification(s) as read`);
        }
        catch (error) {
            toast.error('Failed to mark notifications as read');
        }
    };
    const handleDeleteSelected = async () => {
        if (selectedIds.length === 0) {
            toast.error('No notifications selected');
            return;
        }
        if (!confirm(`Delete ${selectedIds.length} notification(s)?`)) {
            return;
        }
        try {
            await onDelete(selectedIds);
            setSelectedIds([]);
            toast.success(`Deleted ${selectedIds.length} notification(s)`);
        }
        catch (error) {
            toast.error('Failed to delete notifications');
        }
    };
    const handleNotificationClick = async (notification) => {
        // Mark as read if unread
        if (!notification.is_read) {
            await onMarkAsRead([notification.id]);
        }
        // Navigate to link if provided
        if (onNotificationClick) {
            onNotificationClick(notification);
        }
    };
    return (_jsxs("div", { className: "space-y-4", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsxs("div", { className: "relative", children: [_jsx(Bell, { className: "h-6 w-6 text-gray-700" }), unreadCount > 0 && (_jsx("span", { className: "absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center", children: unreadCount > 9 ? '9+' : unreadCount }))] }), _jsxs("div", { children: [_jsx("h2", { className: "text-xl font-bold text-gray-900", children: "Notifications" }), _jsxs("p", { className: "text-sm text-gray-600", children: [unreadCount, " unread \u2022 ", notifications.length, " total"] })] })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Button, { size: "sm", variant: "secondary", onClick: () => setShowSettings(!showSettings), children: _jsx(Settings, { className: "h-4 w-4" }) }), _jsxs(Button, { size: "sm", variant: "primary", onClick: onMarkAllAsRead, disabled: unreadCount === 0, children: [_jsx(CheckCheck, { className: "h-4 w-4 mr-2" }), "Mark All Read"] })] })] }), _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { className: "flex items-center gap-2 bg-gray-100 p-1 rounded-lg", children: [_jsxs("button", { onClick: () => setFilterType('all'), className: `px-4 py-1.5 text-sm font-medium rounded-md transition-all ${filterType === 'all'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'}`, children: ["All (", notifications.length, ")"] }), _jsxs("button", { onClick: () => setFilterType('unread'), className: `px-4 py-1.5 text-sm font-medium rounded-md transition-all ${filterType === 'unread'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'}`, children: ["Unread (", unreadCount, ")"] }), _jsxs("button", { onClick: () => setFilterType('read'), className: `px-4 py-1.5 text-sm font-medium rounded-md transition-all ${filterType === 'read'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'}`, children: ["Read (", notifications.length - unreadCount, ")"] })] }), selectedIds.length > 0 && (_jsxs("div", { className: "flex items-center gap-2", children: [_jsxs("span", { className: "text-sm text-gray-600", children: [selectedIds.length, " selected"] }), _jsxs(Button, { size: "sm", variant: "secondary", onClick: handleMarkSelectedAsRead, children: [_jsx(Check, { className: "h-3 w-3 mr-1" }), "Mark Read"] }), _jsxs(Button, { size: "sm", variant: "danger", onClick: handleDeleteSelected, children: [_jsx(Trash2, { className: "h-3 w-3 mr-1" }), "Delete"] })] }))] }), filteredNotifications.length > 0 && (_jsxs("div", { className: "flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-lg", children: [_jsx("input", { type: "checkbox", checked: selectedIds.length === filteredNotifications.length, onChange: handleSelectAll, className: "h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" }), _jsxs("span", { className: "text-sm text-gray-700 font-medium", children: ["Select all ", filteredNotifications.length, " notification(s)"] })] })), _jsx("div", { className: "space-y-2", children: filteredNotifications.length === 0 ? (_jsx(Card, { children: _jsxs(CardContent, { className: "p-12 text-center", children: [_jsx(Bell, { className: "h-16 w-16 mx-auto text-gray-300 mb-4" }), _jsx("h3", { className: "text-lg font-medium text-gray-900 mb-2", children: filterType === 'unread' ? 'No unread notifications' : 'No notifications' }), _jsx("p", { className: "text-sm text-gray-500", children: filterType === 'unread'
                                    ? "You're all caught up!"
                                    : 'Notifications will appear here' })] }) })) : (filteredNotifications.map((notification) => {
                    const Icon = NOTIFICATION_ICONS[notification.type];
                    const iconColor = NOTIFICATION_COLORS[notification.type];
                    const borderColor = PRIORITY_COLORS[notification.priority];
                    const isSelected = selectedIds.includes(notification.id);
                    return (_jsx(Card, { className: `transition-all cursor-pointer border-l-4 ${borderColor} ${!notification.is_read ? 'bg-blue-50' : 'bg-white'} ${isSelected ? 'ring-2 ring-blue-500' : ''}`, children: _jsx(CardContent, { className: "p-4", children: _jsxs("div", { className: "flex items-start gap-3", children: [_jsx("input", { type: "checkbox", checked: isSelected, onChange: () => handleToggleSelect(notification.id), onClick: (e) => e.stopPropagation(), className: "mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" }), _jsx("div", { className: `p-2 rounded-lg ${iconColor} flex-shrink-0`, children: _jsx(Icon, { className: "h-5 w-5" }) }), _jsxs("div", { className: "flex-1 min-w-0", onClick: () => handleNotificationClick(notification), children: [_jsxs("div", { className: "flex items-start justify-between gap-2 mb-1", children: [_jsx("h4", { className: `text-sm font-medium ${!notification.is_read ? 'text-gray-900' : 'text-gray-700'}`, children: notification.title }), !notification.is_read && (_jsx("span", { className: "h-2 w-2 bg-blue-600 rounded-full flex-shrink-0 mt-1.5" }))] }), _jsx("p", { className: `text-sm ${!notification.is_read ? 'text-gray-700' : 'text-gray-600'}`, children: notification.message }), notification.actor_name && (_jsxs("p", { className: "text-xs text-gray-500 mt-1", children: ["by ", notification.actor_name] })), _jsxs("div", { className: "flex items-center gap-3 mt-2", children: [_jsx("span", { className: "text-xs text-gray-500", children: formatDistanceToNow(new Date(notification.created_at), { addSuffix: true }) }), notification.priority === 'urgent' && (_jsx("span", { className: "text-xs font-semibold text-red-600 bg-red-100 px-2 py-0.5 rounded", children: "URGENT" }))] })] }), _jsxs("div", { className: "flex items-center gap-1 flex-shrink-0", children: [!notification.is_read && (_jsx("button", { onClick: (e) => {
                                                    e.stopPropagation();
                                                    onMarkAsRead([notification.id]);
                                                }, className: "p-1.5 text-blue-600 hover:bg-blue-100 rounded transition-colors", title: "Mark as read", children: _jsx(Check, { className: "h-4 w-4" }) })), _jsx("button", { onClick: (e) => {
                                                    e.stopPropagation();
                                                    onDelete([notification.id]);
                                                }, className: "p-1.5 text-red-600 hover:bg-red-100 rounded transition-colors", title: "Delete", children: _jsx(X, { className: "h-4 w-4" }) })] })] }) }) }, notification.id));
                })) }), showSettings && (_jsx(Card, { children: _jsxs(CardContent, { className: "p-6", children: [_jsxs("div", { className: "flex items-center justify-between mb-4", children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: "Notification Settings" }), _jsx("button", { onClick: () => setShowSettings(false), className: "text-gray-400 hover:text-gray-600", children: _jsx(X, { className: "h-5 w-5" }) })] }), _jsxs("div", { className: "space-y-4", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Email Notifications" }), _jsx("p", { className: "text-sm text-gray-600", children: "Receive notifications via email" })] }), _jsx("input", { type: "checkbox", defaultChecked: true, className: "h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" })] }), _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Task Assignments" }), _jsx("p", { className: "text-sm text-gray-600", children: "When a task is assigned to you" })] }), _jsx("input", { type: "checkbox", defaultChecked: true, className: "h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" })] }), _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Deadline Reminders" }), _jsx("p", { className: "text-sm text-gray-600", children: "Approaching task deadlines" })] }), _jsx("input", { type: "checkbox", defaultChecked: true, className: "h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" })] }), _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Comments & Mentions" }), _jsx("p", { className: "text-sm text-gray-600", children: "When someone mentions you" })] }), _jsx("input", { type: "checkbox", defaultChecked: true, className: "h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" })] }), _jsx("div", { className: "pt-4 border-t border-gray-200", children: _jsx(Button, { variant: "primary", className: "w-full", children: "Save Preferences" }) })] })] }) }))] }));
};
