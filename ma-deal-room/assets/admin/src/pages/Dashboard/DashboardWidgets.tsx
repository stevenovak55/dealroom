import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { AlertCircle, Calendar } from 'lucide-react';
import { formatDate, formatRelativeTime, isOverdue } from '@/utils/formatDate';
import type { Transaction, Task } from '@/api/types';
import { Link } from 'react-router-dom';
import { cn } from '@/utils/cn';

/**
 * Dashboard Widgets (Mobile-First Redesign)
 *
 * Responsive dashboard widget components with mobile-first design:
 * - Mobile (<768px): Full-width with touch-friendly spacing
 * - Desktop (>=768px): Optimized layouts with smaller padding
 *
 * Widgets:
 * - StatsWidget: Statistics card with icon and trend
 * - ActiveTransactionsWidget: List of active transactions
 * - OverdueTasksWidget: List of overdue tasks
 * - UpcomingDeadlinesWidget: List of upcoming deadlines
 */

interface StatsWidgetProps {
  title: string;
  value: number;
  icon: React.ReactNode;
  trend?: {
    value: number;
    label: string;
  };
}

export const StatsWidget = ({ title, value, icon, trend }: StatsWidgetProps) => {
  return (
    <Card>
      <CardContent className={cn(
        'flex items-center justify-between',
        // Responsive padding
        'pt-5 md:pt-6',
        'pb-5 md:pb-6'
      )}>
        <div>
          <p className="text-sm md:text-base font-medium text-gray-600">{title}</p>
          <p className="text-2xl md:text-3xl font-bold text-gray-900 mt-2">{value}</p>
          {trend && (
            <p className="text-xs md:text-sm text-gray-500 mt-1">
              {trend.value > 0 ? '+' : ''}{trend.value} {trend.label}
            </p>
          )}
        </div>
        {/* Responsive icon size */}
        <div className={cn(
          'bg-primary-100 rounded-full flex items-center justify-center text-primary-600',
          'h-12 w-12 md:h-14 md:w-14'
        )}>
          {icon}
        </div>
      </CardContent>
    </Card>
  );
};

interface ActiveTransactionsWidgetProps {
  transactions: Transaction[];
  isLoading?: boolean;
}

export const ActiveTransactionsWidget = ({
  transactions,
  isLoading,
}: ActiveTransactionsWidgetProps) => {
  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-lg md:text-xl">Active Transactions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse space-y-3 md:space-y-4">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-16 md:h-20 bg-gray-200 rounded" />
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className={cn(
          'flex items-center justify-between',
          'text-lg md:text-xl'
        )}>
          <span>Active Transactions</span>
          {/* Touch-friendly "View all" link */}
          <Link
            to="/transactions"
            className={cn(
              'text-sm md:text-base text-primary-600 hover:text-primary-700',
              // Increase touch target
              'py-1 px-2 -mr-2'
            )}
          >
            View all
          </Link>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-3 md:space-y-4">
          {transactions.slice(0, 5).map((transaction) => (
            <Link
              key={transaction.transaction_id}
              to={`/transactions/${transaction.transaction_id}`}
              className={cn(
                'block rounded-lg border border-gray-200',
                'hover:border-primary-300 hover:bg-primary-50',
                'transition-colors',
                // Touch-friendly padding
                'p-4 md:p-3'
              )}
            >
              <div className="flex items-start justify-between gap-3">
                <div className="flex-1 min-w-0">
                  <p className="text-base md:text-sm font-medium text-gray-900 truncate">
                    {transaction.property_address}
                  </p>
                  <p className="text-sm md:text-xs text-gray-500 mt-1">
                    {transaction.property_city}, {transaction.property_state}
                  </p>
                </div>
                <Badge variant="info" size="md">{transaction.property_type}</Badge>
              </div>
              <div className={cn(
                'mt-3 md:mt-2',
                'flex flex-wrap items-center gap-3 md:gap-4',
                'text-xs md:text-xs text-gray-500'
              )}>
                <span>Closing: {formatDate(transaction.closing_date)}</span>
                {transaction.task_summary && (
                  <span>
                    {transaction.task_summary.completed}/{transaction.task_summary.total} tasks
                  </span>
                )}
              </div>
            </Link>
          ))}
        </div>
      </CardContent>
    </Card>
  );
};

