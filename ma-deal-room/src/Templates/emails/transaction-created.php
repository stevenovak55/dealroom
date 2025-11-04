<?php
/**
 * Transaction Created Email Template
 * Variables: $transaction, $recipient_name, $recipient_role
 */

$subject = sprintf('[MA Deal Room] New Transaction Created: %s', $transaction->property_address ?? 'Property');
$preheader = 'A new real estate transaction has been created.';
$cta_url = admin_url('admin.php?page=ma-deal-room#/transactions/' . ($transaction->id ?? ''));
$cta_text = 'View Transaction';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				New Transaction Created
			</h2>
		</td>
	</tr>

	<!-- Main Content -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Hello <?php echo esc_html($recipient_name ?? 'there'); ?>,
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				A new real estate transaction has been created
				<?php if (isset($recipient_role) && $recipient_role): ?>
					and you have been added as <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $recipient_role))); ?></strong>.
				<?php else: ?>
					in MA Deal Room.
				<?php endif; ?>
			</p>
		</td>
	</tr>

	<!-- Transaction Details Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 8px; border: 2px solid #10b981; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Property Address -->
						<h3 style="margin: 0 0 8px 0; padding: 0; color: #065f46; font-size: 22px; font-weight: 700;">
							<?php echo esc_html($transaction->property_address ?? 'N/A'); ?>
						</h3>
						<p style="margin: 0 0 20px 0; padding: 0; color: #047857; font-size: 15px;">
							<?php
							$location_parts = array_filter([
								$transaction->property_city ?? null,
								$transaction->property_state ?? null,
								$transaction->property_zip ?? null
							]);
							echo esc_html(implode(', ', $location_parts));
							?>
						</p>

						<!-- Transaction Details Grid -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 6px;">
							<tr>
								<td style="padding: 16px; width: 50%; border-right: 1px solid #d1fae5;">
									<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
										Property Type
									</p>
									<p style="margin: 0; padding: 0; color: #1f2937; font-size: 15px; font-weight: 600;">
										<?php echo esc_html(ucfirst($transaction->property_type ?? 'N/A')); ?>
									</p>
								</td>
								<td style="padding: 16px; width: 50%;">
									<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
										Status
									</p>
									<p style="margin: 0; padding: 0;">
										<span style="display: inline-block; padding: 4px 12px; background-color: #fef3c7; color: #92400e; font-size: 13px; font-weight: 600; border-radius: 12px;">
											<?php echo esc_html(ucfirst($transaction->status ?? 'Active')); ?>
										</span>
									</p>
								</td>
							</tr>
						</table>

						<!-- Additional Details -->
						<?php if (isset($transaction->closing_date) && $transaction->closing_date): ?>
						<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 12px; background-color: #ffffff; border-radius: 6px;">
							<tr>
								<td style="padding: 16px;">
									<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
										📅 Closing Date
									</p>
									<p style="margin: 0; padding: 0; color: #1f2937; font-size: 16px; font-weight: 700;">
										<?php echo date('F j, Y', strtotime($transaction->closing_date)); ?>
									</p>
								</td>
							</tr>
						</table>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- What's Next -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0 0 12px 0; padding: 0; color: #2c3e50; font-size: 17px; font-weight: 600;">
				What's Next?
			</p>
			<ul style="margin: 0; padding: 0 0 0 20px; color: #4a5568; font-size: 15px; line-height: 24px;">
				<li style="margin-bottom: 8px;">Review transaction details and timeline</li>
				<li style="margin-bottom: 8px;">Check assigned tasks and upcoming deadlines</li>
				<li style="margin-bottom: 8px;">Upload required documents</li>
				<li>Coordinate with other parties involved</li>
			</ul>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
