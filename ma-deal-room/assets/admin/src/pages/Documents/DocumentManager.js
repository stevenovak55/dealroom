import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { FileText, Search, Download, Upload, Eye, SortAsc, SortDesc, } from 'lucide-react';
import { useGetDocuments } from '@/api/queries/useDocuments';
import { useGetTransactions } from '@/api/queries/useTransactions';
import { Button } from '@/components/shared/Button';
import { Card } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { DocumentViewer } from '@/components/Documents/DocumentViewer';
import { DocumentEditModal } from '@/components/Documents/DocumentEditModal';
import { downloadDocument } from '@/api/queries/useDocuments';
import { formatDateTime } from '@/utils/formatDate';
const DOCUMENT_TYPE_OPTIONS = [
    { value: 'all', label: 'All Types' },
    { value: 'contract', label: 'Contracts' },
    { value: 'inspection', label: 'Inspections' },
    { value: 'disclosure', label: 'Disclosures' },
    { value: 'appraisal', label: 'Appraisals' },
    { value: 'title', label: 'Title Documents' },
    { value: 'financing', label: 'Financing' },
    { value: 'insurance', label: 'Insurance' },
    { value: 'other', label: 'Other' },
];
export const DocumentManager = () => {
    const [searchParams] = useSearchParams();
    const transactionIdParam = searchParams.get('transaction_id');
    // Filters and search
    const [searchQuery, setSearchQuery] = useState('');
    const [documentTypeFilter, setDocumentTypeFilter] = useState('all');
    const [transactionFilter, setTransactionFilter] = useState(transactionIdParam || 'all');
    const [sortField, setSortField] = useState('created_at');
    const [sortOrder, setSortOrder] = useState('desc');
    // Modals
    const [viewingDocument, setViewingDocument] = useState(null);
    const [editingDocument, setEditingDocument] = useState(null);
    // Fetch data
    const { data: documentsData, isLoading } = useGetDocuments({
        transaction_id: transactionFilter !== 'all' ? parseInt(transactionFilter) : undefined,
        document_type: documentTypeFilter !== 'all' ? documentTypeFilter : undefined,
    });
    const { data: transactionsData } = useGetTransactions();
    const documents = documentsData?.data || [];
    const transactions = transactionsData?.data || [];
    // Filter and sort documents
    const filteredAndSortedDocuments = useMemo(() => {
        let filtered = documents;
        // Search filter
        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter((doc) => doc.title?.toLowerCase().includes(query) ||
                doc.file_name.toLowerCase().includes(query) ||
                doc.description?.toLowerCase().includes(query));
        }
        // Sort
        const sorted = [...filtered].sort((a, b) => {
            let aVal;
            let bVal;
            switch (sortField) {
                case 'created_at':
                    aVal = new Date(a.created_at).getTime();
                    bVal = new Date(b.created_at).getTime();
                    break;
                case 'title':
                    aVal = (a.title || a.file_name).toLowerCase();
                    bVal = (b.title || b.file_name).toLowerCase();
                    break;
                case 'file_size':
                    aVal = a.file_size;
                    bVal = b.file_size;
                    break;
                case 'document_type':
                    aVal = a.document_type || '';
                    bVal = b.document_type || '';
                    break;
                default:
                    return 0;
            }
            if (sortOrder === 'asc') {
                return aVal > bVal ? 1 : -1;
            }
            else {
                return aVal < bVal ? 1 : -1;
            }
        });
        return sorted;
    }, [documents, searchQuery, sortField, sortOrder]);
    // Toggle sort
    const toggleSort = (field) => {
        if (sortField === field) {
            setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
        }
        else {
            setSortField(field);
            setSortOrder('desc');
        }
    };
    // Get transaction name
    const getTransactionName = (transactionId) => {
        const transaction = transactions.find((t) => t.transaction_id === transactionId);
        return transaction ? transaction.property_address : `Transaction #${transactionId}`;
    };
    // Document type badge
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
    // Stats
    const stats = useMemo(() => {
        const totalSize = documents.reduce((sum, doc) => sum + doc.file_size, 0);
        const typeCount = {};
        documents.forEach((doc) => {
            const type = doc.document_type || 'other';
            typeCount[type] = (typeCount[type] || 0) + 1;
        });
        return {
            total: documents.length,
            totalSize: totalSize,
            byType: typeCount,
        };
    }, [documents]);
    const formatBytes = (bytes) => {
        if (bytes < 1024)
            return bytes + ' B';
        if (bytes < 1024 * 1024)
            return (bytes / 1024).toFixed(2) + ' KB';
        if (bytes < 1024 * 1024 * 1024)
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
    };
    if (isLoading) {
        return (_jsx("div", { className: "flex items-center justify-center h-96", children: _jsx("div", { className: "animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" }) }));
    }
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-start justify-between", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: "Document Manager" }), _jsx("p", { className: "text-gray-600 mt-1", children: "Manage all transaction documents in one place" })] }), _jsxs(Button, { variant: "primary", disabled: true, children: [_jsx(Upload, { className: "w-4 h-4 mr-2" }), "Upload Documents"] })] }), _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-4 gap-4", children: [_jsx(Card, { className: "p-4", children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600", children: "Total Documents" }), _jsx("p", { className: "text-2xl font-bold text-gray-900 mt-1", children: stats.total })] }), _jsx(FileText, { className: "w-8 h-8 text-blue-500" })] }) }), _jsx(Card, { className: "p-4", children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600", children: "Total Storage" }), _jsx("p", { className: "text-2xl font-bold text-gray-900 mt-1", children: formatBytes(stats.totalSize) })] }), _jsx(Download, { className: "w-8 h-8 text-green-500" })] }) }), _jsx(Card, { className: "p-4", children: _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-2", children: "By Type" }), _jsx("div", { className: "space-y-1", children: Object.entries(stats.byType)
                                        .slice(0, 3)
                                        .map(([type, count]) => (_jsxs("div", { className: "flex items-center justify-between text-sm", children: [_jsx("span", { className: "capitalize text-gray-700", children: type }), _jsx("span", { className: "font-semibold text-gray-900", children: count })] }, type))) })] }) }), _jsx(Card, { className: "p-4", children: _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-2", children: "Quick Actions" }), _jsxs("div", { className: "space-y-2", children: [_jsx("button", { className: "w-full text-left text-sm text-blue-600 hover:text-blue-700", children: "Export list" }), _jsx("button", { className: "w-full text-left text-sm text-blue-600 hover:text-blue-700", children: "Bulk download" })] })] }) })] }), _jsx(Card, { className: "p-4", children: _jsxs("div", { className: "flex flex-wrap gap-4 items-center", children: [_jsx("div", { className: "flex-1 min-w-[250px]", children: _jsxs("div", { className: "relative", children: [_jsx(Search, { className: "absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" }), _jsx("input", { type: "text", placeholder: "Search documents...", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), className: "w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" })] }) }), _jsx("div", { className: "min-w-[180px]", children: _jsx("select", { value: documentTypeFilter, onChange: (e) => setDocumentTypeFilter(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent", children: DOCUMENT_TYPE_OPTIONS.map((option) => (_jsx("option", { value: option.value, children: option.label }, option.value))) }) }), _jsx("div", { className: "min-w-[200px]", children: _jsxs("select", { value: transactionFilter, onChange: (e) => setTransactionFilter(e.target.value), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent", children: [_jsx("option", { value: "all", children: "All Transactions" }), transactions.map((transaction) => (_jsx("option", { value: transaction.transaction_id, children: transaction.property_address }, transaction.transaction_id)))] }) }), _jsxs("div", { className: "text-sm text-gray-600", children: [filteredAndSortedDocuments.length, " of ", documents.length, " documents"] })] }) }), _jsx(Card, { children: _jsx("div", { className: "overflow-x-auto", children: _jsxs("table", { className: "w-full", children: [_jsx("thead", { className: "bg-gray-50 border-b border-gray-200", children: _jsxs("tr", { children: [_jsx("th", { className: "px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100", onClick: () => toggleSort('title'), children: _jsxs("div", { className: "flex items-center gap-1", children: ["Document", sortField === 'title' &&
                                                        (sortOrder === 'asc' ? (_jsx(SortAsc, { className: "w-4 h-4" })) : (_jsx(SortDesc, { className: "w-4 h-4" })))] }) }), _jsx("th", { className: "px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100", onClick: () => toggleSort('document_type'), children: _jsxs("div", { className: "flex items-center gap-1", children: ["Type", sortField === 'document_type' &&
                                                        (sortOrder === 'asc' ? (_jsx(SortAsc, { className: "w-4 h-4" })) : (_jsx(SortDesc, { className: "w-4 h-4" })))] }) }), _jsx("th", { className: "px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider", children: "Transaction" }), _jsx("th", { className: "px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100", onClick: () => toggleSort('file_size'), children: _jsxs("div", { className: "flex items-center gap-1", children: ["Size", sortField === 'file_size' &&
                                                        (sortOrder === 'asc' ? (_jsx(SortAsc, { className: "w-4 h-4" })) : (_jsx(SortDesc, { className: "w-4 h-4" })))] }) }), _jsx("th", { className: "px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100", onClick: () => toggleSort('created_at'), children: _jsxs("div", { className: "flex items-center gap-1", children: ["Uploaded", sortField === 'created_at' &&
                                                        (sortOrder === 'asc' ? (_jsx(SortAsc, { className: "w-4 h-4" })) : (_jsx(SortDesc, { className: "w-4 h-4" })))] }) }), _jsx("th", { className: "px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider", children: "Actions" })] }) }), _jsx("tbody", { className: "bg-white divide-y divide-gray-200", children: filteredAndSortedDocuments.length === 0 ? (_jsx("tr", { children: _jsx("td", { colSpan: 6, className: "px-4 py-8 text-center text-gray-500", children: "No documents found" }) })) : (filteredAndSortedDocuments.map((document) => (_jsxs("tr", { className: "hover:bg-gray-50", children: [_jsx("td", { className: "px-4 py-4", children: _jsxs("div", { children: [_jsx("div", { className: "text-sm font-medium text-gray-900", children: document.title || document.file_name }), document.description && (_jsx("div", { className: "text-sm text-gray-500", children: document.description }))] }) }), _jsx("td", { className: "px-4 py-4", children: getDocumentTypeBadge(document.document_type) }), _jsx("td", { className: "px-4 py-4 text-sm text-gray-900", children: getTransactionName(document.transaction_id) }), _jsx("td", { className: "px-4 py-4 text-sm text-gray-900", children: document.formatted_size }), _jsx("td", { className: "px-4 py-4 text-sm text-gray-500", children: formatDateTime(document.created_at) }), _jsx("td", { className: "px-4 py-4 text-right", children: _jsxs("div", { className: "flex items-center justify-end gap-1", children: [_jsx(Button, { size: "sm", variant: "ghost", onClick: () => setViewingDocument(document), title: "Preview", children: _jsx(Eye, { className: "w-4 h-4" }) }), _jsx(Button, { size: "sm", variant: "ghost", onClick: () => downloadDocument(document.id, document.file_name), title: "Download", children: _jsx(Download, { className: "w-4 h-4" }) })] }) })] }, document.id)))) })] }) }) }), viewingDocument && (_jsx(DocumentViewer, { document: viewingDocument, onClose: () => setViewingDocument(null) })), editingDocument && (_jsx(DocumentEditModal, { isOpen: true, document: editingDocument, onClose: () => setEditingDocument(null) }))] }));
};
