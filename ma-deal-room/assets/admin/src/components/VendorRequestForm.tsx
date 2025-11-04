/**
 * Vendor Request Form Component
 *
 * Modal form for creating vendor requests from the admin interface.
 * Allows agents to send service requests to external vendors.
 *
 * @package MADealRoom
 * @since 2.0.0
 */

import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import toast from 'react-hot-toast';
import { Send, Copy, CheckCircle } from 'lucide-react';
import { vendorRequestService, CreateVendorRequestPayload, VendorRequest } from '../api/vendorRequestService';
import { Modal, ModalFooter } from './shared/Modal';
import { Button } from './shared/Button';
import type { Task } from '../api/types';

interface VendorRequestFormProps {
  isOpen: boolean;
  onClose: () => void;
  transactionId: number;
  taskId?: number;
  tasks?: Task[];
  onSuccess?: (vendorRequest: VendorRequest) => void;
}

interface FormData {
  task_id: string;
  vendor_type: string;
  vendor_email: string;
  vendor_phone: string;
  vendor_name: string;
  vendor_company: string;
  notes: string;
}

const VENDOR_TYPES = [
  { value: 'inspector', label: 'Home Inspector' },
  { value: 'appraiser', label: 'Appraiser' },
  { value: 'attorney', label: 'Attorney' },
  { value: 'fire_dept', label: 'Fire Department' },
  { value: 'septic_inspector', label: 'Septic Inspector' },
  { value: 'hoa_manager', label: 'HOA Manager' },
  { value: 'title_company', label: 'Title Company' },
  { value: 'contractor', label: 'Contractor' },
  { value: 'other', label: 'Other' },
];

