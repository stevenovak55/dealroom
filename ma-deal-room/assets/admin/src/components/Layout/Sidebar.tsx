import { NavLink } from 'react-router-dom';
import { Home, FileText, Layout, Library, Bell, Settings, Menu, FolderOpen, Database, FileSignature, Users, Briefcase } from 'lucide-react';
import { useUIStore } from '@/store/useUIStore';
import { cn } from '@/utils/cn';

const navigation = [
  { name: 'Dashboard', href: '/', icon: Home },
  { name: 'Transactions', href: '/transactions', icon: FileText },
  { name: 'Vendors', href: '/vendors', icon: Briefcase },
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
  const { sidebarCollapsed, toggleSidebar } = useUIStore();

  return (
    <div
      className={cn(
        'flex flex-col bg-gray-900 text-white transition-all duration-300',
        sidebarCollapsed ? 'w-16' : 'w-64'
      )}
    >
      {/* Header */}
      <div className="flex items-center justify-between h-16 px-4 border-b border-gray-800">
        {!sidebarCollapsed && (
          <h1 className="text-lg font-bold">MA Deal Room</h1>
        )}
        <button
          onClick={toggleSidebar}
          className="p-2 rounded-lg hover:bg-gray-800 transition-colors"
        >
          <Menu className="h-5 w-5" />
        </button>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-2 py-4 space-y-1">
        {navigation.map((item) => (
          <NavLink
            key={item.name}
            to={item.href}
            className={({ isActive }) =>
              cn(
                'flex items-center px-3 py-2 rounded-lg text-sm font-medium transition-colors',
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
          <p className="text-xs text-gray-400">Version 1.0.0</p>
        </div>
      )}
    </div>
  );
};
