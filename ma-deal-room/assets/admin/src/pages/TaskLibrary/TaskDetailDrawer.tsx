import {
  CheckCircle2,
  Clock,
  User,
  AlertCircle,
  Edit,
  Trash2,
  Copy,
  ExternalLink,
  Tag,
  FileText,
  Calendar,
  GitBranch,
  Filter
} from 'lucide-react';
import { Drawer } from '@/components/shared/Drawer';
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';
import { useDeleteTaskDefinition, useGetTaskTemplates } from '@/api/queries/useTaskDefinitions';
import { showToast } from '@/utils/toast';
import type { TaskDefinition } from '@/api/types';

interface TaskDetailDrawerProps {
  task: TaskDefinition | null;
  isOpen: boolean;
  onClose: () => void;
  onEdit: (task: TaskDefinition) => void;
  onClone: (task: TaskDefinition) => void;
}

const priorityColors = {
  low: 'bg-gray-100 text-gray-700 border-gray-300',
  normal: 'bg-blue-100 text-blue-700 border-blue-300',
  high: 'bg-orange-100 text-orange-700 border-orange-300',
  urgent: 'bg-red-100 text-red-700 border-red-300',
};

const ownerRoleLabels: Record<string, string> = {
  agent: 'Agent',
  seller: 'Seller',
  buyer: 'Buyer',
  seller_attorney: 'Seller Attorney',
  buyer_attorney: 'Buyer Attorney',
  vendor: 'Vendor',
  system: 'System',
};

