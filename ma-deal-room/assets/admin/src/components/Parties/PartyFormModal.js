import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Modal, ModalFooter } from '@/components/shared/Modal';
import { useCreateParty, useUpdateParty } from '@/api/queries/useParties';
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
export const PartyFormModal = ({ isOpen, onClose, transactionId, party, }) => {
    const isEditing = !!party;
    const createParty = useCreateParty(transactionId);
    const updateParty = useUpdateParty(transactionId, party?.id || 0);
    const [formData, setFormData] = useState({
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
        }
        else {
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
    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (isEditing) {
                await updateParty.mutateAsync(formData);
            }
            else {
                await createParty.mutateAsync(formData);
            }
            onClose();
        }
        catch (error) {
            console.error('Failed to save party:', error);
        }
    };
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
    };
    return (_jsx(Modal, { isOpen: isOpen, onClose: onClose, title: isEditing ? 'Edit Party' : 'Add Party', size: "md", children: _jsxs("form", { onSubmit: handleSubmit, children: [_jsxs("div", { className: "space-y-4", children: [_jsxs("div", { children: [_jsxs("label", { htmlFor: "role", className: "block text-sm font-medium text-gray-700 mb-1", children: ["Role ", _jsx("span", { className: "text-red-500", children: "*" })] }), _jsx("select", { id: "role", name: "role", value: formData.role, onChange: handleChange, required: true, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", children: PARTY_ROLES.map((role) => (_jsx("option", { value: role.value, children: role.label }, role.value))) })] }), _jsxs("div", { children: [_jsxs("label", { htmlFor: "contact_name", className: "block text-sm font-medium text-gray-700 mb-1", children: ["Contact Name ", _jsx("span", { className: "text-red-500", children: "*" })] }), _jsx("input", { type: "text", id: "contact_name", name: "contact_name", value: formData.contact_name, onChange: handleChange, required: true, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "John Doe" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "company_name", className: "block text-sm font-medium text-gray-700 mb-1", children: "Company Name" }), _jsx("input", { type: "text", id: "company_name", name: "company_name", value: formData.company_name, onChange: handleChange, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "Acme Law Firm" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "email", className: "block text-sm font-medium text-gray-700 mb-1", children: "Email" }), _jsx("input", { type: "email", id: "email", name: "email", value: formData.email, onChange: handleChange, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "john@example.com" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "phone", className: "block text-sm font-medium text-gray-700 mb-1", children: "Phone" }), _jsx("input", { type: "tel", id: "phone", name: "phone", value: formData.phone, onChange: handleChange, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "(555) 123-4567" })] }), _jsxs("div", { children: [_jsx("label", { htmlFor: "address", className: "block text-sm font-medium text-gray-700 mb-1", children: "Address" }), _jsx("textarea", { id: "address", name: "address", value: formData.address, onChange: handleChange, rows: 2, className: "w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500", placeholder: "123 Main St, Boston, MA 02109" })] })] }), _jsxs(ModalFooter, { children: [_jsx("button", { type: "button", onClick: onClose, className: "px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500", children: "Cancel" }), _jsx("button", { type: "submit", disabled: createParty.isPending || updateParty.isPending, className: "px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed", children: createParty.isPending || updateParty.isPending
                                ? 'Saving...'
                                : isEditing
                                    ? 'Update Party'
                                    : 'Add Party' })] })] }) }));
};
