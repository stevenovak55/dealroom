<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Task Completed</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
	<div style="background-color: #d4edda; border-left: 4px solid #28a745; padding: 20px; margin-bottom: 20px;">
		<h2 style="margin-top: 0; color: #28a745;">Task Completed!</h2>
	</div>

	<div style="background-color: #ffffff; padding: 20px; border: 1px solid #dee2e6;">
		<p>Great news! The following task has been marked as completed:</p>

		<h3 style="margin-top: 20px;">{{task_title}}</h3>

		<div style="background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 4px;">
			<p style="margin: 5px 0;"><strong>Property:</strong> {{property_address}}</p>
			<p style="margin: 5px 0;"><strong>Completed By:</strong> {{completed_by}}</p>
			<p style="margin: 5px 0;"><strong>Completed At:</strong> {{completed_at}}</p>
		</div>

		<p>Your transaction is progressing smoothly. Keep up the great work!</p>

		<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
			<a href="{{dashboard_url}}" style="background-color: #28a745; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">View Transaction</a>
		</div>
	</div>

	<div style="margin-top: 20px; padding: 20px; background-color: #f8f9fa; text-align: center; font-size: 12px; color: #6c757d;">
		<p>This is an automated notification from MA Deal Room.</p>
		<p>&copy; 2025 MA Deal Room. All rights reserved.</p>
	</div>
</body>
</html>
