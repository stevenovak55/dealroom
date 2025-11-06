import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { CheckSquare, X, Trash2, Archive, Tag, UserPlus, Calendar, FileText, Download, Mail, AlertCircle } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card } from '@/components/shared/Card';
import toast from 'react-hot-toast';
const TRANSACTION_ACTIONS = [
    {
        action: 'update_status',
        label: 'Update Status',
        icon: CheckSquare,
        description: 'Change status for selected transactions',
        color: 'bg-blue-500 hover:bg-blue-600',
        requiresParams: true,
    },
    {
        action: 'assign_agent',
        label: 'Assign Agent',
        icon: UserPlus,
        description: 'Assign agent to selected transactions',
        color: 'bg-purple-500 hover:bg-purple-600',
        requiresParams: true,
    },
    {
        action: 'add_tags',
        label: 'Add Tags',
        icon: Tag,
        description: 'Add tags to selected transactions',
        color: 'bg-green-500 hover:bg-green-600',
        requiresParams: true,
    },
    {
        action: 'update_date',
        label: 'Update Date',
        icon: Calendar,
        description: 'Set closing date for selected transactions',
        color: 'bg-amber-500 hover:bg-amber-600',
        requiresParams: true,
    },
    {
        action: 'bulk_email',
        label: 'Send Email',
        icon: Mail,
        description: 'Send email to all parties in selected transactions',
        color: 'bg-indigo-500 hover:bg-indigo-600',
        requiresParams: true,
    },
    {
        action: 'generate_report',
        label: 'Generate Report',
        icon: FileText,
        description: 'Create report for selected transactions',
        color: 'bg-cyan-500 hover:bg-cyan-600',
        requiresParams: true,
    },
    {
        action: 'export',
        label: 'Export Data',
        icon: Download,
        description: 'Export selected transactions to CSV/Excel',
        color: 'bg-teal-500 hover:bg-teal-600',
    },
    {
        action: 'archive',
        label: 'Archive',
        icon: Archive,
        description: 'Archive selected transactions',
        color: 'bg-gray-500 hover:bg-gray-600',
        confirmMessage: 'Are you sure you want to archive {count} transactions?',
    },
    {
        action: 'delete',
        label: 'Delete',
        icon: Trash2,
        description: 'Permanently delete selected transactions',
        color: 'bg-red-500 hover:bg-red-600',
        confirmMessage: 'Are you sure you want to permanently delete {count} transactions? This cannot be undone.',
        dangerous: true,
    },
];
const TASK_ACTIONS = [
    {
        action: 'update_status',
        label: 'Update Status',
        icon: CheckSquare,
        description: 'Change status for selected tasks',
        color: 'bg-blue-500 hover:bg-blue-600',
        requiresParams: true,
    },
    {
        action: 'assign_agent',
        label: 'Reassign Tasks',
        icon: UserPlus,
        description: 'Reassign selected tasks to different owner',
        color: 'bg-purple-500 hover:bg-purple-600',
        requiresParams: true,
    },
    {
        action: 'update_date',
        label: 'Update Due Date',
        icon: Calendar,
        description: 'Set due date for selected tasks',
        color: 'bg-amber-500 hover:bg-amber-600',
        requiresParams: true,
    },
    {
        action: 'delete',
        label: 'Delete',
        icon: Trash2,
        description: 'Permanently delete selected tasks',
        color: 'bg-red-500 hover:bg-red-600',
        confirmMessage: 'Are you sure you want to permanently delete {count} tasks? This cannot be undone.',
        dangerous: true,
    },
];
export const BulkActionsToolbar = ({ selectedItems, itemType, onClearSelection, onBulkAction, }) => {
    const [showActionMenu, setShowActionMenu] = useState(false);
    const [selectedAction, setSelectedAction] = useState(null);
    const [actionParams, setActionParams] = useState({});
    const actions = itemType === 'transactions' ? TRANSACTION_ACTIONS : TASK_ACTIONS;
    const handleActionClick = async (action) => {
        if (action.requiresParams) {
            setSelectedAction(action);
            setShowActionMenu(false);
        }
        else {
            await executeAction(action);
        }
    };
    const executeAction = async (action, params) => {
        // Show confirmation for dangerous actions
        if (action.confirmMessage) {
            const message = action.confirmMessage.replace('{count}', selectedItems.length.toString());
            if (!confirm(message)) {
                return;
            }
        }
        try {
            await onBulkAction(action.action, params);
            toast.success(`${action.label} completed successfully for ${selectedItems.length} items`);
            setSelectedAction(null);
            setActionParams({});
            onClearSelection();
        }
        catch (error) {
            console.error('Bulk action failed:', error);
            toast.error(error?.message || `Failed to ${action.label.toLowerCase()}`);
        }
    };
    const handleSubmitParams = async () => {
        if (!selectedAction)
            return;
        await executeAction(selectedAction, actionParams);
    };
    if (selectedItems.length === 0) {
        return null;
    }
    return (_jsxs(_Fragment, { children: [_jsxs(Card, { className: "fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 shadow-2xl", children: [_jsx("div", { className: "px-6 py-4", children: _jsxs("div", { className: "flex items-center gap-4", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx("div", { className: "h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center", children: _jsx(CheckSquare, { className: "h-5 w-5 text-blue-600" }) }), _jsxs("div", { children: [_jsxs("p", { className: "text-sm font-semibold text-gray-900", children: [selectedItems.length, " ", itemType === 'transactions' ? 'transaction' : 'task', selectedItems.length !== 1 ? 's' : '', " selected"] }), _jsx("button", { onClick: onClearSelection, className: "text-xs text-blue-600 hover:text-blue-700 font-medium", children: "Clear selection" })] })] }), _jsx("div", { className: "h-10 w-px bg-gray-200" }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Button, { size: "sm", variant: "secondary", onClick: () => setShowActionMenu(!showActionMenu), children: "Bulk Actions" }), _jsx("button", { onClick: onClearSelection, className: "p-2 text-gray-400 hover:text-gray-600 rounded transition-colors", title: "Clear selection", children: _jsx(X, { className: "h-4 w-4" }) })] })] }) }), showActionMenu && (_jsx("div", { className: "border-t border-gray-200 px-6 py-4", children: _jsx("div", { className: "grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2", children: actions.map((action) => (_jsxs("button", { onClick: () => handleActionClick(action), className: `p-3 rounded-lg text-white text-left transition-all ${action.color} ${action.dangerous ? 'ring-2 ring-red-300' : ''}`, children: [_jsx(action.icon, { className: "h-5 w-5 mb-2" }), _jsx("p", { className: "text-sm font-medium", children: action.label }), _jsx("p", { className: "text-xs opacity-90 mt-1", children: action.description })] }, action.action))) }) }))] }), selectedAction && (_jsx("div", { className: "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4", children: _jsx(Card, { className: "max-w-lg w-full", children: _jsxs("div", { className: "p-6", children: [_jsxs("div", { className: "flex items-center justify-between mb-4", children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: selectedAction.label }), _jsx("button", { onClick: () => {
                                            setSelectedAction(null);
                                            setActionParams({});
                                        }, className: "text-gray-400 hover:text-gray-600", children: _jsx(X, { className: "h-5 w-5" }) })] }), _jsxs("div", { className: "mb-4 p-3 bg-blue-50 rounded-lg flex items-start gap-2", children: [_jsx(AlertCircle, { className: "h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" }), _jsxs("div", { children: [_jsxs("p", { className: "text-sm font-medium text-blue-900", children: ["Applying to ", selectedItems.length, " ", itemType === 'transactions' ? 'transaction' : 'task', selectedItems.length !== 1 ? 's' : ''] }), _jsx("p", { className: "text-xs text-blue-700 mt-1", children: selectedAction.description })] })] }), _jsxs("div", { className: "space-y-4", children: [selectedAction.action === 'update_status' && (_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Select New Status" }), _jsxs("select", { value: actionParams.status || '', onChange: (e) => setActionParams({ ...actionParams, status: e.target.value }), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500", children: [_jsx("option", { value: "", children: "Choose status..." }), itemType === 'transactions' ? (_jsxs(_Fragment, { children: [_jsx("option", { value: "prospect", children: "Prospect" }), _jsx("option", { value: "listing_active", children: "Listing Active" }), _jsx("option", { value: "under_agreement", children: "Under Agreement" }), _jsx("option", { value: "closed", children: "Closed" }), _jsx("option", { value: "cancelled", children: "Cancelled" })] })) : (_jsxs(_Fragment, { children: [_jsx("option", { value: "pending", children: "Pending" }), _jsx("option", { value: "in_progress", children: "In Progress" }), _jsx("option", { value: "completed", children: "Completed" }), _jsx("option", { value: "blocked", children: "Blocked" }), _jsx("option", { value: "cancelled", children: "Cancelled" })] }))] })] })), selectedAction.action === 'assign_agent' && (_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: itemType === 'transactions' ? 'Select Agent' : 'Reassign To' }), _jsx("input", { type: "text", value: actionParams.agent_name || '', onChange: (e) => setActionParams({ ...actionParams, agent_name: e.target.value }), placeholder: "Enter agent name or ID", className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] })), selectedAction.action === 'add_tags' && (_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Tags (comma-separated)" }), _jsx("input", { type: "text", value: actionParams.tags || '', onChange: (e) => setActionParams({ ...actionParams, tags: e.target.value }), placeholder: "e.g., urgent, high-value, needs-review", className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] })), selectedAction.action === 'update_date' && (_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: itemType === 'transactions' ? 'Closing Date' : 'Due Date' }), _jsx("input", { type: "date", value: actionParams.date || '', onChange: (e) => setActionParams({ ...actionParams, date: e.target.value }), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] })), selectedAction.action === 'bulk_email' && (_jsxs(_Fragment, { children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Email Subject" }), _jsx("input", { type: "text", value: actionParams.subject || '', onChange: (e) => setActionParams({ ...actionParams, subject: e.target.value }), placeholder: "Email subject...", className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Email Message" }), _jsx("textarea", { value: actionParams.message || '', onChange: (e) => setActionParams({ ...actionParams, message: e.target.value }), placeholder: "Email message...", rows: 4, className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] })] })), selectedAction.action === 'generate_report' && (_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Report Type" }), _jsxs("select", { value: actionParams.report_type || '', onChange: (e) => setActionParams({ ...actionParams, report_type: e.target.value }), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500", children: [_jsx("option", { value: "", children: "Choose report type..." }), _jsx("option", { value: "summary", children: "Summary Report" }), _jsx("option", { value: "detailed", children: "Detailed Report" }), _jsx("option", { value: "timeline", children: "Timeline Report" }), _jsx("option", { value: "financial", children: "Financial Report" })] })] }))] }), _jsxs("div", { className: "flex items-center justify-end gap-2 mt-6 pt-4 border-t border-gray-200", children: [_jsx(Button, { variant: "secondary", onClick: () => {
                                            setSelectedAction(null);
                                            setActionParams({});
                                        }, children: "Cancel" }), _jsxs(Button, { variant: "primary", onClick: handleSubmitParams, disabled: !actionParams || Object.keys(actionParams).length === 0, children: ["Apply to ", selectedItems.length, " item", selectedItems.length !== 1 ? 's' : ''] })] })] }) }) }))] }));
};
