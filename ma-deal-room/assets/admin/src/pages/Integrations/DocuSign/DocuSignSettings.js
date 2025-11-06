import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
/**
 * DocuSign Settings Component
 *
 * Allows admins to configure DocuSign integration with OAuth 2.0 JWT authentication.
 */
import { useState, useEffect } from 'react';
import { Box, Button, Card, CardContent, Typography, Alert, CircularProgress, Chip, Stack, TextField, FormControl, InputLabel, Select, MenuItem, Paper, Divider, Link, } from '@mui/material';
import { CloudDone, CloudOff, Check as CheckIcon, Error as ErrorIcon, Info as InfoIcon, } from '@mui/icons-material';
import { docusignService } from '../../../api/docusignService';
export default function DocuSignSettings() {
    const [config, setConfig] = useState({
        integration_key: '',
        user_id: '',
        private_key: '',
        account_id_docusign: '',
        environment: 'sandbox',
        is_active: true,
    });
    const [configured, setConfigured] = useState(false);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState(null);
    const [errors, setErrors] = useState({});
    useEffect(() => {
        loadConfiguration();
    }, []);
    const loadConfiguration = async () => {
        try {
            setLoading(true);
            const data = await docusignService.getConfig();
            setConfigured(data.configured);
            if (data.config) {
                setConfig({
                    ...data.config,
                    private_key: '', // Don't show private key for security
                });
            }
        }
        catch (error) {
            console.error('Failed to load DocuSign configuration:', error);
        }
        finally {
            setLoading(false);
        }
    };
    const validate = () => {
        const newErrors = {};
        if (!config.integration_key?.trim()) {
            newErrors.integration_key = 'Integration Key is required';
        }
        if (!config.user_id?.trim()) {
            newErrors.user_id = 'User ID is required';
        }
        if (!configured && !config.private_key?.trim()) {
            newErrors.private_key = 'Private Key is required for initial setup';
        }
        if (!config.account_id_docusign?.trim()) {
            newErrors.account_id_docusign = 'DocuSign Account ID is required';
        }
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };
    const handleSave = async () => {
        if (!validate()) {
            return;
        }
        try {
            setSaving(true);
            setTestResult(null);
            await docusignService.saveConfig(config);
            alert('DocuSign configuration saved successfully!');
            await loadConfiguration();
        }
        catch (error) {
            alert(`Failed to save configuration: ${error.response?.data?.message || error.message}`);
        }
        finally {
            setSaving(false);
        }
    };
    const handleTestConnection = async () => {
        if (!configured) {
            alert('Please save the configuration first before testing the connection.');
            return;
        }
        try {
            setTesting(true);
            setTestResult(null);
            const result = await docusignService.testConnection();
            setTestResult(result);
        }
        catch (error) {
            setTestResult({
                success: false,
                message: error.response?.data?.message || error.message || 'Connection test failed',
            });
        }
        finally {
            setTesting(false);
        }
    };
    if (loading) {
        return (_jsx(Box, { display: "flex", justifyContent: "center", alignItems: "center", minHeight: "400px", children: _jsx(CircularProgress, {}) }));
    }
    return (_jsxs(Box, { children: [_jsx(Typography, { variant: "h4", gutterBottom: true, children: "DocuSign Integration" }), _jsx(Card, { sx: { mb: 3 }, children: _jsx(CardContent, { children: _jsx(Stack, { direction: "row", spacing: 2, alignItems: "center", children: configured ? (_jsxs(_Fragment, { children: [_jsx(CloudDone, { color: "success", sx: { fontSize: 40 } }), _jsxs(Box, { children: [_jsx(Typography, { variant: "h6", children: "Connected" }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "DocuSign is configured and ready to use" })] }), _jsx(Chip, { label: config.environment === 'production' ? 'Production' : 'Sandbox', color: config.environment === 'production' ? 'error' : 'warning', size: "small" })] })) : (_jsxs(_Fragment, { children: [_jsx(CloudOff, { color: "disabled", sx: { fontSize: 40 } }), _jsxs(Box, { children: [_jsx(Typography, { variant: "h6", children: "Not Connected" }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "Configure DocuSign to enable electronic signatures" })] })] })) }) }) }), !configured && (_jsxs(Alert, { severity: "info", sx: { mb: 3 }, children: [_jsx(Typography, { variant: "subtitle2", gutterBottom: true, children: _jsx("strong", { children: "Setup Instructions:" }) }), _jsx(Typography, { variant: "body2", component: "div", children: _jsxs("ol", { children: [_jsxs("li", { children: ["Create a DocuSign developer account at", ' ', _jsx(Link, { href: "https://developers.docusign.com/", target: "_blank", rel: "noopener", children: "developers.docusign.com" })] }), _jsx("li", { children: "Create an integration app and note the Integration Key" }), _jsx("li", { children: "Generate an RSA key pair and add the public key to your app" }), _jsx("li", { children: "Grant consent for the integration in DocuSign admin panel" }), _jsx("li", { children: "Fill in the configuration details below" })] }) })] })), _jsx(Card, { children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "Configuration" }), _jsx(Divider, { sx: { mb: 3 } }), _jsxs(Stack, { spacing: 3, children: [_jsxs(FormControl, { fullWidth: true, children: [_jsx(InputLabel, { children: "Environment" }), _jsxs(Select, { value: config.environment, onChange: (e) => setConfig({ ...config, environment: e.target.value }), label: "Environment", children: [_jsx(MenuItem, { value: "sandbox", children: "Sandbox (Testing)" }), _jsx(MenuItem, { value: "production", children: "Production" })] })] }), _jsx(TextField, { fullWidth: true, label: "Integration Key", value: config.integration_key, onChange: (e) => setConfig({ ...config, integration_key: e.target.value }), error: !!errors.integration_key, helperText: errors.integration_key || 'OAuth Client ID from DocuSign', placeholder: "e.g., 12345678-abcd-1234-abcd-123456789abc" }), _jsx(TextField, { fullWidth: true, label: "User ID", value: config.user_id, onChange: (e) => setConfig({ ...config, user_id: e.target.value }), error: !!errors.user_id, helperText: errors.user_id || 'DocuSign User ID (GUID)', placeholder: "e.g., 12345678-abcd-1234-abcd-123456789abc" }), _jsx(TextField, { fullWidth: true, label: "DocuSign Account ID", value: config.account_id_docusign, onChange: (e) => setConfig({ ...config, account_id_docusign: e.target.value }), error: !!errors.account_id_docusign, helperText: errors.account_id_docusign || 'Your DocuSign account ID', placeholder: "e.g., 12345678" }), _jsx(TextField, { fullWidth: true, multiline: true, rows: 6, label: "Private Key", value: config.private_key, onChange: (e) => setConfig({ ...config, private_key: e.target.value }), error: !!errors.private_key, helperText: errors.private_key ||
                                        'RSA Private Key in PEM format (leave empty if already configured)', placeholder: "-----BEGIN RSA PRIVATE KEY-----\n...\n-----END RSA PRIVATE KEY-----" })] }), _jsxs(Stack, { direction: "row", spacing: 2, sx: { mt: 3 }, children: [_jsx(Button, { variant: "contained", onClick: handleSave, disabled: saving, startIcon: saving ? _jsx(CircularProgress, { size: 20 }) : null, children: saving ? 'Saving...' : 'Save Configuration' }), configured && (_jsx(Button, { variant: "outlined", onClick: handleTestConnection, disabled: testing, startIcon: testing ? _jsx(CircularProgress, { size: 20 }) : null, children: testing ? 'Testing...' : 'Test Connection' }))] }), testResult && (_jsx(Alert, { severity: testResult.success ? 'success' : 'error', sx: { mt: 2 }, icon: testResult.success ? _jsx(CheckIcon, {}) : _jsx(ErrorIcon, {}), children: _jsx(Typography, { variant: "body2", children: testResult.message }) }))] }) }), _jsx(Paper, { sx: { p: 2, mt: 3, bgcolor: 'grey.50' }, children: _jsxs(Stack, { direction: "row", spacing: 1, alignItems: "flex-start", children: [_jsx(InfoIcon, { color: "info", sx: { mt: 0.5 } }), _jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle2", gutterBottom: true, children: "About DocuSign Integration" }), _jsx(Typography, { variant: "body2", color: "text.secondary", children: "DocuSign integration enables electronic signature capabilities for contracts, disclosures, and other documents within your deal room transactions. Documents are sent securely for signing and automatically stored back into the transaction upon completion." })] })] }) })] }));
}
