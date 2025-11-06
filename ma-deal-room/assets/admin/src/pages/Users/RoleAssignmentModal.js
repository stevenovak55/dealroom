import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Modal } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Select } from '@/components/shared/Select';
import { Shield, Check } from 'lucide-react';
import { useAssignRole } from '@/api/queries/useUsers';
import { getRoleLabel, ROLE_CATEGORIES } from '@/constants/roleTypes';
export const RoleAssignmentModal = ({ userId, userType, onClose }) => {
    const [selectedRole, setSelectedRole] = useState('');
    const [transactionId, setTransactionId] = useState('');
    const [isPrimary, setIsPrimary] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState('');
    const assignRoleMutation = useAssignRole();
    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!selectedRole) {
            alert('Please select a role');
            return;
        }
        try {
            await assignRoleMutation.mutateAsync({
                userId,
                userType,
                roleType: selectedRole,
                transactionId: transactionId ? parseInt(transactionId, 10) : undefined,
                isPrimary,
            });
            alert('Role assigned successfully');
            onClose();
        }
        catch (error) {
            alert('Failed to assign role');
        }
    };
    // Get role options for selected category
    const categoryOptions = [
        { value: '', label: 'Select a category...' },
        ...Object.entries(ROLE_CATEGORIES).map(([key, cat]) => ({
            value: key,
            label: `${cat.label} - ${cat.description}`,
        })),
    ];
    const roleOptions = selectedCategory
        ? [
            { value: '', label: 'Select a role...' },
            ...ROLE_CATEGORIES[selectedCategory].roles.map((role) => ({
                value: role,
                label: getRoleLabel(role),
            })),
        ]
        : [{ value: '', label: 'First select a category...' }];
    return (_jsx(Modal, { isOpen: true, onClose: onClose, title: "Assign Role", icon: _jsx(Shield, { className: "h-6 w-6 text-primary-600" }), children: _jsxs("form", { onSubmit: handleSubmit, className: "space-y-6", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Role Category" }), _jsx(Select, { value: selectedCategory, onChange: (e) => {
                                setSelectedCategory(e.target.value);
                                setSelectedRole(''); // Reset role when category changes
                            }, options: categoryOptions, required: true }), _jsx("p", { className: "text-xs text-gray-500 mt-1", children: "Choose a category to see available roles" })] }), selectedCategory && (_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Role Type" }), _jsx(Select, { value: selectedRole, onChange: (e) => setSelectedRole(e.target.value), options: roleOptions, required: true, disabled: !selectedCategory }), selectedRole && (_jsxs("div", { className: "mt-2 p-3 bg-blue-50 rounded-lg", children: [_jsx("p", { className: "text-sm text-blue-900 font-medium", children: getRoleLabel(selectedRole) }), _jsx("p", { className: "text-xs text-blue-700 mt-1" })] }))] })), selectedCategory && selectedCategory.startsWith('vendor') && (_jsxs("div", { className: "bg-yellow-50 border border-yellow-200 rounded-lg p-4", children: [_jsx("p", { className: "text-sm text-yellow-900 font-medium", children: "\uD83D\uDCE6 Vendor Role" }), _jsx("p", { className: "text-xs text-yellow-700 mt-1", children: "This is a service provider role. Vendors typically have limited access to specific transactions and tasks." })] })), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Transaction ID (Optional)" }), _jsx("input", { type: "number", className: "w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500", placeholder: "Leave empty for global role", value: transactionId, onChange: (e) => setTransactionId(e.target.value) }), _jsx("p", { className: "text-xs text-gray-500 mt-1", children: "Assign this role to a specific transaction, or leave empty for global role" })] }), _jsxs("div", { className: "flex items-center justify-between p-4 bg-gray-50 rounded-lg", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Set as Primary Role" }), _jsx("p", { className: "text-sm text-gray-500", children: "This will be the user's main role in the system" })] }), _jsxs("label", { className: "relative inline-flex items-center cursor-pointer", children: [_jsx("input", { type: "checkbox", className: "sr-only peer", checked: isPrimary, onChange: (e) => setIsPrimary(e.target.checked) }), _jsx("div", { className: "w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600" })] })] }), _jsxs("div", { className: "flex items-center justify-end gap-3 pt-4 border-t border-gray-200", children: [_jsx(Button, { type: "button", variant: "secondary", onClick: onClose, children: "Cancel" }), _jsxs(Button, { type: "submit", variant: "primary", isLoading: assignRoleMutation.isPending, disabled: !selectedRole, children: [_jsx(Check, { className: "h-4 w-4 mr-2" }), "Assign Role"] })] })] }) }));
};
