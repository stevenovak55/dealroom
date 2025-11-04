# Index Strategy and Query Optimization

## Overview

This document analyzes critical query patterns for the MA Deal Room plugin and provides EXPLAIN guidance for index optimization. The schema is designed to handle 10,000+ active transactions per account with sub-100ms query response times.

---

## Critical Query Patterns

### 1. Timeline Query - All Tasks for Transaction

**Use Case**: Display transaction timeline/checklist with all tasks ordered by due date

**Query**:
```sql
SELECT
    t.id,
    t.title,
    t.description,
    t.status,
    t.owner_role,
    t.due_at,
    t.completed_at,
    p.contact_name AS assigned_to,
    t.metadata
FROM wp_ma_deal_tasks t
LEFT JOIN wp_ma_deal_parties p ON t.assigned_party_id = p.id
WHERE t.transaction_id = ?
ORDER BY t.due_at ASC, t.sort_order ASC;
```

**Index Used**: `idx_transaction_status_due (transaction_id, status, due_at)`

**EXPLAIN Analysis**:
```
+----+-------------+-------+------+---------------------------+---------------------------+
| id | select_type | table | type | possible_keys             | key                       |
+----+-------------+-------+------+---------------------------+---------------------------+
|  1 | SIMPLE      | t     | ref  | idx_transaction_status_due| idx_transaction_status_due|
|  1 | SIMPLE      | p     | eq_ref| PRIMARY                   | PRIMARY                   |
+----+-------------+-------+------+---------------------------+---------------------------+
```

**Optimization Notes**:
- Composite index `(transaction_id, status, due_at)` provides covering index for filtering and sorting
- Average 20-30 tasks per transaction = minimal rows scanned
- LEFT JOIN on parties uses PRIMARY key lookup (very fast)
- **Expected Rows**: 20-30
- **Execution Time**: <10ms

**Scaling Considerations**:
- No change needed at 10K+ transactions (filtering by transaction_id is highly selective)
- Consider adding `sort_order` to index if custom sorting heavily used

---

### 2. Overdue Tasks - System-Wide

**Use Case**: Background job to identify overdue tasks across all active transactions

**Query**:
```sql
SELECT
    t.id,
    t.transaction_id,
    t.title,
    t.due_at,
    trans.property_address,
    trans.closing_date,
    trans.account_id
FROM wp_ma_deal_tasks t
INNER JOIN wp_ma_deal_transactions trans ON t.transaction_id = trans.id
WHERE t.status IN ('pending', 'in_progress')
  AND t.due_at < NOW()
  AND trans.status IN ('listing_active', 'under_agreement')
ORDER BY t.due_at ASC
LIMIT 100;
```

**Index Used**:
- `idx_status_due (status, due_at)` on tasks table
- `idx_status_closing (status, closing_date)` on transactions table

**EXPLAIN Analysis**:
```
+----+-------------+-------+-------+---------------------------+------------------+
| id | select_type | table | type  | possible_keys             | key              |
+----+-------------+-------+-------+---------------------------+------------------+
|  1 | SIMPLE      | t     | range | idx_status_due            | idx_status_due   |
|  1 | SIMPLE      | trans | eq_ref| PRIMARY,idx_status_closing| PRIMARY          |
+----+-------------+-------+-------+---------------------------+------------------+
```

**Optimization Notes**:
- Index `(status, due_at)` allows efficient range scan for overdue tasks
- At 10K transactions × 25 tasks = 250K rows, filtering by status + due_at reduces to ~100-500 rows
- JOIN to transactions uses PRIMARY key (optimal)
- Additional filter on trans.status applied post-JOIN (acceptable)
- **Expected Rows**: 100-500 overdue tasks system-wide
- **Execution Time**: <50ms

**Scaling Considerations**:
- At 100K+ transactions (2.5M+ task rows):
  - Consider adding composite index on transactions: `(status, id)` for faster JOIN filtering
  - Add caching layer for this query (refresh every 5 minutes)
  - Partition tasks table by `created_at` to archive old transactions

---

### 3. Reminder Queue - Next 100 Pending Reminders

**Use Case**: Cron job to process reminder queue every 5 minutes

