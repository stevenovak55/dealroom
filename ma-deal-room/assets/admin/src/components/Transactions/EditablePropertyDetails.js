import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Edit2, Save, X } from 'lucide-react';
import { useUpdateTransaction } from '@/api/queries/useTransactions';
import { formatCurrency } from '@/utils/formatDate';
export const EditablePropertyDetails = ({ transaction }) => {
    const updateMutation = useUpdateTransaction(transaction.transaction_id);
    const [editingField, setEditingField] = useState(null);
    const [editValue, setEditValue] = useState('');
    const handleEdit = (field, currentValue) => {
        setEditingField(field);
        setEditValue(currentValue?.toString() || '');
    };
    const handleCancel = () => {
        setEditingField(null);
        setEditValue('');
    };
    const handleSave = async (field) => {
        try {
            // Convert value based on field type
            let value = editValue.trim();
            if (!value) {
                value = null;
            }
            else if (field === 'list_price' || field === 'accepted_offer_price') {
                // Remove currency formatting if present
                value = parseFloat(value.replace(/[$,]/g, ''));
                if (isNaN(value)) {
                    alert('Please enter a valid price');
                    return;
                }
            }
            else if (field === 'bedrooms' || field === 'bathrooms' || field === 'lot_size') {
                value = parseFloat(value);
                if (isNaN(value)) {
                    alert('Please enter a valid number');
                    return;
                }
            }
            else if (field === 'square_feet' || field === 'property_year_built' || field === 'parking_spaces') {
                value = parseInt(value);
                if (isNaN(value)) {
                    alert('Please enter a valid whole number');
                    return;
                }
            }
            await updateMutation.mutateAsync({
                [field]: value
            });
            setEditingField(null);
            setEditValue('');
        }
        catch (error) {
            console.error('Failed to update field:', error);
            alert('Failed to update. Please try again.');
        }
    };
    const renderEditableField = (field, label, value, formatFunc, inputType = 'text', placeholder) => {
        const isEditing = editingField === field;
        const displayValue = value ? (formatFunc ? formatFunc(value) : value) : 'Not set';
        return (_jsxs("div", { children: [_jsx("dt", { className: "text-sm text-gray-500", children: label }), _jsx("dd", { className: "text-sm font-medium text-gray-900 mt-1", children: isEditing ? (_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("input", { type: inputType, value: editValue, onChange: (e) => setEditValue(e.target.value), className: "flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-primary-500", placeholder: placeholder, autoFocus: true, onKeyDown: (e) => {
                                    if (e.key === 'Enter') {
                                        handleSave(field);
                                    }
                                    else if (e.key === 'Escape') {
                                        handleCancel();
                                    }
                                } }), _jsx("button", { onClick: () => handleSave(field), className: "p-1 text-green-600 hover:bg-green-50 rounded", disabled: updateMutation.isPending, children: _jsx(Save, { className: "h-4 w-4" }) }), _jsx("button", { onClick: handleCancel, className: "p-1 text-red-600 hover:bg-red-50 rounded", children: _jsx(X, { className: "h-4 w-4" }) })] })) : (_jsxs("div", { className: "flex items-center gap-2 group", children: [_jsx("span", { className: value ? '' : 'text-gray-400 italic', children: displayValue }), _jsx("button", { onClick: () => handleEdit(field, value), className: "opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-opacity", children: _jsx(Edit2, { className: "h-3.5 w-3.5" }) })] })) })] }));
    };
    return (_jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6", children: [_jsxs("div", { children: [_jsx("h4", { className: "font-medium text-gray-900 mb-4", children: "Address & Basic Info" }), _jsxs("dl", { className: "space-y-3", children: [renderEditableField('property_address', 'Address', transaction.property_address, undefined, 'text', '123 Main St, Boston, MA 02101'), renderEditableField('bedrooms', 'Bedrooms', transaction.bedrooms, (val) => `${val} bed${val !== 1 ? 's' : ''}`, 'number', '3'), renderEditableField('bathrooms', 'Bathrooms', transaction.bathrooms, (val) => `${val} bath${val !== 1 ? 's' : ''}`, 'number', '2.5'), _jsxs("div", { children: [_jsx("dt", { className: "text-sm text-gray-500", children: "Property Type" }), _jsx("dd", { className: "text-sm font-medium text-gray-900 mt-1 capitalize", children: transaction.property_type })] })] })] }), _jsxs("div", { children: [_jsx("h4", { className: "font-medium text-gray-900 mb-4", children: "Property Details" }), _jsxs("dl", { className: "space-y-3", children: [renderEditableField('square_feet', 'Square Feet', transaction.square_feet, (val) => `${val.toLocaleString()} sq ft`, 'number', '2000'), renderEditableField('property_year_built', 'Year Built', transaction.property_year_built, undefined, 'number', '1990'), renderEditableField('lot_size', 'Lot Size (acres)', transaction.lot_size, (val) => `${val} acres`, 'number', '0.25'), renderEditableField('parking_spaces', 'Parking Spaces', transaction.parking_spaces, (val) => `${val} space${val !== 1 ? 's' : ''}`, 'number', '2')] })] }), _jsxs("div", { children: [_jsx("h4", { className: "font-medium text-gray-900 mb-4", children: "Financial & Status" }), _jsxs("dl", { className: "space-y-3", children: [renderEditableField('list_price', 'List Price', transaction.list_price, formatCurrency, 'text', '500000'), renderEditableField('accepted_offer_price', 'Accepted Offer Price', transaction.accepted_offer_price, formatCurrency, 'text', '495000'), _jsxs("div", { children: [_jsx("dt", { className: "text-sm text-gray-500", children: "Transaction Side" }), _jsx("dd", { className: "text-sm font-medium text-gray-900 mt-1 capitalize", children: transaction.transaction_side === 'listing' ? 'Listing (Seller) Side' : 'Buyer Side' })] }), _jsxs("div", { children: [_jsx("dt", { className: "text-sm text-gray-500", children: "Status" }), _jsx("dd", { className: "text-sm font-medium text-gray-900 mt-1 capitalize", children: transaction.status.replace(/_/g, ' ') })] })] })] })] }));
};
