# Architecture Decision Records (ADRs)

**Project**: MA Deal Room
**Purpose**: Document major architectural and design decisions
**Format**: Each ADR follows the pattern: Context, Decision, Consequences

---

## ADR-001: WordPress Plugin Architecture

**Date**: 2025-10-30
**Status**: ✅ Accepted
**Context**:

The MA Deal Room system needs to be adopted quickly by Massachusetts real estate agents who already have established technology stacks. The target market (real estate agents and brokerages) commonly uses WordPress for their websites and has existing WordPress hosting infrastructure.

**Alternatives Considered**:
1. **Standalone SaaS Application**: Build as Next.js/NestJS/Postgres standalone app
   - Pros: Complete control, modern tech stack, easier scaling
   - Cons: Requires new hosting, separate login system, longer adoption curve

2. **WordPress Plugin**: Build as WordPress plugin with custom tables
   - Pros: Leverages existing WP installations, familiar admin UI, rapid adoption
   - Cons: WordPress constraints, PHP ecosystem

3. **Hybrid Approach**: WordPress + separate API server
   - Pros: Best of both worlds
   - Cons: Complex deployment, higher maintenance burden

**Decision**:

Build as a **WordPress plugin** for Phase 1 with clean API boundaries to enable future standalone app.

**Rationale**:
- 40%+ of real estate agent websites run WordPress
- Existing hosting infrastructure (no new costs)
- Familiar WordPress admin interface reduces training
- WP-CLI enables background job processing
- REST API design allows future mobile app integration
- Custom tables avoid WordPress CPT limitations

**Architecture Principles**:
1. **Custom database tables** (not Custom Post Types) for performance at scale
2. **Clean REST API** design (mobile-app ready from day 1)
3. **Modern PHP** (8.0+, PSR-4, type hints, service container)
4. **Separation of concerns** (models, repositories, services, controllers)
5. **WordPress integration** via hooks, not core modifications

**Consequences**:

✅ **Positive**:
- Rapid adoption within existing agent tech stacks
- No additional hosting/infrastructure costs for early adopters
- Leverages WordPress ecosystem (plugins, themes, hosting)
- Familiar admin UI reduces training time
- WP-CLI perfect for cron job processing

⚠️ **Trade-offs**:
- Constrained to PHP (vs. Node.js/TypeScript)
- WordPress version compatibility maintenance
- Custom tables require manual migration system

📋 **Mitigation**:
- Clean API design allows standalone app in Phase 2
- Modern PHP practices (8.0+, Composer, PSR-4) minimize WP-specific code
- Repository pattern abstracts database access for future migration

---

## ADR-002: Custom Database Tables vs. Custom Post Types

**Date**: 2025-10-30
**Status**: ✅ Accepted
**Context**:

WordPress provides Custom Post Types (CPTs) as the "WordPress way" to store custom data. However, MA Deal Room requires complex relational data, high-performance queries, and multi-tenant isolation at scale (thousands of accounts, tens of thousands of transactions).

**Alternatives Considered**:
1. **Custom Post Types** with Post Meta
   - Pros: "WordPress way", uses wp_posts, meta queries
   - Cons: Poor performance at scale, limited query flexibility, no foreign keys

2. **Custom Tables** with proper schema
   - Pros: Full SQL power, indexes, foreign keys, clean schema
   - Cons: Manual migration system, outside WordPress norms

**Decision**:

Use **custom database tables** with proper relational schema.

**Technical Requirements**:
- 8 core tables with proper foreign keys
- Composite indexes on common query patterns: `(transaction_id, status, due_at)`
- JSON columns for flexible metadata
- InnoDB engine for transaction support
- utf8mb4 character set for international characters

**Performance Targets**:
- Timeline query (20-30 tasks): <10ms
- Overdue tasks (system-wide): <50ms
- Reminder queue (100 items): <20ms
- Dashboard (50 transactions): <30ms

**Consequences**:

✅ **Positive**:
- Sub-100ms query performance at 10K+ transactions
- Proper relational integrity (foreign keys, cascade delete)
- Flexible indexing strategy
- Clean schema matches domain model
- Scales to 100K+ transactions with documented optimization paths

⚠️ **Trade-offs**:
- Not "the WordPress way"
- Custom migration system required
- Manual schema versioning

📋 **Implementation**:
- Idempotent migrations (CREATE TABLE IF NOT EXISTS)
- Migration tracking table: `wp_ma_deal_migrations`
- Rollback scripts for each migration
- EXPLAIN analysis documented for critical queries

---

## ADR-003: Multi-Tenant Architecture Strategy

**Date**: 2025-10-30
**Status**: ✅ Accepted
**Context**:

MA Deal Room will be used by multiple real estate agencies/brokerages. Each needs data isolation while sharing the same WordPress installation for cost efficiency.

