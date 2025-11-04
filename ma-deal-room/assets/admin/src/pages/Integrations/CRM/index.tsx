/**
 * CRM Integration Page
 *
 * Main CRM integration page with tabs for Settings, Sync Configuration, and Monitoring.
 */

import React, { useState } from 'react';
import { Box, Container, Tab, Tabs, Paper } from '@mui/material';
import CRMSettings from './CRMSettings';
import SyncSettings from './SyncSettings';
import SyncMonitor from './SyncMonitor';

interface TabPanelProps {
  children?: React.ReactNode;
  index: number;
  value: number;
}

function TabPanel(props: TabPanelProps) {
  const { children, value, index, ...other } = props;

  return (
    <div
      role="tabpanel"
      hidden={value !== index}
      id={`crm-tabpanel-${index}`}
      aria-labelledby={`crm-tab-${index}`}
      {...other}
    >
      {value === index && <Box sx={{ p: 3 }}>{children}</Box>}
    </div>
  );
}

export default function CRMIntegrationPage() {
  const [currentTab, setCurrentTab] = useState(0);

  const handleTabChange = (_event: React.SyntheticEvent, newValue: number) => {
    setCurrentTab(newValue);
  };

  return (
    <Container maxWidth="lg" sx={{ mt: 4, mb: 4 }}>
      <Paper>
        <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
          <Tabs value={currentTab} onChange={handleTabChange} aria-label="CRM integration tabs">
            <Tab label="CRM Connections" id="crm-tab-0" />
            <Tab label="Sync Settings" id="crm-tab-1" />
            <Tab label="Sync Monitor" id="crm-tab-2" />
          </Tabs>
        </Box>

        <TabPanel value={currentTab} index={0}>
          <CRMSettings />
        </TabPanel>

        <TabPanel value={currentTab} index={1}>
          <SyncSettings />
        </TabPanel>

        <TabPanel value={currentTab} index={2}>
          <SyncMonitor />
        </TabPanel>
      </Paper>
    </Container>
  );
}
