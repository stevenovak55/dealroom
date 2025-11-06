import { jsx as _jsx } from "react/jsx-runtime";
import { forwardRef } from 'react';
import { Input } from './Input';
export const DatePicker = forwardRef(({ ...props }, ref) => {
    return _jsx(Input, { ref: ref, type: "date", ...props });
});
DatePicker.displayName = 'DatePicker';
