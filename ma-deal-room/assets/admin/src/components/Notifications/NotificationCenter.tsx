import React, { useState, useMemo } from 'react';
import { Bell, Check, CheckCheck, X, Settings, Trash2, Mail, AlertCircle, CheckCircle, Info, AlertTriangle } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card, CardContent } from '@/components/shared/Card';
import { formatDistanceToNow } from 'date-fns';
import toast from 'react-hot-toast';

export type NotificationType =
  | 'task_assigned'
  | 'task_completed'
  | 'task_overdue'
  | 'transaction_update'
  | 'document_uploaded'
  | 'comment_mention'
  | 'deadline_approaching'
  | 'system_alert';

export type NotificationPriority = 'low' | 'medium' | 'high' | 'urgent';

export interface Notification {
  id: number;
  type: NotificationType;
  priority: NotificationPriority;
  title: string;
  message: string;
  link?: string;
  is_read: boolean;
  created_at: string;
  actor_name?: string;
  transaction_id?: number;
  task_id?: number;
  metadata?: Record<string, any>;
}

interface NotificationCenterProps {
  notifications: Notification[];
  onMarkAsRead: (ids: number[]) => Promise<void>;
  onMarkAllAsRead: () => Promise<void>;
  onDelete: (ids: number[]) => Promise<void>;
  onNotificationClick?: (notification: Notification) => void;
}

const NOTIFICATION_ICONS: Record<NotificationType, React.ElementType> = {
  task_assigned: CheckCircle,
  task_completed: CheckCheck,
  task_overdue: AlertCircle,
  transaction_update: Info,
  document_uploaded: Mail,
  comment_mention: AlertCircle,
  deadline_approaching: AlertTriangle,
  system_alert: AlertCircle,
};

const NOTIFICATION_COLORS: Record<NotificationType, string> = {
  task_assigned: 'bg-blue-100 text-blue-600',
  task_completed: 'bg-green-100 text-green-600',
  task_overdue: 'bg-red-100 text-red-600',
  transaction_update: 'bg-purple-100 text-purple-600',
  document_uploaded: 'bg-indigo-100 text-indigo-600',
  comment_mention: 'bg-amber-100 text-amber-600',
  deadline_approaching: 'bg-orange-100 text-orange-600',
  system_alert: 'bg-gray-100 text-gray-600',
};

const PRIORITY_COLORS: Record<NotificationPriority, string> = {
  low: 'border-gray-200',
  medium: 'border-blue-300',
  high: 'border-amber-400',
  urgent: 'border-red-500',
};

