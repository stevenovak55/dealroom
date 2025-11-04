import { useState } from 'react';
import { X, Plus } from 'lucide-react';
import { useGetTemplates } from '@/api/queries/useTemplates';
import { useApplyTemplate } from '@/api/queries/useTransactions';
import { Button } from '@/components/shared/Button';
import { Modal } from '@/components/shared/Modal';

interface ApplyTemplateModalProps {
  isOpen: boolean;
  onClose: () => void;
  transactionId: number;
}

export const ApplyTemplateModal = ({
  isOpen,
  onClose,
  transactionId,
}: ApplyTemplateModalProps) => {
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | null>(null);

  const { data: templatesData, isLoading: templatesLoading } = useGetTemplates();
  const applyMutation = useApplyTemplate(transactionId);

  const templates = templatesData?.data || [];

  const handleApply = async () => {
    if (!selectedTemplateId) {
      alert('Please select a template');
      return;
    }

    try {
      const result = await applyMutation.mutateAsync(selectedTemplateId);
      alert(result.message || 'Template applied successfully!');
      onClose();
    } catch (error) {
      console.error('Failed to apply template:', error);
      alert('Failed to apply template. Please try again.');
    }
  };

  const handleClose = () => {
    setSelectedTemplateId(null);
    onClose();
  };

  return (
    <Modal isOpen={isOpen} onClose={handleClose}>
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h2 className="text-xl font-semibold text-gray-900">Apply Template</h2>
          <button
            onClick={handleClose}
            className="text-gray-400 hover:text-gray-500"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Content */}
        <div className="px-6 py-4 overflow-y-auto flex-1">
          <p className="text-sm text-gray-600 mb-4">
            Select a template to apply to this transaction. Tasks from the template will be added to your existing tasks.
          </p>

          {templatesLoading ? (
            <div className="text-center py-8">
              <p className="text-gray-500">Loading templates...</p>
            </div>
          ) : templates.length === 0 ? (
            <div className="text-center py-8">
              <p className="text-gray-500">No templates available</p>
            </div>
          ) : (
            <div className="space-y-3">
              {templates.map((template) => (
                <label
                  key={template.template_id}
                  className={`flex items-start p-4 border-2 rounded-lg cursor-pointer transition-colors ${
                    selectedTemplateId === template.template_id
                      ? 'border-primary-500 bg-primary-50'
                      : 'border-gray-200 hover:border-primary-300'
                  }`}
                >
                  <input
                    type="radio"
                    name="template"
                    value={template.template_id}
                    checked={selectedTemplateId === template.template_id}
                    onChange={() => setSelectedTemplateId(template.template_id)}
                    className="mt-1 h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300"
                  />
                  <div className="ml-3 flex-1">
                    <p className="font-medium text-gray-900">{template.name}</p>
                    {template.description && (
                      <p className="text-sm text-gray-500 mt-1">{template.description}</p>
                    )}
                    <p className="text-xs text-gray-400 mt-2">
                      {template.task_count || 0} tasks
                    </p>
                  </div>
                </label>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-3">
          <Button variant="secondary" onClick={handleClose}>
            Cancel
          </Button>
          <Button
            variant="primary"
            onClick={handleApply}
            isLoading={applyMutation.isPending}
            disabled={!selectedTemplateId}
          >
            <Plus className="h-4 w-4 mr-2" />
            Apply Template
          </Button>
        </div>
      </div>
    </Modal>
  );
};
