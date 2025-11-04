<?php
/**
 * Email Verification Template
 * Variables: $user_name, $verification_url, $expiry_hours
 */

$subject = 'Verify Your Email Address - MA Deal Room';
$preheader = 'Please confirm your email address to activate your account.';
$cta_url = $verification_url ?? '';
$cta_text = 'Verify Email Address';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				Verify Your Email Address
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
				Thank you for registering with MA Deal Room! To complete your registration and activate your account, please verify your email address by clicking the button below.
			</p>
		</td>
	</tr>

	<!-- Security Notice -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 4px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0; padding: 0; color: #78350f; font-size: 14px; line-height: 20px;">
							<strong>⚠️ Security Notice:</strong> This verification link will expire in <strong><?php echo esc_html($expiry_hours ?? 24); ?> hours</strong> for your security.
						</p>
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
				<?php echo esc_url($verification_url ?? ''); ?>
			</p>
		</td>
	</tr>

	<!-- Ignore Message -->
	<tr>
		<td style="padding-top: 16px;">
			<p style="margin: 0; padding: 0; color: #a0aec0; font-size: 14px; line-height: 20px; font-style: italic;">
				If you didn't create an account with MA Deal Room, please ignore this email. No account will be created without email verification.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
