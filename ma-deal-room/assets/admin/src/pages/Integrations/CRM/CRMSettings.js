import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
/**
 * CRM Settings Component
 *
 * Allows admins to connect Salesforce or HubSpot via OAuth 2.0.
 * Displays connection status and provides test connection functionality.
 */
import { useState, useEffect } from 'react';
import { Box, Button, Card, CardContent, Typography, Alert, CircularProgress, Chip, Stack, Dialog, DialogTitle, DialogContent, DialogActions, TextField } from '@mui/material';
import { CloudDone, CloudOff, Link as LinkIcon, Delete as DeleteIcon, Check as CheckIcon } from '@mui/icons-material';
import { crmService } from '../../../api/crmService';
export default function CRMSettings() {
    const [configurations, setConfigurations] = useState([]);
    const [supportedProviders, setSupportedProviders] = useState({});
    const [loading, setLoading] = useState(true);
    const [testing, setTesting] = useState(null);
    const [testResults, setTestResults] = useState({});
    const [apiKeyDialog, setApiKeyDialog] = useState(null);
    const [apiKey, setApiKey] = useState('');
    useEffect(() => {
        loadConfigurations();
    }, []);
    const loadConfigurations = async () => {
        try {
            setLoading(true);
            const data = await crmService.getConfigurations();
            setConfigurations(data.configurations);
            setSupportedProviders(data.supported_providers);
        }
        catch (error) {
            console.error('Failed to load CRM configurations:', error);
        }
        finally {
            setLoading(false);
        }
    };
    const handleConnect = (provider) => {
        // Check if provider supports API key
        if (provider === 'hubspot' && supportedProviders[provider]?.supports_api_key) {
            setApiKeyDialog(provider);
            return;
        }
        // OAuth flow
        const redirectUri = `${window.location.origin}/wp-admin/admin.php?page=ma-deal-integrations-crm&oauth=callback`;
        const authUrl = crmService.getOAuthUrl(provider, redirectUri);
        // Open OAuth window
        window.location.href = authUrl;
    };
    const handleApiKeyConnect = async () => {
        if (!apiKeyDialog)
            return;
        try {
            setLoading(true);
            await crmService.configureCRM({
                provider: apiKeyDialog,
                credentials: {
                    api_key: apiKey
                }
            });
            setApiKeyDialog(null);
            setApiKey('');
            loadConfigurations();
        }
        catch (error) {
            console.error('Failed to configure CRM with API key:', error);
        }
        finally {
            setLoading(false);
        }
    };
    const handleTestConnection = async (provider) => {
        try {
            setTesting(provider);
            const result = await crmService.testConnection(provider);
            setTestResults(prev => ({ ...prev, [provider]: result }));
        }
        catch (error) {
            setTestResults(prev => ({
                ...prev,
                [provider]: { success: false, message: 'Connection test failed' }
            }));
        }
        finally {
            setTesting(null);
        }
    };
    const handleDisconnect = async (configId) => {
        if (!confirm('Are you sure you want to disconnect this CRM?'))
            return;
        try {
            setLoading(true);
            await crmService.deleteConfiguration(configId);
            loadConfigurations();
        }
        catch (error) {
            console.error('Failed to disconnect CRM:', error);
        }
        finally {
            setLoading(false);
        }
    };
    const getProviderConfig = (provider) => {
        return configurations.find(c => c.provider_type === provider);
    };
    const isConnected = (provider) => {
        return !!getProviderConfig(provider);
    };
    if (loading) {
        return (_jsx(Box, { display: "flex", justifyContent: "center", alignItems: "center", minHeight: 400, children: _jsx(CircularProgress, {}) }));
    }
    return (_jsxs(Box, { children: [_jsx(Typography, { variant: "h5", gutterBottom: true, children: "CRM Connections" }), _jsx(Typography, { variant: "body2", color: "text.secondary", paragraph: true, children: "Connect your CRM to sync contacts, deals, and activities automatically." }), _jsxs(Stack, { spacing: 3, sx: { mt: 3 }, children: [_jsx(Card, { children: _jsxs(CardContent, { children: [_jsxs(Box, { display: "flex", alignItems: "center", justifyContent: "space-between", children: [_jsxs(Box, { display: "flex", alignItems: "center", gap: 2, children: [_jsx(Box, { component: "img", src: "https://www.salesforce.com/content/dam/sfdc-docs/www/logos/logo-salesforce.svg", alt: "Salesforce", sx: { height: 40 } }), _jsxs(Box, { children: [_jsx(Typography, { variant: "h6", children: "Salesforce" }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "Sync contacts as Contacts, deals as Opportunities" })] })] }), _jsx(Chip, { icon: isConnected('salesforce') ? _jsx(CloudDone, {}) : _jsx(CloudOff, {}), label: isConnected('salesforce') ? 'Connected' : 'Not Connected', color: isConnected('salesforce') ? 'success' : 'default' })] }), testResults.salesforce && (_jsxs(Alert, { severity: testResults.salesforce.success ? 'success' : 'error', sx: { mt: 2 }, onClose: () => setTestResults(prev => ({ ...prev, salesforce: null })), children: [testResults.salesforce.message, testResults.salesforce.details && (_jsx(Typography, { variant: "caption", display: "block", children: testResults.salesforce.details.display_name || testResults.salesforce.details.organization_id }))] })), _jsx(Stack, { direction: "row", spacing: 2, sx: { mt: 2 }, children: !isConnected('salesforce') ? (_jsx(Button, { variant: "contained", startIcon: _jsx(LinkIcon, {}), onClick: () => handleConnect('salesforce'), children: "Connect Salesforce" })) : (_jsxs(_Fragment, { children: [_jsx(Button, { variant: "outlined", startIcon: testing === 'salesforce' ? _jsx(CircularProgress, { size: 20 }) : _jsx(CheckIcon, {}), onClick: () => handleTestConnection('salesforce'), disabled: testing === 'salesforce', children: "Test Connection" }), _jsx(Button, { variant: "outlined", color: "error", startIcon: _jsx(DeleteIcon, {}), onClick: () => {
                                                    const config = getProviderConfig('salesforce');
                                                    if (config)
                                                        handleDisconnect(config.id);
                                                }, children: "Disconnect" })] })) }), isConnected('salesforce') && (_jsx(Box, { sx: { mt: 2 }, children: _jsxs(Typography, { variant: "caption", color: "text.secondary", children: ["Last synced: ", getProviderConfig('salesforce')?.last_sync_at || 'Never'] }) }))] }) }), _jsx(Card, { children: _jsxs(CardContent, { children: [_jsxs(Box, { display: "flex", alignItems: "center", justifyContent: "space-between", children: [_jsxs(Box, { display: "flex", alignItems: "center", gap: 2, children: [_jsx(Box, { component: "img", src: "https://www.hubspot.com/hubfs/HubSpot_Logos/HubSpot-Inversed-Favicon.png", alt: "HubSpot", sx: { height: 40 } }), _jsxs(Box, { children: [_jsx(Typography, { variant: "h6", children: "HubSpot" }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "Sync contacts as Contacts, deals as Deals" })] })] }), _jsx(Chip, { icon: isConnected('hubspot') ? _jsx(CloudDone, {}) : _jsx(CloudOff, {}), label: isConnected('hubspot') ? 'Connected' : 'Not Connected', color: isConnected('hubspot') ? 'success' : 'default' })] }), testResults.hubspot && (_jsxs(Alert, { severity: testResults.hubspot.success ? 'success' : 'error', sx: { mt: 2 }, onClose: () => setTestResults(prev => ({ ...prev, hubspot: null })), children: [testResults.hubspot.message, testResults.hubspot.details && (_jsxs(Typography, { variant: "caption", display: "block", children: ["Hub ID: ", testResults.hubspot.details.hub_id] }))] })), _jsx(Stack, { direction: "row", spacing: 2, sx: { mt: 2 }, children: !isConnected('hubspot') ? (_jsx(Button, { variant: "contained", startIcon: _jsx(LinkIcon, {}), onClick: () => handleConnect('hubspot'), children: "Connect HubSpot" })) : (_jsxs(_Fragment, { children: [_jsx(Button, { variant: "outlined", startIcon: testing === 'hubspot' ? _jsx(CircularProgress, { size: 20 }) : _jsx(CheckIcon, {}), onClick: () => handleTestConnection('hubspot'), disabled: testing === 'hubspot', children: "Test Connection" }), _jsx(Button, { variant: "outlined", color: "error", startIcon: _jsx(DeleteIcon, {}), onClick: () => {
                                                    const config = getProviderConfig('hubspot');
                                                    if (config)
                                                        handleDisconnect(config.id);
                                                }, children: "Disconnect" })] })) }), isConnected('hubspot') && (_jsx(Box, { sx: { mt: 2 }, children: _jsxs(Typography, { variant: "caption", color: "text.secondary", children: ["Last synced: ", getProviderConfig('hubspot')?.last_sync_at || 'Never'] }) }))] }) })] }), _jsxs(Dialog, { open: !!apiKeyDialog, onClose: () => setApiKeyDialog(null), children: [_jsx(DialogTitle, { children: "Connect with API Key" }), _jsxs(DialogContent, { children: [_jsxs(Typography, { variant: "body2", paragraph: true, children: ["Enter your ", apiKeyDialog, " API key to connect."] }), _jsx(TextField, { autoFocus: true, fullWidth: true, label: "API Key", type: "password", value: apiKey, onChange: (e) => setApiKey(e.target.value), sx: { mt: 2 } })] }), _jsxs(DialogActions, { children: [_jsx(Button, { onClick: () => setApiKeyDialog(null), children: "Cancel" }), _jsx(Button, { onClick: handleApiKeyConnect, variant: "contained", children: "Connect" })] })] })] }));
}
