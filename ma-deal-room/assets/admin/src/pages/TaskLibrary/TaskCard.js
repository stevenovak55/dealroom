import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { CheckCircle2, Clock, User, AlertCircle, Edit, Trash2, ExternalLink, ChevronDown, ChevronUp } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';
import { useDeleteTaskDefinition, useGetTaskTemplates } from '@/api/queries/useTaskDefinitions';
import { showToast } from '@/utils/toast';
const priorityColors = {
    low: 'bg-gray-100 text-gray-700',
    normal: 'bg-blue-100 text-blue-700',
    high: 'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
};
const ownerRoleLabels = {
    agent: 'Agent',
    seller: 'Seller',
    buyer: 'Buyer',
    seller_attorney: 'Seller Attorney',
    buyer_attorney: 'Buyer Attorney',
    vendor: 'Vendor',
    system: 'System',
};
export const TaskCard = ({ task, onEdit }) => {
    const [isExpanded, setIsExpanded] = useState(false);
    const [showTemplates, setShowTemplates] = useState(false);
    const deleteMutation = useDeleteTaskDefinition();
    const { data: templates } = useGetTaskTemplates(task.id, showTemplates);
    const handleDelete = async () => {
        if (task.is_system) {
            showToast.error('System tasks cannot be deleted');
            return;
        }
        if (!confirm(`Are you sure you want to delete "${task.title}"?\nThis cannot be undone.`)) {
            return;
        }
        try {
            await deleteMutation.mutateAsync(task.id);
            showToast.success('Task deleted successfully');
        }
        catch (error) {
            console.error('Failed to delete task:', error);
            const message = error.response?.data?.message || 'Failed to delete task. Please try again.';
            showToast.error(message);
        }
    };
    const handleViewTemplates = () => {
        setShowTemplates(!showTemplates);
    };
    return (_jsxs(Card, { className: "card-hover-effect", children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "flex items-start justify-between", children: _jsxs("div", { className: "flex-1", children: [_jsxs("div", { className: "flex items-center gap-2 mb-1", children: [task.is_milestone && (_jsx(CheckCircle2, { className: "h-4 w-4 text-green-600 flex-shrink-0" })), task.is_required && (_jsx(AlertCircle, { className: "h-4 w-4 text-orange-600 flex-shrink-0" })), _jsx("span", { className: "text-sm font-semibold text-gray-900 leading-tight", children: task.title })] }), _jsxs("div", { className: "flex items-center gap-2 flex-wrap", children: [_jsx(Badge, { variant: "default", className: priorityColors[task.priority] || priorityColors.normal, children: task.priority }), task.is_system && _jsx(Badge, { variant: "info", children: "System" })] })] }) }) }), _jsxs(CardContent, { children: [task.description && (_jsx("p", { className: "text-sm text-gray-600 mb-3 line-clamp-2", children: task.description })), _jsxs("div", { className: "space-y-2 mb-4", children: [_jsxs("div", { className: "flex items-center gap-2 text-sm", children: [_jsx(User, { className: "h-4 w-4 text-gray-400" }), _jsx("span", { className: "text-gray-500", children: "Owner:" }), _jsx("span", { className: "font-medium text-gray-900", children: ownerRoleLabels[task.owner_role] || task.owner_role })] }), task.due_calculation && (_jsxs("div", { className: "flex items-center gap-2 text-sm", children: [_jsx(Clock, { className: "h-4 w-4 text-gray-400" }), _jsx("span", { className: "text-gray-500", children: "Due:" }), _jsx("code", { className: "font-mono text-xs bg-gray-100 px-2 py-0.5 rounded", children: task.due_calculation })] })), task.estimated_duration && (_jsxs("div", { className: "flex items-center gap-2 text-sm", children: [_jsx(Clock, { className: "h-4 w-4 text-gray-400" }), _jsx("span", { className: "text-gray-500", children: "Duration:" }), _jsxs("span", { className: "font-medium text-gray-900", children: [task.estimated_duration, " ", task.estimated_duration === 1 ? 'day' : 'days'] })] }))] }), (task.applies_if || task.depends_on?.length > 0) && (_jsxs("div", { className: "mb-4", children: [_jsxs(Button, { size: "sm", variant: "ghost", onClick: () => setIsExpanded(!isExpanded), className: "w-full justify-between", children: [_jsxs("span", { className: "text-xs", children: [isExpanded ? 'Hide' : 'Show', " advanced details"] }), isExpanded ? (_jsx(ChevronUp, { className: "h-3 w-3" })) : (_jsx(ChevronDown, { className: "h-3 w-3" }))] }), isExpanded && (_jsxs("div", { className: "mt-2 space-y-2 text-xs bg-gray-50 p-3 rounded", children: [task.applies_if && (_jsxs("div", { children: [_jsx("span", { className: "font-medium text-gray-700", children: "Condition:" }), _jsx("code", { className: "block mt-1 font-mono text-xs bg-white p-2 rounded border border-gray-200", children: task.applies_if })] })), task.depends_on?.length > 0 && (_jsxs("div", { children: [_jsx("span", { className: "font-medium text-gray-700", children: "Depends on:" }), _jsx("div", { className: "flex flex-wrap gap-1 mt-1", children: task.depends_on.map((dep, idx) => (_jsx(Badge, { variant: "default", className: "text-xs", children: dep }, idx))) })] }))] }))] })), _jsx("div", { className: "mb-4 pb-4 border-b border-gray-200", children: _jsxs("div", { className: "text-xs text-gray-500", children: ["Task Key: ", _jsx("code", { className: "font-mono", children: task.task_key })] }) }), _jsxs(Button, { size: "sm", variant: "ghost", onClick: handleViewTemplates, className: "w-full mb-2", children: [_jsx(ExternalLink, { className: "h-3 w-3 mr-1" }), showTemplates ? 'Hide' : 'View', " templates using this task"] }), showTemplates && templates && (_jsx("div", { className: "mb-4 text-xs", children: templates.length === 0 ? (_jsx("p", { className: "text-gray-500 italic", children: "Not used in any templates" })) : (_jsx("div", { className: "space-y-1", children: templates.map((template) => (_jsxs("div", { className: "bg-gray-50 p-2 rounded flex items-center justify-between", children: [_jsx("span", { className: "text-gray-900", children: template.name }), _jsx(Badge, { variant: "default", children: template.property_type })] }, template.template_id))) })) })), !task.is_system && (_jsxs("div", { className: "flex items-center gap-2", children: [_jsxs(Button, { size: "sm", variant: "secondary", onClick: () => onEdit(task), className: "flex-1", children: [_jsx(Edit, { className: "h-3 w-3 mr-1" }), "Edit"] }), _jsxs(Button, { size: "sm", variant: "danger", onClick: handleDelete, isLoading: deleteMutation.isPending, className: "flex-1", children: [_jsx(Trash2, { className: "h-3 w-3 mr-1" }), "Delete"] })] }))] })] }));
};
