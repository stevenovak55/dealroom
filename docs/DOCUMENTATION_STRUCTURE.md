# Documentation Structure Guide

This guide explains the documentation structure for the MA Deal Room V2.0.0 plugin.

## Root Level Documentation

**Essential Project Files:**
- `AI_MASTER.md` - Universal AI guidelines and project philosophy
- `CLAUDE.md` - Claude Code instructions and workflow
- `CODEX.md` - CodeX/Gemini model instructions
- `GEMINI.md` - Gemini model instructions and capabilities

**Setup & Deployment:**
- `INSTALLATION_INSTRUCTIONS.md` - How to install and configure the plugin
- `DEPLOYMENT_CHECKLIST.md` - Pre-deployment verification checklist
- `WP_PLUGIN_DEPLOYMENT_AGENT.md` - WordPress plugin deployment procedures

**Project Planning:**
- `DEVELOPMENT_ROADMAP.md` - Current and future development plans
- `DEVELOPMENT_ROADMAP_completed_tasks.md` - Completed milestone tracking
- `VERSION_HISTORY.md` - Version release history and changelog

**Documentation:**
- `README.md` - Main project readme
- `DEVELOPER_GUIDE.md` - Developer setup and contribution guide
- `USER_GUIDE.md` - End-user documentation

## Plugin Documentation (`/docs/`)

### `/api/`
REST API documentation:
- Endpoint references
- Authentication requirements
- Request/response examples
- Integration guides

### `/deployment/`
Deployment procedures and configurations:
- Production deployment guides
- CI/CD pipeline documentation
- Database migration guides

### `/integrations/`
Third-party integration documentation:
- DocuSign integration
- CRM systems (HubSpot, Salesforce)
- MLS data sources
- Email services (SendGrid, Twilio)

### `/performance/`
Performance optimization documentation:
- Caching strategies
- Query optimization
- Performance monitoring

### `/research/`
Research notes and architectural decisions:
- Feature research
- Technology selections
- Architecture rationale

## Archived Documentation (`./.archive/`)

Historical documentation has been organized into `./.archive/docs/` for reference:

### `/archive/docs/completed-phases/`
- Detailed completion reports for each development phase
- Component implementation summaries
- System integration documentation

### `/archive/docs/deployment-reports/`
- Historical deployment test results
- Release documentation for versions 1.0.0 - 2.0.0
- Integration test reports

### `/archive/docs/troubleshooting-fixes/`
- Past bug fixes and their solutions
- Troubleshooting guides for resolved issues
- Performance analysis and optimization records

### `/archive/docs/session-notes/`
- Development session progress notes
- Phase planning documents
- Task analysis and reorganization notes

## Quick Navigation

**I need to...**
- [Install the plugin](../INSTALLATION_INSTRUCTIONS.md)
- [Deploy to production](../DEPLOYMENT_CHECKLIST.md)
- [Understand the API](./api/)
- [Integrate with DocuSign](./integrations/)
- [Troubleshoot an issue](../../.archive/docs/troubleshooting-fixes/) (historical reference)
- [Check development progress](../../DEVELOPMENT_ROADMAP.md)
- [Set up development environment](../DEVELOPER_GUIDE.md)
- [Deploy using wp-plugin-deployment agent](../WP_PLUGIN_DEPLOYMENT_AGENT.md)

## Document Types

### Configuration Guides
Located in root. Essential for setup and deployment.

### API Documentation
Located in `/docs/api/`. Reference for developers building integrations.

### Integration Guides
Located in `/docs/integrations/`. Instructions for connecting external services.

### Development Notes
Located in `/.archive/docs/session-notes/`. Historical reference for implementation decisions.

### Troubleshooting
Located in `/.archive/docs/troubleshooting-fixes/`. Solutions for issues encountered during development.

## Best Practices

1. **For new developers:** Start with README.md, then INSTALLATION_INSTRUCTIONS.md, then DEVELOPER_GUIDE.md

2. **For deployments:** Follow DEPLOYMENT_CHECKLIST.md, then WP_PLUGIN_DEPLOYMENT_AGENT.md

3. **For feature implementation:** Check docs/integrations/ and DEVELOPMENT_ROADMAP.md

4. **For historical context:** Check .archive/docs/ with the appropriate category

5. **For API integration:** Reference docs/api/

## Maintenance

- Core documentation is kept concise and production-focused
- Historical documentation is preserved in `.archive/` for reference
- Development notes are archived when phases complete
- Deployment procedures remain at root for easy access

---

**Last Updated:** November 4, 2024
**Version:** 2.0.0 Production
