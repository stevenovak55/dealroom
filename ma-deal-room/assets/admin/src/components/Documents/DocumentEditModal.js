import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { useUpdateDocument } from '@/api/queries/useDocuments';
const DOCUMENT_TYPES = [
    { value: 'contract', label: 'Contract' },
    { value: 'inspection', label: 'Inspection Report' },
    { value: 'disclosure', label: 'Disclosure' },
    { value: 'appraisal', label: 'Appraisal' },
    { value: 'title', label: 'Title Document' },
    { value: 'financing', label: 'Financing Document' },
    { value: 'insurance', label: 'Insurance' },
    { value: 'other', label: 'Other' },
];
export const DocumentEditModal = ({ isOpen, onClose, document, }) => {
    const updateMutation = useUpdateDocument(document.id);
    const [formData, setFormData] = useState({
        title: document.title || document.file_name,
        description: document.description || '',
        document_type: document.document_type || 'other',
        is_public: document.is_public,
    });
    useEffect(() => {
        if (isOpen) {
            setFormData({
                title: document.title || document.file_name,
                description: document.description || '',
                document_type: document.document_type || 'other',
                is_public: document.is_public,
            });
        }
    }, [document, isOpen]);
    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            await updateMutation.mutateAsync(formData);
            onClose();
        }
        catch (error) {
            console.error('Failed to update document:', error);
        }
    };
    const handleChange = (e) => {
        const { name, value, type } = e.target;
        setFormData((prev) => ({
            ...prev,
            [name]: type === 'checkbox' ? e.target.checked : value,
        }));
    };
    return (_jsx(Modal, { isOpen: isOpen, onClose: onClose, title: "Edit Document", size: "md", children: _jsxs("form", { onSubmit: handleSubmit, children: [_jsxs("div", { className: "space-y-4", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: "File Name" }), _jsx("input", { type: "text", value: document.file_name, disabled: true, className: "w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "title", className: "block text-sm font-medium text-gray-700 mb-1", children: "Title" }), _jsx("input", { type: "text", id: "title", name: "title", value: formData.title, onChange: handleChange, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "Document title" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "document_type", className: "block text-sm font-medium text-gray-700 mb-1", children: "Document Type" }), _jsx("select", { id: "document_type", name: "document_type", value: formData.document_type, onChange: handleChange, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", children: DOCUMENT_TYPES.map((type) => (_jsx("option", { value: type.value, children: type.label }, type.value))) })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "description", className: "block text-sm font-medium text-gray-700 mb-1", children: "Description" }), _jsx("textarea", { id: "description", name: "description", value: formData.description, onChange: handleChange, rows: 3, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "Add notes about this document" })] }), _jsxs("div", { className: "flex items-center", children: [_jsx("input", { type: "checkbox", id: "is_public", name: "is_public", checked: formData.is_public, onChange: handleChange, className: "h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" }), _jsx("label", { htmlFor: "is_public", className: "ml-2 block text-sm text-gray-700", children: "Make this document publicly accessible (generates a shareable link)" })] })] }), _jsxs(ModalFooter, { children: [_jsx("button", { type: "button", onClick: onClose, className: "px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500", children: "Cancel" }), _jsx("button", { type: "submit", disabled: updateMutation.isPending, className: "px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed", children: updateMutation.isPending ? 'Saving...' : 'Save Changes' })] })] }) }));
};
