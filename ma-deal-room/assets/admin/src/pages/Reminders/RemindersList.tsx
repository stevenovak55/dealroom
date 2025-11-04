import { Bell, Mail, Smartphone, CheckCircle, AlertCircle } from 'lucide-react';
import { useGetUpcomingReminders } from '@/api/queries/useReminders';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Badge } from '@/components/shared/Badge';
import { PageLoader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { formatDateTime } from '@/utils/formatDate';

export const RemindersList = () => {
  const { data, isLoading } = useGetUpcomingReminders({ days: 30 });

  const reminders = data?.data || [];

  const getChannelIcon = (channel: string) => {
    if (channel === 'email') return <Mail className="h-4 w-4" />;
    if (channel === 'sms') return <Smartphone className="h-4 w-4" />;
    return <Bell className="h-4 w-4" />;
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, 'success' | 'warning' | 'danger' | 'default'> = {
      sent: 'success',
      pending: 'warning',
      failed: 'danger',
      cancelled: 'default',
    };
    return <Badge variant={variants[status] || 'default'}>{status}</Badge>;
  };

  if (isLoading) {
    return <PageLoader />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Reminders</h1>
        <p className="text-sm text-gray-500 mt-1">
          Upcoming reminders for tasks and deadlines
        </p>
      </div>

      {/* Reminders list */}
      {reminders.length === 0 ? (
        <Card>
          <EmptyState
            icon={Bell}
            title="No upcoming reminders"
            description="Reminders will appear here when tasks approach their due dates"
          />
        </Card>
      ) : (
        <Card>
          <CardHeader>
            <CardTitle>Upcoming Reminders (Next 30 Days)</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {reminders.map((reminder) => (
                <div
                  key={reminder.reminder_id}
                  className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        {getChannelIcon(reminder.channel)}
                        <h4 className="font-medium text-gray-900">
                          {reminder.task_title || 'Task reminder'}
                        </h4>
                      </div>
                      <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                          <p className="text-gray-500">Scheduled</p>
                          <p className="text-gray-900">{formatDateTime(reminder.scheduled_at)}</p>
                        </div>
                        <div>
                          <p className="text-gray-500">Recipient</p>
                          <p className="text-gray-900">
                            {reminder.recipient_email || reminder.recipient_phone || 'N/A'}
                          </p>
                        </div>
                        <div>
                          <p className="text-gray-500">Channel</p>
                          <p className="text-gray-900 capitalize">{reminder.channel}</p>
                        </div>
                        <div>
                          <p className="text-gray-500">Recipient Type</p>
                          <p className="text-gray-900 capitalize">{reminder.recipient_type}</p>
                        </div>
                      </div>
                      {reminder.sent_at && (
                        <div className="mt-2 flex items-center gap-2 text-sm text-success-600">
                          <CheckCircle className="h-4 w-4" />
                          <span>Sent {formatDateTime(reminder.sent_at)}</span>
                        </div>
                      )}
                      {reminder.failure_reason && (
                        <div className="mt-2 flex items-center gap-2 text-sm text-danger-600">
                          <AlertCircle className="h-4 w-4" />
                          <span>{reminder.failure_reason}</span>
                        </div>
                      )}
                    </div>
                    {getStatusBadge(reminder.status)}
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
