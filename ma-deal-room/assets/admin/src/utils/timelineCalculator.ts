/**
 * MA Real Estate Timeline Calculator
 *
 * Calculates standard Massachusetts real estate transaction milestone dates
 * based on offer acceptance date and standard timelines.
 *
 * Standard MA Timeline (after offer acceptance):
 * - Inspection Contingency: 7 days after offer acceptance
 * - P&S Agreement: 10 days after offer acceptance
 * - Loan Commitment: 3 weeks (21 days) after P&S
 * - Closing: 2 weeks (14 days) after loan commitment
 */

/**
 * Add business days to a date (Monday-Friday, excluding weekends)
 */
export function addBusinessDays(date: Date, days: number): Date {
  const result = new Date(date);
  let addedDays = 0;

  while (addedDays < days) {
    result.setDate(result.getDate() + 1);
    const dayOfWeek = result.getDay();

    // Skip weekends (0 = Sunday, 6 = Saturday)
    if (dayOfWeek !== 0 && dayOfWeek !== 6) {
      addedDays++;
    }
  }

  return result;
}

/**
 * Add calendar days to a date
 */
export function addDays(date: Date, days: number): Date {
  const result = new Date(date);
  result.setDate(result.getDate() + days);
  return result;
}

/**
 * Format date as YYYY-MM-DD for HTML date inputs
 */
export function formatDateForInput(date: Date): string {
  return date.toISOString().split('T')[0];
}

/**
 * Parse date string and return Date object
 */
export function parseDate(dateString: string): Date | null {
  if (!dateString) return null;
  const parsed = new Date(dateString);
  return isNaN(parsed.getTime()) ? null : parsed;
}

/**
 * Calculate all milestone dates based on offer acceptance date
 * Following MA standard timeline
 */
export interface MilestoneCalculation {
  offerAcceptedDate: Date;
  inspectionContingencyDate: Date;
  psAgreementDate: Date;
  loanCommitmentDate: Date;
  closingDate: Date;
}

export function calculateMilestonesFromOfferAcceptance(
  offerAcceptedDate: string | Date
): MilestoneCalculation | null {
  const baseDate = typeof offerAcceptedDate === 'string'
    ? parseDate(offerAcceptedDate)
    : offerAcceptedDate;

  if (!baseDate) return null;

  // Inspection contingency: 7 calendar days after offer acceptance
  const inspectionContingencyDate = addDays(baseDate, 7);

  // P&S Agreement: 10 calendar days after offer acceptance
  const psAgreementDate = addDays(baseDate, 10);

  // Loan commitment: 3 weeks (21 calendar days) after P&S
  const loanCommitmentDate = addDays(psAgreementDate, 21);

  // Closing: 2 weeks (14 calendar days) after loan commitment
  const closingDate = addDays(loanCommitmentDate, 14);

  return {
    offerAcceptedDate: baseDate,
    inspectionContingencyDate,
    psAgreementDate,
    loanCommitmentDate,
    closingDate,
  };
}

/**
 * Calculate P&S date from offer acceptance (10 days)
 */
export function calculatePSDate(offerAcceptedDate: string | Date): Date | null {
  const baseDate = typeof offerAcceptedDate === 'string'
    ? parseDate(offerAcceptedDate)
    : offerAcceptedDate;

  if (!baseDate) return null;
  return addDays(baseDate, 10);
}

/**
 * Calculate loan commitment date from P&S date (21 days)
 */
export function calculateLoanCommitmentDate(psDate: string | Date): Date | null {
  const baseDate = typeof psDate === 'string'
    ? parseDate(psDate)
    : psDate;

  if (!baseDate) return null;
  return addDays(baseDate, 21);
}

/**
 * Calculate closing date from loan commitment date (14 days)
 */
export function calculateClosingDate(loanCommitmentDate: string | Date): Date | null {
  const baseDate = typeof loanCommitmentDate === 'string'
    ? parseDate(loanCommitmentDate)
    : loanCommitmentDate;

  if (!baseDate) return null;
  return addDays(baseDate, 14);
}

/**
 * Calculate closing date from P&S date (35 days total: 21 + 14)
 */
