import React, { useMemo, useState } from 'react';
import { format, differenceInDays, addDays, parseISO, startOfDay } from 'date-fns';
import { Calendar, Clock, CheckCircle2, AlertCircle, Circle } from 'lucide-react';
import type { Task, Transaction } from '../../api/types';

interface GanttChartProps {
  tasks: Task[];
  transaction?: Transaction;
  onTaskClick?: (task: Task) => void;
  onTaskDragEnd?: (taskId: number, newDueDate: string) => void;
  showDependencies?: boolean;
  showMilestones?: boolean;
  groupByPhase?: boolean;
}

interface TimelineTask extends Task {
  startDate: Date;
  endDate: Date;
  durationDays: number;
  rowIndex: number;
}

const STATUS_COLORS = {
  completed: 'bg-green-500',
  in_progress: 'bg-blue-500',
  pending: 'bg-gray-400',
  blocked: 'bg-red-500',
  cancelled: 'bg-gray-300',
  skipped: 'bg-yellow-500',
};

const STATUS_ICONS = {
  completed: CheckCircle2,
  in_progress: Clock,
  pending: Circle,
  blocked: AlertCircle,
  cancelled: Circle,
  skipped: Circle,
};

export const GanttChart: React.FC<GanttChartProps> = ({
  tasks,
  transaction,
  onTaskClick,
  onTaskDragEnd,
  showDependencies = true,
  showMilestones = true,
  // groupByPhase prop is accepted but not yet implemented in the Gantt view
  // Phase grouping is available in the Template Canvas
}) => {
  const [draggedTask, setDraggedTask] = useState<number | null>(null);

  // Helper to safely parse dates
  const safeParseDate = (dateString: string | null | undefined): Date | null => {
    if (!dateString) return null;
    try {
      const parsed = parseISO(dateString);
      // Check if date is valid
      if (isNaN(parsed.getTime())) return null;
      return parsed;
    } catch (error) {
      console.warn('Failed to parse date:', dateString, error);
      return null;
    }
  };

  // Calculate timeline bounds
  const { timelineTasks, minDate, maxDate, totalDays } = useMemo(() => {
    if (tasks.length === 0) {
      return {
        timelineTasks: [],
        minDate: new Date(),
        maxDate: addDays(new Date(), 30),
        totalDays: 30,
      };
    }

    // Find min and max dates from tasks (only valid dates)
    const dates: Date[] = [];

    tasks.forEach((t) => {
      const parsedDate = safeParseDate(t.due_at);
      if (parsedDate) dates.push(parsedDate);
    });

    // Include transaction milestone dates
    if (transaction?.ps_agreement_date) {
      const psDate = safeParseDate(transaction.ps_agreement_date);
      if (psDate) dates.push(psDate);
    }
    if (transaction?.closing_date) {
      const closingDate = safeParseDate(transaction.closing_date);
      if (closingDate) dates.push(closingDate);
    }

    const min = dates.length > 0
      ? startOfDay(new Date(Math.min(...dates.map(d => d.getTime()))))
      : startOfDay(new Date());

    const max = dates.length > 0
      ? startOfDay(new Date(Math.max(...dates.map(d => d.getTime()))))
      : addDays(min, 30);

    // Add padding to timeline
    const paddedMin = addDays(min, -7);
    const paddedMax = addDays(max, 7);
    const total = differenceInDays(paddedMax, paddedMin);

    // Convert tasks to timeline tasks with positions (only tasks with valid dates)
    const timelineTasks: TimelineTask[] = tasks
      .map((task) => {
        const parsedDate = safeParseDate(task.due_at);
        if (!parsedDate) return null;

        const endDate = startOfDay(parsedDate);
        // Estimate start date (3 days before due date, or use created_at)
        const startDate = addDays(endDate, -3);
        const durationDays = Math.max(1, differenceInDays(endDate, startDate));

        return {
          ...task,
          startDate,
          endDate,
          durationDays,
          rowIndex: 0, // Will be set after sorting
        };
      })
      .filter((task): task is TimelineTask => task !== null)
      .sort((a, b) => a.sort_order - b.sort_order)
      .map((task, index) => ({ ...task, rowIndex: index }));

    return {
      timelineTasks,
      minDate: paddedMin,
      maxDate: paddedMax,
      totalDays: total,
    };
  }, [tasks, transaction]);

  // Calculate position for a date
  const getDatePosition = (date: Date): number => {
    const days = differenceInDays(date, minDate);
    return (days / totalDays) * 100;
  };

  // Calculate position and width for a task bar
  const getTaskBarStyle = (task: TimelineTask) => {
    const left = getDatePosition(task.startDate);
    const right = getDatePosition(task.endDate);
    const width = right - left;

    return {
      left: `${Math.max(0, left)}%`,
      width: `${Math.max(1, width)}%`,
    };
  };

  // Handle drag start
  const handleDragStart = (taskId: number, e: React.DragEvent) => {
    setDraggedTask(taskId);
    e.dataTransfer.effectAllowed = 'move';
  };

  // Handle drop
  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    if (!draggedTask || !onTaskDragEnd) return;

    const rect = e.currentTarget.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const percentage = x / rect.width;
    const newDayOffset = Math.floor(percentage * totalDays);
    const newDueDate = addDays(minDate, newDayOffset);

    onTaskDragEnd(draggedTask, format(newDueDate, 'yyyy-MM-dd'));
    setDraggedTask(null);
  };

  // Render milestone marker
  const renderMilestone = (label: string, date: string, color: string) => {
    const parsedDate = safeParseDate(date);
    if (!parsedDate) return null;

    const position = getDatePosition(parsedDate);

    return (
      <div
        key={label}
        className="absolute top-0 bottom-0 flex flex-col items-center pointer-events-none"
        style={{ left: `${position}%` }}
      >
        <div className={`w-0.5 h-full ${color} opacity-30`} />
        <div
          className={`absolute top-0 ${color} text-white text-xs px-2 py-1 rounded shadow-md whitespace-nowrap transform -translate-x-1/2`}
        >
          <Calendar className="inline w-3 h-3 mr-1" />
          {label}
        </div>
      </div>
    );
  };

  // Today marker
  const todayPosition = getDatePosition(new Date());

  if (timelineTasks.length === 0) {
    return (
      <div className="flex items-center justify-center h-64 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
        <div className="text-center">
          <Calendar className="w-12 h-12 mx-auto text-gray-400 mb-2" />
          <p className="text-gray-500">No tasks with due dates to display</p>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200">
      {/* Timeline Header */}
      <div className="p-4 border-b border-gray-200">
        <h3 className="text-lg font-semibold text-gray-900">Transaction Timeline</h3>
        <p className="text-sm text-gray-500 mt-1">
          {format(minDate, 'MMM d, yyyy')} - {format(maxDate, 'MMM d, yyyy')} ({totalDays} days)
        </p>
      </div>

      {/* Gantt Chart */}
      <div className="overflow-x-auto">
        <div className="min-w-full p-4">
          {/* Date axis */}
          <div className="relative h-8 mb-2 bg-gray-50 rounded">
            {Array.from({ length: Math.ceil(totalDays / 7) }, (_, i) => {
              const weekDate = addDays(minDate, i * 7);
              const position = getDatePosition(weekDate);
              return (
                <div
                  key={i}
                  className="absolute text-xs text-gray-600"
                  style={{ left: `${position}%` }}
                >
                  {format(weekDate, 'MMM d')}
                </div>
              );
            })}
          </div>

          {/* Timeline grid and tasks */}
          <div className="relative" style={{ minHeight: `${timelineTasks.length * 50 + 100}px` }}>
            {/* Grid lines */}
            <div className="absolute inset-0 pointer-events-none">
              {Array.from({ length: Math.ceil(totalDays / 7) }, (_, i) => {
                const position = getDatePosition(addDays(minDate, i * 7));
                return (
                  <div
                    key={i}
                    className="absolute top-0 bottom-0 w-px bg-gray-200"
                    style={{ left: `${position}%` }}
                  />
                );
              })}
            </div>

            {/* Today marker */}
            <div
              className="absolute top-0 bottom-0 w-0.5 bg-blue-500 opacity-50 pointer-events-none z-10"
              style={{ left: `${todayPosition}%` }}
            >
              <div className="absolute -top-6 left-1/2 transform -translate-x-1/2 bg-blue-500 text-white text-xs px-2 py-1 rounded">
                Today
              </div>
            </div>

            {/* Milestones */}
            {showMilestones && transaction?.ps_agreement_date &&
              renderMilestone('P&S Date', transaction.ps_agreement_date, 'bg-purple-500')}
            {showMilestones && transaction?.closing_date &&
              renderMilestone('Closing', transaction.closing_date, 'bg-green-600')}

            {/* Task bars */}
            {timelineTasks.map((task) => {
              const StatusIcon = STATUS_ICONS[task.status];
              const barStyle = getTaskBarStyle(task);
              const isOverdue = task.status !== 'completed' &&
                task.due_at &&
                new Date(task.due_at) < new Date();

              return (
                <div
                  key={task.id}
                  className="absolute h-10 flex items-center"
                  style={{
                    top: `${task.rowIndex * 50 + 50}px`,
                    left: 0,
                    right: 0,
                  }}
                >
                  {/* Task label */}
                  <div className="absolute left-0 w-48 pr-4 text-sm truncate">
                    <StatusIcon className="inline w-4 h-4 mr-1" />
                    {task.title}
                  </div>

                  {/* Task bar */}
                  <div className="absolute left-52 right-0">
                    <div
                      className={`
                        h-8 rounded-md cursor-pointer transition-all hover:shadow-lg
                        ${STATUS_COLORS[task.status]}
                        ${isOverdue ? 'ring-2 ring-red-500 ring-offset-2' : ''}
                        ${draggedTask === task.id ? 'opacity-50' : 'opacity-90'}
                      `}
                      style={barStyle}
                      draggable={onTaskDragEnd !== undefined}
                      onDragStart={(e) => handleDragStart(task.id, e)}
                      onDragOver={(e) => e.preventDefault()}
                      onDrop={(e) => handleDrop(e)}
                      onClick={() => onTaskClick?.(task)}
                      title={`${task.title}\nDue: ${task.due_at ? format(task.endDate, 'MMM d, yyyy') : 'No date'}\nStatus: ${task.status}`}
                    >
                      <div className="px-2 py-1 text-white text-xs font-medium truncate">
                        {task.title}
                      </div>
                    </div>
                  </div>

                  {/* Dependencies */}
                  {showDependencies && task.depends_on_task_ids && Array.isArray(task.depends_on_task_ids) && task.depends_on_task_ids.length > 0 && (
                    <div className="absolute left-52 right-0 pointer-events-none">
                      {task.depends_on_task_ids.map((depId) => {
                        const depTask = timelineTasks.find((t) => t.id === depId);
                        if (!depTask) return null;

                        const fromPos = getTaskBarStyle(depTask);
                        const toPos = getTaskBarStyle(task);

                        // Simple arrow line (could be enhanced with SVG)
                        return (
                          <div
                            key={depId}
                            className="absolute h-0.5 bg-gray-400 opacity-50"
                            style={{
                              left: fromPos.left,
                              width: `calc(${toPos.left} - ${fromPos.left})`,
                              top: '16px',
                            }}
                          />
                        );
                      })}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </div>

      {/* Legend */}
      <div className="p-4 border-t border-gray-200 bg-gray-50">
        <div className="flex flex-wrap gap-4 text-sm">
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 bg-green-500 rounded" />
            <span>Completed</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 bg-blue-500 rounded" />
            <span>In Progress</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 bg-gray-400 rounded" />
            <span>Pending</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 bg-red-500 rounded" />
            <span>Blocked</span>
          </div>
          <div className="flex items-center gap-2">
            <div className="w-4 h-4 ring-2 ring-red-500 rounded" />
            <span>Overdue</span>
          </div>
        </div>
      </div>
    </div>
  );
};
