import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import React from 'react';
import { X, Download, ExternalLink, ZoomIn, ZoomOut } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { downloadDocument } from '@/api/queries/useDocuments';
export const DocumentViewer = ({ document, onClose }) => {
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
            return (_jsx("div", { className: "relative w-full h-full bg-gray-100", children: _jsx("iframe", { src: `${download_url}#zoom=${zoom}`, className: "w-full h-full border-0", title: file_name, style: { minHeight: '600px' } }) }));
        }
        // Image Preview
        if (mime_type.startsWith('image/')) {
            return (_jsx("div", { className: "flex items-center justify-center h-full bg-gray-100 p-4", children: _jsx("img", { src: download_url, alt: file_name, className: "max-w-full max-h-full object-contain", style: { transform: `scale(${zoom / 100})`, transition: 'transform 0.2s' } }) }));
        }
        // Text Preview
        if (mime_type === 'text/plain') {
            return (_jsx("div", { className: "p-6 bg-white h-full overflow-auto", children: _jsx("iframe", { src: download_url, className: "w-full h-full border-0", title: file_name, style: { minHeight: '600px' } }) }));
        }
        // Unsupported - show download option
        return (_jsx("div", { className: "flex flex-col items-center justify-center h-full bg-gray-50 p-8", children: _jsxs("div", { className: "text-center max-w-md", children: [_jsx("div", { className: "mb-4", children: _jsx("svg", { className: "w-24 h-24 mx-auto text-gray-400", fill: "none", viewBox: "0 0 24 24", stroke: "currentColor", children: _jsx("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" }) }) }), _jsx("h3", { className: "text-lg font-semibold text-gray-900 mb-2", children: "Preview not available" }), _jsx("p", { className: "text-gray-600 mb-6", children: "This file type cannot be previewed in the browser. Download the file to view it." }), _jsxs("div", { className: "space-y-2", children: [_jsxs("p", { className: "text-sm text-gray-500", children: [_jsx("span", { className: "font-medium", children: "File:" }), " ", file_name] }), _jsxs("p", { className: "text-sm text-gray-500", children: [_jsx("span", { className: "font-medium", children: "Type:" }), " ", mime_type] }), _jsxs("p", { className: "text-sm text-gray-500", children: [_jsx("span", { className: "font-medium", children: "Size:" }), " ", document.formatted_size] })] })] }) }));
    };
    const showZoomControls = document.mime_type === 'application/pdf' ||
        document.mime_type.startsWith('image/');
    return (_jsx("div", { className: "fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4", children: _jsxs("div", { className: "bg-white rounded-lg shadow-2xl w-full max-w-6xl h-[90vh] flex flex-col", children: [_jsxs("div", { className: "flex items-center justify-between p-4 border-b border-gray-200 bg-gray-50", children: [_jsxs("div", { className: "flex-1 min-w-0 mr-4", children: [_jsx("h2", { className: "text-lg font-semibold text-gray-900 truncate", children: document.title || document.file_name }), document.description && (_jsx("p", { className: "text-sm text-gray-600 truncate", children: document.description }))] }), _jsxs("div", { className: "flex items-center gap-2 flex-shrink-0", children: [showZoomControls && (_jsxs(_Fragment, { children: [_jsx(Button, { size: "sm", variant: "ghost", onClick: handleZoomOut, disabled: zoom <= 50, title: "Zoom Out", children: _jsx(ZoomOut, { className: "h-4 w-4" }) }), _jsxs("span", { className: "text-sm text-gray-600 min-w-[60px] text-center", children: [zoom, "%"] }), _jsx(Button, { size: "sm", variant: "ghost", onClick: handleZoomIn, disabled: zoom >= 200, title: "Zoom In", children: _jsx(ZoomIn, { className: "h-4 w-4" }) }), _jsx("div", { className: "w-px h-6 bg-gray-300 mx-2" })] })), _jsx(Button, { size: "sm", variant: "ghost", onClick: handleOpenInNewTab, title: "Open in New Tab", children: _jsx(ExternalLink, { className: "h-4 w-4" }) }), _jsx(Button, { size: "sm", variant: "ghost", onClick: handleDownload, title: "Download", children: _jsx(Download, { className: "h-4 w-4" }) }), _jsx(Button, { size: "sm", variant: "ghost", onClick: onClose, title: "Close", children: _jsx(X, { className: "h-4 w-4" }) })] })] }), _jsx("div", { className: "flex-1 overflow-hidden", children: renderPreview() }), _jsxs("div", { className: "flex items-center justify-between p-4 border-t border-gray-200 bg-gray-50", children: [_jsxs("div", { className: "flex items-center gap-4 text-sm text-gray-600", children: [_jsxs("span", { children: [_jsx("span", { className: "font-medium", children: "Size:" }), " ", document.formatted_size] }), _jsx("span", { children: "\u2022" }), _jsxs("span", { children: [_jsx("span", { className: "font-medium", children: "Type:" }), " ", document.document_type || 'Other'] }), _jsx("span", { children: "\u2022" }), _jsxs("span", { children: [_jsx("span", { className: "font-medium", children: "Uploaded:" }), ' ', new Date(document.created_at).toLocaleDateString()] })] }), _jsx(Button, { variant: "secondary", size: "sm", onClick: onClose, children: "Close" })] })] }) }));
};
