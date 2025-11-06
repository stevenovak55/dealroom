import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/utils/cn';
import { useIsMobile } from '@/hooks/useMediaQuery';
export const Modal = ({ isOpen, onClose, title, children, size = 'md', showCloseButton = true, }) => {
    const isMobile = useIsMobile();
    // Handle escape key
    useEffect(() => {
        const handleEscape = (e) => {
            if (e.key === 'Escape' && isOpen) {
                onClose();
            }
        };
        document.addEventListener('keydown', handleEscape);
        return () => document.removeEventListener('keydown', handleEscape);
    }, [isOpen, onClose]);
    // Body scroll lock
    useEffect(() => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
        }
        else {
            document.body.style.overflow = '';
        }
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);
    if (!isOpen)
        return null;
    // Desktop sizes (ignored on mobile where it's full-screen)
    const sizes = {
        sm: 'md:max-w-md',
        md: 'md:max-w-lg',
        lg: 'md:max-w-2xl',
        xl: 'md:max-w-4xl',
        full: 'md:max-w-7xl',
    };
    return (_jsxs("div", { className: "fixed inset-0 z-mobile-modal overflow-y-auto", role: "dialog", "aria-modal": "true", "aria-labelledby": title ? 'modal-title' : undefined, children: [_jsx("div", { className: cn('fixed inset-0 bg-black/50', 'transition-opacity duration-normal', 'animate-in fade-in'), onClick: onClose, "aria-hidden": "true" }), _jsx("div", { className: cn('flex', 
                // Mobile: Full-screen, align to bottom
                'min-h-full items-end', 
                // Desktop: Centered
                'md:min-h-full md:items-center md:justify-center md:p-4'), children: _jsxs("div", { className: cn('relative w-full bg-white shadow-xl', 
                    // Mobile: Full-screen with rounded top corners, slide up animation
                    'max-h-[90vh] rounded-t-2xl', 'animate-in slide-in-from-bottom duration-normal', 'pt-safe-top pb-safe-bottom', 
                    // Desktop: Centered dialog with all rounded corners
                    'md:max-h-[85vh] md:rounded-lg', 'md:animate-in md:fade-in md:zoom-in-95', sizes[size]), onClick: (e) => e.stopPropagation(), children: [isMobile && (_jsx("div", { className: "flex justify-center pt-3 pb-2 md:hidden", children: _jsx("div", { className: "w-12 h-1 bg-gray-300 rounded-full", "aria-hidden": "true" }) })), (title || showCloseButton) && (_jsxs("div", { className: cn('flex items-center justify-between', 'px-4 py-3', 'md:px-6 md:py-4', 'border-b border-gray-200'), children: [title && (_jsx("h2", { id: "modal-title", className: cn('font-semibold text-gray-900', 'text-lg', 'md:text-xl'), children: title })), _jsx("div", { className: "flex-1" }), showCloseButton && (_jsx("button", { type: "button", onClick: onClose, className: cn('min-h-touch min-w-touch', 'flex items-center justify-center', 'p-2 -mr-2 rounded-lg', 'text-gray-400 hover:text-gray-600 hover:bg-gray-100', 'transition-colors duration-fast'), "aria-label": "Close", children: _jsx(X, { className: "h-5 w-5" }) }))] })), _jsx("div", { className: cn('overflow-y-auto', 'px-4 py-3', 'md:px-6 md:py-4', 
                            // Calculate max height (full height - header - footer - safe areas)
                            'max-h-[calc(90vh-8rem)]', 'md:max-h-[calc(85vh-8rem)]'), children: children })] }) })] }));
};
export const ModalFooter = ({ children, className }) => {
    return (_jsx("div", { className: cn('flex items-center gap-3', 
        // Mobile: Stack buttons vertically, full width
        'flex-col-reverse', 'px-4 py-3', 
        // Desktop: Horizontal layout
        'md:flex-row md:justify-end', 'md:px-6 md:py-4', 'border-t border-gray-200 bg-gray-50', className), children: children }));
};
