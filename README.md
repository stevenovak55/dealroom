# MA Deal Room

A comprehensive, customizable sell-side transaction "deal room" for Massachusetts real estate agents with AI assistance, multi-tenant scale, and mobile app support.

[![PHP Tests](https://github.com/stevenovak55/dealroom/actions/workflows/test.yml/badge.svg)](https://github.com/stevenovak55/dealroom/actions/workflows/test.yml)
[![Frontend Lint & Build](https://github.com/stevenovak55/dealroom/actions/workflows/lint.yml/badge.svg)](https://github.com/stevenovak55/dealroom/actions/workflows/lint.yml)
[![codecov](https://codecov.io/gh/stevenovak55/dealroom/branch/main/graph/badge.svg)](https://codecov.io/gh/stevenovak55/dealroom)

> **GitHub Repository**: [stevenovak55/dealroom](https://github.com/stevenovak55/dealroom)

## Features

- **Smart Task Management**: Property-type-specific checklists with MA regulatory compliance
- **Automated Reminders**: Email & SMS notifications for critical deadlines
- **Template System**: YAML-based task templates for different property types (SFH, Condo, Multi-family)
- **Vendor Portal**: External vendor scheduling and document collection
- **Timeline Visualization**: Gantt-style transaction timeline with milestones
- **AI Assistance**: Intelligent deadline tracking and proactive nudges
- **Multi-tenant Ready**: Scalable architecture for thousands of accounts
- **Mobile-Ready API**: REST API designed for future mobile app integration

## Architecture

Built as a **WordPress plugin** for rapid adoption within existing agent tech stacks:

- **Custom Database Tables**: High-performance schema with proper indexing
- **REST API**: `/wp-json/ma-deal/v1/*` endpoints for all operations
- **React Admin UI**: Modern SPA for intuitive user experience
- **Background Workers**: WP-CLI commands for queue processing and reminders
- **Template DSL**: Flexible YAML-based task definition system

See `docs/DECISIONS.md` for Architecture Decision Records.

## Massachusetts Compliance

Built-in templates cover mandatory seller-side requirements:

- **Title 5 Septic Inspections** (310 CMR 15.000)
- **Smoke & Carbon Monoxide Certificates** (M.G.L. c.148 §26F)
- **Lead Paint Disclosure** (105 CMR 460)
- **Agency Disclosure** (Massachusetts mandatory timing)
- **Condo 6(d) Certificates** (c.183A §6(d))
- Municipal requirements (lien certs, water readings, etc.)

See `docs/RESEARCH.md` for detailed regulatory research with citations.

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Node.js 18+ (for React development)
- PHP 8.2+ (for local development without Docker)
- Composer (PHP dependency manager)

### Installation

1. **Clone and configure**:
```bash
git clone <repository-url> ma-deal-room
cd ma-deal-room
cp .env.example .env
```

2. **Start Docker environment**:
```bash
docker-compose up -d
```

This starts:
- WordPress at http://localhost:8080
- MySQL database
- Redis cache
- PhpMyAdmin at http://localhost:8081

3. **Install WordPress**:
Visit http://localhost:8080 and complete the WordPress installation wizard.

4. **Activate plugin**:
```bash
# Via WP-CLI
docker-compose exec wpcli wp plugin activate ma-deal-room

# Or via WordPress admin:
# Navigate to Plugins → Installed Plugins → Activate "MA Deal Room"
```

5. **Run database migrations**:
```bash
docker-compose exec wpcli wp ma-deal migrate
```

6. **Seed demo data** (optional):
```bash
docker-compose exec wpcli wp ma-deal seed
```

7. **Access admin**:
Navigate to WordPress Admin → MA Deal Room

### Development Workflow

#### Working with PHP

```bash
# Install PHP dependencies
cd ma-deal-room
composer install

# Run tests
composer test

# Code formatting
composer format

# Static analysis
composer analyze
```

#### Working with React Admin

```bash
# Install dependencies
cd ma-deal-room/assets/admin
npm install

# Development mode (hot reload)
npm run dev

# Production build
npm run build

# Run tests
npm test

# Linting
npm run lint
```

#### Background Jobs

```bash
# Process reminder queue
docker-compose exec wpcli wp ma-deal reminders:send

# Run general queue
docker-compose exec wpcli wp ma-deal queue:run

# Reload templates from YAML files
docker-compose exec wpcli wp ma-deal templates:sync
```

#### System Cron Setup (Production)

Add to your system crontab:
```cron
# Check and send reminders every hour
0 * * * * docker-compose -f /path/to/dealroom/docker-compose.yml exec -T wpcli wp ma-deal reminders:send

# Process general queue every 15 minutes
*/15 * * * * docker-compose -f /path/to/dealroom/docker-compose.yml exec -T wpcli wp ma-deal queue:run
```

See `docs/CRON.md` for detailed scheduling recommendations.

## Project Structure

```
ma-deal-room/
├── docs/                      # Documentation
│   ├── RESEARCH.md           # MA legal requirements with citations
│   ├── DECISIONS.md          # Architecture Decision Records
│   ├── API.md                # REST API reference
│   ├── TEMPLATES.md          # Template DSL documentation
│   ├── ROADMAP.md            # Future milestones
│   └── CHANGELOG.md          # User-facing changes
├── templates/                 # YAML task templates
│   ├── sfh_city_water.yaml
│   ├── sfh_septic.yaml
│   ├── condo.yaml
│   └── multifamily.yaml
├── ma-deal-room/             # WordPress plugin
│   ├── ma-deal-room.php      # Plugin bootstrap
│   ├── composer.json
│   ├── src/                  # PHP source
│   │   ├── Core/
│   │   ├── Database/
│   │   ├── Models/
│   │   ├── Repositories/
│   │   ├── Services/
│   │   ├── REST/
│   │   ├── Admin/
│   │   └── CLI/
│   ├── assets/
│   │   ├── admin/           # React admin app
│   │   ├── vendor-portal/   # Public vendor interface
│   │   └── emails/          # Email templates
│   └── tests/
├── docker-compose.yml        # Docker environment
└── .env.example             # Environment configuration template
```

## Documentation

- **[API Reference](docs/API.md)**: Complete REST endpoint documentation
- **[Template DSL](docs/TEMPLATES.md)**: How to create custom task templates
- **[Research](docs/RESEARCH.md)**: MA regulatory requirements with citations
- **[ADRs](docs/DECISIONS.md)**: Architecture decisions and rationale
- **[Roadmap](docs/ROADMAP.md)**: Planned features and milestones
- **[Development](docs/DEVELOPMENT.md)**: Detailed development guide

## Testing

```bash
# PHP Unit tests
cd ma-deal-room
composer test

# PHP Integration tests
composer test:integration

# React tests
cd ma-deal-room/assets/admin
npm test

# E2E tests
npm run test:e2e
```

## Multi-AI Development

This project uses a multi-AI orchestration system:

- **Claude (Boss)**: Project orchestration, planning, documentation
- **claude-db**: Database design, migrations, query optimization
- **gemini-implementer**: Large-scale PHP implementation
- **codex-ui**: UI/UX and React components

See `AI_MASTER.md`, `CLAUDE.md`, `GEMINI.md`, and `CODEX.md` for AI-specific instructions.

## Contributing

See `docs/DEVELOPMENT.md` for development guidelines and workflow.

## License

[License TBD]

## Support

For issues, questions, or feature requests, please file an issue in the project repository.

---

🤖 Built with AI assistance using multi-agent orchestration
