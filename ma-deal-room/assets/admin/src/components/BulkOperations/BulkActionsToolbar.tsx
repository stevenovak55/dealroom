import React, { useState } from 'react';
import { CheckSquare, X, Trash2, Archive, Tag, UserPlus, Calendar, FileText, Download, Mail, AlertCircle } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card } from '@/components/shared/Card';
import toast from 'react-hot-toast';
import type { Transaction, Task } from '@/api/types';

interface BulkActionsToolbarProps {
  selectedItems: number[];
  itemType: 'transactions' | 'tasks';
  onClearSelection: () => void;
  onBulkAction: (action: BulkAction, params?: any) => Promise<void>;
  allItems?: Transaction[] | Task[];
}

export type BulkAction =
  | 'delete'
  | 'archive'
  | 'export'
  | 'update_status'
  | 'assign_agent'
  | 'add_tags'
  | 'bulk_email'
  | 'update_date'
  | 'generate_report';

interface BulkActionOption {
  action: BulkAction;
  label: string;
  icon: React.ElementType;
  description: string;
  color: string;
  requiresParams?: boolean;
  confirmMessage?: string;
  dangerous?: boolean;
}

const TRANSACTION_ACTIONS: BulkActionOption[] = [
  {
    action: 'update_status',
    label: 'Update Status',
    icon: CheckSquare,
    description: 'Change status for selected transactions',
    color: 'bg-blue-500 hover:bg-blue-600',
    requiresParams: true,
  },
  {
    action: 'assign_agent',
    label: 'Assign Agent',
    icon: UserPlus,
    description: 'Assign agent to selected transactions',
    color: 'bg-purple-500 hover:bg-purple-600',
    requiresParams: true,
  },
  {
    action: 'add_tags',
    label: 'Add Tags',
    icon: Tag,
    description: 'Add tags to selected transactions',
    color: 'bg-green-500 hover:bg-green-600',
    requiresParams: true,
  },
  {
    action: 'update_date',
    label: 'Update Date',
    icon: Calendar,
    description: 'Set closing date for selected transactions',
    color: 'bg-amber-500 hover:bg-amber-600',
    requiresParams: true,
  },
  {
    action: 'bulk_email',
    label: 'Send Email',
    icon: Mail,
    description: 'Send email to all parties in selected transactions',
    color: 'bg-indigo-500 hover:bg-indigo-600',
    requiresParams: true,
  },
  {
    action: 'generate_report',
    label: 'Generate Report',
    icon: FileText,
    description: 'Create report for selected transactions',
    color: 'bg-cyan-500 hover:bg-cyan-600',
    requiresParams: true,
  },
  {
    action: 'export',
    label: 'Export Data',
    icon: Download,
    description: 'Export selected transactions to CSV/Excel',
    color: 'bg-teal-500 hover:bg-teal-600',
  },
  {
    action: 'archive',
    label: 'Archive',
    icon: Archive,
    description: 'Archive selected transactions',
    color: 'bg-gray-500 hover:bg-gray-600',
    confirmMessage: 'Are you sure you want to archive {count} transactions?',
  },
  {
    action: 'delete',
    label: 'Delete',
    icon: Trash2,
    description: 'Permanently delete selected transactions',
    color: 'bg-red-500 hover:bg-red-600',
    confirmMessage: 'Are you sure you want to permanently delete {count} transactions? This cannot be undone.',
    dangerous: true,
  },
];

const TASK_ACTIONS: BulkActionOption[] = [
  {
    action: 'update_status',
    label: 'Update Status',
    icon: CheckSquare,
    description: 'Change status for selected tasks',
    color: 'bg-blue-500 hover:bg-blue-600',
    requiresParams: true,
  },
  {
    action: 'assign_agent',
    label: 'Reassign Tasks',
    icon: UserPlus,
    description: 'Reassign selected tasks to different owner',
    color: 'bg-purple-500 hover:bg-purple-600',
    requiresParams: true,
  },
  {
    action: 'update_date',
    label: 'Update Due Date',
    icon: Calendar,
    description: 'Set due date for selected tasks',
    color: 'bg-amber-500 hover:bg-amber-600',
    requiresParams: true,
  },
  {
    action: 'delete',
    label: 'Delete',
    icon: Trash2,
    description: 'Permanently delete selected tasks',
    color: 'bg-red-500 hover:bg-red-600',
    confirmMessage: 'Are you sure you want to permanently delete {count} tasks? This cannot be undone.',
    dangerous: true,
  },
];

