import { useState, useMemo } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Plus, Search } from 'lucide-react';
import { useGetTransactions } from '@/api/queries/useTransactions';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { Card } from '@/components/shared/Card';
import { Select } from '@/components/shared/Select';
import { PageLoader } from '@/components/shared/Loader';
import { EmptyState } from '@/components/shared/EmptyState';
import { formatDate } from '@/utils/formatDate';
import type { Transaction } from '@/api/types';

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'prospect', label: 'Prospect' },
  { value: 'listing_active', label: 'Listing Active' },
  { value: 'under_agreement', label: 'Under Agreement' },
  { value: 'closed', label: 'Closed' },
  { value: 'cancelled', label: 'Cancelled' },
];

// Property type filter - not yet implemented
// const propertyTypeOptions = [
//   { value: '', label: 'All Types' },
//   { value: 'SFH', label: 'Single Family Home' },
//   { value: 'Condo', label: 'Condo' },
//   { value: 'Multifamily', label: 'Multifamily' },
//   { value: 'Land', label: 'Land' },
//   { value: 'Commercial', label: 'Commercial' },
// ];

export const TransactionsList = () => {
  const navigate = useNavigate();
  const [statusFilter, setStatusFilter] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  const { data, isLoading } = useGetTransactions({
    status: statusFilter || undefined,
    per_page: 50,
  });

  const transactions = data?.data || [];

  // Filter by search query - memoized for performance
  const filteredTransactions = useMemo(() => {
    if (!searchQuery) return transactions;

    const query = searchQuery.toLowerCase();
    return transactions.filter((t) =>
      t.property_address?.toLowerCase().includes(query) ||
      t.property_city?.toLowerCase().includes(query) ||
      t.property_state?.toLowerCase().includes(query) ||
      t.property_zip?.toLowerCase().includes(query)
    );
  }, [transactions, searchQuery]);

  const getStatusBadge = (status: Transaction['status']) => {
    const variants: Record<Transaction['status'], 'default' | 'info' | 'warning' | 'success' | 'danger'> = {
      prospect: 'default',
      listing_active: 'info',
      under_agreement: 'warning',
      closed: 'success',
      cancelled: 'danger',
    };
    return <Badge variant={variants[status]}>{status.replace('_', ' ')}</Badge>;
  };

  if (isLoading) {
    return <PageLoader />;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Transactions</h1>
          <p className="text-sm text-gray-500 mt-1">
            Manage all your real estate transactions
          </p>
        </div>
        <Link to="/transactions/new">
          <Button variant="primary">
            <Plus className="h-4 w-4 mr-2" />
            New Transaction
          </Button>
        </Link>
      </div>

      {/* Filters */}
      <Card className="p-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
            <input
              type="text"
              placeholder="Search by address or city..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"
            />
          </div>
          <Select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            options={statusOptions}
          />
        </div>
      </Card>

      {/* Transactions list */}
      {filteredTransactions.length === 0 ? (
        <Card>
          <EmptyState
            title="No transactions found"
            description="Get started by creating your first transaction"
            action={{
              label: 'New Transaction',
              onClick: () => navigate('/transactions/new'),
            }}
          />
        </Card>
      ) : (
        <div className="grid grid-cols-1 gap-4">
          {filteredTransactions.map((transaction) => (
            <div
              key={transaction.transaction_id}
              className="cursor-pointer"
              onClick={() => navigate(`/transactions/${transaction.transaction_id}`)}
            >
              <Card className="p-6 hover:shadow-md transition-shadow">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-start gap-3">
                    <div className="flex-1">
                      <h3 className="text-lg font-semibold text-gray-900">
                        {transaction.property_address}
                      </h3>
                      <p className="text-sm text-gray-500">
                        {transaction.property_city}, {transaction.property_state} {transaction.property_zip}
                      </p>
                    </div>
                    {getStatusBadge(transaction.status)}
                  </div>

                  <div className="mt-4 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div>
                      <p className="text-xs text-gray-500">Property Type</p>
                      <p className="text-sm font-medium text-gray-900 mt-1">
                        {transaction.property_type}
                      </p>
                    </div>
                    <div>
                      <p className="text-xs text-gray-500">Offer Accepted</p>
                      <p className="text-sm font-medium text-gray-900 mt-1">
                        {formatDate(transaction.offer_accepted_date) || <span className="text-gray-400">—</span>}
                      </p>
                    </div>
                    <div>
                      <p className="text-xs text-gray-500">P&S Date</p>
                      <p className="text-sm font-medium text-gray-900 mt-1">
                        {formatDate(transaction.ps_agreement_date) || <span className="text-gray-400">—</span>}
                      </p>
                    </div>
                    <div>
                      <p className="text-xs text-gray-500">Loan Commitment</p>
                      <p className="text-sm font-medium text-gray-900 mt-1">
                        {formatDate(transaction.loan_commitment_date) || <span className="text-gray-400">—</span>}
                      </p>
                    </div>
                    <div>
                      <p className="text-xs text-gray-500">Closing Date</p>
                      <p className="text-sm font-medium text-gray-900 mt-1">
                        {formatDate(transaction.closing_date) || <span className="text-gray-400">—</span>}
                      </p>
                    </div>
                    <div>
                      <p className="text-xs text-gray-500">Tasks</p>
                      <p className="text-sm font-medium text-gray-900 mt-1">
                        {transaction.task_summary?.completed || 0} / {transaction.task_summary?.total || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              </Card>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};
