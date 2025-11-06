import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
/**
 * DocuSignTemplateSelector Component
 *
 * Allows users to browse and select DocuSign templates for envelope creation.
 * Displays templates in a searchable grid with preview option.
 */
import { useState, useEffect } from 'react';
import { Box, Card, CardContent, CardActions, Button, TextField, CircularProgress, Alert, Typography, Chip, Stack, InputAdornment, } from '@mui/material';
import { Search, Description, Visibility, CheckCircle, } from '@mui/icons-material';
import { docusignService } from '../../api/docusignService';
/**
 * DocuSignTemplateSelector - Grid of selectable DocuSign templates
 */
export default function DocuSignTemplateSelector({ onSelect, transactionId, }) {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [templates, setTemplates] = useState([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedTemplateId, setSelectedTemplateId] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(null);
    useEffect(() => {
        loadTemplates();
    }, []);
    const loadTemplates = async (search) => {
        try {
            setLoading(true);
            setError(null);
            const data = await docusignService.listTemplates(search);
            setTemplates(data.templates || []);
        }
        catch (err) {
            const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message || 'Failed to load templates';
            setError(message);
        }
        finally {
            setLoading(false);
        }
    };
    const handleSearch = (query) => {
        setSearchQuery(query);
        loadTemplates(query);
    };
    const handlePreview = async (templateId) => {
        if (!transactionId) {
            alert('Transaction ID is required for template preview');
            return;
        }
        try {
            setPreviewLoading(templateId);
            const preview = await docusignService.previewTemplate(templateId, transactionId);
            console.log('Template preview:', preview);
            alert('Preview data logged to console');
        }
        catch (err) {
            const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message;
            alert('Failed to preview template: ' + message);
        }
        finally {
            setPreviewLoading(null);
        }
    };
    const handleSelectTemplate = (templateId) => {
        setSelectedTemplateId(templateId);
        onSelect(templateId);
    };
    const formatDate = (dateString) => {
        if (!dateString)
            return 'Unknown';
        try {
            return new Date(dateString).toLocaleDateString();
        }
        catch {
            return dateString;
        }
    };
    if (loading && templates.length === 0) {
        return (_jsx(Box, { display: "flex", justifyContent: "center", alignItems: "center", minHeight: "400px", children: _jsx(CircularProgress, {}) }));
    }
    if (error) {
        return (_jsx(Alert, { severity: "error", onClose: () => setError(null), children: error }));
    }
    return (_jsx(Box, { children: _jsxs(Stack, { spacing: 3, children: [_jsx(TextField, { fullWidth: true, label: "Search Templates", placeholder: "Search by template name...", value: searchQuery, onChange: (e) => handleSearch(e.target.value), InputProps: {
                        startAdornment: (_jsx(InputAdornment, { position: "start", children: _jsx(Search, {}) })),
                        endAdornment: loading ? (_jsx(InputAdornment, { position: "end", children: _jsx(CircularProgress, { size: 20 }) })) : null,
                    } }), templates.length === 0 ? (_jsx(Alert, { severity: "info", children: searchQuery ? 'No templates match your search' : 'No templates available' })) : (_jsxs(_Fragment, { children: [_jsxs(Typography, { variant: "body2", color: "text.secondary", children: [templates.length, " template(s) found"] }), _jsx(Box, { display: "grid", gridTemplateColumns: { xs: '1fr', sm: '1fr 1fr', md: 'repeat(3, 1fr)' }, gap: 3, children: templates.map((template) => (_jsx(Box, { children: _jsxs(Card, { variant: "outlined", sx: {
                                        height: '100%',
                                        display: 'flex',
                                        flexDirection: 'column',
                                        border: selectedTemplateId === template.templateId ? 2 : 1,
                                        borderColor: selectedTemplateId === template.templateId ? 'primary.main' : 'divider',
                                    }, children: [_jsx(CardContent, { sx: { flexGrow: 1 }, children: _jsxs(Stack, { spacing: 2, children: [_jsxs(Stack, { direction: "row", justifyContent: "space-between", alignItems: "flex-start", children: [_jsx(Description, { color: "primary", sx: { fontSize: 40 } }), selectedTemplateId === template.templateId && (_jsx(Chip, { label: "Selected", color: "primary", size: "small", icon: _jsx(CheckCircle, {}) }))] }), _jsxs(Box, { children: [_jsx(Typography, { variant: "h6", gutterBottom: true, noWrap: true, title: template.name, children: template.name }), template.description && (_jsx(Typography, { variant: "body2", color: "text.secondary", sx: {
                                                                    display: '-webkit-box',
                                                                    WebkitLineClamp: 3,
                                                                    WebkitBoxOrient: 'vertical',
                                                                    overflow: 'hidden',
                                                                }, children: template.description }))] }), _jsxs(Stack, { spacing: 0.5, children: [template.lastModified && (_jsxs(Typography, { variant: "caption", color: "text.secondary", children: ["Modified: ", formatDate(template.lastModified)] })), template.owner && template.owner.name && (_jsxs(Typography, { variant: "caption", color: "text.secondary", children: ["Owner: ", template.owner.name] })), template.shared && (_jsx(Chip, { label: "Shared", size: "small", variant: "outlined" }))] })] }) }), _jsxs(CardActions, { sx: { justifyContent: 'space-between', p: 2 }, children: [transactionId && (_jsx(Button, { size: "small", variant: "outlined", onClick: () => handlePreview(template.templateId), disabled: previewLoading === template.templateId, startIcon: previewLoading === template.templateId ? (_jsx(CircularProgress, { size: 16 })) : (_jsx(Visibility, {})), children: "Preview" })), _jsx(Button, { size: "small", variant: selectedTemplateId === template.templateId ? 'contained' : 'outlined', onClick: () => handleSelectTemplate(template.templateId), sx: { ml: 'auto' }, children: selectedTemplateId === template.templateId ? 'Selected' : 'Select' })] })] }) }, template.templateId))) })] }))] }) }));
}
