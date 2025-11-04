#!/bin/bash
# Cleanup Test and Debug Files
# Run from dealroom directory

echo "Cleaning up test and debug files..."
echo "===================================="
echo ""

# List of files to remove
files=(
    "add-loan-commitment-tasks.php"
    "add-tc-workflow-tasks.php"
    "check-due-calculation.php"
    "check-task-definitions.php"
    "check-template-format.php"
    "check-template-yaml.php"
    "check-which-mode.php"
    "clear-overrides.php"
    "debug-conditions.php"
    "debug-db-structure.php"
    "debug-due-date-calc.php"
    "debug-task-creation.php"
    "debug-task-dates.php"
    "debug-task-generation.php"
    "extract-tasks.php"
    "find-offer-anchored-tasks.php"
    "fix-earnest-money-tasks.php"
    "fix-task-definitions.php"
    "implement-modular-tasks.php"
    "link-loan-commitment-tasks.php"
    "link-templates-to-tasks.php"
    "list-all-tasks.php"
    "list-generated-task-keys.php"
    "list-task-definitions.php"
    "run-migration.php"
    "show-offer-tasks.php"
    "sync-templates.php"
    "test-assign-task.php"
    "test-auth.php"
    "test-auto-assign.php"
    "test-complete-workflow.php"
    "test-create-assigned-task.php"
    "test-create-transaction.php"
    "test-document-endpoints.php"
    "test-email-notifications.php"
    "test-extraction.php"
    "test-filtering.php"
    "test-fixes.php"
    "test-modular-engine.php"
    "test-notifications.php"
    "test-parse-base-template.php"
    "test-party-crud.php"
    "test-repositories.php"
    "test-simple-get-tasks.php"
    "test-smtp.php"
    "test-task-assignment.php"
    "test-task-crud.php"
    "test-task-notification.php"
    "test-template-engine.php"
    "test-templates.php"
    "test-transaction-create.php"
    "test_phase2_filtering.php"
    "test_phase3_rental_filtering.php"
    "test_phase3_rental_filtering_wp.php"
    "test_phase4_commercial_filtering_wp.php"
    "test_phase5_comprehensive.php"
    "test_simple.php"
    "test_task_filtering.php"
    "test-api-endpoints.php"
)

removed=0
not_found=0

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        rm "$file"
        echo "✓ Removed: $file"
        ((removed++))
    else
        ((not_found++))
    fi
done

echo ""
echo "===================================="
echo "Summary:"
echo "  Removed: $removed files"
echo "  Not found: $not_found files"
echo ""
echo "✅ Cleanup complete!"
