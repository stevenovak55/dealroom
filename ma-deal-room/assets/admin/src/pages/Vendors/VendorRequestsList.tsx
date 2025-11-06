import { useState } from 'react';
import { Plus, Mail, X, CheckCircle, Clock, AlertCircle, Ban } from 'lucide-react';
import { format } from 'date-fns';
import { useGetVendorRequests, useResendVendorRequest, useCancelVendorRequest } from '@/api/queries/useVendorRequests';
import { Card, CardContent } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { Loader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { CreateVendorRequestModal } from './CreateVendorRequestModal';
import { showToast } from '@/utils/toast';

const statusConfig = {
  sent: { label: 'Sent', icon: Mail, color: 'blue' },
  opened: { label: 'Opened', icon: Clock, color: 'yellow' },
  scheduled: { label: 'Scheduled', icon: Clock, color: 'purple' },
  completed: { label: 'Completed', icon: CheckCircle, color: 'green' },
  expired: { label: 'Expired', icon: AlertCircle, color: 'red' },
  cancelled: { label: 'Cancelled', icon: Ban, color: 'gray' },
};

const vendorTypeLabels = {
  fire_dept: 'Fire Department',
  septic_inspector: 'Septic Inspector',
  hoa_manager: 'HOA Manager',
  title_company: 'Title Company',
  appraiser: 'Appraiser',
  inspector: 'Inspector',
  other: 'Other',
};

export const VendorRequestsList = () => {
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [selectedTransaction] = useState<number | undefined>();

  const { data: vendorRequests, isLoading } = useGetVendorRequests(selectedTransaction);
  const resendMutation = useResendVendorRequest();
  const cancelMutation = useCancelVendorRequest();

  const handleResend = async (id: number) => {
    try {
      await resendMutation.mutateAsync(id);
      showToast.success('Vendor request resent successfully');
    } catch (error) {
      showToast.error('Failed to resend vendor request');
    }
  };

  const handleCancel = async (id: number) => {
    if (!confirm('Are you sure you want to cancel this vendor request?')) {
      return;
    }

    try {
      await cancelMutation.mutateAsync(id);
      showToast.success('Vendor request cancelled');
    } catch (error) {
      showToast.error('Failed to cancel vendor request');
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <Loader />
      </div>
    );
  }

  return (
    <div className="p-6 max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Vendor Requests</h1>
          <p className="text-gray-600 mt-1">Manage external vendor tasks and track their progress</p>
        </div>
        <Button onClick={() => setIsCreateModalOpen(true)} size="lg">
          <Plus className="h-5 w-5 mr-2" />
          Send Request
        </Button>
      </div>

      {/* Stats Cards */}
      {vendorRequests && vendorRequests.length > 0 && (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <Card>
            <CardContent className="pt-5">
              <div className="text-sm font-medium text-gray-600">Total Requests</div>
              <div className="text-2xl font-bold text-gray-900 mt-1">{vendorRequests.length}</div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-5">
              <div className="text-sm font-medium text-gray-600">Pending</div>
              <div className="text-2xl font-bold text-yellow-600 mt-1">
                {vendorRequests.filter((r) => ['sent', 'opened'].includes(r.status)).length}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-5">
              <div className="text-sm font-medium text-gray-600">Scheduled</div>
              <div className="text-2xl font-bold text-purple-600 mt-1">
                {vendorRequests.filter((r) => r.status === 'scheduled').length}
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="pt-5">
              <div className="text-sm font-medium text-gray-600">Completed</div>
              <div className="text-2xl font-bold text-green-600 mt-1">
                {vendorRequests.filter((r) => r.status === 'completed').length}
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Requests List */}
      {!vendorRequests || vendorRequests.length === 0 ? (
        <Card>
          <CardContent className="py-12">
            <EmptyState
              title="No Vendor Requests"
              description="Get started by sending a request to an external vendor"
              action={{
                label: 'Send Request',
                onClick: () => setIsCreateModalOpen(true),
              }}
            />
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {vendorRequests.map((request) => {
            const statusInfo = statusConfig[request.status];
            const StatusIcon = statusInfo.icon;

            return (
              <Card key={request.id}>
                <CardContent className="p-6">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-3 mb-2">
                        <h3 className="text-lg font-semibold text-gray-900">
                          {vendorTypeLabels[request.vendor_type]}
                        </h3>
                        <Badge variant={statusInfo.color as any}>
                          <StatusIcon className="h-3 w-3 mr-1" />
                          {statusInfo.label}
                        </Badge>
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                          <div className="text-sm text-gray-600">Vendor Contact</div>
                          <div className="text-sm font-medium text-gray-900 mt-1">
                            {request.vendor_email}
                          </div>
                          {request.vendor_phone && (
                            <div className="text-sm text-gray-600 mt-1">{request.vendor_phone}</div>
                          )}
                        </div>

                        <div>
                          <div className="text-sm text-gray-600">Transaction</div>
                          <div className="text-sm font-medium text-gray-900 mt-1">
                            {request.transaction?.property_address || `Transaction #${request.transaction_id}`}
                          </div>
                          {request.task && (
                            <div className="text-sm text-gray-600 mt-1">{request.task.title}</div>
                          )}
                        </div>

                        {request.scheduled_date && (
                          <div>
                            <div className="text-sm text-gray-600">Scheduled Date</div>
                            <div className="text-sm font-medium text-gray-900 mt-1">
                              {format(new Date(request.scheduled_date), 'MMM d, yyyy')}
                              {request.scheduled_time && ` at ${request.scheduled_time}`}
                            </div>
                          </div>
                        )}

                        {request.last_opened_at && (
                          <div>
                            <div className="text-sm text-gray-600">Last Opened</div>
                            <div className="text-sm font-medium text-gray-900 mt-1">
                              {format(new Date(request.last_opened_at), 'MMM d, yyyy h:mm a')}
                            </div>
                          </div>
                        )}
                      </div>

                      {request.completion_notes && (
                        <div className="mt-4 p-3 bg-gray-50 rounded-lg">
                          <div className="text-sm font-medium text-gray-700">Completion Notes</div>
                          <div className="text-sm text-gray-600 mt-1">{request.completion_notes}</div>
                        </div>
                      )}

                      <div className="flex items-center gap-2 mt-4 text-xs text-gray-500">
                        <span>Created {format(new Date(request.created_at), 'MMM d, yyyy')}</span>
                        {request.status !== 'completed' && request.token_expires_at && (
                          <>
                            <span>•</span>
                            <span>Expires {format(new Date(request.token_expires_at), 'MMM d, yyyy')}</span>
                          </>
                        )}
                      </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center gap-2 ml-4">
                      {['sent', 'opened', 'expired'].includes(request.status) && (
                        <Button
                          variant="secondary"
                          size="sm"
                          onClick={() => handleResend(request.id)}
                          disabled={resendMutation.isPending}
                        >
                          <Mail className="h-4 w-4 mr-1" />
                          Resend
                        </Button>
                      )}
                      {!['completed', 'cancelled'].includes(request.status) && (
                        <Button
                          variant="secondary"
                          size="sm"
                          onClick={() => handleCancel(request.id)}
                          disabled={cancelMutation.isPending}
                        >
                          <X className="h-4 w-4 mr-1" />
                          Cancel
                        </Button>
                      )}
                    </div>
                  </div>
                </CardContent>
              </Card>
            );
          })}
        </div>
      )}

      {/* Create Modal */}
      <CreateVendorRequestModal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
      />
    </div>
  );
};
