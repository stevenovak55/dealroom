/**
 * Vendor Requests List Component
 *
 * Lists and filters vendor requests for a transaction.
 *
 * @package MADealRoom
 * @since 2.0.0
 */

import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Plus, Filter } from 'lucide-react';
import { vendorRequestService, VendorRequest } from '../api/vendorRequestService';
import { VendorRequestForm } from './VendorRequestForm';
import { VendorRequestCard } from './VendorRequestCard';
import { VendorRatingForm } from './VendorRatingForm';
import { Button } from './shared/Button';
import type { Task } from '../api/types';

interface VendorRequestsListProps {
  transactionId: number;
  tasks?: Task[];
}

type StatusFilter = 'all' | 'sent' | 'opened' | 'scheduled' | 'completed';

export const VendorRequestsList: React.FC<VendorRequestsListProps> = ({
  transactionId,
  tasks = [],
}) => {
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [ratingRequestId, setRatingRequestId] = useState<number | null>(null);

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['vendor-requests', transactionId, statusFilter],
    queryFn: () =>
      vendorRequestService.list({
        transaction_id: transactionId,
        status: statusFilter === 'all' ? undefined : statusFilter,
      }),
  });

  const vendorRequests = data?.vendor_requests || [];

  const statusCounts = {
    all: vendorRequests.length,
    sent: vendorRequests.filter((vr: VendorRequest) => vr.status === 'sent').length,
    opened: vendorRequests.filter((vr: VendorRequest) => vr.status === 'opened').length,
    scheduled: vendorRequests.filter((vr: VendorRequest) => vr.status === 'scheduled').length,
    completed: vendorRequests.filter((vr: VendorRequest) => vr.status === 'completed').length,
  };

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-4">
        <p className="text-red-800">Failed to load vendor requests</p>
        <Button variant="secondary" size="sm" onClick={() => refetch()} className="mt-2">
          Retry
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold text-gray-900">Vendor Requests</h2>
        <Button
          variant="primary"
          onClick={() => setShowCreateForm(true)}
        >
          <Plus className="w-4 h-4 mr-2" />
          Request Vendor
        </Button>
      </div>

      {/* Filter Tabs */}
      <div className="flex gap-2 border-b border-gray-200">
        {(['all', 'sent', 'opened', 'scheduled', 'completed'] as StatusFilter[]).map((status) => (
          <button
            key={status}
            onClick={() => setStatusFilter(status)}
            className={`
              px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors
              ${
                statusFilter === status
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300'
              }
            `}
          >
            {status.charAt(0).toUpperCase() + status.slice(1)}
            <span className="ml-2 px-2 py-0.5 bg-gray-100 rounded-full text-xs">
              {statusCounts[status]}
            </span>
          </button>
        ))}
      </div>

      {/* Loading State */}
      {isLoading && (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-48 bg-gray-100 rounded-lg animate-pulse" />
          ))}
        </div>
      )}

      {/* Empty State */}
      {!isLoading && vendorRequests.length === 0 && (
        <div className="text-center py-12">
          <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Filter className="w-8 h-8 text-gray-400" />
          </div>
          <h3 className="text-lg font-medium text-gray-900 mb-2">
            {statusFilter === 'all' ? 'No vendor requests yet' : `No ${statusFilter} requests`}
          </h3>
          <p className="text-gray-600 mb-4">
            {statusFilter === 'all'
              ? 'Create your first vendor request to get started'
              : 'Try a different filter or create a new request'}
          </p>
          <Button
            variant="primary"
            onClick={() => setShowCreateForm(true)}
          >
            <Plus className="w-4 h-4 mr-2" />
            Request Vendor
          </Button>
        </div>
      )}

      {/* Vendor Requests Grid */}
      {!isLoading && vendorRequests.length > 0 && (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {vendorRequests.map((vendorRequest: VendorRequest) => (
            <VendorRequestCard
              key={vendorRequest.id}
              vendorRequest={vendorRequest}
              onDelete={() => refetch()}
              onRate={() => setRatingRequestId(vendorRequest.id)}
            />
          ))}
        </div>
      )}

      {/* Create Form Modal */}
      <VendorRequestForm
        isOpen={showCreateForm}
        onClose={() => setShowCreateForm(false)}
        transactionId={transactionId}
        tasks={tasks}
        onSuccess={() => {
          refetch();
          setShowCreateForm(false);
        }}
      />

      {/* Rating Form Modal */}
      {ratingRequestId && (
        <VendorRatingForm
          isOpen={true}
          onClose={() => setRatingRequestId(null)}
          vendorRequestId={ratingRequestId}
          onSuccess={() => {
            refetch();
            setRatingRequestId(null);
          }}
        />
      )}
    </div>
  );
};

export default VendorRequestsList;
