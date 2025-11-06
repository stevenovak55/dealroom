import { jsx as _jsx, jsxs as _jsxs } from "react/jsx-runtime";
import { useState } from 'react';
import { Zap, Plus, X, Save, AlertCircle } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import toast from 'react-hot-toast';
const TRIGGER_OPTIONS = [
    { value: 'task_completed', label: 'Task Completed', description: 'When a task is marked as completed' },
    { value: 'task_created', label: 'Task Created', description: 'When a new task is created' },
    { value: 'task_overdue', label: 'Task Overdue', description: 'When a task becomes overdue' },
    { value: 'date_reached', label: 'Date Reached', description: 'When a specific date is reached' },
    { value: 'field_changed', label: 'Field Changed', description: 'When a field value changes' },
    { value: 'status_changed', label: 'Status Changed', description: 'When transaction status changes' },
];
const ACTION_OPTIONS = [
    { value: 'create_task', label: 'Create Task', description: 'Automatically create a new task' },
    { value: 'send_notification', label: 'Send Notification', description: 'Send notification to users' },
    { value: 'update_field', label: 'Update Field', description: 'Update a field value' },
    { value: 'assign_party', label: 'Assign Party', description: 'Assign task to a party' },
    { value: 'send_email', label: 'Send Email', description: 'Send custom email' },
    { value: 'webhook', label: 'Webhook', description: 'Trigger external webhook' },
];
export const TaskAutomationRules = ({ 
// transactionId and templateId reserved for future use with API integration
onSave, }) => {
    const [rules, setRules] = useState([]);
    const [isAddingRule, setIsAddingRule] = useState(false);
    const [newRule, setNewRule] = useState({
        name: '',
        trigger: 'task_completed',
        conditions: [],
        actions: [],
        is_active: true,
    });
    const handleAddCondition = () => {
        setNewRule({
            ...newRule,
            conditions: [
                ...(newRule.conditions || []),
                { field: '', operator: 'equals', value: '' },
            ],
        });
    };
    const handleAddAction = () => {
        setNewRule({
            ...newRule,
            actions: [
                ...(newRule.actions || []),
                { type: 'send_notification', params: {} },
            ],
        });
    };
    const handleSaveRule = () => {
        if (!newRule.name || !newRule.trigger) {
            toast.error('Please provide rule name and trigger');
            return;
        }
        if (!newRule.actions || newRule.actions.length === 0) {
            toast.error('Please add at least one action');
            return;
        }
        const rule = {
            id: `rule-${Date.now()}`,
            name: newRule.name,
            trigger: newRule.trigger,
            conditions: newRule.conditions || [],
            actions: newRule.actions,
            is_active: newRule.is_active || true,
            created_at: new Date().toISOString(),
        };
        const updatedRules = [...rules, rule];
        setRules(updatedRules);
        onSave?.(updatedRules);
        // Reset form
        setNewRule({
            name: '',
            trigger: 'task_completed',
            conditions: [],
            actions: [],
            is_active: true,
        });
        setIsAddingRule(false);
        toast.success('Automation rule created');
    };
    const handleToggleRule = (ruleId) => {
        const updatedRules = rules.map(rule => rule.id === ruleId ? { ...rule, is_active: !rule.is_active } : rule);
        setRules(updatedRules);
        onSave?.(updatedRules);
        toast.success('Rule updated');
    };
    const handleDeleteRule = (ruleId) => {
        if (!confirm('Are you sure you want to delete this automation rule?'))
            return;
        const updatedRules = rules.filter(rule => rule.id !== ruleId);
        setRules(updatedRules);
        onSave?.(updatedRules);
        toast.success('Rule deleted');
    };
    return (_jsxs("div", { className: "space-y-6", children: [_jsxs("div", { className: "flex items-center justify-between", children: [_jsxs("div", { className: "flex items-center gap-3", children: [_jsx("div", { className: "p-2 bg-purple-100 rounded-lg", children: _jsx(Zap, { className: "h-5 w-5 text-purple-600" }) }), _jsxs("div", { children: [_jsx("h3", { className: "text-lg font-semibold text-gray-900", children: "Automation Rules" }), _jsx("p", { className: "text-sm text-gray-600", children: "Define rules to automate task workflows" })] })] }), _jsxs(Button, { variant: "primary", size: "sm", onClick: () => setIsAddingRule(true), children: [_jsx(Plus, { className: "h-4 w-4 mr-2" }), "Add Rule"] })] }), _jsxs("div", { className: "space-y-3", children: [rules.length === 0 && !isAddingRule && (_jsx(Card, { children: _jsxs(CardContent, { className: "p-8 text-center", children: [_jsx(Zap, { className: "h-12 w-12 mx-auto text-gray-300 mb-3" }), _jsx("p", { className: "text-gray-600", children: "No automation rules configured" }), _jsx("p", { className: "text-sm text-gray-500 mt-1", children: "Click \"Add Rule\" to create your first automation" })] }) })), rules.map((rule) => (_jsx(Card, { children: _jsx(CardContent, { className: "p-4", children: _jsxs("div", { className: "flex items-start justify-between", children: [_jsxs("div", { className: "flex-1", children: [_jsxs("div", { className: "flex items-center gap-3 mb-2", children: [_jsx("h4", { className: "font-medium text-gray-900", children: rule.name }), _jsx("span", { className: `px-2 py-1 text-xs font-medium rounded ${rule.is_active
                                                            ? 'bg-green-100 text-green-700'
                                                            : 'bg-gray-100 text-gray-600'}`, children: rule.is_active ? 'Active' : 'Inactive' })] }), _jsxs("div", { className: "text-sm text-gray-600 space-y-1", children: [_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("span", { className: "font-medium", children: "Trigger:" }), _jsx("span", { className: "bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs", children: TRIGGER_OPTIONS.find(t => t.value === rule.trigger)?.label })] }), rule.conditions.length > 0 && (_jsxs("div", { className: "flex items-start gap-2", children: [_jsx("span", { className: "font-medium", children: "Conditions:" }), _jsxs("span", { className: "text-xs", children: [rule.conditions.length, " condition(s)"] })] })), _jsxs("div", { className: "flex items-start gap-2", children: [_jsx("span", { className: "font-medium", children: "Actions:" }), _jsx("div", { className: "flex flex-wrap gap-1", children: rule.actions.map((action, idx) => (_jsx("span", { className: "bg-purple-50 text-purple-700 px-2 py-0.5 rounded text-xs", children: ACTION_OPTIONS.find(a => a.value === action.type)?.label }, idx))) })] })] })] }), _jsxs("div", { className: "flex items-center gap-2 ml-4", children: [_jsx(Button, { size: "sm", variant: "secondary", onClick: () => handleToggleRule(rule.id), children: rule.is_active ? 'Disable' : 'Enable' }), _jsx(Button, { size: "sm", variant: "danger", onClick: () => handleDeleteRule(rule.id), children: _jsx(X, { className: "h-4 w-4" }) })] })] }) }) }, rule.id)))] }), isAddingRule && (_jsxs(Card, { children: [_jsx(CardHeader, { children: _jsx(CardTitle, { children: "Create Automation Rule" }) }), _jsxs(CardContent, { className: "space-y-4", children: [_jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: "Rule Name *" }), _jsx("input", { type: "text", value: newRule.name || '', onChange: (e) => setNewRule({ ...newRule, name: e.target.value }), placeholder: "e.g., Auto-create inspection task after offer accepted", className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500" })] }), _jsxs("div", { children: [_jsx("label", { className: "block text-sm font-medium text-gray-700 mb-1", children: "Trigger *" }), _jsx("select", { value: newRule.trigger, onChange: (e) => setNewRule({ ...newRule, trigger: e.target.value }), className: "w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500", children: TRIGGER_OPTIONS.map((option) => (_jsxs("option", { value: option.value, children: [option.label, " - ", option.description] }, option.value))) })] }), _jsxs("div", { children: [_jsxs("div", { className: "flex items-center justify-between mb-2", children: [_jsx("label", { className: "block text-sm font-medium text-gray-700", children: "Conditions (Optional)" }), _jsxs(Button, { size: "sm", variant: "secondary", onClick: handleAddCondition, children: [_jsx(Plus, { className: "h-3 w-3 mr-1" }), "Add Condition"] })] }), newRule.conditions && newRule.conditions.length > 0 && (_jsx("div", { className: "space-y-2 p-3 bg-gray-50 rounded-lg", children: newRule.conditions.map((condition, idx) => (_jsxs("div", { className: "flex items-center gap-2", children: [_jsx("input", { type: "text", placeholder: "Field name", value: condition.field, onChange: (e) => {
                                                        const updated = [...(newRule.conditions || [])];
                                                        updated[idx].field = e.target.value;
                                                        setNewRule({ ...newRule, conditions: updated });
                                                    }, className: "flex-1 px-2 py-1 text-sm border border-gray-300 rounded" }), _jsxs("select", { value: condition.operator, onChange: (e) => {
                                                        const updated = [...(newRule.conditions || [])];
                                                        updated[idx].operator = e.target.value;
                                                        setNewRule({ ...newRule, conditions: updated });
                                                    }, className: "px-2 py-1 text-sm border border-gray-300 rounded", children: [_jsx("option", { value: "equals", children: "Equals" }), _jsx("option", { value: "not_equals", children: "Not Equals" }), _jsx("option", { value: "contains", children: "Contains" }), _jsx("option", { value: "greater_than", children: "Greater Than" }), _jsx("option", { value: "less_than", children: "Less Than" })] }), _jsx("input", { type: "text", placeholder: "Value", value: condition.value, onChange: (e) => {
                                                        const updated = [...(newRule.conditions || [])];
                                                        updated[idx].value = e.target.value;
                                                        setNewRule({ ...newRule, conditions: updated });
                                                    }, className: "flex-1 px-2 py-1 text-sm border border-gray-300 rounded" }), _jsx("button", { onClick: () => {
                                                        const updated = newRule.conditions?.filter((_, i) => i !== idx);
                                                        setNewRule({ ...newRule, conditions: updated });
                                                    }, className: "p-1 text-red-600 hover:bg-red-50 rounded", children: _jsx(X, { className: "h-4 w-4" }) })] }, idx))) }))] }), _jsxs("div", { children: [_jsxs("div", { className: "flex items-center justify-between mb-2", children: [_jsxs("label", { className: "block text-sm font-medium text-gray-700", children: ["Actions * ", _jsx("span", { className: "text-red-500", children: "Required" })] }), _jsxs(Button, { size: "sm", variant: "primary", onClick: handleAddAction, children: [_jsx(Plus, { className: "h-3 w-3 mr-1" }), "Add Action"] })] }), newRule.actions && newRule.actions.length > 0 ? (_jsx("div", { className: "space-y-2 p-3 bg-purple-50 rounded-lg", children: newRule.actions.map((action, idx) => (_jsxs("div", { className: "flex items-center gap-2 p-2 bg-white rounded border border-purple-200", children: [_jsx("select", { value: action.type, onChange: (e) => {
                                                        const updated = [...(newRule.actions || [])];
                                                        updated[idx].type = e.target.value;
                                                        setNewRule({ ...newRule, actions: updated });
                                                    }, className: "flex-1 px-2 py-1 text-sm border border-gray-300 rounded", children: ACTION_OPTIONS.map((option) => (_jsx("option", { value: option.value, children: option.label }, option.value))) }), _jsx("button", { onClick: () => {
                                                        const updated = newRule.actions?.filter((_, i) => i !== idx);
                                                        setNewRule({ ...newRule, actions: updated });
                                                    }, className: "p-1 text-red-600 hover:bg-red-50 rounded", children: _jsx(X, { className: "h-4 w-4" }) })] }, idx))) })) : (_jsxs("div", { className: "p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2", children: [_jsx(AlertCircle, { className: "h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" }), _jsxs("div", { children: [_jsx("p", { className: "text-sm font-medium text-amber-900", children: "No actions defined" }), _jsx("p", { className: "text-sm text-amber-700", children: "Click \"Add Action\" to define what happens when this rule triggers" })] })] }))] }), _jsxs("div", { className: "flex items-center justify-end gap-2 pt-4 border-t border-gray-200", children: [_jsx(Button, { variant: "secondary", onClick: () => {
                                            setIsAddingRule(false);
                                            setNewRule({
                                                name: '',
                                                trigger: 'task_completed',
                                                conditions: [],
                                                actions: [],
                                                is_active: true,
                                            });
                                        }, children: "Cancel" }), _jsxs(Button, { variant: "primary", onClick: handleSaveRule, children: [_jsx(Save, { className: "h-4 w-4 mr-2" }), "Save Rule"] })] })] })] }))] }));
};
