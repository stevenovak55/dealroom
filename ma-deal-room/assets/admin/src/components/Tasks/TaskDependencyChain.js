import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useMemo, useState } from 'react';
import { GitBranch, ChevronRight, CheckCircle, Clock, AlertCircle, Circle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { format } from 'date-fns';
const STATUS_COLORS = {
    completed: 'bg-green-500 text-white',
    in_progress: 'bg-blue-500 text-white',
    pending: 'bg-gray-400 text-white',
    blocked: 'bg-red-500 text-white',
    cancelled: 'bg-gray-300 text-gray-600',
    skipped: 'bg-yellow-500 text-white',
};
const STATUS_ICONS = {
    completed: CheckCircle,
    in_progress: Clock,
    pending: Circle,
    blocked: AlertCircle,
    cancelled: Circle,
    skipped: Circle,
};
export const TaskDependencyChain = ({ tasks, onTaskClick, }) => {
    const [selectedTaskId, setSelectedTaskId] = useState(null);
    const [viewMode, setViewMode] = useState('tree');
    // Build dependency graph
    const dependencyGraph = useMemo(() => {
        const graph = new Map();
        // Initialize nodes
        tasks.forEach(taskItem => {
            graph.set(taskItem.id, {
                task: taskItem,
                dependencies: [],
                dependents: [],
                level: 0,
            });
        });
        // Build dependencies
        tasks.forEach(task => {
            const node = graph.get(task.id);
            if (!node)
                return;
            if (task.depends_on_task_ids && Array.isArray(task.depends_on_task_ids)) {
                task.depends_on_task_ids.forEach(depId => {
                    const depTask = tasks.find(t => t.id === depId);
                    if (depTask) {
                        node.dependencies.push(depTask);
                        const depNode = graph.get(depId);
                        if (depNode) {
                            depNode.dependents.push(task);
                        }
                    }
                });
            }
        });
        // Calculate levels (for tree view)
        const calculateLevel = (taskId, visited = new Set()) => {
            if (visited.has(taskId))
                return 0; // Circular dependency
            visited.add(taskId);
            const node = graph.get(taskId);
            if (!node || node.dependencies.length === 0)
                return 0;
            const maxDepLevel = Math.max(...node.dependencies.map(dep => calculateLevel(dep.id, new Set(visited))));
            return maxDepLevel + 1;
        };
        tasks.forEach(task => {
            const node = graph.get(task.id);
            if (node) {
                node.level = calculateLevel(task.id);
            }
        });
        return graph;
    }, [tasks]);
    // Get root tasks (no dependencies)
    const rootTasks = useMemo(() => {
        return tasks.filter(task => {
            const node = dependencyGraph.get(task.id);
            return node && node.dependencies.length === 0;
        });
    }, [tasks, dependencyGraph]);
    // Get tasks by level
    const tasksByLevel = useMemo(() => {
        const levels = new Map();
        dependencyGraph.forEach((node) => {
            if (!levels.has(node.level)) {
                levels.set(node.level, []);
            }
            levels.get(node.level)?.push(node.task);
        });
        return levels;
    }, [dependencyGraph]);
    // Render task node
    const renderTaskNode = (task, depth = 0) => {
        const node = dependencyGraph.get(task.id);
        if (!node)
            return null;
        const StatusIcon = STATUS_ICONS[task.status];
        const isSelected = selectedTaskId === task.id;
        return (_jsxs("div", { className: "relative", children: [_jsxs("div", { className: `flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-all ${isSelected
                        ? 'bg-blue-50 border-2 border-blue-500'
                        : 'bg-white border border-gray-200 hover:border-gray-300 hover:shadow-sm'}`, style: { marginLeft: `${depth * 24}px` }, onClick: () => {
                        setSelectedTaskId(task.id);
                        onTaskClick?.(task);
                    }, children: [_jsx("div", { className: `p-2 rounded ${STATUS_COLORS[task.status]}`, children: _jsx(StatusIcon, { className: "h-4 w-4" }) }), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("h4", { className: "font-medium text-gray-900 text-sm truncate", children: task.title }), _jsxs("div", { className: "flex items-center gap-3 mt-1", children: [_jsx("span", { className: "text-xs text-gray-500", children: task.owner_role.replace('_', ' ') }), task.due_at && (_jsxs("span", { className: "text-xs text-gray-500", children: ["Due: ", format(new Date(task.due_at), 'MMM d')] }))] })] }), node.dependencies.length > 0 && (_jsxs("div", { className: "flex items-center gap-1 text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded", children: [_jsx(GitBranch, { className: "h-3 w-3" }), node.dependencies.length] })), node.dependents.length > 0 && (_jsxs("div", { className: "flex items-center gap-1 text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded", children: [_jsx(ChevronRight, { className: "h-3 w-3" }), node.dependents.length] }))] }), viewMode === 'tree' && node.dependents.length > 0 && (_jsx("div", { className: "mt-2 space-y-2", children: node.dependents.map(dependent => renderTaskNode(dependent, depth + 1)) }))] }, task.id));
    };
    // Critical path analysis
    const criticalPath = useMemo(() => {
        const findLongestPath = (taskId, visited = new Set()) => {
            if (visited.has(taskId))
                return [];
            visited.add(taskId);
            const node = dependencyGraph.get(taskId);
            if (!node)
                return [];
            if (node.dependents.length === 0) {
                return [node.task];
            }
            const paths = node.dependents.map(dep => findLongestPath(dep.id, new Set(visited)));
            const longestPath = paths.reduce((longest, current) => current.length > longest.length ? current : longest, []);
            return [node.task, ...longestPath];
        };
        const paths = rootTasks.map(task => findLongestPath(task.id));
        return paths.reduce((longest, current) => current.length > longest.length ? current : longest, []);
    }, [rootTasks, dependencyGraph]);
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx("div", { className: "p-2 bg-blue-100 rounded-lg", children: _jsx(GitBranch, { className: "h-5 w-5 text-blue-600" }) }), _jsxs("div", { children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: "Task Dependency Chain" }), _jsxs("p", { className: "text-sm text-gray-600", children: [tasks.length, " tasks \u2022 ", rootTasks.length, " root tasks \u2022 ", tasksByLevel.size, " levels"] })] })] }), _jsxs("div", { className: "flex items-center gap-2 bg-gray-100 p-1 rounded-lg", children: [_jsx("button", { onClick: () => setViewMode('tree'), className: `px-3 py-1 text-sm rounded ${viewMode === 'tree'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'}`, children: "Tree View" }), _jsx("button", { onClick: () => setViewMode('list'), className: `px-3 py-1 text-sm rounded ${viewMode === 'list'
                                    ? 'bg-white text-gray-900 shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900'}`, children: "Level View" })] })] }), criticalPath.length > 0 && (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs(CardTitle, { className: "text-base flex items-center gap-2", children: [_jsx(AlertCircle, { className: "h-4 w-4 text-amber-600" }), "Critical Path (", criticalPath.length, " tasks)"] }) }), _jsx(CardContent, { children: _jsx("div", { className: "flex items-center gap-2 overflow-x-auto pb-2", children: criticalPath.map((task, idx) => (_jsxs(React.Fragment, { children: [_jsxs("div", { className: `flex-shrink-0 px-3 py-2 rounded border-2 cursor-pointer ${task.status === 'completed'
                                            ? 'border-green-500 bg-green-50'
                                            : 'border-amber-500 bg-amber-50'}`, onClick: () => {
                                            setSelectedTaskId(task.id);
                                            onTaskClick?.(task);
                                        }, children: [_jsx("p", { className: "text-sm font-medium text-gray-900 whitespace-nowrap", children: task.title }), _jsx("p", { className: "text-xs text-gray-600", children: task.owner_role.replace('_', ' ') })] }), idx < criticalPath.length - 1 && (_jsx(ChevronRight, { className: "h-4 w-4 text-gray-400 flex-shrink-0" }))] }, task.id))) }) })] })), _jsx(Card, { children: _jsx(CardContent, { className: "p-4", children: viewMode === 'tree' ? (_jsx("div", { className: "space-y-2", children: rootTasks.length === 0 ? (_jsxs("div", { className: "text-center py-8 text-gray-500", children: [_jsx(GitBranch, { className: "h-12 w-12 mx-auto mb-3 text-gray-300" }), _jsx("p", { children: "All tasks have dependencies" })] })) : (rootTasks.map(task => renderTaskNode(task))) })) : (_jsx("div", { className: "space-y-6", children: Array.from(tasksByLevel.entries())
                            .sort(([a], [b]) => a - b)
                            .map(([level, levelTasks]) => (_jsxs("div", { children: [_jsxs("h4", { className: "text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2", children: [_jsxs("span", { className: "px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs", children: ["Level ", level] }), _jsxs("span", { className: "text-gray-500", children: ["(", levelTasks.length, " tasks)"] })] }), _jsx("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3", children: levelTasks.map(task => renderTaskNode(task, 0)) })] }, level))) })) }) }), selectedTaskId && dependencyGraph.get(selectedTaskId) && (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-base", children: "Dependency Details" }) }), _jsx(CardContent, { children: (() => {
                            const node = dependencyGraph.get(selectedTaskId);
                            if (!node)
                                return null;
                            return (_jsxs("div", { className: "space-y-4", children: [_jsxs("div", { children: [_jsx("h4", { className: "font-medium text-gray-900 mb-2", children: node.task.title }), _jsx("p", { className: "text-sm text-gray-600", children: node.task.description })] }), node.dependencies.length > 0 && (_jsxs("div", { children: [_jsxs("h5", { className: "text-sm font-medium text-gray-700 mb-2", children: ["Depends On (", node.dependencies.length, ")"] }), _jsx("div", { className: "space-y-1", children: node.dependencies.map(dep => (_jsxs("div", { className: "text-sm p-2 bg-gray-50 rounded flex items-center justify-between", children: [_jsx("span", { children: dep.title }), _jsx("span", { className: `px-2 py-0.5 rounded text-xs ${STATUS_COLORS[dep.status]}`, children: dep.status })] }, dep.id))) })] })), node.dependents.length > 0 && (_jsxs("div", { children: [_jsxs("h5", { className: "text-sm font-medium text-gray-700 mb-2", children: ["Blocks (", node.dependents.length, ")"] }), _jsx("div", { className: "space-y-1", children: node.dependents.map(dep => (_jsxs("div", { className: "text-sm p-2 bg-blue-50 rounded flex items-center justify-between", children: [_jsx("span", { children: dep.title }), _jsx("span", { className: `px-2 py-0.5 rounded text-xs ${STATUS_COLORS[dep.status]}`, children: dep.status })] }, dep.id))) })] }))] }));
                        })() })] }))] }));
};
