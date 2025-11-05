import { Outlet } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { Header } from './Header';
import { BottomNavigation } from './BottomNavigation';

export const AppShell = () => {
  return (
    <div className="flex h-screen overflow-hidden">
      {/* Sidebar - Desktop only, Mobile drawer */}
      <Sidebar />

      {/* Main content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <Header />

        {/* Page content */}
        <main className="flex-1 overflow-y-auto bg-gray-50 pb-16 md:pb-0">
          <div className="container mx-auto px-4 py-4 md:px-6 md:py-8 max-w-7xl">
            <Outlet />
          </div>
        </main>

        {/* Bottom Navigation - Mobile only */}
        <BottomNavigation />
      </div>
    </div>
  );
};
