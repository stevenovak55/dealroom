<?php
/**
 * Party Added Email Template
 * Variables: $party, $transaction, $added_by, $welcome_message
 */

$subject = sprintf('[MA Deal Room] You\'ve Been Added: %s', $transaction->property_address ?? 'Transaction');
$preheader = sprintf('You have been added as %s to a transaction.', ucwords(str_replace('_', ' ', $party->role ?? 'party member')));
$cta_url = admin_url('admin.php?page=ma-deal-room#/transactions/' . ($transaction->id ?? ''));
$cta_text = 'View Transaction';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				You've Been Added to a Transaction
			</h2>
		</td>
	</tr>

	<!-- Main Content -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Hello <?php echo esc_html($party->contact_name ?? 'there'); ?>,
			</p>
		</td>
	</tr>

	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				<?php if (isset($added_by) && $added_by): ?>
					<strong><?php echo esc_html($added_by); ?></strong> has added you to a real estate transaction in MA Deal Room.
				<?php else: ?>
					You have been added to a real estate transaction in MA Deal Room.
				<?php endif; ?>
			</p>
		</td>
	</tr>

	<!-- Role Badge -->
	<tr>
		<td align="center" style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
				<tr>
					<td style="padding: 12px 24px; background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); border-radius: 24px; box-shadow: 0 4px 12px rgba(0,115,170,0.3);">
						<p style="margin: 0; padding: 0; color: #ffffff; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
							Your Role
						</p>
						<p style="margin: 4px 0 0 0; padding: 0; color: #ffffff; font-size: 18px; font-weight: 700;">
							<?php echo esc_html(ucwords(str_replace('_', ' ', $party->role ?? 'Party Member'))); ?>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Transaction Details Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 8px; border: 2px solid #10b981; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Header -->
						<p style="margin: 0 0 16px 0; padding: 0; color: #065f46; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
							Transaction Details
						</p>

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

						<!-- Transaction Meta Grid -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<!-- Property Type -->
								<td style="width: 50%; padding-right: 6px; padding-bottom: 12px;">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 6px;">
										<tr>
											<td style="padding: 12px;">
												<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase;">
													Property Type
												</p>
												<p style="margin: 0; padding: 0; color: #1f2937; font-size: 15px; font-weight: 600;">
													<?php echo esc_html(ucfirst($transaction->property_type ?? 'N/A')); ?>
												</p>
											</td>
										</tr>
									</table>
								</td>
								<!-- Status -->
								<td style="width: 50%; padding-left: 6px; padding-bottom: 12px;">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 6px;">
										<tr>
											<td style="padding: 12px;">
												<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase;">
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
								</td>
							</tr>
							<!-- Closing Date (if available) -->
							<?php if (isset($transaction->closing_date) && $transaction->closing_date): ?>
							<tr>
								<td colspan="2" style="padding-top: 0;">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border-radius: 6px;">
										<tr>
											<td style="padding: 12px;">
												<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase;">
													📅 Expected Closing Date
												</p>
												<p style="margin: 0; padding: 0; color: #dc2626; font-size: 16px; font-weight: 700;">
													<?php echo date('l, F j, Y', strtotime($transaction->closing_date)); ?>
												</p>
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<?php endif; ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Welcome/Custom Message -->
	<?php if (isset($welcome_message) && $welcome_message): ?>
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #eff6ff; border-left: 4px solid #0073aa; border-radius: 4px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0 0 8px 0; padding: 0; color: #1e40af; font-size: 14px; font-weight: 600;">
							Message from <?php echo esc_html($added_by ?? 'the team'); ?>:
						</p>
						<p style="margin: 0; padding: 0; color: #3730a3; font-size: 14px; line-height: 20px;">
							<?php echo nl2br(esc_html($welcome_message)); ?>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<?php endif; ?>

	<!-- What You Can Do -->
	<tr>
		<td style="padding-bottom: 20px;">
			<p style="margin: 0 0 12px 0; padding: 0; color: #2c3e50; font-size: 17px; font-weight: 600;">
				What You Can Do:
			</p>
			<ul style="margin: 0; padding: 0 0 0 20px; color: #4a5568; font-size: 15px; line-height: 24px;">
				<li style="margin-bottom: 8px;">View transaction details and timeline</li>
				<li style="margin-bottom: 8px;">Access and upload documents</li>
				<li style="margin-bottom: 8px;">Track tasks and deadlines</li>
				<li>Communicate with other parties involved</li>
			</ul>
		</td>
	</tr>

	<!-- Call to Action -->
	<tr>
		<td>
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Click the button below to access the transaction and get started.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
