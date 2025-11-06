import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Plus, Search } from 'lucide-react';
import { useGetTransactions } from '@/api/queries/useTransactions';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { Card } from '@/components/shared/Card';
import { Select } from '@/components/shared/Select';
import { PageLoader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { formatDate } from '@/utils/formatDate';
import { cn } from '@/utils/cn';
/**
 * TransactionsList Component (Mobile-First Redesign)
 *
 * Responsive transactions list page with mobile-first design:
 * - Mobile (<768px): Vertical header, full-width filters, 2-column detail grid
 * - Desktop (>=768px): Horizontal header, multi-column filters, 6-column grid
 *
 * Features:
 * - Search by address, city, state, or zip
 * - Filter by transaction status
 * - Touch-friendly transaction cards
 * - Responsive detail grids
 * - Click card to view transaction details
 */
const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'prospect', label: 'Prospect' },
    { value: 'listing_active', label: 'Listing Active' },
    { value: 'under_agreement', label: 'Under Agreement' },
    { value: 'closed', label: 'Closed' },
    { value: 'cancelled', label: 'Cancelled' },
];
// Property type filter - not yet implemented
// const propertyTypeOptions = [
//   { value: '', label: 'All Types' },
//   { value: 'SFH', label: 'Single Family Home' },
//   { value: 'Condo', label: 'Condo' },
//   { value: 'Multifamily', label: 'Multifamily' },
//   { value: 'Land', label: 'Land' },
//   { value: 'Commercial', label: 'Commercial' },
// ];
export const TransactionsList = () => {
    const navigate = useNavigate();
    const [statusFilter, setStatusFilter] = useState('');
    const [searchQuery, setSearchQuery] = useState('');
    const { data, isLoading } = useGetTransactions({
        status: statusFilter || undefined,
        per_page: 50,
    });
    const transactions = data?.data || [];
    // Filter by search query - memoized for performance
    const filteredTransactions = useMemo(() => {
        if (!searchQuery)
            return transactions;
        const query = searchQuery.toLowerCase();
        return transactions.filter((t) => t.property_address?.toLowerCase().includes(query) ||
            t.property_city?.toLowerCase().includes(query) ||
            t.property_state?.toLowerCase().includes(query) ||
            t.property_zip?.toLowerCase().includes(query));
    }, [transactions, searchQuery]);
    const getStatusBadge = (status) => {
        const variants = {
            prospect: 'default',
            listing_active: 'info',
            under_agreement: 'warning',
            closed: 'success',
            cancelled: 'danger',
        };
        return _jsx(Badge, { variant: variants[status], size: "md", children: status.replace('_', ' ') });
    };
    if (isLoading) {
        return _jsx(PageLoader, {});
    }
    return (_jsxs("div", { className: "space-y-5 md:space-y-6", children: [_jsxs("div", { className: cn('flex flex-col space-y-4', 'md:flex-row md:items-center md:justify-between md:space-y-0'), children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl md:text-3xl font-bold text-gray-900", children: "Transactions" }), _jsx("p", { className: "text-sm md:text-base text-gray-500 mt-1", children: "Manage all your real estate transactions" })] }), _jsx(Link, { to: "/transactions/new", className: "w-full md:w-auto", children: _jsxs(Button, { variant: "primary", size: "lg", className: "w-full md:w-auto", children: [_jsx(Plus, { className: "h-5 w-5 mr-2" }), "New Transaction"] }) })] }), _jsx(Card, { className: "p-5 md:p-4", children: _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-3 gap-4", children: [_jsxs("div", { className: "relative", children: [_jsx(Search, { className: cn('absolute left-3 top-1/2 -translate-y-1/2 text-gray-400', 'h-5 w-5 md:h-5 md:w-5') }), _jsx("input", { type: "text", placeholder: "Search by address or city...", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), className: cn('w-full pl-10 pr-4 border border-gray-300 rounded-lg', 'focus:outline-none focus:ring-2 focus:ring-primary-500', 'text-base md:text-sm', 
                                    // Touch-friendly height on mobile (48px)
                                    'py-3 md:py-2') })] }), _jsx(Select, { value: statusFilter, onChange: (e) => setStatusFilter(e.target.value), options: statusOptions })] }) }), filteredTransactions.length === 0 ? (_jsx(Card, { children: _jsx(EmptyState, { title: "No transactions found", description: "Get started by creating your first transaction", action: {
                        label: 'New Transaction',
                        onClick: () => navigate('/transactions/new'),
                    } }) })) : (_jsx("div", { className: "grid grid-cols-1 gap-4", children: filteredTransactions.map((transaction) => (_jsx("div", { className: "cursor-pointer", onClick: () => navigate(`/transactions/${transaction.transaction_id}`), children: _jsx(Card, { className: cn('hover:shadow-md transition-shadow', 
                        // Touch-friendly padding
                        'p-5 md:p-6'), children: _jsx("div", { className: "flex items-start justify-between", children: _jsxs("div", { className: "flex-1", children: [_jsxs("div", { className: "flex items-start gap-3", children: [_jsxs("div", { className: "flex-1", children: [_jsx("h3", { className: "text-lg md:text-xl font-semibold text-gray-900", children: transaction.property_address }), _jsxs("p", { className: "text-sm md:text-base text-gray-500 mt-1", children: [transaction.property_city, ", ", transaction.property_state, " ", transaction.property_zip] })] }), getStatusBadge(transaction.status)] }), _jsxs("div", { className: cn('grid gap-4', 'mt-5 md:mt-4', 'grid-cols-2 md:grid-cols-4 lg:grid-cols-6'), children: [_jsxs("div", { children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500", children: "Property Type" }), _jsx("p", { className: "text-sm md:text-sm font-medium text-gray-900 mt-1", children: transaction.property_type })] }), _jsxs("div", { children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500", children: "Offer Accepted" }), _jsx("p", { className: "text-sm md:text-sm font-medium text-gray-900 mt-1", children: formatDate(transaction.offer_accepted_date) || _jsx("span", { className: "text-gray-400", children: "\u2014" }) })] }), _jsxs("div", { children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500", children: "P&S Date" }), _jsx("p", { className: "text-sm md:text-sm font-medium text-gray-900 mt-1", children: formatDate(transaction.ps_agreement_date) || _jsx("span", { className: "text-gray-400", children: "\u2014" }) })] }), _jsxs("div", { children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500", children: "Loan Commitment" }), _jsx("p", { className: "text-sm md:text-sm font-medium text-gray-900 mt-1", children: formatDate(transaction.loan_commitment_date) || _jsx("span", { className: "text-gray-400", children: "\u2014" }) })] }), _jsxs("div", { children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500", children: "Closing Date" }), _jsx("p", { className: "text-sm md:text-sm font-medium text-gray-900 mt-1", children: formatDate(transaction.closing_date) || _jsx("span", { className: "text-gray-400", children: "\u2014" }) })] }), _jsxs("div", { children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500", children: "Tasks" }), _jsxs("p", { className: "text-sm md:text-sm font-medium text-gray-900 mt-1", children: [transaction.task_summary?.completed || 0, " / ", transaction.task_summary?.total || 0] })] })] })] }) }) }) }, transaction.transaction_id))) }))] }));
};
