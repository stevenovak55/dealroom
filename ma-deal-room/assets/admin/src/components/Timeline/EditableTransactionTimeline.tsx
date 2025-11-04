import { useState, useEffect } from 'react';
import { Check, Circle, Clock, Edit2, HelpCircle, Sparkles, X, Save } from 'lucide-react';
import { formatDate } from '@/utils/formatDate';
import { Tooltip } from '@/components/shared/Tooltip';
import { Button } from '@/components/shared/Button';
import { useUpdateTransaction } from '@/api/queries/useTransactions';
import {
  suggestMilestoneDates,
  isOverdue,
  isDueSoon,
  daysBetween,
  getRelativeTime,
  type DateSuggestions,
} from '@/utils/timelineCalculator';
import type { Transaction } from '@/api/types';

interface EditableTransactionTimelineProps {
  transaction: Transaction;
}

interface Milestone {
  id: keyof Pick<Transaction, 'listing_date' | 'offer_accepted_date' | 'ps_agreement_date' | 'loan_commitment_date' | 'closing_date'>;
  label: string;
  date?: string;
  description: string;
  helpText: {
    listing: string;
    buyer: string;
  } | string;
}

export const EditableTransactionTimeline = ({ transaction }: EditableTransactionTimelineProps) => {
  const updateMutation = useUpdateTransaction(transaction.transaction_id);
  const [editingMilestone, setEditingMilestone] = useState<string | null>(null);
  const [localDates, setLocalDates] = useState({
    listing_date: transaction.listing_date || '',
    offer_accepted_date: transaction.offer_accepted_date || '',
    ps_agreement_date: transaction.ps_agreement_date || '',
    loan_commitment_date: transaction.loan_commitment_date || '',
    closing_date: transaction.closing_date || '',
  });
  const [suggestions, setSuggestions] = useState<DateSuggestions>({});

  const transactionSide = transaction.transaction_side || 'listing';

  const milestones: Milestone[] = [
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

  const getMilestoneHelp = (milestone: Milestone): string => {
    if (typeof milestone.helpText === 'string') {
      return milestone.helpText;
    }
    return milestone.helpText[transactionSide];
  };

  const getMilestoneStatus = (date?: string): 'completed' | 'current' | 'overdue' | 'pending' => {
    if (!date) return 'pending';

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

  const getMilestoneIcon = (status: 'completed' | 'current' | 'overdue' | 'pending') => {
    if (status === 'completed') {
      return <Check className="h-4 w-4 text-white" />;
    }
    if (status === 'current' || status === 'overdue') {
      return <Clock className="h-4 w-4 text-white" />;
    }
    return <Circle className="h-3 w-3 text-gray-400" />;
  };

  const getMilestoneColor = (status: 'completed' | 'current' | 'overdue' | 'pending') => {
    if (status === 'completed') return 'bg-green-500';
    if (status === 'overdue') return 'bg-red-500';
    if (status === 'current') return 'bg-blue-500';
    return 'bg-gray-300';
  };

  const handleDateChange = (milestoneId: string, value: string) => {
    setLocalDates(prev => ({
      ...prev,
      [milestoneId]: value
    }));
  };

  const handleSaveDate = async (milestoneId: string) => {
    try {
      await updateMutation.mutateAsync({
        [milestoneId]: localDates[milestoneId as keyof typeof localDates] || null
      });
      setEditingMilestone(null);
    } catch (error) {
      console.error('Failed to update date:', error);
      alert('Failed to update date. Please try again.');
    }
  };

  const handleCancelEdit = (milestoneId: string) => {
    // Restore original value
    setLocalDates(prev => ({
      ...prev,
      [milestoneId]: transaction[milestoneId as keyof Transaction] as string || ''
    }));
    setEditingMilestone(null);
  };

  const handleApplySuggestions = async () => {
    const updates: Record<string, string> = {};
    Object.entries(suggestions).forEach(([key, value]) => {
      if (!localDates[key as keyof typeof localDates] && value) {
        updates[key] = value;
      }
    });

    if (Object.keys(updates).length === 0) {
      alert('No new suggestions to apply');
      return;
    }

    try {
      await updateMutation.mutateAsync(updates);
    } catch (error) {
      console.error('Failed to apply suggestions:', error);
      alert('Failed to apply suggestions. Please try again.');
    }
  };

  const hasSuggestions = Object.keys(suggestions).length > 0;

  return (
    <div className="py-6">
      {/* Actions */}
      {hasSuggestions && (
        <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="flex items-start justify-between">
            <div className="flex items-start gap-3">
              <Sparkles className="h-5 w-5 text-blue-600 mt-0.5" />
              <div>
                <p className="text-sm font-medium text-blue-900">
                  Suggested Dates Available
                </p>
                <p className="text-sm text-blue-700 mt-1">
                  Based on MA standard timeline, we can suggest dates for empty milestones
                </p>
              </div>
            </div>
            <Button
              size="sm"
              variant="primary"
              onClick={handleApplySuggestions}
              isLoading={updateMutation.isPending}
            >
              Use Suggestions
            </Button>
          </div>
        </div>
      )}

      <div className="relative">
        {/* Timeline line */}
        <div className="absolute top-6 left-0 right-0 h-0.5 bg-gray-200" aria-hidden="true">
          <div
            className="h-full bg-green-500 transition-all duration-500"
            style={{
              width: `${(milestones.filter((m) => getMilestoneStatus(m.date) === 'completed').length / milestones.length) * 100}%`
            }}
          />
        </div>

        {/* Milestones */}
        <div className="relative flex justify-between">
          {milestones.map((milestone, index) => {
            const status = getMilestoneStatus(milestone.date);
            const isEditing = editingMilestone === milestone.id;
            const hasSuggestion = suggestions[milestone.id] && !milestone.date;

            return (
              <div key={milestone.id} className="flex flex-col items-center" style={{ flex: 1 }}>
                {/* Icon */}
                <div
                  className={`relative z-10 flex items-center justify-center w-12 h-12 rounded-full border-4 border-white ${getMilestoneColor(status)} transition-all duration-300 cursor-pointer hover:scale-110 group`}
                  onClick={() => !isEditing && setEditingMilestone(milestone.id)}
                >
                  {getMilestoneIcon(status)}
                  {!isEditing && (
                    <div className="absolute -top-1 -right-1 h-5 w-5 bg-primary-600 text-white rounded-full p-1 shadow-lg border-2 border-white group-hover:bg-primary-700 group-hover:scale-125 transition-all duration-200">
                      <Edit2 className="h-full w-full" />
                    </div>
                  )}
                </div>

                {/* Label & Help */}
                <div className="mt-3 text-center">
                  <div className="flex items-center gap-1 justify-center">
                    <p className={`text-sm font-medium ${status === 'pending' ? 'text-gray-500' : 'text-gray-900'}`}>
                      {milestone.label}
                    </p>
                    <Tooltip content={getMilestoneHelp(milestone)}>
                      <HelpCircle className="h-3.5 w-3.5 text-gray-400 hover:text-gray-600 cursor-help" />
                    </Tooltip>
                  </div>
                  <p className="text-xs text-gray-500 mt-1">{milestone.description}</p>

                  {/* Date display or edit */}
                  {isEditing ? (
                    <div className="mt-2 flex flex-col items-center gap-2">
                      <input
                        type="date"
                        value={localDates[milestone.id]}
                        onChange={(e) => handleDateChange(milestone.id, e.target.value)}
                        className="text-xs px-2 py-1 border border-gray-300 rounded"
                        autoFocus
                      />
                      <div className="flex gap-1">
                        <button
                          onClick={() => handleSaveDate(milestone.id)}
                          className="p-1 text-green-600 hover:bg-green-50 rounded"
                          disabled={updateMutation.isPending}
                        >
                          <Save className="h-3 w-3" />
                        </button>
                        <button
                          onClick={() => handleCancelEdit(milestone.id)}
                          className="p-1 text-red-600 hover:bg-red-50 rounded"
                        >
                          <X className="h-3 w-3" />
                        </button>
                      </div>
                    </div>
                  ) : milestone.date ? (
                    <div className="mt-1">
                      <p className={`text-xs font-medium ${
                        status === 'completed' ? 'text-green-600' :
                        status === 'overdue' ? 'text-red-600' :
                        status === 'current' ? 'text-blue-600' :
                        'text-gray-900'
                      }`}>
                        {formatDate(milestone.date)}
                      </p>
                      <p className="text-xs text-gray-500 mt-0.5">
                        {getRelativeTime(milestone.date)}
                      </p>
                    </div>
                  ) : hasSuggestion ? (
                    <div className="mt-1">
                      <p className="text-xs text-gray-400 italic">Not set</p>
                      <p className="text-xs text-blue-600 mt-0.5">
                        Suggest: {formatDate(suggestions[milestone.id])}
                      </p>
                    </div>
                  ) : (
                    <p className="text-xs text-gray-400 mt-1 italic">Not set</p>
                  )}

                  {/* Days until/since */}
                  {milestone.date && index < milestones.length - 1 && milestones[index + 1].date && (
                    <p className="text-xs text-gray-400 mt-2">
                      ↓ {daysBetween(milestone.date, milestones[index + 1].date!)} days
                    </p>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Legend */}
      <div className="mt-8 flex items-center justify-center gap-6 text-xs text-gray-600">
        <div className="flex items-center gap-2">
          <div className="w-3 h-3 rounded-full bg-green-500"></div>
          <span>Completed</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="w-3 h-3 rounded-full bg-blue-500"></div>
          <span>Upcoming</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="w-3 h-3 rounded-full bg-red-500"></div>
          <span>Overdue</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="w-3 h-3 rounded-full bg-gray-300"></div>
          <span>Not Set</span>
        </div>
      </div>

      {/* Days until closing */}
      {localDates.closing_date && (
        <div className="mt-6 text-center">
          <p className="text-2xl font-bold text-gray-900">
            {daysBetween(new Date(), localDates.closing_date)} days
          </p>
          <p className="text-sm text-gray-500">until closing</p>
        </div>
      )}
    </div>
  );
};