**Alternatives Considered**:
1. **Separate WordPress Instances** per agency
   - Pros: Complete isolation, simple
   - Cons: Expensive, hard to manage, no cross-agency features

2. **WordPress Multisite** with shared plugin
   - Pros: WordPress native, some isolation
   - Cons: Complex, performance issues, limited flexibility

3. **Single Database, Account-Based Isolation**
   - Pros: Efficient, flexible, scales well
   - Cons: Requires careful query filtering

**Decision**:

**Single database with account-based isolation** using `account_id` foreign key throughout.

**Implementation**:
- `wp_ma_deal_accounts` table with organization data
- All tables have `account_id` foreign key
- All queries automatically filtered by current account
- Account-level settings (timezone, branding, notifications)
- WordPress user association: `account_id` in user meta

**Security**:
- Every query filters by `account_id`
- REST API endpoints verify account access
- WP capabilities integrated with account permissions
- Account admins can't access other accounts' data

**Consequences**:

✅ **Positive**:
- Cost-efficient: Single WP installation serves thousands of agencies
- Scales horizontally (one DB, many accounts)
- Enables future cross-agency features (referral networks)
- Simple backup/restore (one database)

⚠️ **Trade-offs**:
- Must carefully filter all queries
- No physical data isolation between accounts
- Risk of accidental cross-account data leak if query bug

📋 **Mitigation**:
- Repository pattern enforces account filtering
- Unit tests verify account isolation
- Audit log tracks all data access
- Regular security reviews of account filtering logic

---

## ADR-004: REST API Design for Future Mobile App

**Date**: 2025-10-30
**Status**: ✅ Accepted
**Context**:

While Phase 1 targets WordPress admin users, the roadmap includes a mobile app for agents to manage transactions on-the-go. The REST API design must support both web and mobile clients from day 1.

**Alternatives Considered**:
1. **WordPress-Only API**: Tight coupling to WP admin
   - Pros: Simple, uses WP auth
   - Cons: Hard to use from mobile, WP-specific responses

2. **Clean REST API**: Standard JSON API with auth tokens
   - Pros: Platform-agnostic, mobile-ready, standard patterns
   - Cons: More work upfront

**Decision**:

Build **clean, mobile-ready REST API** from day 1.

**API Design Principles**:
1. **Namespace**: `/wp-json/ma-deal/v1/` (versioned)
2. **Resources**: RESTful resource naming (transactions, tasks, templates)
3. **Standard Verbs**: GET, POST, PUT, DELETE
4. **Authentication**: WordPress REST API nonce (Phase 1), JWT tokens (Phase 2)
5. **Responses**: Consistent JSON structure with success/error patterns
6. **Pagination**: Link headers, cursor-based for large lists
7. **Filtering**: Query parameters (`?status=active&sort=due_at`)

**Endpoints Designed**:
```
# Transactions
GET    /wp-json/ma-deal/v1/transactions
GET    /wp-json/ma-deal/v1/transactions/{id}
POST   /wp-json/ma-deal/v1/transactions
PUT    /wp-json/ma-deal/v1/transactions/{id}
DELETE /wp-json/ma-deal/v1/transactions/{id}

# Tasks
GET    /wp-json/ma-deal/v1/tasks?transaction_id={id}
POST   /wp-json/ma-deal/v1/tasks/{id}/complete
POST   /wp-json/ma-deal/v1/tasks/{id}/skip

# Templates
GET    /wp-json/ma-deal/v1/templates
GET    /wp-json/ma-deal/v1/templates/{id}

# Reminders
GET    /wp-json/ma-deal/v1/reminders/upcoming

# Vendor Portal (Public)
GET    /wp-json/ma-deal/v1/vendor/{token}
POST   /wp-json/ma-deal/v1/vendor/{token}
```

**Consequences**:

✅ **Positive**:
- Mobile app can use same API as web interface
- Platform-agnostic (iOS, Android, web, third-party integrations)
- Standard REST patterns easy for developers
- API versioning allows breaking changes
- Public vendor endpoints work without WP login

⚠️ **Trade-offs**:
- More upfront work vs. WP-only approach
- Need to maintain API compatibility

📋 **Future Enhancements**:
- JWT authentication for mobile (Phase 2)
- Webhooks for integrations (Phase 3)
- GraphQL for complex queries (Phase 4)

---

## ADR-005: Template DSL Design

**Date**: 2025-10-30
**Status**: ✅ Accepted
**Context**:

MA Deal Room must handle complex, property-type-specific transaction checklists with conditional logic, dependencies, and automated reminders. A flexible configuration system is needed that non-developers can modify.

