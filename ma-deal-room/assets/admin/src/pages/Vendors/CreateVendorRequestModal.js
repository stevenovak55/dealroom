import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { Modal } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { useCreateVendorRequest } from '@/api/queries/useVendorRequests';
import { useGetTransactions } from '@/api/queries/useTransactions';
import { useGetTasks } from '@/api/queries/useTasks';
import { showToast } from '@/utils/toast';
const vendorTypeOptions = [
    { value: 'fire_dept', label: 'Fire Department' },
    { value: 'septic_inspector', label: 'Septic Inspector' },
    { value: 'hoa_manager', label: 'HOA Manager' },
    { value: 'title_company', label: 'Title Company' },
    { value: 'appraiser', label: 'Appraiser' },
    { value: 'inspector', label: 'Inspector' },
    { value: 'other', label: 'Other' },
];
export const CreateVendorRequestModal = ({ isOpen, onClose, transactionId, taskId, }) => {
    const [selectedTransactionId, setSelectedTransactionId] = useState(transactionId);
    const { register, handleSubmit, formState: { errors }, watch, reset } = useForm({
        defaultValues: {
            transaction_id: transactionId,
            task_id: taskId,
        },
    });
    const createMutation = useCreateVendorRequest();
    const { data: transactions } = useGetTransactions({ status: 'active' });
    const { data: tasks } = useGetTasks({ transaction_id: selectedTransactionId });
    const watchTransactionId = watch('transaction_id');
    // Update selected transaction when form value changes
    if (watchTransactionId && watchTransactionId !== selectedTransactionId) {
        setSelectedTransactionId(watchTransactionId);
    }
    const onSubmit = async (data) => {
        try {
            await createMutation.mutateAsync({
                task_id: data.task_id,
                transaction_id: data.transaction_id,
                vendor_type: data.vendor_type,
                vendor_email: data.vendor_email,
                vendor_phone: data.vendor_phone || undefined,
            });
            showToast.success('Vendor request sent successfully');
            reset();
            onClose();
        }
        catch (error) {
            showToast.error('Failed to send vendor request');
        }
    };
    const transactionOptions = transactions?.data?.map((t) => ({
        value: t.transaction_id.toString(),
        label: t.property_address,
    })) || [];
    const taskOptions = tasks?.data?.map((t) => ({
        value: t.id.toString(),
        label: t.title,
    })) || [];
    return (_jsx(Modal, { isOpen: isOpen, onClose: onClose, title: "Send Vendor Request", children: _jsxs("form", { onSubmit: handleSubmit(onSubmit), className: "space-y-4", children: [!transactionId && (_jsx(Select, { label: "Transaction", options: transactionOptions, error: errors.transaction_id?.message, ...register('transaction_id', {
                        required: 'Transaction is required',
                        valueAsNumber: true,
                    }) })), !taskId && (_jsx(Select, { label: "Task", options: taskOptions, error: errors.task_id?.message, disabled: !selectedTransactionId, ...register('task_id', {
                        required: 'Task is required',
                        valueAsNumber: true,
                    }) })), _jsx(Select, { label: "Vendor Type", options: vendorTypeOptions, error: errors.vendor_type?.message, ...register('vendor_type', { required: 'Vendor type is required' }) }), _jsx(Input, { label: "Vendor Email", type: "email", placeholder: "vendor@example.com", error: errors.vendor_email?.message, ...register('vendor_email', {
                        required: 'Email is required',
                        pattern: {
                            value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
                            message: 'Invalid email address',
                        },
                    }) }), _jsx(Input, { label: "Vendor Phone (Optional)", type: "tel", placeholder: "(555) 555-5555", error: errors.vendor_phone?.message, ...register('vendor_phone') }), _jsxs("div", { className: "bg-blue-50 border border-blue-200 rounded-lg p-4", children: [_jsx("p", { className: "text-sm text-blue-900", children: "The vendor will receive an email with a secure link to access their portal where they can:" }), _jsxs("ul", { className: "text-sm text-blue-800 mt-2 ml-4 list-disc space-y-1", children: [_jsx("li", { children: "View task details" }), _jsx("li", { children: "Schedule an appointment" }), _jsx("li", { children: "Upload completion documents" }), _jsx("li", { children: "Provide notes and updates" })] })] }), _jsxs("div", { className: "flex items-center justify-end gap-3 pt-4", children: [_jsx(Button, { type: "button", variant: "secondary", onClick: onClose, children: "Cancel" }), _jsx(Button, { type: "submit", disabled: createMutation.isPending, children: createMutation.isPending ? 'Sending...' : 'Send Request' })] })] }) }));
};