export function calculateClosingDateFromPS(psDate: string | Date): Date | null {
  const baseDate = typeof psDate === 'string'
    ? parseDate(psDate)
    : psDate;

  if (!baseDate) return null;
  return addDays(baseDate, 35);
}

/**
 * Suggest dates based on what's already entered
 * Returns suggestions for empty fields based on filled ones
 */
export interface DateSuggestions {
  listing_date?: string;
  offer_accepted_date?: string;
  ps_agreement_date?: string;
  loan_commitment_date?: string;
  closing_date?: string;
}

export function suggestMilestoneDates(existingDates: {
  offer_accepted_date?: string;
  ps_agreement_date?: string;
  loan_commitment_date?: string;
  closing_date?: string;
}): DateSuggestions {
  const suggestions: DateSuggestions = {};

  // If offer acceptance is set, calculate forward
  if (existingDates.offer_accepted_date) {
    const milestones = calculateMilestonesFromOfferAcceptance(existingDates.offer_accepted_date);
    if (milestones) {
      if (!existingDates.ps_agreement_date) {
        suggestions.ps_agreement_date = formatDateForInput(milestones.psAgreementDate);
      }
      if (!existingDates.loan_commitment_date) {
        suggestions.loan_commitment_date = formatDateForInput(milestones.loanCommitmentDate);
      }
      if (!existingDates.closing_date) {
        suggestions.closing_date = formatDateForInput(milestones.closingDate);
      }
    }
  }
  // If P&S is set but not offer acceptance, calculate forward from P&S
  else if (existingDates.ps_agreement_date) {
    if (!existingDates.loan_commitment_date) {
      const loanDate = calculateLoanCommitmentDate(existingDates.ps_agreement_date);
      if (loanDate) {
        suggestions.loan_commitment_date = formatDateForInput(loanDate);
      }
    }
    if (!existingDates.closing_date) {
      const closingDate = calculateClosingDateFromPS(existingDates.ps_agreement_date);
      if (closingDate) {
        suggestions.closing_date = formatDateForInput(closingDate);
      }
    }
  }
  // If loan commitment is set, calculate closing
  else if (existingDates.loan_commitment_date && !existingDates.closing_date) {
    const closingDate = calculateClosingDate(existingDates.loan_commitment_date);
    if (closingDate) {
      suggestions.closing_date = formatDateForInput(closingDate);
    }
  }

  return suggestions;
}

/**
 * Get days between two dates
 */
export function daysBetween(date1: Date | string, date2: Date | string): number {
  const d1 = typeof date1 === 'string' ? parseDate(date1) : date1;
  const d2 = typeof date2 === 'string' ? parseDate(date2) : date2;

  if (!d1 || !d2) return 0;

  const diffTime = Math.abs(d2.getTime() - d1.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays;
}

/**
 * Check if a date is in the past
 */
export function isOverdue(date: Date | string): boolean {
  const d = typeof date === 'string' ? parseDate(date) : date;
  if (!d) return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  d.setHours(0, 0, 0, 0);

  return d < today;
}

/**
 * Check if a date is coming up soon (within X days)
 */
export function isDueSoon(date: Date | string, daysThreshold: number = 7): boolean {
  const d = typeof date === 'string' ? parseDate(date) : date;
  if (!d) return false;

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  d.setHours(0, 0, 0, 0);

  const daysUntil = daysBetween(today, d);
  return daysUntil <= daysThreshold && d >= today;
}

/**
 * Format date for display (e.g., "Jan 15, 2025")
 */
export function formatDateForDisplay(date: Date | string): string {
  const d = typeof date === 'string' ? parseDate(date) : date;
  if (!d) return '';

  return d.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

/**
 * Get relative time description (e.g., "3 days ago", "in 5 days")
 */
export function getRelativeTime(date: Date | string): string {
  const d = typeof date === 'string' ? parseDate(date) : date;
  if (!d) return '';

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  d.setHours(0, 0, 0, 0);

  const days = daysBetween(today, d);

  if (d < today) {
    if (days === 0) return 'today';
    if (days === 1) return 'yesterday';
    return `${days} days ago`;
  } else {
    if (days === 0) return 'today';
    if (days === 1) return 'tomorrow';
    return `in ${days} days`;
  }
}
