import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';
/**
 * Merge Tailwind CSS classes with proper precedence
 */
export function cn(...inputs) {
    return twMerge(clsx(inputs));
}
