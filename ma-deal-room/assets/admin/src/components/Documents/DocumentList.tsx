import { useState } from 'react';
import { useGetDocuments, useDeleteDocument, downloadDocument } from '@/api/queries/useDocuments';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { EmptyState } from '@/components/shared/EmptyState';
import { Download, Edit, Trash2, File, FileText, Image, FileSpreadsheet } from 'lucide-react';
import { formatDateTime } from '@/utils/formatDate';
import type { Document } from '@/api/types';

interface DocumentListProps {
  transactionId: number;
  onEdit?: (document: Document) => void;
}

export const DocumentList = ({ transactionId, onEdit }: DocumentListProps) => {
  const { data: documentsData, isLoading } = useGetDocuments({ transaction_id: transactionId });
  const deleteMutation = useDeleteDocument();
  const [filter, setFilter] = useState<string>('all');

  const documents = documentsData?.data || [];

  const handleDelete = async (documentId: number) => {
    if (!confirm('Are you sure you want to delete this document? This cannot be undone.')) {
      return;
    }

    try {
      await deleteMutation.mutateAsync(documentId);
    } catch (error) {
      console.error('Failed to delete document:', error);
      alert('Failed to delete document. Please try again.');
    }
  };

  const handleDownload = (document: Document) => {
    downloadDocument(document.id, document.file_name);
  };

  const getFileIcon = (mimeType: string) => {
    if (mimeType.startsWith('image/')) {
      return <Image className="h-8 w-8 text-blue-500" />;
    }
    if (mimeType.includes('pdf')) {
      return <FileText className="h-8 w-8 text-red-500" />;
    }
    if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) {
      return <FileSpreadsheet className="h-8 w-8 text-green-500" />;
    }
    if (mimeType.includes('word') || mimeType.includes('document')) {
      return <FileText className="h-8 w-8 text-blue-600" />;
    }
    return <File className="h-8 w-8 text-gray-500" />;
  };

  const getDocumentTypeBadge = (type?: string) => {
    if (!type) return null;

    const typeConfig: Record<string, { label: string; variant: 'default' | 'success' | 'warning' | 'danger' | 'info' }> = {
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

  // Filter documents
  const filteredDocuments = filter === 'all'
    ? documents
    : documents.filter(doc => doc.document_type === filter);

  // Get unique document types for filter
  const documentTypes = Array.from(new Set(documents.map(doc => doc.document_type).filter(Boolean)));

  if (isLoading) {
    return <div className="text-center py-8 text-gray-500">Loading documents...</div>;
  }

  if (documents.length === 0) {
    return (
      <EmptyState
        icon={File}
        title="No documents uploaded"
        description="Upload your first document to get started"
      />
    );
  }

  return (
    <div className="space-y-4">
      {/* Filter */}
      {documentTypes.length > 0 && (
        <div className="flex items-center gap-2 flex-wrap">
          <span className="text-sm font-medium text-gray-700">Filter:</span>
          <button
            onClick={() => setFilter('all')}
            className={`px-3 py-1 text-sm rounded-md ${
              filter === 'all'
                ? 'bg-blue-100 text-blue-700'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            }`}
          >
            All ({documents.length})
          </button>
          {documentTypes.map((type) => {
            const count = documents.filter(doc => doc.document_type === type).length;
            return (
              <button
                key={type}
                onClick={() => setFilter(type!)}
                className={`px-3 py-1 text-sm rounded-md capitalize ${
                  filter === type
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                }`}
              >
                {type} ({count})
              </button>
            );
          })}
        </div>
      )}

      {/* Documents List */}
      <div className="space-y-3">
        {filteredDocuments.map((document) => (
          <div
            key={document.id}
            className="flex items-start gap-4 p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow"
          >
            {/* File Icon */}
            <div className="flex-shrink-0 mt-1">
              {getFileIcon(document.mime_type)}
            </div>

            {/* Document Info */}
            <div className="flex-1 min-w-0">
              <div className="flex items-start justify-between gap-4">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-1">
                    <h4 className="text-sm font-medium text-gray-900 truncate">
                      {document.title || document.file_name}
                    </h4>
                    {getDocumentTypeBadge(document.document_type)}
                  </div>
                  {document.description && (
                    <p className="text-sm text-gray-600 mb-2">{document.description}</p>
                  )}
                  <div className="flex items-center gap-4 text-xs text-gray-500">
                    <span>{document.formatted_size}</span>
                    <span>•</span>
                    <span>Uploaded {formatDateTime(document.created_at)}</span>
                    {document.file_name !== document.title && (
                      <>
                        <span>•</span>
                        <span className="truncate max-w-xs" title={document.file_name}>
                          {document.file_name}
                        </span>
                      </>
                    )}
                  </div>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-1 flex-shrink-0">
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => handleDownload(document)}
                    title="Download"
                  >
                    <Download className="h-4 w-4" />
                  </Button>
                  {onEdit && (
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={() => onEdit(document)}
                      title="Edit"
                    >
                      <Edit className="h-4 w-4" />
                    </Button>
                  )}
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => handleDelete(document.id)}
                    isLoading={deleteMutation.isPending}
                    title="Delete"
                  >
                    <Trash2 className="h-4 w-4 text-red-600" />
                  </Button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {filteredDocuments.length === 0 && filter !== 'all' && (
        <div className="text-center py-8">
          <p className="text-gray-500">No documents found with this filter</p>
          <button
            onClick={() => setFilter('all')}
            className="text-blue-600 hover:text-blue-700 text-sm mt-2"
          >
            Clear filter
          </button>
        </div>
      )}
    </div>
  );
};
