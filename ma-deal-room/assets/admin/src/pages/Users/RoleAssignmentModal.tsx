import { useState } from 'react';
import { Modal } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Select } from '@/components/shared/Select';
import { Shield, Check } from 'lucide-react';
import { useAssignRole } from '@/api/queries/useUsers';
import { getRoleOptionsGrouped, getRoleLabel, ROLE_CATEGORIES, type RoleType } from '@/constants/roleTypes';

interface RoleAssignmentModalProps {
  userId: number;
  userType: 'custom' | 'wordpress';
  onClose: () => void;
}

export const RoleAssignmentModal = ({ userId, userType, onClose }: RoleAssignmentModalProps) => {
  const [selectedRole, setSelectedRole] = useState<RoleType | ''>('');
  const [transactionId, setTransactionId] = useState<string>('');
  const [isPrimary, setIsPrimary] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<string>('');

  const assignRoleMutation = useAssignRole();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!selectedRole) {
      alert('Please select a role');
      return;
    }

    try {
      await assignRoleMutation.mutateAsync({
        userId,
        userType,
        roleType: selectedRole,
        transactionId: transactionId ? parseInt(transactionId, 10) : undefined,
        isPrimary,
      });
      alert('Role assigned successfully');
      onClose();
    } catch (error) {
      alert('Failed to assign role');
    }
  };

  // Get role options for selected category
  const categoryOptions = [
    { value: '', label: 'Select a category...' },
    ...Object.entries(ROLE_CATEGORIES).map(([key, cat]) => ({
      value: key,
      label: `${cat.label} - ${cat.description}`,
    })),
  ];

  const roleOptions = selectedCategory
    ? [
        { value: '', label: 'Select a role...' },
        ...ROLE_CATEGORIES[selectedCategory as keyof typeof ROLE_CATEGORIES].roles.map((role) => ({
          value: role,
          label: getRoleLabel(role as RoleType),
        })),
      ]
    : [{ value: '', label: 'First select a category...' }];

  return (
    <Modal
      isOpen={true}
      onClose={onClose}
      title="Assign Role"
      icon={<Shield className="h-6 w-6 text-primary-600" />}
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Category Selection */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Role Category
          </label>
          <Select
            value={selectedCategory}
            onChange={(e) => {
              setSelectedCategory(e.target.value);
              setSelectedRole(''); // Reset role when category changes
            }}
            options={categoryOptions}
            required
          />
          <p className="text-xs text-gray-500 mt-1">
            Choose a category to see available roles
          </p>
        </div>

        {/* Role Selection */}
        {selectedCategory && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Role Type
            </label>
            <Select
              value={selectedRole}
              onChange={(e) => setSelectedRole(e.target.value as RoleType)}
              options={roleOptions}
              required
              disabled={!selectedCategory}
            />
            {selectedRole && (
              <div className="mt-2 p-3 bg-blue-50 rounded-lg">
                <p className="text-sm text-blue-900 font-medium">
                  {getRoleLabel(selectedRole as RoleType)}
                </p>
                <p className="text-xs text-blue-700 mt-1">
                  {/* Add role description here if needed */}
                </p>
              </div>
            )}
          </div>
        )}

        {/* Vendor Category Info */}
        {selectedCategory && selectedCategory.startsWith('vendor') && (
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p className="text-sm text-yellow-900 font-medium">
              📦 Vendor Role
            </p>
            <p className="text-xs text-yellow-700 mt-1">
              This is a service provider role. Vendors typically have limited access
              to specific transactions and tasks.
            </p>
          </div>
        )}

        {/* Transaction ID (Optional) */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Transaction ID (Optional)
          </label>
          <input
            type="number"
            className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            placeholder="Leave empty for global role"
            value={transactionId}
            onChange={(e) => setTransactionId(e.target.value)}
          />
          <p className="text-xs text-gray-500 mt-1">
            Assign this role to a specific transaction, or leave empty for global role
          </p>
        </div>

        {/* Primary Role Toggle */}
        <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
          <div>
            <p className="font-medium text-gray-900">Set as Primary Role</p>
            <p className="text-sm text-gray-500">
              This will be the user's main role in the system
            </p>
          </div>
          <label className="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              className="sr-only peer"
              checked={isPrimary}
              onChange={(e) => setIsPrimary(e.target.checked)}
            />
            <div className="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
        </div>

        {/* Actions */}
        <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
          <Button type="button" variant="secondary" onClick={onClose}>
            Cancel
          </Button>
          <Button
            type="submit"
            variant="primary"
            isLoading={assignRoleMutation.isPending}
            disabled={!selectedRole}
          >
            <Check className="h-4 w-4 mr-2" />
            Assign Role
          </Button>
        </div>
      </form>
    </Modal>
  );
};
