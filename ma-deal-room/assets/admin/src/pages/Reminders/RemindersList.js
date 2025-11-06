import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Bell, Mail, Smartphone, CheckCircle, AlertCircle } from 'lucide-react';
import { useGetUpcomingReminders } from '@/api/queries/useReminders';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { PageLoader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { formatDateTime } from '@/utils/formatDate';
export const RemindersList = () => {
    const { data, isLoading } = useGetUpcomingReminders({ days: 30 });
    const reminders = data?.data || [];
    const getChannelIcon = (channel) => {
        if (channel === 'email')
            return _jsx(Mail, { className: "h-4 w-4" });
        if (channel === 'sms')
            return _jsx(Smartphone, { className: "h-4 w-4" });
        return _jsx(Bell, { className: "h-4 w-4" });
    };
    const getStatusBadge = (status) => {
        const variants = {
            sent: 'success',
            pending: 'warning',
            failed: 'danger',
            cancelled: 'default',
        };
        return _jsx(Badge, { variant: variants[status] || 'default', children: status });
    };
    if (isLoading) {
        return _jsx(PageLoader, {});
    }
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: "Reminders" }), _jsx("p", { className: "text-sm text-gray-500 mt-1", children: "Upcoming reminders for tasks and deadlines" })] }), reminders.length === 0 ? (_jsx(Card, { children: _jsx(EmptyState, { icon: Bell, title: "No upcoming reminders", description: "Reminders will appear here when tasks approach their due dates" }) })) : (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Upcoming Reminders (Next 30 Days)" }) }), _jsx(CardContent, { children: _jsx("div", { className: "space-y-3", children: reminders.map((reminder) => (_jsx("div", { className: "p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors", children: _jsxs("div", { className: "flex items-start justify-between", children: [_jsxs("div", { className: "flex-1", children: [_jsxs("div", { className: "flex items-center gap-2 mb-2", children: [getChannelIcon(reminder.channel), _jsx("h4", { className: "font-medium text-gray-900", children: reminder.task_title || 'Task reminder' })] }), _jsxs("div", { className: "grid grid-cols-2 gap-4 text-sm", children: [_jsxs("div", { children: [_jsx("p", { className: "text-gray-500", children: "Scheduled" }), _jsx("p", { className: "text-gray-900", children: formatDateTime(reminder.scheduled_at) })] }), _jsxs("div", { children: [_jsx("p", { className: "text-gray-500", children: "Recipient" }), _jsx("p", { className: "text-gray-900", children: reminder.recipient_email || reminder.recipient_phone || 'N/A' })] }), _jsxs("div", { children: [_jsx("p", { className: "text-gray-500", children: "Channel" }), _jsx("p", { className: "text-gray-900 capitalize", children: reminder.channel })] }), _jsxs("div", { children: [_jsx("p", { className: "text-gray-500", children: "Recipient Type" }), _jsx("p", { className: "text-gray-900 capitalize", children: reminder.recipient_type })] })] }), reminder.sent_at && (_jsxs("div", { className: "mt-2 flex items-center gap-2 text-sm text-success-600", children: [_jsx(CheckCircle, { className: "h-4 w-4" }), _jsxs("span", { children: ["Sent ", formatDateTime(reminder.sent_at)] })] })), reminder.failure_reason && (_jsxs("div", { className: "mt-2 flex items-center gap-2 text-sm text-danger-600", children: [_jsx(AlertCircle, { className: "h-4 w-4" }), _jsx("span", { children: reminder.failure_reason })] }))] }), getStatusBadge(reminder.status)] }) }, reminder.reminder_id))) }) })] }))] }));
};
