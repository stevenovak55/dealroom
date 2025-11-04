# CRM Activity Logging Integration Guide

This guide shows how to integrate CRM activity logging into your services to automatically sync Deal Room activities to CRM timelines (Salesforce Tasks/Notes, HubSpot Timeline Events).

## Quick Start

The easiest way to log activities is using the `CRMActivityLogger` helper class:

```php
use MA_Deal_Room\Services\Integration\CRM\CRMActivityLogger;

// Log task completion
CRMActivityLogger::logTaskCompletion($transaction_id, [
    'title' => 'Home Inspection',
    'description' => 'Completed home inspection with no major issues',
    'completed_by' => 'John Agent',
    'completed_at' => '2025-11-03',
    'priority' => 'High'
]);

// Log document upload
CRMActivityLogger::logDocumentUpload($transaction_id, [
    'filename' => 'inspection_report.pdf',
    'file_type' => 'application/pdf',
    'file_size' => 1024000,
    'uploaded_by' => 'Jane Smith',
    'category' => 'Inspection Documents'
]);

// Log status change
CRMActivityLogger::logStatusChange($transaction_id, 'active', 'under_contract');

// Log vendor interaction
CRMActivityLogger::logVendorInteraction($transaction_id, [
    'vendor_name' => 'ABC Home Inspection',
    'service_type' => 'Home Inspection',
    'status' => 'completed',
    'notes' => 'Inspection completed successfully'
]);

// Log note
CRMActivityLogger::logNote($transaction_id, [
    'content' => 'Buyer requested additional repairs based on inspection',
    'created_by' => 'Agent Name'
]);

// Log email sent
CRMActivityLogger::logEmailSent($transaction_id, [
    'to' => 'buyer@example.com',
    'subject' => 'Inspection Report Available',
    'body' => 'Your inspection report is now available...'
]);
```

## Integration Examples

### TaskService Integration

When implementing TaskService, add CRM logging after task completion:

```php
class TaskService {
    public function completeTask(int $task_id): void {
        $task = $this->repository->getById($task_id);

        // Mark task as complete in database
        $this->repository->update($task_id, [
            'status' => 'completed',
            'completed_at' => current_time('mysql')
        ]);

        // Log to CRM
        CRMActivityLogger::logTaskCompletion($task['transaction_id'], [
            'title' => $task['title'],
            'description' => $task['description'],
            'completed_by' => get_current_user()->display_name,
            'completed_at' => current_time('mysql'),
            'priority' => $task['priority']
        ]);
    }
}
```

### DocumentService Integration

When implementing DocumentService, add CRM logging after upload:

```php
class DocumentService {
    public function uploadDocument(int $transaction_id, array $file_data): int {
        // Save document to database
        $document_id = $this->repository->create([
            'transaction_id' => $transaction_id,
            'filename' => $file_data['name'],
            'file_path' => $file_data['path'],
            'file_type' => $file_data['type'],
            'file_size' => $file_data['size'],
        ]);

        // Log to CRM
        CRMActivityLogger::logDocumentUpload($transaction_id, [
            'filename' => $file_data['name'],
            'file_type' => $file_data['type'],
            'file_size' => $file_data['size'],
            'uploaded_by' => get_current_user()->display_name,
            'category' => $file_data['category']
        ]);

        return $document_id;
    }
}
```

### Transaction Status Change Integration

Status changes are already integrated in the Transaction model:

```php
// When updating transaction status, it automatically logs to CRM
$transaction->updateCRMStage($new_status, $old_status);
```

### VendorService Integration

Add to vendor request send/update methods:

```php
class VendorService {
    public function sendVendorRequest(int $transaction_id, array $vendor_data): int {
        // Create vendor request
        $request_id = $this->repository->create($vendor_data);

        // Send email to vendor
        $this->sendEmail($vendor_data);

        // Log to CRM
        CRMActivityLogger::logVendorInteraction($transaction_id, [
            'vendor_name' => $vendor_data['vendor_name'],
            'service_type' => $vendor_data['service_type'],
            'status' => 'sent',
            'sent_at' => current_time('mysql')
        ]);

        return $request_id;
    }
}
```

## WordPress Hooks

You can also use WordPress hooks for automatic logging:

```php
// Register hooks in your plugin initialization
ActivitySyncService::registerHooks();

// Then trigger from anywhere in your code
do_action('ma_deal_task_completed', $transaction_id, $task_data);
do_action('ma_deal_document_uploaded', $transaction_id, $document_data);
do_action('ma_deal_status_changed', $transaction_id, $old_status, $new_status);
do_action('ma_deal_vendor_request_sent', $transaction_id, $vendor_data);
do_action('ma_deal_note_added', $transaction_id, $note_data);
do_action('ma_deal_email_sent', $transaction_id, $email_data);
```

## Activity Batching

For high-volume operations, activities can be batched:

```php
$service = new ActivitySyncService();

// Log multiple activities
foreach ($tasks as $task) {
    $service->logTaskCompletion($transaction_id, $task, true); // true = batch mode
}

// Flush all batched activities at once
$service->flushAllBatches();
```

## Configuration

Activity logging is controlled by CRM configuration settings:

- **sync_activities**: Enable/disable activity logging for this CRM
- **sync_enabled**: Master sync switch (must be enabled)

Admins can configure this via:
```
POST /wp-json/ma-deal/v1/crm/configure
{
  "provider": "salesforce",
  "sync_activities": true
}
```

## CRM Activity Types

### Salesforce
Activities are logged as **Tasks** with:
- Subject
- Description
- Activity Date
- Status (Completed)
- Priority (Normal/High/Low)
- Linked to Opportunity (WhatId)

### HubSpot
Activities are logged as **Notes** (Timeline Events) with:
- Note body (description)
- Timestamp
- Associated with Deal

## Error Handling

All activity logging methods catch and log exceptions without throwing:

```php
// This will never throw an exception
CRMActivityLogger::logTaskCompletion($transaction_id, $task_data);

// Errors are logged to error_log
// Check PHP error log for: "CRM task logging error: ..."
```

## Performance Considerations

1. **Asynchronous Processing**: Consider using the batch mode for multiple activities
2. **Error Tolerance**: Failed activity logs don't block the main operation
3. **Selective Logging**: Only log when transaction is synced to CRM
4. **Caching**: CRM client instances are cached for performance

## Testing Activity Logging

```php
// Test task completion logging
$result = CRMActivityLogger::logTaskCompletion(123, [
    'title' => 'Test Task',
    'description' => 'Testing CRM activity logging',
    'completed_by' => 'Test User'
]);

// Check Salesforce: Activities tab on Opportunity
// Check HubSpot: Timeline tab on Deal
```

## Future Enhancements

- [ ] Add support for Calendar Events (appointments, showings)
- [ ] Add support for Phone Calls logging
- [ ] Add bi-directional sync (CRM → Deal Room)
- [ ] Add activity deduplication
- [ ] Add activity history view in admin dashboard
