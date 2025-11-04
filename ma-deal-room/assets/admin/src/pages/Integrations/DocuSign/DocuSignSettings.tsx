/**
 * DocuSign Settings Component
 *
 * Allows admins to configure DocuSign integration with OAuth 2.0 JWT authentication.
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
  TextField,
  FormControl,
  InputLabel,
  Select,
  MenuItem,
  Paper,
  Divider,
  Link,
} from '@mui/material';
import {
  CloudDone,
  CloudOff,
  Check as CheckIcon,
  Error as ErrorIcon,
  Info as InfoIcon,
} from '@mui/icons-material';
import { docusignService, DocuSignConfig } from '../../../api/docusignService';

export default function DocuSignSettings() {
  const [config, setConfig] = useState<Partial<DocuSignConfig>>({
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
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});

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
    } catch (error) {
      console.error('Failed to load DocuSign configuration:', error);
    } finally {
      setLoading(false);
    }
  };

  const validate = (): boolean => {
    const newErrors: Record<string, string> = {};

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
    } catch (error: any) {
      alert(`Failed to save configuration: ${error.response?.data?.message || error.message}`);
    } finally {
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
    } catch (error: any) {
      setTestResult({
        success: false,
        message: error.response?.data?.message || error.message || 'Connection test failed',
      });
    } finally {
      setTesting(false);
    }
  };

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
        <CircularProgress />
      </Box>
    );
  }

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        DocuSign Integration
      </Typography>

      {/* Status Card */}
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Stack direction="row" spacing={2} alignItems="center">
            {configured ? (
              <>
                <CloudDone color="success" sx={{ fontSize: 40 }} />
                <Box>
                  <Typography variant="h6">Connected</Typography>
                  <Typography variant="body2" color="text.secondary">
                    DocuSign is configured and ready to use
                  </Typography>
                </Box>
                <Chip
                  label={config.environment === 'production' ? 'Production' : 'Sandbox'}
                  color={config.environment === 'production' ? 'error' : 'warning'}
                  size="small"
                />
              </>
            ) : (
              <>
                <CloudOff color="disabled" sx={{ fontSize: 40 }} />
                <Box>
                  <Typography variant="h6">Not Connected</Typography>
                  <Typography variant="body2" color="text.secondary">
                    Configure DocuSign to enable electronic signatures
                  </Typography>
                </Box>
              </>
            )}
          </Stack>
        </CardContent>
      </Card>

      {/* Setup Instructions */}
      {!configured && (
        <Alert severity="info" sx={{ mb: 3 }}>
          <Typography variant="subtitle2" gutterBottom>
            <strong>Setup Instructions:</strong>
          </Typography>
          <Typography variant="body2" component="div">
            <ol>
              <li>
                Create a DocuSign developer account at{' '}
                <Link href="https://developers.docusign.com/" target="_blank" rel="noopener">
                  developers.docusign.com
                </Link>
              </li>
              <li>Create an integration app and note the Integration Key</li>
              <li>Generate an RSA key pair and add the public key to your app</li>
              <li>Grant consent for the integration in DocuSign admin panel</li>
              <li>Fill in the configuration details below</li>
            </ol>
          </Typography>
        </Alert>
      )}

      {/* Configuration Form */}
      <Card>
        <CardContent>
          <Typography variant="h6" gutterBottom>
            Configuration
          </Typography>
          <Divider sx={{ mb: 3 }} />

          <Stack spacing={3}>
            <FormControl fullWidth>
              <InputLabel>Environment</InputLabel>
              <Select
                value={config.environment}
                onChange={(e) => setConfig({ ...config, environment: e.target.value as any })}
                label="Environment"
              >
                <MenuItem value="sandbox">Sandbox (Testing)</MenuItem>
                <MenuItem value="production">Production</MenuItem>
              </Select>
            </FormControl>

            <TextField
              fullWidth
              label="Integration Key"
              value={config.integration_key}
              onChange={(e) => setConfig({ ...config, integration_key: e.target.value })}
              error={!!errors.integration_key}
              helperText={errors.integration_key || 'OAuth Client ID from DocuSign'}
              placeholder="e.g., 12345678-abcd-1234-abcd-123456789abc"
            />

            <TextField
              fullWidth
              label="User ID"
              value={config.user_id}
              onChange={(e) => setConfig({ ...config, user_id: e.target.value })}
              error={!!errors.user_id}
              helperText={errors.user_id || 'DocuSign User ID (GUID)'}
              placeholder="e.g., 12345678-abcd-1234-abcd-123456789abc"
            />

            <TextField
              fullWidth
              label="DocuSign Account ID"
              value={config.account_id_docusign}
              onChange={(e) => setConfig({ ...config, account_id_docusign: e.target.value })}
              error={!!errors.account_id_docusign}
              helperText={errors.account_id_docusign || 'Your DocuSign account ID'}
              placeholder="e.g., 12345678"
            />

            <TextField
              fullWidth
              multiline
              rows={6}
              label="Private Key"
              value={config.private_key}
              onChange={(e) => setConfig({ ...config, private_key: e.target.value })}
              error={!!errors.private_key}
              helperText={
                errors.private_key ||
                'RSA Private Key in PEM format (leave empty if already configured)'
              }
              placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;...&#10;-----END RSA PRIVATE KEY-----"
            />
          </Stack>

          <Stack direction="row" spacing={2} sx={{ mt: 3 }}>
            <Button
              variant="contained"
              onClick={handleSave}
              disabled={saving}
              startIcon={saving ? <CircularProgress size={20} /> : null}
            >
              {saving ? 'Saving...' : 'Save Configuration'}
            </Button>
            {configured && (
              <Button
                variant="outlined"
                onClick={handleTestConnection}
                disabled={testing}
                startIcon={testing ? <CircularProgress size={20} /> : null}
              >
                {testing ? 'Testing...' : 'Test Connection'}
              </Button>
            )}
          </Stack>

          {/* Test Results */}
          {testResult && (
            <Alert
              severity={testResult.success ? 'success' : 'error'}
              sx={{ mt: 2 }}
              icon={testResult.success ? <CheckIcon /> : <ErrorIcon />}
            >
              <Typography variant="body2">{testResult.message}</Typography>
            </Alert>
          )}
        </CardContent>
      </Card>

      {/* Information Card */}
      <Paper sx={{ p: 2, mt: 3, bgcolor: 'grey.50' }}>
        <Stack direction="row" spacing={1} alignItems="flex-start">
          <InfoIcon color="info" sx={{ mt: 0.5 }} />
          <Box>
            <Typography variant="subtitle2" gutterBottom>
              About DocuSign Integration
            </Typography>
            <Typography variant="body2" color="text.secondary">
              DocuSign integration enables electronic signature capabilities for contracts, disclosures, and
              other documents within your deal room transactions. Documents are sent securely for signing and
              automatically stored back into the transaction upon completion.
            </Typography>
          </Box>
        </Stack>
      </Paper>
    </Box>
  );
}
