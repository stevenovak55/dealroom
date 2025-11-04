/**
 * DocuSignTemplateSelector Component
 * 
 * Allows users to browse and select DocuSign templates for envelope creation.
 * Displays templates in a searchable grid with preview option.
 */

import { useState, useEffect } from 'react';
import {
  Box,
  Card,
  CardContent,
  CardActions,
  Button,
  TextField,
  CircularProgress,
  Alert,
  Typography,
  Chip,
  Stack,
  InputAdornment,
} from '@mui/material';
import {
  Search,
  Description,
  Visibility,
  CheckCircle,
} from '@mui/icons-material';
import { docusignService, DocuSignTemplate } from '../../api/docusignService';

interface DocuSignTemplateSelectorProps {
  onSelect: (templateId: string) => void;
  transactionId?: number;
}

/**
 * DocuSignTemplateSelector - Grid of selectable DocuSign templates
 */
export default function DocuSignTemplateSelector({
  onSelect,
  transactionId,
}: DocuSignTemplateSelectorProps) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [templates, setTemplates] = useState<DocuSignTemplate[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedTemplateId, setSelectedTemplateId] = useState<string | null>(null);
  const [previewLoading, setPreviewLoading] = useState<string | null>(null);

  useEffect(() => {
    loadTemplates();
  }, []);

  const loadTemplates = async (search?: string) => {
    try {
      setLoading(true);
      setError(null);
      const data = await docusignService.listTemplates(search);
      setTemplates(data.templates || []);
    } catch (err: any) {
      const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message || 'Failed to load templates';
      setError(message);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (query: string) => {
    setSearchQuery(query);
    loadTemplates(query);
  };

  const handlePreview = async (templateId: string) => {
    if (!transactionId) {
      alert('Transaction ID is required for template preview');
      return;
    }

    try {
      setPreviewLoading(templateId);
      const preview = await docusignService.previewTemplate(templateId, transactionId);
      console.log('Template preview:', preview);
      alert('Preview data logged to console');
    } catch (err: any) {
      const message = err.response && err.response.data && err.response.data.message ? err.response.data.message : err.message;
      alert('Failed to preview template: ' + message);
    } finally {
      setPreviewLoading(null);
    }
  };

  const handleSelectTemplate = (templateId: string) => {
    setSelectedTemplateId(templateId);
    onSelect(templateId);
  };

  const formatDate = (dateString?: string) => {
    if (!dateString) return 'Unknown';
    try {
      return new Date(dateString).toLocaleDateString();
    } catch {
      return dateString;
    }
  };

  if (loading && templates.length === 0) {
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
      <Stack spacing={3}>
        <TextField
          fullWidth
          label="Search Templates"
          placeholder="Search by template name..."
          value={searchQuery}
          onChange={(e) => handleSearch(e.target.value)}
          InputProps={{
            startAdornment: (
              <InputAdornment position="start">
                <Search />
              </InputAdornment>
            ),
            endAdornment: loading ? (
              <InputAdornment position="end">
                <CircularProgress size={20} />
              </InputAdornment>
            ) : null,
          }}
        />

        {templates.length === 0 ? (
          <Alert severity="info">
            {searchQuery ? 'No templates match your search' : 'No templates available'}
          </Alert>
        ) : (
          <>
            <Typography variant="body2" color="text.secondary">
              {templates.length} template(s) found
            </Typography>

            <Box
              display="grid"
              gridTemplateColumns={{ xs: '1fr', sm: '1fr 1fr', md: 'repeat(3, 1fr)' }}
              gap={3}
            >
              {templates.map((template) => (
                <Box key={template.templateId}>
                  <Card
                    variant="outlined"
                    sx={{
                      height: '100%',
                      display: 'flex',
                      flexDirection: 'column',
                      border: selectedTemplateId === template.templateId ? 2 : 1,
                      borderColor: selectedTemplateId === template.templateId ? 'primary.main' : 'divider',
                    }}
                  >
                    <CardContent sx={{ flexGrow: 1 }}>
                      <Stack spacing={2}>
                        <Stack direction="row" justifyContent="space-between" alignItems="flex-start">
                          <Description color="primary" sx={{ fontSize: 40 }} />
                          {selectedTemplateId === template.templateId && (
                            <Chip
                              label="Selected"
                              color="primary"
                              size="small"
                              icon={<CheckCircle />}
                            />
                          )}
                        </Stack>

                        <Box>
                          <Typography variant="h6" gutterBottom noWrap title={template.name}>
                            {template.name}
                          </Typography>
                          {template.description && (
                            <Typography
                              variant="body2"
                              color="text.secondary"
                              sx={{
                                display: '-webkit-box',
                                WebkitLineClamp: 3,
                                WebkitBoxOrient: 'vertical',
                                overflow: 'hidden',
                              }}
                            >
                              {template.description}
                            </Typography>
                          )}
                        </Box>

                        <Stack spacing={0.5}>
                          {template.lastModified && (
                            <Typography variant="caption" color="text.secondary">
                              Modified: {formatDate(template.lastModified)}
                            </Typography>
                          )}
                          {template.owner && template.owner.name && (
                            <Typography variant="caption" color="text.secondary">
                              Owner: {template.owner.name}
                            </Typography>
                          )}
                          {template.shared && (
                            <Chip label="Shared" size="small" variant="outlined" />
                          )}
                        </Stack>
                      </Stack>
                    </CardContent>

                    <CardActions sx={{ justifyContent: 'space-between', p: 2 }}>
                      {transactionId && (
                        <Button
                          size="small"
                          variant="outlined"
                          onClick={() => handlePreview(template.templateId)}
                          disabled={previewLoading === template.templateId}
                          startIcon={
                            previewLoading === template.templateId ? (
                              <CircularProgress size={16} />
                            ) : (
                              <Visibility />
                            )
                          }
                        >
                          Preview
                        </Button>
                      )}
                      <Button
                        size="small"
                        variant={selectedTemplateId === template.templateId ? 'contained' : 'outlined'}
                        onClick={() => handleSelectTemplate(template.templateId)}
                        sx={{ ml: 'auto' }}
                      >
                        {selectedTemplateId === template.templateId ? 'Selected' : 'Select'}
                      </Button>
                    </CardActions>
                  </Card>
                </Box>
              ))}
            </Box>
          </>
        )}
      </Stack>
    </Box>
  );
}
