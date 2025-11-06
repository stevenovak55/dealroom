import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
/**
 * DocuSignEnvelopeList Component
 *
 * Lists all DocuSign envelopes with pagination, filtering, and search.
 */
import { useState, useEffect } from 'react';
import { Box, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper, Pagination, TextField, Select, MenuItem, FormControl, InputLabel, CircularProgress, Alert, Chip, Stack, Typography, IconButton, } from '@mui/material';
import { Visibility, CheckCircle, Schedule, Cancel, Send, } from '@mui/icons-material';
import { docusignService } from '../../api/docusignService';
import { formatDateTime } from '../../utils/formatDate';
import DocuSignEnvelopeDetail from './DocuSignEnvelopeDetail';
/**
 * DocuSignEnvelopeList - Display paginated list of envelopes with filters
 */
export default function DocuSignEnvelopeList({ transactionId }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [envelopes, setEnvelopes] = useState([]);
    const [filteredEnvelopes, setFilteredEnvelopes] = useState([]);
    const [page, setPage] = useState(1);
    const [statusFilter, setStatusFilter] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedEnvelopeId, setSelectedEnvelopeId] = useState(null);
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
        }
        catch (err) {
            const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message || 'Failed to load envelopes';
            setError(message);
        }
        finally {
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
            filtered = filtered.filter((env) => env.subject.toLowerCase().includes(query) ||
                (env.message && env.message.toLowerCase().includes(query)));
        }
        setFilteredEnvelopes(filtered);
        setPage(1);
    };
    const getStatusChip = (status) => {
        const statusConfig = {
            completed: { label: 'Completed', color: 'success', icon: _jsx(CheckCircle, { fontSize: "small" }) },
            sent: { label: 'Sent', color: 'info', icon: _jsx(Send, { fontSize: "small" }) },
            delivered: { label: 'Delivered', color: 'primary', icon: _jsx(Send, { fontSize: "small" }) },
            signed: { label: 'Signed', color: 'success', icon: _jsx(CheckCircle, { fontSize: "small" }) },
            declined: { label: 'Declined', color: 'error', icon: _jsx(Cancel, { fontSize: "small" }) },
            voided: { label: 'Voided', color: 'error', icon: _jsx(Cancel, { fontSize: "small" }) },
            created: { label: 'Created', color: 'default', icon: _jsx(Schedule, { fontSize: "small" }) },
        };
        const config = statusConfig[status] || { label: status, color: 'default', icon: null };
        return _jsx(Chip, { label: config.label, color: config.color, size: "small", icon: config.icon });
    };
    const paginatedEnvelopes = filteredEnvelopes.slice((page - 1) * itemsPerPage, page * itemsPerPage);
    const totalPages = Math.ceil(filteredEnvelopes.length / itemsPerPage);
    if (loading) {
        return (_jsx(Box, { display: "flex", justifyContent: "center", alignItems: "center", minHeight: "400px", children: _jsx(CircularProgress, {}) }));
    }
    if (error) {
        return (_jsx(Alert, { severity: "error", onClose: () => setError(null), children: error }));
    }
    return (_jsxs(Box, { children: [_jsxs(Stack, { spacing: 2, sx: { mb: 3 }, children: [_jsxs(Stack, { direction: "row", spacing: 2, alignItems: "center", children: [_jsx(TextField, { label: "Search", placeholder: "Search by subject or message...", value: searchQuery, onChange: (e) => setSearchQuery(e.target.value), size: "small", sx: { flexGrow: 1 } }), _jsxs(FormControl, { size: "small", sx: { minWidth: 200 }, children: [_jsx(InputLabel, { children: "Status" }), _jsxs(Select, { value: statusFilter, onChange: (e) => setStatusFilter(e.target.value), label: "Status", children: [_jsx(MenuItem, { value: "all", children: "All Statuses" }), _jsx(MenuItem, { value: "created", children: "Created" }), _jsx(MenuItem, { value: "sent", children: "Sent" }), _jsx(MenuItem, { value: "delivered", children: "Delivered" }), _jsx(MenuItem, { value: "signed", children: "Signed" }), _jsx(MenuItem, { value: "completed", children: "Completed" }), _jsx(MenuItem, { value: "declined", children: "Declined" }), _jsx(MenuItem, { value: "voided", children: "Voided" })] })] })] }), _jsxs(Typography, { variant: "body2", color: "text.secondary", children: ["Showing ", paginatedEnvelopes.length, " of ", filteredEnvelopes.length, " envelope(s)"] })] }), filteredEnvelopes.length === 0 ? (_jsx(Alert, { severity: "info", children: envelopes.length === 0
                    ? 'No envelopes found'
                    : 'No envelopes match the current filters' })) : (_jsxs(_Fragment, { children: [_jsx(TableContainer, { component: Paper, children: _jsxs(Table, { children: [_jsx(TableHead, { children: _jsxs(TableRow, { children: [_jsx(TableCell, { children: "Subject" }), _jsx(TableCell, { children: "Status" }), _jsx(TableCell, { children: "Transaction" }), _jsx(TableCell, { children: "Sent Date" }), _jsx(TableCell, { children: "Completed Date" }), _jsx(TableCell, { align: "right", children: "Actions" })] }) }), _jsx(TableBody, { children: paginatedEnvelopes.map((envelope) => (_jsxs(TableRow, { hover: true, sx: { cursor: 'pointer' }, onClick: () => setSelectedEnvelopeId(envelope.envelope_id), children: [_jsxs(TableCell, { children: [_jsx(Typography, { variant: "body2", children: envelope.subject }), envelope.message && (_jsx(Typography, { variant: "caption", color: "text.secondary", children: envelope.message }))] }), _jsx(TableCell, { children: getStatusChip(envelope.status) }), _jsx(TableCell, { children: _jsxs(Typography, { variant: "body2", children: ["#", envelope.transaction_id] }) }), _jsx(TableCell, { children: envelope.sent_at ? formatDateTime(envelope.sent_at) : '-' }), _jsx(TableCell, { children: envelope.completed_at ? formatDateTime(envelope.completed_at) : '-' }), _jsx(TableCell, { align: "right", children: _jsx(IconButton, { size: "small", onClick: (e) => {
                                                        e.stopPropagation();
                                                        setSelectedEnvelopeId(envelope.envelope_id);
                                                    }, children: _jsx(Visibility, { fontSize: "small" }) }) })] }, envelope.envelope_id))) })] }) }), totalPages > 1 && (_jsx(Box, { display: "flex", justifyContent: "center", sx: { mt: 3 }, children: _jsx(Pagination, { count: totalPages, page: page, onChange: (_, value) => setPage(value), color: "primary" }) }))] })), selectedEnvelopeId && (_jsx(DocuSignEnvelopeDetail, { envelopeId: selectedEnvelopeId, onClose: () => {
                    setSelectedEnvelopeId(null);
                    loadEnvelopes();
                } }))] }));
}
