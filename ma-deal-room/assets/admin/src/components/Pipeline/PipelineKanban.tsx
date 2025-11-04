import React, { useState, useMemo } from 'react';
import { GripVertical, Plus, MoreVertical, DollarSign, Calendar, MapPin, User, Tag } from 'lucide-react';
import { Card, CardContent } from '@/components/shared/Card';
import type { Transaction } from '@/api/types';
import { format, differenceInDays } from 'date-fns';

export type PipelineStage =
  | 'prospect'
  | 'listing_active'
  | 'under_agreement'
  | 'pending_closing'
  | 'closed'
  | 'cancelled';

interface PipelineKanbanProps {
  transactions: Transaction[];
  onUpdateStatus: (transactionId: number, newStatus: string) => Promise<void>;
  onTransactionClick?: (transaction: Transaction) => void;
  onAddTransaction?: (stage: string) => void;
}

interface StageConfig {
  id: PipelineStage;
  label: string;
  color: string;
  bgColor: string;
  borderColor: string;
}

const PIPELINE_STAGES: StageConfig[] = [
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

export const PipelineKanban: React.FC<PipelineKanbanProps> = ({
  transactions,
  onUpdateStatus,
  onTransactionClick,
  onAddTransaction,
}) => {
  const [draggedCard, setDraggedCard] = useState<Transaction | null>(null);
  const [draggedOverStage, setDraggedOverStage] = useState<string | null>(null);

  // Group transactions by stage
  const transactionsByStage = useMemo(() => {
    const grouped = new Map<PipelineStage, Transaction[]>();
    PIPELINE_STAGES.forEach(stage => {
      grouped.set(stage.id, []);
    });

    transactions.forEach(transaction => {
      const status = transaction.status as PipelineStage;
      if (grouped.has(status)) {
        grouped.get(status)?.push(transaction);
      }
    });

    return grouped;
  }, [transactions]);

  // Calculate stage totals
  const stageTotals = useMemo(() => {
    const totals = new Map<PipelineStage, number>();
    transactionsByStage.forEach((txns, stage) => {
      const total = txns.reduce((sum, t) => sum + (t.sale_price || 0), 0);
      totals.set(stage, total);
    });
    return totals;
  }, [transactionsByStage]);

  const handleDragStart = (transaction: Transaction) => {
    setDraggedCard(transaction);
  };

  const handleDragEnd = () => {
    setDraggedCard(null);
    setDraggedOverStage(null);
  };

  const handleDragOver = (e: React.DragEvent, stageId: string) => {
    e.preventDefault();
    setDraggedOverStage(stageId);
  };

  const handleDrop = async (e: React.DragEvent, stageId: string) => {
    e.preventDefault();
    if (!draggedCard || draggedCard.status === stageId) {
      setDraggedCard(null);
      setDraggedOverStage(null);
      return;
    }

    try {
      await onUpdateStatus(draggedCard.transaction_id, stageId);
    } catch (error) {
      console.error('Failed to update transaction status:', error);
    }

    setDraggedCard(null);
    setDraggedOverStage(null);
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const getDaysInStage = (transaction: Transaction) => {
    const now = new Date();
    const updatedAt = transaction.updated_at ? new Date(transaction.updated_at) : new Date(transaction.created_at);
    return differenceInDays(now, updatedAt);
  };

  return (
    <div className="flex gap-4 overflow-x-auto pb-4 h-[calc(100vh-200px)]">
      {PIPELINE_STAGES.map((stage) => {
        const stageTransactions = transactionsByStage.get(stage.id) || [];
        const stageTotal = stageTotals.get(stage.id) || 0;
        const isDraggedOver = draggedOverStage === stage.id;

        return (
          <div
            key={stage.id}
            className="flex-shrink-0 w-80 flex flex-col"
            onDragOver={(e) => handleDragOver(e, stage.id)}
            onDrop={(e) => handleDrop(e, stage.id)}
          >
            {/* Stage Header */}
            <Card className={`mb-3 ${stage.borderColor} border-t-4`}>
              <CardContent className="p-4">
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <span className={`px-3 py-1 rounded-full text-sm font-semibold ${stage.bgColor} ${stage.color}`}>
                      {stageTransactions.length}
                    </span>
                    <h3 className="font-semibold text-gray-900">{stage.label}</h3>
                  </div>
                  {onAddTransaction && (
                    <button
                      onClick={() => onAddTransaction(stage.id)}
                      className="p-1 text-gray-400 hover:text-gray-600 rounded transition-colors"
                    >
                      <Plus className="h-4 w-4" />
                    </button>
                  )}
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <DollarSign className="h-4 w-4 text-gray-400" />
                  <span className="font-semibold text-gray-700">
                    {formatCurrency(stageTotal)}
                  </span>
                </div>
              </CardContent>
            </Card>

            {/* Cards Container */}
            <div
              className={`flex-1 space-y-3 p-2 rounded-lg transition-colors ${
                isDraggedOver ? 'bg-blue-50 border-2 border-blue-300 border-dashed' : 'bg-gray-50'
              }`}
            >
              {stageTransactions.length === 0 ? (
                <div className="flex items-center justify-center h-32 text-gray-400 text-sm">
                  Drop deals here
                </div>
              ) : (
                stageTransactions.map((transaction) => {
                  const daysInStage = getDaysInStage(transaction);

                  return (
                    <div
                      key={transaction.transaction_id}
                      draggable
                      onDragStart={() => handleDragStart(transaction)}
                      onDragEnd={handleDragEnd}
                      onClick={() => onTransactionClick?.(transaction)}
                      className={`cursor-move ${
                        draggedCard?.transaction_id === transaction.transaction_id
                          ? 'opacity-50 rotate-2'
                          : ''
                      }`}
                    >
                      <Card className="hover:shadow-lg transition-all">
                      <CardContent className="p-4">
                        {/* Header with grip and menu */}
                        <div className="flex items-start gap-2 mb-3">
                          <GripVertical className="h-4 w-4 text-gray-400 flex-shrink-0 mt-1 cursor-grab" />
                          <div className="flex-1 min-w-0">
                            <h4 className="font-semibold text-gray-900 text-sm truncate">
                              {transaction.property_address}
                            </h4>
                            <div className="flex items-center gap-1 text-xs text-gray-600 mt-1">
                              <MapPin className="h-3 w-3" />
                              {transaction.property_city}, {transaction.property_state}
                            </div>
                          </div>
                          <button
                            onClick={(e) => {
                              e.stopPropagation();
                            }}
                            className="p-1 text-gray-400 hover:text-gray-600 rounded"
                          >
                            <MoreVertical className="h-4 w-4" />
                          </button>
                        </div>

                        {/* Price */}
                        <div className="flex items-center gap-2 mb-3 p-2 bg-gray-50 rounded">
                          <DollarSign className="h-4 w-4 text-green-600" />
                          <span className="font-bold text-gray-900">
                            {formatCurrency(transaction.sale_price || 0)}
                          </span>
                        </div>

                        {/* Agent */}
                        {transaction.assigned_agent_id && (
                          <div className="flex items-center gap-2 text-xs text-gray-600 mb-2">
                            <User className="h-3 w-3" />
                            Agent ID: {transaction.assigned_agent_id}
                          </div>
                        )}

                        {/* Dates */}
                        <div className="space-y-1 text-xs text-gray-600 mb-3">
                          {transaction.listing_date && (
                            <div className="flex items-center gap-2">
                              <Calendar className="h-3 w-3" />
                              Listed: {format(new Date(transaction.listing_date), 'MMM d, yyyy')}
                            </div>
                          )}
                          {transaction.closing_date && (
                            <div className="flex items-center gap-2">
                              <Calendar className="h-3 w-3" />
                              Closes: {format(new Date(transaction.closing_date), 'MMM d, yyyy')}
                            </div>
                          )}
                        </div>

                        {/* Task Progress */}
                        {transaction.task_summary && (
                          <div className="mb-3">
                            <div className="flex items-center justify-between text-xs text-gray-600 mb-1">
                              <span>Tasks</span>
                              <span>
                                {transaction.task_summary.completed}/{transaction.task_summary.total}
                              </span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-1.5">
                              <div
                                className="bg-blue-600 h-1.5 rounded-full transition-all"
                                style={{
                                  width: `${
                                    transaction.task_summary.total > 0
                                      ? (transaction.task_summary.completed / transaction.task_summary.total) * 100
                                      : 0
                                  }%`,
                                }}
                              />
                            </div>
                          </div>
                        )}

                        {/* Tags */}
                        {transaction.property_type && (
                          <div className="flex flex-wrap gap-1">
                            <span className="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 rounded-full flex items-center gap-1">
                              <Tag className="h-3 w-3" />
                              {transaction.property_type}
                            </span>
                            <span className="px-2 py-0.5 text-xs bg-gray-200 text-gray-700 rounded-full">
                              {daysInStage}d in stage
                            </span>
                          </div>
                        )}

                        {/* Alerts */}
                        {transaction.task_summary && transaction.task_summary.overdue > 0 && (
                          <div className="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700 font-medium">
                            ⚠️ {transaction.task_summary.overdue} overdue task
                            {transaction.task_summary.overdue !== 1 ? 's' : ''}
                          </div>
                        )}
                      </CardContent>
                    </Card>
                    </div>
                  );
                })
              )}
            </div>
          </div>
        );
      })}
    </div>
  );
};
