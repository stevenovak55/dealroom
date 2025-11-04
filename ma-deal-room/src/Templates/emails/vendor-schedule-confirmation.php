<?php
/**
 * Vendor Schedule Confirmation Email Template
 * Sent to agent when vendor schedules an appointment
 *
 * Variables: $agent_name, $vendor_type, $vendor_name, $vendor_email, $vendor_phone,
 *            $property_address, $scheduled_datetime, $dashboard_url
 */

$subject = 'Vendor Scheduled: ' . ($vendor_type ?? 'Service');
$preheader = 'A vendor has scheduled their appointment for ' . ($property_address ?? 'your property');
$cta_url = $dashboard_url ?? admin_url('admin.php?page=ma-deal-room');
$cta_text = 'View Transaction Details';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				📅 Vendor Appointment Scheduled
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
				Good news! <strong><?php echo esc_html($vendor_name ?? 'A vendor'); ?></strong> has scheduled their appointment.
			</p>
		</td>
	</tr>

	<!-- Schedule Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border-radius: 8px; border: 2px solid #16a34a; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Service Type -->
						<div style="margin-bottom: 20px;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #14532d; font-size: 13px; font-weight: 600; text-transform: uppercase;">
								Service Type
							</p>
							<p style="margin: 0; padding: 12px; background-color: #ffffff; border-radius: 6px; color: #15803d; font-size: 18px; font-weight: 700;">
								<?php echo esc_html($vendor_type ?? 'General Service'); ?>
							</p>
						</div>

						<!-- Scheduled Date/Time -->
						<div style="margin-bottom: 16px; padding: 16px; background-color: #ffffff; border-radius: 6px;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								📅 Scheduled For
							</p>
							<p style="margin: 0; padding: 0; color: #1f2937; font-size: 20px; font-weight: 700;">
								<?php echo esc_html($scheduled_datetime ?? 'Not specified'); ?>
							</p>
						</div>

						<!-- Property Address -->
						<div style="padding: 16px; background-color: #ffffff; border-radius: 6px;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								📍 Property Address
							</p>
							<p style="margin: 0; padding: 0; color: #1f2937; font-size: 16px; font-weight: 600;">
								<?php echo esc_html($property_address ?? 'N/A'); ?>
							</p>
						</div>
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
						<p style="margin: 0 0 6px 0; padding: 0; color: #4b5563; font-size: 14px;">
							<strong>Email:</strong> <a href="mailto:<?php echo esc_attr($vendor_email); ?>" style="color: #0073aa; text-decoration: none;"><?php echo esc_html($vendor_email); ?></a>
						</p>
						<?php if ($vendor_phone): ?>
							<p style="margin: 0; padding: 0; color: #4b5563; font-size: 14px;">
								<strong>Phone:</strong> <?php echo esc_html($vendor_phone); ?>
							</p>
						<?php endif; ?>
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
				<li style="margin-bottom: 8px;">Add this appointment to your calendar</li>
				<li style="margin-bottom: 8px;">Confirm access arrangements for the property</li>
				<li>Follow up with the vendor if you need to make any changes</li>
			</ul>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
