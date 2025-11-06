import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
/**
 * Sync Settings Component
 *
 * Configure sync options: enable/disable sync, sync direction, field mapping, conflict resolution.
 */
import { useState, useEffect } from 'react';
import { Box, Button, Card, CardContent, Typography, Switch, FormControlLabel, FormControl, InputLabel, Select, MenuItem, Alert, CircularProgress, Divider, Stack, Chip } from '@mui/material';
import { Save as SaveIcon } from '@mui/icons-material';
import { crmService } from '../../../api/crmService';
export default function SyncSettings() {
    const [configurations, setConfigurations] = useState([]);
    const [selectedProvider, setSelectedProvider] = useState('');
    const [settings, setSettings] = useState({
        sync_enabled: true,
        sync_contacts: true,
        sync_deals: true,
        sync_activities: true,
        sync_direction: 'bidirectional',
        conflict_resolution: 'last_write_wins'
    });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [saveMessage, setSaveMessage] = useState(null);
    useEffect(() => {
        loadConfigurations();
    }, []);
    useEffect(() => {
        if (selectedProvider) {
            loadProviderSettings(selectedProvider);
        }
    }, [selectedProvider, configurations]);
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
    const loadProviderSettings = (provider) => {
        const config = configurations.find(c => c.provider_type === provider);
        if (config) {
            setSettings({
                sync_enabled: config.sync_enabled,
                sync_contacts: config.sync_contacts,
                sync_deals: config.sync_deals,
                sync_activities: config.sync_activities,
                sync_direction: config.sync_direction,
                conflict_resolution: config.conflict_resolution
            });
        }
    };
    const handleSave = async () => {
        if (!selectedProvider)
            return;
        try {
            setSaving(true);
            setSaveMessage(null);
            await crmService.configureCRM({
                provider: selectedProvider,
                credentials: {}, // Keep existing credentials
                ...settings
            });
            setSaveMessage({ type: 'success', text: 'Settings saved successfully!' });
            loadConfigurations();
        }
        catch (error) {
            console.error('Failed to save settings:', error);
            setSaveMessage({ type: 'error', text: 'Failed to save settings. Please try again.' });
        }
        finally {
            setSaving(false);
        }
    };
    if (loading) {
        return (_jsx(Box, { display: "flex", justifyContent: "center", alignItems: "center", minHeight: 400, children: _jsx(CircularProgress, {}) }));
    }
    if (configurations.length === 0) {
        return (_jsx(Alert, { severity: "info", children: "No CRM connections found. Please connect a CRM in the Settings tab first." }));
    }
    return (_jsxs(Box, { children: [_jsx(Typography, { variant: "h5", gutterBottom: true, children: "Sync Configuration" }), _jsx(Typography, { variant: "body2", color: "text.secondary", paragraph: true, children: "Configure how data syncs between Deal Room and your CRM." }), _jsx(Card, { sx: { mb: 3 }, children: _jsx(CardContent, { children: _jsxs(FormControl, { fullWidth: true, children: [_jsx(InputLabel, { children: "CRM Provider" }), _jsx(Select, { value: selectedProvider, label: "CRM Provider", onChange: (e) => setSelectedProvider(e.target.value), children: configurations.map(config => (_jsxs(MenuItem, { value: config.provider_type, children: [config.provider_type === 'salesforce' ? 'Salesforce' : 'HubSpot', config.is_active && _jsx(Chip, { label: "Active", size: "small", color: "success", sx: { ml: 1 } })] }, config.id))) })] }) }) }), selectedProvider && (_jsxs(_Fragment, { children: [_jsx(Card, { sx: { mb: 3 }, children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "Enable Sync" }), _jsx(Divider, { sx: { mb: 2 } }), _jsx(FormControlLabel, { control: _jsx(Switch, { checked: settings.sync_enabled, onChange: (e) => setSettings({ ...settings, sync_enabled: e.target.checked }) }), label: "Enable CRM synchronization" }), _jsx(Typography, { variant: "caption", color: "text.secondary", display: "block", sx: { ml: 4 }, children: "Master switch for all CRM sync operations" })] }) }), _jsx(Card, { sx: { mb: 3 }, children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "What to Sync" }), _jsx(Divider, { sx: { mb: 2 } }), _jsxs(Stack, { spacing: 1, children: [_jsx(FormControlLabel, { control: _jsx(Switch, { checked: settings.sync_contacts, onChange: (e) => setSettings({ ...settings, sync_contacts: e.target.checked }), disabled: !settings.sync_enabled }), label: "Sync Contacts" }), _jsx(Typography, { variant: "caption", color: "text.secondary", display: "block", sx: { ml: 4 }, children: "Synchronize contacts between Deal Room and CRM" }), _jsx(FormControlLabel, { control: _jsx(Switch, { checked: settings.sync_deals, onChange: (e) => setSettings({ ...settings, sync_deals: e.target.checked }), disabled: !settings.sync_enabled }), label: "Sync Deals/Opportunities" }), _jsxs(Typography, { variant: "caption", color: "text.secondary", display: "block", sx: { ml: 4 }, children: ["Synchronize transactions as ", selectedProvider === 'salesforce' ? 'Opportunities' : 'Deals'] }), _jsx(FormControlLabel, { control: _jsx(Switch, { checked: settings.sync_activities, onChange: (e) => setSettings({ ...settings, sync_activities: e.target.checked }), disabled: !settings.sync_enabled }), label: "Sync Activities" }), _jsx(Typography, { variant: "caption", color: "text.secondary", display: "block", sx: { ml: 4 }, children: "Log activities (tasks, documents, status changes) to CRM timeline" })] })] }) }), _jsx(Card, { sx: { mb: 3 }, children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "Sync Direction" }), _jsx(Divider, { sx: { mb: 2 } }), _jsxs(FormControl, { fullWidth: true, children: [_jsx(InputLabel, { children: "Direction" }), _jsxs(Select, { value: settings.sync_direction, label: "Direction", onChange: (e) => setSettings({ ...settings, sync_direction: e.target.value }), disabled: !settings.sync_enabled, children: [_jsx(MenuItem, { value: "bidirectional", children: "Bidirectional - Sync both ways" }), _jsx(MenuItem, { value: "oneway_to_crm", children: "One-way to CRM - Deal Room \u2192 CRM only" }), _jsx(MenuItem, { value: "oneway_from_crm", children: "One-way from CRM - CRM \u2192 Deal Room only" })] })] }), _jsxs(Typography, { variant: "caption", color: "text.secondary", display: "block", sx: { mt: 1 }, children: [settings.sync_direction === 'bidirectional' &&
                                            'Changes in either system will sync to the other', settings.sync_direction === 'oneway_to_crm' &&
                                            'Changes in Deal Room will sync to CRM, but not vice versa', settings.sync_direction === 'oneway_from_crm' &&
                                            'Changes in CRM will sync to Deal Room, but not vice versa'] })] }) }), _jsx(Card, { sx: { mb: 3 }, children: _jsxs(CardContent, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: "Conflict Resolution" }), _jsx(Divider, { sx: { mb: 2 } }), _jsxs(FormControl, { fullWidth: true, children: [_jsx(InputLabel, { children: "Strategy" }), _jsxs(Select, { value: settings.conflict_resolution, label: "Strategy", onChange: (e) => setSettings({ ...settings, conflict_resolution: e.target.value }), disabled: !settings.sync_enabled, children: [_jsx(MenuItem, { value: "last_write_wins", children: "Last Write Wins - Most recent change takes precedence" }), _jsx(MenuItem, { value: "crm_wins", children: "CRM Wins - CRM data always takes precedence" }), _jsx(MenuItem, { value: "dealroom_wins", children: "Deal Room Wins - Deal Room data always takes precedence" }), _jsx(MenuItem, { value: "manual", children: "Manual - Require manual conflict resolution" })] })] }), _jsx(Typography, { variant: "caption", color: "text.secondary", display: "block", sx: { mt: 1 }, children: "How to handle conflicts when the same record is modified in both systems" })] }) }), saveMessage && (_jsx(Alert, { severity: saveMessage.type, sx: { mb: 2 }, children: saveMessage.text })), _jsx(Button, { variant: "contained", startIcon: saving ? _jsx(CircularProgress, { size: 20 }) : _jsx(SaveIcon, {}), onClick: handleSave, disabled: saving || !settings.sync_enabled, size: "large", children: "Save Settings" })] }))] }));
}
