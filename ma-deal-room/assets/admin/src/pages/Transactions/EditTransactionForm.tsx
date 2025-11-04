import { useNavigate, useParams, Link } from 'react-router-dom';
import { ArrowLeft, Save } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { useGetTransaction, useUpdateTransaction } from '@/api/queries/useTransactions';
import { Button } from '@/components/shared/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { PageLoader } from '@/components/shared/Loader';
import type { UpdateTransactionInput } from '@/api/types';

const propertyTypeOptions = [
  { value: 'SFH', label: 'Single Family Home' },
  { value: 'Condo', label: 'Condo' },
  { value: 'Multifamily', label: 'Multifamily' },
  { value: 'Land', label: 'Land' },
  { value: 'Commercial', label: 'Commercial' },
];

const statusOptions = [
  { value: 'prospect', label: 'Prospect' },
  { value: 'listing_active', label: 'Listing Active' },
  { value: 'under_agreement', label: 'Under Agreement' },
  { value: 'closed', label: 'Closed' },
  { value: 'cancelled', label: 'Cancelled' },
];

export const EditTransactionForm = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const transactionId = parseInt(id || '0');

  const { data: transaction, isLoading } = useGetTransaction(transactionId);
  const updateMutation = useUpdateTransaction(transactionId);

  const { register, handleSubmit, formState: { errors } } = useForm<UpdateTransactionInput>({
    values: transaction ? {
      property_address: transaction.property_address,
      property_city: transaction.property_city,
      property_state: transaction.property_state,
      property_zip: transaction.property_zip,
      property_type: transaction.property_type,
      sale_price: transaction.sale_price,
      listing_date: transaction.listing_date,
      offer_accepted_date: transaction.offer_accepted_date,
      ps_agreement_date: transaction.ps_agreement_date,
      loan_commitment_date: transaction.loan_commitment_date,
      closing_date: transaction.closing_date,
      status: transaction.status,
      notes: transaction.notes,
    } : undefined,
  });

  const onSubmit = async (data: UpdateTransactionInput) => {
    try {
      await updateMutation.mutateAsync(data);
      navigate(`/transactions/${transactionId}`);
    } catch (error) {
      console.error('Failed to update transaction:', error);
      alert('Failed to update transaction. Please try again.');
    }
  };

  if (isLoading) {
    return <PageLoader />;
  }

  if (!transaction) {
    return <div>Transaction not found</div>;
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link to={`/transactions/${transactionId}`}>
          <Button variant="ghost" size="sm">
            <ArrowLeft className="h-4 w-4 mr-2" />
            Back
          </Button>
        </Link>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Edit Transaction</h1>
          <p className="text-sm text-gray-500 mt-1">{transaction.property_address}</p>
        </div>
      </div>

      {/* Form */}
      <form onSubmit={handleSubmit(onSubmit)}>
        <Card>
          <CardHeader>
            <CardTitle>Transaction Details</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-6">
              {/* Basic Information */}
              <div>
                <h3 className="font-medium text-gray-900 mb-4">Basic Information</h3>
                <div className="space-y-4">
                  <Input
                    label="Property Address"
                    placeholder="123 Main St"
                    {...register('property_address')}
                    error={errors.property_address?.message}
                  />
                  <div className="grid grid-cols-3 gap-4">
                    <Input
                      label="City"
                      placeholder="Boston"
                      {...register('property_city')}
                    />
                    <Input
                      label="State"
                      placeholder="MA"
                      {...register('property_state')}
                    />
                    <Input
                      label="ZIP Code"
                      placeholder="02101"
                      {...register('property_zip')}
                    />
                  </div>
                  <Select
                    label="Property Type"
                    options={propertyTypeOptions}
                    {...register('property_type')}
                  />
                  <Select
                    label="Status"
                    options={statusOptions}
                    {...register('status')}
                  />
                </div>
              </div>

              {/* Financial Information */}
              <div>
                <h3 className="font-medium text-gray-900 mb-4">Financial Information</h3>
                <Input
                  label="Sale Price"
                  type="number"
                  placeholder="500000"
                  {...register('sale_price', { valueAsNumber: true })}
                />
              </div>

              {/* Key Dates */}
              <div>
                <h3 className="font-medium text-gray-900 mb-4">Key Dates</h3>
                <div className="space-y-4">
                  <Input
                    label="Listing Signed Date"
                    type="date"
                    {...register('listing_date')}
                    helperText="When listing agreement was signed with seller"
                  />
                  <Input
                    label="Offer Acceptance Date"
                    type="date"
                    {...register('offer_accepted_date')}
                    helperText="When seller accepted buyer's offer"
                  />
                  <Input
                    label="P&S Agreement Date"
                    type="date"
                    {...register('ps_agreement_date')}
                    helperText="Purchase & Sale agreement signing date"
                  />
                  <Input
                    label="Loan Commitment Date"
                    type="date"
                    {...register('loan_commitment_date')}
                    helperText="When lender commits to financing"
                  />
                  <Input
                    label="Closing Date"
                    type="date"
                    {...register('closing_date')}
                    helperText="Scheduled closing date"
                  />
                </div>
              </div>

              {/* Notes */}
              <div>
                <h3 className="font-medium text-gray-900 mb-4">Notes</h3>
                <textarea
                  {...register('notes')}
                  rows={4}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                  placeholder="Add any additional notes about this transaction..."
                />
              </div>
            </div>

            {/* Action Buttons */}
            <div className="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
              <Button
                type="button"
                variant="secondary"
                onClick={() => navigate(`/transactions/${transactionId}`)}
              >
                Cancel
              </Button>
              <Button
                type="submit"
                variant="primary"
                isLoading={updateMutation.isPending}
              >
                <Save className="h-4 w-4 mr-2" />
                Save Changes
              </Button>
            </div>
          </CardContent>
        </Card>
      </form>
    </div>
  );
};
