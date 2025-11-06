import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { Badge } from '@/components/shared/Badge';
import { DataTable } from '@/components/shared/DataTable';
import { PageLoader } from '@/components/shared/Loader';
import { InviteUserModal } from '@/components/Users/InviteUserModal';
import { Users as UsersIcon, Search, UserPlus, Edit, Trash2, Lock, Unlock, Shield, Mail, Calendar, Clock, X, MailOpen, } from 'lucide-react';
import { useGetUsers, useDeleteUser, useLockUser, useUnlockUser } from '@/api/queries/useUsers';
import { useGetInvitations, useCancelInvitation } from '@/api/queries/useInvitations';
import { getRoleLabel, getUserStatusColor, ROLE_CATEGORIES } from '@/constants/roleTypes';
import { formatDate } from '@/utils/formatDate';
export const UsersList = () => {
    const [activeTab, setActiveTab] = useState('users');
    const [page, setPage] = useState(1);
    const [perPage] = useState(20);
    const [userType, setUserType] = useState('custom');
    const [statusFilter, setStatusFilter] = useState('');
    const [roleFilter, setRoleFilter] = useState('');
    const [categoryFilter, setCategoryFilter] = useState('');
    const [searchQuery, setSearchQuery] = useState('');
    const [isInviteModalOpen, setIsInviteModalOpen] = useState(false);
    const { data, isLoading, error } = useGetUsers({
        page,
        per_page: perPage,
        user_type: userType,
        status: statusFilter,
        role_type: roleFilter,
        search: searchQuery,
    });
    const deleteMutation = useDeleteUser();
    const lockMutation = useLockUser();
    const unlockMutation = useUnlockUser();
    // Invitations query (only fetch when tab is active)
    const { data: invitationsData, isLoading: invitationsLoading } = useGetInvitations(activeTab === 'invitations' ? { status: 'pending', page, per_page: perPage } : {});
    const cancelInvitationMutation = useCancelInvitation();
    const handleDelete = async (user) => {
        if (!confirm(`Are you sure you want to delete ${user.email}?`)) {
            return;
        }
        try {
            await deleteMutation.mutateAsync({ id: user.id, userType });
            alert('User deleted successfully');
        }
        catch (error) {
            alert('Failed to delete user');
        }
    };
    const handleLock = async (user) => {
        if (!confirm(`Are you sure you want to lock ${user.email}?`)) {
            return;
        }
        try {
            await lockMutation.mutateAsync({ id: user.id, userType });
            alert('User account locked');
        }
        catch (error) {
            alert('Failed to lock user');
        }
    };
    const handleUnlock = async (user) => {
        try {
            await unlockMutation.mutateAsync({ id: user.id, userType });
            alert('User account unlocked');
        }
        catch (error) {
            alert('Failed to unlock user');
        }
    };
    const handleCancelInvitation = async (invitationId, email) => {
        if (!confirm(`Are you sure you want to cancel the invitation to ${email}?`)) {
            return;
        }
        try {
            await cancelInvitationMutation.mutateAsync(invitationId);
            alert('Invitation cancelled successfully');
        }
        catch (error) {
            alert('Failed to cancel invitation');
        }
    };
    // Filter by category
    const filteredRoleOptions = categoryFilter
        ? ROLE_CATEGORIES[categoryFilter].roles.map((role) => ({
            value: role,
            label: getRoleLabel(role),
        }))
        : [];
    const categoryOptions = [
        { value: '', label: 'All Categories' },
        ...Object.entries(ROLE_CATEGORIES).map(([key, cat]) => ({
            value: key,
            label: cat.label,
        })),
    ];
    const statusOptions = [
        { value: '', label: 'All Statuses' },
        { value: 'active', label: 'Active' },
        { value: 'inactive', label: 'Inactive' },
        { value: 'suspended', label: 'Suspended' },
        { value: 'pending_verification', label: 'Pending Verification' },
    ];
    const columns = [
        {
            header: 'User',
            accessor: 'email',
            cell: (user) => (_jsxs("div", { className: "flex items-center gap-3", children: [_jsx("div", { className: "h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center", children: _jsx("span", { className: "text-sm font-medium text-primary-700", children: (user.first_name?.[0] || user.email[0]).toUpperCase() }) }), _jsxs("div", { children: [_jsx(Link, { to: `/users/${user.id}`, className: "font-medium text-gray-900 hover:text-primary-600", children: user.first_name && user.last_name
                                    ? `${user.first_name} ${user.last_name}`
                                    : user.email }), _jsx("p", { className: "text-sm text-gray-500", children: user.email })] })] })),
        },
        {
            header: 'Role',
            accessor: 'roles',
            cell: (user) => {
                const primaryRole = user.roles?.find((r) => r.is_primary);
                const roleType = primaryRole?.role_type || 'buyer';
                return (_jsxs(Badge, { children: [_jsx(Shield, { className: "h-3 w-3 mr-1" }), getRoleLabel(roleType)] }));
            },
        },
        {
            header: 'Status',
            accessor: 'status',
            cell: (user) => {
                const color = getUserStatusColor(user.status);
                const variantMap = {
                    green: 'success',
                    yellow: 'warning',
                    red: 'danger',
                    gray: 'default',
                };
                return (_jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Badge, { variant: variantMap[color], children: user.status.replace('_', ' ') }), !user.email_verified && (_jsxs(Badge, { variant: "warning", className: "text-xs", children: [_jsx(Mail, { className: "h-3 w-3 mr-1" }), "Unverified"] })), user.locked_until && new Date(user.locked_until) > new Date() && (_jsxs(Badge, { variant: "danger", className: "text-xs", children: [_jsx(Lock, { className: "h-3 w-3 mr-1" }), "Locked"] }))] }));
            },
        },
        {
            header: 'Last Login',
            accessor: 'last_login_at',
            cell: (user) => (_jsx("div", { className: "text-sm text-gray-600", children: user.last_login_at ? (_jsxs(_Fragment, { children: [_jsxs("div", { className: "flex items-center gap-1", children: [_jsx(Calendar, { className: "h-3 w-3" }), formatDate(user.last_login_at)] }), user.last_login_ip && (_jsx("div", { className: "text-xs text-gray-500 mt-1", children: user.last_login_ip }))] })) : (_jsx("span", { className: "text-gray-400", children: "Never" })) })),
        },
        {
            header: 'Actions',
            accessor: 'id',
            cell: (user) => (_jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Link, { to: `/users/${user.id}`, children: _jsx(Button, { variant: "secondary", size: "sm", children: _jsx(Edit, { className: "h-4 w-4" }) }) }), user.locked_until && new Date(user.locked_until) > new Date() ? (_jsx(Button, { variant: "secondary", size: "sm", onClick: () => handleUnlock(user), title: "Unlock account", children: _jsx(Unlock, { className: "h-4 w-4" }) })) : (_jsx(Button, { variant: "secondary", size: "sm", onClick: () => handleLock(user), title: "Lock account", children: _jsx(Lock, { className: "h-4 w-4" }) })), _jsx(Button, { variant: "danger", size: "sm", onClick: () => handleDelete(user), title: "Delete user", children: _jsx(Trash2, { className: "h-4 w-4" }) })] })),
        },
    ];
    // Invitations table columns
    const invitationColumns = [
        {
            header: 'Email',
            accessor: 'email',
            cell: (invitation) => (_jsxs("div", { className: "flex items-center gap-2", children: [_jsx(MailOpen, { className: "h-4 w-4 text-gray-400" }), _jsx("span", { className: "font-medium text-gray-900", children: invitation.email })] })),
        },
        {
            header: 'Role',
            accessor: 'role_type',
            cell: (invitation) => (_jsxs(Badge, { children: [_jsx(Shield, { className: "h-3 w-3 mr-1" }), getRoleLabel(invitation.role_type)] })),
        },
        {
            header: 'Status',
            accessor: 'status',
            cell: (invitation) => {
                const statusColors = {
                    pending: 'warning',
                    accepted: 'success',
                    declined: 'danger',
                    cancelled: 'default',
                    expired: 'danger',
                };
                return (_jsx(Badge, { variant: statusColors[invitation.status] || 'default', children: invitation.status }));
            },
        },
        {
            header: 'Sent',
            accessor: 'created_at',
            cell: (invitation) => (_jsxs("div", { className: "text-sm text-gray-600 flex items-center gap-1", children: [_jsx(Clock, { className: "h-3 w-3" }), formatDate(invitation.created_at)] })),
        },
        {
            header: 'Expires',
            accessor: 'expires_at',
            cell: (invitation) => {
                const isExpired = new Date(invitation.expires_at) < new Date();
                return (_jsx("div", { className: `text-sm ${isExpired ? 'text-red-600' : 'text-gray-600'}`, children: formatDate(invitation.expires_at) }));
            },
        },
        {
            header: 'Actions',
            accessor: 'id',
            cell: (invitation) => (_jsx(Button, { variant: "danger", size: "sm", onClick: () => handleCancelInvitation(invitation.id, invitation.email), title: "Cancel invitation", children: _jsx(X, { className: "h-4 w-4" }) })),
        },
    ];
    if (isLoading || (activeTab === 'invitations' && invitationsLoading)) {
        return _jsx(PageLoader, {});
    }
    if (error) {
        return (_jsx("div", { className: "text-center py-12", children: _jsx("p", { className: "text-red-600", children: "Failed to load users" }) }));
    }
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsxs("h1", { className: "text-2xl font-bold text-gray-900 flex items-center gap-2", children: [_jsx(UsersIcon, { className: "h-6 w-6" }), "User Management"] }), _jsx("p", { className: "text-sm text-gray-500 mt-1", children: "Manage users, roles, and permissions" })] }), _jsxs(Button, { variant: "primary", onClick: () => setIsInviteModalOpen(true), children: [_jsx(UserPlus, { className: "h-4 w-4 mr-2" }), "Invite User"] })] }), _jsx("div", { className: "border-b border-gray-200", children: _jsxs("nav", { className: "-mb-px flex space-x-8", children: [_jsx("button", { onClick: () => {
                                setActiveTab('users');
                                setPage(1);
                            }, className: `
              whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
              ${activeTab === 'users'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}
            `, children: _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(UsersIcon, { className: "h-4 w-4" }), "Users", data?.pagination.total && (_jsx(Badge, { variant: "info", children: data.pagination.total }))] }) }), _jsx("button", { onClick: () => {
                                setActiveTab('invitations');
                                setPage(1);
                            }, className: `
              whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
              ${activeTab === 'invitations'
                                ? 'border-primary-500 text-primary-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'}
            `, children: _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(MailOpen, { className: "h-4 w-4" }), "Pending Invitations", invitationsData?.pagination.total && (_jsx(Badge, { variant: "warning", children: invitationsData.pagination.total }))] }) })] }) }), activeTab === 'users' && (_jsx(Card, { children: _jsxs(CardContent, { className: "pt-6", children: [_jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4", children: [_jsx("div", { className: "lg:col-span-2", children: _jsxs("div", { className: "relative", children: [_jsx(Search, { className: "absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" }), _jsx(Input, { placeholder: "Search by name or email...", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), className: "pl-10" })] }) }), _jsx(Select, { value: userType, onChange: (e) => setUserType(e.target.value), options: [
                                        { value: 'custom', label: 'Custom Users' },
                                        { value: 'wordpress', label: 'WordPress Users' },
                                    ] }), _jsx(Select, { value: statusFilter, onChange: (e) => setStatusFilter(e.target.value), options: statusOptions }), _jsx(Select, { value: categoryFilter, onChange: (e) => {
                                        setCategoryFilter(e.target.value);
                                        setRoleFilter(''); // Reset role filter when category changes
                                    }, options: categoryOptions }), categoryFilter && filteredRoleOptions.length > 0 && (_jsx(Select, { value: roleFilter, onChange: (e) => setRoleFilter(e.target.value), options: [
                                        { value: '', label: 'All Roles' },
                                        ...filteredRoleOptions,
                                    ] }))] }), (searchQuery || statusFilter || roleFilter || categoryFilter) && (_jsxs("div", { className: "mt-4 flex items-center gap-2 flex-wrap", children: [_jsx("span", { className: "text-sm text-gray-600", children: "Active filters:" }), searchQuery && (_jsxs(Badge, { children: ["Search: ", searchQuery, _jsx("button", { onClick: () => setSearchQuery(''), className: "ml-2 hover:text-gray-900", children: "\u00D7" })] })), statusFilter && (_jsxs(Badge, { children: ["Status: ", statusOptions.find((o) => o.value === statusFilter)?.label, _jsx("button", { onClick: () => setStatusFilter(''), className: "ml-2 hover:text-gray-900", children: "\u00D7" })] })), categoryFilter && (_jsxs(Badge, { children: ["Category: ", ROLE_CATEGORIES[categoryFilter].label, _jsx("button", { onClick: () => {
                                                setCategoryFilter('');
                                                setRoleFilter('');
                                            }, className: "ml-2 hover:text-gray-900", children: "\u00D7" })] })), roleFilter && (_jsxs(Badge, { children: ["Role: ", getRoleLabel(roleFilter), _jsx("button", { onClick: () => setRoleFilter(''), className: "ml-2 hover:text-gray-900", children: "\u00D7" })] })), _jsx("button", { onClick: () => {
                                        setSearchQuery('');
                                        setStatusFilter('');
                                        setRoleFilter('');
                                        setCategoryFilter('');
                                    }, className: "text-sm text-primary-600 hover:text-primary-700", children: "Clear all" })] }))] }) })), activeTab === 'users' && (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs(CardTitle, { children: [data?.pagination.total || 0, " Users", userType === 'custom' ? ' (Custom)' : ' (WordPress)'] }) }), _jsx(CardContent, { children: data?.users && data.users.length > 0 ? (_jsxs(_Fragment, { children: [_jsx(DataTable, { data: data.users, columns: columns }), data.pagination.total_pages > 1 && (_jsxs("div", { className: "mt-6 flex items-center justify-between border-t border-gray-200 pt-4", children: [_jsxs("div", { className: "text-sm text-gray-600", children: ["Showing ", ((page - 1) * perPage) + 1, " to", ' ', Math.min(page * perPage, data.pagination.total), " of", ' ', data.pagination.total, " users"] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Button, { variant: "secondary", size: "sm", disabled: page === 1, onClick: () => setPage(page - 1), children: "Previous" }), _jsxs("span", { className: "text-sm text-gray-600", children: ["Page ", page, " of ", data.pagination.total_pages] }), _jsx(Button, { variant: "secondary", size: "sm", disabled: page === data.pagination.total_pages, onClick: () => setPage(page + 1), children: "Next" })] })] }))] })) : (_jsxs("div", { className: "text-center py-12", children: [_jsx(UsersIcon, { className: "h-12 w-12 text-gray-400 mx-auto mb-4" }), _jsx("p", { className: "text-gray-600 mb-2", children: "No users found" }), _jsx("p", { className: "text-sm text-gray-500", children: "Try adjusting your filters or search query" })] })) })] })), activeTab === 'invitations' && (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs(CardTitle, { children: [invitationsData?.pagination.total || 0, " Pending Invitations"] }) }), _jsx(CardContent, { children: invitationsData?.invitations && invitationsData.invitations.length > 0 ? (_jsxs(_Fragment, { children: [_jsx(DataTable, { data: invitationsData.invitations, columns: invitationColumns }), invitationsData.pagination.total_pages > 1 && (_jsxs("div", { className: "mt-6 flex items-center justify-between border-t border-gray-200 pt-4", children: [_jsxs("div", { className: "text-sm text-gray-600", children: ["Showing ", ((page - 1) * perPage) + 1, " to", ' ', Math.min(page * perPage, invitationsData.pagination.total), " of", ' ', invitationsData.pagination.total, " invitations"] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Button, { variant: "secondary", size: "sm", disabled: page === 1, onClick: () => setPage(page - 1), children: "Previous" }), _jsxs("span", { className: "text-sm text-gray-600", children: ["Page ", page, " of ", invitationsData.pagination.total_pages] }), _jsx(Button, { variant: "secondary", size: "sm", disabled: page === invitationsData.pagination.total_pages, onClick: () => setPage(page + 1), children: "Next" })] })] }))] })) : (_jsxs("div", { className: "text-center py-12", children: [_jsx(MailOpen, { className: "h-12 w-12 text-gray-400 mx-auto mb-4" }), _jsx("p", { className: "text-gray-600 mb-2", children: "No pending invitations" }), _jsx("p", { className: "text-sm text-gray-500", children: "All invitations have been accepted, declined, or expired" })] })) })] })), _jsx(InviteUserModal, { isOpen: isInviteModalOpen, onClose: () => setIsInviteModalOpen(false), accountId: 1 })] }));
};
