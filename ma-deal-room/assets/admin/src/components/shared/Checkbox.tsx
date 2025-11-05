import React from 'react';

interface CheckboxProps {
  id?: string;
  checked?: boolean;
  onCheckedChange?: (checked: boolean) => void;
  className?: string;
  disabled?: boolean;
}

export const Checkbox: React.FC<CheckboxProps> = ({
  id,
  checked = false,
  onCheckedChange,
  className = '',
  disabled = false,
}) => {
  return (
    <input
      type="checkbox"
      id={id}
      checked={checked}
      onChange={(e) => onCheckedChange?.(e.target.checked)}
      className={`h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 ${className}`}
      disabled={disabled}
    />
  );
};
