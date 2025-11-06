import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { Header } from './Header';
import { BottomNav, MobileHeader, MobileMenu } from '../mobile';
import { useIsMobile } from '@/hooks/useMediaQuery';
/**
 * Responsive Layout Component
 *
 * Smart layout wrapper that adapts to screen size:
 * - Mobile (<768px): Shows MobileHeader + BottomNav
 * - Desktop (>=768px): Shows Sidebar + Header
 *
 * Features:
 * - Automatic layout switching based on breakpoint
 * - Mobile menu drawer
 * - Proper content spacing
 * - Safe area insets
 */
export const ResponsiveLayout = () => {
    const isMobile = useIsMobile();
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    return (_jsx(_Fragment, { children: isMobile ? (
        // Mobile Layout
        _jsxs("div", { className: "flex flex-col min-h-screen bg-gray-50", children: [_jsx(MobileHeader, { onMenuOpen: () => setMobileMenuOpen(true) }), _jsx(MobileMenu, { isOpen: mobileMenuOpen, onClose: () => setMobileMenuOpen(false) }), _jsx("main", { className: "flex-1 pt-14 pb-16 overflow-y-auto", children: _jsx("div", { className: "w-full px-2 py-2", children: _jsx(Outlet, {}) }) }), _jsx(BottomNav, {})] })) : (
        // Desktop Layout
        _jsxs("div", { className: "flex h-screen overflow-hidden", children: [_jsx(Sidebar, {}), _jsxs("div", { className: "flex-1 flex flex-col overflow-hidden", children: [_jsx(Header, {}), _jsx("main", { className: "flex-1 overflow-y-auto bg-gray-50", children: _jsx("div", { className: "container mx-auto px-6 py-8", children: _jsx(Outlet, {}) }) })] })] })) }));
};
