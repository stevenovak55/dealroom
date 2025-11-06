import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { Activity, User, FileText, Edit, Trash2, Plus, Download, Search, Calendar } from 'lucide-react';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { format, startOfDay, endOfDay, isWithinInterval } from 'date-fns';
const ACTION_ICONS = {
    create: Plus,
    update: Edit,
    delete: Trash2,
    view: Search,
    export: Download,
    import: Download,
    login: User,
    logout: User,
    assign: User,
    complete: FileText,
    approve: FileText,
    reject: Trash2,
};
const ACTION_COLORS = {
    create: 'bg-green-100 text-green-700',
    update: 'bg-blue-100 text-blue-700',
    delete: 'bg-red-100 text-red-700',
    view: 'bg-gray-100 text-gray-700',
    export: 'bg-purple-100 text-purple-700',
    import: 'bg-purple-100 text-purple-700',
    login: 'bg-indigo-100 text-indigo-700',
    logout: 'bg-gray-100 text-gray-700',
    assign: 'bg-amber-100 text-amber-700',
    complete: 'bg-green-100 text-green-700',
    approve: 'bg-green-100 text-green-700',
    reject: 'bg-red-100 text-red-700',
};
const ACTION_LABELS = {
    create: 'Created',
    update: 'Updated',
    delete: 'Deleted',
    view: 'Viewed',
    export: 'Exported',
    import: 'Imported',
    login: 'Logged In',
    logout: 'Logged Out',
    assign: 'Assigned',
    complete: 'Completed',
    approve: 'Approved',
    reject: 'Rejected',
};
const ENTITY_LABELS = {
    transaction: 'Transaction',
    task: 'Task',
    template: 'Template',
    document: 'Document',
    user: 'User',
    comment: 'Comment',
    report: 'Report',
    setting: 'Setting',
};
export const ActivityAuditLog = ({ entries, onExport, }) => {
    const [searchQuery, setSearchQuery] = useState('');
    const [filterAction, setFilterAction] = useState('all');
    const [filterEntity, setFilterEntity] = useState('all');
    const [filterUser, setFilterUser] = useState('');
    const [dateRange, setDateRange] = useState({
        start: format(startOfDay(new Date()), 'yyyy-MM-dd'),
        end: format(endOfDay(new Date()), 'yyyy-MM-dd'),
    });
    const [expandedEntry, setExpandedEntry] = useState(null);
    // Get unique users
    const uniqueUsers = useMemo(() => {
        const users = new Map();
        entries.forEach(entry => {
            if (!users.has(entry.user_id)) {
                users.set(entry.user_id, {
                    id: entry.user_id,
                    name: entry.user_name,
                    email: entry.user_email,
                });
            }
        });
        return Array.from(users.values());
    }, [entries]);
    // Filter entries
    const filteredEntries = useMemo(() => {
        let filtered = [...entries];
        // Text search
        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(entry => entry.user_name?.toLowerCase().includes(query) ||
                entry.description?.toLowerCase().includes(query) ||
                entry.entity_name?.toLowerCase().includes(query));
        }
        // Action filter
        if (filterAction !== 'all') {
            filtered = filtered.filter(entry => entry.action === filterAction);
        }
        // Entity filter
        if (filterEntity !== 'all') {
            filtered = filtered.filter(entry => entry.entity_type === filterEntity);
        }
        // User filter
        if (filterUser) {
            filtered = filtered.filter(entry => entry.user_id === parseInt(filterUser));
        }
        // Date range filter
        if (dateRange.start && dateRange.end) {
            const start = new Date(dateRange.start);
            const end = new Date(dateRange.end);
            filtered = filtered.filter(entry => isWithinInterval(new Date(entry.created_at), { start, end }));
        }
        // Sort by date (newest first)
        return filtered.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
    }, [entries, searchQuery, filterAction, filterEntity, filterUser, dateRange]);
    // Group entries by date
    const groupedEntries = useMemo(() => {
        const groups = new Map();
        filteredEntries.forEach(entry => {
            const dateKey = format(new Date(entry.created_at), 'yyyy-MM-dd');
            if (!groups.has(dateKey)) {
                groups.set(dateKey, []);
            }
            groups.get(dateKey)?.push(entry);
        });
        return Array.from(groups.entries()).sort((a, b) => b[0].localeCompare(a[0]));
    }, [filteredEntries]);
    const handleToggleExpand = (entryId) => {
        setExpandedEntry(expandedEntry === entryId ? null : entryId);
    };
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx("div", { className: "p-2 bg-blue-100 rounded-lg", children: _jsx(Activity, { className: "h-6 w-6 text-blue-600" }) }), _jsxs("div", { children: [_jsx("h2", { className: "text-2xl font-bold text-gray-900", children: "Activity Audit Log" }), _jsxs("p", { className: "text-sm text-gray-600 mt-1", children: [filteredEntries.length, " of ", entries.length, " activities"] })] })] }), onExport && (_jsxs(Button, { variant: "secondary", onClick: onExport, children: [_jsx(Download, { className: "h-4 w-4 mr-2" }), "Export Log"] }))] }), _jsx(Card, { children: _jsxs(CardContent, { className: "p-6", children: [_jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Search" }), _jsxs("div", { className: "relative", children: [_jsx(Search, { className: "absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" }), _jsx("input", { type: "text", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), placeholder: "Search activities...", className: "w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Action" }), _jsxs("select", { value: filterAction, onChange: (e) => setFilterAction(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500", children: [_jsx("option", { value: "all", children: "All Actions" }), Object.keys(ACTION_LABELS).map(action => (_jsx("option", { value: action, children: ACTION_LABELS[action] }, action)))] })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Entity Type" }), _jsxs("select", { value: filterEntity, onChange: (e) => setFilterEntity(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500", children: [_jsx("option", { value: "all", children: "All Types" }), Object.keys(ENTITY_LABELS).map(entity => (_jsx("option", { value: entity, children: ENTITY_LABELS[entity] }, entity)))] })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "User" }), _jsxs("select", { value: filterUser, onChange: (e) => setFilterUser(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500", children: [_jsx("option", { value: "", children: "All Users" }), uniqueUsers.map(user => (_jsx("option", { value: user.id, children: user.name }, user.id)))] })] })] }), _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4 mt-4", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Start Date" }), _jsx("input", { type: "date", value: dateRange.start, onChange: (e) => setDateRange({ ...dateRange, start: e.target.value }), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "End Date" }), _jsx("input", { type: "date", value: dateRange.end, onChange: (e) => setDateRange({ ...dateRange, end: e.target.value }), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] })] })] }) }), _jsx("div", { className: "space-y-6", children: groupedEntries.length === 0 ? (_jsx(Card, { children: _jsxs(CardContent, { className: "p-12 text-center", children: [_jsx(Activity, { className: "h-16 w-16 mx-auto text-gray-300 mb-4" }), _jsx("h3", { className: "text-lg font-medium text-gray-900 mb-2", children: "No activities found" }), _jsx("p", { className: "text-sm text-gray-500", children: "Try adjusting your filters or search query" })] }) })) : (groupedEntries.map(([date, dateEntries]) => (_jsxs("div", { children: [_jsxs("div", { className: "flex items-center gap-3 mb-4", children: [_jsx(Calendar, { className: "h-5 w-5 text-gray-400" }), _jsx("h3", { className: "text-lg font-semibold text-gray-900", children: format(new Date(date), 'EEEE, MMMM d, yyyy') }), _jsxs("span", { className: "text-sm text-gray-500", children: ["(", dateEntries.length, " ", dateEntries.length === 1 ? 'activity' : 'activities', ")"] })] }), _jsx("div", { className: "space-y-3", children: dateEntries.map((entry) => {
                                const Icon = ACTION_ICONS[entry.action];
                                const color = ACTION_COLORS[entry.action];
                                const isExpanded = expandedEntry === entry.id;
                                return (_jsx(Card, { className: "hover:shadow-md transition-shadow", children: _jsx(CardContent, { className: "p-4", children: _jsxs("div", { className: "flex items-start gap-4", children: [_jsx("div", { className: `p-2 rounded-lg ${color} flex-shrink-0`, children: _jsx(Icon, { className: "h-5 w-5" }) }), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsxs("div", { className: "flex items-start justify-between gap-2 mb-2", children: [_jsxs("div", { className: "flex-1", children: [_jsxs("div", { className: "flex items-center gap-2 flex-wrap", children: [_jsx("span", { className: "font-medium text-gray-900", children: entry.user_name }), _jsx("span", { className: `px-2 py-0.5 text-xs font-medium rounded ${color}`, children: ACTION_LABELS[entry.action] }), _jsx("span", { className: "px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded", children: ENTITY_LABELS[entry.entity_type] })] }), _jsx("p", { className: "text-sm text-gray-700 mt-1", children: entry.description }), entry.entity_name && (_jsxs("p", { className: "text-sm text-gray-600 mt-1", children: [_jsx("span", { className: "font-medium", children: "Target:" }), " ", entry.entity_name] }))] }), _jsx("span", { className: "text-xs text-gray-500 whitespace-nowrap", children: format(new Date(entry.created_at), 'h:mm a') })] }), (entry.changes || entry.metadata || entry.ip_address) && (_jsxs("button", { onClick: () => handleToggleExpand(entry.id), className: "text-sm text-blue-600 hover:text-blue-700 font-medium", children: [isExpanded ? 'Hide' : 'Show', " details"] })), isExpanded && (_jsxs("div", { className: "mt-3 p-3 bg-gray-50 rounded-lg space-y-2", children: [entry.changes && Object.keys(entry.changes).length > 0 && (_jsxs("div", { children: [_jsx("h5", { className: "text-xs font-semibold text-gray-700 mb-2", children: "Changes:" }), Object.entries(entry.changes).map(([field, change]) => (_jsxs("div", { className: "text-xs text-gray-600 mb-1", children: [_jsxs("span", { className: "font-medium", children: [field, ":"] }), ' ', _jsx("span", { className: "line-through text-red-600", children: String(change.old) }), ' → ', _jsx("span", { className: "text-green-600", children: String(change.new) })] }, field)))] })), entry.ip_address && (_jsxs("div", { className: "text-xs text-gray-600", children: [_jsx("span", { className: "font-medium", children: "IP Address:" }), " ", entry.ip_address] })), entry.metadata && Object.keys(entry.metadata).length > 0 && (_jsxs("div", { children: [_jsx("h5", { className: "text-xs font-semibold text-gray-700 mb-2", children: "Metadata:" }), _jsx("pre", { className: "text-xs text-gray-600 overflow-auto", children: JSON.stringify(entry.metadata, null, 2) })] }))] }))] })] }) }) }, entry.id));
                            }) })] }, date)))) })] }));
};
