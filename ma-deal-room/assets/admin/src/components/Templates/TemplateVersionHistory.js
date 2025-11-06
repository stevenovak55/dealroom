import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { History, RotateCcw, X, FileText, User, Clock } from 'lucide-react';
import { useGetTemplateVersions, useRestoreTemplateVersion } from '@/api/queries/useTemplates';
import { Button } from '@/components/shared/Button';
import { format } from 'date-fns';
import toast from 'react-hot-toast';
export const TemplateVersionHistory = ({ templateId, templateName, isOpen, onClose, }) => {
    const [selectedVersion, setSelectedVersion] = useState(null);
    const { data: versions, isLoading } = useGetTemplateVersions(templateId, isOpen);
    const restoreMutation = useRestoreTemplateVersion();
    if (!isOpen)
        return null;
    const handleRestore = async (versionId) => {
        if (!confirm('Are you sure you want to restore this version? The current version will be saved to history.')) {
            return;
        }
        try {
            await restoreMutation.mutateAsync({ templateId, versionId });
            toast.success('Template version restored successfully');
            onClose();
        }
        catch (error) {
            console.error('Failed to restore version:', error);
            toast.error(error?.message || 'Failed to restore version');
        }
    };
    return (_jsx("div", { className: "fixed inset-0 z-50 overflow-y-auto", children: _jsxs("div", { className: "flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0", children: [_jsx("div", { className: "fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75", onClick: onClose }), _jsxs("div", { className: "inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg", children: [_jsxs("div", { className: "flex items-center justify-between p-6 border-b border-gray-200", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx("div", { className: "p-2 bg-blue-100 rounded-lg", children: _jsx(History, { className: "w-5 h-5 text-blue-600" }) }), _jsxs("div", { children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: "Version History" }), _jsx("p", { className: "text-sm text-gray-600", children: templateName })] })] }), _jsx("button", { onClick: onClose, className: "p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors", children: _jsx(X, { className: "w-5 h-5" }) })] }), _jsx("div", { className: "p-6", children: isLoading ? (_jsx("div", { className: "flex items-center justify-center h-64", children: _jsx("div", { className: "animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" }) })) : !versions || versions.length === 0 ? (_jsxs("div", { className: "flex flex-col items-center justify-center h-64 text-gray-500", children: [_jsx(History, { className: "w-12 h-12 mb-4 text-gray-300" }), _jsx("p", { children: "No version history available" })] })) : (_jsx("div", { className: "space-y-4", children: versions.map((version, index) => (_jsxs("div", { className: `border rounded-lg p-4 transition-all ${selectedVersion === version.version_id
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-200 hover:border-gray-300'}`, onClick: () => setSelectedVersion(version.version_id), children: [_jsxs("div", { className: "flex items-start justify-between", children: [_jsxs("div", { className: "flex-1", children: [_jsxs("div", { className: "flex items-center gap-3 mb-2", children: [_jsxs("span", { className: "px-3 py-1 text-xs font-semibold text-blue-600 bg-blue-100 rounded-full", children: ["Version ", version.version] }), index === 0 && (_jsx("span", { className: "px-2 py-1 text-xs font-medium text-green-600 bg-green-100 rounded", children: "Current" }))] }), _jsx("h4", { className: "font-medium text-gray-900 mb-1", children: version.name }), version.description && (_jsx("p", { className: "text-sm text-gray-600 mb-3", children: version.description })), _jsxs("div", { className: "flex items-center gap-4 text-xs text-gray-500", children: [_jsxs("div", { className: "flex items-center gap-1", children: [_jsx(User, { className: "w-3 h-3" }), version.created_by_name || `User ${version.created_by_user_id}`] }), _jsxs("div", { className: "flex items-center gap-1", children: [_jsx(Clock, { className: "w-3 h-3" }), format(new Date(version.created_at), 'MMM d, yyyy h:mm a')] })] }), version.change_notes && (_jsxs("div", { className: "mt-3 p-2 bg-gray-50 rounded text-sm text-gray-700", children: [_jsx("p", { className: "font-medium text-gray-900 mb-1", children: "Change Notes:" }), version.change_notes] }))] }), _jsxs("div", { className: "flex flex-col gap-2 ml-4", children: [_jsxs(Button, { size: "sm", variant: "secondary", onClick: (e) => {
                                                                e.stopPropagation();
                                                                setSelectedVersion(selectedVersion === version.version_id ? null : version.version_id);
                                                            }, children: [_jsx(FileText, { className: "w-4 h-4 mr-1" }), "View YAML"] }), index !== 0 && (_jsxs(Button, { size: "sm", variant: "primary", onClick: (e) => {
                                                                e.stopPropagation();
                                                                handleRestore(version.version_id);
                                                            }, isLoading: restoreMutation.isPending, children: [_jsx(RotateCcw, { className: "w-4 h-4 mr-1" }), "Restore"] }))] })] }), selectedVersion === version.version_id && (_jsx("div", { className: "mt-4 border-t border-gray-200 pt-4", children: _jsx("pre", { className: "p-4 bg-gray-900 text-gray-100 rounded-lg text-xs overflow-x-auto", children: version.template_yaml }) }))] }, version.version_id))) })) }), _jsx("div", { className: "flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50", children: _jsx(Button, { variant: "secondary", onClick: onClose, children: "Close" }) })] })] }) }));
};
