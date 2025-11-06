import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { Check, Circle, Clock } from 'lucide-react';
import { formatDate } from '@/utils/formatDate';
export const TransactionTimeline = ({ transaction }) => {
    const milestones = [
        {
            id: 'created',
            label: 'Created',
            date: transaction.created_at,
            description: 'Transaction created in system',
        },
        {
            id: 'listing',
            label: 'Listing Signed',
            date: transaction.listing_date,
            description: 'Listing agreement signed',
        },
        {
            id: 'offer',
            label: 'Offer Accepted',
            date: transaction.offer_accepted_date,
            description: 'Seller accepted offer',
        },
        {
            id: 'ps',
            label: 'P&S Signed',
            date: transaction.ps_agreement_date,
            description: 'Purchase & Sale agreement',
        },
        {
            id: 'loan',
            label: 'Loan Commitment',
            date: transaction.loan_commitment_date,
            description: 'Lender commits to financing',
        },
        {
            id: 'closing',
            label: 'Closing',
            date: transaction.closing_date,
            description: 'Scheduled closing date',
        },
    ];
    const getMilestoneStatus = (date, index) => {
        if (!date)
            return 'pending';
        const milestoneDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (milestoneDate <= today) {
            return 'completed';
        }
        // Check if this is the next milestone
        const prevMilestones = milestones.slice(0, index);
        const hasCompletedPrev = prevMilestones.every(m => m.date && new Date(m.date) <= today);
        const nextMilestones = milestones.slice((index || 0) + 1);
        const hasNoFutureCompleted = nextMilestones.every(m => !m.date || new Date(m.date) > today);
        if (hasCompletedPrev && hasNoFutureCompleted) {
            return 'current';
        }
        return 'pending';
    };
    const getMilestoneIcon = (status) => {
        if (status === 'completed') {
            return _jsx(Check, { className: "h-4 w-4 text-white" });
        }
        if (status === 'current') {
            return _jsx(Clock, { className: "h-4 w-4 text-white" });
        }
        return _jsx(Circle, { className: "h-3 w-3 text-gray-400" });
    };
    const getMilestoneColor = (status) => {
        if (status === 'completed')
            return 'bg-green-500';
        if (status === 'current')
            return 'bg-blue-500';
        return 'bg-gray-300';
    };
    return (_jsxs("div", { className: "py-6", children: [_jsxs("div", { className: "relative", children: [_jsx("div", { className: "absolute top-6 left-0 right-0 h-0.5 bg-gray-200", "aria-hidden": "true", children: _jsx("div", { className: "h-full bg-green-500 transition-all duration-500", style: {
                                width: `${(milestones.filter((m, i) => getMilestoneStatus(m.date, i) === 'completed').length / milestones.length) * 100}%`
                            } }) }), _jsx("div", { className: "relative flex justify-between", children: milestones.map((milestone, index) => {
                            const status = getMilestoneStatus(milestone.date, index);
                            return (_jsxs("div", { className: "flex flex-col items-center", style: { flex: 1 }, children: [_jsx("div", { className: `relative z-10 flex items-center justify-center w-12 h-12 rounded-full border-4 border-white ${getMilestoneColor(status)} transition-all duration-300`, children: getMilestoneIcon(status) }), _jsxs("div", { className: "mt-3 text-center", children: [_jsx("p", { className: `text-sm font-medium ${status === 'pending' ? 'text-gray-500' : 'text-gray-900'}`, children: milestone.label }), _jsx("p", { className: "text-xs text-gray-500 mt-1", children: milestone.description }), milestone.date ? (_jsx("p", { className: `text-xs mt-1 font-medium ${status === 'completed' ? 'text-green-600' :
                                                    status === 'current' ? 'text-blue-600' :
                                                        'text-gray-900'}`, children: formatDate(milestone.date) })) : (_jsx("p", { className: "text-xs text-gray-400 mt-1 italic", children: "Not set" }))] })] }, milestone.id));
                        }) })] }), _jsxs("div", { className: "mt-8 flex items-center justify-center gap-6 text-xs text-gray-600", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-3 h-3 rounded-full bg-green-500" }), _jsx("span", { children: "Completed" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-3 h-3 rounded-full bg-blue-500" }), _jsx("span", { children: "Current" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-3 h-3 rounded-full bg-gray-300" }), _jsx("span", { children: "Pending" })] })] })] }));
};
