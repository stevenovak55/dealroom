import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { TaskCard } from './TaskCard';
import { TaskDetailModal } from './TaskDetailModal';
import { TaskFilters } from './TaskFilters';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { EmptyState } from '@/components/shared/EmptyState';
import { CheckCircle, Plus, Trash2 } from 'lucide-react';
import { useCompleteTask, useDeleteTask } from '@/api/queries/useTasks';
export const TaskList = ({ tasks, filters, onFiltersChange, onAddTask, onEditTask, onDeleteTask }) => {
    const [selectedTask, setSelectedTask] = useState(null);
    const [selectedTaskIds, setSelectedTaskIds] = useState(new Set());
    const [isSelectionMode, setIsSelectionMode] = useState(false);
    const completeMutation = useCompleteTask();
    const deleteMutation = useDeleteTask();
    // Filter tasks client-side for assigneeRole only (not supported by backend yet)
    const filteredTasks = filters.assigneeRole
        ? tasks.filter((task) => task.owner_role === filters.assigneeRole)
        : tasks;
    // Group tasks by status
    const pendingTasks = filteredTasks.filter((t) => t.status === 'pending');
    const completedTasks = filteredTasks.filter((t) => t.status === 'completed');
    const blockedTasks = filteredTasks.filter((t) => t.status === 'blocked');
    const skippedTasks = filteredTasks.filter((t) => t.status === 'skipped');
    // Selection handlers
    const toggleTaskSelection = (taskId) => {
        setSelectedTaskIds((prev) => {
            const newSet = new Set(prev);
            if (newSet.has(taskId)) {
                newSet.delete(taskId);
            }
            else {
                newSet.add(taskId);
            }
            return newSet;
        });
    };
    const selectAll = () => {
        const allTaskIds = filteredTasks.map((t) => t.id);
        setSelectedTaskIds(new Set(allTaskIds));
    };
    const deselectAll = () => {
        setSelectedTaskIds(new Set());
    };
    const toggleSelectionMode = () => {
        setIsSelectionMode((prev) => !prev);
        if (isSelectionMode) {
            deselectAll();
        }
    };
    // Bulk actions
    const handleBulkComplete = async () => {
        if (selectedTaskIds.size === 0)
            return;
        if (!confirm(`Complete ${selectedTaskIds.size} selected task(s)?`)) {
            return;
        }
        try {
            // Complete each task individually
            await Promise.all(Array.from(selectedTaskIds).map((taskId) => completeMutation.mutateAsync({ id: taskId })));
            deselectAll();
            setIsSelectionMode(false);
        }
        catch (error) {
            console.error('Failed to complete tasks:', error);
            alert('Failed to complete some tasks. Please try again.');
        }
    };
    const handleBulkDelete = async () => {
        if (selectedTaskIds.size === 0)
            return;
        if (!confirm(`Delete ${selectedTaskIds.size} selected task(s)? This cannot be undone.`)) {
            return;
        }
        try {
            // Delete each task individually
            await Promise.all(Array.from(selectedTaskIds).map((taskId) => deleteMutation.mutateAsync(taskId)));
            deselectAll();
            setIsSelectionMode(false);
        }
        catch (error) {
            console.error('Failed to delete tasks:', error);
            alert('Failed to delete some tasks. Please try again.');
        }
    };
    const allSelected = filteredTasks.length > 0 && selectedTaskIds.size === filteredTasks.length;
    return (_jsxs(_Fragment, { children: [_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsx(CardTitle, { children: "Tasks" }), _jsx("div", { className: "flex items-center gap-2", children: isSelectionMode ? (_jsxs(_Fragment, { children: [selectedTaskIds.size > 0 && (_jsxs(_Fragment, { children: [_jsxs("span", { className: "text-sm text-gray-600", children: [selectedTaskIds.size, " selected"] }), _jsxs(Button, { size: "sm", variant: "primary", onClick: handleBulkComplete, isLoading: completeMutation.isPending, children: [_jsx(CheckCircle, { className: "h-4 w-4 mr-2" }), "Complete"] }), _jsxs(Button, { size: "sm", variant: "danger", onClick: handleBulkDelete, isLoading: deleteMutation.isPending, children: [_jsx(Trash2, { className: "h-4 w-4 mr-2" }), "Delete"] })] })), _jsx(Button, { size: "sm", variant: "secondary", onClick: toggleSelectionMode, children: "Cancel" })] })) : (_jsxs(_Fragment, { children: [_jsx(Button, { size: "sm", variant: "secondary", onClick: toggleSelectionMode, children: "Select" }), onAddTask && (_jsxs(Button, { size: "sm", onClick: onAddTask, children: [_jsx(Plus, { className: "h-4 w-4 mr-2" }), "Add Task"] }))] })) })] }) }), _jsxs(CardContent, { children: [isSelectionMode && filteredTasks.length > 0 && (_jsxs("div", { className: "mb-4 flex items-center gap-2", children: [_jsx("input", { type: "checkbox", checked: allSelected, onChange: allSelected ? deselectAll : selectAll, className: "h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" }), _jsx("span", { className: "text-sm text-gray-700", children: allSelected ? 'Deselect All' : 'Select All' })] })), _jsx(TaskFilters, { filters: filters, onFiltersChange: onFiltersChange }), filteredTasks.length === 0 ? (_jsx(EmptyState, { icon: CheckCircle, title: "No tasks found", description: "Try adjusting your filters" })) : (_jsxs("div", { className: "space-y-6 mt-6", children: [pendingTasks.length > 0 && (_jsxs("div", { children: [_jsxs("h3", { className: "text-sm font-semibold text-gray-900 mb-3", children: ["Pending (", pendingTasks.length, ")"] }), _jsx("div", { className: "space-y-3", children: pendingTasks.map((task) => (_jsx(TaskCard, { task: task, onTaskClick: isSelectionMode ? undefined : setSelectedTask, onEdit: isSelectionMode ? undefined : onEditTask, onDelete: isSelectionMode ? undefined : onDeleteTask, isSelectionMode: isSelectionMode, isSelected: selectedTaskIds.has(task.id), onToggleSelect: () => toggleTaskSelection(task.id) }, task.id))) })] })), blockedTasks.length > 0 && (_jsxs("div", { children: [_jsxs("h3", { className: "text-sm font-semibold text-gray-900 mb-3", children: ["Blocked (", blockedTasks.length, ")"] }), _jsx("div", { className: "space-y-3", children: blockedTasks.map((task) => (_jsx(TaskCard, { task: task, onTaskClick: isSelectionMode ? undefined : setSelectedTask, onEdit: isSelectionMode ? undefined : onEditTask, onDelete: isSelectionMode ? undefined : onDeleteTask, isSelectionMode: isSelectionMode, isSelected: selectedTaskIds.has(task.id), onToggleSelect: () => toggleTaskSelection(task.id) }, task.id))) })] })), completedTasks.length > 0 && (_jsxs("div", { children: [_jsxs("h3", { className: "text-sm font-semibold text-gray-900 mb-3", children: ["Completed (", completedTasks.length, ")"] }), _jsx("div", { className: "space-y-3", children: completedTasks.map((task) => (_jsx(TaskCard, { task: task, onTaskClick: isSelectionMode ? undefined : setSelectedTask, onEdit: isSelectionMode ? undefined : onEditTask, onDelete: isSelectionMode ? undefined : onDeleteTask, isSelectionMode: isSelectionMode, isSelected: selectedTaskIds.has(task.id), onToggleSelect: () => toggleTaskSelection(task.id) }, task.id))) })] })), skippedTasks.length > 0 && (_jsxs("div", { children: [_jsxs("h3", { className: "text-sm font-semibold text-gray-900 mb-3", children: ["Skipped (", skippedTasks.length, ")"] }), _jsx("div", { className: "space-y-3", children: skippedTasks.map((task) => (_jsx(TaskCard, { task: task, onTaskClick: isSelectionMode ? undefined : setSelectedTask, onEdit: isSelectionMode ? undefined : onEditTask, onDelete: isSelectionMode ? undefined : onDeleteTask, isSelectionMode: isSelectionMode, isSelected: selectedTaskIds.has(task.id), onToggleSelect: () => toggleTaskSelection(task.id) }, task.id))) })] }))] }))] })] }), selectedTask && (_jsx(TaskDetailModal, { task: selectedTask, isOpen: !!selectedTask, onClose: () => setSelectedTask(null) }))] }));
};
