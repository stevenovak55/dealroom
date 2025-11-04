import React from 'react';
import { X, Download, ExternalLink, ZoomIn, ZoomOut } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import type { Document } from '@/api/types';
import { downloadDocument } from '@/api/queries/useDocuments';

interface DocumentViewerProps {
  document: Document;
  onClose: () => void;
}

export const DocumentViewer: React.FC<DocumentViewerProps> = ({ document, onClose }) => {
  const [zoom, setZoom] = React.useState(100);

  const handleDownload = () => {
    downloadDocument(document.id, document.file_name);
  };

  const handleOpenInNewTab = () => {
    window.open(document.download_url, '_blank');
  };

  const handleZoomIn = () => {
    setZoom((prev) => Math.min(prev + 25, 200));
  };

  const handleZoomOut = () => {
    setZoom((prev) => Math.max(prev - 25, 50));
  };

  const renderPreview = () => {
    const { mime_type, download_url, file_name } = document;

    // PDF Preview
    if (mime_type === 'application/pdf') {
      return (
        <div className="relative w-full h-full bg-gray-100">
          <iframe
            src={`${download_url}#zoom=${zoom}`}
            className="w-full h-full border-0"
            title={file_name}
            style={{ minHeight: '600px' }}
          />
        </div>
      );
    }

    // Image Preview
    if (mime_type.startsWith('image/')) {
      return (
        <div className="flex items-center justify-center h-full bg-gray-100 p-4">
          <img
            src={download_url}
            alt={file_name}
            className="max-w-full max-h-full object-contain"
            style={{ transform: `scale(${zoom / 100})`, transition: 'transform 0.2s' }}
          />
        </div>
      );
    }

    // Text Preview
    if (mime_type === 'text/plain') {
      return (
        <div className="p-6 bg-white h-full overflow-auto">
          <iframe
            src={download_url}
            className="w-full h-full border-0"
            title={file_name}
            style={{ minHeight: '600px' }}
          />
        </div>
      );
    }

    // Unsupported - show download option
    return (
      <div className="flex flex-col items-center justify-center h-full bg-gray-50 p-8">
        <div className="text-center max-w-md">
          <div className="mb-4">
            <svg
              className="w-24 h-24 mx-auto text-gray-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={1.5}
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
          </div>
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Preview not available
          </h3>
          <p className="text-gray-600 mb-6">
            This file type cannot be previewed in the browser. Download the file to view it.
          </p>
          <div className="space-y-2">
            <p className="text-sm text-gray-500">
              <span className="font-medium">File:</span> {file_name}
            </p>
            <p className="text-sm text-gray-500">
              <span className="font-medium">Type:</span> {mime_type}
            </p>
            <p className="text-sm text-gray-500">
              <span className="font-medium">Size:</span> {document.formatted_size}
            </p>
          </div>
        </div>
      </div>
    );
  };

  const showZoomControls = document.mime_type === 'application/pdf' ||
                           document.mime_type.startsWith('image/');

  return (
    <div className="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-lg shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50">
          <div className="flex-1 min-w-0 mr-4">
            <h2 className="text-lg font-semibold text-gray-900 truncate">
              {document.title || document.file_name}
            </h2>
            {document.description && (
              <p className="text-sm text-gray-600 truncate">{document.description}</p>
            )}
          </div>

          <div className="flex items-center gap-2 flex-shrink-0">
            {/* Zoom Controls */}
            {showZoomControls && (
              <>
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={handleZoomOut}
                  disabled={zoom <= 50}
                  title="Zoom Out"
                >
                  <ZoomOut className="h-4 w-4" />
                </Button>
                <span className="text-sm text-gray-600 min-w-[60px] text-center">
                  {zoom}%
                </span>
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={handleZoomIn}
                  disabled={zoom >= 200}
                  title="Zoom In"
                >
                  <ZoomIn className="h-4 w-4" />
                </Button>
                <div className="w-px h-6 bg-gray-300 mx-2" />
              </>
            )}

            {/* Action Buttons */}
            <Button
              size="sm"
              variant="ghost"
              onClick={handleOpenInNewTab}
              title="Open in New Tab"
            >
              <ExternalLink className="h-4 w-4" />
            </Button>
            <Button
              size="sm"
              variant="ghost"
              onClick={handleDownload}
              title="Download"
            >
              <Download className="h-4 w-4" />
            </Button>
            <Button
              size="sm"
              variant="ghost"
              onClick={onClose}
              title="Close"
            >
              <X className="h-4 w-4" />
            </Button>
          </div>
        </div>

        {/* Preview Area */}
        <div className="flex-1 overflow-hidden">
          {renderPreview()}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between p-4 border-t border-gray-200 bg-gray-50">
          <div className="flex items-center gap-4 text-sm text-gray-600">
            <span>
              <span className="font-medium">Size:</span> {document.formatted_size}
            </span>
            <span>•</span>
            <span>
              <span className="font-medium">Type:</span> {document.document_type || 'Other'}
            </span>
            <span>•</span>
            <span>
              <span className="font-medium">Uploaded:</span>{' '}
              {new Date(document.created_at).toLocaleDateString()}
            </span>
          </div>
          <Button variant="secondary" size="sm" onClick={onClose}>
            Close
          </Button>
        </div>
      </div>
    </div>
  );
};
