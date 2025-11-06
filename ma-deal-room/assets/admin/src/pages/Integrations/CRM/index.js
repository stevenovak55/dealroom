import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
/**
 * CRM Integration Page
 *
 * Main CRM integration page with tabs for Settings, Sync Configuration, and Monitoring.
 */
import { useState } from 'react';
import { Box, Container, Tab, Tabs, Paper } from '@mui/material';
import CRMSettings from './CRMSettings';
import SyncSettings from './SyncSettings';
import SyncMonitor from './SyncMonitor';
function TabPanel(props) {
    const { children, value, index, ...other } = props;
    return (_jsx("div", { role: "tabpanel", hidden: value !== index, id: `crm-tabpanel-${index}`, "aria-labelledby": `crm-tab-${index}`, ...other, children: value === index && _jsx(Box, { sx: { p: 3 }, children: children }) }));
}
export default function CRMIntegrationPage() {
    const [currentTab, setCurrentTab] = useState(0);
    const handleTabChange = (_event, newValue) => {
        setCurrentTab(newValue);
    };
    return (_jsx(Container, { maxWidth: "lg", sx: { mt: 4, mb: 4 }, children: _jsxs(Paper, { children: [_jsx(Box, { sx: { borderBottom: 1, borderColor: 'divider' }, children: _jsxs(Tabs, { value: currentTab, onChange: handleTabChange, "aria-label": "CRM integration tabs", children: [_jsx(Tab, { label: "CRM Connections", id: "crm-tab-0" }), _jsx(Tab, { label: "Sync Settings", id: "crm-tab-1" }), _jsx(Tab, { label: "Sync Monitor", id: "crm-tab-2" })] }) }), _jsx(TabPanel, { value: currentTab, index: 0, children: _jsx(CRMSettings, {}) }), _jsx(TabPanel, { value: currentTab, index: 1, children: _jsx(SyncSettings, {}) }), _jsx(TabPanel, { value: currentTab, index: 2, children: _jsx(SyncMonitor, {}) })] }) }));
}
