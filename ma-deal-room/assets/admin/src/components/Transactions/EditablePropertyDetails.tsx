import { useState } from 'react';
import { Edit2, Save, X } from 'lucide-react';
import { useUpdateTransaction } from '@/api/queries/useTransactions';
import { formatCurrency } from '@/utils/formatDate';
import type { Transaction } from '@/api/types';

interface EditablePropertyDetailsProps {
  transaction: Transaction;
}

type EditableField = 'property_address' | 'list_price' | 'accepted_offer_price' | 'bedrooms' | 'bathrooms' | 'square_feet' | 'property_year_built' | 'lot_size' | 'parking_spaces';

export const EditablePropertyDetails = ({ transaction }: EditablePropertyDetailsProps) => {
  const updateMutation = useUpdateTransaction(transaction.transaction_id);
  const [editingField, setEditingField] = useState<EditableField | null>(null);
  const [editValue, setEditValue] = useState<string>('');

  const handleEdit = (field: EditableField, currentValue: any) => {
    setEditingField(field);
    setEditValue(currentValue?.toString() || '');
  };

  const handleCancel = () => {
    setEditingField(null);
    setEditValue('');
  };

  const handleSave = async (field: EditableField) => {
    try {
      // Convert value based on field type
      let value: any = editValue.trim();

      if (!value) {
        value = null;
      } else if (field === 'list_price' || field === 'accepted_offer_price') {
        // Remove currency formatting if present
        value = parseFloat(value.replace(/[$,]/g, ''));
        if (isNaN(value)) {
          alert('Please enter a valid price');
          return;
        }
      } else if (field === 'bedrooms' || field === 'bathrooms' || field === 'lot_size') {
        value = parseFloat(value);
        if (isNaN(value)) {
          alert('Please enter a valid number');
          return;
        }
      } else if (field === 'square_feet' || field === 'property_year_built' || field === 'parking_spaces') {
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
    } catch (error) {
      console.error('Failed to update field:', error);
      alert('Failed to update. Please try again.');
    }
  };

  const renderEditableField = (
    field: EditableField,
    label: string,
    value: any,
    formatFunc?: (val: any) => string,
    inputType: string = 'text',
    placeholder?: string
  ) => {
    const isEditing = editingField === field;
    const displayValue = value ? (formatFunc ? formatFunc(value) : value) : 'Not set';

    return (
      <div>
        <dt className="text-sm text-gray-500">{label}</dt>
        <dd className="text-sm font-medium text-gray-900 mt-1">
          {isEditing ? (
            <div className="flex items-center gap-2">
              <input
                type={inputType}
                value={editValue}
                onChange={(e) => setEditValue(e.target.value)}
                className="flex-1 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder={placeholder}
                autoFocus
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    handleSave(field);
                  } else if (e.key === 'Escape') {
                    handleCancel();
                  }
                }}
              />
              <button
                onClick={() => handleSave(field)}
                className="p-1 text-green-600 hover:bg-green-50 rounded"
                disabled={updateMutation.isPending}
              >
                <Save className="h-4 w-4" />
              </button>
              <button
                onClick={handleCancel}
                className="p-1 text-red-600 hover:bg-red-50 rounded"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
          ) : (
            <div className="flex items-center gap-2 group">
              <span className={value ? '' : 'text-gray-400 italic'}>{displayValue}</span>
              <button
                onClick={() => handleEdit(field, value)}
                className="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded transition-opacity"
              >
                <Edit2 className="h-3.5 w-3.5" />
              </button>
            </div>
          )}
        </dd>
      </div>
    );
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      {/* Column 1 - Address & Basic Info */}
      <div>
        <h4 className="font-medium text-gray-900 mb-4">Address & Basic Info</h4>
        <dl className="space-y-3">
          {renderEditableField(
            'property_address',
            'Address',
            transaction.property_address,
            undefined,
            'text',
            '123 Main St, Boston, MA 02101'
          )}

          {renderEditableField(
            'bedrooms',
            'Bedrooms',
            transaction.bedrooms,
            (val) => `${val} bed${val !== 1 ? 's' : ''}`,
            'number',
            '3'
          )}

          {renderEditableField(
            'bathrooms',
            'Bathrooms',
            transaction.bathrooms,
            (val) => `${val} bath${val !== 1 ? 's' : ''}`,
            'number',
            '2.5'
          )}

          <div>
            <dt className="text-sm text-gray-500">Property Type</dt>
            <dd className="text-sm font-medium text-gray-900 mt-1 capitalize">
              {transaction.property_type}
            </dd>
          </div>
        </dl>
      </div>

      {/* Column 2 - Property Details */}
      <div>
        <h4 className="font-medium text-gray-900 mb-4">Property Details</h4>
        <dl className="space-y-3">
          {renderEditableField(
            'square_feet',
            'Square Feet',
            transaction.square_feet,
            (val) => `${val.toLocaleString()} sq ft`,
            'number',
            '2000'
          )}

          {renderEditableField(
            'property_year_built',
            'Year Built',
            transaction.property_year_built,
            undefined,
            'number',
            '1990'
          )}

          {renderEditableField(
            'lot_size',
            'Lot Size (acres)',
            transaction.lot_size,
            (val) => `${val} acres`,
            'number',
            '0.25'
          )}

          {renderEditableField(
            'parking_spaces',
            'Parking Spaces',
            transaction.parking_spaces,
            (val) => `${val} space${val !== 1 ? 's' : ''}`,
            'number',
            '2'
          )}
        </dl>
      </div>

      {/* Column 3 - Financial & Status */}
      <div>
        <h4 className="font-medium text-gray-900 mb-4">Financial & Status</h4>
        <dl className="space-y-3">
          {renderEditableField(
            'list_price',
            'List Price',
            transaction.list_price,
            formatCurrency,
            'text',
            '500000'
          )}

          {renderEditableField(
            'accepted_offer_price',
            'Accepted Offer Price',
            transaction.accepted_offer_price,
            formatCurrency,
            'text',
            '495000'
          )}

          <div>
            <dt className="text-sm text-gray-500">Transaction Side</dt>
            <dd className="text-sm font-medium text-gray-900 mt-1 capitalize">
              {transaction.transaction_side === 'listing' ? 'Listing (Seller) Side' : 'Buyer Side'}
            </dd>
          </div>

          <div>
            <dt className="text-sm text-gray-500">Status</dt>
            <dd className="text-sm font-medium text-gray-900 mt-1 capitalize">
              {transaction.status.replace(/_/g, ' ')}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  );
};
