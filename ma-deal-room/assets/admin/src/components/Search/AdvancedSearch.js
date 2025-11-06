import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Search, Filter, X, Save, ChevronDown } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card, CardContent } from '@/components/shared/Card';
const FILTER_FIELDS = [
    { value: 'status', label: 'Status', type: 'select', options: ['prospect', 'listing_active', 'under_agreement', 'closed', 'cancelled'] },
    { value: 'property_type', label: 'Property Type', type: 'select', options: ['SFH', 'Condo', 'Multifamily', 'Land', 'Commercial'] },
    { value: 'property_city', label: 'City', type: 'text' },
    { value: 'property_state', label: 'State', type: 'text' },
    { value: 'property_zip', label: 'ZIP Code', type: 'text' },
    { value: 'sale_price', label: 'Sale Price', type: 'number' },
    { value: 'listing_date', label: 'Listing Date', type: 'date' },
    { value: 'closing_date', label: 'Closing Date', type: 'date' },
    { value: 'assigned_agent_id', label: 'Agent', type: 'number' },
];
const OPERATORS = {
    text: [
        { value: 'contains', label: 'Contains' },
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Does not equal' },
    ],
    number: [
        { value: 'equals', label: 'Equals' },
        { value: 'greater_than', label: 'Greater than' },
        { value: 'less_than', label: 'Less than' },
        { value: 'between', label: 'Between' },
    ],
    date: [
        { value: 'equals', label: 'On' },
        { value: 'greater_than', label: 'After' },
        { value: 'less_than', label: 'Before' },
        { value: 'between', label: 'Between' },
    ],
    select: [
        { value: 'equals', label: 'Is' },
        { value: 'not_equals', label: 'Is not' },
        { value: 'in', label: 'Is one of' },
    ],
};
export const AdvancedSearch = ({ onSearch, onSaveSearch, savedSearches = [], }) => {
    const [isExpanded, setIsExpanded] = useState(false);
    const [quickSearch, setQuickSearch] = useState('');
    const [filters, setFilters] = useState([]);
    const [showSaveDialog, setShowSaveDialog] = useState(false);
    const [searchName, setSearchName] = useState('');
    const handleAddFilter = () => {
        setFilters([...filters, { field: 'status', operator: 'equals', value: '' }]);
    };
    const handleRemoveFilter = (index) => {
        setFilters(filters.filter((_, i) => i !== index));
    };
    const handleUpdateFilter = (index, updates) => {
        const newFilters = [...filters];
        newFilters[index] = { ...newFilters[index], ...updates };
        setFilters(newFilters);
    };
    const handleSearch = () => {
        // Include quick search as a filter if present
        const allFilters = [...filters];
        if (quickSearch) {
            allFilters.push({
                field: '_quick_search',
                operator: 'contains',
                value: quickSearch,
                label: `Quick search: "${quickSearch}"`,
            });
        }
        onSearch(allFilters);
    };
    const handleSaveSearch = () => {
        if (!searchName.trim()) {
            alert('Please enter a name for this search');
            return;
        }
        const savedSearch = {
            id: `search-${Date.now()}`,
            name: searchName,
            filters,
            created_at: new Date().toISOString(),
        };
        onSaveSearch?.(savedSearch);
        setShowSaveDialog(false);
        setSearchName('');
    };
    const handleLoadSearch = (search) => {
        setFilters(search.filters);
        setIsExpanded(true);
        onSearch(search.filters);
    };
    const handleClear = () => {
        setFilters([]);
        setQuickSearch('');
        onSearch([]);
    };
    const getFieldType = (fieldName) => {
        return FILTER_FIELDS.find(f => f.value === fieldName)?.type || 'text';
    };
    const getFieldOptions = (fieldName) => {
        return FILTER_FIELDS.find(f => f.value === fieldName)?.options || [];
    };
    return (_jsx(Card, { children: _jsxs(CardContent, { className: "p-4", children: [_jsxs("div", { className: "flex items-center gap-2 mb-4", children: [_jsxs("div", { className: "relative flex-1", children: [_jsx(Search, { className: "absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" }), _jsx("input", { type: "text", placeholder: "Quick search by address, city, or status...", value: quickSearch, onChange: (e) => setQuickSearch(e.target.value), onKeyPress: (e) => e.key === 'Enter' && handleSearch(), className: "w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" })] }), _jsxs(Button, { variant: "secondary", onClick: () => setIsExpanded(!isExpanded), className: "flex items-center gap-2", children: [_jsx(Filter, { className: "h-4 w-4" }), "Advanced", _jsx(ChevronDown, { className: `h-4 w-4 transition-transform ${isExpanded ? 'rotate-180' : ''}` })] }), _jsx(Button, { variant: "primary", onClick: handleSearch, children: "Search" })] }), savedSearches.length > 0 && (_jsxs("div", { className: "mb-4", children: [_jsx("p", { className: "text-sm font-medium text-gray-700 mb-2", children: "Saved Searches" }), _jsx("div", { className: "flex flex-wrap gap-2", children: savedSearches.map((search) => (_jsx("button", { onClick: () => handleLoadSearch(search), className: "px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full hover:bg-blue-100 transition-colors", children: search.name }, search.id))) })] })), isExpanded && (_jsxs("div", { className: "space-y-4 pt-4 border-t border-gray-200", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsx("h4", { className: "font-medium text-gray-900", children: "Advanced Filters" }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Button, { size: "sm", variant: "secondary", onClick: handleClear, children: "Clear All" }), _jsxs(Button, { size: "sm", variant: "secondary", onClick: handleAddFilter, children: [_jsx(Filter, { className: "h-3 w-3 mr-1" }), "Add Filter"] }), filters.length > 0 && onSaveSearch && (_jsxs(Button, { size: "sm", variant: "primary", onClick: () => setShowSaveDialog(true), children: [_jsx(Save, { className: "h-3 w-3 mr-1" }), "Save Search"] }))] })] }), filters.length === 0 ? (_jsxs("div", { className: "text-center py-8 text-gray-500", children: [_jsx(Filter, { className: "h-12 w-12 mx-auto mb-3 text-gray-300" }), _jsx("p", { children: "No filters added" }), _jsx("p", { className: "text-sm mt-1", children: "Click \"Add Filter\" to create custom search criteria" })] })) : (_jsx("div", { className: "space-y-3", children: filters.map((filter, index) => {
                                const fieldType = getFieldType(filter.field);
                                const operators = OPERATORS[fieldType] || OPERATORS.text;
                                const options = getFieldOptions(filter.field);
                                return (_jsxs("div", { className: "flex items-center gap-2 p-3 bg-gray-50 rounded-lg", children: [_jsx("select", { value: filter.field, onChange: (e) => handleUpdateFilter(index, { field: e.target.value, value: '' }), className: "px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm", children: FILTER_FIELDS.map((field) => (_jsx("option", { value: field.value, children: field.label }, field.value))) }), _jsx("select", { value: filter.operator, onChange: (e) => handleUpdateFilter(index, { operator: e.target.value }), className: "px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm", children: operators.map((op) => (_jsx("option", { value: op.value, children: op.label }, op.value))) }), fieldType === 'select' ? (filter.operator === 'in' ? (_jsx("select", { multiple: true, value: Array.isArray(filter.value) ? filter.value : [], onChange: (e) => {
                                                const selected = Array.from(e.target.selectedOptions, option => option.value);
                                                handleUpdateFilter(index, { value: selected });
                                            }, className: "flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm", children: options.map((opt) => (_jsx("option", { value: opt, children: opt }, opt))) })) : (_jsxs("select", { value: filter.value, onChange: (e) => handleUpdateFilter(index, { value: e.target.value }), className: "flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm", children: [_jsx("option", { value: "", children: "Select..." }), options.map((opt) => (_jsx("option", { value: opt, children: opt }, opt)))] }))) : filter.operator === 'between' ? (_jsxs("div", { className: "flex-1 flex items-center gap-2", children: [_jsx("input", { type: fieldType === 'date' ? 'date' : fieldType, value: Array.isArray(filter.value) ? filter.value[0] : '', onChange: (e) => {
                                                        const current = Array.isArray(filter.value) ? filter.value : ['', ''];
                                                        handleUpdateFilter(index, { value: [e.target.value, current[1]] });
                                                    }, className: "flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm" }), _jsx("span", { className: "text-gray-500", children: "and" }), _jsx("input", { type: fieldType === 'date' ? 'date' : fieldType, value: Array.isArray(filter.value) ? filter.value[1] : '', onChange: (e) => {
                                                        const current = Array.isArray(filter.value) ? filter.value : ['', ''];
                                                        handleUpdateFilter(index, { value: [current[0], e.target.value] });
                                                    }, className: "flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm" })] })) : (_jsx("input", { type: fieldType === 'date' ? 'date' : fieldType, value: filter.value, onChange: (e) => handleUpdateFilter(index, { value: e.target.value }), className: "flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 text-sm", placeholder: `Enter ${FILTER_FIELDS.find(f => f.value === filter.field)?.label.toLowerCase()}...` })), _jsx("button", { onClick: () => handleRemoveFilter(index), className: "p-2 text-red-600 hover:bg-red-50 rounded transition-colors", children: _jsx(X, { className: "h-4 w-4" }) })] }, index));
                            }) }))] })), (filters.length > 0 || quickSearch) && (_jsxs("div", { className: "mt-4 pt-4 border-t border-gray-200", children: [_jsx("p", { className: "text-sm font-medium text-gray-700 mb-2", children: "Active Filters" }), _jsxs("div", { className: "flex flex-wrap gap-2", children: [quickSearch && (_jsxs("div", { className: "px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm flex items-center gap-2", children: [_jsx(Search, { className: "h-3 w-3" }), quickSearch, _jsx("button", { onClick: () => setQuickSearch(''), className: "hover:text-blue-900", children: _jsx(X, { className: "h-3 w-3" }) })] })), filters.map((filter, index) => (_jsxs("div", { className: "px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm flex items-center gap-2", children: [FILTER_FIELDS.find(f => f.value === filter.field)?.label, ":", ' ', filter.operator.replace('_', ' '), " ", Array.isArray(filter.value) ? filter.value.join(', ') : filter.value, _jsx("button", { onClick: () => handleRemoveFilter(index), className: "hover:text-gray-900", children: _jsx(X, { className: "h-3 w-3" }) })] }, index)))] })] })), showSaveDialog && (_jsxs("div", { className: "mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg", children: [_jsx("h4", { className: "font-medium text-gray-900 mb-2", children: "Save this search" }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("input", { type: "text", value: searchName, onChange: (e) => setSearchName(e.target.value), placeholder: "Enter search name...", className: "flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" }), _jsx(Button, { size: "sm", variant: "primary", onClick: handleSaveSearch, children: "Save" }), _jsx(Button, { size: "sm", variant: "secondary", onClick: () => setShowSaveDialog(false), children: "Cancel" })] })] }))] }) }));
};
// Export utility function for applying filters to data
export const applyFilters = (data, filters) => {
    if (filters.length === 0)
        return data;
    return data.filter(item => {
        return filters.every(filter => {
            const value = item[filter.field];
            // Quick search special case
            if (filter.field === '_quick_search') {
                const searchTerm = filter.value.toLowerCase();
                return (item.property_address?.toLowerCase().includes(searchTerm) ||
                    item.property_city?.toLowerCase().includes(searchTerm) ||
                    item.property_state?.toLowerCase().includes(searchTerm) ||
                    item.status?.toLowerCase().includes(searchTerm));
            }
            switch (filter.operator) {
                case 'equals':
                    return value === filter.value;
                case 'not_equals':
                    return value !== filter.value;
                case 'contains':
                    return value?.toString().toLowerCase().includes(filter.value.toLowerCase());
                case 'greater_than':
                    return Number(value) > Number(filter.value);
                case 'less_than':
                    return Number(value) < Number(filter.value);
                case 'between':
                    return Array.isArray(filter.value) &&
                        Number(value) >= Number(filter.value[0]) &&
                        Number(value) <= Number(filter.value[1]);
                case 'in':
                    return Array.isArray(filter.value) && filter.value.includes(value);
                default:
                    return true;
            }
        });
    });
};
