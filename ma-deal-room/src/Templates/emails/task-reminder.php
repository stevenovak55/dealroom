<?php
/**
 * Task Reminder Email Template
 * Variables: $task, $transaction, $assignee, $days_until_due
 */

$subject = sprintf('[MA Deal Room] Task Reminder: %s', $task->title ?? 'Task');
$preheader = sprintf('Task due in %d day(s): %s', $days_until_due ?? 0, $task->title ?? '');
$cta_url = admin_url('admin.php?page=ma-deal-room#/transactions/' . ($transaction->id ?? ''));
$cta_text = 'Complete Task';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #dc2626; font-size: 24px; font-weight: 600; line-height: 32px;">
				⏰ Task Reminder
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
				This is a reminder that you have a task due
				<?php
				$days = $days_until_due ?? 0;
				if ($days == 0) {
					echo '<strong style="color: #dc2626;">TODAY</strong>';
				} elseif ($days == 1) {
					echo '<strong style="color: #f59e0b;">TOMORROW</strong>';
				} else {
					echo sprintf('<strong style="color: #0073aa;">in %d days</strong>', $days);
				}
				?>:
			</p>
		</td>
	</tr>

	<!-- Task Details Card with Urgency -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-radius: 8px; border: 2px solid #dc2626; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Task Title -->
						<h3 style="margin: 0 0 16px 0; padding: 0; color: #991b1b; font-size: 20px; font-weight: 700;">
							<?php echo esc_html($task->title ?? 'Untitled Task'); ?>
						</h3>

						<!-- Urgency Banner -->
						<div style="margin-bottom: 16px; padding: 12px; background-color: #991b1b; border-radius: 4px;">
							<p style="margin: 0; padding: 0; color: #ffffff; font-size: 14px; font-weight: 700; text-align: center;">
								<?php if ($days == 0): ?>
									⚠️ DUE TODAY - Immediate Action Required
								<?php elseif ($days == 1): ?>
									🔔 DUE TOMORROW - Action Required Soon
								<?php else: ?>
									📌 Due in <?php echo $days; ?> days
								<?php endif; ?>
							</p>
						</div>

						<!-- Task Description -->
						<?php if (isset($task->description) && $task->description): ?>
						<div style="margin-bottom: 20px; padding: 16px; background-color: #ffffff; border-radius: 6px;">
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
									<p style="margin: 0; padding: 0; color: #6b7280; font-size: 13px; font-weight: 600; text-transform: uppercase;">
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
								<td style="padding: 12px 0 0 0;">
									<p style="margin: 0; padding: 0; color: #6b7280; font-size: 13px; font-weight: 600; text-transform: uppercase;">
										📅 Due Date
									</p>
									<p style="margin: 4px 0 0 0; padding: 0; color: #dc2626; font-size: 16px; font-weight: 700;">
										<?php echo date('l, F j, Y \a\t g:i A', strtotime($task->due_at)); ?>
									</p>
								</td>
							</tr>
							<?php endif; ?>
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
				Please complete this task as soon as possible to keep the transaction on track.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
