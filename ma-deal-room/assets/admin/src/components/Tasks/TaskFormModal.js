import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { useCreateTask, useUpdateTask } from '@/api/queries/useTasks';
import { useGetTaskDefinitions } from '@/api/queries/useTaskDefinitions';
const TASK_STATUSES = [
    { value: 'pending', label: 'Pending' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'blocked', label: 'Blocked' },
    { value: 'skipped', label: 'Skipped' },
];
const OWNER_ROLES = [
    { value: 'agent', label: 'Agent' },
    { value: 'seller', label: 'Seller' },
    { value: 'buyer', label: 'Buyer' },
    { value: 'seller_attorney', label: 'Seller Attorney' },
    { value: 'buyer_attorney', label: 'Buyer Attorney' },
    { value: 'vendor', label: 'Vendor' },
    { value: 'system', label: 'System' },
];
export const TaskFormModal = ({ isOpen, onClose, transactionId, task, }) => {
    const isEditing = !!task;
    const createTask = useCreateTask();
    const updateTask = useUpdateTask(task?.id || 0);
    const [mode, setMode] = useState('custom');
    const [selectedDefinition, setSelectedDefinition] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const { data: taskDefinitionsData } = useGetTaskDefinitions();
    const taskDefinitions = taskDefinitionsData?.data || [];
    const [formData, setFormData] = useState({
        transaction_id: transactionId,
        task_key: '',
        title: '',
        description: '',
        owner_role: 'agent',
        status: 'pending',
        due_at: '',
        sort_order: 0,
    });
    // Load existing task data when editing
    useEffect(() => {
        if (task) {
            setMode('custom'); // Always use custom mode when editing
            setFormData({
                title: task.title,
                description: task.description || '',
                owner_role: task.owner_role,
                status: task.status,
                due_at: task.due_at ? task.due_at.split(' ')[0] : '', // Convert datetime to date
                sort_order: task.sort_order,
            });
        }
        else {
            // Reset form when creating new task
            setMode('custom');
            setSelectedDefinition(null);
            setSearchQuery('');
            setFormData({
                transaction_id: transactionId,
                task_key: `custom_task_${Date.now()}`,
                title: '',
                description: '',
                owner_role: 'agent',
                status: 'pending',
                due_at: '',
                sort_order: 0,
            });
        }
    }, [task, transactionId, isOpen]);
    // Handle selecting a task from the library
    const handleSelectFromLibrary = (definition) => {
        setSelectedDefinition(definition);
        setFormData({
            transaction_id: transactionId,
            task_key: definition.task_key,
            title: definition.title,
            description: definition.description || '',
            owner_role: definition.owner_role || 'agent',
            status: 'pending',
            due_at: '',
            sort_order: 0,
        });
    };
    // Filter task definitions based on search query
    const filteredDefinitions = taskDefinitions.filter((def) => def.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
        def.description?.toLowerCase().includes(searchQuery.toLowerCase()) ||
        def.category?.toLowerCase().includes(searchQuery.toLowerCase()));
    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (isEditing) {
                await updateTask.mutateAsync(formData);
            }
            else {
                await createTask.mutateAsync(formData);
            }
            onClose();
        }
        catch (error) {
            console.error('Failed to save task:', error);
        }
    };
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: name === 'sort_order' ? parseInt(value) || 0 : value,
        }));
    };
    return (_jsx(Modal, { isOpen: isOpen, onClose: onClose, title: isEditing ? 'Edit Task' : 'Add Task', size: "lg", children: _jsxs("form", { onSubmit: handleSubmit, children: [!isEditing && (_jsx("div", { className: "border-b border-gray-200 mb-4", children: _jsxs("nav", { className: "flex gap-4", children: [_jsx("button", { type: "button", onClick: () => setMode('custom'), className: `py-2 px-1 border-b-2 font-medium text-sm ${mode === 'custom'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`, children: "Custom Task" }), _jsx("button", { type: "button", onClick: () => setMode('library'), className: `py-2 px-1 border-b-2 font-medium text-sm ${mode === 'library'
                                    ? 'border-blue-500 text-blue-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}`, children: "From Task Library" })] }) })), _jsxs("div", { className: "space-y-4", children: [mode === 'library' && !isEditing && (_jsxs("div", { className: "space-y-3", children: [_jsx("div", { children: _jsx("input", { type: "text", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), placeholder: "Search tasks...", className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" }) }), _jsx("div", { className: "max-h-96 overflow-y-auto border border-gray-200 rounded-md", children: filteredDefinitions.length === 0 ? (_jsx("div", { className: "p-4 text-center text-gray-500", children: "No tasks found" })) : (_jsx("div", { className: "divide-y divide-gray-200", children: filteredDefinitions.map((def) => (_jsxs("button", { type: "button", onClick: () => handleSelectFromLibrary(def), className: `w-full text-left p-3 hover:bg-gray-50 transition-colors ${selectedDefinition?.id === def.id ? 'bg-blue-50 border-l-4 border-blue-500' : ''}`, children: [_jsx("div", { className: "font-medium text-gray-900", children: def.title }), def.description && (_jsx("div", { className: "text-sm text-gray-500 mt-1 line-clamp-2", children: def.description })), def.category && (_jsxs("div", { className: "text-xs text-gray-400 mt-1", children: ["Category: ", def.category] }))] }, def.id))) })) }), selectedDefinition && (_jsxs("div", { className: "p-3 bg-blue-50 border border-blue-200 rounded-md", children: [_jsxs("p", { className: "text-sm text-blue-800", children: ["Selected: ", _jsx("strong", { children: selectedDefinition.title })] }), _jsx("p", { className: "text-xs text-blue-600 mt-1", children: "You can modify the details below before adding" })] }))] })), _jsxs("div", { children: [_jsxs("label", { htmlFor: "title", className: "block text-sm font-medium text-gray-700 mb-1", children: ["Title ", _jsx("span", { className: "text-red-500", children: "*" })] }), _jsx("input", { type: "text", id: "title", name: "title", value: formData.title, onChange: handleChange, required: true, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "Enter task title" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "description", className: "block text-sm font-medium text-gray-700 mb-1", children: "Description" }), _jsx("textarea", { id: "description", name: "description", value: formData.description, onChange: handleChange, rows: 3, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "Enter task description" })] }), _jsxs("div", { className: "grid grid-cols-2 gap-4", children: [_jsxs("div", { children: [_jsxs("label", { htmlFor: "owner_role", className: "block text-sm font-medium text-gray-700 mb-1", children: ["Owner Role ", _jsx("span", { className: "text-red-500", children: "*" })] }), _jsx("select", { id: "owner_role", name: "owner_role", value: formData.owner_role, onChange: handleChange, required: true, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", children: OWNER_ROLES.map((role) => (_jsx("option", { value: role.value, children: role.label }, role.value))) })] }), _jsxs("div", { children: [_jsxs("label", { htmlFor: "status", className: "block text-sm font-medium text-gray-700 mb-1", children: ["Status ", _jsx("span", { className: "text-red-500", children: "*" })] }), _jsx("select", { id: "status", name: "status", value: formData.status, onChange: handleChange, required: true, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", children: TASK_STATUSES.map((status) => (_jsx("option", { value: status.value, children: status.label }, status.value))) })] })] }), _jsxs("div", { className: "grid grid-cols-2 gap-4", children: [_jsxs("div", { children: [_jsx("label", { htmlFor: "due_at", className: "block text-sm font-medium text-gray-700 mb-1", children: "Due Date" }), _jsx("input", { type: "date", id: "due_at", name: "due_at", value: formData.due_at, onChange: handleChange, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "sort_order", className: "block text-sm font-medium text-gray-700 mb-1", children: "Sort Order" }), _jsx("input", { type: "number", id: "sort_order", name: "sort_order", value: formData.sort_order, onChange: handleChange, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "0" })] })] })] }), _jsxs(ModalFooter, { children: [_jsx("button", { type: "button", onClick: onClose, className: "px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500", children: "Cancel" }), _jsx("button", { type: "submit", disabled: createTask.isPending || updateTask.isPending, className: "px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed", children: createTask.isPending || updateTask.isPending
                                ? 'Saving...'
                                : isEditing
                                    ? 'Update Task'
                                    : 'Add Task' })] })] }) }));
};
