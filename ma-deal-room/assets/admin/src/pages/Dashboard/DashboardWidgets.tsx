import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { AlertCircle, Calendar } from 'lucide-react';
import { formatDate, formatRelativeTime, isOverdue } from '@/utils/formatDate';
import type { Transaction, Task } from '@/api/types';
import { Link } from 'react-router-dom';

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
      <CardContent className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-600">{title}</p>
          <p className="text-3xl font-bold text-gray-900 mt-2">{value}</p>
          {trend && (
            <p className="text-sm text-gray-500 mt-1">
              {trend.value > 0 ? '+' : ''}{trend.value} {trend.label}
            </p>
          )}
        </div>
        <div className="h-12 w-12 bg-primary-100 rounded-full flex items-center justify-center text-primary-600">
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
          <CardTitle>Active Transactions</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse space-y-3">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-16 bg-gray-200 rounded" />
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <span>Active Transactions</span>
          <Link to="/transactions" className="text-sm text-primary-600 hover:text-primary-700">
            View all
          </Link>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {transactions.slice(0, 5).map((transaction) => (
            <Link
              key={transaction.transaction_id}
              to={`/transactions/${transaction.transaction_id}`}
              className="block p-3 rounded-lg border border-gray-200 hover:border-primary-300 hover:bg-primary-50 transition-colors"
            >
              <div className="flex items-start justify-between">
                <div className="flex-1 min-w-0">
                  <p className="font-medium text-gray-900 truncate">
                    {transaction.property_address}
                  </p>
                  <p className="text-sm text-gray-500">
                    {transaction.property_city}, {transaction.property_state}
                  </p>
                </div>
                <Badge variant="info">{transaction.property_type}</Badge>
              </div>
              <div className="mt-2 flex items-center gap-4 text-xs text-gray-500">
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
          <CardTitle>Overdue Tasks</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse space-y-3">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-12 bg-gray-200 rounded" />
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
        <CardTitle className="flex items-center gap-2">
          <AlertCircle className="h-5 w-5 text-danger-500" />
          Overdue Tasks ({overdueTasks.length})
        </CardTitle>
      </CardHeader>
      <CardContent>
        {overdueTasks.length === 0 ? (
          <p className="text-sm text-gray-500 text-center py-4">No overdue tasks</p>
        ) : (
          <div className="space-y-2">
            {overdueTasks.slice(0, 5).map((task) => (
              <div
                key={task.id}
                className="p-3 rounded-lg border border-danger-200 bg-danger-50"
              >
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-900 text-sm">{task.title}</p>
                    <p className="text-xs text-danger-600 mt-1">
                      Due {formatRelativeTime(task.due_at)}
                    </p>
                  </div>
                  <Badge variant="danger">Overdue</Badge>
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
          <CardTitle>Upcoming Deadlines</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="animate-pulse space-y-3">
            {[1, 2, 3].map((i) => (
              <div key={i} className="h-12 bg-gray-200 rounded" />
            ))}
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Calendar className="h-5 w-5 text-warning-500" />
          Upcoming Deadlines (Next 7 Days)
        </CardTitle>
      </CardHeader>
      <CardContent>
        {tasks.length === 0 ? (
          <p className="text-sm text-gray-500 text-center py-4">No upcoming deadlines</p>
        ) : (
          <div className="space-y-2">
            {tasks.slice(0, 5).map((task) => (
              <div
                key={task.id}
                className="p-3 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors"
              >
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-gray-900 text-sm">{task.title}</p>
                    <p className="text-xs text-gray-500 mt-1">
                      Due {formatDate(task.due_at)}
                    </p>
                  </div>
                  <Badge variant="warning">Upcoming</Badge>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};
