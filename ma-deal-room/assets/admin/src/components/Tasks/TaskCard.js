import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { CheckCircle, Circle, Clock, User, Edit, Trash2 } from 'lucide-react';
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';
import { useCompleteTask, useSkipTask } from '@/api/queries/useTasks';
import { formatDate, isOverdue } from '@/utils/formatDate';
import { cn } from '@/utils/cn';
export const TaskCard = ({ task, onTaskClick, onEdit, onDelete, isSelectionMode = false, isSelected = false, onToggleSelect, }) => {
    const completeMutation = useCompleteTask();
    const skipMutation = useSkipTask();
    const handleComplete = async (e) => {
        e.stopPropagation();
        try {
            await completeMutation.mutateAsync({ id: task.id });
        }
        catch (error) {
            console.error('Failed to complete task:', error);
        }
    };
    const handleSkip = async (e) => {
        e.stopPropagation();
        try {
            await skipMutation.mutateAsync({
                id: task.id,
                data: { reason: 'Skipped by user' },
            });
        }
        catch (error) {
            console.error('Failed to skip task:', error);
        }
    };
    const handleEdit = (e) => {
        e.stopPropagation();
        onEdit?.(task);
    };
    const handleDelete = (e) => {
        e.stopPropagation();
        onDelete?.(task.id);
    };
    const getStatusBadge = () => {
        if (task.status === 'completed') {
            return _jsx(Badge, { variant: "success", children: "Completed" });
        }
        if (task.status === 'skipped') {
            return _jsx(Badge, { variant: "default", children: "Skipped" });
        }
        if (task.status === 'blocked') {
            return _jsx(Badge, { variant: "danger", children: "Blocked" });
        }
        if (isOverdue(task.due_at) && task.status === 'pending') {
            return _jsx(Badge, { variant: "danger", children: "Overdue" });
        }
        return _jsx(Badge, { variant: "warning", children: "Pending" });
    };
    const isTaskOverdue = isOverdue(task.due_at) && task.status === 'pending';
    const handleCardClick = () => {
        if (isSelectionMode) {
            onToggleSelect?.();
        }
        else {
            onTaskClick?.(task);
        }
    };
    return (_jsx("div", { className: cn('p-4 border rounded-lg hover:shadow-md transition-all cursor-pointer', isTaskOverdue && 'border-danger-300 bg-danger-50', task.status === 'completed' && 'bg-gray-50 opacity-75', !isTaskOverdue && task.status !== 'completed' && 'border-gray-200 bg-white', isSelectionMode && isSelected && 'ring-2 ring-primary-500 bg-primary-50'), onClick: handleCardClick, children: _jsxs("div", { className: "flex items-start gap-3", children: [isSelectionMode ? (_jsx("div", { className: "flex-shrink-0 mt-1", children: _jsx("input", { type: "checkbox", checked: isSelected, onChange: (e) => {
                            e.stopPropagation();
                            onToggleSelect?.();
                        }, className: "h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" }) })) : (_jsx("div", { className: "flex-shrink-0 mt-1", children: task.status === 'completed' ? (_jsx(CheckCircle, { className: "h-5 w-5 text-success-600" })) : (_jsx(Circle, { className: "h-5 w-5 text-gray-400" })) })), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsxs("div", { className: "flex items-start justify-between gap-3", children: [_jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("h4", { className: cn('font-medium text-gray-900', task.status === 'completed' && 'line-through text-gray-500'), children: task.title }), task.description && (_jsx("p", { className: "text-sm text-gray-600 mt-1 line-clamp-2", children: task.description }))] }), getStatusBadge()] }), _jsxs("div", { className: "flex items-center gap-4 mt-3 text-xs text-gray-500", children: [task.due_at && (_jsxs("div", { className: "flex items-center gap-1", children: [_jsx(Clock, { className: "h-3 w-3" }), _jsxs("span", { children: ["Due ", formatDate(task.due_at)] })] })), task.owner_role && (_jsxs("div", { className: "flex items-center gap-1", children: [_jsx(User, { className: "h-3 w-3" }), _jsx("span", { className: "capitalize", children: task.owner_role.replace('_', ' ') })] }))] }), task.depends_on_task_ids && task.depends_on_task_ids.length > 0 && (_jsxs("div", { className: "mt-2 text-xs text-gray-500", children: ["Depends on ", task.depends_on_task_ids.length, " task(s)"] })), !isSelectionMode && (_jsxs("div", { className: "flex items-center gap-2 mt-3", children: [task.status === 'pending' && (_jsxs(_Fragment, { children: [_jsxs(Button, { size: "sm", variant: "primary", onClick: handleComplete, isLoading: completeMutation.isPending, children: [_jsx(CheckCircle, { className: "h-3 w-3 mr-1" }), "Complete"] }), _jsx(Button, { size: "sm", variant: "secondary", onClick: handleSkip, isLoading: skipMutation.isPending, children: "Skip" })] })), onEdit && (_jsxs(Button, { size: "sm", variant: "ghost", onClick: handleEdit, children: [_jsx(Edit, { className: "h-3 w-3 mr-1" }), "Edit"] })), onDelete && (_jsxs(Button, { size: "sm", variant: "ghost", onClick: handleDelete, children: [_jsx(Trash2, { className: "h-3 w-3 mr-1 text-red-600" }), "Delete"] }))] }))] })] }) }));
};
