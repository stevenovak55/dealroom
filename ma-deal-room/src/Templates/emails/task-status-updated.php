<?php
/**
 * Task Status Updated Email Template
 * Variables: $task, $transaction, $old_status, $new_status, $assignee, $updated_by
 */

$subject = sprintf('[MA Deal Room] Task Updated: %s', $task->title ?? 'Task');
$preheader = sprintf('Task status changed from %s to %s', ucfirst($old_status ?? 'unknown'), ucfirst($new_status ?? 'unknown'));
$cta_url = admin_url('admin.php?page=ma-deal-room#/transactions/' . ($transaction->id ?? ''));
$cta_text = 'View Transaction';

// Determine status color based on new status
$status_colors = [
	'completed' => ['bg' => '#dcfce7', 'border' => '#10b981', 'text' => '#065f46'],
	'in_progress' => ['bg' => '#dbeafe', 'border' => '#0073aa', 'text' => '#1e40af'],
	'pending' => ['bg' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#92400e'],
	'blocked' => ['bg' => '#fee2e2', 'border' => '#dc2626', 'text' => '#991b1b'],
];

$status_key = strtolower($new_status ?? 'pending');
$colors = $status_colors[$status_key] ?? $status_colors['pending'];

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				Task Status Updated
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
				The status of your task has been updated
				<?php if (isset($updated_by) && $updated_by): ?>
					by <strong><?php echo esc_html($updated_by); ?></strong>
				<?php endif; ?>:
			</p>
		</td>
	</tr>

	<!-- Status Change Indicator -->
	<tr>
		<td align="center" style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
				<tr>
					<!-- Old Status -->
					<td style="padding: 12px 20px; background-color: #f3f4f6; color: #6b7280; font-size: 16px; font-weight: 600; border-radius: 6px;">
						<?php echo esc_html(ucfirst($old_status ?? 'Unknown')); ?>
					</td>
					<!-- Arrow -->
					<td style="padding: 0 16px; color: #9ca3af; font-size: 24px; font-weight: bold;">
						→
					</td>
					<!-- New Status -->
					<td style="padding: 12px 20px; background-color: <?php echo $colors['bg']; ?>; color: <?php echo $colors['text']; ?>; font-size: 16px; font-weight: 700; border-radius: 6px; border: 2px solid <?php echo $colors['border']; ?>;">
						<?php echo esc_html(ucfirst($new_status ?? 'Unknown')); ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Task Details Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: <?php echo $colors['bg']; ?>; border-radius: 8px; border: 2px solid <?php echo $colors['border']; ?>; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Task Title -->
						<h3 style="margin: 0 0 16px 0; padding: 0; color: <?php echo $colors['text']; ?>; font-size: 20px; font-weight: 700;">
							<?php echo esc_html($task->title ?? 'Untitled Task'); ?>
						</h3>

						<!-- Task Description -->
						<?php if (isset($task->description) && $task->description): ?>
						<div style="margin-bottom: 20px; padding: 16px; background-color: #ffffff; border-radius: 6px;">
							<p style="margin: 0; padding: 0; color: #374151; font-size: 15px; line-height: 22px;">
								<?php echo nl2br(esc_html($task->description)); ?>
							</p>
						</div>
						<?php endif; ?>

						<!-- Task Meta Information -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 6px;">
							<tr>
								<td style="padding: 16px;">
									<!-- Transaction -->
									<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
										📍 Transaction
									</p>
									<p style="margin: 0 0 16px 0; padding: 0; color: #1f2937; font-size: 15px; font-weight: 600;">
										<?php echo esc_html($transaction->property_address ?? 'N/A'); ?>
									</p>

									<!-- Due Date -->
									<?php if (isset($task->due_at) && $task->due_at): ?>
									<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
										📅 Due Date
									</p>
									<p style="margin: 0; padding: 0; color: #1f2937; font-size: 15px; font-weight: 600;">
										<?php echo date('l, F j, Y', strtotime($task->due_at)); ?>
									</p>
									<?php endif; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Status-specific Messages -->
	<?php if ($status_key === 'completed'): ?>
	<tr>
		<td style="padding-bottom: 20px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f0fdf4; border-left: 4px solid #10b981; border-radius: 4px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0; padding: 0; color: #065f46; font-size: 15px; line-height: 22px;">
							<strong>✅ Great job!</strong> This task has been marked as complete. Thank you for keeping the transaction on track.
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php elseif ($status_key === 'blocked'): ?>
	<tr>
		<td style="padding-bottom: 20px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fef2f2; border-left: 4px solid #dc2626; border-radius: 4px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0; padding: 0; color: #991b1b; font-size: 15px; line-height: 22px;">
							<strong>⚠️ Attention needed:</strong> This task is currently blocked. Please review the transaction details and resolve any blockers as soon as possible.
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php elseif ($status_key === 'in_progress'): ?>
	<tr>
		<td style="padding-bottom: 20px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #eff6ff; border-left: 4px solid #0073aa; border-radius: 4px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0; padding: 0; color: #1e40af; font-size: 15px; line-height: 22px;">
							<strong>🚀 In Progress:</strong> This task is now being worked on. Keep up the momentum!
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php endif; ?>

	<!-- Call to Action -->
	<tr>
		<td style="padding-top: 8px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Click the button below to view the full transaction details.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
