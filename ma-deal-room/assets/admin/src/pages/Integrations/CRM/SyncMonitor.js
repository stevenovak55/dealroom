import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
/**
 * Sync Monitor Component
 *
 * Display sync status, statistics, and provide manual sync triggers.
 */
import { useState, useEffect } from 'react';
import { Box, Button, Card, CardContent, Typography, Alert, CircularProgress, Grid, LinearProgress, Stack, Chip, FormControl, InputLabel, Select, MenuItem, Paper } from '@mui/material';
import { Sync as SyncIcon, CheckCircle as CheckCircleIcon, Error as ErrorIcon, Refresh as RefreshIcon } from '@mui/icons-material';
import { crmService } from '../../../api/crmService';
export default function SyncMonitor() {
    const [configurations, setConfigurations] = useState([]);
    const [selectedProvider, setSelectedProvider] = useState('');
    const [syncStatus, setSyncStatus] = useState(null);
    const [syncing, setSyncing] = useState(false);
    const [syncResults, setSyncResults] = useState(null);
    const [loading, setLoading] = useState(true);
    useEffect(() => {
        loadConfigurations();
    }, []);
    useEffect(() => {
        if (selectedProvider) {
            loadSyncStatus(selectedProvider);
        }
    }, [selectedProvider]);
    const loadConfigurations = async () => {
        try {
            setLoading(true);
            const data = await crmService.getConfigurations();
            setConfigurations(data.configurations);
            // Auto-select first connected provider
            if (data.configurations.length > 0) {
                setSelectedProvider(data.configurations[0].provider_type);
            }
        }
        catch (error) {
            console.error('Failed to load CRM configurations:', error);
        }
        finally {
            setLoading(false);
        }
    };
    const loadSyncStatus = async (provider) => {
        try {
            const status = await crmService.getSyncStatus(provider);
            setSyncStatus(status);
        }
        catch (error) {
            console.error('Failed to load sync status:', error);
        }
    };
    const handleManualSync = async (type) => {
        if (!selectedProvider)
            return;
        try {
            setSyncing(true);
            setSyncResults(null);
            const results = { success: true };
            if (type === 'contacts' || type === 'both') {
                results.contacts = await crmService.syncContacts(selectedProvider);
            }
            if (type === 'deals' || type === 'both') {
                results.deals = await crmService.syncDeals(selectedProvider);
            }
            setSyncResults(results);
            loadSyncStatus(selectedProvider);
        }
        catch (error) {
            console.error('Manual sync failed:', error);
            setSyncResults({ success: false, error: 'Sync failed' });
        }
        finally {
            setSyncing(false);
        }
    };
    if (loading) {
        return (_jsx(Box, { display: "flex", justifyContent: "center", alignItems: "center", minHeight: 400, children: _jsx(CircularProgress, {}) }));
    }
    if (configurations.length === 0) {
        return (_jsx(Alert, { severity: "info", children: "No CRM connections found. Please connect a CRM in the Settings tab first." }));
    }
    return (_jsxs(Box, { children: [_jsxs(Box, { display: "flex", alignItems: "center", justifyContent: "space-between", mb: 3, children: [_jsxs(Box, { children: [_jsx(Typography, { variant: "h5", gutterBottom: true, children: "Sync Monitor" }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "Monitor sync status and trigger manual synchronization." })] }), _jsx(Button, { variant: "outlined", startIcon: _jsx(RefreshIcon, {}), onClick: () => selectedProvider && loadSyncStatus(selectedProvider), children: "Refresh" })] }), _jsx(Card, { sx: { mb: 3 }, children: _jsx(CardContent, { children: _jsxs(FormControl, { fullWidth: true, children: [_jsx(InputLabel, { children: "CRM Provider" }), _jsx(Select, { value: selectedProvider, label: "CRM Provider", onChange: (e) => setSelectedProvider(e.target.value), children: configurations.map(config => (_jsx(MenuItem, { value: config.provider_type, children: config.provider_type === 'salesforce' ? 'Salesforce' : 'HubSpot' }, config.id))) })] }) }) }), selectedProvider && syncStatus && (_jsxs(_Fragment, { children: [_jsxs(Grid, { container: true, spacing: 3, sx: { mb: 3 }, children: [_jsx(Grid, { size: { xs: 12, md: 4 }, children: _jsx(Card, { children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", color: "primary", children: syncStatus.contacts.total }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "Total Contacts" })] }) }) }), _jsx(Grid, { size: { xs: 12, md: 4 }, children: _jsx(Card, { children: _jsxs(CardContent, { children: [_jsxs(Box, { display: "flex", alignItems: "center", gap: 1, children: [_jsx(CheckCircleIcon, { color: "success" }), _jsx(Typography, { variant: "h6", color: "success.main", children: syncStatus.contacts.synced })] }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "Synced Successfully" })] }) }) }), _jsx(Grid, { size: { xs: 12, md: 4 }, children: _jsx(Card, { children: _jsxs(CardContent, { children: [_jsxs(Box, { display: "flex", alignItems: "center", gap: 1, children: [_jsx(ErrorIcon, { color: "error" }), _jsx(Typography, { variant: "h6", color: "error.main", children: syncStatus.contacts.errors })] }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "Sync Errors" })] }) }) })] }), _jsx(Card, { sx: { mb: 3 }, children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "Last Sync" }), _jsx(Typography, { variant: "body1", children: syncStatus.last_sync
                                        ? new Date(syncStatus.last_sync).toLocaleString()
                                        : 'Never synced' }), _jsx(Chip, { label: syncStatus.sync_enabled ? 'Sync Enabled' : 'Sync Disabled', color: syncStatus.sync_enabled ? 'success' : 'default', size: "small", sx: { mt: 1 } })] }) }), _jsx(Card, { sx: { mb: 3 }, children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "Manual Sync" }), _jsx(Typography, { variant: "body2", color: "text.secondary", paragraph: true, children: "Trigger a manual synchronization now. This will sync data in both directions." }), _jsxs(Stack, { direction: "row", spacing: 2, children: [_jsx(Button, { variant: "contained", startIcon: syncing ? _jsx(CircularProgress, { size: 20 }) : _jsx(SyncIcon, {}), onClick: () => handleManualSync('contacts'), disabled: syncing, children: "Sync Contacts" }), _jsx(Button, { variant: "contained", startIcon: syncing ? _jsx(CircularProgress, { size: 20 }) : _jsx(SyncIcon, {}), onClick: () => handleManualSync('deals'), disabled: syncing, children: "Sync Deals" }), _jsx(Button, { variant: "contained", color: "primary", startIcon: syncing ? _jsx(CircularProgress, { size: 20 }) : _jsx(SyncIcon, {}), onClick: () => handleManualSync('both'), disabled: syncing, children: "Sync All" })] })] }) }), syncing && (_jsx(Card, { sx: { mb: 3 }, children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "Syncing..." }), _jsx(LinearProgress, {})] }) })), syncResults && (_jsx(Card, { sx: { mb: 3 }, children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "Sync Results" }), syncResults.success ? (_jsx(Alert, { severity: "success", sx: { mb: 2 }, children: "Sync completed successfully!" })) : (_jsxs(Alert, { severity: "error", sx: { mb: 2 }, children: ["Sync failed: ", syncResults.error] })), syncResults.contacts && (_jsxs(Box, { sx: { mb: 2 }, children: [_jsx(Typography, { variant: "subtitle1", gutterBottom: true, children: "Contacts Sync" }), _jsx(Paper, { variant: "outlined", sx: { p: 2 }, children: _jsxs(Grid, { container: true, spacing: 2, children: [syncResults.contacts.from_crm && (_jsxs(Grid, { size: { xs: 12, md: 6 }, children: [_jsx(Typography, { variant: "body2", color: "text.secondary", children: "From CRM:" }), _jsxs(Typography, { children: ["Created: ", syncResults.contacts.from_crm.created, " | Updated: ", syncResults.contacts.from_crm.updated, " | Errors: ", syncResults.contacts.from_crm.errors?.length || 0] })] })), syncResults.contacts.to_crm && (_jsxs(Grid, { size: { xs: 12, md: 6 }, children: [_jsx(Typography, { variant: "body2", color: "text.secondary", children: "To CRM:" }), _jsxs(Typography, { children: ["Created: ", syncResults.contacts.to_crm.created, " | Updated: ", syncResults.contacts.to_crm.updated, " | Errors: ", syncResults.contacts.to_crm.errors?.length || 0] })] }))] }) })] })), syncResults.deals && (_jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle1", gutterBottom: true, children: "Deals Sync" }), _jsx(Paper, { variant: "outlined", sx: { p: 2 }, children: _jsxs(Grid, { container: true, spacing: 2, children: [syncResults.deals.from_crm && (_jsxs(Grid, { size: { xs: 12, md: 6 }, children: [_jsx(Typography, { variant: "body2", color: "text.secondary", children: "From CRM:" }), _jsxs(Typography, { children: ["Created: ", syncResults.deals.from_crm.created, " | Updated: ", syncResults.deals.from_crm.updated, " | Errors: ", syncResults.deals.from_crm.errors?.length || 0] })] })), syncResults.deals.to_crm && (_jsxs(Grid, { size: { xs: 12, md: 6 }, children: [_jsx(Typography, { variant: "body2", color: "text.secondary", children: "To CRM:" }), _jsxs(Typography, { children: ["Created: ", syncResults.deals.to_crm.created, " | Updated: ", syncResults.deals.to_crm.updated, " | Errors: ", syncResults.deals.to_crm.errors?.length || 0] })] }))] }) })] }))] }) }))] }))] }));
}
