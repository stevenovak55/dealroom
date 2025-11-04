import { useState, FormEvent } from 'react';
import { Input } from '../shared/Input';
import { Button } from '../shared/Button';
import { useAuth } from '@/hooks/useAuth';

export interface TwoFactorVerifyFormProps {
  onSuccess?: () => void;
  onCancel?: () => void;
}

export const TwoFactorVerifyForm = ({ onSuccess, onCancel }: TwoFactorVerifyFormProps) => {
  const { verify2FA, verifyBackupCode, isLoading, error, clearError } = useAuth();
  const [code, setCode] = useState('');
  const [useBackupCode, setUseBackupCode] = useState(false);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    clearError();

    if (!code.trim()) {
      return;
    }

    try {
      if (useBackupCode) {
        await verifyBackupCode(code.trim());
      } else {
        await verify2FA(code.trim());
      }
      onSuccess?.();
    } catch (err) {
      // Error is already set in the auth store
    }
  };

  const toggleCodeType = () => {
    setCode('');
    setUseBackupCode(!useBackupCode);
    clearError();
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div>
        <h2 className="text-2xl font-bold text-gray-900">Two-factor authentication</h2>
        <p className="mt-2 text-sm text-gray-600">
          {useBackupCode ? (
            <>Enter one of your backup codes to sign in.</>
          ) : (
            <>Enter the 6-digit code from your authenticator app.</>
          )}
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
        label={useBackupCode ? 'Backup code' : 'Authentication code'}
        type="text"
        value={code}
        onChange={(e) => {
          const value = e.target.value.replace(/\s/g, '');
          if (useBackupCode) {
            // Backup codes are alphanumeric
            setCode(value.toUpperCase());
          } else {
            // TOTP codes are 6 digits
            if (/^\d{0,6}$/.test(value)) {
              setCode(value);
            }
          }
        }}
        required
        autoComplete="off"
        placeholder={useBackupCode ? 'XXXXXXXX' : '000000'}
        disabled={isLoading}
        autoFocus
        maxLength={useBackupCode ? 16 : 6}
        className="text-center text-2xl tracking-wider"
      />

      <div className="space-y-3">
        <Button type="submit" className="w-full" isLoading={isLoading}>
          Verify and sign in
        </Button>

        <button
          type="button"
          onClick={toggleCodeType}
          className="w-full text-center text-sm font-medium text-primary-600 hover:text-primary-500"
          disabled={isLoading}
        >
          {useBackupCode ? 'Use authenticator app code' : 'Use backup code instead'}
        </button>

        {onCancel && (
          <Button
            type="button"
            variant="ghost"
            className="w-full"
            onClick={onCancel}
            disabled={isLoading}
          >
            Cancel
          </Button>
        )}
      </div>

      <div className="rounded-md bg-gray-50 p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg
              className="h-5 w-5 text-gray-400"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fillRule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z"
                clipRule="evenodd"
              />
            </svg>
          </div>
          <div className="ml-3 flex-1">
            <p className="text-sm text-gray-700">
              {useBackupCode ? (
                <>
                  Each backup code can only be used once. After using a backup code, make sure to
                  regenerate your backup codes from your account settings.
                </>
              ) : (
                <>
                  Open your authenticator app and enter the 6-digit code. The code changes every 30
                  seconds.
                </>
              )}
            </p>
          </div>
        </div>
      </div>
    </form>
  );
};
