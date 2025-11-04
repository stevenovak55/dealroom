import { useState, useEffect } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { useCreateParty, useUpdateParty } from '@/api/queries/useParties';
import type { Party, CreatePartyInput, UpdatePartyInput } from '@/api/types';

interface PartyFormModalProps {
  isOpen: boolean;
  onClose: () => void;
  transactionId: number;
  party?: Party;
}

const PARTY_ROLES = [
  { value: 'buyer', label: 'Buyer' },
  { value: 'seller', label: 'Seller' },
  { value: 'buyer_attorney', label: 'Buyer Attorney' },
  { value: 'seller_attorney', label: 'Seller Attorney' },
  { value: 'buyer_lender', label: 'Buyer Lender' },
  { value: 'buyer_agent', label: 'Buyer Agent' },
  { value: 'seller_agent', label: 'Seller Agent' },
  { value: 'title_company', label: 'Title Company' },
  { value: 'inspector', label: 'Inspector' },
  { value: 'appraiser', label: 'Appraiser' },
  { value: 'hoa_manager', label: 'HOA Manager' },
  { value: 'septic_inspector', label: 'Septic Inspector' },
  { value: 'fire_dept', label: 'Fire Department' },
  { value: 'other', label: 'Other' },
];

export const PartyFormModal = ({
  isOpen,
  onClose,
  transactionId,
  party,
}: PartyFormModalProps) => {
  const isEditing = !!party;
  const createParty = useCreateParty(transactionId);
  const updateParty = useUpdateParty(transactionId, party?.id || 0);

  const [formData, setFormData] = useState<CreatePartyInput>({
    role: 'buyer',
    contact_name: '',
    company_name: '',
    email: '',
    phone: '',
    address: '',
  });

  // Load existing party data when editing
  useEffect(() => {
    if (party) {
      setFormData({
        role: party.role,
        contact_name: party.contact_name,
        company_name: party.company_name || '',
        email: party.email || '',
        phone: party.phone || '',
        address: party.address || '',
      });
    } else {
      // Reset form when creating new party
      setFormData({
        role: 'buyer',
        contact_name: '',
        company_name: '',
        email: '',
        phone: '',
        address: '',
      });
    }
  }, [party, isOpen]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      if (isEditing) {
        await updateParty.mutateAsync(formData as UpdatePartyInput);
      } else {
        await createParty.mutateAsync(formData);
      }
      onClose();
    } catch (error) {
      console.error('Failed to save party:', error);
    }
  };

  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEditing ? 'Edit Party' : 'Add Party'}
      size="md"
    >
      <form onSubmit={handleSubmit}>
        <div className="space-y-4">
          {/* Role */}
          <div>
            <label htmlFor="role" className="block text-sm font-medium text-gray-700 mb-1">
              Role <span className="text-red-500">*</span>
            </label>
            <select
              id="role"
              name="role"
              value={formData.role}
              onChange={handleChange}
              required
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            >
              {PARTY_ROLES.map((role) => (
                <option key={role.value} value={role.value}>
                  {role.label}
                </option>
              ))}
            </select>
          </div>

          {/* Contact Name */}
          <div>
            <label htmlFor="contact_name" className="block text-sm font-medium text-gray-700 mb-1">
              Contact Name <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="contact_name"
              name="contact_name"
              value={formData.contact_name}
              onChange={handleChange}
              required
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              placeholder="John Doe"
            />
          </div>

          {/* Company Name */}
          <div>
            <label htmlFor="company_name" className="block text-sm font-medium text-gray-700 mb-1">
              Company Name
            </label>
            <input
              type="text"
              id="company_name"
              name="company_name"
              value={formData.company_name}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              placeholder="Acme Law Firm"
            />
          </div>

          {/* Email */}
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              placeholder="john@example.com"
            />
          </div>

          {/* Phone */}
          <div>
            <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-1">
              Phone
            </label>
            <input
              type="tel"
              id="phone"
              name="phone"
              value={formData.phone}
              onChange={handleChange}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              placeholder="(555) 123-4567"
            />
          </div>

          {/* Address */}
          <div>
            <label htmlFor="address" className="block text-sm font-medium text-gray-700 mb-1">
              Address
            </label>
            <textarea
              id="address"
              name="address"
              value={formData.address}
              onChange={handleChange}
              rows={2}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              placeholder="123 Main St, Boston, MA 02109"
            />
          </div>
        </div>

        <ModalFooter>
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={createParty.isPending || updateParty.isPending}
            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {createParty.isPending || updateParty.isPending
              ? 'Saving...'
              : isEditing
              ? 'Update Party'
              : 'Add Party'}
          </button>
        </ModalFooter>
      </form>
    </Modal>
  );
};
