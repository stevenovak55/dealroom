import React, { useMemo } from 'react';
import { TrendingUp, TrendingDown, AlertTriangle, CheckCircle, Clock, DollarSign, Calendar, Activity } from 'lucide-react';
import { Card, CardContent } from '@/components/shared/Card';
import type { Transaction } from '@/api/types';
import { differenceInDays, format, startOfMonth, endOfMonth, isWithinInterval } from 'date-fns';

interface DashboardMetricsProps {
  transactions: Transaction[];
}

interface MetricCardProps {
  title: string;
  value: string | number;
  change?: number;
  changeLabel?: string;
  icon: React.ElementType;
  iconColor: string;
  trend?: 'up' | 'down' | 'neutral';
}

const MetricCard: React.FC<MetricCardProps> = ({
  title,
  value,
  change,
  changeLabel,
  icon: Icon,
  iconColor,
  trend,
}) => {
  return (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div className="flex-1">
            <p className="text-sm font-medium text-gray-600 mb-1">{title}</p>
            <p className="text-3xl font-bold text-gray-900">{value}</p>
            {change !== undefined && (
              <div className="flex items-center gap-2 mt-2">
                {trend === 'up' && (
                  <div className="flex items-center text-green-600">
                    <TrendingUp className="h-4 w-4 mr-1" />
                    <span className="text-sm font-medium">+{change}%</span>
                  </div>
                )}
                {trend === 'down' && (
                  <div className="flex items-center text-red-600">
                    <TrendingDown className="h-4 w-4 mr-1" />
                    <span className="text-sm font-medium">{change}%</span>
                  </div>
                )}
                {changeLabel && (
                  <span className="text-sm text-gray-500">{changeLabel}</span>
                )}
              </div>
            )}
          </div>
          <div className={`p-4 rounded-lg ${iconColor}`}>
            <Icon className="h-8 w-8 text-white" />
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export const DashboardMetrics: React.FC<DashboardMetricsProps> = ({ transactions }) => {
  const metrics = useMemo(() => {
    const now = new Date();
    const monthStart = startOfMonth(now);
    const monthEnd = endOfMonth(now);

    // Current month transactions
    const thisMonthTransactions = transactions.filter(t => {
      const createdDate = new Date(t.created_at);
      return isWithinInterval(createdDate, { start: monthStart, end: monthEnd });
    });

    // Active transactions
    const activeCount = transactions.filter(
      t => ['listing_active', 'under_agreement'].includes(t.status)
    ).length;

    // Closed this month
    const closedThisMonth = transactions.filter(
      t => t.status === 'closed' && t.actual_closing_date &&
      isWithinInterval(new Date(t.actual_closing_date), { start: monthStart, end: monthEnd })
    ).length;

    // Total volume
    const totalVolume = transactions.reduce((sum, t) => {
      return sum + (t.sale_price || 0);
    }, 0);

    // This month volume
    const monthVolume = thisMonthTransactions.reduce((sum, t) => {
      return sum + (t.sale_price || 0);
    }, 0);

    // Task statistics
    const totalTasks = transactions.reduce((sum, t) => sum + (t.task_summary?.total || 0), 0);
    const completedTasks = transactions.reduce((sum, t) => sum + (t.task_summary?.completed || 0), 0);
    const overdueTasks = transactions.reduce((sum, t) => sum + (t.task_summary?.overdue || 0), 0);
    const completionRate = totalTasks > 0 ? ((completedTasks / totalTasks) * 100).toFixed(1) : '0';

    // Average days to close
    const closedTransactions = transactions.filter(t => t.status === 'closed' && t.actual_closing_date);
    const avgDaysToClose = closedTransactions.length > 0
      ? Math.round(
          closedTransactions.reduce((sum, t) => {
            const created = new Date(t.created_at);
            const closed = new Date(t.actual_closing_date!);
            return sum + differenceInDays(closed, created);
          }, 0) / closedTransactions.length
        )
      : 0;

    // Upcoming closings (next 30 days)
    const upcomingClosings = transactions.filter(t => {
      if (!t.closing_date) return false;
      const closingDate = new Date(t.closing_date);
      const daysUntil = differenceInDays(closingDate, now);
      return daysUntil >= 0 && daysUntil <= 30;
    }).length;

    return {
      activeCount,
      closedThisMonth,
      totalVolume,
      monthVolume,
      completionRate,
      overdueTasks,
      avgDaysToClose,
      upcomingClosings,
      thisMonthCount: thisMonthTransactions.length,
    };
  }, [transactions]);

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <MetricCard
        title="Active Transactions"
        value={metrics.activeCount}
        change={12}
        changeLabel="vs last month"
        icon={Activity}
        iconColor="bg-blue-500"
        trend="up"
      />

      <MetricCard
        title="Closed This Month"
        value={metrics.closedThisMonth}
        change={8}
        changeLabel="vs last month"
        icon={CheckCircle}
        iconColor="bg-green-500"
        trend="up"
      />

      <MetricCard
        title="Total Volume"
        value={`$${(metrics.totalVolume / 1000000).toFixed(1)}M`}
        change={15}
        changeLabel="vs last month"
        icon={DollarSign}
        iconColor="bg-emerald-500"
        trend="up"
      />

      <MetricCard
        title="Task Completion"
        value={`${metrics.completionRate}%`}
        icon={CheckCircle}
        iconColor="bg-purple-500"
      />

      <MetricCard
        title="Overdue Tasks"
        value={metrics.overdueTasks}
        icon={AlertTriangle}
        iconColor={metrics.overdueTasks > 0 ? 'bg-red-500' : 'bg-gray-400'}
      />

      <MetricCard
        title="Avg. Days to Close"
        value={metrics.avgDaysToClose}
        change={-5}
        changeLabel="improvement"
        icon={Clock}
        iconColor="bg-amber-500"
        trend="up"
      />

      <MetricCard
        title="Closing Next 30 Days"
        value={metrics.upcomingClosings}
        icon={Calendar}
        iconColor="bg-indigo-500"
      />

      <MetricCard
        title="New This Month"
        value={metrics.thisMonthCount}
        change={20}
        changeLabel="vs last month"
        icon={TrendingUp}
        iconColor="bg-cyan-500"
        trend="up"
      />
    </div>
  );
};

// Activity Feed Component
interface ActivityFeedProps {
  transactions: Transaction[];
  limit?: number;
}

export const ActivityFeed: React.FC<ActivityFeedProps> = ({ transactions, limit = 10 }) => {
  const recentActivity = useMemo(() => {
    const activities: Array<{
      id: string;
      type: 'transaction' | 'task' | 'closing';
      title: string;
      description: string;
      timestamp: Date;
      icon: React.ElementType;
      iconColor: string;
    }> = [];

    // Recent transactions
    transactions
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      .slice(0, limit)
      .forEach(t => {
        activities.push({
          id: `trans-${t.transaction_id}`,
          type: 'transaction',
          title: 'New Transaction',
          description: `${t.property_address}, ${t.property_city}`,
          timestamp: new Date(t.created_at),
          icon: Activity,
          iconColor: 'bg-blue-100 text-blue-600',
        });
      });

    // Upcoming closings
    transactions
      .filter(t => t.closing_date)
      .sort((a, b) => {
        const dateA = new Date(a.closing_date!).getTime();
        const dateB = new Date(b.closing_date!).getTime();
        return dateA - dateB;
      })
      .slice(0, 5)
      .forEach(t => {
        const daysUntil = differenceInDays(new Date(t.closing_date!), new Date());
        if (daysUntil >= 0 && daysUntil <= 30) {
          activities.push({
            id: `closing-${t.transaction_id}`,
            type: 'closing',
            title: `Closing in ${daysUntil} days`,
            description: `${t.property_address}`,
            timestamp: new Date(t.closing_date!),
            icon: Calendar,
            iconColor: 'bg-green-100 text-green-600',
          });
        }
      });

    return activities
      .sort((a, b) => b.timestamp.getTime() - a.timestamp.getTime())
      .slice(0, limit);
  }, [transactions, limit]);

  return (
    <Card>
      <CardContent className="p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
        <div className="space-y-4">
          {recentActivity.length === 0 ? (
            <p className="text-sm text-gray-500 text-center py-8">No recent activity</p>
          ) : (
            recentActivity.map(activity => {
              const Icon = activity.icon;
              return (
                <div key={activity.id} className="flex items-start gap-3">
                  <div className={`p-2 rounded-lg ${activity.iconColor} flex-shrink-0`}>
                    <Icon className="h-4 w-4" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900">{activity.title}</p>
                    <p className="text-sm text-gray-600 truncate">{activity.description}</p>
                    <p className="text-xs text-gray-500 mt-1">
                      {format(activity.timestamp, 'MMM d, yyyy h:mm a')}
                    </p>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </CardContent>
    </Card>
  );
};

// Task Performance Chart
interface TaskPerformanceProps {
  transactions: Transaction[];
}

export const TaskPerformance: React.FC<TaskPerformanceProps> = ({ transactions }) => {
  const performanceData = useMemo(() => {
    const total = transactions.reduce((sum, t) => sum + (t.task_summary?.total || 0), 0);
    const completed = transactions.reduce((sum, t) => sum + (t.task_summary?.completed || 0), 0);
    const pending = transactions.reduce((sum, t) => sum + (t.task_summary?.pending || 0), 0);
    const overdue = transactions.reduce((sum, t) => sum + (t.task_summary?.overdue || 0), 0);

    return { total, completed, pending, overdue };
  }, [transactions]);

  const completionPercentage = performanceData.total > 0
    ? (performanceData.completed / performanceData.total) * 100
    : 0;

  return (
    <Card>
      <CardContent className="p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Task Performance</h3>

        {/* Progress Bar */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium text-gray-700">Overall Completion</span>
            <span className="text-sm font-bold text-gray-900">{completionPercentage.toFixed(1)}%</span>
          </div>
          <div className="w-full bg-gray-200 rounded-full h-3">
            <div
              className="bg-green-500 h-3 rounded-full transition-all"
              style={{ width: `${completionPercentage}%` }}
            />
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-2 gap-4">
          <div className="p-3 bg-green-50 rounded-lg">
            <p className="text-xs text-green-700 font-medium mb-1">Completed</p>
            <p className="text-2xl font-bold text-green-900">{performanceData.completed}</p>
          </div>
          <div className="p-3 bg-blue-50 rounded-lg">
            <p className="text-xs text-blue-700 font-medium mb-1">Pending</p>
            <p className="text-2xl font-bold text-blue-900">{performanceData.pending}</p>
          </div>
          <div className="p-3 bg-red-50 rounded-lg">
            <p className="text-xs text-red-700 font-medium mb-1">Overdue</p>
            <p className="text-2xl font-bold text-red-900">{performanceData.overdue}</p>
          </div>
          <div className="p-3 bg-gray-50 rounded-lg">
            <p className="text-xs text-gray-700 font-medium mb-1">Total</p>
            <p className="text-2xl font-bold text-gray-900">{performanceData.total}</p>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};
