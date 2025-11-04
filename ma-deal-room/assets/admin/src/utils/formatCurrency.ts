/**
 * Format number as currency
 */
export const formatCurrency = (amount: number | undefined): string => {
  if (amount === undefined || amount === null) return 'Not set';

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
};

/**
 * Format number with commas
 */
export const formatNumber = (num: number | undefined): string => {
  if (num === undefined || num === null) return '0';

  return new Intl.NumberFormat('en-US').format(num);
};
