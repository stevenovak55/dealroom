import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { Search, Database, RefreshCw, Settings as SettingsIcon, CheckCircle, AlertCircle } from 'lucide-react';
import { mlsService } from '@/api/mlsService';
import { MLSConfigModal } from './MLSConfigModal';
export const MLSDashboard = () => {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [stats, setStats] = useState(null);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
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
        }
        catch (err) {
            console.error('Failed to load stats:', err);
        }
    };
    const loadConfigCount = async () => {
        try {
            const response = await mlsService.listConfigs();
            if (response.data) {
                const configs = response.data;
                const activeCount = configs.filter((c) => c.is_active).length;
                setActiveConfigCount(activeCount);
            }
        }
        catch (err) {
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
            }
            else {
                setError(response.message || 'Search failed');
            }
        }
        catch (err) {
            console.error('Search failed:', err);
            setError(err.message || 'Failed to search MLS. Please check your configuration.');
        }
        finally {
            setIsSearching(false);
        }
    };
    const handleImport = async (mlsNumber) => {
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
            }
            else {
                setError(response.message || 'Import failed');
            }
        }
        catch (err) {
            console.error('Import failed:', err);
            setError(err.message || 'Failed to import property');
        }
    };
    return (_jsxs("div", { className: "container mx-auto px-4 py-8", children: [_jsxs("div", { className: "flex items-center justify-between mb-8", children: [_jsxs("div", { children: [_jsx("h1", { className: "text-3xl font-bold text-gray-900", children: "MLS Integration" }), _jsx("p", { className: "mt-2 text-gray-600", children: "Search and import properties from MLS" })] }), _jsxs("button", { className: "flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700", onClick: () => setShowConfigModal(true), children: [_jsx(SettingsIcon, { className: "h-5 w-5" }), "Configure MLS"] })] }), error && (_jsxs("div", { className: "mb-6 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3", children: [_jsx(AlertCircle, { className: "h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" }), _jsx("p", { className: "text-red-800", children: error })] })), success && (_jsxs("div", { className: "mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3", children: [_jsx(CheckCircle, { className: "h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" }), _jsx("p", { className: "text-green-800", children: success })] })), _jsxs("div", { className: "bg-white rounded-lg shadow-sm p-6 mb-8", children: [_jsxs("h2", { className: "text-xl font-semibold mb-4 flex items-center gap-2", children: [_jsx(Search, { className: "h-5 w-5" }), "Search MLS Listings"] }), _jsxs("div", { className: "flex gap-4", children: [_jsx("input", { type: "text", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), placeholder: "Enter MLS Number (e.g., 12345678)", className: "flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent", onKeyPress: (e) => e.key === 'Enter' && handleSearch() }), _jsx("button", { onClick: handleSearch, disabled: isSearching, className: "px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2", children: isSearching ? (_jsxs(_Fragment, { children: [_jsx(RefreshCw, { className: "h-5 w-5 animate-spin" }), "Searching..."] })) : (_jsxs(_Fragment, { children: [_jsx(Search, { className: "h-5 w-5" }), "Search"] })) })] })] }), _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-3 gap-6 mb-8", children: [_jsx("div", { className: "bg-white rounded-lg shadow-sm p-6", children: _jsxs("div", { className: "flex items-center gap-4", children: [_jsx("div", { className: "p-3 bg-blue-100 rounded-lg", children: _jsx(Database, { className: "h-6 w-6 text-blue-600" }) }), _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600", children: "Total Imported" }), _jsx("p", { className: "text-2xl font-bold text-gray-900", children: stats?.total_imported ?? 0 })] })] }) }), _jsx("div", { className: "bg-white rounded-lg shadow-sm p-6", children: _jsxs("div", { className: "flex items-center gap-4", children: [_jsx("div", { className: "p-3 bg-green-100 rounded-lg", children: _jsx(RefreshCw, { className: "h-6 w-6 text-green-600" }) }), _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600", children: "Last Import" }), _jsx("p", { className: "text-lg font-bold text-gray-900", children: stats?.last_import
                                                ? new Date(stats.last_import).toLocaleDateString()
                                                : 'Never' })] })] }) }), _jsx("div", { className: "bg-white rounded-lg shadow-sm p-6", children: _jsxs("div", { className: "flex items-center gap-4", children: [_jsx("div", { className: "p-3 bg-purple-100 rounded-lg", children: _jsx(SettingsIcon, { className: "h-6 w-6 text-purple-600" }) }), _jsxs("div", { children: [_jsx("p", { className: "text-sm text-gray-600", children: "Active Configs" }), _jsx("p", { className: "text-2xl font-bold text-gray-900", children: activeConfigCount })] })] }) })] }), searchResults.length > 0 && (_jsxs("div", { className: "bg-white rounded-lg shadow-sm p-6", children: [_jsx("h2", { className: "text-xl font-semibold mb-4", children: "Search Results" }), _jsx("div", { className: "space-y-4", children: searchResults.map((result, index) => (_jsx("div", { className: "border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors", children: _jsxs("div", { className: "flex justify-between items-start", children: [_jsxs("div", { className: "flex-1", children: [_jsx("h3", { className: "font-semibold text-lg", children: result.address }), _jsxs("p", { className: "text-gray-600", children: ["MLS #: ", result.mls_number] }), result.price && (_jsxs("p", { className: "text-lg font-semibold text-green-600 mt-1", children: ["$", result.price.toLocaleString()] })), _jsxs("div", { className: "flex gap-4 mt-2 text-sm text-gray-600", children: [result.bedrooms && _jsxs("span", { children: [result.bedrooms, " beds"] }), result.bathrooms && _jsxs("span", { children: [result.bathrooms, " baths"] }), result.square_feet && _jsxs("span", { children: [result.square_feet.toLocaleString(), " sq ft"] })] }), result.is_imported && (_jsxs("p", { className: "text-sm text-green-600 mt-2 flex items-center gap-1", children: [_jsx(CheckCircle, { className: "h-4 w-4" }), "Already imported (Transaction #", result.existing_transaction_id, ")"] }))] }), _jsx("button", { onClick: () => handleImport(result.mls_number), disabled: result.is_imported, className: "px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed", children: result.is_imported ? 'Imported' : 'Import' })] }) }, index))) })] })), searchResults.length === 0 && !isSearching && !error && (_jsxs("div", { className: "bg-white rounded-lg shadow-sm p-12 text-center", children: [_jsx(Database, { className: "h-16 w-16 text-gray-400 mx-auto mb-4" }), _jsx("h3", { className: "text-lg font-semibold text-gray-900 mb-2", children: "Search for MLS Properties" }), _jsx("p", { className: "text-gray-600 mb-6", children: "Enter an MLS number above to search and import properties" })] })), _jsx(MLSConfigModal, { isOpen: showConfigModal, onClose: () => setShowConfigModal(false), onConfigSaved: handleConfigSaved })] }));
};
