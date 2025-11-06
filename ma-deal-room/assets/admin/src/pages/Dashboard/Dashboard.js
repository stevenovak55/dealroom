import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Link } from 'react-router-dom';
import { useGetTransactions } from '@/api/queries/useTransactions';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { PageLoader } from '@/components/shared/Loader';
import { formatDate, formatCurrency } from '@/utils/formatDate';
import { Plus, Home, TrendingUp, CheckCircle, Clock } from 'lucide-react';
import { useIsMobile } from '@/hooks/useMediaQuery';
import { cn } from '@/utils/cn';
/**
 * Dashboard Component (Mobile-First Redesign)
 *
 * Responsive dashboard with mobile-first design:
 * - Mobile (<768px): Vertical layout with card-based transaction list
 * - Desktop (>=768px): Grid layout with table view
 *
 * Features:
 * - Statistics cards (4 cards)
 * - Recent transactions list
 * - Touch-friendly actions
 * - Responsive grid layout
 */
const Dashboard = () => {
    const { data: transactionsData, isLoading } = useGetTransactions({ page: 1, per_page: 10 });
    const isMobile = useIsMobile();
    if (isLoading) {
        return _jsx(PageLoader, {});
    }
    const transactions = transactionsData?.data || [];
    const totalTransactions = transactionsData?.pagination.total || 0;
    // Calculate statistics
    const activeTransactions = transactions.filter((t) => t.status === 'under_agreement' || t.status === 'listing_active').length;
    const closedTransactions = transactions.filter((t) => t.status === 'closed').length;
    const totalTasks = transactions.reduce((sum, t) => sum + (t.task_summary?.total || 0), 0);
    const completedTasks = transactions.reduce((sum, t) => sum + (t.task_summary?.completed || 0), 0);
    const getStatusBadge = (status) => {
        switch (status) {
            case 'closed':
                return _jsx(Badge, { variant: "success", size: "md", children: "Closed" });
            case 'under_agreement':
                return _jsx(Badge, { variant: "info", size: "md", children: "Under Agreement" });
            case 'listing_active':
                return _jsx(Badge, { variant: "warning", size: "md", children: "Active Listing" });
            case 'prospect':
                return _jsx(Badge, { variant: "default", size: "md", children: "Prospect" });
            case 'cancelled':
                return _jsx(Badge, { variant: "danger", size: "md", children: "Cancelled" });
            default:
                return _jsx(Badge, { variant: "default", size: "md", children: status });
        }
    };
    return (_jsxs("div", { className: "space-y-5 md:space-y-6", children: [_jsxs("div", { className: cn('flex flex-col space-y-4', 'md:flex-row md:items-center md:justify-between md:space-y-0'), children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl md:text-3xl font-bold text-gray-900", children: "Dashboard" }), _jsx("p", { className: "text-sm md:text-base text-gray-500 mt-1", children: "Welcome to MA Deal Room" })] }), _jsx(Link, { to: "/transactions/new", className: "w-full md:w-auto", children: _jsxs(Button, { size: "lg", className: "w-full md:w-auto", children: [_jsx(Plus, { className: "h-5 w-5 mr-2" }), "New Transaction"] }) })] }), _jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 md:gap-6", children: [_jsx(Card, { children: _jsx(CardContent, { className: cn('pt-5 md:pt-6', 'pb-5 md:pb-6'), children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-base font-medium text-gray-600", children: "Total Transactions" }), _jsx("p", { className: "text-2xl md:text-3xl font-bold text-gray-900 mt-2", children: totalTransactions })] }), _jsx("div", { className: cn('h-12 w-12 md:h-14 md:w-14', 'bg-blue-100 rounded-lg flex items-center justify-center'), children: _jsx(Home, { className: "h-6 w-6 md:h-7 md:w-7 text-blue-600" }) })] }) }) }), _jsx(Card, { children: _jsx(CardContent, { className: cn('pt-5 md:pt-6', 'pb-5 md:pb-6'), children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-base font-medium text-gray-600", children: "Active" }), _jsx("p", { className: "text-2xl md:text-3xl font-bold text-gray-900 mt-2", children: activeTransactions })] }), _jsx("div", { className: cn('h-12 w-12 md:h-14 md:w-14', 'bg-green-100 rounded-lg flex items-center justify-center'), children: _jsx(TrendingUp, { className: "h-6 w-6 md:h-7 md:w-7 text-green-600" }) })] }) }) }), _jsx(Card, { children: _jsx(CardContent, { className: cn('pt-5 md:pt-6', 'pb-5 md:pb-6'), children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-base font-medium text-gray-600", children: "Closed" }), _jsx("p", { className: "text-2xl md:text-3xl font-bold text-gray-900 mt-2", children: closedTransactions })] }), _jsx("div", { className: cn('h-12 w-12 md:h-14 md:w-14', 'bg-purple-100 rounded-lg flex items-center justify-center'), children: _jsx(CheckCircle, { className: "h-6 w-6 md:h-7 md:w-7 text-purple-600" }) })] }) }) }), _jsx(Card, { children: _jsx(CardContent, { className: cn('pt-5 md:pt-6', 'pb-5 md:pb-6'), children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-base font-medium text-gray-600", children: "Tasks Progress" }), _jsxs("p", { className: "text-2xl md:text-3xl font-bold text-gray-900 mt-2", children: [completedTasks, "/", totalTasks] })] }), _jsx("div", { className: cn('h-12 w-12 md:h-14 md:w-14', 'bg-orange-100 rounded-lg flex items-center justify-center'), children: _jsx(Clock, { className: "h-6 w-6 md:h-7 md:w-7 text-orange-600" }) })] }) }) })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs("div", { className: cn('flex flex-col space-y-3', 'md:flex-row md:items-center md:justify-between md:space-y-0'), children: [_jsx(CardTitle, { className: "text-lg md:text-xl", children: "Recent Transactions" }), _jsx(Link, { to: "/transactions", className: "w-full md:w-auto", children: _jsx(Button, { variant: "ghost", size: isMobile ? 'md' : 'sm', className: "w-full md:w-auto", children: "View All" }) })] }) }), _jsx(CardContent, { children: transactions.length === 0 ? (_jsxs("div", { className: "text-center py-12", children: [_jsx("p", { className: "text-sm md:text-base text-gray-500 mb-4", children: "No transactions yet" }), _jsx(Link, { to: "/transactions/new", children: _jsxs(Button, { size: "lg", children: [_jsx(Plus, { className: "h-5 w-5 mr-2" }), "Create Your First Transaction"] }) })] })) : isMobile ? (
                        // Mobile: Card view
                        _jsx("div", { className: "space-y-3", children: transactions.map((transaction) => (_jsx(Card, { interactive: true, children: _jsx(CardContent, { className: "p-4", children: _jsxs("div", { className: "space-y-3", children: [_jsxs("div", { children: [_jsx("div", { className: "font-medium text-gray-900", children: transaction.property_address }), _jsxs("div", { className: "text-sm text-gray-500", children: [transaction.property_city, ", ", transaction.property_state] })] }), _jsxs("div", { className: "flex items-center justify-between", children: [getStatusBadge(transaction.status), _jsx("span", { className: "text-lg font-semibold text-gray-900", children: transaction.sale_price ? formatCurrency(transaction.sale_price) : '-' })] }), _jsxs("div", { className: "grid grid-cols-2 gap-3 text-sm", children: [_jsxs("div", { children: [_jsx("span", { className: "text-gray-500", children: "Closing:" }), _jsx("div", { className: "font-medium text-gray-900", children: transaction.closing_date ? formatDate(transaction.closing_date) : '-' })] }), _jsxs("div", { children: [_jsx("span", { className: "text-gray-500", children: "Tasks:" }), _jsxs("div", { className: "font-medium text-gray-900", children: [transaction.task_summary?.completed || 0, "/", transaction.task_summary?.total || 0] })] })] }), _jsx(Link, { to: `/transactions/${transaction.transaction_id}`, className: "block", children: _jsx(Button, { variant: "ghost", size: "sm", className: "w-full", children: "View Details" }) })] }) }) }, transaction.transaction_id))) })) : (
                        // Desktop: Table view
                        _jsx("div", { className: "overflow-x-auto", children: _jsxs("table", { className: "min-w-full divide-y divide-gray-200", children: [_jsx("thead", { children: _jsxs("tr", { children: [_jsx("th", { className: "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider", children: "Property" }), _jsx("th", { className: "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider", children: "Status" }), _jsx("th", { className: "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider", children: "Price" }), _jsx("th", { className: "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider", children: "Closing Date" }), _jsx("th", { className: "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider", children: "Tasks" }), _jsx("th", { className: "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider", children: "Actions" })] }) }), _jsx("tbody", { className: "bg-white divide-y divide-gray-200", children: transactions.map((transaction) => (_jsxs("tr", { className: "hover:bg-gray-50", children: [_jsxs("td", { className: "px-6 py-4 whitespace-nowrap", children: [_jsx("div", { className: "text-sm font-medium text-gray-900", children: transaction.property_address }), _jsxs("div", { className: "text-sm text-gray-500", children: [transaction.property_city, ", ", transaction.property_state] })] }), _jsx("td", { className: "px-6 py-4 whitespace-nowrap", children: getStatusBadge(transaction.status) }), _jsx("td", { className: "px-6 py-4 whitespace-nowrap text-sm text-gray-900", children: transaction.sale_price
                                                        ? formatCurrency(transaction.sale_price)
                                                        : '-' }), _jsx("td", { className: "px-6 py-4 whitespace-nowrap text-sm text-gray-500", children: transaction.closing_date
                                                        ? formatDate(transaction.closing_date)
                                                        : '-' }), _jsx("td", { className: "px-6 py-4 whitespace-nowrap", children: _jsxs("div", { className: "text-sm text-gray-900", children: [transaction.task_summary?.completed || 0, "/", transaction.task_summary?.total || 0] }) }), _jsx("td", { className: "px-6 py-4 whitespace-nowrap text-right text-sm font-medium", children: _jsx(Link, { to: `/transactions/${transaction.transaction_id}`, children: _jsx(Button, { variant: "ghost", size: "sm", children: "View" }) }) })] }, transaction.transaction_id))) })] }) })) })] })] }));
};
export default Dashboard;
