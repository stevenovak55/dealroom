import { jsx as _jsx } from "react/jsx-runtime";
import { cn } from '@/utils/cn';
export const Table = ({ children, className }) => {
    return (_jsx("div", { className: cn('overflow-x-auto -mx-4 md:mx-0', 
        // Smooth scrolling on mobile
        'overflow-scrolling-touch', 
        // Scroll snap for better mobile experience
        'snap-x snap-mandatory md:snap-none'), children: _jsx("table", { className: cn('w-full divide-y divide-gray-200', 
            // Minimum width to prevent cramping on small screens
            'min-w-[640px] md:min-w-full', className), children: children }) }));
};
export const TableHeader = ({ children, className }) => {
    return (_jsx("thead", { className: cn('bg-gray-50', 
        // Sticky header on mobile for better UX
        'sticky top-0 z-10 md:static md:z-auto', className), children: children }));
};
export const TableBody = ({ children, className }) => {
    return (_jsx("tbody", { className: cn('divide-y divide-gray-200 bg-white', className), children: children }));
};
export const TableRow = ({ children, className, onClick }) => {
    return (_jsx("tr", { className: cn(onClick && 'cursor-pointer hover:bg-gray-50 active:bg-gray-100', onClick && 'transition-colors duration-fast', className), onClick: onClick, children: children }));
};
export const TableHead = ({ children, className }) => {
    return (_jsx("th", { className: cn(
        // Mobile-first padding
        'px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider', 'md:px-6 md:py-3', className), children: children }));
};
export const TableCell = ({ children, className }) => {
    return (_jsx("td", { className: cn(
        // Mobile-first padding and text
        'px-3 py-3 text-sm text-gray-900', 'md:px-6 md:py-4', 
        // Allow wrapping on mobile for better readability
        'md:whitespace-nowrap', className), children: children }));
};
