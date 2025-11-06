/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    // Mobile-first breakpoints
    screens: {
      'xs': '320px',   // Small phones (iPhone SE)
      'sm': '640px',   // Large phones
      'md': '768px',   // Tablets portrait
      'lg': '1024px',  // Tablets landscape / Small desktops
      'xl': '1280px',  // Desktops
      '2xl': '1536px', // Large desktops
    },
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          800: '#1e40af',
          900: '#1e3a8a',
        },
        success: {
          50: '#f0fdf4',
          100: '#dcfce7',
          200: '#bbf7d0',
          300: '#86efac',
          400: '#4ade80',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          800: '#166534',
          900: '#14532d',
        },
        warning: {
          50: '#fffbeb',
          100: '#fef3c7',
          200: '#fde68a',
          300: '#fcd34d',
          400: '#fbbf24',
          500: '#f59e0b',
          600: '#d97706',
          700: '#b45309',
          800: '#92400e',
          900: '#78350f',
        },
        danger: {
          50: '#fef2f2',
          100: '#fee2e2',
          200: '#fecaca',
          300: '#fca5a5',
          400: '#f87171',
          500: '#ef4444',
          600: '#dc2626',
          700: '#b91c1c',
          800: '#991b1b',
          900: '#7f1d1d',
        },
      },
      // Mobile-optimized spacing
      spacing: {
        'mobile': '1rem',       // 16px - standard mobile spacing
        'mobile-sm': '0.75rem', // 12px - tight mobile spacing
        'mobile-lg': '1.5rem',  // 24px - generous mobile spacing
        'mobile-xl': '2rem',    // 32px - extra large mobile spacing
      },
      // Touch target sizing (iOS HIG: minimum 44x44px)
      minHeight: {
        'touch': '44px',          // Minimum touch target height
        'touch-lg': '48px',       // Comfortable touch target
        'touch-xl': '56px',       // Large touch target
      },
      minWidth: {
        'touch': '44px',          // Minimum touch target width
        'touch-lg': '48px',       // Comfortable touch target
        'touch-xl': '56px',       // Large touch target
      },
      // Mobile-first max widths
      maxWidth: {
        'mobile': '100%',         // Full width on mobile
        'mobile-content': '480px', // Max content width for mobile
      },
      // Z-index scale for mobile layers
      zIndex: {
        'mobile-nav': '50',       // Bottom navigation
        'mobile-header': '40',    // Mobile header
        'mobile-menu': '60',      // Drawer menu
        'mobile-modal': '70',     // Full-screen modals
        'mobile-toast': '80',     // Toast notifications
      },
      // Mobile-optimized font sizes
      fontSize: {
        'mobile-xs': ['0.75rem', { lineHeight: '1rem' }],    // 12px
        'mobile-sm': ['0.875rem', { lineHeight: '1.25rem' }], // 14px
        'mobile-base': ['1rem', { lineHeight: '1.5rem' }],    // 16px
        'mobile-lg': ['1.125rem', { lineHeight: '1.75rem' }], // 18px
        'mobile-xl': ['1.25rem', { lineHeight: '1.75rem' }],  // 20px
        'mobile-2xl': ['1.5rem', { lineHeight: '2rem' }],     // 24px
      },
      // Animation for mobile interactions
      transitionDuration: {
        'fast': '150ms',          // Quick transitions
        'normal': '250ms',        // Normal transitions
        'slow': '350ms',          // Slower transitions
      },
      // Safe area insets for notched devices
      padding: {
        'safe-top': 'env(safe-area-inset-top)',
        'safe-bottom': 'env(safe-area-inset-bottom)',
        'safe-left': 'env(safe-area-inset-left)',
        'safe-right': 'env(safe-area-inset-right)',
      },
    },
  },
  plugins: [],
};
