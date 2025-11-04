import { useMemo } from 'react';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell } from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { formatDate, isOverdue } from '@/utils/formatDate';
import type { Task } from '@/api/types';

interface TimelineViewProps {
  tasks: Task[];
}

export const TimelineView = ({ tasks }: TimelineViewProps) => {
  // Prepare data for chart
  const chartData = useMemo(() => {
    return tasks
      .filter((task) => task.due_at)
      .map((task) => ({
        name: task.title,
        date: new Date(task.due_at!).getTime(),
        status: task.status,
        isOverdue: isOverdue(task.due_at),
      }))
      .sort((a, b) => a.date - b.date);
  }, [tasks]);

  const getBarColor = (task: typeof chartData[0]) => {
    if (task.status === 'completed') return '#22c55e';
    if (task.isOverdue) return '#ef4444';
    return '#3b82f6';
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Timeline</CardTitle>
      </CardHeader>
      <CardContent>
        {chartData.length === 0 ? (
          <p className="text-center text-gray-500 py-8">No tasks with due dates</p>
        ) : (
          <ResponsiveContainer width="100%" height={400}>
            <BarChart data={chartData} layout="vertical">
              <XAxis type="number" hide />
              <YAxis
                dataKey="name"
                type="category"
                width={200}
                tick={{ fontSize: 12 }}
              />
              <Tooltip
                content={({ payload }) => {
                  if (!payload || !payload[0]) return null;
                  const data = payload[0].payload;
                  return (
                    <div className="bg-white p-3 border border-gray-200 rounded shadow-lg">
                      <p className="font-medium text-gray-900">{data.name}</p>
                      <p className="text-sm text-gray-600">
                        Due: {formatDate(new Date(data.date).toISOString())}
                      </p>
                      <p className="text-sm text-gray-600 capitalize">
                        Status: {data.status}
                      </p>
                    </div>
                  );
                }}
              />
              <Bar dataKey="date" radius={[0, 4, 4, 0]}>
                {chartData.map((entry, index) => (
                  <Cell key={`cell-${index}`} fill={getBarColor(entry)} />
                ))}
              </Bar>
            </BarChart>
          </ResponsiveContainer>
        )}
      </CardContent>
    </Card>
  );
};
