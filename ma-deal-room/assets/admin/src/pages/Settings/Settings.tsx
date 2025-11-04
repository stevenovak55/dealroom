import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { Save } from 'lucide-react';
import { useGetSettings, useUpdateSettings, type Settings as SettingsType } from '@/api/queries/useSettings';

const timezoneOptions = [
  { value: 'America/New_York', label: 'Eastern Time (ET)' },
  { value: 'America/Chicago', label: 'Central Time (CT)' },
  { value: 'America/Denver', label: 'Mountain Time (MT)' },
  { value: 'America/Los_Angeles', label: 'Pacific Time (PT)' },
];

export const Settings = () => {
  const { data: settings, isLoading } = useGetSettings();
  const updateMutation = useUpdateSettings();

  const { register, handleSubmit, reset, formState: { isDirty } } = useForm<SettingsType>();

  // Reset form when settings load
  useEffect(() => {
    if (settings) {
      reset(settings);
    }
  }, [settings, reset]);

  const onSubmit = async (data: SettingsType) => {
    try {
      await updateMutation.mutateAsync(data);
      // Show success message
      alert('Settings saved successfully!');
    } catch (error) {
      console.error('Failed to save settings:', error);
      alert('Failed to save settings. Please try again.');
    }
  };

  if (isLoading) {
    return <PageLoader />;
  }

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6 max-w-4xl">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
        <p className="text-sm text-gray-500 mt-1">
          Manage your account preferences and notifications
        </p>
      </div>

      {/* Account Settings */}
      <Card>
        <CardHeader>
          <CardTitle>Account Settings</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <Input
            label="Account Name"
            placeholder="My Real Estate Agency"
            {...register('account_name')}
          />
          <Input
            label="Company Name"
            placeholder="Acme Real Estate"
            {...register('company_name')}
          />
          <Input
            label="Email"
            type="email"
            placeholder="admin@example.com"
            {...register('email')}
          />
          <Input
            label="Phone"
            type="tel"
            placeholder="(555) 123-4567"
            {...register('phone')}
          />
          <Select
            label="Timezone"
            options={timezoneOptions}
            {...register('timezone')}
          />
        </CardContent>
      </Card>

      {/* Notification Settings */}
      <Card>
        <CardHeader>
          <CardTitle>Notification Preferences</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-gray-900">Email Notifications</p>
              <p className="text-sm text-gray-500">
                Receive email updates for task deadlines
              </p>
            </div>
            <input
              type="checkbox"
              className="h-4 w-4 text-primary-600 rounded"
              {...register('notifications.email')}
            />
          </div>
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-gray-900">SMS Notifications</p>
              <p className="text-sm text-gray-500">
                Receive text messages for urgent reminders
              </p>
            </div>
            <input
              type="checkbox"
              className="h-4 w-4 text-primary-600 rounded"
              {...register('notifications.sms')}
            />
          </div>
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-gray-900">Daily Digest</p>
              <p className="text-sm text-gray-500">
                Get a summary of tasks and deadlines each morning
              </p>
            </div>
            <input
              type="checkbox"
              className="h-4 w-4 text-primary-600 rounded"
              {...register('notifications.daily_digest')}
            />
          </div>
        </CardContent>
      </Card>

      {/* Branding */}
      <Card>
        <CardHeader>
          <CardTitle>Branding</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <Input
            label="Company Logo URL"
            placeholder="https://example.com/logo.png"
            {...register('branding.logo_url')}
          />
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Primary Color
            </label>
            <input
              type="color"
              className="h-10 w-20 rounded border border-gray-300"
              {...register('branding.primary_color')}
            />
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
          Save Settings
        </Button>
      </div>
    </form>
  );
};
