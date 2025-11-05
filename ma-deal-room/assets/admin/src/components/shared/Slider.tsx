import React from 'react';

interface SliderProps {
  id?: string;
  min?: number;
  max?: number;
  step?: number;
  value?: number[];
  onValueChange?: (value: number[]) => void;
  className?: string;
  disabled?: boolean;
}

export const Slider: React.FC<SliderProps> = ({
  id,
  min = 0,
  max = 100,
  step = 1,
  value = [0],
  onValueChange,
  className = '',
  disabled = false,
}) => {
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = parseFloat(e.target.value);
    onValueChange?.([newValue]);
  };

  return (
    <input
      type="range"
      id={id}
      min={min}
      max={max}
      step={step}
      value={value[0]}
      onChange={handleChange}
      disabled={disabled}
      className={`w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer ${className}`}
    />
  );
};