**Alternatives Considered**:
1. **Hard-Coded PHP Logic**: Tasks in code
   - Pros: Fast, type-safe
   - Cons: Requires developer to add new tasks, not customizable

2. **Database-Driven**: Tasks in database with admin UI
   - Pros: Flexible, UI-driven
   - Cons: Complex UI, hard to version control, no easy import/export

3. **YAML Configuration DSL**: Tasks in YAML files
   - Pros: Version-controllable, readable, flexible, importable
   - Cons: Need parser, potential for syntax errors

**Decision**:

Build **YAML-based template DSL** with git-versionable configuration files.

**DSL Features**:
```yaml
- id: "task_id"
  title: "Task Title"
  mandatory: true
  applies_if: "property.year_built < 1978 AND property.type == 'SFH'"
  due: "Closing"
  due_offset: "-21d"
  assignee_role: "Seller"
  depends_on: ["other_task_id"]
  reminders:
    - offset: "-7d"
      channels: ["email", "sms"]
  citations:
    - url: "https://mass.gov/..."
      title: "MA Law Reference"
```

**Conditional Logic**: Simple expression language
- Property comparisons: `property.year_built < 1978`
- List membership: `property.city IN ['Boston', 'Lynn']`
- Logical operators: `AND`, `OR`
- Type checks: `property.type == 'SFH'`

**Relative Due Dates**: Anchor-based offsets
- Anchors: ListingDate, Offer, PS, Closing, FirstMeeting
- Offsets: `"-21d"` (21 days before), `"+7d"` (7 days after)

**Dependencies**: Task relationships
- Topological sort for execution order
- Circular dependency detection
- Blocked status if dependencies incomplete

**Consequences**:

✅ **Positive**:
- Templates in git (version control, code review, rollback)
- Readable by non-developers (YAML is human-friendly)
- Easy to create new property types
- Templates importable/exportable
- Community can share templates

⚠️ **Trade-offs**:
- YAML syntax errors possible
- Expression evaluator needs testing
- More complex than hard-coded logic

📋 **Mitigation**:
- YAML validation on load
- Clear error messages for syntax errors
- Template testing framework
- Documentation with examples

---

## ADR-006: Background Job Processing Strategy

**Date**: 2025-10-30
**Status**: ✅ Accepted
**Context**:

MA Deal Room needs to process reminders, send emails/SMS, and handle vendor requests asynchronously. WordPress Cron is unreliable (requires site traffic). A robust background job system is required.

**Alternatives Considered**:
1. **WordPress Cron**: Built-in cron
   - Pros: No setup required
   - Cons: Requires traffic, unreliable, no retry logic

2. **External Queue** (Redis/RabbitMQ)
   - Pros: Robust, scalable, reliable
   - Cons: Additional infrastructure, complex setup

3. **WP-CLI + System Cron**: Command-line workers
   - Pros: Reliable, simple, no extra infrastructure
   - Cons: Requires shell access

**Decision**:

Use **WP-CLI commands** triggered by **system cron** for reliability.

**Commands**:
```bash
wp ma-deal reminders:send  # Process reminder queue
wp ma-deal queue:run       # General job queue
wp ma-deal templates:sync  # Reload templates from files
```

**Cron Schedule**:
```cron
# Reminders every hour
0 * * * * wp ma-deal reminders:send

# General queue every 15 minutes
*/15 * * * * wp ma-deal queue:run

# Template sync daily at 2am
0 2 * * * wp ma-deal templates:sync
```

**Job Design**:
- Idempotent: Safe to run multiple times
- Batch processing: Process 100 items per run
- Retry logic: Exponential backoff for failures
- Timeout: Max 5 minutes per job run
- Logging: All job runs logged to events table

**Consequences**:

✅ **Positive**:
- Reliable: Runs on schedule, not dependent on site traffic
- Simple: No additional infrastructure required
- Testable: Can run commands manually for testing
- Scalable: Can increase frequency if needed

⚠️ **Trade-offs**:
- Requires shell access to server
- Not real-time (minimum 1-minute delay)
- No distributed job system (single server)

📋 **Future Scaling**:
- Phase 2: Add Redis job queue for real-time processing
- Phase 3: Distributed workers across multiple servers
- Phase 4: Webhooks for instant notifications

---

## ADR-007: Regulatory Compliance Documentation

**Date**: 2025-10-30
**Status**: ✅ Accepted
**Context**:

MA Deal Room must help agents comply with Massachusetts real estate regulations. Non-compliance can result in fines ($11K for lead paint errors), delays, or legal issues. The system must encode official requirements accurately.

**Decision**:

Embed **regulatory compliance** as a first-class feature with comprehensive citations.

