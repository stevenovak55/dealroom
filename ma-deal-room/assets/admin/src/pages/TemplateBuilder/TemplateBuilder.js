import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Save, ArrowLeft, Eye } from 'lucide-react';
import { TaskLibrarySidebar } from './TaskLibrarySidebar';
import { TemplateCanvas } from './TemplateCanvas';
import { useCreateTemplate, useUpdateTemplate, useGetTemplate } from '@/api/queries/useTemplates';
import { Button } from '@/components/shared/Button';
import { Card } from '@/components/shared/Card';
import toast from 'react-hot-toast';
const PROPERTY_TYPES = [
    { value: 'SFH', label: 'Single Family Home' },
    { value: 'Condo', label: 'Condominium' },
    { value: 'Multifamily', label: 'Multifamily' },
    { value: 'Land', label: 'Land' },
    { value: 'Commercial', label: 'Commercial' },
    { value: 'Any', label: 'Any Property Type' },
];
const TRANSACTION_TYPE_OPTIONS = [
    { value: 'all', label: 'All Transaction Types' },
    { value: 'buy_side', label: 'Buy Side' },
    { value: 'sell_side', label: 'Sell Side' },
    { value: 'rental', label: 'Rental' },
    { value: 'commercial', label: 'Commercial' },
    { value: 'dual_agency', label: 'Dual Agency' },
];
export const TemplateBuilder = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const isEditMode = Boolean(id);
    // Template metadata
    const [templateName, setTemplateName] = useState('');
    const [templateDescription, setTemplateDescription] = useState('');
    const [propertyType, setPropertyType] = useState('Any');
    const [transactionType, setTransactionType] = useState('all');
    const [tasks, setTasks] = useState([]);
    const [showPreview, setShowPreview] = useState(false);
    // Mutations
    const createMutation = useCreateTemplate();
    const updateMutation = useUpdateTemplate();
    // Load existing template
    const { data: existingTemplate } = useGetTemplate(id ? parseInt(id) : 0, Boolean(id));
    // Populate form when editing
    useEffect(() => {
        if (existingTemplate) {
            setTemplateName(existingTemplate.name);
            setTemplateDescription(existingTemplate.description || '');
            setPropertyType(existingTemplate.property_type);
            setTransactionType(existingTemplate.transaction_type || 'all');
            // TODO: Parse template_yaml to extract tasks
            // For now, templates will start empty in edit mode
        }
    }, [existingTemplate]);
    // Handle task addition
    const handleTaskAdd = (taskDefinition) => {
        // Check if already exists
        if (tasks.some((t) => t.taskDefinition.id === taskDefinition.id)) {
            toast.error('This task is already in the template');
            return;
        }
        const newTask = {
            id: `temp-${Date.now()}`,
            taskDefinition,
            sortOrder: tasks.length,
            isOptional: false,
        };
        setTasks([...tasks, newTask]);
        toast.success(`Added "${taskDefinition.title}"`);
    };
    // Handle task removal
    const handleTaskRemove = (taskId) => {
        setTasks(tasks.filter((t) => t.id !== taskId));
        toast.success('Task removed');
    };
    // Generate YAML from tasks
    const generateYAML = () => {
        const yamlContent = tasks
            .map((task, index) => {
            const def = task.taskDefinition;
            return `  - task_key: "${def.task_key}"
    title: "${def.title}"
    description: "${def.description || ''}"
    owner_role: "${def.owner_role}"
    priority: "${def.priority}"
    ${def.due_calculation ? `due_calculation: "${def.due_calculation}"` : '# due_calculation: "p_and_s + 7 days"'}
    ${def.estimated_duration ? `estimated_duration: ${def.estimated_duration}` : ''}
    ${def.is_milestone ? 'is_milestone: true' : ''}
    ${def.is_required ? 'is_required: true' : ''}
    ${task.isOptional ? 'is_optional: true' : ''}
    sort_order: ${index + 1}`;
        })
            .join('\n');
        return `# Generated Template: ${templateName}
# Property Type: ${propertyType}
# Transaction Type: ${transactionType}
# Created: ${new Date().toISOString()}

name: "${templateName}"
description: "${templateDescription}"
property_type: "${propertyType}"
transaction_type: "${transactionType}"

tasks:
${yamlContent || '  # No tasks defined'}
`;
    };
    // Handle save
    const handleSave = async () => {
        // Validation
        if (!templateName.trim()) {
            toast.error('Template name is required');
            return;
        }
        if (tasks.length === 0) {
            toast.error('Please add at least one task to the template');
            return;
        }
        const templateYAML = generateYAML();
        try {
            if (isEditMode && id) {
                await updateMutation.mutateAsync({
                    id: parseInt(id),
                    data: {
                        name: templateName,
                        description: templateDescription,
                        property_type: propertyType,
                        transaction_type: transactionType,
                        template_yaml: templateYAML,
                        is_active: true,
                    },
                });
                toast.success('Template updated successfully!');
            }
            else {
                await createMutation.mutateAsync({
                    name: templateName,
                    description: templateDescription,
                    property_type: propertyType,
                    transaction_type: transactionType,
                    template_yaml: templateYAML,
                    is_active: true,
                });
                toast.success('Template created successfully!');
            }
            navigate('/templates');
        }
        catch (error) {
            console.error('Save failed:', error);
            toast.error(error?.message || 'Failed to save template');
        }
    };
    return (_jsxs("div", { className: "h-screen flex flex-col", children: [_jsxs("div", { className: "bg-white border-b border-gray-200 px-6 py-4", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { className: "flex items-center gap-4", children: [_jsxs(Button, { variant: "ghost", size: "sm", onClick: () => navigate('/templates'), children: [_jsx(ArrowLeft, { className: "w-4 h-4 mr-2" }), "Back"] }), _jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: isEditMode ? 'Edit Template' : 'Create Template' }), _jsx("p", { className: "text-sm text-gray-600 mt-1", children: "Build your custom transaction template" })] })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsxs(Button, { variant: "secondary", size: "sm", onClick: () => setShowPreview(!showPreview), children: [_jsx(Eye, { className: "w-4 h-4 mr-2" }), showPreview ? 'Hide' : 'Show', " YAML"] }), _jsxs(Button, { variant: "primary", onClick: handleSave, isLoading: createMutation.isPending || updateMutation.isPending, children: [_jsx(Save, { className: "w-4 h-4 mr-2" }), isEditMode ? 'Update Template' : 'Save Template'] })] })] }), _jsx(Card, { className: "mt-4 p-4", children: _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: "Template Name *" }), _jsx("input", { type: "text", value: templateName, onChange: (e) => setTemplateName(e.target.value), placeholder: "e.g., SFH with Septic", className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: "Property Type *" }), _jsx("select", { value: propertyType, onChange: (e) => setPropertyType(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent", children: PROPERTY_TYPES.map((type) => (_jsx("option", { value: type.value, children: type.label }, type.value))) })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: "Transaction Type *" }), _jsx("select", { value: transactionType, onChange: (e) => setTransactionType(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent", children: TRANSACTION_TYPE_OPTIONS.map((type) => (_jsx("option", { value: type.value, children: type.label }, type.value))) })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: "Description" }), _jsx("input", { type: "text", value: templateDescription, onChange: (e) => setTemplateDescription(e.target.value), placeholder: "Brief description...", className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" })] })] }) })] }), _jsxs("div", { className: "flex-1 flex overflow-hidden", children: [_jsx("div", { className: "w-80 flex-shrink-0 overflow-hidden", children: _jsx(TaskLibrarySidebar, { onTaskSelect: handleTaskAdd }) }), _jsx("div", { className: "flex-1 overflow-hidden", children: _jsx(TemplateCanvas, { tasks: tasks, onTasksChange: setTasks, onTaskRemove: handleTaskRemove }) }), showPreview && (_jsxs("div", { className: "w-96 flex-shrink-0 bg-gray-900 text-gray-100 overflow-auto p-4", children: [_jsx("h3", { className: "text-lg font-semibold mb-3", children: "YAML Preview" }), _jsx("pre", { className: "text-xs font-mono whitespace-pre-wrap", children: generateYAML() })] }))] })] }));
};
