import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { PageLoader } from '@/components/shared/Loader';
import { ArrowLeft, Save, Mail, Phone, Shield, Lock, Unlock, Trash2, Plus, X, AlertCircle, } from 'lucide-react';
import { useGetUser, useGetUserRoles, useUpdateUser, useDeleteUser, useLockUser, useUnlockUser, useRemoveRole, } from '@/api/queries/useUsers';
import { getRoleLabel, getUserStatusColor } from '@/constants/roleTypes';
import { formatDate } from '@/utils/formatDate';
import { RoleAssignmentModal } from './RoleAssignmentModal';
export const UserDetail = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const userId = parseInt(id || '0', 10);
    const userType = 'custom'; // TODO: Make this dynamic based on route or query param
    const [isRoleModalOpen, setIsRoleModalOpen] = useState(false);
    const { data: user, isLoading } = useGetUser(userId, userType);
    const { data: roles } = useGetUserRoles(userId, userType);
    const updateMutation = useUpdateUser();
    const deleteMutation = useDeleteUser();
    const lockMutation = useLockUser();
    const unlockMutation = useUnlockUser();
    const removeRoleMutation = useRemoveRole();
    const { register, handleSubmit, formState: { isDirty } } = useForm({
        values: user,
    });
    const onSubmit = async (data) => {
        try {
            await updateMutation.mutateAsync({
                id: userId,
                userType,
                data: {
                    first_name: data.first_name,
                    last_name: data.last_name,
                    phone: data.phone,
                },
            });
            alert('User updated successfully');
        }
        catch (error) {
            alert('Failed to update user');
        }
    };
    const handleDelete = async () => {
        if (!confirm(`Are you sure you want to delete this user? This action cannot be undone.`)) {
            return;
        }
        try {
            await deleteMutation.mutateAsync({ id: userId, userType });
            alert('User deleted successfully');
            navigate('/users');
        }
        catch (error) {
            alert('Failed to delete user');
        }
    };
    const handleLock = async () => {
        if (!confirm(`Are you sure you want to lock this user's account?`)) {
            return;
        }
        try {
            await lockMutation.mutateAsync({ id: userId, userType });
            alert('User account locked');
        }
        catch (error) {
            alert('Failed to lock user');
        }
    };
    const handleUnlock = async () => {
        try {
            await unlockMutation.mutateAsync({ id: userId, userType });
            alert('User account unlocked');
        }
        catch (error) {
            alert('Failed to unlock user');
        }
    };
    const handleRemoveRole = async (role) => {
        if (!confirm(`Remove ${getRoleLabel(role.role_type)} role?`)) {
            return;
        }
        try {
            await removeRoleMutation.mutateAsync({
                userId,
                roleId: role.id,
                userType,
            });
            alert('Role removed successfully');
        }
        catch (error) {
            alert('Failed to remove role');
        }
    };
    if (isLoading || !user) {
        return _jsx(PageLoader, {});
    }
    const isLocked = user.locked_until && new Date(user.locked_until) > new Date();
    const statusColor = getUserStatusColor(user.status);
    const statusColorMap = {
        green: 'bg-green-100 text-green-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        red: 'bg-red-100 text-red-800',
        gray: 'bg-gray-100 text-gray-800',
    };
    return (_jsxs("div", { className: "space-y-6 max-w-5xl", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { className: "flex items-center gap-4", children: [_jsx(Link, { to: "/users", children: _jsxs(Button, { variant: "secondary", size: "sm", children: [_jsx(ArrowLeft, { className: "h-4 w-4 mr-2" }), "Back to Users"] }) }), _jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: user.first_name && user.last_name
                                            ? `${user.first_name} ${user.last_name}`
                                            : user.email }), _jsx("p", { className: "text-sm text-gray-500 mt-1", children: user.email })] })] }), _jsxs("div", { className: "flex items-center gap-2", children: [isLocked ? (_jsxs(Button, { variant: "secondary", onClick: handleUnlock, children: [_jsx(Unlock, { className: "h-4 w-4 mr-2" }), "Unlock Account"] })) : (_jsxs(Button, { variant: "secondary", onClick: handleLock, children: [_jsx(Lock, { className: "h-4 w-4 mr-2" }), "Lock Account"] })), _jsxs(Button, { variant: "danger", onClick: handleDelete, children: [_jsx(Trash2, { className: "h-4 w-4 mr-2" }), "Delete User"] })] })] }), !user.email_verified && (_jsxs("div", { className: "bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3", children: [_jsx(AlertCircle, { className: "h-5 w-5 text-yellow-600 mt-0.5" }), _jsxs("div", { children: [_jsx("p", { className: "font-medium text-yellow-900", children: "Email Not Verified" }), _jsx("p", { className: "text-sm text-yellow-700 mt-1", children: "This user has not verified their email address yet." })] })] })), isLocked && (_jsxs("div", { className: "bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3", children: [_jsx(Lock, { className: "h-5 w-5 text-red-600 mt-0.5" }), _jsxs("div", { children: [_jsx("p", { className: "font-medium text-red-900", children: "Account Locked" }), _jsxs("p", { className: "text-sm text-red-700 mt-1", children: ["This account is locked until ", formatDate(user.locked_until), ".", user.failed_login_attempts > 0 && (_jsxs(_Fragment, { children: [" (", user.failed_login_attempts, " failed login attempts)"] }))] })] })] })), _jsxs("div", { className: "grid grid-cols-1 lg:grid-cols-3 gap-6", children: [_jsxs("div", { className: "lg:col-span-2 space-y-6", children: [_jsx("form", { onSubmit: handleSubmit(onSubmit), children: _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Profile Information" }) }), _jsxs(CardContent, { className: "space-y-4", children: [_jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4", children: [_jsx(Input, { label: "First Name", placeholder: "John", ...register('first_name') }), _jsx(Input, { label: "Last Name", placeholder: "Doe", ...register('last_name') })] }), _jsx(Input, { label: "Email", type: "email", value: user.email, disabled: true, icon: _jsx(Mail, { className: "h-4 w-4" }) }), _jsx(Input, { label: "Phone", type: "tel", placeholder: "(555) 123-4567", icon: _jsx(Phone, { className: "h-4 w-4" }), ...register('phone') }), isDirty && (_jsx("div", { className: "flex justify-end", children: _jsxs(Button, { type: "submit", variant: "primary", isLoading: updateMutation.isPending, children: [_jsx(Save, { className: "h-4 w-4 mr-2" }), "Save Changes"] }) }))] })] }) }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsx(CardTitle, { children: "Roles & Permissions" }), _jsxs(Button, { variant: "secondary", size: "sm", onClick: () => setIsRoleModalOpen(true), children: [_jsx(Plus, { className: "h-4 w-4 mr-2" }), "Assign Role"] })] }) }), _jsx(CardContent, { children: roles && roles.length > 0 ? (_jsx("div", { className: "space-y-3", children: roles.map((role) => (_jsxs("div", { className: "flex items-center justify-between p-3 bg-gray-50 rounded-lg", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx(Shield, { className: "h-5 w-5 text-gray-600" }), _jsxs("div", { children: [_jsxs("p", { className: "font-medium text-gray-900", children: [getRoleLabel(role.role_type), role.is_primary && (_jsx(Badge, { variant: "primary", className: "ml-2", children: "Primary" }))] }), role.transaction_id && (_jsxs("p", { className: "text-sm text-gray-500", children: ["Transaction #", role.transaction_id] })), _jsxs("p", { className: "text-xs text-gray-500", children: ["Assigned ", formatDate(role.assigned_at)] })] })] }), _jsx(Button, { variant: "secondary", size: "sm", onClick: () => handleRemoveRole(role), children: _jsx(X, { className: "h-4 w-4" }) })] }, role.id))) })) : (_jsxs("div", { className: "text-center py-8", children: [_jsx(Shield, { className: "h-12 w-12 text-gray-400 mx-auto mb-3" }), _jsx("p", { className: "text-gray-600 mb-2", children: "No roles assigned" }), _jsxs(Button, { variant: "secondary", size: "sm", onClick: () => setIsRoleModalOpen(true), children: [_jsx(Plus, { className: "h-4 w-4 mr-2" }), "Assign First Role"] })] })) })] })] }), _jsxs("div", { className: "space-y-6", children: [_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Account Status" }) }), _jsxs(CardContent, { className: "space-y-3", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-1", children: "Status" }), _jsx("span", { className: `inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusColorMap[statusColor]}`, children: user.status.replace('_', ' ') })] }), _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-1", children: "Email Verified" }), _jsx("p", { className: "font-medium", children: user.email_verified ? (_jsx("span", { className: "text-green-600", children: "\u2713 Verified" })) : (_jsx("span", { className: "text-yellow-600", children: "\u2717 Not Verified" })) }), user.email_verified_at && (_jsx("p", { className: "text-xs text-gray-500 mt-1", children: formatDate(user.email_verified_at) }))] }), _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-1", children: "Member Since" }), _jsx("p", { className: "font-medium", children: formatDate(user.created_at) })] }), _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-1", children: "Last Updated" }), _jsx("p", { className: "font-medium", children: formatDate(user.updated_at) })] })] })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Security Information" }) }), _jsxs(CardContent, { className: "space-y-3", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-1", children: "Last Login" }), _jsx("p", { className: "font-medium", children: user.last_login_at ? formatDate(user.last_login_at) : 'Never' }), user.last_login_ip && (_jsx("p", { className: "text-xs text-gray-500 mt-1", children: user.last_login_ip }))] }), _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-1", children: "Failed Login Attempts" }), _jsx("p", { className: "font-medium", children: user.failed_login_attempts > 0 ? (_jsx("span", { className: "text-red-600", children: user.failed_login_attempts })) : (_jsx("span", { className: "text-green-600", children: "0" })) })] }), isLocked && (_jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600 mb-1", children: "Locked Until" }), _jsx("p", { className: "font-medium text-red-600", children: formatDate(user.locked_until) })] }))] })] })] })] }), isRoleModalOpen && (_jsx(RoleAssignmentModal, { userId: userId, userType: userType, onClose: () => setIsRoleModalOpen(false) }))] }));
};
