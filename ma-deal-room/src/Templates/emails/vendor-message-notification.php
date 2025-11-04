<?php
/**
 * Vendor Message Notification Email Template
 * Sent when a new message is received (agent <-> vendor)
 *
 * Variables: $recipient_name, $sender_name, $vendor_type, $property_address, $message, $portal_url
 */

$subject = 'New Message: ' . ($property_address ?? 'Transaction');
$preheader = 'You have a new message from ' . ($sender_name ?? 'a participant');
$cta_url = $portal_url ?? admin_url('admin.php?page=ma-deal-room');
$cta_text = 'View & Reply';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				💬 New Message Received
			</h2>
		</td>
	</tr>

	<!-- Main Content -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Hi <?php echo esc_html($recipient_name ?? 'there'); ?>,
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				You have a new message from <strong><?php echo esc_html($sender_name ?? 'a participant'); ?></strong>.
			</p>
		</td>
	</tr>

	<!-- Message Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); border-radius: 8px; border: 2px solid #6366f1; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Service Type -->
						<div style="margin-bottom: 16px; padding: 12px; background-color: #ffffff; border-radius: 6px;">
							<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								Regarding
							</p>
							<p style="margin: 0; padding: 0; color: #4338ca; font-size: 16px; font-weight: 700;">
								<?php echo esc_html($vendor_type ?? 'General Service'); ?> • <?php echo esc_html($property_address ?? 'Transaction'); ?>
							</p>
						</div>

						<!-- From -->
						<div style="margin-bottom: 16px; padding: 12px; background-color: #ffffff; border-radius: 6px;">
							<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								From
							</p>
							<p style="margin: 0; padding: 0; color: #1f2937; font-size: 14px; font-weight: 600;">
								<?php echo esc_html($sender_name ?? 'Unknown'); ?>
							</p>
						</div>

						<!-- Message Content -->
						<div style="padding: 16px; background-color: #ffffff; border-radius: 6px; border-left: 4px solid #6366f1;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								Message
							</p>
							<p style="margin: 0; padding: 0; color: #374151; font-size: 14px; line-height: 22px;">
								<?php echo nl2br(esc_html($message ?? 'No message content')); ?>
							</p>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- CTA -->
	<tr>
		<td style="padding-bottom: 20px; text-align: center;">
			<p style="margin: 0 0 16px 0; padding: 0; color: #4a5568; font-size: 15px;">
				Click below to view the full conversation and reply:
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
