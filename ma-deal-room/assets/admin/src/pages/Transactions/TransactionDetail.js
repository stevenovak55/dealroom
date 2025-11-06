import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import { ArrowLeft, Edit, Trash2, Plus, Calendar, FileText } from 'lucide-react';
import { useGetTransaction, useDeleteTransaction } from '@/api/queries/useTransactions';
import { useGetTasks, useDeleteTask } from '@/api/queries/useTasks';
import { useGetParties, useDeleteParty } from '@/api/queries/useParties';
import { useGetTransactionEvents } from '@/api/queries/useEvents';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { PageLoader } from '@/components/shared/Loader';
import { formatDate, formatDateTime, formatCurrency } from '@/utils/formatDate';
import { TaskList } from '@/components/Tasks/TaskList';
import { PartyFormModal } from '@/components/Parties/PartyFormModal';
import { TaskFormModal } from '@/components/Tasks/TaskFormModal';
import { ApplyTemplateModal } from '@/components/Transactions/ApplyTemplateModal';
import { DocumentUpload } from '@/components/Documents/DocumentUpload';
import { DocumentList } from '@/components/Documents/DocumentList';
import { DocumentEditModal } from '@/components/Documents/DocumentEditModal';
import { EditableTransactionTimeline } from '@/components/Timeline/EditableTransactionTimeline';
import { EditablePropertyDetails } from '@/components/Transactions/EditablePropertyDetails';
import { cn } from '@/utils/cn';
import { useIsMobile } from '@/hooks/useMediaQuery';
export const TransactionDetail = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const isMobile = useIsMobile();
    const transactionId = parseInt(id || '0');
    const [activeTab, setActiveTab] = useState('details');
    // Modal state
    const [showPartyModal, setShowPartyModal] = useState(false);
    const [editingParty, setEditingParty] = useState();
    const [showTaskModal, setShowTaskModal] = useState(false);
    const [editingTask, setEditingTask] = useState();
    const [showDocumentEditModal, setShowDocumentEditModal] = useState(false);
    const [editingDocument, setEditingDocument] = useState();
    const [showApplyTemplateModal, setShowApplyTemplateModal] = useState(false);
    // Task filters state
    const [taskFilters, setTaskFilters] = useState({
        status: '',
        assigneeRole: '',
        search: '',
    });
    const { data: transaction, isLoading: transactionLoading } = useGetTransaction(transactionId);
    const { data: tasksData } = useGetTasks({
        transaction_id: transactionId,
        status: taskFilters.status || undefined,
        search: taskFilters.search || undefined,
    });
    const { data: parties } = useGetParties(transactionId);
    const { data: eventsData } = useGetTransactionEvents(transactionId);
    const deleteMutation = useDeleteTransaction();
    const deletePartyMutation = useDeleteParty(transactionId);
    const deleteTaskMutation = useDeleteTask();
    if (transactionLoading) {
        return _jsx(PageLoader, {});
    }
    if (!transaction) {
        return _jsx("div", { children: "Transaction not found" });
    }
    const tasks = tasksData?.data || [];
    const events = eventsData?.data || [];
    const handleDelete = async () => {
        if (!confirm('Are you sure you want to delete this transaction? This cannot be undone.')) {
            return;
        }
        try {
            await deleteMutation.mutateAsync(transactionId);
            navigate('/transactions');
        }
        catch (error) {
            console.error('Failed to delete transaction:', error);
            alert('Failed to delete transaction. Please try again.');
        }
    };
    // Party handlers
    const handleAddParty = () => {
        setEditingParty(undefined);
        setShowPartyModal(true);
    };
    const handleEditParty = (party) => {
        setEditingParty(party);
        setShowPartyModal(true);
    };
    const handleDeleteParty = async (partyId) => {
        if (!confirm('Are you sure you want to delete this party?')) {
            return;
        }
        try {
            await deletePartyMutation.mutateAsync(partyId);
        }
        catch (error) {
            console.error('Failed to delete party:', error);
            alert('Failed to delete party. Please try again.');
        }
    };
    const handleClosePartyModal = () => {
        setShowPartyModal(false);
        setEditingParty(undefined);
    };
    // Task handlers
    const handleAddTask = () => {
        setEditingTask(undefined);
        setShowTaskModal(true);
    };
    const handleEditTask = (task) => {
        setEditingTask(task);
        setShowTaskModal(true);
    };
    const handleDeleteTask = async (taskId) => {
        if (!confirm('Are you sure you want to delete this task?')) {
            return;
        }
        try {
            await deleteTaskMutation.mutateAsync(taskId);
        }
        catch (error) {
            console.error('Failed to delete task:', error);
            alert('Failed to delete task. Please try again.');
        }
    };
    const handleCloseTaskModal = () => {
        setShowTaskModal(false);
        setEditingTask(undefined);
    };
    // Document handlers
    const handleEditDocument = (document) => {
        setEditingDocument(document);
        setShowDocumentEditModal(true);
    };
    const handleCloseDocumentEditModal = () => {
        setShowDocumentEditModal(false);
        setEditingDocument(undefined);
    };
    const tabs = [
        { id: 'details', label: 'Property Details' },
        { id: 'tasks', label: `Tasks (${tasks.length})` },
        { id: 'parties', label: `Parties (${parties?.length || 0})` },
        { id: 'documents', label: 'Documents' },
        { id: 'activity', label: 'Activity' },
    ];
    return (_jsxs("div", { className: "space-y-5 md:space-y-6", children: [_jsx("div", { className: "space-y-4 md:space-y-0", children: _jsxs("div", { className: cn('flex flex-col space-y-3', 'md:flex-row md:items-center md:justify-between md:space-y-0'), children: [_jsxs("div", { className: "flex flex-col space-y-3 md:flex-row md:items-center md:gap-4 md:space-y-0", children: [_jsx(Link, { to: "/transactions", className: "inline-block w-fit", children: _jsxs(Button, { variant: "ghost", size: "md", children: [_jsx(ArrowLeft, { className: "h-5 w-5 mr-2" }), "Back"] }) }), _jsxs("div", { children: [_jsx("h1", { className: "text-xl md:text-2xl lg:text-3xl font-bold text-gray-900", children: transaction.property_address }), _jsxs("p", { className: "text-sm md:text-base text-gray-500 mt-1", children: [transaction.property_city, ", ", transaction.property_state, " ", transaction.property_zip] })] })] }), _jsxs("div", { className: cn('grid grid-cols-2 gap-2', 'md:flex md:items-center md:gap-2'), children: [_jsxs(Button, { variant: "secondary", size: "md", onClick: () => navigate(`/transactions/${transactionId}/timeline`), className: "w-full md:w-auto", children: [_jsx(Calendar, { className: "h-5 w-5 md:h-4 md:w-4 mr-2" }), _jsx("span", { className: "hidden sm:inline", children: "Timeline" }), _jsx("span", { className: "sm:hidden", children: "Timeline" })] }), _jsxs(Button, { variant: "secondary", size: "md", onClick: () => setShowApplyTemplateModal(true), className: "w-full md:w-auto", children: [_jsx(FileText, { className: "h-5 w-5 md:h-4 md:w-4 mr-2" }), _jsx("span", { className: "hidden sm:inline", children: "Apply Template" }), _jsx("span", { className: "sm:hidden", children: "Template" })] }), _jsxs(Button, { variant: "secondary", size: "md", onClick: () => navigate(`/transactions/${transactionId}/edit`), className: "w-full md:w-auto", children: [_jsx(Edit, { className: "h-5 w-5 md:h-4 md:w-4 mr-2" }), "Edit"] }), _jsxs(Button, { variant: "danger", size: "md", onClick: handleDelete, isLoading: deleteMutation.isPending, className: "w-full md:w-auto", children: [_jsx(Trash2, { className: "h-5 w-5 md:h-4 md:w-4 mr-2" }), "Delete"] })] })] }) }), _jsx(Card, { className: "p-5 md:p-6", children: _jsxs("div", { className: "grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5 md:gap-6", children: [_jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-sm text-gray-500", children: "Status" }), _jsx(Badge, { variant: "info", size: "md", className: "mt-2 md:mt-1", children: transaction.status })] }), _jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-sm text-gray-500", children: "Property Type" }), _jsx("p", { className: "text-base md:text-lg font-semibold text-gray-900 mt-2 md:mt-1", children: transaction.property_type })] }), _jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-sm text-gray-500", children: "Closing Date" }), _jsx("p", { className: "text-base md:text-lg font-semibold text-gray-900 mt-2 md:mt-1", children: formatDate(transaction.closing_date) })] }), _jsxs("div", { children: [_jsx("p", { className: "text-sm md:text-sm text-gray-500", children: "Sale Price" }), _jsx("p", { className: "text-base md:text-lg font-semibold text-gray-900 mt-2 md:mt-1", children: formatCurrency(transaction.sale_price) })] })] }) }), isMobile ? (_jsx("div", { className: "px-1", children: _jsx("select", { value: activeTab, onChange: (e) => setActiveTab(e.target.value), className: "w-full px-3 py-3 text-base font-medium border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500", children: tabs.map((tab) => (_jsx("option", { value: tab.id, children: tab.label }, tab.id))) }) })) : (_jsx("div", { className: "border-b border-gray-200", children: _jsx("nav", { className: "flex gap-8", children: tabs.map((tab) => (_jsx("button", { onClick: () => setActiveTab(tab.id), className: cn('border-b-2 font-medium whitespace-nowrap py-4 px-1 text-base', activeTab === tab.id
                            ? 'border-primary-500 text-primary-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'), children: tab.label }, tab.id))) }) })), activeTab === 'details' && (_jsxs(_Fragment, { children: [_jsxs(Card, { className: "mb-5 md:mb-6", children: [_jsxs(CardHeader, { children: [_jsx(CardTitle, { className: "text-lg md:text-xl", children: "Transaction Timeline" }), _jsx("p", { className: "text-sm md:text-base text-gray-500 mt-1", children: "Click any milestone to edit its date. We'll suggest dates based on MA standard timeline." })] }), _jsx(CardContent, { children: _jsx(EditableTransactionTimeline, { transaction: transaction }) })] }), _jsxs(Card, { children: [_jsxs(CardHeader, { children: [_jsx(CardTitle, { className: "text-lg md:text-xl", children: "Property Details" }), _jsx("p", { className: "text-sm md:text-base text-gray-500 mt-1", children: "Hover over any field and click the edit icon to update. All fields are optional." })] }), _jsxs(CardContent, { children: [_jsx(EditablePropertyDetails, { transaction: transaction }), transaction.notes && (_jsxs("div", { className: "mt-5 md:mt-6 pt-5 md:pt-6 border-t border-gray-200", children: [_jsx("h4", { className: "text-base md:text-base font-medium text-gray-900 mb-2", children: "Notes" }), _jsx("p", { className: "text-sm md:text-base text-gray-600", children: transaction.notes })] }))] })] })] })), activeTab === 'tasks' && (_jsx(TaskList, { tasks: tasks, transactionId: transactionId, filters: taskFilters, onFiltersChange: setTaskFilters, onAddTask: handleAddTask, onEditTask: handleEditTask, onDeleteTask: handleDeleteTask })), activeTab === 'parties' && (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsxs("div", { className: cn('flex flex-col space-y-3', 'md:flex-row md:items-center md:justify-between md:space-y-0'), children: [_jsx(CardTitle, { className: "text-lg md:text-xl", children: "Transaction Parties" }), _jsxs(Button, { size: "md", onClick: handleAddParty, className: "w-full md:w-auto", children: [_jsx(Plus, { className: "h-5 w-5 mr-2" }), "Add Party"] })] }) }), _jsx(CardContent, { children: parties && parties.length === 0 ? (_jsxs("div", { className: "text-center py-10 md:py-12", children: [_jsx("p", { className: "text-sm md:text-base text-gray-500 mb-4", children: "No parties added yet" }), _jsxs(Button, { size: "lg", onClick: handleAddParty, className: "w-full md:w-auto", children: [_jsx(Plus, { className: "h-5 w-5 mr-2" }), "Add Your First Party"] })] })) : (_jsx("div", { className: "space-y-4 md:space-y-4", children: parties?.map((party) => (_jsxs("div", { className: cn('border border-gray-200 rounded-lg', 'p-4 md:p-4'), children: [_jsxs("div", { className: cn('flex flex-col space-y-3', 'md:flex-row md:items-start md:justify-between md:space-y-0'), children: [_jsxs("div", { className: "flex-1", children: [_jsx("p", { className: "text-base md:text-base font-medium text-gray-900", children: party.contact_name }), _jsx("p", { className: "text-sm md:text-sm text-gray-500 capitalize mt-1", children: party.role.replace(/_/g, ' ') }), party.company_name && (_jsx("p", { className: "text-sm md:text-sm text-gray-600 mt-1", children: party.company_name }))] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsx(Badge, { variant: "default", size: "md", children: party.role.replace(/_/g, ' ') }), _jsx(Button, { size: "md", variant: "ghost", onClick: () => handleEditParty(party), children: _jsx(Edit, { className: "h-5 w-5 md:h-4 md:w-4" }) }), _jsx(Button, { size: "md", variant: "ghost", onClick: () => handleDeleteParty(party.id), isLoading: deletePartyMutation.isPending, children: _jsx(Trash2, { className: "h-5 w-5 md:h-4 md:w-4 text-red-600" }) })] })] }), _jsxs("div", { className: "mt-4 md:mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm md:text-sm", children: [party.email && (_jsxs("div", { children: [_jsx("p", { className: "text-gray-500", children: "Email" }), _jsx("p", { className: "text-gray-900 mt-1", children: party.email })] })), party.phone && (_jsxs("div", { children: [_jsx("p", { className: "text-gray-500", children: "Phone" }), _jsx("p", { className: "text-gray-900 mt-1", children: party.phone })] })), party.address && (_jsxs("div", { className: "col-span-1 sm:col-span-2", children: [_jsx("p", { className: "text-gray-500", children: "Address" }), _jsx("p", { className: "text-gray-900 mt-1", children: party.address })] }))] })] }, party.id))) })) })] })), activeTab === 'documents' && (_jsxs("div", { className: "space-y-5 md:space-y-6", children: [_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-lg md:text-xl", children: "Upload Document" }) }), _jsx(CardContent, { children: _jsx(DocumentUpload, { transactionId: transactionId }) })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-lg md:text-xl", children: "Documents" }) }), _jsx(CardContent, { children: _jsx(DocumentList, { transactionId: transactionId, onEdit: handleEditDocument }) })] })] })), activeTab === 'activity' && (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { className: "text-lg md:text-xl", children: "Activity Log" }) }), _jsx(CardContent, { children: _jsx("div", { className: "space-y-4 md:space-y-4", children: events.map((event) => (_jsxs("div", { className: "flex gap-3 md:gap-4", children: [_jsx("div", { className: "flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-primary-500" }), _jsxs("div", { className: "flex-1 pb-4 border-b border-gray-200 last:border-0", children: [_jsx("p", { className: "text-sm md:text-base font-medium text-gray-900 capitalize", children: event.event_type.replace('_', ' ') }), _jsx("p", { className: "text-xs md:text-sm text-gray-500 mt-1", children: formatDateTime(event.created_at) }), event.payload && (_jsx("div", { className: "mt-2 text-xs md:text-sm text-gray-600 overflow-x-auto", children: JSON.stringify(event.payload, null, 2) }))] })] }, event.event_id))) }) })] })), _jsx(PartyFormModal, { isOpen: showPartyModal, onClose: handleClosePartyModal, transactionId: transactionId, party: editingParty }), _jsx(TaskFormModal, { isOpen: showTaskModal, onClose: handleCloseTaskModal, transactionId: transactionId, task: editingTask }), _jsx(ApplyTemplateModal, { isOpen: showApplyTemplateModal, onClose: () => setShowApplyTemplateModal(false), transactionId: transactionId }), editingDocument && (_jsx(DocumentEditModal, { isOpen: showDocumentEditModal, onClose: handleCloseDocumentEditModal, document: editingDocument }))] }));
};
