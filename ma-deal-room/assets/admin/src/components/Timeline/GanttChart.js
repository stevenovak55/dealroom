import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useMemo, useState } from 'react';
import { format, differenceInDays, addDays, parseISO, startOfDay } from 'date-fns';
import { Calendar, Clock, CheckCircle2, AlertCircle, Circle } from 'lucide-react';
const STATUS_COLORS = {
    completed: 'bg-green-500',
    in_progress: 'bg-blue-500',
    pending: 'bg-gray-400',
    blocked: 'bg-red-500',
    cancelled: 'bg-gray-300',
    skipped: 'bg-yellow-500',
};
const STATUS_ICONS = {
    completed: CheckCircle2,
    in_progress: Clock,
    pending: Circle,
    blocked: AlertCircle,
    cancelled: Circle,
    skipped: Circle,
};
export const GanttChart = ({ tasks, transaction, onTaskClick, onTaskDragEnd, showDependencies = true, showMilestones = true,
// groupByPhase prop is accepted but not yet implemented in the Gantt view
// Phase grouping is available in the Template Canvas
 }) => {
    const [draggedTask, setDraggedTask] = useState(null);
    // Helper to safely parse dates
    const safeParseDate = (dateString) => {
        if (!dateString)
            return null;
        try {
            const parsed = parseISO(dateString);
            // Check if date is valid
            if (isNaN(parsed.getTime()))
                return null;
            return parsed;
        }
        catch (error) {
            console.warn('Failed to parse date:', dateString, error);
            return null;
        }
    };
    // Calculate timeline bounds
    const { timelineTasks, minDate, maxDate, totalDays } = useMemo(() => {
        if (tasks.length === 0) {
            return {
                timelineTasks: [],
                minDate: new Date(),
                maxDate: addDays(new Date(), 30),
                totalDays: 30,
            };
        }
        // Find min and max dates from tasks (only valid dates)
        const dates = [];
        tasks.forEach((t) => {
            const parsedDate = safeParseDate(t.due_at);
            if (parsedDate)
                dates.push(parsedDate);
        });
        // Include transaction milestone dates
        if (transaction?.ps_agreement_date) {
            const psDate = safeParseDate(transaction.ps_agreement_date);
            if (psDate)
                dates.push(psDate);
        }
        if (transaction?.closing_date) {
            const closingDate = safeParseDate(transaction.closing_date);
            if (closingDate)
                dates.push(closingDate);
        }
        const min = dates.length > 0
            ? startOfDay(new Date(Math.min(...dates.map(d => d.getTime()))))
            : startOfDay(new Date());
        const max = dates.length > 0
            ? startOfDay(new Date(Math.max(...dates.map(d => d.getTime()))))
            : addDays(min, 30);
        // Add padding to timeline
        const paddedMin = addDays(min, -7);
        const paddedMax = addDays(max, 7);
        const total = differenceInDays(paddedMax, paddedMin);
        // Convert tasks to timeline tasks with positions (only tasks with valid dates)
        const timelineTasks = tasks
            .map((task) => {
            const parsedDate = safeParseDate(task.due_at);
            if (!parsedDate)
                return null;
            const endDate = startOfDay(parsedDate);
            // Estimate start date (3 days before due date, or use created_at)
            const startDate = addDays(endDate, -3);
            const durationDays = Math.max(1, differenceInDays(endDate, startDate));
            return {
                ...task,
                startDate,
                endDate,
                durationDays,
                rowIndex: 0, // Will be set after sorting
            };
        })
            .filter((task) => task !== null)
            .sort((a, b) => a.sort_order - b.sort_order)
            .map((task, index) => ({ ...task, rowIndex: index }));
        return {
            timelineTasks,
            minDate: paddedMin,
            maxDate: paddedMax,
            totalDays: total,
        };
    }, [tasks, transaction]);
    // Calculate position for a date
    const getDatePosition = (date) => {
        const days = differenceInDays(date, minDate);
        return (days / totalDays) * 100;
    };
    // Calculate position and width for a task bar
    const getTaskBarStyle = (task) => {
        const left = getDatePosition(task.startDate);
        const right = getDatePosition(task.endDate);
        const width = right - left;
        return {
            left: `${Math.max(0, left)}%`,
            width: `${Math.max(1, width)}%`,
        };
    };
    // Handle drag start
    const handleDragStart = (taskId, e) => {
        setDraggedTask(taskId);
        e.dataTransfer.effectAllowed = 'move';
    };
    // Handle drop
    const handleDrop = (e) => {
        e.preventDefault();
        if (!draggedTask || !onTaskDragEnd)
            return;
        const rect = e.currentTarget.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const percentage = x / rect.width;
        const newDayOffset = Math.floor(percentage * totalDays);
        const newDueDate = addDays(minDate, newDayOffset);
        onTaskDragEnd(draggedTask, format(newDueDate, 'yyyy-MM-dd'));
        setDraggedTask(null);
    };
    // Render milestone marker
    const renderMilestone = (label, date, color) => {
        const parsedDate = safeParseDate(date);
        if (!parsedDate)
            return null;
        const position = getDatePosition(parsedDate);
        return (_jsxs("div", { className: "absolute top-0 bottom-0 flex flex-col items-center pointer-events-none", style: { left: `${position}%` }, children: [_jsx("div", { className: `w-0.5 h-full ${color} opacity-30` }), _jsxs("div", { className: `absolute top-0 ${color} text-white text-xs px-2 py-1 rounded shadow-md whitespace-nowrap transform -translate-x-1/2`, children: [_jsx(Calendar, { className: "inline w-3 h-3 mr-1" }), label] })] }, label));
    };
    // Today marker
    const todayPosition = getDatePosition(new Date());
    if (timelineTasks.length === 0) {
        return (_jsx("div", { className: "flex items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300", children: _jsxs("div", { className: "text-center", children: [_jsx(Calendar, { className: "w-12 h-12 mx-auto text-gray-400 mb-2" }), _jsx("p", { className: "text-gray-500", children: "No tasks with due dates to display" })] }) }));
    }
    return (_jsxs("div", { className: "bg-white rounded-lg shadow-sm border border-gray-200", children: [_jsxs("div", { className: "p-4 border-b border-gray-200", children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: "Transaction Timeline" }), _jsxs("p", { className: "text-sm text-gray-500 mt-1", children: [format(minDate, 'MMM d, yyyy'), " - ", format(maxDate, 'MMM d, yyyy'), " (", totalDays, " days)"] })] }), _jsx("div", { className: "overflow-x-auto", children: _jsxs("div", { className: "min-w-full p-4", children: [_jsx("div", { className: "relative h-8 mb-2 bg-gray-50 rounded", children: Array.from({ length: Math.ceil(totalDays / 7) }, (_, i) => {
                                const weekDate = addDays(minDate, i * 7);
                                const position = getDatePosition(weekDate);
                                return (_jsx("div", { className: "absolute text-xs text-gray-600", style: { left: `${position}%` }, children: format(weekDate, 'MMM d') }, i));
                            }) }), _jsxs("div", { className: "relative", style: { minHeight: `${timelineTasks.length * 50 + 100}px` }, children: [_jsx("div", { className: "absolute inset-0 pointer-events-none", children: Array.from({ length: Math.ceil(totalDays / 7) }, (_, i) => {
                                        const position = getDatePosition(addDays(minDate, i * 7));
                                        return (_jsx("div", { className: "absolute top-0 bottom-0 w-px bg-gray-200", style: { left: `${position}%` } }, i));
                                    }) }), _jsx("div", { className: "absolute top-0 bottom-0 w-0.5 bg-blue-500 opacity-50 pointer-events-none z-10", style: { left: `${todayPosition}%` }, children: _jsx("div", { className: "absolute -top-6 left-1/2 transform -translate-x-1/2 bg-blue-500 text-white text-xs px-2 py-1 rounded", children: "Today" }) }), showMilestones && transaction?.ps_agreement_date &&
                                    renderMilestone('P&S Date', transaction.ps_agreement_date, 'bg-purple-500'), showMilestones && transaction?.closing_date &&
                                    renderMilestone('Closing', transaction.closing_date, 'bg-green-600'), timelineTasks.map((task) => {
                                    const StatusIcon = STATUS_ICONS[task.status];
                                    const barStyle = getTaskBarStyle(task);
                                    const isOverdue = task.status !== 'completed' &&
                                        task.due_at &&
                                        new Date(task.due_at) < new Date();
                                    return (_jsxs("div", { className: "absolute h-10 flex items-center", style: {
                                            top: `${task.rowIndex * 50 + 50}px`,
                                            left: 0,
                                            right: 0,
                                        }, children: [_jsxs("div", { className: "absolute left-0 w-48 pr-4 text-sm truncate", children: [_jsx(StatusIcon, { className: "inline w-4 h-4 mr-1" }), task.title] }), _jsx("div", { className: "absolute left-52 right-0", children: _jsx("div", { className: `
                        h-8 rounded-md cursor-pointer transition-all hover:shadow-lg
                        ${STATUS_COLORS[task.status]}
                        ${isOverdue ? 'ring-2 ring-red-500 ring-offset-2' : ''}
                        ${draggedTask === task.id ? 'opacity-50' : 'opacity-90'}
                      `, style: barStyle, draggable: onTaskDragEnd !== undefined, onDragStart: (e) => handleDragStart(task.id, e), onDragOver: (e) => e.preventDefault(), onDrop: (e) => handleDrop(e), onClick: () => onTaskClick?.(task), title: `${task.title}\nDue: ${task.due_at ? format(task.endDate, 'MMM d, yyyy') : 'No date'}\nStatus: ${task.status}`, children: _jsx("div", { className: "px-2 py-1 text-white text-xs font-medium truncate", children: task.title }) }) }), showDependencies && task.depends_on_task_ids && Array.isArray(task.depends_on_task_ids) && task.depends_on_task_ids.length > 0 && (_jsx("div", { className: "absolute left-52 right-0 pointer-events-none", children: task.depends_on_task_ids.map((depId) => {
                                                    const depTask = timelineTasks.find((t) => t.id === depId);
                                                    if (!depTask)
                                                        return null;
                                                    const fromPos = getTaskBarStyle(depTask);
                                                    const toPos = getTaskBarStyle(task);
                                                    // Simple arrow line (could be enhanced with SVG)
                                                    return (_jsx("div", { className: "absolute h-0.5 bg-gray-400 opacity-50", style: {
                                                            left: fromPos.left,
                                                            width: `calc(${toPos.left} - ${fromPos.left})`,
                                                            top: '16px',
                                                        } }, depId));
                                                }) }))] }, task.id));
                                })] })] }) }), _jsx("div", { className: "p-4 border-t border-gray-200 bg-gray-50", children: _jsxs("div", { className: "flex flex-wrap gap-4 text-sm", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-4 h-4 bg-green-500 rounded" }), _jsx("span", { children: "Completed" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-4 h-4 bg-blue-500 rounded" }), _jsx("span", { children: "In Progress" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-4 h-4 bg-gray-400 rounded" }), _jsx("span", { children: "Pending" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-4 h-4 bg-red-500 rounded" }), _jsx("span", { children: "Blocked" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-4 h-4 ring-2 ring-red-500 rounded" }), _jsx("span", { children: "Overdue" })] })] }) })] }));
};
