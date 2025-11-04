import { useState, useEffect } from 'react';
import { X, Plus, Check, AlertCircle, Trash2, Settings, Loader } from 'lucide-react';
import { mlsService, MLSConfig } from '@/api/mlsService';

interface MLSConfigModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfigSaved?: () => void;
}

interface Provider {
  type: string;
  name: string;
  description: string;
}

interface ProviderField {
  name: string;
  label: string;
  type: string;
  required: boolean;
  description?: string;
  placeholder?: string;
  default?: any;
  options?: any[];
}

export const MLSConfigModal = ({ isOpen, onClose, onConfigSaved }: MLSConfigModalProps) => {
  const [configs, setConfigs] = useState<MLSConfig[]>([]);
  const [providers, setProviders] = useState<Provider[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Form state
  const [showForm, setShowForm] = useState(false);
  const [editingConfig, setEditingConfig] = useState<MLSConfig | null>(null);
  const [formData, setFormData] = useState({
    name: '',
    provider_type: '',
    is_active: true,
    credentials: {} as Record<string, any>,
    settings: {} as Record<string, any>,
  });
  const [providerFields, setProviderFields] = useState<ProviderField[]>([]);
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
        setConfigs(response.data as MLSConfig[]);
      }
    } catch (err: any) {
      console.error('Failed to load configs:', err);
      setError('Failed to load configurations');
    } finally {
      setIsLoading(false);
    }
  };

  const loadProviders = async () => {
    try {
      const response = await mlsService.getProviders();
      if (response.success) {
        setProviders(response.data || []);
      }
    } catch (err: any) {
      console.error('Failed to load providers:', err);
    }
  };

  const loadProviderFields = async (providerType: string) => {
    try {
      const response = await mlsService.getProviderFields(providerType);
      if (response.success && response.data) {
        setProviderFields(response.data as ProviderField[]);
      }
    } catch (err: any) {
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

  const handleEdit = (config: MLSConfig) => {
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
      } else {
        setError(response.message || 'Connection test failed');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to test connection');
    } finally {
      setTestingConnection(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
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
      } else {
        response = await mlsService.createConfig(configData);
      }

      if (response.success) {
        setSuccess(editingConfig ? 'Configuration updated!' : 'Configuration created!');
        setShowForm(false);
        await loadConfigs();
        if (onConfigSaved) {
          onConfigSaved();
        }
      } else {
        setError(response.message || 'Failed to save configuration');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to save configuration');
    } finally {
      setIsLoading(false);
    }
  };

  const handleDelete = async (configId: number) => {
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
      } else {
        setError(response.message || 'Failed to delete configuration');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to delete configuration');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCredentialChange = (fieldName: string, value: any) => {
    setFormData(prev => ({
      ...prev,
      credentials: {
        ...prev.credentials,
        [fieldName]: value,
      },
    }));
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b">
          <div className="flex items-center gap-3">
            <Settings className="h-6 w-6 text-primary-600" />
            <h2 className="text-2xl font-bold text-gray-900">MLS Configuration</h2>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        {/* Content */}
        <div className="flex-1 overflow-y-auto p-6">
          {/* Alerts */}
          {error && (
            <div className="mb-4 bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
              <AlertCircle className="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
              <p className="text-red-800">{error}</p>
            </div>
          )}

          {success && (
            <div className="mb-4 bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3">
              <Check className="h-5 w-5 text-green-600 flex-shrink-0 mt-0.5" />
              <p className="text-green-800">{success}</p>
            </div>
          )}

          {/* Form View */}
          {showForm ? (
            <form onSubmit={handleSubmit} className="space-y-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Configuration Name *
                </label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  placeholder="e.g., MLSPin Production"
                  required
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Provider Type *
                </label>
                <select
                  value={formData.provider_type}
                  onChange={(e) => setFormData({ ...formData, provider_type: e.target.value, credentials: {} })}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  required
                  disabled={!!editingConfig}
                >
                  <option value="">Select a provider...</option>
                  {providers.map((provider) => (
                    <option key={provider.type} value={provider.type}>
                      {provider.name}
                    </option>
                  ))}
                </select>
                {formData.provider_type && (
                  <p className="mt-1 text-sm text-gray-500">
                    {providers.find(p => p.type === formData.provider_type)?.description}
                  </p>
                )}
              </div>

              {/* Dynamic provider fields */}
              {providerFields.map((field) => (
                <div key={field.name}>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    {field.label} {field.required && '*'}
                  </label>
                  {field.type === 'password' ? (
                    <input
                      type="password"
                      value={formData.credentials[field.name] || ''}
                      onChange={(e) => handleCredentialChange(field.name, e.target.value)}
                      placeholder={field.placeholder}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      required={field.required}
                    />
                  ) : field.type === 'textarea' ? (
                    <textarea
                      value={formData.credentials[field.name] || ''}
                      onChange={(e) => handleCredentialChange(field.name, e.target.value)}
                      placeholder={field.placeholder}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      rows={4}
                      required={field.required}
                    />
                  ) : (
                    <input
                      type={field.type === 'url' ? 'url' : 'text'}
                      value={formData.credentials[field.name] || ''}
                      onChange={(e) => handleCredentialChange(field.name, e.target.value)}
                      placeholder={field.placeholder}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                      required={field.required}
                    />
                  )}
                  {field.description && (
                    <p className="mt-1 text-sm text-gray-500">{field.description}</p>
                  )}
                </div>
              ))}

              <div className="flex items-center">
                <input
                  type="checkbox"
                  id="is_active"
                  checked={formData.is_active}
                  onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                  className="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                />
                <label htmlFor="is_active" className="ml-2 block text-sm text-gray-700">
                  Set as active configuration
                </label>
              </div>

              <div className="flex gap-3 pt-4 border-t">
                <button
                  type="button"
                  onClick={() => setShowForm(false)}
                  className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleTestConnection}
                  disabled={!formData.provider_type || Object.keys(formData.credentials).length === 0 || testingConnection}
                  className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                  {testingConnection ? (
                    <>
                      <Loader className="h-4 w-4 animate-spin" />
                      Testing...
                    </>
                  ) : (
                    'Test Connection'
                  )}
                </button>
                <button
                  type="submit"
                  disabled={isLoading}
                  className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                  {isLoading ? (
                    <>
                      <Loader className="h-4 w-4 animate-spin" />
                      Saving...
                    </>
                  ) : (
                    <>
                      <Check className="h-4 w-4" />
                      {editingConfig ? 'Update' : 'Create'}
                    </>
                  )}
                </button>
              </div>
            </form>
          ) : (
            /* List View */
            <div>
              <div className="flex items-center justify-between mb-6">
                <p className="text-gray-600">
                  Manage your MLS provider configurations
                </p>
                <button
                  onClick={handleCreateNew}
                  className="flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                >
                  <Plus className="h-5 w-5" />
                  Add Configuration
                </button>
              </div>

              {isLoading ? (
                <div className="flex justify-center py-12">
                  <Loader className="h-8 w-8 text-primary-600 animate-spin" />
                </div>
              ) : configs.length === 0 ? (
                <div className="text-center py-12 bg-gray-50 rounded-lg">
                  <Settings className="h-12 w-12 text-gray-400 mx-auto mb-3" />
                  <h3 className="text-lg font-semibold text-gray-900 mb-2">
                    No configurations yet
                  </h3>
                  <p className="text-gray-600 mb-4">
                    Add your first MLS provider configuration to get started
                  </p>
                  <button
                    onClick={handleCreateNew}
                    className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                  >
                    <Plus className="h-5 w-5" />
                    Add Configuration
                  </button>
                </div>
              ) : (
                <div className="space-y-4">
                  {configs.map((config) => (
                    <div
                      key={config.id}
                      className="border border-gray-200 rounded-lg p-4 hover:border-primary-300 transition-colors"
                    >
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <div className="flex items-center gap-3 mb-2">
                            <h3 className="text-lg font-semibold text-gray-900">
                              {config.name}
                            </h3>
                            {config.is_active && (
                              <span className="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded">
                                Active
                              </span>
                            )}
                          </div>
                          <p className="text-sm text-gray-600 mb-1">
                            Provider: <span className="font-medium">{config.provider_type.toUpperCase()}</span>
                          </p>
                          {config.credentials?.api_url && (
                            <p className="text-sm text-gray-600">
                              API URL: <span className="font-mono text-xs">{config.credentials.api_url}</span>
                            </p>
                          )}
                          {config.credentials?.server_url && (
                            <p className="text-sm text-gray-600">
                              Server: <span className="font-mono text-xs">{config.credentials.server_url}</span>
                            </p>
                          )}
                        </div>
                        <div className="flex gap-2">
                          <button
                            onClick={() => handleEdit(config)}
                            className="px-3 py-1 text-sm border border-gray-300 text-gray-700 rounded hover:bg-gray-50"
                          >
                            Edit
                          </button>
                          <button
                            onClick={() => handleDelete(config.id)}
                            className="px-3 py-1 text-sm border border-red-300 text-red-700 rounded hover:bg-red-50"
                          >
                            <Trash2 className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
