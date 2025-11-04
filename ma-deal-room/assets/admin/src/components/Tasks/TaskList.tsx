import { useState } from 'react';
import { TaskCard } from './TaskCard';
import { TaskDetailModal } from './TaskDetailModal';
import { TaskFilters } from './TaskFilters';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { EmptyState } from '@/components/shared/EmptyState';
import { CheckCircle, Plus, Trash2 } from 'lucide-react';
import { useCompleteTask, useDeleteTask } from '@/api/queries/useTasks';
import type { Task } from '@/api/types';

interface TaskListProps {
  tasks: Task[];
  transactionId?: number;
  filters: {
    status: string;
    assigneeRole: string;
    search: string;
  };
  onFiltersChange: (filters: { status: string; assigneeRole: string; search: string }) => void;
  onAddTask?: () => void;
  onEditTask?: (task: Task) => void;
  onDeleteTask?: (taskId: number) => void;
}

export const TaskList = ({ tasks, filters, onFiltersChange, onAddTask, onEditTask, onDeleteTask }: TaskListProps) => {
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);
  const [selectedTaskIds, setSelectedTaskIds] = useState<Set<number>>(new Set());
  const [isSelectionMode, setIsSelectionMode] = useState(false);

  const completeMutation = useCompleteTask();
  const deleteMutation = useDeleteTask();

  // Filter tasks client-side for assigneeRole only (not supported by backend yet)
  const filteredTasks = filters.assigneeRole
    ? tasks.filter((task) => task.owner_role === filters.assigneeRole)
    : tasks;

  // Group tasks by status
  const pendingTasks = filteredTasks.filter((t) => t.status === 'pending');
  const completedTasks = filteredTasks.filter((t) => t.status === 'completed');
  const blockedTasks = filteredTasks.filter((t) => t.status === 'blocked');
  const skippedTasks = filteredTasks.filter((t) => t.status === 'skipped');

  // Selection handlers
  const toggleTaskSelection = (taskId: number) => {
    setSelectedTaskIds((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(taskId)) {
        newSet.delete(taskId);
      } else {
        newSet.add(taskId);
      }
      return newSet;
    });
  };

  const selectAll = () => {
    const allTaskIds = filteredTasks.map((t) => t.id);
    setSelectedTaskIds(new Set(allTaskIds));
  };

  const deselectAll = () => {
    setSelectedTaskIds(new Set());
  };

  const toggleSelectionMode = () => {
    setIsSelectionMode((prev) => !prev);
    if (isSelectionMode) {
      deselectAll();
    }
  };

  // Bulk actions
  const handleBulkComplete = async () => {
    if (selectedTaskIds.size === 0) return;

    if (!confirm(`Complete ${selectedTaskIds.size} selected task(s)?`)) {
      return;
    }

    try {
      // Complete each task individually
      await Promise.all(
        Array.from(selectedTaskIds).map((taskId) =>
          completeMutation.mutateAsync({ id: taskId })
        )
      );
      deselectAll();
      setIsSelectionMode(false);
    } catch (error) {
      console.error('Failed to complete tasks:', error);
      alert('Failed to complete some tasks. Please try again.');
    }
  };

  const handleBulkDelete = async () => {
    if (selectedTaskIds.size === 0) return;

    if (!confirm(`Delete ${selectedTaskIds.size} selected task(s)? This cannot be undone.`)) {
      return;
    }

    try {
      // Delete each task individually
      await Promise.all(
        Array.from(selectedTaskIds).map((taskId) =>
          deleteMutation.mutateAsync(taskId)
        )
      );
      deselectAll();
      setIsSelectionMode(false);
    } catch (error) {
      console.error('Failed to delete tasks:', error);
      alert('Failed to delete some tasks. Please try again.');
    }
  };

  const allSelected = filteredTasks.length > 0 && selectedTaskIds.size === filteredTasks.length;

  return (
    <>
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Tasks</CardTitle>
            <div className="flex items-center gap-2">
              {isSelectionMode ? (
                <>
                  {selectedTaskIds.size > 0 && (
                    <>
                      <span className="text-sm text-gray-600">
                        {selectedTaskIds.size} selected
                      </span>
                      <Button
                        size="sm"
                        variant="primary"
                        onClick={handleBulkComplete}
                        isLoading={completeMutation.isPending}
                      >
                        <CheckCircle className="h-4 w-4 mr-2" />
                        Complete
                      </Button>
                      <Button
                        size="sm"
                        variant="danger"
                        onClick={handleBulkDelete}
                        isLoading={deleteMutation.isPending}
                      >
                        <Trash2 className="h-4 w-4 mr-2" />
                        Delete
                      </Button>
                    </>
                  )}
                  <Button size="sm" variant="secondary" onClick={toggleSelectionMode}>
                    Cancel
                  </Button>
                </>
              ) : (
                <>
                  <Button size="sm" variant="secondary" onClick={toggleSelectionMode}>
                    Select
                  </Button>
                  {onAddTask && (
                    <Button size="sm" onClick={onAddTask}>
                      <Plus className="h-4 w-4 mr-2" />
                      Add Task
                    </Button>
                  )}
                </>
              )}
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {/* Selection controls */}
          {isSelectionMode && filteredTasks.length > 0 && (
            <div className="mb-4 flex items-center gap-2">
              <input
                type="checkbox"
                checked={allSelected}
                onChange={allSelected ? deselectAll : selectAll}
                className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
              />
              <span className="text-sm text-gray-700">
                {allSelected ? 'Deselect All' : 'Select All'}
              </span>
            </div>
          )}

          {/* Filters */}
          <TaskFilters filters={filters} onFiltersChange={onFiltersChange} />

          {/* Task groups */}
          {filteredTasks.length === 0 ? (
            <EmptyState
              icon={CheckCircle}
              title="No tasks found"
              description="Try adjusting your filters"
            />
          ) : (
            <div className="space-y-6 mt-6">
              {/* Pending tasks */}
              {pendingTasks.length > 0 && (
                <div>
                  <h3 className="text-sm font-semibold text-gray-900 mb-3">
                    Pending ({pendingTasks.length})
                  </h3>
                  <div className="space-y-3">
                    {pendingTasks.map((task) => (
                      <TaskCard
                        key={task.id}
                        task={task}
                        onTaskClick={isSelectionMode ? undefined : setSelectedTask}
                        onEdit={isSelectionMode ? undefined : onEditTask}
                        onDelete={isSelectionMode ? undefined : onDeleteTask}
                        isSelectionMode={isSelectionMode}
                        isSelected={selectedTaskIds.has(task.id)}
                        onToggleSelect={() => toggleTaskSelection(task.id)}
                      />
                    ))}
                  </div>
                </div>
              )}

              {/* Blocked tasks */}
              {blockedTasks.length > 0 && (
                <div>
                  <h3 className="text-sm font-semibold text-gray-900 mb-3">
                    Blocked ({blockedTasks.length})
                  </h3>
                  <div className="space-y-3">
                    {blockedTasks.map((task) => (
                      <TaskCard
                        key={task.id}
                        task={task}
                        onTaskClick={isSelectionMode ? undefined : setSelectedTask}
                        onEdit={isSelectionMode ? undefined : onEditTask}
                        onDelete={isSelectionMode ? undefined : onDeleteTask}
                        isSelectionMode={isSelectionMode}
                        isSelected={selectedTaskIds.has(task.id)}
                        onToggleSelect={() => toggleTaskSelection(task.id)}
                      />
                    ))}
                  </div>
                </div>
              )}

              {/* Completed tasks */}
              {completedTasks.length > 0 && (
                <div>
                  <h3 className="text-sm font-semibold text-gray-900 mb-3">
                    Completed ({completedTasks.length})
                  </h3>
                  <div className="space-y-3">
                    {completedTasks.map((task) => (
                      <TaskCard
                        key={task.id}
                        task={task}
                        onTaskClick={isSelectionMode ? undefined : setSelectedTask}
                        onEdit={isSelectionMode ? undefined : onEditTask}
                        onDelete={isSelectionMode ? undefined : onDeleteTask}
                        isSelectionMode={isSelectionMode}
                        isSelected={selectedTaskIds.has(task.id)}
                        onToggleSelect={() => toggleTaskSelection(task.id)}
                      />
                    ))}
                  </div>
                </div>
              )}

              {/* Skipped tasks */}
              {skippedTasks.length > 0 && (
                <div>
                  <h3 className="text-sm font-semibold text-gray-900 mb-3">
                    Skipped ({skippedTasks.length})
                  </h3>
                  <div className="space-y-3">
                    {skippedTasks.map((task) => (
                      <TaskCard
                        key={task.id}
                        task={task}
                        onTaskClick={isSelectionMode ? undefined : setSelectedTask}
                        onEdit={isSelectionMode ? undefined : onEditTask}
                        onDelete={isSelectionMode ? undefined : onDeleteTask}
                        isSelectionMode={isSelectionMode}
                        isSelected={selectedTaskIds.has(task.id)}
                        onToggleSelect={() => toggleTaskSelection(task.id)}
                      />
                    ))}
                  </div>
                </div>
              )}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Task detail modal */}
      {selectedTask && (
        <TaskDetailModal
          task={selectedTask}
          isOpen={!!selectedTask}
          onClose={() => setSelectedTask(null)}
        />
      )}
    </>
  );
};
