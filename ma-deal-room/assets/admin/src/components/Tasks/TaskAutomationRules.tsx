import React, { useState } from 'react';
import { Zap, Plus, X, Save, AlertCircle } from 'lucide-react';
import { Button } from '@/components/shared/Button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/shared/Card';
import toast from 'react-hot-toast';

export type AutomationTrigger =
  | 'task_completed'
  | 'task_created'
  | 'task_overdue'
  | 'date_reached'
  | 'field_changed'
  | 'status_changed';

export type AutomationAction =
  | 'create_task'
  | 'send_notification'
  | 'update_field'
  | 'assign_party'
  | 'send_email'
  | 'webhook';

export interface AutomationCondition {
  field: string;
  operator: 'equals' | 'not_equals' | 'contains' | 'greater_than' | 'less_than';
  value: string;
}

export interface AutomationRule {
  id: string;
  name: string;
  trigger: AutomationTrigger;
  conditions: AutomationCondition[];
  actions: Array<{
    type: AutomationAction;
    params: Record<string, any>;
  }>;
  is_active: boolean;
  created_at?: string;
}

interface TaskAutomationRulesProps {
  transactionId?: number;
  templateId?: number;
  onSave?: (rules: AutomationRule[]) => void;
}

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

export const TaskAutomationRules: React.FC<TaskAutomationRulesProps> = ({
  // transactionId and templateId reserved for future use with API integration
  onSave,
}) => {
  const [rules, setRules] = useState<AutomationRule[]>([]);
  const [isAddingRule, setIsAddingRule] = useState(false);

  const [newRule, setNewRule] = useState<Partial<AutomationRule>>({
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

    const rule: AutomationRule = {
      id: `rule-${Date.now()}`,
      name: newRule.name!,
      trigger: newRule.trigger!,
      conditions: newRule.conditions || [],
      actions: newRule.actions!,
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

  const handleToggleRule = (ruleId: string) => {
    const updatedRules = rules.map(rule =>
      rule.id === ruleId ? { ...rule, is_active: !rule.is_active } : rule
    );
    setRules(updatedRules);
    onSave?.(updatedRules);
    toast.success('Rule updated');
  };

  const handleDeleteRule = (ruleId: string) => {
    if (!confirm('Are you sure you want to delete this automation rule?')) return;

    const updatedRules = rules.filter(rule => rule.id !== ruleId);
    setRules(updatedRules);
    onSave?.(updatedRules);
    toast.success('Rule deleted');
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="p-2 bg-purple-100 rounded-lg">
            <Zap className="h-5 w-5 text-purple-600" />
          </div>
          <div>
            <h3 className="text-lg font-semibold text-gray-900">Automation Rules</h3>
            <p className="text-sm text-gray-600">Define rules to automate task workflows</p>
          </div>
        </div>
        <Button
          variant="primary"
          size="sm"
          onClick={() => setIsAddingRule(true)}
        >
          <Plus className="h-4 w-4 mr-2" />
          Add Rule
        </Button>
      </div>

      {/* Existing Rules */}
      <div className="space-y-3">
        {rules.length === 0 && !isAddingRule && (
          <Card>
            <CardContent className="p-8 text-center">
              <Zap className="h-12 w-12 mx-auto text-gray-300 mb-3" />
              <p className="text-gray-600">No automation rules configured</p>
              <p className="text-sm text-gray-500 mt-1">
                Click "Add Rule" to create your first automation
              </p>
            </CardContent>
          </Card>
        )}

        {rules.map((rule) => (
          <Card key={rule.id}>
            <CardContent className="p-4">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <h4 className="font-medium text-gray-900">{rule.name}</h4>
                    <span className={`px-2 py-1 text-xs font-medium rounded ${
                      rule.is_active
                        ? 'bg-green-100 text-green-700'
                        : 'bg-gray-100 text-gray-600'
                    }`}>
                      {rule.is_active ? 'Active' : 'Inactive'}
                    </span>
                  </div>

                  <div className="text-sm text-gray-600 space-y-1">
                    <div className="flex items-center gap-2">
                      <span className="font-medium">Trigger:</span>
                      <span className="bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs">
                        {TRIGGER_OPTIONS.find(t => t.value === rule.trigger)?.label}
                      </span>
                    </div>

                    {rule.conditions.length > 0 && (
                      <div className="flex items-start gap-2">
                        <span className="font-medium">Conditions:</span>
                        <span className="text-xs">{rule.conditions.length} condition(s)</span>
                      </div>
                    )}

                    <div className="flex items-start gap-2">
                      <span className="font-medium">Actions:</span>
                      <div className="flex flex-wrap gap-1">
                        {rule.actions.map((action, idx) => (
                          <span key={idx} className="bg-purple-50 text-purple-700 px-2 py-0.5 rounded text-xs">
                            {ACTION_OPTIONS.find(a => a.value === action.type)?.label}
                          </span>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>

                <div className="flex items-center gap-2 ml-4">
                  <Button
                    size="sm"
                    variant="secondary"
                    onClick={() => handleToggleRule(rule.id)}
                  >
                    {rule.is_active ? 'Disable' : 'Enable'}
                  </Button>
                  <Button
                    size="sm"
                    variant="danger"
                    onClick={() => handleDeleteRule(rule.id)}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Add New Rule Form */}
      {isAddingRule && (
        <Card>
          <CardHeader>
            <CardTitle>Create Automation Rule</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {/* Rule Name */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Rule Name *
              </label>
              <input
                type="text"
                value={newRule.name || ''}
                onChange={(e) => setNewRule({ ...newRule, name: e.target.value })}
                placeholder="e.g., Auto-create inspection task after offer accepted"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500"
              />
            </div>

            {/* Trigger */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Trigger *
              </label>
              <select
                value={newRule.trigger}
                onChange={(e) => setNewRule({ ...newRule, trigger: e.target.value as AutomationTrigger })}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500"
              >
                {TRIGGER_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label} - {option.description}
                  </option>
                ))}
              </select>
            </div>

            {/* Conditions */}
            <div>
              <div className="flex items-center justify-between mb-2">
                <label className="block text-sm font-medium text-gray-700">
                  Conditions (Optional)
                </label>
                <Button
                  size="sm"
                  variant="secondary"
                  onClick={handleAddCondition}
                >
                  <Plus className="h-3 w-3 mr-1" />
                  Add Condition
                </Button>
              </div>
              {newRule.conditions && newRule.conditions.length > 0 && (
                <div className="space-y-2 p-3 bg-gray-50 rounded-lg">
                  {newRule.conditions.map((condition, idx) => (
                    <div key={idx} className="flex items-center gap-2">
                      <input
                        type="text"
                        placeholder="Field name"
                        value={condition.field}
                        onChange={(e) => {
                          const updated = [...(newRule.conditions || [])];
                          updated[idx].field = e.target.value;
                          setNewRule({ ...newRule, conditions: updated });
                        }}
                        className="flex-1 px-2 py-1 text-sm border border-gray-300 rounded"
                      />
                      <select
                        value={condition.operator}
                        onChange={(e) => {
                          const updated = [...(newRule.conditions || [])];
                          updated[idx].operator = e.target.value as any;
                          setNewRule({ ...newRule, conditions: updated });
                        }}
                        className="px-2 py-1 text-sm border border-gray-300 rounded"
                      >
                        <option value="equals">Equals</option>
                        <option value="not_equals">Not Equals</option>
                        <option value="contains">Contains</option>
                        <option value="greater_than">Greater Than</option>
                        <option value="less_than">Less Than</option>
                      </select>
                      <input
                        type="text"
                        placeholder="Value"
                        value={condition.value}
                        onChange={(e) => {
                          const updated = [...(newRule.conditions || [])];
                          updated[idx].value = e.target.value;
                          setNewRule({ ...newRule, conditions: updated });
                        }}
                        className="flex-1 px-2 py-1 text-sm border border-gray-300 rounded"
                      />
                      <button
                        onClick={() => {
                          const updated = newRule.conditions?.filter((_, i) => i !== idx);
                          setNewRule({ ...newRule, conditions: updated });
                        }}
                        className="p-1 text-red-600 hover:bg-red-50 rounded"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Actions */}
            <div>
              <div className="flex items-center justify-between mb-2">
                <label className="block text-sm font-medium text-gray-700">
                  Actions * <span className="text-red-500">Required</span>
                </label>
                <Button
                  size="sm"
                  variant="primary"
                  onClick={handleAddAction}
                >
                  <Plus className="h-3 w-3 mr-1" />
                  Add Action
                </Button>
              </div>
              {newRule.actions && newRule.actions.length > 0 ? (
                <div className="space-y-2 p-3 bg-purple-50 rounded-lg">
                  {newRule.actions.map((action, idx) => (
                    <div key={idx} className="flex items-center gap-2 p-2 bg-white rounded border border-purple-200">
                      <select
                        value={action.type}
                        onChange={(e) => {
                          const updated = [...(newRule.actions || [])];
                          updated[idx].type = e.target.value as AutomationAction;
                          setNewRule({ ...newRule, actions: updated });
                        }}
                        className="flex-1 px-2 py-1 text-sm border border-gray-300 rounded"
                      >
                        {ACTION_OPTIONS.map((option) => (
                          <option key={option.value} value={option.value}>
                            {option.label}
                          </option>
                        ))}
                      </select>
                      <button
                        onClick={() => {
                          const updated = newRule.actions?.filter((_, i) => i !== idx);
                          setNewRule({ ...newRule, actions: updated });
                        }}
                        className="p-1 text-red-600 hover:bg-red-50 rounded"
                      >
                        <X className="h-4 w-4" />
                      </button>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                  <AlertCircle className="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <p className="text-sm font-medium text-amber-900">No actions defined</p>
                    <p className="text-sm text-amber-700">Click "Add Action" to define what happens when this rule triggers</p>
                  </div>
                </div>
              )}
            </div>

            {/* Form Actions */}
            <div className="flex items-center justify-end gap-2 pt-4 border-t border-gray-200">
              <Button
                variant="secondary"
                onClick={() => {
                  setIsAddingRule(false);
                  setNewRule({
                    name: '',
                    trigger: 'task_completed',
                    conditions: [],
                    actions: [],
                    is_active: true,
                  });
                }}
              >
                Cancel
              </Button>
              <Button
                variant="primary"
                onClick={handleSaveRule}
              >
                <Save className="h-4 w-4 mr-2" />
                Save Rule
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};
