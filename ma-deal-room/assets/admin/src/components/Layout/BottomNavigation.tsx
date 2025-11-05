import { NavLink } from 'react-router-dom';
import { Home, FileText, FolderOpen, CheckSquare, MoreHorizontal } from 'lucide-react';
import { cn } from '@/utils/cn';
import { useUIStore } from '@/store/useUIStore';

const bottomNavItems = [
  { name: 'Dashboard', href: '/', icon: Home },
  { name: 'Transactions', href: '/transactions', icon: FileText },
  { name: 'Documents', href: '/documents', icon: FolderOpen },
  { name: 'Tasks', href: '/task-library', icon: CheckSquare },
  { name: 'More', href: '#', icon: MoreHorizontal, isMore: true },
];

export const BottomNavigation = () => {
  const { toggleMobileMenu } = useUIStore();

  const handleMoreClick = (e: React.MouseEvent) => {
    e.preventDefault();
    toggleMobileMenu();
  };

  return (
    <nav className="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40 safe-area-pb">
      <div className="grid grid-cols-5 h-16">
        {bottomNavItems.map((item) => (
          item.isMore ? (
            <button
              key={item.name}
              onClick={handleMoreClick}
              className="flex flex-col items-center justify-center gap-1 text-gray-600 hover:text-primary-600 transition-colors active:scale-95"
            >
              <item.icon className="h-6 w-6" />
              <span className="text-xs font-medium">{item.name}</span>
            </button>
          ) : (
            <NavLink
              key={item.name}
              to={item.href}
              className={({ isActive }) =>
                cn(
                  'flex flex-col items-center justify-center gap-1 transition-colors active:scale-95',
                  isActive
                    ? 'text-primary-600'
                    : 'text-gray-600 hover:text-primary-600'
                )
              }
            >
              {({ isActive }) => (
                <>
                  <item.icon className={cn('h-6 w-6', isActive && 'fill-current')} />
                  <span className="text-xs font-medium">{item.name}</span>
                </>
              )}
            </NavLink>
          )
        ))}
      </div>
    </nav>
  );
};
