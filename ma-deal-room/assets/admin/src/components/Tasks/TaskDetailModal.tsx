import { useState } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { formatDateTime } from '@/utils/formatDate';
import { UserPlus } from 'lucide-react';
import { VendorRequestForm } from '../VendorRequestForm';
import type { Task } from '@/api/types';

interface TaskDetailModalProps {
  task: Task;
  isOpen: boolean;
  onClose: () => void;
  onRequestVendor?: () => void;
}

export const TaskDetailModal = ({ task, isOpen, onClose, onRequestVendor }: TaskDetailModalProps) => {
  const [showVendorForm, setShowVendorForm] = useState(false);

  // Check if task can have vendor requested
  const canRequestVendor = task.owner_role === 'vendor' && task.status !== 'completed';

  const handleRequestVendor = () => {
    setShowVendorForm(true);
  };

  const handleVendorRequestSuccess = () => {
    setShowVendorForm(false);
    onRequestVendor?.();
  };

  return (
    <>
      <Modal isOpen={isOpen} onClose={onClose} title="Task Details" size="lg">
        <div className="space-y-6">
          {/* Title and status */}
          <div>
            <div className="flex items-start justify-between gap-4">
              <h3 className="text-xl font-semibold text-gray-900">{task.title}</h3>
              <Badge variant={task.status === 'completed' ? 'success' : 'warning'}>
                {task.status}
              </Badge>
            </div>
            {task.description && (
              <p className="text-sm text-gray-600 mt-2">{task.description}</p>
            )}
            {canRequestVendor && (
              <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p className="text-sm text-blue-800 mb-3">
                  This task is assigned to a vendor. Request a vendor to complete this work:
                </p>
                <Button variant="primary" size="sm" onClick={handleRequestVendor}>
                  <UserPlus className="w-4 h-4 mr-2" />
                  Request Vendor
                </Button>
              </div>
            )}
          </div>

        {/* Details */}
        <div className="grid grid-cols-2 gap-6">
          <div>
            <p className="text-sm font-medium text-gray-500">Due Date</p>
            <p className="text-sm text-gray-900 mt-1">{formatDateTime(task.due_at)}</p>
          </div>
          <div>
            <p className="text-sm font-medium text-gray-500">Owner Role</p>
            <p className="text-sm text-gray-900 mt-1 capitalize">
              {task.owner_role.replace('_', ' ')}
            </p>
          </div>
          <div>
            <p className="text-sm font-medium text-gray-500">Created</p>
            <p className="text-sm text-gray-900 mt-1">{formatDateTime(task.created_at)}</p>
          </div>
          {task.completed_at && (
            <div>
              <p className="text-sm font-medium text-gray-500">Completed</p>
              <p className="text-sm text-gray-900 mt-1">{formatDateTime(task.completed_at)}</p>
            </div>
          )}
        </div>

        {/* Dependencies */}
        {task.depends_on_task_ids && task.depends_on_task_ids.length > 0 && (
          <div>
            <p className="text-sm font-medium text-gray-500 mb-2">Dependencies</p>
            <p className="text-sm text-gray-600">
              This task depends on {task.depends_on_task_ids.length} other task(s)
            </p>
          </div>
        )}

        {/* Metadata */}
        {task.metadata && Object.keys(task.metadata).length > 0 && (
          <div>
            <p className="text-sm font-medium text-gray-500 mb-2">Additional Information</p>
            <pre className="text-xs text-gray-600 bg-gray-50 p-3 rounded">
              {JSON.stringify(task.metadata, null, 2)}
            </pre>
          </div>
        )}
      </div>

      <ModalFooter>
        <Button variant="secondary" onClick={onClose}>
          Close
        </Button>
      </ModalFooter>
    </Modal>

      {/* Vendor Request Form */}
      {showVendorForm && (
        <VendorRequestForm
          isOpen={showVendorForm}
          onClose={() => setShowVendorForm(false)}
          transactionId={task.transaction_id}
          taskId={task.id}
          onSuccess={handleVendorRequestSuccess}
        />
      )}
    </>
  );
};
