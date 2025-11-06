import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { GripVertical, Trash2, Eye, EyeOff, ChevronDown, ChevronRight } from 'lucide-react';
import { TASK_PHASES } from '@/api/types';
const PHASE_ORDER = [
    'pre_listing',
    'post_listing',
    'pre_agreement',
    'post_agreement',
    'pre_closing',
    'post_closing',
    'any',
];
export const TemplateCanvas = ({ tasks, onTasksChange, onTaskRemove, }) => {
    const [draggedTaskId, setDraggedTaskId] = useState(null);
    const [dragOverIndex, setDragOverIndex] = useState(null);
    const [collapsedPhases, setCollapsedPhases] = useState(new Set());
    // Group tasks by phase
    const tasksByPhase = useMemo(() => {
        const grouped = new Map();
        // Initialize all phases
        PHASE_ORDER.forEach(phase => {
            grouped.set(phase, []);
        });
        // Group tasks by their phase
        tasks.forEach(task => {
            const phase = task.taskDefinition.phase || 'any';
            const phaseTask = grouped.get(phase) || [];
            phaseTask.push(task);
            grouped.set(phase, phaseTask);
        });
        return grouped;
    }, [tasks]);
    // Handle drop from sidebar (new task)
    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        const taskDataStr = e.dataTransfer.getData('application/json');
        if (!taskDataStr)
            return;
        try {
            const taskDefinition = JSON.parse(taskDataStr);
            // Check if task already exists
            if (tasks.some((t) => t.taskDefinition.id === taskDefinition.id)) {
                alert('This task is already in the template');
                return;
            }
            // Add new task
            const newTask = {
                id: `temp-${Date.now()}`,
                taskDefinition,
                sortOrder: tasks.length,
                isOptional: false,
            };
            onTasksChange([...tasks, newTask]);
        }
        catch (error) {
            console.error('Failed to parse task data:', error);
        }
        setDragOverIndex(null);
    };
    // Handle drag over
    const handleDragOver = (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    };
    // Handle reorder drag start
    const handleTaskDragStart = (e, taskId) => {
        setDraggedTaskId(taskId);
        e.dataTransfer.effectAllowed = 'move';
    };
    // Handle reorder drag over
    const handleTaskDragOver = (e, index) => {
        e.preventDefault();
        e.stopPropagation();
        setDragOverIndex(index);
    };
    // Handle reorder drop
    const handleTaskDrop = (e, targetIndex) => {
        e.preventDefault();
        e.stopPropagation();
        if (!draggedTaskId)
            return;
        const draggedIndex = tasks.findIndex((t) => t.id === draggedTaskId);
        if (draggedIndex === -1)
            return;
        // Reorder tasks
        const newTasks = [...tasks];
        const [removed] = newTasks.splice(draggedIndex, 1);
        newTasks.splice(targetIndex, 0, removed);
        // Update sort orders
        const reordered = newTasks.map((task, index) => ({
            ...task,
            sortOrder: index,
        }));
        onTasksChange(reordered);
        setDraggedTaskId(null);
        setDragOverIndex(null);
    };
    // Toggle optional
    const toggleOptional = (taskId) => {
        const updated = tasks.map((task) => task.id === taskId ? { ...task, isOptional: !task.isOptional } : task);
        onTasksChange(updated);
    };
    // Toggle phase collapse
    const togglePhase = (phase) => {
        const newCollapsed = new Set(collapsedPhases);
        if (newCollapsed.has(phase)) {
            newCollapsed.delete(phase);
        }
        else {
            newCollapsed.add(phase);
        }
        setCollapsedPhases(newCollapsed);
    };
    // Get phase color
    const getPhaseColor = (phase) => {
        const colors = {
            pre_listing: 'border-sky-300 bg-sky-50',
            post_listing: 'border-blue-300 bg-blue-50',
            pre_agreement: 'border-amber-300 bg-amber-50',
            post_agreement: 'border-orange-300 bg-orange-50',
            pre_closing: 'border-purple-300 bg-purple-50',
            post_closing: 'border-emerald-300 bg-emerald-50',
            any: 'border-gray-300 bg-gray-50',
        };
        return colors[phase] || 'border-gray-300 bg-gray-50';
    };
    return (_jsxs("div", { className: "h-full flex flex-col bg-gray-50", onDrop: handleDrop, onDragOver: handleDragOver, children: [_jsxs("div", { className: "p-4 bg-white border-b border-gray-200", children: [_jsx("h2", { className: "text-lg font-semibold text-gray-900", children: "Template Tasks" }), _jsxs("p", { className: "text-sm text-gray-600 mt-1", children: [tasks.length, " ", tasks.length === 1 ? 'task' : 'tasks', " in template"] })] }), _jsx("div", { className: "flex-1 overflow-y-auto p-4", children: tasks.length === 0 ? (_jsx("div", { className: "flex items-center justify-center h-full", children: _jsxs("div", { className: "text-center max-w-md", children: [_jsx("svg", { className: "w-20 h-20 mx-auto text-gray-300 mb-4", fill: "none", viewBox: "0 0 24 24", stroke: "currentColor", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1, d: "M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" }) }), _jsx("h3", { className: "text-lg font-medium text-gray-900 mb-2", children: "No tasks yet" }), _jsx("p", { className: "text-gray-600", children: "Drag tasks from the library to build your template" })] }) })) : (_jsx("div", { className: "space-y-3", children: PHASE_ORDER.map((phase) => {
                        const phaseTasks = tasksByPhase.get(phase) || [];
                        if (phaseTasks.length === 0)
                            return null;
                        const isCollapsed = collapsedPhases.has(phase);
                        return (_jsxs("div", { className: `border-l-4 rounded ${getPhaseColor(phase)}`, children: [_jsxs("button", { onClick: () => togglePhase(phase), className: "w-full px-3 py-2 flex items-center justify-between hover:bg-opacity-70 transition-colors", children: [_jsxs("div", { className: "flex items-center gap-2", children: [isCollapsed ? (_jsx(ChevronRight, { className: "w-4 h-4" })) : (_jsx(ChevronDown, { className: "w-4 h-4" })), _jsx("span", { className: "font-medium text-sm", children: TASK_PHASES[phase]?.label || phase }), _jsxs("span", { className: "text-xs text-gray-600", children: ["(", phaseTasks.length, ")"] })] }), _jsx("span", { className: "text-xs text-gray-500", children: TASK_PHASES[phase]?.description })] }), !isCollapsed && (_jsx("div", { className: "space-y-1 pb-2 px-2", children: phaseTasks.map((task) => {
                                        const globalIndex = tasks.findIndex(t => t.id === task.id);
                                        return (_jsx("div", { draggable: true, onDragStart: (e) => handleTaskDragStart(e, task.id), onDragOver: (e) => handleTaskDragOver(e, globalIndex), onDrop: (e) => handleTaskDrop(e, globalIndex), className: `
                              bg-white rounded border transition-all
                              ${draggedTaskId === task.id ? 'opacity-50 border-blue-400' : 'border-gray-300'}
                              ${dragOverIndex === globalIndex ? 'border-blue-400 border-dashed' : ''}
                              ${task.isOptional ? 'bg-gray-50' : ''}
                              hover:shadow-sm cursor-move
                            `, children: _jsxs("div", { className: "px-3 py-2 flex items-center gap-2", children: [_jsx(GripVertical, { className: "w-4 h-4 text-gray-400 flex-shrink-0" }), _jsxs("span", { className: "text-xs font-medium text-gray-500 w-6 flex-shrink-0", children: ["#", globalIndex + 1] }), _jsx("div", { className: "flex-1 min-w-0", children: _jsxs("span", { className: "text-sm text-gray-900 truncate block", children: [task.taskDefinition.title, task.isOptional && (_jsx("span", { className: "ml-1.5 text-xs text-gray-500 italic", children: "(optional)" }))] }) }), _jsxs("div", { className: "flex items-center gap-0.5 flex-shrink-0", children: [_jsx("button", { onClick: () => toggleOptional(task.id), className: "p-1 hover:bg-gray-100 rounded transition-colors", title: task.isOptional ? 'Mark as required' : 'Mark as optional', children: task.isOptional ? (_jsx(EyeOff, { className: "w-3.5 h-3.5 text-gray-400" })) : (_jsx(Eye, { className: "w-3.5 h-3.5 text-gray-600" })) }), _jsx("button", { onClick: () => onTaskRemove(task.id), className: "p-1 hover:bg-red-50 rounded transition-colors", title: "Remove task", children: _jsx(Trash2, { className: "w-3.5 h-3.5 text-red-600" }) })] })] }) }, task.id));
                                    }) }))] }, phase));
                    }) })) })] }));
};
