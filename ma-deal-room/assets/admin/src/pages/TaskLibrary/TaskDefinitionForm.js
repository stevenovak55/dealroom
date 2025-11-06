import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { useGetTaskCategories } from '@/api/queries/useTaskCategories';
import { useCreateTaskDefinition, useUpdateTaskDefinition, } from '@/api/queries/useTaskDefinitions';
import { showToast } from '@/utils/toast';
export const TaskDefinitionForm = ({ task, isOpen, onClose, }) => {
    const { data: categories } = useGetTaskCategories();
    const createMutation = useCreateTaskDefinition();
    const updateMutation = useUpdateTaskDefinition();
    const [formData, setFormData] = useState({
        task_key: '',
        category: '',
        title: '',
        description: '',
        owner_role: 'agent',
        priority: 'normal',
        estimated_duration: undefined,
        due_calculation: '',
        applies_if: '',
        depends_on: [],
        metadata: {},
        is_milestone: false,
        is_required: false,
    });
    const [dependsOnInput, setDependsOnInput] = useState('');
    const [errors, setErrors] = useState({});
    useEffect(() => {
        if (task) {
            setFormData({
                task_key: task.task_key || '',
                category: task.category || '',
                title: task.title || '',
                description: task.description || '',
                owner_role: task.owner_role || 'agent',
                priority: task.priority || 'normal',
                estimated_duration: task.estimated_duration || undefined,
                due_calculation: task.due_calculation || '',
                applies_if: task.applies_if || '',
                depends_on: task.depends_on || [],
                metadata: task.metadata || {},
                is_milestone: task.is_milestone || false,
                is_required: task.is_required || false,
            });
            setDependsOnInput(task.depends_on?.join(', ') || '');
        }
        else {
            setFormData({
                task_key: '',
                category: '',
                title: '',
                description: '',
                owner_role: 'agent',
                priority: 'normal',
                estimated_duration: undefined,
                due_calculation: '',
                applies_if: '',
                depends_on: [],
                metadata: {},
                is_milestone: false,
                is_required: false,
            });
            setDependsOnInput('');
        }
        setErrors({});
    }, [task, isOpen]);
    const handleChange = (field, value) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors((prev) => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }
    };
    const handleDependsOnChange = (value) => {
        setDependsOnInput(value);
        const deps = value
            .split(',')
            .map((d) => d.trim())
            .filter((d) => d.length > 0);
        handleChange('depends_on', deps);
    };
    const validate = () => {
        const newErrors = {};
        if (!formData.task_key.trim()) {
            newErrors.task_key = 'Task key is required';
        }
        else if (!/^[a-z0-9_-]+$/.test(formData.task_key)) {
            newErrors.task_key = 'Task key must contain only lowercase letters, numbers, hyphens, and underscores';
        }
        if (!formData.category) {
            newErrors.category = 'Category is required';
        }
        if (!formData.title.trim()) {
            newErrors.title = 'Title is required';
        }
        if (!formData.owner_role) {
            newErrors.owner_role = 'Owner role is required';
        }
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };
    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!validate()) {
            showToast.error('Please fix validation errors');
            return;
        }
        try {
            const submitData = {
                ...formData,
                estimated_duration: formData.estimated_duration || undefined,
                due_calculation: formData.due_calculation || undefined,
                applies_if: formData.applies_if || undefined,
            };
            if (task) {
                await updateMutation.mutateAsync({
                    id: task.id,
                    data: submitData,
                });
                showToast.success('Task updated successfully');
            }
            else {
                await createMutation.mutateAsync(submitData);
                showToast.success('Task created successfully');
            }
            onClose();
        }
        catch (error) {
            console.error('Failed to save task definition:', error);
            const message = error.response?.data?.message || 'Failed to save task definition. Please try again.';
            showToast.error(message);
        }
    };
    const isLoading = createMutation.isPending || updateMutation.isPending;
    return (_jsx(Modal, { isOpen: isOpen, onClose: onClose, title: task ? 'Edit Custom Task' : 'Create Custom Task', size: "lg", children: _jsxs("form", { onSubmit: handleSubmit, className: "space-y-4", children: [_jsx(Input, { label: "Task Key", value: formData.task_key, onChange: (e) => handleChange('task_key', e.target.value), error: errors.task_key, placeholder: "e.g., custom_inspection_followup", disabled: !!task, helperText: "Unique identifier (lowercase, numbers, hyphens, underscores only)" }), _jsx(Input, { label: "Title", value: formData.title, onChange: (e) => handleChange('title', e.target.value), error: errors.title, placeholder: "e.g., Follow up on inspection results", required: true }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: "Description" }), _jsx("textarea", { value: formData.description, onChange: (e) => handleChange('description', e.target.value), rows: 3, className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500", placeholder: "Describe what this task involves..." })] }), _jsxs("div", { className: "grid grid-cols-2 gap-4", children: [_jsx(Select, { label: "Category", value: formData.category, onChange: (e) => handleChange('category', e.target.value), error: errors.category, required: true, options: [
                                { value: '', label: 'Select category...' },
                                ...(categories?.map((cat) => ({
                                    value: cat.category_key,
                                    label: cat.name,
                                })) || []),
                            ] }), _jsx(Select, { label: "Default Owner", value: formData.owner_role, onChange: (e) => handleChange('owner_role', e.target.value), error: errors.owner_role, required: true, options: [
                                { value: 'agent', label: 'Agent' },
                                { value: 'seller', label: 'Seller' },
                                { value: 'buyer', label: 'Buyer' },
                                { value: 'seller_attorney', label: 'Seller Attorney' },
                                { value: 'buyer_attorney', label: 'Buyer Attorney' },
                                { value: 'vendor', label: 'Vendor' },
                            ] })] }), _jsxs("div", { className: "grid grid-cols-2 gap-4", children: [_jsx(Select, { label: "Priority", value: formData.priority, onChange: (e) => handleChange('priority', e.target.value), options: [
                                { value: 'low', label: 'Low' },
                                { value: 'normal', label: 'Normal' },
                                { value: 'high', label: 'High' },
                                { value: 'urgent', label: 'Urgent' },
                            ] }), _jsx(Input, { label: "Estimated Duration (days)", type: "number", value: formData.estimated_duration || '', onChange: (e) => handleChange('estimated_duration', e.target.value ? parseInt(e.target.value) : undefined), placeholder: "e.g., 3", min: "1" })] }), _jsx(Input, { label: "Due Date Calculation", value: formData.due_calculation, onChange: (e) => handleChange('due_calculation', e.target.value), placeholder: "e.g., Closing-21d, PS+7d, listing_date+5d", helperText: "Relative to closing, P&S (PS), or listing_date. Use format: Closing-21d" }), _jsxs("div", { className: "border-t border-gray-200 pt-4", children: [_jsx("h3", { className: "text-sm font-medium text-gray-900 mb-3", children: "Advanced Options" }), _jsx(Input, { label: "Applies If (Condition)", value: formData.applies_if, onChange: (e) => handleChange('applies_if', e.target.value), placeholder: "e.g., property.has_septic == true", helperText: "Task will only be created if this condition is met" }), _jsx("div", { className: "mt-3", children: _jsx(Input, { label: "Depends On (Task Keys)", value: dependsOnInput, onChange: (e) => handleDependsOnChange(e.target.value), placeholder: "e.g., task1, task2, task3", helperText: "Comma-separated list of task keys this task depends on" }) }), _jsxs("div", { className: "mt-3 space-y-2", children: [_jsxs("label", { className: "flex items-center gap-2 cursor-pointer", children: [_jsx("input", { type: "checkbox", checked: formData.is_milestone, onChange: (e) => handleChange('is_milestone', e.target.checked), className: "rounded border-gray-300 text-primary-600 focus:ring-primary-500" }), _jsx("span", { className: "text-sm text-gray-700", children: "Mark as milestone" })] }), _jsxs("label", { className: "flex items-center gap-2 cursor-pointer", children: [_jsx("input", { type: "checkbox", checked: formData.is_required, onChange: (e) => handleChange('is_required', e.target.checked), className: "rounded border-gray-300 text-primary-600 focus:ring-primary-500" }), _jsx("span", { className: "text-sm text-gray-700", children: "Required task (cannot be skipped)" })] })] })] }), _jsxs(ModalFooter, { children: [_jsx(Button, { variant: "ghost", onClick: onClose, disabled: isLoading, children: "Cancel" }), _jsx(Button, { type: "submit", variant: "primary", isLoading: isLoading, children: task ? 'Update Task' : 'Create Task' })] })] }) }));
};
