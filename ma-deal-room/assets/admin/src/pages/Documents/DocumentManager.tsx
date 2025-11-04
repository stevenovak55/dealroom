import React, { useState, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  FileText,
  Search,
  Download,
  Upload,
  Eye,
  SortAsc,
  SortDesc,
} from 'lucide-react';
import { useGetDocuments } from '@/api/queries/useDocuments';
import { useGetTransactions } from '@/api/queries/useTransactions';
import { Button } from '@/components/shared/Button';
import { Card } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { DocumentViewer } from '@/components/Documents/DocumentViewer';
import { DocumentEditModal } from '@/components/Documents/DocumentEditModal';
import { downloadDocument } from '@/api/queries/useDocuments';
import type { Document } from '@/api/types';
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

type SortField = 'created_at' | 'title' | 'file_size' | 'document_type';
type SortOrder = 'asc' | 'desc';

export const DocumentManager: React.FC = () => {
  const [searchParams] = useSearchParams();
  const transactionIdParam = searchParams.get('transaction_id');

  // Filters and search
  const [searchQuery, setSearchQuery] = useState('');
  const [documentTypeFilter, setDocumentTypeFilter] = useState<string>('all');
  const [transactionFilter, setTransactionFilter] = useState<string>(transactionIdParam || 'all');
  const [sortField, setSortField] = useState<SortField>('created_at');
  const [sortOrder, setSortOrder] = useState<SortOrder>('desc');

  // Modals
  const [viewingDocument, setViewingDocument] = useState<Document | null>(null);
  const [editingDocument, setEditingDocument] = useState<Document | null>(null);

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
      filtered = filtered.filter(
        (doc) =>
          doc.title?.toLowerCase().includes(query) ||
          doc.file_name.toLowerCase().includes(query) ||
          doc.description?.toLowerCase().includes(query)
      );
    }

    // Sort
    const sorted = [...filtered].sort((a, b) => {
      let aVal: any;
      let bVal: any;

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
      } else {
        return aVal < bVal ? 1 : -1;
      }
    });

    return sorted;
  }, [documents, searchQuery, sortField, sortOrder]);

  // Toggle sort
  const toggleSort = (field: SortField) => {
    if (sortField === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortField(field);
      setSortOrder('desc');
    }
  };

  // Get transaction name
  const getTransactionName = (transactionId: number) => {
    const transaction = transactions.find((t) => t.transaction_id === transactionId);
    return transaction ? transaction.property_address : `Transaction #${transactionId}`;
  };

  // Document type badge
  const getDocumentTypeBadge = (type?: string) => {
    if (!type) return null;

    const typeConfig: Record<
      string,
      { label: string; variant: 'default' | 'success' | 'warning' | 'danger' | 'info' }
    > = {
      contract: { label: 'Contract', variant: 'info' },
      inspection: { label: 'Inspection', variant: 'warning' },
      disclosure: { label: 'Disclosure', variant: 'default' },
      appraisal: { label: 'Appraisal', variant: 'success' },
      title: { label: 'Title', variant: 'info' },
      financing: { label: 'Financing', variant: 'success' },
      insurance: { label: 'Insurance', variant: 'default' },
      other: { label: 'Other', variant: 'default' },
    };

    const config = typeConfig[type] || { label: type, variant: 'default' as const };
    return <Badge variant={config.variant}>{config.label}</Badge>;
  };

  // Stats
  const stats = useMemo(() => {
    const totalSize = documents.reduce((sum, doc) => sum + doc.file_size, 0);
    const typeCount: Record<string, number> = {};
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

  const formatBytes = (bytes: number): string => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    return (bytes / (1024 * 1024 * 1024)).toFixed(2) + ' GB';
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Document Manager</h1>
          <p className="text-gray-600 mt-1">
            Manage all transaction documents in one place
          </p>
        </div>
        <Button variant="primary" disabled>
          <Upload className="w-4 h-4 mr-2" />
          Upload Documents
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Total Documents</p>
              <p className="text-2xl font-bold text-gray-900 mt-1">{stats.total}</p>
            </div>
            <FileText className="w-8 h-8 text-blue-500" />
          </div>
        </Card>
        <Card className="p-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-gray-600">Total Storage</p>
              <p className="text-2xl font-bold text-gray-900 mt-1">{formatBytes(stats.totalSize)}</p>
            </div>
            <Download className="w-8 h-8 text-green-500" />
          </div>
        </Card>
        <Card className="p-4">
          <div>
            <p className="text-sm text-gray-600 mb-2">By Type</p>
            <div className="space-y-1">
              {Object.entries(stats.byType)
                .slice(0, 3)
                .map(([type, count]) => (
                  <div key={type} className="flex items-center justify-between text-sm">
                    <span className="capitalize text-gray-700">{type}</span>
                    <span className="font-semibold text-gray-900">{count}</span>
                  </div>
                ))}
            </div>
          </div>
        </Card>
        <Card className="p-4">
          <div>
            <p className="text-sm text-gray-600 mb-2">Quick Actions</p>
            <div className="space-y-2">
              <button className="w-full text-left text-sm text-blue-600 hover:text-blue-700">
                Export list
              </button>
              <button className="w-full text-left text-sm text-blue-600 hover:text-blue-700">
                Bulk download
              </button>
            </div>
          </div>
        </Card>
      </div>

      {/* Filters */}
      <Card className="p-4">
        <div className="flex flex-wrap gap-4 items-center">
          {/* Search */}
          <div className="flex-1 min-w-[250px]">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search documents..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* Document Type Filter */}
          <div className="min-w-[180px]">
            <select
              value={documentTypeFilter}
              onChange={(e) => setDocumentTypeFilter(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              {DOCUMENT_TYPE_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>

          {/* Transaction Filter */}
          <div className="min-w-[200px]">
            <select
              value={transactionFilter}
              onChange={(e) => setTransactionFilter(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Transactions</option>
              {transactions.map((transaction) => (
                <option key={transaction.transaction_id} value={transaction.transaction_id}>
                  {transaction.property_address}
                </option>
              ))}
            </select>
          </div>

          {/* Results count */}
          <div className="text-sm text-gray-600">
            {filteredAndSortedDocuments.length} of {documents.length} documents
          </div>
        </div>
      </Card>

      {/* Documents Table */}
      <Card>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th
                  className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  onClick={() => toggleSort('title')}
                >
                  <div className="flex items-center gap-1">
                    Document
                    {sortField === 'title' &&
                      (sortOrder === 'asc' ? (
                        <SortAsc className="w-4 h-4" />
                      ) : (
                        <SortDesc className="w-4 h-4" />
                      ))}
                  </div>
                </th>
                <th
                  className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  onClick={() => toggleSort('document_type')}
                >
                  <div className="flex items-center gap-1">
                    Type
                    {sortField === 'document_type' &&
                      (sortOrder === 'asc' ? (
                        <SortAsc className="w-4 h-4" />
                      ) : (
                        <SortDesc className="w-4 h-4" />
                      ))}
                  </div>
                </th>
                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Transaction
                </th>
                <th
                  className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  onClick={() => toggleSort('file_size')}
                >
                  <div className="flex items-center gap-1">
                    Size
                    {sortField === 'file_size' &&
                      (sortOrder === 'asc' ? (
                        <SortAsc className="w-4 h-4" />
                      ) : (
                        <SortDesc className="w-4 h-4" />
                      ))}
                  </div>
                </th>
                <th
                  className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  onClick={() => toggleSort('created_at')}
                >
                  <div className="flex items-center gap-1">
                    Uploaded
                    {sortField === 'created_at' &&
                      (sortOrder === 'asc' ? (
                        <SortAsc className="w-4 h-4" />
                      ) : (
                        <SortDesc className="w-4 h-4" />
                      ))}
                  </div>
                </th>
                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredAndSortedDocuments.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                    No documents found
                  </td>
                </tr>
              ) : (
                filteredAndSortedDocuments.map((document) => (
                  <tr key={document.id} className="hover:bg-gray-50">
                    <td className="px-4 py-4">
                      <div>
                        <div className="text-sm font-medium text-gray-900">
                          {document.title || document.file_name}
                        </div>
                        {document.description && (
                          <div className="text-sm text-gray-500">{document.description}</div>
                        )}
                      </div>
                    </td>
                    <td className="px-4 py-4">
                      {getDocumentTypeBadge(document.document_type)}
                    </td>
                    <td className="px-4 py-4 text-sm text-gray-900">
                      {getTransactionName(document.transaction_id)}
                    </td>
                    <td className="px-4 py-4 text-sm text-gray-900">
                      {document.formatted_size}
                    </td>
                    <td className="px-4 py-4 text-sm text-gray-500">
                      {formatDateTime(document.created_at)}
                    </td>
                    <td className="px-4 py-4 text-right">
                      <div className="flex items-center justify-end gap-1">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => setViewingDocument(document)}
                          title="Preview"
                        >
                          <Eye className="w-4 h-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => downloadDocument(document.id, document.file_name)}
                          title="Download"
                        >
                          <Download className="w-4 h-4" />
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </Card>

      {/* Document Viewer Modal */}
      {viewingDocument && (
        <DocumentViewer
          document={viewingDocument}
          onClose={() => setViewingDocument(null)}
        />
      )}

      {/* Document Edit Modal */}
      {editingDocument && (
        <DocumentEditModal
          isOpen={true}
          document={editingDocument}
          onClose={() => setEditingDocument(null)}
        />
      )}
    </div>
  );
};
