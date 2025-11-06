// TypeScript types for MA Deal Room API
export const TASK_PHASES = {
    pre_listing: { label: 'Pre-Listing', description: 'Before listing contract is signed' },
    post_listing: { label: 'Post-Listing', description: 'After listing contract signed' },
    pre_agreement: { label: 'Pre-Agreement', description: 'Before P&S is signed' },
    post_agreement: { label: 'Post-Agreement', description: 'After P&S signed' },
    pre_closing: { label: 'Pre-Closing', description: 'Before closing date' },
    post_closing: { label: 'Post-Closing', description: 'After closing' },
    any: { label: 'Any Phase', description: 'Applies to all phases' },
};
export const TRANSACTION_TYPES = {
    buy_side: { label: 'Buy Side', description: 'Buyer representation' },
    sell_side: { label: 'Sell Side', description: 'Seller representation' },
    rental: { label: 'Rental', description: 'Rental transactions' },
    commercial: { label: 'Commercial', description: 'Commercial transactions' },
    dual_agency: { label: 'Dual Agency', description: 'Dual agency representation' },
    all: { label: 'All Types', description: 'Applies to all transaction types' },
};
