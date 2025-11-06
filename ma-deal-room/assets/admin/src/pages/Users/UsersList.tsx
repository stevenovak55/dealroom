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
import {
  Users as UsersIcon,
  Search,
  UserPlus,
  Edit,
  Trash2,
  Lock,
  Unlock,
  Shield,
  Mail,
  Calendar,
  Clock,
  X,
  MailOpen,
} from 'lucide-react';
import { useGetUsers, useDeleteUser, useLockUser, useUnlockUser, type User } from '@/api/queries/useUsers';
import { useGetInvitations, useCancelInvitation } from '@/api/queries/useInvitations';
import { getRoleLabel, getUserStatusColor, ROLE_CATEGORIES, type RoleCategory } from '@/constants/roleTypes';
import { formatDate } from '@/utils/formatDate';

type TabType = 'users' | 'invitations';

export const UsersList = () => {
  const [activeTab, setActiveTab] = useState<TabType>('users');
  const [page, setPage] = useState(1);
  const [perPage] = useState(20);
  const [userType, setUserType] = useState<'custom' | 'wordpress'>('custom');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [roleFilter, setRoleFilter] = useState<string>('');
  const [categoryFilter, setCategoryFilter] = useState<RoleCategory | ''>('');
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
  const { data: invitationsData, isLoading: invitationsLoading } = useGetInvitations(
    { status: 'pending', page, per_page: perPage },
    activeTab === 'invitations' // Only fetch when on invitations tab
  );
  const cancelInvitationMutation = useCancelInvitation();

  const handleDelete = async (user: User) => {
    if (!confirm(`Are you sure you want to delete ${user.email}?`)) {
      return;
    }

    try {
      await deleteMutation.mutateAsync({ id: user.id, userType });
      alert('User deleted successfully');
    } catch (error) {
      alert('Failed to delete user');
    }
  };

  const handleLock = async (user: User) => {
    if (!confirm(`Are you sure you want to lock ${user.email}?`)) {
      return;
    }

    try {
      await lockMutation.mutateAsync({ id: user.id, userType });
      alert('User account locked');
    } catch (error) {
      alert('Failed to lock user');
    }
  };

  const handleUnlock = async (user: User) => {
    try {
      await unlockMutation.mutateAsync({ id: user.id, userType });
      alert('User account unlocked');
    } catch (error) {
      alert('Failed to unlock user');
    }
  };

  const handleCancelInvitation = async (invitationId: number, email: string) => {
    if (!confirm(`Are you sure you want to cancel the invitation to ${email}?`)) {
      return;
    }

    try {
      await cancelInvitationMutation.mutateAsync(invitationId);
      alert('Invitation cancelled successfully');
    } catch (error) {
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
      accessor: 'email' as keyof User,
      cell: (user: User) => (
        <div className="flex items-center gap-3">
          <div className="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
            <span className="text-sm font-medium text-primary-700">
              {(user.first_name?.[0] || user.email[0]).toUpperCase()}
            </span>
          </div>
          <div>
            <Link
              to={`/users/${user.id}`}
              className="font-medium text-gray-900 hover:text-primary-600"
            >
              {user.first_name && user.last_name
                ? `${user.first_name} ${user.last_name}`
                : user.email}
            </Link>
            <p className="text-sm text-gray-500">{user.email}</p>
          </div>
        </div>
      ),
    },
    {
      header: 'Role',
      accessor: 'roles' as keyof User,
      cell: (user: User) => {
        const primaryRole = user.roles?.find((r) => r.is_primary);
        const roleType = primaryRole?.role_type || 'buyer';
        return (
          <Badge>
            <Shield className="h-3 w-3 mr-1" />
            {getRoleLabel(roleType as any)}
          </Badge>
        );
      },
    },
    {
      header: 'Status',
      accessor: 'status' as keyof User,
      cell: (user: User) => {
        const color = getUserStatusColor(user.status);
        const variantMap: Record<string, 'success' | 'warning' | 'danger' | 'default'> = {
          green: 'success',
          yellow: 'warning',
          red: 'danger',
          gray: 'default',
        };
        return (
          <div className="flex items-center gap-2">
            <Badge variant={variantMap[color]}>
              {user.status.replace('_', ' ')}
            </Badge>
            {!user.email_verified && (
              <Badge variant="warning" className="text-xs">
                <Mail className="h-3 w-3 mr-1" />
                Unverified
              </Badge>
            )}
            {user.locked_until && new Date(user.locked_until) > new Date() && (
              <Badge variant="danger" className="text-xs">
                <Lock className="h-3 w-3 mr-1" />
                Locked
              </Badge>
            )}
          </div>
        );
      },
    },
    {
      header: 'Last Login',
      accessor: 'last_login_at' as keyof User,
      cell: (user: User) => (
        <div className="text-sm text-gray-600">
          {user.last_login_at ? (
            <>
              <div className="flex items-center gap-1">
                <Calendar className="h-3 w-3" />
                {formatDate(user.last_login_at)}
              </div>
              {user.last_login_ip && (
                <div className="text-xs text-gray-500 mt-1">{user.last_login_ip}</div>
              )}
            </>
          ) : (
            <span className="text-gray-400">Never</span>
          )}
        </div>
      ),
    },
    {
      header: 'Actions',
      accessor: 'id' as keyof User,
      cell: (user: User) => (
        <div className="flex items-center gap-2">
          <Link to={`/users/${user.id}`}>
            <Button variant="secondary" size="sm">
              <Edit className="h-4 w-4" />
            </Button>
          </Link>
          {user.locked_until && new Date(user.locked_until) > new Date() ? (
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handleUnlock(user)}
              title="Unlock account"
            >
              <Unlock className="h-4 w-4" />
            </Button>
          ) : (
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handleLock(user)}
              title="Lock account"
            >
              <Lock className="h-4 w-4" />
            </Button>
          )}
          <Button
            variant="danger"
            size="sm"
            onClick={() => handleDelete(user)}
            title="Delete user"
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      ),
    },
  ];

  // Invitations table columns
  const invitationColumns = [
    {
      header: 'Email',
      accessor: 'email' as const,
      cell: (invitation: any) => (
        <div className="flex items-center gap-2">
          <MailOpen className="h-4 w-4 text-gray-400" />
          <span className="font-medium text-gray-900">{invitation.email}</span>
        </div>
      ),
    },
    {
      header: 'Role',
      accessor: 'role_type' as const,
      cell: (invitation: any) => (
        <Badge>
          <Shield className="h-3 w-3 mr-1" />
          {getRoleLabel(invitation.role_type as any)}
        </Badge>
      ),
    },
    {
      header: 'Status',
      accessor: 'status' as const,
      cell: (invitation: any) => {
        const statusColors: Record<string, 'warning' | 'success' | 'danger' | 'default'> = {
          pending: 'warning',
          accepted: 'success',
          declined: 'danger',
          cancelled: 'default',
          expired: 'danger',
        };
        return (
          <Badge variant={statusColors[invitation.status] || 'default'}>
            {invitation.status}
          </Badge>
        );
      },
    },
    {
      header: 'Sent',
      accessor: 'created_at' as const,
      cell: (invitation: any) => (
        <div className="text-sm text-gray-600 flex items-center gap-1">
          <Clock className="h-3 w-3" />
          {formatDate(invitation.created_at)}
        </div>
      ),
    },
    {
      header: 'Expires',
      accessor: 'expires_at' as const,
      cell: (invitation: any) => {
        const isExpired = new Date(invitation.expires_at) < new Date();
        return (
          <div className={`text-sm ${isExpired ? 'text-red-600' : 'text-gray-600'}`}>
            {formatDate(invitation.expires_at)}
          </div>
        );
      },
    },
    {
      header: 'Actions',
      accessor: 'id' as const,
      cell: (invitation: any) => (
        <Button
          variant="danger"
          size="sm"
          onClick={() => handleCancelInvitation(invitation.id, invitation.email)}
          title="Cancel invitation"
        >
          <X className="h-4 w-4" />
        </Button>
      ),
    },
  ];

  if (isLoading || (activeTab === 'invitations' && invitationsLoading)) {
    return <PageLoader />;
  }

  if (error) {
    return (
      <div className="text-center py-12">
        <p className="text-red-600">Failed to load users</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <UsersIcon className="h-6 w-6" />
            User Management
          </h1>
          <p className="text-sm text-gray-500 mt-1">
            Manage users, roles, and permissions
          </p>
        </div>
        <Button variant="primary" onClick={() => setIsInviteModalOpen(true)}>
          <UserPlus className="h-4 w-4 mr-2" />
          Invite User
        </Button>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          <button
            onClick={() => {
              setActiveTab('users');
              setPage(1);
            }}
            className={`
              whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
              ${
                activeTab === 'users'
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }
            `}
          >
            <div className="flex items-center gap-2">
              <UsersIcon className="h-4 w-4" />
              Users
              {data?.pagination?.total !== undefined && (
                <Badge variant="info">{data.pagination?.total}</Badge>
              )}
            </div>
          </button>
          <button
            onClick={() => {
              setActiveTab('invitations');
              setPage(1);
            }}
            className={`
              whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm
              ${
                activeTab === 'invitations'
                  ? 'border-primary-500 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }
            `}
          >
            <div className="flex items-center gap-2">
              <MailOpen className="h-4 w-4" />
              Pending Invitations
              {invitationsData?.pagination?.total !== undefined && (
                <Badge variant="warning">{invitationsData.pagination?.total}</Badge>
              )}
            </div>
          </button>
        </nav>
      </div>

      {/* Filters Card (only for users tab) */}
      {activeTab === 'users' && (
        <Card>
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {/* Search */}
            <div className="lg:col-span-2">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search by name or email..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>

            {/* User Type */}
            <Select
              value={userType}
              onChange={(e) => setUserType(e.target.value as 'custom' | 'wordpress')}
              options={[
                { value: 'custom', label: 'Custom Users' },
                { value: 'wordpress', label: 'WordPress Users' },
              ]}
            />

            {/* Status Filter */}
            <Select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              options={statusOptions}
            />

            {/* Category Filter */}
            <Select
              value={categoryFilter}
              onChange={(e) => {
                setCategoryFilter(e.target.value as RoleCategory | '');
                setRoleFilter(''); // Reset role filter when category changes
              }}
              options={categoryOptions}
            />

            {/* Role Filter (shows when category selected) */}
            {categoryFilter && filteredRoleOptions.length > 0 && (
              <Select
                value={roleFilter}
                onChange={(e) => setRoleFilter(e.target.value)}
                options={[
                  { value: '', label: 'All Roles' },
                  ...filteredRoleOptions,
                ]}
              />
            )}
          </div>

          {/* Active Filters */}
          {(searchQuery || statusFilter || roleFilter || categoryFilter) && (
            <div className="mt-4 flex items-center gap-2 flex-wrap">
              <span className="text-sm text-gray-600">Active filters:</span>
              {searchQuery && (
                <Badge>
                  Search: {searchQuery}
                  <button
                    onClick={() => setSearchQuery('')}
                    className="ml-2 hover:text-gray-900"
                  >
                    ×
                  </button>
                </Badge>
              )}
              {statusFilter && (
                <Badge>
                  Status: {statusOptions.find((o) => o.value === statusFilter)?.label}
                  <button
                    onClick={() => setStatusFilter('')}
                    className="ml-2 hover:text-gray-900"
                  >
                    ×
                  </button>
                </Badge>
              )}
              {categoryFilter && (
                <Badge>
                  Category: {ROLE_CATEGORIES[categoryFilter].label}
                  <button
                    onClick={() => {
                      setCategoryFilter('');
                      setRoleFilter('');
                    }}
                    className="ml-2 hover:text-gray-900"
                  >
                    ×
                  </button>
                </Badge>
              )}
              {roleFilter && (
                <Badge>
                  Role: {getRoleLabel(roleFilter as any)}
                  <button
                    onClick={() => setRoleFilter('')}
                    className="ml-2 hover:text-gray-900"
                  >
                    ×
                  </button>
                </Badge>
              )}
              <button
                onClick={() => {
                  setSearchQuery('');
                  setStatusFilter('');
                  setRoleFilter('');
                  setCategoryFilter('');
                }}
                className="text-sm text-primary-600 hover:text-primary-700"
              >
                Clear all
              </button>
            </div>
          )}
        </CardContent>
      </Card>
      )}

      {/* Users Table */}
      {activeTab === 'users' && (
        <Card>
        <CardHeader>
          <CardTitle>
            {data?.pagination.total || 0} Users
            {userType === 'custom' ? ' (Custom)' : ' (WordPress)'}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {data?.users && data.users.length > 0 ? (
            <>
              <DataTable data={data.users} columns={columns} />

              {/* Pagination */}
              {data?.pagination?.total_pages && data.pagination.total_pages > 1 && (
                <div className="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                  <div className="text-sm text-gray-600">
                    Showing {((page - 1) * perPage) + 1} to{' '}
                    {Math.min(page * perPage, data.pagination?.total || 0)} of{' '}
                    {data.pagination?.total || 0} users
                  </div>
                  <div className="flex items-center gap-2">
                    <Button
                      variant="secondary"
                      size="sm"
                      disabled={page === 1}
                      onClick={() => setPage(page - 1)}
                    >
                      Previous
                    </Button>
                    <span className="text-sm text-gray-600">
                      Page {page} of {data.pagination?.total_pages || 1}
                    </span>
                    <Button
                      variant="secondary"
                      size="sm"
                      disabled={page === (data.pagination?.total_pages || 1)}
                      onClick={() => setPage(page + 1)}
                    >
                      Next
                    </Button>
                  </div>
                </div>
              )}
            </>
          ) : (
            <div className="text-center py-12">
              <UsersIcon className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-600 mb-2">No users found</p>
              <p className="text-sm text-gray-500">
                Try adjusting your filters or search query
              </p>
            </div>
          )}
        </CardContent>
      </Card>
      )}

      {/* Pending Invitations Table */}
      {activeTab === 'invitations' && (
        <Card>
          <CardHeader>
            <CardTitle>
              {invitationsData?.pagination.total || 0} Pending Invitations
            </CardTitle>
          </CardHeader>
          <CardContent>
            {invitationsData?.invitations && invitationsData.invitations.length > 0 ? (
              <>
                <DataTable data={invitationsData.invitations} columns={invitationColumns} />

                {/* Pagination */}
                {invitationsData?.pagination?.total_pages && invitationsData.pagination.total_pages > 1 && (
                  <div className="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                    <div className="text-sm text-gray-600">
                      Showing {((page - 1) * perPage) + 1} to{' '}
                      {Math.min(page * perPage, invitationsData.pagination?.total || 0)} of{' '}
                      {invitationsData.pagination?.total || 0} invitations
                    </div>
                    <div className="flex items-center gap-2">
                      <Button
                        variant="secondary"
                        size="sm"
                        disabled={page === 1}
                        onClick={() => setPage(page - 1)}
                      >
                        Previous
                      </Button>
                      <span className="text-sm text-gray-600">
                        Page {page} of {invitationsData.pagination?.total_pages || 1}
                      </span>
                      <Button
                        variant="secondary"
                        size="sm"
                        disabled={page === (invitationsData.pagination?.total_pages || 1)}
                        onClick={() => setPage(page + 1)}
                      >
                        Next
                      </Button>
                    </div>
                  </div>
                )}
              </>
            ) : (
              <div className="text-center py-12">
                <MailOpen className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-600 mb-2">No pending invitations</p>
                <p className="text-sm text-gray-500">
                  All invitations have been accepted, declined, or expired
                </p>
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Invite User Modal */}
      <InviteUserModal
        isOpen={isInviteModalOpen}
        onClose={() => setIsInviteModalOpen(false)}
        accountId={1} // TODO: Get from auth context or settings
      />
    </div>
  );
};
