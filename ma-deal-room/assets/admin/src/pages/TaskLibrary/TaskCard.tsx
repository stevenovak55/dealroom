import { useState } from 'react';
import {
  CheckCircle2,
  Clock,
  User,
  AlertCircle,
  Edit,
  Trash2,
  ExternalLink,
  ChevronDown,
  ChevronUp
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';
import { useDeleteTaskDefinition, useGetTaskTemplates } from '@/api/queries/useTaskDefinitions';
import { showToast } from '@/utils/toast';
import type { TaskDefinition } from '@/api/types';

interface TaskCardProps {
  task: TaskDefinition;
  onEdit: (task: TaskDefinition) => void;
}

const priorityColors = {
  low: 'bg-gray-100 text-gray-700',
  normal: 'bg-blue-100 text-blue-700',
  high: 'bg-orange-100 text-orange-700',
  urgent: 'bg-red-100 text-red-700',
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

export const TaskCard = ({ task, onEdit }: TaskCardProps) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [showTemplates, setShowTemplates] = useState(false);
  const deleteMutation = useDeleteTaskDefinition();
  const { data: templates } = useGetTaskTemplates(task.id, showTemplates);

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
    } catch (error: any) {
      console.error('Failed to delete task:', error);
      const message = error.response?.data?.message || 'Failed to delete task. Please try again.';
      showToast.error(message);
    }
  };

  const handleViewTemplates = () => {
    setShowTemplates(!showTemplates);
  };

  return (
    <Card className="card-hover-effect">
      <CardHeader>
        <CardTitle className="flex items-start justify-between">
          <div className="flex-1">
            <div className="flex items-center gap-2 mb-1">
              {task.is_milestone && (
                <CheckCircle2 className="h-4 w-4 text-green-600 flex-shrink-0" />
              )}
              {task.is_required && (
                <AlertCircle className="h-4 w-4 text-orange-600 flex-shrink-0" />
              )}
              <span className="text-sm font-semibold text-gray-900 leading-tight">
                {task.title}
              </span>
            </div>
            <div className="flex items-center gap-2 flex-wrap">
              <Badge
                variant="default"
                className={priorityColors[task.priority] || priorityColors.normal}
              >
                {task.priority}
              </Badge>
              {task.is_system && <Badge variant="info">System</Badge>}
            </div>
          </div>
        </CardTitle>
      </CardHeader>
      <CardContent>
        {/* Description */}
        {task.description && (
          <p className="text-sm text-gray-600 mb-3 line-clamp-2">
            {task.description}
          </p>
        )}

        {/* Task Details */}
        <div className="space-y-2 mb-4">
          {/* Owner Role */}
          <div className="flex items-center gap-2 text-sm">
            <User className="h-4 w-4 text-gray-400" />
            <span className="text-gray-500">Owner:</span>
            <span className="font-medium text-gray-900">
              {ownerRoleLabels[task.owner_role] || task.owner_role}
            </span>
          </div>

          {/* Due Calculation */}
          {task.due_calculation && (
            <div className="flex items-center gap-2 text-sm">
              <Clock className="h-4 w-4 text-gray-400" />
              <span className="text-gray-500">Due:</span>
              <code className="font-mono text-xs bg-gray-100 px-2 py-0.5 rounded">
                {task.due_calculation}
              </code>
            </div>
          )}

          {/* Estimated Duration */}
          {task.estimated_duration && (
            <div className="flex items-center gap-2 text-sm">
              <Clock className="h-4 w-4 text-gray-400" />
              <span className="text-gray-500">Duration:</span>
              <span className="font-medium text-gray-900">
                {task.estimated_duration} {task.estimated_duration === 1 ? 'day' : 'days'}
              </span>
            </div>
          )}
        </div>

        {/* Expandable details */}
        {(task.applies_if || task.depends_on?.length > 0) && (
          <div className="mb-4">
            <Button
              size="sm"
              variant="ghost"
              onClick={() => setIsExpanded(!isExpanded)}
              className="w-full justify-between"
            >
              <span className="text-xs">
                {isExpanded ? 'Hide' : 'Show'} advanced details
              </span>
              {isExpanded ? (
                <ChevronUp className="h-3 w-3" />
              ) : (
                <ChevronDown className="h-3 w-3" />
              )}
            </Button>

            {isExpanded && (
              <div className="mt-2 space-y-2 text-xs bg-gray-50 p-3 rounded">
                {task.applies_if && (
                  <div>
                    <span className="font-medium text-gray-700">Condition:</span>
                    <code className="block mt-1 font-mono text-xs bg-white p-2 rounded border border-gray-200">
                      {task.applies_if}
                    </code>
                  </div>
                )}
                {task.depends_on?.length > 0 && (
                  <div>
                    <span className="font-medium text-gray-700">Depends on:</span>
                    <div className="flex flex-wrap gap-1 mt-1">
                      {task.depends_on.map((dep, idx) => (
                        <Badge key={idx} variant="default" className="text-xs">
                          {dep}
                        </Badge>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        )}

        {/* Task Key */}
        <div className="mb-4 pb-4 border-b border-gray-200">
          <div className="text-xs text-gray-500">
            Task Key: <code className="font-mono">{task.task_key}</code>
          </div>
        </div>

        {/* View templates using this task */}
        <Button
          size="sm"
          variant="ghost"
          onClick={handleViewTemplates}
          className="w-full mb-2"
        >
          <ExternalLink className="h-3 w-3 mr-1" />
          {showTemplates ? 'Hide' : 'View'} templates using this task
        </Button>

        {showTemplates && templates && (
          <div className="mb-4 text-xs">
            {templates.length === 0 ? (
              <p className="text-gray-500 italic">Not used in any templates</p>
            ) : (
              <div className="space-y-1">
                {templates.map((template) => (
                  <div
                    key={template.template_id}
                    className="bg-gray-50 p-2 rounded flex items-center justify-between"
                  >
                    <span className="text-gray-900">{template.name}</span>
                    <Badge variant="default">{template.property_type}</Badge>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {/* Action buttons */}
        {!task.is_system && (
          <div className="flex items-center gap-2">
            <Button
              size="sm"
              variant="secondary"
              onClick={() => onEdit(task)}
              className="flex-1"
            >
              <Edit className="h-3 w-3 mr-1" />
              Edit
            </Button>
            <Button
              size="sm"
              variant="danger"
              onClick={handleDelete}
              isLoading={deleteMutation.isPending}
              className="flex-1"
            >
              <Trash2 className="h-3 w-3 mr-1" />
              Delete
            </Button>
          </div>
        )}
      </CardContent>
    </Card>
  );
};
