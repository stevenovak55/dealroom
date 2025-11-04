import { useState, useEffect } from 'react';
import { Search, Database, RefreshCw, Settings as SettingsIcon, CheckCircle, AlertCircle } from 'lucide-react';
import { mlsService, MLSListing, MLSStats } from '@/api/mlsService';
import { MLSConfigModal } from './MLSConfigModal';

export const MLSDashboard = () => {
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<MLSListing[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [stats, setStats] = useState<MLSStats | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [showConfigModal, setShowConfigModal] = useState(false);
  const [activeConfigCount, setActiveConfigCount] = useState(0);

  // Load stats on mount
  useEffect(() => {
    loadStats();
    loadConfigCount();
  }, []);

  const loadStats = async () => {
    try {
      const response = await mlsService.getStats();
      setStats(response.data);
    } catch (err: any) {
      console.error('Failed to load stats:', err);
    }
  };

  const loadConfigCount = async () => {
    try {
      const response = await mlsService.listConfigs();
      if (response.data) {
        const configs = response.data as any[];
        const activeCount = configs.filter((c: any) => c.is_active).length;
        setActiveConfigCount(activeCount);
      }
    } catch (err: any) {
      console.error('Failed to load config count:', err);
    }
  };

  const handleConfigSaved = () => {
    loadStats();
    loadConfigCount();
  };

  const handleSearch = async () => {
    if (!searchQuery.trim()) {
      setError('Please enter an MLS number');
      return;
    }

    setIsSearching(true);
    setError(null);
    setSuccess(null);

    try {
      const response = await mlsService.search({
        mls_number: searchQuery,
      });

      if (response.success) {
        setSearchResults(response.data.listings || []);
        if (response.data.listings.length === 0) {
          setError('No properties found with that MLS number');
        }
      } else {
        setError(response.message || 'Search failed');
      }
    } catch (err: any) {
      console.error('Search failed:', err);
      setError(err.message || 'Failed to search MLS. Please check your configuration.');
    } finally {
      setIsSearching(false);
    }
  };

  const handleImport = async (mlsNumber: string) => {
    try {
      setError(null);
      setSuccess(null);

      const response = await mlsService.importProperty(mlsNumber, true);

      if (response.success) {
        setSuccess(`Property ${mlsNumber} imported successfully!`);
        // Reload stats
        await loadStats();
        // Refresh search results
        await handleSearch();
      } else {
        setError(response.message || 'Import failed');
      }
    } catch (err: any) {
      console.error('Import failed:', err);
      setError(err.message || 'Failed to import property');
    }
  };

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Header */}
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">MLS Integration</h1>
          <p className="mt-2 text-gray-600">
            Search and import properties from MLS
          </p>
        </div>
        <button
          className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
          onClick={() => setShowConfigModal(true)}
        >
          <SettingsIcon className="h-5 w-5" />
          Configure MLS
        </button>
      </div>

      {/* Alerts */}
      {error && (
        <div className="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
          <AlertCircle className="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
          <p className="text-red-800">{error}</p>
        </div>
      )}

      {success && (
        <div className="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3">
          <CheckCircle className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
          <p className="text-green-800">{success}</p>
        </div>
      )}

      {/* Search Section */}
      <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
        <h2 className="text-xl font-semibold mb-4 flex items-center gap-2">
          <Search className="h-5 w-5" />
          Search MLS Listings
        </h2>
        <div className="flex gap-4">
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Enter MLS Number (e.g., 12345678)"
            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
          />
          <button
            onClick={handleSearch}
            disabled={isSearching}
            className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            {isSearching ? (
              <>
                <RefreshCw className="h-5 w-5 animate-spin" />
                Searching...
              </>
            ) : (
              <>
                <Search className="h-5 w-5" />
                Search
              </>
            )}
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-blue-100 rounded-lg">
              <Database className="h-6 w-6 text-blue-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Total Imported</p>
              <p className="text-2xl font-bold text-gray-900">
                {stats?.total_imported ?? 0}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-green-100 rounded-lg">
              <RefreshCw className="h-6 w-6 text-green-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Last Import</p>
              <p className="text-lg font-bold text-gray-900">
                {stats?.last_import
                  ? new Date(stats.last_import).toLocaleDateString()
                  : 'Never'}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center gap-4">
            <div className="p-3 bg-purple-100 rounded-lg">
              <SettingsIcon className="h-6 w-6 text-purple-600" />
            </div>
            <div>
              <p className="text-sm text-gray-600">Active Configs</p>
              <p className="text-2xl font-bold text-gray-900">{activeConfigCount}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Search Results */}
      {searchResults.length > 0 && (
        <div className="bg-white rounded-lg shadow-sm p-6">
          <h2 className="text-xl font-semibold mb-4">Search Results</h2>
          <div className="space-y-4">
            {searchResults.map((result, index) => (
              <div
                key={index}
                className="border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors"
              >
                <div className="flex justify-between items-start">
                  <div className="flex-1">
                    <h3 className="font-semibold text-lg">{result.address}</h3>
                    <p className="text-gray-600">MLS #: {result.mls_number}</p>
                    {result.price && (
                      <p className="text-lg font-semibold text-green-600 mt-1">
                        ${result.price.toLocaleString()}
                      </p>
                    )}
                    <div className="flex gap-4 mt-2 text-sm text-gray-600">
                      {result.bedrooms && <span>{result.bedrooms} beds</span>}
                      {result.bathrooms && <span>{result.bathrooms} baths</span>}
                      {result.square_feet && <span>{result.square_feet.toLocaleString()} sq ft</span>}
                    </div>
                    {result.is_imported && (
                      <p className="text-sm text-green-600 mt-2 flex items-center gap-1">
                        <CheckCircle className="h-4 w-4" />
                        Already imported (Transaction #{result.existing_transaction_id})
                      </p>
                    )}
                  </div>
                  <button
                    onClick={() => handleImport(result.mls_number)}
                    disabled={result.is_imported}
                    className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    {result.is_imported ? 'Imported' : 'Import'}
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Empty State */}
      {searchResults.length === 0 && !isSearching && !error && (
        <div className="bg-white rounded-lg shadow-sm p-12 text-center">
          <Database className="h-16 w-16 text-gray-400 mx-auto mb-4" />
          <h3 className="text-lg font-semibold text-gray-900 mb-2">
            Search for MLS Properties
          </h3>
          <p className="text-gray-600 mb-6">
            Enter an MLS number above to search and import properties
          </p>
        </div>
      )}

      {/* Configuration Modal */}
      <MLSConfigModal
        isOpen={showConfigModal}
        onClose={() => setShowConfigModal(false)}
        onConfigSaved={handleConfigSaved}
      />
    </div>
  );
};
