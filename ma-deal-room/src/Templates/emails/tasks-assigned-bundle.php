<?php
/**
 * Tasks Assigned Bundle Email Template
 *
 * Used when multiple tasks are assigned to a user at the same time
 * Variables: $tasks_count, $tasks, $transaction, $user_name, $summary_url
 */

$subject = sprintf('[MA Deal Room] %d Tasks Assigned to You', $tasks_count ?? count($tasks ?? []));
$preheader = sprintf('You have been assigned %d new tasks', $tasks_count ?? count($tasks ?? []));
$cta_url = $summary_url ?? admin_url('admin.php?page=ma-deal-room#/tasks');
$cta_text = isset($transaction) && $transaction ? 'View Transaction & Tasks' : 'View All Tasks';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				<?php echo esc_html($tasks_count ?? count($tasks ?? [])); ?> Tasks Assigned to You
			</h2>
		</td>
	</tr>

	<!-- Main Content -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Hello <?php echo esc_html($user_name ?? 'there'); ?>,
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding-bottom: 24px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				<?php if (isset($transaction) && $transaction): ?>
					You have been assigned <strong><?php echo esc_html($tasks_count ?? count($tasks ?? [])); ?> tasks</strong> for the transaction:
					<strong><?php echo esc_html($transaction->property_address ?? 'N/A'); ?></strong>
				<?php else: ?>
					You have been assigned <strong><?php echo esc_html($tasks_count ?? count($tasks ?? [])); ?> new tasks</strong>.
				<?php endif; ?>
			</p>
		</td>
	</tr>

	<!-- Tasks List -->
	<?php if (isset($tasks) && is_array($tasks) && !empty($tasks)): ?>
		<tr>
			<td style="padding-bottom: 24px;">
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #ebf8ff 0%, #e0f2fe 100%); border-radius: 8px; border: 2px solid #0073aa; overflow: hidden;">
					<tr>
						<td style="padding: 24px;">
							<h3 style="margin: 0 0 16px 0; padding: 0; color: #1e40af; font-size: 18px; font-weight: 700;">
								Task Summary
							</h3>

							<!-- Task List -->
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<?php
								$display_tasks = array_slice($tasks, 0, \MADealRoom\Services\NotificationQueueService::MAX_BUNDLE_ITEMS);
								foreach ($display_tasks as $index => $task_data):
									$task = is_array($task_data) ? (object)$task_data['task'] : $task_data;
								?>
								<tr>
									<td style="padding: 12px 16px; <?php echo $index > 0 ? 'border-top: 1px solid #bfdbfe;' : ''; ?> background-color: #ffffff; border-radius: 6px;">
										<table border="0" cellpadding="0" cellspacing="0" width="100%">
											<tr>
												<td style="width: 24px; vertical-align: top; padding-right: 12px;">
													<div style="width: 24px; height: 24px; border-radius: 50%; background-color: #3b82f6; color: #ffffff; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; text-align: center; line-height: 24px;">
														<?php echo $index + 1; ?>
													</div>
												</td>
												<td style="vertical-align: top;">
													<p style="margin: 0 0 4px 0; padding: 0; color: #1f2937; font-size: 15px; font-weight: 600; line-height: 20px;">
														<?php echo esc_html($task->title ?? 'Untitled Task'); ?>
													</p>
													<?php if (isset($task->description) && $task->description): ?>
														<p style="margin: 0; padding: 0; color: #6b7280; font-size: 13px; line-height: 18px;">
															<?php echo esc_html(mb_substr($task->description, 0, 80) . (mb_strlen($task->description) > 80 ? '...' : '')); ?>
														</p>
													<?php endif; ?>
													<?php if (isset($task->due_at) && $task->due_at): ?>
														<p style="margin: 4px 0 0 0; padding: 0; color: #dc2626; font-size: 12px; font-weight: 600;">
															Due: <?php echo date('M j, Y', strtotime($task->due_at)); ?>
														</p>
													<?php endif; ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<?php if ($index < count($display_tasks) - 1): ?>
								<tr><td style="height: 8px;"></td></tr>
								<?php endif; ?>
								<?php endforeach; ?>
							</table>

							<!-- More tasks indicator -->
							<?php if (count($tasks) > \MADealRoom\Services\NotificationQueueService::MAX_BUNDLE_ITEMS): ?>
							<div style="margin-top: 16px; padding: 12px; background-color: #ffffff; border-radius: 6px; text-align: center;">
								<p style="margin: 0; padding: 0; color: #6b7280; font-size: 14px;">
									<strong>+<?php echo count($tasks) - \MADealRoom\Services\NotificationQueueService::MAX_BUNDLE_ITEMS; ?> more tasks</strong>
								</p>
							</div>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	<?php endif; ?>

	<!-- Call to Action -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Click the button below to view and manage all your assigned tasks.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
