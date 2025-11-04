import React, { useState } from 'react';
import { History, RotateCcw, X, FileText, User, Clock } from 'lucide-react';
import { useGetTemplateVersions, useRestoreTemplateVersion } from '@/api/queries/useTemplates';
import { Button } from '@/components/shared/Button';
import { format } from 'date-fns';
import toast from 'react-hot-toast';

interface TemplateVersionHistoryProps {
  templateId: number;
  templateName: string;
  isOpen: boolean;
  onClose: () => void;
}

export const TemplateVersionHistory: React.FC<TemplateVersionHistoryProps> = ({
  templateId,
  templateName,
  isOpen,
  onClose,
}) => {
  const [selectedVersion, setSelectedVersion] = useState<number | null>(null);
  const { data: versions, isLoading } = useGetTemplateVersions(templateId, isOpen);
  const restoreMutation = useRestoreTemplateVersion();

  if (!isOpen) return null;

  const handleRestore = async (versionId: number) => {
    if (!confirm('Are you sure you want to restore this version? The current version will be saved to history.')) {
      return;
    }

    try {
      await restoreMutation.mutateAsync({ templateId, versionId });
      toast.success('Template version restored successfully');
      onClose();
    } catch (error: any) {
      console.error('Failed to restore version:', error);
      toast.error(error?.message || 'Failed to restore version');
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        {/* Background overlay */}
        <div
          className="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
          onClick={onClose}
        />

        {/* Modal panel */}
        <div className="inline-block w-full max-w-4xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-gray-200">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-blue-100 rounded-lg">
                <History className="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <h3 className="text-lg font-semibold text-gray-900">Version History</h3>
                <p className="text-sm text-gray-600">{templateName}</p>
              </div>
            </div>
            <button
              onClick={onClose}
              className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Content */}
          <div className="p-6">
            {isLoading ? (
              <div className="flex items-center justify-center h-64">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" />
              </div>
            ) : !versions || versions.length === 0 ? (
              <div className="flex flex-col items-center justify-center h-64 text-gray-500">
                <History className="w-12 h-12 mb-4 text-gray-300" />
                <p>No version history available</p>
              </div>
            ) : (
              <div className="space-y-4">
                {versions.map((version, index) => (
                  <div
                    key={version.version_id}
                    className={`border rounded-lg p-4 transition-all ${
                      selectedVersion === version.version_id
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-200 hover:border-gray-300'
                    }`}
                    onClick={() => setSelectedVersion(version.version_id)}
                  >
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-3 mb-2">
                          <span className="px-3 py-1 text-xs font-semibold text-blue-600 bg-blue-100 rounded-full">
                            Version {version.version}
                          </span>
                          {index === 0 && (
                            <span className="px-2 py-1 text-xs font-medium text-green-600 bg-green-100 rounded">
                              Current
                            </span>
                          )}
                        </div>

                        <h4 className="font-medium text-gray-900 mb-1">{version.name}</h4>
                        {version.description && (
                          <p className="text-sm text-gray-600 mb-3">{version.description}</p>
                        )}

                        <div className="flex items-center gap-4 text-xs text-gray-500">
                          <div className="flex items-center gap-1">
                            <User className="w-3 h-3" />
                            {version.created_by_name || `User ${version.created_by_user_id}`}
                          </div>
                          <div className="flex items-center gap-1">
                            <Clock className="w-3 h-3" />
                            {format(new Date(version.created_at), 'MMM d, yyyy h:mm a')}
                          </div>
                        </div>

                        {version.change_notes && (
                          <div className="mt-3 p-2 bg-gray-50 rounded text-sm text-gray-700">
                            <p className="font-medium text-gray-900 mb-1">Change Notes:</p>
                            {version.change_notes}
                          </div>
                        )}
                      </div>

                      <div className="flex flex-col gap-2 ml-4">
                        <Button
                          size="sm"
                          variant="secondary"
                          onClick={(e) => {
                            e.stopPropagation();
                            setSelectedVersion(
                              selectedVersion === version.version_id ? null : version.version_id
                            );
                          }}
                        >
                          <FileText className="w-4 h-4 mr-1" />
                          View YAML
                        </Button>
                        {index !== 0 && (
                          <Button
                            size="sm"
                            variant="primary"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleRestore(version.version_id);
                            }}
                            isLoading={restoreMutation.isPending}
                          >
                            <RotateCcw className="w-4 h-4 mr-1" />
                            Restore
                          </Button>
                        )}
                      </div>
                    </div>

                    {/* YAML Preview */}
                    {selectedVersion === version.version_id && (
                      <div className="mt-4 border-t border-gray-200 pt-4">
                        <pre className="p-4 bg-gray-900 text-gray-100 rounded-lg text-xs overflow-x-auto">
                          {version.template_yaml}
                        </pre>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200 bg-gray-50">
            <Button variant="secondary" onClick={onClose}>
              Close
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};
