import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Download, Search } from 'lucide-react';
import { useGetTransaction } from '../../api/queries/useTransactions';
import { useGetTasks } from '../../api/queries/useTasks';
import { GanttChart } from '../../components/Timeline/GanttChart';
import toast from 'react-hot-toast';
import { cn } from '@/utils/cn';
/**
 * TimelineView Component (Mobile-First Redesign)
 *
 * Responsive Gantt chart timeline view with mobile-first design:
 * - Mobile (<768px): Stacked layout, scrollable filters, vertical controls
 * - Desktop (>=768px): Grid layouts, horizontal controls
 *
 * Features:
 * - Interactive Gantt chart with task dependencies
 * - Search and filter tasks
 * - Toggle dependencies, milestones, and phase grouping
 * - Export timeline (coming soon)
 * - Touch-friendly controls
 */
export const TimelineView = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const transactionId = id ? parseInt(id, 10) : 0;
    const { data: transaction, isLoading: transactionLoading } = useGetTransaction(transactionId);
    const { data: tasksResponse, isLoading: tasksLoading } = useGetTasks({
        transaction_id: transactionId,
    });
    // Filters
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');
    const [ownerRoleFilter, setOwnerRoleFilter] = useState('all');
    const [showDependencies, setShowDependencies] = useState(true);
    const [showMilestones, setShowMilestones] = useState(true);
    const [groupByPhase, setGroupByPhase] = useState(true);
    // Filter tasks
    const filteredTasks = useMemo(() => {
        if (!tasksResponse?.data)
            return [];
        let tasks = tasksResponse.data;
        // Search filter
        if (searchQuery) {
            const query = searchQuery.toLowerCase();
            tasks = tasks.filter((task) => task.title?.toLowerCase().includes(query) ||
                task.description?.toLowerCase().includes(query));
        }
        // Status filter
        if (statusFilter !== 'all') {
            tasks = tasks.filter((task) => task.status === statusFilter);
        }
        // Owner role filter
        if (ownerRoleFilter !== 'all') {
            tasks = tasks.filter((task) => task.owner_role === ownerRoleFilter);
        }
        return tasks;
    }, [tasksResponse, searchQuery, statusFilter, ownerRoleFilter]);
    // Handle task click
    const handleTaskClick = (task) => {
        // Navigate to task detail or open modal
        navigate(`/transactions/${transactionId}?taskId=${task.id}`);
    };
    // Handle drag end (reschedule task)
    const handleTaskDragEnd = async (taskId, newDueDate) => {
        // For now, just show a toast. Full implementation would require
        // backend API endpoint that accepts task updates
        toast.success(`Task ${taskId} rescheduled to ${newDueDate}`);
        console.log('Task rescheduled:', { taskId, newDueDate });
        // TODO: Implement task rescheduling API call
        // await apiClient.put(`/tasks/${taskId}`, { due_at: newDueDate });
    };
    // Export timeline
    const handleExport = () => {
        toast.success('Export feature coming soon!');
        // TODO: Implement export to PDF/image
    };
    if (transactionLoading || tasksLoading) {
        return (_jsx("div", { className: "flex items-center justify-center h-96", children: _jsx("div", { className: "animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" }) }));
    }
    if (!transaction) {
        return (_jsx("div", { className: "flex items-center justify-center h-96", children: _jsxs("div", { className: "text-center", children: [_jsx("h2", { className: "text-2xl font-bold text-gray-900", children: "Transaction not found" }), _jsx("button", { onClick: () => navigate('/transactions'), className: "mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600", children: "Back to Transactions" })] }) }));
    }
    return (_jsxs("div", { className: "space-y-5 md:space-y-6", children: [_jsxs("div", { className: "bg-white shadow-sm rounded-lg p-5 md:p-6", children: [_jsxs("div", { className: cn('flex flex-col space-y-4', 'md:flex-row md:items-start md:justify-between md:space-y-0'), children: [_jsxs("div", { children: [_jsx("h1", { className: "text-xl md:text-2xl lg:text-3xl font-bold text-gray-900", children: "Timeline View" }), _jsx("p", { className: "text-base md:text-lg text-gray-600 mt-1", children: transaction.property_address }), _jsxs("p", { className: "text-sm md:text-base text-gray-500 mt-0.5", children: [transaction.property_city, ", ", transaction.property_state, " ", transaction.property_zip] })] }), _jsxs("div", { className: cn('flex flex-col space-y-2', 'sm:flex-row sm:gap-2 sm:space-y-0'), children: [_jsxs("button", { onClick: handleExport, className: cn('flex items-center justify-center gap-2', 'px-6 py-3 md:px-4 md:py-2', 'border border-gray-300 rounded-md', 'hover:bg-gray-50 transition-colors', 'text-base md:text-sm font-medium', 'w-full sm:w-auto'), children: [_jsx(Download, { className: "w-5 h-5 md:w-4 md:h-4" }), "Export"] }), _jsx("button", { onClick: () => navigate(`/transactions/${transactionId}`), className: cn('px-6 py-3 md:px-4 md:py-2', 'bg-blue-500 text-white rounded-md', 'hover:bg-blue-600 transition-colors', 'text-base md:text-sm font-medium', 'w-full sm:w-auto'), children: "Back to Details" })] })] }), _jsxs("div", { className: cn('mt-5 md:mt-4', 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-4'), children: [_jsxs("div", { className: "bg-gray-50 rounded-lg p-4 md:p-3", children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500 uppercase", children: "Status" }), _jsx("p", { className: "text-sm md:text-sm font-semibold text-gray-900 capitalize mt-1", children: transaction.status.replace('_', ' ') })] }), _jsxs("div", { className: "bg-gray-50 rounded-lg p-4 md:p-3", children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500 uppercase", children: "Property Type" }), _jsx("p", { className: "text-sm md:text-sm font-semibold text-gray-900 mt-1", children: transaction.property_type })] }), _jsxs("div", { className: "bg-gray-50 rounded-lg p-4 md:p-3", children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500 uppercase", children: "P&S Date" }), _jsx("p", { className: "text-sm md:text-sm font-semibold text-gray-900 mt-1", children: transaction.ps_agreement_date
                                            ? new Date(transaction.ps_agreement_date).toLocaleDateString()
                                            : 'Not set' })] }), _jsxs("div", { className: "bg-gray-50 rounded-lg p-4 md:p-3", children: [_jsx("p", { className: "text-xs md:text-xs text-gray-500 uppercase", children: "Closing Date" }), _jsx("p", { className: "text-sm md:text-sm font-semibold text-gray-900 mt-1", children: transaction.closing_date
                                            ? new Date(transaction.closing_date).toLocaleDateString()
                                            : 'Not set' })] })] })] }), _jsxs("div", { className: "bg-white shadow-sm rounded-lg p-4 md:p-4", children: [_jsxs("div", { className: "flex flex-col gap-4 md:flex-row md:flex-wrap md:items-center", children: [_jsx("div", { className: "flex-1 min-w-full md:min-w-[200px]", children: _jsxs("div", { className: "relative", children: [_jsx(Search, { className: cn('absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400', 'w-5 h-5 md:w-4 md:h-4') }), _jsx("input", { type: "text", placeholder: "Search tasks...", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), className: cn('w-full border border-gray-300 rounded-md', 'focus:ring-2 focus:ring-blue-500 focus:border-transparent', 'text-base md:text-sm', 
                                            // Touch-friendly padding
                                            'pl-10 pr-4 py-3 md:py-2') })] }) }), _jsx("div", { className: "w-full md:w-auto md:min-w-[150px]", children: _jsxs("select", { value: statusFilter, onChange: (e) => setStatusFilter(e.target.value), className: cn('w-full border border-gray-300 rounded-md', 'focus:ring-2 focus:ring-blue-500 focus:border-transparent', 'text-base md:text-sm', 
                                    // Touch-friendly padding
                                    'px-4 py-3 md:px-3 md:py-2'), children: [_jsx("option", { value: "all", children: "All Statuses" }), _jsx("option", { value: "pending", children: "Pending" }), _jsx("option", { value: "in_progress", children: "In Progress" }), _jsx("option", { value: "completed", children: "Completed" }), _jsx("option", { value: "blocked", children: "Blocked" }), _jsx("option", { value: "skipped", children: "Skipped" })] }) }), _jsx("div", { className: "w-full md:w-auto md:min-w-[150px]", children: _jsxs("select", { value: ownerRoleFilter, onChange: (e) => setOwnerRoleFilter(e.target.value), className: cn('w-full border border-gray-300 rounded-md', 'focus:ring-2 focus:ring-blue-500 focus:border-transparent', 'text-base md:text-sm', 
                                    // Touch-friendly padding
                                    'px-4 py-3 md:px-3 md:py-2'), children: [_jsx("option", { value: "all", children: "All Roles" }), _jsx("option", { value: "agent", children: "Agent" }), _jsx("option", { value: "buyer", children: "Buyer" }), _jsx("option", { value: "seller", children: "Seller" }), _jsx("option", { value: "buyer_attorney", children: "Buyer Attorney" }), _jsx("option", { value: "seller_attorney", children: "Seller Attorney" }), _jsx("option", { value: "vendor", children: "Vendor" })] }) }), _jsxs("div", { className: "flex flex-col gap-3 md:flex-row md:gap-2 w-full md:w-auto", children: [_jsxs("label", { className: cn('flex items-center gap-2', 'text-sm md:text-sm', 
                                        // Touch-friendly padding
                                        'py-1'), children: [_jsx("input", { type: "checkbox", checked: showDependencies, onChange: (e) => setShowDependencies(e.target.checked), className: cn('rounded border-gray-300 text-blue-500 focus:ring-blue-500', 
                                                // Touch-friendly size
                                                'w-5 h-5 md:w-4 md:h-4') }), "Dependencies"] }), _jsxs("label", { className: cn('flex items-center gap-2', 'text-sm md:text-sm', 
                                        // Touch-friendly padding
                                        'py-1'), children: [_jsx("input", { type: "checkbox", checked: showMilestones, onChange: (e) => setShowMilestones(e.target.checked), className: cn('rounded border-gray-300 text-blue-500 focus:ring-blue-500', 
                                                // Touch-friendly size
                                                'w-5 h-5 md:w-4 md:h-4') }), "Milestones"] }), _jsxs("label", { className: cn('flex items-center gap-2', 'text-sm md:text-sm', 
                                        // Touch-friendly padding
                                        'py-1'), children: [_jsx("input", { type: "checkbox", checked: groupByPhase, onChange: (e) => setGroupByPhase(e.target.checked), className: cn('rounded border-gray-300 text-blue-500 focus:ring-blue-500', 
                                                // Touch-friendly size
                                                'w-5 h-5 md:w-4 md:h-4') }), "Group by Phase"] })] })] }), _jsxs("div", { className: "mt-3 text-sm md:text-sm text-gray-600", children: ["Showing ", filteredTasks.length, " of ", tasksResponse?.data?.length || 0, " tasks"] })] }), _jsx(GanttChart, { tasks: filteredTasks, transaction: transaction, onTaskClick: handleTaskClick, onTaskDragEnd: handleTaskDragEnd, showDependencies: showDependencies, showMilestones: showMilestones, groupByPhase: groupByPhase })] }));
};
