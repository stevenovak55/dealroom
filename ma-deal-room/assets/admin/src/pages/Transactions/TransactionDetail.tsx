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

type TabType = 'details' | 'tasks' | 'parties' | 'documents' | 'activity';

export const TransactionDetail = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
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
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Link to="/transactions">
            <Button variant="ghost" size="sm">
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back
            </Button>
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">{transaction.property_address}</h1>
            <p className="text-sm text-gray-500 mt-1">
              {transaction.property_city}, {transaction.property_state} {transaction.property_zip}
            </p>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="secondary"
            size="sm"
            onClick={() => navigate(`/transactions/${transactionId}/timeline`)}
          >
            <Calendar className="h-4 w-4 mr-2" />
            Timeline
          </Button>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => setShowApplyTemplateModal(true)}
          >
            <FileText className="h-4 w-4 mr-2" />
            Apply Template
          </Button>
          <Button
            variant="secondary"
            size="sm"
            onClick={() => navigate(`/transactions/${transactionId}/edit`)}
          >
            <Edit className="h-4 w-4 mr-2" />
            Edit
          </Button>
          <Button
            variant="danger"
            size="sm"
            onClick={handleDelete}
            isLoading={deleteMutation.isPending}
          >
            <Trash2 className="h-4 w-4 mr-2" />
            Delete
          </Button>
        </div>
      </div>

      {/* Status and key info */}
      <Card className="p-6">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div>
            <p className="text-sm text-gray-500">Status</p>
            <Badge variant="info" className="mt-1">{transaction.status}</Badge>
          </div>
          <div>
            <p className="text-sm text-gray-500">Property Type</p>
            <p className="text-lg font-semibold text-gray-900 mt-1">{transaction.property_type}</p>
          </div>
          <div>
            <p className="text-sm text-gray-500">Closing Date</p>
            <p className="text-lg font-semibold text-gray-900 mt-1">
              {formatDate(transaction.closing_date)}
            </p>
          </div>
          <div>
            <p className="text-sm text-gray-500">Sale Price</p>
            <p className="text-lg font-semibold text-gray-900 mt-1">
              {formatCurrency(transaction.sale_price)}
            </p>
          </div>
        </div>
      </Card>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="flex gap-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id as TabType)}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab.id
                  ? 'border-primary-600 text-primary-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </nav>
      </div>

      {/* Tab content */}
      {activeTab === 'details' && (
        <>
          {/* Transaction Timeline */}
          <Card className="mb-6">
            <CardHeader>
              <CardTitle>Transaction Timeline</CardTitle>
              <p className="text-sm text-gray-500 mt-1">
                Click any milestone to edit its date. We'll suggest dates based on MA standard timeline.
              </p>
            </CardHeader>
            <CardContent>
              <EditableTransactionTimeline transaction={transaction} />
            </CardContent>
          </Card>

          {/* Property Details */}
          <Card>
            <CardHeader>
              <CardTitle>Property Details</CardTitle>
              <p className="text-sm text-gray-500 mt-1">
                Hover over any field and click the edit icon to update. All fields are optional.
              </p>
            </CardHeader>
            <CardContent>
              <EditablePropertyDetails transaction={transaction} />
              {transaction.notes && (
                <div className="mt-6 pt-6 border-t border-gray-200">
                  <h4 className="font-medium text-gray-900 mb-2">Notes</h4>
                  <p className="text-sm text-gray-600">{transaction.notes}</p>
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
            <div className="flex items-center justify-between">
              <CardTitle>Transaction Parties</CardTitle>
              <Button size="sm" onClick={handleAddParty}>
                <Plus className="h-4 w-4 mr-2" />
                Add Party
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {parties && parties.length === 0 ? (
              <div className="text-center py-12">
                <p className="text-gray-500 mb-4">No parties added yet</p>
                <Button size="sm" onClick={handleAddParty}>
                  <Plus className="h-4 w-4 mr-2" />
                  Add Your First Party
                </Button>
              </div>
            ) : (
              <div className="space-y-4">
                {parties?.map((party) => (
                  <div key={party.id} className="p-4 border border-gray-200 rounded-lg">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <p className="font-medium text-gray-900">{party.contact_name}</p>
                        <p className="text-sm text-gray-500 capitalize">{party.role.replace(/_/g, ' ')}</p>
                        {party.company_name && (
                          <p className="text-sm text-gray-600 mt-1">{party.company_name}</p>
                        )}
                      </div>
                      <div className="flex items-center gap-2">
                        <Badge variant="default">{party.role.replace(/_/g, ' ')}</Badge>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => handleEditParty(party)}
                        >
                          <Edit className="h-4 w-4" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => handleDeleteParty(party.id)}
                          isLoading={deletePartyMutation.isPending}
                        >
                          <Trash2 className="h-4 w-4 text-red-600" />
                        </Button>
                      </div>
                    </div>
                    <div className="mt-3 grid grid-cols-2 gap-4 text-sm">
                      {party.email && (
                        <div>
                          <p className="text-gray-500">Email</p>
                          <p className="text-gray-900">{party.email}</p>
                        </div>
                      )}
                      {party.phone && (
                        <div>
                          <p className="text-gray-500">Phone</p>
                          <p className="text-gray-900">{party.phone}</p>
                        </div>
                      )}
                      {party.address && (
                        <div className="col-span-2">
                          <p className="text-gray-500">Address</p>
                          <p className="text-gray-900">{party.address}</p>
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
        <div className="space-y-6">
          {/* Upload Section */}
          <Card>
            <CardHeader>
              <CardTitle>Upload Document</CardTitle>
            </CardHeader>
            <CardContent>
              <DocumentUpload transactionId={transactionId} />
            </CardContent>
          </Card>

          {/* Documents List */}
          <Card>
            <CardHeader>
              <CardTitle>Documents</CardTitle>
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
            <CardTitle>Activity Log</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {events.map((event) => (
                <div key={event.event_id} className="flex gap-4">
                  <div className="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-primary-500" />
                  <div className="flex-1 pb-4 border-b border-gray-200 last:border-0">
                    <p className="text-sm font-medium text-gray-900 capitalize">
                      {event.event_type.replace('_', ' ')}
                    </p>
                    <p className="text-sm text-gray-500 mt-1">
                      {formatDateTime(event.created_at)}
                    </p>
                    {event.payload && (
                      <div className="mt-2 text-sm text-gray-600">
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
