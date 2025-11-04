# MA Deal Room Plugin - Installation Instructions

## Plugin Information
- **File**: ma-deal-room.zip
- **Size**: 5.7 MB
- **Version**: 2.0.0
- **WordPress Tested**: 6.0+
- **PHP Required**: 8.0+
- **MySQL Required**: 8.0+

---

## Installation Steps

### Method 1: WordPress Admin Upload (Recommended)

1. **Download the plugin file**
   - File: `ma-deal-room.zip`
   - Location: `/home/snova/projects/dealroom/ma-deal-room.zip`

2. **Login to WordPress Admin**
   - Go to your WordPress admin dashboard
   - Navigate to: **Plugins → Add New**

3. **Upload Plugin**
   - Click **"Upload Plugin"** button at the top
   - Click **"Choose File"**
   - Select `ma-deal-room.zip`
   - Click **"Install Now"**

4. **Activate Plugin**
   - After installation completes, click **"Activate Plugin"**
   - The plugin will automatically:
     - Create 29 database tables
     - Run 24 migrations
     - Seed default data (templates, task categories)
     - Create a default account

5. **Verify Installation**
   - Go to **Plugins → Installed Plugins**
   - Confirm "MA Deal Room" is active
   - Look for green checkmark

---

### Method 2: Manual FTP/SFTP Upload

1. **Extract the zip file** on your computer

2. **Upload via FTP/SFTP**
   - Connect to your server via FTP/SFTP
   - Navigate to: `/wp-content/plugins/`
   - Upload the entire `ma-deal-room` folder
   - Ensure file permissions are correct (755 for folders, 644 for files)

3. **Activate in WordPress**
   - Login to WordPress Admin
   - Go to **Plugins → Installed Plugins**
   - Find "MA Deal Room" and click **"Activate"**

---

## Post-Installation Configuration

### 1. Required Settings

**WordPress Permalinks**:
- Go to **Settings → Permalinks**
- Select **"Post name"** or any option except "Plain"
- Click **"Save Changes"**
- This is REQUIRED for REST API to work

### 2. Email Configuration (Optional but Recommended)

The plugin needs email for:
- User registration verification
- Password resets
- Transaction notifications

**Install WP Mail SMTP** (Recommended):
```
1. Go to: Plugins → Add New
2. Search: "WP Mail SMTP"
3. Install and activate
4. Configure with your SMTP settings
```

### 3. Access the Dashboard

**Frontend Dashboard URL**:
```
https://yourdomain.com/agent-dashboard/
```

**Default Login Page**:
```
https://yourdomain.com/agent-dashboard/#/auth/login
```

---

## First-Time Setup

### Create Your First User

**Option 1: Via Registration Form**
```
1. Go to: https://yourdomain.com/agent-dashboard/#/auth/register
2. Fill out registration form
3. Check email for verification link
4. Verify email and login
```

**Option 2: Via WordPress Admin (Manual)**
```
1. Login to WordPress admin
2. Install "WP-CLI" or use database directly
3. Create user via REST API or database
```

### Test the Installation

Create a test transaction:
```
1. Login to agent dashboard
2. Click "New Transaction"
3. Fill out property details
4. Save
5. Verify transaction appears in dashboard
```

---

## Important Notes

### PHP Dependencies
- The plugin includes all dependencies via Composer
- NO additional PHP packages need to be installed
- If you see errors, ensure PHP 8.0+ is active

### Database Requirements
- MySQL 8.0+ or MariaDB 10.5+
- InnoDB storage engine (default)
- Foreign key support enabled

### File Permissions
```
Folders: 755 (rwxr-xr-x)
Files:   644 (rw-r--r--)
```

### Memory Requirements
- Minimum: 256MB PHP memory limit
- Recommended: 512MB+

---

## Troubleshooting

### Issue: "Plugin activation failed"
**Solution**: 
- Check PHP error logs
- Ensure PHP 8.0+ is installed
- Verify database credentials in wp-config.php

### Issue: "REST API not working"
**Solution**:
- Set WordPress permalinks (Settings → Permalinks)
- Check .htaccess file exists and is writable
- Flush permalinks

### Issue: "White screen after activation"
**Solution**:
- Increase PHP memory limit to 512MB
- Check PHP error logs
- Deactivate plugin via database if needed:
```sql
UPDATE wp_options 
SET option_value = '' 
WHERE option_name = 'active_plugins';
```

### Issue: "Database tables not created"
**Solution**:
- Check MySQL user has CREATE TABLE permissions
- Verify database connection
- Look for migration errors in debug.log

### Issue: "Email not sending"
**Solution**:
- Install WP Mail SMTP plugin
- Configure SMTP settings
- Test email functionality

---

## Support Files Included

- `README.md` - Plugin overview
- `database/migrations/` - All database migrations
- `docs/` - API documentation
- `tests/` - PHPUnit test files

---

## What Gets Created on Activation

### Database Tables (29):
- Accounts, Users, Transactions
- Tasks, Templates, Documents
- Notifications, Events, Contacts
- MLS, CRM, DocuSign integrations
- Rate limits, Sessions

### Default Data:
- 1 Default Account
- 7 Transaction Templates
- 14 Task Categories
- 276 Task Definitions

---

## Next Steps After Installation

1. **Create an Admin User** via registration
2. **Configure MLS Settings** (if using Bridge Interactive)
3. **Set up CRM Integration** (Salesforce/HubSpot - optional)
4. **Configure DocuSign** (optional)
5. **Customize Templates** for your workflow
6. **Invite Team Members** via user invitations

---

## Security Recommendations

1. **Use HTTPS** - SSL certificate required for production
2. **Strong Passwords** - Enforce via WordPress settings
3. **Regular Backups** - Database and files
4. **Update WordPress** - Keep core and plugins updated
5. **Limit Login Attempts** - Use security plugin

---

## Version Information

**Current Version**: 2.0.0 - Production Ready
**Last Updated**: November 4, 2025
**Compatibility**: WordPress 6.0+ / PHP 8.0+ / MySQL 8.0+