export const VendorRequestForm: React.FC<VendorRequestFormProps> = ({
  isOpen,
  onClose,
  transactionId,
  taskId,
  tasks = [],
  onSuccess,
}) => {
  const queryClient = useQueryClient();
  const [showSuccess, setShowSuccess] = useState(false);
  const [createdRequest, setCreatedRequest] = useState<{
    vendor_request: VendorRequest;
    portal_url: string;
    email_sent: boolean;
  } | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  } = useForm<FormData>({
    defaultValues: {
      task_id: taskId?.toString() || '',
      vendor_type: 'inspector',
      vendor_email: '',
      vendor_phone: '',
      vendor_name: '',
      vendor_company: '',
      notes: '',
    },
  });

  const createMutation = useMutation({
    mutationFn: (data: CreateVendorRequestPayload) => vendorRequestService.create(data),
    onSuccess: (response) => {
      setCreatedRequest(response);
      setShowSuccess(true);
      toast.success('Vendor request created successfully!');
      queryClient.invalidateQueries({ queryKey: ['vendor-requests'] });
      if (onSuccess) {
        onSuccess(response.vendor_request);
      }
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to create vendor request');
    },
  });

  const onSubmit = (data: FormData) => {
    const payload: CreateVendorRequestPayload = {
      transaction_id: transactionId,
      vendor_type: data.vendor_type,
      vendor_email: data.vendor_email,
      vendor_phone: data.vendor_phone || undefined,
      vendor_name: data.vendor_name || undefined,
      vendor_company: data.vendor_company || undefined,
      notes: data.notes || undefined,
    };

    if (data.task_id && data.task_id !== '') {
      payload.task_id = parseInt(data.task_id);
    }

    createMutation.mutate(payload);
  };

  const handleClose = () => {
    reset();
    setShowSuccess(false);
    setCreatedRequest(null);
    onClose();
  };

  const handleCopyUrl = () => {
    if (createdRequest) {
      navigator.clipboard.writeText(createdRequest.portal_url);
      toast.success('Portal URL copied to clipboard!');
    }
  };

  const handleSendAnother = () => {
    reset();
    setShowSuccess(false);
    setCreatedRequest(null);
  };

  if (!isOpen) return null;

  // Success State
  if (showSuccess && createdRequest) {
    return (
      <Modal isOpen={isOpen} onClose={handleClose} title="Vendor Request Sent!" size="lg">
        <div className="space-y-6">
          {/* Success Icon */}
          <div className="flex justify-center">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
              <CheckCircle className="w-10 h-10 text-green-600" />
            </div>
          </div>

          {/* Success Message */}
          <div className="text-center">
            <p className="text-lg font-semibold text-gray-900 mb-2">
              Invitation Email Sent Successfully!
            </p>
            <p className="text-sm text-gray-600">
              The vendor has been sent an email with a link to access their portal.
            </p>
          </div>

          {/* Portal URL */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Vendor Portal URL
            </label>
            <div className="flex gap-2">
              <input
                type="text"
                value={createdRequest.portal_url}
                readOnly
                className="flex-1 px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm font-mono"
              />
              <Button
                variant="secondary"
                size="sm"
                onClick={handleCopyUrl}
              >
                <Copy className="w-4 h-4 mr-2" />
                Copy
              </Button>
            </div>
            <p className="text-xs text-gray-500 mt-1">
              Share this URL if you need to send it manually (link expires in 30 days)
            </p>
          </div>

          {/* Vendor Details */}
          <div className="bg-gray-50 rounded-lg p-4 space-y-2">
            <h4 className="font-medium text-gray-900 mb-3">Request Details</h4>
            <div className="grid grid-cols-2 gap-3 text-sm">
              <div>
                <span className="text-gray-500">Vendor Type:</span>
                <span className="ml-2 text-gray-900 font-medium">
                  {vendorRequestService.getVendorTypeLabel(createdRequest.vendor_request.vendor_type)}
                </span>
              </div>
              <div>
                <span className="text-gray-500">Email:</span>
                <span className="ml-2 text-gray-900 font-medium">
                  {createdRequest.vendor_request.vendor_email}
                </span>
              </div>
              {createdRequest.vendor_request.vendor_phone && (
                <div>
                  <span className="text-gray-500">Phone:</span>
                  <span className="ml-2 text-gray-900 font-medium">
                    {createdRequest.vendor_request.vendor_phone}
                  </span>
                </div>
              )}
              <div>
                <span className="text-gray-500">Status:</span>
                <span className="ml-2 px-2 py-0.5 bg-gray-200 text-gray-800 rounded text-xs font-medium">
                  Sent
                </span>
              </div>
            </div>
          </div>

          {/* Email Status */}
          <div className="border-l-4 border-blue-500 bg-blue-50 p-4">
            <p className="text-sm text-blue-800">
              <strong>Email Status:</strong>{' '}
              {createdRequest.email_sent
                ? 'Invitation email sent successfully ✓'
                : 'Email could not be sent - please share the portal URL manually'}
            </p>
          </div>
        </div>

        <ModalFooter>
          <Button variant="secondary" onClick={handleSendAnother}>
            Send Another
          </Button>
          <Button variant="primary" onClick={handleClose}>
            Done
          </Button>
        </ModalFooter>
      </Modal>
    );
  }

  // Form State
  return (
    <Modal isOpen={isOpen} onClose={handleClose} title="Request Vendor" size="lg">
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        {/* Task Selection (if multiple tasks available) */}
        {tasks.length > 0 && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              Related Task (Optional)
            </label>
            <select
              {...register('task_id')}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">No specific task</option>
              {tasks.map((task) => (
                <option key={task.id} value={task.id}>
                  {task.title}
                </option>
              ))}
            </select>
            <p className="text-xs text-gray-500 mt-1">
              Link this vendor request to a specific task
            </p>
          </div>
        )}

        {/* Vendor Type */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Vendor Type <span className="text-red-500">*</span>
          </label>
          <select
            {...register('vendor_type', { required: 'Vendor type is required' })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            {VENDOR_TYPES.map((type) => (
              <option key={type.value} value={type.value}>
                {type.label}
              </option>
            ))}
          </select>
          {errors.vendor_type && (
            <p className="text-xs text-red-600 mt-1">{errors.vendor_type.message}</p>
          )}
        </div>

        {/* Vendor Email */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Vendor Email <span className="text-red-500">*</span>
          </label>
          <input
            type="email"
            {...register('vendor_email', {
              required: 'Email is required',
              pattern: {
                value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                message: 'Invalid email address',
              },
            })}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="vendor@example.com"
          />
          {errors.vendor_email && (
            <p className="text-xs text-red-600 mt-1">{errors.vendor_email.message}</p>
          )}
          <p className="text-xs text-gray-500 mt-1">
            Invitation will be sent to this email address
          </p>
        </div>

        {/* Vendor Phone */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Vendor Phone (Optional)
          </label>
          <input
            type="tel"
            {...register('vendor_phone')}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="+1-555-0100"
          />
        </div>

        {/* Vendor Name */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Contact Name (Optional)
          </label>
          <input
            type="text"
            {...register('vendor_name')}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="John Smith"
          />
        </div>

        {/* Vendor Company */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Company Name (Optional)
          </label>
          <input
            type="text"
            {...register('vendor_company')}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="ABC Inspections"
          />
        </div>

        {/* Notes */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Notes (Optional)
          </label>
          <textarea
            {...register('notes')}
            rows={3}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Special instructions or additional information..."
          />
          <p className="text-xs text-gray-500 mt-1">
            These notes will be visible to the vendor in their portal
          </p>
        </div>

        {/* Info Box */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p className="text-sm text-blue-800">
            <strong>What happens next:</strong>
            <br />
            1. A secure portal link will be generated
            <br />
            2. An invitation email will be sent to the vendor
            <br />
            3. The vendor can access their portal to respond
            <br />
            4. You'll be notified when they take actions
          </p>
        </div>

        <ModalFooter>
          <Button type="button" variant="secondary" onClick={handleClose}>
            Cancel
          </Button>
          <Button
            type="submit"
            variant="primary"
            disabled={createMutation.isPending}
          >
            <Send className="w-4 h-4 mr-2" />
            {createMutation.isPending ? 'Sending...' : 'Send Request'}
          </Button>
        </ModalFooter>
      </form>
    </Modal>
  );
};

export default VendorRequestForm;
