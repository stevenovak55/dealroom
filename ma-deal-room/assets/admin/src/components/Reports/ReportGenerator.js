import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { FileText, Download, Calendar, Filter, Mail, Printer, CheckCircle } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import { format } from 'date-fns';
import toast from 'react-hot-toast';
const REPORT_TYPES = [
    {
        type: 'transaction_summary',
        label: 'Transaction Summary',
        description: 'High-level overview of all transactions',
        icon: FileText,
        supportedFormats: ['pdf', 'xlsx', 'csv'],
    },
    {
        type: 'transaction_detailed',
        label: 'Detailed Transaction Report',
        description: 'Comprehensive details for each transaction',
        icon: FileText,
        supportedFormats: ['pdf', 'xlsx'],
    },
    {
        type: 'task_completion',
        label: 'Task Completion Report',
        description: 'Task status and completion metrics',
        icon: CheckCircle,
        supportedFormats: ['pdf', 'xlsx', 'csv'],
    },
    {
        type: 'pipeline_snapshot',
        label: 'Pipeline Snapshot',
        description: 'Current state of all deals in pipeline',
        icon: Filter,
        supportedFormats: ['pdf', 'xlsx'],
    },
    {
        type: 'financial_summary',
        label: 'Financial Summary',
        description: 'Revenue, commissions, and financial metrics',
        icon: Download,
        supportedFormats: ['pdf', 'xlsx'],
    },
    {
        type: 'timeline_report',
        label: 'Timeline Report',
        description: 'Chronological view of activities and milestones',
        icon: Calendar,
        supportedFormats: ['pdf'],
    },
    {
        type: 'agent_performance',
        label: 'Agent Performance',
        description: 'Individual agent metrics and KPIs',
        icon: FileText,
        supportedFormats: ['pdf', 'xlsx'],
    },
];
export const ReportGenerator = ({ transactions = [], tasks = [], onGenerate, }) => {
    const [selectedType, setSelectedType] = useState('transaction_summary');
    const [selectedFormat, setSelectedFormat] = useState('pdf');
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
            const config = {
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
        }
        catch (error) {
            console.error('Failed to generate report:', error);
            toast.error(error?.message || 'Failed to generate report');
        }
        finally {
            setIsGenerating(false);
        }
    };
    const handleQuickExport = async (exportFormat) => {
        setIsGenerating(true);
        try {
            const config = {
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
        }
        catch (error) {
            console.error('Failed to export:', error);
            toast.error('Failed to export data');
        }
        finally {
            setIsGenerating(false);
        }
    };
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { children: [_jsx("h2", { className: "text-2xl font-bold text-gray-900", children: "Reports & Exports" }), _jsx("p", { className: "text-sm text-gray-600 mt-1", children: "Generate reports and export data in various formats" })] }), _jsxs("div", { className: "flex items-center gap-2", children: [_jsxs(Button, { size: "sm", variant: "secondary", onClick: () => handleQuickExport('csv'), disabled: isGenerating, children: [_jsx(Download, { className: "h-4 w-4 mr-2" }), "Quick CSV"] }), _jsxs(Button, { size: "sm", variant: "secondary", onClick: () => handleQuickExport('xlsx'), disabled: isGenerating, children: [_jsx(Download, { className: "h-4 w-4 mr-2" }), "Quick Excel"] })] })] }), _jsx(Card, { children: _jsx(CardContent, { className: "p-6", children: _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-3 gap-4", children: [_jsxs("div", { className: "text-center p-4 bg-blue-50 rounded-lg", children: [_jsx("p", { className: "text-sm font-medium text-blue-700", children: "Total Transactions" }), _jsx("p", { className: "text-3xl font-bold text-blue-900 mt-2", children: transactions.length })] }), _jsxs("div", { className: "text-center p-4 bg-green-50 rounded-lg", children: [_jsx("p", { className: "text-sm font-medium text-green-700", children: "Total Tasks" }), _jsx("p", { className: "text-3xl font-bold text-green-900 mt-2", children: tasks.length })] }), _jsxs("div", { className: "text-center p-4 bg-purple-50 rounded-lg", children: [_jsx("p", { className: "text-sm font-medium text-purple-700", children: "Date Range" }), _jsxs("p", { className: "text-sm font-bold text-purple-900 mt-2", children: [format(new Date(dateRange.start), 'MMM d'), " - ", format(new Date(dateRange.end), 'MMM d, yyyy')] })] })] }) }) }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Select Report Type" }) }), _jsx(CardContent, { children: _jsx("div", { className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4", children: REPORT_TYPES.map((reportType) => {
                                const Icon = reportType.icon;
                                const isSelected = selectedType === reportType.type;
                                return (_jsx("button", { onClick: () => setSelectedType(reportType.type), className: `p-4 rounded-lg border-2 text-left transition-all ${isSelected
                                        ? 'border-blue-500 bg-blue-50'
                                        : 'border-gray-200 hover:border-gray-300 bg-white'}`, children: _jsxs("div", { className: "flex items-start gap-3", children: [_jsx("div", { className: `p-2 rounded ${isSelected ? 'bg-blue-100' : 'bg-gray-100'}`, children: _jsx(Icon, { className: `h-5 w-5 ${isSelected ? 'text-blue-600' : 'text-gray-600'}` }) }), _jsxs("div", { className: "flex-1", children: [_jsx("h4", { className: `font-medium ${isSelected ? 'text-blue-900' : 'text-gray-900'}`, children: reportType.label }), _jsx("p", { className: `text-sm mt-1 ${isSelected ? 'text-blue-700' : 'text-gray-600'}`, children: reportType.description })] })] }) }, reportType.type));
                            }) }) })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Report Configuration" }) }), _jsxs(CardContent, { className: "space-y-6", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Date Range" }), _jsxs("div", { className: "grid grid-cols-1 md:grid-cols-2 gap-4", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-xs text-gray-600 mb-1", children: "Start Date" }), _jsx("input", { type: "date", value: dateRange.start, onChange: (e) => setDateRange({ ...dateRange, start: e.target.value }), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-xs text-gray-600 mb-1", children: "End Date" }), _jsx("input", { type: "date", value: dateRange.end, onChange: (e) => setDateRange({ ...dateRange, end: e.target.value }), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" })] })] })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-2", children: "Export Format" }), _jsx("div", { className: "flex gap-2", children: supportedFormats.map((format) => (_jsx("button", { onClick: () => setSelectedFormat(format), className: `px-4 py-2 rounded-md border-2 font-medium transition-all ${selectedFormat === format
                                                ? 'border-blue-500 bg-blue-50 text-blue-700'
                                                : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300'}`, children: format.toUpperCase() }, format))) })] }), selectedFormat === 'pdf' && (_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("input", { type: "checkbox", id: "include-charts", checked: includeCharts, onChange: (e) => setIncludeCharts(e.target.checked), className: "h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" }), _jsx("label", { htmlFor: "include-charts", className: "text-sm text-gray-700", children: "Include charts and visualizations" })] })), _jsxs("div", { className: "flex items-center justify-end gap-3 pt-4 border-t border-gray-200", children: [_jsxs(Button, { variant: "secondary", disabled: isGenerating, children: [_jsx(Mail, { className: "h-4 w-4 mr-2" }), "Email Report"] }), _jsxs(Button, { variant: "secondary", disabled: isGenerating, children: [_jsx(Printer, { className: "h-4 w-4 mr-2" }), "Print Preview"] }), _jsxs(Button, { variant: "primary", onClick: handleGenerate, disabled: isGenerating, children: [_jsx(Download, { className: "h-4 w-4 mr-2" }), isGenerating ? 'Generating...' : 'Generate Report'] })] })] })] }), _jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Recent Reports" }) }), _jsx(CardContent, { children: _jsxs("div", { className: "text-center py-8 text-gray-500", children: [_jsx(FileText, { className: "h-12 w-12 mx-auto mb-3 text-gray-300" }), _jsx("p", { children: "No recent reports" }), _jsx("p", { className: "text-sm mt-1", children: "Generated reports will appear here" })] }) })] })] }));
};
