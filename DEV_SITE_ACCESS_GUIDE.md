# MA Deal Room Development Site - Access Guide

## Site Information

| Property | Value |
|----------|-------|
| **Main Site** | http://localhost:8080 |
| **WordPress Admin** | http://localhost:8080/wp-admin |
| **REST API** | http://localhost:8080/wp-json/ma-deal-room/v1 |
| **phpMyAdmin** | http://localhost:8082 |

---

## Test User Credentials

### WordPress Admin User
- **Username:** admin
- **Email:** admin@example.com
- **Role:** Administrator
- **Note:** Default WordPress installation password

### Test Users
| Username | Email | Role |
|----------|-------|------|
| demouser | demo@example.com | Subscriber |
| Steven | steve@bmnboston.com | Subscriber |
| testuser | test@example.com | Subscriber |

---

## Database Access

### Direct MySQL Access
```bash
# Using Docker
docker exec ma-dealroom-db mysql -u dealroom -pdealroom_dev_pass ma_dealroom

# Or via phpMyAdmin: http://localhost:8082
# User: root / dealroom
# Password: root_secure_pass / dealroom_dev_pass
```

### Database Configuration
- **Host:** localhost:3307 (external) or db:3306 (internal)
- **Database:** ma_dealroom
- **User:** dealroom
- **Password:** dealroom_dev_pass

---

## Environment Details

### Infrastructure
- **WordPress Version:** 6.8.3
- **PHP Version:** 8.2 (Apache)
- **MySQL Version:** 8.0
- **Redis:** 7-alpine
- **Docker Network:** dealroom-network

### Container Details
```
WordPress Container:     ma-dealroom-wp   → localhost:8080
MySQL Container:         ma-dealroom-db   → localhost:3307
phpMyAdmin Container:    ma-dealroom-pma  → localhost:8082
Redis Container:         ma-dealroom-redis → localhost:6380
WP-CLI Container:        ma-dealroom-cli  (utility)
```

---

## Common Development Tasks

### Check Plugin Status
```bash
docker exec ma-dealroom-cli wp plugin list
docker exec ma-dealroom-cli wp plugin get ma-deal-room/ma-deal-room.php
```

### Check Database Tables
```bash
docker exec ma-dealroom-db mysql -u dealroom -pdealroom_dev_pass ma_dealroom -e "SHOW TABLES LIKE 'wp_ma_deal%';"
```

### View WordPress Error Logs
```bash
docker exec ma-dealroom-wp tail -f /var/www/html/wp-content/debug.log
```

### Run WP-CLI Commands
```bash
docker exec ma-dealroom-cli wp option get siteurl
docker exec ma-dealroom-cli wp user list
```

---

## Testing the Plugin

### API Testing

**Get all transactions (requires authentication):**
```bash
curl -X GET http://localhost:8080/wp-json/ma-deal-room/v1/transactions
# Returns 401 without authentication (expected)
```

**Get available REST endpoints:**
```bash
curl http://localhost:8080/wp-json/ma-deal-room/v1 | jq .
```

### Authentication Testing

**Check auth endpoints:**
```bash
curl -X POST http://localhost:8080/wp-json/ma-deal-room/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'
```

---

## Database Tables Overview

### Core Tables
- **wp_ma_deal_transactions** - Main transaction records
- **wp_ma_deal_tasks** - Task assignments and tracking
- **wp_ma_deal_documents** - Document storage metadata
- **wp_ma_deal_notifications** - Notification records

### User Management
- **wp_ma_deal_custom_users** - Custom user system
- **wp_ma_deal_user_roles** - User role assignments
- **wp_ma_deal_user_sessions** - Session tracking
- **wp_ma_deal_user_invitations** - User invitations

### Authentication
- **wp_ma_deal_custom_users** - Custom user accounts
- **wp_ma_deal_2fa_secrets** - Two-factor secrets
- **wp_ma_deal_email_verifications** - Email verification tokens
- **wp_ma_deal_password_resets** - Password reset tokens

### Integrations
- **wp_ma_deal_mls_config** - MLS configuration
- **wp_ma_deal_crm_config** - CRM configuration
- **wp_ma_deal_docusign_config** - DocuSign configuration
- **wp_ma_deal_docusign_envelopes** - DocuSign envelope tracking

### Support Tables
- **wp_ma_deal_task_definitions** - Task templates
- **wp_ma_deal_parties** - Transaction participants
- **wp_ma_deal_contacts** - Contact records
- **wp_ma_deal_notification_queue** - Email/SMS queue
- **wp_ma_deal_reminders** - Task reminders
- **wp_ma_deal_events** - Transaction timeline events
- **wp_ma_deal_rate_limits** - API rate limit tracking
- **wp_ma_deal_migrations** - Migration tracking

---

## Plugin File Structure