**Query**:
```sql
SELECT
    r.id,
    r.task_id,
    r.transaction_id,
    r.recipient_email,
    r.recipient_phone,
    r.channel,
    r.scheduled_at,
    r.message_template,
    r.metadata,
    t.title AS task_title,
    trans.property_address
FROM wp_ma_deal_reminders r
INNER JOIN wp_ma_deal_tasks t ON r.task_id = t.id
INNER JOIN wp_ma_deal_transactions trans ON r.transaction_id = trans.id
WHERE r.status = 'pending'
  AND r.scheduled_at <= NOW()
ORDER BY r.scheduled_at ASC
LIMIT 100;
```

**Index Used**: `idx_status_scheduled (status, scheduled_at)`

**EXPLAIN Analysis**:
```
+----+-------------+-------+-------+----------------------+----------------------+
| id | select_type | table | type  | possible_keys        | key                  |
+----+-------------+-------+-------+----------------------+----------------------+
|  1 | SIMPLE      | r     | range | idx_status_scheduled | idx_status_scheduled |
|  1 | SIMPLE      | t     | eq_ref| PRIMARY              | PRIMARY              |
|  1 | SIMPLE      | trans | eq_ref| PRIMARY              | PRIMARY              |
+----+-------------+-------+-------+----------------------+----------------------+
```

**Optimization Notes**:
- Composite index `(status, scheduled_at)` perfectly optimizes queue processing
- Range scan on `scheduled_at <= NOW()` with LIMIT 100 = very fast
- JOINs use PRIMARY keys (optimal)
- **Expected Rows**: 100-500 pending reminders per query
- **Execution Time**: <20ms

**Scaling Considerations**:
- At scale (600K+ reminder rows), add cleanup job to archive `sent` reminders older than 90 days
- Consider separate table for `sent` reminders vs. active queue
- Add index on `(status, retry_count)` if retry logic becomes complex

---

### 4. Transaction Dashboard - Active Transactions with Task Counts

**Use Case**: Agent dashboard showing all active transactions with task progress

**Query**:
```sql
SELECT
    trans.id,
    trans.property_address,
    trans.property_city,
    trans.property_type,
    trans.status,
    trans.closing_date,
    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) AS completed_tasks,
    COUNT(CASE WHEN t.status IN ('pending', 'in_progress') THEN 1 END) AS active_tasks,
    COUNT(CASE WHEN t.status = 'pending' AND t.due_at < NOW() THEN 1 END) AS overdue_tasks
FROM wp_ma_deal_transactions trans
LEFT JOIN wp_ma_deal_tasks t ON trans.id = t.transaction_id
WHERE trans.account_id = ?
  AND trans.status IN ('listing_active', 'under_agreement')
GROUP BY trans.id
ORDER BY trans.closing_date ASC, trans.created_at DESC
LIMIT 50;
```

**Index Used**:
- `idx_account_status_closing (account_id, status, closing_date)` on transactions
- `idx_transaction_status_due (transaction_id, status, due_at)` on tasks

**EXPLAIN Analysis**:
```
+----+-------------+-------+------+---------------------------+-----------------------------+
| id | select_type | table | type | possible_keys             | key                         |
+----+-------------+-------+------+---------------------------+-----------------------------+
|  1 | SIMPLE      | trans | ref  | idx_account_status_closing| idx_account_status_closing  |
|  1 | SIMPLE      | t     | ref  | idx_transaction_status_due| idx_transaction_status_due  |
+----+-------------+-------+------+---------------------------+-----------------------------+
```

**Optimization Notes**:
- Composite index `(account_id, status, closing_date)` provides covering index for filtering AND sorting
- At 50 active transactions × 25 tasks = 1,250 rows scanned (acceptable)
- GROUP BY requires temporary table but with LIMIT 50 this is fast
- **Expected Rows**: 50 transactions × 25 tasks = 1,250 rows
- **Execution Time**: <30ms

**Scaling Considerations**:
- If dashboard becomes slow at high transaction counts:
  - Add materialized view with pre-computed task counts (refresh on task update)
  - Cache dashboard results per account (invalidate on transaction/task changes)
  - Consider adding `task_summary` JSON column to transactions table (denormalized counts)

---

### 5. Vendor Request Lookup by Token

**Use Case**: Public-facing vendor portal accessed via signed URL

