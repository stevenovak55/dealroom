import { Check, Circle, Clock } from 'lucide-react';
import { formatDate } from '@/utils/formatDate';
import type { Transaction } from '@/api/types';

interface TransactionTimelineProps {
  transaction: Transaction;
}

interface Milestone {
  id: string;
  label: string;
  date?: string;
  description: string;
}

export const TransactionTimeline = ({ transaction }: TransactionTimelineProps) => {
  const milestones: Milestone[] = [
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

  const getMilestoneStatus = (date?: string, index?: number): 'completed' | 'current' | 'pending' => {
    if (!date) return 'pending';

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

  const getMilestoneIcon = (status: 'completed' | 'current' | 'pending') => {
    if (status === 'completed') {
      return <Check className="h-4 w-4 text-white" />;
    }
    if (status === 'current') {
      return <Clock className="h-4 w-4 text-white" />;
    }
    return <Circle className="h-3 w-3 text-gray-400" />;
  };

  const getMilestoneColor = (status: 'completed' | 'current' | 'pending') => {
    if (status === 'completed') return 'bg-green-500';
    if (status === 'current') return 'bg-blue-500';
    return 'bg-gray-300';
  };

  return (
    <div className="py-6">
      <div className="relative">
        {/* Timeline line */}
        <div className="absolute top-6 left-0 right-0 h-0.5 bg-gray-200" aria-hidden="true">
          <div
            className="h-full bg-green-500 transition-all duration-500"
            style={{
              width: `${(milestones.filter((m, i) => getMilestoneStatus(m.date, i) === 'completed').length / milestones.length) * 100}%`
            }}
          />
        </div>

        {/* Milestones */}
        <div className="relative flex justify-between">
          {milestones.map((milestone, index) => {
            const status = getMilestoneStatus(milestone.date, index);

            return (
              <div key={milestone.id} className="flex flex-col items-center" style={{ flex: 1 }}>
                {/* Icon */}
                <div
                  className={`relative z-10 flex items-center justify-center w-12 h-12 rounded-full border-4 border-white ${getMilestoneColor(status)} transition-all duration-300`}
                >
                  {getMilestoneIcon(status)}
                </div>

                {/* Label */}
                <div className="mt-3 text-center">
                  <p className={`text-sm font-medium ${status === 'pending' ? 'text-gray-500' : 'text-gray-900'}`}>
                    {milestone.label}
                  </p>
                  <p className="text-xs text-gray-500 mt-1">{milestone.description}</p>

                  {/* Date */}
                  {milestone.date ? (
                    <p className={`text-xs mt-1 font-medium ${
                      status === 'completed' ? 'text-green-600' :
                      status === 'current' ? 'text-blue-600' :
                      'text-gray-900'
                    }`}>
                      {formatDate(milestone.date)}
                    </p>
                  ) : (
                    <p className="text-xs text-gray-400 mt-1 italic">Not set</p>
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
          <span>Current</span>
        </div>
        <div className="flex items-center gap-2">
          <div className="w-3 h-3 rounded-full bg-gray-300"></div>
          <span>Pending</span>
        </div>
      </div>
    </div>
  );
};
