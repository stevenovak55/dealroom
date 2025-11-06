import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { Save } from 'lucide-react';
import { useGetSettings, useUpdateSettings } from '@/api/queries/useSettings';
const timezoneOptions = [
    { value: 'America/New_York', label: 'Eastern Time (ET)' },
    { value: 'America/Chicago', label: 'Central Time (CT)' },
    { value: 'America/Denver', label: 'Mountain Time (MT)' },
    { value: 'America/Los_Angeles', label: 'Pacific Time (PT)' },
];
export const Settings = () => {
    const { data: settings, isLoading } = useGetSettings();
    const updateMutation = useUpdateSettings();
    const { register, handleSubmit, reset, formState: { isDirty } } = useForm();
    // Reset form when settings load
    useEffect(() => {
        if (settings) {
            reset(settings);
        }
    }, [settings, reset]);
    const onSubmit = async (data) => {
        try {
            await updateMutation.mutateAsync(data);
            // Show success message
            alert('Settings saved successfully!');
        }
        catch (error) {
            console.error('Failed to save settings:', error);
            alert('Failed to save settings. Please try again.');
        }
    };
    if (isLoading) {
        return _jsx(PageLoader, {});
    }
    return (_jsxs("form", { onSubmit: handleSubmit(onSubmit), className: "space-y-6 max-w-4xl", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: "Settings" }), _jsx("p", { className: "text-sm text-gray-500 mt-1", children: "Manage your account preferences and notifications" })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Account Settings" }) }), _jsxs(CardContent, { className: "space-y-4", children: [_jsx(Input, { label: "Account Name", placeholder: "My Real Estate Agency", ...register('account_name') }), _jsx(Input, { label: "Company Name", placeholder: "Acme Real Estate", ...register('company_name') }), _jsx(Input, { label: "Email", type: "email", placeholder: "admin@example.com", ...register('email') }), _jsx(Input, { label: "Phone", type: "tel", placeholder: "(555) 123-4567", ...register('phone') }), _jsx(Select, { label: "Timezone", options: timezoneOptions, ...register('timezone') })] })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Notification Preferences" }) }), _jsxs(CardContent, { className: "space-y-4", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Email Notifications" }), _jsx("p", { className: "text-sm text-gray-500", children: "Receive email updates for task deadlines" })] }), _jsx("input", { type: "checkbox", className: "h-4 w-4 text-primary-600 rounded", ...register('notifications.email') })] }), _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "SMS Notifications" }), _jsx("p", { className: "text-sm text-gray-500", children: "Receive text messages for urgent reminders" })] }), _jsx("input", { type: "checkbox", className: "h-4 w-4 text-primary-600 rounded", ...register('notifications.sms') })] }), _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Daily Digest" }), _jsx("p", { className: "text-sm text-gray-500", children: "Get a summary of tasks and deadlines each morning" })] }), _jsx("input", { type: "checkbox", className: "h-4 w-4 text-primary-600 rounded", ...register('notifications.daily_digest') })] })] })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Branding" }) }), _jsxs(CardContent, { className: "space-y-4", children: [_jsx(Input, { label: "Company Logo URL", placeholder: "https://example.com/logo.png", ...register('branding.logo_url') }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Primary Color" }), _jsx("input", { type: "color", className: "h-10 w-20 rounded border border-gray-300", ...register('branding.primary_color') })] })] })] }), _jsxs("div", { className: "flex justify-end gap-2", children: [isDirty && (_jsx("p", { className: "text-sm text-gray-500 self-center", children: "You have unsaved changes" })), _jsxs(Button, { type: "submit", variant: "primary", isLoading: updateMutation.isPending, disabled: !isDirty, children: [_jsx(Save, { className: "h-4 w-4 mr-2" }), "Save Settings"] })] })] }));
};
