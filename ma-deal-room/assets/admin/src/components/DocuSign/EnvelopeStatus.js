import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
/**
 * EnvelopeStatus Component
 *
 * Displays DocuSign envelope status on transaction detail pages.
 * Shows envelope status, recipients, timestamps, and action buttons.
 */
import { useState, useEffect } from 'react';
import { Box, Chip, List, ListItem, ListItemText, Button, CircularProgress, Alert, Typography, Stack, Divider, Paper, Link, } from '@mui/material';
import { CheckCircle, Schedule, Cancel, Send, OpenInNew, } from '@mui/icons-material';
import { docusignService } from '../../api/docusignService';
import { formatDateTime } from '../../utils/formatDate';
/**
 * EnvelopeStatus - Display envelope status with recipient information and actions
 */
export default function EnvelopeStatus({ envelopeId, onRefresh }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [envelope, setEnvelope] = useState(null);
    const [docusignStatus, setDocusignStatus] = useState(null);
    const [actionLoading, setActionLoading] = useState(null);
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
            if (onRefresh)
                onRefresh();
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
        const reason = prompt('Enter reason for voiding this envelope:');
        if (!reason) {
            return;
        }
        try {
            setActionLoading('void');
            await docusignService.voidEnvelope(envelopeId, reason);
            alert('Envelope voided successfully!');
            await loadEnvelope();
            if (onRefresh)
                onRefresh();
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
    const getRecipientStatus = (recipient) => {
        if (recipient.status === 'completed')
            return 'Signed';
        if (recipient.status === 'sent')
            return 'Pending';
        if (recipient.status === 'delivered')
            return 'Delivered';
        if (recipient.status === 'declined')
            return 'Declined';
        return recipient.status || 'Unknown';
    };
    if (loading) {
        return (_jsx(Box, { display: "flex", justifyContent: "center", alignItems: "center", minHeight: "200px", children: _jsx(CircularProgress, {}) }));
    }
    if (error) {
        return (_jsx(Alert, { severity: "error", onClose: () => setError(null), children: error }));
    }
    if (!envelope) {
        return (_jsx(Alert, { severity: "warning", children: "Envelope not found" }));
    }
    const canResend = envelope.status === 'sent' || envelope.status === 'delivered';
    const canVoid = envelope.status !== 'completed' && envelope.status !== 'voided' && envelope.status !== 'declined';
    return (_jsx(Paper, { elevation: 2, sx: { p: 3 }, children: _jsxs(Stack, { spacing: 3, children: [_jsx(Box, { children: _jsxs(Stack, { direction: "row", justifyContent: "space-between", alignItems: "flex-start", spacing: 2, children: [_jsxs(Box, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, children: envelope.subject }), envelope.message && (_jsx(Typography, { variant: "body2", color: "text.secondary", children: envelope.message }))] }), getStatusChip(envelope.status)] }) }), _jsx(Divider, {}), _jsxs(Stack, { spacing: 1, children: [_jsx(Typography, { variant: "subtitle2", color: "text.secondary", children: "Timeline" }), _jsxs(Stack, { spacing: 0.5, children: [envelope.sent_at && (_jsxs(Typography, { variant: "body2", children: [_jsx("strong", { children: "Sent:" }), " ", formatDateTime(envelope.sent_at)] })), envelope.completed_at && (_jsxs(Typography, { variant: "body2", children: [_jsx("strong", { children: "Completed:" }), " ", formatDateTime(envelope.completed_at)] })), envelope.voided_at && (_jsxs(Typography, { variant: "body2", children: [_jsx("strong", { children: "Voided:" }), " ", formatDateTime(envelope.voided_at)] })), envelope.voided_reason && (_jsxs(Typography, { variant: "body2", color: "error", children: [_jsx("strong", { children: "Void Reason:" }), " ", envelope.voided_reason] }))] })] }), envelope.recipients && envelope.recipients.length > 0 && (_jsxs(_Fragment, { children: [_jsx(Divider, {}), _jsxs(Box, { children: [_jsx(Typography, { variant: "subtitle2", color: "text.secondary", gutterBottom: true, children: "Recipients" }), _jsx(List, { dense: true, disablePadding: true, children: envelope.recipients.map((recipient, index) => {
                                        const dsRecipient = docusignStatus && docusignStatus.recipients && docusignStatus.recipients.signers ? docusignStatus.recipients.signers.find((s) => s.email === recipient.email) : null;
                                        return (_jsx(ListItem, { disablePadding: true, sx: { py: 1 }, children: _jsx(ListItemText, { primary: _jsxs(Stack, { direction: "row", alignItems: "center", spacing: 1, children: [_jsxs(Typography, { variant: "body2", children: [recipient.name, " (", recipient.role, ")"] }), recipient.routing_order && (_jsx(Chip, { label: 'Order ' + recipient.routing_order, size: "small", variant: "outlined" }))] }), secondary: _jsxs(Stack, { direction: "row", spacing: 2, alignItems: "center", children: [_jsx("span", { children: recipient.email }), dsRecipient && (_jsxs(_Fragment, { children: [_jsx("span", { children: "\u2022" }), _jsx("span", { children: getRecipientStatus(dsRecipient) }), dsRecipient.signedDateTime && (_jsxs(_Fragment, { children: [_jsx("span", { children: "\u2022" }), _jsxs("span", { children: ["Signed: ", new Date(dsRecipient.signedDateTime).toLocaleString()] })] }))] }))] }) }) }, index));
                                    }) })] })] })), docusignStatus && docusignStatus.envelopeUri && (_jsxs(_Fragment, { children: [_jsx(Divider, {}), _jsxs(Link, { href: docusignStatus.envelopeUri, target: "_blank", rel: "noopener noreferrer", sx: { display: 'flex', alignItems: 'center', gap: 1 }, children: [_jsx(OpenInNew, { fontSize: "small" }), "View in DocuSign"] })] })), (canResend || canVoid) && (_jsxs(_Fragment, { children: [_jsx(Divider, {}), _jsxs(Stack, { direction: "row", spacing: 2, children: [canResend && (_jsx(Button, { variant: "outlined", onClick: handleResend, disabled: actionLoading === 'resend', startIcon: actionLoading === 'resend' ? _jsx(CircularProgress, { size: 16 }) : _jsx(Send, {}), children: actionLoading === 'resend' ? 'Resending...' : 'Resend' })), canVoid && (_jsx(Button, { variant: "outlined", color: "error", onClick: handleVoid, disabled: actionLoading === 'void', startIcon: actionLoading === 'void' ? _jsx(CircularProgress, { size: 16 }) : _jsx(Cancel, {}), children: actionLoading === 'void' ? 'Voiding...' : 'Void' }))] })] }))] }) }));
}
