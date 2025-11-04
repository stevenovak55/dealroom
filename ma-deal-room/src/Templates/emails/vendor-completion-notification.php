<?php
/**
 * Vendor Completion Notification Email Template
 * Sent to agent when vendor completes their work
 *
 * Variables: $agent_name, $vendor_type, $vendor_name, $vendor_email,
 *            $property_address, $completion_notes, $document_url, $dashboard_url
 */

$subject = 'Vendor Work Completed: ' . ($vendor_type ?? 'Service');
$preheader = 'A vendor has completed their work for ' . ($property_address ?? 'your property');
$cta_url = $dashboard_url ?? admin_url('admin.php?page=ma-deal-room');
$cta_text = 'View Transaction Details';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				✅ Vendor Work Completed
			</h2>
		</td>
	</tr>

	<!-- Main Content -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Hi <?php echo esc_html($agent_name ?? 'there'); ?>,
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Great news! <strong><?php echo esc_html($vendor_name ?? 'The vendor'); ?></strong> has completed their work.
			</p>
		</td>
	</tr>

	<!-- Completion Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 8px; border: 2px solid #3b82f6; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Service Type -->
						<div style="margin-bottom: 20px;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #1e3a8a; font-size: 13px; font-weight: 600; text-transform: uppercase;">
								Completed Service
							</p>
							<p style="margin: 0; padding: 12px; background-color: #ffffff; border-radius: 6px; color: #1e40af; font-size: 18px; font-weight: 700;">
								<?php echo esc_html($vendor_type ?? 'General Service'); ?>
							</p>
						</div>

						<!-- Property Address -->
						<div style="margin-bottom: 16px; padding: 16px; background-color: #ffffff; border-radius: 6px;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								📍 Property Address
							</p>
							<p style="margin: 0; padding: 0; color: #1f2937; font-size: 16px; font-weight: 600;">
								<?php echo esc_html($property_address ?? 'N/A'); ?>
							</p>
						</div>

						<!-- Completion Notes -->
						<?php if ($completion_notes): ?>
						<div style="margin-bottom: 16px; padding: 16px; background-color: #ffffff; border-radius: 6px; border-left: 3px solid #3b82f6;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								📝 Completion Notes
							</p>
							<p style="margin: 0; padding: 0; color: #374151; font-size: 14px; line-height: 20px;">
								<?php echo nl2br(esc_html($completion_notes)); ?>
							</p>
						</div>
						<?php endif; ?>

						<!-- Document Link -->
						<?php if ($document_url): ?>
						<div style="padding: 16px; background-color: #ffffff; border-radius: 6px; text-align: center;">
							<a href="<?php echo esc_url($document_url); ?>" style="display: inline-block; padding: 12px 24px; background-color: #3b82f6; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
								📄 View Completion Document
							</a>
						</div>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Vendor Contact Information -->
	<tr>
		<td style="padding-bottom: 20px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6; border-radius: 6px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0 0 12px 0; padding: 0; color: #374151; font-size: 14px; font-weight: 600;">
							Vendor Contact Information:
						</p>
						<p style="margin: 0 0 6px 0; padding: 0; color: #4b5563; font-size: 14px;">
							<strong>Name:</strong> <?php echo esc_html($vendor_name ?? 'Not provided'); ?>
						</p>
						<p style="margin: 0; padding: 0; color: #4b5563; font-size: 14px;">
							<strong>Email:</strong> <a href="mailto:<?php echo esc_attr($vendor_email); ?>" style="color: #0073aa; text-decoration: none;"><?php echo esc_html($vendor_email); ?></a>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Next Steps -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0 0 12px 0; padding: 0; color: #2c3e50; font-size: 17px; font-weight: 600;">
				What's Next:
			</p>
			<ul style="margin: 0; padding: 0 0 0 20px; color: #4a5568; font-size: 15px; line-height: 24px;">
				<li style="margin-bottom: 8px;">Review the completion notes and any documents provided</li>
				<li style="margin-bottom: 8px;">Rate your experience with this vendor (helps future transactions!)</li>
				<li>Contact the vendor if you have any questions or concerns</li>
			</ul>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
