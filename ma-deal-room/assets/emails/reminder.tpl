<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Reminder</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #2563eb;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 8px 8px;
        }
        .task-title {
            font-size: 20px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .task-description {
            color: #6b7280;
            margin-bottom: 20px;
        }
        .info-box {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid #2563eb;
        }
        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-top: 4px;
        }
        .footer {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            background: #2563eb;
            color: white !important;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 15px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">Task Reminder</h1>
    </div>

    <div class="content">
        <div class="task-title">{{task_title}}</div>

        <div class="task-description">{{task_description}}</div>

        <div class="info-box">
            <div class="info-label">Property Address</div>
            <div class="info-value">{{property_address}}</div>
        </div>

        <div class="info-box">
            <div class="info-label">Due Date</div>
            <div class="info-value">{{due_date}}</div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{dashboard_url}}" class="button">View Transaction</a>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated reminder from MA Deal Room</p>
        <p>Transaction ID: #{{transaction_id}}</p>
    </div>
</body>
</html>
