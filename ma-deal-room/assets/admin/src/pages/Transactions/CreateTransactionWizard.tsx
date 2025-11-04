import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ArrowLeft, ArrowRight, Check, Search, RefreshCw, Database } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { useCreateTransaction } from '@/api/queries/useTransactions';
import { useGetTemplates } from '@/api/queries/useTemplates';
import { Button } from '@/components/shared/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { mlsService } from '@/api/mlsService';
import type { CreateTransactionInput } from '@/api/types';

const propertyTypeOptions = [
  { value: 'SFH', label: 'Single Family Home' },
  { value: 'Condo', label: 'Condo' },
  { value: 'Multifamily', label: 'Multifamily' },
  { value: 'Land', label: 'Land' },
  { value: 'Commercial', label: 'Commercial' },
];

const transactionSideOptions = [
  { value: 'listing', label: 'Listing Side (Representing Seller)' },
  { value: 'buyer', label: 'Buyer Side (Representing Buyer)' },
];

const steps = [
  { id: 1, name: 'Property Details', description: 'Basic property information' },
  { id: 2, name: 'Choose Template', description: 'Select a checklist template' },
  { id: 3, name: 'Review', description: 'Review and create' },
];

export const CreateTransactionWizard = () => {
  const navigate = useNavigate();
  const [currentStep, setCurrentStep] = useState(1);
  const [formData, setFormData] = useState<Partial<CreateTransactionInput>>({
    property_state: 'MA',
    transaction_side: 'listing',
  });
  const [importMode, setImportMode] = useState<'manual' | 'mls'>('manual');
  const [mlsNumber, setMlsNumber] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const [mlsSearchError, setMlsSearchError] = useState<string | null>(null);

  const createMutation = useCreateTransaction();

  const { register, handleSubmit, formState: { errors }, watch, reset } = useForm<CreateTransactionInput>({
    defaultValues: {
      transaction_side: 'listing',
      property_state: 'MA',
    }
  });

  // Watch form values for dynamic template filtering
  const watchedTransactionSide = watch('transaction_side');
  const watchedPropertyType = watch('property_type');

  // Fetch templates with dynamic filtering
  const { data: templatesData } = useGetTemplates({
    transaction_side: watchedTransactionSide,
    property_type: watchedPropertyType,
  });

  const templates = templatesData?.data || [];

  const nextStep = () => setCurrentStep((prev) => Math.min(prev + 1, steps.length));
  const prevStep = () => setCurrentStep((prev) => Math.max(prev - 1, 1));

  const handleStepSubmit = (data: any) => {
    console.log('Form submitted with data:', data);
    setFormData((prev) => ({ ...prev, ...data }));
    if (currentStep < steps.length) {
      nextStep();
    }
  };

  const handleFinalSubmit = async () => {
    try {
      const result = await createMutation.mutateAsync(formData as CreateTransactionInput);
      navigate(`/transactions/${result.transaction_id}`);
    } catch (error) {
      console.error('Failed to create transaction:', error);
    }
  };

  const handleMLSSearch = async () => {
    if (!mlsNumber.trim()) {
      setMlsSearchError('Please enter an MLS number');
      return;
    }

    setIsSearching(true);
    setMlsSearchError(null);

    try {
      const response = await mlsService.search({ mls_number: mlsNumber });

      if (response.success && response.data.listings && response.data.listings.length > 0) {
        const listing = response.data.listings[0];

        // Map MLS property subtype to our property type options
        // Try both property_subtype and property_sub_type (backend uses snake_case)
        const propertySubType = listing.property_subtype || listing.property_sub_type;
        let mappedPropertyType: string | undefined = undefined;
        if (propertySubType) {
          const typeMapping: Record<string, string> = {
            'Single Family Residence': 'SFH',
            'Single Family': 'SFH',
            'Condominium': 'Condo',
            'Condo': 'Condo',
            'Multi-Family': 'Multifamily',
            'Multifamily': 'Multifamily',
            'Land': 'Land',
            'Commercial': 'Commercial',
          };
          mappedPropertyType = typeMapping[propertySubType];
        }

        // Prepare the form data from MLS listing
        const mlsFormData = {
          transaction_side: 'listing' as const,
          property_address: listing.address || '',
          property_city: listing.city || '',
          property_state: listing.state || 'MA',
          property_zip: listing.zip || '',
          property_type: mappedPropertyType as any,
          sale_price: listing.price as any,
          mls_number: listing.mls_number,
        };

        // Update React Hook Form using reset() - more reliable than setValue()
        reset(mlsFormData, { keepDefaultValues: false });

        // Update formData state for the success message and MLS number display
        setFormData(prev => ({ ...prev, ...mlsFormData }));

        setMlsSearchError(null);
      } else {
        setMlsSearchError('No property found with that MLS number');
      }
    } catch (err: any) {
      setMlsSearchError(err.message || 'Failed to search MLS');
    } finally {
      setIsSearching(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="sm" onClick={() => navigate('/transactions')}>
          <ArrowLeft className="h-4 w-4 mr-2" />
          Back
        </Button>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Create New Transaction</h1>
          <p className="text-sm text-gray-500 mt-1">Follow the steps to create a transaction</p>
        </div>
      </div>

      {/* Progress steps */}
      <div className="flex items-center justify-between">
        {steps.map((step, index) => (
          <div key={step.id} className="flex items-center flex-1">
            <div className="flex items-center">
              <div
                className={`flex items-center justify-center w-10 h-10 rounded-full border-2 ${
                  currentStep > step.id
                    ? 'bg-primary-600 border-primary-600'
                    : currentStep === step.id
                    ? 'border-primary-600 text-primary-600'
                    : 'border-gray-300 text-gray-400'
                }`}
              >
                {currentStep > step.id ? (
                  <Check className="h-5 w-5 text-white" />
                ) : (
                  <span className="text-sm font-medium">{step.id}</span>
                )}
              </div>
              <div className="ml-3">
                <p className={`text-sm font-medium ${currentStep >= step.id ? 'text-gray-900' : 'text-gray-400'}`}>
                  {step.name}
                </p>
                <p className="text-xs text-gray-500">{step.description}</p>
              </div>
            </div>
            {index < steps.length - 1 && (
              <div className={`flex-1 h-0.5 mx-4 ${currentStep > step.id ? 'bg-primary-600' : 'bg-gray-300'}`} />
            )}
          </div>
        ))}
      </div>

      {/* Step content */}
      <Card>
        <CardHeader>
          <CardTitle>{steps[currentStep - 1].name}</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit(handleStepSubmit, (errors) => {
            console.log('Form validation errors:', errors);
          })}>
            {currentStep === 1 && (
              <div className="space-y-4">
                <Select
                  label="Transaction Side"
                  options={transactionSideOptions}
                  defaultValue={formData.transaction_side}
                  {...register('transaction_side', { required: true })}
                  helperText="Are you representing the seller (listing) or the buyer?"
                  required
                />

                {/* Import Mode Selector */}
                <div className="border-t border-b border-gray-200 py-4 my-4">
                  <label className="text-sm font-medium text-gray-700 mb-3 block">
                    How would you like to add property details?
                  </label>
                  <div className="grid grid-cols-2 gap-3">
                    <button
                      type="button"
                      onClick={() => setImportMode('manual')}
                      className={`p-4 border-2 rounded-lg text-left transition-colors ${
                        importMode === 'manual'
                          ? 'border-primary-600 bg-primary-50'
                          : 'border-gray-200 hover:border-gray-300'
                      }`}
                    >
                      <div className="font-medium text-gray-900">Manual Entry</div>
                      <div className="text-sm text-gray-500 mt-1">Enter property details manually</div>
                    </button>
                    <button
                      type="button"
                      onClick={() => setImportMode('mls')}
                      className={`p-4 border-2 rounded-lg text-left transition-colors ${
                        importMode === 'mls'
                          ? 'border-primary-600 bg-primary-50'
                          : 'border-gray-200 hover:border-gray-300'
                      }`}
                    >
                      <div className="flex items-center gap-2 font-medium text-gray-900">
                        <Database className="h-4 w-4" />
                        Import from MLS
                      </div>
                      <div className="text-sm text-gray-500 mt-1">Search by MLS number</div>
                    </button>
                  </div>
                </div>

                {/* MLS Search Section */}
                {importMode === 'mls' && (
                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-3">
                    <label className="block text-sm font-medium text-gray-900">
                      MLS Number
                    </label>
                    <div className="flex gap-2">
                      <input
                        type="text"
                        value={mlsNumber}
                        onChange={(e) => setMlsNumber(e.target.value)}
                        placeholder="e.g., 73429927"
                        className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), handleMLSSearch())}
                      />
                      <button
                        type="button"
                        onClick={handleMLSSearch}
                        disabled={isSearching}
                        className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-2"
                      >
                        {isSearching ? (
                          <>
                            <RefreshCw className="h-4 w-4 animate-spin" />
                            Searching...
                          </>
                        ) : (
                          <>
                            <Search className="h-4 w-4" />
                            Search
                          </>
                        )}
                      </button>
                    </div>
                    {mlsSearchError && (
                      <p className="text-sm text-red-600">{mlsSearchError}</p>
                    )}
                    {formData.mls_number && !mlsSearchError && (
                      <p className="text-sm text-green-600">
                        ✓ Property found! Form fields have been auto-filled. You can edit them below if needed.
                      </p>
                    )}
                  </div>
                )}

                <Input
                  label="Property Address"
                  placeholder="123 Main St"
                  {...register('property_address', { required: true })}
                  error={errors.property_address ? 'Address is required' : undefined}
                  required
                />
                <div className="grid grid-cols-3 gap-4">
                  <Input
                    label="City"
                    placeholder="Boston"
                    {...register('property_city', { required: true })}
                    required
                  />
                  <Input
                    label="State"
                    placeholder="MA"
                    {...register('property_state', { required: true })}
                    required
                  />
                  <Input
                    label="ZIP Code"
                    placeholder="02101"
                    {...register('property_zip', { required: true })}
                    required
                  />
                </div>
                <Select
                  label="Property Type"
                  options={propertyTypeOptions}
                  {...register('property_type', { required: true })}
                  required
                />
                <Input
                  label="Sale Price"
                  type="number"
                  placeholder="500000"
                  {...register('sale_price')}
                />

                {/* Hidden field for MLS number - managed by setValue() */}
                <input type="hidden" {...register('mls_number')} />

                {formData.mls_number && (
                  <div className="bg-green-50 border border-green-200 rounded-lg p-3">
                    <p className="text-sm text-green-800">
                      <strong>MLS Number:</strong> {formData.mls_number}
                    </p>
                  </div>
                )}
              </div>
            )}

            {currentStep === 2 && (
              <div className="space-y-4">
                <div className="mb-4">
                  <p className="text-sm text-gray-600">
                    Select a template to auto-generate tasks for this transaction
                  </p>
                  {watchedPropertyType && watchedTransactionSide && (
                    <p className="text-sm text-primary-600 mt-2 font-medium">
                      {templates.length} template{templates.length !== 1 ? 's' : ''} available for {' '}
                      {watchedTransactionSide === 'listing' ? 'listing-side' : 'buyer-side'} {' '}
                      {watchedPropertyType} transactions
                    </p>
                  )}
                  {templates.length === 0 && watchedPropertyType && (
                    <p className="text-sm text-orange-600 mt-2">
                      No templates found. Please check your property type and transaction side selections.
                    </p>
                  )}
                </div>
                <div className="grid grid-cols-1 gap-3">
                  {templates.map((template) => (
                    <label
                      key={template.template_id}
                      className="flex items-start p-4 border-2 rounded-lg cursor-pointer hover:border-primary-300 transition-colors"
                    >
                      <input
                        type="radio"
                        value={template.template_id}
                        defaultChecked={formData.template_id === template.template_id.toString()}
                        {...register('template_id')}
                        className="mt-1"
                      />
                      <div className="ml-3 flex-1">
                        <p className="font-medium text-gray-900">{template.name}</p>
                        <p className="text-sm text-gray-500 mt-1">{template.description}</p>
                        <p className="text-xs text-gray-400 mt-2">{template.task_count} tasks</p>
                      </div>
                    </label>
                  ))}
                </div>
              </div>
            )}

            {currentStep === 3 && (
              <div className="space-y-6">
                <div>
                  <h3 className="font-medium text-gray-900 mb-4">Review Transaction Details</h3>
                  <dl className="space-y-3">
                    <div>
                      <dt className="text-sm text-gray-500">Transaction Side</dt>
                      <dd className="text-sm font-medium text-gray-900 mt-1">
                        {formData.transaction_side === 'listing' ? 'Listing (Seller) Side' : 'Buyer Side'}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-sm text-gray-500">Property Address</dt>
                      <dd className="text-sm font-medium text-gray-900 mt-1">
                        {formData.property_address}, {formData.property_city}, {formData.property_state} {formData.property_zip}
                      </dd>
                    </div>
                    <div>
                      <dt className="text-sm text-gray-500">Property Type</dt>
                      <dd className="text-sm font-medium text-gray-900 mt-1">{formData.property_type}</dd>
                    </div>
                    {formData.sale_price && (
                      <div>
                        <dt className="text-sm text-gray-500">Sale Price</dt>
                        <dd className="text-sm font-medium text-gray-900 mt-1">
                          ${Number(formData.sale_price).toLocaleString()}
                        </dd>
                      </div>
                    )}
                    {formData.template_id && (
                      <div>
                        <dt className="text-sm text-gray-500">Template Selected</dt>
                        <dd className="text-sm font-medium text-gray-900 mt-1">
                          {templates.find(t => t.template_id.toString() === formData.template_id)?.name}
                        </dd>
                      </div>
                    )}
                  </dl>
                  <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <p className="text-sm text-blue-800">
                      <strong>Next Step:</strong> After creating this transaction, you'll be taken to the timeline
                      view where you can add milestone dates and manage tasks.
                    </p>
                  </div>
                </div>
              </div>
            )}

            {/* Navigation buttons */}
            <div className="flex items-center justify-between mt-8">
              <Button
                type="button"
                variant="secondary"
                onClick={prevStep}
                disabled={currentStep === 1}
              >
                <ArrowLeft className="h-4 w-4 mr-2" />
                Previous
              </Button>
              {currentStep < steps.length ? (
                <Button type="submit" variant="primary">
                  Next
                  <ArrowRight className="h-4 w-4 ml-2" />
                </Button>
              ) : (
                <Button
                  type="button"
                  variant="primary"
                  onClick={handleFinalSubmit}
                  isLoading={createMutation.isPending}
                >
                  Create Transaction
                </Button>
              )}
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
};
