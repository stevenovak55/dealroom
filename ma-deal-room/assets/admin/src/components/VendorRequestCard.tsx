/**
 * Vendor Request Card Component
 *
 * Displays a vendor request with status, details, and action buttons.
 *
 * @package MADealRoom
 * @since 2.0.0
 */

import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import {
  Copy,
  Mail,
  Trash2,
  Star,
  Calendar,
  FileText,
  AlertCircle,
  Phone,
  Building,
} from 'lucide-react';
import { vendorRequestService, VendorRequest } from '../api/vendorRequestService';
import { Button } from './shared/Button';
import { Badge } from './shared/Badge';
import { format } from 'date-fns';

interface VendorRequestCardProps {
  vendorRequest: VendorRequest;
  onDelete?: () => void;
  onRate?: () => void;
}

const VENDOR_TYPE_ICONS: Record<string, string> = {
  inspector: '🔍',
  appraiser: '📊',
  attorney: '⚖️',
  fire_dept: '🚒',
  septic_inspector: '🚰',
  hoa_manager: '🏘️',
  title_company: '📋',
  contractor: '🔨',
  other: '📦',
};

export const VendorRequestCard: React.FC<VendorRequestCardProps> = ({
  vendorRequest,
  onDelete,
  onRate,
}) => {
  const queryClient = useQueryClient();
  const [isExpanded, setIsExpanded] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const deleteMutation = useMutation({
    mutationFn: () => vendorRequestService.delete(vendorRequest.id),
    onSuccess: () => {
      toast.success('Vendor request deleted');
      queryClient.invalidateQueries({ queryKey: ['vendor-requests'] });
      onDelete?.();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to delete');
    },
  });

  const resendMutation = useMutation({
    mutationFn: () => vendorRequestService.resendInvitation(vendorRequest.id),
    onSuccess: () => {
      toast.success('Invitation resent successfully!');
      queryClient.invalidateQueries({ queryKey: ['vendor-requests'] });
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to resend');
    },
  });

  const handleCopyUrl = () => {
    vendorRequestService.copyPortalUrl(vendorRequest);
    toast.success('Portal URL copied to clipboard!');
  };

  const handleResend = () => {
    resendMutation.mutate();
  };

  const handleDelete = () => {
    if (showDeleteConfirm) {
      deleteMutation.mutate();
    } else {
      setShowDeleteConfirm(true);
      setTimeout(() => setShowDeleteConfirm(false), 3000);
    }
  };

  const statusColor = vendorRequestService.getStatusColor(vendorRequest.status);
  const statusLabel = vendorRequestService.getStatusLabel(vendorRequest.status);
  const vendorTypeLabel = vendorRequestService.getVendorTypeLabel(vendorRequest.vendor_type);
  const vendorIcon = VENDOR_TYPE_ICONS[vendorRequest.vendor_type] || VENDOR_TYPE_ICONS.other;

  const isExpired = vendorRequestService.isTokenExpired(vendorRequest);
  const canDelete = vendorRequestService.canDelete(vendorRequest);
  const canResend = vendorRequestService.canResend(vendorRequest);
  const canRate = vendorRequestService.canRate(vendorRequest);

  return (
    <div className="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow">
      {/* Header */}
      <div
        className="p-4 cursor-pointer"
        onClick={() => setIsExpanded(!isExpanded)}
      >
        <div className="flex items-start justify-between">
          <div className="flex items-start gap-3 flex-1">
            {/* Icon */}
            <div className="text-3xl">{vendorIcon}</div>

            {/* Info */}
            <div className="flex-1 min-w-0">
              <h3 className="text-lg font-semibold text-gray-900 mb-1">
                {vendorTypeLabel}
              </h3>
              <div className="space-y-1">
                <p className="text-sm text-gray-600 flex items-center gap-2">
                  <Mail className="w-4 h-4 flex-shrink-0" />
                  {vendorRequest.vendor_email}
                </p>
                {vendorRequest.vendor_phone && (
                  <p className="text-sm text-gray-600 flex items-center gap-2">
                    <Phone className="w-4 h-4 flex-shrink-0" />
                    {vendorRequest.vendor_phone}
                  </p>
                )}
                {vendorRequest.vendor_company && (
                  <p className="text-sm text-gray-600 flex items-center gap-2">
                    <Building className="w-4 h-4 flex-shrink-0" />
                    {vendorRequest.vendor_company}
                  </p>
                )}
              </div>
            </div>
          </div>

          {/* Status Badge */}
          <Badge variant={statusColor as any}>{statusLabel}</Badge>
        </div>

        {/* Quick Info */}
        <div className="mt-3 flex flex-wrap gap-3 text-sm text-gray-600">
          {vendorRequest.scheduled_date && (
            <div className="flex items-center gap-1">
              <Calendar className="w-4 h-4" />
              <span>
                {format(new Date(vendorRequest.scheduled_date), 'MMM d, yyyy')}
                {vendorRequest.scheduled_time && ` at ${vendorRequest.scheduled_time}`}
              </span>
            </div>
          )}
          {vendorRequest.document_url && (
            <div className="flex items-center gap-1">
              <FileText className="w-4 h-4" />
              <span>Document uploaded</span>
            </div>
          )}
          {vendorRequest.average_rating && (
            <div className="flex items-center gap-1">
              <Star className="w-4 h-4 text-yellow-500 fill-yellow-500" />
              <span>{vendorRequest.average_rating.toFixed(1)}</span>
            </div>
          )}
          {isExpired && (
            <div className="flex items-center gap-1 text-red-600">
              <AlertCircle className="w-4 h-4" />
              <span>Token expired</span>
            </div>
          )}
        </div>
      </div>

      {/* Expanded Details */}
      {isExpanded && (
        <div className="border-t border-gray-200 p-4 bg-gray-50 space-y-3">
          {/* Completion Notes */}
          {vendorRequest.completion_notes && (
            <div>
              <h4 className="text-sm font-medium text-gray-700 mb-1">Completion Notes</h4>
              <p className="text-sm text-gray-600">{vendorRequest.completion_notes}</p>
            </div>
          )}

          {/* Metadata */}
          {vendorRequest.metadata && (
            <div>
              <h4 className="text-sm font-medium text-gray-700 mb-1">Notes</h4>
              <p className="text-sm text-gray-600">
                {vendorRequest.metadata.notes || 'No notes'}
              </p>
            </div>
          )}

          {/* Timestamps */}
          <div className="grid grid-cols-2 gap-2 text-xs text-gray-500">
            <div>
              <span className="font-medium">Created:</span>{' '}
              {format(new Date(vendorRequest.created_at), 'MMM d, yyyy')}
            </div>
            {vendorRequest.last_opened_at && (
              <div>
                <span className="font-medium">Last Opened:</span>{' '}
                {format(new Date(vendorRequest.last_opened_at), 'MMM d, yyyy')}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Actions */}
      <div className="border-t border-gray-200 p-3 bg-white flex flex-wrap gap-2">
        <Button
          variant="secondary"
          size="sm"
          onClick={handleCopyUrl}
        >
          <Copy className="w-3 h-3 mr-1" />
          Copy URL
        </Button>

        {canResend && (
          <Button
            variant="secondary"
            size="sm"
            onClick={handleResend}
            disabled={resendMutation.isPending}
          >
            <Mail className="w-3 h-3 mr-1" />
            {resendMutation.isPending ? 'Sending...' : 'Resend'}
          </Button>
        )}

        {canRate && onRate && (
          <Button
            variant="secondary"
            size="sm"
            onClick={onRate}
          >
            <Star className="w-3 h-3 mr-1" />
            Rate
          </Button>
        )}

        {vendorRequest.document_url && (
          <Button
            variant="secondary"
            size="sm"
            onClick={() => window.open(vendorRequest.document_url!, '_blank')}
          >
            <FileText className="w-3 h-3 mr-1" />
            View Document
          </Button>
        )}

        {canDelete && (
          <Button
            variant="danger"
            size="sm"
            onClick={handleDelete}
            disabled={deleteMutation.isPending}
            className="ml-auto"
          >
            <Trash2 className="w-3 h-3 mr-1" />
            {showDeleteConfirm
              ? 'Confirm Delete?'
              : deleteMutation.isPending
              ? 'Deleting...'
              : 'Delete'}
          </Button>
        )}
      </div>
    </div>
  );
};

export default VendorRequestCard;