**Implementation**:
1. **Research-Driven Development**: Deep research of MA regulations first
2. **Official Citations**: Every regulatory task links to official sources
3. **Compliance Templates**: Property-type-specific templates encode requirements
4. **Audit Trail**: Event log proves compliance (when tasks completed, by whom)
5. **Documentation**: RESEARCH.md with detailed regulatory analysis

**Regulatory Coverage**:
- ✅ Title 5 Septic (310 CMR 15.000)
- ✅ Smoke & CO Certificates (M.G.L. c.148 §26F/§26F½)
- ✅ Lead Paint Disclosure (105 CMR 460.720)
- ✅ Agency Disclosure (MA mandatory)
- ✅ Condo 6(d) Certificate (M.G.L. c.183A §6(d))
- ✅ Municipal Lien Certificate (M.G.L. c.60 §23)

**Citations Format**:
```yaml
citations:
  - url: "https://www.mass.gov/regulations/..."
    title: "310 CMR 15.000: Septic Systems (Title 5)"
```

**Consequences**:

✅ **Positive**:
- Agents avoid costly compliance mistakes
- Built-in reminders prevent missed deadlines
- Audit trail for disputes or investigations
- Official citations build trust
- Updates easy when regulations change

⚠️ **Maintenance Required**:
- Monitor MA regulation changes
- Update templates when laws change
- Verify citations remain valid

📋 **Ongoing**:
- Quarterly review of MA regulations
- Template versioning for regulation updates
- User notifications when templates updated

---

## ADR-008: Vendor Portal Public Access Design

**Date**: 2025-10-30
**Status**: ✅ Accepted
**Context**:

Vendors (fire departments, inspectors, HOAs, attorneys) need to respond to scheduling requests without creating WordPress accounts. The system must be secure but frictionless for external parties.

**Alternatives Considered**:
1. **Email-Only**: Vendors respond via email
   - Pros: Simple, no tech required
   - Cons: Manual processing, no structured data

2. **WordPress User Accounts**: Create accounts for vendors
   - Pros: Full authentication
   - Cons: Friction, password resets, unwieldy for one-time use

3. **Signed URL Tokens**: Time-limited public URLs
   - Pros: No login required, secure, expiring
   - Cons: Need careful implementation

**Decision**:

Use **signed URL tokens** with expiration for vendor portal access.

**Implementation**:
- Generate unique 64-character token per vendor request
- Token stored in `wp_ma_deal_vendor_requests.signed_url_token`
- Token expires after 7 days (configurable)
- URL format: `/wp-json/ma-deal/v1/vendor/{token}`
- Public endpoint (no authentication required)
- Validate token + expiration on each request

**Security**:
- Tokens are cryptographically random (not predictable)
- Single-use after vendor responds (or multi-use with rate limiting)
- Expiration prevents long-term access
- No sensitive data in URL (token is opaque identifier)
- HTTPS required for all requests

**Vendor Flow**:
1. Agent sends vendor request → System generates token
2. Email sent to vendor with link: `https://site.com/vendor-portal/{token}`
3. Vendor clicks link → Sees request details (property, preferred dates)
4. Vendor responds (accept/decline/propose times) → Submits form
5. System records response → Agent notified
6. Token expires or is revoked

**Consequences**:

✅ **Positive**:
- Zero friction for vendors (no account creation)
- Secure (time-limited, cryptographically random tokens)
- Professional vendor experience
- Automated tracking and notifications

⚠️ **Trade-offs**:
- Tokens can be forwarded (but expire quickly)
- No vendor history across requests (each token is per-request)

📋 **Future Enhancements**:
- Vendor accounts for repeat vendors (Phase 2)
- Vendor portal with calendar integration (Phase 3)
- SMS notifications to vendors (Phase 4)

---

## Summary of Key Decisions

| ADR | Decision | Impact | Status |
|-----|----------|--------|--------|
| 001 | WordPress Plugin Architecture | Rapid adoption, leverages existing infrastructure | ✅ Implemented |
| 002 | Custom Database Tables | Performance at scale, proper relational model | ✅ Implemented |
| 003 | Multi-Tenant Account-Based Isolation | Cost-efficient, scales to thousands of accounts | ✅ Implemented |
| 004 | Mobile-Ready REST API | Future-proof for mobile app development | ✅ Implemented |
| 005 | YAML Template DSL | Flexible, version-controllable task definitions | ✅ Implemented |
| 006 | WP-CLI Background Jobs | Reliable async processing without infrastructure | ✅ Implemented |
| 007 | Regulatory Compliance Focus | Avoid agent mistakes, build trust, provide value | ✅ Implemented |
| 008 | Signed URL Vendor Portal | Frictionless external party access | ✅ Implemented |

---

**Maintained by**: MA Deal Room Team
**Last Updated**: 2025-10-30
**Review Cycle**: Quarterly or when major decisions made
