import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { Library, Plus, Search, Filter, X } from 'lucide-react';
import { useGetTaskDefinitions } from '@/api/queries/useTaskDefinitions';
import { useGetTaskCategories } from '@/api/queries/useTaskCategories';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { TaskCard } from './TaskCard';
import { TaskDefinitionForm } from './TaskDefinitionForm';
export const TaskLibrary = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [showSystemOnly, setShowSystemOnly] = useState(true);
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedTask, setSelectedTask] = useState(null);
    // Fetch grouped task definitions
    const { data: groupedData, isLoading } = useGetTaskDefinitions({
        grouped: true,
        search: searchTerm || undefined,
        category: selectedCategory || undefined,
        is_system: showSystemOnly || undefined,
    });
    const { data: categories } = useGetTaskCategories();
    // Convert grouped data to proper format
    const taskGroups = useMemo(() => {
        if (!groupedData || typeof groupedData !== 'object')
            return [];
        return Object.entries(groupedData)
            .map(([key, group]) => ({
            ...group,
            category_key: key,
        }))
            .filter(group => group.tasks && group.tasks.length > 0)
            .sort((a, b) => a.category_name.localeCompare(b.category_name));
    }, [groupedData]);
    const totalTasks = useMemo(() => {
        return taskGroups.reduce((sum, group) => sum + (group.tasks?.length || 0), 0);
    }, [taskGroups]);
    if (isLoading) {
        return _jsx(PageLoader, {});
    }
    const handleOpenCreate = () => {
        setSelectedTask(null);
        setIsFormOpen(true);
    };
    const handleOpenEdit = (task) => {
        setSelectedTask(task);
        setIsFormOpen(true);
    };
    const handleCloseForm = () => {
        setIsFormOpen(false);
        setSelectedTask(null);
    };
    const handleClearFilters = () => {
        setSearchTerm('');
        setSelectedCategory('');
        setShowSystemOnly(true);
    };
    const hasActiveFilters = searchTerm || selectedCategory || !showSystemOnly;
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: "Task Library" }), _jsxs("p", { className: "text-sm text-gray-500 mt-1", children: ["Browse ", totalTasks, " reusable task definitions across ", taskGroups.length, " categories"] })] }), _jsxs(Button, { variant: "primary", onClick: handleOpenCreate, children: [_jsx(Plus, { className: "h-4 w-4 mr-2" }), "Create Custom Task"] })] }), _jsx(Card, { children: _jsx(CardContent, { className: "p-4", children: _jsxs("div", { className: "flex flex-col md:flex-row gap-4", children: [_jsxs("div", { className: "flex-1 relative", children: [_jsx(Search, { className: "absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" }), _jsx("input", { type: "text", placeholder: "Search tasks by title or description...", value: searchTerm, onChange: (e) => setSearchTerm(e.target.value), className: "w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Filter, { className: "h-4 w-4 text-gray-400" }), _jsxs("select", { value: selectedCategory, onChange: (e) => setSelectedCategory(e.target.value), className: "px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500", children: [_jsx("option", { value: "", children: "All Categories" }), categories?.map((cat) => (_jsx("option", { value: cat.category_key, children: cat.name }, cat.category_key)))] })] }), _jsxs("label", { className: "flex items-center gap-2 cursor-pointer whitespace-nowrap", children: [_jsx("input", { type: "checkbox", checked: showSystemOnly, onChange: (e) => setShowSystemOnly(e.target.checked), className: "rounded border-gray-300 text-primary-600 focus:ring-primary-500" }), _jsx("span", { className: "text-sm text-gray-700", children: "System tasks only" })] }), hasActiveFilters && (_jsxs(Button, { size: "sm", variant: "ghost", onClick: handleClearFilters, children: [_jsx(X, { className: "h-4 w-4 mr-1" }), "Clear"] }))] }) }) }), taskGroups.length === 0 ? (_jsx(Card, { children: _jsx(EmptyState, { icon: Library, title: "No tasks found", description: hasActiveFilters
                        ? 'Try adjusting your filters or search terms'
                        : 'No task definitions available' }) })) : (_jsx("div", { className: "space-y-8", children: taskGroups.map((group) => (_jsxs("div", { children: [_jsxs("div", { className: "flex items-center gap-3 mb-4", children: [_jsx("div", { className: "w-1 h-6 rounded-full", style: { backgroundColor: group.color || '#6366f1' } }), _jsxs("div", { children: [_jsx("h2", { className: "text-lg font-semibold text-gray-900", children: group.category_name }), group.description && (_jsx("p", { className: "text-sm text-gray-500", children: group.description }))] }), _jsxs("span", { className: "ml-auto text-sm text-gray-500", children: [group.tasks.length, " ", group.tasks.length === 1 ? 'task' : 'tasks'] })] }), _jsx("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4", children: group.tasks.map((task) => (_jsx(TaskCard, { task: task, onEdit: handleOpenEdit }, task.id))) })] }, group.category_key))) })), _jsx(TaskDefinitionForm, { task: selectedTask, isOpen: isFormOpen, onClose: handleCloseForm })] }));
};
