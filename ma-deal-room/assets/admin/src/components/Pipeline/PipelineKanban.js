import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { GripVertical, Plus, MoreVertical, DollarSign, Calendar, MapPin, User, Tag } from 'lucide-react';
import { Card, CardContent } from '@/components/shared/Card';
import { format, differenceInDays } from 'date-fns';
const PIPELINE_STAGES = [
    {
        id: 'prospect',
        label: 'Prospect',
        color: 'text-gray-700',
        bgColor: 'bg-gray-100',
        borderColor: 'border-gray-300',
    },
    {
        id: 'listing_active',
        label: 'Active Listing',
        color: 'text-blue-700',
        bgColor: 'bg-blue-100',
        borderColor: 'border-blue-300',
    },
    {
        id: 'under_agreement',
        label: 'Under Agreement',
        color: 'text-purple-700',
        bgColor: 'bg-purple-100',
        borderColor: 'border-purple-300',
    },
    {
        id: 'pending_closing',
        label: 'Pending Closing',
        color: 'text-amber-700',
        bgColor: 'bg-amber-100',
        borderColor: 'border-amber-300',
    },
    {
        id: 'closed',
        label: 'Closed',
        color: 'text-green-700',
        bgColor: 'bg-green-100',
        borderColor: 'border-green-300',
    },
    {
        id: 'cancelled',
        label: 'Cancelled',
        color: 'text-red-700',
        bgColor: 'bg-red-100',
        borderColor: 'border-red-300',
    },
];
export const PipelineKanban = ({ transactions, onUpdateStatus, onTransactionClick, onAddTransaction, }) => {
    const [draggedCard, setDraggedCard] = useState(null);
    const [draggedOverStage, setDraggedOverStage] = useState(null);
    // Group transactions by stage
    const transactionsByStage = useMemo(() => {
        const grouped = new Map();
        PIPELINE_STAGES.forEach(stage => {
            grouped.set(stage.id, []);
        });
        transactions.forEach(transaction => {
            const status = transaction.status;
            if (grouped.has(status)) {
                grouped.get(status)?.push(transaction);
            }
        });
        return grouped;
    }, [transactions]);
    // Calculate stage totals
    const stageTotals = useMemo(() => {
        const totals = new Map();
        transactionsByStage.forEach((txns, stage) => {
            const total = txns.reduce((sum, t) => sum + (t.sale_price || 0), 0);
            totals.set(stage, total);
        });
        return totals;
    }, [transactionsByStage]);
    const handleDragStart = (transaction) => {
        setDraggedCard(transaction);
    };
    const handleDragEnd = () => {
        setDraggedCard(null);
        setDraggedOverStage(null);
    };
    const handleDragOver = (e, stageId) => {
        e.preventDefault();
        setDraggedOverStage(stageId);
    };
    const handleDrop = async (e, stageId) => {
        e.preventDefault();
        if (!draggedCard || draggedCard.status === stageId) {
            setDraggedCard(null);
            setDraggedOverStage(null);
            return;
        }
        try {
            await onUpdateStatus(draggedCard.transaction_id, stageId);
        }
        catch (error) {
            console.error('Failed to update transaction status:', error);
        }
        setDraggedCard(null);
        setDraggedOverStage(null);
    };
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
        }).format(amount);
    };
    const getDaysInStage = (transaction) => {
        const now = new Date();
        const updatedAt = transaction.updated_at ? new Date(transaction.updated_at) : new Date(transaction.created_at);
        return differenceInDays(now, updatedAt);
    };
    return (_jsx("div", { className: "flex gap-4 overflow-x-auto pb-4 h-[calc(100vh-200px)]", children: PIPELINE_STAGES.map((stage) => {
            const stageTransactions = transactionsByStage.get(stage.id) || [];
            const stageTotal = stageTotals.get(stage.id) || 0;
            const isDraggedOver = draggedOverStage === stage.id;
            return (_jsxs("div", { className: "flex-shrink-0 w-80 flex flex-col", onDragOver: (e) => handleDragOver(e, stage.id), onDrop: (e) => handleDrop(e, stage.id), children: [_jsx(Card, { className: `mb-3 ${stage.borderColor} border-t-4`, children: _jsxs(CardContent, { className: "p-4", children: [_jsxs("div", { className: "flex items-center justify-between mb-2", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("span", { className: `px-3 py-1 rounded-full text-sm font-semibold ${stage.bgColor} ${stage.color}`, children: stageTransactions.length }), _jsx("h3", { className: "font-semibold text-gray-900", children: stage.label })] }), onAddTransaction && (_jsx("button", { onClick: () => onAddTransaction(stage.id), className: "p-1 text-gray-400 hover:text-gray-600 rounded transition-colors", children: _jsx(Plus, { className: "h-4 w-4" }) }))] }), _jsxs("div", { className: "flex items-center gap-2 text-sm", children: [_jsx(DollarSign, { className: "h-4 w-4 text-gray-400" }), _jsx("span", { className: "font-semibold text-gray-700", children: formatCurrency(stageTotal) })] })] }) }), _jsx("div", { className: `flex-1 space-y-3 p-2 rounded-lg transition-colors ${isDraggedOver ? 'bg-blue-50 border-2 border-blue-300 border-dashed' : 'bg-gray-50'}`, children: stageTransactions.length === 0 ? (_jsx("div", { className: "flex items-center justify-center h-32 text-gray-400 text-sm", children: "Drop deals here" })) : (stageTransactions.map((transaction) => {
                            const daysInStage = getDaysInStage(transaction);
                            return (_jsx("div", { draggable: true, onDragStart: () => handleDragStart(transaction), onDragEnd: handleDragEnd, onClick: () => onTransactionClick?.(transaction), className: `cursor-move ${draggedCard?.transaction_id === transaction.transaction_id
                                    ? 'opacity-50 rotate-2'
                                    : ''}`, children: _jsx(Card, { className: "hover:shadow-lg transition-all", children: _jsxs(CardContent, { className: "p-4", children: [_jsxs("div", { className: "flex items-start gap-2 mb-3", children: [_jsx(GripVertical, { className: "h-4 w-4 text-gray-400 flex-shrink-0 mt-1 cursor-grab" }), _jsxs("div", { className: "flex-1 min-w-0", children: [_jsx("h4", { className: "font-semibold text-gray-900 text-sm truncate", children: transaction.property_address }), _jsxs("div", { className: "flex items-center gap-1 text-xs text-gray-600 mt-1", children: [_jsx(MapPin, { className: "h-3 w-3" }), transaction.property_city, ", ", transaction.property_state] })] }), _jsx("button", { onClick: (e) => {
                                                            e.stopPropagation();
                                                        }, className: "p-1 text-gray-400 hover:text-gray-600 rounded", children: _jsx(MoreVertical, { className: "h-4 w-4" }) })] }), _jsxs("div", { className: "flex items-center gap-2 mb-3 p-2 bg-gray-50 rounded", children: [_jsx(DollarSign, { className: "h-4 w-4 text-green-600" }), _jsx("span", { className: "font-bold text-gray-900", children: formatCurrency(transaction.sale_price || 0) })] }), transaction.assigned_agent_id && (_jsxs("div", { className: "flex items-center gap-2 text-xs text-gray-600 mb-2", children: [_jsx(User, { className: "h-3 w-3" }), "Agent ID: ", transaction.assigned_agent_id] })), _jsxs("div", { className: "space-y-1 text-xs text-gray-600 mb-3", children: [transaction.listing_date && (_jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Calendar, { className: "h-3 w-3" }), "Listed: ", format(new Date(transaction.listing_date), 'MMM d, yyyy')] })), transaction.closing_date && (_jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Calendar, { className: "h-3 w-3" }), "Closes: ", format(new Date(transaction.closing_date), 'MMM d, yyyy')] }))] }), transaction.task_summary && (_jsxs("div", { className: "mb-3", children: [_jsxs("div", { className: "flex items-center justify-between text-xs text-gray-600 mb-1", children: [_jsx("span", { children: "Tasks" }), _jsxs("span", { children: [transaction.task_summary.completed, "/", transaction.task_summary.total] })] }), _jsx("div", { className: "w-full bg-gray-200 rounded-full h-1.5", children: _jsx("div", { className: "bg-blue-600 h-1.5 rounded-full transition-all", style: {
                                                                width: `${transaction.task_summary.total > 0
                                                                    ? (transaction.task_summary.completed / transaction.task_summary.total) * 100
                                                                    : 0}%`,
                                                            } }) })] })), transaction.property_type && (_jsxs("div", { className: "flex flex-wrap gap-1", children: [_jsxs("span", { className: "px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full flex items-center gap-1", children: [_jsx(Tag, { className: "h-3 w-3" }), transaction.property_type] }), _jsxs("span", { className: "px-2 py-0.5 text-xs bg-gray-200 text-gray-700 rounded-full", children: [daysInStage, "d in stage"] })] })), transaction.task_summary && transaction.task_summary.overdue > 0 && (_jsxs("div", { className: "mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700 font-medium", children: ["\u26A0\uFE0F ", transaction.task_summary.overdue, " overdue task", transaction.task_summary.overdue !== 1 ? 's' : ''] }))] }) }) }, transaction.transaction_id));
                        })) })] }, stage.id));
        }) }));
};
