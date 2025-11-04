import React, { useState, useMemo } from 'react';
import { Activity, User, FileText, Edit, Trash2, Plus, Download, Search, Calendar } from 'lucide-react';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { format, startOfDay, endOfDay, isWithinInterval } from 'date-fns';

export type AuditAction =
  | 'create'
  | 'update'
  | 'delete'
  | 'view'
  | 'export'
  | 'import'
  | 'login'
  | 'logout'
  | 'assign'
  | 'complete'
  | 'approve'
  | 'reject';

export type AuditEntity =
  | 'transaction'
  | 'task'
  | 'template'
  | 'document'
  | 'user'
  | 'comment'
  | 'report'
  | 'setting';

export interface AuditLogEntry {
  id: number;
  user_id: number;
  user_name: string;
  user_email: string;
  action: AuditAction;
  entity_type: AuditEntity;
  entity_id?: number;
  entity_name?: string;
  description: string;
  ip_address?: string;
  user_agent?: string;
  changes?: Record<string, { old: any; new: any }>;
  metadata?: Record<string, any>;
  created_at: string;
}

interface ActivityAuditLogProps {
  entries: AuditLogEntry[];
  onExport?: () => Promise<void>;
}

const ACTION_ICONS: Record<AuditAction, React.ElementType> = {
  create: Plus,
  update: Edit,
  delete: Trash2,
  view: Search,
  export: Download,
  import: Download,
  login: User,
  logout: User,
  assign: User,
  complete: FileText,
  approve: FileText,
  reject: Trash2,
};

const ACTION_COLORS: Record<AuditAction, string> = {
  create: 'bg-green-100 text-green-700',
  update: 'bg-blue-100 text-blue-700',
  delete: 'bg-red-100 text-red-700',
  view: 'bg-gray-100 text-gray-700',
  export: 'bg-purple-100 text-purple-700',
  import: 'bg-purple-100 text-purple-700',
  login: 'bg-indigo-100 text-indigo-700',
  logout: 'bg-gray-100 text-gray-700',
  assign: 'bg-amber-100 text-amber-700',
  complete: 'bg-green-100 text-green-700',
  approve: 'bg-green-100 text-green-700',
  reject: 'bg-red-100 text-red-700',
};

const ACTION_LABELS: Record<AuditAction, string> = {
  create: 'Created',
  update: 'Updated',
  delete: 'Deleted',
  view: 'Viewed',
  export: 'Exported',
  import: 'Imported',
  login: 'Logged In',
  logout: 'Logged Out',
  assign: 'Assigned',
  complete: 'Completed',
  approve: 'Approved',
  reject: 'Rejected',
};

const ENTITY_LABELS: Record<AuditEntity, string> = {
  transaction: 'Transaction',
  task: 'Task',
  template: 'Template',
  document: 'Document',
  user: 'User',
  comment: 'Comment',
  report: 'Report',
  setting: 'Setting',
};

