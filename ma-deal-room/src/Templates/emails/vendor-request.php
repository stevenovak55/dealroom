<?php
/**
 * Vendor Request Email Template
 * Variables: $vendor_name, $vendor_email, $transaction, $service_type, $requester_name, $message
 */

$subject = sprintf('[MA Deal Room] Vendor Request: %s', $service_type ?? 'Service');
$preheader = sprintf('You have been requested for %s services', $service_type ?? 'vendor');
$cta_url = admin_url('admin.php?page=ma-deal-room#/transactions/' . ($transaction->id ?? ''));
$cta_text = 'View Request Details';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				New Vendor Service Request
			</h2>
		</td>
	</tr>

	<!-- Main Content -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Hello <?php echo esc_html($vendor_name ?? 'there'); ?>,
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				<?php if (isset($requester_name) && $requester_name): ?>
					<strong><?php echo esc_html($requester_name); ?></strong> has requested your services for an upcoming real estate transaction.
				<?php else: ?>
					You have been requested for a real estate transaction service.
				<?php endif; ?>
			</p>
		</td>
	</tr>

	<!-- Service Request Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 8px; border: 2px solid #f59e0b; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Service Type -->
						<div style="margin-bottom: 20px;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #78350f; font-size: 13px; font-weight: 600; text-transform: uppercase;">
								Service Requested
							</p>
							<p style="margin: 0; padding: 12px; background-color: #ffffff; border-radius: 6px; color: #92400e; font-size: 18px; font-weight: 700;">
								<?php echo esc_html($service_type ?? 'General Service'); ?>
							</p>
						</div>

						<!-- Transaction Details -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 6px;">
							<tr>
								<td style="padding: 16px;">
									<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
										📍 Property Address
									</p>
									<p style="margin: 0; padding: 0; color: #1f2937; font-size: 16px; font-weight: 600;">
										<?php echo esc_html($transaction->property_address ?? 'N/A'); ?>
									</p>
									<p style="margin: 4px 0 0 0; padding: 0; color: #6b7280; font-size: 14px;">
										<?php
										$location_parts = array_filter([
											$transaction->property_city ?? null,
											$transaction->property_state ?? null,
											$transaction->property_zip ?? null
										]);
										echo esc_html(implode(', ', $location_parts));
										?>
									</p>
								</td>
							</tr>
						</table>

						<!-- Property Type & Closing Date -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 12px;">
							<tr>
								<td style="width: 50%; padding-right: 6px;">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 6px;">
										<tr>
											<td style="padding: 12px;">
												<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase;">
													Property Type
												</p>
												<p style="margin: 0; padding: 0; color: #1f2937; font-size: 14px; font-weight: 600;">
													<?php echo esc_html(ucfirst($transaction->property_type ?? 'N/A')); ?>
												</p>
											</td>
										</tr>
									</table>
								</td>
								<td style="width: 50%; padding-left: 6px;">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 6px;">
										<tr>
											<td style="padding: 12px;">
												<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase;">
													Closing Date
												</p>
												<p style="margin: 0; padding: 0; color: #1f2937; font-size: 14px; font-weight: 600;">
													<?php echo isset($transaction->closing_date) ? date('M j, Y', strtotime($transaction->closing_date)) : 'TBD'; ?>
												</p>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>

						<!-- Custom Message -->
						<?php if (isset($message) && $message): ?>
						<div style="margin-top: 16px; padding: 16px; background-color: #ffffff; border-radius: 6px; border-left: 3px solid #0073aa;">
							<p style="margin: 0 0 8px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
								Additional Details
							</p>
							<p style="margin: 0; padding: 0; color: #374151; font-size: 14px; line-height: 20px;">
								<?php echo nl2br(esc_html($message)); ?>
							</p>
						</div>
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
				Next Steps:
			</p>
			<ol style="margin: 0; padding: 0 0 0 20px; color: #4a5568; font-size: 15px; line-height: 24px;">
				<li style="margin-bottom: 8px;">Review the transaction details</li>
				<li style="margin-bottom: 8px;">Contact the requester to discuss your availability and pricing</li>
				<li>Confirm your acceptance or propose alternative arrangements</li>
			</ol>
		</td>
	</tr>

	<!-- Contact Information -->
	<?php if (isset($requester_name) || isset($requester_email) || isset($requester_phone)): ?>
	<tr>
		<td style="padding-bottom: 20px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6; border-radius: 6px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0 0 12px 0; padding: 0; color: #374151; font-size: 14px; font-weight: 600;">
							Contact Information:
						</p>
						<?php if (isset($requester_name) && $requester_name): ?>
							<p style="margin: 0 0 6px 0; padding: 0; color: #4b5563; font-size: 14px;">
								<strong>Name:</strong> <?php echo esc_html($requester_name); ?>
							</p>
						<?php endif; ?>
						<?php if (isset($requester_email) && $requester_email): ?>
							<p style="margin: 0 0 6px 0; padding: 0; color: #4b5563; font-size: 14px;">
								<strong>Email:</strong> <a href="mailto:<?php echo esc_attr($requester_email); ?>" style="color: #0073aa; text-decoration: none;"><?php echo esc_html($requester_email); ?></a>
							</p>
						<?php endif; ?>
						<?php if (isset($requester_phone) && $requester_phone): ?>
							<p style="margin: 0; padding: 0; color: #4b5563; font-size: 14px;">
								<strong>Phone:</strong> <?php echo esc_html($requester_phone); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php endif; ?>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
