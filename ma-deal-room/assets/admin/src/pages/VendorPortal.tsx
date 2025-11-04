import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import toast from 'react-hot-toast';
import {
  createVendorPortalAPI,
  VendorPortalData,
  ScheduleData,
  AvailabilityWindow,
  CompletionData,
  MessageData,
} from '../api/vendorPortalClient';
import VendorDashboard from '../components/vendor/VendorDashboard';
import SchedulingForm from '../components/vendor/SchedulingForm';
import DocumentUploader from '../components/vendor/DocumentUploader';
import MessageThread from '../components/vendor/MessageThread';
import CompletionForm from '../components/vendor/CompletionForm';
import { AlertCircle, Loader2 } from 'lucide-react';

export default function VendorPortal() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const [api, setApi] = useState<ReturnType<typeof createVendorPortalAPI> | null>(null);
  const queryClient = useQueryClient();

  useEffect(() => {
    if (token) {
      setApi(createVendorPortalAPI(token));
    }
  }, [token]);

  // Fetch portal data
  const {
    data: portalData,
    isLoading,
    error,
    refetch,
  } = useQuery<VendorPortalData>({
    queryKey: ['vendorPortal', token],
    queryFn: () => api!.getPortalData(),
    enabled: !!api && !!token,
    refetchInterval: 30000, // Refetch every 30 seconds for real-time updates
  });

  // Schedule appointment mutation
  const scheduleMutation = useMutation({
    mutationFn: (data: ScheduleData) => api!.scheduleAppointment(data),
    onSuccess: () => {
      toast.success('Appointment scheduled successfully!');
      refetch();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to schedule appointment');
    },
  });

  // Submit availability mutation
  const availabilityMutation = useMutation({
    mutationFn: (windows: AvailabilityWindow[]) => api!.submitAvailability(windows),
    onSuccess: () => {
      toast.success('Availability submitted successfully!');
      refetch();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to submit availability');
    },
  });

  // Upload document mutation
  const uploadMutation = useMutation({
    mutationFn: (file: File) => api!.uploadDocument(file),
    onSuccess: () => {
      toast.success('Document uploaded successfully!');
      refetch();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to upload document');
    },
  });

  // Complete request mutation
  const completeMutation = useMutation({
    mutationFn: (data: CompletionData) => api!.completeRequest(data),
    onSuccess: () => {
      toast.success('Request marked as completed!');
      refetch();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to complete request');
    },
  });

  // Send message mutation
  const sendMessageMutation = useMutation({
    mutationFn: (data: MessageData) => api!.sendMessage(data),
    onSuccess: () => {
      toast.success('Message sent!');
      refetch();
    },
    onError: (error: any) => {
      toast.error(error.response?.data?.message || 'Failed to send message');
    },
  });

  // Mark messages as read mutation
  const markReadMutation = useMutation({
    mutationFn: () => api!.markMessagesAsRead(),
    onSuccess: () => {
      refetch();
    },
  });

  if (!token) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-xl p-8 max-w-md w-full">
          <div className="flex items-center gap-3 text-red-600 mb-4">
            <AlertCircle className="w-6 h-6" />
            <h2 className="text-xl font-semibold">Invalid Access</h2>
          </div>
          <p className="text-gray-600">
            No valid token found. Please use the link provided in your email.
          </p>
        </div>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
        <div className="flex flex-col items-center gap-4">
          <Loader2 className="w-12 h-12 animate-spin text-blue-600" />
          <p className="text-gray-600 font-medium">Loading your portal...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-xl p-8 max-w-md w-full">
          <div className="flex items-center gap-3 text-red-600 mb-4">
            <AlertCircle className="w-6 h-6" />
            <h2 className="text-xl font-semibold">Access Denied</h2>
          </div>
          <p className="text-gray-600 mb-4">
            {(error as any).response?.data?.message ||
              'This link has expired or is invalid. Please contact the agent for a new link.'}
          </p>
          <p className="text-sm text-gray-500">
            If you believe this is an error, please reach out to your real estate agent.
          </p>
        </div>
      </div>
    );
  }

  if (!portalData) {
    return null;
  }

  const { vendor_request, transaction, messages, availability, rating } = portalData;
  const isCompleted = vendor_request.status === 'completed';
  const isExpired = vendor_request.status === 'expired';
  const isScheduled = vendor_request.status === 'scheduled';

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 shadow-sm">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl sm:text-3xl font-bold text-gray-900">
                Vendor Portal
              </h1>
              <p className="text-sm sm:text-base text-gray-600 mt-1">
                {transaction.property_address}, {transaction.property_city}
              </p>
            </div>
            <div className="flex items-center gap-2">
              <span className={`
                px-3 py-1 rounded-full text-xs sm:text-sm font-medium
                ${vendor_request.status === 'completed' ? 'bg-green-100 text-green-800' :
                  vendor_request.status === 'scheduled' ? 'bg-blue-100 text-blue-800' :
                  vendor_request.status === 'opened' ? 'bg-yellow-100 text-yellow-800' :
                  vendor_request.status === 'expired' ? 'bg-red-100 text-red-800' :
                  'bg-gray-100 text-gray-800'
                }
              `}>
                {vendor_request.status.charAt(0).toUpperCase() + vendor_request.status.slice(1)}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column - Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Dashboard Overview */}
            <VendorDashboard
              vendorRequest={vendor_request}
              transaction={transaction}
              rating={rating}
            />

            {/* Scheduling Section */}
            {!isCompleted && !isExpired && (
              <SchedulingForm
                vendorRequest={vendor_request}
                availability={availability}
                onSchedule={(data) => scheduleMutation.mutate(data)}
                onSubmitAvailability={(windows) => availabilityMutation.mutate(windows)}
                isSubmitting={scheduleMutation.isPending || availabilityMutation.isPending}
              />
            )}

            {/* Document Upload */}
            {isScheduled && !isCompleted && !isExpired && (
              <DocumentUploader
                vendorRequest={vendor_request}
                onUpload={(file) => uploadMutation.mutate(file)}
                isUploading={uploadMutation.isPending}
              />
            )}

            {/* Completion Form */}
            {isScheduled && !isCompleted && !isExpired && vendor_request.document_url && (
              <CompletionForm
                vendorRequest={vendor_request}
                onComplete={(data) => completeMutation.mutate(data)}
                isSubmitting={completeMutation.isPending}
              />
            )}

            {/* Rating Display */}
            {isCompleted && rating && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                  Performance Rating
                </h3>
                <div className="flex items-center gap-2 mb-4">
                  <div className="text-3xl font-bold text-yellow-500">
                    {'★'.repeat(rating.rating)}{'☆'.repeat(5 - rating.rating)}
                  </div>
                  <span className="text-gray-600">
                    {rating.rating}/5
                  </span>
                </div>
                {rating.review && (
                  <p className="text-gray-700 italic">"{rating.review}"</p>
                )}
              </div>
            )}
          </div>

          {/* Right Column - Messages */}
          <div className="lg:col-span-1">
            <MessageThread
              messages={messages.messages}
              unreadCount={messages.unread_vendor}
              vendorRequest={vendor_request}
              onSendMessage={(data) => sendMessageMutation.mutate(data)}
              onMarkAsRead={() => markReadMutation.mutate()}
              isSending={sendMessageMutation.isPending}
            />
          </div>
        </div>
      </div>
    </div>
  );
}
