import React, { useState } from 'react';
import { FileText, Download, Calendar, Filter, Mail, Printer, CheckCircle } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import type { Transaction, Task } from '@/api/types';
import { format } from 'date-fns';
import toast from 'react-hot-toast';

export type ReportType =
  | 'transaction_summary'
  | 'transaction_detailed'
  | 'task_completion'
  | 'pipeline_snapshot'
  | 'financial_summary'
  | 'timeline_report'
  | 'agent_performance'
  | 'custom';

export type ExportFormat = 'pdf' | 'csv' | 'xlsx' | 'json';

interface ReportConfig {
  type: ReportType;
  format: ExportFormat;
  dateRange?: {
    start: string;
    end: string;
  };
  filters?: Record<string, any>;
  includeFields?: string[];
  groupBy?: string;
  sortBy?: string;
  includeCharts?: boolean;
  includeAttachments?: boolean;
}

interface ReportGeneratorProps {
  transactions?: Transaction[];
  tasks?: Task[];
  onGenerate: (config: ReportConfig) => Promise<Blob>;
}

const REPORT_TYPES = [
  {
    type: 'transaction_summary' as ReportType,
    label: 'Transaction Summary',
    description: 'High-level overview of all transactions',
    icon: FileText,
    supportedFormats: ['pdf', 'xlsx', 'csv'] as ExportFormat[],
  },
  {
    type: 'transaction_detailed' as ReportType,
    label: 'Detailed Transaction Report',
    description: 'Comprehensive details for each transaction',
    icon: FileText,
    supportedFormats: ['pdf', 'xlsx'] as ExportFormat[],
  },
  {
    type: 'task_completion' as ReportType,
    label: 'Task Completion Report',
    description: 'Task status and completion metrics',
    icon: CheckCircle,
    supportedFormats: ['pdf', 'xlsx', 'csv'] as ExportFormat[],
  },
  {
    type: 'pipeline_snapshot' as ReportType,
    label: 'Pipeline Snapshot',
    description: 'Current state of all deals in pipeline',
    icon: Filter,
    supportedFormats: ['pdf', 'xlsx'] as ExportFormat[],
  },
  {
    type: 'financial_summary' as ReportType,
    label: 'Financial Summary',
    description: 'Revenue, commissions, and financial metrics',
    icon: Download,
    supportedFormats: ['pdf', 'xlsx'] as ExportFormat[],
  },
  {
    type: 'timeline_report' as ReportType,
    label: 'Timeline Report',
    description: 'Chronological view of activities and milestones',
    icon: Calendar,
    supportedFormats: ['pdf'] as ExportFormat[],
  },
  {
    type: 'agent_performance' as ReportType,
    label: 'Agent Performance',
    description: 'Individual agent metrics and KPIs',
    icon: FileText,
    supportedFormats: ['pdf', 'xlsx'] as ExportFormat[],
  },
];

