// User role types and their display information
export const ROLE_TYPES = {
    // Agency/Professional Roles
    broker: {
        value: 'broker',
        label: 'Broker/Agency Admin',
        description: 'Full admin access to agency/brokerage',
        category: 'agency',
        icon: '🏢',
    },
    agent: {
        value: 'agent',
        label: 'Real Estate Agent',
        description: 'Manages transactions for clients',
        category: 'agency',
        icon: '👔',
    },
    // Client Roles
    buyer: {
        value: 'buyer',
        label: 'Buyer',
        description: 'Purchase side client',
        category: 'client',
        icon: '🏠',
    },
    seller: {
        value: 'seller',
        label: 'Seller',
        description: 'Sale side client',
        category: 'client',
        icon: '💼',
    },
    // Legal/Professional Roles
    buyer_attorney: {
        value: 'buyer_attorney',
        label: "Buyer's Attorney",
        description: "Buyer's legal counsel",
        category: 'legal',
        icon: '⚖️',
    },
    seller_attorney: {
        value: 'seller_attorney',
        label: "Seller's Attorney",
        description: "Seller's legal counsel",
        category: 'legal',
        icon: '⚖️',
    },
    // Vendor Roles - Financial
    lender: {
        value: 'lender',
        label: 'Mortgage Lender',
        description: 'Mortgage financing provider',
        category: 'vendor_financial',
        icon: '🏦',
    },
    // Vendor Roles - Inspection
    inspector: {
        value: 'inspector',
        label: 'Home Inspector',
        description: 'Property inspection services',
        category: 'vendor_inspection',
        icon: '🔍',
    },
    appraiser: {
        value: 'appraiser',
        label: 'Appraiser',
        description: 'Property valuation services',
        category: 'vendor_inspection',
        icon: '📊',
    },
    septic_inspector: {
        value: 'septic_inspector',
        label: 'Septic Inspector',
        description: 'Septic system inspection',
        category: 'vendor_inspection',
        icon: '🚰',
    },
    // Vendor Roles - Services
    vendor: {
        value: 'vendor',
        label: 'General Vendor',
        description: 'General contractor or service provider',
        category: 'vendor_services',
        icon: '🔧',
    },
    photographer: {
        value: 'photographer',
        label: 'Photographer',
        description: 'Property photography services',
        category: 'vendor_services',
        icon: '📸',
    },
    // Vendor Roles - Title/Closing
    title_company: {
        value: 'title_company',
        label: 'Title Company',
        description: 'Title search and insurance',
        category: 'vendor_title',
        icon: '📜',
    },
    escrow: {
        value: 'escrow',
        label: 'Escrow Officer',
        description: 'Escrow and closing services',
        category: 'vendor_title',
        icon: '🔐',
    },
    // Vendor Roles - Other
    hoa_manager: {
        value: 'hoa_manager',
        label: 'HOA Manager',
        description: 'Homeowners association management',
        category: 'vendor_other',
        icon: '🏘️',
    },
    fire_dept: {
        value: 'fire_dept',
        label: 'Fire Department',
        description: 'Fire safety inspection',
        category: 'vendor_other',
        icon: '🚒',
    },
};
// Role categories
export const ROLE_CATEGORIES = {
    agency: {
        label: 'Agency',
        description: 'Brokerage and agent roles',
        roles: ['broker', 'agent'],
    },
    client: {
        label: 'Clients',
        description: 'Buyers and sellers',
        roles: ['buyer', 'seller'],
    },
    legal: {
        label: 'Legal',
        description: 'Attorneys and legal counsel',
        roles: ['buyer_attorney', 'seller_attorney'],
    },
    vendor_financial: {
        label: 'Financial Services',
        description: 'Lenders and financial institutions',
        roles: ['lender'],
    },
    vendor_inspection: {
        label: 'Inspection Services',
        description: 'Home inspectors and appraisers',
        roles: ['inspector', 'appraiser', 'septic_inspector'],
    },
    vendor_services: {
        label: 'Service Providers',
        description: 'Contractors and service vendors',
        roles: ['vendor', 'photographer'],
    },
    vendor_title: {
        label: 'Title & Closing',
        description: 'Title companies and escrow',
        roles: ['title_company', 'escrow'],
    },
    vendor_other: {
        label: 'Other Services',
        description: 'HOA, fire department, etc.',
        roles: ['hoa_manager', 'fire_dept'],
    },
};
// User status types
export const USER_STATUS = {
    active: {
        value: 'active',
        label: 'Active',
        color: 'green',
        description: 'User can access the system',
    },
    inactive: {
        value: 'inactive',
        label: 'Inactive',
        color: 'gray',
        description: 'User account is disabled',
    },
    suspended: {
        value: 'suspended',
        label: 'Suspended',
        color: 'red',
        description: 'User account is temporarily suspended',
    },
    pending_verification: {
        value: 'pending_verification',
        label: 'Pending Verification',
        color: 'yellow',
        description: 'User needs to verify email',
    },
};
// Helper functions
export const getRoleInfo = (roleType) => {
    return ROLE_TYPES[roleType];
};
export const getRoleLabel = (roleType) => {
    return ROLE_TYPES[roleType]?.label || roleType;
};
export const getRoleCategory = (roleType) => {
    return ROLE_TYPES[roleType]?.category;
};
export const getRolesByCategory = (category) => {
    return ROLE_CATEGORIES[category].roles.map((roleType) => ROLE_TYPES[roleType]);
};
export const isVendorRole = (roleType) => {
    const category = getRoleCategory(roleType);
    return category?.startsWith('vendor') ?? false;
};
export const getUserStatusInfo = (status) => {
    return USER_STATUS[status];
};
export const getUserStatusColor = (status) => {
    return USER_STATUS[status]?.color || 'gray';
};
// Get all role types as array
export const getAllRoleTypes = () => {
    return Object.keys(ROLE_TYPES);
};
// Get all vendor role types
export const getVendorRoleTypes = () => {
    return getAllRoleTypes().filter(isVendorRole);
};
// Get role options for select dropdowns
export const getRoleOptions = () => {
    return Object.entries(ROLE_TYPES).map(([value, info]) => ({
        value,
        label: info.label,
        category: info.category,
    }));
};
// Get role options grouped by category
export const getRoleOptionsGrouped = () => {
    return Object.entries(ROLE_CATEGORIES).map(([categoryKey, category]) => ({
        label: category.label,
        options: category.roles.map((roleType) => ({
            value: roleType,
            label: ROLE_TYPES[roleType].label,
        })),
    }));
};
