import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Modal } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { Label } from '@/components/shared/Label';
import { Textarea } from '@/components/shared/Textarea';
import { Mail, Shield, MessageSquare } from 'lucide-react';
import { ROLE_CATEGORIES, getRoleLabel } from '@/constants/roleTypes';
import { useSendInvitation } from '@/api/queries/useInvitations';
export const InviteUserModal = ({ isOpen, onClose, accountId }) => {
    const [email, setEmail] = useState('');
    const [roleCategory, setRoleCategory] = useState('');
    const [roleType, setRoleType] = useState('');
    const [message, setMessage] = useState('');
    const sendInvitation = useSendInvitation();
    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!email || !roleType) {
            alert('Please fill in all required fields');
            return;
        }
        try {
            await sendInvitation.mutateAsync({
                email,
                role_type: roleType,
                account_id: accountId,
                message: message || undefined,
            });
            alert('Invitation sent successfully!');
            handleClose();
        }
        catch (error) {
            alert(error.message || 'Failed to send invitation');
        }
    };
    const handleClose = () => {
        setEmail('');
        setRoleCategory('');
        setRoleType('');
        setMessage('');
        onClose();
    };
    // Get role options based on selected category
    const roleOptions = roleCategory && roleCategory in ROLE_CATEGORIES
        ? ROLE_CATEGORIES[roleCategory].roles.map((role) => ({
            value: role,
            label: getRoleLabel(role),
        }))
        : [];
    const categoryOptions = Object.entries(ROLE_CATEGORIES).map(([key, cat]) => ({
        value: key,
        label: cat.label,
    }));
    return (_jsx(Modal, { isOpen: isOpen, onClose: handleClose, title: "Invite New User", size: "md", children: _jsxs("form", { onSubmit: handleSubmit, className: "space-y-6", children: [_jsxs("div", { children: [_jsx(Label, { htmlFor: "email", required: true, children: "Email Address" }), _jsxs("div", { className: "relative mt-1", children: [_jsx(Mail, { className: "absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" }), _jsx(Input, { id: "email", type: "email", value: email, onChange: (e) => setEmail(e.target.value), placeholder: "user@example.com", className: "pl-10", required: true })] }), _jsx("p", { className: "mt-1 text-sm text-gray-500", children: "The user will receive an email invitation to join" })] }), _jsxs("div", { children: [_jsx(Label, { htmlFor: "roleCategory", required: true, children: "Role Category" }), _jsxs("div", { className: "relative mt-1", children: [_jsx(Shield, { className: "absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none z-10" }), _jsx(Select, { id: "roleCategory", value: roleCategory, onChange: (e) => {
                                        setRoleCategory(e.target.value);
                                        setRoleType(''); // Reset role when category changes
                                    }, options: [
                                        { value: '', label: 'Select a category...' },
                                        ...categoryOptions,
                                    ], className: "pl-10", required: true })] })] }), roleCategory && (_jsxs("div", { children: [_jsx(Label, { htmlFor: "roleType", required: true, children: "Specific Role" }), _jsx(Select, { id: "roleType", value: roleType, onChange: (e) => setRoleType(e.target.value), options: [
                                { value: '', label: 'Select a role...' },
                                ...roleOptions,
                            ], required: true }), roleType && (_jsxs("p", { className: "mt-1 text-sm text-gray-500", children: ["Selected: ", getRoleLabel(roleType)] }))] })), _jsxs("div", { children: [_jsxs(Label, { htmlFor: "message", children: ["Personal Message ", _jsx("span", { className: "text-gray-400", children: "(Optional)" })] }), _jsxs("div", { className: "relative mt-1", children: [_jsx(MessageSquare, { className: "absolute left-3 top-3 h-4 w-4 text-gray-400" }), _jsx(Textarea, { id: "message", value: message, onChange: (e) => setMessage(e.target.value), placeholder: "Add a personal message to the invitation email...", rows: 3, className: "pl-10" })] })] }), _jsxs("div", { className: "flex items-center justify-end gap-3 pt-4 border-t border-gray-200", children: [_jsx(Button, { type: "button", variant: "secondary", onClick: handleClose, disabled: sendInvitation.isPending, children: "Cancel" }), _jsx(Button, { type: "submit", variant: "primary", disabled: sendInvitation.isPending || !email || !roleType, children: sendInvitation.isPending ? 'Sending...' : 'Send Invitation' })] })] }) }));
};
