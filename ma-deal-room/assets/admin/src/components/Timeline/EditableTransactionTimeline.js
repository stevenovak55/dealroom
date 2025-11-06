import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Check, Circle, Clock, Edit2, HelpCircle, Sparkles, X, Save } from 'lucide-react';
import { formatDate } from '@/utils/formatDate';
import { Tooltip } from '@/components/shared/Tooltip';
import { Button } from '@/components/shared/Button';
import { useUpdateTransaction } from '@/api/queries/useTransactions';
import { useIsMobile } from '@/hooks/useMediaQuery';
import { suggestMilestoneDates, isOverdue, isDueSoon, daysBetween, getRelativeTime, } from '@/utils/timelineCalculator';
export const EditableTransactionTimeline = ({ transaction }) => {
    const isMobile = useIsMobile();
    const updateMutation = useUpdateTransaction(transaction.transaction_id);
    const [editingMilestone, setEditingMilestone] = useState(null);
    const [localDates, setLocalDates] = useState({
        listing_date: transaction.listing_date || '',
        offer_accepted_date: transaction.offer_accepted_date || '',
        ps_agreement_date: transaction.ps_agreement_date || '',
        loan_commitment_date: transaction.loan_commitment_date || '',
        closing_date: transaction.closing_date || '',
    });
    const [suggestions, setSuggestions] = useState({});
    const transactionSide = transaction.transaction_side || 'listing';
    const milestones = [
        {
            id: 'listing_date',
            label: 'Listing Signed',
            date: localDates.listing_date,
            description: transactionSide === 'listing' ? 'Listing agreement signed' : 'Property listed (optional)',
            helpText: {
                listing: "The date your listing agreement was signed and the property goes live on MLS.",
                buyer: "The date the property was first listed (optional for reference)."
            }
        },
        {
            id: 'offer_accepted_date',
            label: 'Offer Accepted',
            date: localDates.offer_accepted_date,
            description: transactionSide === 'listing' ? 'Seller accepted offer' : 'Your buyer\'s offer accepted',
            helpText: {
                listing: "The date the seller accepted the buyer's offer.",
                buyer: "The date your buyer's offer was accepted by the seller."
            }
        },
        {
            id: 'ps_agreement_date',
            label: 'P&S Signed',
            date: localDates.ps_agreement_date,
            description: 'Purchase & Sale agreement',
            helpText: "Purchase & Sale Agreement signing date (typically 10 days after offer acceptance)."
        },
        {
            id: 'loan_commitment_date',
            label: 'Loan Commitment',
            date: localDates.loan_commitment_date,
            description: transactionSide === 'buyer' ? '⚠️ Critical milestone' : 'Buyer financing approved',
            helpText: {
                listing: "Date buyer's lender issues commitment letter (typically 3 weeks after P&S).",
                buyer: "⚠️ CRITICAL: Date your buyer must receive mortgage approval. Track this closely!"
            }
        },
        {
            id: 'closing_date',
            label: 'Closing',
            date: localDates.closing_date,
            description: 'Scheduled closing date',
            helpText: "Final closing date at registry of deeds or attorney's office (typically 2 weeks after loan commitment)."
        },
    ];
    // Calculate suggestions when dates change
    useEffect(() => {
        const newSuggestions = suggestMilestoneDates(localDates);
        setSuggestions(newSuggestions);
    }, [localDates]);
    const getMilestoneHelp = (milestone) => {
        if (typeof milestone.helpText === 'string') {
            return milestone.helpText;
        }
        return milestone.helpText[transactionSide];
    };
    const getMilestoneStatus = (date) => {
        if (!date)
            return 'pending';
        if (isOverdue(date)) {
            return 'overdue';
        }
        if (isDueSoon(date, 14)) {
            return 'current';
        }
        const milestoneDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (milestoneDate <= today) {
            return 'completed';
        }
        return 'pending';
    };
    const getMilestoneIcon = (status) => {
        if (status === 'completed') {
            return _jsx(Check, { className: "h-4 w-4 text-white" });
        }
        if (status === 'current' || status === 'overdue') {
            return _jsx(Clock, { className: "h-4 w-4 text-white" });
        }
        return _jsx(Circle, { className: "h-3 w-3 text-gray-400" });
    };
    const getMilestoneColor = (status) => {
        if (status === 'completed')
            return 'bg-green-500';
        if (status === 'overdue')
            return 'bg-red-500';
        if (status === 'current')
            return 'bg-blue-500';
        return 'bg-gray-300';
    };
    const handleDateChange = (milestoneId, value) => {
        setLocalDates(prev => ({
            ...prev,
            [milestoneId]: value
        }));
    };
    const handleSaveDate = async (milestoneId) => {
        try {
            await updateMutation.mutateAsync({
                [milestoneId]: localDates[milestoneId] || null
            });
            setEditingMilestone(null);
        }
        catch (error) {
            console.error('Failed to update date:', error);
            alert('Failed to update date. Please try again.');
        }
    };
    const handleCancelEdit = (milestoneId) => {
        // Restore original value
        setLocalDates(prev => ({
            ...prev,
            [milestoneId]: transaction[milestoneId] || ''
        }));
        setEditingMilestone(null);
    };
    const handleApplySuggestions = async () => {
        const updates = {};
        Object.entries(suggestions).forEach(([key, value]) => {
            if (!localDates[key] && value) {
                updates[key] = value;
            }
        });
        if (Object.keys(updates).length === 0) {
            alert('No new suggestions to apply');
            return;
        }
        try {
            await updateMutation.mutateAsync(updates);
        }
        catch (error) {
            console.error('Failed to apply suggestions:', error);
            alert('Failed to apply suggestions. Please try again.');
        }
    };
    const hasSuggestions = Object.keys(suggestions).length > 0;
    return (_jsxs("div", { className: "py-6", children: [hasSuggestions && (_jsx("div", { className: "mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg", children: _jsxs("div", { className: "flex items-start justify-between", children: [_jsxs("div", { className: "flex items-start gap-3", children: [_jsx(Sparkles, { className: "h-5 w-5 text-blue-600 mt-0.5" }), _jsxs("div", { children: [_jsx("p", { className: "text-sm font-medium text-blue-900", children: "Suggested Dates Available" }), _jsx("p", { className: "text-sm text-blue-700 mt-1", children: "Based on MA standard timeline, we can suggest dates for empty milestones" })] })] }), _jsx(Button, { size: "sm", variant: "primary", onClick: handleApplySuggestions, isLoading: updateMutation.isPending, children: "Use Suggestions" })] }) })), isMobile ? (
            // Mobile: Vertical Timeline
            _jsxs("div", { className: "relative pl-8", children: [_jsx("div", { className: "absolute top-0 bottom-0 left-6 w-0.5 bg-gray-200", "aria-hidden": "true", children: _jsx("div", { className: "w-full bg-green-500 transition-all duration-500", style: {
                                height: `${(milestones.filter((m) => getMilestoneStatus(m.date) === 'completed').length / milestones.length) * 100}%`
                            } }) }), _jsx("div", { className: "space-y-6", children: milestones.map((milestone, index) => {
                            const status = getMilestoneStatus(milestone.date);
                            const isEditing = editingMilestone === milestone.id;
                            const hasSuggestion = suggestions[milestone.id] && !milestone.date;
                            return (_jsxs("div", { className: "relative flex items-start gap-4", children: [_jsxs("div", { className: `relative z-10 flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-full border-4 border-white ${getMilestoneColor(status)} transition-all duration-300 cursor-pointer active:scale-95`, onClick: () => !isEditing && setEditingMilestone(milestone.id), style: { marginLeft: '-16px' }, children: [getMilestoneIcon(status), !isEditing && (_jsx("div", { className: "absolute -top-1 -right-1 h-5 w-5 bg-primary-600 text-white rounded-full p-1 shadow-lg border-2 border-white active:bg-primary-700", children: _jsx(Edit2, { className: "h-full w-full" }) }))] }), _jsxs("div", { className: "flex-1 pb-4", children: [_jsxs("div", { className: "flex items-center gap-1", children: [_jsx("p", { className: `text-base font-medium ${status === 'pending' ? 'text-gray-500' : 'text-gray-900'}`, children: milestone.label }), _jsx(Tooltip, { content: getMilestoneHelp(milestone), children: _jsx(HelpCircle, { className: "h-4 w-4 text-gray-400 active:text-gray-600" }) })] }), _jsx("p", { className: "text-sm text-gray-500 mt-0.5", children: milestone.description }), isEditing ? (_jsxs("div", { className: "mt-3 flex flex-col gap-2", children: [_jsx("input", { type: "date", value: localDates[milestone.id], onChange: (e) => handleDateChange(milestone.id, e.target.value), className: "text-sm px-3 py-2 border border-gray-300 rounded-lg w-full", autoFocus: true }), _jsxs("div", { className: "flex gap-2", children: [_jsx("button", { onClick: () => handleSaveDate(milestone.id), className: "flex-1 px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-lg active:bg-green-700", disabled: updateMutation.isPending, children: "Save" }), _jsx("button", { onClick: () => handleCancelEdit(milestone.id), className: "flex-1 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg active:bg-gray-200", children: "Cancel" })] })] })) : milestone.date ? (_jsxs("div", { className: "mt-2", children: [_jsx("p", { className: `text-sm font-medium ${status === 'completed' ? 'text-green-600' :
                                                            status === 'overdue' ? 'text-red-600' :
                                                                status === 'current' ? 'text-blue-600' :
                                                                    'text-gray-900'}`, children: formatDate(milestone.date) }), _jsx("p", { className: "text-sm text-gray-500 mt-0.5", children: getRelativeTime(milestone.date) })] })) : hasSuggestion ? (_jsxs("div", { className: "mt-2", children: [_jsx("p", { className: "text-sm text-gray-400 italic", children: "Not set" }), _jsxs("p", { className: "text-sm text-blue-600 mt-0.5", children: ["Suggest: ", formatDate(suggestions[milestone.id])] })] })) : (_jsx("p", { className: "text-sm text-gray-400 mt-2 italic", children: "Not set" })), milestone.date && index < milestones.length - 1 && milestones[index + 1].date && (_jsxs("p", { className: "text-sm text-gray-400 mt-2", children: ["\u2193 ", daysBetween(milestone.date, milestones[index + 1].date), " days to next"] }))] })] }, milestone.id));
                        }) })] })) : (
            // Desktop: Horizontal Timeline
            _jsxs("div", { className: "relative", children: [_jsx("div", { className: "absolute top-6 left-0 right-0 h-0.5 bg-gray-200", "aria-hidden": "true", children: _jsx("div", { className: "h-full bg-green-500 transition-all duration-500", style: {
                                width: `${(milestones.filter((m) => getMilestoneStatus(m.date) === 'completed').length / milestones.length) * 100}%`
                            } }) }), _jsx("div", { className: "relative flex justify-between", children: milestones.map((milestone, index) => {
                            const status = getMilestoneStatus(milestone.date);
                            const isEditing = editingMilestone === milestone.id;
                            const hasSuggestion = suggestions[milestone.id] && !milestone.date;
                            return (_jsxs("div", { className: "flex flex-col items-center", style: { flex: 1 }, children: [_jsxs("div", { className: `relative z-10 flex items-center justify-center w-12 h-12 rounded-full border-4 border-white ${getMilestoneColor(status)} transition-all duration-300 cursor-pointer hover:scale-110 group`, onClick: () => !isEditing && setEditingMilestone(milestone.id), children: [getMilestoneIcon(status), !isEditing && (_jsx("div", { className: "absolute -top-1 -right-1 h-5 w-5 bg-primary-600 text-white rounded-full p-1 shadow-lg border-2 border-white group-hover:bg-primary-700 group-hover:scale-125 transition-all duration-200", children: _jsx(Edit2, { className: "h-full w-full" }) }))] }), _jsxs("div", { className: "mt-3 text-center", children: [_jsxs("div", { className: "flex items-center gap-1 justify-center", children: [_jsx("p", { className: `text-sm font-medium ${status === 'pending' ? 'text-gray-500' : 'text-gray-900'}`, children: milestone.label }), _jsx(Tooltip, { content: getMilestoneHelp(milestone), children: _jsx(HelpCircle, { className: "h-3.5 w-3.5 text-gray-400 hover:text-gray-600 cursor-help" }) })] }), _jsx("p", { className: "text-xs text-gray-500 mt-1", children: milestone.description }), isEditing ? (_jsxs("div", { className: "mt-2 flex flex-col items-center gap-2", children: [_jsx("input", { type: "date", value: localDates[milestone.id], onChange: (e) => handleDateChange(milestone.id, e.target.value), className: "text-xs px-2 py-1 border border-gray-300 rounded", autoFocus: true }), _jsxs("div", { className: "flex gap-1", children: [_jsx("button", { onClick: () => handleSaveDate(milestone.id), className: "p-1 text-green-600 hover:bg-green-50 rounded", disabled: updateMutation.isPending, children: _jsx(Save, { className: "h-3 w-3" }) }), _jsx("button", { onClick: () => handleCancelEdit(milestone.id), className: "p-1 text-red-600 hover:bg-red-50 rounded", children: _jsx(X, { className: "h-3 w-3" }) })] })] })) : milestone.date ? (_jsxs("div", { className: "mt-1", children: [_jsx("p", { className: `text-xs font-medium ${status === 'completed' ? 'text-green-600' :
                                                            status === 'overdue' ? 'text-red-600' :
                                                                status === 'current' ? 'text-blue-600' :
                                                                    'text-gray-900'}`, children: formatDate(milestone.date) }), _jsx("p", { className: "text-xs text-gray-500 mt-0.5", children: getRelativeTime(milestone.date) })] })) : hasSuggestion ? (_jsxs("div", { className: "mt-1", children: [_jsx("p", { className: "text-xs text-gray-400 italic", children: "Not set" }), _jsxs("p", { className: "text-xs text-blue-600 mt-0.5", children: ["Suggest: ", formatDate(suggestions[milestone.id])] })] })) : (_jsx("p", { className: "text-xs text-gray-400 mt-1 italic", children: "Not set" })), milestone.date && index < milestones.length - 1 && milestones[index + 1].date && (_jsxs("p", { className: "text-xs text-gray-400 mt-2", children: ["\u2193 ", daysBetween(milestone.date, milestones[index + 1].date), " days"] }))] })] }, milestone.id));
                        }) })] })), _jsxs("div", { className: "mt-8 flex items-center justify-center gap-6 text-xs text-gray-600", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-3 h-3 rounded-full bg-green-500" }), _jsx("span", { children: "Completed" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-3 h-3 rounded-full bg-blue-500" }), _jsx("span", { children: "Upcoming" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-3 h-3 rounded-full bg-red-500" }), _jsx("span", { children: "Overdue" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx("div", { className: "w-3 h-3 rounded-full bg-gray-300" }), _jsx("span", { children: "Not Set" })] })] }), localDates.closing_date && (_jsxs("div", { className: "mt-6 text-center", children: [_jsxs("p", { className: "text-2xl font-bold text-gray-900", children: [daysBetween(new Date(), localDates.closing_date), " days"] }), _jsx("p", { className: "text-sm text-gray-500", children: "until closing" })] }))] }));
};
