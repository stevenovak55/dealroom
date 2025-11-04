/**
 * EnvelopeStatus Component
 * 
 * Displays DocuSign envelope status on transaction detail pages.
 * Shows envelope status, recipients, timestamps, and action buttons.
 */

import { useState, useEffect } from 'react';
import {
  Box,
  Chip,
  List,
  ListItem,
  ListItemText,
  Button,
  CircularProgress,
  Alert,
  Typography,
  Stack,
  Divider,
  Paper,
  Link,
} from '@mui/material';
import {
  CheckCircle,
  Schedule,
  Cancel,
  Send,
  OpenInNew,
} from '@mui/icons-material';
import { docusignService, DocuSignEnvelope } from '../../api/docusignService';
import { formatDateTime } from '../../utils/formatDate';

interface EnvelopeStatusProps {
  envelopeId: string;
  onRefresh?: () => void;
}

/**
 * EnvelopeStatus - Display envelope status with recipient information and actions
 */
export default function EnvelopeStatus({ envelopeId, onRefresh }: EnvelopeStatusProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [envelope, setEnvelope] = useState<DocuSignEnvelope | null>(null);
  const [docusignStatus, setDocusignStatus] = useState<any>(null);
  const [actionLoading, setActionLoading] = useState<'resend' | 'void' | null>(null);

  useEffect(() => {
    loadEnvelope();
  }, [envelopeId]);

  const loadEnvelope = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await docusignService.getEnvelope(envelopeId);
      setEnvelope(data.envelope);
      setDocusignStatus(data.docusign_status);
    } catch (err: any) {
      const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message || 'Failed to load envelope';
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  const handleResend = async () => {
    if (!confirm('Resend envelope notifications to all recipients?')) {
      return;
    }

    try {
      setActionLoading('resend');
      await docusignService.resendEnvelope(envelopeId);
      alert('Envelope notifications resent successfully!');
      await loadEnvelope();
      if (onRefresh) onRefresh();
    } catch (err: any) {
      const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message;
      alert('Failed to resend: ' + message);
    } finally {
      setActionLoading(null);
    }
  };

  const handleVoid = async () => {
    const reason = prompt('Enter reason for voiding this envelope:');
    if (!reason) {
      return;
    }

    try {
      setActionLoading('void');
      await docusignService.voidEnvelope(envelopeId, reason);
      alert('Envelope voided successfully!');
      await loadEnvelope();
      if (onRefresh) onRefresh();
    } catch (err: any) {
      const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message;
      alert('Failed to void: ' + message);
    } finally {
      setActionLoading(null);
    }
  };

  const getStatusChip = (status: string) => {
    const statusConfig: Record<string, { label: string; color: any; icon: any }> = {
      completed: { label: 'Completed', color: 'success', icon: <CheckCircle fontSize="small" /> },
      sent: { label: 'Sent', color: 'info', icon: <Send fontSize="small" /> },
      delivered: { label: 'Delivered', color: 'primary', icon: <Send fontSize="small" /> },
      signed: { label: 'Signed', color: 'success', icon: <CheckCircle fontSize="small" /> },
      declined: { label: 'Declined', color: 'error', icon: <Cancel fontSize="small" /> },
      voided: { label: 'Voided', color: 'error', icon: <Cancel fontSize="small" /> },
      created: { label: 'Created', color: 'default', icon: <Schedule fontSize="small" /> },
    };

    const config = statusConfig[status] || { label: status, color: 'default', icon: null };
    return <Chip label={config.label} color={config.color} size="small" icon={config.icon} />;
  };

  const getRecipientStatus = (recipient: any) => {
    if (recipient.status === 'completed') return 'Signed';
    if (recipient.status === 'sent') return 'Pending';
    if (recipient.status === 'delivered') return 'Delivered';
    if (recipient.status === 'declined') return 'Declined';
    return recipient.status || 'Unknown';
  };

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="200px">
        <CircularProgress />
      </Box>
    );
  }

  if (error) {
    return (
      <Alert severity="error" onClose={() => setError(null)}>
        {error}
      </Alert>
    );
  }

  if (!envelope) {
    return (
      <Alert severity="warning">
        Envelope not found
      </Alert>
    );
  }

  const canResend = envelope.status === 'sent' || envelope.status === 'delivered';
  const canVoid = envelope.status !== 'completed' && envelope.status !== 'voided' && envelope.status !== 'declined';

  return (
    <Paper elevation={2} sx={{ p: 3 }}>
      <Stack spacing={3}>
        <Box>
          <Stack direction="row" justifyContent="space-between" alignItems="flex-start" spacing={2}>
            <Box>
              <Typography variant="h6" gutterBottom>
                {envelope.subject}
              </Typography>
              {envelope.message && (
                <Typography variant="body2" color="text.secondary">
                  {envelope.message}
                </Typography>
              )}
            </Box>
            {getStatusChip(envelope.status)}
          </Stack>
        </Box>

        <Divider />

        <Stack spacing={1}>
          <Typography variant="subtitle2" color="text.secondary">
            Timeline
          </Typography>
          <Stack spacing={0.5}>
            {envelope.sent_at && (
              <Typography variant="body2">
                <strong>Sent:</strong> {formatDateTime(envelope.sent_at)}
              </Typography>
            )}
            {envelope.completed_at && (
              <Typography variant="body2">
                <strong>Completed:</strong> {formatDateTime(envelope.completed_at)}
              </Typography>
            )}
            {envelope.voided_at && (
              <Typography variant="body2">
                <strong>Voided:</strong> {formatDateTime(envelope.voided_at)}
              </Typography>
            )}
            {envelope.voided_reason && (
              <Typography variant="body2" color="error">
                <strong>Void Reason:</strong> {envelope.voided_reason}
              </Typography>
            )}
          </Stack>
        </Stack>

        {envelope.recipients && envelope.recipients.length > 0 && (
          <>
            <Divider />
            <Box>
              <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                Recipients
              </Typography>
              <List dense disablePadding>
                {envelope.recipients.map((recipient, index) => {
                  const dsRecipient = docusignStatus && docusignStatus.recipients && docusignStatus.recipients.signers ? docusignStatus.recipients.signers.find(
                    (s: any) => s.email === recipient.email
                  ) : null;
                  return (
                    <ListItem key={index} disablePadding sx={{ py: 1 }}>
                      <ListItemText
                        primary={
                          <Stack direction="row" alignItems="center" spacing={1}>
                            <Typography variant="body2">
                              {recipient.name} ({recipient.role})
                            </Typography>
                            {recipient.routing_order && (
                              <Chip label={'Order ' + recipient.routing_order} size="small" variant="outlined" />
                            )}
                          </Stack>
                        }
                        secondary={
                          <Stack direction="row" spacing={2} alignItems="center">
                            <span>{recipient.email}</span>
                            {dsRecipient && (
                              <>
                                <span>•</span>
                                <span>{getRecipientStatus(dsRecipient)}</span>
                                {dsRecipient.signedDateTime && (
                                  <>
                                    <span>•</span>
                                    <span>Signed: {new Date(dsRecipient.signedDateTime).toLocaleString()}</span>
                                  </>
                                )}
                              </>
                            )}
                          </Stack>
                        }
                      />
                    </ListItem>
                  );
                })}
              </List>
            </Box>
          </>
        )}

        {docusignStatus && docusignStatus.envelopeUri && (
          <>
            <Divider />
            <Link
              href={docusignStatus.envelopeUri}
              target="_blank"
              rel="noopener noreferrer"
              sx={{ display: 'flex', alignItems: 'center', gap: 1 }}
            >
              <OpenInNew fontSize="small" />
              View in DocuSign
            </Link>
          </>
        )}

        {(canResend || canVoid) && (
          <>
            <Divider />
            <Stack direction="row" spacing={2}>
              {canResend && (
                <Button
                  variant="outlined"
                  onClick={handleResend}
                  disabled={actionLoading === 'resend'}
                  startIcon={actionLoading === 'resend' ? <CircularProgress size={16} /> : <Send />}
                >
                  {actionLoading === 'resend' ? 'Resending...' : 'Resend'}
                </Button>
              )}
              {canVoid && (
                <Button
                  variant="outlined"
                  color="error"
                  onClick={handleVoid}
                  disabled={actionLoading === 'void'}
                  startIcon={actionLoading === 'void' ? <CircularProgress size={16} /> : <Cancel />}
                >
                  {actionLoading === 'void' ? 'Voiding...' : 'Void'}
                </Button>
              )}
            </Stack>
          </>
        )}
      </Stack>
    </Paper>
  );
}
