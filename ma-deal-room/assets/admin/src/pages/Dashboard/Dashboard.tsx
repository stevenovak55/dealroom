import { Link } from 'react-router-dom';
import { useGetTransactions } from '@/api/queries/useTransactions';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { Button } from '@/components/shared/Button';
import { Badge } from '@/components/shared/Badge';
import { PageLoader } from '@/components/shared/Loader';
import { formatDate, formatCurrency } from '@/utils/formatDate';
import { Plus, Home, TrendingUp, CheckCircle, Clock } from 'lucide-react';
import { useIsMobile } from '@/hooks/useMediaQuery';
import { cn } from '@/utils/cn';

/**
 * Dashboard Component (Mobile-First Redesign)
 *
 * Responsive dashboard with mobile-first design:
 * - Mobile (<768px): Vertical layout with card-based transaction list
 * - Desktop (>=768px): Grid layout with table view
 *
 * Features:
 * - Statistics cards (4 cards)
 * - Recent transactions list
 * - Touch-friendly actions
 * - Responsive grid layout
 */

const Dashboard = () => {
  const { data: transactionsData, isLoading } = useGetTransactions({ page: 1, per_page: 10 });
  const isMobile = useIsMobile();

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
        return <Badge variant="success" size="md">Closed</Badge>;
      case 'under_agreement':
        return <Badge variant="info" size="md">Under Agreement</Badge>;
      case 'listing_active':
        return <Badge variant="warning" size="md">Active Listing</Badge>;
      case 'prospect':
        return <Badge variant="default" size="md">Prospect</Badge>;
      case 'cancelled':
        return <Badge variant="danger" size="md">Cancelled</Badge>;
      default:
        return <Badge variant="default" size="md">{status}</Badge>;
    }
  };

  return (
    <div className="space-y-5 md:space-y-6">
      {/* Header - responsive layout */}
      <div className={cn(
        'flex flex-col space-y-4',
        'md:flex-row md:items-center md:justify-between md:space-y-0'
      )}>
        <div>
          <h1 className="text-2xl md:text-3xl font-bold text-gray-900">Dashboard</h1>
          <p className="text-sm md:text-base text-gray-500 mt-1">Welcome to MA Deal Room</p>
        </div>
        <Link to="/transactions/new" className="w-full md:w-auto">
          <Button size="lg" className="w-full md:w-auto">
            <Plus className="h-5 w-5 mr-2" />
            New Transaction
          </Button>
        </Link>
      </div>

      {/* Statistics Cards - responsive grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
        <Card>
          <CardContent className={cn(
            'pt-5 md:pt-6',
            'pb-5 md:pb-6'
          )}>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm md:text-base font-medium text-gray-600">Total Transactions</p>
                <p className="text-2xl md:text-3xl font-bold text-gray-900 mt-2">{totalTransactions}</p>
              </div>
              <div className={cn(
                'h-12 w-12 md:h-14 md:w-14',
                'bg-blue-100 rounded-lg flex items-center justify-center'
              )}>
                <Home className="h-6 w-6 md:h-7 md:w-7 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className={cn(
            'pt-5 md:pt-6',
            'pb-5 md:pb-6'
          )}>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm md:text-base font-medium text-gray-600">Active</p>
                <p className="text-2xl md:text-3xl font-bold text-gray-900 mt-2">{activeTransactions}</p>
              </div>
              <div className={cn(
                'h-12 w-12 md:h-14 md:w-14',
                'bg-green-100 rounded-lg flex items-center justify-center'
              )}>
                <TrendingUp className="h-6 w-6 md:h-7 md:w-7 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className={cn(
            'pt-5 md:pt-6',
            'pb-5 md:pb-6'
          )}>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm md:text-base font-medium text-gray-600">Closed</p>
                <p className="text-2xl md:text-3xl font-bold text-gray-900 mt-2">{closedTransactions}</p>
              </div>
              <div className={cn(
                'h-12 w-12 md:h-14 md:w-14',
                'bg-purple-100 rounded-lg flex items-center justify-center'
              )}>
                <CheckCircle className="h-6 w-6 md:h-7 md:w-7 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className={cn(
            'pt-5 md:pt-6',
            'pb-5 md:pb-6'
          )}>
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm md:text-base font-medium text-gray-600">Tasks Progress</p>
                <p className="text-2xl md:text-3xl font-bold text-gray-900 mt-2">
                  {completedTasks}/{totalTasks}
                </p>
              </div>
              <div className={cn(
                'h-12 w-12 md:h-14 md:w-14',
                'bg-orange-100 rounded-lg flex items-center justify-center'
              )}>
                <Clock className="h-6 w-6 md:h-7 md:w-7 text-orange-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Transactions - responsive: cards on mobile, table on desktop */}
      <Card>
        <CardHeader>
          <div className={cn(
            'flex flex-col space-y-3',
            'md:flex-row md:items-center md:justify-between md:space-y-0'
          )}>
            <CardTitle className="text-lg md:text-xl">Recent Transactions</CardTitle>
            <Link to="/transactions" className="w-full md:w-auto">
              <Button variant="ghost" size={isMobile ? 'md' : 'sm'} className="w-full md:w-auto">
                View All
              </Button>
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          {transactions.length === 0 ? (
            <div className="text-center py-12">
              <p className="text-sm md:text-base text-gray-500 mb-4">No transactions yet</p>
              <Link to="/transactions/new">
                <Button size="lg">
                  <Plus className="h-5 w-5 mr-2" />
                  Create Your First Transaction
                </Button>
              </Link>
            </div>
          ) : isMobile ? (
            // Mobile: Card view
            <div className="space-y-3">
              {transactions.map((transaction) => (
                <Card key={transaction.transaction_id} interactive>
                  <CardContent className="p-4">
                    <div className="space-y-3">
                      {/* Property address */}
                      <div>
                        <div className="font-medium text-gray-900">
                          {transaction.property_address}
                        </div>
                        <div className="text-sm text-gray-500">
                          {transaction.property_city}, {transaction.property_state}
                        </div>
                      </div>

                      {/* Status and Price row */}
                      <div className="flex items-center justify-between">
                        {getStatusBadge(transaction.status)}
                        <span className="text-lg font-semibold text-gray-900">
                          {transaction.sale_price ? formatCurrency(transaction.sale_price) : '-'}
                        </span>
                      </div>

                      {/* Details grid */}
                      <div className="grid grid-cols-2 gap-3 text-sm">
                        <div>
                          <span className="text-gray-500">Closing:</span>
                          <div className="font-medium text-gray-900">
                            {transaction.closing_date ? formatDate(transaction.closing_date) : '-'}
                          </div>
                        </div>
                        <div>
                          <span className="text-gray-500">Tasks:</span>
                          <div className="font-medium text-gray-900">
                            {transaction.task_summary?.completed || 0}/{transaction.task_summary?.total || 0}
                          </div>
                        </div>
                      </div>

                      {/* View button */}
                      <Link to={`/transactions/${transaction.transaction_id}`} className="block">
                        <Button variant="ghost" size="sm" className="w-full">
                          View Details
                        </Button>
                      </Link>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          ) : (
            // Desktop: Table view
            <div className="overflow-x-auto">
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
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default Dashboard;