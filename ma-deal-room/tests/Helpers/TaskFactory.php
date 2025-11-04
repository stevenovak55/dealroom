<?php
/**
 * Task Factory
 *
 * Helper class for creating test tasks.
 *
 * @package MADealRoom\Tests\Helpers
 */

namespace MADealRoom\Tests\Helpers;

/**
 * Task Factory Class
 */
class TaskFactory
{
    /**
     * Create a test task
     *
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function create(array $attributes = []): array
    {
        $defaults = [
            'task_id' => self::randomUuid(),
            'transaction_id' => self::randomUuid(),
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'category' => 'general',
            'responsible_party' => 'buyer_agent',
            'status' => 'pending',
            'priority' => 'medium',
            'sequence' => 1,
            'is_required' => 1,
            'can_be_skipped' => 0,
            'anchor_type' => 'offer_accepted_date',
            'offset_days' => 7,
            'offset_direction' => 'after',
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'created_at' => self::now(),
            'updated_at' => self::now(),
        ];

        return array_merge($defaults, $attributes);
    }

    /**
     * Create multiple test tasks
     *
     * @param int $count Number of tasks to create
     * @param array $attributes Common attributes for all tasks
     * @return array<array> Array of task data
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        $tasks = [];
        for ($i = 0; $i < $count; $i++) {
            $tasks[] = self::create(array_merge($attributes, ['sequence' => $i + 1]));
        }
        return $tasks;
    }

    /**
     * Create a pending task
     *
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function createPending(array $attributes = []): array
    {
        return self::create(array_merge([
            'status' => 'pending',
        ], $attributes));
    }

    /**
     * Create an in-progress task
     *
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function createInProgress(array $attributes = []): array
    {
        return self::create(array_merge([
            'status' => 'in_progress',
            'started_at' => self::now(),
        ], $attributes));
    }

    /**
     * Create a completed task
     *
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function createCompleted(array $attributes = []): array
    {
        return self::create(array_merge([
            'status' => 'complete',
            'started_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            'completed_at' => self::now(),
        ], $attributes));
    }

    /**
     * Create an overdue task
     *
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function createOverdue(array $attributes = []): array
    {
        return self::create(array_merge([
            'status' => 'pending',
            'due_date' => date('Y-m-d', strtotime('-3 days')),
        ], $attributes));
    }

    /**
     * Create a skipped task
     *
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function createSkipped(array $attributes = []): array
    {
        return self::create(array_merge([
            'status' => 'skipped',
            'can_be_skipped' => 1,
            'skipped_at' => self::now(),
            'skipped_reason' => 'Not applicable',
        ], $attributes));
    }

    /**
     * Create a high priority task
     *
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function createHighPriority(array $attributes = []): array
    {
        return self::create(array_merge([
            'priority' => 'high',
            'title' => 'High Priority Task',
        ], $attributes));
    }

    /**
     * Create a task with dependencies
     *
     * @param array $dependencyIds Array of task IDs this task depends on
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function createWithDependencies(array $dependencyIds, array $attributes = []): array
    {
        $task = self::create($attributes);
        $task['dependencies'] = $dependencyIds;
        return $task;
    }

    /**
     * Create a task assigned to a specific party
     *
     * @param string $party Responsible party
     * @param array $attributes Task attributes
     * @return array Task data
     */
    public static function createForParty(string $party, array $attributes = []): array
    {
        $titles = [
            'buyer' => 'Buyer Task',
            'seller' => 'Seller Task',
            'buyer_agent' => 'Buyer Agent Task',
            'seller_agent' => 'Seller Agent Task',
            'attorney' => 'Attorney Task',
            'lender' => 'Lender Task',
        ];

        return self::create(array_merge([
            'responsible_party' => $party,
            'title' => $titles[$party] ?? 'Task',
        ], $attributes));
    }

    /**
     * Create tasks for a transaction timeline
     *
     * @param string $transactionId Transaction ID
     * @param string $offerAcceptedDate Offer accepted date
     * @return array<array> Array of task data
     */
    public static function createTimeline(string $transactionId, string $offerAcceptedDate): array
    {
        return [
            self::create([
                'transaction_id' => $transactionId,
                'title' => 'Home Inspection',
                'anchor_type' => 'offer_accepted_date',
                'offset_days' => 7,
                'due_date' => date('Y-m-d', strtotime($offerAcceptedDate . ' +7 days')),
                'sequence' => 1,
            ]),
            self::create([
                'transaction_id' => $transactionId,
                'title' => 'Mortgage Application',
                'anchor_type' => 'offer_accepted_date',
                'offset_days' => 3,
                'due_date' => date('Y-m-d', strtotime($offerAcceptedDate . ' +3 days')),
                'sequence' => 2,
            ]),
            self::create([
                'transaction_id' => $transactionId,
                'title' => 'Sign P&S Agreement',
                'anchor_type' => 'offer_accepted_date',
                'offset_days' => 14,
                'due_date' => date('Y-m-d', strtotime($offerAcceptedDate . ' +14 days')),
                'sequence' => 3,
            ]),
        ];
    }

    /**
     * Generate a random UUID
     *
     * @return string UUID
     */
    private static function randomUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Get current timestamp
     *
     * @return string Timestamp
     */
    private static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
