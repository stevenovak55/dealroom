import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { useGetDocuments, useDeleteDocument, downloadDocument } from '@/api/queries/useDocuments';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { EmptyState } from '@/components/shared/EmptyState';
import { Download, Edit, Trash2, File, FileText, Image, FileSpreadsheet } from 'lucide-react';
import { formatDateTime } from '@/utils/formatDate';
export const DocumentList = ({ transactionId, onEdit }) => {
    const { data: documentsData, isLoading } = useGetDocuments({ transaction_id: transactionId });
    const deleteMutation = useDeleteDocument();
    const [filter, setFilter] = useState('all');
    const documents = documentsData?.data || [];
    const handleDelete = async (documentId) => {
        if (!confirm('Are you sure you want to delete this document? This cannot be undone.')) {
            return;
        }
        try {
            await deleteMutation.mutateAsync(documentId);
        }
        catch (error) {
            console.error('Failed to delete document:', error);
            alert('Failed to delete document. Please try again.');
        }
    };
    const handleDownload = (document) => {
        downloadDocument(document.id, document.file_name);
    };
    const getFileIcon = (mimeType) => {
        if (mimeType.startsWith('image/')) {
            return _jsx(Image, { className: "h-8 w-8 text-blue-500" });
        }
        if (mimeType.includes('pdf')) {
            return _jsx(FileText, { className: "h-8 w-8 text-red-500" });
        }
        if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) {
            return _jsx(FileSpreadsheet, { className: "h-8 w-8 text-green-500" });
        }
        if (mimeType.includes('word') || mimeType.includes('document')) {
            return _jsx(FileText, { className: "h-8 w-8 text-blue-600" });
        }
        return _jsx(File, { className: "h-8 w-8 text-gray-500" });
    };
    const getDocumentTypeBadge = (type) => {
        if (!type)
            return null;
        const typeConfig = {
            contract: { label: 'Contract', variant: 'info' },
            inspection: { label: 'Inspection', variant: 'warning' },
            disclosure: { label: 'Disclosure', variant: 'default' },
            appraisal: { label: 'Appraisal', variant: 'success' },
            title: { label: 'Title', variant: 'info' },
            financing: { label: 'Financing', variant: 'success' },
            insurance: { label: 'Insurance', variant: 'default' },
            other: { label: 'Other', variant: 'default' },
        };
        const config = typeConfig[type] || { label: type, variant: 'default' };
        return _jsx(Badge, { variant: config.variant, children: config.label });
    };
    // Filter documents
    const filteredDocuments = filter === 'all'
        ? documents
        : documents.filter(doc => doc.document_type === filter);
    // Get unique document types for filter
    const documentTypes = Array.from(new Set(documents.map(doc => doc.document_type).filter(Boolean)));
    if (isLoading) {
        return _jsx("div", { className: "text-center py-8 text-gray-500", children: "Loading documents..." });
    }
    if (documents.length === 0) {
        return (_jsx(EmptyState, { icon: File, title: "No documents uploaded", description: "Upload your first document to get started" }));
    }
    return (_jsxs("div", { className: "space-y-4", children: [documentTypes.length > 0 && (_jsxs("div", { className: "flex items-center gap-2 flex-wrap", children: [_jsx("span", { className: "text-sm font-medium text-gray-700", children: "Filter:" }), _jsxs("button", { onClick: () => setFilter('all'), className: `px-3 py-1 text-sm rounded-md ${filter === 'all'
                            ? 'bg-blue-100 text-blue-700'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`, children: ["All (", documents.length, ")"] }), documentTypes.map((type) => {
                        const count = documents.filter(doc => doc.document_type === type).length;
                        return (_jsxs("button", { onClick: () => setFilter(type), className: `px-3 py-1 text-sm rounded-md capitalize ${filter === type
                                ? 'bg-blue-100 text-blue-700'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`, children: [type, " (", count, ")"] }, type));
                    })] })), _jsx("div", { className: "space-y-3", children: filteredDocuments.map((document) => (_jsxs("div", { className: "flex items-start gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow", children: [_jsx("div", { className: "flex-shrink-0 mt-1", children: getFileIcon(document.mime_type) }), _jsx("div", { className: "flex-1 min-w-0", children: _jsxs("div", { className: "flex items-start justify-between gap-4", children: [_jsxs("div", { className: "flex-1 min-w-0", children: [_jsxs("div", { className: "flex items-center gap-2 mb-1", children: [_jsx("h4", { className: "text-sm font-medium text-gray-900 truncate", children: document.title || document.file_name }), getDocumentTypeBadge(document.document_type)] }), document.description && (_jsx("p", { className: "text-sm text-gray-600 mb-2", children: document.description })), _jsxs("div", { className: "flex items-center gap-4 text-xs text-gray-500", children: [_jsx("span", { children: document.formatted_size }), _jsx("span", { children: "\u2022" }), _jsxs("span", { children: ["Uploaded ", formatDateTime(document.created_at)] }), document.file_name !== document.title && (_jsxs(_Fragment, { children: [_jsx("span", { children: "\u2022" }), _jsx("span", { className: "truncate max-w-xs", title: document.file_name, children: document.file_name })] }))] })] }), _jsxs("div", { className: "flex items-center gap-1 flex-shrink-0", children: [_jsx(Button, { size: "sm", variant: "ghost", onClick: () => handleDownload(document), title: "Download", children: _jsx(Download, { className: "h-4 w-4" }) }), onEdit && (_jsx(Button, { size: "sm", variant: "ghost", onClick: () => onEdit(document), title: "Edit", children: _jsx(Edit, { className: "h-4 w-4" }) })), _jsx(Button, { size: "sm", variant: "ghost", onClick: () => handleDelete(document.id), isLoading: deleteMutation.isPending, title: "Delete", children: _jsx(Trash2, { className: "h-4 w-4 text-red-600" }) })] })] }) })] }, document.id))) }), filteredDocuments.length === 0 && filter !== 'all' && (_jsxs("div", { className: "text-center py-8", children: [_jsx("p", { className: "text-gray-500", children: "No documents found with this filter" }), _jsx("button", { onClick: () => setFilter('all'), className: "text-blue-600 hover:text-blue-700 text-sm mt-2", children: "Clear filter" })] }))] }));
};
