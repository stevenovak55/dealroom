/**
 * Sync Monitor Component
 *
 * Display sync status, statistics, and provide manual sync triggers.
 */

import { useState, useEffect } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  Typography,
  Alert,
  CircularProgress,
  Grid,
  LinearProgress,
  Stack,
  Chip,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Paper
} from '@mui/material';
import {
  Sync as SyncIcon,
  CheckCircle as CheckCircleIcon,
  Error as ErrorIcon,
  Refresh as RefreshIcon
} from '@mui/icons-material';
import { crmService, CRMConfiguration, SyncStatus } from '../../../api/crmService';

export default function SyncMonitor() {
  const [configurations, setConfigurations] = useState<CRMConfiguration[]>([]);
  const [selectedProvider, setSelectedProvider] = useState<'salesforce' | 'hubspot' | ''>('');
  const [syncStatus, setSyncStatus] = useState<SyncStatus | null>(null);
  const [syncing, setSyncing] = useState(false);
  const [syncResults, setSyncResults] = useState<any>(null);
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
    } catch (error) {
      console.error('Failed to load CRM configurations:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadSyncStatus = async (provider: 'salesforce' | 'hubspot') => {
    try {
      const status = await crmService.getSyncStatus(provider);
      setSyncStatus(status);
    } catch (error) {
      console.error('Failed to load sync status:', error);
    }
  };

  const handleManualSync = async (type: 'contacts' | 'deals' | 'both') => {
    if (!selectedProvider) return;

    try {
      setSyncing(true);
      setSyncResults(null);

      const results: any = { success: true };

      if (type === 'contacts' || type === 'both') {
        results.contacts = await crmService.syncContacts(selectedProvider);
      }

      if (type === 'deals' || type === 'both') {
        results.deals = await crmService.syncDeals(selectedProvider);
      }

      setSyncResults(results);
      loadSyncStatus(selectedProvider);
    } catch (error) {
      console.error('Manual sync failed:', error);
      setSyncResults({ success: false, error: 'Sync failed' });
    } finally {
      setSyncing(false);
    }
  };

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight={400}>
        <CircularProgress />
      </Box>
    );
  }

  if (configurations.length === 0) {
    return (
      <Alert severity="info">
        No CRM connections found. Please connect a CRM in the Settings tab first.
      </Alert>
    );
  }

  return (
    <Box>
      <Box display="flex" alignItems="center" justifyContent="space-between" mb={3}>
        <Box>
          <Typography variant="h5" gutterBottom>
            Sync Monitor
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Monitor sync status and trigger manual synchronization.
          </Typography>
        </Box>
        <Button
          variant="outlined"
          startIcon={<RefreshIcon />}
          onClick={() => selectedProvider && loadSyncStatus(selectedProvider)}
        >
          Refresh
        </Button>
      </Box>

      {/* Provider Selection */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <FormControl fullWidth>
            <InputLabel>CRM Provider</InputLabel>
            <Select
              value={selectedProvider}
              label="CRM Provider"
              onChange={(e) => setSelectedProvider(e.target.value as 'salesforce' | 'hubspot' | '')}
            >
              {configurations.map(config => (
                <MenuItem key={config.id} value={config.provider_type}>
                  {config.provider_type === 'salesforce' ? 'Salesforce' : 'HubSpot'}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
        </CardContent>
      </Card>

      {selectedProvider && syncStatus && (
        <>
          {/* Sync Status Overview */}
          <Grid container spacing={3} sx={{ mb: 3 }}>
            <Grid size={{ xs: 12, md: 4 }}>
              <Card>
                <CardContent>
                  <Typography variant="h6" color="primary">
                    {syncStatus.contacts.total}
                  </Typography>
                  <Typography variant="body2" color="text.secondary">
                    Total Contacts
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
            <Grid size={{ xs: 12, md: 4 }}>
              <Card>
                <CardContent>
                  <Box display="flex" alignItems="center" gap={1}>
                    <CheckCircleIcon color="success" />
                    <Typography variant="h6" color="success.main">
                      {syncStatus.contacts.synced}
                    </Typography>
                  </Box>
                  <Typography variant="body2" color="text.secondary">
                    Synced Successfully
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
            <Grid size={{ xs: 12, md: 4 }}>
              <Card>
                <CardContent>
                  <Box display="flex" alignItems="center" gap={1}>
                    <ErrorIcon color="error" />
                    <Typography variant="h6" color="error.main">
                      {syncStatus.contacts.errors}
                    </Typography>
                  </Box>
                  <Typography variant="body2" color="text.secondary">
                    Sync Errors
                  </Typography>
                </CardContent>
              </Card>
            </Grid>
          </Grid>

          {/* Last Sync Info */}
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Last Sync
              </Typography>
              <Typography variant="body1">
                {syncStatus.last_sync
                  ? new Date(syncStatus.last_sync).toLocaleString()
                  : 'Never synced'}
              </Typography>
              <Chip
                label={syncStatus.sync_enabled ? 'Sync Enabled' : 'Sync Disabled'}
                color={syncStatus.sync_enabled ? 'success' : 'default'}
                size="small"
                sx={{ mt: 1 }}
              />
            </CardContent>
          </Card>

          {/* Manual Sync Controls */}
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Manual Sync
              </Typography>
              <Typography variant="body2" color="text.secondary" paragraph>
                Trigger a manual synchronization now. This will sync data in both directions.
              </Typography>

              <Stack direction="row" spacing={2}>
                <Button
                  variant="contained"
                  startIcon={syncing ? <CircularProgress size={20} /> : <SyncIcon />}
                  onClick={() => handleManualSync('contacts')}
                  disabled={syncing}
                >
                  Sync Contacts
                </Button>
                <Button
                  variant="contained"
                  startIcon={syncing ? <CircularProgress size={20} /> : <SyncIcon />}
                  onClick={() => handleManualSync('deals')}
                  disabled={syncing}
                >
                  Sync Deals
                </Button>
                <Button
                  variant="contained"
                  color="primary"
                  startIcon={syncing ? <CircularProgress size={20} /> : <SyncIcon />}
                  onClick={() => handleManualSync('both')}
                  disabled={syncing}
                >
                  Sync All
                </Button>
              </Stack>
            </CardContent>
          </Card>

          {/* Sync Progress */}
          {syncing && (
            <Card sx={{ mb: 3 }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  Syncing...
                </Typography>
                <LinearProgress />
              </CardContent>
            </Card>
          )}

          {/* Sync Results */}
          {syncResults && (
            <Card sx={{ mb: 3 }}>
              <CardContent>
                <Typography variant="h6" gutterBottom>
                  Sync Results
                </Typography>

                {syncResults.success ? (
                  <Alert severity="success" sx={{ mb: 2 }}>
                    Sync completed successfully!
                  </Alert>
                ) : (
                  <Alert severity="error" sx={{ mb: 2 }}>
                    Sync failed: {syncResults.error}
                  </Alert>
                )}

                {syncResults.contacts && (
                  <Box sx={{ mb: 2 }}>
                    <Typography variant="subtitle1" gutterBottom>
                      Contacts Sync
                    </Typography>
                    <Paper variant="outlined" sx={{ p: 2 }}>
                      <Grid container spacing={2}>
                        {syncResults.contacts.from_crm && (
                          <Grid size={{ xs: 12, md: 6 }}>
                            <Typography variant="body2" color="text.secondary">
                              From CRM:
                            </Typography>
                            <Typography>
                              Created: {syncResults.contacts.from_crm.created} |
                              Updated: {syncResults.contacts.from_crm.updated} |
                              Errors: {syncResults.contacts.from_crm.errors?.length || 0}
                            </Typography>
                          </Grid>
                        )}
                        {syncResults.contacts.to_crm && (
                          <Grid size={{ xs: 12, md: 6 }}>
                            <Typography variant="body2" color="text.secondary">
                              To CRM:
                            </Typography>
                            <Typography>
                              Created: {syncResults.contacts.to_crm.created} |
                              Updated: {syncResults.contacts.to_crm.updated} |
                              Errors: {syncResults.contacts.to_crm.errors?.length || 0}
                            </Typography>
                          </Grid>
                        )}
                      </Grid>
                    </Paper>
                  </Box>
                )}

                {syncResults.deals && (
                  <Box>
                    <Typography variant="subtitle1" gutterBottom>
                      Deals Sync
                    </Typography>
                    <Paper variant="outlined" sx={{ p: 2 }}>
                      <Grid container spacing={2}>
                        {syncResults.deals.from_crm && (
                          <Grid size={{ xs: 12, md: 6 }}>
                            <Typography variant="body2" color="text.secondary">
                              From CRM:
                            </Typography>
                            <Typography>
                              Created: {syncResults.deals.from_crm.created} |
                              Updated: {syncResults.deals.from_crm.updated} |
                              Errors: {syncResults.deals.from_crm.errors?.length || 0}
                            </Typography>
                          </Grid>
                        )}
                        {syncResults.deals.to_crm && (
                          <Grid size={{ xs: 12, md: 6 }}>
                            <Typography variant="body2" color="text.secondary">
                              To CRM:
                            </Typography>
                            <Typography>
                              Created: {syncResults.deals.to_crm.created} |
                              Updated: {syncResults.deals.to_crm.updated} |
                              Errors: {syncResults.deals.to_crm.errors?.length || 0}
                            </Typography>
                          </Grid>
                        )}
                      </Grid>
                    </Paper>
                  </Box>
                )}
              </CardContent>
            </Card>
          )}
        </>
      )}
    </Box>
  );
}
