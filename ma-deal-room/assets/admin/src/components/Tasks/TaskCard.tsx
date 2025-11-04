import { CheckCircle, Circle, Clock, User, Edit, Trash2 } from 'lucide-react';
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';
import { useCompleteTask, useSkipTask } from '@/api/queries/useTasks';
import { formatDate, isOverdue } from '@/utils/formatDate';
import type { Task } from '@/api/types';
import { cn } from '@/utils/cn';

interface TaskCardProps {
  task: Task;
  onTaskClick?: (task: Task) => void;
  onEdit?: (task: Task) => void;
  onDelete?: (taskId: number) => void;
  isSelectionMode?: boolean;
  isSelected?: boolean;
  onToggleSelect?: () => void;
}

export const TaskCard = ({
  task,
  onTaskClick,
  onEdit,
  onDelete,
  isSelectionMode = false,
  isSelected = false,
  onToggleSelect,
}: TaskCardProps) => {
  const completeMutation = useCompleteTask();
  const skipMutation = useSkipTask();

  const handleComplete = async (e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      await completeMutation.mutateAsync({ id: task.id });
    } catch (error) {
      console.error('Failed to complete task:', error);
    }
  };

  const handleSkip = async (e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      await skipMutation.mutateAsync({
        id: task.id,
        data: { reason: 'Skipped by user' },
      });
    } catch (error) {
      console.error('Failed to skip task:', error);
    }
  };

  const handleEdit = (e: React.MouseEvent) => {
    e.stopPropagation();
    onEdit?.(task);
  };

  const handleDelete = (e: React.MouseEvent) => {
    e.stopPropagation();
    onDelete?.(task.id);
  };

  const getStatusBadge = () => {
    if (task.status === 'completed') {
      return <Badge variant="success">Completed</Badge>;
    }
    if (task.status === 'skipped') {
      return <Badge variant="default">Skipped</Badge>;
    }
    if (task.status === 'blocked') {
      return <Badge variant="danger">Blocked</Badge>;
    }
    if (isOverdue(task.due_at) && task.status === 'pending') {
      return <Badge variant="danger">Overdue</Badge>;
    }
    return <Badge variant="warning">Pending</Badge>;
  };

  const isTaskOverdue = isOverdue(task.due_at) && task.status === 'pending';

  const handleCardClick = () => {
    if (isSelectionMode) {
      onToggleSelect?.();
    } else {
      onTaskClick?.(task);
    }
  };

  return (
    <div
      className={cn(
        'p-4 border rounded-lg hover:shadow-md transition-all cursor-pointer',
        isTaskOverdue && 'border-danger-300 bg-danger-50',
        task.status === 'completed' && 'bg-gray-50 opacity-75',
        !isTaskOverdue && task.status !== 'completed' && 'border-gray-200 bg-white',
        isSelectionMode && isSelected && 'ring-2 ring-primary-500 bg-primary-50'
      )}
      onClick={handleCardClick}
    >
      <div className="flex items-start gap-3">
        {/* Checkbox for selection mode */}
        {isSelectionMode ? (
          <div className="flex-shrink-0 mt-1">
            <input
              type="checkbox"
              checked={isSelected}
              onChange={(e) => {
                e.stopPropagation();
                onToggleSelect?.();
              }}
              className="h-5 w-5 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
            />
          </div>
        ) : (
          <div className="flex-shrink-0 mt-1">
            {task.status === 'completed' ? (
              <CheckCircle className="h-5 w-5 text-success-600" />
            ) : (
              <Circle className="h-5 w-5 text-gray-400" />
            )}
          </div>
        )}

        {/* Content */}
        <div className="flex-1 min-w-0">
          <div className="flex items-start justify-between gap-3">
            <div className="flex-1 min-w-0">
              <h4 className={cn(
                'font-medium text-gray-900',
                task.status === 'completed' && 'line-through text-gray-500'
              )}>
                {task.title}
              </h4>
              {task.description && (
                <p className="text-sm text-gray-600 mt-1 line-clamp-2">{task.description}</p>
              )}
            </div>
            {getStatusBadge()}
          </div>

          {/* Metadata */}
          <div className="flex items-center gap-4 mt-3 text-xs text-gray-500">
            {task.due_at && (
              <div className="flex items-center gap-1">
                <Clock className="h-3 w-3" />
                <span>Due {formatDate(task.due_at)}</span>
              </div>
            )}
            {task.owner_role && (
              <div className="flex items-center gap-1">
                <User className="h-3 w-3" />
                <span className="capitalize">{task.owner_role.replace('_', ' ')}</span>
              </div>
            )}
          </div>

          {/* Dependencies */}
          {task.depends_on_task_ids && task.depends_on_task_ids.length > 0 && (
            <div className="mt-2 text-xs text-gray-500">
              Depends on {task.depends_on_task_ids.length} task(s)
            </div>
          )}

          {/* Actions */}
          {!isSelectionMode && (
            <div className="flex items-center gap-2 mt-3">
              {task.status === 'pending' && (
                <>
                  <Button
                    size="sm"
                    variant="primary"
                    onClick={handleComplete}
                    isLoading={completeMutation.isPending}
                  >
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Complete
                  </Button>
                  <Button
                    size="sm"
                    variant="secondary"
                    onClick={handleSkip}
                    isLoading={skipMutation.isPending}
                  >
                    Skip
                  </Button>
                </>
              )}
              {onEdit && (
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={handleEdit}
                >
                  <Edit className="h-3 w-3 mr-1" />
                  Edit
                </Button>
              )}
              {onDelete && (
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={handleDelete}
                >
                  <Trash2 className="h-3 w-3 mr-1 text-red-600" />
                  Delete
                </Button>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
