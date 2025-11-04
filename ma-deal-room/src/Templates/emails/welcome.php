<?php
/**
 * Welcome Email Template
 * Variables: $user_name, $user_email, $login_url
 */

$subject = 'Welcome to MA Deal Room!';
$preheader = 'Your account has been created successfully.';
$cta_url = $login_url ?? admin_url('admin.php?page=ma-deal-room');
$cta_text = 'Get Started';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				Welcome to MA Deal Room, <?php echo esc_html($user_name ?? 'there'); ?>!
			</h2>
		</td>
	</tr>

	<!-- Main Content -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Thank you for joining MA Deal Room, the premier platform for managing real estate transactions in Massachusetts.
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Your account has been successfully created with the email address:
			</p>
			<p style="margin: 8px 0 0 0; padding: 12px; background-color: #f7fafc; border-radius: 4px; color: #2d3748; font-size: 15px; font-weight: 500;">
				<?php echo esc_html($user_email ?? ''); ?>
			</p>
		</td>
	</tr>

	<!-- Features -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0 0 16px 0; padding: 0; color: #2c3e50; font-size: 17px; font-weight: 600;">
				What you can do with MA Deal Room:
			</p>

			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td style="padding: 12px; background-color: #ebf8ff; border-left: 3px solid #0073aa; margin-bottom: 8px;">
						<p style="margin: 0; padding: 0; color: #2c5282; font-size: 14px; font-weight: 600;">📋 Manage Transactions</p>
						<p style="margin: 4px 0 0 0; padding: 0; color: #4a5568; font-size: 13px; line-height: 18px;">
							Track all your real estate deals from offer to closing
						</p>
					</td>
				</tr>
			</table>

			<div style="height: 8px;"></div>

			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td style="padding: 12px; background-color: #f0fdf4; border-left: 3px solid #10b981; margin-bottom: 8px;">
						<p style="margin: 0; padding: 0; color: #065f46; font-size: 14px; font-weight: 600;">✅ Track Tasks</p>
						<p style="margin: 4px 0 0 0; padding: 0; color: #4a5568; font-size: 13px; line-height: 18px;">
							Never miss a deadline with automated task management
						</p>
					</td>
				</tr>
			</table>

			<div style="height: 8px;"></div>

			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td style="padding: 12px; background-color: #fef3c7; border-left: 3px solid #f59e0b; margin-bottom: 8px;">
						<p style="margin: 0; padding: 0; color: #92400e; font-size: 14px; font-weight: 600;">📄 Manage Documents</p>
						<p style="margin: 4px 0 0 0; padding: 0; color: #4a5568; font-size: 13px; line-height: 18px;">
							Securely store and share transaction documents
						</p>
					</td>
				</tr>
			</table>

			<div style="height: 8px;"></div>

			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td style="padding: 12px; background-color: #fce7f3; border-left: 3px solid #ec4899; margin-bottom: 8px;">
						<p style="margin: 0; padding: 0; color: #9f1239; font-size: 14px; font-weight: 600;">👥 Collaborate</p>
						<p style="margin: 4px 0 0 0; padding: 0; color: #4a5568; font-size: 13px; line-height: 18px;">
							Work seamlessly with agents, buyers, sellers, and vendors
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Next Steps -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Ready to get started? Click the button below to access your dashboard.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
