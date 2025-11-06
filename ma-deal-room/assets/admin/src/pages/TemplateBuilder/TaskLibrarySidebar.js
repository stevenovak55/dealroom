import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import React, { useState, useMemo, useCallback } from 'react';
import { Search, GripVertical, Plus } from 'lucide-react';
import { useGetTaskDefinitions } from '@/api/queries/useTaskDefinitions';
import { TASK_PHASES } from '@/api/types';
const CATEGORY_COLORS = {
    'buyer-dd': 'bg-blue-100 text-blue-800',
    'seller-dd': 'bg-green-100 text-green-800',
    'inspections': 'bg-yellow-100 text-yellow-800',
    'legal': 'bg-purple-100 text-purple-800',
    'municipal': 'bg-red-100 text-red-800',
    'closing': 'bg-indigo-100 text-indigo-800',
    'post-closing': 'bg-gray-100 text-gray-800',
};
const PHASE_COLORS = {
    'pre_listing': 'bg-sky-100 text-sky-800',
    'post_listing': 'bg-blue-100 text-blue-800',
    'pre_agreement': 'bg-amber-100 text-amber-800',
    'post_agreement': 'bg-orange-100 text-orange-800',
    'pre_closing': 'bg-purple-100 text-purple-800',
    'post_closing': 'bg-emerald-100 text-emerald-800',
    'any': 'bg-gray-100 text-gray-600',
};
const TaskLibrarySidebarComponent = ({ onTaskSelect }) => {
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [expandedCategories, setExpandedCategories] = useState(new Set(['all']));
    const { data: taskDefsData, isLoading } = useGetTaskDefinitions({ grouped: true });
    // Handle grouped response - when grouped=true, API returns object with category keys
    const groupedTasks = useMemo(() => {
        if (!taskDefsData) {
            return [];
        }
        // Check if it's already an array (grouped response)
        if (Array.isArray(taskDefsData)) {
            return taskDefsData;
        }
        // Check if it has a .data property (paginated response)
        if ('data' in taskDefsData && Array.isArray(taskDefsData.data)) {
            return taskDefsData.data;
        }
        // Maybe it's wrapped in pagination object
        if ('data' in taskDefsData && taskDefsData.data && 'data' in taskDefsData.data) {
            const innerData = taskDefsData.data.data;
            if (Array.isArray(innerData)) {
                return innerData;
            }
        }
        // API returns object with category keys when grouped=true: { deal_setup: {...}, party_onboarding: {...} }
        // Convert to array of category objects
        if (typeof taskDefsData === 'object' && !Array.isArray(taskDefsData)) {
            const categoriesArray = Object.values(taskDefsData);
            return categoriesArray;
        }
        return [];
    }, [taskDefsData]);
    // Filter tasks
    const filteredGroups = useMemo(() => {
        if (!groupedTasks || groupedTasks.length === 0)
            return [];
        let filtered = groupedTasks;
        // Category filter
        if (selectedCategory !== 'all') {
            filtered = filtered.filter((group) => group.category_key === selectedCategory);
        }
        // Search filter
        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            filtered = filtered
                .map((group) => {
                // Ensure group has tasks array
                if (!group.tasks || !Array.isArray(group.tasks)) {
                    return { ...group, tasks: [] };
                }
                return {
                    ...group,
                    tasks: group.tasks.filter((task) => task?.title?.toLowerCase().includes(query) ||
                        task?.description?.toLowerCase().includes(query) ||
                        task?.category?.toLowerCase().includes(query)),
                };
            })
                .filter((group) => group.tasks && group.tasks.length > 0);
        }
        return filtered;
    }, [groupedTasks, selectedCategory, searchQuery]);
    // Toggle category expansion
    const toggleCategory = useCallback((categoryKey) => {
        setExpandedCategories(prev => {
            const newExpanded = new Set(prev);
            if (newExpanded.has(categoryKey)) {
                newExpanded.delete(categoryKey);
            }
            else {
                newExpanded.add(categoryKey);
            }
            return newExpanded;
        });
    }, []);
    // Handle drag start
    const handleDragStart = useCallback((e, task) => {
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('application/json', JSON.stringify(task));
        e.dataTransfer.setData('text/plain', task.title);
    }, []);
    // Get category badge color
    const getCategoryColor = (categoryKey) => {
        return CATEGORY_COLORS[categoryKey] || 'bg-gray-100 text-gray-800';
    };
    // Get phase badge color
    const getPhaseColor = (phase) => {
        if (!phase)
            return null;
        return PHASE_COLORS[phase] || 'bg-gray-100 text-gray-600';
    };
    if (isLoading) {
        return (_jsx("div", { className: "flex items-center justify-center h-64", children: _jsx("div", { className: "animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" }) }));
    }
    return (_jsxs("div", { className: "h-full flex flex-col bg-white border-r border-gray-200", children: [_jsxs("div", { className: "p-4 border-b border-gray-200", children: [_jsx("h2", { className: "text-lg font-semibold text-gray-900 mb-3", children: "Task Library" }), _jsxs("div", { className: "relative mb-3", children: [_jsx(Search, { className: "absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" }), _jsx("input", { type: "text", placeholder: "Search tasks...", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), className: "w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" })] }), _jsxs("select", { value: selectedCategory, onChange: (e) => setSelectedCategory(e.target.value), className: "w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent", children: [_jsx("option", { value: "all", children: "All Categories" }), groupedTasks.map((group) => (_jsxs("option", { value: group.category_key, children: [group.category_name, " (", group.tasks.length, ")"] }, group.category_key)))] })] }), _jsx("div", { className: "flex-1 overflow-y-auto", children: filteredGroups.length === 0 ? (_jsx("div", { className: "p-4 text-center text-gray-500 text-sm", children: "No tasks found" })) : (filteredGroups.map((group) => (_jsxs("div", { className: "border-b border-gray-200", children: [_jsxs("button", { onClick: () => toggleCategory(group.category_key), className: "w-full flex items-center justify-between p-3 hover:bg-gray-50 transition-colors text-left", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("span", { className: `text-xs px-2 py-1 rounded font-medium ${getCategoryColor(group.category_key)}`, children: group.category_name }), _jsxs("span", { className: "text-xs text-gray-500", children: ["(", group.tasks.length, ")"] })] }), _jsx("svg", { className: `w-4 h-4 text-gray-400 transition-transform ${expandedCategories.has(group.category_key) ? 'transform rotate-180' : ''}`, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 2, d: "M19 9l-7 7-7-7" }) })] }), expandedCategories.has(group.category_key) && (_jsx("div", { className: "bg-gray-50", children: group.tasks.map((task) => (_jsx("div", { draggable: true, onDragStart: (e) => handleDragStart(e, task), className: "p-3 border-b border-gray-200 hover:bg-white cursor-move transition-colors", title: "Drag to add to template", children: _jsxs("div", { className: "flex items-start gap-2", children: [_jsx(GripVertical, { className: "w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" }), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("h4", { className: "text-sm font-medium text-gray-900 mb-1", children: task.title }), task.description && (_jsx("p", { className: "text-xs text-gray-600 line-clamp-2 mb-2", children: task.description })), _jsxs("div", { className: "flex items-center gap-2 flex-wrap", children: [_jsx("span", { className: "text-xs text-gray-500", children: task.owner_role.replace('_', ' ') }), task.phase && (_jsx("span", { className: `text-xs px-1.5 py-0.5 rounded ${getPhaseColor(task.phase)}`, children: TASK_PHASES[task.phase]?.label || task.phase })), task.is_milestone && (_jsx("span", { className: "text-xs bg-yellow-100 text-yellow-800 px-1.5 py-0.5 rounded", children: "Milestone" })), task.is_required && (_jsx("span", { className: "text-xs bg-red-100 text-red-800 px-1.5 py-0.5 rounded", children: "Required" }))] })] }), onTaskSelect && (_jsx("button", { onClick: () => onTaskSelect(task), className: "p-1 hover:bg-gray-200 rounded", title: "Add to template", children: _jsx(Plus, { className: "w-4 h-4 text-gray-600" }) }))] }) }, task.id))) }))] }, group.category_key)))) }), _jsx("div", { className: "p-3 border-t border-gray-200 bg-gray-50", children: _jsx("p", { className: "text-xs text-gray-600 text-center", children: "\uD83D\uDCA1 Drag tasks to the canvas to build your template" }) })] }));
};
// Memoize component to prevent unnecessary re-renders
export const TaskLibrarySidebar = React.memo(TaskLibrarySidebarComponent);
