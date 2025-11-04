/**
 * CRM Settings Component
 *
 * Allows admins to connect Salesforce or HubSpot via OAuth 2.0.
 * Displays connection status and provides test connection functionality.
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
  Chip,
  Stack,
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField
} from '@mui/material';
import {
  CloudDone,
  CloudOff,
  Link as LinkIcon,
  Delete as DeleteIcon,
  Check as CheckIcon
} from '@mui/icons-material';
import { crmService, CRMConfiguration, CRMProvider } from '../../../api/crmService';

export default function CRMSettings() {
  const [configurations, setConfigurations] = useState<CRMConfiguration[]>([]);
  const [supportedProviders, setSupportedProviders] = useState<Record<string, CRMProvider>>({});
  const [loading, setLoading] = useState(true);
  const [testing, setTesting] = useState<string | null>(null);
  const [testResults, setTestResults] = useState<Record<string, any>>({});
  const [apiKeyDialog, setApiKeyDialog] = useState<string | null>(null);
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
    } catch (error) {
      console.error('Failed to load CRM configurations:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleConnect = (provider: 'salesforce' | 'hubspot') => {
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
    if (!apiKeyDialog) return;

    try {
      setLoading(true);
      await crmService.configureCRM({
        provider: apiKeyDialog as 'salesforce' | 'hubspot',
        credentials: {
          api_key: apiKey
        }
      });

      setApiKeyDialog(null);
      setApiKey('');
      loadConfigurations();
    } catch (error) {
      console.error('Failed to configure CRM with API key:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleTestConnection = async (provider: 'salesforce' | 'hubspot') => {
    try {
      setTesting(provider);
      const result = await crmService.testConnection(provider);
      setTestResults(prev => ({ ...prev, [provider]: result }));
    } catch (error) {
      setTestResults(prev => ({
        ...prev,
        [provider]: { success: false, message: 'Connection test failed' }
      }));
    } finally {
      setTesting(null);
    }
  };

  const handleDisconnect = async (configId: number) => {
    if (!confirm('Are you sure you want to disconnect this CRM?')) return;

    try {
      setLoading(true);
      await crmService.deleteConfiguration(configId);
      loadConfigurations();
    } catch (error) {
      console.error('Failed to disconnect CRM:', error);
    } finally {
      setLoading(false);
    }
  };

  const getProviderConfig = (provider: string) => {
    return configurations.find(c => c.provider_type === provider);
  };

  const isConnected = (provider: string) => {
    return !!getProviderConfig(provider);
  };

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight={400}>
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      <Typography variant="h5" gutterBottom>
        CRM Connections
      </Typography>
      <Typography variant="body2" color="text.secondary" paragraph>
        Connect your CRM to sync contacts, deals, and activities automatically.
      </Typography>

      <Stack spacing={3} sx={{ mt: 3 }}>
        {/* Salesforce */}
        <Card>
          <CardContent>
            <Box display="flex" alignItems="center" justifyContent="space-between">
              <Box display="flex" alignItems="center" gap={2}>
                <Box
                  component="img"
                  src="https://www.salesforce.com/content/dam/sfdc-docs/www/logos/logo-salesforce.svg"
                  alt="Salesforce"
                  sx={{ height: 40 }}
                />
                <Box>
                  <Typography variant="h6">Salesforce</Typography>
                  <Typography variant="body2" color="text.secondary">
                    Sync contacts as Contacts, deals as Opportunities
                  </Typography>
                </Box>
              </Box>
              <Chip
                icon={isConnected('salesforce') ? <CloudDone /> : <CloudOff />}
                label={isConnected('salesforce') ? 'Connected' : 'Not Connected'}
                color={isConnected('salesforce') ? 'success' : 'default'}
              />
            </Box>

            {testResults.salesforce && (
              <Alert
                severity={testResults.salesforce.success ? 'success' : 'error'}
                sx={{ mt: 2 }}
                onClose={() => setTestResults(prev => ({ ...prev, salesforce: null }))}
              >
                {testResults.salesforce.message}
                {testResults.salesforce.details && (
                  <Typography variant="caption" display="block">
                    {testResults.salesforce.details.display_name || testResults.salesforce.details.organization_id}
                  </Typography>
                )}
              </Alert>
            )}

            <Stack direction="row" spacing={2} sx={{ mt: 2 }}>
              {!isConnected('salesforce') ? (
                <Button
                  variant="contained"
                  startIcon={<LinkIcon />}
                  onClick={() => handleConnect('salesforce')}
                >
                  Connect Salesforce
                </Button>
              ) : (
                <>
                  <Button
                    variant="outlined"
                    startIcon={testing === 'salesforce' ? <CircularProgress size={20} /> : <CheckIcon />}
                    onClick={() => handleTestConnection('salesforce')}
                    disabled={testing === 'salesforce'}
                  >
                    Test Connection
                  </Button>
                  <Button
                    variant="outlined"
                    color="error"
                    startIcon={<DeleteIcon />}
                    onClick={() => {
                      const config = getProviderConfig('salesforce');
                      if (config) handleDisconnect(config.id);
                    }}
                  >
                    Disconnect
                  </Button>
                </>
              )}
            </Stack>

            {isConnected('salesforce') && (
              <Box sx={{ mt: 2 }}>
                <Typography variant="caption" color="text.secondary">
                  Last synced: {getProviderConfig('salesforce')?.last_sync_at || 'Never'}
                </Typography>
              </Box>
            )}
          </CardContent>
        </Card>

        {/* HubSpot */}
        <Card>
          <CardContent>
            <Box display="flex" alignItems="center" justifyContent="space-between">
              <Box display="flex" alignItems="center" gap={2}>
                <Box
                  component="img"
                  src="https://www.hubspot.com/hubfs/HubSpot_Logos/HubSpot-Inversed-Favicon.png"
                  alt="HubSpot"
                  sx={{ height: 40 }}
                />
                <Box>
                  <Typography variant="h6">HubSpot</Typography>
                  <Typography variant="body2" color="text.secondary">
                    Sync contacts as Contacts, deals as Deals
                  </Typography>
                </Box>
              </Box>
              <Chip
                icon={isConnected('hubspot') ? <CloudDone /> : <CloudOff />}
                label={isConnected('hubspot') ? 'Connected' : 'Not Connected'}
                color={isConnected('hubspot') ? 'success' : 'default'}
              />
            </Box>

            {testResults.hubspot && (
              <Alert
                severity={testResults.hubspot.success ? 'success' : 'error'}
                sx={{ mt: 2 }}
                onClose={() => setTestResults(prev => ({ ...prev, hubspot: null }))}
              >
                {testResults.hubspot.message}
                {testResults.hubspot.details && (
                  <Typography variant="caption" display="block">
                    Hub ID: {testResults.hubspot.details.hub_id}
                  </Typography>
                )}
              </Alert>
            )}

            <Stack direction="row" spacing={2} sx={{ mt: 2 }}>
              {!isConnected('hubspot') ? (
                <Button
                  variant="contained"
                  startIcon={<LinkIcon />}
                  onClick={() => handleConnect('hubspot')}
                >
                  Connect HubSpot
                </Button>
              ) : (
                <>
                  <Button
                    variant="outlined"
                    startIcon={testing === 'hubspot' ? <CircularProgress size={20} /> : <CheckIcon />}
                    onClick={() => handleTestConnection('hubspot')}
                    disabled={testing === 'hubspot'}
                  >
                    Test Connection
                  </Button>
                  <Button
                    variant="outlined"
                    color="error"
                    startIcon={<DeleteIcon />}
                    onClick={() => {
                      const config = getProviderConfig('hubspot');
                      if (config) handleDisconnect(config.id);
                    }}
                  >
                    Disconnect
                  </Button>
                </>
              )}
            </Stack>

            {isConnected('hubspot') && (
              <Box sx={{ mt: 2 }}>
                <Typography variant="caption" color="text.secondary">
                  Last synced: {getProviderConfig('hubspot')?.last_sync_at || 'Never'}
                </Typography>
              </Box>
            )}
          </CardContent>
        </Card>
      </Stack>

      {/* API Key Dialog */}
      <Dialog open={!!apiKeyDialog} onClose={() => setApiKeyDialog(null)}>
        <DialogTitle>Connect with API Key</DialogTitle>
        <DialogContent>
          <Typography variant="body2" paragraph>
            Enter your {apiKeyDialog} API key to connect.
          </Typography>
          <TextField
            autoFocus
            fullWidth
            label="API Key"
            type="password"
            value={apiKey}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setApiKey(e.target.value)}
            sx={{ mt: 2 }}
          />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setApiKeyDialog(null)}>Cancel</Button>
          <Button onClick={handleApiKeyConnect} variant="contained">
            Connect
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
}
