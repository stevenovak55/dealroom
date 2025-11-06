import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
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
    const [selectedTransaction] = useState();
    const { data: vendorRequests, isLoading } = useGetVendorRequests(selectedTransaction);
    const resendMutation = useResendVendorRequest();
    const cancelMutation = useCancelVendorRequest();
    const handleResend = async (id) => {
        try {
            await resendMutation.mutateAsync(id);
            showToast.success('Vendor request resent successfully');
        }
        catch (error) {
            showToast.error('Failed to resend vendor request');
        }
    };
    const handleCancel = async (id) => {
        if (!confirm('Are you sure you want to cancel this vendor request?')) {
            return;
        }
        try {
            await cancelMutation.mutateAsync(id);
            showToast.success('Vendor request cancelled');
        }
        catch (error) {
            showToast.error('Failed to cancel vendor request');
        }
    };
    if (isLoading) {
        return (_jsx("div", { className: "flex items-center justify-center h-64", children: _jsx(Loader, {}) }));
    }
    return (_jsxs("div", { className: "p-6 max-w-7xl mx-auto", children: [_jsxs("div", { className: "flex items-center justify-between mb-6", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-3xl font-bold text-gray-900", children: "Vendor Requests" }), _jsx("p", { className: "text-gray-600 mt-1", children: "Manage external vendor tasks and track their progress" })] }), _jsxs(Button, { onClick: () => setIsCreateModalOpen(true), size: "lg", children: [_jsx(Plus, { className: "h-5 w-5 mr-2" }), "Send Request"] })] }), vendorRequests && vendorRequests.length > 0 && (_jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6", children: [_jsx(Card, { children: _jsxs(CardContent, { className: "pt-5", children: [_jsx("div", { className: "text-sm font-medium text-gray-600", children: "Total Requests" }), _jsx("div", { className: "text-2xl font-bold text-gray-900 mt-1", children: vendorRequests.length })] }) }), _jsx(Card, { children: _jsxs(CardContent, { className: "pt-5", children: [_jsx("div", { className: "text-sm font-medium text-gray-600", children: "Pending" }), _jsx("div", { className: "text-2xl font-bold text-yellow-600 mt-1", children: vendorRequests.filter((r) => ['sent', 'opened'].includes(r.status)).length })] }) }), _jsx(Card, { children: _jsxs(CardContent, { className: "pt-5", children: [_jsx("div", { className: "text-sm font-medium text-gray-600", children: "Scheduled" }), _jsx("div", { className: "text-2xl font-bold text-purple-600 mt-1", children: vendorRequests.filter((r) => r.status === 'scheduled').length })] }) }), _jsx(Card, { children: _jsxs(CardContent, { className: "pt-5", children: [_jsx("div", { className: "text-sm font-medium text-gray-600", children: "Completed" }), _jsx("div", { className: "text-2xl font-bold text-green-600 mt-1", children: vendorRequests.filter((r) => r.status === 'completed').length })] }) })] })), !vendorRequests || vendorRequests.length === 0 ? (_jsx(Card, { children: _jsx(CardContent, { className: "py-12", children: _jsx(EmptyState, { title: "No Vendor Requests", description: "Get started by sending a request to an external vendor", action: {
                            label: 'Send Request',
                            onClick: () => setIsCreateModalOpen(true),
                        } }) }) })) : (_jsx("div", { className: "space-y-4", children: vendorRequests.map((request) => {
                    const statusInfo = statusConfig[request.status];
                    const StatusIcon = statusInfo.icon;
                    return (_jsx(Card, { children: _jsx(CardContent, { className: "p-6", children: _jsxs("div", { className: "flex items-start justify-between", children: [_jsxs("div", { className: "flex-1", children: [_jsxs("div", { className: "flex items-center gap-3 mb-2", children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: vendorTypeLabels[request.vendor_type] }), _jsxs(Badge, { variant: statusInfo.color, children: [_jsx(StatusIcon, { className: "h-3 w-3 mr-1" }), statusInfo.label] })] }), _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4 mt-4", children: [_jsxs("div", { children: [_jsx("div", { className: "text-sm text-gray-600", children: "Vendor Contact" }), _jsx("div", { className: "text-sm font-medium text-gray-900 mt-1", children: request.vendor_email }), request.vendor_phone && (_jsx("div", { className: "text-sm text-gray-600 mt-1", children: request.vendor_phone }))] }), _jsxs("div", { children: [_jsx("div", { className: "text-sm text-gray-600", children: "Transaction" }), _jsx("div", { className: "text-sm font-medium text-gray-900 mt-1", children: request.transaction?.property_address || `Transaction #${request.transaction_id}` }), request.task && (_jsx("div", { className: "text-sm text-gray-600 mt-1", children: request.task.title }))] }), request.scheduled_date && (_jsxs("div", { children: [_jsx("div", { className: "text-sm text-gray-600", children: "Scheduled Date" }), _jsxs("div", { className: "text-sm font-medium text-gray-900 mt-1", children: [format(new Date(request.scheduled_date), 'MMM d, yyyy'), request.scheduled_time && ` at ${request.scheduled_time}`] })] })), request.last_opened_at && (_jsxs("div", { children: [_jsx("div", { className: "text-sm text-gray-600", children: "Last Opened" }), _jsx("div", { className: "text-sm font-medium text-gray-900 mt-1", children: format(new Date(request.last_opened_at), 'MMM d, yyyy h:mm a') })] }))] }), request.completion_notes && (_jsxs("div", { className: "mt-4 p-3 bg-gray-50 rounded-lg", children: [_jsx("div", { className: "text-sm font-medium text-gray-700", children: "Completion Notes" }), _jsx("div", { className: "text-sm text-gray-600 mt-1", children: request.completion_notes })] })), _jsxs("div", { className: "flex items-center gap-2 mt-4 text-xs text-gray-500", children: [_jsxs("span", { children: ["Created ", format(new Date(request.created_at), 'MMM d, yyyy')] }), request.status !== 'completed' && request.token_expires_at && (_jsxs(_Fragment, { children: [_jsx("span", { children: "\u2022" }), _jsxs("span", { children: ["Expires ", format(new Date(request.token_expires_at), 'MMM d, yyyy')] })] }))] })] }), _jsxs("div", { className: "flex items-center gap-2 ml-4", children: [['sent', 'opened', 'expired'].includes(request.status) && (_jsxs(Button, { variant: "secondary", size: "sm", onClick: () => handleResend(request.id), disabled: resendMutation.isPending, children: [_jsx(Mail, { className: "h-4 w-4 mr-1" }), "Resend"] })), !['completed', 'cancelled'].includes(request.status) && (_jsxs(Button, { variant: "secondary", size: "sm", onClick: () => handleCancel(request.id), disabled: cancelMutation.isPending, children: [_jsx(X, { className: "h-4 w-4 mr-1" }), "Cancel"] }))] })] }) }) }, request.id));
                }) })), _jsx(CreateVendorRequestModal, { isOpen: isCreateModalOpen, onClose: () => setIsCreateModalOpen(false) })] }));
};
