import { useMemo } from 'react';
import { Library, CheckCircle, AlertCircle, TrendingUp } from 'lucide-react';
import { Card, CardContent } from '@/components/shared/Card';
import type { TaskDefinitionGrouped } from '@/api/types';

interface TaskLibraryStatsProps {
  taskGroups: Array<TaskDefinitionGrouped & { category_key: string }>;
}

export const TaskLibraryStats = ({ taskGroups }: TaskLibraryStatsProps) => {
  const stats = useMemo(() => {
    const allTasks = taskGroups.flatMap(group => group.tasks || []);

    return {
      total: allTasks.length,
      categories: taskGroups.length,
      milestones: allTasks.filter(t => t.is_milestone).length,
      required: allTasks.filter(t => t.is_required).length,
      systemTasks: allTasks.filter(t => t.is_system).length,
      customTasks: allTasks.filter(t => !t.is_system).length,
      highPriority: allTasks.filter(t => t.priority === 'high' || t.priority === 'urgent').length,
    };
  }, [taskGroups]);

  const statCards = [
    {
      label: 'Total Tasks',
      value: stats.total,
      icon: Library,
      color: 'text-blue-600',
      bgColor: 'bg-blue-50',
      description: `${stats.systemTasks} system, ${stats.customTasks} custom`,
    },
    {
      label: 'Categories',
      value: stats.categories,
      icon: TrendingUp,
      color: 'text-purple-600',
      bgColor: 'bg-purple-50',
      description: 'Task categories available',
    },
    {
      label: 'Milestones',
      value: stats.milestones,
      icon: CheckCircle,
      color: 'text-green-600',
      bgColor: 'bg-green-50',
      description: 'Critical milestone tasks',
    },
    {
      label: 'High Priority',
      value: stats.highPriority,
      icon: AlertCircle,
      color: 'text-orange-600',
      bgColor: 'bg-orange-50',
      description: 'High/urgent priority tasks',
    },
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      {statCards.map((stat, index) => {
        const Icon = stat.icon;
        return (
          <Card key={index} className="hover:shadow-md transition-shadow">
            <CardContent className="p-4">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <p className="text-sm font-medium text-gray-600 mb-1">{stat.label}</p>
                  <p className="text-3xl font-bold text-gray-900 mb-1">{stat.value}</p>
                  <p className="text-xs text-gray-500">{stat.description}</p>
                </div>
                <div className={`p-3 rounded-lg ${stat.bgColor}`}>
                  <Icon className={`h-6 w-6 ${stat.color}`} />
                </div>
              </div>
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
};
