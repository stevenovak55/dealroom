import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { CheckCircle2, Clock, User, AlertCircle, Edit, Trash2, Copy, ExternalLink, Tag, FileText, Calendar, GitBranch, Filter } from 'lucide-react';
import { Drawer } from '@/components/shared/Drawer';
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';
import { useDeleteTaskDefinition, useGetTaskTemplates } from '@/api/queries/useTaskDefinitions';
import { showToast } from '@/utils/toast';
const priorityColors = {
    low: 'bg-gray-100 text-gray-700 border-gray-300',
    normal: 'bg-blue-100 text-blue-700 border-blue-300',
    high: 'bg-orange-100 text-orange-700 border-orange-300',
    urgent: 'bg-red-100 text-red-700 border-red-300',
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
export const TaskDetailDrawer = ({ task, isOpen, onClose, onEdit, onClone, }) => {
    const deleteMutation = useDeleteTaskDefinition();
    const { data: templates } = useGetTaskTemplates(task?.id || 0, isOpen && !!task);
    if (!task)
        return null;
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
            onClose();
        }
        catch (error) {
            const message = error.response?.data?.message || 'Failed to delete task';
            showToast.error(message);
        }
    };
    const handleEdit = () => {
        onEdit(task);
        onClose();
    };
    const handleClone = () => {
        onClone(task);
        onClose();
    };
    return (_jsx(Drawer, { isOpen: isOpen, onClose: onClose, size: "xl", children: _jsxs("div", { className: "p-6 space-y-6", children: [_jsxs("div", { children: [_jsxs("div", { className: "flex items-start gap-3 mb-3", children: [task.is_milestone && (_jsx(CheckCircle2, { className: "h-6 w-6 text-green-600 flex-shrink-0 mt-1" })), task.is_required && (_jsx(AlertCircle, { className: "h-6 w-6 text-orange-600 flex-shrink-0 mt-1" })), _jsxs("div", { className: "flex-1", children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900 leading-tight", children: task.title }), _jsxs("div", { className: "flex items-center gap-2 mt-2 flex-wrap", children: [_jsxs(Badge, { variant: "default", className: `${priorityColors[task.priority]} border`, children: [task.priority, " priority"] }), task.is_system && _jsx(Badge, { variant: "info", children: "System Task" }), task.is_milestone && _jsx(Badge, { variant: "success", children: "Milestone" }), task.is_required && _jsx(Badge, { variant: "warning", children: "Required" })] })] })] }), _jsxs("div", { className: "flex items-center gap-2 text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-md", children: [_jsx(Tag, { className: "h-4 w-4" }), _jsx("span", { className: "font-mono", children: task.task_key })] })] }), _jsxs("div", { className: "flex items-center gap-2 pb-4 border-b border-gray-200", children: [_jsxs(Button, { size: "sm", variant: "secondary", onClick: handleEdit, disabled: task.is_system, children: [_jsx(Edit, { className: "h-4 w-4 mr-2" }), "Edit"] }), _jsxs(Button, { size: "sm", variant: "secondary", onClick: handleClone, children: [_jsx(Copy, { className: "h-4 w-4 mr-2" }), "Clone"] }), !task.is_system && (_jsxs(Button, { size: "sm", variant: "danger", onClick: handleDelete, isLoading: deleteMutation.isPending, children: [_jsx(Trash2, { className: "h-4 w-4 mr-2" }), "Delete"] }))] }), task.description && (_jsxs("div", { className: "space-y-2", children: [_jsxs("div", { className: "flex items-center gap-2 text-sm font-semibold text-gray-700", children: [_jsx(FileText, { className: "h-4 w-4" }), "Description"] }), _jsx("p", { className: "text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-md", children: task.description })] })), _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4", children: [_jsxs("div", { className: "bg-gray-50 p-4 rounded-lg", children: [_jsxs("div", { className: "flex items-center gap-2 text-sm font-medium text-gray-500 mb-2", children: [_jsx(Tag, { className: "h-4 w-4" }), "Category"] }), _jsx("p", { className: "text-gray-900 font-medium capitalize", children: task.category.replace(/_/g, ' ') })] }), _jsxs("div", { className: "bg-gray-50 p-4 rounded-lg", children: [_jsxs("div", { className: "flex items-center gap-2 text-sm font-medium text-gray-500 mb-2", children: [_jsx(User, { className: "h-4 w-4" }), "Default Owner"] }), _jsx("p", { className: "text-gray-900 font-medium", children: ownerRoleLabels[task.owner_role] || task.owner_role })] }), task.estimated_duration && (_jsxs("div", { className: "bg-gray-50 p-4 rounded-lg", children: [_jsxs("div", { className: "flex items-center gap-2 text-sm font-medium text-gray-500 mb-2", children: [_jsx(Clock, { className: "h-4 w-4" }), "Estimated Duration"] }), _jsxs("p", { className: "text-gray-900 font-medium", children: [task.estimated_duration, " ", task.estimated_duration === 1 ? 'day' : 'days'] })] })), task.due_calculation && (_jsxs("div", { className: "bg-gray-50 p-4 rounded-lg", children: [_jsxs("div", { className: "flex items-center gap-2 text-sm font-medium text-gray-500 mb-2", children: [_jsx(Calendar, { className: "h-4 w-4" }), "Due Date Calculation"] }), _jsx("code", { className: "text-gray-900 font-mono text-sm bg-white px-2 py-1 rounded border border-gray-200", children: task.due_calculation })] }))] }), (task.applies_if || task.depends_on?.length > 0) && (_jsxs("div", { className: "space-y-4 border-t border-gray-200 pt-4", children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: "Advanced Configuration" }), task.applies_if && (_jsxs("div", { className: "space-y-2", children: [_jsxs("div", { className: "flex items-center gap-2 text-sm font-semibold text-gray-700", children: [_jsx(Filter, { className: "h-4 w-4" }), "Applies If (Condition)"] }), _jsx("div", { className: "bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-sm overflow-x-auto", children: task.applies_if }), _jsx("p", { className: "text-xs text-gray-500", children: "This task will only be created when the above condition evaluates to true" })] })), task.depends_on && task.depends_on.length > 0 && (_jsxs("div", { className: "space-y-2", children: [_jsxs("div", { className: "flex items-center gap-2 text-sm font-semibold text-gray-700", children: [_jsx(GitBranch, { className: "h-4 w-4" }), "Dependencies (", task.depends_on.length, ")"] }), _jsx("div", { className: "flex flex-wrap gap-2", children: task.depends_on.map((dep, idx) => (_jsx(Badge, { variant: "default", className: "font-mono text-xs", children: dep }, idx))) }), _jsx("p", { className: "text-xs text-gray-500", children: "This task depends on the completion of the above tasks" })] }))] })), _jsxs("div", { className: "space-y-4 border-t border-gray-200 pt-4", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx(ExternalLink, { className: "h-5 w-5 text-gray-400" }), _jsx("h3", { className: "text-lg font-semibold text-gray-900", children: "Templates Using This Task" })] }), templates && templates.length > 0 ? (_jsx("div", { className: "grid grid-cols-1 gap-2", children: templates.map((template) => (_jsxs("div", { className: "flex items-center justify-between bg-gray-50 p-3 rounded-lg hover:bg-gray-100 transition-colors", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: template.name }), template.description && (_jsx("p", { className: "text-sm text-gray-500 mt-0.5", children: template.description }))] }), _jsx(Badge, { variant: "default", children: template.property_type })] }, template.template_id))) })) : (_jsx("p", { className: "text-gray-500 text-sm italic", children: "This task is not currently used in any templates" }))] }), task.metadata && Object.keys(task.metadata).length > 0 && (_jsxs("div", { className: "space-y-2 border-t border-gray-200 pt-4", children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: "Additional Metadata" }), _jsx("div", { className: "bg-gray-50 p-4 rounded-lg", children: _jsx("pre", { className: "text-xs text-gray-700 overflow-x-auto", children: JSON.stringify(task.metadata, null, 2) }) })] }))] }) }));
};
