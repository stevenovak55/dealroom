import { Routes, Route, Navigate } from 'react-router-dom';
import { ResponsiveLayout } from '@/components/Layout/ResponsiveLayout';
import { ProtectedRoute } from '@/components/ProtectedRoute';
import Dashboard from '@/pages/Dashboard/Dashboard';
import { TransactionsList } from '@/pages/Transactions/TransactionsList';
import { TransactionDetail } from '@/pages/Transactions/TransactionDetail';
import { CreateTransactionWizard } from '@/pages/Transactions/CreateTransactionWizard';
import { EditTransactionForm } from '@/pages/Transactions/EditTransactionForm';
import { TimelineView } from '@/pages/Timeline/TimelineView';
import { DocumentManager } from '@/pages/Documents/DocumentManager';
import { TemplatesList } from '@/pages/Templates/TemplatesList';
import { TemplateBuilder } from '@/pages/TemplateBuilder/TemplateBuilder';
import { TemplateAnalytics } from '@/pages/Templates/TemplateAnalytics';
import { TaskLibraryEnhanced } from '@/pages/TaskLibrary/TaskLibraryEnhanced';
import { RemindersList } from '@/pages/Reminders/RemindersList';
import { VendorRequestsList } from '@/pages/Vendors';
import { UsersList, UserDetail } from '@/pages/Users';
import { MLSDashboard } from '@/pages/MLS/MLSDashboard';
import { DocuSignSettings } from '@/pages/Integrations/DocuSign';
import CRMIntegrationPage from '@/pages/Integrations/CRM';
import { Settings } from '@/pages/Settings/Settings';
import { UserProfile } from '@/pages/UserProfile';
import {
  LoginPage,
  RegisterPage,
  ForgotPasswordPage,
  ResetPasswordPage,
  VerifyEmailPage,
  TwoFactorVerifyPage,
} from '@/pages/Auth';

export const AppRoutes = () => {
  return (
    <Routes>
      {/* Public Auth Routes */}
      <Route path="/auth/login" element={<LoginPage />} />
      <Route path="/auth/register" element={<RegisterPage />} />
      <Route path="/auth/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/auth/reset-password" element={<ResetPasswordPage />} />
      <Route path="/auth/verify-email" element={<VerifyEmailPage />} />
      <Route path="/auth/2fa-verify" element={<TwoFactorVerifyPage />} />

      {/* Protected App Routes */}
      <Route
        path="/"
        element={
          <ProtectedRoute redirectTo="/auth/login">
            <ResponsiveLayout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Dashboard />} />
        <Route path="transactions">
          <Route index element={<TransactionsList />} />
          <Route path="new" element={<CreateTransactionWizard />} />
          <Route path=":id" element={<TransactionDetail />} />
          <Route path=":id/edit" element={<EditTransactionForm />} />
          <Route path=":id/timeline" element={<TimelineView />} />
        </Route>
        <Route path="documents" element={<DocumentManager />} />
        <Route path="vendors" element={<VendorRequestsList />} />
        <Route path="users">
          <Route index element={<UsersList />} />
          <Route path=":id" element={<UserDetail />} />
        </Route>
        <Route path="templates">
          <Route index element={<TemplatesList />} />
          <Route path="new" element={<TemplateBuilder />} />
          <Route path=":id/edit" element={<TemplateBuilder />} />
          <Route path=":id/analytics" element={<TemplateAnalytics />} />
        </Route>
        <Route path="task-library" element={<TaskLibraryEnhanced />} />
        <Route path="reminders" element={<RemindersList />} />
        <Route path="mls" element={<MLSDashboard />} />
        <Route path="integrations/docusign" element={<DocuSignSettings />} />
        <Route path="integrations/crm" element={<CRMIntegrationPage />} />
        <Route path="settings" element={<Settings />} />
        <Route path="profile" element={<UserProfile />} />
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  );
};