**Query**:
```sql
SELECT
    vr.id,
    vr.task_id,
    vr.transaction_id,
    vr.vendor_type,
    vr.status,
    vr.scheduled_date,
    vr.token_expires_at,
    t.title AS task_title,
    t.description,
    trans.property_address,
    trans.property_city,
    trans.closing_date,
    p.contact_name AS agent_name,
    p.phone AS agent_phone,
    p.email AS agent_email
FROM wp_ma_deal_vendor_requests vr
INNER JOIN wp_ma_deal_tasks t ON vr.task_id = t.id
INNER JOIN wp_ma_deal_transactions trans ON vr.transaction_id = trans.id
LEFT JOIN wp_ma_deal_parties p ON trans.id = p.transaction_id AND p.role = 'seller_agent'
WHERE vr.token = ?
  AND vr.token_expires_at > NOW()
  AND vr.status NOT IN ('expired', 'cancelled');
```

**Index Used**: `idx_token (token)` [UNIQUE]

**EXPLAIN Analysis**:
```
+----+-------------+-------+-------+----------------------+----------------------+
| id | select_type | table | type  | possible_keys        | key                  |
+----+-------------+-------+-------+----------------------+----------------------+
|  1 | SIMPLE      | vr    | const | idx_token            | idx_token            |
|  1 | SIMPLE      | t     | eq_ref| PRIMARY              | PRIMARY              |
|  1 | SIMPLE      | trans | eq_ref| PRIMARY              | PRIMARY              |
|  1 | SIMPLE      | p     | ref   | idx_transaction_role | idx_transaction_role |
+----+-------------+-------+-------+----------------------+----------------------+
```

**Optimization Notes**:
- UNIQUE index on `token` provides instant lookup (const type = fastest)
- All JOINs use PRIMARY or indexed columns
- This is a single-row lookup = <5ms response time
- **Expected Rows**: 1
- **Execution Time**: <5ms

**Scaling Considerations**:
- No optimization needed - token lookup is O(1) with UNIQUE index
- Add monitoring for expired tokens (cleanup job)

---

## Index Effectiveness Summary

| Table | Index Name | Columns | Query Pattern | Selectivity | Notes |
|-------|------------|---------|---------------|-------------|-------|
| `wp_ma_deal_tasks` | `idx_transaction_status_due` | `(transaction_id, status, due_at)` | Timeline query | **Excellent** | Covering index for most task queries |
| `wp_ma_deal_tasks` | `idx_status_due` | `(status, due_at)` | Overdue tasks | **Good** | Range scan for background jobs |
| `wp_ma_deal_tasks` | `idx_due_at` | `(due_at)` | Calendar views | **Moderate** | Secondary index for date-based views |
| `wp_ma_deal_transactions` | `idx_account_status_closing` | `(account_id, status, closing_date)` | Dashboard | **Excellent** | Covering index for filtering + sorting |
| `wp_ma_deal_transactions` | `idx_status_closing` | `(status, closing_date)` | Global reports | **Good** | Cross-account queries |
| `wp_ma_deal_reminders` | `idx_status_scheduled` | `(status, scheduled_at)` | Queue processing | **Excellent** | Perfect for cron jobs |
| `wp_ma_deal_vendor_requests` | `idx_token` | `(token)` | Public vendor portal | **Perfect** | UNIQUE lookup O(1) |
| `wp_ma_deal_events` | `idx_transaction_created` | `(transaction_id, created_at)` | Audit trail | **Good** | Chronological event log |
| `wp_ma_deal_events` | `idx_account_created` | `(account_id, created_at)` | Account-wide audit | **Good** | Compliance reports |
| `wp_ma_deal_parties` | `idx_transaction_role` | `(transaction_id, role)` | Contact lookup | **Excellent** | Fast party retrieval |

---

## Performance Testing Recommendations

### 1. Load Test Data Volume

Create realistic test dataset:
- **Accounts**: 100 accounts
- **Transactions**: 10,000 active + 40,000 closed = 50,000 total
- **Tasks**: 50,000 × 25 = 1,250,000 rows
- **Reminders**: 1,250,000 × 3 = 3,750,000 rows (with cleanup of sent)
- **Events**: 2,000,000 audit events

### 2. Query Performance Benchmarks

Target response times:
- Timeline query (single transaction): **<10ms**
- Overdue tasks (system-wide): **<50ms**
- Reminder queue (next 100): **<20ms**
- Dashboard (50 transactions): **<30ms**
- Vendor token lookup: **<5ms**

### 3. EXPLAIN Plan Validation

