-- Migration: Fix Task Due Calculation Values
-- Version: 007
-- Description: Correct task due_calculation fields to use proper anchors (Offer, PS, LoanCommitment, Closing) instead of listing_date

UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Offer+2d' WHERE task_key = 'collect-earnest-money-deposit';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Offer+3d' WHERE task_key = 'deposit-emd-into-escrow-account';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Offer+3d' WHERE task_key = 'send-emd-receipt-to-all-parties';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Offer+3d' WHERE task_key = 'create-escrow-account-ledger-entry';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+3d' WHERE task_key = 'submit-formal-mortgage-application';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+8d' WHERE task_key = 'order-property-appraisal';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+30d' WHERE task_key = 'obtain-mortgage-commitment';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'LoanCommitment+1d' WHERE task_key = 'review-loan-terms-and-conditions';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'LoanCommitment+7d' WHERE task_key = 'clear-loan-conditions';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+5d' WHERE task_key = 'schedule-home-inspection';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+9d' WHERE task_key = 'review-inspection-report';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+14d' WHERE task_key = 'negotiate-inspection-items';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+15d' WHERE task_key = 'finalize-inspection-agreement';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+5d' WHERE task_key = 'schedule-specialized-inspections';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+25d' WHERE task_key = 'coordinate-repair-work';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Offer+7d' WHERE task_key = 'negotiate-p-s-terms';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+1d' WHERE task_key = 'update-transaction-timeline';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'PS+18d' WHERE task_key = 'address-appraisal-issues';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-21d' WHERE task_key = 'order-title-insurance';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-21d' WHERE task_key = 'order-municipal-lien-certificate';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-18d' WHERE task_key = 'review-title-commitment';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-14d' WHERE task_key = 'clear-title-issues';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-10d' WHERE task_key = 'order-homeowners-insurance';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-7d' WHERE task_key = 'coordinate-utility-transfers';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-2d' WHERE task_key = 'schedule-final-walkthrough';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-3d' WHERE task_key = 'confirm-wire-instructions';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-1d' WHERE task_key = 'conduct-final-walkthrough';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing+0d' WHERE task_key = 'address-walkthrough-issues';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing-2d' WHERE task_key = 'confirm-closing-attendance';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing+0d' WHERE task_key = 'disburse-funds';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'Closing+3d' WHERE task_key = 'confirm-deed-recording';
UPDATE {prefix}ma_deal_task_definitions SET due_calculation = 'FirstMeeting+0d' WHERE task_key = 'provide-agency-disclosure-form';
UPDATE {prefix}ma_deal_template_tasks SET override_due_calculation = NULL WHERE override_due_calculation IS NOT NULL AND override_due_calculation != '';
