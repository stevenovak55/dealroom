/**
 * Sync Settings Component
 *
 * Configure sync options: enable/disable sync, sync direction, field mapping, conflict resolution.
 */

import { useState, useEffect } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  Typography,
  Switch,
  FormControlLabel,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Alert,
  CircularProgress,
  Divider,
  Stack,
  Chip
} from '@mui/material';
import { Save as SaveIcon } from '@mui/icons-material';
import { crmService, CRMConfiguration } from '../../../api/crmService';

export default function SyncSettings() {
  const [configurations, setConfigurations] = useState<CRMConfiguration[]>([]);
  const [selectedProvider, setSelectedProvider] = useState<'salesforce' | 'hubspot' | ''>('');
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
  const [saveMessage, setSaveMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

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
    } catch (error) {
      console.error('Failed to load CRM configurations:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadProviderSettings = (provider: string) => {
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
    if (!selectedProvider) return;

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
    } catch (error) {
      console.error('Failed to save settings:', error);
      setSaveMessage({ type: 'error', text: 'Failed to save settings. Please try again.' });
    } finally {
      setSaving(false);
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
      <Typography variant="h5" gutterBottom>
        Sync Configuration
      </Typography>
      <Typography variant="body2" color="text.secondary" paragraph>
        Configure how data syncs between Deal Room and your CRM.
      </Typography>

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
                  {config.is_active && <Chip label="Active" size="small" color="success" sx={{ ml: 1 }} />}
                </MenuItem>
              ))}
            </Select>
          </FormControl>
        </CardContent>
      </Card>

      {selectedProvider && (
        <>
          {/* Enable/Disable Sync */}
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Enable Sync
              </Typography>
              <Divider sx={{ mb: 2 }} />

              <FormControlLabel
                control={
                  <Switch
                    checked={settings.sync_enabled}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSettings({ ...settings, sync_enabled: e.target.checked })}
                  />
                }
                label="Enable CRM synchronization"
              />
              <Typography variant="caption" color="text.secondary" display="block" sx={{ ml: 4 }}>
                Master switch for all CRM sync operations
              </Typography>
            </CardContent>
          </Card>

          {/* What to Sync */}
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                What to Sync
              </Typography>
              <Divider sx={{ mb: 2 }} />

              <Stack spacing={1}>
                <FormControlLabel
                  control={
                    <Switch
                      checked={settings.sync_contacts}
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSettings({ ...settings, sync_contacts: e.target.checked })}
                      disabled={!settings.sync_enabled}
                    />
                  }
                  label="Sync Contacts"
                />
                <Typography variant="caption" color="text.secondary" display="block" sx={{ ml: 4 }}>
                  Synchronize contacts between Deal Room and CRM
                </Typography>

                <FormControlLabel
                  control={
                    <Switch
                      checked={settings.sync_deals}
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSettings({ ...settings, sync_deals: e.target.checked })}
                      disabled={!settings.sync_enabled}
                    />
                  }
                  label="Sync Deals/Opportunities"
                />
                <Typography variant="caption" color="text.secondary" display="block" sx={{ ml: 4 }}>
                  Synchronize transactions as {selectedProvider === 'salesforce' ? 'Opportunities' : 'Deals'}
                </Typography>

                <FormControlLabel
                  control={
                    <Switch
                      checked={settings.sync_activities}
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSettings({ ...settings, sync_activities: e.target.checked })}
                      disabled={!settings.sync_enabled}
                    />
                  }
                  label="Sync Activities"
                />
                <Typography variant="caption" color="text.secondary" display="block" sx={{ ml: 4 }}>
                  Log activities (tasks, documents, status changes) to CRM timeline
                </Typography>
              </Stack>
            </CardContent>
          </Card>

          {/* Sync Direction */}
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Sync Direction
              </Typography>
              <Divider sx={{ mb: 2 }} />

              <FormControl fullWidth>
                <InputLabel>Direction</InputLabel>
                <Select
                  value={settings.sync_direction}
                  label="Direction"
                  onChange={(e) => setSettings({ ...settings, sync_direction: e.target.value as string })}
                  disabled={!settings.sync_enabled}
                >
                  <MenuItem value="bidirectional">
                    Bidirectional - Sync both ways
                  </MenuItem>
                  <MenuItem value="oneway_to_crm">
                    One-way to CRM - Deal Room → CRM only
                  </MenuItem>
                  <MenuItem value="oneway_from_crm">
                    One-way from CRM - CRM → Deal Room only
                  </MenuItem>
                </Select>
              </FormControl>
              <Typography variant="caption" color="text.secondary" display="block" sx={{ mt: 1 }}>
                {settings.sync_direction === 'bidirectional' &&
                  'Changes in either system will sync to the other'}
                {settings.sync_direction === 'oneway_to_crm' &&
                  'Changes in Deal Room will sync to CRM, but not vice versa'}
                {settings.sync_direction === 'oneway_from_crm' &&
                  'Changes in CRM will sync to Deal Room, but not vice versa'}
              </Typography>
            </CardContent>
          </Card>

          {/* Conflict Resolution */}
          <Card sx={{ mb: 3 }}>
            <CardContent>
              <Typography variant="h6" gutterBottom>
                Conflict Resolution
              </Typography>
              <Divider sx={{ mb: 2 }} />

              <FormControl fullWidth>
                <InputLabel>Strategy</InputLabel>
                <Select
                  value={settings.conflict_resolution}
                  label="Strategy"
                  onChange={(e) => setSettings({ ...settings, conflict_resolution: e.target.value as string })}
                  disabled={!settings.sync_enabled}
                >
                  <MenuItem value="last_write_wins">
                    Last Write Wins - Most recent change takes precedence
                  </MenuItem>
                  <MenuItem value="crm_wins">
                    CRM Wins - CRM data always takes precedence
                  </MenuItem>
                  <MenuItem value="dealroom_wins">
                    Deal Room Wins - Deal Room data always takes precedence
                  </MenuItem>
                  <MenuItem value="manual">
                    Manual - Require manual conflict resolution
                  </MenuItem>
                </Select>
              </FormControl>
              <Typography variant="caption" color="text.secondary" display="block" sx={{ mt: 1 }}>
                How to handle conflicts when the same record is modified in both systems
              </Typography>
            </CardContent>
          </Card>

          {/* Save Button */}
          {saveMessage && (
            <Alert severity={saveMessage.type} sx={{ mb: 2 }}>
              {saveMessage.text}
            </Alert>
          )}

          <Button
            variant="contained"
            startIcon={saving ? <CircularProgress size={20} /> : <SaveIcon />}
            onClick={handleSave}
            disabled={saving || !settings.sync_enabled}
            size="large"
          >
            Save Settings
          </Button>
        </>
      )}
    </Box>
  );
}