export const TaskDetailDrawer = ({
  task,
  isOpen,
  onClose,
  onEdit,
  onClone,
}: TaskDetailDrawerProps) => {
  const deleteMutation = useDeleteTaskDefinition();
  const { data: templates } = useGetTaskTemplates(task?.id || 0, isOpen && !!task);

  if (!task) return null;

  const handleDelete = async () => {
    if (task.is_system) {
      showToast.error('System tasks cannot be deleted');
      return;
    }

    if (!confirm(`Are you sure you want to delete "${task.title}"?\nThis cannot be undone.`)) {
      return;
    }

    try {
      await deleteMutation.mutateAsync(task.id);
      showToast.success('Task deleted successfully');
      onClose();
    } catch (error: any) {
      const message = error.response?.data?.message || 'Failed to delete task';
      showToast.error(message);
    }
  };

  const handleEdit = () => {
    onEdit(task);
    onClose();
  };

  const handleClone = () => {
    onClone(task);
    onClose();
  };

  return (
    <Drawer isOpen={isOpen} onClose={onClose} size="xl">
      <div className="p-6 space-y-6">
        {/* Header Section */}
        <div>
          <div className="flex items-start gap-3 mb-3">
            {task.is_milestone && (
              <CheckCircle2 className="h-6 w-6 text-green-600 flex-shrink-0 mt-1" />
            )}
            {task.is_required && (
              <AlertCircle className="h-6 w-6 text-orange-600 flex-shrink-0 mt-1" />
            )}
            <div className="flex-1">
              <h1 className="text-2xl font-bold text-gray-900 leading-tight">
                {task.title}
              </h1>
              <div className="flex items-center gap-2 mt-2 flex-wrap">
                <Badge
                  variant="default"
                  className={`${priorityColors[task.priority]} border`}
                >
                  {task.priority} priority
                </Badge>
                {task.is_system && <Badge variant="info">System Task</Badge>}
                {task.is_milestone && <Badge variant="success">Milestone</Badge>}
                {task.is_required && <Badge variant="warning">Required</Badge>}
              </div>
            </div>
          </div>

          {/* Task Key */}
          <div className="flex items-center gap-2 text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded-md">
            <Tag className="h-4 w-4" />
            <span className="font-mono">{task.task_key}</span>
          </div>
        </div>

        {/* Action Buttons */}
        <div className="flex items-center gap-2 pb-4 border-b border-gray-200">
          <Button
            size="sm"
            variant="secondary"
            onClick={handleEdit}
            disabled={task.is_system}
          >
            <Edit className="h-4 w-4 mr-2" />
            Edit
          </Button>
          <Button
            size="sm"
            variant="secondary"
            onClick={handleClone}
          >
            <Copy className="h-4 w-4 mr-2" />
            Clone
          </Button>
          {!task.is_system && (
            <Button
              size="sm"
              variant="danger"
              onClick={handleDelete}
              isLoading={deleteMutation.isPending}
            >
              <Trash2 className="h-4 w-4 mr-2" />
              Delete
            </Button>
          )}
        </div>

        {/* Description */}
        {task.description && (
          <div className="space-y-2">
            <div className="flex items-center gap-2 text-sm font-semibold text-gray-700">
              <FileText className="h-4 w-4" />
              Description
            </div>
            <p className="text-gray-600 leading-relaxed bg-gray-50 p-4 rounded-md">
              {task.description}
            </p>
          </div>
        )}

        {/* Key Information Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Category */}
          <div className="bg-gray-50 p-4 rounded-lg">
            <div className="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
              <Tag className="h-4 w-4" />
              Category
            </div>
            <p className="text-gray-900 font-medium capitalize">{task.category.replace(/_/g, ' ')}</p>
          </div>

          {/* Owner Role */}
          <div className="bg-gray-50 p-4 rounded-lg">
            <div className="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
              <User className="h-4 w-4" />
              Default Owner
            </div>
            <p className="text-gray-900 font-medium">
              {ownerRoleLabels[task.owner_role] || task.owner_role}
            </p>
          </div>

          {/* Estimated Duration */}
          {task.estimated_duration && (
            <div className="bg-gray-50 p-4 rounded-lg">
              <div className="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
                <Clock className="h-4 w-4" />
                Estimated Duration
              </div>
              <p className="text-gray-900 font-medium">
                {task.estimated_duration} {task.estimated_duration === 1 ? 'day' : 'days'}
              </p>
            </div>
          )}

          {/* Due Calculation */}
          {task.due_calculation && (
            <div className="bg-gray-50 p-4 rounded-lg">
              <div className="flex items-center gap-2 text-sm font-medium text-gray-500 mb-2">
                <Calendar className="h-4 w-4" />
                Due Date Calculation
              </div>
              <code className="text-gray-900 font-mono text-sm bg-white px-2 py-1 rounded border border-gray-200">
                {task.due_calculation}
              </code>
            </div>
          )}
        </div>

        {/* Advanced Details */}
        {(task.applies_if || task.depends_on?.length > 0) && (
          <div className="space-y-4 border-t border-gray-200 pt-4">
            <h3 className="text-lg font-semibold text-gray-900">Advanced Configuration</h3>

            {/* Conditional Logic */}
            {task.applies_if && (
              <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm font-semibold text-gray-700">
                  <Filter className="h-4 w-4" />
                  Applies If (Condition)
                </div>
                <div className="bg-gray-900 text-gray-100 p-4 rounded-lg font-mono text-sm overflow-x-auto">
                  {task.applies_if}
                </div>
                <p className="text-xs text-gray-500">
                  This task will only be created when the above condition evaluates to true
                </p>
              </div>
            )}

            {/* Dependencies */}
            {task.depends_on && task.depends_on.length > 0 && (
              <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm font-semibold text-gray-700">
                  <GitBranch className="h-4 w-4" />
                  Dependencies ({task.depends_on.length})
                </div>
                <div className="flex flex-wrap gap-2">
                  {task.depends_on.map((dep, idx) => (
                    <Badge key={idx} variant="default" className="font-mono text-xs">
                      {dep}
                    </Badge>
                  ))}
                </div>
                <p className="text-xs text-gray-500">
                  This task depends on the completion of the above tasks
                </p>
              </div>
            )}
          </div>
        )}

        {/* Templates Using This Task */}
        <div className="space-y-4 border-t border-gray-200 pt-4">
          <div className="flex items-center gap-2">
            <ExternalLink className="h-5 w-5 text-gray-400" />
            <h3 className="text-lg font-semibold text-gray-900">
              Templates Using This Task
            </h3>
          </div>

          {templates && templates.length > 0 ? (
            <div className="grid grid-cols-1 gap-2">
              {templates.map((template) => (
                <div
                  key={template.template_id}
                  className="flex items-center justify-between bg-gray-50 p-3 rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <div>
                    <p className="font-medium text-gray-900">{template.name}</p>
                    {template.description && (
                      <p className="text-sm text-gray-500 mt-0.5">{template.description}</p>
                    )}
                  </div>
                  <Badge variant="default">{template.property_type}</Badge>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500 text-sm italic">
              This task is not currently used in any templates
            </p>
          )}
        </div>

        {/* Metadata */}
        {task.metadata && Object.keys(task.metadata).length > 0 && (
          <div className="space-y-2 border-t border-gray-200 pt-4">
            <h3 className="text-lg font-semibold text-gray-900">Additional Metadata</h3>
            <div className="bg-gray-50 p-4 rounded-lg">
              <pre className="text-xs text-gray-700 overflow-x-auto">
                {JSON.stringify(task.metadata, null, 2)}
              </pre>
            </div>
          </div>
        )}
      </div>
    </Drawer>
  );
};
