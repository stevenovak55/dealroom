# MA Deal Room WordPress Plugin

Massachusetts real estate transaction management system with automated task tracking, reminders, and vendor coordination.

## Overview

MA Deal Room is a production-ready WordPress plugin built with modern PHP architecture to streamline real estate transactions in Massachusetts. It provides automated task management, deadline tracking, reminder notifications, and vendor coordination for real estate agents and brokerages.

## Requirements

- **PHP**: 8.0 or higher
- **WordPress**: 6.0 or higher
- **MySQL**: 5.7+ or MariaDB 10.2+
- **Composer**: For dependency management

## Installation

### 1. Install Dependencies

```bash
cd ma-deal-room
composer install
```

### 2. Activate Plugin

Upload the `ma-deal-room` directory to `/wp-content/plugins/` and activate through the WordPress admin.

### 3. Run Database Migrations

Option A - Automatic (on activation):
- Migrations run automatically when plugin is activated

Option B - Manual (WP-CLI):
```bash
wp ma-deal migrate
```

### 4. Verify Installation

```bash
wp ma-deal migrate:status
```

## Architecture

### Directory Structure

```
ma-deal-room/
├── ma-deal-room.php          # Main plugin file
├── composer.json              # Composer dependencies
├── src/
│   ├── Core/                  # Core plugin infrastructure
│   │   ├── Plugin.php         # Singleton plugin class
│   │   ├── ServiceContainer.php
│   │   └── Hooks.php
│   ├── Database/
│   │   └── Migrator.php       # Database migration manager
│   ├── Models/                # Data models
│   ├── Repositories/          # Database access layer
│   ├── Services/              # Business logic
│   ├── REST/Controllers/      # REST API endpoints
│   ├── Admin/                 # WordPress admin integration
│   └── CLI/                   # WP-CLI commands
├── database/
│   ├── schema.sql             # Complete database schema
│   ├── migrations/            # Versioned migrations
│   └── seed.sql               # Sample data
└── assets/
    ├── emails/                # Email templates
    └── admin/                 # React admin UI (Phase 6)
```

### Design Patterns

- **Singleton Pattern**: Plugin core initialization
- **Service Container**: Dependency injection
- **Repository Pattern**: Database abstraction
- **PSR-4 Autoloading**: Modern PHP namespacing

## Features

### Current (Phase 4 - Backend Scaffold)

- ✅ Complete database schema with 8 tables
- ✅ PSR-4 autoloaded class structure
- ✅ Service container for dependency injection
- ✅ Repository pattern for database access
- ✅ REST API endpoints for all resources
- ✅ WP-CLI commands for background processing
- ✅ Database migration system
- ✅ Email service with template support
- ✅ WordPress admin menu structure

### Upcoming

- **Phase 5**: YAML template engine, task scheduling
- **Phase 6**: React admin UI, vendor portal
- **Phase 7**: Email/SMS notifications, reminder queue

## REST API

Base URL: `https://your-site.com/wp-json/ma-deal/v1/`

### Endpoints

**Transactions**
- `GET /transactions` - List transactions
- `GET /transactions/{id}` - Get transaction
- `POST /transactions` - Create transaction
- `PUT /transactions/{id}` - Update transaction
- `DELETE /transactions/{id}` - Delete transaction

**Tasks**
- `GET /tasks?transaction_id={id}` - List tasks
- `GET /tasks/{id}` - Get task
- `POST /tasks/{id}/complete` - Mark task complete
- `POST /tasks/{id}/skip` - Skip task

**Templates**
- `GET /templates` - List templates
- `GET /templates/{id}` - Get template

**Reminders**
- `GET /reminders/upcoming` - List upcoming reminders

**Vendor Portal** (Public)
- `GET /vendor/{token}` - Access vendor request
- `POST /vendor/{token}` - Update vendor request

### Authentication

All endpoints except vendor portal require WordPress authentication. Use standard WordPress REST API authentication methods:

- Cookie authentication (built-in)
- Application passwords (WP 5.6+)
- OAuth (via plugin)

## WP-CLI Commands

### Migrations

```bash
# Run pending migrations
wp ma-deal migrate

# Rollback last migration
wp ma-deal migrate:rollback

# Show migration status
wp ma-deal migrate:status
```

### Reminders

```bash
# Send pending reminders
wp ma-deal reminders:send

# Limit batch size
wp ma-deal reminders:send --limit=50
```

### Templates

```bash
# Sync system templates
wp ma-deal templates:sync

# List templates
wp ma-deal templates:list
```

### Queue Processing

```bash
# Run background queue
wp ma-deal queue:run
```

### Seed Data

```bash
# Load sample data (development only)
wp ma-deal seed
```

## Database Schema

8 custom tables with `wp_ma_deal_` prefix:

1. **accounts** - Multi-tenant organizations
2. **transactions** - Property transactions
3. **parties** - Transaction contacts
4. **templates** - Task templates
5. **tasks** - Instantiated tasks
6. **events** - Audit log
7. **reminders** - Reminder queue
8. **vendor_requests** - External vendor tasks

See `database/README.md` for detailed schema documentation.

## Development

### Local Setup

```bash
# Install dependencies
composer install

# Run migrations
wp ma-deal migrate

# Load sample data
wp ma-deal seed

# Check status
wp ma-deal migrate:status
```

### Code Standards

- PHP 8.0+ type hints everywhere
- PSR-4 autoloading
- WordPress coding standards (tabs for indentation)
- Docblocks for all classes and methods
- Security: sanitize inputs, escape outputs, use $wpdb->prepare()

### Testing

```bash
# Run PHPUnit tests (coming in Phase 5)
composer test
```

## Configuration

Plugin constants defined in `ma-deal-room.php`:

- `MA_DEAL_VERSION` - Plugin version
- `MA_DEAL_PATH` - Plugin directory path
- `MA_DEAL_URL` - Plugin directory URL

## Security

- All database queries use `$wpdb->prepare()` to prevent SQL injection
- REST API endpoints require proper authentication and capabilities
- Vendor portal uses signed URLs with expiration
- Sensitive data never exposed in public endpoints
- Input sanitization and output escaping throughout

## Performance

- Composite indexes on high-traffic queries
- Query result caching where appropriate
- Lazy loading of services via service container
- Background processing via WP-Cron and WP-CLI

## Support

For issues, feature requests, or questions:

1. Check this README
2. Review `database/README.md` for schema questions
3. Examine `docs/RESEARCH.md` for MA requirements
4. Contact the development team

## License

GPL-2.0-or-later

## Credits

Built for Massachusetts real estate professionals.

---

**Version**: 1.0.7
**Last Updated**: 2025-10-30
