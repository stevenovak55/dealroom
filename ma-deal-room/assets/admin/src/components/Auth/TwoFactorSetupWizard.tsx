import { useState, FormEvent } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { twoFactorApi } from '@/api/services/auth';

export interface TwoFactorSetupWizardProps {
  onSuccess?: () => void;
  onCancel?: () => void;
}

interface SetupData {
  secret: string;
  qr_code: string;
  backup_codes: string[];
}

type Step = 'scan' | 'verify' | 'backup-codes' | 'complete';

export const TwoFactorSetupWizard = ({ onSuccess, onCancel }: TwoFactorSetupWizardProps) => {
  const [step, setStep] = useState<Step>('scan');
  const [setupData, setSetupData] = useState<SetupData | null>(null);
  const [verificationCode, setVerificationCode] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleStartSetup = async () => {
    setIsLoading(true);
    setError(null);

    try {
      const data = await twoFactorApi.enable2FA();
      setSetupData(data);
      setStep('scan');
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to initialize 2FA setup');
    } finally {
      setIsLoading(false);
    }
  };

  const handleVerify = async (e: FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError(null);

    try {
      await twoFactorApi.verifySetup(verificationCode);
      setStep('backup-codes');
    } catch (err: any) {
      setError(err.response?.data?.message || 'Invalid verification code');
    } finally {
      setIsLoading(false);
    }
  };

  const handleComplete = () => {
    setStep('complete');
    onSuccess?.();
  };

  const handleDownloadBackupCodes = () => {
    if (!setupData?.backup_codes) return;

    const content = [
      'MA Deal Room - Two-Factor Authentication Backup Codes',
      '',
      'Keep these codes in a safe place. Each code can only be used once.',
      'Generated: ' + new Date().toLocaleString(),
      '',
      ...setupData.backup_codes.map((code, i) => `${i + 1}. ${code}`),
    ].join('\n');

    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'ma-deal-room-backup-codes.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
  };

  // Initial state - not started
  if (!setupData && step === 'scan') {
    return (
      <div className="space-y-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">
            Enable two-factor authentication
          </h2>
          <p className="mt-2 text-sm text-gray-600">
            Add an extra layer of security to your account by requiring a verification code in
            addition to your password.
          </p>
        </div>

        {error && (
          <div className="rounded-md bg-danger-50 p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg
                  className="h-5 w-5 text-danger-400"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-danger-800">{error}</h3>
              </div>
            </div>
          </div>
        )}

        <div className="rounded-lg border border-gray-200 bg-gray-50 p-6">
          <h3 className="text-lg font-medium text-gray-900">How it works:</h3>
          <ol className="mt-4 space-y-3 text-sm text-gray-600">
            <li className="flex">
              <span className="mr-3 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700">
                1
              </span>
              <span>Download an authenticator app on your phone (Google Authenticator, Authy, etc.)</span>
            </li>
            <li className="flex">
              <span className="mr-3 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700">
                2
              </span>
              <span>Scan the QR code with your authenticator app</span>
            </li>
            <li className="flex">
              <span className="mr-3 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700">
                3
              </span>
              <span>Enter the 6-digit code from your app to verify</span>
            </li>
            <li className="flex">
              <span className="mr-3 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-medium text-primary-700">
                4
              </span>
              <span>Save your backup codes in a secure location</span>
            </li>
          </ol>
        </div>

        <div className="flex space-x-3">
          <Button onClick={handleStartSetup} isLoading={isLoading} className="flex-1">
            Get started
          </Button>
          {onCancel && (
            <Button variant="secondary" onClick={onCancel} disabled={isLoading}>
              Cancel
            </Button>
          )}
        </div>
      </div>
    );
  }

  // Step 1: Scan QR code
  if (step === 'scan' && setupData) {
    return (
      <div className="space-y-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Scan QR code</h2>
          <p className="mt-2 text-sm text-gray-600">
            Open your authenticator app and scan this QR code.
          </p>
        </div>

        <div className="flex flex-col items-center space-y-4 rounded-lg border border-gray-200 bg-white p-6">
          <div className="rounded-lg bg-white p-4">
            <img src={setupData.qr_code} alt="QR Code" className="h-48 w-48" />
          </div>

          <div className="text-center">
            <p className="text-sm font-medium text-gray-700">Can't scan the code?</p>
            <p className="mt-1 text-xs text-gray-500">
              Enter this code manually in your app:
            </p>
            <code className="mt-2 block rounded bg-gray-100 px-3 py-2 text-sm font-mono text-gray-900">
              {setupData.secret}
            </code>
          </div>
        </div>

        <Button onClick={() => setStep('verify')} className="w-full">
          Next: Verify code
        </Button>
      </div>
    );
  }

  // Step 2: Verify code
  if (step === 'verify' && setupData) {
    return (
      <form onSubmit={handleVerify} className="space-y-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Verify your setup</h2>
          <p className="mt-2 text-sm text-gray-600">
            Enter the 6-digit code from your authenticator app to complete setup.
          </p>
        </div>

        {error && (
          <div className="rounded-md bg-danger-50 p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg
                  className="h-5 w-5 text-danger-400"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    fillRule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                    clipRule="evenodd"
                  />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-danger-800">{error}</h3>
              </div>
            </div>
          </div>
        )}

        <Input
          label="Verification code"
          type="text"
          value={verificationCode}
          onChange={(e) => {
            const value = e.target.value.replace(/\D/g, '');
            if (value.length <= 6) {
              setVerificationCode(value);
            }
          }}
          required
          autoComplete="off"
          placeholder="000000"
          disabled={isLoading}
          autoFocus
          maxLength={6}
          className="text-center text-2xl tracking-wider"
        />

        <div className="flex space-x-3">
          <Button
            type="button"
            variant="secondary"
            onClick={() => setStep('scan')}
            disabled={isLoading}
          >
            Back
          </Button>
          <Button type="submit" isLoading={isLoading} className="flex-1">
            Verify and continue
          </Button>
        </div>
      </form>
    );
  }

  // Step 3: Save backup codes
  if (step === 'backup-codes' && setupData) {
    return (
      <div className="space-y-6">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Save your backup codes</h2>
          <p className="mt-2 text-sm text-gray-600">
            Store these codes in a safe place. You can use them to access your account if you lose
            your phone.
          </p>
        </div>

        <div className="rounded-md bg-warning-50 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg
                className="h-5 w-5 text-warning-400"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="ml-3">
              <h3 className="text-sm font-medium text-warning-800">
                Each code can only be used once
              </h3>
              <p className="mt-1 text-sm text-warning-700">
                Download or write down these codes now. You won't be able to see them again.
              </p>
            </div>
          </div>
        </div>

        <div className="rounded-lg border border-gray-200 bg-gray-50 p-6">
          <div className="grid grid-cols-2 gap-3 font-mono text-sm">
            {setupData.backup_codes.map((code) => (
              <div
                key={code}
                className="rounded bg-white px-3 py-2 text-center text-gray-900 shadow-sm"
              >
                {code}
              </div>
            ))}
          </div>
        </div>

        <div className="flex space-x-3">
          <Button
            variant="secondary"
            onClick={handleDownloadBackupCodes}
            className="flex-1"
          >
            Download codes
          </Button>
          <Button onClick={handleComplete} className="flex-1">
            I've saved my codes
          </Button>
        </div>
      </div>
    );
  }

  // Step 4: Complete
  if (step === 'complete') {
    return (
      <div className="space-y-6">
        <div className="text-center">
          <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-success-100">
            <svg
              className="h-6 w-6 text-success-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <h2 className="mt-4 text-2xl font-bold text-gray-900">
            Two-factor authentication enabled!
          </h2>
          <p className="mt-2 text-sm text-gray-600">
            Your account is now protected with two-factor authentication. You'll need to enter a
            code from your authenticator app each time you sign in.
          </p>
        </div>

        <div className="rounded-lg border border-gray-200 bg-gray-50 p-4">
          <h3 className="text-sm font-medium text-gray-900">What happens next?</h3>
          <ul className="mt-2 space-y-2 text-sm text-gray-600">
            <li className="flex items-start">
              <svg
                className="mr-2 h-5 w-5 flex-shrink-0 text-success-500"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                  clipRule="evenodd"
                />
              </svg>
              <span>You'll be asked for a code when signing in from a new device</span>
            </li>
            <li className="flex items-start">
              <svg
                className="mr-2 h-5 w-5 flex-shrink-0 text-success-500"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                  clipRule="evenodd"
                />
              </svg>
              <span>Keep your backup codes safe in case you lose access to your phone</span>
            </li>
            <li className="flex items-start">
              <svg
                className="mr-2 h-5 w-5 flex-shrink-0 text-success-500"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                  clipRule="evenodd"
                />
              </svg>
              <span>You can regenerate backup codes from your account settings anytime</span>
            </li>
          </ul>
        </div>

        <Button onClick={onSuccess} className="w-full">
          Done
        </Button>
      </div>
    );
  }

  return null;
};
