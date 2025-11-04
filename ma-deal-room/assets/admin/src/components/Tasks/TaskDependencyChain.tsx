import React, { useMemo, useState } from 'react';
import { GitBranch, ChevronRight, CheckCircle, Clock, AlertCircle, Circle } from 'lucide-react';
import type { Task } from '@/api/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { format } from 'date-fns';

interface TaskDependencyChainProps {
  tasks: Task[];
  onTaskClick?: (task: Task) => void;
}

interface TaskNode {
  task: Task;
  dependencies: Task[];
  dependents: Task[];
  level: number;
}

const STATUS_COLORS = {
  completed: 'bg-green-500 text-white',
  in_progress: 'bg-blue-500 text-white',
  pending: 'bg-gray-400 text-white',
  blocked: 'bg-red-500 text-white',
  cancelled: 'bg-gray-300 text-gray-600',
  skipped: 'bg-yellow-500 text-white',
};

const STATUS_ICONS = {
  completed: CheckCircle,
  in_progress: Clock,
  pending: Circle,
  blocked: AlertCircle,
  cancelled: Circle,
  skipped: Circle,
};

export const TaskDependencyChain: React.FC<TaskDependencyChainProps> = ({
  tasks,
  onTaskClick,
}) => {
  const [selectedTaskId, setSelectedTaskId] = useState<number | null>(null);
  const [viewMode, setViewMode] = useState<'tree' | 'list'>('tree');

  // Build dependency graph
  const dependencyGraph = useMemo(() => {
    const graph = new Map<number, TaskNode>();

    // Initialize nodes
    tasks.forEach(taskItem => {
      graph.set(taskItem.id, {
        task: taskItem,
        dependencies: [],
        dependents: [],
        level: 0,
      });
    });

    // Build dependencies
    tasks.forEach(task => {
      const node = graph.get(task.id);
      if (!node) return;

      if (task.depends_on_task_ids && Array.isArray(task.depends_on_task_ids)) {
        task.depends_on_task_ids.forEach(depId => {
          const depTask = tasks.find(t => t.id === depId);
          if (depTask) {
            node.dependencies.push(depTask);
            const depNode = graph.get(depId);
            if (depNode) {
              depNode.dependents.push(task);
            }
          }
        });
      }
    });

    // Calculate levels (for tree view)
    const calculateLevel = (taskId: number, visited = new Set<number>()): number => {
      if (visited.has(taskId)) return 0; // Circular dependency
      visited.add(taskId);

      const node = graph.get(taskId);
      if (!node || node.dependencies.length === 0) return 0;

      const maxDepLevel = Math.max(
        ...node.dependencies.map(dep => calculateLevel(dep.id, new Set(visited)))
      );
      return maxDepLevel + 1;
    };

    tasks.forEach(task => {
      const node = graph.get(task.id);
      if (node) {
        node.level = calculateLevel(task.id);
      }
    });

    return graph;
  }, [tasks]);

  // Get root tasks (no dependencies)
  const rootTasks = useMemo(() => {
    return tasks.filter(task => {
      const node = dependencyGraph.get(task.id);
      return node && node.dependencies.length === 0;
    });
  }, [tasks, dependencyGraph]);

  // Get tasks by level
  const tasksByLevel = useMemo(() => {
    const levels = new Map<number, Task[]>();
    dependencyGraph.forEach((node) => {
      if (!levels.has(node.level)) {
        levels.set(node.level, []);
      }
      levels.get(node.level)?.push(node.task);
    });
    return levels;
  }, [dependencyGraph]);

  // Render task node
  const renderTaskNode = (task: Task, depth = 0) => {
    const node = dependencyGraph.get(task.id);
    if (!node) return null;

    const StatusIcon = STATUS_ICONS[task.status];
    const isSelected = selectedTaskId === task.id;

    return (
      <div key={task.id} className="relative">
        <div
          className={`flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-all ${
            isSelected
              ? 'bg-blue-50 border-2 border-blue-500'
              : 'bg-white border border-gray-200 hover:border-gray-300 hover:shadow-sm'
          }`}
          style={{ marginLeft: `${depth * 24}px` }}
          onClick={() => {
            setSelectedTaskId(task.id);
            onTaskClick?.(task);
          }}
        >
          {/* Status Icon */}
          <div className={`p-2 rounded ${STATUS_COLORS[task.status]}`}>
            <StatusIcon className="h-4 w-4" />
          </div>

          {/* Task Info */}
          <div className="flex-1 min-w-0">
            <h4 className="font-medium text-gray-900 text-sm truncate">
              {task.title}
            </h4>
            <div className="flex items-center gap-3 mt-1">
              <span className="text-xs text-gray-500">
                {task.owner_role.replace('_', ' ')}
              </span>
              {task.due_at && (
                <span className="text-xs text-gray-500">
                  Due: {format(new Date(task.due_at), 'MMM d')}
                </span>
              )}
            </div>
          </div>

          {/* Dependency Count */}
          {node.dependencies.length > 0 && (
            <div className="flex items-center gap-1 text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
              <GitBranch className="h-3 w-3" />
              {node.dependencies.length}
            </div>
          )}

          {node.dependents.length > 0 && (
            <div className="flex items-center gap-1 text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded">
              <ChevronRight className="h-3 w-3" />
              {node.dependents.length}
            </div>
          )}
        </div>

        {/* Render dependents recursively */}
        {viewMode === 'tree' && node.dependents.length > 0 && (
          <div className="mt-2 space-y-2">
            {node.dependents.map(dependent => renderTaskNode(dependent, depth + 1))}
          </div>
        )}
      </div>
    );
  };

  // Critical path analysis
  const criticalPath = useMemo(() => {
    const findLongestPath = (taskId: number, visited = new Set<number>()): Task[] => {
      if (visited.has(taskId)) return [];
      visited.add(taskId);

      const node = dependencyGraph.get(taskId);
      if (!node) return [];

      if (node.dependents.length === 0) {
        return [node.task];
      }

      const paths = node.dependents.map(dep =>
        findLongestPath(dep.id, new Set(visited))
      );

      const longestPath = paths.reduce((longest, current) =>
        current.length > longest.length ? current : longest
      , []);

      return [node.task, ...longestPath];
    };

    const paths = rootTasks.map(task => findLongestPath(task.id));
    return paths.reduce((longest, current) =>
      current.length > longest.length ? current : longest
    , []);
  }, [rootTasks, dependencyGraph]);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-blue-100 rounded-lg">
            <GitBranch className="h-5 w-5 text-blue-600" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-gray-900">Task Dependency Chain</h3>
            <p className="text-sm text-gray-600">
              {tasks.length} tasks • {rootTasks.length} root tasks • {tasksByLevel.size} levels
            </p>
          </div>
        </div>

        {/* View Mode Toggle */}
        <div className="flex items-center gap-2 bg-gray-100 p-1 rounded-lg">
          <button
            onClick={() => setViewMode('tree')}
            className={`px-3 py-1 text-sm rounded ${
              viewMode === 'tree'
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            Tree View
          </button>
          <button
            onClick={() => setViewMode('list')}
            className={`px-3 py-1 text-sm rounded ${
              viewMode === 'list'
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            Level View
          </button>
        </div>
      </div>

      {/* Critical Path */}
      {criticalPath.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <AlertCircle className="h-4 w-4 text-amber-600" />
              Critical Path ({criticalPath.length} tasks)
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2 overflow-x-auto pb-2">
              {criticalPath.map((task, idx) => (
                <React.Fragment key={task.id}>
                  <div
                    className={`flex-shrink-0 px-3 py-2 rounded border-2 cursor-pointer ${
                      task.status === 'completed'
                        ? 'border-green-500 bg-green-50'
                        : 'border-amber-500 bg-amber-50'
                    }`}
                    onClick={() => {
                      setSelectedTaskId(task.id);
                      onTaskClick?.(task);
                    }}
                  >
                    <p className="text-sm font-medium text-gray-900 whitespace-nowrap">
                      {task.title}
                    </p>
                    <p className="text-xs text-gray-600">{task.owner_role.replace('_', ' ')}</p>
                  </div>
                  {idx < criticalPath.length - 1 && (
                    <ChevronRight className="h-4 w-4 text-gray-400 flex-shrink-0" />
                  )}
                </React.Fragment>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Dependency Tree/List */}
      <Card>
        <CardContent className="p-4">
          {viewMode === 'tree' ? (
            <div className="space-y-2">
              {rootTasks.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <GitBranch className="h-12 w-12 mx-auto mb-3 text-gray-300" />
                  <p>All tasks have dependencies</p>
                </div>
              ) : (
                rootTasks.map(task => renderTaskNode(task))
              )}
            </div>
          ) : (
            <div className="space-y-6">
              {Array.from(tasksByLevel.entries())
                .sort(([a], [b]) => a - b)
                .map(([level, levelTasks]) => (
                  <div key={level}>
                    <h4 className="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                      <span className="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">
                        Level {level}
                      </span>
                      <span className="text-gray-500">({levelTasks.length} tasks)</span>
                    </h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                      {levelTasks.map(task => renderTaskNode(task, 0))}
                    </div>
                  </div>
                ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Selected Task Details */}
      {selectedTaskId && dependencyGraph.get(selectedTaskId) && (
        <Card>
          <CardHeader>
            <CardTitle className="text-base">Dependency Details</CardTitle>
          </CardHeader>
          <CardContent>
            {(() => {
              const node = dependencyGraph.get(selectedTaskId);
              if (!node) return null;

              return (
                <div className="space-y-4">
                  <div>
                    <h4 className="font-medium text-gray-900 mb-2">{node.task.title}</h4>
                    <p className="text-sm text-gray-600">{node.task.description}</p>
                  </div>

                  {node.dependencies.length > 0 && (
                    <div>
                      <h5 className="text-sm font-medium text-gray-700 mb-2">
                        Depends On ({node.dependencies.length})
                      </h5>
                      <div className="space-y-1">
                        {node.dependencies.map(dep => (
                          <div
                            key={dep.id}
                            className="text-sm p-2 bg-gray-50 rounded flex items-center justify-between"
                          >
                            <span>{dep.title}</span>
                            <span className={`px-2 py-0.5 rounded text-xs ${STATUS_COLORS[dep.status]}`}>
                              {dep.status}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {node.dependents.length > 0 && (
                    <div>
                      <h5 className="text-sm font-medium text-gray-700 mb-2">
                        Blocks ({node.dependents.length})
                      </h5>
                      <div className="space-y-1">
                        {node.dependents.map(dep => (
                          <div
                            key={dep.id}
                            className="text-sm p-2 bg-blue-50 rounded flex items-center justify-between"
                          >
                            <span>{dep.title}</span>
                            <span className={`px-2 py-0.5 rounded text-xs ${STATUS_COLORS[dep.status]}`}>
                              {dep.status}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              );
            })()}
          </CardContent>
        </Card>
      )}
    </div>
  );
};
