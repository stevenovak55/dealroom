<?php
/**
 * Task Assigned Email Template
 * Variables: $task, $transaction, $assignee, $assigner_name
 */

$subject = sprintf('[MA Deal Room] New Task Assigned: %s', $task->title ?? 'Task');
$preheader = sprintf('You have been assigned a new task: %s', $task->title ?? '');
$cta_url = admin_url('admin.php?page=ma-deal-room#/transactions/' . ($transaction->id ?? ''));
$cta_text = 'View Task';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				New Task Assigned
			</h2>
		</td>
	</tr>

	<!-- Main Content -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Hello <?php echo esc_html($assignee->contact_name ?? 'there'); ?>,
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				<?php if (isset($assigner_name) && $assigner_name): ?>
					<strong><?php echo esc_html($assigner_name); ?></strong> has assigned a new task to you:
				<?php else: ?>
					A new task has been assigned to you:
				<?php endif; ?>
			</p>
		</td>
	</tr>

	<!-- Task Details Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #ebf8ff 0%, #e0f2fe 100%); border-radius: 8px; border: 2px solid #0073aa; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Task Title -->
						<h3 style="margin: 0 0 16px 0; padding: 0; color: #1e40af; font-size: 20px; font-weight: 700;">
							<?php echo esc_html($task->title ?? 'Untitled Task'); ?>
						</h3>

						<!-- Task Description -->
						<?php if (isset($task->description) && $task->description): ?>
						<div style="margin-bottom: 20px; padding: 16px; background-color: #ffffff; border-radius: 6px; border-left: 3px solid #3b82f6;">
							<p style="margin: 0; padding: 0; color: #374151; font-size: 15px; line-height: 22px;">
								<?php echo nl2br(esc_html($task->description)); ?>
							</p>
						</div>
						<?php endif; ?>

						<!-- Task Meta Information -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<!-- Transaction -->
							<tr>
								<td style="padding: 8px 0;">
									<p style="margin: 0; padding: 0; color: #6b7280; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
										📍 Transaction
									</p>
									<p style="margin: 4px 0 0 0; padding: 0; color: #1f2937; font-size: 15px; font-weight: 500;">
										<?php echo esc_html($transaction->property_address ?? 'N/A'); ?>
									</p>
								</td>
							</tr>

							<!-- Due Date -->
							<?php if (isset($task->due_at) && $task->due_at): ?>
							<tr>
								<td style="padding: 12px 0 8px 0;">
									<p style="margin: 0; padding: 0; color: #6b7280; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
										📅 Due Date
									</p>
									<p style="margin: 4px 0 0 0; padding: 0; color: #dc2626; font-size: 15px; font-weight: 600;">
										<?php echo date('l, F j, Y', strtotime($task->due_at)); ?>
									</p>
								</td>
							</tr>
							<?php endif; ?>

							<!-- Status -->
							<tr>
								<td style="padding: 12px 0 0 0;">
									<p style="margin: 0; padding: 0; color: #6b7280; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
										Status
									</p>
									<p style="margin: 4px 0 0 0; padding: 0;">
										<span style="display: inline-block; padding: 4px 12px; background-color: #fef3c7; color: #92400e; font-size: 13px; font-weight: 600; border-radius: 12px;">
											<?php echo esc_html(ucfirst($task->status ?? 'Pending')); ?>
										</span>
									</p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Call to Action -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Click the button below to view the full transaction details and manage this task.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
