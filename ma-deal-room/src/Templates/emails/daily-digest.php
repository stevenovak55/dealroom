<?php
/**
 * Daily Digest Email Template
 * Variables: $user_name, $pending_tasks, $upcoming_deadlines, $recent_updates
 */

$subject = '[MA Deal Room] Your Daily Digest - ' . date('F j, Y');
$preheader = sprintf('%d pending tasks, %d upcoming deadlines', count($pending_tasks ?? []), count($upcoming_deadlines ?? []));
$cta_url = admin_url('admin.php?page=ma-deal-room');
$cta_text = 'View Dashboard';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				Your Daily Digest
			</h2>
			<p style="margin: 8px 0 0 0; padding: 0; color: #6b7280; font-size: 14px;">
				<?php echo date('l, F j, Y'); ?>
			</p>
		</td>
	</tr>

	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Good morning <?php echo esc_html($user_name ?? 'there'); ?>! Here's your activity summary for today.
			</p>
		</td>
	</tr>

	<!-- Summary Cards -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<!-- Pending Tasks -->
					<td style="width: 48%; padding: 20px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 8px; vertical-align: top;">
						<p style="margin: 0 0 8px 0; padding: 0; color: #92400e; font-size: 13px; font-weight: 600; text-transform: uppercase;">
							Pending Tasks
						</p>
						<p style="margin: 0; padding: 0; color: #78350f; font-size: 36px; font-weight: 700;">
							<?php echo count($pending_tasks ?? []); ?>
						</p>
					</td>
					<td style="width: 4%;"></td>
					<!-- Upcoming Deadlines -->
					<td style="width: 48%; padding: 20px; background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%); border-radius: 8px; vertical-align: top;">
						<p style="margin: 0 0 8px 0; padding: 0; color: #991b1b; font-size: 13px; font-weight: 600; text-transform: uppercase;">
							Upcoming Deadlines
						</p>
						<p style="margin: 0; padding: 0; color: #7f1d1d; font-size: 36px; font-weight: 700;">
							<?php echo count($upcoming_deadlines ?? []); ?>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Pending Tasks Section -->
	<?php if (!empty($pending_tasks)): ?>
	<tr>
		<td style="padding-bottom: 24px;">
			<h3 style="margin: 0 0 16px 0; padding: 0; color: #2c3e50; font-size: 18px; font-weight: 600;">
				📋 Your Pending Tasks
			</h3>
			<?php foreach (array_slice($pending_tasks, 0, 5) as $index => $task): ?>
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 12px; background-color: #f9fafb; border-left: 3px solid #f59e0b; border-radius: 4px;">
					<tr>
						<td style="padding: 12px 16px;">
							<p style="margin: 0 0 4px 0; padding: 0; color: #1f2937; font-size: 15px; font-weight: 600;">
								<?php echo esc_html($task['title'] ?? 'Untitled Task'); ?>
							</p>
							<p style="margin: 0; padding: 0; color: #6b7280; font-size: 13px;">
								<?php echo esc_html($task['transaction'] ?? ''); ?>
								<?php if (isset($task['due_at']) && $task['due_at']): ?>
									• Due: <strong><?php echo date('M j', strtotime($task['due_at'])); ?></strong>
								<?php endif; ?>
							</p>
						</td>
					</tr>
				</table>
			<?php endforeach; ?>
			<?php if (count($pending_tasks) > 5): ?>
				<p style="margin: 8px 0 0 0; padding: 0; color: #6b7280; font-size: 13px; font-style: italic;">
					+ <?php echo count($pending_tasks) - 5; ?> more tasks...
				</p>
			<?php endif; ?>
		</td>
	</tr>
	<?php endif; ?>

	<!-- Upcoming Deadlines Section -->
	<?php if (!empty($upcoming_deadlines)): ?>
	<tr>
		<td style="padding-bottom: 24px;">
			<h3 style="margin: 0 0 16px 0; padding: 0; color: #dc2626; font-size: 18px; font-weight: 600;">
				⏰ Upcoming Deadlines (Next 7 Days)
			</h3>
			<?php foreach (array_slice($upcoming_deadlines, 0, 5) as $deadline): ?>
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 12px; background-color: #fef2f2; border-left: 3px solid #dc2626; border-radius: 4px;">
					<tr>
						<td style="padding: 12px 16px;">
							<p style="margin: 0 0 4px 0; padding: 0; color: #991b1b; font-size: 15px; font-weight: 600;">
								<?php echo esc_html($deadline['title'] ?? ''); ?>
							</p>
							<p style="margin: 0; padding: 0; color: #7f1d1d; font-size: 13px;">
								<?php echo esc_html($deadline['transaction'] ?? ''); ?>
								• Due: <strong><?php echo date('l, F j', strtotime($deadline['due_at'] ?? 'now')); ?></strong>
							</p>
						</td>
					</tr>
				</table>
			<?php endforeach; ?>
		</td>
	</tr>
	<?php endif; ?>

	<!-- Recent Updates Section -->
	<?php if (!empty($recent_updates)): ?>
	<tr>
		<td style="padding-bottom: 24px;">
			<h3 style="margin: 0 0 16px 0; padding: 0; color: #2c3e50; font-size: 18px; font-weight: 600;">
				🔔 Recent Updates
			</h3>
			<?php foreach (array_slice($recent_updates, 0, 3) as $update): ?>
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 12px; background-color: #eff6ff; border-left: 3px solid #0073aa; border-radius: 4px;">
					<tr>
						<td style="padding: 12px 16px;">
							<p style="margin: 0 0 4px 0; padding: 0; color: #1e3a8a; font-size: 14px; font-weight: 600;">
								<?php echo esc_html($update['title'] ?? ''); ?>
							</p>
							<p style="margin: 0; padding: 0; color: #4b5563; font-size: 13px;">
								<?php echo esc_html($update['description'] ?? ''); ?>
							</p>
						</td>
					</tr>
				</table>
			<?php endforeach; ?>
		</td>
	</tr>
	<?php endif; ?>

	<!-- No Activity Message -->
	<?php if (empty($pending_tasks) && empty($upcoming_deadlines) && empty($recent_updates)): ?>
	<tr>
		<td style="padding: 32px; text-align: center; background-color: #f0fdf4; border-radius: 8px;">
			<p style="margin: 0; padding: 0; color: #059669; font-size: 16px; font-weight: 600;">
				✅ You're all caught up!
			</p>
			<p style="margin: 8px 0 0 0; padding: 0; color: #047857; font-size: 14px;">
				No pending tasks or upcoming deadlines at the moment.
			</p>
		</td>
	</tr>
	<?php endif; ?>

	<!-- Footer Message -->
	<tr>
		<td style="padding-top: 16px;">
			<p style="margin: 0; padding: 0; color: #6b7280; font-size: 14px; line-height: 20px;">
				Have a productive day! Visit your dashboard to manage all your transactions and tasks.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
