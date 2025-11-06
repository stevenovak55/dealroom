import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Search } from 'lucide-react';
import { Select } from '@/components/shared/Select';
const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'blocked', label: 'Blocked' },
    { value: 'skipped', label: 'Skipped' },
];
const roleOptions = [
    { value: '', label: 'All Roles' },
    { value: 'agent', label: 'Agent' },
    { value: 'seller', label: 'Seller' },
    { value: 'buyer', label: 'Buyer' },
    { value: 'seller_attorney', label: 'Seller Attorney' },
    { value: 'buyer_attorney', label: 'Buyer Attorney' },
    { value: 'vendor', label: 'Vendor' },
];
export const TaskFilters = ({ filters, onFiltersChange }) => {
    // Local state for search input to enable debouncing
    const [searchInput, setSearchInput] = useState(filters.search);
    // Debounce search input
    useEffect(() => {
        const timer = setTimeout(() => {
            if (searchInput !== filters.search) {
                onFiltersChange({ ...filters, search: searchInput });
            }
        }, 300); // 300ms debounce
        return () => clearTimeout(timer);
    }, [searchInput]); // Only re-run when searchInput changes
    // Sync with external filter changes
    useEffect(() => {
        if (filters.search !== searchInput) {
            setSearchInput(filters.search);
        }
    }, [filters.search]);
    const handleFilterChange = (key, value) => {
        onFiltersChange({ ...filters, [key]: value });
    };
    return (_jsxs("div", { className: "grid grid-cols-1 md:grid-cols-3 gap-4", children: [_jsxs("div", { className: "relative", children: [_jsx(Search, { className: "absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" }), _jsx("input", { type: "text", placeholder: "Search tasks...", value: searchInput, onChange: (e) => setSearchInput(e.target.value), className: "w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" })] }), _jsx(Select, { value: filters.status, onChange: (e) => handleFilterChange('status', e.target.value), options: statusOptions }), _jsx(Select, { value: filters.assigneeRole, onChange: (e) => handleFilterChange('assigneeRole', e.target.value), options: roleOptions })] }));
};