interface OverdueTasksWidgetProps {
  tasks: Task[];
  isLoading?: boolean;
}

export const OverdueTasksWidget = ({ tasks, isLoading }: OverdueTasksWidgetProps) => {
  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-lg md:text-xl">Overdue Tasks</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse space-y-3 md:space-y-4">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-14 md:h-12 bg-gray-200 rounded" />
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  const overdueTasks = tasks.filter((task) => isOverdue(task.due_at));

  return (
    <Card>
      <CardHeader>
        <CardTitle className={cn(
          'flex items-center gap-2',
          'text-lg md:text-xl'
        )}>
          <AlertCircle className="h-5 w-5 md:h-6 md:w-6 text-danger-500" />
          Overdue Tasks ({overdueTasks.length})
        </CardTitle>
      </CardHeader>
      <CardContent>
        {overdueTasks.length === 0 ? (
          <p className="text-sm md:text-base text-gray-500 text-center py-6 md:py-4">
            No overdue tasks
          </p>
        ) : (
          <div className="space-y-3 md:space-y-2">
            {overdueTasks.slice(0, 5).map((task) => (
              <div
                key={task.id}
                className={cn(
                  'rounded-lg border border-danger-200 bg-danger-50',
                  // Touch-friendly padding
                  'p-4 md:p-3'
                )}
              >
                <div className="flex items-start justify-between gap-3">
                  <div className="flex-1 min-w-0">
                    <p className="text-base md:text-sm font-medium text-gray-900">
                      {task.title}
                    </p>
                    <p className="text-sm md:text-xs text-danger-600 mt-1">
                      Due {formatRelativeTime(task.due_at)}
                    </p>
                  </div>
                  <Badge variant="danger" size="md">Overdue</Badge>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};

interface UpcomingDeadlinesWidgetProps {
  tasks: Task[];
  isLoading?: boolean;
}

export const UpcomingDeadlinesWidget = ({ tasks, isLoading }: UpcomingDeadlinesWidgetProps) => {
  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle className="text-lg md:text-xl">Upcoming Deadlines</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse space-y-3 md:space-y-4">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-14 md:h-12 bg-gray-200 rounded" />
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className={cn(
          'flex items-center gap-2',
          'text-lg md:text-xl'
        )}>
          <Calendar className="h-5 w-5 md:h-6 md:w-6 text-warning-500" />
          <span className="hidden sm:inline">Upcoming Deadlines (Next 7 Days)</span>
          <span className="sm:hidden">Upcoming (7 Days)</span>
        </CardTitle>
      </CardHeader>
      <CardContent>
        {tasks.length === 0 ? (
          <p className="text-sm md:text-base text-gray-500 text-center py-6 md:py-4">
            No upcoming deadlines
          </p>
        ) : (
          <div className="space-y-3 md:space-y-2">
            {tasks.slice(0, 5).map((task) => (
              <div
                key={task.id}
                className={cn(
                  'rounded-lg border border-gray-200',
                  'hover:bg-gray-50 transition-colors',
                  // Touch-friendly padding
                  'p-4 md:p-3'
                )}
              >
                <div className="flex items-start justify-between gap-3">
                  <div className="flex-1 min-w-0">
                    <p className="text-base md:text-sm font-medium text-gray-900">
                      {task.title}
                    </p>
                    <p className="text-sm md:text-xs text-gray-500 mt-1">
                      Due {formatDate(task.due_at)}
                    </p>
                  </div>
                  <Badge variant="warning" size="md">Upcoming</Badge>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};
