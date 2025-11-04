import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, TrendingUp, Clock, CheckCircle, AlertTriangle, BarChart3 } from 'lucide-react';
import { useGetTemplate, useGetTemplateAnalytics } from '@/api/queries/useTemplates';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { TASK_PHASES } from '@/api/types';
import type { TaskPhase } from '@/api/types';

export const TemplateAnalytics: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const templateId = id ? parseInt(id) : 0;

  const { data: template, isLoading: templateLoading } = useGetTemplate(templateId);
  const { data: analytics, isLoading: analyticsLoading } = useGetTemplateAnalytics(templateId);

  if (templateLoading || analyticsLoading) {
    return <PageLoader />;
  }

  if (!template || !analytics) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900">Analytics not available</h2>
          <Button onClick={() => navigate('/templates')} className="mt-4">
            Back to Templates
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => navigate('/templates')}
          >
            <ArrowLeft className="w-4 h-4 mr-2" />
            Back
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Template Analytics</h1>
            <p className="text-sm text-gray-600 mt-1">{template.name}</p>
          </div>
        </div>
      </div>

      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Total Usage</p>
                <p className="text-2xl font-bold text-gray-900 mt-2">
                  {analytics.total_usage}
                </p>
                <p className="text-xs text-gray-500 mt-1">transactions created</p>
              </div>
              <div className="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <TrendingUp className="h-6 w-6 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Active Transactions</p>
                <p className="text-2xl font-bold text-gray-900 mt-2">
                  {analytics.active_transactions}
                </p>
                <p className="text-xs text-gray-500 mt-1">currently in progress</p>
              </div>
              <div className="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center">
                <BarChart3 className="h-6 w-6 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Avg. Completion Time</p>
                <p className="text-2xl font-bold text-gray-900 mt-2">
                  {analytics.avg_completion_time_days}
                </p>
                <p className="text-xs text-gray-500 mt-1">days</p>
              </div>
              <div className="h-12 w-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <Clock className="h-6 w-6 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Completion Rate</p>
                <p className="text-2xl font-bold text-gray-900 mt-2">
                  {(analytics.completion_rate * 100).toFixed(1)}%
                </p>
                <p className="text-xs text-gray-500 mt-1">of all transactions</p>
              </div>
              <div className="h-12 w-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                <CheckCircle className="h-6 w-6 text-emerald-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Phase Breakdown */}
      <Card>
        <CardHeader>
          <CardTitle>Performance by Phase</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {Object.entries(analytics.phase_breakdown).map(([phase, stats]) => {
              const phaseInfo = TASK_PHASES[phase as TaskPhase];
              if (!phaseInfo || stats.task_count === 0) return null;

              return (
                <div key={phase} className="border-l-4 border-blue-500 pl-4 py-2">
                  <div className="flex items-center justify-between mb-2">
                    <div>
                      <h4 className="font-medium text-gray-900">{phaseInfo.label}</h4>
                      <p className="text-sm text-gray-600">{phaseInfo.description}</p>
                    </div>
                    <div className="text-right">
                      <p className="text-sm font-medium text-gray-900">
                        {stats.task_count} tasks
                      </p>
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4 mt-2">
                    <div className="bg-gray-50 rounded p-2">
                      <p className="text-xs text-gray-600">Avg. Duration</p>
                      <p className="text-sm font-semibold text-gray-900">
                        {stats.avg_duration_days} days
                      </p>
                    </div>
                    <div className="bg-gray-50 rounded p-2">
                      <p className="text-xs text-gray-600">Completion Rate</p>
                      <p className="text-sm font-semibold text-gray-900">
                        {(stats.completion_rate * 100).toFixed(1)}%
                      </p>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Most Delayed Tasks */}
      {analytics.most_delayed_tasks.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <AlertTriangle className="h-5 w-5 text-amber-600" />
              Tasks with Frequent Delays
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {analytics.most_delayed_tasks.map((task) => (
                <div
                  key={task.task_key}
                  className="flex items-center justify-between p-3 bg-amber-50 border border-amber-200 rounded-lg"
                >
                  <div>
                    <h4 className="font-medium text-gray-900">{task.title}</h4>
                    <p className="text-sm text-gray-600">{task.task_key}</p>
                  </div>
                  <div className="text-right">
                    <p className="text-lg font-semibold text-amber-600">
                      +{task.avg_delay_days} days
                    </p>
                    <p className="text-xs text-gray-600">avg. delay</p>
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};
