import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { X, Plus } from 'lucide-react';
import { useGetTemplates } from '@/api/queries/useTemplates';
import { useApplyTemplate } from '@/api/queries/useTransactions';
import { Button } from '@/components/shared/Button';
import { Modal } from '@/components/shared/Modal';
export const ApplyTemplateModal = ({ isOpen, onClose, transactionId, }) => {
    const [selectedTemplateId, setSelectedTemplateId] = useState(null);
    const { data: templatesData, isLoading: templatesLoading } = useGetTemplates();
    const applyMutation = useApplyTemplate(transactionId);
    const templates = templatesData?.data || [];
    const handleApply = async () => {
        if (!selectedTemplateId) {
            alert('Please select a template');
            return;
        }
        try {
            const result = await applyMutation.mutateAsync(selectedTemplateId);
            alert(result.message || 'Template applied successfully!');
            onClose();
        }
        catch (error) {
            console.error('Failed to apply template:', error);
            alert('Failed to apply template. Please try again.');
        }
    };
    const handleClose = () => {
        setSelectedTemplateId(null);
        onClose();
    };
    return (_jsx(Modal, { isOpen: isOpen, onClose: handleClose, children: _jsxs("div", { className: "bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col", children: [_jsxs("div", { className: "px-6 py-4 border-b border-gray-200 flex items-center justify-between", children: [_jsx("h2", { className: "text-xl font-semibold text-gray-900", children: "Apply Template" }), _jsx("button", { onClick: handleClose, className: "text-gray-400 hover:text-gray-500", children: _jsx(X, { className: "h-5 w-5" }) })] }), _jsxs("div", { className: "px-6 py-4 overflow-y-auto flex-1", children: [_jsx("p", { className: "text-sm text-gray-600 mb-4", children: "Select a template to apply to this transaction. Tasks from the template will be added to your existing tasks." }), templatesLoading ? (_jsx("div", { className: "text-center py-8", children: _jsx("p", { className: "text-gray-500", children: "Loading templates..." }) })) : templates.length === 0 ? (_jsx("div", { className: "text-center py-8", children: _jsx("p", { className: "text-gray-500", children: "No templates available" }) })) : (_jsx("div", { className: "space-y-3", children: templates.map((template) => (_jsxs("label", { className: `flex items-start p-4 border-2 rounded-lg cursor-pointer transition-colors ${selectedTemplateId === template.template_id
                                    ? 'border-primary-500 bg-primary-50'
                                    : 'border-gray-200 hover:border-primary-300'}`, children: [_jsx("input", { type: "radio", name: "template", value: template.template_id, checked: selectedTemplateId === template.template_id, onChange: () => setSelectedTemplateId(template.template_id), className: "mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300" }), _jsxs("div", { className: "ml-3 flex-1", children: [_jsx("p", { className: "font-medium text-gray-900", children: template.name }), template.description && (_jsx("p", { className: "text-sm text-gray-500 mt-1", children: template.description })), _jsxs("p", { className: "text-xs text-gray-400 mt-2", children: [template.task_count || 0, " tasks"] })] })] }, template.template_id))) }))] }), _jsxs("div", { className: "px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3", children: [_jsx(Button, { variant: "secondary", onClick: handleClose, children: "Cancel" }), _jsxs(Button, { variant: "primary", onClick: handleApply, isLoading: applyMutation.isPending, disabled: !selectedTemplateId, children: [_jsx(Plus, { className: "h-4 w-4 mr-2" }), "Apply Template"] })] })] }) }));
};
