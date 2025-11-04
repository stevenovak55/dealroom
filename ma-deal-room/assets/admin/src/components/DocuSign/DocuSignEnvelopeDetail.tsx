/**
 * DocuSignEnvelopeDetail Component
 * 
 * Displays detailed envelope information in a modal dialog.
 * Shows recipients, documents, timeline, and action buttons.
 */

import { useState, useEffect } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  Button,
  CircularProgress,
  Alert,
  Typography,
  Stack,
  Divider,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Chip,
  List,
  ListItem,
  ListItemText,
  Box,
  IconButton,
} from '@mui/material';
import {
  Close,
  CheckCircle,
  Schedule,
  Cancel,
  Send,
  Description,
} from '@mui/icons-material';
import { docusignService, DocuSignEnvelope } from '../../api/docusignService';
import { formatDateTime } from '../../utils/formatDate';

interface DocuSignEnvelopeDetailProps {
  envelopeId: string;
  onClose: () => void;
}

/**
 * DocuSignEnvelopeDetail - Modal showing detailed envelope information
 */
export default function DocuSignEnvelopeDetail({ envelopeId, onClose }: DocuSignEnvelopeDetailProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [envelope, setEnvelope] = useState<DocuSignEnvelope | null>(null);
  const [docusignStatus, setDocusignStatus] = useState<any>(null);
  const [actionLoading, setActionLoading] = useState<'resend' | 'void' | null>(null);
  const [showVoidConfirm, setShowVoidConfirm] = useState(false);
  const [voidReason, setVoidReason] = useState('');

  useEffect(() => {
    if (envelopeId) {
      loadEnvelope();
    }
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
    } catch (err: any) {
      const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message;
      alert('Failed to resend: ' + message);
    } finally {
      setActionLoading(null);
    }
  };

  const handleVoid = async () => {
    if (!voidReason.trim()) {
      alert('Please provide a reason for voiding this envelope');
      return;
    }

    try {
      setActionLoading('void');
      await docusignService.voidEnvelope(envelopeId, voidReason);
      alert('Envelope voided successfully!');
      setShowVoidConfirm(false);
      await loadEnvelope();
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

  const getRecipientStatusChip = (status: string) => {
    if (status === 'completed') return <Chip label="Signed" color="success" size="small" />;
    if (status === 'sent') return <Chip label="Pending" color="warning" size="small" />;
    if (status === 'delivered') return <Chip label="Delivered" color="info" size="small" />;
    if (status === 'declined') return <Chip label="Declined" color="error" size="small" />;
    return <Chip label={status || 'Unknown'} size="small" />;
  };

  const canResend = envelope && (envelope.status === 'sent' || envelope.status === 'delivered');
  const canVoid = envelope && envelope.status !== 'completed' && envelope.status !== 'voided' && envelope.status !== 'declined';

  return (
    <Dialog open={!!envelopeId} onClose={onClose} maxWidth="md" fullWidth>
      <DialogTitle>
        <Stack direction="row" justifyContent="space-between" alignItems="center">
          <Typography variant="h6">Envelope Details</Typography>
          <IconButton onClick={onClose} size="small">
            <Close />
          </IconButton>
        </Stack>
      </DialogTitle>

      <DialogContent dividers>
        {loading ? (
          <Box display="flex" justifyContent="center" alignItems="center" minHeight="300px">
            <CircularProgress />
          </Box>
        ) : error ? (
          <Alert severity="error">{error}</Alert>
        ) : !envelope ? (
          <Alert severity="warning">Envelope not found</Alert>
        ) : (
          <Stack spacing={3}>
            <Box>
              <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                Subject
              </Typography>
              <Typography variant="body1">{envelope.subject}</Typography>
            </Box>

            {envelope.message && (
              <Box>
                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                  Message
                </Typography>
                <Typography variant="body2">{envelope.message}</Typography>
              </Box>
            )}

            <Box>
              <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                Status
              </Typography>
              {getStatusChip(envelope.status)}
            </Box>

            <Divider />

            <Box>
              <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                Timeline
              </Typography>
              <List dense>
                <ListItem>
                  <ListItemText
                    primary="Created"
                    secondary={formatDateTime(envelope.created_at)}
                  />
                </ListItem>
                {envelope.sent_at && (
                  <ListItem>
                    <ListItemText
                      primary="Sent"
                      secondary={formatDateTime(envelope.sent_at)}
                    />
                  </ListItem>
                )}
                {envelope.completed_at && (
                  <ListItem>
                    <ListItemText
                      primary="Completed"
                      secondary={formatDateTime(envelope.completed_at)}
                    />
                  </ListItem>
                )}
                {envelope.voided_at && (
                  <ListItem>
                    <ListItemText
                      primary="Voided"
                      secondary={formatDateTime(envelope.voided_at)}
                    />
                  </ListItem>
                )}
              </List>
              {envelope.voided_reason && (
                <Alert severity="error" sx={{ mt: 1 }}>
                  <Typography variant="body2">
                    <strong>Void Reason:</strong> {envelope.voided_reason}
                  </Typography>
                </Alert>
              )}
            </Box>

            <Divider />

            {envelope.recipients && envelope.recipients.length > 0 && (
              <Box>
                <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                  Recipients
                </Typography>
                <TableContainer component={Paper} variant="outlined">
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell>Name</TableCell>
                        <TableCell>Email</TableCell>
                        <TableCell>Role</TableCell>
                        <TableCell>Order</TableCell>
                        <TableCell>Status</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {envelope.recipients.map((recipient, index) => {
                        const dsRecipient = docusignStatus && docusignStatus.recipients && docusignStatus.recipients.signers ? docusignStatus.recipients.signers.find(
                          (s: any) => s.email === recipient.email
                        ) : null;
                        return (
                          <TableRow key={index}>
                            <TableCell>{recipient.name}</TableCell>
                            <TableCell>{recipient.email}</TableCell>
                            <TableCell>{recipient.role}</TableCell>
                            <TableCell>{recipient.routing_order || '-'}</TableCell>
                            <TableCell>
                              {dsRecipient ? getRecipientStatusChip(dsRecipient.status) : '-'}
                            </TableCell>
                          </TableRow>
                        );
                      })}
                    </TableBody>
                  </Table>
                </TableContainer>
              </Box>
            )}

            {envelope.documents && envelope.documents.length > 0 && (
              <>
                <Divider />
                <Box>
                  <Typography variant="subtitle2" color="text.secondary" gutterBottom>
                    Documents
                  </Typography>
                  <List dense>
                    {envelope.documents.map((doc: any, index: number) => (
                      <ListItem key={index}>
                        <Description fontSize="small" sx={{ mr: 1 }} />
                        <ListItemText
                          primary={doc.name || 'Document ' + (index + 1)}
                          secondary={doc.documentId ? 'ID: ' + doc.documentId : null}
                        />
                      </ListItem>
                    ))}
                  </List>
                </Box>
              </>
            )}
          </Stack>
        )}
      </DialogContent>

      <DialogActions>
        {canResend && (
          <Button
            onClick={handleResend}
            disabled={actionLoading === 'resend'}
            startIcon={actionLoading === 'resend' ? <CircularProgress size={16} /> : <Send />}
          >
            {actionLoading === 'resend' ? 'Resending...' : 'Resend'}
          </Button>
        )}
        {canVoid && !showVoidConfirm && (
          <Button
            onClick={() => setShowVoidConfirm(true)}
            color="error"
            startIcon={<Cancel />}
          >
            Void
          </Button>
        )}
        {showVoidConfirm && (
          <>
            <input
              type="text"
              placeholder="Reason for voiding..."
              value={voidReason}
              onChange={(e) => setVoidReason(e.target.value)}
              style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px', flexGrow: 1 }}
            />
            <Button onClick={() => setShowVoidConfirm(false)}>Cancel</Button>
            <Button
              onClick={handleVoid}
              color="error"
              disabled={actionLoading === 'void'}
              startIcon={actionLoading === 'void' ? <CircularProgress size={16} /> : null}
            >
              {actionLoading === 'void' ? 'Voiding...' : 'Confirm Void'}
            </Button>
          </>
        )}
        {!showVoidConfirm && (
          <Button onClick={onClose}>Close</Button>
        )}
      </DialogActions>
    </Dialog>
  );
}
