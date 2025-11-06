import { jsx as _jsx, jsxs as _jsxs, Fragment as _Fragment } from "react/jsx-runtime";
import { useState, useEffect } from 'react';
import { X, Plus, Check, AlertCircle, Trash2, Settings, Loader } from 'lucide-react';
import { mlsService } from '@/api/mlsService';
export const MLSConfigModal = ({ isOpen, onClose, onConfigSaved }) => {
    const [configs, setConfigs] = useState([]);
    const [providers, setProviders] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    // Form state
    const [showForm, setShowForm] = useState(false);
    const [editingConfig, setEditingConfig] = useState(null);
    const [formData, setFormData] = useState({
        name: '',
        provider_type: '',
        is_active: true,
        credentials: {},
        settings: {},
    });
    const [providerFields, setProviderFields] = useState([]);
    const [testingConnection, setTestingConnection] = useState(false);
    useEffect(() => {
        if (isOpen) {
            loadConfigs();
            loadProviders();
        }
    }, [isOpen]);
    useEffect(() => {
        if (formData.provider_type) {
            loadProviderFields(formData.provider_type);
        }
    }, [formData.provider_type]);
    const loadConfigs = async () => {
        try {
            setIsLoading(true);
            const response = await mlsService.listConfigs();
            if (response.data) {
                setConfigs(response.data);
            }
        }
        catch (err) {
            console.error('Failed to load configs:', err);
            setError('Failed to load configurations');
        }
        finally {
            setIsLoading(false);
        }
    };
    const loadProviders = async () => {
        try {
            const response = await mlsService.getProviders();
            if (response.success) {
                setProviders(response.data || []);
            }
        }
        catch (err) {
            console.error('Failed to load providers:', err);
        }
    };
    const loadProviderFields = async (providerType) => {
        try {
            const response = await mlsService.getProviderFields(providerType);
            if (response.success && response.data) {
                setProviderFields(response.data);
            }
        }
        catch (err) {
            console.error('Failed to load provider fields:', err);
        }
    };
    const handleCreateNew = () => {
        setEditingConfig(null);
        setFormData({
            name: '',
            provider_type: '',
            is_active: true,
            credentials: {},
            settings: {},
        });
        setProviderFields([]);
        setShowForm(true);
        setError(null);
        setSuccess(null);
    };
    const handleEdit = (config) => {
        setEditingConfig(config);
        setFormData({
            name: config.name,
            provider_type: config.provider_type,
            is_active: config.is_active,
            credentials: config.credentials || {},
            settings: config.settings || {},
        });
        setShowForm(true);
        setError(null);
        setSuccess(null);
    };
    const handleTestConnection = async () => {
        setTestingConnection(true);
        setError(null);
        setSuccess(null);
        try {
            const response = await mlsService.testConnection(formData.provider_type, formData.credentials);
            if (response.success) {
                setSuccess('Connection successful!');
            }
            else {
                setError(response.message || 'Connection test failed');
            }
        }
        catch (err) {
            setError(err.message || 'Failed to test connection');
        }
        finally {
            setTestingConnection(false);
        }
    };
    const handleSubmit = async (e) => {
        e.preventDefault();
        setError(null);
        setSuccess(null);
        try {
            setIsLoading(true);
            const configData = {
                name: formData.name,
                provider_type: formData.provider_type,
                is_active: formData.is_active,
                credentials: formData.credentials,
                settings: formData.settings,
            };
            let response;
            if (editingConfig) {
                response = await mlsService.updateConfig(editingConfig.id, configData);
            }
            else {
                response = await mlsService.createConfig(configData);
            }
            if (response.success) {
                setSuccess(editingConfig ? 'Configuration updated!' : 'Configuration created!');
                setShowForm(false);
                await loadConfigs();
                if (onConfigSaved) {
                    onConfigSaved();
                }
            }
            else {
                setError(response.message || 'Failed to save configuration');
            }
        }
        catch (err) {
            setError(err.message || 'Failed to save configuration');
        }
        finally {
            setIsLoading(false);
        }
    };
    const handleDelete = async (configId) => {
        if (!confirm('Are you sure you want to delete this configuration?')) {
            return;
        }
        try {
            setIsLoading(true);
            const response = await mlsService.deleteConfig(configId);
            if (response.success) {
                setSuccess('Configuration deleted');
                await loadConfigs();
                if (onConfigSaved) {
                    onConfigSaved();
                }
            }
            else {
                setError(response.message || 'Failed to delete configuration');
            }
        }
        catch (err) {
            setError(err.message || 'Failed to delete configuration');
        }
        finally {
            setIsLoading(false);
        }
    };
    const handleCredentialChange = (fieldName, value) => {
        setFormData(prev => ({
            ...prev,
            credentials: {
                ...prev.credentials,
                [fieldName]: value,
            },
        }));
    };
    if (!isOpen)
        return null;
    return (_jsx("div", { className: "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4", children: _jsxs("div", { className: "bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col", children: [_jsxs("div", { className: "flex items-center justify-between p-6 border-b", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx(Settings, { className: "h-6 w-6 text-primary-600" }), _jsx("h2", { className: "text-2xl font-bold text-gray-900", children: "MLS Configuration" })] }), _jsx("button", { onClick: onClose, className: "p-2 hover:bg-gray-100 rounded-lg transition-colors", children: _jsx(X, { className: "h-5 w-5" }) })] }), _jsxs("div", { className: "flex-1 overflow-y-auto p-6", children: [error && (_jsxs("div", { className: "mb-4 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3", children: [_jsx(AlertCircle, { className: "h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" }), _jsx("p", { className: "text-red-800", children: error })] })), success && (_jsxs("div", { className: "mb-4 bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3", children: [_jsx(Check, { className: "h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" }), _jsx("p", { className: "text-green-800", children: success })] })), showForm ? (_jsxs("form", { onSubmit: handleSubmit, className: "space-y-6", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Configuration Name *" }), _jsx("input", { type: "text", value: formData.name, onChange: (e) => setFormData({ ...formData, name: e.target.value }), className: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent", placeholder: "e.g., MLSPin Production", required: true })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Provider Type *" }), _jsxs("select", { value: formData.provider_type, onChange: (e) => setFormData({ ...formData, provider_type: e.target.value, credentials: {} }), className: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent", required: true, disabled: !!editingConfig, children: [_jsx("option", { value: "", children: "Select a provider..." }), providers.map((provider) => (_jsx("option", { value: provider.type, children: provider.name }, provider.type)))] }), formData.provider_type && (_jsx("p", { className: "mt-1 text-sm text-gray-500", children: providers.find(p => p.type === formData.provider_type)?.description }))] }), providerFields.map((field) => (_jsxs("div", { children: [_jsxs("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: [field.label, " ", field.required && '*'] }), field.type === 'password' ? (_jsx("input", { type: "password", value: formData.credentials[field.name] || '', onChange: (e) => handleCredentialChange(field.name, e.target.value), placeholder: field.placeholder, className: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent", required: field.required })) : field.type === 'textarea' ? (_jsx("textarea", { value: formData.credentials[field.name] || '', onChange: (e) => handleCredentialChange(field.name, e.target.value), placeholder: field.placeholder, className: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent", rows: 4, required: field.required })) : (_jsx("input", { type: field.type === 'url' ? 'url' : 'text', value: formData.credentials[field.name] || '', onChange: (e) => handleCredentialChange(field.name, e.target.value), placeholder: field.placeholder, className: "w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent", required: field.required })), field.description && (_jsx("p", { className: "mt-1 text-sm text-gray-500", children: field.description }))] }, field.name))), _jsxs("div", { className: "flex items-center", children: [_jsx("input", { type: "checkbox", id: "is_active", checked: formData.is_active, onChange: (e) => setFormData({ ...formData, is_active: e.target.checked }), className: "h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded" }), _jsx("label", { htmlFor: "is_active", className: "ml-2 block text-sm text-gray-700", children: "Set as active configuration" })] }), _jsxs("div", { className: "flex gap-3 pt-4 border-t", children: [_jsx("button", { type: "button", onClick: () => setShowForm(false), className: "px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50", children: "Cancel" }), _jsx("button", { type: "button", onClick: handleTestConnection, disabled: !formData.provider_type || Object.keys(formData.credentials).length === 0 || testingConnection, className: "px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2", children: testingConnection ? (_jsxs(_Fragment, { children: [_jsx(Loader, { className: "h-4 w-4 animate-spin" }), "Testing..."] })) : ('Test Connection') }), _jsx("button", { type: "submit", disabled: isLoading, className: "px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2", children: isLoading ? (_jsxs(_Fragment, { children: [_jsx(Loader, { className: "h-4 w-4 animate-spin" }), "Saving..."] })) : (_jsxs(_Fragment, { children: [_jsx(Check, { className: "h-4 w-4" }), editingConfig ? 'Update' : 'Create'] })) })] })] })) : (
                        /* List View */
                        _jsxs("div", { children: [_jsxs("div", { className: "flex items-center justify-between mb-6", children: [_jsx("p", { className: "text-gray-600", children: "Manage your MLS provider configurations" }), _jsxs("button", { onClick: handleCreateNew, className: "flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700", children: [_jsx(Plus, { className: "h-5 w-5" }), "Add Configuration"] })] }), isLoading ? (_jsx("div", { className: "flex justify-center py-12", children: _jsx(Loader, { className: "h-8 w-8 text-primary-600 animate-spin" }) })) : configs.length === 0 ? (_jsxs("div", { className: "text-center py-12 bg-gray-50 rounded-lg", children: [_jsx(Settings, { className: "h-12 w-12 text-gray-400 mx-auto mb-3" }), _jsx("h3", { className: "text-lg font-semibold text-gray-900 mb-2", children: "No configurations yet" }), _jsx("p", { className: "text-gray-600 mb-4", children: "Add your first MLS provider configuration to get started" }), _jsxs("button", { onClick: handleCreateNew, className: "inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700", children: [_jsx(Plus, { className: "h-5 w-5" }), "Add Configuration"] })] })) : (_jsx("div", { className: "space-y-4", children: configs.map((config) => (_jsx("div", { className: "border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors", children: _jsxs("div", { className: "flex items-start justify-between", children: [_jsxs("div", { className: "flex-1", children: [_jsxs("div", { className: "flex items-center gap-3 mb-2", children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: config.name }), config.is_active && (_jsx("span", { className: "px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded", children: "Active" }))] }), _jsxs("p", { className: "text-sm text-gray-600 mb-1", children: ["Provider: ", _jsx("span", { className: "font-medium", children: config.provider_type.toUpperCase() })] }), config.credentials?.api_url && (_jsxs("p", { className: "text-sm text-gray-600", children: ["API URL: ", _jsx("span", { className: "font-mono text-xs", children: config.credentials.api_url })] })), config.credentials?.server_url && (_jsxs("p", { className: "text-sm text-gray-600", children: ["Server: ", _jsx("span", { className: "font-mono text-xs", children: config.credentials.server_url })] }))] }), _jsxs("div", { className: "flex gap-2", children: [_jsx("button", { onClick: () => handleEdit(config), className: "px-3 py-1 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50", children: "Edit" }), _jsx("button", { onClick: () => handleDelete(config.id), className: "px-3 py-1 text-sm border border-red-300 text-red-700 rounded hover:bg-red-50", children: _jsx(Trash2, { className: "h-4 w-4" }) })] })] }) }, config.id))) }))] }))] })] }) }));
};
