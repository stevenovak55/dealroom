import React, { useState, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Download, Search } from 'lucide-react';
import { useGetTransaction } from '../../api/queries/useTransactions';
import { useGetTasks } from '../../api/queries/useTasks';
import { GanttChart } from '../../components/Timeline/GanttChart';
import type { Task } from '../../api/types';
import toast from 'react-hot-toast';
import { cn } from '@/utils/cn';

/**
 * TimelineView Component (Mobile-First Redesign)
 *
 * Responsive Gantt chart timeline view with mobile-first design:
 * - Mobile (<768px): Stacked layout, scrollable filters, vertical controls
 * - Desktop (>=768px): Grid layouts, horizontal controls
 *
 * Features:
 * - Interactive Gantt chart with task dependencies
 * - Search and filter tasks
 * - Toggle dependencies, milestones, and phase grouping
 * - Export timeline (coming soon)
 * - Touch-friendly controls
 */

export const TimelineView: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const transactionId = id ? parseInt(id, 10) : 0;

  const { data: transaction, isLoading: transactionLoading } = useGetTransaction(transactionId);
  const { data: tasksResponse, isLoading: tasksLoading } = useGetTasks({
    transaction_id: transactionId,
  });

  // Filters
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [ownerRoleFilter, setOwnerRoleFilter] = useState<string>('all');
  const [showDependencies, setShowDependencies] = useState(true);
  const [showMilestones, setShowMilestones] = useState(true);
  const [groupByPhase, setGroupByPhase] = useState(true);

  // Filter tasks
  const filteredTasks = useMemo(() => {
    if (!tasksResponse?.data) return [];

    let tasks = tasksResponse.data;

    // Search filter
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      tasks = tasks.filter((task) =>
        task.title?.toLowerCase().includes(query) ||
        task.description?.toLowerCase().includes(query)
      );
    }

    // Status filter
    if (statusFilter !== 'all') {
      tasks = tasks.filter((task) => task.status === statusFilter);
    }

    // Owner role filter
    if (ownerRoleFilter !== 'all') {
      tasks = tasks.filter((task) => task.owner_role === ownerRoleFilter);
    }

    return tasks;
  }, [tasksResponse, searchQuery, statusFilter, ownerRoleFilter]);

  // Handle task click
  const handleTaskClick = (task: Task) => {
    // Navigate to task detail or open modal
    navigate(`/transactions/${transactionId}?taskId=${task.id}`);
  };

  // Handle drag end (reschedule task)
  const handleTaskDragEnd = async (taskId: number, newDueDate: string) => {
    // For now, just show a toast. Full implementation would require
    // backend API endpoint that accepts task updates
    toast.success(`Task ${taskId} rescheduled to ${newDueDate}`);
    console.log('Task rescheduled:', { taskId, newDueDate });
    // TODO: Implement task rescheduling API call
    // await apiClient.put(`/tasks/${taskId}`, { due_at: newDueDate });
  };

  // Export timeline
  const handleExport = () => {
    toast.success('Export feature coming soon!');
    // TODO: Implement export to PDF/image
  };

  if (transactionLoading || tasksLoading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" />
      </div>
    );
  }

  if (!transaction) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900">Transaction not found</h2>
          <button
            onClick={() => navigate('/transactions')}
            className="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600"
          >
            Back to Transactions
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-5 md:space-y-6">
      {/* Header - responsive layout */}
      <div className="bg-white shadow-sm rounded-lg p-5 md:p-6">
        <div className={cn(
          'flex flex-col space-y-4',
          'md:flex-row md:items-start md:justify-between md:space-y-0'
        )}>
          <div>
            <h1 className="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900">
              Timeline View
            </h1>
            <p className="text-base md:text-lg text-gray-600 mt-1">
              {transaction.property_address}
            </p>
            <p className="text-sm md:text-base text-gray-500 mt-0.5">
              {transaction.property_city}, {transaction.property_state} {transaction.property_zip}
            </p>
          </div>
          {/* Action buttons - responsive layout */}
          <div className={cn(
            'flex flex-col space-y-2',
            'sm:flex-row sm:gap-2 sm:space-y-0'
          )}>
            <button
              onClick={handleExport}
              className={cn(
                'flex items-center justify-center gap-2',
                'px-6 py-3 md:px-4 md:py-2',
                'border border-gray-300 rounded-md',
                'hover:bg-gray-50 transition-colors',
                'text-base md:text-sm font-medium',
                'w-full sm:w-auto'
              )}
            >
              <Download className="w-5 h-5 md:w-4 md:h-4" />
              Export
            </button>
            <button
              onClick={() => navigate(`/transactions/${transactionId}`)}
              className={cn(
                'px-6 py-3 md:px-4 md:py-2',
                'bg-blue-500 text-white rounded-md',
                'hover:bg-blue-600 transition-colors',
                'text-base md:text-sm font-medium',
                'w-full sm:w-auto'
              )}
            >
              Back to Details
            </button>
          </div>
        </div>

        {/* Transaction Info - responsive grid */}
        <div className={cn(
          'mt-5 md:mt-4',
          'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-4'
        )}>
          <div className="bg-gray-50 rounded-lg p-4 md:p-3">
            <p className="text-xs md:text-xs text-gray-500 uppercase">Status</p>
            <p className="text-sm md:text-sm font-semibold text-gray-900 capitalize mt-1">
              {transaction.status.replace('_', ' ')}
            </p>
          </div>
          <div className="bg-gray-50 rounded-lg p-4 md:p-3">
            <p className="text-xs md:text-xs text-gray-500 uppercase">Property Type</p>
            <p className="text-sm md:text-sm font-semibold text-gray-900 mt-1">
              {transaction.property_type}
            </p>
          </div>
          <div className="bg-gray-50 rounded-lg p-4 md:p-3">
            <p className="text-xs md:text-xs text-gray-500 uppercase">P&S Date</p>
            <p className="text-sm md:text-sm font-semibold text-gray-900 mt-1">
              {transaction.ps_agreement_date
                ? new Date(transaction.ps_agreement_date).toLocaleDateString()
                : 'Not set'}
            </p>
          </div>
          <div className="bg-gray-50 rounded-lg p-4 md:p-3">
            <p className="text-xs md:text-xs text-gray-500 uppercase">Closing Date</p>
            <p className="text-sm md:text-sm font-semibold text-gray-900 mt-1">
              {transaction.closing_date
                ? new Date(transaction.closing_date).toLocaleDateString()
                : 'Not set'}
            </p>
          </div>
        </div>
      </div>

      {/* Filters and Controls - responsive layout */}
      <div className="bg-white shadow-sm rounded-lg p-4 md:p-4">
        <div className="flex flex-col gap-4 md:flex-row md:flex-wrap md:items-center">
          {/* Search - full-width on mobile */}
          <div className="flex-1 min-w-full md:min-w-[200px]">
            <div className="relative">
              <Search className={cn(
                'absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400',
                'w-5 h-5 md:w-4 md:h-4'
              )} />
              <input
                type="text"
                placeholder="Search tasks..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className={cn(
                  'w-full border border-gray-300 rounded-md',
                  'focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                  'text-base md:text-sm',
                  // Touch-friendly padding
                  'pl-10 pr-4 py-3 md:py-2'
                )}
              />
            </div>
          </div>

          {/* Status Filter - full-width on mobile */}
          <div className="w-full md:w-auto md:min-w-[150px]">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className={cn(
                'w-full border border-gray-300 rounded-md',
                'focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'text-base md:text-sm',
                // Touch-friendly padding
                'px-4 py-3 md:px-3 md:py-2'
              )}
            >
              <option value="all">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="in_progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="blocked">Blocked</option>
              <option value="skipped">Skipped</option>
            </select>
          </div>

          {/* Owner Role Filter - full-width on mobile */}
          <div className="w-full md:w-auto md:min-w-[150px]">
            <select
              value={ownerRoleFilter}
              onChange={(e) => setOwnerRoleFilter(e.target.value)}
              className={cn(
                'w-full border border-gray-300 rounded-md',
                'focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                'text-base md:text-sm',
                // Touch-friendly padding
                'px-4 py-3 md:px-3 md:py-2'
              )}
            >
              <option value="all">All Roles</option>
              <option value="agent">Agent</option>
              <option value="buyer">Buyer</option>
              <option value="seller">Seller</option>
              <option value="buyer_attorney">Buyer Attorney</option>
              <option value="seller_attorney">Seller Attorney</option>
              <option value="vendor">Vendor</option>
            </select>
          </div>

          {/* View Options - stacked on mobile, horizontal on desktop */}
          <div className="flex flex-col gap-3 md:flex-row md:gap-2 w-full md:w-auto">
            <label className={cn(
              'flex items-center gap-2',
              'text-sm md:text-sm',
              // Touch-friendly padding
              'py-1'
            )}>
              <input
                type="checkbox"
                checked={showDependencies}
                onChange={(e) => setShowDependencies(e.target.checked)}
                className={cn(
                  'rounded border-gray-300 text-blue-500 focus:ring-blue-500',
                  // Touch-friendly size
                  'w-5 h-5 md:w-4 md:h-4'
                )}
              />
              Dependencies
            </label>
            <label className={cn(
              'flex items-center gap-2',
              'text-sm md:text-sm',
              // Touch-friendly padding
              'py-1'
            )}>
              <input
                type="checkbox"
                checked={showMilestones}
                onChange={(e) => setShowMilestones(e.target.checked)}
                className={cn(
                  'rounded border-gray-300 text-blue-500 focus:ring-blue-500',
                  // Touch-friendly size
                  'w-5 h-5 md:w-4 md:h-4'
                )}
              />
              Milestones
            </label>
            <label className={cn(
              'flex items-center gap-2',
              'text-sm md:text-sm',
              // Touch-friendly padding
              'py-1'
            )}>
              <input
                type="checkbox"
                checked={groupByPhase}
                onChange={(e) => setGroupByPhase(e.target.checked)}
                className={cn(
                  'rounded border-gray-300 text-blue-500 focus:ring-blue-500',
                  // Touch-friendly size
                  'w-5 h-5 md:w-4 md:h-4'
                )}
              />
              Group by Phase
            </label>
          </div>
        </div>

        {/* Task Count - responsive text */}
        <div className="mt-3 text-sm md:text-sm text-gray-600">
          Showing {filteredTasks.length} of {tasksResponse?.data?.length || 0} tasks
        </div>
      </div>

      {/* Gantt Chart */}
      <GanttChart
        tasks={filteredTasks}
        transaction={transaction}
        onTaskClick={handleTaskClick}
        onTaskDragEnd={handleTaskDragEnd}
        showDependencies={showDependencies}
        showMilestones={showMilestones}
        groupByPhase={groupByPhase}
      />
    </div>
  );
};
