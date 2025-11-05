import { NavLink } from 'react-router-dom';
import { Home, FileText, Layout, Library, Bell, Settings, Menu, FolderOpen, Database, FileSignature, Users, X } from 'lucide-react';
import { useUIStore } from '@/store/useUIStore';
import { cn } from '@/utils/cn';
import { useEffect } from 'react';

const navigation = [
  { name: 'Dashboard', href: '/', icon: Home },
  { name: 'Transactions', href: '/transactions', icon: FileText },
  { name: 'Documents', href: '/documents', icon: FolderOpen },
  { name: 'MLS Integration', href: '/mls', icon: Database },
  { name: 'CRM Integration', href: '/integrations/crm', icon: Users },
  { name: 'DocuSign', href: '/integrations/docusign', icon: FileSignature },
  { name: 'Templates', href: '/templates', icon: Layout },
  { name: 'Task Library', href: '/task-library', icon: Library },
  { name: 'Reminders', href: '/reminders', icon: Bell },
  { name: 'Settings', href: '/settings', icon: Settings },
];

export const Sidebar = () => {
  const { sidebarCollapsed, toggleSidebar, mobileMenuOpen, setMobileMenuOpen } = useUIStore();

  // Close mobile menu when clicking a link
  const handleLinkClick = () => {
    if (window.innerWidth < 768) {
      setMobileMenuOpen(false);
    }
  };

  // Prevent body scroll when mobile menu is open
  useEffect(() => {
    if (mobileMenuOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [mobileMenuOpen]);

  return (
    <>
      {/* Mobile backdrop */}
      {mobileMenuOpen && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden animate-fade-in"
          onClick={() => setMobileMenuOpen(false)}
        />
      )}

      {/* Sidebar */}
      <div
        className={cn(
          'flex flex-col bg-gray-900 text-white transition-all duration-300',
          // Desktop
          'hidden md:flex',
          sidebarCollapsed ? 'md:w-16' : 'md:w-64',
          // Mobile drawer
          'md:relative fixed inset-y-0 left-0 z-50',
          mobileMenuOpen ? 'flex' : 'hidden md:flex',
          mobileMenuOpen && 'w-64 animate-slide-in-left'
        )}
      >
        {/* Header */}
        <div className="flex items-center justify-between h-16 px-4 border-b border-gray-800">
          {!sidebarCollapsed && (
            <h1 className="text-lg font-bold">MA Deal Room</h1>
          )}
          <button
            onClick={() => {
              if (window.innerWidth < 768) {
                setMobileMenuOpen(false);
              } else {
                toggleSidebar();
              }
            }}
            className="p-2 rounded-lg hover:bg-gray-800 transition-colors touch-target"
          >
            {window.innerWidth < 768 ? (
              <X className="h-5 w-5" />
            ) : (
              <Menu className="h-5 w-5" />
            )}
          </button>
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
          {navigation.map((item) => (
            <NavLink
              key={item.name}
              to={item.href}
              onClick={handleLinkClick}
              className={({ isActive }) =>
                cn(
                  'flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors touch-target',
                  isActive
                    ? 'bg-primary-600 text-white'
                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                )
              }
            >
              <item.icon className="h-5 w-5 flex-shrink-0" />
              {!sidebarCollapsed && <span className="ml-3">{item.name}</span>}
            </NavLink>
          ))}
        </nav>

        {/* Footer */}
        {!sidebarCollapsed && (
          <div className="p-4 border-t border-gray-800">
            <p className="text-xs text-gray-400">Version 2.0.0</p>
          </div>
        )}
      </div>
    </>
  );
};
