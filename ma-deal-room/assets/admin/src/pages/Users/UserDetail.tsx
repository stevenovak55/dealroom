import { useState } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { PageLoader } from '@/components/shared/Loader';
import {
  ArrowLeft,
  Save,
  User as UserIcon,
  Mail,
  Phone,
  Calendar,
  Shield,
  Lock,
  Unlock,
  Trash2,
  Plus,
  X,
  AlertCircle,
} from 'lucide-react';
import {
  useGetUser,
  useGetUserRoles,
  useUpdateUser,
  useDeleteUser,
  useLockUser,
  useUnlockUser,
  useRemoveRole,
  type User,
  type UserRole,
} from '@/api/queries/useUsers';
import { getRoleLabel, getUserStatusColor } from '@/constants/roleTypes';
import { formatDate } from '@/utils/formatDate';
import { RoleAssignmentModal } from './RoleAssignmentModal';

export const UserDetail = () => {
  const { id } = useParams<{ id: string }>();
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

  const { register, handleSubmit, formState: { isDirty } } = useForm<User>({
    values: user,
  });

  const onSubmit = async (data: Partial<User>) => {
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
    } catch (error) {
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
    } catch (error) {
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
    } catch (error) {
      alert('Failed to lock user');
    }
  };

  const handleUnlock = async () => {
    try {
      await unlockMutation.mutateAsync({ id: userId, userType });
      alert('User account unlocked');
    } catch (error) {
      alert('Failed to unlock user');
    }
  };

  const handleRemoveRole = async (role: UserRole) => {
    if (!confirm(`Remove ${getRoleLabel(role.role_type as any)} role?`)) {
      return;
    }

    try {
      await removeRoleMutation.mutateAsync({
        userId,
        roleId: role.id,
        userType,
      });
      alert('Role removed successfully');
    } catch (error) {
      alert('Failed to remove role');
    }
  };

  if (isLoading || !user) {
    return <PageLoader />;
  }

  const isLocked = user.locked_until && new Date(user.locked_until) > new Date();
  const statusColor = getUserStatusColor(user.status);
  const statusColorMap: Record<string, string> = {
    green: 'bg-green-100 text-green-800',
    yellow: 'bg-yellow-100 text-yellow-800',
    red: 'bg-red-100 text-red-800',
    gray: 'bg-gray-100 text-gray-800',
  };

  return (
    <div className="space-y-6 max-w-5xl">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link to="/users">
            <Button variant="secondary" size="sm">
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back to Users
            </Button>
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              {user.first_name && user.last_name
                ? `${user.first_name} ${user.last_name}`
                : user.email}
            </h1>
            <p className="text-sm text-gray-500 mt-1">{user.email}</p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          {isLocked ? (
            <Button variant="secondary" onClick={handleUnlock}>
              <Unlock className="h-4 w-4 mr-2" />
              Unlock Account
            </Button>
          ) : (
            <Button variant="secondary" onClick={handleLock}>
              <Lock className="h-4 w-4 mr-2" />
              Lock Account
            </Button>
          )}
          <Button variant="danger" onClick={handleDelete}>
            <Trash2 className="h-4 w-4 mr-2" />
            Delete User
          </Button>
        </div>
      </div>

      {/* Status Alerts */}
      {!user.email_verified && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="h-5 w-5 text-yellow-600 mt-0.5" />
          <div>
            <p className="font-medium text-yellow-900">Email Not Verified</p>
            <p className="text-sm text-yellow-700 mt-1">
              This user has not verified their email address yet.
            </p>
          </div>
        </div>
      )}

      {isLocked && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
          <Lock className="h-5 w-5 text-red-600 mt-0.5" />
          <div>
            <p className="font-medium text-red-900">Account Locked</p>
            <p className="text-sm text-red-700 mt-1">
              This account is locked until {formatDate(user.locked_until!)}.
              {user.failed_login_attempts > 0 && (
                <> ({user.failed_login_attempts} failed login attempts)</>
              )}
            </p>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Info */}
        <div className="lg:col-span-2 space-y-6">
          {/* Profile Information */}
          <form onSubmit={handleSubmit(onSubmit)}>
            <Card>
              <CardHeader>
                <CardTitle>Profile Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <Input
                    label="First Name"
                    placeholder="John"
                    {...register('first_name')}
                  />
                  <Input
                    label="Last Name"
                    placeholder="Doe"
                    {...register('last_name')}
                  />
                </div>

                <Input
                  label="Email"
                  type="email"
                  value={user.email}
                  disabled
                  icon={<Mail className="h-4 w-4" />}
                />

                <Input
                  label="Phone"
                  type="tel"
                  placeholder="(555) 123-4567"
                  icon={<Phone className="h-4 w-4" />}
                  {...register('phone')}
                />

                {isDirty && (
                  <div className="flex justify-end">
                    <Button
                      type="submit"
                      variant="primary"
                      isLoading={updateMutation.isPending}
                    >
                      <Save className="h-4 w-4 mr-2" />
                      Save Changes
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          </form>

          {/* Roles & Permissions */}
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle>Roles & Permissions</CardTitle>
                <Button
                  variant="secondary"
                  size="sm"
                  onClick={() => setIsRoleModalOpen(true)}
                >
                  <Plus className="h-4 w-4 mr-2" />
                  Assign Role
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {roles && roles.length > 0 ? (
                <div className="space-y-3">
                  {roles.map((role) => (
                    <div
                      key={role.id}
                      className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                    >
                      <div className="flex items-center gap-3">
                        <Shield className="h-5 w-5 text-gray-600" />
                        <div>
                          <p className="font-medium text-gray-900">
                            {getRoleLabel(role.role_type as any)}
                            {role.is_primary && (
                              <Badge variant="primary" className="ml-2">
                                Primary
                              </Badge>
                            )}
                          </p>
                          {role.transaction_id && (
                            <p className="text-sm text-gray-500">
                              Transaction #{role.transaction_id}
                            </p>
                          )}
                          <p className="text-xs text-gray-500">
                            Assigned {formatDate(role.assigned_at)}
                          </p>
                        </div>
                      </div>
                      <Button
                        variant="secondary"
                        size="sm"
                        onClick={() => handleRemoveRole(role)}
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8">
                  <Shield className="h-12 w-12 text-gray-400 mx-auto mb-3" />
                  <p className="text-gray-600 mb-2">No roles assigned</p>
                  <Button
                    variant="secondary"
                    size="sm"
                    onClick={() => setIsRoleModalOpen(true)}
                  >
                    <Plus className="h-4 w-4 mr-2" />
                    Assign First Role
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Account Status */}
          <Card>
            <CardHeader>
              <CardTitle>Account Status</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div>
                <p className="text-sm text-gray-600 mb-1">Status</p>
                <span
                  className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
                    statusColorMap[statusColor]
                  }`}
                >
                  {user.status.replace('_', ' ')}
                </span>
              </div>

              <div>
                <p className="text-sm text-gray-600 mb-1">Email Verified</p>
                <p className="font-medium">
                  {user.email_verified ? (
                    <span className="text-green-600">✓ Verified</span>
                  ) : (
                    <span className="text-yellow-600">✗ Not Verified</span>
                  )}
                </p>
                {user.email_verified_at && (
                  <p className="text-xs text-gray-500 mt-1">
                    {formatDate(user.email_verified_at)}
                  </p>
                )}
              </div>

              <div>
                <p className="text-sm text-gray-600 mb-1">Member Since</p>
                <p className="font-medium">{formatDate(user.created_at)}</p>
              </div>

              <div>
                <p className="text-sm text-gray-600 mb-1">Last Updated</p>
                <p className="font-medium">{formatDate(user.updated_at)}</p>
              </div>
            </CardContent>
          </Card>

          {/* Security Info */}
          <Card>
            <CardHeader>
              <CardTitle>Security Information</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div>
                <p className="text-sm text-gray-600 mb-1">Last Login</p>
                <p className="font-medium">
                  {user.last_login_at ? formatDate(user.last_login_at) : 'Never'}
                </p>
                {user.last_login_ip && (
                  <p className="text-xs text-gray-500 mt-1">{user.last_login_ip}</p>
                )}
              </div>

              <div>
                <p className="text-sm text-gray-600 mb-1">Failed Login Attempts</p>
                <p className="font-medium">
                  {user.failed_login_attempts > 0 ? (
                    <span className="text-red-600">{user.failed_login_attempts}</span>
                  ) : (
                    <span className="text-green-600">0</span>
                  )}
                </p>
              </div>

              {isLocked && (
                <div>
                  <p className="text-sm text-gray-600 mb-1">Locked Until</p>
                  <p className="font-medium text-red-600">
                    {formatDate(user.locked_until!)}
                  </p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Role Assignment Modal */}
      {isRoleModalOpen && (
        <RoleAssignmentModal
          userId={userId}
          userType={userType}
          onClose={() => setIsRoleModalOpen(false)}
        />
      )}
    </div>
  );
};
