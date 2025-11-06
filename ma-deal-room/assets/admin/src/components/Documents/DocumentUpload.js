import { jsx as _jsx, Fragment as _Fragment, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useCallback, useRef } from 'react';
import { useUploadDocument } from '@/api/queries/useDocuments';
import { Button } from '@/components/shared/Button';
import { Upload, File, X } from 'lucide-react';
import { cn } from '@/utils/cn';
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
export const DocumentUpload = ({ transactionId, onUploadComplete }) => {
    const [selectedFile, setSelectedFile] = useState(null);
    const [documentType, setDocumentType] = useState('other');
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [isDragging, setIsDragging] = useState(false);
    const [error, setError] = useState(null);
    const fileInputRef = useRef(null);
    const uploadMutation = useUploadDocument();
    const handleDragEnter = useCallback((e) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(true);
    }, []);
    const handleDragLeave = useCallback((e) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
    }, []);
    const handleDragOver = useCallback((e) => {
        e.preventDefault();
        e.stopPropagation();
    }, []);
    const handleDrop = useCallback((e) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            setSelectedFile(files[0]);
            setError(null);
            if (!title) {
                setTitle(files[0].name);
            }
        }
    }, [title]);
    const handleFileSelect = (e) => {
        const files = e.target.files;
        if (files && files.length > 0) {
            setSelectedFile(files[0]);
            setError(null);
            if (!title) {
                setTitle(files[0].name);
            }
        }
    };
    const handleRemoveFile = () => {
        setSelectedFile(null);
    };
    const handleUpload = async () => {
        if (!selectedFile) {
            setError('No file selected');
            return;
        }
        setError(null);
        console.log('Uploading file:', {
            name: selectedFile.name,
            size: selectedFile.size,
            type: selectedFile.type,
            transactionId,
            documentType,
            title: title || selectedFile.name,
        });
        try {
            await uploadMutation.mutateAsync({
                transaction_id: transactionId,
                file: selectedFile,
                document_type: documentType,
                title: title || selectedFile.name,
                description,
            });
            // Reset form
            setSelectedFile(null);
            setTitle('');
            setDescription('');
            setDocumentType('other');
            // Callback
            onUploadComplete?.();
        }
        catch (error) {
            console.error('Upload failed:', error);
            const errorMessage = error?.message || error?.response?.data?.message || 'Failed to upload document. Please try again.';
            setError(errorMessage);
        }
    };
    const formatFileSize = (bytes) => {
        if (bytes < 1024)
            return bytes + ' B';
        if (bytes < 1024 * 1024)
            return (bytes / 1024).toFixed(2) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    };
    return (_jsxs("div", { className: "space-y-4", children: [_jsx("div", { onDragEnter: handleDragEnter, onDragOver: handleDragOver, onDragLeave: handleDragLeave, onDrop: handleDrop, className: cn('border-2 border-dashed rounded-lg p-8 text-center transition-colors', isDragging
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-300 hover:border-gray-400', selectedFile && 'border-green-500 bg-green-50'), children: !selectedFile ? (_jsxs(_Fragment, { children: [_jsx(Upload, { className: "h-12 w-12 mx-auto text-gray-400 mb-4" }), _jsx("p", { className: "text-sm text-gray-600 mb-2", children: "Drag and drop a file here, or click to browse" }), _jsx("p", { className: "text-xs text-gray-500 mb-4", children: "Supported: PDF, Word, Excel, Images, Text (max 50MB)" }), _jsx("input", { ref: fileInputRef, type: "file", className: "hidden", onChange: handleFileSelect, accept: ".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt" }), _jsx(Button, { type: "button", variant: "secondary", size: "sm", onClick: () => fileInputRef.current?.click(), children: "Select File" })] })) : (_jsxs("div", { className: "flex items-center justify-between p-4 bg-white rounded-lg border border-gray-200", children: [_jsxs("div", { className: "flex items-center gap-3 flex-1 min-w-0", children: [_jsx(File, { className: "h-8 w-8 text-blue-500 flex-shrink-0" }), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("p", { className: "text-sm font-medium text-gray-900 truncate", children: selectedFile.name }), _jsx("p", { className: "text-xs text-gray-500", children: formatFileSize(selectedFile.size) })] })] }), _jsx("button", { type: "button", onClick: handleRemoveFile, className: "ml-4 p-1 hover:bg-gray-100 rounded", children: _jsx(X, { className: "h-5 w-5 text-gray-500" }) })] })) }), error && (_jsx("div", { className: "p-4 bg-red-50 border border-red-200 rounded-lg", children: _jsx("p", { className: "text-sm text-red-800", children: error }) })), selectedFile && (_jsxs("div", { className: "space-y-4", children: [_jsxs("div", { children: [_jsx("label", { htmlFor: "document_type", className: "block text-sm font-medium text-gray-700 mb-1", children: "Document Type" }), _jsx("select", { id: "document_type", value: documentType, onChange: (e) => setDocumentType(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", children: DOCUMENT_TYPES.map((type) => (_jsx("option", { value: type.value, children: type.label }, type.value))) })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "title", className: "block text-sm font-medium text-gray-700 mb-1", children: "Title" }), _jsx("input", { type: "text", id: "title", value: title, onChange: (e) => setTitle(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "Document title" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "description", className: "block text-sm font-medium text-gray-700 mb-1", children: "Description (Optional)" }), _jsx("textarea", { id: "description", value: description, onChange: (e) => setDescription(e.target.value), rows: 2, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "Add notes about this document" })] }), _jsxs("div", { className: "flex justify-end gap-2", children: [_jsx(Button, { type: "button", variant: "secondary", onClick: () => {
                                    setSelectedFile(null);
                                    setTitle('');
                                    setDescription('');
                                }, children: "Cancel" }), _jsxs(Button, { type: "button", onClick: handleUpload, isLoading: uploadMutation.isPending, children: [_jsx(Upload, { className: "h-4 w-4 mr-2" }), "Upload Document"] })] })] }))] }));
};
