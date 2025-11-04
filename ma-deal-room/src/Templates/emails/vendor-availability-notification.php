<?php
/**
 * Vendor Availability Notification Email Template
 * Sent to agent when vendor submits their availability
 *
 * Variables: $agent_name, $vendor_type, $vendor_name, $vendor_email,
 *            $property_address, $availability_slots, $dashboard_url
 */

$subject = 'Vendor Availability Received: ' . ($vendor_type ?? 'Service');
$preheader = 'A vendor has submitted their availability for ' . ($property_address ?? 'your property');
$cta_url = $dashboard_url ?? admin_url('admin.php?page=ma-deal-room');
$cta_text = 'Schedule Appointment';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				📆 Vendor Availability Received
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
				<strong><?php echo esc_html($vendor_name ?? 'A vendor'); ?></strong> has submitted their available time slots. Please review and schedule an appointment.
			</p>
		</td>
	</tr>

	<!-- Availability Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 8px; border: 2px solid #f59e0b; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Service Type -->
						<div style="margin-bottom: 20px;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #78350f; font-size: 13px; font-weight: 600; text-transform: uppercase;">
								Service Type
							</p>
							<p style="margin: 0; padding: 12px; background-color: #ffffff; border-radius: 6px; color: #92400e; font-size: 18px; font-weight: 700;">
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

						<!-- Available Time Slots -->
						<div style="padding: 16px; background-color: #ffffff; border-radius: 6px;">
							<p style="margin: 0 0 12px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								⏰ Available Time Slots
							</p>
							<?php if (!empty($availability_slots)): ?>
								<?php foreach ($availability_slots as $slot): ?>
									<div style="margin-bottom: 8px; padding: 10px; background-color: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px;">
										<p style="margin: 0; padding: 0; color: #92400e; font-size: 14px; font-weight: 600;">
											<?php echo esc_html($slot); ?>
										</p>
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p style="margin: 0; padding: 0; color: #6b7280; font-size: 14px;">
									No specific time slots provided - contact vendor to discuss availability
								</p>
							<?php endif; ?>
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
				<li style="margin-bottom: 8px;">Review the available time slots</li>
				<li style="margin-bottom: 8px;">Select a time that works for your transaction timeline</li>
				<li>Confirm the appointment with the vendor</li>
			</ul>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