export const BulkActionsToolbar: React.FC<BulkActionsToolbarProps> = ({
  selectedItems,
  itemType,
  onClearSelection,
  onBulkAction,
}) => {
  const [showActionMenu, setShowActionMenu] = useState(false);
  const [selectedAction, setSelectedAction] = useState<BulkActionOption | null>(null);
  const [actionParams, setActionParams] = useState<any>({});

  const actions = itemType === 'transactions' ? TRANSACTION_ACTIONS : TASK_ACTIONS;

  const handleActionClick = async (action: BulkActionOption) => {
    if (action.requiresParams) {
      setSelectedAction(action);
      setShowActionMenu(false);
    } else {
      await executeAction(action);
    }
  };

  const executeAction = async (action: BulkActionOption, params?: any) => {
    // Show confirmation for dangerous actions
    if (action.confirmMessage) {
      const message = action.confirmMessage.replace('{count}', selectedItems.length.toString());
      if (!confirm(message)) {
        return;
      }
    }

    try {
      await onBulkAction(action.action, params);
      toast.success(`${action.label} completed successfully for ${selectedItems.length} items`);
      setSelectedAction(null);
      setActionParams({});
      onClearSelection();
    } catch (error: any) {
      console.error('Bulk action failed:', error);
      toast.error(error?.message || `Failed to ${action.label.toLowerCase()}`);
    }
  };

  const handleSubmitParams = async () => {
    if (!selectedAction) return;
    await executeAction(selectedAction, actionParams);
  };

  if (selectedItems.length === 0) {
    return null;
  }

  return (
    <>
      {/* Toolbar */}
      <Card className="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 shadow-2xl">
        <div className="px-6 py-4">
          <div className="flex items-center gap-4">
            {/* Selection count */}
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <CheckSquare className="h-5 w-5 text-blue-600" />
              </div>
              <div>
                <p className="text-sm font-semibold text-gray-900">
                  {selectedItems.length} {itemType === 'transactions' ? 'transaction' : 'task'}
                  {selectedItems.length !== 1 ? 's' : ''} selected
                </p>
                <button
                  onClick={onClearSelection}
                  className="text-xs text-blue-600 hover:text-blue-700 font-medium"
                >
                  Clear selection
                </button>
              </div>
            </div>

            {/* Divider */}
            <div className="h-10 w-px bg-gray-200" />

            {/* Quick actions */}
            <div className="flex items-center gap-2">
              <Button
                size="sm"
                variant="secondary"
                onClick={() => setShowActionMenu(!showActionMenu)}
              >
                Bulk Actions
              </Button>

              {/* Clear selection button */}
              <button
                onClick={onClearSelection}
                className="p-2 text-gray-400 hover:text-gray-600 rounded transition-colors"
                title="Clear selection"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>

        {/* Action menu dropdown */}
        {showActionMenu && (
          <div className="border-t border-gray-200 px-6 py-4">
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
              {actions.map((action) => (
                <button
                  key={action.action}
                  onClick={() => handleActionClick(action)}
                  className={`p-3 rounded-lg text-white text-left transition-all ${action.color} ${
                    action.dangerous ? 'ring-2 ring-red-300' : ''
                  }`}
                >
                  <action.icon className="h-5 w-5 mb-2" />
                  <p className="text-sm font-medium">{action.label}</p>
                  <p className="text-xs opacity-90 mt-1">{action.description}</p>
                </button>
              ))}
            </div>
          </div>
        )}
      </Card>

      {/* Parameter input modal */}
      {selectedAction && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <Card className="max-w-lg w-full">
            <div className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-900">
                  {selectedAction.label}
                </h3>
                <button
                  onClick={() => {
                    setSelectedAction(null);
                    setActionParams({});
                  }}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <X className="h-5 w-5" />
                </button>
              </div>

              <div className="mb-4 p-3 bg-blue-50 rounded-lg flex items-start gap-2">
                <AlertCircle className="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
                <div>
                  <p className="text-sm font-medium text-blue-900">
                    Applying to {selectedItems.length} {itemType === 'transactions' ? 'transaction' : 'task'}
                    {selectedItems.length !== 1 ? 's' : ''}
                  </p>
                  <p className="text-xs text-blue-700 mt-1">
                    {selectedAction.description}
                  </p>
                </div>
              </div>

              {/* Dynamic form based on action */}
              <div className="space-y-4">
                {selectedAction.action === 'update_status' && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Select New Status
                    </label>
                    <select
                      value={actionParams.status || ''}
                      onChange={(e) => setActionParams({ ...actionParams, status: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Choose status...</option>
                      {itemType === 'transactions' ? (
                        <>
                          <option value="prospect">Prospect</option>
                          <option value="listing_active">Listing Active</option>
                          <option value="under_agreement">Under Agreement</option>
                          <option value="closed">Closed</option>
                          <option value="cancelled">Cancelled</option>
                        </>
                      ) : (
                        <>
                          <option value="pending">Pending</option>
                          <option value="in_progress">In Progress</option>
                          <option value="completed">Completed</option>
                          <option value="blocked">Blocked</option>
                          <option value="cancelled">Cancelled</option>
                        </>
                      )}
                    </select>
                  </div>
                )}

                {selectedAction.action === 'assign_agent' && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      {itemType === 'transactions' ? 'Select Agent' : 'Reassign To'}
                    </label>
                    <input
                      type="text"
                      value={actionParams.agent_name || ''}
                      onChange={(e) => setActionParams({ ...actionParams, agent_name: e.target.value })}
                      placeholder="Enter agent name or ID"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                )}

                {selectedAction.action === 'add_tags' && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Tags (comma-separated)
                    </label>
                    <input
                      type="text"
                      value={actionParams.tags || ''}
                      onChange={(e) => setActionParams({ ...actionParams, tags: e.target.value })}
                      placeholder="e.g., urgent, high-value, needs-review"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                )}

                {selectedAction.action === 'update_date' && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      {itemType === 'transactions' ? 'Closing Date' : 'Due Date'}
                    </label>
                    <input
                      type="date"
                      value={actionParams.date || ''}
                      onChange={(e) => setActionParams({ ...actionParams, date: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                    />
                  </div>
                )}

                {selectedAction.action === 'bulk_email' && (
                  <>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Email Subject
                      </label>
                      <input
                        type="text"
                        value={actionParams.subject || ''}
                        onChange={(e) => setActionParams({ ...actionParams, subject: e.target.value })}
                        placeholder="Email subject..."
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Email Message
                      </label>
                      <textarea
                        value={actionParams.message || ''}
                        onChange={(e) => setActionParams({ ...actionParams, message: e.target.value })}
                        placeholder="Email message..."
                        rows={4}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </>
                )}

                {selectedAction.action === 'generate_report' && (
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Report Type
                    </label>
                    <select
                      value={actionParams.report_type || ''}
                      onChange={(e) => setActionParams({ ...actionParams, report_type: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                    >
                      <option value="">Choose report type...</option>
                      <option value="summary">Summary Report</option>
                      <option value="detailed">Detailed Report</option>
                      <option value="timeline">Timeline Report</option>
                      <option value="financial">Financial Report</option>
                    </select>
                  </div>
                )}
              </div>

              {/* Actions */}
              <div className="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-gray-200">
                <Button
                  variant="secondary"
                  onClick={() => {
                    setSelectedAction(null);
                    setActionParams({});
                  }}
                >
                  Cancel
                </Button>
                <Button
                  variant="primary"
                  onClick={handleSubmitParams}
                  disabled={!actionParams || Object.keys(actionParams).length === 0}
                >
                  Apply to {selectedItems.length} item{selectedItems.length !== 1 ? 's' : ''}
                </Button>
              </div>
            </div>
          </Card>
        </div>
      )}
    </>
  );
};
