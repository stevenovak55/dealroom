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

  return (
    <>
      {isMobile ? (
        // Mobile Layout
        <div className="flex flex-col min-h-screen bg-gray-50">
          {/* Mobile Header */}
          <MobileHeader onMenuOpen={() => setMobileMenuOpen(true)} />

          {/* Mobile Menu Drawer */}
          <MobileMenu
            isOpen={mobileMenuOpen}
            onClose={() => setMobileMenuOpen(false)}
          />

          {/* Main Content */}
          <main className="flex-1 pt-14 pb-16 overflow-y-auto">
            <div className="w-full px-2 py-2">
              <Outlet />
            </div>
          </main>

          {/* Bottom Navigation */}
          <BottomNav />
        </div>
      ) : (
        // Desktop Layout
        <div className="flex h-screen overflow-hidden">
          {/* Sidebar */}
          <Sidebar />

          {/* Main content */}
          <div className="flex-1 flex flex-col overflow-hidden">
            {/* Header */}
            <Header />

            {/* Page content */}
            <main className="flex-1 overflow-y-auto bg-gray-50">
              <div className="container mx-auto px-6 py-8">
                <Outlet />
              </div>
            </main>
          </div>
        </div>
      )}
    </>
  );
};
