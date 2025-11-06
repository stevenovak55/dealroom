import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Layout, FileText, Plus, Edit, Trash2, Sparkles, Copy, History, BarChart3, Download, Upload, MoreVertical } from 'lucide-react';
import { useGetTemplates, useCreateTemplate, useUpdateTemplate, useDeleteTemplate, useCloneTemplate, useExportTemplate, useImportTemplate } from '@/api/queries/useTemplates';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { TemplateForm } from './TemplateForm';
import { TemplateVersionHistory } from '@/components/Templates/TemplateVersionHistory';
import toast from 'react-hot-toast';
export const TemplatesList = () => {
    const navigate = useNavigate();
    const { data, isLoading } = useGetTemplates({ is_active: true });
    const createMutation = useCreateTemplate();
    const updateMutation = useUpdateTemplate();
    const deleteMutation = useDeleteTemplate();
    const cloneMutation = useCloneTemplate();
    const exportMutation = useExportTemplate();
    const importMutation = useImportTemplate();
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [selectedTemplate, setSelectedTemplate] = useState(null);
    const [versionHistoryTemplate, setVersionHistoryTemplate] = useState(null);
    const [showDropdown, setShowDropdown] = useState(null);
    const templates = data?.data || [];
    if (isLoading) {
        return _jsx(PageLoader, {});
    }
    const handleOpenCreate = () => {
        setSelectedTemplate(null);
        setIsFormOpen(true);
    };
    const handleOpenEdit = (template) => {
        setSelectedTemplate(template);
        setIsFormOpen(true);
    };
    const handleCloseForm = () => {
        setIsFormOpen(false);
        setSelectedTemplate(null);
    };
    const handleSubmit = async (data) => {
        try {
            if (selectedTemplate) {
                await updateMutation.mutateAsync({ id: selectedTemplate.template_id, data });
            }
            else {
                await createMutation.mutateAsync(data);
            }
            handleCloseForm();
        }
        catch (error) {
            console.error('Failed to save template:', error);
            alert('Failed to save template. Please try again.');
        }
    };
    const handleDelete = async (template) => {
        if (template.is_system) {
            toast.error('System templates cannot be deleted.');
            return;
        }
        if (!confirm(`Are you sure you want to delete "${template.name}"? This cannot be undone.`)) {
            return;
        }
        try {
            await deleteMutation.mutateAsync(template.template_id);
            toast.success('Template deleted successfully');
        }
        catch (error) {
            console.error('Failed to delete template:', error);
            const message = error.response?.data?.message || 'Failed to delete template';
            toast.error(message);
        }
    };
    const handleClone = async (template) => {
        const newName = prompt(`Enter name for cloned template:`, `${template.name} (Copy)`);
        if (!newName)
            return;
        try {
            await cloneMutation.mutateAsync({ id: template.template_id, newName });
            toast.success('Template cloned successfully');
        }
        catch (error) {
            console.error('Failed to clone template:', error);
            toast.error(error?.message || 'Failed to clone template');
        }
    };
    const handleExport = async (template) => {
        try {
            const blob = await exportMutation.mutateAsync(template.template_id);
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `template-${template.name.toLowerCase().replace(/\s+/g, '-')}.yaml`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            toast.success('Template exported successfully');
        }
        catch (error) {
            console.error('Failed to export template:', error);
            toast.error('Failed to export template');
        }
    };
    const handleImport = async (event) => {
        const file = event.target.files?.[0];
        if (!file)
            return;
        try {
            await importMutation.mutateAsync(file);
            toast.success('Template imported successfully');
            event.target.value = ''; // Reset input
        }
        catch (error) {
            console.error('Failed to import template:', error);
            toast.error(error?.message || 'Failed to import template');
        }
    };
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: "Templates" }), _jsx("p", { className: "text-sm text-gray-500 mt-1", children: "Pre-configured checklists for different property types" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsxs("div", { children: [_jsx("input", { id: "import-template", type: "file", accept: ".yaml,.yml", className: "hidden", onChange: handleImport }), _jsx("label", { htmlFor: "import-template", children: _jsxs(Button, { variant: "secondary", children: [_jsx(Upload, { className: "h-4 w-4 mr-2" }), "Import"] }) })] }), _jsxs(Button, { variant: "secondary", onClick: () => navigate('/templates/new'), children: [_jsx(Sparkles, { className: "h-4 w-4 mr-2" }), "Visual Builder"] }), _jsxs(Button, { variant: "primary", onClick: handleOpenCreate, children: [_jsx(Plus, { className: "h-4 w-4 mr-2" }), "Quick Create"] })] })] }), templates.length === 0 ? (_jsx(Card, { children: _jsx(EmptyState, { icon: Layout, title: "No templates available", description: "Contact your administrator to set up templates" }) })) : (_jsx("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6", children: templates.map((template) => (_jsxs(Card, { className: "hover:shadow-lg transition-shadow", children: [_jsx(CardHeader, { children: _jsxs(CardTitle, { className: "flex items-start justify-between", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx(FileText, { className: "h-5 w-5 text-primary-600" }), _jsx("span", { className: "text-base", children: template.name })] }), template.is_system && _jsx(Badge, { variant: "info", children: "System" })] }) }), _jsxs(CardContent, { children: [_jsx("p", { className: "text-sm text-gray-600 mb-4 line-clamp-3", children: template.description || 'No description available' }), _jsxs("div", { className: "flex items-center justify-between text-sm", children: [_jsx("span", { className: "text-gray-500", children: "Property Type" }), _jsx(Badge, { variant: "default", children: template.property_type })] }), _jsxs("div", { className: "flex items-center justify-between text-sm mt-2", children: [_jsx("span", { className: "text-gray-500", children: "Tasks" }), _jsx("span", { className: "font-medium text-gray-900", children: template.task_count || 0 })] }), _jsxs("div", { className: "flex items-center justify-between text-sm mt-2", children: [_jsx("span", { className: "text-gray-500", children: "Version" }), _jsxs("button", { onClick: () => setVersionHistoryTemplate(template), className: "font-medium text-blue-600 hover:text-blue-700 flex items-center gap-1", children: ["v", template.version, _jsx(History, { className: "h-3 w-3" })] })] }), template.usage_count !== undefined && (_jsxs("div", { className: "flex items-center justify-between text-sm mt-2", children: [_jsx("span", { className: "text-gray-500", children: "Usage" }), _jsx("span", { className: "font-medium text-gray-900", children: template.usage_count })] })), _jsxs("div", { className: "flex items-center gap-2 mt-4 pt-4 border-t border-gray-200", children: [_jsxs(Button, { size: "sm", variant: "secondary", onClick: () => navigate(`/templates/${template.template_id}/analytics`), className: "flex-1", children: [_jsx(BarChart3, { className: "h-3 w-3 mr-1" }), "Analytics"] }), !template.is_system && (_jsxs(_Fragment, { children: [_jsx(Button, { size: "sm", variant: "secondary", onClick: () => handleOpenEdit(template), children: _jsx(Edit, { className: "h-3 w-3" }) }), _jsxs("div", { className: "relative", children: [_jsx(Button, { size: "sm", variant: "secondary", onClick: () => setShowDropdown(showDropdown === template.template_id ? null : template.template_id), children: _jsx(MoreVertical, { className: "h-3 w-3" }) }), showDropdown === template.template_id && (_jsxs("div", { className: "absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10", children: [_jsxs("button", { onClick: () => {
                                                                        handleClone(template);
                                                                        setShowDropdown(null);
                                                                    }, className: "w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2", children: [_jsx(Copy, { className: "h-4 w-4" }), "Clone Template"] }), _jsxs("button", { onClick: () => {
                                                                        handleExport(template);
                                                                        setShowDropdown(null);
                                                                    }, className: "w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2", children: [_jsx(Download, { className: "h-4 w-4" }), "Export YAML"] }), _jsxs("button", { onClick: () => {
                                                                        setVersionHistoryTemplate(template);
                                                                        setShowDropdown(null);
                                                                    }, className: "w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2", children: [_jsx(History, { className: "h-4 w-4" }), "Version History"] }), _jsx("div", { className: "border-t border-gray-200 my-1" }), _jsxs("button", { onClick: () => {
                                                                        handleDelete(template);
                                                                        setShowDropdown(null);
                                                                    }, className: "w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2", children: [_jsx(Trash2, { className: "h-4 w-4" }), "Delete"] })] }))] })] }))] })] })] }, template.template_id))) })), _jsx(TemplateForm, { template: selectedTemplate, isOpen: isFormOpen, onClose: handleCloseForm, onSubmit: handleSubmit, isLoading: createMutation.isPending || updateMutation.isPending }), versionHistoryTemplate && (_jsx(TemplateVersionHistory, { templateId: versionHistoryTemplate.template_id, templateName: versionHistoryTemplate.name, isOpen: true, onClose: () => setVersionHistoryTemplate(null) }))] }));
};
