import React, { useState, useMemo } from 'react';
import { GripVertical, Trash2, Eye, EyeOff, ChevronDown, ChevronRight } from 'lucide-react';
import type { TaskDefinition, TaskPhase } from '@/api/types';
import { TASK_PHASES } from '@/api/types';

export interface TemplateTaskItem {
  id: string; // Unique ID for this instance
  taskDefinition: TaskDefinition;
  sortOrder: number;
  isOptional: boolean;
  overrideDueCalculation?: string;
  overrideOwnerRole?: string;
}

interface TemplateCanvasProps {
  tasks: TemplateTaskItem[];
  onTasksChange: (tasks: TemplateTaskItem[]) => void;
  onTaskRemove: (taskId: string) => void;
}

const PHASE_ORDER: TaskPhase[] = [
  'pre_listing',
  'post_listing',
  'pre_agreement',
  'post_agreement',
  'pre_closing',
  'post_closing',
  'any',
];

export const TemplateCanvas: React.FC<TemplateCanvasProps> = ({
  tasks,
  onTasksChange,
  onTaskRemove,
}) => {
  const [draggedTaskId, setDraggedTaskId] = useState<string | null>(null);
  const [dragOverIndex, setDragOverIndex] = useState<number | null>(null);
  const [collapsedPhases, setCollapsedPhases] = useState<Set<string>>(new Set());

  // Group tasks by phase
  const tasksByPhase = useMemo(() => {
    const grouped = new Map<TaskPhase, TemplateTaskItem[]>();

    // Initialize all phases
    PHASE_ORDER.forEach(phase => {
      grouped.set(phase, []);
    });

    // Group tasks by their phase
    tasks.forEach(task => {
      const phase = task.taskDefinition.phase || 'any';
      const phaseTask = grouped.get(phase) || [];
      phaseTask.push(task);
      grouped.set(phase, phaseTask);
    });

    return grouped;
  }, [tasks]);

  // Handle drop from sidebar (new task)
  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();

    const taskDataStr = e.dataTransfer.getData('application/json');
    if (!taskDataStr) return;

    try {
      const taskDefinition: TaskDefinition = JSON.parse(taskDataStr);

      // Check if task already exists
      if (tasks.some((t) => t.taskDefinition.id === taskDefinition.id)) {
        alert('This task is already in the template');
        return;
      }

      // Add new task
      const newTask: TemplateTaskItem = {
        id: `temp-${Date.now()}`,
        taskDefinition,
        sortOrder: tasks.length,
        isOptional: false,
      };

      onTasksChange([...tasks, newTask]);
    } catch (error) {
      console.error('Failed to parse task data:', error);
    }

    setDragOverIndex(null);
  };

  // Handle drag over
  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'copy';
  };

  // Handle reorder drag start
  const handleTaskDragStart = (e: React.DragEvent, taskId: string) => {
    setDraggedTaskId(taskId);
    e.dataTransfer.effectAllowed = 'move';
  };

  // Handle reorder drag over
  const handleTaskDragOver = (e: React.DragEvent, index: number) => {
    e.preventDefault();
    e.stopPropagation();
    setDragOverIndex(index);
  };

  // Handle reorder drop
  const handleTaskDrop = (e: React.DragEvent, targetIndex: number) => {
    e.preventDefault();
    e.stopPropagation();

    if (!draggedTaskId) return;

    const draggedIndex = tasks.findIndex((t) => t.id === draggedTaskId);
    if (draggedIndex === -1) return;

    // Reorder tasks
    const newTasks = [...tasks];
    const [removed] = newTasks.splice(draggedIndex, 1);
    newTasks.splice(targetIndex, 0, removed);

    // Update sort orders
    const reordered = newTasks.map((task, index) => ({
      ...task,
      sortOrder: index,
    }));

    onTasksChange(reordered);
    setDraggedTaskId(null);
    setDragOverIndex(null);
  };

  // Toggle optional
  const toggleOptional = (taskId: string) => {
    const updated = tasks.map((task) =>
      task.id === taskId ? { ...task, isOptional: !task.isOptional } : task
    );
    onTasksChange(updated);
  };

  // Toggle phase collapse
  const togglePhase = (phase: string) => {
    const newCollapsed = new Set(collapsedPhases);
    if (newCollapsed.has(phase)) {
      newCollapsed.delete(phase);
    } else {
      newCollapsed.add(phase);
    }
    setCollapsedPhases(newCollapsed);
  };

  // Get phase color
  const getPhaseColor = (phase: TaskPhase): string => {
    const colors: Record<TaskPhase, string> = {
      pre_listing: 'border-sky-300 bg-sky-50',
      post_listing: 'border-blue-300 bg-blue-50',
      pre_agreement: 'border-amber-300 bg-amber-50',
      post_agreement: 'border-orange-300 bg-orange-50',
      pre_closing: 'border-purple-300 bg-purple-50',
      post_closing: 'border-emerald-300 bg-emerald-50',
      any: 'border-gray-300 bg-gray-50',
    };
    return colors[phase] || 'border-gray-300 bg-gray-50';
  };

  return (
    <div
      className="h-full flex flex-col bg-gray-50"
      onDrop={handleDrop}
      onDragOver={handleDragOver}
    >
      {/* Header */}
      <div className="p-4 bg-white border-b border-gray-200">
        <h2 className="text-lg font-semibold text-gray-900">Template Tasks</h2>
        <p className="text-sm text-gray-600 mt-1">
          {tasks.length} {tasks.length === 1 ? 'task' : 'tasks'} in template
        </p>
      </div>

      {/* Task List - Grouped by Phase */}
      <div className="flex-1 overflow-y-auto p-4">
        {tasks.length === 0 ? (
          <div className="flex items-center justify-center h-full">
            <div className="text-center max-w-md">
              <svg
                className="w-20 h-20 mx-auto text-gray-300 mb-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={1}
                  d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                No tasks yet
              </h3>
              <p className="text-gray-600">
                Drag tasks from the library to build your template
              </p>
            </div>
          </div>
        ) : (
          <div className="space-y-3">
            {PHASE_ORDER.map((phase) => {
              const phaseTasks = tasksByPhase.get(phase) || [];
              if (phaseTasks.length === 0) return null;

              const isCollapsed = collapsedPhases.has(phase);

              return (
                <div key={phase} className={`border-l-4 rounded ${getPhaseColor(phase)}`}>
                  {/* Phase Header */}
                  <button
                    onClick={() => togglePhase(phase)}
                    className="w-full px-3 py-2 flex items-center justify-between hover:bg-opacity-70 transition-colors"
                  >
                    <div className="flex items-center gap-2">
                      {isCollapsed ? (
                        <ChevronRight className="w-4 h-4" />
                      ) : (
                        <ChevronDown className="w-4 h-4" />
                      )}
                      <span className="font-medium text-sm">
                        {TASK_PHASES[phase]?.label || phase}
                      </span>
                      <span className="text-xs text-gray-600">
                        ({phaseTasks.length})
                      </span>
                    </div>
                    <span className="text-xs text-gray-500">
                      {TASK_PHASES[phase]?.description}
                    </span>
                  </button>

                  {/* Phase Tasks */}
                  {!isCollapsed && (
                    <div className="space-y-1 pb-2 px-2">
                      {phaseTasks.map((task) => {
                        const globalIndex = tasks.findIndex(t => t.id === task.id);
                        return (
                          <div
                            key={task.id}
                            draggable
                            onDragStart={(e) => handleTaskDragStart(e, task.id)}
                            onDragOver={(e) => handleTaskDragOver(e, globalIndex)}
                            onDrop={(e) => handleTaskDrop(e, globalIndex)}
                            className={`
                              bg-white rounded border transition-all
                              ${draggedTaskId === task.id ? 'opacity-50 border-blue-400' : 'border-gray-300'}
                              ${dragOverIndex === globalIndex ? 'border-blue-400 border-dashed' : ''}
                              ${task.isOptional ? 'bg-gray-50' : ''}
                              hover:shadow-sm cursor-move
                            `}
                          >
                            <div className="px-3 py-2 flex items-center gap-2">
                              {/* Drag Handle & Number */}
                              <GripVertical className="w-4 h-4 text-gray-400 flex-shrink-0" />
                              <span className="text-xs font-medium text-gray-500 w-6 flex-shrink-0">
                                #{globalIndex + 1}
                              </span>

                              {/* Task Title */}
                              <div className="flex-1 min-w-0">
                                <span className="text-sm text-gray-900 truncate block">
                                  {task.taskDefinition.title}
                                  {task.isOptional && (
                                    <span className="ml-1.5 text-xs text-gray-500 italic">
                                      (optional)
                                    </span>
                                  )}
                                </span>
                              </div>

                              {/* Actions */}
                              <div className="flex items-center gap-0.5 flex-shrink-0">
                                <button
                                  onClick={() => toggleOptional(task.id)}
                                  className="p-1 hover:bg-gray-100 rounded transition-colors"
                                  title={task.isOptional ? 'Mark as required' : 'Mark as optional'}
                                >
                                  {task.isOptional ? (
                                    <EyeOff className="w-3.5 h-3.5 text-gray-400" />
                                  ) : (
                                    <Eye className="w-3.5 h-3.5 text-gray-600" />
                                  )}
                                </button>
                                <button
                                  onClick={() => onTaskRemove(task.id)}
                                  className="p-1 hover:bg-red-50 rounded transition-colors"
                                  title="Remove task"
                                >
                                  <Trash2 className="w-3.5 h-3.5 text-red-600" />
                                </button>
                              </div>
                            </div>
                          </div>
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
};
