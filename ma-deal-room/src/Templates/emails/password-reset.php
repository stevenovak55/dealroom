<?php
/**
 * Password Reset Email Template
 * Variables: $user_name, $reset_url, $expiry_hours, $ip_address
 */

$subject = 'Reset Your Password - MA Deal Room';
$preheader = 'A password reset has been requested for your account.';
$cta_url = $reset_url ?? '';
$cta_text = 'Reset Password';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				Password Reset Request
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
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				We received a request to reset the password for your MA Deal Room account. Click the button below to create a new password.
			</p>
		</td>
	</tr>

	<!-- Security Info Box -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fef2f2; border-left: 4px solid #ef4444; border-radius: 4px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0 0 8px 0; padding: 0; color: #991b1b; font-size: 14px; font-weight: 600;">
							🔒 Security Information
						</p>
						<p style="margin: 0; padding: 0; color: #7f1d1d; font-size: 13px; line-height: 18px;">
							This link expires in <strong><?php echo esc_html($expiry_hours ?? 1); ?> hour(s)</strong>
						</p>
						<?php if (isset($ip_address) && $ip_address): ?>
						<p style="margin: 8px 0 0 0; padding: 0; color: #7f1d1d; font-size: 12px; line-height: 16px;">
							Request originated from: <code style="background-color: #fee2e2; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($ip_address); ?></code>
						</p>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Alternative Link -->
	<tr>
		<td style="padding-top: 24px; padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #718096; font-size: 13px; line-height: 20px;">
				If the button above doesn't work, copy and paste this URL into your browser:
			</p>
			<p style="margin: 8px 0 0 0; padding: 12px; background-color: #f7fafc; border-radius: 4px; color: #2d3748; font-size: 12px; word-break: break-all; font-family: monospace;">
				<?php echo esc_url($reset_url ?? ''); ?>
			</p>
		</td>
	</tr>

	<!-- Ignore Message -->
	<tr>
		<td style="padding-top: 16px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ecfdf5; border-left: 4px solid #10b981; border-radius: 4px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0; padding: 0; color: #065f46; font-size: 14px; line-height: 20px;">
							<strong>Didn't request this?</strong> If you didn't request a password reset, please ignore this email. Your password will remain unchanged, and no action is required.
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
