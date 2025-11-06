import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useNavigate, useParams, Link } from 'react-router-dom';
import { ArrowLeft, Save } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { useGetTransaction, useUpdateTransaction } from '@/api/queries/useTransactions';
import { Button } from '@/components/shared/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { PageLoader } from '@/components/shared/Loader';
import { cn } from '@/utils/cn';
/**
 * EditTransactionForm Component (Mobile-First Redesign)
 *
 * Responsive transaction edit form with mobile-first design:
 * - Mobile (<768px): Full-width inputs, stacked button layout
 * - Desktop (>=768px): Multi-column grids, inline buttons
 *
 * Features:
 * - Basic info, financial info, key dates, notes
 * - Form validation with React Hook Form
 * - Touch-friendly inputs and buttons
 * - Responsive grid layouts
 */
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
    const { id } = useParams();
    const transactionId = parseInt(id || '0');
    const { data: transaction, isLoading } = useGetTransaction(transactionId);
    const updateMutation = useUpdateTransaction(transactionId);
    const { register, handleSubmit, formState: { errors } } = useForm({
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
    const onSubmit = async (data) => {
        try {
            await updateMutation.mutateAsync(data);
            navigate(`/transactions/${transactionId}`);
        }
        catch (error) {
            console.error('Failed to update transaction:', error);
            alert('Failed to update transaction. Please try again.');
        }
    };
    if (isLoading) {
        return _jsx(PageLoader, {});
    }
    if (!transaction) {
        return _jsx("div", { children: "Transaction not found" });
    }
    return (_jsxs("div", { className: "max-w-4xl mx-auto space-y-5 md:space-y-6", children: [_jsxs("div", { className: cn('flex flex-col space-y-3', 'md:flex-row md:items-center md:gap-4 md:space-y-0'), children: [_jsx(Link, { to: `/transactions/${transactionId}`, className: "inline-block w-fit", children: _jsxs(Button, { variant: "ghost", size: "md", children: [_jsx(ArrowLeft, { className: "h-5 w-5 mr-2" }), "Back"] }) }), _jsxs("div", { children: [_jsx("h1", { className: "text-xl md:text-2xl lg:text-3xl font-bold text-gray-900", children: "Edit Transaction" }), _jsx("p", { className: "text-sm md:text-base text-gray-500 mt-1", children: transaction.property_address })] })] }), _jsx("form", { onSubmit: handleSubmit(onSubmit), children: _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-lg md:text-xl", children: "Transaction Details" }) }), _jsxs(CardContent, { children: [_jsxs("div", { className: "space-y-6 md:space-y-6", children: [_jsxs("div", { children: [_jsx("h3", { className: "font-medium text-gray-900 mb-4", children: "Basic Information" }), _jsxs("div", { className: "space-y-4", children: [_jsx(Input, { label: "Property Address", placeholder: "123 Main St", ...register('property_address'), error: errors.property_address?.message }), _jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-3 gap-4", children: [_jsx(Input, { label: "City", placeholder: "Boston", ...register('property_city') }), _jsx(Input, { label: "State", placeholder: "MA", ...register('property_state') }), _jsx(Input, { label: "ZIP Code", placeholder: "02101", ...register('property_zip') })] }), _jsx(Select, { label: "Property Type", options: propertyTypeOptions, ...register('property_type') }), _jsx(Select, { label: "Status", options: statusOptions, ...register('status') })] })] }), _jsxs("div", { children: [_jsx("h3", { className: "font-medium text-gray-900 mb-4", children: "Financial Information" }), _jsx(Input, { label: "Sale Price", type: "number", placeholder: "500000", ...register('sale_price', { valueAsNumber: true }) })] }), _jsxs("div", { children: [_jsx("h3", { className: "font-medium text-gray-900 mb-4", children: "Key Dates" }), _jsxs("div", { className: "space-y-4", children: [_jsx(Input, { label: "Listing Signed Date", type: "date", ...register('listing_date'), helperText: "When listing agreement was signed with seller" }), _jsx(Input, { label: "Offer Acceptance Date", type: "date", ...register('offer_accepted_date'), helperText: "When seller accepted buyer's offer" }), _jsx(Input, { label: "P&S Agreement Date", type: "date", ...register('ps_agreement_date'), helperText: "Purchase & Sale agreement signing date" }), _jsx(Input, { label: "Loan Commitment Date", type: "date", ...register('loan_commitment_date'), helperText: "When lender commits to financing" }), _jsx(Input, { label: "Closing Date", type: "date", ...register('closing_date'), helperText: "Scheduled closing date" })] })] }), _jsxs("div", { children: [_jsx("h3", { className: "text-base md:text-base font-medium text-gray-900 mb-4", children: "Notes" }), _jsx("textarea", { ...register('notes'), rows: 4, className: cn('w-full border border-gray-300 rounded-md', 'focus:outline-none focus:ring-2 focus:ring-primary-500', 'text-base md:text-sm', 
                                                    // Touch-friendly padding
                                                    'px-4 py-3 md:px-3 md:py-2'), placeholder: "Add any additional notes about this transaction..." })] })] }), _jsxs("div", { className: cn('flex flex-col-reverse space-y-3 space-y-reverse', 'sm:flex-row sm:items-center sm:justify-end sm:gap-3 sm:space-y-0', 'mt-8 pt-6 border-t border-gray-200'), children: [_jsx(Button, { type: "button", variant: "secondary", size: "lg", onClick: () => navigate(`/transactions/${transactionId}`), className: "w-full sm:w-auto", children: "Cancel" }), _jsxs(Button, { type: "submit", variant: "primary", size: "lg", isLoading: updateMutation.isPending, className: "w-full sm:w-auto", children: [_jsx(Save, { className: "h-5 w-5 mr-2" }), "Save Changes"] })] })] })] }) })] }));
};
