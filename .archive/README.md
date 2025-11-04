# Archive Directory

This directory contains archived files that were removed from the main project directory to maintain a clean production structure. These files are organized by category and can be referenced for historical context or troubleshooting.

## Directory Structure

### `/releases/legacy/`
Contains previous versions of the plugin before v2.0.0:
- v1.0.0 through v1.0.7 releases (tar.gz and/or .zip formats)
- Legacy documentation for each version

**Note:** v2.0.0-production.zip is the current production release and remains in the root directory.

### `/backups/`
Database and plugin backups:
- `backup_pre_phase1.sql` - Database state before Phase 1 implementation
- `ma-deal-room-plugin.tar.gz` - Legacy plugin snapshot
- `ma-deal-room-nested-copy.zip` - Nested archive removed from plugin directory

### `/dev-scripts/`
Development and testing scripts that are no longer needed for production:

#### `/tests/`
Test scripts used during development phases (73 files)
- DocuSign integration tests
- MLS import/search tests
- CRM deployment tests
- Queue system tests
- Authentication tests
- Notification tests
- Email tests

#### `/migrations/`
Database migration helper scripts (10 files)
- Script runners for specific migration versions
- Migration diagnostics tools
- Database schema verification

#### `/setup/`
Configuration and setup scripts (15 files)
- MLS PIN configuration tools
- Email/SMS setup scripts
- Notification queue processors
- Task definition sync tools

### `/docs/archive/`
Documentation organized by phase and purpose:

#### `/completed-phases/`
Implementation summaries and completion reports for each development phase
- Complete system overviews (Auth, Backend, Frontend, CRM, MLS, DocuSign)
- Feature completion reports
- Component implementation guides

#### `/deployment-reports/`
Deployment test results, phase completion reports, and release documentation (120+ files)
- Phase 1-5 completion reports
- Deployment test reports with dates
- Plugin deployment test results
- Integration test reports
- Release guides (v1.0.1 through v2.0.0)

#### `/troubleshooting-fixes/`
Bug fixes, troubleshooting guides, and issue resolutions (30+ files)
- Critical fixes (403 errors, cache invalidation, etc.)
- CRM deployment issues and solutions
- MLS integration troubleshooting
- Database migration fixes
- Security audit reports
- Performance analysis documents

#### `/session-notes/`
Development session notes, progress tracking, and planning documents (40+ files)
- Session notes documenting development progress
- Phase planning and implementation plans
- Task reorganization documents
- Analysis reports
- Research documentation

## Accessing Historical Documentation

If you need to reference previous versions or understand past decisions:

1. **For v1.x release notes:** Check `/releases/legacy/`
2. **For Phase-specific implementation details:** See `/docs/archive/completed-phases/`
3. **For deployment procedures:** Refer to `/docs/archive/deployment-reports/`
4. **For troubleshooting issues:** Check `/docs/archive/troubleshooting-fixes/`
5. **For development history:** See `/docs/archive/session-notes/`

## Size Breakdown

- **Legacy Releases:** 522 MB
- **Development Scripts:** 620 KB
- **Archived Documentation:** ~8-10 MB
- **Total Archive Size:** ~530 MB

## Notes

- All archived development scripts remain intact and can be restored if needed
- Database backups are preserved for recovery purposes
- Legacy versions are kept for reference but are not used in production
- For current development, use the files in `/ma-deal-room/` directory
- Current production release: `ma-deal-room-v2.0.0-production.zip`

## When to Use Archive Files

✓ **When to reference:**
- Understanding historical implementation decisions
- Reviewing past deployment procedures
- Investigating issues that reference specific phases
- Comparing code changes between versions

✗ **When NOT to use:**
- Development of new features (use active codebase)
- Deployment to production (use v2.0.0-production.zip)
- Current testing (use integration test files in `/ma-deal-room/tests/`)

---

**Last Updated:** November 4, 2024
**Archive Created During:** V2.0.0 Production Cleanup
