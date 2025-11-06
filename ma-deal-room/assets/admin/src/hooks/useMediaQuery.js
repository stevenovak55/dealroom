import { useState, useEffect } from 'react';
/**
 * Custom hook for responsive breakpoint detection
 *
 * Breakpoints match Tailwind config:
 * - xs: 320px (Small phones)
 * - sm: 640px (Large phones)
 * - md: 768px (Tablets)
 * - lg: 1024px (Desktops)
 * - xl: 1280px (Large desktops)
 * - 2xl: 1536px (XL desktops)
 *
 * @param query - Media query string (e.g., '(min-width: 768px)')
 * @returns boolean - Whether the media query matches
 *
 * @example
 * const isMobile = useMediaQuery('(max-width: 767px)');
 * const isTablet = useMediaQuery('(min-width: 768px) and (max-width: 1023px)');
 * const isDesktop = useMediaQuery('(min-width: 1024px)');
 */
export function useMediaQuery(query) {
    const [matches, setMatches] = useState(() => {
        // Server-side rendering or initial state
        if (typeof window === 'undefined') {
            return false;
        }
        return window.matchMedia(query).matches;
    });
    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }
        const mediaQuery = window.matchMedia(query);
        // Update state initially
        setMatches(mediaQuery.matches);
        // Define handler
        const handler = (event) => {
            setMatches(event.matches);
        };
        // Modern browsers
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', handler);
            return () => mediaQuery.removeEventListener('change', handler);
        }
        // Legacy browsers
        else {
            // @ts-ignore - Legacy API
            mediaQuery.addListener(handler);
            // @ts-ignore - Legacy API
            return () => mediaQuery.removeListener(handler);
        }
    }, [query]);
    return matches;
}
/**
 * Predefined breakpoint hooks for convenience
 */
export function useIsMobile() {
    return useMediaQuery('(max-width: 767px)');
}
export function useIsTablet() {
    return useMediaQuery('(min-width: 768px) and (max-width: 1023px)');
}
export function useIsDesktop() {
    return useMediaQuery('(min-width: 1024px)');
}
export function useIsSmallPhone() {
    return useMediaQuery('(max-width: 639px)');
}
export function useIsLargeScreen() {
    return useMediaQuery('(min-width: 1280px)');
}
/**
 * Hook to detect device type
 * Returns: 'mobile', 'tablet', or 'desktop'
 */
export function useDeviceType() {
    const isMobile = useIsMobile();
    const isTablet = useIsTablet();
    if (isMobile)
        return 'mobile';
    if (isTablet)
        return 'tablet';
    return 'desktop';
}
/**
 * Hook to check if device supports touch
 */
export function useIsTouchDevice() {
    const [isTouch, setIsTouch] = useState(false);
    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }
        setIsTouch('ontouchstart' in window ||
            navigator.maxTouchPoints > 0 ||
            // @ts-ignore - Legacy API
            (navigator.msMaxTouchPoints && navigator.msMaxTouchPoints > 0));
    }, []);
    return isTouch;
}
