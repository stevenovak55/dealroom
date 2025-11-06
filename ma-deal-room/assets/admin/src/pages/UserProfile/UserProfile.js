import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { Button } from '@/components/shared/Button';
import { PageLoader } from '@/components/shared/Loader';
import { Save, User as UserIcon, Mail, Calendar, Shield } from 'lucide-react';
import { useGetUserProfile, useUpdateUserProfile } from '@/api/queries/useUserProfile';
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
    const { register, handleSubmit, reset, formState: { isDirty } } = useForm();
    // Reset form when profile loads
    useEffect(() => {
        if (profile) {
            reset(profile);
        }
    }, [profile, reset]);
    const onSubmit = async (data) => {
        try {
            await updateMutation.mutateAsync({
                display_name: data.display_name,
                first_name: data.first_name,
                last_name: data.last_name,
                preferences: data.preferences,
            });
            alert('Profile updated successfully!');
        }
        catch (error) {
            console.error('Failed to update profile:', error);
            alert('Failed to update profile. Please try again.');
        }
    };
    if (isLoading || !profile) {
        return _jsx(PageLoader, {});
    }
    return (_jsxs("form", { onSubmit: handleSubmit(onSubmit), className: "space-y-6 max-w-4xl", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-2xl font-bold text-gray-900", children: "User Profile" }), _jsx("p", { className: "text-sm text-gray-500 mt-1", children: "Manage your personal information and preferences" })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Profile Information" }) }), _jsx(CardContent, { children: _jsxs("div", { className: "flex items-start gap-6", children: [_jsxs("div", { className: "flex-shrink-0", children: [_jsxs("div", { className: "relative", children: [_jsx("img", { src: profile.avatar_url, alt: profile.display_name, className: "h-24 w-24 rounded-full border-2 border-gray-200" }), _jsx("div", { className: "absolute bottom-0 right-0 h-6 w-6 bg-primary-600 rounded-full flex items-center justify-center border-2 border-white", children: _jsx(UserIcon, { className: "h-3.5 w-3.5 text-white" }) })] }), _jsx("p", { className: "text-xs text-gray-500 text-center mt-2", children: _jsx("a", { href: "https://gravatar.com", target: "_blank", rel: "noopener noreferrer", className: "text-primary-600 hover:text-primary-700", children: "Change on Gravatar" }) })] }), _jsxs("div", { className: "flex-1 space-y-4", children: [_jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4", children: [_jsx(Input, { label: "First Name", placeholder: "John", ...register('first_name') }), _jsx(Input, { label: "Last Name", placeholder: "Doe", ...register('last_name') })] }), _jsx(Input, { label: "Display Name", placeholder: "John Doe", ...register('display_name') }), _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200", children: [_jsxs("div", { children: [_jsxs("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: [_jsx(Mail, { className: "h-4 w-4 inline mr-1" }), "Email"] }), _jsx("p", { className: "text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-lg", children: profile.email }), _jsx("p", { className: "text-xs text-gray-500 mt-1", children: "Contact admin to change email" })] }), _jsxs("div", { children: [_jsxs("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: [_jsx(Shield, { className: "h-4 w-4 inline mr-1" }), "Role"] }), _jsx("p", { className: "text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-lg capitalize", children: profile.role.replace('_', ' ') })] }), _jsxs("div", { children: [_jsxs("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: [_jsx(UserIcon, { className: "h-4 w-4 inline mr-1" }), "Username"] }), _jsx("p", { className: "text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-lg", children: profile.username })] }), _jsxs("div", { children: [_jsxs("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: [_jsx(Calendar, { className: "h-4 w-4 inline mr-1" }), "Member Since"] }), _jsx("p", { className: "text-sm text-gray-900 bg-gray-50 px-3 py-2 rounded-lg", children: formatDate(profile.registered_date) })] })] })] })] }) })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Preferences" }) }), _jsxs(CardContent, { className: "space-y-4", children: [_jsx(Select, { label: "Date Format", options: dateFormatOptions, ...register('preferences.date_format') }), _jsx(Select, { label: "Time Format", options: timeFormatOptions, ...register('preferences.time_format') }), _jsx(Select, { label: "Items Per Page", options: itemsPerPageOptions, ...register('preferences.items_per_page') }), _jsxs("div", { className: "flex items-center justify-between pt-4 border-t border-gray-200", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Email Notifications" }), _jsx("p", { className: "text-sm text-gray-500", children: "Receive email updates for important events" })] }), _jsx("input", { type: "checkbox", className: "h-4 w-4 text-primary-600 rounded", ...register('preferences.email_notifications') })] })] })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Security" }) }), _jsx(CardContent, { children: _jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("p", { className: "font-medium text-gray-900", children: "Password" }), _jsx("p", { className: "text-sm text-gray-500", children: "Change your WordPress password" })] }), _jsx(Button, { type: "button", variant: "secondary", onClick: () => {
                                        window.location.href = '/wp-admin/profile.php';
                                    }, children: "Change Password" })] }) })] }), _jsxs("div", { className: "flex justify-end gap-2", children: [isDirty && (_jsx("p", { className: "text-sm text-gray-500 self-center", children: "You have unsaved changes" })), _jsxs(Button, { type: "submit", variant: "primary", isLoading: updateMutation.isPending, disabled: !isDirty, children: [_jsx(Save, { className: "h-4 w-4 mr-2" }), "Save Changes"] })] })] }));
};
