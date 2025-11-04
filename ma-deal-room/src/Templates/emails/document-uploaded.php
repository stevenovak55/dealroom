<?php
/**
 * Document Uploaded Email Template
 * Variables: $document, $transaction, $uploaded_by, $recipient_name
 */

$subject = sprintf('[MA Deal Room] New Document: %s', $document->title ?? $document->file_name ?? 'Document');
$preheader = 'A new document has been uploaded to your transaction.';
$cta_url = admin_url('admin.php?page=ma-deal-room#/transactions/' . ($transaction->id ?? '') . '#documents');
$cta_text = 'View Document';

ob_start();
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">
	<!-- Greeting -->
	<tr>
		<td style="padding-bottom: 24px;">
			<h2 style="margin: 0; padding: 0; color: #2c3e50; font-size: 24px; font-weight: 600; line-height: 32px;">
				New Document Uploaded
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
				<?php if (isset($uploaded_by) && $uploaded_by): ?>
					<strong><?php echo esc_html($uploaded_by); ?></strong> has uploaded a new document to your transaction:
				<?php else: ?>
					A new document has been uploaded to your transaction:
				<?php endif; ?>
			</p>
		</td>
	</tr>

	<!-- Document Details Card -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 8px; border: 2px solid #f59e0b; overflow: hidden;">
				<tr>
					<td style="padding: 24px;">
						<!-- Document Icon & Name -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td style="width: 60px; vertical-align: top;">
									<div style="width: 48px; height: 48px; background-color: #ffffff; border-radius: 8px; display: flex; align-items: center; justify-content: center; text-align: center; line-height: 48px; font-size: 24px;">
										📄
									</div>
								</td>
								<td style="vertical-align: top;">
									<h3 style="margin: 0 0 4px 0; padding: 0; color: #78350f; font-size: 18px; font-weight: 700;">
										<?php echo esc_html($document->title ?? $document->file_name ?? 'Untitled Document'); ?>
									</h3>
									<?php if (isset($document->file_name) && isset($document->title) && $document->title !== $document->file_name): ?>
										<p style="margin: 0; padding: 0; color: #92400e; font-size: 13px; font-family: monospace;">
											<?php echo esc_html($document->file_name); ?>
										</p>
									<?php endif; ?>
								</td>
							</tr>
						</table>

						<!-- Document Details -->
						<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 16px; background-color: #ffffff; border-radius: 6px;">
							<tr>
								<td style="padding: 16px;">
									<!-- Document Type -->
									<?php if (isset($document->document_type) && $document->document_type): ?>
									<p style="margin: 0 0 12px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
										Document Type
									</p>
									<p style="margin: 0 0 16px 0; padding: 0;">
										<span style="display: inline-block; padding: 6px 14px; background-color: #dbeafe; color: #1e40af; font-size: 13px; font-weight: 600; border-radius: 12px;">
											<?php echo esc_html(ucwords(str_replace('_', ' ', $document->document_type))); ?>
										</span>
									</p>
									<?php endif; ?>

									<!-- Description -->
									<?php if (isset($document->description) && $document->description): ?>
									<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
										Description
									</p>
									<p style="margin: 0; padding: 12px; background-color: #f9fafb; border-radius: 4px; color: #374151; font-size: 14px; line-height: 20px;">
										<?php echo nl2br(esc_html($document->description)); ?>
									</p>
									<?php endif; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- Transaction Info -->
	<tr>
		<td style="padding-bottom: 24px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f4f6; border-radius: 6px;">
				<tr>
					<td style="padding: 16px;">
						<p style="margin: 0 0 4px 0; padding: 0; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase;">
							📍 Transaction
						</p>
						<p style="margin: 0; padding: 0; color: #1f2937; font-size: 16px; font-weight: 600;">
							<?php echo esc_html($transaction->property_address ?? 'N/A'); ?>
						</p>
						<?php if (isset($transaction->property_city) || isset($transaction->property_state)): ?>
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
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<!-- File Info -->
	<?php if (isset($document->file_size) || isset($document->uploaded_at)): ?>
	<tr>
		<td style="padding-bottom: 20px;">
			<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<?php if (isset($document->file_size) && $document->file_size): ?>
					<td style="width: 50%; padding-right: 8px;">
						<p style="margin: 0; padding: 0; color: #9ca3af; font-size: 12px;">
							<strong>Size:</strong> <?php echo esc_html($document->file_size); ?>
						</p>
					</td>
					<?php endif; ?>
					<?php if (isset($document->uploaded_at) && $document->uploaded_at): ?>
					<td style="width: 50%; padding-left: 8px;">
						<p style="margin: 0; padding: 0; color: #9ca3af; font-size: 12px;">
							<strong>Uploaded:</strong> <?php echo date('M j, Y g:i A', strtotime($document->uploaded_at)); ?>
						</p>
					</td>
					<?php endif; ?>
				</tr>
			</table>
		</td>
	</tr>
	<?php endif; ?>

	<!-- Call to Action -->
	<tr>
		<td>
			<p style="margin: 0; padding: 0; color: #4a5568; font-size: 16px; line-height: 24px;">
				Click the button below to view this document and all transaction files.
			</p>
		</td>
	</tr>
</table>

<?php
$content = ob_get_clean();
include __DIR__ . '/base.php';
?>
