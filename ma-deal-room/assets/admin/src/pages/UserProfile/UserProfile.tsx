import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { Save, User as UserIcon, Mail, Calendar, Shield } from 'lucide-react';
import { useGetUserProfile, useUpdateUserProfile, type UserProfile as UserProfileType } from '@/api/queries/useUserProfile';
import { formatDate } from '@/utils/formatDate';

const dateFormatOptions = [
  { value: 'm/d/Y', label: 'MM/DD/YYYY (e.g., 12/31/2024)' },
  { value: 'd/m/Y', label: 'DD/MM/YYYY (e.g., 31/12/2024)' },
  { value: 'Y-m-d', label: 'YYYY-MM-DD (e.g., 2024-12-31)' },
];

const timeFormatOptions = [
  { value: 'g:i A', label: '12-hour (e.g., 3:45 PM)' },
  { value: 'H:i', label: '24-hour (e.g., 15:45)' },
];

const itemsPerPageOptions = [
  { value: '10', label: '10 items' },
  { value: '25', label: '25 items' },
  { value: '50', label: '50 items' },
  { value: '100', label: '100 items' },
];

export const UserProfile = () => {
  const { data: profile, isLoading } = useGetUserProfile();
  const updateMutation = useUpdateUserProfile();

  const { register, handleSubmit, reset, formState: { isDirty } } = useForm<UserProfileType>();

  // Reset form when profile loads
  useEffect(() => {
    if (profile) {
      reset(profile);
    }
  }, [profile, reset]);

  const onSubmit = async (data: UserProfileType) => {
    try {
      await updateMutation.mutateAsync({
        display_name: data.display_name,
        first_name: data.first_name,
        last_name: data.last_name,
        preferences: data.preferences,
      });
      alert('Profile updated successfully!');
    } catch (error) {
      console.error('Failed to update profile:', error);
      alert('Failed to update profile. Please try again.');
    }
  };

  if (isLoading || !profile) {
    return <PageLoader />;
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6 max-w-4xl">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">User Profile</h1>
        <p className="text-sm text-gray-500 mt-1">
          Manage your personal information and preferences
        </p>
      </div>

      {/* Profile Card */}
      <Card>
        <CardHeader>
          <CardTitle>Profile Information</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex items-start gap-6">
            {/* Avatar */}
            <div className="flex-shrink-0">
              <div className="relative">
                <img
                  src={profile.avatar_url}
                  alt={profile.display_name}
                  className="h-24 w-24 rounded-full border-2 border-gray-200"
                />
                <div className="absolute bottom-0 right-0 h-6 w-6 bg-primary-600 rounded-full flex items-center justify-center border-2 border-white">
                  <UserIcon className="h-3.5 w-3.5 text-white" />
                </div>
              </div>
              <p className="text-xs text-gray-500 text-center mt-2">
                <a
                  href="https://gravatar.com"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary-600 hover:text-primary-700"
                >
                  Change on Gravatar
                </a>
              </p>
            </div>

            {/* Profile Fields */}
            <div className="flex-1 space-y-4">
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
                label="Display Name"
                placeholder="John Doe"
                {...register('display_name')}
              />

              {/* Read-only fields */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    <Mail className="h-4 w-4 inline mr-1" />
                    Email
                  </label>
                  <p className="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">
                    {profile.email}
                  </p>
                  <p className="text-xs text-gray-500 mt-1">
                    Contact admin to change email
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    <Shield className="h-4 w-4 inline mr-1" />
                    Role
                  </label>
                  <p className="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-lg capitalize">
                    {profile.role.replace('_', ' ')}
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    <UserIcon className="h-4 w-4 inline mr-1" />
                    Username
                  </label>
                  <p className="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">
                    {profile.username}
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    <Calendar className="h-4 w-4 inline mr-1" />
                    Member Since
                  </label>
                  <p className="text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-lg">
                    {formatDate(profile.registered_date)}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Preferences */}
      <Card>
        <CardHeader>
          <CardTitle>Preferences</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <Select
            label="Date Format"
            options={dateFormatOptions}
            {...register('preferences.date_format')}
          />

          <Select
            label="Time Format"
            options={timeFormatOptions}
            {...register('preferences.time_format')}
          />

          <Select
            label="Items Per Page"
            options={itemsPerPageOptions}
            {...register('preferences.items_per_page')}
          />

          <div className="flex items-center justify-between pt-4 border-t border-gray-200">
            <div>
              <p className="font-medium text-gray-900">Email Notifications</p>
              <p className="text-sm text-gray-500">
                Receive email updates for important events
              </p>
            </div>
            <input
              type="checkbox"
              className="h-4 w-4 text-primary-600 rounded"
              {...register('preferences.email_notifications')}
            />
          </div>
        </CardContent>
      </Card>

      {/* Security */}
      <Card>
        <CardHeader>
          <CardTitle>Security</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-gray-900">Password</p>
              <p className="text-sm text-gray-500">
                Change your WordPress password
              </p>
            </div>
            <Button
              type="button"
              variant="secondary"
              onClick={() => {
                window.location.href = '/wp-admin/profile.php';
              }}
            >
              Change Password
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Save button */}
      <div className="flex justify-end gap-2">
        {isDirty && (
          <p className="text-sm text-gray-500 self-center">You have unsaved changes</p>
        )}
        <Button
          type="submit"
          variant="primary"
          isLoading={updateMutation.isPending}
          disabled={!isDirty}
        >
          <Save className="h-4 w-4 mr-2" />
          Save Changes
        </Button>
      </div>
    </form>
  );
};