Run EXPLAIN on all critical queries with production-scale data:

```sql
EXPLAIN SELECT ... -- Timeline query
EXPLAIN SELECT ... -- Overdue tasks
EXPLAIN SELECT ... -- Reminder queue
EXPLAIN SELECT ... -- Dashboard
EXPLAIN SELECT ... -- Vendor lookup
```

Check for:
- **type**: Should be `const`, `eq_ref`, or `ref` (avoid `ALL` table scans)
- **possible_keys**: Verify correct indexes available
- **key**: Verify optimizer chose the right index
- **rows**: Should match expected row counts above
- **Extra**: Watch for "Using temporary", "Using filesort" (acceptable for small result sets)

### 4. Slow Query Monitoring

Enable slow query log in production:

```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1; -- Log queries >100ms
SET GLOBAL log_queries_not_using_indexes = 'ON';
```

---

## Scaling Strategies

### At 50,000+ Transactions Per Account

**Issue**: Dashboard query scans too many rows
**Solution**: Add materialized task summary to transactions table

```sql
ALTER TABLE wp_ma_deal_transactions
ADD COLUMN task_summary JSON DEFAULT NULL
COMMENT 'Cached task counts: {completed: 12, active: 8, overdue: 2}';

-- Update trigger on task status change
CREATE TRIGGER update_task_summary
AFTER UPDATE ON wp_ma_deal_tasks
FOR EACH ROW
BEGIN
  -- Recalculate and update task_summary JSON
END;
```

### At 1M+ Audit Events

**Issue**: Events table becomes too large, slowing inserts
**Solution**: Partition by created_at

```sql
ALTER TABLE wp_ma_deal_events
PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
  PARTITION p202501 VALUES LESS THAN (202502),
  PARTITION p202502 VALUES LESS THAN (202503),
  -- ... monthly partitions
  PARTITION pmax VALUES LESS THAN MAXVALUE
);
```

### At 3M+ Reminders (with history)

**Issue**: Reminder queue queries slow due to large table
**Solution**: Archive sent reminders to separate table

```sql
CREATE TABLE wp_ma_deal_reminders_archive LIKE wp_ma_deal_reminders;

-- Monthly job to move sent reminders >90 days old
INSERT INTO wp_ma_deal_reminders_archive
SELECT * FROM wp_ma_deal_reminders
WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

DELETE FROM wp_ma_deal_reminders
WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### At 10M+ Tasks (Very High Scale)

**Issue**: Status/due_at queries slow across massive dataset
**Solution**: Add filtered index for active tasks only (MySQL 8.0.13+)

```sql
CREATE INDEX idx_active_tasks_due
ON wp_ma_deal_tasks (due_at)
WHERE status IN ('pending', 'in_progress');
```

---

## Index Maintenance

### Regular Maintenance Tasks

1. **Analyze Tables** (monthly):
   ```sql
   ANALYZE TABLE wp_ma_deal_tasks;
   ANALYZE TABLE wp_ma_deal_transactions;
   ANALYZE TABLE wp_ma_deal_reminders;
   ```

2. **Optimize Tables** (quarterly):
   ```sql
   OPTIMIZE TABLE wp_ma_deal_tasks;
   OPTIMIZE TABLE wp_ma_deal_events;
   ```

3. **Check Index Fragmentation**:
   ```sql
   SELECT table_name, index_name,
          round(stat_value * @@innodb_page_size / 1024 / 1024, 2) AS size_mb
   FROM mysql.innodb_index_stats
   WHERE database_name = 'wordpress'
     AND table_name LIKE 'wp_ma_deal_%'
     AND stat_name = 'size'
   ORDER BY size_mb DESC;
   ```

---

## Conclusion

The index strategy is designed for:
- **Current Scale**: 10,000 active transactions per account
- **Performance Target**: <50ms for all critical queries
- **Scaling Headroom**: 10x growth (100,000 transactions) without major changes

**Key Design Decisions**:
1. Composite indexes on high-cardinality columns first (transaction_id, account_id)
2. Denormalized transaction_id in reminders/vendor_requests for faster JOINs
3. JSON columns for flexible metadata without schema changes
4. Foreign keys with CASCADE for referential integrity
5. Separate indexes for different query patterns (timeline vs. dashboard vs. queue)

Monitor slow query log in production and adjust indexes based on actual usage patterns.