export const NotificationCenter: React.FC<NotificationCenterProps> = ({
  notifications,
  onMarkAsRead,
  onMarkAllAsRead,
  onDelete,
  onNotificationClick,
}) => {
  const [filterType, setFilterType] = useState<'all' | 'unread' | 'read'>('all');
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [showSettings, setShowSettings] = useState(false);

  // Filter notifications
  const filteredNotifications = useMemo(() => {
    let filtered = [...notifications];

    if (filterType === 'unread') {
      filtered = filtered.filter(n => !n.is_read);
    } else if (filterType === 'read') {
      filtered = filtered.filter(n => n.is_read);
    }

    // Sort by date (newest first)
    return filtered.sort((a, b) =>
      new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    );
  }, [notifications, filterType]);

  // Unread count
  const unreadCount = useMemo(() => {
    return notifications.filter(n => !n.is_read).length;
  }, [notifications]);

  const handleToggleSelect = (id: number) => {
    setSelectedIds(prev => {
      if (prev.includes(id)) {
        return prev.filter(i => i !== id);
      } else {
        return [...prev, id];
      }
    });
  };

  const handleSelectAll = () => {
    if (selectedIds.length === filteredNotifications.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(filteredNotifications.map(n => n.id));
    }
  };

  const handleMarkSelectedAsRead = async () => {
    if (selectedIds.length === 0) {
      toast.error('No notifications selected');
      return;
    }

    try {
      await onMarkAsRead(selectedIds);
      setSelectedIds([]);
      toast.success(`Marked ${selectedIds.length} notification(s) as read`);
    } catch (error: any) {
      toast.error('Failed to mark notifications as read');
    }
  };

  const handleDeleteSelected = async () => {
    if (selectedIds.length === 0) {
      toast.error('No notifications selected');
      return;
    }

    if (!confirm(`Delete ${selectedIds.length} notification(s)?`)) {
      return;
    }

    try {
      await onDelete(selectedIds);
      setSelectedIds([]);
      toast.success(`Deleted ${selectedIds.length} notification(s)`);
    } catch (error: any) {
      toast.error('Failed to delete notifications');
    }
  };

  const handleNotificationClick = async (notification: Notification) => {
    // Mark as read if unread
    if (!notification.is_read) {
      await onMarkAsRead([notification.id]);
    }

    // Navigate to link if provided
    if (onNotificationClick) {
      onNotificationClick(notification);
    }
  };

  return (
    <div className="space-y-4">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="relative">
            <Bell className="h-6 w-6 text-gray-700" />
            {unreadCount > 0 && (
              <span className="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                {unreadCount > 9 ? '9+' : unreadCount}
              </span>
            )}
          </div>
          <div>
            <h2 className="text-xl font-bold text-gray-900">Notifications</h2>
            <p className="text-sm text-gray-600">
              {unreadCount} unread • {notifications.length} total
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="secondary"
            onClick={() => setShowSettings(!showSettings)}
          >
            <Settings className="h-4 w-4" />
          </Button>
          <Button
            size="sm"
            variant="primary"
            onClick={onMarkAllAsRead}
            disabled={unreadCount === 0}
          >
            <CheckCheck className="h-4 w-4 mr-2" />
            Mark All Read
          </Button>
        </div>
      </div>

      {/* Filter tabs */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2 bg-gray-100 p-1 rounded-lg">
          <button
            onClick={() => setFilterType('all')}
            className={`px-4 py-1.5 text-sm font-medium rounded-md transition-all ${
              filterType === 'all'
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            All ({notifications.length})
          </button>
          <button
            onClick={() => setFilterType('unread')}
            className={`px-4 py-1.5 text-sm font-medium rounded-md transition-all ${
              filterType === 'unread'
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            Unread ({unreadCount})
          </button>
          <button
            onClick={() => setFilterType('read')}
            className={`px-4 py-1.5 text-sm font-medium rounded-md transition-all ${
              filterType === 'read'
                ? 'bg-white text-gray-900 shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
            }`}
          >
            Read ({notifications.length - unreadCount})
          </button>
        </div>

        {/* Bulk actions */}
        {selectedIds.length > 0 && (
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">
              {selectedIds.length} selected
            </span>
            <Button size="sm" variant="secondary" onClick={handleMarkSelectedAsRead}>
              <Check className="h-3 w-3 mr-1" />
              Mark Read
            </Button>
            <Button size="sm" variant="danger" onClick={handleDeleteSelected}>
              <Trash2 className="h-3 w-3 mr-1" />
              Delete
            </Button>
          </div>
        )}
      </div>

      {/* Select All */}
      {filteredNotifications.length > 0 && (
        <div className="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-lg">
          <input
            type="checkbox"
            checked={selectedIds.length === filteredNotifications.length}
            onChange={handleSelectAll}
            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <span className="text-sm text-gray-700 font-medium">
            Select all {filteredNotifications.length} notification(s)
          </span>
        </div>
      )}

      {/* Notifications list */}
      <div className="space-y-2">
        {filteredNotifications.length === 0 ? (
          <Card>
            <CardContent className="p-12 text-center">
              <Bell className="h-16 w-16 mx-auto text-gray-300 mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                {filterType === 'unread' ? 'No unread notifications' : 'No notifications'}
              </h3>
              <p className="text-sm text-gray-500">
                {filterType === 'unread'
                  ? "You're all caught up!"
                  : 'Notifications will appear here'}
              </p>
            </CardContent>
          </Card>
        ) : (
          filteredNotifications.map((notification) => {
            const Icon = NOTIFICATION_ICONS[notification.type];
            const iconColor = NOTIFICATION_COLORS[notification.type];
            const borderColor = PRIORITY_COLORS[notification.priority];
            const isSelected = selectedIds.includes(notification.id);

            return (
              <Card
                key={notification.id}
                className={`transition-all cursor-pointer border-l-4 ${borderColor} ${
                  !notification.is_read ? 'bg-blue-50' : 'bg-white'
                } ${isSelected ? 'ring-2 ring-blue-500' : ''}`}
              >
                <CardContent className="p-4">
                  <div className="flex items-start gap-3">
                    {/* Checkbox */}
                    <input
                      type="checkbox"
                      checked={isSelected}
                      onChange={() => handleToggleSelect(notification.id)}
                      onClick={(e) => e.stopPropagation()}
                      className="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />

                    {/* Icon */}
                    <div className={`p-2 rounded-lg ${iconColor} flex-shrink-0`}>
                      <Icon className="h-5 w-5" />
                    </div>

                    {/* Content */}
                    <div
                      className="flex-1 min-w-0"
                      onClick={() => handleNotificationClick(notification)}
                    >
                      <div className="flex items-start justify-between gap-2 mb-1">
                        <h4 className={`text-sm font-medium ${
                          !notification.is_read ? 'text-gray-900' : 'text-gray-700'
                        }`}>
                          {notification.title}
                        </h4>
                        {!notification.is_read && (
                          <span className="h-2 w-2 bg-blue-600 rounded-full flex-shrink-0 mt-1.5" />
                        )}
                      </div>

                      <p className={`text-sm ${
                        !notification.is_read ? 'text-gray-700' : 'text-gray-600'
                      }`}>
                        {notification.message}
                      </p>

                      {notification.actor_name && (
                        <p className="text-xs text-gray-500 mt-1">
                          by {notification.actor_name}
                        </p>
                      )}

                      <div className="flex items-center gap-3 mt-2">
                        <span className="text-xs text-gray-500">
                          {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
                        </span>
                        {notification.priority === 'urgent' && (
                          <span className="text-xs font-semibold text-red-600 bg-red-100 px-2 py-0.5 rounded">
                            URGENT
                          </span>
                        )}
                      </div>
                    </div>

                    {/* Quick actions */}
                    <div className="flex items-center gap-1 flex-shrink-0">
                      {!notification.is_read && (
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            onMarkAsRead([notification.id]);
                          }}
                          className="p-1.5 text-blue-600 hover:bg-blue-100 rounded transition-colors"
                          title="Mark as read"
                        >
                          <Check className="h-4 w-4" />
                        </button>
                      )}
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          onDelete([notification.id]);
                        }}
                        className="p-1.5 text-red-600 hover:bg-red-100 rounded transition-colors"
                        title="Delete"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            );
          })
        )}
      </div>

      {/* Settings Panel */}
      {showSettings && (
        <Card>
          <CardContent className="p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold text-gray-900">Notification Settings</h3>
              <button
                onClick={() => setShowSettings(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium text-gray-900">Email Notifications</p>
                  <p className="text-sm text-gray-600">Receive notifications via email</p>
                </div>
                <input
                  type="checkbox"
                  defaultChecked
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium text-gray-900">Task Assignments</p>
                  <p className="text-sm text-gray-600">When a task is assigned to you</p>
                </div>
                <input
                  type="checkbox"
                  defaultChecked
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium text-gray-900">Deadline Reminders</p>
                  <p className="text-sm text-gray-600">Approaching task deadlines</p>
                </div>
                <input
                  type="checkbox"
                  defaultChecked
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
              </div>

              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium text-gray-900">Comments & Mentions</p>
                  <p className="text-sm text-gray-600">When someone mentions you</p>
                </div>
                <input
                  type="checkbox"
                  defaultChecked
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
              </div>

              <div className="pt-4 border-t border-gray-200">
                <Button variant="primary" className="w-full">
                  Save Preferences
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};
