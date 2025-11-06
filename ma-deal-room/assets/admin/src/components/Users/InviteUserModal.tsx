import { useState } from 'react';
import { Modal } from '@/components/shared/Modal';
import { Button } from '@/components/shared/Button';
import { Input } from '@/components/shared/Input';
import { Select } from '@/components/shared/Select';
import { Label } from '@/components/shared/Label';
import { Textarea } from '@/components/shared/Textarea';
import { Mail, Shield, MessageSquare } from 'lucide-react';
import { ROLE_CATEGORIES, getRoleLabel, type RoleType } from '@/constants/roleTypes';
import { useSendInvitation } from '@/api/queries/useInvitations';

interface InviteUserModalProps {
  isOpen: boolean;
  onClose: () => void;
  accountId: number;
}

export const InviteUserModal = ({ isOpen, onClose, accountId }: InviteUserModalProps) => {
  const [email, setEmail] = useState('');
  const [roleCategory, setRoleCategory] = useState('');
  const [roleType, setRoleType] = useState<RoleType | ''>('');
  const [message, setMessage] = useState('');

  const sendInvitation = useSendInvitation();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!email || !roleType) {
      alert('Please fill in all required fields');
      return;
    }

    try {
      await sendInvitation.mutateAsync({
        email,
        role_type: roleType,
        account_id: accountId,
        message: message || undefined,
      });

      alert('Invitation sent successfully!');
      handleClose();
    } catch (error: any) {
      alert(error.message || 'Failed to send invitation');
    }
  };

  const handleClose = () => {
    setEmail('');
    setRoleCategory('');
    setRoleType('');
    setMessage('');
    onClose();
  };

  // Get role options based on selected category
  const roleOptions = roleCategory && roleCategory in ROLE_CATEGORIES
    ? ROLE_CATEGORIES[roleCategory as keyof typeof ROLE_CATEGORIES].roles.map((role: string) => ({
        value: role,
        label: getRoleLabel(role as RoleType),
      }))
    : [];

  const categoryOptions = Object.entries(ROLE_CATEGORIES).map(([key, cat]) => ({
    value: key,
    label: cat.label,
  }));

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title="Invite New User"
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Email */}
        <div>
          <Label htmlFor="email" required>
            Email Address
          </Label>
          <div className="relative mt-1">
            <Mail className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <Input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="user@example.com"
              className="pl-10"
              required
            />
          </div>
          <p className="mt-1 text-sm text-gray-500">
            The user will receive an email invitation to join
          </p>
        </div>

        {/* Role Category */}
        <div>
          <Label htmlFor="roleCategory" required>
            Role Category
          </Label>
          <div className="relative mt-1">
            <Shield className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none z-10" />
            <Select
              id="roleCategory"
              value={roleCategory}
              onChange={(e) => {
                setRoleCategory(e.target.value);
                setRoleType(''); // Reset role when category changes
              }}
              options={[
                { value: '', label: 'Select a category...' },
                ...categoryOptions,
              ]}
              className="pl-10"
              required
            />
          </div>
        </div>

        {/* Specific Role */}
        {roleCategory && (
          <div>
            <Label htmlFor="roleType" required>
              Specific Role
            </Label>
            <Select
              id="roleType"
              value={roleType}
              onChange={(e) => setRoleType(e.target.value as RoleType)}
              options={[
                { value: '', label: 'Select a role...' },
                ...roleOptions,
              ]}
              required
            />
            {roleType && (
              <p className="mt-1 text-sm text-gray-500">
                Selected: {getRoleLabel(roleType)}
              </p>
            )}
          </div>
        )}

        {/* Personal Message (Optional) */}
        <div>
          <Label htmlFor="message">
            Personal Message <span className="text-gray-400">(Optional)</span>
          </Label>
          <div className="relative mt-1">
            <MessageSquare className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
            <Textarea
              id="message"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              placeholder="Add a personal message to the invitation email..."
              rows={3}
              className="pl-10"
            />
          </div>
        </div>

        {/* Actions */}
        <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
          <Button
            type="button"
            variant="secondary"
            onClick={handleClose}
            disabled={sendInvitation.isPending}
          >
            Cancel
          </Button>
          <Button
            type="submit"
            variant="primary"
            disabled={sendInvitation.isPending || !email || !roleType}
          >
            {sendInvitation.isPending ? 'Sending...' : 'Send Invitation'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
