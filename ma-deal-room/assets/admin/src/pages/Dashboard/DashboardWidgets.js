import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { AlertCircle, Calendar } from 'lucide-react';
import { formatDate, formatRelativeTime, isOverdue } from '@/utils/formatDate';
import { Link } from 'react-router-dom';
import { cn } from '@/utils/cn';
export const StatsWidget = ({ title, value, icon, trend }) => {
    return (_jsx(Card, { children: _jsxs(CardContent, { className: cn('flex items-center justify-between', 
            // Responsive padding
            'pt-5 md:pt-6', 'pb-5 md:pb-6'), children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-base font-medium text-gray-600", children: title }), _jsx("p", { className: "text-2xl md:text-3xl font-bold text-gray-900 mt-2", children: value }), trend && (_jsxs("p", { className: "text-xs md:text-sm text-gray-500 mt-1", children: [trend.value > 0 ? '+' : '', trend.value, " ", trend.label] }))] }), _jsx("div", { className: cn('bg-primary-100 rounded-full flex items-center justify-center text-primary-600', 'h-12 w-12 md:h-14 md:w-14'), children: icon })] }) }));
};
export const ActiveTransactionsWidget = ({ transactions, isLoading, }) => {
    if (isLoading) {
        return (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-lg md:text-xl", children: "Active Transactions" }) }), _jsx(CardContent, { children: _jsx("div", { className: "animate-pulse space-y-3 md:space-y-4", children: [1, 2, 3].map((i) => (_jsx("div", { className: "h-16 md:h-20 bg-gray-200 rounded" }, i))) }) })] }));
    }
    return (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs(CardTitle, { className: cn('flex items-center justify-between', 'text-lg md:text-xl'), children: [_jsx("span", { children: "Active Transactions" }), _jsx(Link, { to: "/transactions", className: cn('text-sm md:text-base text-primary-600 hover:text-primary-700', 
                            // Increase touch target
                            'py-1 px-2 -mr-2'), children: "View all" })] }) }), _jsx(CardContent, { children: _jsx("div", { className: "space-y-3 md:space-y-4", children: transactions.slice(0, 5).map((transaction) => (_jsxs(Link, { to: `/transactions/${transaction.transaction_id}`, className: cn('block rounded-lg border border-gray-200', 'hover:border-primary-300 hover:bg-primary-50', 'transition-colors', 
                        // Touch-friendly padding
                        'p-4 md:p-3'), children: [_jsxs("div", { className: "flex items-start justify-between gap-3", children: [_jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("p", { className: "text-base md:text-sm font-medium text-gray-900 truncate", children: transaction.property_address }), _jsxs("p", { className: "text-sm md:text-xs text-gray-500 mt-1", children: [transaction.property_city, ", ", transaction.property_state] })] }), _jsx(Badge, { variant: "info", size: "md", children: transaction.property_type })] }), _jsxs("div", { className: cn('mt-3 md:mt-2', 'flex flex-wrap items-center gap-3 md:gap-4', 'text-xs md:text-xs text-gray-500'), children: [_jsxs("span", { children: ["Closing: ", formatDate(transaction.closing_date)] }), transaction.task_summary && (_jsxs("span", { children: [transaction.task_summary.completed, "/", transaction.task_summary.total, " tasks"] }))] })] }, transaction.transaction_id))) }) })] }));
};
export const OverdueTasksWidget = ({ tasks, isLoading }) => {
    if (isLoading) {
        return (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-lg md:text-xl", children: "Overdue Tasks" }) }), _jsx(CardContent, { children: _jsx("div", { className: "animate-pulse space-y-3 md:space-y-4", children: [1, 2, 3].map((i) => (_jsx("div", { className: "h-14 md:h-12 bg-gray-200 rounded" }, i))) }) })] }));
    }
    const overdueTasks = tasks.filter((task) => isOverdue(task.due_at));
    return (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs(CardTitle, { className: cn('flex items-center gap-2', 'text-lg md:text-xl'), children: [_jsx(AlertCircle, { className: "h-5 w-5 md:h-6 md:w-6 text-danger-500" }), "Overdue Tasks (", overdueTasks.length, ")"] }) }), _jsx(CardContent, { children: overdueTasks.length === 0 ? (_jsx("p", { className: "text-sm md:text-base text-gray-500 text-center py-6 md:py-4", children: "No overdue tasks" })) : (_jsx("div", { className: "space-y-3 md:space-y-2", children: overdueTasks.slice(0, 5).map((task) => (_jsx("div", { className: cn('rounded-lg border border-danger-200 bg-danger-50', 
                        // Touch-friendly padding
                        'p-4 md:p-3'), children: _jsxs("div", { className: "flex items-start justify-between gap-3", children: [_jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("p", { className: "text-base md:text-sm font-medium text-gray-900", children: task.title }), _jsxs("p", { className: "text-sm md:text-xs text-danger-600 mt-1", children: ["Due ", formatRelativeTime(task.due_at)] })] }), _jsx(Badge, { variant: "danger", size: "md", children: "Overdue" })] }) }, task.id))) })) })] }));
};
export const UpcomingDeadlinesWidget = ({ tasks, isLoading }) => {
    if (isLoading) {
        return (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-lg md:text-xl", children: "Upcoming Deadlines" }) }), _jsx(CardContent, { children: _jsx("div", { className: "animate-pulse space-y-3 md:space-y-4", children: [1, 2, 3].map((i) => (_jsx("div", { className: "h-14 md:h-12 bg-gray-200 rounded" }, i))) }) })] }));
    }
    return (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs(CardTitle, { className: cn('flex items-center gap-2', 'text-lg md:text-xl'), children: [_jsx(Calendar, { className: "h-5 w-5 md:h-6 md:w-6 text-warning-500" }), _jsx("span", { className: "hidden sm:inline", children: "Upcoming Deadlines (Next 7 Days)" }), _jsx("span", { className: "sm:hidden", children: "Upcoming (7 Days)" })] }) }), _jsx(CardContent, { children: tasks.length === 0 ? (_jsx("p", { className: "text-sm md:text-base text-gray-500 text-center py-6 md:py-4", children: "No upcoming deadlines" })) : (_jsx("div", { className: "space-y-3 md:space-y-2", children: tasks.slice(0, 5).map((task) => (_jsx("div", { className: cn('rounded-lg border border-gray-200', 'hover:bg-gray-50 transition-colors', 
                        // Touch-friendly padding
                        'p-4 md:p-3'), children: _jsxs("div", { className: "flex items-start justify-between gap-3", children: [_jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("p", { className: "text-base md:text-sm font-medium text-gray-900", children: task.title }), _jsxs("p", { className: "text-sm md:text-xs text-gray-500 mt-1", children: ["Due ", formatDate(task.due_at)] })] }), _jsx(Badge, { variant: "warning", size: "md", children: "Upcoming" })] }) }, task.id))) })) })] }));
};