export const ActivityAuditLog: React.FC<ActivityAuditLogProps> = ({
  entries,
  onExport,
}) => {
  const [searchQuery, setSearchQuery] = useState('');
  const [filterAction, setFilterAction] = useState<AuditAction | 'all'>('all');
  const [filterEntity, setFilterEntity] = useState<AuditEntity | 'all'>('all');
  const [filterUser, setFilterUser] = useState('');
  const [dateRange, setDateRange] = useState({
    start: format(startOfDay(new Date()), 'yyyy-MM-dd'),
    end: format(endOfDay(new Date()), 'yyyy-MM-dd'),
  });
  const [expandedEntry, setExpandedEntry] = useState<number | null>(null);

  // Get unique users
  const uniqueUsers = useMemo(() => {
    const users = new Map<number, { id: number; name: string; email: string }>();
    entries.forEach(entry => {
      if (!users.has(entry.user_id)) {
        users.set(entry.user_id, {
          id: entry.user_id,
          name: entry.user_name,
          email: entry.user_email,
        });
      }
    });
    return Array.from(users.values());
  }, [entries]);

  // Filter entries
  const filteredEntries = useMemo(() => {
    let filtered = [...entries];

    // Text search
    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(entry =>
        entry.user_name?.toLowerCase().includes(query) ||
        entry.description?.toLowerCase().includes(query) ||
        entry.entity_name?.toLowerCase().includes(query)
      );
    }

    // Action filter
    if (filterAction !== 'all') {
      filtered = filtered.filter(entry => entry.action === filterAction);
    }

    // Entity filter
    if (filterEntity !== 'all') {
      filtered = filtered.filter(entry => entry.entity_type === filterEntity);
    }

    // User filter
    if (filterUser) {
      filtered = filtered.filter(entry => entry.user_id === parseInt(filterUser));
    }

    // Date range filter
    if (dateRange.start && dateRange.end) {
      const start = new Date(dateRange.start);
      const end = new Date(dateRange.end);
      filtered = filtered.filter(entry =>
        isWithinInterval(new Date(entry.created_at), { start, end })
      );
    }

    // Sort by date (newest first)
    return filtered.sort((a, b) =>
      new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    );
  }, [entries, searchQuery, filterAction, filterEntity, filterUser, dateRange]);

  // Group entries by date
  const groupedEntries = useMemo(() => {
    const groups = new Map<string, AuditLogEntry[]>();
    filteredEntries.forEach(entry => {
      const dateKey = format(new Date(entry.created_at), 'yyyy-MM-dd');
      if (!groups.has(dateKey)) {
        groups.set(dateKey, []);
      }
      groups.get(dateKey)?.push(entry);
    });
    return Array.from(groups.entries()).sort((a, b) => b[0].localeCompare(a[0]));
  }, [filteredEntries]);

  const handleToggleExpand = (entryId: number) => {
    setExpandedEntry(expandedEntry === entryId ? null : entryId);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-blue-100 rounded-lg">
            <Activity className="h-6 w-6 text-blue-600" />
          </div>
          <div>
            <h2 className="text-2xl font-bold text-gray-900">Activity Audit Log</h2>
            <p className="text-sm text-gray-600 mt-1">
              {filteredEntries.length} of {entries.length} activities
            </p>
          </div>
        </div>

        {onExport && (
          <Button variant="secondary" onClick={onExport}>
            <Download className="h-4 w-4 mr-2" />
            Export Log
          </Button>
        )}
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {/* Search */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Search
              </label>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search activities..."
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            {/* Action Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Action
              </label>
              <select
                value={filterAction}
                onChange={(e) => setFilterAction(e.target.value as AuditAction | 'all')}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">All Actions</option>
                {Object.keys(ACTION_LABELS).map(action => (
                  <option key={action} value={action}>
                    {ACTION_LABELS[action as AuditAction]}
                  </option>
                ))}
              </select>
            </div>

            {/* Entity Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Entity Type
              </label>
              <select
                value={filterEntity}
                onChange={(e) => setFilterEntity(e.target.value as AuditEntity | 'all')}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">All Types</option>
                {Object.keys(ENTITY_LABELS).map(entity => (
                  <option key={entity} value={entity}>
                    {ENTITY_LABELS[entity as AuditEntity]}
                  </option>
                ))}
              </select>
            </div>

            {/* User Filter */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                User
              </label>
              <select
                value={filterUser}
                onChange={(e) => setFilterUser(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Users</option>
                {uniqueUsers.map(user => (
                  <option key={user.id} value={user.id}>
                    {user.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          {/* Date Range */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Start Date
              </label>
              <input
                type="date"
                value={dateRange.start}
                onChange={(e) => setDateRange({ ...dateRange, start: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                End Date
              </label>
              <input
                type="date"
                value={dateRange.end}
                onChange={(e) => setDateRange({ ...dateRange, end: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Activity Timeline */}
      <div className="space-y-6">
        {groupedEntries.length === 0 ? (
          <Card>
            <CardContent className="p-12 text-center">
              <Activity className="h-16 w-16 mx-auto text-gray-300 mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">No activities found</h3>
              <p className="text-sm text-gray-500">
                Try adjusting your filters or search query
              </p>
            </CardContent>
          </Card>
        ) : (
          groupedEntries.map(([date, dateEntries]) => (
            <div key={date}>
              {/* Date Header */}
              <div className="flex items-center gap-3 mb-4">
                <Calendar className="h-5 w-5 text-gray-400" />
                <h3 className="text-lg font-semibold text-gray-900">
                  {format(new Date(date), 'EEEE, MMMM d, yyyy')}
                </h3>
                <span className="text-sm text-gray-500">
                  ({dateEntries.length} {dateEntries.length === 1 ? 'activity' : 'activities'})
                </span>
              </div>

              {/* Entries */}
              <div className="space-y-3">
                {dateEntries.map((entry) => {
                  const Icon = ACTION_ICONS[entry.action];
                  const color = ACTION_COLORS[entry.action];
                  const isExpanded = expandedEntry === entry.id;

                  return (
                    <Card key={entry.id} className="hover:shadow-md transition-shadow">
                      <CardContent className="p-4">
                        <div className="flex items-start gap-4">
                          {/* Icon */}
                          <div className={`p-2 rounded-lg ${color} flex-shrink-0`}>
                            <Icon className="h-5 w-5" />
                          </div>

                          {/* Content */}
                          <div className="flex-1 min-w-0">
                            <div className="flex items-start justify-between gap-2 mb-2">
                              <div className="flex-1">
                                <div className="flex items-center gap-2 flex-wrap">
                                  <span className="font-medium text-gray-900">
                                    {entry.user_name}
                                  </span>
                                  <span className={`px-2 py-0.5 text-xs font-medium rounded ${color}`}>
                                    {ACTION_LABELS[entry.action]}
                                  </span>
                                  <span className="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 rounded">
                                    {ENTITY_LABELS[entry.entity_type]}
                                  </span>
                                </div>
                                <p className="text-sm text-gray-700 mt-1">
                                  {entry.description}
                                </p>
                                {entry.entity_name && (
                                  <p className="text-sm text-gray-600 mt-1">
                                    <span className="font-medium">Target:</span> {entry.entity_name}
                                  </p>
                                )}
                              </div>
                              <span className="text-xs text-gray-500 whitespace-nowrap">
                                {format(new Date(entry.created_at), 'h:mm a')}
                              </span>
                            </div>

                            {/* Expandable details */}
                            {(entry.changes || entry.metadata || entry.ip_address) && (
                              <button
                                onClick={() => handleToggleExpand(entry.id)}
                                className="text-sm text-blue-600 hover:text-blue-700 font-medium"
                              >
                                {isExpanded ? 'Hide' : 'Show'} details
                              </button>
                            )}

                            {isExpanded && (
                              <div className="mt-3 p-3 bg-gray-50 rounded-lg space-y-2">
                                {entry.changes && Object.keys(entry.changes).length > 0 && (
                                  <div>
                                    <h5 className="text-xs font-semibold text-gray-700 mb-2">Changes:</h5>
                                    {Object.entries(entry.changes).map(([field, change]) => (
                                      <div key={field} className="text-xs text-gray-600 mb-1">
                                        <span className="font-medium">{field}:</span>{' '}
                                        <span className="line-through text-red-600">{String(change.old)}</span>
                                        {' → '}
                                        <span className="text-green-600">{String(change.new)}</span>
                                      </div>
                                    ))}
                                  </div>
                                )}

                                {entry.ip_address && (
                                  <div className="text-xs text-gray-600">
                                    <span className="font-medium">IP Address:</span> {entry.ip_address}
                                  </div>
                                )}

                                {entry.metadata && Object.keys(entry.metadata).length > 0 && (
                                  <div>
                                    <h5 className="text-xs font-semibold text-gray-700 mb-2">Metadata:</h5>
                                    <pre className="text-xs text-gray-600 overflow-auto">
                                      {JSON.stringify(entry.metadata, null, 2)}
                                    </pre>
                                  </div>
                                )}
                              </div>
                            )}
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  );
                })}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};
