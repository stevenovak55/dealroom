import { Link } from 'react-router-dom';
import { useGetTransactions } from '@/api/queries/useTransactions';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { PageLoader } from '@/components/shared/Loader';
import { formatDate, formatCurrency } from '@/utils/formatDate';
import { Plus, Home, TrendingUp, CheckCircle, Clock } from 'lucide-react';

const Dashboard = () => {
  const { data: transactionsData, isLoading } = useGetTransactions({ page: 1, per_page: 10 });

  if (isLoading) {
    return <PageLoader />;
  }

  const transactions = transactionsData?.data || [];
  const totalTransactions = transactionsData?.pagination.total || 0;

  // Calculate statistics
  const activeTransactions = transactions.filter(
    (t) => t.status === 'under_agreement' || t.status === 'listing_active'
  ).length;
  const closedTransactions = transactions.filter((t) => t.status === 'closed').length;
  const totalTasks = transactions.reduce((sum, t) => sum + (t.task_summary?.total || 0), 0);
  const completedTasks = transactions.reduce((sum, t) => sum + (t.task_summary?.completed || 0), 0);

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'closed':
        return <Badge variant="success">Closed</Badge>;
      case 'under_agreement':
        return <Badge variant="info">Under Agreement</Badge>;
      case 'listing_active':
        return <Badge variant="warning">Active Listing</Badge>;
      case 'prospect':
        return <Badge variant="default">Prospect</Badge>;
      case 'cancelled':
        return <Badge variant="danger">Cancelled</Badge>;
      default:
        return <Badge variant="default">{status}</Badge>;
    }
  };

  return (
    <div className="space-y-4 md:space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900">Dashboard</h1>
          <p className="text-gray-500 mt-1 text-sm md:text-base">Welcome to MA Deal Room</p>
        </div>
        <Link to="/transactions/new" className="sm:flex-shrink-0">
          <Button className="w-full sm:w-auto">
            <Plus className="h-4 w-4 mr-2" />
            New Transaction
          </Button>
        </Link>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
        <Card className="card-hover-effect">
          <CardContent className="pt-4 md:pt-6">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
              <div className="flex-1">
                <p className="text-xs md:text-sm font-medium text-gray-600">Total Transactions</p>
                <p className="text-xl md:text-2xl font-bold text-gray-900 mt-1 md:mt-2">{totalTransactions}</p>
              </div>
              <div className="h-10 w-10 md:h-12 md:w-12 bg-blue-100 rounded-lg flex items-center justify-center self-end md:self-auto">
                <Home className="h-5 w-5 md:h-6 md:w-6 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="card-hover-effect">
          <CardContent className="pt-4 md:pt-6">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
              <div className="flex-1">
                <p className="text-xs md:text-sm font-medium text-gray-600">Active</p>
                <p className="text-xl md:text-2xl font-bold text-gray-900 mt-1 md:mt-2">{activeTransactions}</p>
              </div>
              <div className="h-10 w-10 md:h-12 md:w-12 bg-green-100 rounded-lg flex items-center justify-center self-end md:self-auto">
                <TrendingUp className="h-5 w-5 md:h-6 md:w-6 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="card-hover-effect">
          <CardContent className="pt-4 md:pt-6">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
              <div className="flex-1">
                <p className="text-xs md:text-sm font-medium text-gray-600">Closed</p>
                <p className="text-xl md:text-2xl font-bold text-gray-900 mt-1 md:mt-2">{closedTransactions}</p>
              </div>
              <div className="h-10 w-10 md:h-12 md:w-12 bg-purple-100 rounded-lg flex items-center justify-center self-end md:self-auto">
                <CheckCircle className="h-5 w-5 md:h-6 md:w-6 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="card-hover-effect">
          <CardContent className="pt-4 md:pt-6">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
              <div className="flex-1">
                <p className="text-xs md:text-sm font-medium text-gray-600">Tasks Progress</p>
                <p className="text-xl md:text-2xl font-bold text-gray-900 mt-1 md:mt-2">
                  {completedTasks}/{totalTasks}
                </p>
              </div>
              <div className="h-10 w-10 md:h-12 md:w-12 bg-orange-100 rounded-lg flex items-center justify-center self-end md:self-auto">
                <Clock className="h-5 w-5 md:h-6 md:w-6 text-orange-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Transactions */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Recent Transactions</CardTitle>
            <Link to="/transactions">
              <Button variant="ghost" size="sm">
                View All
              </Button>
            </Link>
          </div>
        </CardHeader>
        <CardContent className="p-0">
          {transactions.length === 0 ? (
            <div className="text-center py-12 px-4">
              <p className="text-gray-500 mb-4">No transactions yet</p>
              <Link to="/transactions/new">
                <Button className="w-full sm:w-auto">
                  <Plus className="h-4 w-4 mr-2" />
                  Create Your First Transaction
                </Button>
              </Link>
            </div>
          ) : (
            <>
              {/* Desktop Table View */}
              <div className="hidden md:block overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Property
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Price
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Closing Date
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tasks
                      </th>
                      <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {transactions.map((transaction) => (
                      <tr key={transaction.transaction_id} className="hover:bg-gray-50">
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm font-medium text-gray-900">
                            {transaction.property_address}
                          </div>
                          <div className="text-sm text-gray-500">
                            {transaction.property_city}, {transaction.property_state}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          {getStatusBadge(transaction.status)}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {transaction.sale_price
                            ? formatCurrency(transaction.sale_price)
                            : '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {transaction.closing_date
                            ? formatDate(transaction.closing_date)
                            : '-'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-gray-900">
                            {transaction.task_summary?.completed || 0}/
                            {transaction.task_summary?.total || 0}
                          </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                          <Link to={`/transactions/${transaction.transaction_id}`}>
                            <Button variant="ghost" size="sm">
                              View
                            </Button>
                          </Link>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>

              {/* Mobile Card View */}
              <div className="md:hidden divide-y divide-gray-200">
                {transactions.map((transaction) => (
                  <Link
                    key={transaction.transaction_id}
                    to={`/transactions/${transaction.transaction_id}`}
                    className="block p-4 hover:bg-gray-50 active:bg-gray-100 transition-colors"
                  >
                    <div className="space-y-2">
                      <div className="flex items-start justify-between gap-2">
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-semibold text-gray-900 truncate">
                            {transaction.property_address}
                          </p>
                          <p className="text-xs text-gray-500 mt-0.5">
                            {transaction.property_city}, {transaction.property_state}
                          </p>
                        </div>
                        {getStatusBadge(transaction.status)}
                      </div>
                      <div className="grid grid-cols-2 gap-3 text-xs">
                        <div>
                          <span className="text-gray-500">Price:</span>
                          <span className="ml-1 font-medium text-gray-900">
                            {transaction.sale_price
                              ? formatCurrency(transaction.sale_price)
                              : '-'}
                          </span>
                        </div>
                        <div>
                          <span className="text-gray-500">Tasks:</span>
                          <span className="ml-1 font-medium text-gray-900">
                            {transaction.task_summary?.completed || 0}/
                            {transaction.task_summary?.total || 0}
                          </span>
                        </div>
                        {transaction.closing_date && (
                          <div className="col-span-2">
                            <span className="text-gray-500">Closing:</span>
                            <span className="ml-1 font-medium text-gray-900">
                              {formatDate(transaction.closing_date)}
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  </Link>
                ))}
              </div>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default Dashboard;