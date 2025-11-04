# MA Deal Room Database Documentation

## Overview

This directory contains the complete database architecture for the MA Deal Room WordPress plugin - a Massachusetts-focused real estate transaction management system with automated task tracking, reminders, and vendor coordination.

**Design Goals**:
- Multi-tenant architecture supporting thousands of agencies
- Scalable to 10,000+ active transactions per account
- Sub-100ms query response times for critical operations
- Compliance audit trail for regulatory requirements
- Flexible metadata storage for evolving requirements

---

## Table of Contents

1. [Schema Overview](#schema-overview)
2. [File Structure](#file-structure)
3. [Installation](#installation)
4. [Migration Process](#migration-process)
5. [Rollback Procedure](#rollback-procedure)
6. [Table Descriptions](#table-descriptions)
7. [Relationships](#relationships)
8. [Index Strategy](#index-strategy)
9. [Scaling Strategy](#scaling-strategy)
10. [Development Workflow](#development-workflow)

---

## Schema Overview

### Database Statistics

| Metric | Value |
|--------|-------|
| **Total Tables** | 8 custom tables + 1 migration tracking |
| **Storage Engine** | InnoDB |
| **Character Set** | utf8mb4 (full Unicode + emoji support) |
| **Foreign Keys** | 16 relationships with CASCADE/SET NULL |
| **Indexes** | 35+ covering indexes for performance |
| **Estimated Size** (10K transactions) | 2-5 GB |

### Table Prefix

All custom tables use the prefix: `wp_ma_deal_`

This follows WordPress naming conventions and prevents conflicts with core WordPress tables or other plugins.

---

## File Structure

```
ma-deal-room/database/
├── schema.sql                      # Complete schema definition
├── INDEX_STRATEGY.md               # Query optimization guide with EXPLAIN analysis
├── README.md                       # This file
├── seed.sql                        # Sample data for development
└── migrations/
    ├── 001_initial_schema.sql      # Idempotent migration
    └── rollback_001.sql            # Rollback script
```

---

## Installation

### Prerequisites

- MySQL 5.7+ or MariaDB 10.2+
- WordPress 5.8+ (for proper JSON column support)
- PHP 7.4+ (for plugin compatibility)
- Database user with CREATE/DROP TABLE privileges

### Initial Setup

**Option 1: Direct Schema Installation** (Development)

```bash
# From project root
mysql -u username -p wordpress_db < ma-deal-room/database/schema.sql
```

**Option 2: Migration System** (Production Recommended)

```bash
# Run initial migration
mysql -u username -p wordpress_db < ma-deal-room/database/migrations/001_initial_schema.sql

# Verify migration applied
mysql -u username -p wordpress_db -e "SELECT * FROM wp_ma_deal_migrations;"
```

**Option 3: WordPress Plugin Activation Hook** (Automated)

```php
// In ma-deal-room.php main plugin file

register_activation_hook(__FILE__, 'ma_deal_room_install_db');

function ma_deal_room_install_db() {
    global $wpdb;

    $schema_file = plugin_dir_path(__FILE__) . 'database/schema.sql';
    $schema_sql = file_get_contents($schema_file);

    // Execute schema
    $wpdb->query($schema_sql);

    // Record installation
    update_option('ma_deal_room_db_version', '1.0');
}
```

### Load Sample Data (Development Only)

```bash
mysql -u username -p wordpress_db < ma-deal-room/database/seed.sql
```

This creates:
- 1 sample account (Bay State Realty)
- 3 transactions (SFH with septic, Condo, Multifamily)
- 15 parties
- 24 tasks across different statuses
- 15 reminders
- 30 audit events

---

## Migration Process

### Migration Tracking

Migrations are tracked in `wp_ma_deal_migrations` table:

```sql
CREATE TABLE wp_ma_deal_migrations (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  migration_number VARCHAR(10) NOT NULL,
  migration_name VARCHAR(255) NOT NULL,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  rollback_available BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (id),
  UNIQUE KEY idx_migration_number (migration_number)
);
```

### Creating New Migrations

**Naming Convention**: `{number}_{description}.sql`

Example: `002_add_template_categories.sql`

**Template**:

```sql
-- Migration 002: Add Template Categories
-- Description: Adds category support to templates table
-- Idempotent: Yes
-- Rollback: See rollback_002.sql

-- Check if migration already applied
SET @migration_exists = (
  SELECT COUNT(*) FROM wp_ma_deal_migrations
  WHERE migration_number = '002'
);

-- Only proceed if not applied
-- ... migration SQL here ...

-- Record migration
INSERT INTO wp_ma_deal_migrations
(migration_number, migration_name, applied_at, rollback_available)
VALUES ('002', 'Add Template Categories', NOW(), TRUE)
ON DUPLICATE KEY UPDATE migration_name = migration_name;
```

### Running Migrations

**Sequential Execution**:

```bash
# Run migrations in order
mysql -u username -p wordpress_db < migrations/001_initial_schema.sql
mysql -u username -p wordpress_db < migrations/002_add_template_categories.sql
# ... etc
```

**Check Applied Migrations**:

```sql
SELECT migration_number, migration_name, applied_at
FROM wp_ma_deal_migrations
ORDER BY id ASC;
```

---

## Rollback Procedure

### Pre-Rollback Checklist

**WARNING: Rollback operations DELETE DATA. Always backup first.**

1. **Backup Database**:
   ```bash
   mysqldump -u username -p wordpress_db > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Verify Rollback File Exists**:
   ```bash
   ls -l migrations/rollback_001.sql
   ```

3. **Check Dependencies**:
   - Ensure no other plugins depend on these tables
   - Verify no active cron jobs are running

### Execute Rollback

```bash
# Rollback migration 001 (removes all 8 core tables)
mysql -u username -p wordpress_db < migrations/rollback_001.sql
```

**What Rollback Does**:
1. Drops all 8 custom tables in reverse dependency order
2. Removes migration record from `wp_ma_deal_migrations`
3. Restores database to pre-migration state

### Verify Rollback

```sql
-- Check tables are removed
SHOW TABLES LIKE 'wp_ma_deal_%';

-- Should return 0 rows (or only wp_ma_deal_migrations if other migrations exist)
```

### Rollback Recovery

If rollback fails mid-execution:

```bash
# Restore from backup
mysql -u username -p wordpress_db < backup_20251030_143000.sql

# Then retry rollback or manually clean up
```

---

## Table Descriptions

### 1. wp_ma_deal_accounts

**Purpose**: Multi-tenant organization/agency accounts

**Key Columns**:
- `owner_user_id`: Links to WordPress wp_users table
- `settings`: JSON - timezone, branding, notification preferences
- `subscription_tier`: Pricing tier (free, pro, enterprise)
- `subscription_expires_at`: Subscription expiration date

**Use Cases**:
- Separate data for different real estate agencies
- Account-level settings and branding
- Subscription/billing management

---

### 2. wp_ma_deal_transactions

**Purpose**: Core property transaction entity

**Key Columns**:
- `property_metadata`: JSON - flexible fields (has_septic, bedrooms, sqft, etc.)
- `property_year_built`: Critical for lead paint disclosure condition
- `ps_agreement_date`: Purchase & Sale date - anchor for relative due dates
- `closing_date`: Scheduled closing - primary timeline anchor
- `status`: Transaction lifecycle (prospect → listing_active → under_agreement → closed)

**Status Flow**:
```
prospect → listing_active → under_agreement → closed
                ↓                  ↓
            cancelled         cancelled
```

**Critical for**:
- Task due date calculations (e.g., "Closing-21d")
- Conditional task logic (e.g., "if property.year_built < 1978")
- Dashboard filtering and reporting

---

### 3. wp_ma_deal_parties

**Purpose**: Transaction contacts and vendors

**Roles**:
- Transaction parties: buyer, seller, buyer/seller attorneys, agents
- Vendors: inspector, appraiser, septic_inspector, fire_dept, hoa_manager, title_company

**Key Columns**:
- `metadata`: JSON - role-specific fields (bar_number for attorneys, license_number for inspectors)
- `role`: ENUM - ensures consistent party classification

**Use Cases**:
- Contact information for task assignments
- Vendor coordination and communication
- Automated email/SMS recipient resolution

---

### 4. wp_ma_deal_templates

**Purpose**: YAML task template storage

**Key Columns**:
- `template_yaml`: LONGTEXT - full YAML definition with conditions and dependencies
- `is_system`: System templates (read-only, shipped with plugin)
- `account_id`: NULL for system templates, account-specific otherwise
- `version`: Template versioning for change tracking

**Template YAML Structure**:
```yaml
version: 1.0
property_type: SFH

tasks:
  - key: title5_septic
    title: Schedule Title 5 Septic Inspection
    owner: seller
    due: Closing-90d
    mandatory: true
    applies_if: "property.has_septic == true"
    reminders: [-120d, -90d, -60d]
    citations:
      - https://www.mass.gov/guides/septic-system
```

**Use Cases**:
- Generate tasks when transaction created
- Support conditional task logic
- Customize workflows per property type

---

### 5. wp_ma_deal_tasks

**Purpose**: Instantiated tasks from templates

**Key Columns**:
- `task_key`: Unique identifier from template (e.g., "title5_septic")
- `due_at`: Calculated datetime based on template offset
- `depends_on_task_ids`: JSON array of task IDs (dependencies)
- `applies_if_condition`: Stored condition for audit trail
- `metadata`: JSON - citations, notes, external_url, estimated_duration

**Status Values**:
- `pending`: Not started
- `in_progress`: Being worked on
- `completed`: Finished
- `blocked`: Waiting on dependency
- `skipped`: Not applicable (condition failed)
- `cancelled`: Transaction cancelled

**Critical Indexes**:
- `idx_transaction_status_due (transaction_id, status, due_at)` - Timeline query
- `idx_status_due (status, due_at)` - Overdue task detection

---

### 6. wp_ma_deal_events

**Purpose**: Compliance audit log

**Key Columns**:
- `entity_type`: transaction, task, party, template, reminder, vendor_request
- `entity_id`: ID of changed entity
- `event_type`: created, updated, deleted, status_changed, completed, reminded, etc.
- `old_data` / `new_data`: JSON - before/after state
- `ip_address` / `user_agent`: Compliance tracking

**Use Cases**:
- Regulatory compliance (MA requires transaction records)
- Debugging ("Who changed this task?")
- Activity timeline for transaction
- Analytics and reporting

**Scaling**:
- High volume table (100+ events per transaction)
- Consider partitioning by `created_at` at scale
- Archive events older than 7 years (MA record retention)

---

### 7. wp_ma_deal_reminders

**Purpose**: Email/SMS reminder queue

**Key Columns**:
- `scheduled_at`: When to send reminder
- `channel`: email, sms, or both
- `status`: pending, sent, failed, cancelled
- `retry_count` / `max_retries`: Failure handling
- `transaction_id`: Denormalized for query performance

**Queue Processing**:
```sql
-- Cron job runs every 5 minutes
SELECT * FROM wp_ma_deal_reminders
WHERE status = 'pending'
  AND scheduled_at <= NOW()
ORDER BY scheduled_at ASC
LIMIT 100;
```

**Scaling**:
- Archive sent reminders older than 90 days
- Consider separate active/archive tables at high volume

---

### 8. wp_ma_deal_vendor_requests

**Purpose**: External vendor scheduling with signed URLs

**Key Columns**:
- `token`: Unique 64-character signed URL token
- `token_expires_at`: URL expiration (typically 30-60 days)
- `vendor_type`: fire_dept, septic_inspector, hoa_manager, etc.
- `scheduled_date` / `scheduled_time`: Vendor's scheduled appointment
- `document_url`: Uploaded certificate/report

**Public Portal Flow**:
1. System generates signed URL: `https://dealroom.com/vendor/{token}`
2. Email sent to vendor (fire dept, HOA, inspector)
3. Vendor clicks link → public portal (no login required)
4. Vendor schedules appointment or uploads certificate
5. System updates task status automatically

**Security**:
- UNIQUE index on `token` prevents collisions
- Token expiration enforced
- No sensitive data exposed in public view

---

## Relationships

### Entity Relationship Diagram

```
wp_ma_deal_accounts (1)
    ├─→ (N) wp_ma_deal_transactions
    ├─→ (N) wp_ma_deal_templates
    └─→ (N) wp_ma_deal_events

wp_ma_deal_transactions (1)
    ├─→ (N) wp_ma_deal_parties
    ├─→ (N) wp_ma_deal_tasks
    ├─→ (N) wp_ma_deal_events
    ├─→ (N) wp_ma_deal_reminders
    └─→ (N) wp_ma_deal_vendor_requests

wp_ma_deal_tasks (1)
    ├─→ (N) wp_ma_deal_reminders
    ├─→ (N) wp_ma_deal_vendor_requests
    └─→ (1) wp_ma_deal_parties [optional assignment]

wp_ma_deal_templates (1)
    └─→ (N) wp_ma_deal_tasks [source template]
```

### Foreign Key Constraints

All foreign keys use:
- **ON DELETE CASCADE**: Child records deleted when parent deleted (transactions, tasks, etc.)
- **ON DELETE SET NULL**: Soft reference (template deletion doesn't delete tasks)

**Critical Cascade Paths**:
```
Account deleted → Transactions deleted → Tasks deleted → Reminders deleted
                                      → Parties deleted
                                      → Events deleted
```

---

## Index Strategy

See [INDEX_STRATEGY.md](INDEX_STRATEGY.md) for detailed query analysis and EXPLAIN plans.

### Most Critical Indexes

| Index | Table | Purpose | Cardinality |
|-------|-------|---------|-------------|
| `idx_transaction_status_due` | tasks | Timeline query | **Excellent** |
| `idx_status_scheduled` | reminders | Queue processing | **Excellent** |
| `idx_account_status_closing` | transactions | Dashboard | **Excellent** |
| `idx_token` | vendor_requests | Public portal | **Perfect** (UNIQUE) |

### Index Coverage

- **35+ indexes** across 8 tables
- **Composite indexes** on high-selectivity columns first
- **Covering indexes** for common query patterns
- **JSON indexing** not used (flexible metadata, infrequent filters)

---

## Scaling Strategy

### Current Capacity (No Changes Needed)

- **10,000 active transactions** per account
- **250,000 total tasks** per account
- **750,000 active reminders**
- **Sub-100ms** query response times

### At 50,000+ Transactions Per Account

**Optimization**: Add materialized task summary to transactions table

```sql
ALTER TABLE wp_ma_deal_transactions
ADD COLUMN task_summary JSON DEFAULT NULL
COMMENT 'Cached: {completed: 12, active: 8, overdue: 2}';

-- Update via trigger on task status change
CREATE TRIGGER update_task_summary AFTER UPDATE ON wp_ma_deal_tasks...
```

### At 1M+ Events (Audit Log)

**Optimization**: Partition by date

```sql
ALTER TABLE wp_ma_deal_events
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
  PARTITION p202501 VALUES LESS THAN (202502),
  PARTITION p202502 VALUES LESS THAN (202503),
  -- Monthly partitions...
);
```

### At 3M+ Reminders

**Optimization**: Archive sent reminders

```sql
-- Move sent reminders >90 days old to archive table
CREATE TABLE wp_ma_deal_reminders_archive LIKE wp_ma_deal_reminders;

INSERT INTO wp_ma_deal_reminders_archive
SELECT * FROM wp_ma_deal_reminders
WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Read Replicas (Very High Scale)

For read-heavy workloads at extreme scale:
- Master: Handles writes (tasks, events, reminders)
- Replica 1: Dashboard queries
- Replica 2: Reporting and analytics

---

## Development Workflow

### Local Development Setup

```bash
# 1. Clone repository
git clone <repo-url>
cd dealroom

# 2. Install schema
mysql -u root -p dealroom_dev < ma-deal-room/database/schema.sql

# 3. Load seed data
mysql -u root -p dealroom_dev < ma-deal-room/database/seed.sql

# 4. Verify installation
mysql -u root -p dealroom_dev -e "SELECT COUNT(*) FROM wp_ma_deal_transactions;"
# Should return: 3
```

### Schema Changes

**Process**:
1. Create new migration file: `migrations/00X_description.sql`
2. Create corresponding rollback: `migrations/rollback_00X.sql`
3. Test on local database
4. Update `INDEX_STRATEGY.md` if adding/modifying indexes
5. Update this README if table structure changes
6. Deploy to staging → production

**Migration Best Practices**:
- Always make migrations **idempotent** (use IF NOT EXISTS)
- Include rollback script
- Test with production-scale data (use seed data generator)
- Document breaking changes

### Testing Queries

```bash
# Enable query logging
mysql -u root -p dealroom_dev -e "SET GLOBAL slow_query_log = 'ON';"
mysql -u root -p dealroom_dev -e "SET GLOBAL long_query_time = 0.1;"

# Run test queries
mysql -u root -p dealroom_dev < tests/query_performance.sql

# Check slow query log
tail -f /var/log/mysql/slow-query.log
```

### Database Reset

```bash
# Nuclear option - destroys all data
mysql -u root -p dealroom_dev < migrations/rollback_001.sql
mysql -u root -p dealroom_dev < migrations/001_initial_schema.sql
mysql -u root -p dealroom_dev < seed.sql
```

---

## Backup and Recovery

### Backup Strategy

**Daily Automated Backup**:
```bash
#!/bin/bash
# cron: 0 2 * * * /path/to/backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u backup_user -p$BACKUP_PASS wordpress_db \
  --tables wp_ma_deal_* \
  --single-transaction \
  --quick \
  --lock-tables=false \
  | gzip > /backups/ma_deal_room_$DATE.sql.gz

# Retain 30 days
find /backups -name "ma_deal_room_*.sql.gz" -mtime +30 -delete
```

**Pre-Migration Backup**:
```bash
# Before running any migration
mysqldump -u username -p wordpress_db \
  --tables wp_ma_deal_* \
  > pre_migration_$(date +%Y%m%d_%H%M%S).sql
```

### Recovery

```bash
# Restore from backup
gunzip < /backups/ma_deal_room_20251030_020000.sql.gz | \
  mysql -u username -p wordpress_db
```

---

## Troubleshooting

### Common Issues

**Issue**: Foreign key constraint fails during migration

```sql
-- Solution: Disable FK checks temporarily
SET FOREIGN_KEY_CHECKS = 0;
-- Run migration
SET FOREIGN_KEY_CHECKS = 1;
```

**Issue**: Slow dashboard queries

```sql
-- Diagnosis: Check index usage
EXPLAIN SELECT ... -- your slow query

-- Solution: See INDEX_STRATEGY.md for optimization
```

**Issue**: JSON column errors on older MySQL

```
Error: Unknown data type: 'json'
```

**Solution**: Upgrade to MySQL 5.7+ or MariaDB 10.2+

**Issue**: Migration tracking table missing

```sql
-- Recreate manually
CREATE TABLE IF NOT EXISTS wp_ma_deal_migrations (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  migration_number VARCHAR(10) NOT NULL,
  migration_name VARCHAR(255) NOT NULL,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  rollback_available BOOLEAN NOT NULL DEFAULT TRUE,
  PRIMARY KEY (id),
  UNIQUE KEY idx_migration_number (migration_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Additional Resources

- [INDEX_STRATEGY.md](INDEX_STRATEGY.md) - Query optimization guide
- [RESEARCH.md](../../docs/RESEARCH.md) - MA real estate requirements
- [tasks-matrix.csv](../../docs/research/tasks-matrix.csv) - Task definitions

---

## Support

For database-related questions:
1. Check this README first
2. Review INDEX_STRATEGY.md for performance issues
3. Examine seed.sql for data examples
4. Consult migration files for schema history

---

**Database Version**: 1.0
**Last Updated**: 2025-10-30
**Maintained By**: MA Deal Room Development Team
