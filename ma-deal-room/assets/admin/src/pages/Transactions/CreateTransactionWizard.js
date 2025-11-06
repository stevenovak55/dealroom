import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
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
import { cn } from '@/utils/cn';
/**
 * CreateTransactionWizard Component (Mobile-First Redesign)
 *
 * Responsive multi-step transaction creation wizard with mobile-first design:
 * - Mobile (<768px): Vertical progress, stacked form fields, full-width buttons
 * - Desktop (>=768px): Horizontal progress, multi-column grids
 *
 * Features:
 * - 3-step wizard: Property Details → Template → Review
 * - MLS import or manual entry
 * - Template selection with filtering
 * - Form validation with React Hook Form
 * - Touch-friendly inputs and buttons
 */
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
    const [formData, setFormData] = useState({
        property_state: 'MA',
        transaction_side: 'listing',
    });
    const [importMode, setImportMode] = useState('manual');
    const [mlsNumber, setMlsNumber] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [mlsSearchError, setMlsSearchError] = useState(null);
    const createMutation = useCreateTransaction();
    const { register, handleSubmit, formState: { errors }, watch, reset } = useForm({
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
    const handleStepSubmit = (data) => {
        console.log('Form submitted with data:', data);
        setFormData((prev) => ({ ...prev, ...data }));
        if (currentStep < steps.length) {
            nextStep();
        }
    };
    const handleFinalSubmit = async () => {
        try {
            const result = await createMutation.mutateAsync(formData);
            navigate(`/transactions/${result.transaction_id}`);
        }
        catch (error) {
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
                let mappedPropertyType = undefined;
                if (propertySubType) {
                    const typeMapping = {
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
                    transaction_side: 'listing',
                    property_address: listing.address || '',
                    property_city: listing.city || '',
                    property_state: listing.state || 'MA',
                    property_zip: listing.zip || '',
                    property_type: mappedPropertyType,
                    sale_price: listing.price,
                    mls_number: listing.mls_number,
                };
                // Update React Hook Form using reset() - more reliable than setValue()
                reset(mlsFormData, { keepDefaultValues: false });
                // Update formData state for the success message and MLS number display
                setFormData(prev => ({ ...prev, ...mlsFormData }));
                setMlsSearchError(null);
            }
            else {
                setMlsSearchError('No property found with that MLS number');
            }
        }
        catch (err) {
            setMlsSearchError(err.message || 'Failed to search MLS');
        }
        finally {
            setIsSearching(false);
        }
    };
    return (_jsxs("div", { className: "max-w-4xl mx-auto space-y-5 md:space-y-6", children: [_jsxs("div", { className: cn('flex flex-col space-y-3', 'md:flex-row md:items-center md:gap-4 md:space-y-0'), children: [_jsxs(Button, { variant: "ghost", size: "md", onClick: () => navigate('/transactions'), className: "w-fit", children: [_jsx(ArrowLeft, { className: "h-5 w-5 mr-2" }), "Back"] }), _jsxs("div", { children: [_jsx("h1", { className: "text-xl md:text-2xl lg:text-3xl font-bold text-gray-900", children: "Create New Transaction" }), _jsx("p", { className: "text-sm md:text-base text-gray-500 mt-1", children: "Follow the steps to create a transaction" })] })] }), _jsx("div", { className: cn('flex flex-col space-y-4', 'md:flex-row md:items-center md:justify-between md:space-y-0'), children: steps.map((step, index) => (_jsxs("div", { className: cn('flex items-center', 'md:flex-1'), children: [_jsxs("div", { className: "flex items-center flex-1 md:flex-initial", children: [_jsx("div", { className: cn('flex items-center justify-center rounded-full border-2', 'w-10 h-10 md:w-10 md:h-10', currentStep > step.id
                                        ? 'bg-primary-600 border-primary-600'
                                        : currentStep === step.id
                                            ? 'border-primary-600 text-primary-600'
                                            : 'border-gray-300 text-gray-400'), children: currentStep > step.id ? (_jsx(Check, { className: "h-5 w-5 text-white" })) : (_jsx("span", { className: "text-sm font-medium", children: step.id })) }), _jsxs("div", { className: "ml-3 flex-1", children: [_jsx("p", { className: cn('font-medium', 'text-sm md:text-sm', currentStep >= step.id ? 'text-gray-900' : 'text-gray-400'), children: step.name }), _jsx("p", { className: "text-xs md:text-xs text-gray-500 mt-0.5", children: step.description })] })] }), index < steps.length - 1 && (_jsxs(_Fragment, { children: [_jsx("div", { className: cn('ml-5 w-0.5 h-6', 'md:hidden', currentStep > step.id ? 'bg-primary-600' : 'bg-gray-300') }), _jsx("div", { className: cn('hidden md:block', 'flex-1 h-0.5 mx-4', currentStep > step.id ? 'bg-primary-600' : 'bg-gray-300') })] }))] }, step.id))) }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-lg md:text-xl", children: steps[currentStep - 1].name }) }), _jsx(CardContent, { children: _jsxs("form", { onSubmit: handleSubmit(handleStepSubmit, (errors) => {
                                console.log('Form validation errors:', errors);
                            }), children: [currentStep === 1 && (_jsxs("div", { className: "space-y-5 md:space-y-4", children: [_jsx(Select, { label: "Transaction Side", options: transactionSideOptions, defaultValue: formData.transaction_side, ...register('transaction_side', { required: true }), helperText: "Are you representing the seller (listing) or the buyer?", required: true }), _jsxs("div", { className: "border-t border-b border-gray-200 py-4 md:py-4 my-4", children: [_jsx("label", { className: "text-sm md:text-base font-medium text-gray-700 mb-3 block", children: "How would you like to add property details?" }), _jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-2 gap-3", children: [_jsxs("button", { type: "button", onClick: () => setImportMode('manual'), className: cn('border-2 rounded-lg text-left transition-colors', 
                                                            // Touch-friendly padding
                                                            'p-4 md:p-4', importMode === 'manual'
                                                                ? 'border-primary-600 bg-primary-50'
                                                                : 'border-gray-200 hover:border-gray-300'), children: [_jsx("div", { className: "text-base md:text-base font-medium text-gray-900", children: "Manual Entry" }), _jsx("div", { className: "text-sm md:text-sm text-gray-500 mt-1", children: "Enter property details manually" })] }), _jsxs("button", { type: "button", onClick: () => setImportMode('mls'), className: cn('border-2 rounded-lg text-left transition-colors', 
                                                            // Touch-friendly padding
                                                            'p-4 md:p-4', importMode === 'mls'
                                                                ? 'border-primary-600 bg-primary-50'
                                                                : 'border-gray-200 hover:border-gray-300'), children: [_jsxs("div", { className: "flex items-center gap-2 font-medium text-gray-900 text-base md:text-base", children: [_jsx(Database, { className: "h-5 w-5 md:h-4 md:w-4" }), "Import from MLS"] }), _jsx("div", { className: "text-sm md:text-sm text-gray-500 mt-1", children: "Search by MLS number" })] })] })] }), importMode === 'mls' && (_jsxs("div", { className: cn('bg-blue-50 border border-blue-200 rounded-lg space-y-3', 'p-4 md:p-4'), children: [_jsx("label", { className: "block text-sm md:text-base font-medium text-gray-900", children: "MLS Number" }), _jsxs("div", { className: cn('flex flex-col space-y-2', 'sm:flex-row sm:gap-2 sm:space-y-0'), children: [_jsx("input", { type: "text", value: mlsNumber, onChange: (e) => setMlsNumber(e.target.value), placeholder: "e.g., 73429927", className: cn('flex-1 px-4 border border-gray-300 rounded-lg', 'focus:ring-2 focus:ring-primary-500 focus:border-transparent', 'text-base md:text-sm', 
                                                            // Touch-friendly height
                                                            'py-3 md:py-2'), onKeyPress: (e) => e.key === 'Enter' && (e.preventDefault(), handleMLSSearch()) }), _jsx("button", { type: "button", onClick: handleMLSSearch, disabled: isSearching, className: cn('bg-primary-600 text-white rounded-lg', 'hover:bg-primary-700 disabled:opacity-50', 'flex items-center justify-center gap-2', 'text-base md:text-sm font-medium', 
                                                            // Touch-friendly size
                                                            'px-6 py-3 md:px-4 md:py-2', 'w-full sm:w-auto'), children: isSearching ? (_jsxs(_Fragment, { children: [_jsx(RefreshCw, { className: "h-5 w-5 md:h-4 md:w-4 animate-spin" }), "Searching..."] })) : (_jsxs(_Fragment, { children: [_jsx(Search, { className: "h-5 w-5 md:h-4 md:w-4" }), "Search"] })) })] }), mlsSearchError && (_jsx("p", { className: "text-sm md:text-sm text-red-600", children: mlsSearchError })), formData.mls_number && !mlsSearchError && (_jsx("p", { className: "text-sm md:text-sm text-green-600", children: "\u2713 Property found! Form fields have been auto-filled. You can edit them below if needed." }))] })), _jsx(Input, { label: "Property Address", placeholder: "123 Main St", ...register('property_address', { required: true }), error: errors.property_address ? 'Address is required' : undefined, required: true }), _jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-3 gap-4", children: [_jsx(Input, { label: "City", placeholder: "Boston", ...register('property_city', { required: true }), required: true }), _jsx(Input, { label: "State", placeholder: "MA", ...register('property_state', { required: true }), required: true }), _jsx(Input, { label: "ZIP Code", placeholder: "02101", ...register('property_zip', { required: true }), required: true })] }), _jsx(Select, { label: "Property Type", options: propertyTypeOptions, ...register('property_type', { required: true }), required: true }), _jsx(Input, { label: "Sale Price", type: "number", placeholder: "500000", ...register('sale_price') }), _jsx("input", { type: "hidden", ...register('mls_number') }), formData.mls_number && (_jsx("div", { className: "bg-green-50 border border-green-200 rounded-lg p-3", children: _jsxs("p", { className: "text-sm text-green-800", children: [_jsx("strong", { children: "MLS Number:" }), " ", formData.mls_number] }) }))] })), currentStep === 2 && (_jsxs("div", { className: "space-y-5 md:space-y-4", children: [_jsxs("div", { className: "mb-4", children: [_jsx("p", { className: "text-sm md:text-base text-gray-600", children: "Select a template to auto-generate tasks for this transaction" }), watchedPropertyType && watchedTransactionSide && (_jsxs("p", { className: "text-sm md:text-sm text-primary-600 mt-2 font-medium", children: [templates.length, " template", templates.length !== 1 ? 's' : '', " available for ", ' ', watchedTransactionSide === 'listing' ? 'listing-side' : 'buyer-side', " ", ' ', watchedPropertyType, " transactions"] })), templates.length === 0 && watchedPropertyType && (_jsx("p", { className: "text-sm md:text-sm text-orange-600 mt-2", children: "No templates found. Please check your property type and transaction side selections." }))] }), _jsx("div", { className: "grid grid-cols-1 gap-3 md:gap-3", children: templates.map((template) => (_jsxs("label", { className: cn('flex items-start border-2 rounded-lg cursor-pointer', 'hover:border-primary-300 transition-colors', 
                                                // Touch-friendly padding
                                                'p-4 md:p-4'), children: [_jsx("input", { type: "radio", value: template.template_id, defaultChecked: formData.template_id === template.template_id.toString(), ...register('template_id'), className: cn('mt-1', 
                                                        // Touch-friendly radio button size
                                                        'w-5 h-5 md:w-4 md:h-4') }), _jsxs("div", { className: "ml-3 flex-1", children: [_jsx("p", { className: "text-base md:text-base font-medium text-gray-900", children: template.name }), _jsx("p", { className: "text-sm md:text-sm text-gray-500 mt-1", children: template.description }), _jsxs("p", { className: "text-xs md:text-xs text-gray-400 mt-2", children: [template.task_count, " tasks"] })] })] }, template.template_id))) })] })), currentStep === 3 && (_jsx("div", { className: "space-y-5 md:space-y-6", children: _jsxs("div", { children: [_jsx("h3", { className: "text-base md:text-lg font-medium text-gray-900 mb-4", children: "Review Transaction Details" }), _jsxs("dl", { className: "space-y-4 md:space-y-3", children: [_jsxs("div", { children: [_jsx("dt", { className: "text-sm md:text-sm text-gray-500", children: "Transaction Side" }), _jsx("dd", { className: "text-sm md:text-base font-medium text-gray-900 mt-1", children: formData.transaction_side === 'listing' ? 'Listing (Seller) Side' : 'Buyer Side' })] }), _jsxs("div", { children: [_jsx("dt", { className: "text-sm md:text-sm text-gray-500", children: "Property Address" }), _jsxs("dd", { className: "text-sm md:text-base font-medium text-gray-900 mt-1", children: [formData.property_address, ", ", formData.property_city, ", ", formData.property_state, " ", formData.property_zip] })] }), _jsxs("div", { children: [_jsx("dt", { className: "text-sm md:text-sm text-gray-500", children: "Property Type" }), _jsx("dd", { className: "text-sm md:text-base font-medium text-gray-900 mt-1", children: formData.property_type })] }), formData.sale_price && (_jsxs("div", { children: [_jsx("dt", { className: "text-sm md:text-sm text-gray-500", children: "Sale Price" }), _jsxs("dd", { className: "text-sm md:text-base font-medium text-gray-900 mt-1", children: ["$", Number(formData.sale_price).toLocaleString()] })] })), formData.template_id && (_jsxs("div", { children: [_jsx("dt", { className: "text-sm md:text-sm text-gray-500", children: "Template Selected" }), _jsx("dd", { className: "text-sm md:text-base font-medium text-gray-900 mt-1", children: templates.find(t => t.template_id.toString() === formData.template_id)?.name })] }))] }), _jsx("div", { className: cn('bg-blue-50 border border-blue-200 rounded-md', 'mt-5 md:mt-6', 'p-4 md:p-4'), children: _jsxs("p", { className: "text-sm md:text-sm text-blue-800", children: [_jsx("strong", { children: "Next Step:" }), " After creating this transaction, you'll be taken to the timeline view where you can add milestone dates and manage tasks."] }) })] }) })), _jsxs("div", { className: cn('flex items-center justify-between', 'mt-8 md:mt-8', 'gap-3 md:gap-0'), children: [_jsxs(Button, { type: "button", variant: "secondary", size: "lg", onClick: prevStep, disabled: currentStep === 1, className: "flex-1 sm:flex-initial", children: [_jsx(ArrowLeft, { className: "h-5 w-5 mr-2" }), _jsx("span", { className: "hidden sm:inline", children: "Previous" }), _jsx("span", { className: "sm:hidden", children: "Back" })] }), currentStep < steps.length ? (_jsxs(Button, { type: "submit", variant: "primary", size: "lg", className: "flex-1 sm:flex-initial", children: [_jsx("span", { children: "Next" }), _jsx(ArrowRight, { className: "h-5 w-5 ml-2" })] })) : (_jsxs(Button, { type: "button", variant: "primary", size: "lg", onClick: handleFinalSubmit, isLoading: createMutation.isPending, className: "flex-1 sm:flex-initial", children: [_jsx("span", { className: "hidden sm:inline", children: "Create Transaction" }), _jsx("span", { className: "sm:hidden", children: "Create" })] }))] })] }) })] })] }));
};
