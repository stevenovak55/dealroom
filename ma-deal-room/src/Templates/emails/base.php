<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta name="x-apple-disable-message-reformatting">
	<title><?php echo isset($subject) ? esc_html($subject) : 'MA Deal Room'; ?></title>
	<!--[if mso]>
	<style type="text/css">
		body, table, td {font-family: Arial, Helvetica, sans-serif !important;}
	</style>
	<![endif]-->
</head>
<body style="margin: 0; padding: 0; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f4f4f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
	<!-- Wrapper Table -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4; padding: 20px 0;">
		<tr>
			<td align="center">
				<!-- Container Table -->
				<table border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">

					<!-- Header -->
					<tr>
						<td align="center" style="background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); padding: 40px 20px;">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tr>
									<td align="center">
										<h1 style="margin: 0; padding: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">
											MA Deal Room
										</h1>
										<p style="margin: 8px 0 0 0; padding: 0; color: #e6f3ff; font-size: 14px; font-weight: 400;">
											Real Estate Transaction Management
										</p>
									</td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Content -->
					<tr>
						<td style="padding: 40px 40px 20px 40px;">
							<?php if (isset($preheader) && $preheader): ?>
								<div style="display: none; max-height: 0; overflow: hidden; mso-hide: all;">
									<?php echo esc_html($preheader); ?>
								</div>
							<?php endif; ?>

							<?php echo $content ?? ''; ?>
						</td>
					</tr>

					<!-- CTA Button Section (if provided) -->
					<?php if (isset($cta_url) && $cta_url && isset($cta_text) && $cta_text): ?>
					<tr>
						<td align="center" style="padding: 0 40px 40px 40px;">
							<table border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td align="center" style="border-radius: 6px; background: linear-gradient(135deg, #0073aa 0%, #005a87 100%); box-shadow: 0 4px 12px rgba(0,115,170,0.3);">
										<a href="<?php echo esc_url($cta_url); ?>" target="_blank" style="display: inline-block; padding: 16px 40px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 16px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 6px; letter-spacing: 0.3px;">
											<?php echo esc_html($cta_text); ?>
										</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endif; ?>

					<!-- Divider -->
					<tr>
						<td style="padding: 0 40px;">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tr>
									<td style="border-top: 1px solid #e5e5e5;"></td>
								</tr>
							</table>
						</td>
					</tr>

					<!-- Footer -->
					<tr>
						<td align="center" style="padding: 30px 40px; background-color: #f9f9f9;">
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tr>
									<td align="center" style="padding-bottom: 15px;">
										<p style="margin: 0; padding: 0; color: #666666; font-size: 12px; line-height: 18px;">
											This is an automated notification from <strong>MA Deal Room</strong>
										</p>
									</td>
								</tr>
								<tr>
									<td align="center" style="padding-bottom: 15px;">
										<p style="margin: 0; padding: 0; color: #999999; font-size: 11px; line-height: 16px;">
											© <?php echo date('Y'); ?> MA Deal Room. All rights reserved.
										</p>
									</td>
								</tr>
								<?php if (isset($unsubscribe_url) && $unsubscribe_url): ?>
								<tr>
									<td align="center">
										<a href="<?php echo esc_url($unsubscribe_url); ?>" style="color: #0073aa; font-size: 11px; text-decoration: underline;">
											Unsubscribe from these notifications
										</a>
									</td>
								</tr>
								<?php endif; ?>
							</table>
						</td>
					</tr>

				</table>
				<!-- End Container Table -->
			</td>
		</tr>
	</table>
	<!-- End Wrapper Table -->
</body>
</html>
