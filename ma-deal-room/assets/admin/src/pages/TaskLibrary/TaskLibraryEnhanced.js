import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { Library, Plus, Filter, X, ArrowUpDown, Eye, Copy } from 'lucide-react';
import { Toaster } from 'react-hot-toast';
import { useGetTaskDefinitions } from '@/api/queries/useTaskDefinitions';
import { useGetTaskCategories } from '@/api/queries/useTaskCategories';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { EmptyState } from '@/components/shared/EmptyState';
import { TaskLibrarySkeleton } from '@/components/shared/Skeleton';
import { DebouncedSearchInput } from '@/components/shared/DebouncedSearchInput';
import { TaskCard } from './TaskCard';
import { TaskDefinitionForm } from './TaskDefinitionForm';
import { TaskDetailDrawer } from './TaskDetailDrawer';
import { TaskLibraryStats } from './TaskLibraryStats';
import { showToast } from '@/utils/toast';
export const TaskLibraryEnhanced = () => {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCategories, setSelectedCategories] = useState([]);
    const [selectedPriorities, setSelectedPriorities] = useState([]);
    const [showSystemOnly, setShowSystemOnly] = useState(true);
    const [sortBy, setSortBy] = useState('category');
    const [sortDirection, setSortDirection] = useState('asc');
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedTask, setSelectedTask] = useState(null);
    const [detailDrawerTask, setDetailDrawerTask] = useState(null);
    const [isDetailDrawerOpen, setIsDetailDrawerOpen] = useState(false);
    const [showFilters, setShowFilters] = useState(false);
    // Fetch grouped task definitions
    const { data: groupedData, isLoading } = useGetTaskDefinitions({
        grouped: true,
        search: searchTerm || undefined,
        is_system: showSystemOnly || undefined,
    });
    const { data: categories } = useGetTaskCategories();
    // Convert and filter grouped data
    const taskGroups = useMemo(() => {
        if (!groupedData || typeof groupedData !== 'object')
            return [];
        let groups = Object.entries(groupedData)
            .map(([key, group]) => ({
            ...group,
            category_key: key,
        }))
            .filter(group => group.tasks && group.tasks.length > 0);
        // Filter by selected categories
        if (selectedCategories.length > 0) {
            groups = groups.filter(group => selectedCategories.includes(group.category_key));
        }
        // Filter by selected priorities
        if (selectedPriorities.length > 0) {
            groups = groups.map(group => ({
                ...group,
                tasks: group.tasks.filter(task => selectedPriorities.includes(task.priority)),
            })).filter(group => group.tasks.length > 0);
        }
        // Sort groups
        if (sortBy === 'category') {
            groups.sort((a, b) => {
                const comparison = a.category_name.localeCompare(b.category_name);
                return sortDirection === 'asc' ? comparison : -comparison;
            });
        }
        // Sort tasks within groups
        groups.forEach(group => {
            group.tasks.sort((a, b) => {
                let comparison = 0;
                switch (sortBy) {
                    case 'name':
                        comparison = a.title.localeCompare(b.title);
                        break;
                    case 'priority':
                        const priorityOrder = { urgent: 4, high: 3, normal: 2, low: 1 };
                        comparison = (priorityOrder[b.priority] || 0) -
                            (priorityOrder[a.priority] || 0);
                        break;
                    default:
                        comparison = 0;
                }
                return sortDirection === 'asc' ? comparison : -comparison;
            });
        });
        return groups;
    }, [groupedData, selectedCategories, selectedPriorities, sortBy, sortDirection]);
    const totalTasks = useMemo(() => {
        return taskGroups.reduce((sum, group) => sum + (group.tasks?.length || 0), 0);
    }, [taskGroups]);
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
    const handleViewDetails = (task) => {
        setDetailDrawerTask(task);
        setIsDetailDrawerOpen(true);
    };
    const handleCloneTask = async (task) => {
        const clonedData = {
            task_key: `${task.task_key}_copy_${Date.now()}`,
            category: task.category,
            title: `${task.title} (Copy)`,
            description: task.description,
            owner_role: task.owner_role,
            priority: task.priority,
            estimated_duration: task.estimated_duration,
            due_calculation: task.due_calculation,
            applies_if: task.applies_if,
            depends_on: task.depends_on,
            metadata: task.metadata,
            is_milestone: task.is_milestone,
            is_required: task.is_required,
        };
        setSelectedTask({
            ...task,
            id: 0,
            task_key: clonedData.task_key,
            title: clonedData.title,
            is_system: false,
        });
        setIsFormOpen(true);
        showToast.info('Task cloned! Modify and save to create.');
    };
    const handleClearFilters = () => {
        setSearchTerm('');
        setSelectedCategories([]);
        setSelectedPriorities([]);
        setShowSystemOnly(true);
    };
    const toggleCategory = (categoryKey) => {
        setSelectedCategories(prev => prev.includes(categoryKey)
            ? prev.filter(c => c !== categoryKey)
            : [...prev, categoryKey]);
    };
    const togglePriority = (priority) => {
        setSelectedPriorities(prev => prev.includes(priority)
            ? prev.filter(p => p !== priority)
            : [...prev, priority]);
    };
    const toggleSort = (option) => {
        if (sortBy === option) {
            setSortDirection(prev => prev === 'asc' ? 'desc' : 'asc');
        }
        else {
            setSortBy(option);
            setSortDirection('asc');
        }
    };
    const hasActiveFilters = searchTerm || selectedCategories.length > 0 ||
        selectedPriorities.length > 0 || !showSystemOnly;
    if (isLoading) {
        return (_jsxs("div", { className: "space-y-6", children: [_jsx(Toaster, { position: "top-right" }), _jsx(TaskLibrarySkeleton, {})] }));
    }
    return (_jsxs("div", { className: "space-y-6", children: [_jsx(Toaster, { position: "top-right" }), _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: "Task Library" }), _jsxs("p", { className: "text-sm text-gray-500 mt-1", children: ["Browse ", totalTasks, " reusable task definitions across ", taskGroups.length, " categories"] })] }), _jsxs(Button, { variant: "primary", onClick: handleOpenCreate, children: [_jsx(Plus, { className: "h-4 w-4 mr-2" }), "Create Custom Task"] })] }), _jsx(TaskLibraryStats, { taskGroups: taskGroups }), _jsx(Card, { children: _jsx(CardContent, { className: "p-4", children: _jsxs("div", { className: "flex flex-col gap-4", children: [_jsxs("div", { className: "flex flex-col md:flex-row gap-4", children: [_jsx(DebouncedSearchInput, { value: searchTerm, onChange: setSearchTerm, placeholder: "Search tasks by title or description...", className: "flex-1" }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsxs(Button, { size: "sm", variant: showFilters ? 'primary' : 'secondary', onClick: () => setShowFilters(!showFilters), children: [_jsx(Filter, { className: "h-4 w-4 mr-2" }), "Filters", (selectedCategories.length + selectedPriorities.length) > 0 && (_jsx("span", { className: "ml-2 bg-primary-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center", children: selectedCategories.length + selectedPriorities.length }))] }), _jsxs(Button, { size: "sm", variant: "secondary", onClick: () => toggleSort(sortBy), children: [_jsx(ArrowUpDown, { className: "h-4 w-4 mr-2" }), "Sort"] }), hasActiveFilters && (_jsxs(Button, { size: "sm", variant: "ghost", onClick: handleClearFilters, children: [_jsx(X, { className: "h-4 w-4 mr-1" }), "Clear"] }))] })] }), showFilters && (_jsxs("div", { className: "border-t border-gray-200 pt-4 space-y-4", children: [_jsxs("div", { children: [_jsx("label", { className: "text-sm font-medium text-gray-700 mb-2 block", children: "Categories" }), _jsx("div", { className: "flex flex-wrap gap-2", children: categories?.map((cat) => (_jsx("button", { onClick: () => toggleCategory(cat.category_key), className: `px-3 py-1 text-sm rounded-full border transition-colors ${selectedCategories.includes(cat.category_key)
                                                        ? 'bg-primary-100 border-primary-500 text-primary-700'
                                                        : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100'}`, children: cat.name }, cat.category_key))) })] }), _jsxs("div", { children: [_jsx("label", { className: "text-sm font-medium text-gray-700 mb-2 block", children: "Priority" }), _jsx("div", { className: "flex flex-wrap gap-2", children: ['low', 'normal', 'high', 'urgent'].map((priority) => (_jsx("button", { onClick: () => togglePriority(priority), className: `px-3 py-1 text-sm rounded-full border capitalize transition-colors ${selectedPriorities.includes(priority)
                                                        ? 'bg-primary-100 border-primary-500 text-primary-700'
                                                        : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100'}`, children: priority }, priority))) })] }), _jsxs("div", { children: [_jsx("label", { className: "text-sm font-medium text-gray-700 mb-2 block", children: "Sort By" }), _jsx("div", { className: "flex flex-wrap gap-2", children: [
                                                    { value: 'category', label: 'Category' },
                                                    { value: 'name', label: 'Name' },
                                                    { value: 'priority', label: 'Priority' },
                                                ].map((option) => (_jsxs("button", { onClick: () => toggleSort(option.value), className: `px-3 py-1 text-sm rounded-full border transition-colors ${sortBy === option.value
                                                        ? 'bg-primary-100 border-primary-500 text-primary-700'
                                                        : 'bg-gray-50 border-gray-300 text-gray-700 hover:bg-gray-100'}`, children: [option.label, sortBy === option.value && (_jsx("span", { className: "ml-1", children: sortDirection === 'asc' ? '↑' : '↓' }))] }, option.value))) })] }), _jsxs("label", { className: "flex items-center gap-2 cursor-pointer", children: [_jsx("input", { type: "checkbox", checked: showSystemOnly, onChange: (e) => setShowSystemOnly(e.target.checked), className: "rounded border-gray-300 text-primary-600 focus:ring-primary-500" }), _jsx("span", { className: "text-sm text-gray-700", children: "Show system tasks only" })] })] }))] }) }) }), taskGroups.length === 0 ? (_jsx(Card, { children: _jsx(EmptyState, { icon: Library, title: "No tasks found", description: hasActiveFilters
                        ? 'Try adjusting your filters or search terms'
                        : 'No task definitions available' }) })) : (_jsx("div", { className: "space-y-8", children: taskGroups.map((group) => (_jsxs("div", { className: "animate-fade-in", children: [_jsxs("div", { className: "flex items-center gap-3 mb-4", children: [_jsx("div", { className: "w-1 h-6 rounded-full", style: { backgroundColor: group.color || '#6366f1' } }), _jsxs("div", { className: "flex-1", children: [_jsx("h2", { className: "text-lg font-semibold text-gray-900", children: group.category_name }), group.description && (_jsx("p", { className: "text-sm text-gray-500", children: group.description }))] }), _jsxs("span", { className: "text-sm text-gray-500", children: [group.tasks.length, " ", group.tasks.length === 1 ? 'task' : 'tasks'] })] }), _jsx("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4", children: group.tasks.map((task) => (_jsxs("div", { className: "relative group", children: [_jsx(TaskCard, { task: task, onEdit: handleOpenEdit }), _jsxs("div", { className: "absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1", children: [_jsx("button", { onClick: () => handleViewDetails(task), className: "p-2 bg-white rounded-lg shadow-md hover:bg-gray-50 transition-colors", title: "View details", children: _jsx(Eye, { className: "h-4 w-4 text-gray-600" }) }), _jsx("button", { onClick: () => handleCloneTask(task), className: "p-2 bg-white rounded-lg shadow-md hover:bg-gray-50 transition-colors", title: "Clone task", children: _jsx(Copy, { className: "h-4 w-4 text-gray-600" }) })] })] }, task.id))) })] }, group.category_key))) })), _jsx(TaskDefinitionForm, { task: selectedTask, isOpen: isFormOpen, onClose: handleCloseForm }), _jsx(TaskDetailDrawer, { task: detailDrawerTask, isOpen: isDetailDrawerOpen, onClose: () => setIsDetailDrawerOpen(false), onEdit: handleOpenEdit, onClone: handleCloneTask })] }));
};
