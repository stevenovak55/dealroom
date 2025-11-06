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

interface CreateVendorRequestModalProps {
  isOpen: boolean;
  onClose: () => void;
  transactionId?: number;
  taskId?: number;
}

interface FormData {
  transaction_id: number;
  task_id: number;
  vendor_type: string;
  vendor_email: string;
  vendor_phone: string;
}

const vendorTypeOptions = [
  { value: 'fire_dept', label: 'Fire Department' },
  { value: 'septic_inspector', label: 'Septic Inspector' },
  { value: 'hoa_manager', label: 'HOA Manager' },
  { value: 'title_company', label: 'Title Company' },
  { value: 'appraiser', label: 'Appraiser' },
  { value: 'inspector', label: 'Inspector' },
  { value: 'other', label: 'Other' },
];

export const CreateVendorRequestModal = ({
  isOpen,
  onClose,
  transactionId,
  taskId,
}: CreateVendorRequestModalProps) => {
  const [selectedTransactionId, setSelectedTransactionId] = useState(transactionId);

  const { register, handleSubmit, formState: { errors }, watch, reset } = useForm<FormData>({
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

  const onSubmit = async (data: FormData) => {
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
    } catch (error) {
      showToast.error('Failed to send vendor request');
    }
  };

  const transactionOptions =
    transactions?.data?.map((t) => ({
      value: t.transaction_id.toString(),
      label: t.property_address,
    })) || [];

  const taskOptions =
    tasks?.data?.map((t) => ({
      value: t.id.toString(),
      label: t.title,
    })) || [];

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Send Vendor Request">
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
        {!transactionId && (
          <Select
            label="Transaction"
            options={transactionOptions}
            error={errors.transaction_id?.message}
            {...register('transaction_id', {
              required: 'Transaction is required',
              valueAsNumber: true,
            })}
          />
        )}

        {!taskId && (
          <Select
            label="Task"
            options={taskOptions}
            error={errors.task_id?.message}
            disabled={!selectedTransactionId}
            {...register('task_id', {
              required: 'Task is required',
              valueAsNumber: true,
            })}
          />
        )}

        <Select
          label="Vendor Type"
          options={vendorTypeOptions}
          error={errors.vendor_type?.message}
          {...register('vendor_type', { required: 'Vendor type is required' })}
        />

        <Input
          label="Vendor Email"
          type="email"
          placeholder="vendor@example.com"
          error={errors.vendor_email?.message}
          {...register('vendor_email', {
            required: 'Email is required',
            pattern: {
              value: /^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}$/i,
              message: 'Invalid email address',
            },
          })}
        />

        <Input
          label="Vendor Phone (Optional)"
          type="tel"
          placeholder="(555) 555-5555"
          error={errors.vendor_phone?.message}
          {...register('vendor_phone')}
        />

        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p className="text-sm text-blue-900">
            The vendor will receive an email with a secure link to access their portal where they can:
          </p>
          <ul className="text-sm text-blue-800 mt-2 ml-4 list-disc space-y-1">
            <li>View task details</li>
            <li>Schedule an appointment</li>
            <li>Upload completion documents</li>
            <li>Provide notes and updates</li>
          </ul>
        </div>

        <div className="flex items-center justify-end gap-3 pt-4">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button type="submit" disabled={createMutation.isPending}>
            {createMutation.isPending ? 'Sending...' : 'Send Request'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