```
/wp-content/plugins/ma-deal-room/
├── ma-deal-room.php                 # Main plugin file
├── database/
│   └── migrations/                  # 29 database migrations
├── src/
│   ├── Admin/                       # Admin pages
│   ├── Core/                        # Core plugin logic
│   ├── Database/                    # Database layer
│   ├── REST/                        # REST API controllers
│   ├── Services/                    # Business logic
│   ├── Middleware/                  # Request middleware
│   ├── Models/                      # Data models
│   └── Repositories/                # Data access
├── assets/
│   ├── admin/                       # React dashboard
│   ├── emails/                      # Email templates
│   └── public/                      # Frontend assets
├── tests/                           # Test suite
└── vendor/                          # Dependencies
```

---

## Testing Endpoints

### Transaction Endpoints
```
GET    /ma-deal-room/v1/transactions
POST   /ma-deal-room/v1/transactions
GET    /ma-deal-room/v1/transactions/{id}
PUT    /ma-deal-room/v1/transactions/{id}
DELETE /ma-deal-room/v1/transactions/{id}
```

### Task Endpoints
```
GET    /ma-deal-room/v1/tasks
POST   /ma-deal-room/v1/tasks
GET    /ma-deal-room/v1/tasks/{id}
POST   /ma-deal-room/v1/tasks/{id}/complete
POST   /ma-deal-room/v1/tasks/{id}/skip
```

### Document Endpoints
```
GET    /ma-deal-room/v1/documents
POST   /ma-deal-room/v1/documents
GET    /ma-deal-room/v1/documents/{id}
GET    /ma-deal-room/v1/documents/{id}/download
```

### Authentication Endpoints
```
POST   /ma-deal-room/v1/auth/register
POST   /ma-deal-room/v1/auth/login
POST   /ma-deal-room/v1/auth/logout
POST   /ma-deal-room/v1/auth/refresh
POST   /ma-deal-room/v1/auth/verify-email
POST   /ma-deal-room/v1/auth/request-password-reset
POST   /ma-deal-room/v1/auth/reset-password
```

---

## Troubleshooting

### Site Not Responding
```bash
# Check container status
docker-compose ps

# Restart containers
docker-compose restart

# Check WordPress logs
docker exec ma-dealroom-wp tail -f /var/www/html/wp-content/debug.log
```

### Database Connection Issues
```bash
# Test database connectivity
docker exec ma-dealroom-db mysql -u dealroom -pdealroom_dev_pass -e "SELECT 1"

# Check MySQL logs
docker exec ma-dealroom-db tail -f /var/log/mysql/error.log
```

### Plugin Not Loading
```bash
# Verify plugin is active
docker exec ma-dealroom-cli wp plugin is-active ma-deal-room/ma-deal-room.php

# Check for PHP errors
docker exec ma-dealroom-wp php -l /var/www/html/wp-content/plugins/ma-deal-room/ma-deal-room.php
```

---

## Performance Testing

### Database Query Performance
```bash
# Check slow queries
docker exec ma-dealroom-db mysql -u dealroom -pdealroom_dev_pass ma_dealroom -e "SET GLOBAL slow_query_log = 'ON';"

# View query execution
docker exec ma-dealroom-cli wp db query "SELECT * FROM wp_ma_deal_transactions LIMIT 1;"
```

### Redis Cache Testing
```bash
# Check Redis connection
docker exec ma-dealroom-redis redis-cli ping

# Monitor Redis commands
docker exec ma-dealroom-redis redis-cli monitor
```

---

## Development Workflow

### Making Code Changes

1. **Edit plugin files locally:**
   ```bash
   # Files in /home/snova/projects/dealroom/ma-deal-room/
   # are automatically mounted in the container
   ```

2. **Verify changes in WordPress:**
   ```bash
   # WordPress will immediately reflect file changes
   # Refresh the admin page to see updates
   ```

3. **Clear cache if needed:**
   ```bash
   docker exec ma-dealroom-cli wp cache flush
   ```

### Testing API Changes

1. **Make API request:**
   ```bash
   curl http://localhost:8080/wp-json/ma-deal-room/v1/endpoint
   ```

2. **Check response:**
   ```bash
   # Verify proper HTTP status code
   # Check response payload
   # Verify error handling
   ```

---

## Important Notes

- ✅ All changes to plugin files are immediately reflected
- ✅ Database persists between container restarts
- ✅ Redis cache is available for performance testing
- ✅ phpMyAdmin provides GUI database access
- ✅ WP-CLI is available for command-line operations
- ✅ WordPress debug logging is enabled
- ✅ All 30 database tables are present and initialized
- ✅ All 100+ REST API endpoints are registered

---

## Next Steps

1. **Access the admin dashboard:**
   - Navigate to http://localhost:8080/wp-admin
   - Log in with admin credentials

2. **Check the plugin:**
   - Go to Plugins in the admin menu
   - Verify MA Deal Room is active

3. **Test the API:**
   - Use REST endpoints documented above
   - Test with curl, Postman, or your API client

4. **Review the comprehensive test report:**
   - Read `COMPREHENSIVE_TEST_REPORT.md` for full testing details

---

**Development Site Testing Status: ✅ COMPLETE & OPERATIONAL**