export const ReportGenerator: React.FC<ReportGeneratorProps> = ({
  transactions = [],
  tasks = [],
  onGenerate,
}) => {
  const [selectedType, setSelectedType] = useState<ReportType>('transaction_summary');
  const [selectedFormat, setSelectedFormat] = useState<ExportFormat>('pdf');
  const [dateRange, setDateRange] = useState({
    start: format(new Date(new Date().setMonth(new Date().getMonth() - 1)), 'yyyy-MM-dd'),
    end: format(new Date(), 'yyyy-MM-dd'),
  });
  const [includeCharts, setIncludeCharts] = useState(true);
  const [isGenerating, setIsGenerating] = useState(false);

  const selectedReportType = REPORT_TYPES.find(rt => rt.type === selectedType);
  const supportedFormats = selectedReportType?.supportedFormats || ['pdf'];

  const handleGenerate = async () => {
    setIsGenerating(true);
    try {
      const config: ReportConfig = {
        type: selectedType,
        format: selectedFormat,
        dateRange,
        includeCharts,
      };

      const blob = await onGenerate(config);

      // Download the file
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `${selectedType}-${format(new Date(), 'yyyy-MM-dd')}.${selectedFormat}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);

      toast.success('Report generated successfully');
    } catch (error: any) {
      console.error('Failed to generate report:', error);
      toast.error(error?.message || 'Failed to generate report');
    } finally {
      setIsGenerating(false);
    }
  };

  const handleQuickExport = async (exportFormat: ExportFormat) => {
    setIsGenerating(true);
    try {
      const config: ReportConfig = {
        type: 'transaction_summary',
        format: exportFormat,
      };

      const blob = await onGenerate(config);

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `export-${format(new Date(), 'yyyy-MM-dd')}.${exportFormat}`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);

      toast.success(`Exported to ${exportFormat.toUpperCase()}`);
    } catch (error: any) {
      console.error('Failed to export:', error);
      toast.error('Failed to export data');
    } finally {
      setIsGenerating(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Reports & Exports</h2>
          <p className="text-sm text-gray-600 mt-1">
            Generate reports and export data in various formats
          </p>
        </div>

        {/* Quick export buttons */}
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant="secondary"
            onClick={() => handleQuickExport('csv')}
            disabled={isGenerating}
          >
            <Download className="h-4 w-4 mr-2" />
            Quick CSV
          </Button>
          <Button
            size="sm"
            variant="secondary"
            onClick={() => handleQuickExport('xlsx')}
            disabled={isGenerating}
          >
            <Download className="h-4 w-4 mr-2" />
            Quick Excel
          </Button>
        </div>
      </div>

      {/* Data Summary */}
      <Card>
        <CardContent className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="text-center p-4 bg-blue-50 rounded-lg">
              <p className="text-sm font-medium text-blue-700">Total Transactions</p>
              <p className="text-3xl font-bold text-blue-900 mt-2">{transactions.length}</p>
            </div>
            <div className="text-center p-4 bg-green-50 rounded-lg">
              <p className="text-sm font-medium text-green-700">Total Tasks</p>
              <p className="text-3xl font-bold text-green-900 mt-2">{tasks.length}</p>
            </div>
            <div className="text-center p-4 bg-purple-50 rounded-lg">
              <p className="text-sm font-medium text-purple-700">Date Range</p>
              <p className="text-sm font-bold text-purple-900 mt-2">
                {format(new Date(dateRange.start), 'MMM d')} - {format(new Date(dateRange.end), 'MMM d, yyyy')}
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Report Type Selection */}
      <Card>
        <CardHeader>
          <CardTitle>Select Report Type</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {REPORT_TYPES.map((reportType) => {
              const Icon = reportType.icon;
              const isSelected = selectedType === reportType.type;

              return (
                <button
                  key={reportType.type}
                  onClick={() => setSelectedType(reportType.type)}
                  className={`p-4 rounded-lg border-2 text-left transition-all ${
                    isSelected
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300 bg-white'
                  }`}
                >
                  <div className="flex items-start gap-3">
                    <div className={`p-2 rounded ${isSelected ? 'bg-blue-100' : 'bg-gray-100'}`}>
                      <Icon className={`h-5 w-5 ${isSelected ? 'text-blue-600' : 'text-gray-600'}`} />
                    </div>
                    <div className="flex-1">
                      <h4 className={`font-medium ${isSelected ? 'text-blue-900' : 'text-gray-900'}`}>
                        {reportType.label}
                      </h4>
                      <p className={`text-sm mt-1 ${isSelected ? 'text-blue-700' : 'text-gray-600'}`}>
                        {reportType.description}
                      </p>
                    </div>
                  </div>
                </button>
              );
            })}
          </div>
        </CardContent>
      </Card>

      {/* Configuration */}
      <Card>
        <CardHeader>
          <CardTitle>Report Configuration</CardTitle>
        </CardHeader>
        <CardContent className="space-y-6">
          {/* Date Range */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Date Range
            </label>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs text-gray-600 mb-1">Start Date</label>
                <input
                  type="date"
                  value={dateRange.start}
                  onChange={(e) => setDateRange({ ...dateRange, start: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-xs text-gray-600 mb-1">End Date</label>
                <input
                  type="date"
                  value={dateRange.end}
                  onChange={(e) => setDateRange({ ...dateRange, end: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>

          {/* Export Format */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Export Format
            </label>
            <div className="flex gap-2">
              {supportedFormats.map((format) => (
                <button
                  key={format}
                  onClick={() => setSelectedFormat(format)}
                  className={`px-4 py-2 rounded-md border-2 font-medium transition-all ${
                    selectedFormat === format
                      ? 'border-blue-500 bg-blue-50 text-blue-700'
                      : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'
                  }`}
                >
                  {format.toUpperCase()}
                </button>
              ))}
            </div>
          </div>

          {/* Options */}
          {selectedFormat === 'pdf' && (
            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                id="include-charts"
                checked={includeCharts}
                onChange={(e) => setIncludeCharts(e.target.checked)}
                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label htmlFor="include-charts" className="text-sm text-gray-700">
                Include charts and visualizations
              </label>
            </div>
          )}

          {/* Generate Button */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
            <Button
              variant="secondary"
              disabled={isGenerating}
            >
              <Mail className="h-4 w-4 mr-2" />
              Email Report
            </Button>
            <Button
              variant="secondary"
              disabled={isGenerating}
            >
              <Printer className="h-4 w-4 mr-2" />
              Print Preview
            </Button>
            <Button
              variant="primary"
              onClick={handleGenerate}
              disabled={isGenerating}
            >
              <Download className="h-4 w-4 mr-2" />
              {isGenerating ? 'Generating...' : 'Generate Report'}
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Recent Reports */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Reports</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-center py-8 text-gray-500">
            <FileText className="h-12 w-12 mx-auto mb-3 text-gray-300" />
            <p>No recent reports</p>
            <p className="text-sm mt-1">Generated reports will appear here</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};
