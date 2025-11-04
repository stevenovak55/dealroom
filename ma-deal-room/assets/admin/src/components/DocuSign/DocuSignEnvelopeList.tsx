/**
 * DocuSignEnvelopeList Component
 * 
 * Lists all DocuSign envelopes with pagination, filtering, and search.
 */

import { useState, useEffect } from 'react';
import {
  Box,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Paper,
  Pagination,
  TextField,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
  CircularProgress,
  Alert,
  Chip,
  Stack,
  Typography,
  IconButton,
} from '@mui/material';
import {
  Visibility,
  CheckCircle,
  Schedule,
  Cancel,
  Send,
} from '@mui/icons-material';
import { docusignService, DocuSignEnvelope } from '../../api/docusignService';
import { formatDateTime } from '../../utils/formatDate';
import DocuSignEnvelopeDetail from './DocuSignEnvelopeDetail';

interface DocuSignEnvelopeListProps {
  transactionId?: number;
}

/**
 * DocuSignEnvelopeList - Display paginated list of envelopes with filters
 */
export default function DocuSignEnvelopeList({ transactionId }: DocuSignEnvelopeListProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [envelopes, setEnvelopes] = useState<DocuSignEnvelope[]>([]);
  const [filteredEnvelopes, setFilteredEnvelopes] = useState<DocuSignEnvelope[]>([]);
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedEnvelopeId, setSelectedEnvelopeId] = useState<string | null>(null);

  const itemsPerPage = 20;

  useEffect(() => {
    loadEnvelopes();
  }, [transactionId]);

  useEffect(() => {
    filterEnvelopes();
  }, [envelopes, statusFilter, searchQuery]);

  const loadEnvelopes = async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await docusignService.listEnvelopes(transactionId);
      setEnvelopes(data.envelopes || []);
    } catch (err: any) {
      const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message || 'Failed to load envelopes';
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  const filterEnvelopes = () => {
    let filtered = envelopes;

    if (statusFilter !== 'all') {
      filtered = filtered.filter((env) => env.status === statusFilter);
    }

    if (searchQuery.trim()) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(
        (env) =>
          env.subject.toLowerCase().includes(query) ||
          (env.message && env.message.toLowerCase().includes(query))
      );
    }

    setFilteredEnvelopes(filtered);
    setPage(1);
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

  const paginatedEnvelopes = filteredEnvelopes.slice(
    (page - 1) * itemsPerPage,
    page * itemsPerPage
  );

  const totalPages = Math.ceil(filteredEnvelopes.length / itemsPerPage);

  if (loading) {
    return (
      <Box display="flex" justifyContent="center" alignItems="center" minHeight="400px">
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

  return (
    <Box>
      <Stack spacing={2} sx={{ mb: 3 }}>
        <Stack direction="row" spacing={2} alignItems="center">
          <TextField
            label="Search"
            placeholder="Search by subject or message..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            size="small"
            sx={{ flexGrow: 1 }}
          />
          <FormControl size="small" sx={{ minWidth: 200 }}>
            <InputLabel>Status</InputLabel>
            <Select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              label="Status"
            >
              <MenuItem value="all">All Statuses</MenuItem>
              <MenuItem value="created">Created</MenuItem>
              <MenuItem value="sent">Sent</MenuItem>
              <MenuItem value="delivered">Delivered</MenuItem>
              <MenuItem value="signed">Signed</MenuItem>
              <MenuItem value="completed">Completed</MenuItem>
              <MenuItem value="declined">Declined</MenuItem>
              <MenuItem value="voided">Voided</MenuItem>
            </Select>
          </FormControl>
        </Stack>

        <Typography variant="body2" color="text.secondary">
          Showing {paginatedEnvelopes.length} of {filteredEnvelopes.length} envelope(s)
        </Typography>
      </Stack>

      {filteredEnvelopes.length === 0 ? (
        <Alert severity="info">
          {envelopes.length === 0
            ? 'No envelopes found'
            : 'No envelopes match the current filters'}
        </Alert>
      ) : (
        <>
          <TableContainer component={Paper}>
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Subject</TableCell>
                  <TableCell>Status</TableCell>
                  <TableCell>Transaction</TableCell>
                  <TableCell>Sent Date</TableCell>
                  <TableCell>Completed Date</TableCell>
                  <TableCell align="right">Actions</TableCell>
                </TableRow>
              </TableHead>
              <TableBody>
                {paginatedEnvelopes.map((envelope) => (
                  <TableRow
                    key={envelope.envelope_id}
                    hover
                    sx={{ cursor: 'pointer' }}
                    onClick={() => setSelectedEnvelopeId(envelope.envelope_id)}
                  >
                    <TableCell>
                      <Typography variant="body2">{envelope.subject}</Typography>
                      {envelope.message && (
                        <Typography variant="caption" color="text.secondary">
                          {envelope.message}
                        </Typography>
                      )}
                    </TableCell>
                    <TableCell>{getStatusChip(envelope.status)}</TableCell>
                    <TableCell>
                      <Typography variant="body2">#{envelope.transaction_id}</Typography>
                    </TableCell>
                    <TableCell>
                      {envelope.sent_at ? formatDateTime(envelope.sent_at) : '-'}
                    </TableCell>
                    <TableCell>
                      {envelope.completed_at ? formatDateTime(envelope.completed_at) : '-'}
                    </TableCell>
                    <TableCell align="right">
                      <IconButton
                        size="small"
                        onClick={(e) => {
                          e.stopPropagation();
                          setSelectedEnvelopeId(envelope.envelope_id);
                        }}
                      >
                        <Visibility fontSize="small" />
                      </IconButton>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>

          {totalPages > 1 && (
            <Box display="flex" justifyContent="center" sx={{ mt: 3 }}>
              <Pagination
                count={totalPages}
                page={page}
                onChange={(_, value) => setPage(value)}
                color="primary"
              />
            </Box>
          )}
        </>
      )}

      {selectedEnvelopeId && (
        <DocuSignEnvelopeDetail
          envelopeId={selectedEnvelopeId}
          onClose={() => {
            setSelectedEnvelopeId(null);
            loadEnvelopes();
          }}
        />
      )}
    </Box>
  );
}
