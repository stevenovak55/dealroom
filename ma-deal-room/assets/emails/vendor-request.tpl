<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Action Required - MA Deal Room</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
	<div style="background-color: #28a745; color: white; padding: 20px; text-align: center;">
		<h2 style="margin: 0;">MA Deal Room</h2>
		<p style="margin: 5px 0;">Action Required</p>
	</div>

	<div style="background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6;">
		<p>Hello,</p>

		<p>You have been requested to complete a task for a real estate transaction. Please click the button below to access the vendor portal and provide the required information.</p>

		<div style="margin: 30px 0; text-align: center;">
			<a href="{{signed_url}}" style="background-color: #28a745; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 4px; display: inline-block; font-size: 16px; font-weight: bold;">Access Vendor Portal</a>
		</div>

		<div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0;">
			<p style="margin: 0;"><strong>Important:</strong> This link will expire on {{expires_at}}.</p>
		</div>

		<p>If you have any questions, please contact the listing agent directly.</p>

		<p><small>If you're having trouble clicking the button, copy and paste this URL into your browser:<br>
		<span style="color: #007bff; word-break: break-all;">{{signed_url}}</span></small></p>
	</div>

	<div style="margin-top: 20px; padding: 20px; background-color: #f8f9fa; text-align: center; font-size: 12px; color: #6c757d;">
		<p>This is an automated message from MA Deal Room.</p>
		<p>&copy; 2025 MA Deal Room. All rights reserved.</p>
	</div>
</body>
</html>
