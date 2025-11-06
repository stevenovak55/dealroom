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
import type { Party, Task, Document } from '@/api/types';
import { cn } from '@/utils/cn';
import { useIsMobile } from '@/hooks/useMediaQuery';

/**
 * TransactionDetail Component (Mobile-First Redesign)
 *
 * Responsive transaction detail page with mobile-first design:
 * - Mobile (<768px): Stacked layout, full-width buttons, scrollable tabs
 * - Desktop (>=768px): Horizontal layouts, grouped buttons
 *
 * Features:
 * - Tabbed interface (details, tasks, parties, documents, activity)
 * - Editable property details and timeline
 * - Task, party, and document management
 * - Activity log
 * - Template application
 */

type TabType = 'details' | 'tasks' | 'parties' | 'documents' | 'activity';

export const TransactionDetail = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const isMobile = useIsMobile();
  const transactionId = parseInt(id || '0');
  const [activeTab, setActiveTab] = useState<TabType>('details');

  // Modal state
  const [showPartyModal, setShowPartyModal] = useState(false);
  const [editingParty, setEditingParty] = useState<Party | undefined>();
  const [showTaskModal, setShowTaskModal] = useState(false);
  const [editingTask, setEditingTask] = useState<Task | undefined>();
  const [showDocumentEditModal, setShowDocumentEditModal] = useState(false);
  const [editingDocument, setEditingDocument] = useState<Document | undefined>();
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
    return <PageLoader />;
  }

  if (!transaction) {
    return <div>Transaction not found</div>;
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
    } catch (error) {
      console.error('Failed to delete transaction:', error);
      alert('Failed to delete transaction. Please try again.');
    }
  };

  // Party handlers
  const handleAddParty = () => {
    setEditingParty(undefined);
    setShowPartyModal(true);
  };

  const handleEditParty = (party: Party) => {
    setEditingParty(party);
    setShowPartyModal(true);
  };

  const handleDeleteParty = async (partyId: number) => {
    if (!confirm('Are you sure you want to delete this party?')) {
      return;
    }

    try {
      await deletePartyMutation.mutateAsync(partyId);
    } catch (error) {
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

  const handleEditTask = (task: Task) => {
    setEditingTask(task);
    setShowTaskModal(true);
  };

  const handleDeleteTask = async (taskId: number) => {
    if (!confirm('Are you sure you want to delete this task?')) {
      return;
    }

    try {
      await deleteTaskMutation.mutateAsync(taskId);
    } catch (error) {
      console.error('Failed to delete task:', error);
      alert('Failed to delete task. Please try again.');
    }
  };

  const handleCloseTaskModal = () => {
    setShowTaskModal(false);
    setEditingTask(undefined);
  };

  // Document handlers
  const handleEditDocument = (document: Document) => {
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

  return (
    <div className="space-y-5 md:space-y-6">
      {/* Header - responsive layout */}
      <div className="space-y-4 md:space-y-0">
        {/* Back button + Title - vertical stack on mobile */}
        <div className={cn(
          'flex flex-col space-y-3',
          'md:flex-row md:items-center md:justify-between md:space-y-0'
        )}>
          <div className="flex flex-col space-y-3 md:flex-row md:items-center md:gap-4 md:space-y-0">
            <Link to="/transactions" className="inline-block w-fit">
              <Button variant="ghost" size="md">
                <ArrowLeft className="h-5 w-5 mr-2" />
                Back
              </Button>
            </Link>
            <div>
              <h1 className="text-xl md:text-2xl lg:text-3xl font-bold text-gray-900">
                {transaction.property_address}
              </h1>
              <p className="text-sm md:text-base text-gray-500 mt-1">
                {transaction.property_city}, {transaction.property_state} {transaction.property_zip}
              </p>
            </div>
          </div>

          {/* Action buttons - full-width on mobile, grouped on desktop */}
          <div className={cn(
            'grid grid-cols-2 gap-2',
            'md:flex md:items-center md:gap-2'
          )}>
            <Button
              variant="secondary"
              size="md"
              onClick={() => navigate(`/transactions/${transactionId}/timeline`)}
              className="w-full md:w-auto"
            >
              <Calendar className="h-5 w-5 md:h-4 md:w-4 mr-2" />
              <span className="hidden sm:inline">Timeline</span>
              <span className="sm:hidden">Timeline</span>
            </Button>
            <Button
              variant="secondary"
              size="md"
              onClick={() => setShowApplyTemplateModal(true)}
              className="w-full md:w-auto"
            >
              <FileText className="h-5 w-5 md:h-4 md:w-4 mr-2" />
              <span className="hidden sm:inline">Apply Template</span>
              <span className="sm:hidden">Template</span>
            </Button>
            <Button
              variant="secondary"
              size="md"
              onClick={() => navigate(`/transactions/${transactionId}/edit`)}
              className="w-full md:w-auto"
            >
              <Edit className="h-5 w-5 md:h-4 md:w-4 mr-2" />
              Edit
            </Button>
            <Button
              variant="danger"
              size="md"
              onClick={handleDelete}
              isLoading={deleteMutation.isPending}
              className="w-full md:w-auto"
            >
              <Trash2 className="h-5 w-5 md:h-4 md:w-4 mr-2" />
              Delete
            </Button>
          </div>
        </div>
      </div>

      {/* Status and key info - responsive grid and padding */}
      <Card className="p-5 md:p-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-5 md:gap-6">
          <div>
            <p className="text-sm md:text-sm text-gray-500">Status</p>
            <Badge variant="info" size="md" className="mt-2 md:mt-1">{transaction.status}</Badge>
          </div>
          <div>
            <p className="text-sm md:text-sm text-gray-500">Property Type</p>
            <p className="text-base md:text-lg font-semibold text-gray-900 mt-2 md:mt-1">
              {transaction.property_type}
            </p>
          </div>
          <div>
            <p className="text-sm md:text-sm text-gray-500">Closing Date</p>
            <p className="text-base md:text-lg font-semibold text-gray-900 mt-2 md:mt-1">
              {formatDate(transaction.closing_date)}
            </p>
          </div>
          <div>
            <p className="text-sm md:text-sm text-gray-500">Sale Price</p>
            <p className="text-base md:text-lg font-semibold text-gray-900 mt-2 md:mt-1">
              {formatCurrency(transaction.sale_price)}
            </p>
          </div>
        </div>
      </Card>

      {/* Tabs - dropdown on mobile, tabs on desktop */}
      {isMobile ? (
        <div className="px-1">
          <select
            value={activeTab}
            onChange={(e) => setActiveTab(e.target.value as TabType)}
            className="w-full px-3 py-3 text-base font-medium border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          >
            {tabs.map((tab) => (
              <option key={tab.id} value={tab.id}>
                {tab.label}
              </option>
            ))}
          </select>
        </div>
      ) : (
        <div className="border-b border-gray-200">
          <nav className="flex gap-8">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id as TabType)}
                className={cn(
                  'border-b-2 font-medium whitespace-nowrap py-4 px-1 text-base',
                  activeTab === tab.id
                    ? 'border-primary-500 text-primary-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                )}
              >
                {tab.label}
              </button>
            ))}
          </nav>
        </div>
      )}

      {/* Tab content */}
      {activeTab === 'details' && (
        <>
          {/* Transaction Timeline - responsive card */}
          <Card className="mb-5 md:mb-6">
            <CardHeader>
              <CardTitle className="text-lg md:text-xl">Transaction Timeline</CardTitle>
              <p className="text-sm md:text-base text-gray-500 mt-1">
                Click any milestone to edit its date. We'll suggest dates based on MA standard timeline.
              </p>
            </CardHeader>
            <CardContent>
              <EditableTransactionTimeline transaction={transaction} />
            </CardContent>
          </Card>

          {/* Property Details - responsive card */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg md:text-xl">Property Details</CardTitle>
              <p className="text-sm md:text-base text-gray-500 mt-1">
                Hover over any field and click the edit icon to update. All fields are optional.
              </p>
            </CardHeader>
            <CardContent>
              <EditablePropertyDetails transaction={transaction} />
              {transaction.notes && (
                <div className="mt-5 md:mt-6 pt-5 md:pt-6 border-t border-gray-200">
                  <h4 className="text-base md:text-base font-medium text-gray-900 mb-2">Notes</h4>
                  <p className="text-sm md:text-base text-gray-600">{transaction.notes}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </>
      )}

      {activeTab === 'tasks' && (
        <TaskList
          tasks={tasks}
          transactionId={transactionId}
          filters={taskFilters}
          onFiltersChange={setTaskFilters}
          onAddTask={handleAddTask}
          onEditTask={handleEditTask}
          onDeleteTask={handleDeleteTask}
        />
      )}

      {activeTab === 'parties' && (
        <Card>
          <CardHeader>
            <div className={cn(
              'flex flex-col space-y-3',
              'md:flex-row md:items-center md:justify-between md:space-y-0'
            )}>
              <CardTitle className="text-lg md:text-xl">Transaction Parties</CardTitle>
              <Button size="md" onClick={handleAddParty} className="w-full md:w-auto">
                <Plus className="h-5 w-5 mr-2" />
                Add Party
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {parties && parties.length === 0 ? (
              <div className="text-center py-10 md:py-12">
                <p className="text-sm md:text-base text-gray-500 mb-4">No parties added yet</p>
                <Button size="lg" onClick={handleAddParty} className="w-full md:w-auto">
                  <Plus className="h-5 w-5 mr-2" />
                  Add Your First Party
                </Button>
              </div>
            ) : (
              <div className="space-y-4 md:space-y-4">
                {parties?.map((party) => (
                  <div key={party.id} className={cn(
                    'border border-gray-200 rounded-lg',
                    'p-4 md:p-4'
                  )}>
                    <div className={cn(
                      'flex flex-col space-y-3',
                      'md:flex-row md:items-start md:justify-between md:space-y-0'
                    )}>
                      <div className="flex-1">
                        <p className="text-base md:text-base font-medium text-gray-900">{party.contact_name}</p>
                        <p className="text-sm md:text-sm text-gray-500 capitalize mt-1">
                          {party.role.replace(/_/g, ' ')}
                        </p>
                        {party.company_name && (
                          <p className="text-sm md:text-sm text-gray-600 mt-1">{party.company_name}</p>
                        )}
                      </div>
                      <div className="flex items-center gap-2">
                        <Badge variant="default" size="md">{party.role.replace(/_/g, ' ')}</Badge>
                        <Button
                          size="md"
                          variant="ghost"
                          onClick={() => handleEditParty(party)}
                        >
                          <Edit className="h-5 w-5 md:h-4 md:w-4" />
                        </Button>
                        <Button
                          size="md"
                          variant="ghost"
                          onClick={() => handleDeleteParty(party.id)}
                          isLoading={deletePartyMutation.isPending}
                        >
                          <Trash2 className="h-5 w-5 md:h-4 md:w-4 text-red-600" />
                        </Button>
                      </div>
                    </div>
                    <div className="mt-4 md:mt-3 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm md:text-sm">
                      {party.email && (
                        <div>
                          <p className="text-gray-500">Email</p>
                          <p className="text-gray-900 mt-1">{party.email}</p>
                        </div>
                      )}
                      {party.phone && (
                        <div>
                          <p className="text-gray-500">Phone</p>
                          <p className="text-gray-900 mt-1">{party.phone}</p>
                        </div>
                      )}
                      {party.address && (
                        <div className="col-span-1 sm:col-span-2">
                          <p className="text-gray-500">Address</p>
                          <p className="text-gray-900 mt-1">{party.address}</p>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {activeTab === 'documents' && (
        <div className="space-y-5 md:space-y-6">
          {/* Upload Section - responsive card */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg md:text-xl">Upload Document</CardTitle>
            </CardHeader>
            <CardContent>
              <DocumentUpload transactionId={transactionId} />
            </CardContent>
          </Card>

          {/* Documents List - responsive card */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg md:text-xl">Documents</CardTitle>
            </CardHeader>
            <CardContent>
              <DocumentList
                transactionId={transactionId}
                onEdit={handleEditDocument}
              />
            </CardContent>
          </Card>
        </div>
      )}

      {activeTab === 'activity' && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg md:text-xl">Activity Log</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4 md:space-y-4">
              {events.map((event) => (
                <div key={event.event_id} className="flex gap-3 md:gap-4">
                  <div className="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-primary-500" />
                  <div className="flex-1 pb-4 border-b border-gray-200 last:border-0">
                    <p className="text-sm md:text-base font-medium text-gray-900 capitalize">
                      {event.event_type.replace('_', ' ')}
                    </p>
                    <p className="text-xs md:text-sm text-gray-500 mt-1">
                      {formatDateTime(event.created_at)}
                    </p>
                    {event.payload && (
                      <div className="mt-2 text-xs md:text-sm text-gray-600 overflow-x-auto">
                        {JSON.stringify(event.payload, null, 2)}
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Modals */}
      <PartyFormModal
        isOpen={showPartyModal}
        onClose={handleClosePartyModal}
        transactionId={transactionId}
        party={editingParty}
      />

      <TaskFormModal
        isOpen={showTaskModal}
        onClose={handleCloseTaskModal}
        transactionId={transactionId}
        task={editingTask}
      />

      <ApplyTemplateModal
        isOpen={showApplyTemplateModal}
        onClose={() => setShowApplyTemplateModal(false)}
        transactionId={transactionId}
      />

      {editingDocument && (
        <DocumentEditModal
          isOpen={showDocumentEditModal}
          onClose={handleCloseDocumentEditModal}
          document={editingDocument}
        />
      )}
    </div>
  );
};
