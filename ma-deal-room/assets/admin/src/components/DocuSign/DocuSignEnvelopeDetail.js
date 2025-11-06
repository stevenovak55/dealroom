import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
/**
 * DocuSignEnvelopeDetail Component
 *
 * Displays detailed envelope information in a modal dialog.
 * Shows recipients, documents, timeline, and action buttons.
 */
import { useState, useEffect } from 'react';
import { Dialog, DialogTitle, DialogContent, DialogActions, Button, CircularProgress, Alert, Typography, Stack, Divider, Table, TableBody, TableCell, TableContainer, TableHead, TableRow, Paper, Chip, List, ListItem, ListItemText, Box, IconButton, } from '@mui/material';
import { Close, CheckCircle, Schedule, Cancel, Send, Description, } from '@mui/icons-material';
import { docusignService } from '../../api/docusignService';
import { formatDateTime } from '../../utils/formatDate';
/**
 * DocuSignEnvelopeDetail - Modal showing detailed envelope information
 */
export default function DocuSignEnvelopeDetail({ envelopeId, onClose }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [envelope, setEnvelope] = useState(null);
    const [docusignStatus, setDocusignStatus] = useState(null);
    const [actionLoading, setActionLoading] = useState(null);
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
        }
        catch (err) {
            const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message || 'Failed to load envelope';
            setError(message);
        }
        finally {
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
        }
        catch (err) {
            const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message;
            alert('Failed to resend: ' + message);
        }
        finally {
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
        }
        catch (err) {
            const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message;
            alert('Failed to void: ' + message);
        }
        finally {
            setActionLoading(null);
        }
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
    const getRecipientStatusChip = (status) => {
        if (status === 'completed')
            return _jsx(Chip, { label: "Signed", color: "success", size: "small" });
        if (status === 'sent')
            return _jsx(Chip, { label: "Pending", color: "warning", size: "small" });
        if (status === 'delivered')
            return _jsx(Chip, { label: "Delivered", color: "info", size: "small" });
        if (status === 'declined')
            return _jsx(Chip, { label: "Declined", color: "error", size: "small" });
        return _jsx(Chip, { label: status || 'Unknown', size: "small" });
    };
    const canResend = envelope && (envelope.status === 'sent' || envelope.status === 'delivered');
    const canVoid = envelope && envelope.status !== 'completed' && envelope.status !== 'voided' && envelope.status !== 'declined';
    return (_jsxs(Dialog, { open: !!envelopeId, onClose: onClose, maxWidth: "md", fullWidth: true, children: [_jsx(DialogTitle, { children: _jsxs(Stack, { direction: "row", justifyContent: "space-between", alignItems: "center", children: [_jsx(Typography, { variant: "h6", children: "Envelope Details" }), _jsx(IconButton, { onClick: onClose, size: "small", children: _jsx(Close, {}) })] }) }), _jsx(DialogContent, { dividers: true, children: loading ? (_jsx(Box, { display: "flex", justifyContent: "center", alignItems: "center", minHeight: "300px", children: _jsx(CircularProgress, {}) })) : error ? (_jsx(Alert, { severity: "error", children: error })) : !envelope ? (_jsx(Alert, { severity: "warning", children: "Envelope not found" })) : (_jsxs(Stack, { spacing: 3, children: [_jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle2", color: "text.secondary", gutterBottom: true, children: "Subject" }), _jsx(Typography, { variant: "body1", children: envelope.subject })] }), envelope.message && (_jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle2", color: "text.secondary", gutterBottom: true, children: "Message" }), _jsx(Typography, { variant: "body2", children: envelope.message })] })), _jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle2", color: "text.secondary", gutterBottom: true, children: "Status" }), getStatusChip(envelope.status)] }), _jsx(Divider, {}), _jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle2", color: "text.secondary", gutterBottom: true, children: "Timeline" }), _jsxs(List, { dense: true, children: [_jsx(ListItem, { children: _jsx(ListItemText, { primary: "Created", secondary: formatDateTime(envelope.created_at) }) }), envelope.sent_at && (_jsx(ListItem, { children: _jsx(ListItemText, { primary: "Sent", secondary: formatDateTime(envelope.sent_at) }) })), envelope.completed_at && (_jsx(ListItem, { children: _jsx(ListItemText, { primary: "Completed", secondary: formatDateTime(envelope.completed_at) }) })), envelope.voided_at && (_jsx(ListItem, { children: _jsx(ListItemText, { primary: "Voided", secondary: formatDateTime(envelope.voided_at) }) }))] }), envelope.voided_reason && (_jsx(Alert, { severity: "error", sx: { mt: 1 }, children: _jsxs(Typography, { variant: "body2", children: [_jsx("strong", { children: "Void Reason:" }), " ", envelope.voided_reason] }) }))] }), _jsx(Divider, {}), envelope.recipients && envelope.recipients.length > 0 && (_jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle2", color: "text.secondary", gutterBottom: true, children: "Recipients" }), _jsx(TableContainer, { component: Paper, variant: "outlined", children: _jsxs(Table, { size: "small", children: [_jsx(TableHead, { children: _jsxs(TableRow, { children: [_jsx(TableCell, { children: "Name" }), _jsx(TableCell, { children: "Email" }), _jsx(TableCell, { children: "Role" }), _jsx(TableCell, { children: "Order" }), _jsx(TableCell, { children: "Status" })] }) }), _jsx(TableBody, { children: envelope.recipients.map((recipient, index) => {
                                                    const dsRecipient = docusignStatus && docusignStatus.recipients && docusignStatus.recipients.signers ? docusignStatus.recipients.signers.find((s) => s.email === recipient.email) : null;
                                                    return (_jsxs(TableRow, { children: [_jsx(TableCell, { children: recipient.name }), _jsx(TableCell, { children: recipient.email }), _jsx(TableCell, { children: recipient.role }), _jsx(TableCell, { children: recipient.routing_order || '-' }), _jsx(TableCell, { children: dsRecipient ? getRecipientStatusChip(dsRecipient.status) : '-' })] }, index));
                                                }) })] }) })] })), envelope.documents && envelope.documents.length > 0 && (_jsxs(_Fragment, { children: [_jsx(Divider, {}), _jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle2", color: "text.secondary", gutterBottom: true, children: "Documents" }), _jsx(List, { dense: true, children: envelope.documents.map((doc, index) => (_jsxs(ListItem, { children: [_jsx(Description, { fontSize: "small", sx: { mr: 1 } }), _jsx(ListItemText, { primary: doc.name || 'Document ' + (index + 1), secondary: doc.documentId ? 'ID: ' + doc.documentId : null })] }, index))) })] })] }))] })) }), _jsxs(DialogActions, { children: [canResend && (_jsx(Button, { onClick: handleResend, disabled: actionLoading === 'resend', startIcon: actionLoading === 'resend' ? _jsx(CircularProgress, { size: 16 }) : _jsx(Send, {}), children: actionLoading === 'resend' ? 'Resending...' : 'Resend' })), canVoid && !showVoidConfirm && (_jsx(Button, { onClick: () => setShowVoidConfirm(true), color: "error", startIcon: _jsx(Cancel, {}), children: "Void" })), showVoidConfirm && (_jsxs(_Fragment, { children: [_jsx("input", { type: "text", placeholder: "Reason for voiding...", value: voidReason, onChange: (e) => setVoidReason(e.target.value), style: { padding: '8px', border: '1px solid #ccc', borderRadius: '4px', flexGrow: 1 } }), _jsx(Button, { onClick: () => setShowVoidConfirm(false), children: "Cancel" }), _jsx(Button, { onClick: handleVoid, color: "error", disabled: actionLoading === 'void', startIcon: actionLoading === 'void' ? _jsx(CircularProgress, { size: 16 }) : null, children: actionLoading === 'void' ? 'Voiding...' : 'Confirm Void' })] })), !showVoidConfirm && (_jsx(Button, { onClick: onClose, children: "Close" }))] })] }));
}
